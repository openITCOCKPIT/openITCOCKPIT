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
?>

<div class="btn-group font-sm display-flex">
    <div class="btn-group input-group-btn input-group-prepend" ng-init="type='host';name='<?= __('Hosts') ?>'">
        <button type="button"
                class="btn btn-default dropdown-toggle no-border"
                data-toggle="dropdown" aria-expanded="false">
            <span ng-hide="isSearching">{{name}}</span>
            <i class="fa fa-spinner fa-spin" ng-show="isSearching"></i>
            <span class="caret"></span>
        </button>
        <ul class="dropdown-menu">
            <li ng-class="{active: type === 'host'}" class="dropdown-item"
                ng-click="setSearchType('host', '<?= __('Hosts') ?>')">
                <a href="javascript:void(0)">
                    <i class="fa fa-check" ng-show="type === 'host'"></i>
                    <?= __('Hosts') ?>
                </a>
            </li>
            <li ng-class="{active: type === 'service'}" class="dropdown-item"
                ng-click="setSearchType('service', '<?= __('Services') ?>')">
                <a href="javascript:void(0)">
                    <i class="fa fa-check" ng-show="type === 'service'"></i>
                    <?= __('Services') ?>
                </a>
            </li>
            <li ng-class="{active: type === 'uuid'}" class="dropdown-item"
                ng-click="setSearchType('uuid', '<?= __('UUID') ?>')">
                <a href="javascript:void(0)">
                    <i class="fa fa-check" ng-show="type === 'uuid'"></i>
                    <?= __('UUID') ?>
                </a>
            </li>
            <li ng-class="{active: type === 'address'}" class="dropdown-item"
                ng-click="setSearchType('address', '<?= __('IP address') ?>')">
                <a href="javascript:void(0)">
                    <i class="fa fa-check" ng-show="type === 'address'"></i>
                    <?= __('IP address') ?>
                </a>
            </li>
            <li ng-class="{active: type === 'tags_host'}" class="dropdown-item"
                ng-click="setSearchType('tags_host', '<?= __('Tags (Host)') ?>')">
                <a href="javascript:void(0)">
                    <i class="fa fa-check" ng-show="type === 'tags_host'"></i>
                    <?= __('Tags (Host)') ?>
                </a>
            </li>
            <li ng-class="{active: type === 'tags_service'}" class="dropdown-item"
                ng-click="setSearchType('tags_service', '<?= __('Tags (Service)') ?>')">
                <a href="javascript:void(0)">
                    <i class="fa fa-check" ng-show="type === 'tags_service'"></i>
                    <?= __('Tags (Service)') ?>
                </a>
            </li>
        </ul>
    </div>

    <input ng-show="type !== 'tags_host' && type!== 'tags_service'"
           type="text"
           class="form-control top-search-border"
           placeholder="<?= __('Type to search') ?>"
           ng-model="searchStr"
           ng-keydown="isReturnKey($event)"
           ng-disabled="isSearching"
           style="border-top-left-radius: 0; border-bottom-left-radius: 0;">
    <input ng-hide="type !== 'tags_host' && type!== 'tags_service'"
           type="text"
           class="form-control top-search-border"
           placeholder="<?= __('Type to search. Please use comma separated list to search multiple values') ?>"
           ng-model="searchStr"
           ng-keydown="isReturnKey($event)"
           ng-disabled="isSearching"
           style="border-top-left-radius: 0; border-bottom-left-radius: 0;">
</div>
