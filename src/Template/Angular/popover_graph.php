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

<div class="popup-graph-container" id="serviceGraphContainer-{{graphPopoverId}}">
    <div class="row">
        <div class="col-sm-12 col-md-12 col-lg-6" ng-show="isLoadingGraph">
            <div class="text-center padding-top-20 padding-bottom-20" style="width:100%;" ng-show="isLoadingGraph">
                <i class="fa fa-refresh fa-4x fa-spin"></i>
            </div>
        </div>

        <div class="col-sm-12 col-md-12 col-lg-6"
             ng-repeat="(index, value) in popoverPerfdata"
             ng-if="index < 4"
             ng-class="{'col-lg-12': popoverPerfdata.length === 1}">
            <div class="text-center bold word-wrap-anywhere">{{value.datasource.name}}</div>
            <div id="serviceGraphUPlot-{{graphPopoverId}}-{{index}}" class="serviceGraphUPlot"></div>
        </div>
    </div>
</div>
