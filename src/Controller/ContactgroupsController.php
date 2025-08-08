<?php
// Copyright (C) <2015>  <it-novum GmbH>
//
// This file is dual licensed
//
// 1.
//	This program is free software: you can redistribute it and/or modify
//	it under the terms of the GNU General Public License as published by
//	the Free Software Foundation, version 3 of the License.
//
//	This program is distributed in the hope that it will be useful,
//	but WITHOUT ANY WARRANTY; without even the implied warranty of
//	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//	GNU General Public License for more details.
//
//	You should have received a copy of the GNU General Public License
//	along with this program.  If not, see <http://www.gnu.org/licenses/>.
//

// 2.
//	If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//	under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//	License agreement and license key will be shipped with the order
//	confirmation.

declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\Changelog;
use App\Model\Table\ChangelogsTable;
use App\Model\Table\ContactgroupsTable;
use App\Model\Table\ContactsTable;
use App\Model\Table\ContactsToContactgroupsTable;
use App\Model\Table\ContainersTable;
use Cake\Cache\Cache;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use itnovum\openITCOCKPIT\Core\AngularJS\Api;
use itnovum\openITCOCKPIT\Core\KeyValueStore;
use itnovum\openITCOCKPIT\Core\UUID;
use itnovum\openITCOCKPIT\Core\ValueObjects\User;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\ContactgroupsFilter;


/**
 * Class ContactgroupsController
 * @package App\Controller
 */
class ContactgroupsController extends AppController {

    public function index() {
        if (!$this->isAngularJsRequest()) {
            //Only ship HTML Template
            return;
        }

        /** @var $ContactgroupsTable ContactgroupsTable */
        $ContactgroupsTable = TableRegistry::getTableLocator()->get('Contactgroups');
        /** @var $ContactsToContactgroupsTable ContactsToContactgroupsTable */
        $ContactsToContactgroupsTable = TableRegistry::getTableLocator()->get('ContactsToContactgroups');

        $ContactgroupsFilter = new ContactgroupsFilter($this->request);
        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $ContactgroupsFilter->getPage());

        $MY_RIGHTS = [];
        if ($this->hasRootPrivileges === false) {
            /** @var $ContainersTable ContainersTable */
            //$ContainersTable = TableRegistry::getTableLocator()->get('Containers');
            //$MY_RIGHTS = $ContainersTable->resolveChildrenOfContainerIds($this->MY_RIGHTS);
            // ITC-2863 $this->MY_RIGHTS is already resolved and contains all containerIds a user has access to
            $MY_RIGHTS = $this->MY_RIGHTS;
        }
        $contactgroups = $ContactgroupsTable->getContactgroupsIndex($ContactgroupsFilter, $PaginateOMat, $MY_RIGHTS);
        foreach ($contactgroups as $index => $contactgroup) {
            $contactgroups[$index]['Contactgroup']['allow_edit'] = $this->isWritableContainer($contactgroup['Container']['parent_id']);
            $contactgroups[$index]['Contactgroup']['contact_count'] = $ContactsToContactgroupsTable->getContactsCountByContactgroupId($contactgroup['Contactgroup']['id']);
        }


