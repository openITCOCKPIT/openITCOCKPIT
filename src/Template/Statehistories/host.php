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

<host-browser-menu
    ng-if="hostBrowserMenuConfig"
    config="hostBrowserMenuConfig"
    last-load-date="0"
    root-copy-to-clipboard="rootCopyToClipboard"></host-browser-menu>

<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?php echo __('Host'); ?>
                    <span class="fw-300"><i><?php echo __('state history'); ?></i></span>
                </h2>
                <div class="panel-toolbar">
                    <button class="btn btn-xs btn-default mr-1 shadow-0" ng-click="load()">
                        <i class="fas fa-sync"></i> <?php echo __('Refresh'); ?>
                    </button>
                    <button class="btn btn-xs btn-primary shadow-0" ng-click="triggerFilter()">
                        <i class="fas fa-filter"></i> <?php echo __('Filter'); ?>
                    </button>
                </div>
            </div>
            <div class="panel-container show">
                <div class="panel-content">

                    <!-- Start Filter -->
                    <div class="list-filter card margin-bottom-10" ng-show="showFilter">
                        <div class="card-header">
                            <i class="fa fa-filter"></i> <?php echo __('Filter'); ?>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-xs-12 col-md-6 margin-bottom-10">
                                    <div class="form-group">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span
                                                    class="input-group-text filter-text"><?php echo __('From'); ?></span>
                                            </div>
                                            <input type="datetime-local" class="form-control form-control-sm"
                                                   style="padding:0.5rem 0.875rem;"
                                                   placeholder="<?php echo __('From date'); ?>"
                                                   ng-model="from_time"
                                                   ng-model-options="{debounce: 500, timeSecondsFormat:'ss', timeStripZeroSeconds: true}">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xs-12 col-md-6 margin-bottom-10">
                                    <div class="form-group">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fa fa-filter"></i></span>
                                            </div>
                                            <input type="text" class="form-control form-control-sm"
                                                   placeholder="<?php echo __('Filter by output'); ?>"
                                                   ng-model="filter.StatehistoryHosts.output"
                                                   ng-model-options="{debounce: 500}">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xs-12 col-md-6 margin-bottom-10">
                                    <div class="form-group">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span
                                                    class="input-group-text filter-text"><?php echo __('To'); ?></span>
                                            </div>
                                            <input type="datetime-local" class="form-control form-control-sm"
                                                   style="padding:0.5rem 0.875rem;"
                                                   placeholder="<?php echo __('To date'); ?>"
                                                   ng-model="to_time"
                                                   ng-model-options="{debounce: 500, timeSecondsFormat:'ss', timeStripZeroSeconds: true}">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-xs-12 col-lg-3">
                                    <fieldset>
                                        <h5><?php echo __('States'); ?></h5>
                                        <div class="form-group">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox"
                                                       id="statusFilterUp"
                                                       class="custom-control-input"
                                                       name="checkbox"
                                                       checked="checked"
                                                       ng-model="filter.StatehistoryHosts.state.recovery"
                                                       ng-model-options="{debounce: 500}">
                                                <label class="custom-control-label custom-control-label-up"
                                                       for="statusFilterUp"><?php echo __('Up'); ?></label>
                                            </div>

                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox"
                                                       id="statusFilterDown"
                                                       class="custom-control-input"
                                                       name="checkbox"
                                                       checked="checked"
                                                       ng-model="filter.StatehistoryHosts.state.down"
                                                       ng-model-options="{debounce: 500}">
                                                <label class="custom-control-label custom-control-label-down"
                                                       for="statusFilterDown"><?php echo __('Down'); ?></label>
                                            </div>

                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox"
                                                       id="statusFilterUnreachable"
                                                       class="custom-control-input"
                                                       name="checkbox"
                                                       checked="checked"
                                                       ng-model="filter.StatehistoryHosts.state.unreachable"
                                                       ng-model-options="{debounce: 500}">
                                                <label class="custom-control-label custom-control-label-unreachable"
                                                       for="statusFilterUnreachable"><?php echo __('Unreachable'); ?></label>
                                            </div>

                                        </div>
                                    </fieldset>
                                </div>
                                <div class="col-xs-12 col-lg-3">
                                    <fieldset>
                                        <h5><?php echo __('State Types'); ?></h5>
                                        <div class="form-group">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox"
                                                       id="statusFilterSoft"
                                                       class="custom-control-input"
                                                       name="checkbox"
                                                       checked="checked"
                                                       ng-model="filter.StatehistoryHosts.state_types.soft"
                                                       ng-model-options="{debounce: 500}">
                                                <label class="custom-control-label"
                                                       for="statusFilterSoft"><?php echo __('Soft'); ?></label>
                                            </div>

                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox"
                                                       id="statusFilterHard"
                                                       class="custom-control-input"
                                                       name="checkbox"
                                                       checked="checked"
                                                       ng-model="filter.StatehistoryHosts.state_types.hard"
                                                       ng-model-options="{debounce: 500}">
                                                <label class="custom-control-label"
                                                       for="statusFilterHard"><?php echo __('Hard'); ?></label>
                                            </div>
                                        </div>
                                    </fieldset>
                                </div>
                            </div>
                            <div class="float-right">
                                <button type="button" ng-click="resetFilter()"
                                        class="btn btn-xs btn-danger">
                                    <?php echo __('Reset Filter'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                    <!-- Filter End -->

                    <div class="frame-wrap">
                        <table class="table table-striped m-0 table-bordered table-hover table-sm">
                            <thead>
                            <tr>
                                <th class="no-sort" ng-click="orderBy('StatehistoryHosts.state')">
                                    <i class="fa" ng-class="getSortClass('StatehistoryHosts.state')"></i>
                                    <?php echo __('State'); ?>
                                </th>
                                <th class="no-sort" ng-click="orderBy('StatehistoryHosts.state_time')">
                                    <i class="fa" ng-class="getSortClass('StatehistoryHosts.state_time')"></i>
                                    <?php echo __('Date'); ?>
                                </th>
                                <th class="no-sort" ng-click="orderBy('StatehistoryHosts.current_check_attempt')">
                                    <i class="fa" ng-class="getSortClass('StatehistoryHosts.current_check_attempt')"></i>
                                    <?php echo __('Check attempt'); ?>
                                </th>
                                <th class="no-sort" ng-click="orderBy('StatehistoryHosts.state_type')">
                                    <i class="fa" ng-class="getSortClass('StatehistoryHosts.state_type')"></i>
                                    <?php echo __('State type'); ?>
                                </th>
                                <th class="no-sort" ng-click="orderBy('StatehistoryHosts.output')">
                                    <i class="fa" ng-class="getSortClass('StatehistoryHosts.output')"></i>
                                    <?php echo __('Host output'); ?>
                                </th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr ng-repeat="StatehistoryHost in statehistories">

                                <td class="text-center">
                                    <hoststatusicon state="StatehistoryHost.StatehistoryHost.state" is-hardstate="StatehistoryHost.StatehistoryHost.is_hardstate"></hoststatusicon>
                                </td>
                                <td>
                                    {{ StatehistoryHost.StatehistoryHost.state_time }}
                                </td>
                                <td class="text-center">
                                    {{ StatehistoryHost.StatehistoryHost.current_check_attempt }}/{{
                                    StatehistoryHost.StatehistoryHost.max_check_attempts }}
                                </td>
                                <td class="text-center">
                                    <span class="badge text-uppercase"
                                          ng-class="{'badge-success': StatehistoryHost.StatehistoryHost.state == 0,
                                          'badge-danger': StatehistoryHost.StatehistoryHost.state == 1,
                                          'badge-secondary': StatehistoryHost.StatehistoryHost.state == 2}"
                                          ng-show="StatehistoryHost.StatehistoryHost.is_hardstate">
                                        <?= __('Hard'); ?>
                                    </span>
                                    <span class="badge text-uppercase opacity-50"
                                          ng-class="{'badge-success': StatehistoryHost.StatehistoryHost.state == 0,
                                          'badge-danger': StatehistoryHost.StatehistoryHost.state == 1,
                                          'badge-secondary': StatehistoryHost.StatehistoryHost.state == 2}"
                                          ng-show="!StatehistoryHost.StatehistoryHost.is_hardstate">
                                        <?= __('Soft'); ?>
                                    </span>
                                </td>
                                <td>
                                    <div ng-bind-html="StatehistoryHost.StatehistoryHost.outputHtml | trustAsHtml"></div>
                                </td>
                            </tr>

                            </tbody>
                        </table>
                        <div class="margin-top-10" ng-show="statehistories.length == 0">
                            <div class="text-center text-danger italic">
                                <?php echo __('No entries match the selection'); ?>
                            </div>
                        </div>
                        <scroll scroll="scroll" click-action="changepage" ng-if="scroll"></scroll>
                        <paginator paging="paging" click-action="changepage" ng-if="paging"></paginator>
                        <?php echo $this->element('paginator_or_scroll'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
