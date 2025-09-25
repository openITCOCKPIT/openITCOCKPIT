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
        <a ui-sref="ServicetemplatesIndex">
            <i class="fa-solid fa-pen-to-square"></i> <?php echo __('Service templates'); ?>
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
                    <?php echo __('Edit service template:'); ?>
                    <span class="fw-300"><i>{{ post.Servicetemplate.template_name }}</i></span>
                </h2>
                <div class="panel-toolbar">
                    <div class="text-muted cursor-default d-none d-sm-none d-md-none d-lg-block margin-right-10">
                        UUID: {{post.Servicetemplate.uuid}}
                    </div>
                    <span ng-if="typeDetails"
                          class="badge border margin-right-10 {{typeDetails.class}} {{typeDetails.color}}">
                        <i class="{{typeDetails.icon}}"></i>
                        {{typeDetails.title}}
                    </span>
                    <?php if ($this->Acl->hasPermission('index', 'servicetemplates')): ?>
                        <a back-button href="javascript:void(0);" fallback-state='ServicetemplatesIndex'
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
                            {objectName : '<?php echo __('Service template'); ?>' , message: '<?php echo __('created successfully'); ?>'}">

                        <!-- BASIC CONFIGURATION START -->

                        <div class="card margin-bottom-10">
                            <div class="card-header">
                                <i class="fa-solid fa-wand-magic-sparkles"></i> <?php echo __('Basic configuration'); ?>
                            </div>
                            <div class="card-body">
                                <div class="form-group required" ng-class="{'has-error': errors.container_id}">
                                    <label class="control-label" for="Container">
                                        <?php echo __('Container'); ?>
                                    </label>
                                    <select
                                        id="Container"
                                        data-placeholder="<?php echo __('Please choose'); ?>"
                                        class="form-control"
                                        chosen="containers"
                                        ng-options="container.key as container.value for container in containers"
                                        ng-model="post.Servicetemplate.container_id">
                                    </select>
                                    <div ng-show="post.Servicetemplate.container_id < 1" class="warning-glow">
                                        <?php echo __('Please select a container.'); ?>
                                    </div>
                                    <div ng-repeat="error in errors.container_id">
                                        <div class="help-block text-danger">{{ error }}</div>
                                    </div>
                                </div>

                                <div class="form-group required" ng-class="{'has-error': errors.template_name}">
                                    <label class="control-label">
                                        <?php echo __('Template name'); ?>
                                    </label>
                                    <input
                                        id="ServiceName"
                                        class="form-control"
                                        type="text"
                                        ng-disabled="post.Servicetemplate.template_name == 'OITC_AGENT_ACTIVE'"
                                        ng-model="post.Servicetemplate.template_name">
                                    <div ng-repeat="error in errors.template_name">
                                        <div class="help-block text-danger">{{ error }}</div>
                                    </div>
                                    <div class="help-block">
                                        <?= __('Name of the service template.') ?>
                                    </div>
                                </div>

                                <div class="form-group required"
                                     ng-class="{'has-error': errors.servicetemplatetype_id}">
                                    <label class="control-label">
                                        <?php echo __('Template Type'); ?>
                                    </label>
                                    <select
                                        id="ServiceServicetemplateSelect"
                                        data-placeholder="<?php echo __('Please choose'); ?>"
                                        class="form-control"
                                        chosen="servicetemplatetypes"
                                        ng-options="templatetype.key as templatetype.value.title for templatetype in servicetemplatetypes"
                                        ng-model="post.Servicetemplate.servicetemplatetype_id">
                                    </select>
                                    <div class="help-block">
                                        <?= __('Defines the type of the template. Use "Generic template" if you are not sure.') ?>
                                    </div>
                                    <div ng-repeat="error in errors.servicetemplatetype_id">
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
                                        ng-model="post.Servicetemplate.name">
                                    <div class="help-block">
                                        <?= __('Default name of services using this service template.') ?>
                                    </div>
                                    <div ng-repeat="error in errors.name">
                                        <div class="help-block text-danger">{{ error }}</div>
                                    </div>
                                </div>

                                <div class="form-group" ng-class="{'has-error': errors.description}">
                                    <label class="control-label">
                                        <?php echo __('Description'); ?>
                                    </label>
                                    <div class="input-group">
                                        <input
                                            class="form-control"
                                            type="text"
                                            ng-model="post.Servicetemplate.description">
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
                                            ng-model="post.Servicetemplate.servicegroups._ids">
                                        </select>
                                    </div>
                                    <div ng-repeat="error in errors.servicegroups">
                                        <div class="help-block text-danger">{{ error }}</div>
                                    </div>
                                </div>


                                <div class="form-group" ng-class="{'has-error': errors.tags}">
                                    <label class="control-label">
                                        <?php echo __('Tags'); ?>
                                    </label>
                                    <input class="form-control tagsinput"
                                           data-role="tagsinput"
                                           type="text"
                                           ng-model="post.Servicetemplate.tags"
                                           id="ServiceTagsInput">
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
                                        <priority-directive priority="post.Servicetemplate.priority"
                                                            callback="setPriority"></priority-directive>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- BASIC CONFIGURATION END -->
                        <!-- CHECK CONFIGURATION START -->

                        <div class="card margin-bottom-10">
                            <div class="card-header">
                                <i class="fa fa-terminal"></i> <?php echo __('Check configuration'); ?>
                            </div>
                            <div class="card-body">
                                <div class="form-group required"
                                     ng-class="{'has-error': errors.check_period_id}">
                                    <label class="control-label">
                                        <?php if ($this->Acl->hasPermission('edit', 'timeperiods')): ?>
                                            <a ui-sref="TimeperiodsEdit({id:post.Servicetemplate.check_period_id})">
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
                                            ng-model="post.Servicetemplate.check_period_id">
                                        </select>
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
                                               id="activeChecksEnabled"
                                               ng-model="post.Servicetemplate.active_checks_enabled">
                                        <label class="custom-control-label" for="activeChecksEnabled">
                                            <?php echo __('Enable active checks'); ?>
                                        </label>
                                    </div>
                                    <div class="col col-xs-12 col-md-offset-2 help-block">
                                        <?php echo __('If disabled the check command won\'t be executed. This is useful if an external program sends state data to openITCOCKPIT.'); ?>
                                    </div>
                                </div>

                                <div class="form-group" ng-class="{'has-error': errors.freshness_checks_enabled}"
                                     ng-show="post.Servicetemplate.active_checks_enabled == 0">
                                    <div class="custom-control custom-checkbox  margin-bottom-10"
                                         ng-class="{'has-error': errors.freshness_checks_enabled}">
                                        <input type="checkbox"
                                               class="custom-control-input"
                                               ng-true-value="1"
                                               ng-false-value="0"
                                               id="freshness_checks_enabled"
                                               ng-model="post.Servicetemplate.freshness_checks_enabled">
                                        <label class="custom-control-label" for="freshness_checks_enabled">
                                            <?php echo __('Enable freshness check'); ?>
                                        </label>
                                    </div>

                                    <div class="help-block">
                                        <?php echo __('If enabled the system will check that passive checks for this service will be received as frequently as defined.'); ?>
                                    </div>
                                </div>

                                <div class="form-group required" ng-class="{'has-error': errors.freshness_threshold}"
                                     ng-show="post.Servicetemplate.active_checks_enabled == 0 && post.Servicetemplate.freshness_checks_enabled == 1">
                                    <label class="col-xs-12 col-lg-2 control-label">
                                        <?php echo __('Freshness threshold'); ?>
                                    </label>
                                    <interval-input-directive
                                        interval="post.Servicetemplate.freshness_threshold"></interval-input-directive>
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
                                            <a ui-sref="CommandsEdit({id:post.Servicetemplate.command_id})">
                                                <?= __('Check command'); ?>
                                            </a>
                                        <?php else: ?>
                                            <?= __('Check command'); ?>
                                        <?php endif; ?>
                                    </label>
                                    <div class="input-group" style="width: 100%;">
                                        <select
                                            data-placeholder="<?php echo __('Please choose'); ?>"
                                            class="form-control"
                                            chosen="commands"
                                            ng-options="command.key as command.value for command in commands"
                                            ng-model="post.Servicetemplate.command_id">
                                        </select>
                                    </div>
                                    <div class="help-block" ng-hide="post.Servicetemplate.active_checks_enabled">
                                        <?php echo __('Due to active checking is disabled, this command will only be used as freshness check command.'); ?>
                                    </div>
                                    <div ng-repeat="error in errors.command_id">
                                        <div class="help-block text-danger">{{ error }}</div>
                                    </div>
                                </div>

                                <div class="form-group"
                                     ng-class="{'has-error': errors.servicetemplatecommandargumentvalue}"
                                     ng-repeat="servicetemplatecommandargumentvalue in post.Servicetemplate.servicetemplatecommandargumentvalues">
                                    <label class="col-xs-12 col-lg-offset-2 col-lg-10 control-label text-purple">
                                        {{servicetemplatecommandargumentvalue.commandargument.human_name}}
                                    </label>
                                    <div class="col-xs-12 col-lg-8">
                                        <div class="input-group">
                                            <input
                                                class="form-control"
                                                type="text"
                                                ng-model="servicetemplatecommandargumentvalue.value">
                                        </div>
                                        <div ng-repeat="error in errors.servicetemplatecommandargumentvalues">
                                            <div class="help-block text-danger">{{ error }}</div>
                                        </div>
                                        <div class="help-block">
                                            {{servicetemplatecommandargumentvalue.commandargument.name}}
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group"
                                     ng-show="post.Servicetemplate.command_id > 0 && post.Servicetemplate.servicecommandargumentvalues.length == 0">
                                    <div class="col-xs-12 col-lg-offset-2 text-info">
                                        <i class="fa fa-info-circle"></i>
                                        <?php echo __('This command does not have any parameters.'); ?>
                                    </div>
                                </div>

                                <div class="form-group required" ng-class="{'has-error': errors.check_interval}">
                                    <label class="col-xs-12 col-lg-2 control-label">
                                        <?php echo __('Check interval'); ?>
                                    </label>
                                    <interval-input-directive
                                        interval="post.Servicetemplate.check_interval"></interval-input-directive>
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
                                    <interval-input-directive
                                        interval="post.Servicetemplate.retry_interval"></interval-input-directive>

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
                                                        ng-click="post.Servicetemplate.max_check_attempts = <?php echo h($i) ?>"
                                                        ng-class="{'active': post.Servicetemplate.max_check_attempts == <?php echo h($i); ?>}">
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
                                                    ng-model="post.Servicetemplate.max_check_attempts">
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
                                                seconds="(post.Servicetemplate.check_interval + (post.Servicetemplate.max_check_attempts -1) * post.Servicetemplate.retry_interval)"></human-time-directive>
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
                        <div class="card margin-bottom-10">
                            <div class="card-header">
                                <i class="fa fa-envelope-open"></i> <?php echo __('Notification configuration'); ?>
                            </div>
                            <div class="card-body">
                                <div class="custom-control custom-checkbox"
                                     ng-class="{'has-error': errors.notifications_enabled}">
                                    <input type="checkbox"
                                           id="notificationsEnabled"
                                           class="custom-control-input"
                                           name="checkbox"
                                           checked="checked"
                                           ng-true-value="1"
                                           ng-false-value="0"
                                           ng-model="post.Servicetemplate.notifications_enabled">
                                    <label class="custom-control-label"
                                           for="notificationsEnabled"><?php echo __('Enable notifications'); ?></label>
                                    <div class="help-block">
                                        <?php echo __('This option is used to determine whether or not notifications are enabled for this host.'); ?>
                                    </div>
                                </div>
                                <div class="form-group required"
                                     ng-class="{'has-error': errors.notify_period_id}">
                                    <label class="control-label" for="NotificationPeriod">
                                        <?php if ($this->Acl->hasPermission('edit', 'timeperiods')): ?>
                                            <a ui-sref="TimeperiodsEdit({id:post.Servicetemplate.notify_period_id})">
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
                                            ng-model="post.Servicetemplate.notify_period_id">
                                        </select>
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
                                    <interval-input-directive
                                        interval="post.Servicetemplate.notification_interval"></interval-input-directive>
                                    <div class="col-xs-12 col-lg-offset-2">
                                        <div ng-repeat="error in errors.notification_interval">
                                            <div class="help-block text-danger">{{ error }}</div>
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
                                                ng-model="post.Servicetemplate.contacts._ids">
                                            </select>
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
                                                ng-model="post.Servicetemplate.contactgroups._ids">
                                            </select>
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
                                                class="custom-control custom-checkbox margin-bottom-10 custom-control-right-badge"
                                                ng-class="{'has-error': errors.<?php echo $serviceOption['field']; ?>}">
                                                <input type="checkbox"
                                                       class="custom-control-input"
                                                       ng-true-value="1"
                                                       ng-false-value="0"
                                                       id="<?php echo $serviceOption['field']; ?>"
                                                       ng-model="post.Servicetemplate.<?php echo $serviceOption['field']; ?>">
                                                <label for="<?php echo $serviceOption['field']; ?>"
                                                       class="col col-md-6 custom-control-label custom-control-label-<?php echo $serviceOption['class']; ?> padding-top-0 margin-right-10 ">
                                                    <span
                                                        class="badge badge-<?php echo $serviceOption['class']; ?> notify-label-small">
                                                        <?php echo $serviceOption['text']; ?>
                                                    </span>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </fieldset>
                            </div>
                        </div>
                        <!-- NOTIFICATION CONFIGURATION END -->

                        <!-- MISC. CONFIGURATION START -->

                        <div class="card margin-bottom-10">
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
                                            ng-model="post.Servicetemplate.service_url">
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
                                            ng-model="post.Servicetemplate.notes">
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
                                               id="flapDetectionEnabled"
                                               ng-true-value="1"
                                               ng-false-value="0"
                                               ng-model="post.Servicetemplate.flap_detection_enabled">
                                        <label class="custom-control-label" for="flapDetectionEnabled">
                                            <?php echo __('Flap detection enabled'); ?>
                                        </label>
                                    </div>
                                </div>


                                <fieldset ng-show="post.Servicetemplate.flap_detection_enabled">
                                    <legend class="fs-sm"
                                            ng-class="{'has-error-no-form': errors.flap_detection_on_ok}">
                                        <div class="required">
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
                                                class="custom-control custom-checkbox margin-bottom-10 custom-control-right-badge"
                                                ng-class="{'has-error': errors.<?php echo $serviceFlapOption['field']; ?>}">
                                                <input type="checkbox" name="checkbox"
                                                       class="custom-control-input"
                                                       ng-true-value="1"
                                                       ng-false-value="0"
                                                       ng-disabled="!post.Servicetemplate.flap_detection_enabled"
                                                       id="<?php echo $serviceFlapOption['field']; ?>"
                                                       ng-model="post.Servicetemplate.<?php echo $serviceFlapOption['field']; ?>">
                                                <label for="<?php echo $serviceFlapOption['field']; ?>"
                                                       class="col col-md-6 custom-control-label custom-control-label-<?php echo $serviceFlapOption['class']; ?> padding-top-0 margin-right-10">
                                                    <span
                                                        class="badge badge-<?php echo $serviceFlapOption['class']; ?> notify-label-small">
                                                        <?php echo $serviceFlapOption['text']; ?>
                                                    </span>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </fieldset>


                                <div class="form-group" ng-class="{'has-error': errors.is_volatile}">
                                    <div class="custom-control custom-checkbox margin-bottom-10 "
                                         ng-class="{'has-error': errors.is_volatile}">
                                        <input type="checkbox"
                                               class="custom-control-input"
                                               id="isVolatile"
                                               ng-true-value="1"
                                               ng-false-value="0"
                                               ng-model="post.Servicetemplate.is_volatile">
                                        <label class="custom-control-label" for="isVolatile">
                                            <?php echo __('Status volatile'); ?>
                                        </label>
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
                        <div class="card margin-bottom-10">
                            <div class="card-header">
                                <i class="fa fa-exclamation"></i> <?php echo __('Event Handler configuration'); ?>
                            </div>
                            <div class="card-body">

                                <div class="form-group required"
                                     ng-class="{'has-error': errors.eventhandler_command_id}">
                                    <label class="control-label" for="ServiceEventHandlerSelect">
                                        <?php if ($this->Acl->hasPermission('edit', 'commands')): ?>
                                            <a ui-sref="CommandsEdit({id:post.Servicetemplate.eventhandler_command_id})"
                                               ng-if="post.Servicetemplate.eventhandler_command_id > 0">
                                                <?php echo __('Event Handler'); ?>
                                            </a>
                                            <span
                                                ng-if="post.Servicetemplate.eventhandler_command_id == 0"><?php echo __('Event Handler'); ?></span>
                                        <?php else: ?>
                                            <?php echo __('Event Handler'); ?>
                                        <?php endif; ?>
                                    </label>
                                    <div class="input-group" style="width: 100%;">
                                        <select
                                            id="ServiceEventHandlerSelect"
                                            data-placeholder="<?php echo __('Please choose'); ?>"
                                            class="form-control"
                                            chosen="commands"
                                            ng-options="eventhandler.key as eventhandler.value for eventhandler in eventhandlerCommands"
                                            ng-model="post.Servicetemplate.eventhandler_command_id">
                                            <option></option>
                                        </select>
                                    </div>
                                    <div ng-repeat="error in errors.eventhandler_command_id">
                                        <div class="help-block text-danger">{{ error }}</div>
                                    </div>
                                </div>

                                <div class="form-group"
                                     ng-class="{'has-error': errors.servicetemplateeventcommandargumentvalue}"
                                     ng-repeat="servicetemplateeventcommandargumentvalue in post.Servicetemplate.servicetemplateeventcommandargumentvalues">
                                    <label class="col-xs-12 col-lg-offset-2 col-lg-10 control-label text-purple">
                                        {{servicetemplateeventcommandargumentvalue.commandargument.human_name}}
                                    </label>
                                    <div class="col-xs-12 col-lg-8">
                                        <div class="input-group">
                                            <input
                                                class="form-control"
                                                type="text"
                                                ng-model="servicetemplateeventcommandargumentvalue.value">
                                        </div>
                                        <div ng-repeat="error in errors.servicetemplateeventcommandargumentvalue">
                                            <div class="help-block text-danger">{{ error }}</div>
                                        </div>
                                        <div class="help-block">
                                            {{servicetemplateeventcommandargumentvalue.commandargument.name}}
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group"
                                     ng-show="post.Servicetemplate.eventhandler_command_id > 0 && post.Servicetemplate.servicetemplateeventcommandargumentvalue.length == 0">
                                    <div class="col-xs-12 col-lg-offset-2 text-info">
                                        <i class="fa fa-info-circle"></i>
                                        <?php echo __('This Event Handler command does not have any parameters.'); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- EVENT HANDLER CONFIGURATION END -->
                        <!-- SERVICE MACRO CONFIGURATION START -->
                        <div class="card margin-bottom-10">
                            <div class="card-header">
                                <i class="fa fa-dollar"></i> <?php echo __('Service macro configuration'); ?>
                            </div>
                            <div class="card-body" ng-class="{'has-error-no-form': errors.customvariables_unique}">
                                <div class="row">
                                    <div ng-repeat="error in errors.customvariables_unique">
                                        <div class=" col-xs-12 text-danger">{{ error }}</div>
                                    </div>
                                </div>

                                <div class="row" ng-repeat="customvariable in post.Servicetemplate.customvariables">
                                    <macros-directive macro="customvariable"
                                                      macro-name="'<?php echo __('SERVICE'); ?>'"
                                                      index="$index"
                                                      callback="deleteMacroCallback"
                                                      errors="getMacroErrors($index)"
                                                      class="col-lg-12"
                                    ></macros-directive>
                                </div>

                                <div class="row">
                                    <div class="col-xs-12 col-lg-12 padding-top-10 text-info"
                                         ng-show="post.Servicetemplate.customvariables.length > 0">
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
                            <div class="card margin-bottom-10">
                                <div class="card-header">
                                    <i class="fa-solid fa-award"></i> <?= __('Service Level Agreement'); ?>
                                </div>
                                <div class="card-body">
                                    <div class="form-group">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox"
                                                   class="custom-control-input"
                                                   id="isSlaRelevant"
                                                   ng-true-value="1"
                                                   ng-false-value="0"
                                                   ng-model="post.Servicetemplate.sla_relevant">
                                            <label class="custom-control-label" for="isSlaRelevant">
                                                <?php echo __('SLA relevant'); ?>
                                            </label>
                                        </div>
                                        <div class="col-xs-12 col-lg-offset-2 col-lg-12">
                                            <div class="help-block mt-0 text-danger">
                                                <span class="font-lg">⚠️</span>
                                                <?= __('This setting affects all services, which are using this service template. If deactivated, all SLA related data will be removed permanently.
To exclude single services, please edit the service configuration directly instead.'); ?>
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
                                    <button class="btn btn-primary" type="submit">
                                        <?php echo __('Update service template'); ?>
                                    </button>
                                    <a back-button href="javascript:void(0);" fallback-state='ServicetemplatesIndex'
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
