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

<div class="row padding-top-5 padding-bottom-20">
    <div class="col-lg-6">
        <div class="form-group">
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fa fa-filter"></i></span>
                </div>
                <div class="col tagsinputFilter">
                    <input class="form-control tagsinput"
                           data-role="tagsinput"
                           type="text"
                           placeholder="<?php echo __('Filter by service name'); ?>"
                           ng-model="search.name">
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <span ng-click="selectAll()"
              class="pointer padding-right-20 align-middle">
            <i class="fas fa-lg fa-check-square"></i>
            <?php echo __('Select all'); ?>
        </span>
        <span ng-click="undoSelection()" class="pointer align-middle">
            <i class="fas fa-lg fa-square"></i>
            <?php echo __('Undo selection'); ?>
        </span>
    </div>
</div>
