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
<div class="btn-group">
    <button data-toggle="dropdown" class="btn dropdown-toggle btn-xs btn-success">
        <span><i class="fa fa-plus"></i> <?php echo __('Create downtime'); ?></span> <i
                class="fa fa-caret-down"></i>
    </button>
    <ul class="dropdown-menu pull-right">
        <?php if ($this->Acl->hasPermission('addHostdowntime', 'systemdowntimes')): ?>
            <li>
                <a ui-sref="SystemdowntimesAddHostdowntime">
                    <i class="fa fa-desktop"></i>
                    <?php echo __('Create host downtime'); ?>
                </a>
            </li>
        <?php endif; ?>
        <?php if ($this->Acl->hasPermission('addServicedowntime', 'systemdowntimes')): ?>
            <li>
                <a ui-sref="SystemdowntimesAddServicedowntime">
                    <i class="fa fa-cog"></i>
                    <?php echo __('Create service downtime'); ?>
                </a>
            </li>
        <?php endif; ?>
        <?php if ($this->Acl->hasPermission('addHostdowntime', 'systemdowntimes')): ?>
            <li>
                <a ui-sref="SystemdowntimesAddHostgroupdowntime">
                    <i class="fa fa-sitemap"></i>
                    <?php echo __('Create host group downtime'); ?>
                </a>
            </li>
        <?php endif; ?>
        <?php if ($this->Acl->hasPermission('addHostdowntime', 'systemdowntimes')): ?>
            <li>
                <a ui-sref="SystemdowntimesAddContainerdowntime">
                    <i class="fa fa-link"></i>
                    <?php echo __('Create container downtime'); ?>
                </a>
            </li>
        <?php endif; ?>
    </ul>
</div>

