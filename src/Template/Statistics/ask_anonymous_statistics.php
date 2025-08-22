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


<div id="angulWeAskForYourHelpModal" class="modal" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="far fa-question-circle"></i>
                    <?php echo __('We ask for your help'); ?>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="fa fa-times"></i></span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col">
                        <?php echo __('
                        We are always interested to improve openITCOCKPIT with every new version.
                        You can help us, by submitting anonymous statistical information to us.
                        '); ?>
                    </div>
                </div>
                <br/>

                <div class="row">
                    <div class="col-12 margin-top-10">
                        <h4><?php echo __('What data do we collect?'); ?></h4>
                    </div>
                    <div class="col-12">
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
                        <p>
                            <a ui-sref="StatisticsIndex" data-dismiss="modal">
                                <?php echo __('Show me all metrics you like to collect.'); ?>
                            </a>
                        </p>
                    </div>

                    <div class="col-12 margin-top-10">
                        <h4><?php echo __('Do you track users on the interface?'); ?></h4>
                    </div>

                    <div class="col-12">
                        <?php echo __('No. We are not interested in any user or user behavior data.'); ?>
                    </div>

                </div>

                <div class="row">
                    <div class="col-12 margin-top-10">
                        <h4><?php echo __('Why do you ask for this data?'); ?></h4>
                    </div>


                    <div class="col-12">
                        <p><?php echo __('
                            This information influences decisions such as which openITCOCKPIT modules should
                            get new features or which operating system is used by most of the users.

                            In addition the data will help use to optimize the database schema and improve scalability of
                            openITCOCKPIT.
                        '); ?></p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary mr-auto" data-dismiss="modal" ng-click="save(2)">
                    <?php echo __('Ask me again'); ?>
                </button>
                <a ui-sref="StatisticsIndex" class="btn btn-default" data-dismiss="modal">
                    <?php echo __('I want more information'); ?>
                </a>
                <button type="button" class="btn btn-success" ng-click="save(1)">
                    <?php echo __('Yes, I want to help'); ?>
                </button>
                <button type="button" class="btn btn-danger" data-dismiss="modal" ng-click="save(0)">
                    <?php echo __('No, thanks'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<div id="manyThanksForYourSupport">
    <i class="fas fa-smile" style="font-size: 100px;"></i>
    <div style="width:100%;" class="padding-top-20">
        <?php echo __('Many thanks for your support!'); ?>
    </div>
</div>
