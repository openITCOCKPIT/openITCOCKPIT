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

use App\Model\Table\DocumentationsTable;
use App\Model\Table\HostsTable;
use App\Model\Table\HosttemplatesTable;
use App\Model\Table\ServicesTable;
use App\Model\Table\ServicetemplatesTable;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\NotFoundException;
use Cake\I18n\DateTime;
use Cake\ORM\TableRegistry;
use Exception;
use itnovum\openITCOCKPIT\Core\ValueObjects\User;
use ParsedownExtra;

/**
 * Class DocumentationsController
 * @package App\Controller
 */
class DocumentationsController extends AppController {
    /**
     * @param null $uuid
     * @param string $type
     * @throws Exception
     */
    public function view($uuid = null, $type = 'host') {
        if (!$this->isAngularJsRequest()) {
            //Only ship template
            return;
        }

        if (empty($type)) {
            throw new BadRequestException();
        }
        $type = strtolower($type);

        /** @var $DocumentationsTable DocumentationsTable */
        $DocumentationsTable = TableRegistry::getTableLocator()->get('Documentations');

        switch ($type) {
            case 'host':
                /** @var $HostsTable HostsTable */
                $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
                $host = $HostsTable->getHostByUuid($uuid);
                $objectId = $host->get('id');
                $uuid = $host->get('uuid');

                //Can user see this object?
                if (!$this->allowedByContainerId($host->getContainerIds(), false)) {
                    $this->render403();
                    return;
                }

                //Can user edit this object?
                $allowEdit = $this->allowedByContainerId($host->getContainerIds());

                $objectName = 'HOST - Not used by object host'; //Not used by object host

                break;

            case 'service':
                /** @var $ServicesTable ServicesTable */
                $ServicesTable = TableRegistry::getTableLocator()->get('Services');
                $service = $ServicesTable->getServiceByUuid($uuid);
                $objectId = $service->get('id');
                $uuid = $service->get('uuid');

                //Can user see this object?
                if (!$this->allowedByContainerId($service->get('host')->getContainerIds(), false)) {
                    $this->render403();
                    return;
                }

                //Can user edit this object?
                $allowEdit = $this->allowedByContainerId($service->get('host')->getContainerIds());

                $objectName = 'SERVICE - Not used by object service'; //Not used by object service

                break;

            case 'hosttemplate':
                /** @var $HosttemplatesTable HosttemplatesTable */
                $HosttemplatesTable = TableRegistry::getTableLocator()->get('Hosttemplates');
                $hosttemplate = $HosttemplatesTable->getHosttemplateByUuid($uuid);
                $objectId = $hosttemplate['Hosttemplate']['id'];
                $uuid = $hosttemplate['Hosttemplate']['uuid'];

                //Can user see this object?
                if (!$this->allowedByContainerId($hosttemplate['Hosttemplate']['container_id'], false)) {
                    $this->render403();
                    return;
                }

                //Can user edit this object?
                $allowEdit = $this->allowedByContainerId($hosttemplate['Hosttemplate']['container_id']);

                $objectName = $hosttemplate['Hosttemplate']['name'];
                break;

            case 'servicetemplate':
                /** @var $ServicetemplatesTable ServicetemplatesTable */
                $ServicetemplatesTable = TableRegistry::getTableLocator()->get('Servicetemplates');
                $servicetemplate = $ServicetemplatesTable->getServicetemplateByUuid($uuid);
                $objectId = $servicetemplate['Servicetemplate']['id'];
                $uuid = $servicetemplate['Servicetemplate']['uuid'];

                //Can user see this object?
                if (!$this->allowedByContainerId($servicetemplate['Servicetemplate']['container_id'], false)) {
                    $this->render403();
                    return;
                }

                //Can user edit this object?
                $allowEdit = $this->allowedByContainerId($servicetemplate['Servicetemplate']['container_id']);

                $objectName = $servicetemplate['Servicetemplate']['template_name'];

                break;

            default:
                throw new BadRequestException('Type not supported.');
                break;
        }


        if ($this->request->is('get') && $this->isAngularJsRequest()) {
            $User = new User($this->getUser());
            $UserTime = $User->getUserTime();

            $docuExists = $DocumentationsTable->existsByUuid($uuid);
            $lastUpdate = $UserTime->format(time());
            $content = '';
            if ($docuExists) {
                $documentation = $DocumentationsTable->getDocumentationByUuid($uuid);

                /** @var \Cake\I18n\DateTime $modified */
                $modified = $documentation->get('modified');
                $lastUpdate = $UserTime->format($modified->getTimestamp());
                $content = $documentation->get('content');
            }

            $this->set('lastUpdate', $lastUpdate);
            $this->set('allowEdit', $allowEdit);
            $this->set('docuExists', $docuExists);
            $this->set('bbcode', $content);
            $this->set('objectId', $objectId);
            $this->set('objectName', $objectName);
            $this->viewBuilder()->setOption('serialize', ['lastUpdate', 'allowEdit', 'docuExists', 'bbcode', 'objectId', 'objectName']);

            return;
        }

        if ($this->request->is('post') || $this->request->is('put')) {
            if ($allowEdit === false) {
                $this->render403();
                return;
            }
            $content = $this->request->getData('content');

            if ($DocumentationsTable->existsByUuid($uuid)) {
                $entity = $DocumentationsTable->getDocumentationByUuid($uuid);
            } else {
                $entity = $DocumentationsTable->newEmptyEntity();
            }

            $entity = $DocumentationsTable->patchEntity($entity, [
                'content' => $content,
                'uuid'    => $uuid
            ]);

            if (strlen(trim($content)) > 0) {
                $DocumentationsTable->save($entity);
                if ($entity->hasErrors()) {
                    $this->response = $this->response->withStatus(400);
                    $this->set('error', $entity->getErrors());
                    $this->viewBuilder()->setOption('serialize', ['error']);
                    return;
                }
            } else {
                //Delete existing record if any
                $DocumentationsTable->deleteDocumentationByUuid($uuid);
            }

            $this->set('documentation', $entity);
            $this->viewBuilder()->setOption('serialize', ['documentation']);
            return;

        }
    }

