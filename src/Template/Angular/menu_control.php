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
//  If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//  under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//  License agreement and license key will be shipped with the order
//  confirmation.
?>

<!-- Desktop menu button -->
<div class="hidden-md-down dropdown-icon-menu position-relative">
    <a href="javascript:void(0);" class="header-btn btn js-waves-off" data-action="toggle" ng-click="toggleMenuHidden()"
       data-class="nav-function-hidden" title="Hide Navigation">
        <i class="fas fa-bars"></i>
    </a>
    <ul>
        <li>
            <a href="javascript:void(0);" class="btn js-waves-off" data-action="toggle" data-class="nav-function-minify"
               ng-click="toggleMenuMinify()" title="Minify Navigation">
                <i class="far fa-caret-square-left"></i>
            </a>
        </li>
        <li>
            <a href="javascript:void(0);" class="btn js-waves-off" data-action="toggle" data-class="nav-function-fixed"
               ng-click="toggleMenuFixed()" title="Lock Navigation">
                <i class="fas fa-lock"></i>
            </a>
        </li>
    </ul>
    <div class="hidden-lg-up">
        <a href="javascript:void(0);" class="header-btn btn press-scale-down" data-action="toggle" data-class="mobile-nav-on">
            <i class="fas fa-bars"></i>
        </a>
    </div>
</div>


<!-- Mobile menu button -->
<div class="hidden-lg-up">
    <a href="#" class="header-btn btn press-scale-down" data-action="toggle" data-class="mobile-nav-on">
        <i class="ni ni-menu"></i>
    </a>
</div>
