<?php
// Copyright (C) <2015-present>  <it-novum GmbH>
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

use App\itnovum\openITCOCKPIT\Core\Dashboards\HostStatusOverviewExtendedJson;
use App\itnovum\openITCOCKPIT\Core\Dashboards\HostsTopAlertJson;
use App\itnovum\openITCOCKPIT\Core\Dashboards\ServiceStatusOverviewExtendedJson;
use App\itnovum\openITCOCKPIT\Core\Dashboards\ServicesTopAlertJson;
use App\itnovum\openITCOCKPIT\Perfdata\NagiosAdapter;
use App\Lib\Exceptions\MissingDbBackendException;
use App\Model\Table\ContainersTable;
use App\Model\Table\DashboardTabAllocationsTable;
use App\Model\Table\DashboardTabsTable;
use App\Model\Table\HostsTable;
use App\Model\Table\ParenthostsTable;
use App\Model\Table\RegistersTable;
use App\Model\Table\ServicesTable;
use App\Model\Table\UsersTable;
use App\Model\Table\WidgetsTable;
use Cake\Core\Plugin;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\NotFoundException;
use Cake\I18n\FrozenTime;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use itnovum\openITCOCKPIT\Core\CalendarTime;
use itnovum\openITCOCKPIT\Core\Dashboards\DowntimeHostListJson;
use itnovum\openITCOCKPIT\Core\Dashboards\DowntimeServiceListJson;
use itnovum\openITCOCKPIT\Core\Dashboards\HostStatusListJson;
use itnovum\openITCOCKPIT\Core\Dashboards\HostStatusOverviewJson;
use itnovum\openITCOCKPIT\Core\Dashboards\NoticeJson;
use itnovum\openITCOCKPIT\Core\Dashboards\ServiceStatusListJson;
use itnovum\openITCOCKPIT\Core\Dashboards\ServiceStatusOverviewJson;
use itnovum\openITCOCKPIT\Core\Dashboards\TachoJson;
use itnovum\openITCOCKPIT\Core\Dashboards\TacticalOverviewJson;
use itnovum\openITCOCKPIT\Core\Dashboards\TrafficlightJson;
use itnovum\openITCOCKPIT\Core\Dashboards\WebsiteJson;
use itnovum\openITCOCKPIT\Core\Hoststatus;
use itnovum\openITCOCKPIT\Core\HoststatusConditions;
use itnovum\openITCOCKPIT\Core\HoststatusFields;
use itnovum\openITCOCKPIT\Core\Servicestatus;
use itnovum\openITCOCKPIT\Core\ServicestatusFields;
use itnovum\openITCOCKPIT\Core\ValueObjects\User;
use itnovum\openITCOCKPIT\Core\Views\Host;
use itnovum\openITCOCKPIT\Core\Views\Service;
use ParsedownExtra;
use PrometheusModule\Lib\PrometheusAdapter;
use RuntimeException;
use Statusengine\PerfdataParser;


/**
 * Class DashboardsController
 * @package App\Controller
 */
class DashboardsController extends AppController {

    use LocatorAwareTrait;

    public function index() {
        if (!$this->isAngularJsRequest()) {
            //Only ship template
            return;
        }

        $User = new User($this->getUser());

        /** @var UsersTable $UsersTable */
        $UsersTable = TableRegistry::getTableLocator()->get('Users');
        $user = $UsersTable->get($User->getId());
        $tabRotationInterval = (int)$user->dashboard_tab_rotation;

        /** @var DashboardTabsTable $DashboardTabsTable */
        $DashboardTabsTable = TableRegistry::getTableLocator()->get('DashboardTabs');
        /** @var WidgetsTable $WidgetsTable */
        $WidgetsTable = TableRegistry::getTableLocator()->get('Widgets');


        // If user has neither OWN or allocated tabs, create the default tab.
        if ($DashboardTabsTable->hasUserATab($User) === false) {
            $entitiy = $DashboardTabsTable->createNewTab($User->getId());
            if ($entitiy) {
                //Create default widgets
                $entitiy = $DashboardTabsTable->patchEntity($entitiy, [
                    'widgets' => $WidgetsTable->getDefaultWidgets($this->PERMISSIONS)
                ]);

                $DashboardTabsTable->save($entitiy);
            }
        }
        $tabs = $DashboardTabsTable->getAllTabsByUser($User);

        $widgets = $WidgetsTable->getAvailableWidgets($this->PERMISSIONS);

        $this->set('tabs', $tabs);
        $this->set('widgets', $widgets);
        $this->set('tabRotationInterval', $tabRotationInterval);
        $this->viewBuilder()->setOption('serialize', ['tabs', 'widgets', 'tabRotationInterval']);
    }

    /****************************
     *       AJAX METHODS       *
     ****************************/

    public function getWidgetsForTab($tabId) {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }

        $tabId = (int)$tabId;

        /** @var DashboardTabsTable $DashboardTabsTable */
        $DashboardTabsTable = TableRegistry::getTableLocator()->get('DashboardTabs');

        if (!$DashboardTabsTable->existsById($tabId)) {
            throw new NotFoundException(sprintf('Tab with id %s not found', $tabId));
        }

        $User = new User($this->getUser());
        $widgets = $DashboardTabsTable->getWidgetsForTabByUserIdAndTabId($User->getId(), $tabId, $User->getId());
        if (empty($widgets)) {
            // Check dashboard allocations
            // Check for allocated Dashboards!
            /** @var DashboardTabAllocationsTable $DashboardTabAllocationsTable */
            $DashboardTabAllocationsTable = TableRegistry::getTableLocator()->get('DashboardTabAllocations');

            $allocations = $DashboardTabAllocationsTable->getDashboardAllocationTabIdForUser($tabId, $User);
            if (!empty($allocations)) {
                // Load the allocated tab
                $widgets = $DashboardTabsTable->getWidgetsForTabByUserIdAndTabId($allocations['dashboard_tab']['user_id'], $tabId, $User->getId());
            }
        }


