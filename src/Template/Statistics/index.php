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
        <a ui-sref="StatisticsIndex">
            <i class="fa fa-line-chart"></i> <?php echo __('Anonymous statistics'); ?>
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
                    <?php echo __('Anonymous statistics'); ?>
                    <span class="fw-300"><i><?php echo __('We ask for your help'); ?></i></span>
                </h2>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <div class="frame-wrap">
                        <div class="alert alert-info" ng-show="settings.Systemsetting.value === 0">
                            <i class="fa-fw fa fa-times"></i>
                            <?php echo __('Sending of anonymous statistics is currently disabled.'); ?>
                            <button class="btn btn-xs btn-success float-right" ng-click="save(1)">
                                <?php echo __('Enable'); ?>
                            </button>
                        </div>
                        <div class="alert alert-info" ng-show="settings.Systemsetting.value === 2">
                            <i class="fa-fw fa fa-times"></i>
                            <?php echo __('Sending of anonymous statistics is currently disabled. Waiting for your approval.'); ?>
                            <button class="btn btn-xs btn-success float-right" ng-click="save(1)">
                                <?php echo __('Enable'); ?>
                            </button>
                        </div>

                        <div class="alert alert-success" ng-show="settings.Systemsetting.value === 1">
                            <i class="fa-fw fa fa-check"></i>
                            <?php echo __('Sending of anonymous statistics is currently enabled. Many thanks for your support!'); ?>
                            <button class="btn btn-xs btn-danger float-right" ng-click="save(0)">
                                <?php echo __('Disable'); ?>
                            </button>
                        </div>


                        <div class="col-xs-12 margin-top-10">
                            <h4><?php echo __('What data do we collect?'); ?></h4>
                        </div>
                        <div class="col-xs-12">
                            <p><?php echo __('We are not interested in who you are, in which company you work or any other personal data.'); ?></p>
                            <p>
                                <?php echo __('We just ask you, to provide us system metrics like:'); ?>
                            </p>
                            <ul>
                                <li><?php echo __('CPU load, number of CPU cores and CPU model'); ?></li>
                                <li><?php echo __('Memory and swap usage'); ?></li>
                                <li><?php echo __('Version of used monitoring engine'); ?></li>
                                <li><?php echo __('Version of PHP'); ?></li>
                                <li><?php echo __('Version of openITCOCKPIT and installed openITCOCKPIT modules'); ?></li>
                                <li><?php echo __('Number of monitored hosts and services'); ?></li>
                                <li><?php echo __('Used operating system'); ?></li>
                                <li><?php echo __('MySQL usage statistics like number of select, insert and delete statements'); ?></li>
                            </ul>
                        </div>

                        <div class="col-xs-12 margin-top-10">
                            <h4><?php echo __('Do you track users on the interface?'); ?></h4>
                        </div>

                        <div class="col-xs-12">
                            <?php echo __('No. We are not interested in any user or user behavior data.'); ?>
                        </div>


                        <div class="col-xs-12 margin-top-10">
                            <h4><?php echo __('What records would you like to send exactly?'); ?></h4>
                        </div>
                        <div class="col-xs-12">
                            <p><?php echo __('To be as transparent as possible, we show you all records that will be send to us. (This data was generated on your system.)'); ?></p>
                        </div>
                        <div class="col-xs-12">
                            <div class="form-group">
                            <textarea class="form-control" rows="15"
                                      readonly><?php echo h($statisticsAsJson); ?></textarea>
                            </div>
                        </div>

                        <div class="col-xs-12 margin-top-10">
                            <h5><?php echo __('What is the'); ?> <code>system_id</code>?</h5>
                        </div>
                        <div class="col-xs-12">
                            <?php echo __(
                                'The system_id is a unique number that was generated on your system.
                                The system_id is included in every request, to enable our database to locate your record and update it.
                        '); ?>
                            <br/>
                            <strong>
                                <?php echo __('The system_id will not be linked to any other records.'); ?>
                            </strong>
                            <br/>

                            <?php echo __('If you want to change your system_id, you can delete the file'); ?>
                            <?php if (IS_CONTAINER): ?>
                                <code>/opt/openitc/var/system-id</code>
                            <?php else: ?>
                                <code>/opt/openitc/etc/system-id</code>.
                            <?php endif; ?>
                            <?php echo __('openITCOCKPIT will generate a new system_id, if required.'); ?>

                        </div>


                        <div class="col-xs-12 margin-top-10">
                            <h4><?php echo __('Why do you ask for this data?'); ?></h4>
                        </div>
                        <div class="col-xs-12">
                            <p><?php echo __('
                            This information influences decisions such as which openITCOCKPIT modules should
                            get new features or which operating system is used by most of the users.

                            In addition the data will help use to optimize the database schema and improve scalability of
                            openITCOCKPIT.
                        '); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
