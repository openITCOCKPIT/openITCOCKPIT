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
        <a ui-sref="AdministratorsQuerylog">
            <i class="fa fa-database"></i> <?php echo __('Databases query log'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <i class="fa fa-list"></i> <?php echo __('Overview'); ?>
    </li>
</ol>

<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?php echo __('Databases'); ?>
                    <span class="fw-300"><i><?php echo __('query log'); ?></i></span>
                </h2>
                <div class="panel-toolbar">
                    <div class="text-success" ng-show="connected">
                        <i class="fa fa-check"></i>
                        <?php echo __('Successfully connected'); ?>
                    </div>

                    <div class="text-info margin-right-5">
                        <?php echo __('Used query log slots'); ?>
                        ({{queryLog.length}}/15)
                    </div>

                    <button class="btn btn-xs btn-default mr-1 shadow-0" ng-click="connectToQueryLogServer()"
                            ng-if="!connected && connectionError && !manualReconnect">
                        <i class="fas fa-sync"></i> <?php echo __('Try manual reconnect'); ?>
                    </button>
                    <button class="btn btn-xs btn-default mr-1 shadow-0" ng-click="truncate()">
                        <i class="fas fa-trash"></i> <?php echo __('Truncate log'); ?>
                    </button>
                </div>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <div class="frame-wrap">
                        <div class="alert alert-info alert-block">
                            <a href="javascript:void(0);" data-dismiss="alert" class="close">×</a><h5
                                class="alert-heading"><i
                                    class="fa fa-info-circle"></i> <?php echo __('Enable query logging'); ?></h5>
                            <?php echo __(
                                'It is very important to set {0} to {1} in the file',
                                ' <code>log</code>',
                                ' <code>true</code>'
                            ); ?>
                            <code>config/datasource.php</code>
                        </div>

                        <div class="alert alert-danger alert-block" ng-show="connectionError">
                            <a href="javascript:void(0);" data-dismiss="alert" class="close">×</a><h5
                                class="alert-heading"><i
                                    class="fa fa-warning"></i> <?php echo __('Connection error'); ?></h5>
                            <?php echo __('Could not connect to Query Log WebSocket Server. Did you execute'); ?>
                            <code>oitc query_log --websocket-server --pretty --hide-acl</code>?
                        </div>

                        <div ng-repeat="queries in queryLog" class="margin-bottom-10">
                            <div class="row">
                                <div class="col-xs-12 text-info">
                                    <h2>
                                        Database "{{queries.datasource}}"
                                        {{queries.count}} queries took {{queries.time}} ms
                                    </h2>
                                </div>
                            </div>

                            <div class="row bold">
                                <div class="col-xs-12 col-md-9">
                                    Query
                                </div>
                                <div class="col-xs-12 col-md-1">
                                    Affected
                                </div>
                                <div class="col-xs-12 col-md-1">
                                    num. Rows
                                </div>
                                <div class="col-xs-12 col-md-1">
                                    Took ms
                                </div>
                            </div>
                            <div class="row" ng-repeat="query in queries.queries">
                                <div ng-bind-html="query.query | trustAsHtml" class="col-xs-12 col-md-9"></div>
                                <div class="col-xs-12 col-md-1">
                                    {{query.affected}}
                                </div>
                                <div class="col-xs-12 col-md-1">
                                    {{query.numRows}}
                                </div>
                                <div class="col-xs-12 col-md-1">
                                    {{query.took}}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
