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

namespace itnovum\openITCOCKPIT\Core;


use App\Lib\Exceptions\MissingDbBackendException;
use App\Lib\Interfaces\AcknowledgementHostsTableInterface;
use App\Lib\Interfaces\AcknowledgementServicesTableInterface;
use App\Lib\Interfaces\DowntimehistoryHostsTableInterface;
use App\Lib\Interfaces\DowntimehistoryServicesTableInterface;
use App\Lib\Interfaces\HostchecksTableInterface;
use App\Lib\Interfaces\HoststatusTableInterface;
use App\Lib\Interfaces\LogentriesTableInterface;
use App\Lib\Interfaces\NotificationHostsLogTableInterface;
use App\Lib\Interfaces\NotificationHostsTableInterface;
use App\Lib\Interfaces\NotificationServicesLogTableInterface;
use App\Lib\Interfaces\NotificationServicesTableInterface;
use App\Lib\Interfaces\ServicechecksTableInterface;
use App\Lib\Interfaces\ServicestatusTableInterface;
use App\Lib\Interfaces\StatehistoryHostTableInterface;
use App\Lib\Interfaces\StatehistoryServiceTableInterface;
use Cake\Core\Configure;
use Cake\ORM\TableRegistry;

class DbBackend {

    /**
     * @var string
     */
    private $backend;

    /**
     * DbBackend constructor.
     */
    public function __construct() {
        $configFile = CONFIG . 'dbbackend.php';
        if (file_exists($configFile)) {
            Configure::load('dbbackend');
            $this->backend = Configure::read('dbbackend');
        } else {
            //Use default backend as fallback
            $this->backend = 'Statusengine3';
        }
    }

    /**
     * @return bool
     */
    public function isNdoUtils() {
        return $this->backend === 'Nagios';
    }

    /**
     * @return bool
     */
    public function isCrateDb() {
        return $this->backend === 'Crate';
    }

    /**
     * @return bool
     */
    public function isStatusengine3() {
        return $this->backend === 'Statusengine3';
    }

    /**
     * @return string
     */
    public function getBackendAsString() {
        return $this->backend;
    }

    /**
     * @return HoststatusTableInterface
     * @throws MissingDbBackendException
     */
    public function getHoststatusTable() {
        if ($this->isNdoUtils()) {
            /** @var $HoststatusTable HoststatusTableInterface */
            $HoststatusTable = TableRegistry::getTableLocator()->get('Statusengine2Module.Hoststatus');
            return $HoststatusTable;
        }

        if ($this->isCrateDb()) {
            throw new MissingDbBackendException('MissingDbBackendException');
        }

        if ($this->isStatusengine3()) {
            /** @var $HoststatusTable HoststatusTableInterface */
            $HoststatusTable = TableRegistry::getTableLocator()->get('Statusengine3Module.Hoststatus');
            return $HoststatusTable;
        }
    }

    /**
     * @return ServicestatusTableInterface
     * @throws MissingDbBackendException
     */
    public function getServicestatusTable() {
        if ($this->isNdoUtils()) {
            /** @var $ServicestatusTable ServicestatusTableInterface */
            $ServicestatusTable = TableRegistry::getTableLocator()->get('Statusengine2Module.Servicestatus');
            return $ServicestatusTable;
        }

        if ($this->isCrateDb()) {
            throw new MissingDbBackendException('MissingDbBackendException');
        }

        if ($this->isStatusengine3()) {
            /** @var $ServicestatusTable ServicestatusTableInterface */
            $ServicestatusTable = TableRegistry::getTableLocator()->get('Statusengine3Module.Servicestatus');
            return $ServicestatusTable;
        }
    }

    /**
     * @return HostchecksTableInterface
     * @throws MissingDbBackendException
     */
    public function getHostchecksTable() {
        if ($this->isNdoUtils()) {
            /** @var $HostchecksTable HostchecksTableInterface */
            $HostchecksTable = TableRegistry::getTableLocator()->get('Statusengine2Module.Hostchecks');
            return $HostchecksTable;
        }

        if ($this->isCrateDb()) {
            throw new MissingDbBackendException('MissingDbBackendException');
        }

        if ($this->isStatusengine3()) {
            /** @var $HostchecksTable HostchecksTableInterface */
            $HostchecksTable = TableRegistry::getTableLocator()->get('Statusengine3Module.Hostchecks');
            return $HostchecksTable;
        }
    }

