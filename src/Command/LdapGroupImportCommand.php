<?php
// Copyright (C) 2015-2025  it-novum GmbH
// Copyright (C) 2025-today Allgeier IT Services GmbH
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

use App\Model\Entity\Ldapgroup;
use App\Model\Entity\User;
use App\Model\Entity\Usercontainerrole;
use App\Model\Table\LdapgroupsTable;
use App\Model\Table\SystemsettingsTable;
use App\Model\Table\UsercontainerrolesTable;
use App\Model\Table\UsersTable;
use Cake\Cache\Cache;
use Cake\Console\Arguments;
use Cake\Command\Command;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use itnovum\openITCOCKPIT\Core\Interfaces\CronjobInterface;
use itnovum\openITCOCKPIT\Ldap\LdapClient;

/**
 * LdapGroupImport command.
 */
class LdapGroupImportCommand extends Command implements CronjobInterface {

    /**
     * Hook method for defining this command's option parser.
     *
     * @see https://book.cakephp.org/3.0/en/console-and-shells/commands.html#defining-arguments-and-options
     *
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
     * @return \Cake\Console\ConsoleOptionParser The built parser.
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser {
        $parser = parent::buildOptionParser($parser);

        return $parser;
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

        $this->syncLdapGroupsWithDatabase($io);
        $this->assignUserContainerRolesToUsers($io);
        Cache::clear('permissions');
    }

    /**
     * @param ConsoleIo $io
     * @return void
     * @throws \FreeDSx\Ldap\Exception\BindException
     */
    private function syncLdapGroupsWithDatabase(ConsoleIo $io) {
        /** @var SystemsettingsTable $SystemsettingsTable */
        $SystemsettingsTable = TableRegistry::getTableLocator()->get('Systemsettings');

        $io->out('Scan for new LDAP groups. This will take a while...');

        /** @var LdapgroupsTable $LdapgroupsTable */
        $LdapgroupsTable = TableRegistry::getTableLocator()->get('Ldapgroups');

        $LdapClient = LdapClient::fromSystemsettings($SystemsettingsTable->findAsArraySection('FRONTEND'));

        $ldapGroupsFromDb = $LdapgroupsTable->getGroupsForSync();
        $ldapGroupsFromLdap = $LdapClient->getGroups();

        // Check which groups only exists in our database but not in LDAP anymore and needs to be removed
        $ldapGroupsFromLdapHash = [];
        foreach ($ldapGroupsFromLdap as $ldapGroup) {
            $ldapGroupsFromLdapHash[$ldapGroup['dn']] = $ldapGroup;
        }

        $removed = 0;
        $ldapGroupsToRemove = [];
        foreach ($ldapGroupsFromDb as $dn => $ldapGroupFromDb) {
            /** @var Ldapgroup $ldapGroup */
            if (!isset($ldapGroupsFromLdapHash[$ldapGroupFromDb->dn])) {
                // This group was removed from LDAP
                $ldapGroupsToRemove[] = $ldapGroupFromDb;
                $removed++;
            }
        }


        $LdapgroupsTable->deleteMany($ldapGroupsToRemove);
        foreach ($ldapGroupsToRemove as $groupToRemove) {
            $io->out(sprintf('Deleted LDAP group: <warning>%s</warning> from database', $groupToRemove->dn));
        }


        // Add new LDAP Groups to database
        $created = 0;
        foreach ($ldapGroupsFromLdap as $ldapGroup) {
            if (!isset($ldapGroupsFromDb[$ldapGroup['dn']])) {
                // This LDAP group does not exists in database
                $entity = $LdapgroupsTable->newEntity([
                    'cn'          => $ldapGroup['cn'],
                    'dn'          => $ldapGroup['dn'],
                    'description' => $ldapGroup['description']
                ]);

                if ($entity->hasErrors() === false) {
                    $LdapgroupsTable->save($entity);
                    $created++;
                    $io->out(sprintf('Created LDAP group: <success>%s</success>', $ldapGroup['dn']));
                }
            }
        }

        $io->out(sprintf('Imported %s groups, removed %s groups from database.', $created, $removed));

        $io->success('   Ok');
        $io->hr();
    }

    private function assignUserContainerRolesToUsers(ConsoleIo $io) {
        $io->out('Assign User Container Roles that have LDAP associations to users');

        /** @var SystemsettingsTable $SystemsettingsTable */
        $SystemsettingsTable = TableRegistry::getTableLocator()->get('Systemsettings');
        $LdapClient = LdapClient::fromSystemsettings($SystemsettingsTable->findAsArraySection('FRONTEND'));

        /** @var UsersTable $UsersTable */
        $UsersTable = TableRegistry::getTableLocator()->get('Users');
        /** @var UsercontainerrolesTable $UsercontainerrolesTable */
        $UsercontainerrolesTable = TableRegistry::getTableLocator()->get('Usercontainerroles');

        foreach ($UsersTable->getLdapUsersForSync() as $user) {
            /** @var User $user */
            $io->out(sprintf('Query LDAP groups from LDAP for user <success>%s</success>', $user->samaccountname));

            $ldapUser = $LdapClient->getUser($user->samaccountname, true);
            if ($ldapUser) {
                $userContainerRoleContainerPermissionsLdap = $UsercontainerrolesTable->getContainerPermissionsByLdapUserMemberOf(
                    $ldapUser['memberof']
                );

                // Keep manually assigned user container roles
                $data = [
                    'usercontainerroles'      => [],
                    // For validation only (always empty in the LdapGroupCommand bc this is part of the usercontainerroles array above)
                    'usercontainerroles_ldap' => [
                        '_ids' => []
                    ],
                    // Add any containers for the validation (in case usercontainerroles is empty)
                    'containers'              => [
                        '_ids' => Hash::extract($user, 'containers.{n}.id')
                    ]
                ];
                foreach ($user->usercontainerroles as $usercontainerrole) {
                    /** @var Usercontainerrole $usercontainerrole */
                    $data['usercontainerroles'][$usercontainerrole->id] = [
                        'id'        => $usercontainerrole->id,
                        '_joinData' => [
                            'through_ldap' => false // This got assigned manually
                        ]
                    ];
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

                $user = $UsersTable->patchEntity($user, $data);
                $UsersTable->save($user);
                if ($user->hasErrors()) {
                    Log::error(sprintf(
                        'LdapGroupImportCommand: Could not save user [%s] %s',
                        $user->id,
                        $user->samaccountname
                    ));
                    Log::error(json_encode($user->getErrors()));
                }
            }

        }

        $io->success('   Ok');
        $io->hr();
    }
}
