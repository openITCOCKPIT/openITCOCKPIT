<?php
// Copyright (C) 2015-2025  it-novum GmbH
// Copyright (C) 2025-today AVENDIS GmbH
//
// This file is dual licensed
//
// 1.
//     This program is free software: you can redistribute it and/or modify
//     it under the terms of the GNU General Public License as published by
//     the Free Software Foundation, version 3 of the License.
//
//     This program is distributed in the hope that it will be useful,
//     but WITHOUT ANY WARRANTY; without even the implied warranty of
//     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//     GNU General Public License for more details.
//
//     You should have received a copy of the GNU General Public License
//     along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
// 2.
//     If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//     under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//     License agreement and license key will be shipped with the order
//     confirmation.

// 2.
//	If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//	under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//	License agreement and license key will be shipped with the order
//	confirmation.

declare(strict_types=1);

namespace App\Command;

use App\Model\Table\SystemsettingsTable;
use App\Model\Table\UsercontainerrolesTable;
use App\Model\Table\UserDefaultTemplatesTable;
use App\Model\Table\UsersTable;
use Cake\Cache\Cache;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use FreeDSx\Ldap\Exception\OperationException;
use itnovum\openITCOCKPIT\Core\Interfaces\CronjobInterface;
use itnovum\openITCOCKPIT\Ldap\LdapClient;

/**
 * LdapGroupImport command.
 */
class LdapUserImportCommand extends Command implements CronjobInterface {

    /**
     * Hook method for defining this command's option parser.
     *
     * @see https://book.cakephp.org/3.0/en/console-and-shells/commands.html#defining-arguments-and-options
     *
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
     * @return \Cake\Console\ConsoleOptionParser The built parser.
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser {
        return parent::buildOptionParser($parser);
    }

    /**
     * Implement this method with your command's logic.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return null|void|int The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io) {
        /** @var SystemsettingsTable $SystemsettingsTable */
        $SystemsettingsTable = TableRegistry::getTableLocator()->get('Systemsettings');

        if ($SystemsettingsTable->isLdapAuth() === false) {
            // No LDAP no LDAP sync :)
            return;
        }

