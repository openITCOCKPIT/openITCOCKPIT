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

namespace App\Controller;

use App\itnovum\openITCOCKPIT\Filter\EventlogsFilter;
use App\Model\Table\EventlogsTable;
use Cake\ORM\TableRegistry;
use itnovum\openITCOCKPIT\Core\ValueObjects\User;
use itnovum\openITCOCKPIT\Database\PaginateOMat;

class EventlogsController extends AppController {

    public function index() {
        if (!$this->isApiRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }

        /** @var EventlogsTable $EventlogsTable */
        $EventlogsTable = TableRegistry::getTableLocator()->get('Eventlogs');

        $User = new User($this->getUser());
        $UserTime = $User->getUserTime();

        $EventlogsFilter = new EventlogsFilter($this->request, $UserTime);
        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $EventlogsFilter->getPage());
        $MY_RIGHTS = $this->MY_RIGHTS;
        if ($this->hasRootPrivileges === true) {
            $MY_RIGHTS = [];
        }

        $logTypes = $this->request->getQuery('types', []);

        $all_events = [];
        if (!empty($logTypes)) {
            $all_events = $EventlogsTable->getEventlogIndex($EventlogsFilter, $logTypes, $PaginateOMat, $MY_RIGHTS, false);
        }

        foreach ($all_events as $index => $event) {
            $changeTimestamp = $event['created']->getTimestamp();
            $all_events[$index]['time'] = $UserTime->format($changeTimestamp);
            $all_events[$index]['recordExists'] = $EventlogsTable->recordExists($event['model'], $event['object_id']);
            $all_events[$index]['actorExists'] = ($event['actor'] === 'System') || $EventlogsTable->recordExists('User', $event['user_id']);
            $dataJson = json_decode($all_events[$index]['data'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $all_events[$index]['data'] = $dataJson;
            }
        }

        $typeTranslations = $this->getTypeTranslations();
        $typeIconClasses = $this->getTypeIconClasses();

        $this->set('all_events', $all_events);
        $this->set('logTypes', $logTypes);
        $this->set('typeTranslations', $typeTranslations);
        $this->set('typeIconClasses', $typeIconClasses);
        $this->viewBuilder()->setOption('serialize', ['all_events', 'logTypes', 'typeTranslations', 'typeIconClasses']);
    }