    /**
     * @throws Exception
     */
    public function wiki() {
        if (!$this->isAngularJsRequest()) {
            //Only ship template
            return;
        }

        $documentations = [
            'additional_help' => [
                'name'      => ('Additional Help'),
                'directory' => 'additional_help',
                'children'  => [
                    'mysql_performance'          => [
                        'name'        => __('MySQL performance'),
                        'description' => __('A few tips to optimize your MySQL performance.'),
                        'file'        => 'mysql_performance',
                        'icon'        => ['fas', 'database'],
                    ],
                    'browser_push_notifications' => [
                        'name'        => __('Browser Push Notifications'),
                        'description' => __('How to setup openITCOCKPIT browser push notifications.'),
                        'file'        => 'browser_push_notifications',
                        'icon'        => ['fas', 'bell'],
                    ],
                ],
            ],
        ];

        if ($this->request->is('get')) {
            $this->set('documentations', $documentations);
            $this->viewBuilder()->setOption('serialize', ['documentations']);
            return;
        }

        if ($this->request->is('post')) {

            $category = $this->request->getData('category');
            $documentation = $this->request->getData('documentation');

            if (!isset($documentations[$category]['children'][$documentation])) {
                throw new NotFoundException();
            }

            $file = ROOT . DS . 'docs' . DS . 'en' . DS . $documentations[$category]['directory'] . DS . $documentations[$category]['children'][$documentation]['file'] . '.md';
            if (!file_exists($file)) {
                throw new NotFoundException('Markdown file not found!');
            }

            $parsedown = new ParsedownExtra();
            $html = $parsedown->text(file_get_contents($file));

            $this->set('documentation', $documentations[$category]['children'][$documentation]);
            $this->set('html', $html);
            $this->viewBuilder()->setOption('serialize', ['documentation', 'html']);
            return;
        }
    }
}
