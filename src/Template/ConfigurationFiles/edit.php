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
        <a ui-sref="ConfigurationFilesIndex">
            <i class="fa fa-file-text"></i> <?php echo __('Configuration file'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <i class="fa fa-edit"></i> <?php echo __('Edit'); ?>
    </li>
</ol>

<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?php echo __('Edit configuration file '); ?>
                    <span class="fw-300"><i>{{ ConfigFile.linkedOutfile }}</i></span>
                </h2>
                <div class="panel-toolbar">
                    <button class="btn btn-xs btn-primary mr-1 shadow-0" ng-click="askRestoreDefault()">
                        <i class="fa fa-recycle"></i> <?php echo __('Restore default'); ?>
                    </button>
                    <?php if ($this->Acl->hasPermission('index', 'services')): ?>
                        <a back-button href="javascript:void(0);" fallback-state='ConfigurationFilesIndex' class="btn btn-default btn-xs mr-1 shadow-0">
                            <i class="fas fa-long-arrow-alt-left"></i> <?php echo __('Back'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <div class="frame-wrap">
                    <!-- Loading used AngularJs directives dynamically -->
                    <ng-include
                        ng-if="ConfigFile.angularDirective"
                        src="'/ConfigurationFiles/dynamicDirective?directive='+ConfigFile.angularDirective"></ng-include>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Confirm restore modal -->
<div id="angularConfirmRestoreDefault" class="modal" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary txt-color-white">
                <h5 class="modal-title">
                    <i class="fa fa-edit"></i>
                    <?php echo __('Attention!'); ?>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="fa fa-times"></i></span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-lg-12">
                        <?php echo __('Do you really want to restore default configuration? All manual changes will be lost.'); ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" ng-click="restoreDefault(ConfigFile.dbKey)" data-dismiss="modal">
                    <i class="fa fa-refresh fa-spin" ng-show="isRestoring"></i>
                    <?php echo __('Restore default'); ?>
                </button>
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <?php echo __('Close'); ?>
                </button>
            </div>
        </div>
    </div>
</div>
