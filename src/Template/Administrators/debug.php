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

use itnovum\openITCOCKPIT\Core\RepositoryChecker;
use itnovum\openITCOCKPIT\Core\System\Health\LsbRelease;

$Logo = new \itnovum\openITCOCKPIT\Core\Views\Logo();

/** @var RepositoryChecker $RepositoryChecker */
/** @var LsbRelease $LsbRelease */
?>
<div class="row">
    <ol class="breadcrumb page-breadcrumb">
        <li class="breadcrumb-item">
            <a ui-sref="DashboardsIndex">
                <i class="fa fa-home"></i> <?php echo __('Home'); ?>
            </a>
        </li>
        <li class="breadcrumb-item">
            <a ui-sref="AdministratorsDebug">
                <i class="fa fa-bug"></i> {{interfaceInformation.systemname}}
            </a>
        </li>
        <li class="breadcrumb-item">
            <i class="fa fa-list"></i> <?php echo __('Debugging information'); ?>
        </li>
    </ol>

    <div class="ml-auto mr-3" ng-show="interfaceInformation.oitc_is_debugging_mode">
        <div class="float-right">
            <div class="btn-group" role="group">
                <button class="btn btn-success btn-xl" ng-show="!hasXdebugCookie" ng-click="setXdebugCookie()">
                    <i class="fa-solid fa-bug"></i>
                    <?php echo __('Set XDEBUG_TRIGGER cookie'); ?>
                </button>
                <button class="btn btn-danger btn-xl" ng-show="hasXdebugCookie" ng-click="removeXdebugCookie()">
                    <i class="fa-solid fa-bug-slash"></i>
                    <?php echo __('Remove XDEBUG_TRIGGER cookie'); ?>
                </button>
                <a ui-sref="AdministratorsQuerylog" class="btn btn-default btn-xl">
                    <i class="fa fa-database"></i>
                    <?php echo __('Show SQL query log'); ?>
                </a>
            </div>
        </div>
    </div>
</div>

<div id="error_msg" ng-hide="processInformation.gearmanReachable">
    <div class="alert alert-danger alert-block">
        <h5 class="alert-heading"><i class="fa fa-warning"></i>
            <?php echo __('Critical error!'); ?>
        </h5>
        <?php echo __('Could not connect to Gearman Job Server! No background tasks will get executed!'); ?>
    </div>
</div>

