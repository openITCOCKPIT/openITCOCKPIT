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
<ol class="breadcrumb page-breadcrumb">
    <li class="breadcrumb-item">
        <a ui-sref="DashboardsIndex">
            <i class="fa fa-home"></i> <?php echo __('Home'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <a ui-sref="TimeperiodsIndex">
            <i class="fa fa-clock-o"></i> <?php echo __('Time periods'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <i class="fas fa-calendar-week"></i> <?php echo __('View details'); ?>
    </li>
</ol>


<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?php echo __('Time period:'); ?>
                    <span class="fw-300">
                        <i>
                            {{timeperiod.name}}
                        </i>
                    </span>
                </h2>
                <div class="panel-toolbar">
                    <div class="text-muted cursor-default d-none d-sm-none d-md-none d-lg-block margin-right-10">
                        UUID: {{timeperiod.uuid}}
                    </div>
                    <?php if ($this->Acl->hasPermission('index', 'timeperiods')): ?>
                        <a back-button href="javascript:void(0);" fallback-state='TimeperiodsIndex'
                           class="btn btn-default btn-xs mr-1 shadow-0">
                            <i class="fas fa-long-arrow-alt-left"></i> <?php echo __('Back'); ?>
                        </a>
                    <?php endif; ?>
                    <?php if ($this->Acl->hasPermission('edit', 'timeperiods')): ?>
                        <button class="btn btn-xs btn-default mr-1 shadow-0"
                                ui-sref="TimeperiodsEdit({id: id})">
                            <i class="fas fa-cog"></i> <?php echo __('Edit'); ?>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <div class="row padding-top-20">
                        <div class="col-lg-12">
                            <div id="calendar">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
