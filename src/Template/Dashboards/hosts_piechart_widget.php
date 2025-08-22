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

use itnovum\openITCOCKPIT\Core\RFCRouter;

?>
<div class="row no-padding">
    <div class="col-xs-12 col-lg-12 text-center">

        <img ng-src="/angular/getPieChart/{{hoststatusCount[0]}}/{{hoststatusCount[1]}}/{{hoststatusCount[2]}}.png">

        <div class="row text-center padding-bottom-10 font-xs">

            <?php if ($this->Acl->hasPermission('index', 'Hosts', '')): ?>
                <div class="col-xs-12 col-md-4 col-lg-4 no-padding">
                    <a ui-sref="HostsIndex({hoststate: [0], sort: 'Hoststatus.last_state_change', direction: 'desc'})">
                        <i class="fa fa-square up"></i>
                        {{hoststatusCount[0]}} ({{hoststatusCountPercentage[0]}} %)
                    </a>
                </div>
            <?php else: ?>
                <div class="col-xs-12 col-md-4 col-lg-4">
                    <i class="fa fa-square up"></i>
                    {{hoststatusCount[0]}} ({{hoststatusCountPercentage[0]}} %)
                </div>
            <?php endif; ?>

            <?php if ($this->Acl->hasPermission('index', 'Hosts', '')): ?>
                <div class="col-xs-12 col-md-4 col-lg-4 no-padding">
                    <a ui-sref="HostsIndex({hoststate: [1], sort: 'Hoststatus.last_state_change', direction: 'desc'})">
                        <i class="fa fa-square down"></i>
                        {{hoststatusCount[1]}} ({{hoststatusCountPercentage[1]}} %)
                    </a>
                </div>
            <?php else: ?>
                <div class="col-xs-12 col-md-4 col-lg-4">
                    <i class="fa fa-square down"></i>
                    {{hoststatusCount[1]}} ({{hoststatusCountPercentage[1]}} %)
                </div>
            <?php endif; ?>

            <?php if ($this->Acl->hasPermission('index', 'Hosts', '')): ?>
                <div class="col-xs-12 col-md-4 col-lg-4 no-padding">
                    <a ui-sref="HostsIndex({hoststate: [2], sort: 'Hoststatus.last_state_change', direction: 'desc'})">
                        <i class="fa fa-square unreachable"></i>
                        {{hoststatusCount[2]}} ({{hoststatusCountPercentage[2]}} %)
                    </a>
                </div>
            <?php else: ?>
                <div class="col-xs-12 col-md-4 col-lg-4">
                    <i class="fa fa-square unreachable"></i>
                    {{hoststatusCount[2]}} ({{hoststatusCountPercentage[2]}} %)
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>
