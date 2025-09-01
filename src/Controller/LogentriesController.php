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

// 2.
//	If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//	under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//	License agreement and license key will be shipped with the order
//	confirmation.

declare(strict_types=1);

namespace App\Controller;

use App\itnovum\openITCOCKPIT\Core\UuidCache;
use App\Model\Table\HostsTable;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use itnovum\openITCOCKPIT\Core\AngularJS\Request\AngularRequest;
use itnovum\openITCOCKPIT\Core\LogentryConditions;
use itnovum\openITCOCKPIT\Core\ValueObjects\User;
use itnovum\openITCOCKPIT\Core\Views\Logentry;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\LogentryFilter;

class LogentriesController extends AppController {


    public function index() {
        if (!$this->isAngularJsRequest()) {
            //Only ship HTML template for angular
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }

        $User = new User($this->getUser());
        $UserTime = $User->getUserTime();

        $LogentriesTable = $this->DbBackend->getLogentriesTable();

        $LogentryFilter = new LogentryFilter($this->request);

        if ($LogentryFilter->hasHostIdFilter()) {
            /** @var HostsTable $HostsTable */
            $HostsTable = TableRegistry::getTableLocator()->get('Hosts');

            $hostIds = $LogentryFilter->getHostIds();
            $hosts = $HostsTable->getHostsByIds($hostIds);
            $LogentryFilter->addUuidsToMatching(Hash::extract($hosts, '{n}.uuid'));
        }

        $AngularRequest = new AngularRequest($this->request);
        $LogentryConditions = new LogentryConditions();
        $LogentryConditions->setFrom($UserTime->toServerTime($AngularRequest->getFrom()));
        $LogentryConditions->setTo($UserTime->toServerTime($AngularRequest->getTo()));
        $LogentryConditions->setOrder($AngularRequest->getOrderForPaginator('Logentries.entry_time', 'desc'));

        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $LogentryFilter->getPage());

        $UuidCache = new UuidCache();
        $UuidCache->buildCache();

        $all_logentries = $LogentriesTable->getLogentries($LogentryFilter, $PaginateOMat, $LogentryConditions);
        $logentries = [];
        foreach ($all_logentries as $logentry) {
            $logentry = new Logentry($logentry, $UserTime);
            $logentry = $logentry->toArray();

            $logentry['logentry_data_html'] = $UuidCache->replaceUuidWithAngularJsLink(h($logentry['logentry_data']));
            $logentry['logentry_data_angular_html'] = $UuidCache->replaceUuidWithAngularLink(h($logentry['logentry_data']), $this->PERMISSIONS);
            $logentries[] = $logentry;
        }

        $this->set('logentries', $logentries);
        $this->viewBuilder()->setOption('serialize', ['logentries']);
    }

}