<?php if (IS_CONTAINER === false): ?>
    <?php echo $this->element('repository_checker'); ?>

    <?php if ($LsbRelease->getCodename() === 'bionic'): ?>
        <div class="alert alert-danger alert-block">
            <a class="close" data-dismiss="alert" href="javascript:void(0);">×</a>
            <h4 class="alert-heading">
                <i class="fa fa-warning"></i>
                <?php echo __('Ubuntu Bionic 18.04 is end of life soon!'); ?>
            </h4>
            <?php echo __('Official end of life of Ubuntu Bionic scheduled for April 2023.'); ?>
            <?php echo __('Therefore openITCOCKPIT 4.5.5 will be one of the last releases for Ubuntu Bionic. Please update to Ubuntu Focal to receive further updates.'); ?>
            <br/>
            <?php echo __('Need help updating your system? Please don\'t hesitate to contact our enterprise support {0}.', '<a class="txt-color-darken" href="mailto:support@itsm.it-novum.com">support@itsm.it-novum.com</a>'); ?>
        </div>
    <?php endif; ?>

    <?php if ($LsbRelease->getCodename() === 'focal'): ?>
        <div class="alert alert-danger alert-block">
            <a class="close" data-dismiss="alert" href="javascript:void(0);">×</a>
            <h4 class="alert-heading">
                <i class="fa fa-warning"></i>
                <?php echo __('Ubuntu Focal 20.04 is end of life soon!'); ?>
            </h4>
            <?php echo __('Official end of life of Ubuntu Focal scheduled for April 2025.'); ?>
            <?php echo __('Therefore openITCOCKPIT 4.8.x will be one of the last releases for Ubuntu Focal. Please update to Ubuntu Jammy to receive further updates.'); ?>
            <br/>
            <?php echo __('Need help updating your system? Please don\'t hesitate to contact our enterprise support {0}.', '<a class="txt-color-darken" href="https://openitcockpit.io/contact/">https://openitcockpit.io/contact/</a>'); ?>
        </div>
    <?php endif; ?>

    <?php if ($LsbRelease->getCodename() === 'buster'): ?>
        <div class="alert alert-danger alert-block">
            <a class="close" data-dismiss="alert" href="javascript:void(0);">×</a>
            <h4 class="alert-heading">
                <i class="fa fa-warning"></i>
                <?php echo __('Debian Buster 10 end of life!'); ?>
            </h4>
            <?php echo __('Debian Buster is not supported by the Debian security team anymore!'); ?>
            <?php echo __('Therefore openITCOCKPIT 4.5.5 will be one of the last releases for Debian Buster. Please update to Debian Bullseye to receive further updates.'); ?>
            <br/>
            <?php echo __('Need help updating your system? Please don\'t hesitate to contact our enterprise support {0}.', '<a class="txt-color-darken" href="mailto:support@itsm.it-novum.com">support@itsm.it-novum.com</a>'); ?>
        </div>
    <?php endif; ?>

    <?php if ($LsbRelease->getCodename() === 'bullseye'): ?>
        <div class="alert alert-danger alert-block">
            <a class="close" data-dismiss="alert" href="javascript:void(0);">×</a>
            <h4 class="alert-heading">
                <i class="fa fa-warning"></i>
                <?php echo __('Debian Bullseye 11 end of life!'); ?>
            </h4>
            <?php echo __('Debian Bullseye is not supported by the Debian security team anymore!'); ?>
            <?php echo __('Therefore openITCOCKPIT 4.8.x will be one of the last releases for Debian Bullseye. Please update to Debian Bookworm to receive further updates.'); ?>
            <br/>
            <?php echo __('Need help updating your system? Please don\'t hesitate to contact our enterprise support {0}.', '<a class="txt-color-darken" href="https://openitcockpit.io/contact/">https://openitcockpit.io/contact/</a>'); ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<div class="row">
    <div class="col-xs-12 col-lg-6">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <i class="fa fa-globe-americas">&nbsp;</i>
                    <?php echo __('Interface information'); ?>
                </h2>
            </div>
            <div class="panel-container show">
                <div class="panel-content">

                    <div class="row">
                        <div class="col-12 text-center">
                            <img class="img-fluid" alt="Logo" src="<?= $Logo->getLogoForHtml() ?>"
                                 style="max-height: 209px;">
                        </div>
                    </div>

                    <div class="frame-wrap">
                        <dl class="dl-horizontal dl-inline">
                            <dt><?php echo __('System name'); ?>:</dt>
                            <dd>{{interfaceInformation.systemname}}</dd>

                            <dt><?php echo __('Version'); ?>:</dt>
                            <dd>{{interfaceInformation.version}}</dd>

                            <dt><?php echo __('Edition'); ?>:</dt>
                            <dd>{{interfaceInformation.edition}}</dd>

                            <dt><?php echo __('Monitoring Engine'); ?>:</dt>
                            <dd>{{interfaceInformation.monitoring_engine}}</dd>

                            <dt><?php echo __('Path for config'); ?>:</dt>
                            <dd>{{interfaceInformation.path_for_config}}</dd>

                            <dt><?php echo __('Path for backups'); ?>:</dt>
                            <dd>{{interfaceInformation.path_for_backups}}</dd>

                            <dt><?php echo __('Command interface'); ?>:</dt>
                            <dd>{{interfaceInformation.command_interface}}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xs-12 col-lg-6">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <i class="fa fa-cogs">&nbsp;</i>
                    <?php echo __('Process information'); ?>
                </h2>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <div class="frame-wrap">
                        <div>
                            <div class="widget-body padding-10" ng-hide="processInformation.gearmanReachable"
                                 style="min-height: 215px;">
                                <div id="error_msg">
                                    <div class="alert alert-danger alert-block">
                                        <h5 class="alert-heading"><i class="fa fa-warning"></i>
                                            <?php echo __('Error'); ?>
                                        </h5>
                                        <?php echo __('Gearman-Job-Server is not running, openITCOCKPIT could not check the state of background daemons'); ?>
                                        <br/>
                                        <?php echo __('Please start Gearman-Job-Server first'); ?>
                                    </div>
                                </div>
                            </div>

                            <div class="widget-body padding-10" ng-hide="processInformation.isGearmanWorkerRunning"
                                 style="min-height: 215px;">
                                <div id="error_msg">
                                    <div class="alert alert-danger alert-block">
                                        <h5 class="alert-heading"><i class="fa fa-warning"></i>
                                            <?php echo __('Error'); ?></h5>
                                        <?php echo __('gearman_worker is not running, openITCOCKPIT could not check the state of background daemons'); ?>
                                        <br/>
                                        <?php echo __('Please start gearman_worker first'); ?>
                                    </div>
                                </div>
                            </div>


                            <div class="widget-body padding-10"
                                 ng-show="processInformation.gearmanReachable && processInformation.isGearmanWorkerRunning"
                                 style="min-height: 215px;">
                                <table class="table table-bordered table-sm">
                                    <thead>
                                    <tr>
                                        <th><?= __('Process'); ?></th>
                                        <th><?= __('State'); ?></th>
                                        <th class="text-center"><i class="fas fa-info-circle"></i></th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr>
                                        <td><?= __('Monitoring engine'); ?></td>
                                        <td>
                                            <span class="badge border border-success text-success"
                                                  ng-show="processInformation.backgroundProcesses.isNagiosRunning">
                                                <?= __('Running') ?>
                                            </span>
                                            <span class="badge border border-danger text-danger"
                                                  ng-hide="processInformation.backgroundProcesses.isNagiosRunning">
                                                <?= __('Stopped') ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <a data-original-title="{{interfaceInformation.monitoring_engine}}"
                                               data-placement="right"
                                               rel="tooltip" class="text-info" href="javascript:void(0);">
                                                <i class="fas fa-info-circle"></i>
                                            </a>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td><?= __('Database connector'); ?></td>
                                        <td>
                                            <span class="badge border border-success text-success"
                                                  ng-show="processInformation.backgroundProcesses.isStatusengineRunning">
                                                <?= __('Running') ?>
                                            </span>
                                            <span class="badge border border-danger text-danger"
                                                  ng-hide="processInformation.backgroundProcesses.isStatusengineRunning">
                                                <?= __('Stopped') ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <a data-original-title="<?= __('Statusengine') ?>"
                                               data-placement="right"
                                               rel="tooltip" class="text-info" href="javascript:void(0);">
                                                <i class="fas fa-info-circle"></i>
                                            </a>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td><?= __('Perfdata processor'); ?></td>
                                        <td>
                                            <span class="badge border border-success text-success"
                                                  ng-show="processInformation.backgroundProcesses.isStatusengineRunning">
                                                <?= __('Running') ?>
                                            </span>
                                            <span class="badge border border-danger text-danger"
                                                  ng-hide="processInformation.backgroundProcesses.isStatusengineRunning">
                                                <?= __('Stopped') ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <a data-original-title="<?= __('Statusengine') ?>"
                                               data-placement="right"
                                               rel="tooltip" class="text-info" href="javascript:void(0);">
                                                <i class="fas fa-info-circle"></i>
                                            </a>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td><?= __('Queuing engine'); ?></td>
                                        <td>
                                            <span class="badge border border-success text-success">
                                                <?= __('Running') ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <a data-original-title="<?php echo h('openITCOCKPIT uses the Gearman Job Server to run different background tasks'); ?>"
                                               data-placement="right" rel="tooltip" class="text-info"
                                               href="javascript:void(0);">
                                                <i class="fa fa-info-circle"></i>
                                            </a>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td><?= __('Gearman Worker'); ?></td>
                                        <td>
                                            <span class="badge border border-success text-success"
                                                  ng-show="processInformation.backgroundProcesses.isGearmanWorkerRunning">
                                                <?= __('Running') ?>
                                            </span>
                                            <span class="badge border border-danger text-danger"
                                                  ng-hide="processInformation.backgroundProcesses.isGearmanWorkerRunning">
                                                <?= __('Stopped') ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <a data-original-title="<?php echo h('Execute background jobs like refresh of monitoring configuration.'); ?>"
                                               data-placement="right" rel="tooltip" class="text-info"
                                               href="javascript:void(0);">
                                                <i class="fa fa-info-circle"></i>
                                            </a>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td><?= __('OITC Cmd'); ?></td>
                                        <td>
                                            <span class="badge border border-success text-success"
                                                  ng-show="processInformation.backgroundProcesses.isOitcCmdRunning">
                                                <?= __('Running') ?>
                                            </span>
                                            <span class="badge border border-danger text-danger"
                                                  ng-hide="processInformation.backgroundProcesses.isOitcCmdRunning">
                                                <?= __('Stopped') ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <a data-original-title="<?php echo h('External command interface used by Check_MK to pass check results.'); ?>"
                                               data-placement="right" rel="tooltip" class="text-info"
                                               href="javascript:void(0);">
                                                <i class="fa fa-info-circle"></i>
                                            </a>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td><?= __('NSTA'); ?></td>
                                        <td>
                                            <span class="badge border border-success text-success"
                                                  ng-show="processInformation.backgroundProcesses.isNstaRunning">
                                                <?= __('Running') ?>
                                            </span>
                                            <span class="badge border border-danger text-danger"
                                                  ng-hide="processInformation.backgroundProcesses.isNstaRunning">
                                                <?= __('Stopped') ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <a data-original-title="<?php echo h('phpNSTA is only installed and running if you are using Distributed Monitoring.'); ?>"
                                               data-placement="right" rel="tooltip" class="text-info"
                                               href="javascript:void(0);">
                                                <i class="fa fa-info-circle"></i>
                                            </a>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td><?= __('Push notification service'); ?></td>
                                        <td>
                                            <span class="badge border border-success text-success"
                                                  ng-show="processInformation.backgroundProcesses.isPushNotificationRunning">
                                                <?= __('Running') ?>
                                            </span>
                                            <span class="badge border border-danger text-danger"
                                                  ng-hide="processInformation.backgroundProcesses.isPushNotificationRunning">
                                                <?= __('Stopped') ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <a data-original-title="<?php echo h('Service required to send push notifications to your browser window.'); ?>"
                                               data-placement="right" rel="tooltip" class="text-info"
                                               href="javascript:void(0);">
                                                <i class="fa fa-info-circle"></i>
                                            </a>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td><?= __('Nodejs Backend'); ?></td>
                                        <td>
                                            <span class="badge border border-success text-success"
                                                  ng-show="processInformation.backgroundProcesses.isNodeJsServerRunning">
                                                <?= __('Running') ?>
                                            </span>
                                            <span class="badge border border-danger text-danger"
                                                  ng-hide="processInformation.backgroundProcesses.isNodeJsServerRunning">
                                                <?= __('Stopped') ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <a data-original-title="<?php echo h('Service required to run server side JavaScript to render PDF files and charts for email notifications and PDF reports.'); ?>"
                                               data-placement="right" rel="tooltip" class="text-info"
                                               href="javascript:void(0);">
                                                <i class="fa fa-info-circle"></i>
                                            </a>
                                        </td>
                                    </tr>

                                    </tbody>
                                </table>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xs-12 col-lg-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <i class="fa fa-hdd-o">&nbsp;</i>
                    <?php echo __('Server information'); ?>
                </h2>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <div class="frame-wrap">
                        <dl class="dl-horizontal dl-inline">
                            <dt><?php echo __('Address'); ?>:</dt>
                            <dd>{{serverInformation.address}}</dd>

                            <dt><?php echo __('Webserver'); ?>:</dt>
                            <dd>{{serverInformation.webserver}}</dd>

                            <dt><?php echo __('HTTPS / TLS'); ?>:</dt>
                            <dd>{{serverInformation.tls}}</dd>

                            <dt><?php echo __('OS'); ?>:</dt>
                            <dd>{{serverInformation.os_version}}</dd>

                            <dt><?php echo __('Kernel'); ?>:</dt>
                            <dd>{{serverInformation.kernel}}</dd>

                            <dt><?php echo __('Containerized'); ?>:</dt>
                            <dd>{{serverInformation.containerized}}</dd>

                            <dt><?php echo __('PHP version'); ?>:</dt>
                            <dd>{{serverInformation.php_version}}</dd>

                            <dt><?php echo __('PHP Memory limit'); ?>:</dt>
                            <dd>{{serverInformation.php_memory_limit}}</dd>

                            <dt><?php echo __('PHP Max. execution time'); ?>:</dt>
                            <dd>{{serverInformation.php_max_execution_time}}</dd>

                            <dt><?php echo __('PHP loaded extensions'); ?>:</dt>
                            <dd>{{serverInformation.php_extensions.join(', ')}}</dd>

                            <dt><?php echo __('CPU model'); ?>:</dt>
                            <dd>{{serverInformation.cpu_processor}}</dd>

                            <dt><?php echo __('CPU Architecture'); ?>:</dt>
                            <dd>{{serverInformation.architecture}}</dd>

                            <dt><?php echo __('Number of CPU cores'); ?>:</dt>
                            <dd>{{serverInformation.cpu_cores}}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xs-12 col-lg-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <i class="fa fa-bar-chart">&nbsp;</i>
                    <?php echo __('CPU load'); ?>
                </h2>
                <div class="panel-toolbar">
                    <div class="form-group panelToolbarInput">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox"
                                   class="custom-control-input"
                                   id="debuggingKeepHistory"
                                   ng-model="graph.keepHistory">
                            <label class="custom-control-label no-margin" for="debuggingKeepHistory">
                                <?php echo __('Keep history'); ?>
                            </label>
                        </div>
                    </div>

                    <button class="btn btn-xs btn-default mr-1 shadow-0" ng-click="load()">
                        <i class="fas fa-sync"></i> <?php echo __('Refresh'); ?>
                    </button>

                    <?php echo __('Current load:'); ?> {{currentCpuLoad['1']}}, {{currentCpuLoad['5']}},
                    {{currentCpuLoad['15']}}
                </div>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <div class="frame-wrap">
                        <div class="card" ng-show="!renderGraph">
                            {{currentCpuLoad['1']}}, {{currentCpuLoad['5']}}, {{currentCpuLoad['15']}}
                        </div>
                        <div ng-show="renderGraph">
                            <div class="graph_legend">
                                <table style="font-size: 11px; color:#545454">
                                    <tbody>
                                    <tr>
                                        <td class="legendColorBox">
                                            <div style="">
                                                <div style="border:2px solid #6595B4;overflow:hidden"></div>
                                            </div>
                                        </td>
                                        <td class="legendLabel">
                                            <span><?php echo __('1 Minute'); ?></span>
                                        </td>
                                        <td class="legendColorBox">
                                            <div style="">
                                                <div style="border:2px solid #7E9D3A;overflow:hidden"></div>
                                            </div>
                                        </td>
                                        <td class="legendLabel">
                                            <span><?php echo __('5 Minutes'); ?></span>
                                        </td>
                                        <td class="legendColorBox">
                                            <div style="">
                                                <div style="border:2px solid #E24913;overflow:hidden"></div>
                                            </div>
                                        </td>
                                        <td class="legendLabel">
                                            <span><?php echo __('15 Minutes'); ?></span>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div id="graph_data_tooltip"></div>
                            <div id="graphCanvas" style="height: 300px;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<div class="row">
    <div class="col-xs-12 col-lg-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <i class="fa fa-pie-chart">&nbsp;</i>
                    <?php echo __('Memory and disk usage'); ?>
                </h2>
                <div class="panel-toolbar">
                    <button class="btn btn-xs btn-default mr-1 shadow-0" ng-click="load()">
                        <i class="fas fa-sync"></i> <?php echo __('Refresh'); ?>
                    </button>
                </div>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <div class="frame-wrap">
                        <b><?php echo __('Memory usage'); ?>:</b>
                        <div class="card">
                            <div class="card-body">
                                <?php echo __('Total:'); ?> {{memory.memory.total}}

                                <span class="txt-color-green"><?php echo __('Used:'); ?>
                                    {{memory.memory.used}}MB
                                </span>

                                <span class="txt-color-orange"><?php echo __('Cached:'); ?>
                                    {{memory.memory.cached}}MB
                                </span>

                                <span class="txt-color-blue"><?php echo __('Buffers:'); ?>
                                    {{memory.memory.buffers}}MB
                                </span>

                                <div class="progress progress-lg" style="margin-bottom: 0px;">
                                    <div
                                        style="width: {{(memory.memory.used / memory.memory.total) * 100 }}%; position: unset;"
                                        class="progress-bar bg-ok">
                                        <span ng-show="memory.memory.used > 50">{{memory.memory.used}}MB</span>
                                    </div>
                                    <div
                                        style="width: {{(memory.memory.cached / memory.memory.total) * 100 }}%; position: unset;"
                                        class="progress-bar bg-warning">
                                        <span ng-show="memory.memory.cached > 10">{{memory.memory.cached}}MB</span>
                                    </div>
                                    <div
                                        style="width: {{(memory.memory.buffers / memory.memory.total) * 100 }}%; position: unset;"
                                        class="progress-bar bg-downtime">
                                        <span ng-show="memory.memory.buffers > 10">{{memory.memory.buffers}}MB</span>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <br/>
                        <b><?php echo __('Swap usage'); ?>:</b>
                        <div class="card">
                            <div class="card-body">
                                <?php echo __('Total:'); ?> {{memory.swap.total}}

                                <span class="txt-color-red"><?php echo __('Used:'); ?>
                                    {{memory.swap.used}}MB
                                </span>


                                <div class="progress progress-lg" style="margin-bottom: 0px;">
                                    <div
                                        style="width: {{(memory.swap.used / memory.swap.total) * 100 }}%; position: unset;"
                                        class="progress-bar bg-critical">
                                        <span ng-show="memory.swap.used > 50">{{memory.swap.used}}MB</span>
                                    </div>
                                </div>
                            </div>
                        </div>


                        <br/>
                        <b><?php echo __('Disk usage'); ?>:</b>
                        <div class="card">
                            <div class="card-body" ng-repeat="disk in diskUsage">
                                <b>{{disk.disk}}</b>
                                (
                                <?php echo __('Size'); ?>: {{disk.size}}
                                <?php echo __('Available'); ?>: {{disk.avail}}
                                <?php echo __('Mount point'); ?>: {{disk.mountpoint}}
                                )


                                <div class="progress progress-lg" style="margin-bottom: 0px;">
                                    <div style="width: {{disk.use_percentage}}%; position: unset;"
                                         class="progress-bar bg-downtime">
                                        <span ng-show="disk.use_percentage > 5">{{disk.use_percentage}}%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<div class="row">
    <div class="col-xs-12 col-lg-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <i class="fa fa-list-ol">&nbsp;</i>
                    <?php echo __('Queuing engine'); ?>
                </h2>
                <div class="panel-toolbar">
                    <button class="btn btn-xs btn-default mr-1 shadow-0" ng-click="load()">
                        <i class="fas fa-sync"></i> <?php echo __('Refresh'); ?>
                    </button>
                </div>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <div class="frame-wrap">
                        <table class="table table-striped m-0 table-bordered table-hover table-sm">
                            <thead>
                            <tr>
                                <th class="no-sort">
                                    <?php echo __('Queue name'); ?>
                                </th>
                                <th class="no-sort">
                                    <?php echo __('Jobs waiting'); ?>
                                </th>
                                <th class="no-sort">
                                    <?php echo __('Active jobs'); ?>
                                </th>
                                <th class="no-sort">
                                    <?php echo __('Worker available'); ?>
                                </th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr ng-repeat="(queueName, queue) in gearmanStatus"
                                class="{{getGearmanStatusClass(queue.jobs, queue.worker)}}">
                                <td>{{queueName}}</td>
                                <td>
                                    {{queue.jobs}}
                                    <span id="{{queueName}}_sparkline"></span>
                                </td>
                                <td>{{queue.running}}</td>
                                <td>{{queue.worker}}</td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<div class="row">
    <div class="col-xs-12 col-lg-6">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <i class="fa fa-envelope">&nbsp;</i>
                    <?php echo __('Email configuration'); ?>
                </h2>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <div class="frame-wrap">
                        <dl class="dl-horizontal">
                            <dt><?php echo __('Mail server address'); ?>:</dt>
                            <dd>{{emailInformation.host}}</dd>

                            <dt><?php echo __('Mail server port'); ?>:</dt>
                            <dd>{{emailInformation.port}}</dd>

                            <dt><?php echo __('Transport method'); ?>:</dt>
                            <dd>{{emailInformation.transport}}</dd>

                            <dt><?php echo __('Username'); ?>:</dt>
                            <dd>{{emailInformation.username}}</dd>

                            <dt><?php echo __('Password'); ?>:</dt>
                            <dd>
                                <i><?php echo __('Password hidden due to security please see the file /opt/openitc/frontend/config/email.php for detailed configuration information.'); ?></i>
                            </dd>

                            <dt>&nbsp;</dt>
                            <dd>
                                <button class="btn btn-xs btn-default" ng-click="sendTestMail()">
                                    <?php echo __('Send test Email to'); ?> {{emailInformation.test_mail_address}}
                                </button>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xs-12 col-lg-6">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <i class="fa fa-desktop">&nbsp;</i>
                    <?php echo __('Client information'); ?>
                </h2>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <div class="frame-wrap">
                        <dl class="dl-horizontal">
                            <dt><?php echo __('Client OS'); ?>:</dt>
                            <dd>{{userInformation.user_os}}</dd>

                            <dt><?php echo __('Client browser'); ?>:</dt>
                            <dd>{{userInformation.user_agent}}</dd>

                            <dt><?php echo __('Client IP address'); ?>:</dt>
                            <dd>{{userInformation.user_remote_address}}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- php info -->
