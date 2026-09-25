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

namespace itnovum\openITCOCKPIT\Core\Timeline;


use itnovum\openITCOCKPIT\Core\Views\UserTime;

class NotificationsContactServicesSerializer {

    /**
     * @var array
     */
    private array $notificationTimeRange;

    /**
     * @var array
     */
    private array $filteredContacts;

    /**
     * @var array
     */
    private array $timePeriodNames;

    /**
     * @var UserTime
     */
    private $userTime;

    /**
     * @var int
     */
    private $groupId;


    /**
     * NotificationContactSerializer constructor.
     * @param array $notificationContactRecords
     * @param array $filteredContacts
     * @param array $timePeriodNames
     * @param UserTime $UserTime
     */

    public function __construct(array $notificationContactRecords, array $filteredContacts, array $timePeriodNames, UserTime $UserTime) {

        $this->notificationTimeRange = $notificationContactRecords;
        $this->filteredContacts = $filteredContacts;
        $this->timePeriodNames = $timePeriodNames;

        $this->userTime = $UserTime;
        $this->groupId = (new Groups())->getNotificationContactId();
    }

    public function serialize(): array {
        $result = [];

        foreach ($this->filteredContacts as $related_user) {
            $service_timeperiod_id = $related_user['service_timeperiod_id']; // [2,3,4]

            if (isset($service_timeperiod_id) && !empty($this->notificationTimeRange[$service_timeperiod_id])) {
                foreach ($this->notificationTimeRange[$service_timeperiod_id] as $timeRange) {

                    $periodName = "";

                    if (isset($this->timePeriodNames[$service_timeperiod_id])) {
                        $periodName = $this->timePeriodNames[$service_timeperiod_id];
                    }

                    $content = '<b class="not-xss-filtered-html timeline-contact-badge d-inline-block lh-1 fs-xs">';

                    $isEnabled = ($related_user['service_notifications_enabled'] ?? 0) === 1;
                    $content .= $related_user['name'] !== '' ? '<b class="item-title mx-1">' . $related_user['name'] . '</b><br/>' : '';
                    $content .= '<b class="badge badge-envelope bg-light margin-right position-relative d-inline-flex align-items-center justify-content-center px-1 mx-1"><i class="fa-solid fa-envelope fs-6  " ></i><i class="fa-solid badge-marke ' . ($isEnabled ? 'fa-check text-success' : 'fa-xmark text-danger') . ' position-absolute bottom-0 end-0 fs-6 " ></i></b>';

                    if ($isEnabled) {
                        $content .= $related_user['notify_service_recovery'] === 1 ? '<b class="badge bg-success me-1">R</b> ' : '';
                        $content .= $related_user['notify_service_warning'] === 1 ? '<b class="badge bg-warning me-1">W</b> ' : '';
                        $content .= $related_user['notify_service_unknown'] === 1 ? '<b class="badge bg-secondary me-1">U</b> ' : '';
                        $content .= $related_user['notify_service_critical'] === 1 ? '<b class="badge bg-danger me-1">C</b> ' : '';
                        $content .= $related_user['notify_service_flapping'] === 1 ? '<b class="badge bg-primary me-1"><i class="fa-solid fa-circle"></i></b> ' : '';
                        $content .= $related_user['notify_service_downtime'] === 1 ? '<b class="badge bg-primary me-1"><i class="fa-solid fa-power-off"></i></b></b> ' : '';
                    }

                    $content .= '</b>';

                    $start = $this->userTime->customFormat('Y-m-d H:i:s', $timeRange['start']) ?? '';
                    $end = $this->userTime->customFormat('Y-m-d H:i:s', $timeRange['end']) ?? '';
                    $start_label = $this->userTime->customFormat('H:i', $timeRange['start']) ?? '';
                    $end_label = $this->userTime->customFormat('H:i', $timeRange['end']) ?? '';

                    $title = sprintf('<b class="vis-item-content-username">%s - </b><i class="vis-item-contact-title"><b>%s %s <i>[%s]</i>:</b>  (%s - %s)</i>', h($related_user['name']), ($isEnabled ? __('Active') : __('Inactive')), __('Timeperiod'), h($periodName), h($start_label), h($end_label));

                    $result[] = [
                        'start'     => $start,
                        'end'       => $end,
                        //'type'      => 'box',
                        'className' => "vis-items " . ($isEnabled ? ' ' : 'vis-items-disabled '),
                        'content'   => $content,
                        'title'     => $title,
                        'group'     => $this->groupId
                    ];
                }
            }
        }

        return $result;
    }
}