        $this->set('all_contactgroups', $contactgroups);
        $this->viewBuilder()->setOption('serialize', ['all_contactgroups']);
    }

    public function view($id = null) {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var $ContactgroupsTable ContactgroupsTable */
        $ContactgroupsTable = TableRegistry::getTableLocator()->get('Contactgroups');

        if (!$ContactgroupsTable->existsById($id)) {
            throw new NotFoundException(__('Invalid contact group'));
        }

        $contactgroup = $ContactgroupsTable->getContactgroupById($id);
        if (!$this->allowedByContainerId($contactgroup['container']['parent_id'])) {
            throw new ForbiddenException('403 Forbidden');
        }

        $this->set('contactgroup', $contactgroup);
        $this->viewBuilder()->setOption('serialize', ['contactgroup']);
    }

    public function add() {
        if (!$this->isApiRequest()) {
            //Only ship HTML template for angular
            return;
        }

        if ($this->request->is('post')) {
            /** @var $ContactgroupsTable ContactgroupsTable */
            $ContactgroupsTable = TableRegistry::getTableLocator()->get('Contactgroups');

            /** @var ContainersTable $ContainersTable */
            $ContainersTable = TableRegistry::getTableLocator()->get('Containers');

            $ContainersTable->acquireLock();

            $requestData = $this->request->getData();

            $contactgroup = $ContactgroupsTable->newEmptyEntity();
            $contactgroup = $ContactgroupsTable->patchEntity($contactgroup, $this->request->getData('Contactgroup'));
            $contactgroup->set('uuid', UUID::v4());
            $contactgroup->get('container')->set('containertype_id', CT_CONTACTGROUP);

            $User = new User($this->getUser());

            $contactgroup = $ContactgroupsTable->createContactgroup($contactgroup, $requestData, $User->getId());

            $ContactgroupsTable->save($contactgroup);
            if ($contactgroup->hasErrors()) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', $contactgroup->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            } else {
                //No errors
                Cache::clear('permissions');

                if ($this->isJsonRequest()) {
                    $this->serializeCake4Id($contactgroup); // REST API ID serialization
                    return;
                }
            }
            $this->set('contactgroup', $contactgroup);
            $this->viewBuilder()->setOption('serialize', ['contactgroup']);
        }
    }


    public function edit($id = null) {
        if (!$this->isApiRequest()) {
            //Only ship HTML template for angular
            return;
        }

        /** @var $ContactgroupsTable ContactgroupsTable */
        $ContactgroupsTable = TableRegistry::getTableLocator()->get('Contactgroups');

        if (!$ContactgroupsTable->existsById($id)) {
            throw new NotFoundException(__('Contact group not found'));
        }

        $contactgroup = $ContactgroupsTable->getContactgroupForEdit($id);
        $contactgroupForChangeLog = $contactgroup;

        if (!$this->isWritableContainer($contactgroup['Contactgroup']['container']['parent_id'])) {
            $this->render403();
            return;
        }

        if ($this->request->is('get') && $this->isAngularJsRequest()) {
            //Return contact group information
            $this->set('contactgroup', $contactgroup);
            $this->viewBuilder()->setOption('serialize', ['contactgroup']);
            return;
        }

        if ($this->request->is('post') && $this->isAngularJsRequest()) {
            //Update contact data
            $User = new User($this->getUser());

            /** @var ContainersTable $ContainersTable */
            $ContainersTable = TableRegistry::getTableLocator()->get('Containers');

            $ContainersTable->acquireLock();

            $contactgroupEntity = $ContactgroupsTable->get($id, contain: [
                'Containers'
            ]);
            $contactgroupEntity->setAccess('uuid', false);
            $contactgroupEntity = $ContactgroupsTable->patchEntity($contactgroupEntity, $this->request->getData('Contactgroup'));
            $contactgroupEntity->id = $id;

            $requestData = $this->request->getData();

            $contactgroupEntity = $ContactgroupsTable->updateContactgroup(
                $contactgroupEntity,
                $requestData,
                $contactgroupForChangeLog,
                $User->getId()
            );

            $ContactgroupsTable->save($contactgroupEntity);
            if ($contactgroupEntity->hasErrors()) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', $contactgroupEntity->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            } else {
                //No errors

                if ($this->isJsonRequest()) {
                    $this->serializeCake4Id($contactgroupEntity); // REST API ID serialization
                    return;
                }
            }
            $this->set('contactgroup', $contactgroupEntity);
            $this->viewBuilder()->setOption('serialize', ['contactgroup']);
        }
    }

    public function delete($id) {
        if (!$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

        /** @var $ContactgroupsTable ContactgroupsTable */
        $ContactgroupsTable = TableRegistry::getTableLocator()->get('Contactgroups');

        /** @var ContainersTable $ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');

        if (!$ContactgroupsTable->existsById($id)) {
            throw new NotFoundException(__('Contact group not found'));
        }

        $ContainersTable->acquireLock();

        $contactgroupEntity = $ContactgroupsTable->get($id, contain: [
            'Containers'
        ]);

        if (!$this->isWritableContainer($contactgroupEntity->get('container')->get('parent_id'))) {
            $this->render403();
            return;
        }

        $usedBy = [
            [
                'baseUrl' => '#',
                'state'   => 'ContactgroupsUsedBy',
                'message' => __('Used by other objects'),
                'module'  => 'Core'
            ]
        ];

        if (!$ContactgroupsTable->allowDelete($id)) {
            $this->response = $this->response->withStatus(400);
            $this->set('success', false);
            $this->set('id', $id);
            $this->set('message', __('Issue while deleting contact'));
            $this->set('usedBy', $usedBy);
            $this->viewBuilder()->setOption('serialize', ['success', 'id', 'message', 'usedBy']);
            return;
        }

        $container = $ContainersTable->get($contactgroupEntity->get('container')->get('id'), contain: [
            'Contactgroups'
        ]);
        if ($ContainersTable->allowDelete($container->id, CT_CONTACTGROUP)) {
            if ($ContainersTable->delete($container)) {
                $User = new User($this->getUser());
                Cache::clear('permissions');
                /** @var  ChangelogsTable $ChangelogsTable */
                $ChangelogsTable = TableRegistry::getTableLocator()->get('Changelogs');

                $changelog_data = $ChangelogsTable->parseDataForChangelog(
                    'delete',
                    'contactgroup',
                    $id,
                    OBJECT_CONTACTGROUP,
                    $contactgroupEntity->get('container')->get('parent_id'),
                    $User->getId(),
                    $contactgroupEntity->get('container')->get('name'),
                    $contactgroupEntity->toArray()
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
        $this->response = $this->response->withStatus(400);
        $this->set('success', false);
        $this->set('id', $id);
        $this->set('message', __('Issue while deleting contact'));
        $this->set('usedBy', $usedBy);
        $this->viewBuilder()->setOption('serialize', ['success', 'id', 'message', 'usedBy']);
        return;
    }


    public function copy($id = null) {
        if (!$this->isAngularJsRequest()) {
            //Only ship HTML Template
            return;
        }

        /** @var $ContactgroupsTable ContactgroupsTable */
        $ContactgroupsTable = TableRegistry::getTableLocator()->get('Contactgroups');

        $MY_RIGHTS = $this->MY_RIGHTS;
        if ($this->hasRootPrivileges) {
            $MY_RIGHTS = [];
        }

        if ($this->request->is('get')) {
            $contactgroups = $ContactgroupsTable->getContactgroupsForCopy(func_get_args(), $MY_RIGHTS);
            $this->set('contactgroups', $contactgroups);
            $this->viewBuilder()->setOption('serialize', ['contactgroups']);
            return;
        }

        $hasErrors = false;

        if ($this->request->is('post')) {
            $Cache = new KeyValueStore();

            $postData = $this->request->getData('data');
            $User = new User($this->getUser());
            $userId = $User->getId();

            foreach ($postData as $index => $contactgroupData) {
                if (!isset($contactgroupData['Contactgroup']['id'])) {
                    //Create/clone contact group
                    $sourceContactgroupId = $contactgroupData['Source']['id'];
                    if (!$Cache->has($sourceContactgroupId)) {
                        $sourceContactgroup = $ContactgroupsTable->get($sourceContactgroupId, contain: [
                            'Containers',
                            'Contacts'
                        ])->toArray();
                        $contacts = Hash::extract($sourceContactgroup['contacts'], '{n}.id');
                        $sourceContactgroup['contacts'] = $contacts;
                        $Cache->set($sourceContactgroup['id'], $sourceContactgroup);
                    }

                    $sourceContactgroup = $Cache->get($sourceContactgroupId);

                    $newContactgroupData = [
                        'description' => $contactgroupData['Contactgroup']['description'],
                        'uuid'        => UUID::v4(),
                        'container'   => [
                            'name'             => $contactgroupData['Contactgroup']['container']['name'],
                            'containertype_id' => CT_CONTACTGROUP,
                            'parent_id'        => $sourceContactgroup['container']['parent_id']
                        ],
                        'contacts'    => [
                            '_ids' => $sourceContactgroup['contacts']
                        ]
                    ];

                    $newContactgroupEntity = $ContactgroupsTable->newEntity($newContactgroupData);
                }

                $action = 'copy';
                if (isset($contactgroupData['Contact']['id'])) {
                    //Update existing contact
                    //This happens, if a user copy multiple contacts, and one run into an validation error
                    //All contacts without validation errors got already saved to the database
                    $newContactgroupEntity = $ContactgroupsTable->get($contactgroupData['Contactgroup']['id']);
                    $newContactgroupEntity->setAccess('*', false);
                    $newContactgroupEntity->setAccess(['name', 'description'], true);
                    $newContactgroupEntity = $ContactgroupsTable->patchEntity($newContactgroupEntity, $contactgroupData['Contactgroup']);
                    $newContactgroupData = $newContactgroupEntity->toArray();
                    $action = 'edit';
                }
                $ContactgroupsTable->save($newContactgroupEntity);

                $postData[$index]['Error'] = [];
                if ($newContactgroupEntity->hasErrors()) {
                    $hasErrors = true;
                    $postData[$index]['Error'] = $newContactgroupEntity->getErrors();
                } else {
                    //No errors
                    $postData[$index]['Contactgroup']['id'] = $newContactgroupEntity->get('id');

                    /** @var  ChangelogsTable $ChangelogsTable */
                    $ChangelogsTable = TableRegistry::getTableLocator()->get('Changelogs');

                    $changelog_data = $ChangelogsTable->parseDataForChangelog(
                        $action,
                        'contactgroups',
                        $postData[$index]['Contactgroup']['id'],
                        OBJECT_CONTACTGROUP,
                        $newContactgroupEntity->get('container')->get('parent_id'),
                        $userId,
                        $newContactgroupEntity->get('container')->get('name'),
                        ['Contactgroup' => $newContactgroupData]
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
        Cache::clear('permissions');
        $this->set('result', $postData);
        $this->viewBuilder()->setOption('serialize', ['result']);
    }

    /**
     * @param int|null $id
     */
    public function usedBy($id = null) {
        if (!$this->isApiRequest()) {
            //Only ship HTML template for angular
            return;
        }

        /** @var $ContactgroupsTable ContactgroupsTable */
        $ContactgroupsTable = TableRegistry::getTableLocator()->get('Contactgroups');

        if (!$ContactgroupsTable->existsById($id)) {
            throw new NotFoundException(__('Contact group not found'));
        }

        $ContactgroupsTable->addAssociations([
                'belongsToMany' => [
                    'Hosttemplates'      => [
                        'className' => 'Hosttemplates',
                        'joinTable' => 'contactgroups_to_hosttemplates',
                        'type'      => 'INNER'
                    ],
                    'Hosts'              => [
                        'className' => 'Hosts',
                        'joinTable' => 'contactgroups_to_hosts',
                        'type'      => 'INNER'
                    ],
                    'Servicetemplates'   => [
                        'className' => 'Servicetemplates',
                        'joinTable' => 'contactgroups_to_servicetemplates',
                        'type'      => 'INNER'
                    ],
                    'Services'           => [
                        'className' => 'Services',
                        'joinTable' => 'contactgroups_to_services',
                        'type'      => 'INNER'
                    ],
                    'Hostescalations'    => [
                        'className' => 'Hostescalations',
                        'joinTable' => 'contactgroups_to_hostescalations',
                        'type'      => 'INNER'
                    ],
                    'Serviceescalations' => [
                        'className' => 'Serviceescalations',
                        'joinTable' => 'contactgroups_to_serviceescalations',
                        'type'      => 'INNER'
                    ]

                ]
            ]
        );

        $contactgroupWithRelations = $ContactgroupsTable->find()
            ->where([
                'Contactgroups.id' => $id
            ])
            ->contain([
                'Containers'         => [
                    'fields' => [
                        'Containers.name'
                    ]
                ],
                'Hosttemplates'      => [
                    'fields' => [
                        'ContactgroupsToHosttemplates.contactgroup_id',
                        'Hosttemplates.id',
                        'Hosttemplates.name'
                    ]
                ],
                'Hosts'              => [
                    'fields' => [
                        'ContactgroupsToHosts.contactgroup_id',
                        'Hosts.id',
                        'Hosts.name',
                        'Hosts.address'
                    ]
                ],
                'Servicetemplates'   => [
                    'fields' => [
                        'ContactgroupsToServicetemplates.contactgroup_id',
                        'Servicetemplates.id',
                        'Servicetemplates.name'
                    ]
                ],
                'Services'           => [
                    'Hosts'            => [
                        'fields' => [
                            'Hosts.name'
                        ]
                    ],
                    'Servicetemplates' => [
                        'fields' => [
                            'Servicetemplates.name'
                        ]
                    ],
                    'fields'           => [
                        'ContactgroupsToServices.contactgroup_id',
                        'Services.id',
                        'Services.name'
                    ]
                ],
                'Hostescalations'    => [
                    'fields' => [
                        'ContactgroupsToHostescalations.contactgroup_id',
                        'Hostescalations.id'
                    ]
                ],
                'Serviceescalations' => [
                    'fields' => [
                        'ContactgroupsToServiceescalations.contactgroup_id',
                        'Serviceescalations.id'
                    ]
                ],
            ])
            ->disableHydration()
            ->first();

        /* Format service name for api "hostname|Service oder Service template name" */
        array_walk($contactgroupWithRelations['services'], function (&$service) {
            $serviceName = $service['name'];
            if (empty($service['name'])) {
                $serviceName = $service['servicetemplate']['name'];
            }
            $service['name'] = sprintf('%s|%s', $service['host']['name'], $serviceName);
        });

        //Sort host template, host, service template and service by name
        foreach (['hosttemplates', 'hosts', 'servicetemplates', 'services'] as $modelName) {
            $contactgroupWithRelations[$modelName] = Hash::sort($contactgroupWithRelations[$modelName], '{n}.name', 'asc', [
                    'type'       => 'natural',
                    'ignoreCase' => true
                ]
            );
        }

        if (!$this->allowedByContainerId(Hash::extract($contactgroupWithRelations, 'container_id'), false)) {
            $this->render403();
            return;
        }

        $total = 0;
        $total += sizeof($contactgroupWithRelations['serviceescalations']);
        $total += sizeof($contactgroupWithRelations['hostescalations']);
        $total += sizeof($contactgroupWithRelations['services']);
        $total += sizeof($contactgroupWithRelations['servicetemplates']);
        $total += sizeof($contactgroupWithRelations['hosts']);
        $total += sizeof($contactgroupWithRelations['hosttemplates']);

        $this->set('contactgroupWithRelations', $contactgroupWithRelations);
        $this->set('total', $total);
        $this->viewBuilder()->setOption('serialize', ['contactgroupWithRelations', 'total']);
        $this->set('back_url', $this->referer());
    }


    /****************************
     *       AJAX METHODS       *
     ****************************/

    public function loadContainers() {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var $ContainersTable ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');


        if ($this->hasRootPrivileges === true) {
            $containers = $ContainersTable->easyPath($this->MY_RIGHTS, OBJECT_CONTACTGROUP, [], $this->hasRootPrivileges);
        } else {
            $containers = $ContainersTable->easyPath($this->getWriteContainers(), OBJECT_CONTACTGROUP, [], $this->hasRootPrivileges);
        }

        $this->set('containers', Api::makeItJavaScriptAble($containers));
        $this->viewBuilder()->setOption('serialize', ['containers']);
    }

    public function loadContacts($containerIds = null) {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var $ContainersTable ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');
        /** @var $ContactsTable ContactsTable */
        $ContactsTable = TableRegistry::getTableLocator()->get('Contacts');

        $containerIds = $ContainersTable->resolveChildrenOfContainerIds($containerIds);
        $contacts = $ContactsTable->contactsByContainerId($containerIds, 'list');
        $contacts = Api::makeItJavaScriptAble($contacts);

        $data = [
            'contacts' => $contacts,
        ];
        $this->set($data);
        $this->viewBuilder()->setOption('serialize', array_keys($data));
    }
}
