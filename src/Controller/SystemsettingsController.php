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

use App\Model\Table\SystemsettingsTable;
use Cake\Cache\Cache;
use Cake\ORM\TableRegistry;

/**
 * Class SystemsettingsController
 * @package App\Controller
 */
class SystemsettingsController extends AppController {

    public function index() {
        if (!$this->isApiRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }

        /** @var SystemsettingsTable $SystemsettingsTable */
        $SystemsettingsTable = TableRegistry::getTableLocator()->get('Systemsettings');

        if ($this->request->is('get')) {
            $all_systemsettings = $SystemsettingsTable->getSettings();

            foreach ($all_systemsettings as $sectionName => $settings) {
                $start = strlen($sectionName) + 1;
                foreach ($settings as $index => $setting) {
                    //Remove section prefix like SUDO_SERVER, SUDO_SERVER, MONITORING and so on
                    $all_systemsettings[$sectionName][$index]['alias'] = substr($setting['key'], $start);
                }
            }

            $this->set('all_systemsettings', $all_systemsettings);
            $this->viewBuilder()->setOption('serialize', ['all_systemsettings']);

            return;
        }

        if ($this->request->is('post') || $this->request->is('put')) {
            $systemsettingsEntity = $SystemsettingsTable->getSystemsettings(true);
            $normalizedData = [];
            foreach ($this->request->getData(null, []) as $requestData) {
                foreach ($requestData as $data)
                    $normalizedData[] = $data;
            }
            $systemsettingsPatchedEntities = $SystemsettingsTable->patchEntities($systemsettingsEntity, $normalizedData);
            $result = $SystemsettingsTable->saveMany($systemsettingsPatchedEntities);
            Cache::clear('permissions');
            //debug($result);
            if (!$result) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', []);
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            }
        }
    }

}