        $this->syncLdapUsers($io);
        //$this->assignUserContainerRolesToUsers($io);
        Cache::clear('permissions');
    }

    /**
     * @param ConsoleIo $io
     * @return void
     * @throws \FreeDSx\Ldap\Exception\BindException
     * @throws OperationException
     */
    private function syncLdapUsers(ConsoleIo $io) {
        /** @var UserDefaultTemplatesTable $UserDefaultTemplatesTable */
        $UserDefaultTemplatesTable = TableRegistry::getTableLocator()->get('UserDefaultTemplates');
        $userDefaultTemplates = $UserDefaultTemplatesTable->getUserDefaultTemplatesForLdapUserImport();

        $ldapGroupsToUserDefaultTemplates = [];
        $userDefaultTemplatesWithIdAsIndex = [];

        foreach ($userDefaultTemplates as $userDefaultTemplate) {
            $userDefaultTemplatesWithIdAsIndex[$userDefaultTemplate['id']] = $userDefaultTemplate;
            foreach ($userDefaultTemplate['ldapgroups'] as $ldapGroup) {
                $ldapGroupsToUserDefaultTemplates[$ldapGroup['dn']]['templates'][$userDefaultTemplate['id']] = $userDefaultTemplate['id'];
            }
        }


        $userDefaultTemplatesWithIdAsIndex = Hash::remove($userDefaultTemplatesWithIdAsIndex, '{n}.ldapgroups');
        $userDefaultTemplatesWithIdAsIndex = Hash::remove($userDefaultTemplatesWithIdAsIndex, '{n}._matchingData');

        $userDefaultTemplatesForLdapUserImport = Hash::combine(
            $userDefaultTemplates,
            '{n}._matchingData.Ldapgroups.id',
            // We need the DN for the LDAP group to match it with the LDAP user memberOf attribute
            // The DN contains the pull path (e.g. CN=G_Role_Finance,OU=Groups,OU=Contoso,DC=ad,DC=openitcockpit,DC=com)
            // while the CN contains only the group name (e.g. G_Role_Finance)
            '{n}._matchingData.Ldapgroups.dn'
        );
        if (empty($userDefaultTemplatesWithIdAsIndex)) {
            $io->info('No user defaults with LDAP groups defined. LDAP user import not possible! ➜]');
            return;
        }

        /** @var UsersTable $UsersTable */
        $UsersTable = TableRegistry::getTableLocator()->get('Users');
        $existingUsers = $UsersTable->getUsersForLdapImport();
        $mailAdressesToCheck = Hash::extract($existingUsers, '{n}.email');

        $io->out('Scan for new LDAP users. This will take a while...');

        /** @var SystemsettingsTable $SystemsettingsTable */
        $SystemsettingsTable = TableRegistry::getTableLocator()->get('Systemsettings');

        /** @var UsercontainerrolesTable $UsercontainerrolesTable */
        $UsercontainerrolesTable = TableRegistry::getTableLocator()->get('Usercontainerroles');

        try {
            $LdapClient = LdapClient::fromSystemsettings($SystemsettingsTable->findAsArraySection('FRONTEND'));
            $usersFromLdap = $LdapClient->getUsersByGroupNames(
                '',
                true,
                $userDefaultTemplatesForLdapUserImport
            );
            $imported = 0;
            foreach ($usersFromLdap as $ldapUser) {
                if (!empty($mailAdressesToCheck) && in_array(strtolower($ldapUser['email']), $mailAdressesToCheck, true)) {
                    // User already exists in database
                    //$io->info(sprintf('⚠️ User already exists:  %s [%s]', $ldapUser['display_name'], strtolower($ldapUser['email'])));
                    continue;
                }
                if (empty($ldapUser['samaccountname'])) {
                    // Missing required field
                    $io->error('❗ Missing required field sAMAccountName (Security Account Manager Account Name)');
                    continue;
                }
                if (empty($ldapUser['memberof'])) {
                    // Missing for import required field
                    $io->error('❗ Missing required field memberof');
                    continue;
                }

                $data = [
                    'firstname'      => $ldapUser['givenname'],
                    'lastname'       => $ldapUser['sn'],
                    'email'          => $ldapUser['email'],
                    'phone'          => $ldapUser['telephonenumber'] ?? null,
                    'is_active'      => 1,
                    'samaccountname' => $ldapUser['samaccountname'],
                    'ldap_dn'        => $ldapUser['dn'],
                    'company'        => $ldapUser['company'] ?? null,
                    'department'     => $ldapUser['department'] ?? null,

                ];

                $UsersTable->getValidator()->remove('password');
                $UsersTable->getValidator()->remove('confirm_password');

                $matchedGroup = null;
                $matchedTemplateData = null;


                $userContainerRoleContainerPermissionsLdap = $UsercontainerrolesTable->getContainerPermissionsByLdapUserMemberOf(
                    $ldapUser['memberof']
                );

                foreach ($ldapGroupsToUserDefaultTemplates as $groupDn => $data) {
                    if (in_array($groupDn, $ldapUser['memberof'], true)) {
                        $matchedGroup = $groupDn;
                        $matchedTemplateData = $data;
                        break; // First match for template founded
                    }
                }

                if (!$matchedGroup) {
                    // no default template found for this user, skip it
                    $io->info(sprintf('⚠️ No matching default template found for user: %s [%s]', $ldapUser['display_name'], $matchedGroup));
                    continue;
                }

                $possibleUserDefaultTemplates = array_keys($matchedTemplateData['templates']);
                if (!empty($possibleUserDefaultTemplates)) {
                    $userDefaultTemlate = $userDefaultTemplatesWithIdAsIndex[$possibleUserDefaultTemplates[0]];
                    $io->info(sprintf('✅ Matching default template found for user: %s → %s [ID: %s]',
                            $ldapUser['display_name'],
                            $userDefaultTemlate['name'],
                            $userDefaultTemlate['id']
                        )
                    );
                    $containers = [];
                    if (!empty($userDefaultTemlate['user_containers'])) {
                        foreach ($userDefaultTemlate['user_containers'] as $userContainer) {
                            $containers[] = [
                                'id'        => $userContainer['id'],
                                '_joinData' => [
                                    'permission_level' => $userContainer['_joinData']['permission_level']
                                ]
                            ];
                        }
                    }
                    $data = [
                        'firstname'               => $ldapUser['givenname'],
                        'lastname'                => $ldapUser['sn'],
                        'email'                   => $ldapUser['email'],
                        'phone'                   => $ldapUser['telephonenumber'] ?? null,
                        'is_active'               => 1,
                        'samaccountname'          => $ldapUser['samaccountname'],
                        'company'                 => $ldapUser['company'] ?? null,
                        'department'              => $ldapUser['department'] ?? null,
                        'usercontainerroles_ldap' => [
                            '_ids' => []
                        ],
                        'containers'              => $containers
                    ];
                    $data = array_merge($data, $userDefaultTemlate);

                }
                foreach ($userContainerRoleContainerPermissionsLdap as $usercontainerroleId => $usercontainerrole) {
                    // Do not overwrite any manually user assignments
                    if (!isset($data['usercontainerroles'][$usercontainerroleId])) {
                        $data['usercontainerroles'][$usercontainerroleId] = [
                            'id'        => $usercontainerroleId,
                            '_joinData' => [
                                'through_ldap' => true // This got assigned automatically via LDAP
                            ]
                        ];
                    }
                }
                $userEntity = $UsersTable->newEmptyEntity();
                $userEntity = $UsersTable->patchEntity($userEntity, $data);
                $userEntity = $UsersTable->createUser(
                    $userEntity,
                    ['User' => $data],
                    0
                );

                if ($userEntity->hasErrors()) {
                    Log::error(sprintf(
                        'LdapUserImportCommand: Could not save user [%s] %s',
                        $userEntity->id,
                        $userEntity->samaccountname
                    ));
                    Log::error(json_encode($userEntity->getErrors()));
                }

                $imported++;
                $io->out(sprintf('Imported LDAP user: <success>%s</success>', $ldapUser['display_name']));
            }

            $io->out(sprintf('Imported %s users.', $imported));
            $io->success('   Ok');
            $io->hr();

        } catch (\Exception $e) {
            $io->error('‼️ Error connecting to LDAP: ' . $e->getMessage());
            return;
        }
    }
}
