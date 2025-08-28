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

<div class="row h-100">
    <div class="col-4">
        <div class="card rounded-plus text-center h-100 w-100 calendar-card-shadow">
            <div class="card-header bg-primary text-white font-md h-25 align-items-center d-flex justify-content-center day-card-header-radius">
                {{dateDetails.monthName}}
            </div>
            <div id="day-{{widget.id}}"
                 class="card-body align-items-center d-flex justify-content-center no-padding"
                 style="font-size: {{fontSize}}px;">
                {{dateDetails.dayNumber}}

            </div>
            <div
                class="card-footer bg-primary text-center text-white font-sm h-25 align-items-center d-flex justify-content-center day-card-footer-radius">
                {{dateDetails.weekday}}
            </div>
        </div>
    </div>

    <div class="col-8">
        <table class="table w-100 small table-sm margin-0">
            <thead>
            <tr>
                <th></th>
                <th ng-repeat="weekdayName in dateDetails.weekdayNames">
                    {{weekdayName}}
                </th>
            </tr>
            </thead>
            <tbody>
            <tr ng-repeat="week in dateDetails.days">
                <th class="text-primary">{{week.cw}}</th>
                <td ng-repeat="dayDetail in week.days"
                    ng-class="{'bg-primary-20-alpha': dayDetail.weekday === 6, 'bg-primary-40-alpha': dayDetail.weekday === 7}">
                    <span ng-class="{'badge badge-primary': dayDetail.day === dateDetails.dayNumber}">
                        {{dayDetail.day}}
                    </span>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
</div>
