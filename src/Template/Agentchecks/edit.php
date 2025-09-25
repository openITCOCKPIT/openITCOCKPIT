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
        <a ui-sref="AgentconnectorsWizard">
            <i class="fa fa-user-secret"></i> <?php echo __('openITCOCKPIT Agent'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <a ui-sref="AgentchecksIndex">
            <?php echo __('Checks'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <?php echo __('Edit'); ?>
    </li>
</ol>

<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?php echo __('Edit openITCOCKPIT Agent check:'); ?>
                    <span class="fw-300"><i>{{post.Agentcheck.name}}</i></span>
                </h2>
                <div class="panel-toolbar">
                    <button class="btn btn-xs btn-default shadow-0" ui-sref="AgentchecksIndex">
                        <i class="fa fa-arrow-left"></i>
                        <?php echo __('Back'); ?>
                    </button>
                </div>
            </div>
            <div class="panel-container show">
                <div class="panel-content">

                    <form ng-submit="submit();" class="form-horizontal"
                          ng-init="successMessage=
            {objectName : '<?php echo __('Agent check'); ?>' , message: '<?php echo __('saved successfully'); ?>'}">

                        <div class="form-group required" ng-class="{'has-error': errors.name}">
                            <label class="col col-md-2 control-label">
                                <?php echo __('Name'); ?>
                            </label>
                            <div class="col col-xs-10">
                                <input class="form-control" type="text" ng-model="post.Agentcheck.name">
                                <div ng-repeat="error in errors.name">
                                    <div class="help-block text-danger">{{ error }}</div>
                                </div>
                                <div class="help-block">
                                    <?php echo __('The name of the check is equal to the key from the agents JSON output.'); ?>
                                </div>
                            </div>
                        </div>

                        <div class="form-group required" ng-class="{'has-error': errors.plugin_name}">
                            <label class="col col-md-2 control-label">
                                <?php echo __('Plugin name'); ?>
                            </label>
                            <div class="col col-xs-10">
                                <input class="form-control" type="text" ng-model="post.Agentcheck.plugin_name">
                                <div ng-repeat="error in errors.plugin_name">
                                    <div class="help-block text-danger">{{ error }}</div>
                                </div>
                                <div class="help-block">
                                    <?php echo __('The name of the check plugin used by the poller.'); ?>
                                </div>
                            </div>
                        </div>

                        <div class="form-group required" ng-class="{'has-error': errors.servicetemplate_id}">
                            <label class="col-xs-12 col-lg-2 control-label">
                                <?php if ($this->Acl->hasPermission('edit', 'servicetemplates')): ?>
                                    <a ui-sref="ServicetemplatesEdit({id:post.Agentcheck.servicetemplate_id})">
                                        <?php echo __('Service template'); ?>
                                    </a>
                                <?php else: ?>
                                    <?php echo __('Service template'); ?>
                                <?php endif; ?>
                            </label>
                            <div class="col col-xs-10">
                                <select data-placeholder="<?php echo __('Please choose'); ?>"
                                        class="form-control"
                                        chosen="servicetemplates"
                                        ng-options="servicetemplate.key as servicetemplate.value for servicetemplate in servicetemplates"
                                        ng-model="post.Agentcheck.servicetemplate_id">
                                </select>
                                <div ng-repeat="error in errors.servicetemplate_id">
                                    <div class="help-block text-danger">{{ error }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="card margin-top-20">
                            <div class="card-body">
                                <div class="float-right">
                                    <button type="submit" class="btn btn-primary waves-effect waves-themed">
                                        <?php echo __('Update agent check'); ?>
                                    </button>
                                    <a back-button href="javascript:void(0);" fallback-state='AgentchecksIndex'
                                       class="btn btn-default"><?php echo __('Cancel'); ?></a>
                                </div>
                            </div>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>
