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
        <a ui-sref="ProxyIndex">
            <i class="fas fa-globe"></i> <?php echo __('HTTP-Proxy'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <i class="fa fa-list"></i> <?php echo __('Configuration'); ?>
    </li>
</ol>

<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?php echo __('HTTP-Proxy'); ?>
                    <span class="fw-300"><i><?php echo __('Edit configuration'); ?></i></span>
                </h2>
                <div class="panel-toolbar">

                </div>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <form ng-submit="submit();" class="form-horizontal"
                          ng-init="successMessage=
            {objectName : '<?php echo __('Proxy configuration'); ?>' , message: '<?php echo __('updated successfully'); ?>'}">

                        <div class="form-group required" ng-class="{'has-error':errors.ipaddress}">
                            <label class="control-label">
                                <?php echo __('IP address'); ?>
                            </label>
                            <input
                                ng-model="post.Proxy.ipaddress"
                                class="form-control"
                                maxlength="255"
                                type="text"
                                id="SatelliteAddress">
                            <div ng-repeat="error in errors.ipaddress">
                                <div class="help-block text-danger">{{ error }}</div>
                            </div>
                        </div>

                        <div class="form-group required" ng-class="{'has-error':errors.port}">
                            <label class="control-label">
                                <?php echo __('Port'); ?>
                            </label>
                            <input
                                ng-model="post.Proxy.port"
                                class="form-control"
                                maxlength="255"
                                type="text"
                                id="SatelliteAddress">
                            <div ng-repeat="error in errors.port">
                                <div class="help-block text-danger">{{ error }}</div>
                            </div>
                        </div>

                        <div class="form-group padding-top-10">
                            <div class="custom-control custom-checkbox  margin-bottom-10">
                                <input type="checkbox"
                                       class="custom-control-input"
                                       ng-true-value="1"
                                       ng-false-value="0"
                                       id="proxyEnabled"
                                       ng-model="post.Proxy.enabled">
                                <label class="custom-control-label" for="proxyEnabled">
                                    <?php echo __('Enable proxy'); ?>
                                </label>
                            </div>
                            <div class="help-block">
                                <?php echo __('If disabled the proxy server will not be used.'); ?>
                            </div>
                        </div>
                        <div class="card margin-top-10">
                            <div class="card-body">
                                <div class="float-right">
                                    <button class="btn btn-primary" type="submit">
                                        <?php echo __('Save configuration'); ?>
                                    </button>
                                    <a back-button href="javascript:void(0);" fallback-state='DashboardsIndex'
                                       class="btn btn-default">
                                        <?php echo __('Cancel'); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
