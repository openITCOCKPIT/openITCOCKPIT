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

namespace App\itnovum\openITCOCKPIT\Export;

/**
 * Class DependencyCollector
 *
 * This class holds a mapping of IDs to UUIDs for the export of configuration objects as JSON file
 */
class DependencyCollector {

    /**
     * @var array
     * [id]=>uuid
     */
    private $commands = [];

    /**
     * @var array
     * [id]=>uuid
     */
    private $timeperiods = [];

    /**
     * @var array
     * [id]=>uuid
     */
    private $contacts = [];

    /**
     * @var array
     * [id]=>uuid
     */
    private $contactgroups = [];

    /**
     * @var array
     * [id]=>uuid
     */
    private $servicetemplates = [];

    /**
     * @var array
     * [id]=>uuid
     */
    private $calendars = [];

    public function addCommand(int $id, string $uuid) {
        $this->commands[$id] = $uuid;
    }

    public function addTimperiod(int $id, string $uuid) {
        $this->timeperiods[$id] = $uuid;
    }

    public function addContact(int $id, string $uuid) {
        $this->contacts[$id] = $uuid;
    }

    public function addContactgroup(int $id, string $uuid) {
        $this->contactgroups[$id] = $uuid;
    }

    public function addServicetemplate(int $id, string $uuid) {
        $this->servicetemplates[$id] = $uuid;
    }

    public function addCalendar(int $id, string $uuid) {
        $this->calendars[$id] = $uuid;
    }

    /**
     * @return array
     */
    public function getCommands(): array {
        return $this->commands;
    }

    /**
     * @return array
     */
    public function getTimeperiods(): array {
        return $this->timeperiods;
    }

    /**
     * @return array
     */
    public function getContacts(): array {
        return $this->contacts;
    }

    /**
     * @return array
     */
    public function getContactgroups(): array {
        return $this->contactgroups;
    }

    /**
     * @return array
     */
    public function getServicetemplates(): array {
        return $this->servicetemplates;
    }

    /**
     * @return array
     */
    public function getCalendars(): array {
        return $this->calendars;
    }
}
