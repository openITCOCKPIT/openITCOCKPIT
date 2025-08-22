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
        <a ui-sref="BackupsIndex">
            <i class="fa fa-database"></i> <?php echo __('Backups'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <?php echo __('Backup / Restore'); ?>
    </li>
</ol>

<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?php echo __('Backup management'); ?>
                </h2>
            </div>
            <div class="panel-container show margin-bottom-25">
                <div class="panel-content">

                    <form ng-submit="restore()" class="form-horizontal col-xl-9">

                        <div class="form-group required">
                            <label class="col-xs-12 control-label" for="backupFiles">
                                <?php echo __('Backupfile for Restore'); ?>
                            </label>
                            <div class="col-xs-12">
                                <select
                                    id="backupFiles"
                                    data-placeholder="<?php echo __('Please choose'); ?>"
                                    class="form-control"
                                    chosen="backupFiles"
                                    ng-options="key as value for (key, value) in backupFiles"
                                    ng-model="selectedBackup">
                                </select>
                            </div>
                        </div>

                        <div class="card margin-top-20">
                            <div class="card-body">
                                <div class="float-right">
                                    <?php if ($this->Acl->hasPermission('deleteBackupFile', 'backups')): ?>
                                        <a ng-click="delete()"
                                           class="btn btn-danger waves-effect waves-themed txt-color-white"
                                           ng-disabled="isActionRunning">
                                            <?php echo __('Delete file'); ?>
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($this->Acl->hasPermission('downloadBackupFile', 'backups')): ?>
                                        <a ng-click="download()"
                                           class="btn btn-primary waves-effect waves-themed txt-color-white"
                                           ng-disabled="isActionRunning">
                                            <?php echo __('Download file'); ?>
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($this->Acl->hasPermission('restore', 'backups')): ?>
                                        <button type="submit" class="btn btn-primary waves-effect waves-themed"
                                                ng-disabled="isActionRunning">
                                            <i class="fa fa-spin fa-spinner" ng-show="restoreRunning"></i>
                                            <?php echo __('Start Restore'); ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                    </form>

                    <?php if ($this->Acl->hasPermission('backup', 'backups')): ?>
                        <form ng-submit="backup()" class="form-horizontal margin-top-50 col-xl-9">

                            <div class="form-group">
                                <label class="col-xs-12 control-label"
                                       for="filenameForBackup">
                                    <?php echo __('Filename for Backup'); ?>
                                </label>

                                <div class="col-xs-12">
                                    <input
                                        id="filenameForBackup"
                                        class="form-control"
                                        type="text"
                                        ng-model="filenameForBackup">
                                </div>
                            </div>

                            <div class="card margin-top-20">
                                <div class="card-body">
                                    <div class="float-right">
                                        <button type="submit" class="btn btn-primary waves-effect waves-themed"
                                                ng-disabled="isActionRunning">
                                            <i class="fa fa-spin fa-spinner" ng-show="backupRunning"></i>
                                            <?php echo __('Start Backup'); ?>
                                        </button>
                                    </div>
                                </div>
                            </div>

                        </form>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</div>

<reload-required></reload-required>
