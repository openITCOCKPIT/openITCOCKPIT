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


<div class="jumbotron text-center">
    <div id="notFoundSvg">
        <svg class="scaling-svg">
            <symbol id="not-found-text">
                <text text-anchor="middle"
                      x="50%"
                      y="80%"
                      class="textline"
                      fill="none" stroke="#a94442">
                    <?php echo __('403 Forbidden'); ?>
                </text>
            </symbol>
            <g class="g-ants">
                <use xlink:href="#not-found-text"
                     class="text-add"></use>
                <use xlink:href="#not-found-text"
                     class="text-add"></use>
                <use xlink:href="#not-found-text"
                     class="text-add"></use>
                <use xlink:href="#not-found-text"
                     class="text-add"></use>
                <use xlink:href="#not-found-text"
                     class="text-add"></use>
            </g>
        </svg>
    </div>
    <h1><?php //echo __('Forbidden...'); ?></h1>
    <h2>
        <?php echo __('You do not have the required permissions.'); ?>
    </h2>
</div>