    /**
     * @return ServicechecksTableInterface
     * @throws MissingDbBackendException
     */
    public function getServicechecksTable() {
        if ($this->isNdoUtils()) {
            /** @var $ServicechecksTable ServicechecksTableInterface */
            $ServicechecksTable = TableRegistry::getTableLocator()->get('Statusengine2Module.Servicechecks');
            return $ServicechecksTable;
        }

        if ($this->isCrateDb()) {
            throw new MissingDbBackendException('MissingDbBackendException');
        }

        if ($this->isStatusengine3()) {
            /** @var $ServicechecksTable ServicechecksTableInterface */
            $ServicechecksTable = TableRegistry::getTableLocator()->get('Statusengine3Module.Servicechecks');
            return $ServicechecksTable;
        }
    }

    /**
     * @return StatehistoryHostTableInterface
     * @throws MissingDbBackendException
     */
    public function getStatehistoryHostsTable() {
        if ($this->isNdoUtils()) {
            /** @var $StatehistoryHostsTable StatehistoryHostTableInterface */
            $StatehistoryHostsTable = TableRegistry::getTableLocator()->get('Statusengine2Module.StatehistoryHosts');
            return $StatehistoryHostsTable;
        }

        if ($this->isCrateDb()) {
            throw new MissingDbBackendException('MissingDbBackendException');
        }

        if ($this->isStatusengine3()) {
            /** @var $StatehistoryHostsTable StatehistoryHostTableInterface */
            $StatehistoryHostsTable = TableRegistry::getTableLocator()->get('Statusengine3Module.StatehistoryHosts');
            return $StatehistoryHostsTable;
        }
    }

    /**
     * @return StatehistoryServiceTableInterface
     * @throws MissingDbBackendException
     */
    public function getStatehistoryServicesTable() {
        if ($this->isNdoUtils()) {
            /** @var $StatehistoryServicesTable StatehistoryServiceTableInterface */
            $StatehistoryServicesTable = TableRegistry::getTableLocator()->get('Statusengine2Module.StatehistoryServices');
            return $StatehistoryServicesTable;
        }

        if ($this->isCrateDb()) {
            throw new MissingDbBackendException('MissingDbBackendException');
        }

        if ($this->isStatusengine3()) {
            /** @var $StatehistoryServicesTable StatehistoryServiceTableInterface */
            $StatehistoryServicesTable = TableRegistry::getTableLocator()->get('Statusengine3Module.StatehistoryServices');
            return $StatehistoryServicesTable;
        }
    }

    /**
     * @return AcknowledgementHostsTableInterface
     * @throws MissingDbBackendException
     */
    public function getAcknowledgementHostsTable() {
        if ($this->isNdoUtils()) {
            /** @var $AcknowledgementHostsTable AcknowledgementHostsTableInterface */
            $AcknowledgementHostsTable = TableRegistry::getTableLocator()->get('Statusengine2Module.AcknowledgementHosts');
            return $AcknowledgementHostsTable;
        }

        if ($this->isCrateDb()) {
            throw new MissingDbBackendException('MissingDbBackendException');
        }

        if ($this->isStatusengine3()) {
            /** @var $AcknowledgementHostsTable AcknowledgementHostsTableInterface */
            $AcknowledgementHostsTable = TableRegistry::getTableLocator()->get('Statusengine3Module.AcknowledgementHosts');
            return $AcknowledgementHostsTable;
        }
    }

    /**
     * @return AcknowledgementServicesTableInterface
     * @throws MissingDbBackendException
     */
    public function getAcknowledgementServicesTable() {
        if ($this->isNdoUtils()) {
            /** @var $AcknowledgementServicesTable AcknowledgementServicesTableInterface */
            $AcknowledgementServicesTable = TableRegistry::getTableLocator()->get('Statusengine2Module.AcknowledgementServices');
            return $AcknowledgementServicesTable;
        }

        if ($this->isCrateDb()) {
            throw new MissingDbBackendException('MissingDbBackendException');
        }

        if ($this->isStatusengine3()) {
            /** @var $AcknowledgementServicesTable AcknowledgementServicesTableInterface */
            $AcknowledgementServicesTable = TableRegistry::getTableLocator()->get('Statusengine3Module.AcknowledgementServices');
            return $AcknowledgementServicesTable;
        }
    }

    /**
     * @return NotificationHostsTableInterface
     * @throws MissingDbBackendException
     */
    public function getNotificationHostsTable() {
        if ($this->isNdoUtils()) {
            /** @var $NotificationHostsTable NotificationHostsTableInterface */
            $NotificationHostsTable = TableRegistry::getTableLocator()->get('Statusengine2Module.NotificationHosts');
            return $NotificationHostsTable;
        }

        if ($this->isCrateDb()) {
            throw new MissingDbBackendException('MissingDbBackendException');
        }

        if ($this->isStatusengine3()) {
            /** @var $NotificationHostsTable NotificationHostsTableInterface */
            $NotificationHostsTable = TableRegistry::getTableLocator()->get('Statusengine3Module.NotificationHosts');
            return $NotificationHostsTable;
        }
    }

