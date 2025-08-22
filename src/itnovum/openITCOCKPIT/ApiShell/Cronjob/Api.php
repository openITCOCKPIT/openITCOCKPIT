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

namespace itnovum\openITCOCKPIT\ApiShell\Cronjob;

use itnovum\openITCOCKPIT\ApiShell\CoreApi;
use itnovum\openITCOCKPIT\ApiShell\Interfaces\ApiInterface;
use itnovum\openITCOCKPIT\ApiShell\OptionParser;

/**
 * Class Api
 * @package itnovum\openITCOCKPIT\ApiShell\Cronjob
 * @property \Cronjob $Database
 */
class Api extends CoreApi implements ApiInterface {

    /**
     * @var OptionParser
     */
    private $optionParser;

    /**
     * @var array
     */
    private $data;

    public function setOptionsFromOptionParser(OptionParser $optionParser) {
        $this->optionParser = $optionParser;
        $this->data = $optionParser->getData();
    }

    public function dispatchRequest() {
        switch ($this->optionParser->getAction()) {
            case 'create_missing_cronjobs':
                $this->create_missing_cronjobs();
                break;
        }
    }

    /**
     * @throws \Exception
     */
    public function create_missing_cronjobs() {
        //Check if load cronjob exists
        if (!$this->Database->checkForCronjob('CpuLoad', 'Core')) {
            //Cron does not exists, so we create it
            $this->Database->add('CpuLoad', 'Core', 15);
        }

        //Check if version check cronjob exists
        if (!$this->Database->checkForCronjob('VersionCheck', 'Core')) {
            //Cron does not exists, so we create it
            $this->Database->add('VersionCheck', 'Core', 1440);
        }

        //Check if instantreport cronjob exists
        if (!$this->Database->checkForCronjob('InstantReport', 'Core')) {
            //Cron does not exists, so we create it
            $this->Database->add('InstantReport', 'Core', 1440);
        }

        //Check if SystemHealth cronjob exists
        if (!$this->Database->checkForCronjob('SystemHealth', 'Core')) {
            //Cron does not exists, so we create it
            $this->Database->add('SystemHealth', 'Core', 1);
        }

        //Check if SystemMetrics cronjob exists
        if (!$this->Database->checkForCronjob('SystemMetrics', 'Core')) {
            //Cron does not exists, so we create it
            $this->Database->add('SystemMetrics', 'Core', 240);
        }

        //Check if ConfigGenerator cronjob exists
        if (!$this->Database->checkForCronjob('ConfigGenerator', 'Core')) {
            //Cron does not exists, so we create it
            $this->Database->add('ConfigGenerator', 'Core', 1);
        }
    }

}
