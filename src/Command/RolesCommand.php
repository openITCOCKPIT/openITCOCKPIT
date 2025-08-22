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

declare(strict_types=1);

namespace App\Command;

use Acl\Model\Table\AcosTable;
use Acl\Model\Table\ArosTable;
use App\Lib\AclDependencies;
use App\Lib\DefaultRolePermissions;
use App\Model\Table\ArosAcosTable;
use App\Model\Table\UsergroupsTable;
use Cake\Console\Arguments;
use Cake\Command\Command;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Log\Log;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;

/**
 * Roles command.
 */
class RolesCommand extends Command {

    /**
     * @var bool
     */
    private $restoreAdminDefault = false;

    /**
     * @var bool
     */
    private $enableDefaults = false;

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

        $parser->addOptions([
            'admin'           => ['help' => 'Restore all default user role permissions for "Administrator" role', 'boolean' => true, 'default' => false],
            'enable-defaults' => ['help' => 'Re-Enables all default permissions of pre defined user groups (Viewer) and keeps custom changes. (Excluding "Administrator")', 'boolean' => true, 'default' => false],
        ]);

        return $parser;
    }

    /**
     * Implement this method with your command's logic.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return null|void|int The exit code or null for success
     * @throws \Exception
     */
    public function execute(Arguments $args, ConsoleIo $io) {
        $this->restoreAdminDefault = $args->getOption('admin');
        $this->enableDefaults = $args->getOption('enable-defaults');

        /** @var UsergroupsTable $UsergroupsTable */
        $UsergroupsTable = TableRegistry::getTableLocator()->get('Usergroups');

        /** @var AcosTable $AcosTable */
        $AcosTable = TableRegistry::getTableLocator()->get('Acl.Acos');
        /** @var ArosTable $ArosTable */
        $ArosTable = TableRegistry::getTableLocator()->get('Acl.Aros');
        /** @var ArosAcosTable $ArosAcosTable */
        $ArosAcosTable = TableRegistry::getTableLocator()->get('ArosAcos');

        $usergroups = $UsergroupsTable->getUsergroupsList();
        if (empty($usergroups)) {
            $io->error('No User groups found!');
            exit(1);
        }

        foreach ($usergroups as $usergroupId => $usergroupName) {
            $io->out(__('Checking permissions for user group "{0}"...', $usergroupName), 0);

            //Get current Aros and Acos of the user usergroup
            $usergroup = $UsergroupsTable->find()
                ->contain([
                    'Aros' => [
                        'Acos'
                    ]
                ])
                ->where([
                    'Usergroups.id' => $usergroupId
                ])
                ->disableHydration()
                ->firstOrFail();

            //Create an list of AcoId => 0/1
            $selectedAcos = [];
            if (isset($usergroup['aro']['acos'])) { // May be a empty user group with no permissions yet...
                foreach ($usergroup['aro']['acos'] as $aco) {
                    $acoId = $aco['id'];

                    $allowOrDeny = (int)($aco['_joinData']['_create'] === '1');
                    $selectedAcos[$acoId] = $allowOrDeny;
                }
            }

            $AclDependencies = new AclDependencies();
            //Set always allowed and dependend Acos
            $selectedAcos = $AclDependencies->getDependentAcos($AcosTable, $selectedAcos);

            $aro = $ArosTable->find()
                ->where([
                    'Aros.foreign_key' => $usergroupId
                ])
                ->firstOrFail();

            //Drop old permissions
            $ArosAcosTable->deleteAll([
                'ArosAcos.aro_id' => $aro->get('id')
            ]);

            $arosToAcos = [];
            foreach ($selectedAcos as $acoId => $state) {
                $arosToAcos[] = $ArosAcosTable->newEntity([
                    'aro_id'  => $aro->get('id'),
                    'aco_id'  => $acoId,
                    '_create' => (int)($state === 1),
                    '_read'   => (int)($state === 1),
                    '_update' => (int)($state === 1),
                    '_delete' => (int)($state === 1),
                ]);
            }
            $ArosAcosTable->saveMany($arosToAcos);

            $io->success('    Done');
        }

        if ($this->restoreAdminDefault) {
            $this->restoreAdminDefault($io);
        }

        if ($this->enableDefaults) {
            $this->enableDefaults($io);
        }


        /**** OLD CODE ****/
        return;

        $this->stdout->styles('green', ['text' => 'green']);
        $this->out('Setting aros_acos...    ');

        $acos = $this->Aco->find('threaded', [
            'recursive' => -1,
        ]);
        $alwaysAllowedAcos = $this->Usergroup->getAlwaysAllowedAcos($acos);
        $acoDependencies = $this->Usergroup->getAcoDependencies($acos);

        $inserted = 0;

        $userGroups = [];
        if ($this->onlyAdminGroup === false) {
            $userGroups = $this->Usergroup->find('all', [
                'recurisve' => -1,

                'fields'  => [
                    'Usergroup.id', 'Usergroup.name'
                ],
                'contain' => [],
            ]);
        }

        if ($this->onlyAdminGroup === true) {
            $userGroups = $this->Usergroup->find('all', [
                'recurisve' => -1,

                'fields'     => [
                    'Usergroup.id', 'Usergroup.name'
                ],
                'conditions' => [
                    'Usergroup.name' => 'Administrator'
                ],
                'contain'    => [],
            ]);
        }

        $permissions = $this->Permission->find('all', [
            'recurisve' => -1,
            'contain'   => [],
        ]);
        $myPermissions = $aclData = [];
        foreach ($permissions as $permission) {
            $myPermissions[$permission['Permission']['aro_id']][] = $permission['Permission']['aco_id'];
        }

        if (!empty($userGroups)) {
            foreach ($userGroups as $userGroup) {
                $usergroupCount = 0;
                $this->out('Setting for ' . $userGroup['Usergroup']['name'] . '...', false);
                $aro = $this->Aro->find('first', [
                    'recursive'  => -1,
                    'conditions' => [
                        'Aro.foreign_key' => $userGroup['Usergroup']['id'],
                    ],
                    'fields'     => [
                        'Aro.id',
                    ],
                ]);
                if (!isset($aro['Aro']['id'])) {
                    $this->out('<red>no Aro found</red>');
                    continue;
                }
                $myAroId = intval($aro['Aro']['id']);
                // checking always allowed
                foreach ($alwaysAllowedAcos as $acoId => $acoFullName) {
                    if (!in_array($acoId, $myPermissions[$myAroId])) {
                        if ($usergroupCount == 0)
                            $this->out('');
                        $this->out('<green>Inserting</green> ' . $acoFullName);
                        $aclData[] = [
                            'Permission' => [
                                'aro_id'  => $myAroId,
                                'aco_id'  => $acoId,
                                '_create' => 1,
                                '_read'   => 1,
                                '_update' => 1,
                                '_delete' => 1,
                            ],
                        ];
                        $myPermissions[$myAroId][] = $acoId;
                        ++$inserted;
                        ++$usergroupCount;
                    }
                }

                // checking user rights
                $acoUsergroups = $this->Usergroup->getUsergroupAcos($acos, $userGroup['Usergroup']['name']);
                foreach ($acoUsergroups as $acoId => $acoUsergroup) {
                    if (!in_array($acoId, $myPermissions[$myAroId])) {
                        if ($usergroupCount == 0)
                            $this->out('');
                        $this->out('<green>Inserting</green> ' . $acoUsergroup);
                        $aclData[] = [
                            'Permission' => [
                                'aro_id'  => $myAroId,
                                'aco_id'  => $acoId,
                                '_create' => 1,
                                '_read'   => 1,
                                '_update' => 1,
                                '_delete' => 1,
                            ],
                        ];
                        $myPermissions[$myAroId][] = $acoId;
                        ++$inserted;
                        ++$usergroupCount;
                    }
                }

                // checking depended
                foreach ($acoDependencies as $mainAcoId => $dependedAcoIds) {
                    if (in_array($mainAcoId, $myPermissions[$myAroId])) {
                        foreach ($dependedAcoIds as $dependedAcoId => $dependedFullName) {
                            if (!in_array($dependedAcoId, $myPermissions[$myAroId])) {
                                $helpArr = explode('/', $dependedFullName);
                                if (isset($helpArr[3])) {
                                    $fixedDependedFullName = $helpArr[0] . '/' . $helpArr[1] . '/' . $helpArr[3];
                                } else {
                                    $fixedDependedFullName = $helpArr[0] . '/' . $helpArr[2];
                                }
                                $parsedDependedFullName = preg_replace('/(.+)(\/.+\/)(.+)/', '$1/$3', $fixedDependedFullName);
                                if ($usergroupCount == 0)
                                    $this->out('');
                                $this->out('<green>Inserting</green> ' . $parsedDependedFullName);
                                $aclData[] = [
                                    'Permission' => [
                                        'aro_id'  => $myAroId,
                                        'aco_id'  => $dependedAcoId,
                                        '_create' => 1,
                                        '_read'   => 1,
                                        '_update' => 1,
                                        '_delete' => 1,
                                    ],
                                ];
                                $myPermissions[$myAroId][] = $dependedAcoId;
                                ++$inserted;
                                ++$usergroupCount;
                            }
                        }
                    }
                }

                if ($usergroupCount == 0)
                    $this->out('     <green>done</green>');
            }
        }
        if (!empty($aclData)) {
            $this->Aro->Permission->saveAll($aclData);
        }

        $this->out('<green>' . strval($inserted) . ' row(s) inserted!</green>');
    }

    /**
     * @param ConsoleIo $io
     * @throws \Exception
     */
    private function restoreAdminDefault(ConsoleIo $io) {
        $io->info(__('Restore default permissions for user group "Administrator"'), 0);

        /** @var UsergroupsTable $UsergroupsTable */
        $UsergroupsTable = TableRegistry::getTableLocator()->get('Usergroups');

        /** @var AcosTable $AcosTable */
        $AcosTable = TableRegistry::getTableLocator()->get('Acl.Acos');
        /** @var ArosTable $ArosTable */
        $ArosTable = TableRegistry::getTableLocator()->get('Acl.Aros');
        /** @var ArosAcosTable $ArosAcosTable */
        $ArosAcosTable = TableRegistry::getTableLocator()->get('ArosAcos');

        //Get current Aros and Acos of the user usergroup
        $usergroup = $UsergroupsTable->find()
            ->contain([
                'Aros' => [
                    'Acos'
                ]
            ])
            ->where([
                'Usergroups.name' => 'Administrator'
            ])
            ->disableHydration()
            ->firstOrFail();


        //Load all Acos - Administrator has permission to everything
        $acos = $UsergroupsTable->getAllAcosAsList();

        $selectedAcos = [];
        foreach ($acos as $acoId => $acoPath) {
            $selectedAcos[$acoId] = 1; //Administrator has all permissions by default
        }

        $aro = $ArosTable->find()
            ->where([
                'Aros.foreign_key' => $usergroup['id']
            ])
            ->firstOrFail();

        //Drop old permissions
        $ArosAcosTable->deleteAll([
            'ArosAcos.aro_id' => $aro->get('id')
        ]);

        $arosToAcos = [];
        foreach ($selectedAcos as $acoId => $state) {
            $arosToAcos[] = $ArosAcosTable->newEntity([
                'aro_id'  => $aro->get('id'),
                'aco_id'  => $acoId,
                '_create' => (int)($state === 1),
                '_read'   => (int)($state === 1),
                '_update' => (int)($state === 1),
                '_delete' => (int)($state === 1),
            ]);
        }
        $ArosAcosTable->saveMany($arosToAcos);

        $io->success('    Done');
    }

    /**
     * @param ConsoleIo $io
     * @throws \Exception
     */
    private function enableDefaults(ConsoleIo $io) {
        /** @var UsergroupsTable $UsergroupsTable */
        $UsergroupsTable = TableRegistry::getTableLocator()->get('Usergroups');

        /** @var AcosTable $AcosTable */
        $AcosTable = TableRegistry::getTableLocator()->get('Acl.Acos');
        /** @var ArosTable $ArosTable */
        $ArosTable = TableRegistry::getTableLocator()->get('Acl.Aros');
        /** @var ArosAcosTable $ArosAcosTable */
        $ArosAcosTable = TableRegistry::getTableLocator()->get('ArosAcos');

        //Get an list of all Acos that exists
        $allAcosList = $UsergroupsTable->getAllAcosAsList(false);

        foreach (DefaultRolePermissions::getDefaultRolePermissions() as $userRoleName => $userrole) {
            $io->info(__('Re-Enable default permissions for user group "{0}" but keep custom changes.', $userRoleName), 0);

            //Get current Aros and Acos of the user usergroup
            try {
                $usergroup = $UsergroupsTable->find()
                    ->contain([
                        'Aros' => [
                            'Acos'
                        ]
                    ])
                    ->where([
                        'Usergroups.name' => $userRoleName
                    ])
                    ->disableHydration()
                    ->firstOrFail();
            }catch (RecordNotFoundException $e){
                // Usergroup "Viewer" not found. Got renamed?
                Log::error(sprintf('Usergroup "%s" not found', $userRoleName));
                Log::error($e->getMessage());
                continue;
            }

            //Create an list of current AcoId => 0/1
            $selectedAcos = [];
            if (isset($usergroup['aro']['acos'])) { // May be a empty user group with no permissions yet...
                foreach ($usergroup['aro']['acos'] as $aco) {
                    $acoId = $aco['id'];

                    $allowOrDeny = (int)($aco['_joinData']['_create'] === '1');
                    $selectedAcos[$acoId] = $allowOrDeny;
                }
            }

            // Re-Enable all Acos that are defined in this user role
            foreach ($userrole as $controllerName => $actions) {
                if (substr($controllerName, -6) === 'Module') {
                    $pluginName = $controllerName;
                    $pluginConrollers = $actions;
                    foreach ($pluginConrollers as $pluginControllerName => $pluginActions) {
                        foreach ($pluginActions as $pluginAction) {
                            if (isset($allAcosList[$pluginName . '/' . $pluginControllerName . '/' . $pluginAction])) {
                                $acoId = $allAcosList[$pluginName . '/' . $pluginControllerName . '/' . $pluginAction];
                                $selectedAcos[$acoId] = 1;
                            }
                        }
                    }

                } else {
                    //Core
                    foreach ($actions as $actionName) {
                        if (isset($allAcosList[$controllerName . '/' . $actionName])) {
                            $acoId = $allAcosList[$controllerName . '/' . $actionName];
                            $selectedAcos[$acoId] = 1;
                        }
                    }
                }
            }

            $AclDependencies = new AclDependencies();
            //Set always allowed and dependend Acos
            $selectedAcos = $AclDependencies->getDependentAcos($AcosTable, $selectedAcos);

            $aro = $ArosTable->find()
                ->where([
                    'Aros.foreign_key' => $usergroup['id']
                ])
                ->firstOrFail();

            //Drop old permissions
            $ArosAcosTable->deleteAll([
                'ArosAcos.aro_id' => $aro->get('id')
            ]);

            $arosToAcos = [];
            foreach ($selectedAcos as $acoId => $state) {
                $arosToAcos[] = $ArosAcosTable->newEntity([
                    'aro_id'  => $aro->get('id'),
                    'aco_id'  => $acoId,
                    '_create' => (int)($state === 1),
                    '_read'   => (int)($state === 1),
                    '_update' => (int)($state === 1),
                    '_delete' => (int)($state === 1),
                ]);
            }
            $ArosAcosTable->saveMany($arosToAcos);

            $io->success('    Done');
        }
    }
}
