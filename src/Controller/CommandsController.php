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

namespace App\Controller;

use App\Model\Entity\Changelog;
use App\Model\Table\ChangelogsTable;
use App\Model\Table\CommandsTable;
use App\Model\Table\ContactsTable;
use App\Model\Table\HostsTable;
use App\Model\Table\HosttemplatesTable;
use App\Model\Table\ServicesTable;
use App\Model\Table\ServicetemplatesTable;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\TableRegistry;
use itnovum\openITCOCKPIT\Core\KeyValueStore;
use itnovum\openITCOCKPIT\Core\UUID;
use itnovum\openITCOCKPIT\Core\ValueObjects\User;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\CommandsFilter;
use itnovum\openITCOCKPIT\Monitoring\DefaultMacros;


/**
 * Class CommandsController
 * @package App\Controller
 */
class CommandsController extends AppController {

    public function index() {
        if (!$this->isAngularJsRequest()) {
            //Only ship HTML Template
            return;
        }

        /** @var CommandsTable $CommandsTable */
        $CommandsTable = TableRegistry::getTableLocator()->get('Commands');
        $CommandFilter = new CommandsFilter($this->request);

        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $CommandFilter->getPage());
        $all_commands = $CommandsTable->getCommandsIndex($CommandFilter, $PaginateOMat);

