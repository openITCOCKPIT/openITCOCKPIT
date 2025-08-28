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
        <a ui-sref="HosttemplatesIndex">
            <i class="fa fa-cog"></i> <?php echo __('Host template'); ?>
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
                    <?php echo __('Create new host template'); ?>
                </h2>
                <div class="panel-toolbar">
                    <span ng-if="typeDetails"
                          class="badge border margin-right-10 {{typeDetails.class}} {{typeDetails.color}}">
                        <i class="{{typeDetails.icon}}"></i>
                        {{typeDetails.title}}
                    </span>
                    <?php if ($this->Acl->hasPermission('index', 'hosttemplates')): ?>
                        <a back-button href="javascript:void(0);" fallback-state='HosttemplatesIndex'
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
                             {objectName : '<?php echo __('Host template'); ?>' , message: '<?php echo __('created successfully'); ?>'}">

                        <!-- BASIC CONFIGURATION START -->
                        <div class="card margin-bottom-10">
                            <div class="card-header">
                                <i class="fa-solid fa-wand-magic-sparkles"></i> <?php echo __('Basic configuration'); ?>
                            </div>
                            <div class="card-body">
                                <div class="form-group required" ng-class="{'has-error': errors.container_id}">
                                    <label class="control-label" for="HosttemplateContainer">
                                        <?php echo __('Container'); ?>
                                    </label>
                                    <select
                                        id="HosttemplateContainer"
                                        data-placeholder="<?php echo __('Please choose'); ?>"
                                        class="form-control"
                                        chosen="containers"
                                        ng-options="container.key as container.value for container in containers"
                                        ng-model="post.Hosttemplate.container_id">
                                    </select>
                                    <div ng-show="post.Hosttemplate.container_id < 1" class="warning-glow">
                                        <?php echo __('Please select a container.'); ?>
                                    </div>
                                    <div ng-repeat="error in errors.container_id">
                                        <div class="help-block text-danger">{{ error }}</div>
                                    </div>
                                </div>

                                <div class="form-group required" ng-class="{'has-error': errors.name}">
                                    <label class="control-label">
                                        <?php echo __('Template name'); ?>
                                    </label>
                                    <input
                                        id="HosttemplateName"
                                        class="form-control"
                                        type="text"
                                        ng-model="post.Hosttemplate.name">
                                    <div ng-repeat="error in errors.name">
                                        <div class="help-block text-danger">{{ error }}</div>
                                    </div>
                                </div>

                                <div class="form-group required"
                                     ng-class="{'has-error': errors.hosttemplatetype_id}">
                                    <label class="control-label">
                                        <?php echo __('Template Type'); ?>
                                    </label>
                                    <select
                                        id="HostHosttemplateSelect"
                                        data-placeholder="<?php echo __('Please choose'); ?>"
                                        class="form-control"
                                        chosen="hosttemplatetypes"
                                        ng-options="templatetype.key as templatetype.value.title for templatetype in hosttemplatetypes"
                                        ng-model="post.Hosttemplate.hosttemplatetype_id">
                                    </select>
                                    <div class="help-block">
                                        <?= __('Defines the type of the template. Use "Generic template" if you are not sure.') ?>
                                    </div>
                                    <div ng-repeat="error in errors.hosttemplatetype_id">
                                        <div class="help-block text-danger">{{ error }}</div>
                                    </div>
                                </div>

                                <div class="form-group" ng-class="{'has-error': errors.description}">
                                    <label class="control-label">
                                        <?php echo __('Description'); ?>
                                    </label>
                                    <input
                                        id="HosttemplateDescription"
                                        class="form-control"
                                        type="text"
                                        ng-model="post.Hosttemplate.description">
                                    <div ng-repeat="error in errors.description">
                                        <div class="help-block text-danger">{{ error }}</div>
                                    </div>
                                </div>

                                <div class="form-group" ng-class="{'has-error': errors.hostgroups}">
                                    <label class="control-label" for="HosttemplateHostgroups">
                                        <?php echo __('Host groups'); ?>
                                    </label>
                                    <select
                                        id="HosttemplateHostgroups"
                                        data-placeholder="<?php echo __('Please choose'); ?>"
                                        class="form-control"
                                        chosen="hostgroups"
                                        multiple
                                        ng-options="hostgroup.key as hostgroup.value for hostgroup in hostgroups"
                                        ng-model="post.Hosttemplate.hostgroups._ids">
                                    </select>
                                    <div ng-repeat="error in errors.hostgroups">
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
                                           ng-model="post.Hosttemplate.tags"
                                           id="HosttemplateTagsInput">

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
                                        <priority-directive priority="post.Hosttemplate.priority"
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
                                <div class="form-group required" ng-class="{'has-error': errors.check_period_id}">
                                    <label class="control-label" for="CheckPeriodSelect">
                                        <?php if ($this->Acl->hasPermission('edit', 'timeperiods')): ?>
                                            <a ui-sref="TimeperiodsEdit({id:post.Hosttemplate.check_period_id})"
                                               ng-if="post.Hosttemplate.check_period_id > 0">
                                                <?= __('Check period'); ?>
                                            </a>
                                            <span ng-if="post.Hosttemplate.check_period_id == 0">
                                                <?php echo __('Check period'); ?>
                                            </span>
                                        <?php else: ?>
                                            <?= __('Check period'); ?>
                                        <?php endif; ?>
                                    </label>
                                    <select
                                        id="CheckPeriodSelect"
                                        data-placeholder="<?php echo __('Please choose'); ?>"
                                        class="form-control"
                                        chosen="checkperiods"
                                        ng-options="checkperiod.key as checkperiod.value for checkperiod in checkperiods"
                                        ng-model="post.Hosttemplate.check_period_id">
                                    </select>
                                    <div ng-repeat="error in errors.check_period_id">
                                        <div class="help-block text-danger">{{ error }}</div>
                                    </div>
                                </div>

                                <div class="custom-control custom-checkbox"
                                     ng-class="{'has-error': errors.active_checks_enabled}">
                                    <input type="checkbox"
                                           id="activeChecksEnabled"
                                           class="custom-control-input"
                                           name="checkbox"
                                           checked="checked"
                                           ng-true-value="1"
                                           ng-false-value="0"
                                           ng-model="post.Hosttemplate.active_checks_enabled">
                                    <label class="custom-control-label"
                                           for="activeChecksEnabled"><?php echo __('Enable active checks'); ?></label>
                                    <div class="help-block">
                                        <?php echo __('If disabled the check command won\'t be executed. This is useful if an external program sends state data to openITCOCKPIT.'); ?>
                                    </div>
                                </div>

                                <div class="form-group" ng-class="{'has-error': errors.freshness_checks_enabled}"
                                     ng-show="post.Hosttemplate.active_checks_enabled == 0">
                                    <div class="custom-control custom-checkbox  margin-bottom-10"
                                         ng-class="{'has-error': errors.freshness_checks_enabled}">
                                        <input type="checkbox"
                                               class="custom-control-input"
                                               ng-true-value="1"
                                               ng-false-value="0"
                                               id="freshness_checks_enabled"
                                               ng-model="post.Hosttemplate.freshness_checks_enabled">
                                        <label class="custom-control-label" for="freshness_checks_enabled">
                                            <?php echo __('Enable freshness check'); ?>
                                        </label>
                                    </div>

                                    <div class="help-block">
                                        <?php echo __('If enabled the system will check that passive checks for this service will be received as frequently as defined.'); ?>
                                    </div>
                                </div>

                                <div class="form-group required" ng-class="{'has-error': errors.freshness_threshold}"
                                     ng-show="post.Hosttemplate.active_checks_enabled == 0 && post.Hosttemplate.freshness_checks_enabled == 1">
                                    <label class="col-xs-12 col-lg-2 control-label">
                                        <?php echo __('Freshness threshold'); ?>
                                    </label>
                                    <interval-input-directive
                                        interval="post.Hosttemplate.freshness_threshold"></interval-input-directive>
                                    <div class="col-xs-12 col-lg-offset-2">
                                        <div ng-repeat="error in errors.freshness_threshold">
                                            <div class="help-block text-danger">{{ error }}</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group required" ng-class="{'has-error': errors.command_id}">
                                    <label class="control-label" for="HostCheckCommandSelect">
                                        <?php if ($this->Acl->hasPermission('edit', 'commands')): ?>
                                            <a ui-sref="CommandsEdit({id:post.Hosttemplate.command_id})"
                                               ng-if="post.Hosttemplate.command_id > 0">
                                                <?= __('Check command'); ?>
                                            </a>
                                            <span ng-if="post.Hosttemplate.command_id == 0">
                                                <?php echo __('Check command'); ?>
                                            </span>
                                        <?php else: ?>
                                            <?= __('Check command'); ?>
                                        <?php endif; ?>
                                    </label>
                                    <select
                                        id="HostCheckCommandSelect"
                                        data-placeholder="<?php echo __('Please choose'); ?>"
                                        class="form-control"
                                        chosen="commands"
                                        ng-options="command.key as command.value for command in commands"
                                        ng-model="post.Hosttemplate.command_id">
                                    </select>
                                    <div class="help-block" ng-hide="post.Hosttemplate.active_checks_enabled">
                                        <?php echo __('Due to active checking is disabled, this command will only be used as freshness check command.'); ?>
                                    </div>
                                    <div ng-repeat="error in errors.command_id">
                                        <div class="help-block text-danger">{{ error }}</div>
                                    </div>
                                </div>

                                <div class="form-group"
                                     ng-class="{'has-error': errors.hosttemplatecommandargumentvalues}"
                                     ng-repeat="hosttemplatecommandargumentvalue in post.Hosttemplate.hosttemplatecommandargumentvalues">
                                    <label class="col-xs-12 col-lg-offset-2 col-lg-10 control-label text-purple">
                                        {{hosttemplatecommandargumentvalue.commandargument.human_name}}
                                    </label>
                                    <div class="col-xs-12 col-lg-8">
                                        <input
                                            class="form-control"
                                            type="text"
                                            ng-model="hosttemplatecommandargumentvalue.value">
                                        <div ng-repeat="error in errors.hosttemplatecommandargumentvalues">
                                            <div class="help-block text-danger">{{ error }}</div>
                                        </div>
                                        <div class="help-block">
                                            {{hosttemplatecommandargumentvalue.commandargument.name}}
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group"
                                     ng-show="post.Hosttemplate.command_id > 0 && post.Hosttemplate.hosttemplatecommandargumentvalues.length == 0">
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
                                        interval="post.Hosttemplate.check_interval"></interval-input-directive>
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
                                        interval="post.Hosttemplate.retry_interval"></interval-input-directive>
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
                                                        ng-click="post.Hosttemplate.max_check_attempts = <?php echo h($i) ?>"
                                                        ng-class="{'active': post.Hosttemplate.max_check_attempts == <?php echo h($i); ?>}">
                                                        <?php echo h($i); ?>
                                                    </button>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                        <div class="col-xs-12 col-lg-3">
                                            <div class="input-group" style="width: 100%;">
                                                <input
                                                    class="form-control"
                                                    type="number"
                                                    min="0"
                                                    ng-model="post.Hosttemplate.max_check_attempts">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-lg-offset-2 col-lg-12">
                                        <div class="help-block">
                                            <?php echo __('Number of failed attempts before the host will switch into hard state.'); ?>
                                        </div>
                                        <div class="help-block">
                                            <?php echo __('Worst case time delay until notification command gets executed after state hits a non ok state: '); ?>
                                            <human-time-directive
                                                seconds="(post.Hosttemplate.check_interval + (post.Hosttemplate.max_check_attempts -1) * post.Hosttemplate.retry_interval)"></human-time-directive>
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
                                           ng-model="post.Hosttemplate.notifications_enabled">
                                    <label class="custom-control-label"
                                           for="notificationsEnabled"><?php echo __('Enable notifications'); ?></label>
                                    <div class="help-block">
                                        <?php echo __('This option is used to determine whether or not notifications are enabled for this host.'); ?>
                                    </div>
                                </div>
                                <div class="form-group required" ng-class="{'has-error': errors.notify_period_id}">
                                    <label class="control-label" for="NotifyPeriodSelect">
                                        <?php if ($this->Acl->hasPermission('edit', 'timeperiods')): ?>
                                            <a ui-sref="TimeperiodsEdit({id:post.Hosttemplate.notify_period_id})"
                                               ng-if="post.Hosttemplate.notify_period_id > 0">
                                                <?php echo __('Notification period'); ?>
                                            </a>
                                            <span ng-if="post.Hosttemplate.notify_period_id == 0">
                                                <?php echo __('Notification period'); ?>
                                            </span>
                                        <?php else: ?>
                                            <?php echo __('Notification period'); ?>
                                        <?php endif; ?>
                                    </label>
                                    <select
                                        id="NotifyPeriodSelect"
                                        data-placeholder="<?php echo __('Please choose'); ?>"
                                        class="form-control"
                                        chosen="timeperiods"
                                        ng-options="timeperiod.key as timeperiod.value for timeperiod in timeperiods"
                                        ng-model="post.Hosttemplate.notify_period_id">
                                    </select>
                                    <div ng-repeat="error in errors.notify_period_id">
                                        <div class="help-block text-danger">{{ error }}</div>
                                    </div>
                                </div>

                                <div class="form-group required" ng-class="{'has-error': errors.notification_interval}">
                                    <label class="col-xs-12 col-lg-2 control-label">
                                        <?php echo __('Notification interval'); ?>
                                    </label>
                                    <interval-input-directive
                                        interval="post.Hosttemplate.notification_interval"></interval-input-directive>
                                    <div class="col-xs-12 col-lg-offset-2">
                                        <div ng-repeat="error in errors.notification_interval">
                                            <div class="help-block text-danger">{{ error }}</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group" ng-class="{'has-error': errors.contacts}">
                                    <label class="control-label hintmark" for="ContactsPeriodSelect">
                                        <?php echo __('Contacts'); ?>
                                    </label>
                                    <select
                                        id="ContactsPeriodSelect"
                                        data-placeholder="<?php echo __('Please choose'); ?>"
                                        class="form-control"
                                        chosen="contacts"
                                        multiple
                                        ng-options="contact.key as contact.value for contact in contacts"
                                        ng-model="post.Hosttemplate.contacts._ids">
                                    </select>
                                    <div ng-repeat="error in errors.contacts">
                                        <div class="help-block text-danger">{{ error }}</div>
                                    </div>
                                </div>

                                <div class="form-group" ng-class="{'has-error': errors.contactgroups}">
                                    <label class="control-label hintmark" for="ContactgroupsSelect">
                                        <?php echo __('Contact groups'); ?>
                                    </label>
                                    <select
                                        id="ContactgroupsSelect"
                                        data-placeholder="<?php echo __('Please choose'); ?>"
                                        class="form-control"
                                        chosen="contactgroups"
                                        multiple
                                        ng-options="contactgroup.key as contactgroup.value for contactgroup in contactgroups"
                                        ng-model="post.Hosttemplate.contactgroups._ids">
                                    </select>
                                    <div ng-repeat="error in errors.contactgroups">
                                        <div class="help-block text-danger">{{ error }}</div>
                                    </div>
                                </div>


                                <?php
                                $hostOptions = [
                                    [
                                        'field' => 'notify_on_recovery',
                                        'class' => 'success',
                                        'text'  => __('Recovery')
                                    ],
                                    [
                                        'field' => 'notify_on_down',
                                        'class' => 'danger',
                                        'text'  => __('Down')
                                    ],
                                    [
                                        'field' => 'notify_on_unreachable',
                                        'class' => 'secondary',
                                        'text'  => __('Unreachable')
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
                                                <?php echo __('Host notification options'); ?>
                                            </label>

                                            <div ng-repeat="error in errors.notify_on_recovery">
                                                <div class="text-danger">{{ error }}</div>
                                            </div>
                                        </div>
                                    </legend>
                                    <div class="row">
                                        <?php foreach ($hostOptions as $hostOption): ?>
                                            <div
                                                class="custom-control custom-checkbox margin-bottom-10 custom-control-right"
                                                ng-class="{'has-error': errors.<?php echo $hostOption['field']; ?>}">
                                                <input type="checkbox"
                                                       class="custom-control-input"
                                                       ng-true-value="1"
                                                       ng-false-value="0"
                                                       id="<?php echo $hostOption['field']; ?>"
                                                       ng-model="post.Hosttemplate.<?php echo $hostOption['field']; ?>">
                                                <label for="<?php echo $hostOption['field']; ?>"
                                                       class="col col-md-9 custom-control-label custom-control-label-<?php echo $hostOption['class']; ?> padding-top-0 margin-right-10 ">
                                                    <span
                                                        class="badge badge-<?php echo $hostOption['class']; ?> notify-label-small">
                                                        <?php echo $hostOption['text']; ?>
                                                    </span>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </fieldset>
                            </div>
                        </div>
                        <!-- NOTIFICATION CONFIGURATION END -->

                        <!-- MISC CONFIGURATION START -->
                        <div class="card margin-bottom-10">
                            <div class="card-header">
                                <i class="fa fa-wrench"></i> <?php echo __('Misc. configuration'); ?>
                            </div>
                            <div class="card-body">
                                <div class="form-group" ng-class="{'has-error': errors.host_url}">
                                    <label class="control-label">
                                        <?php echo __('Host URL'); ?>
                                    </label>
                                    <input
                                        id="HosttemplateURL"
                                        class="form-control"
                                        placeholder="https://issues.example.org?host=$HOSTNAME$"
                                        type="text"
                                        ng-model="post.Hosttemplate.host_url">
                                    <div class="help-block">
                                        <?php echo __('The macros $HOSTID$, $HOSTNAME$, $HOSTDISPLAYNAME$ and $HOSTADDRESS$ will be replaced'); ?>
                                    </div>
                                    <div ng-repeat="error in errors.host_url">
                                        <div class="help-block text-danger">{{ name }}</div>
                                    </div>
                                </div>

                                <div class="form-group" ng-class="{'has-error': errors.notes}">
                                    <label class="control-label">
                                        <?php echo __('Notes'); ?>
                                    </label>
                                    <input
                                        id="HosttemplateNotes"
                                        class="form-control"
                                        type="text"
                                        ng-model="post.Hosttemplate.notes">
                                    <div ng-repeat="error in errors.notes">
                                        <div class="help-block text-danger">{{ name }}</div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <div class="custom-control custom-checkbox margin-bottom-10"
                                         ng-class="{'has-error': errors.flap_detection_enabled}">
                                        <input type="checkbox"
                                               id="flapDetectionEnabled"
                                               class="custom-control-input"
                                               ng-true-value="1"
                                               ng-false-value="0"
                                               ng-model="post.Hosttemplate.flap_detection_enabled">
                                        <label class="custom-control-label" for="flapDetectionEnabled">
                                            <?php echo __('Flap detection enabled'); ?>
                                        </label>
                                    </div>
                                </div>

                                <?php
                                $hostFlapOptions = [
                                    [
                                        'field' => 'flap_detection_on_up',
                                        'class' => 'success',
                                        'text'  => __('Recovery')
                                    ],
                                    [
                                        'field' => 'flap_detection_on_down',
                                        'class' => 'danger',
                                        'text'  => __('Down')
                                    ],
                                    [
                                        'field' => 'flap_detection_on_unreachable',
                                        'class' => 'secondary',
                                        'text'  => __('Unreachable')
                                    ]
                                ];
                                ?>

                                <fieldset ng-show="post.Hosttemplate.flap_detection_enabled">
                                    <legend class="fs-sm"
                                            ng-class="{'has-error-no-form': errors.flap_detection_on_up}">
                                        <div ng-class="{'required':post.Hosttemplate.flap_detection_enabled}">
                                            <label class="fs-sm">
                                                <?php echo __('Flap detection options'); ?>
                                            </label>

                                            <div ng-repeat="error in errors.flap_detection_on_up">
                                                <div class="text-danger">{{ error }}</div>
                                            </div>
                                        </div>
                                    </legend>
                                    <div class="row">
                                        <?php foreach ($hostFlapOptions as $hostFlapOption): ?>
                                            <div
                                                class="custom-control custom-checkbox margin-bottom-10 custom-control-right"
                                                ng-class="{'has-error': errors.<?php echo $hostFlapOption['field']; ?>}">
                                                <input type="checkbox" name="checkbox"
                                                       class="custom-control-input"
                                                       ng-true-value="1"
                                                       ng-false-value="0"
                                                       ng-disabled="!post.Hosttemplate.flap_detection_enabled"
                                                       id="<?php echo $hostFlapOption['field']; ?>"
                                                       ng-model="post.Hosttemplate.<?php echo $hostFlapOption['field']; ?>">
                                                <label for="<?php echo $hostFlapOption['field']; ?>"
                                                       class="col col-md-9 custom-control-label custom-control-label-<?php echo $hostFlapOption['class']; ?> padding-top-0 margin-right-10">
                                                    <span
                                                        class="badge badge-<?php echo $hostFlapOption['class']; ?> notify-label-small">
                                                        <?php echo $hostFlapOption['text']; ?>
                                                    </span>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </fieldset>
                            </div>
                        </div>
                        <!-- MISC CONFIGURATION END -->

                        <!-- HOST MACRO CONFIGURATION START -->
                        <div class="card margin-bottom-10">
                            <div class="card-header">
                                <i class="fa fa-usd"></i> <?php echo __('Host macro configuration'); ?>
                            </div>
                            <div class="card-body" ng-class="{'has-error-no-form': errors.customvariables_unique}">
                                <div class="row">
                                    <div ng-repeat="error in errors.customvariables_unique">
                                        <div class=" col-lg-12 text-danger">{{ error }}</div>
                                    </div>
                                </div>

                                <div class="row" ng-repeat="customvariable in post.Hosttemplate.customvariables">
                                    <macros-directive macro="customvariable"
                                                      macro-name="'<?php echo __('HOST'); ?>'"
                                                      index="$index"
                                                      callback="deleteMacroCallback"
                                                      errors="getMacroErrors($index)"
                                                      class="col-lg-12"
                                    ></macros-directive>
                                </div>

                                <div class="row">
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
                        <!-- HOST MACRO CONFIGURATION END -->

                        <?php if (\Cake\Core\Plugin::isLoaded('PrometheusModule')): ?>
                            <!-- PROMETHEUS CONFIGURATION START -->
                            <div class="card margin-bottom-10">
                                <div class="card-header">
                                    <i class="fas fa-broadcast-tower"></i> <?php echo __('Prometheus Exporters'); ?>
                                </div>
                                <div class="card-body">
                                    <div class="form-group" ng-class="{'has-error': errors.prometheus_exporters}">
                                        <label class="control-label" for="ExportersSelect">
                                            <?php echo __('Exporters'); ?>
                                        </label>
                                        <select
                                            id="ExportersSelect"
                                            data-placeholder="<?php echo __('Please choose'); ?>"
                                            class="form-control"
                                            chosen="exporters"
                                            multiple
                                            ng-options="exporter.key as exporter.value for exporter in exporters"
                                            ng-model="post.Hosttemplate.prometheus_exporters._ids">
                                        </select>
                                        <div class="help-block">
                                            <?php echo __('To monitor this host using Prometheus please select the exporters that are installed on the host.'); ?>
                                        </div>
                                        <div ng-repeat="error in errors.prometheus_exporters">
                                            <div class="help-block text-danger">{{ error }}</div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                            <!-- PROMETHEUS CONFIGURATION END -->
                        <?php endif; ?>

                        <?php if (\Cake\Core\Plugin::isLoaded('SLAModule')): ?>
                            <!-- SLA CONFIGURATION START -->
                            <div class="card margin-bottom-10">
                                <div class="card-header">
                                    <i class="fa-solid fa-award"></i> <?= __('Service Level Agreement'); ?>
                                </div>
                                <div class="card-body">
                                    <div class="form-group" ng-class="{'has-error': errors.slas}">
                                        <label class="control-label" for="SlasSelect">
                                            <?php if ($this->Acl->hasPermission('edit', 'slas', 'SLAModule')): ?>
                                                <a ui-sref="SlasEdit({id:post.Hosttemplate.sla_id})"
                                                   ng-show="post.Hosttemplate.sla_id">
                                                    <?= __('Service class'); ?>
                                                </a>
                                                <span ng-hide="post.Hosttemplate.sla_id">
                                                        <?= __('Service class'); ?>
                                                    </span>
                                            <?php else: ?>
                                                <?= __('Service class'); ?>
                                            <?php endif; ?>
                                        </label>
                                        <select
                                            id="SlasSelect"
                                            data-placeholder="<?php echo __('Please choose'); ?>"
                                            class="form-control"
                                            chosen="slas"
                                            ng-options="sla.key as sla.value for sla in slas"
                                            ng-model="post.Hosttemplate.sla_id">
                                            <option></option>
                                        </select>
                                        <div class="help-block">
                                            <?= __('You can define service levels for all hosts using this host template.'); ?>
                                        </div>
                                        <div ng-repeat="error in errors.slas">
                                            <div class="help-block text-danger">{{ error }}</div>
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
                                        <?php echo __('Create host template'); ?>
                                    </button>
                                    <a back-button href="javascript:void(0);" fallback-state='HosttemplatesIndex'
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
