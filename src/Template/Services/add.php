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

use Cake\Core\Plugin;

?>
<ol class="breadcrumb page-breadcrumb">
    <li class="breadcrumb-item">
        <a ui-sref="DashboardsIndex">
            <i class="fa fa-home"></i> <?php echo __('Home'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <a ui-sref="ServicesIndex">
            <i class="fa fa-cog"></i> <?php echo __('Services'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <i class="fa fa-plus"></i> <?php echo __('Add'); ?>
    </li>
</ol>


<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?php echo __('Create new service'); ?>
                </h2>
                <div class="panel-toolbar">
                    <?php if ($this->Acl->hasPermission('index', 'services')): ?>
                        <a back-button href="javascript:void(0);" fallback-state='ServicesIndex'
                           class="btn btn-default btn-xs mr-1 shadow-0">
                            <i class="fas fa-long-arrow-alt-left"></i> <?php echo __('Back'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <form ng-submit="submit();" class="form-horizontal"
                          ng-init="successMessage=
            {objectName : '<?php echo __('Service'); ?>' , message: '<?php echo __('created successfully'); ?>'}">

                        <!-- BASIC CONFIGURATION START -->

                        <div class="card margin-bottom-10">
                            <div class="card-header">
                                <i class="fa-solid fa-wand-magic-sparkles"></i> <?php echo __('Basic configuration'); ?>
                            </div>
                            <div class="card-body">
                                <div class="form-group required" ng-class="{'has-error': errors.host_id}">
                                    <label class="control-label" for="ServiceHosts">
                                        <?php if ($this->Acl->hasPermission('edit', 'hosts')): ?>
                                            <a ui-sref="HostsEdit({id:post.Service.host_id})"
                                               ng-if="post.Service.host_id > 0">
                                                <?php echo __('Host'); ?>
                                            </a>
                                            <span ng-if="post.Service.host_id == 0"><?php echo __('Host'); ?></span>
                                        <?php else: ?>
                                            <?php echo __('Host'); ?>
                                        <?php endif; ?>
                                    </label>
                                    <select
                                        id="ServiceHosts"
                                        data-placeholder="<?php echo __('Please choose'); ?>"
                                        class="form-control"
                                        chosen="hosts"
                                        callback="loadHosts"
                                        ng-options="host.key as host.value for host in hosts"
                                        ng-model="post.Service.host_id">
                                    </select>
                                    <div ng-show="post.Service.host_id < 1" class="warning-glow">
                                        <?php echo __('Please select a host.'); ?>
                                    </div>
                                    <div ng-repeat="error in errors.host_id">
                                        <div class="help-block text-danger">{{ error }}</div>
                                    </div>
                                </div>

                                <div class="form-group required"
                                     ng-class="{'has-error': errors.servicetemplate_id}">
                                    <label class="control-label">
                                        <?php if ($this->Acl->hasPermission('edit', 'servicetemplates')): ?>
                                            <a ui-sref="ServicetemplatesEdit({id:post.Service.servicetemplate_id})"
                                               ng-if="post.Service.servicetemplate_id > 0">
                                                <?php echo __('Service template'); ?>
                                            </a>
                                            <span
                                                ng-if="post.Service.servicetemplate_id == 0"><?php echo __('Service template'); ?></span>
                                        <?php else: ?>
                                            <?php echo __('Service template'); ?>
                                        <?php endif; ?>
                                    </label>
                                    <select
                                        id="ServiceServicetemplateSelect"
                                        data-placeholder="<?php echo __('Please choose'); ?>"
                                        class="form-control"
                                        chosen="servicetemplates"
                                        ng-options="servicetemplate.key as servicetemplate.value for servicetemplate in servicetemplates"
                                        ng-model="post.Service.servicetemplate_id">
                                    </select>
                                    <div ng-show="post.Service.host_id > 0 && post.Service.servicetemplate_id < 1"
                                         class="warning-glow">
                                        <?php echo __('Please select a service template.'); ?>
                                    </div>
                                    <div ng-repeat="error in errors.servicetemplate_id">
                                        <div class="help-block text-danger">{{ error }}</div>
                                    </div>
                                </div>

                                <div class="form-group required" ng-class="{'has-error': errors.name}">
                                    <label class="control-label">
                                        <?php echo __('Service name'); ?>
                                    </label>
                                    <input
                                        id="ServiceName"
                                        class="form-control"
                                        type="text"
                                        ng-model="post.Service.name"
                                        ng-blur="checkForDuplicateServicename();">
                                    <div ng-repeat="error in errors.name">
                                        <div class="help-block text-danger">{{ error }}</div>
                                    </div>
                                </div>

                                <div class="alert alert-warning" role="alert" ng-show="isServicenameInUse">
                                    <div class="d-flex align-items-center">
                                        <div class="alert-icon width-3">
                                            <div class='icon-stack  icon-stack-sm'>
                                                <i class="base base-9 icon-stack-3x opacity-100 color-warning-600"></i>
                                                <i class="fas fa-exclamation-circle icon-stack-1x opacity-100 color-white"></i>
                                            </div>
                                        </div>
                                        <div class="flex-1">
                                            <?= __('A service with the name <strong>{0}</strong> already exists on this host. Duplicate service names could lead to confusion.', '{{ checkedName }}'); ?>
                                        </div>
                                    </div>
                                </div>

                                <div ng-show="post.Service.servicetemplate_id">
                                    <div class="form-group" ng-class="{'has-error': errors.description}">
                                        <label class="control-label">
                                            <?php echo __('Description'); ?>
                                        </label>
                                        <div class="input-group">
                                            <input
                                                class="form-control"
                                                type="text"
                                                ng-model="post.Service.description">

                                            <template-diff ng-show="post.Service.servicetemplate_id"
                                                           value="post.Service.description"
                                                           template-value="servicetemplate.Servicetemplate.description">
                                            </template-diff>
                                        </div>
                                        <div ng-repeat="error in errors.description">
                                            <div class="help-block text-danger">{{ error }}</div>
                                        </div>
                                    </div>

                                    <div class="form-group" ng-class="{'has-error': errors.servicegroups}">
                                        <label class="control-label">
                                            <?php echo __('Service groups'); ?>
                                        </label>
                                        <div class="input-group">
                                            <select
                                                id="ServicegroupsSelect"
                                                data-placeholder="<?php echo __('Please choose'); ?>"
                                                class="custom-select"
                                                chosen="servicegroups"
                                                multiple
                                                ng-options="servicegroup.key as servicegroup.value for servicegroup in servicegroups"
                                                ng-model="post.Service.servicegroups._ids">
                                            </select>
                                            <template-diff ng-show="post.Service.servicetemplate_id"
                                                           value="post.Service.servicegroups._ids"
                                                           template-value="servicetemplate.Servicetemplate.servicegroups._ids"></template-diff>
                                        </div>
                                        <div ng-repeat="error in errors.servicegroups">
                                            <div class="help-block text-danger">{{ error }}</div>
                                        </div>
                                    </div>


                                    <div class="form-group" ng-class="{'has-error': errors.tags}">
                                        <label class="control-label">
                                            <?php echo __('Tags'); ?>
                                        </label>
                                        <div class="input-group">
                                            <div class="col-lg padding-left-0 padding-right-23">
                                                <input class="form-control tagsinput"
                                                       data-role="tagsinput"
                                                       type="text"
                                                       ng-model="post.Service.tags"
                                                       id="ServiceTagsInput">
                                            </div>
                                            <div style="margin-left: -23px;">
                                                <template-diff ng-show="post.Service.servicetemplate_id"
                                                               value="post.Service.tags"
                                                               template-value="servicetemplate.Servicetemplate.tags"
                                                               callback="restoreTemplateTags"></template-diff>
                                            </div>
                                        </div>
                                        <div ng-repeat="error in errors.tags">
                                            <div class="help-block text-danger">{{ error }}</div>
                                        </div>
                                        <div class="help-block">
                                            <?php echo __('Press return to separate tags'); ?>
                                        </div>
                                    </div>


                                    <div class="form-group" ng-class="{'has-error': errors.priority}">
                                        <label class="control-label">
                                            <?php echo __('Priority'); ?>
                                        </label>
                                        <div class="col-xs-12 col-lg-2">
                                            <priority-directive priority="post.Service.priority"
                                                                callback="setPriority"></priority-directive>
                                            <template-diff-button ng-show="post.Service.servicetemplate_id"
                                                                  value="post.Service.priority"
                                                                  template-value="servicetemplate.Servicetemplate.priority">
                                            </template-diff-button>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <!-- BASIC CONFIGURATION END -->

                        <!-- CHECK CONFIGURATION START -->

                        <div class="card margin-bottom-10" ng-show="post.Service.servicetemplate_id">
                            <div class="card-header">
                                <i class="fa fa-terminal"></i> <?php echo __('Check configuration'); ?>
                            </div>
                            <div class="card-body">
                                <div class="form-group required"
                                     ng-class="{'has-error': errors.check_period_id}">
                                    <label class="control-label">
                                        <?php if ($this->Acl->hasPermission('edit', 'timeperiods')): ?>
                                            <a ui-sref="TimeperiodsEdit({id:post.Service.check_period_id})">
                                                <?= __('Check period'); ?>
                                            </a>
                                        <?php else: ?>
                                            <?= __('Check period'); ?>
                                        <?php endif; ?>
                                    </label>
                                    <div class="input-group" style="width: 100%;">
                                        <select
                                            id="CheckPeriodSelect"
                                            data-placeholder="<?php echo __('Please choose'); ?>"
                                            class="form-control"
                                            chosen="checkperiods"
                                            ng-options="checkperiod.key as checkperiod.value for checkperiod in checkperiods"
                                            ng-model="post.Service.check_period_id">
                                        </select>
                                        <template-diff ng-show="post.Service.servicetemplate_id"
                                                       value="post.Service.check_period_id"
                                                       template-value="servicetemplate.Servicetemplate.check_period_id"></template-diff>
                                    </div>
                                    <div ng-repeat="error in errors.check_period_id">
                                        <div class="help-block text-danger">{{ error }}</div>
                                    </div>
                                </div>

                                <div class="form-group" ng-class="{'has-error': errors.active_checks_enabled}">
                                    <div class="custom-control custom-checkbox  margin-bottom-10"
                                         ng-class="{'has-error': errors.active_checks_enabled}">

                                        <input type="checkbox"
                                               class="custom-control-input"
                                               ng-true-value="1"
                                               ng-false-value="0"
                                               id="active_checks_enabled"
                                               ng-model="post.Service.active_checks_enabled">
                                        <label class="custom-control-label" for="active_checks_enabled">
                                            <?php echo __('Enable active checks'); ?>
                                        </label>
                                        <template-diff-button ng-show="post.Service.servicetemplate_id"
                                                              value="post.Service.active_checks_enabled"
                                                              template-value="servicetemplate.Servicetemplate.active_checks_enabled">
                                        </template-diff-button>
                                    </div>

                                    <div class="col col-xs-12 col-md-offset-2 help-block">
                                        <?php echo __('If disabled the check command won\'t be executed. This is useful if an external program sends state data to openITCOCKPIT.'); ?>
                                    </div>
                                </div>


                                <div class="form-group" ng-class="{'has-error': errors.freshness_checks_enabled}"
                                     ng-show="post.Service.active_checks_enabled == 0">
                                    <div class="custom-control custom-checkbox  margin-bottom-10"
                                         ng-class="{'has-error': errors.freshness_checks_enabled}">
                                        <input type="checkbox"
                                               class="custom-control-input"
                                               ng-true-value="1"
                                               ng-false-value="0"
                                               id="freshness_checks_enabled"
                                               ng-model="post.Service.freshness_checks_enabled">
                                        <label class="custom-control-label" for="freshness_checks_enabled">
                                            <?php echo __('Enable freshness check'); ?>
                                        </label>
                                        <template-diff-button ng-show="post.Service.servicetemplate_id"
                                                              value="post.Service.freshness_checks_enabled"
                                                              template-value="servicetemplate.Servicetemplate.freshness_checks_enabled">
                                        </template-diff-button>
                                    </div>
                                    <div class="help-block" ng-hide="post.Service.active_checks_enabled">
                                        <?php echo __('Due to active checking is disabled, this command will only be used as freshness check command.'); ?>
                                    </div>
                                </div>

                                <div class="form-group required" ng-class="{'has-error': errors.freshness_threshold}"
                                     ng-show="post.Service.active_checks_enabled == 0 && post.Service.freshness_checks_enabled == 1">
                                    <label class="col-xs-12 col-lg-2 control-label">
                                        <?php echo __('Freshness threshold'); ?>
                                    </label>
                                    <interval-input-directive
                                        interval="post.Service.freshness_threshold"></interval-input-directive>
                                    <div class="col-xs-12 col-lg-offset-2">
                                        <div ng-repeat="error in errors.freshness_threshold">
                                            <div class="help-block text-danger">{{ error }}</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group required"
                                     ng-class="{'has-error': errors.command_id}">
                                    <label class="control-label">
                                        <?php if ($this->Acl->hasPermission('edit', 'commands')): ?>
                                            <a ui-sref="CommandsEdit({id:post.Service.command_id})">
                                                <?= __('Check command'); ?>
                                            </a>
                                        <?php else: ?>
                                            <?= __('Check command'); ?>
                                        <?php endif; ?>
                                    </label>
                                    <div class="input-group" style="width: 100%;">
                                        <select
                                            id="ServiceCheckCommandSelect"
                                            data-placeholder="<?php echo __('Please choose'); ?>"
                                            class="form-control"
                                            chosen="commands"
                                            ng-options="command.key as command.value for command in commands"
                                            ng-model="post.Service.command_id">
                                        </select>
                                        <template-diff ng-show="post.Service.servicetemplate_id"
                                                       value="post.Service.command_id"
                                                       template-value="servicetemplate.Servicetemplate.command_id"></template-diff>
                                    </div>
                                    <div class="help-block" ng-hide="post.Service.active_checks_enabled">
                                        <?php echo __('Due to active checking is disabled, this command will only be used as freshness check command.'); ?>
                                    </div>
                                    <div ng-repeat="error in errors.command_id">
                                        <div class="help-block text-danger">{{ error }}</div>
                                    </div>
                                </div>

                                <div class="form-group"
                                     ng-class="{'has-error': errors.servicecommandargumentvalues}"
                                     ng-repeat="servicecommandargumentvalue in post.Service.servicecommandargumentvalues">
                                    <label class="col-xs-12 col-lg-offset-2 col-lg-10 control-label text-purple">
                                        {{servicecommandargumentvalue.commandargument.human_name}}
                                    </label>
                                    <div class="col-xs-12 col-lg-8">
                                        <div class="input-group">
                                            <input
                                                class="form-control"
                                                type="text"
                                                ng-model="servicecommandargumentvalue.value">
                                            <template-diff
                                                value="servicecommandargumentvalue.value"
                                                template-value="servicetemplate.Servicetemplate.servicetemplatecommandargumentvalues[$index].value"></template-diff>
                                        </div>
                                        <div ng-repeat="error in errors.servicecommandargumentvalues">
                                            <div class="help-block text-danger">{{ error }}</div>
                                        </div>
                                        <div class="help-block">
                                            {{servicecommandargumentvalue.commandargument.name}}
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group"
                                     ng-show="post.Service.command_id > 0 && post.Service.servicecommandargumentvalues.length == 0">
                                    <div class="col-xs-12 col-lg-offset-2 text-info">
                                        <i class="fa fa-info-circle"></i>
                                        <?php echo __('This command does not have any parameters.'); ?>
                                    </div>
                                </div>

                                <div class="form-group required" ng-class="{'has-error': errors.check_interval}">
                                    <label class="col-xs-12 col-lg-2 control-label">
                                        <?php echo __('Check interval'); ?>
                                    </label>
                                    <interval-input-with-differ-directive
                                        template-id="post.Service.servicetemplate_id"
                                        interval="post.Service.check_interval"
                                        template-value="servicetemplate.Servicetemplate.check_interval"></interval-input-with-differ-directive>
                                    <div class="col-xs-12 col-lg-offset-2">
                                        <div ng-repeat="error in errors.check_interval">
                                            <div class="help-block text-danger">{{ error }}</div>
                                        </div>
                                    </div>
                                </div>


                                <div class="form-group required" ng-class="{'has-error': errors.retry_interval}">
                                    <label class="col-xs-12 col-lg-2 control-label">
                                        <?php echo __('Retry interval'); ?>
                                    </label>
                                    <interval-input-with-differ-directive
                                        template-id="post.Service.servicetemplate_id"
                                        interval="post.Service.retry_interval"
                                        template-value="servicetemplate.Servicetemplate.retry_interval"></interval-input-with-differ-directive>

                                    <div class="col-xs-12 col-lg-offset-2">
                                        <div ng-repeat="error in errors.retry_interval">
                                            <div class="help-block text-danger">{{ error }}</div>
                                        </div>
                                    </div>
                                </div>


                                <div class="form-group required"
                                     ng-class="{'has-error': errors.max_check_attempts}">
                                    <label class="col-xs-12 col-lg-2 control-label">
                                        <?php echo __('Max. number of check attempts'); ?>
                                    </label>
                                    <div class="row">
                                        <div class="col-xs-12 col-lg-6">
                                            <div class="btn-group flex-wrap">
                                                <?php for ($i = 1; $i <= 10; $i++): ?>
                                                    <button
                                                        type="button"
                                                        class="btn btn-default"
                                                        ng-click="post.Service.max_check_attempts = <?php echo h($i) ?>"
                                                        ng-class="{'active': post.Service.max_check_attempts == <?php echo h($i); ?>}">
                                                        <?php echo h($i); ?>
                                                    </button>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                        <div class="col-xs-12 col-lg-6">
                                            <div class="input-group" style="width: 100%;">
                                                <input
                                                    class="form-control"
                                                    type="number"
                                                    min="0"
                                                    ng-model="post.Service.max_check_attempts">
                                                <template-diff ng-show="post.Service.servicetemplate_id"
                                                               value="post.Service.max_check_attempts"
                                                               template-value="servicetemplate.Servicetemplate.max_check_attempts"></template-diff>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-lg-offset-2 col-lg-12">
                                        <div class="help-block">
                                            <?php echo __('Number of failed attempts before the service will switch into hard state.'); ?>
                                        </div>
                                        <div class="help-block">
                                            <?php echo __('Worst case time delay until notification command gets executed after state hits a non ok state: '); ?>
                                            <human-time-directive
                                                seconds="(post.Service.check_interval + (post.Service.max_check_attempts -1) * post.Service.retry_interval)"></human-time-directive>
                                        </div>
                                        <div ng-repeat="error in errors.max_check_attempts">
                                            <div class="help-block text-danger">{{ error }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- CHECK CONFIGURATION END -->

                        <!-- NOTIFICATION CONFIGURATION START -->
                        <div class="card margin-bottom-10" ng-show="post.Service.servicetemplate_id">
                            <div class="card-header">
                                <i class="fa fa-envelope-open"></i> <?php echo __('Notification configuration'); ?>
                            </div>
                            <div class="card-body">
                                <div class="form-group" ng-class="{'has-error': errors.notifications_enabled}">
                                    <div class="custom-control custom-checkbox  margin-bottom-10"
                                         ng-class="{'has-error': errors.notifications_enabled}">

                                        <input type="checkbox"
                                               class="custom-control-input"
                                               ng-true-value="1"
                                               ng-false-value="0"
                                               id="notifications_enabled"
                                               ng-model="post.Service.notifications_enabled">
                                        <label class="custom-control-label" for="notifications_enabled">
                                            <?php echo __('Enable notifications'); ?>
                                        </label>
                                        <template-diff-button ng-show="post.Service.servicetemplate_id"
                                                              value="post.Service.notifications_enabled"
                                                              template-value="servicetemplate.Servicetemplate.notifications_enabled">
                                        </template-diff-button>
                                    </div>

                                    <div class="col col-xs-12 col-md-offset-2 help-block">
                                        <?php echo __('If disabled the check command won\'t be executed. This is useful if an external program sends state data to openITCOCKPIT.'); ?>
                                    </div>
                                </div>
                                <div class="form-group required"
                                     ng-class="{'has-error': errors.notify_period_id}">
                                    <label class="control-label" for="NotificationPeriod">
                                        <?php if ($this->Acl->hasPermission('edit', 'timeperiods')): ?>
                                            <a ui-sref="TimeperiodsEdit({id:post.Service.notify_period_id})">
                                                <?= __('Notification period'); ?>
                                            </a>
                                        <?php else: ?>
                                            <?= __('Notification period'); ?>
                                        <?php endif; ?>
                                    </label>
                                    <div class="input-group" style="width: 100%;">
                                        <select
                                            id="NotificationPeriod"
                                            data-placeholder="<?php echo __('Please choose'); ?>"
                                            class="form-control"
                                            chosen="timeperiods"
                                            ng-options="timeperiod.key as timeperiod.value for timeperiod in timeperiods"
                                            ng-model="post.Service.notify_period_id">
                                        </select>
                                        <template-diff ng-show="post.Service.servicetemplate_id"
                                                       value="post.Service.notify_period_id"
                                                       template-value="servicetemplate.Servicetemplate.notify_period_id"></template-diff>
                                    </div>
                                    <div ng-repeat="error in errors.notify_period_id">
                                        <div class="help-block text-danger">{{ error }}</div>
                                    </div>
                                </div>

                                <div class="form-group required"
                                     ng-class="{'has-error': errors.notification_interval}">
                                    <label class="col-xs-12 col-lg-2 control-label">
                                        <?php echo __('Notification interval'); ?>
                                    </label>
                                    <interval-input-with-differ-directive
                                        template-id="post.Service.servicetemplate_id"
                                        interval="post.Service.notification_interval"
                                        template-value="servicetemplate.Servicetemplate.notification_interval"></interval-input-with-differ-directive>
                                    <div class="col-xs-12 col-lg-offset-2">
                                        <div ng-repeat="error in errors.notification_interval">
                                            <div class="help-block text-danger">{{ error }}</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group"
                                     ng-show="data.areContactsInheritedFromHosttemplate || data.areContactsInheritedFromHost || data.areContactsInheritedFromServicetemplate">
                                    <div class="form-group" ng-class="{'has-error': errors.disableInheritance}">
                                        <div class="custom-control custom-checkbox  margin-bottom-10"
                                             ng-class="{'has-error': errors.disableInheritance}">

                                            <input type="checkbox"
                                                   class="custom-control-input"
                                                   id="resetContacts"
                                                   ng-model="data.disableInheritance">
                                            <label class="custom-control-label" for="resetContacts">
                                                <?php echo __('Disable inheritance'); ?>
                                            </label>
                                        </div>

                                        <div class="col col-xs-12 col-md-offset-2 help-block"
                                             ng-class="{'strikethrough': data.disableInheritance}">
                                            <?php echo __('Contacts and contact groups got inherited from'); ?>

                                            <span ng-class="{'bold': data.areContactsInheritedFromServicetemplate}">
                                                <?php if ($this->Acl->hasPermission('edit', 'servicetemplates')): ?>
                                                    <a ui-sref="ServicetemplatesEdit({id: post.Service.servicetemplate_id})">
                                                        <?php echo __('service template'); ?>
                                                    </a>
                                                <?php else: ?>
                                                    <?php echo __('service template'); ?>
                                                <?php endif; ?>
                                            </span>

                                            <span ng-show="!data.areContactsInheritedFromServicetemplate"
                                                  ng-class="{'bold': data.areContactsInheritedFromHost}">
                                                <i class="fa fa-angle-double-right"></i>
                                                <?php if ($this->Acl->hasPermission('edit', 'hosts')): ?>
                                                    <a ui-sref="HostsEdit({id: host.Host.id})">
                                                        <?php echo __('host'); ?>
                                                    </a>
                                                <?php else: ?>
                                                    <?php echo __('host'); ?>
                                                <?php endif; ?>
                                            </span>

                                            <span ng-show="data.areContactsInheritedFromHosttemplate" class="bold">
                                                <i class="fa fa-angle-double-right"></i>
                                                <?php if ($this->Acl->hasPermission('edit', 'hosttemplates')): ?>
                                                    <a ui-sref="HosttemplatesEdit({id: host.Host.hosttemplate_id})">
                                                        <?php echo __('host template'); ?>
                                                    </a>
                                                <?php else: ?>
                                                    <?php echo __('host template'); ?>
                                                <?php endif; ?>
                                            </span>
                                            .
                                        </div>
                                    </div>


                                </div>


                                <div id="ContactBlocker">
                                    <div class="form-group"
                                         ng-class="{'has-error': errors.contacts}">
                                        <label class="col-xs-12 col-lg-2 control-label">
                                            <?php echo __('Contacts'); ?>
                                        </label>
                                        <div class="input-group" style="width: 100%">
                                            <select
                                                id="ContactsPeriodSelect"
                                                data-placeholder="<?php echo __('Please choose'); ?>"
                                                class="form-control"
                                                chosen="contacts"
                                                multiple
                                                ng-options="contact.key as contact.value for contact in contacts"
                                                ng-model="post.Service.contacts._ids">
                                            </select>
                                            <template-diff ng-show="post.Service.servicetemplate_id"
                                                           value="post.Service.contacts._ids"
                                                           template-value="servicetemplate.Servicetemplate.contacts._ids"></template-diff>
                                        </div>
                                        <div ng-repeat="error in errors.contacts">
                                            <div class="help-block text-danger">{{ error }}</div>
                                        </div>
                                    </div>


                                    <div class="form-group"
                                         ng-class="{'has-error': errors.contactgroups}">
                                        <label class="col-xs-12 col-lg-2 control-label">
                                            <?php echo __('Contact groups'); ?>
                                        </label>
                                        <div class="input-group" style="width: 100%;">
                                            <select
                                                id="ContactgroupsSelect"
                                                data-placeholder="<?php echo __('Please choose'); ?>"
                                                class="form-control"
                                                chosen="contactgroups"
                                                multiple
                                                ng-options="contactgroup.key as contactgroup.value for contactgroup in contactgroups"
                                                ng-model="post.Service.contactgroups._ids">
                                            </select>
                                            <template-diff ng-show="post.Service.servicetemplate_id"
                                                           value="post.Service.contactgroups._ids"
                                                           template-value="servicetemplate.Servicetemplate.contactgroups._ids">
                                            </template-diff>
                                        </div>
                                        <div ng-repeat="error in errors.contactgroups">
                                            <div class="help-block text-danger">{{ error }}</div>
                                        </div>
                                    </div>
                                </div>

                                <hr>

                                <?php
                                $serviceOptions = [
                                    [
                                        'field' => 'notify_on_recovery',
                                        'class' => 'success',
                                        'text'  => __('Recovery')
                                    ],
                                    [
                                        'field' => 'notify_on_warning',
                                        'class' => 'warning',
                                        'text'  => __('Warning')
                                    ],
                                    [
                                        'field' => 'notify_on_critical',
                                        'class' => 'danger',
                                        'text'  => __('Critical')
                                    ],
                                    [
                                        'field' => 'notify_on_unknown',
                                        'class' => 'secondary',
                                        'text'  => __('Unknown')
                                    ],
                                    [
                                        'field' => 'notify_on_flapping',
                                        'class' => 'primary',
                                        'text'  => __('Flapping')
                                    ],
                                    [
                                        'field' => 'notify_on_downtime',
                                        'class' => 'primary',
                                        'text'  => __('Downtime')
                                    ],
                                ];
                                ?>
                                <fieldset>
                                    <legend class="fs-sm"
                                            ng-class="{'has-error-no-form': errors.notify_on_recovery}">
                                        <div class="required">
                                            <label class="fs-sm">
                                                <?php echo __('Service notification options'); ?>
                                            </label>

                                            <div ng-repeat="error in errors.notify_on_recovery">
                                                <div class="text-danger">{{ error }}</div>
                                            </div>
                                        </div>
                                    </legend>
                                    <div class="row">
                                        <?php foreach ($serviceOptions as $serviceOption): ?>
                                            <div
                                                class="custom-control custom-checkbox margin-bottom-10 custom-control-right"
                                                ng-class="{'has-error': errors.<?php echo $serviceOption['field']; ?>}">
                                                <input type="checkbox"
                                                       class="custom-control-input"
                                                       ng-true-value="1"
                                                       ng-false-value="0"
                                                       id="<?php echo $serviceOption['field']; ?>"
                                                       ng-model="post.Service.<?php echo $serviceOption['field']; ?>">
                                                <label for="<?php echo $serviceOption['field']; ?>"
                                                       class="col col-md-6 custom-control-label custom-control-label-<?php echo $serviceOption['class']; ?> padding-top-0 margin-right-10 ">
                                                    <span
                                                        class="badge badge-<?php echo $serviceOption['class']; ?> notify-label-small">
                                                        <?php echo $serviceOption['text']; ?>
                                                    </span>
                                                </label>
                                                <span class="margin-left-15">
                                                    <template-diff-button ng-show="post.Service.servicetemplate_id"
                                                                          value="post.Service.<?php echo $serviceOption['field']; ?>"
                                                                          template-value="servicetemplate.Servicetemplate.<?php echo $serviceOption['field']; ?>">
                                                    </template-diff-button>
                                                </span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </fieldset>
                            </div>
                        </div>
                        <!-- NOTIFICATION CONFIGURATION END -->

                        <!-- MISC. CONFIGURATION START -->

                        <div class="card margin-bottom-10" ng-show="post.Service.servicetemplate_id">
                            <div class="card-header">
                                <i class="fa fa-wrench"></i> <?php echo __('Misc. configuration'); ?>
                            </div>
                            <div class="card-body">

                                <div class="form-group" ng-class="{'has-error': errors.service_url}">
                                    <label class="control-label">
                                        <?php echo __('Service URL'); ?>
                                    </label>
                                    <div class="input-group">
                                        <input
                                            class="form-control"
                                            placeholder="https://issues.example.org?host=$HOSTNAME$&service=$SERVICEDESC$"
                                            type="text"
                                            ng-model="post.Service.service_url">

                                        <template-diff ng-show="post.Service.servicetemplate_id"
                                                       value="post.Service.service_url"
                                                       template-value="servicetemplate.Servicetemplate.service_url"></template-diff>
                                    </div>
                                    <div ng-repeat="error in errors.service_url">
                                        <div class="help-block text-danger">{{ error }}</div>
                                    </div>
                                    <div class="help-block">
                                        <?php echo __('The macros $HOSTID$, $HOSTNAME$, $HOSTDISPLAYNAME$, $HOSTADDRESS$, $SERVICEID$, $SERVICEDESC$, $SERVICEDISPLAYNAME$ will be replaced'); ?>
                                    </div>
                                </div>

                                <div class="form-group" ng-class="{'has-error': errors.notes}">
                                    <label class="control-label">
                                        <?php echo __('Notes'); ?>
                                    </label>
                                    <div class="input-group">
                                        <input
                                            class="form-control"
                                            type="text"
                                            ng-model="post.Service.notes">
                                        <template-diff ng-show="post.Service.servicetemplate_id"
                                                       value="post.Service.notes"
                                                       template-value="servicetemplate.Servicetemplate.notes"></template-diff>
                                    </div>
                                    <div ng-repeat="error in errors.notes">
                                        <div class="help-block text-danger">{{ error }}</div>
                                    </div>
                                </div>

                                <?php
                                $serviceFlapOptions = [
                                    [
                                        'field' => 'flap_detection_on_ok',
                                        'class' => 'success',
                                        'text'  => __('Recovery')
                                    ],
                                    [
                                        'field' => 'flap_detection_on_warning',
                                        'class' => 'warning',
                                        'text'  => __('Warning')
                                    ],
                                    [
                                        'field' => 'flap_detection_on_critical',
                                        'class' => 'danger',
                                        'text'  => __('Critical')
                                    ],
                                    [
                                        'field' => 'flap_detection_on_unknown',
                                        'class' => 'secondary',
                                        'text'  => __('Unknown')
                                    ]
                                ];
                                ?>


                                <div class="form-group" ng-class="{'has-error': errors.flap_detection_enabled}">
                                    <div class="custom-control custom-checkbox margin-bottom-10 "
                                         ng-class="{'has-error': errors.flap_detection_enabled}">
                                        <input type="checkbox"
                                               class="custom-control-input"
                                               ng-true-value="1"
                                               ng-false-value="0"
                                               id="flapDetectionEnabled"
                                               ng-model="post.Service.flap_detection_enabled">
                                        <label class="custom-control-label" for="flapDetectionEnabled">
                                            <?php echo __('Flap detection enabled'); ?>
                                        </label>
                                        <template-diff-button ng-show="post.Service.servicetemplate_id"
                                                              value="post.Service.flap_detection_enabled"
                                                              template-value="servicetemplate.Servicetemplate.flap_detection_enabled">
                                        </template-diff-button>
                                    </div>
                                </div>


                                <fieldset ng-show="post.Service.flap_detection_enabled">
                                    <legend class="fs-sm"
                                            ng-class="{'has-error-no-form': errors.flap_detection_on_ok}">
                                        <div ng-class="{'required':post.Service.flap_detection_enabled}">
                                            <label class="fs-sm">
                                                <?php echo __('Flap detection options'); ?>
                                            </label>

                                            <div ng-repeat="error in errors.flap_detection_on_ok">
                                                <div class="text-danger">{{ error }}</div>
                                            </div>
                                        </div>
                                    </legend>
                                    <div class="row">
                                        <?php foreach ($serviceFlapOptions as $serviceFlapOption): ?>
                                            <div
                                                class="custom-control custom-checkbox margin-bottom-10 custom-control-right"
                                                ng-class="{'has-error': errors.<?php echo $serviceFlapOption['field']; ?>}">
                                                <input type="checkbox" name="checkbox"
                                                       class="custom-control-input"
                                                       ng-true-value="1"
                                                       ng-false-value="0"
                                                       ng-disabled="!post.Service.flap_detection_enabled"
                                                       id="<?php echo $serviceFlapOption['field']; ?>"
                                                       ng-model="post.Service.<?php echo $serviceFlapOption['field']; ?>">
                                                <label for="<?php echo $serviceFlapOption['field']; ?>"
                                                       class="col col-md-6 custom-control-label custom-control-label-<?php echo $serviceFlapOption['class']; ?> padding-top-0 margin-right-10">
                                                    <span
                                                        class="badge badge-<?php echo $serviceFlapOption['class']; ?> notify-label-small">
                                                        <?php echo $serviceFlapOption['text']; ?>
                                                    </span>
                                                </label>
                                                <span class="margin-left-15">
                                                    <template-diff-button ng-show="post.Service.servicetemplate_id"
                                                                          value="post.Service.<?php echo $serviceFlapOption['field']; ?>"
                                                                          template-value="servicetemplate.Servicetemplate.<?php echo $serviceFlapOption['field']; ?>">
                                                    </template-diff-button>
                                                </span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </fieldset>


                                <div class="form-group" ng-class="{'has-error': errors.is_volatile}">
                                    <div class="custom-control custom-checkbox margin-bottom-10 "
                                         ng-class="{'has-error': errors.is_volatile}">
                                        <input type="checkbox"
                                               class="custom-control-input"
                                               ng-true-value="1"
                                               ng-false-value="0"
                                               id="isVolatile"
                                               ng-model="post.Service.is_volatile">
                                        <label class="custom-control-label" for="isVolatile">
                                            <?php echo __('Status volatile'); ?>
                                        </label>
                                        <template-diff-button ng-show="post.Service.servicetemplate_id"
                                                              value="post.Service.is_volatile"
                                                              template-value="servicetemplate.Servicetemplate.is_volatile">
                                        </template-diff-button>
                                    </div>

                                    <div class="col-xs-12 col-lg-offset-2 col-lg-12">
                                        <div class="help-block">
                                            <?php echo __('Will force the monitoring engine to send a notification on each Non-Ok check result that will occur.'); ?>
                                            <a href="https://www.naemon.io/documentation/usersguide/volatileservices.html"
                                               target="_blank">
                                                <i class="fa fa-external-link-alt"></i>
                                                <?php echo __('Online documentation'); ?>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- MISC. CONFIGURATION END -->
                        <!-- EVENT HANDLER CONFIGURATION START -->
                        <div class="card margin-bottom-10" ng-show="post.Service.servicetemplate_id">
                            <div class="card-header">
                                <i class="fa fa-exclamation"></i> <?php echo __('Event Handler configuration'); ?>
                            </div>
                            <div class="card-body">

                                <div class="form-group required"
                                     ng-class="{'has-error': errors.eventhandler_command_id}">
                                    <label class="control-label" for="ServiceEventHandlerSelect">
                                        <?php if ($this->Acl->hasPermission('edit', 'commands')): ?>
                                            <a ui-sref="CommandsEdit({id:post.Service.eventhandler_command_id})"
                                               ng-if="post.Service.eventhandler_command_id > 0">
                                                <?php echo __('Event Handler'); ?>
                                            </a>
                                            <span
                                                ng-if="post.Service.eventhandler_command_id == 0"><?php echo __('Event Handler'); ?></span>
                                        <?php else: ?>
                                            <?php echo __('Event Handler'); ?>
                                        <?php endif; ?>
                                    </label>
                                    <div class="input-group" style="width: 100%;">
                                        <select
                                            id="ServiceEventHandlerSelect"
                                            data-placeholder="<?php echo __('Please choose'); ?>"
                                            class="form-control"
                                            chosen="eventhandlerCommands"
                                            ng-options="eventhandler.key as eventhandler.value for eventhandler in eventhandlerCommands"
                                            ng-model="post.Service.eventhandler_command_id">
                                        </select>
                                        <template-diff ng-show="post.Service.servicetemplate_id"
                                                       value="post.Service.eventhandler_command_id"
                                                       template-value="servicetemplate.Servicetemplate.eventhandler_command_id"></template-diff>
                                    </div>
                                    <div ng-repeat="error in errors.eventhandler_command_id">
                                        <div class="help-block text-danger">{{ error }}</div>
                                    </div>
                                </div>

                                <div class="form-group"
                                     ng-class="{'has-error': errors.serviceeventcommandargumentvalues}"
                                     ng-repeat="serviceeventcommandargumentvalue in post.Service.serviceeventcommandargumentvalues">
                                    <label class="col-xs-12 col-lg-offset-2 col-lg-10 control-label text-purple">
                                        {{serviceeventcommandargumentvalue.commandargument.human_name}}
                                    </label>
                                    <div class="col-xs-12 col-lg-8">
                                        <div class="input-group">
                                            <input
                                                class="form-control"
                                                type="text"
                                                ng-model="serviceeventcommandargumentvalue.value">
                                            <template-diff
                                                value="serviceeventcommandargumentvalue.value"
                                                template-value="servicetemplate.Servicetemplate.servicetemplateeventcommandargumentvalues[$index].value"></template-diff>
                                        </div>
                                        <div ng-repeat="error in errors.serviceeventcommandargumentvalue">
                                            <div class="help-block text-danger">{{ error }}</div>
                                        </div>
                                        <div class="help-block">
                                            {{serviceeventcommandargumentvalue.commandargument.name}}
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group"
                                     ng-show="post.Service.eventhandler_command_id > 0 && post.Service.serviceeventcommandargumentvalues.length == 0">
                                    <div class="col-xs-12 col-lg-offset-2 text-info">
                                        <i class="fa fa-info-circle"></i>
                                        <?php echo __('This Event Handler command does not have any parameters.'); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- EVENT HANDLER CONFIGURATION END -->

                        <!-- SERVICE MACRO CONFIGURATION START -->
                        <div class="card margin-bottom-10" ng-show="post.Service.servicetemplate_id">
                            <div class="card-header">
                                <i class="fa fa-dollar"></i> <?php echo __('Service macro configuration'); ?>
                            </div>
                            <div class="card-body" ng-class="{'has-error-no-form': errors.customvariables_unique}">
                                <div class="row">
                                    <div ng-repeat="error in errors.customvariables_unique">
                                        <div class=" col-xs-12 text-danger">{{ error }}</div>
                                    </div>
                                </div>

                                <div class="row" ng-repeat="customvariable in post.Service.customvariables">
                                    <macros-directive macro="customvariable"
                                                      macro-name="'<?php echo __('SERVICE'); ?>'"
                                                      index="$index"
                                                      callback="deleteMacroCallback"
                                                      errors="getMacroErrors($index)"
                                                      class="col-lg-12">
                                    </macros-directive>
                                </div>

                                <div class="row">
                                    <div class="col-xs-12 col-lg-12 padding-top-10 text-info"
                                         ng-show="post.Service.customvariables.length > 0">
                                        <i class="fa fa-info-circle"></i>
                                        <?php echo __('Macros in green color are inherited from the service template.'); ?>
                                    </div>
                                    <div class="col-lg-12 col-md-offset-2 padding-top-10 text-right">
                                        <button type="button" class="btn btn-success btn-sm"
                                                ng-click="addMacro()">
                                            <i class="fa fa-plus"></i>
                                            <?php echo __('Add new macro'); ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- SERVICE MACRO CONFIGURATION END -->
                        <?php if (Plugin::isLoaded('SLAModule')): ?>
                            <!-- SLA CONFIGURATION START -->
                            <div ng-show="post.Service.servicetemplate_id"
                                 class="card margin-bottom-10">
                                <div class="card-header">
                                    <i class="fa-solid fa-award"></i> <?= __('Service Level Agreement'); ?>
                                    <span ng-show="!isSlaHost" class="italic pl-2">
                                        <i class="fa-solid fa-circle-info text-primary"></i>
                                        <?= __('The selected host is not associated with an SLA!'); ?>
                                    </span>
                                </div>
                                <div class="card-body">
                                    <div class="form-group">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox"
                                                   class="custom-control-input"
                                                   id="isSlaRelevant"
                                                   ng-true-value="1"
                                                   ng-false-value="0"
                                                   ng-disabled="!isSlaHost"
                                                   ng-model="post.Service.sla_relevant">
                                            <label class="custom-control-label" for="isSlaRelevant">
                                                <?php echo __('SLA relevant'); ?>
                                            </label>
                                            <template-diff-button ng-show="post.Service.servicetemplate_id && isSlaHost"
                                                                  value="post.Service.sla_relevant"
                                                                  template-value="servicetemplate.Servicetemplate.sla_relevant">
                                            </template-diff-button>

                                        </div>
                                        <div class="col-xs-12 col-lg-offset-2 col-lg-12">
                                            <div class="help-block mt-0 text-warning" ng-show="isSlaHost"
                                                 ng-class="{'text-warning' : post.Service.sla_relevant == 0}">
                                                <span class="font-lg"
                                                      ng-class="{'warning-icon-gray' : post.Service.sla_relevant == 1}">
                                                    ⚠️
                                                </span>
                                                <?= __('If deactivated, the service will be ignored in the SLA calculation and the related data from the service will be permanently removed.'); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- SLA CONFIGURATION END -->
                        <?php endif; ?>
                        <div class="card margin-top-10">
                            <div class="card-body">
                                <div class="float-right">
                                    <label>
                                        <input type="checkbox" ng-model="data.createAnother">
                                        <?php echo __('Create another'); ?>
                                    </label>
                                    <button class="btn btn-primary" type="submit">
                                        <?php echo __('Create service'); ?>
                                    </button>
                                    <a back-button href="javascript:void(0);" fallback-state='ServicesIndex'
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
