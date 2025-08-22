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

namespace itnovum\openITCOCKPIT\Core\Timeline;


use itnovum\openITCOCKPIT\Core\Views\UserTime;

class NotificationSerializer {

    /**
     * @var array
     */
    private $records;

    /**
     * @var UserTime
     */
    private $UserTime;


    /**
     * @var int
     */
    private $groupId;


    /**
     * @var string
     */
    private $type;

    /**
     * NotificationSerializer constructor.
     * @param array $notificationRecords
     * @param UserTime $UserTime
     * @param string $type
     */
    public function __construct(array $notificationRecords, UserTime $UserTime, $type = 'host') {
        $this->records = $notificationRecords;
        $this->UserTime = $UserTime;

        $TimelineGroups = new Groups();
        $this->groupId = $TimelineGroups->getNotificationsId();
        $this->type = $type;
    }

    public function serialize() {
        $records = [];
        $size = sizeof($this->records);

        for ($i = 0; $i < $size; $i++) {
            if ($this->type === 'host') {
                $notificationTime = $this->records[$i]['NotificationHost']->getStartTime();
                $reasonType = $this->records[$i]['NotificationHost']->getReasonTypeString();
            } else {
                $notificationTime = $this->records[$i]['NotificationService']->getStartTime();
                $reasonType = $this->records[$i]['NotificationService']->getReasonTypeString();
            }
            $title = sprintf(
                '<b class="not-xss-filtered-html">%s</b> via <b class="not-xss-filtered-html">%s</b> at %s (%s: %s)',
                h($this->records[$i]['Contact']->getName()),
                h($this->records[$i]['Command']->getName()),
                $this->UserTime->format($notificationTime),
                __('Reason'),
                h($reasonType)
            );

            $records[] = [
                'start'     => $this->UserTime->customFormat('Y-m-d H:i:s', $notificationTime),
                'type'      => 'box',
                'className' => 'orange',
                'content'   => '<i class="not-xss-filtered-html fas fa-envelope"></i>',
                'title'     => $title,
                'group'     => $this->groupId
            ];
        }

        return $records;
    }
}
