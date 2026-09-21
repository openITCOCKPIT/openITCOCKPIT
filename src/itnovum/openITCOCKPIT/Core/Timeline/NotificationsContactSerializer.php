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

class NotificationsContactSerializer {

    /**
     * @var array
     */
    private array $notificationTimerange;

    /**
     * @var array
     */
    private array $filteredContacts;

    /**
     * @var array
     */
    private array $contactNotificationPeriods;

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
     * @param array $contactNotificationPeriods
     * @param UserTime $UserTime
     */

    public function __construct(array $notificationContactRecords, array $filteredContacts, array $contactNotificationPeriods, UserTime $UserTime) {

        $this->notificationTimerange = $notificationContactRecords;
        $this->filteredContacts = $filteredContacts;
        $this->contactNotificationPeriods = $contactNotificationPeriods;
        $this->userTime = $UserTime;

        $this->groupId = (new Groups())->getNotificationContactId();
    }

    public function serialize(): array {
        $result = [];

        $contactsCollection = new \Cake\Collection\Collection($this->filteredContacts);

        foreach ($this->contactNotificationPeriods as $period) {

            $timeperiod_id = $period['id'];
            $periodName = $period['name'];

            if (isset($timeperiod_id)) {
                $related_user = $contactsCollection->firstMatch(['host_timeperiod_id' => $timeperiod_id]);

                foreach ($this->notificationTimerange[$timeperiod_id] as $timerangeItem) {
                    $content = '<b class="not-xss-filtered-html timeline-contact-badge d-inline-block lh-1 fs-xs">';

                    if (isset($related_user)) {
                        $isEnabled = ($related_user['host_notifications_enabled'] ?? 0) === 1;
                        $content .= $related_user['name'] !== '' ? '<b class="item-title">' . $related_user['name'] . '</b><br>' : '';
                        $content .= '<b class="badge badge-envelope bg-light  text-dark m-1 "><i class="fa-solid fa-envelope text-primary"></i><i class="fa-solid ms-1 ' . ($isEnabled ? 'fa-check text-success' : 'fa-times text-danger') . '"></i></b> ';

                        $content .= $related_user['notify_host_recovery'] === 1 ? '<b class="badge bg-success me-1">R</b> ' : '';
                        $content .= $related_user['notify_host_down'] === 1 ? '<b class="badge bg-danger me-1">D</b> ' : '';
                        $content .= $related_user['notify_host_unreachable'] === 1 ? '<b class="badge bg-secondary me-1">U</b> ' : '';
                        $content .= $related_user['notify_host_flapping'] === 1 ? '<b class="badge bg-primary me-1"><i class="fa-solid fa-circle"></i></b> ' : '';
                        $content .= $related_user['notify_host_downtime'] === 1 ? '<b class="badge bg-primary me-1"><i class="fa-solid fa-power-off"></i></b></b> ' : '';
                    }

                    $content .= '</b>';

                    $start = $this->userTime->customFormat('Y-m-d H:i:s', $timerangeItem['start']) ?? '';
                    $end = $this->userTime->customFormat('Y-m-d H:i:s', $timerangeItem['end']) ?? '';
                    $start_label = $this->userTime->customFormat('H:i', $timerangeItem['start']) ?? '';
                    $end_label = $this->userTime->customFormat('H:i', $timerangeItem['end']) ?? '';

                    $title = sprintf('<i class="vis-item-contact-title"><b>%s %s <i>%s</i>:</b>  (%s - %s)</i>', ($isEnabled ? __('Active') : __('Inactive')), __('Timeperiod'), h($periodName), h($start_label), h($end_label));

                    $result[] = [
                        'start'     => $start,
                        'end'       => $end,
                        'type'      => 'box',
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
