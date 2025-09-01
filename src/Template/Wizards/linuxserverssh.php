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
        <a ui-sref="WizardsIndex">
            <i class="fa-solid fa-wand-magic-sparkles"></i> <?php echo __('Wizards'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <i class="fa-solid fa-wand-magic-sparkles"></i> <?php echo __('Linux Server'); ?>
    </li>
</ol>

<div class="row">
    <div class="col-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?php echo __('Configuration Wizard: Linux Server (SSH)'); ?>
                </h2>
            </div>
            <div class="panel-container show">
                <div class="panel-content fuelux">

                    <form ng-submit="submit();" class="form-horizontal">

                        <div class="wizard">
                            <ul class="nav nav-tabs step-anchor">
                                <li class="active">
                                    <span class="badge badge-info">1</span><?php echo __('Linux Server Information'); ?>
                                    <span class="chevron"></span>
                                </li>
                                <li>
                                    <span class="badge">2</span><?php echo __('Contact configuration'); ?>
                                    <span class="chevron"></span>
                                </li>
                                <li>
                                    <span class="badge">3</span><?php echo __('Configuration overview'); ?>
                                    <span class="chevron"></span>
                                </li>
                            </ul>
                            <div class="pull-right margin-right-5" style="margin-top: -39px;">
                                <div class="actions" style="position: relative; display: inline;">
                                    <button type="submit" class="btn btn-sm btn-success">
                                        <?php echo __('Next'); ?><i class="fa fa-arrow-right"></i>
                                    </button>
                                </div>
                            </div>
                        </div>


                        <div class="step-content">
                            <div class="step-pane active padding-20" id="step1">
                                <h6 class="txt-color-blueDark"><?php echo __('Main server configuration'); ?></h6>
                                <div>
                                    <div class="form-group required" ng-class="{'has-error':errors.login}">
                                        <label class="control-label" for="Login">
                                            <?php echo __('Login'); ?>
                                        </label>
                                        <input ng-model="post.ssh.login"
                                               class="form-control"
                                               maxlength="255"
                                               placeholder="nagios"
                                               type="text"
                                               id="Login">
                                        <div ng-repeat="error in errors.login">
                                            <div class="help-block text-danger">{{ error }}</div>
                                        </div>
                                    </div>
                                    <div class="form-group required" ng-class="{'has-error':errors.port}">
                                        <label class="control-label" for="Port">
                                            <?php echo __('Port'); ?>
                                        </label>
                                        <input ng-model="post.ssh.port"
                                               class="form-control"
                                               placeholder="22"
                                               type="number"
                                               min="0"
                                               max="65535"
                                               step="1"
                                               id="Port">
                                        <div ng-repeat="error in errors.port">
                                            <div class="help-block text-danger">{{ error }}</div>
                                        </div>
                                        <div class="help-block">
                                            <?= __('SSH port of remote machine.') ?>
                                        </div>
                                    </div>
                                    <div class="form-group required" ng-class="{'has-error':errors.private_key_path}">
                                        <label class="control-label" for="PrivateKeyPath">
                                            <?php echo __('PrivateKeyPath'); ?>
                                        </label>
                                        <input ng-model="post.ssh.private_key_path"
                                               class="form-control"
                                               placeholder="/var/lib/nagios/.ssh/id_rsa"
                                               type="text"
                                               id="PrivateKeyPath">
                                        <div ng-repeat="error in errors.private_key_path">
                                            <div class="help-block text-danger">{{ error }}</div>
                                        </div>
                                        <div class="help-block">
                                            <?= __('We will use a default path to private key if this field left empty') ?>
                                        </div>
                                    </div>
                                    <div class="form-group" ng-class="{'has-error':errors.timeout}">
                                        <label class="control-label" for="Timeout">
                                            <?php echo __('Timeout'); ?>
                                        </label>
                                        <input ng-model="post.ssh.timeout"
                                               class="form-control"
                                               placeholder="60"
                                               type="text"
                                               id="Timeout">
                                        <div ng-repeat="error in errors.timeout">
                                            <div class="help-block text-danger">{{ error }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>














