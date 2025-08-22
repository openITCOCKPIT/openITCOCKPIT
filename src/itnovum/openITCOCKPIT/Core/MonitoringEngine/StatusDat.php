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

namespace itnovum\openITCOCKPIT\Core\MonitoringEngine;


class StatusDat {

    /**
     * @var string
     */
    private $statusdatPath;

    /**
     * @var array
     */
    private $downtimeContent = [];

    /**
     * StatusDat constructor.
     * @param string $statusdatPath
     * @throws \RuntimeException
     */
    public function __construct($statusdatPath) {
        $this->statusdatPath = $statusdatPath;
        if (!file_exists($this->statusdatPath)) {
            throw new \RuntimeException(
                sprintf('Error: File "%s" does not exists!', $this->statusdatPath)
            );
        }
    }

    private function loadDowntimeRecords() {
        $this->downtimeContent = [];
        $saveContent = false;
        $statusdat = fopen($this->statusdatPath, "r");
        while (!feof($statusdat)) {
            $line = trim(fgets($statusdat));
            if ($line == "hostdowntime {" || $line == "servicedowntime {") {
                $saveContent = true;
                $section = [];
                continue;
            }

            if ($line == "}" && $saveContent === true) {
                $saveContent = false;
                $this->downtimeContent[] = $section;
            }

            if ($saveContent) {
                $tmp = explode('=', $line);
                $section[$tmp[0]] = $tmp[1];
                unset($tmp);
            }
        }
    }

    /**
     * @return array
     */
    public function parseDowntimes() {
        if (empty($this->downtimeContent)) {
            $this->loadDowntimeRecords();
        }

        return $this->downtimeContent;
    }

    /**
     * @param $downtimeId
     * @param $comment
     * @return bool
     */
    public function checkIfRecurringDowntimeWasScheduled($downtimeId, $comment) {
        if (empty($this->downtimeContent)) {
            $this->loadDowntimeRecords();
        }

        foreach ($this->downtimeContent as $downtime) {
            if ($downtime['comment'] === sprintf('AUTO[%s]: %s', $downtimeId, $comment)) {
                return true;
            }
        }
        return false;
    }

}