        $this->set('widgets', $widgets);
        $this->viewBuilder()->setOption('serialize', ['widgets']);
    }

    public function dynamicDirective() {
        $directiveName = $this->request->getQuery('directive');
        $readOnly = filter_var($this->request->getQuery('readonly'), FILTER_VALIDATE_BOOLEAN);

        /** @var WidgetsTable $WidgetsTable */
        $WidgetsTable = TableRegistry::getTableLocator()->get('Widgets');
        if ($readOnly) {
            $widgets = $WidgetsTable->getAvailableForViewWidgets($this->PERMISSIONS);
        } else {
            $widgets = $WidgetsTable->getAvailableWidgets($this->PERMISSIONS);
        }
        // $widgets = $WidgetsTable->getAvailableForViewWidgets($this->PERMISSIONS);
        $isValidDirective = false;
        foreach ($widgets as $widget) {
            if ($widget['directive'] === $directiveName) {
                $isValidDirective = true;
                break;
            }
        }

        if (!$isValidDirective) {
            throw new ForbiddenException();
        }

        if (strlen($directiveName) < 2) {
            throw new RuntimeException('Wrong AngularJS directive name?');
        }
        $this->set('directiveName', $directiveName);
    }

    public function saveGrid() {
        if (!$this->isAngularJsRequest() || !$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

        $widgetsData = $this->request->getData(null, []);
        //Update DashboardTab last modified
        if (isset($widgetsData[0]['Widget']['dashboard_tab_id'])) {
            $User = new User($this->getUser());
            /** @var DashboardTabsTable $DashboardTabsTable */
            $DashboardTabsTable = TableRegistry::getTableLocator()->get('DashboardTabs');
            /** @var WidgetsTable $WidgetsTable */
            $WidgetsTable = TableRegistry::getTableLocator()->get('Widgets');

            if (!$DashboardTabsTable->isOwnedByUser($widgetsData[0]['Widget']['dashboard_tab_id'], $User->getId())) {
                $this->render403();
                return;
            }

            //Save all Widgets
            $widgets = [];
            foreach ($widgetsData as $widgetData) {
                $widget = $WidgetsTable->get($widgetData['Widget']['id']);
                $widget = $WidgetsTable->patchEntity($widget, $widgetData['Widget']);
                $widgets[] = $widget;
            }
            if ($WidgetsTable->saveMany($widgets)) {
                $tab = $DashboardTabsTable->find()
                    ->where([
                        'DashboardTabs.id'      => $widgetsData[0]['Widget']['dashboard_tab_id'],
                        'DashboardTabs.user_id' => $User->getId()
                    ])
                    ->first();

                if ($tab) {
                    $tab->set('modified', new \Cake\I18n\DateTime());
                    $DashboardTabsTable->save($tab);
                }

                $this->set('message', __('Successfully saved'));
                $this->viewBuilder()->setOption('serialize', ['message']);
                return;
            }
            $this->serializeCake4ErrorMessage($widgets[0]);
        }
    }

    public function addWidgetToTab() {
        if (!$this->isAngularJsRequest() || !$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

        $widget = $this->request->getData('Widget', []);
        if (!isset($widget['typeId']) || !isset($widget['dashboard_tab_id'])) {
            throw new RuntimeException('Missing parameter typeId || dashboard_tab_id');
        }

        $widget['dashboard_tab_id'] = (int)$widget['dashboard_tab_id'];

        /** @var DashboardTabsTable $DashboardTabsTable */
        $DashboardTabsTable = TableRegistry::getTableLocator()->get('DashboardTabs');
        /** @var WidgetsTable $WidgetsTable */
        $WidgetsTable = TableRegistry::getTableLocator()->get('Widgets');

        $User = new User($this->getUser());

        if (!$DashboardTabsTable->isOwnedByUser($widget['dashboard_tab_id'], $User->getId())) {
            throw new NotFoundException('DashboardTab does not exists!');
        }

        if (!$WidgetsTable->isWidgetAvailable($widget['typeId'], $this->PERMISSIONS)) {
            throw new NotFoundException('Widget not found!');
        }

        $widgetDefaults = $WidgetsTable->getWidgetByTypeId($widget['typeId'], $this->PERMISSIONS);
        $data = [
            'dashboard_tab_id' => $widget['dashboard_tab_id'],
            'type_id'          => $widget['typeId'],
            'row'              => 0,
            'col'              => 0,
            'width'            => $widgetDefaults['width'],
            'height'           => $widgetDefaults['height'],
            'title'            => $widgetDefaults['title'],
            'icon'             => $widgetDefaults['icon'],
            'color'            => 'jarviswidget-color-blueDark',
            'directive'        => $widgetDefaults['directive']
        ];

        $entity = $WidgetsTable->newEntity($data);
        $WidgetsTable->save($entity);
        if ($entity->hasErrors()) {
            $this->set('error', $entity->getErrors());
            $this->viewBuilder()->setOption('serialize', ['error']);
            $this->response = $this->response->withStatus(400);
            return;
        }

        $this->set('message', __('Successfully saved'));
        $this->set('widget', $WidgetsTable->getWidgetByIdAsCake2($entity->get('id')));
        $this->viewBuilder()->setOption('serialize', ['message', 'widget']);
    }

    public function removeWidgetFromTab() {
        if (!$this->isAngularJsRequest() || !$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

        $widget = $this->request->getData('Widget', []);
        if (!isset($widget['id']) || !isset($widget['dashboard_tab_id'])) {
            throw new RuntimeException('Missing parameter id || dashboard_tab_id');
        }

        $widget['id'] = (int)$widget['id'];
        $User = new User($this->getUser());

        /** @var WidgetsTable $WidgetsTable */
        $WidgetsTable = TableRegistry::getTableLocator()->get('Widgets');
        /** @var DashboardTabsTable $DashboardTabsTable */
        $DashboardTabsTable = TableRegistry::getTableLocator()->get('DashboardTabs');

        if (!$WidgetsTable->existsById($widget['id'])) {
            throw new NotFoundException('Widget does not exists!');
        }

        $widget = $WidgetsTable->get($widget['id']);

        if (!$DashboardTabsTable->isOwnedByUser($widget->dashboard_tab_id, $User->getId())) {
            throw new NotFoundException();
        }

        if ($WidgetsTable->delete($widget)) {
            $this->set('message', __('Successfully deleted'));
            $this->viewBuilder()->setOption('serialize', ['message', 'widget']);
            return;
        }

        $this->set('message', __('Error while deleting Widget'));
        $this->viewBuilder()->setOption('serialize', ['message']);
        $this->response = $this->response->withStatus(400);
        return;
    }

    public function saveTabOrder() {
        if (!$this->request->is('post') || !$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }

        $newOrder = $this->request->getData('order', []);
        if (empty($newOrder) || !is_array($newOrder)) {
            return;
        }

        $User = new User($this->getUser());
        $success = true;

        /** @var DashboardTabsTable $DashboardTabsTable */
        $DashboardTabsTable = TableRegistry::getTableLocator()->get('DashboardTabs');

        foreach ($newOrder as $key => $tabId) {
            $tab = $DashboardTabsTable->find()
                ->where([
                    'DashboardTabs.id'      => $tabId,
                    'DashboardTabs.user_id' => $User->getId()
                ])
                ->first();

            if ($tab) {
                $tab = $DashboardTabsTable->patchEntity($tab, [
                    'position' => (int)$key
                ]);

                $DashboardTabsTable->save($tab);
                if ($tab->hasErrors()) {
                    $success = false;
                }
            }
        }

        $this->set('success', $success);
        $this->viewBuilder()->setOption('serialize', ['success']);
        if ($success === false) {
            $this->response = $this->response->withStatus(400);
            return;
        }
    }

    public function addNewTab() {
        if (!$this->request->is('post') || !$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }

        $name = $this->request->getData('DashboardTab.name');
        $User = new User($this->getUser());

        /** @var DashboardTabsTable $DashboardTabsTable */
        $DashboardTabsTable = TableRegistry::getTableLocator()->get('DashboardTabs');

        $entity = $DashboardTabsTable->createNewTab($User->getId(), [
            'name' => $name
        ]);

        if ($entity->hasErrors()) {
            return $this->serializeCake4ErrorMessage($entity);
        }

        $this->set('DashboardTab', $DashboardTabsTable->getTabByIdAsCake2($entity->get('id')));
        $this->viewBuilder()->setOption('serialize', ['DashboardTab']);
    }

    public function saveTabRotateInterval() {
        if (!$this->request->is('post') || !$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }

        $tabRotationInterval = (int)$this->request->getData('User.dashboard_tab_rotation', 0);

        $User = new User($this->getUser());

        /** @var UsersTable $UsersTable */
        $UsersTable = TableRegistry::getTableLocator()->get('Users');

        $entity = $UsersTable->get($User->getId());
        $entity = $UsersTable->patchEntity($entity, [
            'dashboard_tab_rotation' => $tabRotationInterval
        ]);
        $UsersTable->save($entity);

        $this->set('success', true);
        $this->viewBuilder()->setOption('serialize', ['success']);
    }

    public function renameDashboardTab() {
        if (!$this->request->is('post') || !$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }
        $User = new User($this->getUser());

        $name = $this->request->getData('DashboardTab.name');
        $id = (int)$this->request->getData('DashboardTab.id');

        /** @var DashboardTabsTable $DashboardTabsTable */
        $DashboardTabsTable = TableRegistry::getTableLocator()->get('DashboardTabs');

        if (!$DashboardTabsTable->isOwnedByUser($id, $User->getId())) {
            $this->render403();
            return;
        }

        $tab = $DashboardTabsTable->find()
            ->where([
                'DashboardTabs.id'      => $id,
                'DashboardTabs.user_id' => $User->getId()
            ])
            ->first();

        if ($tab === null) {
            throw new NotFoundException();
        }

        $tab = $DashboardTabsTable->patchEntity($tab, [
            'name' => $name
        ]);

        $DashboardTabsTable->save($tab);
        if ($tab->hasErrors()) {
            return $this->serializeCake4ErrorMessage($tab);
        }

        $this->set('DashboardTab', [
            'DashboardTab' => $DashboardTabsTable->getTabByIdAsCake2($tab->get('id'))
        ]);
        $this->viewBuilder()->setOption('serialize', ['DashboardTab']);
    }

    public function deleteDashboardTab() {
        if (!$this->request->is('post') || !$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }
        $User = new User($this->getUser());

        $id = (int)$this->request->getData('DashboardTab.id');

        /** @var DashboardTabsTable $DashboardTabsTable */
        $DashboardTabsTable = TableRegistry::getTableLocator()->get('DashboardTabs');

        if (!$DashboardTabsTable->isOwnedByUser($id, $User->getId())) {
            $this->render403();
            return;
        }

        $tab = $DashboardTabsTable->find()
            ->where([
                'DashboardTabs.id'      => $id,
                'DashboardTabs.user_id' => $User->getId()
            ])
            ->first();

        if ($tab === null) {
            throw new NotFoundException();
        }

        if ($DashboardTabsTable->delete($tab)) {
            $this->set('success', true);
            $this->viewBuilder()->setOption('serialize', ['success']);
            return;
        }
        $this->set('success', false);
        $this->viewBuilder()->setOption('serialize', ['success']);
        $this->response = $this->response->withStatus(400);
        return;
    }

    public function startSharing() {
        if (!$this->request->is('post') || !$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }
        $User = new User($this->getUser());

        $id = (int)$this->request->getData('DashboardTab.id', 0);
        /** @var DashboardTabsTable $DashboardTabsTable */
        $DashboardTabsTable = TableRegistry::getTableLocator()->get('DashboardTabs');

        if (!$DashboardTabsTable->isOwnedByUser($id, $User->getId())) {
            $this->render403();
            return;
        }

        $tab = $DashboardTabsTable->find()
            ->where([
                'DashboardTabs.id'      => $id,
                'DashboardTabs.user_id' => $User->getId()
            ])
            ->first();

        if ($tab === null) {
            throw new NotFoundException();
        }

        $tab = $DashboardTabsTable->patchEntity($tab, [
            'shared' => 1
        ]);

        $DashboardTabsTable->save($tab);

        if ($tab->hasErrors()) {
            return $this->serializeCake4ErrorMessage($tab);
        }

        $this->set('success', true);
        $this->viewBuilder()->setOption('serialize', ['success']);
    }

    public function stopSharing() {
        if (!$this->request->is('post') || !$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }
        $User = new User($this->getUser());

        $id = (int)$this->request->getData('DashboardTab.id', 0);
        /** @var DashboardTabsTable $DashboardTabsTable */
        $DashboardTabsTable = TableRegistry::getTableLocator()->get('DashboardTabs');

        if (!$DashboardTabsTable->isOwnedByUser($id, $User->getId())) {
            $this->render403();
            return;
        }

        $tab = $DashboardTabsTable->find()
            ->where([
                'DashboardTabs.id'      => $id,
                'DashboardTabs.user_id' => $User->getId()
            ])
            ->first();

        if ($tab === null) {
            throw new NotFoundException();
        }

        $tab = $DashboardTabsTable->patchEntity($tab, [
            'shared' => 0
        ]);

        $DashboardTabsTable->save($tab);

        if ($tab->hasErrors()) {
            return $this->serializeCake4ErrorMessage($tab);
        }

        $this->set('success', true);
        $this->viewBuilder()->setOption('serialize', ['success']);
    }

    public function getSharedTabs() {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var DashboardTabsTable $DashboardTabsTable */
        $DashboardTabsTable = TableRegistry::getTableLocator()->get('DashboardTabs');
        $tabs = $DashboardTabsTable->getSharedTabs();

        $this->set('tabs', $tabs);
        $this->viewBuilder()->setOption('serialize', ['tabs']);
    }

    public function createFromSharedTab() {
        if (!$this->request->is('post') || !$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }
        $User = new User($this->getUser());

        $id = (int)$this->request->getData('DashboardTab.id', 0);

        /** @var DashboardTabsTable $DashboardTabsTable */
        $DashboardTabsTable = TableRegistry::getTableLocator()->get('DashboardTabs');

        $newTab = $DashboardTabsTable->copySharedTab($id, $User->getId());
        if ($newTab->hasErrors()) {
            return $this->serializeCake4ErrorMessage($newTab);
        }

        $this->set('DashboardTab', [
            'DashboardTab' => [
                'id' => $newTab->get('id')
            ]
        ]);
        $this->viewBuilder()->setOption('serialize', ['DashboardTab']);
    }

    public function checkForUpdates() {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }

        $tabId = (int)$this->request->getQuery('tabId', 0);
        $User = new User($this->getUser());

        /** @var DashboardTabsTable $DashboardTabsTable */
        $DashboardTabsTable = TableRegistry::getTableLocator()->get('DashboardTabs');
        if (!$DashboardTabsTable->isOwnedByUser($tabId, $User->getId())) {
            throw new NotFoundException('DashboardTab not found');
        }

        $tab = $DashboardTabsTable->find()
            ->where([
                'DashboardTabs.id'      => $tabId,
                'DashboardTabs.user_id' => $User->getId()
            ])
            ->first();

        $sourceTab = $DashboardTabsTable->find()
            ->where([
                'DashboardTabs.id' => $tab->get('source_tab_id')
            ])
            ->first();

        $updateAvailable = false;
        if ($sourceTab !== null && $tab !== null) {
            /** @var \Cake\I18n\DateTime $modified */
            $modified = $sourceTab->get('modified');
            $modified = $modified->getTimestamp();

            if ($modified > $tab->get('last_update')) {
                $updateAvailable = true;
            }
        }

        $this->set('updateAvailable', $updateAvailable);
        $this->viewBuilder()->setOption('serialize', ['updateAvailable']);
    }

    public function neverPerformUpdates() {
        if (!$this->request->is('post') || !$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }
        $User = new User($this->getUser());

        $id = (int)$this->request->getData('DashboardTab.id', 0);

        /** @var DashboardTabsTable $DashboardTabsTable */
        $DashboardTabsTable = TableRegistry::getTableLocator()->get('DashboardTabs');
        if (!$DashboardTabsTable->isOwnedByUser($id, $User->getId())) {
            throw new NotFoundException('DashboardTab not found');
        }

        $tab = $DashboardTabsTable->find()
            ->where([
                'DashboardTabs.id'      => $id,
                'DashboardTabs.user_id' => $User->getId()
            ])
            ->first();

        $tab = $DashboardTabsTable->patchEntity($tab, [
            'check_for_updates' => 0
        ]);
        $DashboardTabsTable->save($tab);

        $dashboardTab['DashboardTab']['check_for_updates'] = 0;

        if ($tab->hasErrors()) {
            return $this->serializeCake4ErrorMessage($tab);
        }

        $this->set('success', true);
        $this->viewBuilder()->setOption('serialize', ['success']);
    }

    public function updateSharedTab() {
        if (!$this->request->is('post') || !$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }
        $User = new User($this->getUser());

        $id = (int)$this->request->getData('DashboardTab.id', 0);

        /** @var DashboardTabsTable $DashboardTabsTable */
        $DashboardTabsTable = TableRegistry::getTableLocator()->get('DashboardTabs');
        /** @var WidgetsTable $WidgetsTable */
        $WidgetsTable = TableRegistry::getTableLocator()->get('Widgets');

        if (!$DashboardTabsTable->isOwnedByUser($id, $User->getId())) {
            $this->render403();
            return;
        }

        $tabToUpdate = $DashboardTabsTable->find()
            ->where([
                'DashboardTabs.id'      => $id,
                'DashboardTabs.user_id' => $User->getId()
            ])
            ->first();

        if ($tabToUpdate === null) {
            throw new NotFoundException();
        }

        $sourceTabWithWidgets = $DashboardTabsTable->find()
            ->where([
                'DashboardTabs.id' => $tabToUpdate->get('source_tab_id')
            ])
            ->contain([
                'Widgets'
            ])
            ->first();

        if ($sourceTabWithWidgets === null) {
            throw new NotFoundException();
        }

        $widgets = [];
        foreach ($sourceTabWithWidgets->get('widgets') as $widget) {
            $widgets[] = [
                'type_id'    => $widget->get('type_id'),
                'host_id'    => $widget->get('host_id'),
                'service_id' => $widget->get('service_id'),
                'row'        => $widget->get('row'),
                'col'        => $widget->get('col'),
                'width'      => $widget->get('width'),
                'height'     => $widget->get('height'),
                'title'      => $widget->get('title'),
                'color'      => $widget->get('color'),
                'directive'  => $widget->get('directive'),
                'icon'       => $widget->get('icon'),
                'json_data'  => $widget->get('json_data')
            ];
        }

        $FrozenTime = new \Cake\I18n\DateTime();
        $tabToUpdate = $DashboardTabsTable->patchEntity($tabToUpdate, [
            'last_update' => $FrozenTime->getTimestamp(),
            'locked'      => (bool)$sourceTabWithWidgets->get('locked'),
            'widgets'     => $widgets
        ]);


        //Delete old widgets
        $WidgetsTable->deleteAll([
            'Widgets.dashboard_tab_id' => $id
        ]);

        $DashboardTabsTable->save($tabToUpdate);

        if ($tabToUpdate->hasErrors()) {
            return $this->serializeCake4ErrorMessage($tabToUpdate);
        }

        $this->set('DashboardTab', [
            'DashboardTab' => $tabToUpdate
        ]);
        $this->viewBuilder()->setOption('serialize', ['DashboardTab']);
    }

    public function renameWidget() {
        if (!$this->request->is('post') || !$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }

        $widgetId = (int)$this->request->getData('Widget.id', 0);
        $name = $this->request->getData('Widget.name', '');
        $User = new User($this->getUser());

        /** @var WidgetsTable $WidgetsTable */
        $WidgetsTable = TableRegistry::getTableLocator()->get('Widgets');
        /** @var DashboardTabsTable $DashboardTabsTable */
        $DashboardTabsTable = TableRegistry::getTableLocator()->get('DashboardTabs');

        $widget = $WidgetsTable->get($widgetId);

        if ($widget === null) {
            throw new NotFoundException();
        }

        if (!$DashboardTabsTable->isOwnedByUser($widget->dashboard_tab_id, $User->getId())) {
            throw new NotFoundException();
        }

        $widget = $WidgetsTable->patchEntity($widget, [
            'title' => $name
        ]);

        $WidgetsTable->save($widget);

        if ($widget->hasErrors()) {
            return $this->serializeCake4ErrorMessage($widget);
        }
        $this->set('success', true);
        $this->viewBuilder()->setOption('serialize', ['success']);
    }

    public function lockOrUnlockTab() {
        if (!$this->request->is('post') || !$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }
        $User = new User($this->getUser());

        $id = (int)$this->request->getData('DashboardTab.id');
        $locked = $this->request->getData('DashboardTab.locked') === 'true';

        /** @var DashboardTabsTable $DashboardTabsTable */
        $DashboardTabsTable = TableRegistry::getTableLocator()->get('DashboardTabs');

        if (!$DashboardTabsTable->isOwnedByUser($id, $User->getId())) {
            $this->render403();
            return;
        }

        $tab = $DashboardTabsTable->find()
            ->where([
                'DashboardTabs.id'      => $id,
                'DashboardTabs.user_id' => $User->getId()
            ])
            ->first();

        if ($tab === null) {
            throw new NotFoundException();
        }

        $tab = $DashboardTabsTable->patchEntity($tab, [
            'locked' => $locked
        ]);

        $DashboardTabsTable->save($tab);

        if ($tab->hasErrors()) {
            return $this->serializeCake4ErrorMessage($tab);
        }
        $this->set('success', true);
        $this->viewBuilder()->setOption('serialize', ['success']);
    }

    public function restoreDefault() {
        if (!$this->request->is('post') || !$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }
        $User = new User($this->getUser());

        $id = (int)$this->request->getData('DashboardTab.id');

        /** @var DashboardTabsTable $DashboardTabsTable */
        $DashboardTabsTable = TableRegistry::getTableLocator()->get('DashboardTabs');
        /** @var WidgetsTable $WidgetsTable */
        $WidgetsTable = TableRegistry::getTableLocator()->get('Widgets');

        if (!$DashboardTabsTable->isOwnedByUser($id, $User->getId())) {
            $this->render403();
            return;
        }

        $tab = $DashboardTabsTable->find()
            ->where([
                'DashboardTabs.id'      => $id,
                'DashboardTabs.user_id' => $User->getId()
            ])
            ->first();

        if ($tab === null) {
            throw new NotFoundException();
        }

        //Delete old widgets
        $WidgetsTable->deleteAll([
            'Widgets.dashboard_tab_id' => $id
        ]);

        $tab = $DashboardTabsTable->patchEntity($tab, [
            'widgets' => $WidgetsTable->getDefaultWidgets($this->PERMISSIONS)
        ]);

        $DashboardTabsTable->save($tab);

        if ($tab->hasErrors()) {
            return $this->serializeCake4ErrorMessage($tab);
        }
        $this->set('success', true);
        $this->viewBuilder()->setOption('serialize', ['success']);
    }

    /****************************
     *      Basic Widgets       *
     ****************************/
    public function welcomeWidget() {
        /* New Angular API */
        if ($this->isApiRequest()) {
            /** @var RegistersTable $RegistersTable */
            $RegistersTable = TableRegistry::getTableLocator()->get('Registers');


            $license = $RegistersTable->getLicense();
            $isCommunityEdition = false;
            $hasSubscription = $license !== null;
            if (isset($license['license']) && $license['license'] === $RegistersTable->getCommunityLicenseKey()) {
                $isCommunityEdition = true;
            }

            $user = $this->getUser();
            $User = new User($this->getUser());

            $ServerTime = new \DateTime();
            $ServerTimeZone = new \DateTimeZone($ServerTime->getTimezone()->getName());

            $userImage = null;
            if ($user->get('image') != null && $user->get('image') != '') {
                if (file_exists(WWW_ROOT . 'img' . DS . 'userimages' . DS . $user->get('image'))) {
                    $userImage = '/img/userimages' . DS . $user->get('image');
                }
            }

            $this->set('isCommunityEdition', $isCommunityEdition);
            $this->set('hasSubscription', $hasSubscription);
            $this->set('server_timezone', $ServerTimeZone->getName());
            $this->set('user_timezone', $User->getTimezone());
            $this->set('userImage', $userImage);
            $this->set('user_fullname', $User->getFullName());
            $this->set('OPENITCOCKPIT_VERSION', OPENITCOCKPIT_VERSION);

            $this->viewBuilder()->setOption('serialize', ['isCommunityEdition', 'hasSubscription', 'server_timezone', 'user_timezone', 'userImage', 'user_fullname', 'OPENITCOCKPIT_VERSION']);
            return;
        }

        /** Remove all code below - old AngularJS template code */
        if (!$this->isApiRequest()) {
            //Only ship HTML template

            $user = $this->getUser();


            $userImage = null;

            if ($user->get('image') != null && $user->get('image') != '') {
                if (file_exists(WWW_ROOT . 'img' . DS . 'userimages' . DS . $user->get('image'))) {
                    $userImage = '/img/userimages' . DS . $user->get('image');
                }
            }

            if ($userImage === null) {
                $userImage = '/img/fallback_user.png';

                $User = new User($this->getUser());
                $userImage = $User->getUserAvatar();

            }


            $userFullName = sprintf('%s %s', $user->get('firstname'), $user->get('lastname'));

            /** @var RegistersTable $RegistersTable */
            $RegistersTable = TableRegistry::getTableLocator()->get('Registers');


            $license = $RegistersTable->getLicense();
            $isCommunityEdition = false;
            $hasSubscription = $license !== null;
            if (isset($license['license']) && $license['license'] === $RegistersTable->getCommunityLicenseKey()) {
                $isCommunityEdition = true;
            }


            $this->set('userImage', $userImage);
            $this->set('userFullName', $userFullName);
            $this->set('userTimezone', $user->get('timezone'));
            $this->set('systemname', $this->getSystemname());
            $this->set('isCommunityEdition', $isCommunityEdition);
            $this->set('hasSubscription', $hasSubscription);

            return;
        }
    }

    /**
     * @throws \Exception
     */
    public function parentOutagesWidget() {
        if (!$this->isApiRequest()) {
            //Only ship HTML template
            return;
        }

        $User = new User($this->getUser());
        $UserTime = $User->getUserTime();

        /** @var $ContainersTable ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');

        $containerIds = [];
        if ($this->hasRootPrivileges === false) {
            $containerIds = $ContainersTable->easyPath($this->MY_RIGHTS, OBJECT_HOST, [], $this->hasRootPrivileges, [CT_HOSTGROUP]);
        }


        /** @var HostsTable $HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
        $HoststatusTable = $this->DbBackend->getHoststatusTable();

        $parentHosts = $HostsTable->getParenthostsForDashboard($containerIds);
        $hostUuids = Hash::extract($parentHosts, '{n}.uuid');

        $HoststatusFields = new HoststatusFields($this->DbBackend);
        $HoststatusFields->wildcard();
        $HoststatusConditions = new HoststatusConditions($this->DbBackend);
        $HoststatusConditions->hostsDownAndUnreachable();
        $hoststatus = $HoststatusTable->byUuid($hostUuids, $HoststatusFields, $HoststatusConditions);

        $nonUpParentHostUuids = array_keys($hoststatus);
        if (empty($nonUpParentHostUuids)) {
            $this->set('parent_outages', []);
            $this->viewBuilder()->setOption('serialize', ['parent_outages']);
            return;
        }

        $query = $this->request->getQuery();
        $where = [
            'Hosts.uuid IN' => $nonUpParentHostUuids
        ];

        if (isset($query['filter']['Hosts.name']) && strlen($query['filter']['Hosts.name']) > 0) {
            $where['Hosts.name LIKE'] = sprintf('%%%s%%', $query['filter']['Hosts.name']);
        }

        $parent_outages = [];
        foreach ($HostsTable->getParenthostsForDashboard($containerIds, $where) as $parentHost) {
            $Hoststatus = new Hoststatus([], $UserTime);

            if (isset($hoststatus[$parentHost['uuid']])) {
                $Hoststatus = new Hoststatus($hoststatus[$parentHost['uuid']]['Hoststatus'], $UserTime);
            }
            $parentHost['Hoststatus'] = $Hoststatus->toArray();
            $parent_outages[] = $parentHost;
        }


        $this->set('parent_outages', $parent_outages);
        $this->viewBuilder()->setOption('serialize', ['parent_outages']);
    }

    public function hostsPiechartWidget() {
        return;
    }

    public function hostsPiechart180Widget() {
        return;
    }


    public function servicesPiechartWidget() {
        return;
    }

    public function servicesPiechart180Widget() {
        return;
    }

    public function hostsTopAlertsWidget() {
        if (!$this->isApiRequest()) {
            //Only ship HTML template
            return;
        }
        $HostsTopAlertJson = new HostsTopAlertJson();

        /** @var WidgetsTable $WidgetsTable */
        $WidgetsTable = TableRegistry::getTableLocator()->get('Widgets');

        if ($this->request->is('get')) {
            $widgetId = (int)$this->request->getQuery('widgetId');
            $widget = $WidgetsTable->get($widgetId);
            $data = [];
            if ($widget->get('json_data') !== null && $widget->get('json_data') !== '') {
                $data = json_decode($widget->get('json_data'), true);
            }
            $config = $HostsTopAlertJson->standardizedData($data);
            $this->set('config', $config);
            $this->viewBuilder()->setOption('serialize', ['config']);
            return;
        }

        if ($this->request->is('post')) {
            $config = $HostsTopAlertJson->standardizedData($this->request->getData());
            $widgetId = (int)$this->request->getData('Widget.id', 0);

            if (!$WidgetsTable->existsById($widgetId)) {
                throw new RuntimeException('Invalid widget id');
            }
            $widget = $WidgetsTable->get($widgetId);

            /** @var DashboardTabsTable $DashboardTabsTable */
            $DashboardTabsTable = TableRegistry::getTableLocator()->get('DashboardTabs');

            $User = new User($this->getUser());

            if (!$DashboardTabsTable->isOwnedByUser($widget->dashboard_tab_id, $User->getId())) {
                throw new ForbiddenException();
            }

            $widget = $WidgetsTable->patchEntity($widget, [
                'json_data' => json_encode($config)
            ]);

            $WidgetsTable->save($widget);

            if ($widget->hasErrors()) {
                return $this->serializeCake4ErrorMessage($widget);
            }

            $this->set('config', $config);
            $this->viewBuilder()->setOption('serialize', ['config']);
            return;
        }

        throw new MethodNotAllowedException();
    }

    // This gets also used by the hostsStatusListExtendedWidget to load and save the filters
    public function hostsStatusListWidget() {
        if (!$this->isAngularJsRequest()) {
            //Only ship template
            return;
        }

        $widgetId = (int)$this->request->getQuery('widgetId');
        $HostStatusListJson = new HostStatusListJson();

        /** @var WidgetsTable $WidgetsTable */
        $WidgetsTable = TableRegistry::getTableLocator()->get('Widgets');

        if (!$WidgetsTable->existsById($widgetId)) {
            throw new NotFoundException('Widget not found');
        }

        $widget = $WidgetsTable->get($widgetId);

        if ($this->request->is('get')) {
            $data = [];
            if ($widget->get('json_data') !== null && $widget->get('json_data') !== '') {
                $data = json_decode($widget->get('json_data'), true);
            }
            $config = $HostStatusListJson->standardizedData($data);
            $this->set('config', $config);
            $this->viewBuilder()->setOption('serialize', ['config']);
            return;
        }

        if ($this->request->is('post')) {

            /** @var DashboardTabsTable $DashboardTabsTable */
            $DashboardTabsTable = TableRegistry::getTableLocator()->get('DashboardTabs');

            $User = new User($this->getUser());

            if (!$DashboardTabsTable->isOwnedByUser($widget->dashboard_tab_id, $User->getId())) {
                throw new ForbiddenException();
            }

            $config = $HostStatusListJson->standardizedData($this->request->getData());

            $widget = $WidgetsTable->patchEntity($widget, [
                'json_data' => json_encode($config)
            ]);
            $WidgetsTable->save($widget);

            $this->set('config', $config);
            $this->viewBuilder()->setOption('serialize', ['config']);
            return;
        }

        throw new MethodNotAllowedException();
    }

    public function hostsStatusListExtendedWidget() {
        // Only ship the HTML template
        // otherwise we need to copy&paste the self::hostsStatusListWidget() 1:1
        return;
    }

    public function hostsDowntimeWidget() {
        if (!$this->isAngularJsRequest()) {
            //Only ship template
            return;
        }

        $widgetId = (int)$this->request->getQuery('widgetId');
        $DowntimeHostListJson = new DowntimeHostListJson();

        /** @var WidgetsTable $WidgetsTable */
        $WidgetsTable = TableRegistry::getTableLocator()->get('Widgets');

        if (!$WidgetsTable->existsById($widgetId)) {
            throw new NotFoundException('Widget not found');
        }

        $widget = $WidgetsTable->get($widgetId);

        if ($this->request->is('get')) {
            $data = [];
            if ($widget->get('json_data') !== null && $widget->get('json_data') !== '') {
                $data = json_decode($widget->get('json_data'), true);
            }
            $config = $DowntimeHostListJson->standardizedData($data);
            $this->set('config', $config);
            $this->viewBuilder()->setOption('serialize', ['config']);
            return;
        }

        if ($this->request->is('post')) {

            /** @var DashboardTabsTable $DashboardTabsTable */
            $DashboardTabsTable = TableRegistry::getTableLocator()->get('DashboardTabs');

            $User = new User($this->getUser());

            if (!$DashboardTabsTable->isOwnedByUser($widget->dashboard_tab_id, $User->getId())) {
                throw new ForbiddenException();
            }

            $config = $DowntimeHostListJson->standardizedData($this->request->getData());

            $widget = $WidgetsTable->patchEntity($widget, [
                'json_data' => json_encode($config)
            ]);
            $WidgetsTable->save($widget);

            $this->set('config', $config);
            $this->viewBuilder()->setOption('serialize', ['config']);
            return;
        }

        throw new MethodNotAllowedException();
    }

    public function servicesDowntimeWidget() {
        if (!$this->isAngularJsRequest()) {
            //Only ship template
            return;
        }

        $widgetId = (int)$this->request->getQuery('widgetId');
        $DowntimeServiceListJson = new DowntimeServiceListJson();

        /** @var WidgetsTable $WidgetsTable */
        $WidgetsTable = TableRegistry::getTableLocator()->get('Widgets');

        if (!$WidgetsTable->existsById($widgetId)) {
            throw new NotFoundException('Widget not found');
        }

        $widget = $WidgetsTable->get($widgetId);

        if ($this->request->is('get')) {
            $data = [];
            if ($widget->get('json_data') !== null && $widget->get('json_data') !== '') {
                $data = json_decode($widget->get('json_data'), true);
            }

            $config = $DowntimeServiceListJson->standardizedData($data);
            $this->set('config', $config);
            $this->viewBuilder()->setOption('serialize', ['config']);
            return;
        }

        if ($this->request->is('post')) {

            /** @var DashboardTabsTable $DashboardTabsTable */
            $DashboardTabsTable = TableRegistry::getTableLocator()->get('DashboardTabs');

            $User = new User($this->getUser());

            if (!$DashboardTabsTable->isOwnedByUser($widget->dashboard_tab_id, $User->getId())) {
                throw new ForbiddenException();
            }

            $config = $DowntimeServiceListJson->standardizedData($this->request->getData());

            $widget = $WidgetsTable->patchEntity($widget, [
                'json_data' => json_encode($config)
            ]);
            $WidgetsTable->save($widget);

            $this->set('config', $config);
            $this->viewBuilder()->setOption('serialize', ['config']);
            return;
        }

        throw new MethodNotAllowedException();
    }

    // Gets also used by the servicesStatusListExtendedWidget
    public function servicesStatusListWidget() {
        if (!$this->isAngularJsRequest()) {
            //Only ship template
            return;
        }

        $widgetId = (int)$this->request->getQuery('widgetId');
        $ServiceStatusListJson = new ServiceStatusListJson();

        /** @var WidgetsTable $WidgetsTable */
        $WidgetsTable = TableRegistry::getTableLocator()->get('Widgets');

        if (!$WidgetsTable->existsById($widgetId)) {
            throw new NotFoundException('Widget not found');
        }

        $widget = $WidgetsTable->get($widgetId);

        if ($this->request->is('get')) {
            $data = [];
            if ($widget->get('json_data') !== null && $widget->get('json_data') !== '') {
                $data = json_decode($widget->get('json_data'), true);
            }

            $config = $ServiceStatusListJson->standardizedData($data);
            $this->set('config', $config);
            $this->viewBuilder()->setOption('serialize', ['config']);
            return;
        }

        if ($this->request->is('post')) {
            /** @var DashboardTabsTable $DashboardTabsTable */
            $DashboardTabsTable = TableRegistry::getTableLocator()->get('DashboardTabs');

            $User = new User($this->getUser());

            if (!$DashboardTabsTable->isOwnedByUser($widget->dashboard_tab_id, $User->getId())) {
                throw new ForbiddenException();
            }

            $config = $ServiceStatusListJson->standardizedData($this->request->getData());

            $widget = $WidgetsTable->patchEntity($widget, [
                'json_data' => json_encode($config)
            ]);
            $WidgetsTable->save($widget);

            $this->set('config', $config);
            $this->viewBuilder()->setOption('serialize', ['config']);
            return;
        }

        throw new MethodNotAllowedException();
    }

    public function servicesStatusListExtendedWidget() {
        // Only ship the HTML template
        // otherwise we need to copy&paste the self::servicesStatusListWidget() 1:1
        return;
    }

    public function noticeWidget() {
        if (!$this->isAngularJsRequest()) {
            //Only ship template
            return;
        }
        $widgetId = (int)$this->request->getQuery('widgetId');
        $NoticeJson = new NoticeJson();

        /** @var WidgetsTable $WidgetsTable */
        $WidgetsTable = TableRegistry::getTableLocator()->get('Widgets');

        if (!$WidgetsTable->existsById($widgetId)) {
            throw new NotFoundException('Widget not found');
        }

        $widget = $WidgetsTable->get($widgetId);

        if ($this->request->is('get')) {
            $data = [];
            $htmlContent = '';
            if ($widget->get('json_data') !== null && $widget->get('json_data') !== '') {
                $data = json_decode($widget->get('json_data'), true);
                if (!empty($data['note'])) {
                    $ParseDown = new ParsedownExtra();
                    $htmlContent = $ParseDown->text($data['note']);
                }
            }

            $config = $NoticeJson->standardizedData($data);
            $this->set('config', $config);
            $this->set('htmlContent', $htmlContent);

            $this->viewBuilder()->setOption('serialize', ['config', 'htmlContent']);
            return;
        }


        if ($this->request->is('post')) {

            /** @var DashboardTabsTable $DashboardTabsTable */
            $DashboardTabsTable = TableRegistry::getTableLocator()->get('DashboardTabs');

            $User = new User($this->getUser());

            if (!$DashboardTabsTable->isOwnedByUser($widget->dashboard_tab_id, $User->getId())) {
                throw new ForbiddenException();
            }

            $widget = $WidgetsTable->patchEntity($widget, [
                'json_data' => json_encode([
                    'note' => $this->request->getData('note', '')
                ])
            ]);

            $WidgetsTable->save($widget);
            if ($widget->hasErrors()) {
                return $this->serializeCake4ErrorMessage($widget);
            }

            $this->set('sucess', true);
            $this->viewBuilder()->setOption('sucess', ['config']);
            return;
        }
        throw new MethodNotAllowedException();
    }

    public function trafficLightWidget() {
        if (!$this->isApiRequest()) {
            //Only ship HTML template
            return;
        }

        $TrafficlightJson = new TrafficlightJson();

        /** @var WidgetsTable $WidgetsTable */
        $WidgetsTable = TableRegistry::getTableLocator()->get('Widgets');

        if ($this->request->is('get')) {
            $widgetId = (int)$this->request->getQuery('widgetId');
            if (!$WidgetsTable->existsById($widgetId)) {
                throw new RuntimeException('Invalid widget id');
            }

            $widget = $WidgetsTable->get($widgetId);

            $serviceId = (int)$widget->get('service_id');
            if ($serviceId === 0) {
                $serviceId = null;
            }

            $service = $this->getServicestatusByServiceId($serviceId);

            $data = [];
            if ($widget->get('json_data') !== null && $widget->get('json_data') !== '') {
                $data = json_decode($widget->get('json_data'), true);
            }
            $config = $TrafficlightJson->standardizedData($data);
            $this->set('config', $config);
            $this->set('service', $service);
            $this->set('ACL', $this->getAcls());
            $this->viewBuilder()->setOption('serialize', ['service', 'config', 'ACL']);
            return;
        }


        if ($this->request->is('post')) {
            $config = $TrafficlightJson->standardizedData($this->request->getData());
            $widgetId = (int)$this->request->getData('Widget.id', 0);
            $serviceId = (int)$this->request->getData('Widget.service_id', 0);

            if (!$WidgetsTable->existsById($widgetId)) {
                throw new RuntimeException('Invalid widget id');
            }
            $widget = $WidgetsTable->get($widgetId);

            /** @var DashboardTabsTable $DashboardTabsTable */
            $DashboardTabsTable = TableRegistry::getTableLocator()->get('DashboardTabs');

            $User = new User($this->getUser());

            if (!$DashboardTabsTable->isOwnedByUser($widget->dashboard_tab_id, $User->getId())) {
                throw new ForbiddenException();
            }

            $widget = $WidgetsTable->patchEntity($widget, [
                'service_id' => $serviceId,
                'json_data'  => json_encode($config)
            ]);

            $WidgetsTable->save($widget);

            if ($widget->hasErrors()) {
                return $this->serializeCake4ErrorMessage($widget);
            }

            $service = $this->getServicestatusByServiceId($serviceId);
            $this->set('service', $service);
            $this->set('config', $config);
            $this->set('ACL', $this->getAcls());
            $this->viewBuilder()->setOption('serialize', ['service', 'config', 'ACL']);
            return;
        }
        throw new MethodNotAllowedException();
    }

    public function tachoWidget() {
        if (!$this->isApiRequest()) {
            //Only ship HTML template
            return;
        }

        $TachoJson = new TachoJson();

        /** @var WidgetsTable $WidgetsTable */
        $WidgetsTable = TableRegistry::getTableLocator()->get('Widgets');

        if ($this->request->is('get')) {
            $widgetId = (int)$this->request->getQuery('widgetId');
            if (!$WidgetsTable->existsById($widgetId)) {
                throw new RuntimeException('Invalid widget id');
            }

            $widget = $WidgetsTable->get($widgetId);

            $serviceId = (int)$widget->get('service_id');
            if ($serviceId === 0) {
                $serviceId = null;
            }

            $service = $this->getServicestatusByServiceId($serviceId);
            $data = [];
            $metrics = array_keys($service['Perfdata']);
            foreach ($metrics as $metric) {
                if (Plugin::isLoaded('PrometheusModule') && $service['Service']['serviceType'] === PROMETHEUS_SERVICE) {
                    $PrometheusPerfdataLoader = new \PrometheusModule\Lib\PrometheusPerfdataLoader();
                    $Service = new Service($service);
                    $perfdata = $PrometheusPerfdataLoader->getAvailableMetricsByService($Service, false, true);
                    // Query Prometheus to get all metrics
                    $adapter = new PrometheusAdapter();
                    $service['Perfdata'][$metric]['datasource']['setup'] = $adapter->getPerformanceData(new Service($service), $perfdata[$metric])->toArray();
                } else {
                    $PerfdataParser = new PerfdataParser($service['Servicestatus']['perfdata']);
                    $perfdata = $PerfdataParser->parse();
                    $perfdata = $perfdata[$metric] ?? [];
                    $adapter = new NagiosAdapter();
                    $service['Perfdata'][$metric]['datasource']['setup'] = $adapter->getPerformanceData(new Service($service), $perfdata)->toArray();
                }
            }

            if ($widget->get('json_data') !== null && $widget->get('json_data') !== '') {
                $data = json_decode($widget->get('json_data'), true);
            }
            $config = $TachoJson->standardizedData($data);
            $this->set('config', $config);
            $this->set('service', $service);
            $this->set('ACL', $this->getAcls());
            $this->viewBuilder()->setOption('serialize', ['service', 'config', 'ACL']);
            return;
        }


        if ($this->request->is('post')) {
            $config = $TachoJson->standardizedData($this->request->getData());
            $widgetId = (int)$this->request->getData('Widget.id', 0);
            $serviceId = (int)$this->request->getData('Widget.service_id', 0);

            if (!$WidgetsTable->existsById($widgetId)) {
                throw new RuntimeException('Invalid widget id');
            }
            $widget = $WidgetsTable->get($widgetId);

            /** @var DashboardTabsTable $DashboardTabsTable */
            $DashboardTabsTable = TableRegistry::getTableLocator()->get('DashboardTabs');

            $User = new User($this->getUser());

            if (!$DashboardTabsTable->isOwnedByUser($widget->dashboard_tab_id, $User->getId())) {
                throw new ForbiddenException();
            }

            $widget = $WidgetsTable->patchEntity($widget, [
                'service_id' => $serviceId,
                'json_data'  => json_encode($config)
            ]);

            $WidgetsTable->save($widget);

            if ($widget->hasErrors()) {
                return $this->serializeCake4ErrorMessage($widget);
            }

            $service = $this->getServicestatusByServiceId($serviceId);
            $this->set('service', $service);
            $this->set('config', $config);
            $this->set('ACL', $this->getAcls());
            $this->viewBuilder()->setOption('serialize', ['service', 'config', 'ACL']);
            return;
        }
        throw new MethodNotAllowedException();
    }

    private function getServicestatusByServiceId($id) {
        if ($id === null) {
            return [
                'Service'       => [],
                'Servicestatus' => [],
                'Perfdata'      => []
            ];
        }

        /** @var ServicesTable $ServicesTable */
        $ServicesTable = TableRegistry::getTableLocator()->get('Services');
        $ServicestatusTable = $this->DbBackend->getServicestatusTable();

        $service = $ServicesTable->getServiceById($id);
        if ($service) {
            if ($this->allowedByContainerId($service->get('host')->getContainerIds(), false)) {

                $ServicestatusFields = new ServicestatusFields($this->DbBackend);
                $ServicestatusFields->currentState()->isFlapping()->perfdata();
                $servicestatus = $ServicestatusTable->byUuid($service->get('uuid'), $ServicestatusFields);

                if (!empty($servicestatus)) {
                    $Servicestatus = new Servicestatus($servicestatus['Servicestatus']);
                } else {
                    $Servicestatus = new Servicestatus([
                        'Servicestatus' => []
                    ]);
                }
                $Host = new Host($service->get('host')->toArray());
                $Service = new Service($service->toArray());
                $PerfdataParser = new PerfdataParser($Servicestatus->getPerfdata());


                $serviceForJs = [
                    'Host'          => $Host->toArray(),
                    'Service'       => $Service->toArray(),
                    'Servicestatus' => $Servicestatus->toArray(),
                    'Perfdata'      => $PerfdataParser->parse()
                ];

                $serviceForJs['Service']['isGenericService'] = $serviceForJs['Service']['serviceType'] == GENERIC_SERVICE;
                $serviceForJs['Service']['isEVCService'] = $serviceForJs['Service']['serviceType'] == EVK_SERVICE;
                $serviceForJs['Service']['isSLAService'] = $serviceForJs['Service']['serviceType'] == SLA_SERVICE;
                $serviceForJs['Service']['isMkService'] = $serviceForJs['Service']['serviceType'] == MK_SERVICE;

                $serviceForJs['Service']['id'] = (int)$serviceForJs['Service']['id'];
                $serviceForJs['Host']['id'] = (int)$serviceForJs['Host']['id'];

                if (Plugin::isLoaded('PrometheusModule') && $service->service_type === PROMETHEUS_SERVICE) {
                    // Query Prometheus to get all metrics
                    $ServiceObj = new Service($service->toArray());

                    $PrometheusPerfdataLoader = new \PrometheusModule\Lib\PrometheusPerfdataLoader();
                    $perfdata = $PrometheusPerfdataLoader->getAvailableMetricsByService($ServiceObj, false, true);

                    $serviceForJs['Perfdata'] = $perfdata;

                    foreach ($serviceForJs['Perfdata'] as $metric => $perfdata) {
                        $serviceForJs['Perfdata'][$metric]['metric'] = $metric;
                        $serviceForJs['Servicestatus']['perfdata'] = sprintf(
                            '%s=%s%s;%s;%s;-50;50;',
                            h($metric),
                            h($perfdata['current']),
                            h($perfdata['unit']),
                            h($perfdata['warning']),
                            h($perfdata['critical'])
                        );
                    }
                }

                return $serviceForJs;
            }
        }

        return [
            'Service'       => [],
            'Servicestatus' => []
        ];
    }

    /**
     * @return array
     * @deprecated Remove with openITCOCKPIT 5 as this is not required for the Angular Frontend anymore !!
     */
    private function getAcls() {
        $acl = [
            'hosts'    => [
                'browser' => isset($this->PERMISSIONS['hosts']['browser']),
                'index'   => isset($this->PERMISSIONS['hosts']['index'])
            ],
            'services' => [
                'browser' => isset($this->PERMISSIONS['services']['browser']),
                'index'   => isset($this->PERMISSIONS['services']['index'])

            ],
            'evc'      => [
                'view' => isset($this->PERMISSIONS['eventcorrelationmodule']['eventcorrelations']['view']),
            ],
        ];
        return $acl;
    }

    public function hostStatusOverviewWidget() {
        if (!$this->isApiRequest()) {
            //Only ship HTML template
            return;
        }
        $HostStatusOverviewJson = new HostStatusOverviewJson();

        /** @var WidgetsTable $WidgetsTable */
        $WidgetsTable = TableRegistry::getTableLocator()->get('Widgets');

        if ($this->request->is('get')) {
            $widgetId = (int)$this->request->getQuery('widgetId');
            if (!$WidgetsTable->existsById($widgetId)) {
                throw new RuntimeException('Invalid widget id');
            }

            $MY_RIGHTS = [];
            if ($this->hasRootPrivileges === false) {
                /** @var $ContainersTable ContainersTable */
                //$ContainersTable = TableRegistry::getTableLocator()->get('Containers');
                //$MY_RIGHTS = $ContainersTable->resolveChildrenOfContainerIds($this->MY_RIGHTS);
                // ITC-2863 $this->MY_RIGHTS is already resolved and contains all containerIds a user has access to
                $MY_RIGHTS = $this->MY_RIGHTS;
            }

            $widget = $WidgetsTable->get($widgetId);

            $data = [];
            if ($widget->get('json_data') !== null && $widget->get('json_data') !== '') {
                $data = json_decode($widget->get('json_data'), true);
            }
            $config = $HostStatusOverviewJson->standardizedData($data);

            if ($this->DbBackend->isNdoUtils()) {
                /** @var HostsTable $HostsTable */
                $HostsTable = TableRegistry::getTableLocator()->get('Hosts');

                $count = $HostsTable->getHoststatusCountBySelectedStatus($MY_RIGHTS, $config);
            }

            if ($this->DbBackend->isCrateDb()) {
                throw new MissingDbBackendException('MissingDbBackendException');
            }

            if ($this->DbBackend->isStatusengine3()) {
                /** @var HostsTable $HostsTable */
                $HostsTable = TableRegistry::getTableLocator()->get('Hosts');

                $count = $HostsTable->getHoststatusCountBySelectedStatusStatusengine3($MY_RIGHTS, $config);
            }
            $this->set('config', $config);
            $this->set('statusCount', $count);
            $this->viewBuilder()->setOption('serialize', ['config', 'statusCount']);
            return;
        }

        if ($this->request->is('post')) {
            $config = $HostStatusOverviewJson->standardizedData($this->request->getData());
            $widgetId = (int)$this->request->getData('Widget.id', 0);

            if (!$WidgetsTable->existsById($widgetId)) {
                throw new RuntimeException('Invalid widget id');
            }
            $widget = $WidgetsTable->get($widgetId);

            /** @var DashboardTabsTable $DashboardTabsTable */
            $DashboardTabsTable = TableRegistry::getTableLocator()->get('DashboardTabs');

            $User = new User($this->getUser());

            if (!$DashboardTabsTable->isOwnedByUser($widget->dashboard_tab_id, $User->getId())) {
                throw new ForbiddenException();
            }

            $widget = $WidgetsTable->patchEntity($widget, [
                'json_data' => json_encode($config)
            ]);

            $WidgetsTable->save($widget);

            if ($widget->hasErrors()) {
                return $this->serializeCake4ErrorMessage($widget);
            }

            $this->set('config', $config);
            $this->viewBuilder()->setOption('serialize', ['config']);
            return;
        }

        throw new MethodNotAllowedException();
    }

    public function hostStatusOverviewExtendedWidget() {
        if (!$this->isApiRequest()) {
            //Only ship HTML template
            return;
        }
        $HostStatusOverviewExtendedJson = new HostStatusOverviewExtendedJson();

        /** @var WidgetsTable $WidgetsTable */
        $WidgetsTable = TableRegistry::getTableLocator()->get('Widgets');

        if ($this->request->is('get')) {
            $widgetId = (int)$this->request->getQuery('widgetId');
            if (!$WidgetsTable->existsById($widgetId)) {
                throw new RuntimeException('Invalid widget id');
            }

            $MY_RIGHTS = [];
            if ($this->hasRootPrivileges === false) {
                /** @var $ContainersTable ContainersTable */
                //$ContainersTable = TableRegistry::getTableLocator()->get('Containers');
                //$MY_RIGHTS = $ContainersTable->resolveChildrenOfContainerIds($this->MY_RIGHTS);
                // ITC-2863 $this->MY_RIGHTS is already resolved and contains all containerIds a user has access to
                $MY_RIGHTS = $this->MY_RIGHTS;
            }

            $widget = $WidgetsTable->get($widgetId);

            $data = [];
            if ($widget->get('json_data') !== null && $widget->get('json_data') !== '') {
                $data = json_decode($widget->get('json_data'), true);
            }
            $config = $HostStatusOverviewExtendedJson->standardizedData($data);
            $conditions = $config;
            // Migrate keyword / tags from JSON string to SQL RLIKE query string
            foreach (['keywords', 'not_keywords'] as $field) {
                if (empty($conditions['Host'][$field])) {
                    $conditions['Host'][$field] = [];
                }
                if (isset($conditions['Host'][$field]) && is_string($conditions['Host'][$field])) {
                    $arr = explode(',', $conditions['Host'][$field]);
                    $conditions['Host'][$field] = [];
                    if (!empty($arr)) {
                        $conditions['Host'][$field] = sprintf('.*(%s).*', implode('|', $arr));
                    }
                }
            }
            $hostIds = [];
            if ($this->DbBackend->isNdoUtils()) {
                throw new MissingDbBackendException('MissingDbBackendException');
            }

            if ($this->DbBackend->isCrateDb()) {
                throw new MissingDbBackendException('MissingDbBackendException');
            }

            if ($this->DbBackend->isStatusengine3()) {
                /** @var HostsTable $HostsTable */
                $HostsTable = TableRegistry::getTableLocator()->get('Hosts');

                $hostIds = $HostsTable->getHostIdsBySelectedStatusExtendedStatusengine3($MY_RIGHTS, $conditions);
            }

            $this->set('config', $config);
            $this->set('statusCount', sizeof($hostIds));
            $this->set('hostIds', $hostIds);
            $this->viewBuilder()->setOption('serialize', ['config', 'statusCount', 'hostIds']);
            return;
        }

        if ($this->request->is('post')) {
            $config = $HostStatusOverviewExtendedJson->standardizedData($this->request->getData());
            $widgetId = (int)$this->request->getData('Widget.id', 0);

            if (!$WidgetsTable->existsById($widgetId)) {
                throw new RuntimeException('Invalid widget id');
            }
            $widget = $WidgetsTable->get($widgetId);
            $widget = $WidgetsTable->patchEntity($widget, [
                'json_data' => json_encode($config)
            ]);

            $WidgetsTable->save($widget);

            if ($widget->hasErrors()) {
                return $this->serializeCake4ErrorMessage($widget);
            }

            $this->set('config', $config);
            $this->viewBuilder()->setOption('serialize', ['config']);
            return;
        }

        throw new MethodNotAllowedException();
    }

    public function servicesTopAlertsWidget() {
        if (!$this->isApiRequest()) {
            //Only ship HTML template
            return;
        }
        $ServicesTopAlertJson = new ServicesTopAlertJson();

        /** @var WidgetsTable $WidgetsTable */
        $WidgetsTable = TableRegistry::getTableLocator()->get('Widgets');

        if ($this->request->is('get')) {
            $widgetId = (int)$this->request->getQuery('widgetId');
            $widget = $WidgetsTable->get($widgetId);
            $data = [];
            if ($widget->get('json_data') !== null && $widget->get('json_data') !== '') {
                $data = json_decode($widget->get('json_data'), true);
            }
            $config = $ServicesTopAlertJson->standardizedData($data);
            $this->set('config', $config);
            $this->viewBuilder()->setOption('serialize', ['config']);
            return;
        }

        if ($this->request->is('post')) {
            $config = $ServicesTopAlertJson->standardizedData($this->request->getData());
            $widgetId = (int)$this->request->getData('Widget.id', 0);

            if (!$WidgetsTable->existsById($widgetId)) {
                throw new RuntimeException('Invalid widget id');
            }
            $widget = $WidgetsTable->get($widgetId);

            /** @var DashboardTabsTable $DashboardTabsTable */
            $DashboardTabsTable = TableRegistry::getTableLocator()->get('DashboardTabs');

            $User = new User($this->getUser());

            if (!$DashboardTabsTable->isOwnedByUser($widget->dashboard_tab_id, $User->getId())) {
                throw new ForbiddenException();
            }

            $widget = $WidgetsTable->patchEntity($widget, [
                'json_data' => json_encode($config)
            ]);

            $WidgetsTable->save($widget);

            if ($widget->hasErrors()) {
                return $this->serializeCake4ErrorMessage($widget);
            }

            $this->set('config', $config);
            $this->viewBuilder()->setOption('serialize', ['config']);
            return;
        }

        throw new MethodNotAllowedException();
    }

    public function serviceStatusOverviewWidget() {
        if (!$this->isApiRequest()) {
            //Only ship HTML template
            return;
        }
        $ServiceStatusOverviewJson = new ServiceStatusOverviewJson();

        /** @var WidgetsTable $WidgetsTable */
        $WidgetsTable = TableRegistry::getTableLocator()->get('Widgets');

        if ($this->request->is('get')) {
            $widgetId = (int)$this->request->getQuery('widgetId');
            if (!$WidgetsTable->existsById($widgetId)) {
                throw new RuntimeException('Invalid widget id');
            }

            $MY_RIGHTS = [];
            if ($this->hasRootPrivileges === false) {
                /** @var $ContainersTable ContainersTable */
                //$ContainersTable = TableRegistry::getTableLocator()->get('Containers');
                //$MY_RIGHTS = $ContainersTable->resolveChildrenOfContainerIds($this->MY_RIGHTS);
                // ITC-2863 $this->MY_RIGHTS is already resolved and contains all containerIds a user has access to
                $MY_RIGHTS = $this->MY_RIGHTS;
            }

            $widget = $WidgetsTable->get($widgetId);

            $data = [];
            if ($widget->get('json_data') !== null && $widget->get('json_data') !== '') {
                $data = json_decode($widget->get('json_data'), true);
            }
            $config = $ServiceStatusOverviewJson->standardizedData($data);

            if ($this->DbBackend->isNdoUtils()) {
                /** @var ServicesTable $ServicesTable */
                $ServicesTable = TableRegistry::getTableLocator()->get('Services');

                $count = $ServicesTable->getServicestatusCountBySelectedStatus($MY_RIGHTS, $config);
            }

            if ($this->DbBackend->isCrateDb()) {
                throw new MissingDbBackendException('MissingDbBackendException');
            }

            if ($this->DbBackend->isStatusengine3()) {
                /** @var ServicesTable $ServicesTable */
                $ServicesTable = TableRegistry::getTableLocator()->get('Services');

                $count = $ServicesTable->getServicestatusCountBySelectedStatusStatusengine3($MY_RIGHTS, $config);
            }
            $this->set('config', $config);
            $this->set('statusCount', $count);
            $this->viewBuilder()->setOption('serialize', ['config', 'statusCount']);
            return;
        }

        if ($this->request->is('post')) {
            $config = $ServiceStatusOverviewJson->standardizedData($this->request->getData());

            $widgetId = (int)$this->request->getData('Widget.id', 0);

            if (!$WidgetsTable->existsById($widgetId)) {
                throw new RuntimeException('Invalid widget id');
            }
            $widget = $WidgetsTable->get($widgetId);

            /** @var DashboardTabsTable $DashboardTabsTable */
            $DashboardTabsTable = TableRegistry::getTableLocator()->get('DashboardTabs');

            $User = new User($this->getUser());

            if (!$DashboardTabsTable->isOwnedByUser($widget->dashboard_tab_id, $User->getId())) {
                throw new ForbiddenException();
            }

            $widget = $WidgetsTable->patchEntity($widget, [
                'json_data' => json_encode($config)
            ]);

            $WidgetsTable->save($widget);

            if ($widget->hasErrors()) {
                return $this->serializeCake4ErrorMessage($widget);
            }

            $this->set('config', $config);
            $this->viewBuilder()->setOption('serialize', ['config']);
            return;
        }


        throw new MethodNotAllowedException();
    }

    public function serviceStatusOverviewExtendedWidget() {
        if (!$this->isApiRequest()) {
            //Only ship HTML template
            return;
        }
        $ServiceStatusOverviewExtendedJson = new ServiceStatusOverviewExtendedJson();


        /** @var WidgetsTable $WidgetsTable */
        $WidgetsTable = TableRegistry::getTableLocator()->get('Widgets');

        if ($this->request->is('get')) {
            $widgetId = (int)$this->request->getQuery('widgetId');
            if (!$WidgetsTable->existsById($widgetId)) {
                throw new RuntimeException('Invalid widget id');
            }

            $MY_RIGHTS = [];
            if ($this->hasRootPrivileges === false) {
                /** @var $ContainersTable ContainersTable */
                //$ContainersTable = TableRegistry::getTableLocator()->get('Containers');
                //$MY_RIGHTS = $ContainersTable->resolveChildrenOfContainerIds($this->MY_RIGHTS);
                // ITC-2863 $this->MY_RIGHTS is already resolved and contains all containerIds a user has access to
                $MY_RIGHTS = $this->MY_RIGHTS;
            }

            $widget = $WidgetsTable->get($widgetId);

            $data = [];
            if ($widget->get('json_data') !== null && $widget->get('json_data') !== '') {
                $data = json_decode($widget->get('json_data'), true);
            }
            $config = $ServiceStatusOverviewExtendedJson->standardizedData($data);
            $conditions = $config;
            foreach (['Host', 'Service'] as $tableName) {
                foreach (['keywords', 'not_keywords'] as $field) {
                    if (empty($conditions[$tableName][$field])) {
                        $conditions[$tableName][$field] = [];
                    }

                    if (isset($conditions[$tableName][$field]) && is_string($conditions[$tableName][$field])) {
                        $arr = explode(',', $conditions[$tableName][$field]);
                        $conditions[$tableName][$field] = [];
                        if (!empty($arr)) {
                            $conditions[$tableName][$field] = sprintf('.*(%s).*', implode('|', $arr));
                        }
                    }
                }
            }
            $serviceIds = [];
            if ($this->DbBackend->isNdoUtils()) {
                throw new MissingDbBackendException('MissingDbBackendException');
            }

            if ($this->DbBackend->isCrateDb()) {
                throw new MissingDbBackendException('MissingDbBackendException');
            }

            if ($this->DbBackend->isStatusengine3()) {
                /** @var ServicesTable $ServicesTable */
                $ServicesTable = TableRegistry::getTableLocator()->get('Services');

                $serviceIds = $ServicesTable->getServiceIdsBySelectedStatusExtendedStatusengine3($MY_RIGHTS, $conditions);
            }

            $this->set('config', $config);
            $this->set('statusCount', sizeof($serviceIds));
            $this->set('serviceIds', $serviceIds);
            $this->viewBuilder()->setOption('serialize', ['config', 'statusCount', 'serviceIds']);
            return;
        }

        if ($this->request->is('post')) {
            $config = $ServiceStatusOverviewExtendedJson->standardizedData($this->request->getData());
            $widgetId = (int)$this->request->getData('Widget.id', 0);

            if (!$WidgetsTable->existsById($widgetId)) {
                throw new RuntimeException('Invalid widget id');
            }
            $widget = $WidgetsTable->get($widgetId);

            /** @var DashboardTabsTable $DashboardTabsTable */
            $DashboardTabsTable = TableRegistry::getTableLocator()->get('DashboardTabs');

            $User = new User($this->getUser());

            if (!$DashboardTabsTable->isOwnedByUser($widget->dashboard_tab_id, $User->getId())) {
                throw new ForbiddenException();
            }

            $widget = $WidgetsTable->patchEntity($widget, [
                'json_data' => json_encode($config)
            ]);

            $WidgetsTable->save($widget);

            if ($widget->hasErrors()) {
                return $this->serializeCake4ErrorMessage($widget);
            }

            $this->set('config', $config);
            $this->viewBuilder()->setOption('serialize', ['config']);
            return;
        }

        throw new MethodNotAllowedException();
    }

    public function getPerformanceDataMetrics($serviceId = 0) {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var ServicesTable $ServicesTable */
        $ServicesTable = TableRegistry::getTableLocator()->get('Services');
        $ServicestatusTable = $this->DbBackend->getServicestatusTable();

        if (!$ServicesTable->existsById($serviceId)) {
            throw new NotFoundException();
        }


        $service = $ServicesTable->getServiceById($serviceId);
        $perfdata = [];
        if ($service) {
            if ($this->allowedByContainerId($service->get('host')->getContainerIds(), false)) {
                if (Plugin::isLoaded('PrometheusModule') && $service->service_type === PROMETHEUS_SERVICE) {
                    // Query Prometheus to get all metrics
                    $Service = new Service($service->toArray());

                    $PrometheusPerfdataLoader = new \PrometheusModule\Lib\PrometheusPerfdataLoader();
                    $perfdata = $PrometheusPerfdataLoader->getAvailableMetricsByService($Service, false, true);
                    $adapter = new PrometheusAdapter();
                    foreach ($perfdata as $metric => $data) {
                        $perfdata[$metric]['setup'] = $adapter->getPerformanceData($Service, $perfdata[$metric])->toArray();
                    }
                } else {
                    $ServicestatusFields = new ServicestatusFields($this->DbBackend);
                    $ServicestatusFields->perfdata();
                    $servicestatus = $ServicestatusTable->byUuid($service->get('uuid'), $ServicestatusFields);

                    if (!empty($servicestatus)) {
                        $PerfdataParser = new PerfdataParser($servicestatus['Servicestatus']['perfdata']);
                        $perfdata = $PerfdataParser->parse();
                    }
                }
            }
        }
        $this->set('perfdata', $perfdata);
        $this->viewBuilder()->setOption('serialize', ['perfdata']);
    }


    public function websiteWidget() {
        if (!$this->isAngularJsRequest()) {
            //Only ship template
            return;
        }
        $widgetId = (int)$this->request->getQuery('widgetId');
        $WebsiteJson = new WebsiteJson();

        /** @var WidgetsTable $WidgetsTable */
        $WidgetsTable = TableRegistry::getTableLocator()->get('Widgets');

        if (!$WidgetsTable->existsById($widgetId)) {
            throw new NotFoundException('Widget not found');
        }

        $widget = $WidgetsTable->get($widgetId);

        if ($this->request->is('get')) {
            $data = [];
            $url = 'https://itsm.love'; // https://openitcockpit.io is forbidden to be loaded in an iframe
            if ($widget->get('json_data') !== null && $widget->get('json_data') !== '') {
                $data = json_decode($widget->get('json_data'), true);
                if (!empty($data['url'])) {
                    $url = $data['url'];
                }
            }

            $config = $WebsiteJson->standardizedData($data);
            $this->set('config', $config);
            $this->set('url', $url);

            $this->viewBuilder()->setOption('serialize', ['config', 'url']);
            return;
        }


        if ($this->request->is('post')) {

            /** @var DashboardTabsTable $DashboardTabsTable */
            $DashboardTabsTable = TableRegistry::getTableLocator()->get('DashboardTabs');

            $User = new User($this->getUser());
            if (!$DashboardTabsTable->isOwnedByUser($widget->dashboard_tab_id, $User->getId())) {
                throw new ForbiddenException();
            }

            $widget = $WidgetsTable->patchEntity($widget, [
                'json_data' => json_encode([
                    'url' => $this->request->getData('url', '')
                ])
            ]);

            $WidgetsTable->save($widget);
            if ($widget->hasErrors()) {
                return $this->serializeCake4ErrorMessage($widget);
            }

            $this->set('sucess', true);
            $this->viewBuilder()->setOption('sucess', ['config']);
            return;
        }
        throw new MethodNotAllowedException();
    }

    public function tacticalOverviewHostsWidget() {
        //Only ship HTML template
        return;
    }

    public function tacticalOverviewServicesWidget() {
        //Only ship HTML template
        return;
    }

    public function tacticalOverviewWidget() {
        if (!$this->isAngularJsRequest()) {
            //Only ship template
            return;
        }
        $widgetId = (int)$this->request->getQuery('widgetId');
        $type = $this->request->getQuery('type');
        $TacticalOverviewJson = new TacticalOverviewJson();


        /** @var WidgetsTable $WidgetsTable */
        $WidgetsTable = TableRegistry::getTableLocator()->get('Widgets');

        if (!$WidgetsTable->existsById($widgetId)) {
            throw new NotFoundException('Widget not found');
        }

        $widget = $WidgetsTable->get($widgetId);

        if ($this->request->is('get')) {
            $MY_RIGHTS = [];
            if ($this->hasRootPrivileges === false) {
                /** @var $ContainersTable ContainersTable */
                //$ContainersTable = TableRegistry::getTableLocator()->get('Containers');
                //$MY_RIGHTS = $ContainersTable->resolveChildrenOfContainerIds($this->MY_RIGHTS);
                // ITC-2863 $this->MY_RIGHTS is already resolved and contains all containerIds a user has access to
                $MY_RIGHTS = $this->MY_RIGHTS;
            }

            $data = [];
            if ($widget->get('json_data') !== null && $widget->get('json_data') !== '') {
                $data = json_decode($widget->get('json_data'), true);
            }
            $config = $TacticalOverviewJson->standardizedData($data);

            $conditions = $config;
            // Migrate keyword / tags from JSON string to SQL RLIKE query string
            foreach (['Host', 'Service'] as $tableName) {
                foreach (['keywords', 'not_keywords'] as $field) {
                    if (empty($conditions[$tableName][$field])) {
                        $conditions[$tableName][$field] = [];
                    }

                    if (isset($conditions[$tableName][$field]) && is_string($conditions[$tableName][$field])) {
                        $arr = explode(',', $conditions[$tableName][$field]);
                        $conditions[$tableName][$field] = [];
                        if (!empty($arr)) {
                            $conditions[$tableName][$field] = sprintf('.*(%s).*', implode('|', $arr));
                        }
                    }
                }
            }

            $hoststatusSummary = [];
            $hoststatusCountPercentage = [];
            $servicestatusSummary = [];
            $servicestatusCountPercentage = [];
            switch ($type) {
                case 'hosts':
                    $hoststatus = [];
                    if ($this->DbBackend->isNdoUtils()) {
                        /** @var HostsTable $HostsTable */
                        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
                        $hoststatus = $HostsTable->getHostsWithStatusByConditions($MY_RIGHTS, $conditions);
                    }

                    if ($this->DbBackend->isCrateDb()) {
                        throw new MissingDbBackendException('MissingDbBackendException');
                    }

                    if ($this->DbBackend->isStatusengine3()) {
                        /** @var HostsTable $HostsTable */
                        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
                        $hoststatus = $HostsTable->getHostsWithStatusByConditionsStatusengine3($MY_RIGHTS, $conditions);
                    }
                    $hoststatusSummary = $HostsTable->getHostStateSummary($hoststatus, true);

                    if (!empty($hoststatusSummary) && isset($hoststatusSummary['state'])) {
                        foreach ($hoststatusSummary['state'] as $stateId => $count) {
                            if (is_numeric($stateId)) {
                                if ($hoststatusSummary['total'] > 0) {
                                    $hoststatusCountPercentage[$stateId] = round($count / $hoststatusSummary['total'] * 100, 2);
                                } else {
                                    $hoststatusCountPercentage[$stateId] = 0;
                                }
                            }
                        }
                    }

                    break;
                case 'services':
                    $servicestatus = [];
                    if ($this->DbBackend->isNdoUtils()) {
                        /** @var ServicesTable $ServicesTable */
                        $ServicesTable = TableRegistry::getTableLocator()->get('Services');
                        $servicestatus = $ServicesTable->getServicesWithStatusByConditions($MY_RIGHTS, $conditions);
                    }

                    if ($this->DbBackend->isCrateDb()) {
                        throw new MissingDbBackendException('MissingDbBackendException');
                    }

                    if ($this->DbBackend->isStatusengine3()) {
                        /** @var ServicesTable $ServicesTable */
                        $ServicesTable = TableRegistry::getTableLocator()->get('Services');
                        $servicestatus = $ServicesTable->getServicesWithStatusByConditionsStatusengine3($MY_RIGHTS, $conditions);
                    }
                    $servicestatusSummary = $ServicesTable->getServiceStateSummary($servicestatus, true);

                    if (!empty($servicestatusSummary) && isset($servicestatusSummary['state'])) {
                        foreach ($servicestatusSummary['state'] as $stateId => $count) {
                            if (is_numeric($stateId)) {
                                if ($servicestatusSummary['total'] > 0) {
                                    $servicestatusCountPercentage[$stateId] = round($count / $servicestatusSummary['total'] * 100, 2);
                                } else {
                                    $servicestatusCountPercentage[$stateId] = 0;
                                }
                            }
                        }
                    }

                    break;
            }

            $hostgroupIds = [];
            $servicegroupIds = [];
            if (!empty($config['Hostgroup']['_ids'])) {
                foreach (explode(',', $config['Hostgroup']['_ids']) as $hostgroupId) {
                    $hostgroupIds[] = (int)$hostgroupId;
                }
            }
            if (!empty($config['Servicegroup']['_ids'])) {
                foreach (explode(',', $config['Servicegroup']['_ids']) as $servicegroupId) {
                    $servicegroupIds[] = (int)$servicegroupId;
                }
            }
            $config['Hostgroup']['_ids'] = $hostgroupIds;
            $config['Servicegroup']['_ids'] = $servicegroupIds;


            $this->set('config', $config);
            $this->set('hoststatusSummary', $hoststatusSummary);
            $this->set('hoststatusCountPercentage', $hoststatusCountPercentage);
            $this->set('servicestatusSummary', $servicestatusSummary);
            $this->set('servicestatusCountPercentage', $servicestatusCountPercentage);

            $this->viewBuilder()->setOption('serialize', [
                'config',
                'hoststatusSummary',
                'hoststatusCountPercentage',
                'servicestatusSummary',
                'servicestatusCountPercentage'
            ]);
            return;
        }


        if ($this->request->is('post')) {
            /** @var DashboardTabsTable $DashboardTabsTable */
            $DashboardTabsTable = TableRegistry::getTableLocator()->get('DashboardTabs');

            $User = new User($this->getUser());

            if (!$DashboardTabsTable->isOwnedByUser($widget->dashboard_tab_id, $User->getId())) {
                throw new ForbiddenException();
            }

            $config = $TacticalOverviewJson->standardizedData($this->request->getData());
            $widget = $WidgetsTable->patchEntity($widget, [
                'json_data' => json_encode($config)
            ]);
            $WidgetsTable->save($widget);
            if ($widget->hasErrors()) {
                return $this->serializeCake4ErrorMessage($widget);
            }

            $this->set('sucess', true);
            $this->viewBuilder()->setOption('sucess', ['config']);
            return;
        }
        throw new MethodNotAllowedException();
    }

    public function todayWidget() {
        if (!$this->isAngularJsRequest()) {
            //Only ship template
            return;
        }
        $widgetId = (int)$this->request->getQuery('widgetId');
        /** @var WidgetsTable $WidgetsTable */
        $WidgetsTable = TableRegistry::getTableLocator()->get('Widgets');

        if (!$WidgetsTable->existsById($widgetId)) {
            throw new NotFoundException('Widget not found');
        }

        $widget = $WidgetsTable->get($widgetId);

        if ($this->request->is('get')) {

            $user = $this->getUser();
            $userTimezone = $user->get('timezone');
            if (strlen($userTimezone) < 2) {
                $userTimezone = 'Europe/Berlin';
            }
            $UserTime = new \DateTime($userTimezone);
            $CalendarTime = new CalendarTime();
            $dateDetails = $CalendarTime->getDateDetailsByTimestamp($UserTime->getTimestamp());

            $this->set('dateDetails', $dateDetails);
            $this->viewBuilder()->setOption('serialize', ['dateDetails']);
            return;
        }
        throw new MethodNotAllowedException();
    }

    public function calendarWidget() {
        if (!$this->isAngularJsRequest()) {
            //Only ship template
            return;
        }
        $widgetId = (int)$this->request->getQuery('widgetId');
        /** @var WidgetsTable $WidgetsTable */
        $WidgetsTable = TableRegistry::getTableLocator()->get('Widgets');

        if (!$WidgetsTable->existsById($widgetId)) {
            throw new NotFoundException('Widget not found');
        }

        $widget = $WidgetsTable->get($widgetId);

        if ($this->request->is('get')) {

            $user = $this->getUser();
            $userTimezone = $user->get('timezone');
            if (strlen($userTimezone) < 2) {
                $userTimezone = 'Europe/Berlin';
            }
            $UserTime = new \DateTime($userTimezone);
            $CalendarTime = new CalendarTime();
            $dateDetails = $CalendarTime->getDateDetailsByTimestamp($UserTime->getTimestamp(), true);

            $this->set('dateDetails', $dateDetails);
            $this->viewBuilder()->setOption('serialize', ['dateDetails']);
            return;
        }
        throw new MethodNotAllowedException();
    }

    /*************************************************
     *       openITCOCKPIT Desktop App METHODS       *
     *************************************************/
    public function desktopWidget() {
        $queryData = [];
        if ($this->request->is('post')) {
            $queryData = $this->request->getData();
        }
        $MY_RIGHTS = [];
        if ($this->hasRootPrivileges === false) {
            /** @var $ContainersTable ContainersTable */
            //$ContainersTable = TableRegistry::getTableLocator()->get('Containers');
            //$MY_RIGHTS = $ContainersTable->resolveChildrenOfContainerIds($this->MY_RIGHTS);
            // ITC-2863 $this->MY_RIGHTS is already resolved and contains all containerIds a user has access to
            $MY_RIGHTS = $this->MY_RIGHTS;
        }

        $hostsConfig = $queryData['hostFilters'];
        $servicesConfig = $queryData['serviceFilters'];
        $hoststatus = [];
        $servicestatus = [];
        if ($this->DbBackend->isNdoUtils()) {
            throw new MissingDbBackendException('MissingDbBackendException');
        }

        if ($this->DbBackend->isCrateDb()) {
            throw new MissingDbBackendException('MissingDbBackendException');
        }
        if ($this->DbBackend->isStatusengine3()) {
            /** @var HostsTable $HostsTable */
            $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
            $hoststatus = $HostsTable->getHostsForDesktopWithStatusByConditionsStatusengine3($MY_RIGHTS, $hostsConfig);
            /** @var ServicesTable $ServicesTable */
            $ServicesTable = TableRegistry::getTableLocator()->get('Services');
            $servicestatus = $ServicesTable->getServicesForDesktopWithStatusByConditionsStatusengine3($MY_RIGHTS, $servicesConfig);

        }
        $hoststatusSummary = $HostsTable->getHostStateSummary($hoststatus, false);
        $servicestatusSummary = $ServicesTable->getServiceStateSummary($servicestatus, false);

        $this->set('hoststatusSummary', $hoststatusSummary);
        $this->set('servicestatusSummary', $servicestatusSummary);
        $this->viewBuilder()->setOption('serialize', ['hoststatusSummary', 'servicestatusSummary']);
    }
}
