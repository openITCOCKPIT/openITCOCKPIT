<?php
// Copyright (C) <2015>  <it-novum GmbH>
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

use App\Model\Table\ChangelogsTable;
use App\Model\Table\SystemsettingsTable;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;
use itnovum\openITCOCKPIT\Core\ValueObjects\User;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\ChangelogsFilter;

class ChangelogsController extends AppController {

    public function index() {
        if (!$this->isApiRequest()) {
            //Only ship HTML Template
            return;
        }

        /** @var SystemsettingsTable $SystemsettingsTable */
        $SystemsettingsTable = TableRegistry::getTableLocator()->get('Systemsettings');
        /** @var ChangelogsTable $ChangelogsTable */
        $ChangelogsTable = TableRegistry::getTableLocator()->get('Changelogs');

        $result = $SystemsettingsTable->getSystemsettingByKey('FRONTEND.HIDDEN_USER_IN_CHANGELOG');
        $includeUser = $result->get('value') === '0';
        $User = new User($this->getUser());
        $UserTime = $User->getUserTime();

        $ChangelogsFilter = new ChangelogsFilter($this->request, $UserTime);
        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $ChangelogsFilter->getPage());
        $MY_RIGHTS = $this->MY_RIGHTS;
        if ($this->hasRootPrivileges === true) {
            $MY_RIGHTS = [];
        }


        $showInherit = (bool)($this->request->getQueryParams()['filter']['ShowServices'] ?? false);
        $all_changes = $ChangelogsTable->getChangelogIndex($ChangelogsFilter, $PaginateOMat, $MY_RIGHTS, $includeUser, CORE, false, $showInherit);

        $todayMidnight = strtotime('today');

        foreach ($all_changes as $index => $change) {
            $modelPlural = Inflector::pluralize($change['model']);
            $controllerName = ucfirst($modelPlural);
            $ngState = '';
            $routerLink = [];

            if ($this->hasPermission('edit', $controllerName) && $change['action'] !== 'delete') {

                if ($controllerName === 'Containers') {
                    $ngState = sprintf(
                        '%sIndex',
                        $controllerName
                    );
                    $controllerAction = 'index';
                } else {
                    $ngState = sprintf(
                        '%sEdit',
                        $controllerName
                    );
                    $controllerAction = 'edit';
                }
                $routerLink = ['/', lcfirst($modelPlural), $controllerAction, $change['object_id']];
            }
            if ($this->hasRootPrivileges === false) {
                if ($controllerName === 'Tenants') {
                    $containerToCheck = Hash::extract($change['containers'], '{n}[id>' . CT_GLOBAL . '].id');
                    if (empty(array_intersect($MY_RIGHTS, $containerToCheck))) {
                        unset($all_changes[$index]);
                        continue;
                    }
                }
            }


            $changeTimestamp = $change['created']->getTimestamp();
            $all_changes[$index]['time'] = $UserTime->format($changeTimestamp);
            $isToday = ($changeTimestamp > $todayMidnight);
            if ($isToday) {
                $all_changes[$index]['time'] = $UserTime->customFormat('H:i:s', $changeTimestamp);
            }

            $dataUnserialized = unserialize($change['data']);
            $dataUnserialized = $ChangelogsTable->replaceFieldValues($dataUnserialized);
            $dataUnserialized = $ChangelogsTable->formatDataForView($dataUnserialized, $change['action']);
            $dataUnserialized = $ChangelogsTable->replaceTableNames($dataUnserialized);

            $all_changes[$index]['isToday'] = $isToday;
            $all_changes[$index]['timeAgoInWords'] = $UserTime->timeAgoInWords($changeTimestamp, [
                'end'      => 0,
                'accuracy' => [
                    'year'   => 'month',  // The format if years > 0   (default "day")
                    'month'  => 'month',  // The format if months > 0  (default "day")
                    'week'   => 'day',    // The format if weeks > 0   (default "day")
                    'day'    => 'hour',   // The format if weeks > 0   (default "hour")
                    'hour'   => 'minute', // The format if hours > 0   (default "minute")
                    'minute' => 'minute', // The format if minutes > 0 (default "minute")
                    'second' => 'second', // The format if seconds > 0 (default "second")
                ]
            ]);
            $all_changes[$index]['recordExists'] = $ChangelogsTable->recordExists($change['model'], $change['object_id']);
            $all_changes[$index]['data_unserialized'] = $dataUnserialized;
            $all_changes[$index]['ngState'] = $ngState;
            $all_changes[$index]['routerLink'] = $routerLink;
            $all_changes[$index]['color'] = $ChangelogsTable->getColorByAction($change['action']);
            $all_changes[$index]['icon'] = $ChangelogsTable->getIconByAction($change['action']);
            $all_changes[$index]['faIcon'] = $ChangelogsTable->getFaIconByAction($change['action']);
            $all_changes[$index]['includeUser'] = $includeUser;
        }
        $this->set('all_changes', $all_changes);
        $this->viewBuilder()->setOption('serialize', ['all_changes']);
    }

    //Only for ACLs
    public function entity(): void {
    }
}
