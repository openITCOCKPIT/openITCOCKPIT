<?php
// Copyright (C) 2015-2025  it-novum GmbH
// Copyright (C) 2025-today AVENDIS GmbH
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

use App\itnovum\openITCOCKPIT\Core\PushrelayRequestHandler;
use App\Model\Table\PushNotificationsRelayTable;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\TableRegistry;
use itnovum\openITCOCKPIT\Core\System\Health\SystemId;

/**
 * Class NotificationsRelayController
 * @package App\Controller
 */
class NotificationsrelayController extends AppController {

    use LocatorAwareTrait;

    public function index() {
        if (!$this->isAngularJsRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }

        $SystemId = new SystemId();

        /** @var PushNotificationsRelayTable $PushNotificationsRelayTable */
        $PushNotificationsRelayTable = TableRegistry::getTableLocator()->get('PushNotificationsRelay');

        if ($this->request->is('post') && $this->isAngularJsRequest()) {
            $entity = $PushNotificationsRelayTable->find()->first();
            if (is_null($entity)) {
                //No relay configuration found
                $entity = $PushNotificationsRelayTable->newEmptyEntity();
            }

            $entity = $PushNotificationsRelayTable->patchEntity($entity, $this->request->getData('Relay'));

            if ($entity->hasErrors()) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', $entity->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            }

            $PushNotificationsRelayTable->save($entity);
        }

        $settings = $PushNotificationsRelayTable->getSettings();
        $this->set('relay', $settings);
        $this->set('systemId', $SystemId->getSystemId());
        $this->set('IS_CONTAINER', IS_CONTAINER);
        $this->viewBuilder()->setOption('serialize', ['relay', 'systemId', 'IS_CONTAINER']);
    }

    /****************************
     *       AJAX METHODS       *
     ****************************/

    public function testAndRegisterRelay() {
        if (!$this->request->is('post') || !$this->isJsonRequest()) {
            throw new MethodNotAllowedException();
        }

        $relayAddress = $this->request->getData('address', null);
        $port = $this->request->getData('port', null);

        if (empty($relayAddress) || empty($port) || !is_numeric($port)) {
            throw new BadRequestException('Relay address or port cannot be empty');
        }


        $PushrelayRequestHandler = new PushrelayRequestHandler();
        $result = $PushrelayRequestHandler->registerAndTestAtRelay($relayAddress, $port);

        $this->set('result', $result);
        $this->viewBuilder()->setOption('serialize', ['result']);
    }

}
