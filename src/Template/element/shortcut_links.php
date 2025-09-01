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
<div class="modal fade modal-backdrop-transparent" id="modal-shortcut" tabindex="-1" role="dialog"
     aria-labelledby="modal-shortcut" aria-hidden="true">
    <div class="modal-dialog modal-dialog-top modal-transparent" role="document">
        <div class="modal-content">
            <div class="modal-body">
                <ul class="app-list w-auto h-auto p-0 text-left">
                    <!-- dynamic button -->
                    <li>
                        <a ui-sref="DashboardsIndex" class="app-list-item text-white border-0 m-0">
                            <div class='icon-stack'>
                                <i class="base base-7 icon-stack-3x opacity-100 color-success-500"></i>
                                <i class="fas fa-link icon-stack-1x opacity-100 color-fusion-900"></i>
                            </div>
                            <span class="app-list-name">
                                Dashboard
                            </span>
                        </a>
                    </li>
                    <li>
                        <a href="https://openitcockpit.io" target="_blank"
                           class="app-list-item text-white border-0 m-0">
                            <div class='icon-stack'>
                                <i class="base base-7 icon-stack-3x opacity-100 color-success-500"></i>
                                <i class="fas fa-external-link-alt icon-stack-1x opacity-100 color-fusion-900"></i>
                            </div>
                            <span class="app-list-name" title="openITCOCKPIT.io">
                                openITCOCKPIT.io
                            </span>
                        </a>
                    </li>
                    <!-- static button -->
                    <li style="display: none;">
                        <a href="javascript:void(0);" class="app-list-item linkListAddMore text-white border-0 m-0">
                            <div class="icon-stack">
                                <i class="base base-7 icon-stack-3x opacity-100 color-primary-300"></i>
                                <i class="fas fa-plus icon-stack-1x opacity-100 color-white"></i>
                            </div>
                            <span class="app-list-name">
                                <?php echo __('Add More'); ?>
                            </span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
