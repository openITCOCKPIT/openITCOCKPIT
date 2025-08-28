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

?>
<div class="map-summary-state-popover col-xs-12 no-padding animated slideInRight slightBorder"
     ng-if="host.hostId"
     ng-click="hideTooltip($event)"
     ng-mouseover="stopInterval()"
     ng-mouseleave="startInterval()">
    <section>
        <div class="bg-panel-default padding-top-10 padding-left-10 padding-bottom-10">
            <header>
                <h5 class="text-primary">
                    <i class="fa fa-desktop text-primary"></i>
                    <a ng-show="hasBrowserRight" ui-sref="HostsBrowser({id: host.hostId})">{{host.title}}</a>
                    <div class="display-inline" ng-hide="hasBrowserRight">{{host.title}}</div>
                </h5>
                <div class="col-md-12 no-padding">
                    <div class="tooltipProgressBar" style="width: {{percentValue}}%;"></div>
                </div>
            </header>
            <table class="table table-sm">
                <tr>
                    <td></td>
                    <th class="text-center font-xs">
                        <div class="badge badge-table badge-success"><?php echo __('Ok'); ?></div>
                    </th>
                    <th class="text-center font-xs">
                        <div class="badge badge-table badge-warning"><?php echo __('Warning'); ?></div>
                    </th>
                    <th class="text-center font-xs">
                        <div class="badge badge-table badge-danger"><?php echo __('Critical'); ?></div>
                    </th>
                    <th class="text-center font-xs">
                        <div class="badge badge-table badge-secondary"><?php echo __('Unknown'); ?></div>
                    </th>
                </tr>
                <tr class="font-xs" ng-repeat="(key, state) in serviceStateSummary" ng-if="key !== 'total'">
                    <th>
                        {{key | underscoreless | capitalizeFirstLetter}}
                    </th>
                    <td class="text-center" ng-repeat="counter in [0,1,2,3]">
                        <div ng-show="state[counter] > 0">
                            <?php
                            if ($this->Acl->hasPermission('index', 'services')): ?>
                                <a class="cursor-pointer" ng-click="goToState(key, counter, host.hostId)">
                                    {{state[counter]}}&nbsp;
                                    ({{state[counter]/serviceStateSummary.total*100|number : 1}}%)
                                </a>
                            <?php
                            else: ?>
                                {{state[counter]}}&nbsp;
                                ({{state[counter]/serviceStateSummary.total*100|number : 1}}%)
                            <?php endif; ?>
                        </div>
                        <div ng-show="!state[counter] || state[counter] <= 0">
                            ---
                        </div>
                    </td>
                </tr>
                <tr class="font-xs">
                    <th colspan="5" class="text-right th-border-top">
                        <?php echo __('TOTAL:'); ?>&nbsp;{{serviceStateSummary.total}}
                    </th>
                </tr>
            </table>
        </div>
    </section>
</div>