        $this->set('all_commands', $all_commands);
        $this->viewBuilder()->setOption('serialize', ['all_commands']);
    }

    /**
     * @param null $id
     */
    public function view($id = null) {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var CommandsTable $CommandsTable */
        $CommandsTable = TableRegistry::getTableLocator()->get('Commands');
        if (!$CommandsTable->existsById($id)) {
            throw new NotFoundException(__('Invalid command'));
        }

        $command = $CommandsTable->getCommandById($id);
        $this->set('command', $command);
        $this->viewBuilder()->setOption('serialize', ['command']);
    }

    public function add() {
        if (!$this->isApiRequest()) {
            //Only ship HTML template for angular
            return;
        }

        if ($this->request->is('get')) {
            $DefaultMacros = DefaultMacros::getMacros();
            $this->set('defaultMacros', $DefaultMacros);
            $this->viewBuilder()->setOption('serialize', ['defaultMacros']);
            return;
        }

        /** @var CommandsTable $CommandsTable */
        $CommandsTable = TableRegistry::getTableLocator()->get('Commands');

        if ($this->request->is('post') && $this->isAngularJsRequest()) {
            $command = $CommandsTable->newEmptyEntity();
            $command = $CommandsTable->patchEntity($command, $this->request->getData('Command'));
            $command->set('uuid', UUID::v4());

            // Used by the Changelog
            $requestData = $this->request->getData();
            $User = new User($this->getUser());

            $command = $CommandsTable->createCommand($command, $requestData, $User->getId());
            if ($command->hasErrors()) {
                $this->set('error', $command->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
                $this->response = $this->response->withStatus(400);
                return;
            } else {
                //No errors

                if ($this->isJsonRequest()) {
                    $this->serializeCake4Id($command); // REST API ID serialization
                    return;
                }
            }
            $this->set('command', $command);
            $this->viewBuilder()->setOption('serialize', ['command']);
        }
    }

    /**
     * @param null $id
     */
    public function edit($id = null) {
        if (!$this->isApiRequest()) {
            //Only ship HTML template for angular
            return;
        }

        /** @var CommandsTable $CommandsTable */
        $CommandsTable = TableRegistry::getTableLocator()->get('Commands');
        if (!$CommandsTable->existsById($id)) {
            throw new NotFoundException('Command not found');
        }
        $command = $CommandsTable->getCommandForEdit($id);
        $commandForChangeLog = $command->toArray();

        if ($this->request->is('post') && $this->isAngularJsRequest()) {
            $command = $CommandsTable->patchEntity($command, $this->request->getData('Command'));

            $requestData = $this->request->getData();
            $User = new User($this->getUser());

            $command = $CommandsTable->updateCommand(
                $command,
                $requestData,
                ['Command' => $commandForChangeLog],
                $User->getId()
            );

            if ($command->hasErrors()) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', $command->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            } else {
                //No errors
                if ($this->isJsonRequest()) {
                    $this->serializeCake4Id($command); // REST API ID serialization
                    return;
                }
            }
        }

        $DefaultMacros = DefaultMacros::getMacros();

        $this->set('command', $command);
        $this->set('defaultMacros', $DefaultMacros);
        $this->viewBuilder()->setOption('serialize', ['command', 'defaultMacros']);
    }

    /**
     * @param int|null $id
     */
    public function delete($id = null) {
        if (!$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

        /** @var CommandsTable $CommandsTable */
        $CommandsTable = TableRegistry::getTableLocator()->get('Commands');
        if (!$CommandsTable->existsById($id)) {
            throw new NotFoundException(__('Invalid command'));
        }

        $command = $CommandsTable->getCommandById($id);

        if (!$CommandsTable->allowDelete($id)) {
            $usedBy = [
                [
                    'baseUrl' => '#',
                    'state'   => 'CommandsUsedBy',
                    'message' => __('Used by other objects'),
                    'module'  => 'Core'
                ]
            ];

            $this->response = $this->response->withStatus(400);
            $this->set('success', false);
            $this->set('id', $id);
            $this->set('message', __('Issue while deleting command'));
            $this->set('usedBy', $usedBy);
            $this->viewBuilder()->setOption('serialize', ['success', 'id', 'message', 'usedBy']);
            return;
        }


        if ($CommandsTable->delete($CommandsTable->get($id))) {
            $User = new User($this->getUser());
            /** @var ChangelogsTable $ChangelogsTable */
            $ChangelogsTable = TableRegistry::getTableLocator()->get('Changelogs');

            $changelog_data = $ChangelogsTable->parseDataForChangelog(
                $this->request->getParam('action'),
                $this->request->getParam('controller'),
                $id,
                OBJECT_COMMAND,
                [ROOT_CONTAINER],
                $User->getId(),
                $command['Command']['name'],
                $command
            );
            if ($changelog_data) {
                /** @var Changelog $changelogEntry */
                $changelogEntry = $ChangelogsTable->newEntity($changelog_data);
                $ChangelogsTable->save($changelogEntry);
            }

            $this->set('success', true);
            $this->viewBuilder()->setOption('serialize', ['success']);
            return;
        }


        $this->response = $this->response->withStatus(500);
        $this->set('success', false);
        $this->viewBuilder()->setOption('serialize', ['success']);
        return;
    }

    /**
     * @param null $id
     */
    public function usedBy($id = null) {
        if (!$this->isApiRequest()) {
            //Only ship HTML template for angular
            return;
        }

        /** @var CommandsTable $CommandsTable */
        $CommandsTable = TableRegistry::getTableLocator()->get('Commands');
        if (!$CommandsTable->existsById($id)) {
            throw new NotFoundException(__('Invalid command'));
        }

        $command = $CommandsTable->get($id);

        $objects = [
            'Contacts'         => [],
            'Hosttemplates'    => [],
            'Servicetemplates' => [],
            'Hosts'            => [],
            'Services'         => []
        ];

        $MY_RIGHTS = $this->MY_RIGHTS;
        if ($this->hasRootPrivileges) {
            $MY_RIGHTS = [];
        }

        //Get Contacts
        /** @var ContactsTable $ContactsTable */
        $ContactsTable = TableRegistry::getTableLocator()->get('Contacts');
        $objects['Contacts'] = $ContactsTable->getContactsByCommandId($id, $MY_RIGHTS);


        //Check if the command is used by host or service templates
        /** @var HosttemplatesTable $HosttemplatesTable */
        $HosttemplatesTable = TableRegistry::getTableLocator()->get('Hosttemplates');
        $objects['Hosttemplates'] = $HosttemplatesTable->getHosttemplatesByCommandId($id, $MY_RIGHTS, false);

        /** @var ServicetemplatesTable $ServicetemplatesTable */
        $ServicetemplatesTable = TableRegistry::getTableLocator()->get('Servicetemplates');
        $objects['Servicetemplates'] = $ServicetemplatesTable->getServicetemplatesByCommandId($id, $MY_RIGHTS, false);

        //Checking host and services
        /** @var HostsTable $HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
        $objects['Hosts'] = $HostsTable->getHostsByCommandId($id, $MY_RIGHTS, false);

        /** @var ServicesTable $ServicesTable */
        $ServicesTable = TableRegistry::getTableLocator()->get('Services');
        $objects['Services'] = $ServicesTable->getServicesByCommandId($id, $MY_RIGHTS, false);

        $total = 0;
        $total += sizeof($objects['Contacts']);
        $total += sizeof($objects['Hosttemplates']);
        $total += sizeof($objects['Servicetemplates']);
        $total += sizeof($objects['Hosts']);
        $total += sizeof($objects['Services']);


        $this->set('command', $command->toArray());
        $this->set('objects', $objects);
        $this->set('total', $total);
        $this->viewBuilder()->setOption('serialize', ['command', 'objects', 'total']);
    }

    /**
     * @param int|null $id
     */
    public function copy($id = null) {
        if (!$this->isAngularJsRequest()) {
            //Only ship HTML Template
            return;
        }

        /** @var CommandsTable $CommandsTable */
        $CommandsTable = TableRegistry::getTableLocator()->get('Commands');

        if ($this->request->is('get')) {
            $commands = $CommandsTable->getCommandsForCopy(func_get_args());
            $this->set('commands', $commands);
            $this->viewBuilder()->setOption('serialize', ['commands']);
            return;
        }

        $hasErrors = false;

        if ($this->request->is('post')) {
            $User = new User($this->getUser());
            $Cache = new KeyValueStore();

            $postData = $this->request->getData('data');

            foreach ($postData as $index => $commandData) {
                if (!isset($commandData['Command']['id'])) {
                    //Create/clone command
                    $sourceCommandId = $commandData['Source']['id'];
                    if (!$Cache->has($sourceCommandId)) {
                        $sourceCommand = $CommandsTable->getSourceCommandForCopy($sourceCommandId);
                        $Cache->set($sourceCommand['id'], $sourceCommand);
                    }

                    $sourceCommand = $Cache->get($sourceCommandId);

                    $newCommandData = [
                        'name'             => $commandData['Command']['name'],
                        'command_line'     => $commandData['Command']['command_line'],
                        'command_type'     => $sourceCommand['command_type'],
                        'description'      => $commandData['Command']['description'],
                        'uuid'             => UUID::v4(),
                        'commandarguments' => $sourceCommand['commandarguments']
                    ];

                    $newCommandEntity = $CommandsTable->newEntity($newCommandData);
                }

                $action = 'copy';
                if (isset($commandData['Command']['id'])) {
                    //Update existing command
                    //This happens, if a user copy multiple commands, and one run into an validation error
                    //All commands without validation errors got already saved to the database
                    $newCommandEntity = $CommandsTable->get($commandData['Command']['id']);
                    $newCommandEntity->setAccess('*', false);
                    $newCommandEntity->setAccess(['name', 'command_line', 'description'], true);
                    $newCommandEntity = $CommandsTable->patchEntity($newCommandEntity, $commandData['Command']);
                    $newCommandData = $newCommandEntity->toArray();
                    $action = 'edit';
                }
                $CommandsTable->save($newCommandEntity);

                $postData[$index]['Error'] = [];
                if ($newCommandEntity->hasErrors()) {
                    $hasErrors = true;
                    $postData[$index]['Error'] = $newCommandEntity->getErrors();
                } else {
                    //No errors
                    $postData[$index]['Command']['id'] = $newCommandEntity->get('id');
                    /** @var ChangelogsTable $ChangelogsTable */
                    $ChangelogsTable = TableRegistry::getTableLocator()->get('Changelogs');

                    $changelog_data = $ChangelogsTable->parseDataForChangelog(
                        $action,
                        $this->request->getParam('controller'),
                        $postData[$index]['Command']['id'],
                        OBJECT_COMMAND,
                        [ROOT_CONTAINER],
                        $User->getId(),
                        $postData[$index]['Command']['name'],
                        ['Command' => $newCommandData]
                    );
                    if ($changelog_data) {
                        /** @var Changelog $changelogEntry */
                        $changelogEntry = $ChangelogsTable->newEntity($changelog_data);
                        $ChangelogsTable->save($changelogEntry);
                    }
                }
            }
        }

        if ($hasErrors) {
            $this->response = $this->response->withStatus(400);
        }
        $this->set('result', $postData);
        $this->viewBuilder()->setOption('serialize', ['result']);
    }
}