    /**
     * @return NotificationHostsLogTableInterface
     * @throws MissingDbBackendException
     */
    public function getNotificationHostsLogTable() {
        if ($this->isNdoUtils()) {
            throw new MissingDbBackendException('MissingDbBackendException');
        }

        if ($this->isCrateDb()) {
            throw new MissingDbBackendException('MissingDbBackendException');
        }

        if ($this->isStatusengine3()) {
            /** @var $NotificationHostsLogTable NotificationHostsLogTableInterface */
            $NotificationHostsLogTable = TableRegistry::getTableLocator()->get('Statusengine3Module.NotificationHostsLog');
            return $NotificationHostsLogTable;
        }
    }

    /**
     * @return NotificationServicesTableInterface
     * @throws MissingDbBackendException
     */
    public function getNotificationServicesTable() {
        if ($this->isNdoUtils()) {
            /** @var $NotificationServicesTable NotificationServicesTableInterface */
            $NotificationServicesTable = TableRegistry::getTableLocator()->get('Statusengine2Module.NotificationServices');
            return $NotificationServicesTable;
        }

        if ($this->isCrateDb()) {
            throw new MissingDbBackendException('MissingDbBackendException');
        }

        if ($this->isStatusengine3()) {
            /** @var $NotificationServicesTable NotificationServicesTableInterface */
            $NotificationServicesTable = TableRegistry::getTableLocator()->get('Statusengine3Module.NotificationServices');
            return $NotificationServicesTable;
        }
    }

    /**
     * @return NotificationServicesLogTableInterface
     * @throws MissingDbBackendException
     */
    public function getNotificationServicesLogTable() {
        if ($this->isNdoUtils()) {
            throw new MissingDbBackendException('MissingDbBackendException');
        }

        if ($this->isCrateDb()) {
            throw new MissingDbBackendException('MissingDbBackendException');
        }

        if ($this->isStatusengine3()) {
            /** @var $NotificationServicesLogTable NotificationServicesLogTableInterface */
            $NotificationServicesLogTable = TableRegistry::getTableLocator()->get('Statusengine3Module.NotificationServicesLog');
            return $NotificationServicesLogTable;
        }
    }

    /**
     * @return DowntimehistoryHostsTableInterface
     * @throws MissingDbBackendException
     */
    public function getDowntimehistoryHostsTable() {
        if ($this->isNdoUtils()) {
            /** @var $DowntimehistoryHostsTable DowntimehistoryHostsTableInterface */
            $DowntimehistoryHostsTable = TableRegistry::getTableLocator()->get('Statusengine2Module.DowntimeHosts');
            return $DowntimehistoryHostsTable;
        }

        if ($this->isCrateDb()) {
            throw new MissingDbBackendException('MissingDbBackendException');
        }

        if ($this->isStatusengine3()) {
            /** @var $DowntimehistoryHostsTable DowntimehistoryHostsTableInterface */
            $DowntimehistoryHostsTable = TableRegistry::getTableLocator()->get('Statusengine3Module.DowntimeHosts');
            return $DowntimehistoryHostsTable;
        }
    }

    /**
     * @return DowntimehistoryServicesTableInterface
     * @throws MissingDbBackendException
     */
    public function getDowntimehistoryServicesTable() {
        if ($this->isNdoUtils()) {
            /** @var $DowntimehistoryServicesTable DowntimehistoryServicesTableInterface */
            $DowntimehistoryServicesTable = TableRegistry::getTableLocator()->get('Statusengine2Module.DowntimeServices');
            return $DowntimehistoryServicesTable;
        }

        if ($this->isCrateDb()) {
            throw new MissingDbBackendException('MissingDbBackendException');
        }

        if ($this->isStatusengine3()) {
            /** @var $DowntimehistoryServicesTable DowntimehistoryServicesTableInterface */
            $DowntimehistoryServicesTable = TableRegistry::getTableLocator()->get('Statusengine3Module.DowntimeServices');
            return $DowntimehistoryServicesTable;
        }
    }

    /**
     * @return LogentriesTableInterface
     * @throws MissingDbBackendException
     */
    public function getLogentriesTable() {
        if ($this->isNdoUtils()) {
            /** @var LogentriesTableInterface $LogentriesTable */
            $LogentriesTable = TableRegistry::getTableLocator()->get('Statusengine2Module.Logentries');
            return $LogentriesTable;
        }

        if ($this->isCrateDb()) {
            throw new MissingDbBackendException('MissingDbBackendException');
        }

        if ($this->isStatusengine3()) {
            /** @var LogentriesTableInterface $LogentriesTable */
            $LogentriesTable = TableRegistry::getTableLocator()->get('Statusengine3Module.Logentries');
            return $LogentriesTable;
        }
    }

}
