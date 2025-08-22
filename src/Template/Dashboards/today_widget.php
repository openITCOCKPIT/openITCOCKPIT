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
    <div class="col-12 align-items-top d-flex justify-content-center padding-right-25 padding-left-25">
        <div class="card rounded-plus text-center h-100 w-75 calendar-card-shadow">
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
</div>