<div id="phpinfo">
    <?php
    ob_start();
    phpinfo();
    $phpinfo = ob_get_contents();
    ob_end_clean();
    $phpinfo = preg_replace('%^.*<body>(.*)</body>.*$%ms', '$1', $phpinfo);
    echo "
    <style type='text/css'>
        #phpinfo {}
        #phpinfo pre {margin: 0; font-family: monospace;}
        #phpinfo a:link {color: #009; text-decoration: none; background-color: #fff;}
        #phpinfo a:hover {text-decoration: underline;}
        #phpinfo table {border-collapse: collapse; border: 0; width: 100%; box-shadow: 1px 2px 3px #f6f6f6;}
        #phpinfo .center {text-align: center;}
        #phpinfo .center table {margin: 1em auto; text-align: left;}
        #phpinfo .center th {text-align: center !important;}
        #phpinfo td, th {border: 1px solid #eee; font-size: 0.8125rem; vertical-align: baseline; padding: 4px 5px;}
        #phpinfo h1 {font-size: 150%;}
        #phpinfo h2 {font-size: 125%;}
        #phpinfo .p {text-align: left;}
        #phpinfo .e {color:#000;width: 300px; font-weight: bold;}
        #phpinfo .h {font-weight: bold;}
        #phpinfo .v {color: #000;max-width: 300px; overflow-x: auto; word-wrap: break-word;}
        #phpinfo .v i {color: #000;}
        #phpinfo img {float: right; border: 0;}
        #phpinfo hr {width: 934px; background-color: #ccc; border: 0; height: 1px;}
        #phpinfo tbody tr:nth-of-type(odd) {background-color: #FBFCFC !important;}
        #phpinfo tbody tr:nth-of-type(even) {background-color: #FFFFFF !important;}

    </style>
    <div id='phpinfo'>
        $phpinfo
    </div>
    ";
    ?>
</div>