    /**
     * USED BY THE NEW ANGULAR FRONTEND !!
     * @return void
     */
    public function listToPdf() {

        /** @var EventlogsTable $EventlogsTable */
        $EventlogsTable = TableRegistry::getTableLocator()->get('Eventlogs');

        $EventlogsFilter = new EventlogsFilter($this->request);

        $MY_RIGHTS = $this->MY_RIGHTS;
        if ($this->hasRootPrivileges === true) {
            $MY_RIGHTS = [];
        }

        $logTypes = $this->request->getQuery('types', []);

        $all_events = [];
        if (!empty($logTypes)) {
            $all_events = $EventlogsTable->getEventlogIndex($EventlogsFilter, $logTypes, null, $MY_RIGHTS, false);
        }

        $User = new User($this->getUser());
        $UserTime = $User->getUserTime();

        foreach ($all_events as $index => $event) {
            $changeTimestamp = $event['created']->getTimestamp();
            $all_events[$index]['time'] = $UserTime->format($changeTimestamp);
            $all_events[$index]['recordExists'] = $EventlogsTable->recordExists($event['model'], $event['object_id']);
            $dataJson = json_decode($all_events[$index]['data'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $all_events[$index]['data'] = $dataJson;
            }
        }

        $typeTranslations = $this->getTypeTranslations();
        $typeIconClasses = $this->getTypeIconClasses();

        $this->set('all_events', $all_events);
        $this->set('logTypes', $logTypes);
        $this->set('typeTranslations', $typeTranslations);
        $this->set('typeIconClasses', $typeIconClasses);

        $this->viewBuilder()->setOption(
            'pdfConfig',
            [
                'download' => true,
                'filename' => __('Eventlogs_') . date('dmY_his') . '.pdf',
            ]
        );
    }

    public function listToCsv() {

        /** @var EventlogsTable $EventlogsTable */
        $EventlogsTable = TableRegistry::getTableLocator()->get('Eventlogs');

        $EventlogsFilter = new EventlogsFilter($this->request);

        $MY_RIGHTS = $this->MY_RIGHTS;
        if ($this->hasRootPrivileges === true) {
            $MY_RIGHTS = [];
        }

        $logTypes = $this->request->getQuery('types', []);

        $events = [];
        if (!empty($logTypes)) {
            $events = $EventlogsTable->getEventlogIndex($EventlogsFilter, $logTypes, null, $MY_RIGHTS, false);
        }

        $User = new User($this->getUser());
        $UserTime = $User->getUserTime();

        $typeTranslations = $this->getTypeTranslations();

        $all_events = [];

        foreach ($events as $index => $event) {
            $changeTimestamp = $event['created']->getTimestamp();
            $events[$index]['time'] = $UserTime->format($changeTimestamp);
            $recordExists = $EventlogsTable->recordExists($event['model'], $event['object_id']);
            $dataJson = json_decode($event['data'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $events[$index]['data'] = $dataJson;
            }

            $all_events[$index] = [
                $typeTranslations[$event['type']],
                $event['name']
            ];

            if (in_array('login', $logTypes) || in_array('user_delete', $logTypes) || in_array('user_password_change', $logTypes)) {
                if ($recordExists) {
                    $all_events[$index][] = $event['user_email'];
                } else {
                    $all_events[$index][] = $events[$index]['data']['user_email'];
                }
            }
            $all_events[$index][] = $events[$index]['time'];

        }

        $header = [
            'event_type',
        ];

        if (in_array('login', $logTypes) || in_array('user_delete', $logTypes) || in_array('user_password_change', $logTypes)) {
            $header[] = 'name';
            $header[] = 'user_email';
        }

        $header[] = 'time';

        $this->set('data', $all_events);

        $filename = __('Eventlogs_') . date('dmY_his') . '.csv';
        $this->setResponse($this->getResponse()->withDownload($filename));
        $this->viewBuilder()
            ->setClassName('CsvView.Csv')
            ->setOptions([
                'delimiter' => ';', // Excel prefers ; over ,
                'bom'       => true, // Fix UTF-8 umlauts in Excel
                'serialize' => 'data',
                'header'    => $header,
            ]);
    }

    private function getTypeTranslations() {
        return [
            'login'                => __('User Login'),
            'user_delete'          => __('User Deleted'),
            'user_password_change' => __('User Password Changed'),
            'notification_enabled' => __('Notification Enabled'),
            'notification_disabled' => __('Notification Disabled'),
            'flapdetection_enabled' => __('Flap Detection Enabled'),
            'flapdetection_disabled' => __('Flap Detection Disabled'),
            'custom_notification_sent' => __('Custom Notification Sent'),
            'passive_transfer_check_result' => __('Passive Check Result Received'),
            'acknowledgement_created' => __('Acknowledgement Created'),
            'acknowledgement_deleted' => __('Acknowledgement Deleted'),
            'check_rescheduled' => __('Check Rescheduled'),
            'downtime_created' => __('Downtime Scheduled'),
            'downtime_deleted' => __('Downtime Deleted')
        ];
    }

    private function getTypeTranslationsNew() {
        return [
            'login' => ['title' => __('User Login'), 'description' => __('logged in to the system')],
            'user_delete' => ['title' => __('User Deleted'), 'description' => __('delete user account')],
            'user_password_change' => ['title' => __('User Password Changed'), 'description' => __('change user password')],
            'notification_enabled' => ['title' => __('Notification Enabled'), 'description' => __('enable notifications')],
            'notification_disabled' => ['title' => __('Notification Disabled'), 'description' => __('disable notifications')],
            'flapdetection_enabled' => ['title' => __('Flap Detection Enabled'), 'description' => __('enable Flap detection')],
            'flapdetection_disabled' => ['title' => __('Flap Detection Disabled'), 'description' => __('disable Flap detection')],
            'custom_notification_sent' => ['title' => __('Custom Notification Sent'), 'description' => __('sent custom Notification ')],
            'passive_transfer_check_result' => ['title' => __('Passive Check Result Received'), 'description' => __('passive Check Result Received')],
            'acknowledgement_created' => ['title' => __('Acknowledgement Created'), 'description' => __('create Acknowledgement')],
            'acknowledgement_deleted' => ['title' => __('Acknowledgement Deleted'), 'description' => __('delete Acknowledgement')],
            'check_rescheduled' => ['title' => __('Check Rescheduled'), 'description' => __('run Rescheduled check')],
            'downtime_created' => ['title' => __('Downtime Scheduled'), 'description' => __('scheduled Downtime')],
            'downtime_deleted' => ['title' => __('Downtime Deleted'), 'description' => __('canceled Downtime')],
        ];
    }

    private function getTypeIconClasses() {
        return [
            'default' => [
                'icon'      => ['fas', 'question'],
                'iconPdf'   => 'fa fa-question',
                'className' => 'text-secondary'
            ],
            'login'                => [
                'icon'      => ['fas', 'door-open'],
                'iconPdf'   => 'fa fa-door-open',
                'className' => 'text-success'
            ],
            'user_delete'          => [
                'icon'      => ['fas', 'trash'],
                'iconPdf'   => 'fa fa-trash',
                'className' => 'text-danger'
            ],
            'user_password_change' => [
                'icon'      => ['fas', 'key'],
                'iconPdf'   => 'fa fa-key',
                'className' => 'text-primary'
            ],
            'notification_enabled' => [
                'icon'      => ['fas', 'envelope'],
                'iconPdf'   => 'fa fa-envelope',
                'className' => 'text-success'
            ],
            'notification_disabled' => [
                'icon'      => ['fas', 'envelope'],
                'iconPdf'   => 'fa fa-envelope',
                'className' => 'text-danger'
            ],
            'flapdetection_enabled' => [
                'icon'      => ['fas', 'circle-half-stroke'],
                'iconPdf'   => 'fa fa-circle-half-stroke',
                'className' => 'text-primary'
            ],
            'flapdetection_disabled' => [
                'icon'      => ['fas', 'circle-half-stroke'],
                'iconPdf'   => 'fa fa-circle-half-stroke',
                'className' => 'text-danger'
            ],
            'custom_notification_sent' => [
                'icon'      => ['fas', 'paper-plane'],
                'iconPdf'   => 'fa fa-paper-plane',
                'className' => 'text-info'
            ],
            'passive_transfer_check_result' => [
                'icon'      => ['fas', 'download'],
                'iconPdf'   => 'fa fa-download',
                'className' => 'text-secondary'
            ],
            'acknowledgement_created' => [
                'icon'      => ['fas', 'user'],
                'iconPdf'   => 'fa fa-user',
                'className' => 'text-secondary'
            ],
            'acknowledgement_deleted' => [
                'icon'      => ['fas', 'trash'],
                'iconPdf'   => 'fa fa-trash',
                'className' => 'text-danger'
            ],
            'check_rescheduled' => [
                'icon'      => ['fas', 'refresh'],
                'iconPdf'   => 'fa fa-refresh',
                'className' => 'text-secondary'
            ],
            'downtime_created' => [
                'icon'      => ['fas', 'power-off'],
                'iconPdf'   => 'fa fa-power-off',
                'className' => 'text-success'
            ],
            'downtime_deleted' => [
                'icon'      => ['fas', 'power-off'],
                'iconPdf'   => 'fa fa-power-off',
                'className' => 'text-danger'
            ]
        ];

    }

}
