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

class TimeRangeSerializer {

    /**
     * @var array
     */
    private $records;

    /**
     * @var UserTime
     */
    private $UserTime;


    /**
     * AcknowledgementSerializer constructor.
     * @param array $acknowledgementRecords
     * @param UserTime $UserTime
     */
    public function __construct($timeRangeRecords, UserTime $UserTime) {
        $this->records = $timeRangeRecords;
        $this->UserTime = $UserTime;
    }

    public function serialize() {
        $records = [];
        $size = sizeof($this->records);

        for ($i = 0; $i < $size; $i++) {
            $records[] = [
                'start' => $this->UserTime->customFormat('Y-m-d H:i:s', $this->records[$i]['start']),
                'end'   => $this->UserTime->customFormat('Y-m-d H:i:s', $this->records[$i]['end']),
                'type'  => 'background'
            ];
        }

        return $records;
    }
}
