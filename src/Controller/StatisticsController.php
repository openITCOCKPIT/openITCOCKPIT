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

use App\Model\Table\HostsTable;
use App\Model\Table\ServicesTable;
use App\Model\Table\SystemsettingsTable;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\ORM\TableRegistry;
use itnovum\openITCOCKPIT\Core\System\Health\StatisticsCollector;

/**
 * Class StatisticsController
 * @package App\Controller
 */
class StatisticsController extends AppController {

    public function index() {
        /** @var HostsTable $HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
        /** @var ServicesTable $ServicesTable */
        $ServicesTable = TableRegistry::getTableLocator()->get('Services');

        $StatisticsCollector = new StatisticsCollector($HostsTable, $ServicesTable);
        $statisticsAsJson = json_encode($StatisticsCollector->getData(), JSON_PRETTY_PRINT);
        $this->set('statisticsAsJson', $statisticsAsJson);

        /** @var SystemsettingsTable $SystemsettingsTable */
        $SystemsettingsTable = TableRegistry::getTableLocator()->get('Systemsettings');

        $record = $SystemsettingsTable->getSystemsettingByKeyAsCake2('SYSTEM.ANONYMOUS_STATISTICS');

        $this->set('settings', $record);
        $this->set('statistics', $StatisticsCollector->getData());
        $this->set('IS_CONTAINER', IS_CONTAINER);
        $this->viewBuilder()->setOption('serialize', ['settings', 'statistics', 'IS_CONTAINER']);
    }

    public function ask_anonymous_statistics() {
        //Only ship HTML template
        return;
    }

    public function saveStatisticDecision() {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var SystemsettingsTable $SystemsettingsTable */
        $SystemsettingsTable = TableRegistry::getTableLocator()->get('Systemsettings');

        try {
            $record = $SystemsettingsTable->getSystemsettingByKey('SYSTEM.ANONYMOUS_STATISTICS');
        } catch (RecordNotFoundException $e) {
            if (empty($record)) {
                throw new \RuntimeException('Systemsetting is missing - did you executed openitcockpit-update?');
            }
        }


        if ($this->request->getData('statistics.decision', null) === null) {
            throw new \RuntimeException('Wrong POST request');
        }


        $record->set('value', (int)$this->request->getData('statistics.decision', 0));

        $SystemsettingsTable->save($record);
        if ($record->hasErrors()) {
            $this->set('success', false);
            $this->set('message', __('Error while saving data'));
            $this->viewBuilder()->setOption('serialize', ['success', 'message']);
            return;
        }
        $this->set('success', true);
        $this->set('message', __('Record successfully saved'));
        $this->viewBuilder()->setOption('serialize', ['success', 'message']);
    }

}
