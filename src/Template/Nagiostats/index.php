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
        <a ui-sref="NagiostatsIndex">
            <i class="fas fa-fighter-jet"></i> <?php echo __('Performance Info'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <i class="fa fa-list"></i> <?php echo __('Overview'); ?>
    </li>
</ol>


<div class="row">
    <div class="col-xl-12">
        <div id="panel-0" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?php echo __('Performance Information'); ?>
                    <span class="fw-300"><i><?php echo __('overview'); ?></i></span>
                </h2>
                <div class="panel-toolbar">
                    <button class="btn btn-xs btn-default mr-1 shadow-0" ng-click="load()">
                        <i class="fas fa-sync"></i> <?php echo __('Refresh'); ?>
                    </button>
                </div>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <div class="row">
                        <div class="col-xs-12 col-xl-4">
                            <div id="panel-1" class="panel">
                                <div class="panel-hdr">
                                    <h2>
                                        <?php echo __('Executed Active Service Checks'); ?>
                                    </h2>
                                </div>
                                <div class="panel-container show">
                                    <div class="panel-content">
                                        <div class="frame-wrap">
                                            <table class="table table-striped m-0 table-bordered table-hover table-sm">
                                                <thead>
                                                <tr>
                                                    <th><?php echo __('Timeframe'); ?></th>
                                                    <th><?php echo __('Checks performed'); ?></th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <tr>
                                                    <td><= 1 <?php echo __('minute'); ?></td>
                                                    <td ng-class="{'critical': stats.NUMSVCACTCHK1M == 0}">
                                                        {{stats.NUMSVCACTCHK1M}}
                                                        <span id="NUMSVCACTCHK1M_sparkline"></span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td><= 5 <?php echo __('minutes'); ?></td>
                                                    <td ng-class="{'critical': stats.NUMSVCACTCHK5M == 0}">
                                                        {{stats.NUMSVCACTCHK5M}}
                                                        <span id="NUMSVCACTCHK5M_sparkline"></span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td><= 15 <?php echo __('minutes'); ?></td>
                                                    <td ng-class="{'critical': stats.NUMSVCACTCHK15M == 0}">
                                                        {{stats.NUMSVCACTCHK15M}}
                                                        <span id="NUMSVCACTCHK15M_sparkline"></span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td><= 60 <?php echo __('minutes'); ?></td>
                                                    <td ng-class="{'critical': stats.NUMSVCACTCHK60M == 0}">
                                                        {{stats.NUMSVCACTCHK60M}}
                                                        <span id="NUMSVCACTCHK60M_sparkline"></span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td><?php echo __('Since start'); ?></td>
                                                    <td ng-class="{'critical': stats.NUMSVCCHECKED == 0}">
                                                        {{stats.NUMSVCCHECKED}}
                                                    </td>
                                                </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>


                        <div class="col-xs-12 col-xl-4">
                            <div id="panel-2" class="panel">
                                <div class="panel-hdr">
                                    <h2>
                                        <?php echo __('Active Service Checks Timings'); ?>
                                    </h2>
                                </div>
                                <div class="panel-container show">
                                    <div class="panel-content">
                                        <div class="frame-wrap">
                                            <table class="table table-striped m-0 table-bordered table-hover table-sm">
                                                <thead>
                                                <tr>
                                                    <th><?php echo __('Key'); ?></th>
                                                    <th><?php echo __('Min.'); ?></th>
                                                    <th><?php echo __('Max.'); ?></th>
                                                    <th>&#216;</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <tr>
                                                    <td><?php echo __('Execution time'); ?></td>
                                                    <td>{{stats.MINACTSVCEXT / 1000}} sec.</td>
                                                    <td>{{stats.MAXACTSVCEXT / 1000}} sec.</td>
                                                    <td ng-class="{'warning': stats.AVGACTSVCEXT > 20000, 'critical': stats.AVGACTSVCEXT > 30000}">
                                                        {{stats.AVGACTSVCEXT / 1000}} sec.
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td><?php echo __('Latency'); ?></td>
                                                    <td ng-class="{'warning': stats.MINACTSVCLAT > 20000, 'critical': stats.MINACTSVCLAT > 30000}">
                                                        {{stats.MINACTSVCLAT / 1000}} sec.
                                                    </td>
                                                    <td ng-class="{'warning': stats.MAXACTSVCLAT > 20000, 'critical': stats.MAXACTSVCLAT > 30000}">
                                                        {{stats.MAXACTSVCLAT / 1000}} sec.
                                                    </td>
                                                    <td ng-class="{'warning': stats.AVGACTSVCLAT > 20000, 'critical': stats.AVGACTSVCLAT > 30000}">
                                                        {{stats.AVGACTSVCLAT / 1000}} sec.
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td><?php echo __('State changes'); ?></td>
                                                    <td>{{stats.MINACTSVCPSC}} %</td>
                                                    <td>{{stats.MAXACTSVCPSC}} %</td>
                                                    <td>{{stats.AVGACTSVCPSC}} %</td>
                                                </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xs-12 col-xl-4">
                            <div id="panel-3" class="panel">
                                <div class="panel-hdr">
                                    <h2>
                                        <?php echo __('Additional information'); ?>
                                    </h2>
                                </div>
                                <div class="panel-container show">
                                    <div class="panel-content">
                                        <div class="frame-wrap">
                                            <table class="table table-striped m-0 table-bordered table-hover table-sm">
                                                <thead>
                                                <tr>
                                                    <th><?php echo __('Key'); ?></th>
                                                    <th><?php echo __('Value'); ?></th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <tr>
                                                    <td><?php echo __('Version'); ?></td>
                                                    <td>{{stats.NAGIOSVERSION}}</td>
                                                </tr>
                                                <tr>
                                                    <td><?php echo __('Core PID'); ?></td>
                                                    <td>{{stats.NAGIOSPID}}</td>
                                                </tr>
                                                <tr>
                                                    <td><?php echo __('Total hosts'); ?></td>
                                                    <td>{{stats.NUMHOSTS}}</td>
                                                </tr>
                                                <tr>
                                                    <td><?php echo __('Total services'); ?></td>
                                                    <td>{{stats.NUMSERVICES}}</td>
                                                </tr>
                                                <tr>
                                                    <td><?php echo __('Program uptime'); ?></td>
                                                    <td>{{stats.PROGRUNTIME}}</td>
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
                        <div class="col-xs-12 col-xl-4">
                            <div id="panel-4" class="panel">
                                <div class="panel-hdr">
                                    <h2>
                                        <?php echo __('Passive Service Checks'); ?>
                                    </h2>
                                </div>
                                <div class="panel-container show">
                                    <div class="panel-content">
                                        <div class="frame-wrap">
                                            <table class="table table-striped m-0 table-bordered table-hover table-sm">
                                                <thead>
                                                <tr>
                                                    <th><?php echo __('Timeframe'); ?></th>
                                                    <th><?php echo __('Checks performed'); ?></th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <tr>
                                                    <td><= 1 <?php echo __('minute'); ?></td>
                                                    <td>
                                                        {{stats.NUMSVCPSVCHK1M}}
                                                        <span id="NUMSVCPSVCHK1M_sparkline"></span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td><= 5 <?php echo __('minutes'); ?></td>
                                                    <td>
                                                        {{stats.NUMSVCPSVCHK5M}}
                                                        <span id="NUMSVCPSVCHK5M_sparkline"></span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td><= 15 <?php echo __('minutes'); ?></td>
                                                    <td>
                                                        {{stats.NUMSVCPSVCHK15M}}
                                                        <span id="NUMSVCPSVCHK15M_sparkline"></span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td><= 60 <?php echo __('minutes'); ?></td>
                                                    <td>
                                                        {{stats.NUMSVCPSVCHK60M}}
                                                        <span id="NUMSVCPSVCHK60M_sparkline"></span>
                                                    </td>
                                                </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xs-12 col-xl-4">
                            <div id="panel-5" class="panel">
                                <div class="panel-hdr">
                                    <h2>
                                        <?php echo __('Processed Passive Service Checks'); ?>
                                    </h2>
                                </div>
                                <div class="panel-container show">
                                    <div class="panel-content">
                                        <div class="frame-wrap">
                                            <table class="table table-striped m-0 table-bordered table-hover table-sm">
                                                <thead>
                                                <tr>
                                                    <th><?php echo __('Key'); ?></th>
                                                    <th><?php echo __('Min.'); ?></th>
                                                    <th><?php echo __('Max.'); ?></th>
                                                    <th>&#216;</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <tr>
                                                    <td><?php echo __('State changes'); ?></td>
                                                    <td>{{stats.MINPSVSVCPSC}} %</td>
                                                    <td>{{stats.MAXPSVSVCPSC}} %</td>
                                                    <td>{{stats.AVGPSVSVCPSC}} %</td>
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
                        <div class="col-xs-12 col-xl-4">
                            <div id="panel-5" class="panel">
                                <div class="panel-hdr">
                                    <h2>
                                        <?php echo __('Executed Active Host Checks'); ?>
                                    </h2>
                                </div>
                                <div class="panel-container show">
                                    <div class="panel-content">
                                        <div class="frame-wrap">
                                            <table class="table table-striped m-0 table-bordered table-hover table-sm">
                                                <thead>
                                                <tr>
                                                    <th><?php echo __('Timeframe'); ?></th>
                                                    <th><?php echo __('Checks performed'); ?></th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <tr>
                                                    <td><= 1 <?php echo __('minute'); ?></td>
                                                    <td ng-class="{'critical': stats.NUMHSTACTCHK1M == 0}">
                                                        {{stats.NUMHSTACTCHK1M}}
                                                        <span id="NUMHSTACTCHK1M_sparkline"></span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td><= 5 <?php echo __('minutes'); ?></td>
                                                    <td ng-class="{'critical': stats.NUMHSTACTCHK5M == 0}">
                                                        {{stats.NUMHSTACTCHK5M}}
                                                        <span id="NUMHSTACTCHK5M_sparkline"></span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td><= 15 <?php echo __('minutes'); ?></td>
                                                    <td ng-class="{'critical': stats.NUMHSTACTCHK15M == 0}">
                                                        {{stats.NUMHSTACTCHK15M}}
                                                        <span id="NUMHSTACTCHK15M_sparkline"></span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td><= 60 <?php echo __('minutes'); ?></td>
                                                    <td ng-class="{'critical': stats.NUMHSTACTCHK60M == 0}">
                                                        {{stats.NUMHSTACTCHK60M}}
                                                        <span id="NUMHSTACTCHK60M_sparkline"></span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td><?php echo __('Since start'); ?></td>
                                                    <td ng-class="{'critical': stats.NUMHSTCHECKED == 0}">
                                                        {{stats.NUMHSTCHECKED}}
                                                    </td>
                                                </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xs-12 col-xl-4">
                            <div id="panel-5" class="panel">
                                <div class="panel-hdr">
                                    <h2>
                                        <?php echo __('Active Host Checks Timing'); ?>
                                    </h2>
                                </div>
                                <div class="panel-container show">
                                    <div class="panel-content">
                                        <div class="frame-wrap">
                                            <table class="table table-striped m-0 table-bordered table-hover table-sm">
                                                <thead>
                                                <tr>
                                                    <th><?php echo __('Key'); ?></th>
                                                    <th><?php echo __('Min.'); ?></th>
                                                    <th><?php echo __('Max.'); ?></th>
                                                    <th>&#216;</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <tr>
                                                    <td><?php echo __('Execution time'); ?></td>
                                                    <td ng-class="{'warning': stats.MINACTHSTEXT > 20000, 'critical': stats.MINACTHSTEXT > 30000}">
                                                        {{stats.MINACTHSTEXT / 1000}} sec.
                                                    </td>
                                                    <td ng-class="{'warning': stats.MAXACTHSTEXT > 20000, 'critical': stats.MAXACTHSTEXT > 30000}">
                                                        {{stats.MAXACTHSTEXT / 1000}} sec.
                                                    </td>
                                                    <td ng-class="{'warning': stats.AVGACTHSTEXT > 20000, 'critical': stats.AVGACTHSTEXT > 30000}">
                                                        {{stats.AVGACTHSTEXT / 1000}} sec.
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td><?php echo __('Latency'); ?></td>
                                                    <td ng-class="{'warning': stats.MINACTHSTLAT > 20000, 'critical': stats.MINACTHSTLAT > 30000}">
                                                        {{stats.MINACTHSTLAT / 1000}} sec.
                                                    </td>
                                                    <td ng-class="{'warning': stats.MAXACTHSTLAT > 20000, 'critical': stats.MAXACTHSTLAT > 30000}">
                                                        {{stats.MAXACTHSTLAT / 1000}} sec.
                                                    </td>
                                                    <td ng-class="{'warning': stats.AVGACTHSTLAT > 20000, 'critical': stats.AVGACTHSTLAT > 30000}">
                                                        {{stats.AVGACTHSTLAT / 1000}} sec.
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td><?php echo __('State changes'); ?></td>
                                                    <td>{{stats.MINACTHSTPSC}} %</td>
                                                    <td>{{stats.MAXACTHSTPSC}} %</td>
                                                    <td>{{stats.AVGACTHSTPSC}} %</td>
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
                        <div class="col-xs-12 col-xl-4">
                            <div id="panel-5" class="panel">
                                <div class="panel-hdr">
                                    <h2>
                                        <?php echo __('Processed Passive Host Checks'); ?>
                                    </h2>
                                </div>
                                <div class="panel-container show">
                                    <div class="panel-content">
                                        <div class="frame-wrap">
                                            <table class="table table-striped m-0 table-bordered table-hover table-sm">
                                                <thead>
                                                <tr>
                                                    <th><?php echo __('Timeframe'); ?></th>
                                                    <th><?php echo __('Checks performed'); ?></th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <tr>
                                                    <td><= 1 <?php echo __('minute'); ?></td>
                                                    <td>
                                                        {{stats.NUMHSTPSVCHK1M}}
                                                        <span id="NUMHSTPSVCHK1M_sparkline"></span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td><= 5 <?php echo __('minutes'); ?></td>
                                                    <td>
                                                        {{stats.NUMHSTPSVCHK5M}}
                                                        <span id="NUMHSTPSVCHK5M_sparkline"></span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td><= 15 <?php echo __('minutes'); ?></td>
                                                    <td>
                                                        {{stats.NUMHSTPSVCHK15M}}
                                                        <span id="NUMHSTPSVCHK15M_sparkline"></span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td><= 60 <?php echo __('minutes'); ?></td>
                                                    <td>
                                                        {{stats.NUMHSTPSVCHK60M}}
                                                        <span id="NUMHSTPSVCHK60M_sparkline"></span>
                                                    </td>
                                                </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xs-12 col-xl-4">
                            <div id="panel-5" class="panel">
                                <div class="panel-hdr">
                                    <h2>
                                        <?php echo __('Passive Host Checks'); ?>
                                    </h2>
                                </div>
                                <div class="panel-container show">
                                    <div class="panel-content">
                                        <div class="frame-wrap">
                                            <table class="table table-striped m-0 table-bordered table-hover table-sm">
                                                <thead>
                                                <tr>
                                                    <th><?php echo __('Key'); ?></th>
                                                    <th><?php echo __('Min.'); ?></th>
                                                    <th><?php echo __('Max.'); ?></th>
                                                    <th>&#216;</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <tr>
                                                    <td><?php echo __('State changes'); ?></td>
                                                    <td>{{stats.MINPSVHSTPSC}} %</td>
                                                    <td>{{stats.MAXPSVHSTPSC}} %</td>
                                                    <td>{{stats.AVGPSVHSTPSC}} %</td>
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
        </div>
    </div>
</div>
