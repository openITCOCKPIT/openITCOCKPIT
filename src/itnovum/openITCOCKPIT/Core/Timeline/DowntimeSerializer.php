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

class DowntimeSerializer {

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
     * @var bool
     */
    private $escapeHtml = true;

    /**
     * DowntimeSerializer constructor.
     * @param array $downtimeRecords
     * @param UserTime $UserTime
     */
    public function __construct($downtimeRecords, UserTime $UserTime, $escapeHtml = true) {
        $this->records = $downtimeRecords;
        $this->UserTime = $UserTime;
        $this->escapeHtml = $escapeHtml;

        $TimelineGroups = new Groups();
        $this->groupId = $TimelineGroups->getDowntimesId();

    }

    /**
     * @return array
     */
    public function serialize() {
        $records = [];
        $size = sizeof($this->records);

        for ($i = 0; $i < $size; $i++) {
            $title = sprintf('%s: %s', $this->records[$i]->getAuthorName(), $this->records[$i]->getCommentData());

            if ($this->escapeHtml) {
                $title = h($title);
            }

            if ($this->records[$i]->wasCancelled()) {
                $title = sprintf(
                    '%s (%s at %s)',
                    $title,
                    __(' Cancelled'),
                    $this->UserTime->format($this->records[$i]->getActualEndTime())
                );

                if ($this->escapeHtml) {
                    $title = h($title);
                }
                $records[] = [
                    'start'     => $this->UserTime->customFormat('Y-m-d H:i:s', $this->records[$i]->getScheduledStartTime()),
                    'end'       => $this->UserTime->customFormat('Y-m-d H:i:s', $this->records[$i]->getActualEndTime()),
                    'type'      => 'range',
                    'className' => 'bg-downtime-cancelled text-white',
                    'content'   => $title,
                    'title'     => $title,
                    'group'     => $this->groupId
                ];
            } else {
                $records[] = [
                    'start'     => $this->UserTime->customFormat('Y-m-d H:i:s', $this->records[$i]->getScheduledStartTime()),
                    'end'       => $this->UserTime->customFormat('Y-m-d H:i:s', $this->records[$i]->getScheduledEndTime()),
                    'type'      => 'range',
                    'className' => 'bg-downtime text-white',
                    'content'   => $title,
                    'title'     => $title,
                    'group'     => $this->groupId
                ];
            }

        }

        return $records;
    }
}

