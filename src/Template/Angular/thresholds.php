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

<div class="threshold-container padding-top-10">

    <!-- Range Inclusive (≥ 10 and ≤ 20 - inside the range of 10 to 20) -->
    <div class="subcontainer-{{type}}" ng-if="!inclusive">
        <div class="value-container">
            <div class="values-left-container">
                <div class="speech-bubble-ds">
                    <p>{{min}}</p>
                    <div class="speech-bubble-ds-arrow-left"></div>
                </div>
            </div>
            <div class="values-right-container">
                <div class="speech-bubble-ds">
                    <p>{{max}}</p>
                    <div class="speech-bubble-ds-arrow-right"></div>
                </div>
            </div>
        </div>

        <div class="label-container">
            <div class="label-left"></div>
            <div class="label-right"></div>
        </div>
    </div>

    <!-- Range Exclusive (< 10 or > 20 - outside the range of 10 to 20) -->
    <div class="subcontainer-{{type}}-inclusive" ng-if="inclusive">
        <div class="value-container">
            <div class="values-left-container">
                <div class="speech-bubble-ds">
                    <p>{{min}}</p>
                    <div class="speech-bubble-ds-arrow-left"></div>
                </div>
            </div>
            <div class="values-right-container">
                <div class="speech-bubble-ds">
                    <p>{{max}}</p>
                    <div class="speech-bubble-ds-arrow-right"></div>
                </div>
            </div>
        </div>
        <div class="label-container">
            <div class="label-left-inclusive"></div>
            <div class="label-right-inclusive"></div>
        </div>
    </div>
</div>


