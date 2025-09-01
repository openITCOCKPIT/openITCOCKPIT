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
<ol class="breadcrumb page-breadcrumb">
    <li class="breadcrumb-item">
        <a ui-sref="DashboardsIndex">
            <i class="fa fa-home"></i> <?php echo __('Home'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <a ui-sref="ServicesIndex">
            <i class="fas fa-globe-americas"></i> <?php echo __('Status Map'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <i class="fa fa-eye"></i> <?php echo __('Overview'); ?>
    </li>
</ol>


<div class="row">
    <div class="col-lg-12 margin-bottom-10">
        <select
            id="satellites"
            data-placeholder="<?php echo __('Please select...'); ?>"
            class="form-control"
            chosen="{}"
            ng-model="filter.Hosts.satellite_id"
            ng-model-options="{debounce: 500}">
            <?php
            if (isset($satellites) && !empty($satellites)):
                foreach ($satellites as $satelliteId => $satelliteName):
                    printf('<option value="%s">%s</option>', h($satelliteId), h($satelliteName));
                endforeach;
            endif;
            ?>
        </select>

    </div>
</div>


<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?php echo __('Status map'); ?>
                    <span class="fw-300"><i><?php echo __('overview'); ?></i></span>
                </h2>
                <div class="panel-toolbar">
                    <div class="form-group no-margin padding-right-10">
                        <div class="input-group input-group-sm">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fa fa-filter"></i></span>
                            </div>
                            <input type="text" class="form-control form-control-sm"
                                   placeholder="<?php echo __('Filter by IP address'); ?>"
                                   ng-model="filter.Hosts.address"
                                   ng-model-options="{debounce: 500}">
                        </div>
                    </div>
                    <div class="form-group no-margin padding-right-10">
                        <div class="input-group input-group-sm">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fa fa-desktop"></i></span>
                            </div>
                            <input type="text" class="form-control form-control-sm"
                                   placeholder="<?php echo __('Filter by host name'); ?>"
                                   ng-model="filter.Hosts.name"
                                   ng-model-options="{debounce: 500}">
                        </div>
                    </div>

                    <div class="custom-control custom-checkbox padding-right-10">
                        <input type="checkbox"
                               id="showAll"
                               class="custom-control-input"
                               name="checkbox"
                               checked="checked"
                               ng-model="filter.showAll"
                               ng-model-options="{debounce: 500}"
                               ng-true-value="false"
                               ng-false-value="true">
                        <label
                            class="custom-control-label no-margin"
                            for="showAll"> <?php echo __('Consider parent child relations'); ?></label>
                    </div>

                    <button class="btn btn-xs btn-default mr-1 shadow-0" ng-click="clearFilter();">
                        <i class="fas fa-undo"></i> <?php echo __('Reset'); ?>
                    </button>
                </div>
            </div>
            <div class="panel-container show">
                <div class="panel-content">

                    <!-- Loader -->
                    <div class="row padding-top-80" style="display:none;" id="visProgressbarLoader">
                        <div class="col-12">
                            <div class="visloader-progressbar-center">
                                <div class="text-center">
                                    {{nodesCount}} <?php echo __(' nodes'); ?>
                                    <span class="statusmap-progress-dots"></span>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar progress-bar-striped bg-secondary progress-bar-animated"
                                         role="progressbar"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- End Loader -->

                    <div class="frame-wrap">
                        <span class="no-padding">
                            <i class="fa fa-check-circle no-padding up"></i>
                            <em class="padding-right-10">
                                <?php
                                echo __('Up');
                                ?>
                            </em>
                            <i class="fa fa-exclamation-circle no-padding down"></i>
                            <em class="padding-right-10">
                                <?php
                                echo __('Down');
                                ?>
                            </em>
                            <i class="fa fa-question-circle no-padding unreachable"></i>
                            <em class="padding-right-10">
                                <?php
                                echo __('Unreachable');
                                ?>
                            </em>
                            <i class="fa fa-eye-slash no-padding text-primary"></i>
                            <em class="padding-right-10">
                                <?php
                                echo __('Not monitored');
                                ?>
                            </em>
                            <i class="fa fa-plug no-padding text-primary"></i>
                            <em class="padding-right-10">
                                <?php
                                echo __('Disabled');
                                ?>
                            </em>
                            |
                             <i class="fa fa-power-off no-padding txt-color-blueDark"></i>
                            <em class="padding-right-10">
                                <?php
                                echo __('In downtime');
                                ?>
                            </em>
                             <i class="fa fa-user no-padding txt-color-blueDark"></i>
                            <em class="padding-right-10">
                                <?php
                                echo __('Acknowledged');
                                ?>
                            </em>
                            <i class="fa fa-user-md no-padding txt-color-blueDark"></i>
                            <em class="padding-right-10">
                                <?php
                                echo __('Acknowledged and in downtime');
                                ?>
                            </em>
                        </span>
                        <div class="margin-top-10" ng-if="isEmpty">
                            <div class="text-center text-danger italic">
                                <?php echo __('No entries match the selection'); ?>
                            </div>
                        </div>
                        <div id="statusmap"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<statusmaps-host-and-services-summary-status-directive
    host="statusMapsSummaryHost" has-browser-right="hasBrowserRight"
    ts="statusMapsSummaryTS"></statusmaps-host-and-services-summary-status-directive>
