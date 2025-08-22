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
$timezones = \itnovum\openITCOCKPIT\Core\Timezone::listTimezones();
?>
<ol class="breadcrumb page-breadcrumb">
    <li class="breadcrumb-item">
        <a ui-sref="DashboardsIndex">
            <i class="fa fa-home"></i> <?php echo __('Home'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <a ui-sref="ServiceescalationsIndex">
            <i class="fa fa-bomb"></i> <?php echo __('Service escalation'); ?>
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
                    <?php echo __('Create new service escalation'); ?>
                </h2>
                <div class="panel-toolbar">
                    <?php if ($this->Acl->hasPermission('index', 'serviceescalations')): ?>
                        <a back-button href="javascript:void(0);" fallback-state='ServiceescalationsIndex'
                           class="btn btn-default btn-xs mr-1 shadow-0">
                            <i class="fas fa-long-arrow-alt-left"></i> <?php echo __('Back'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <form class="form-horizontal" ng-init="successMessage=
            {objectName : '<?php echo __('Service escalation'); ?>' , message: '<?php echo __('created successfully'); ?>'}">
                        <div class="form-group required" ng-class="{'has-error': errors.container_id}">
                            <label class="control-label" for="ServiceescalationContainer">
                                <?php echo __('Container'); ?>
                            </label>
                            <select
                                id="ServiceescalationContainer"
                                data-placeholder="<?php echo __('Please choose'); ?>"
                                class="form-control"
                                chosen="containers"
                                ng-options="container.key as container.value for container in containers"
                                ng-model="post.Serviceescalation.container_id">
                            </select>
                            <div ng-show="post.Serviceescalation.container_id < 1" class="warning-glow">
                                <?php echo __('Please select a container.'); ?>
                            </div>
                            <div class="help-block">
                                <?php echo __('Notification escalations could be used to notify a certain user group in case of an emergency.
Once a service escalated, contacts, contact group and notification options will be overwritten by the escalation.'); ?>
                            </div>
                            <div ng-repeat="error in errors.container_id">
                                <div class="help-block text-danger">{{ error }}</div>
                            </div>
                        </div>

                        <div
                            class="col col-12 bordered-vertical-on-left-primary bg-color-lightGray-with-opacity padding-10">
                            <div class="form-group required" ng-class="{'has-error': errors.services}">
                                <label class="control-label">
                                    <i class="fa fa-plus up" aria-hidden="true"></i>
                                    <?php echo __('Services'); ?>
                                </label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-success text-white">
                                            <i class="fas fa-cog"></i>
                                        </span>
                                    </div>
                                    <select
                                        id="ServiceescalationIncludeServices"
                                        data-placeholder="<?php echo __('Please choose'); ?>"
                                        class="custom-select"
                                        multiple
                                        chosen="services"
                                        callback="loadServices"
                                        ng-options="service.key as (service.value.disabled == 0)?service.value.servicename:service.value.servicename+'🔌' group by service.value._matchingData.Hosts.name disable when service.value.disabled == 1 for service in services"
                                        ng-model="post.Serviceescalation.services._ids">
                                    </select>
                                </div>
                                <div ng-repeat="error in errors.services">
                                    <div class="help-block text-danger">{{ error }}</div>
                                </div>
                            </div>

                            <div class="form-group arrowd-vertical-on-right"
                                 ng-class="{'has-error': errors.servicegroups_excluded}">
                                <label class="control-label">
                                    <i class="fa fa-minus down" aria-hidden="true"></i>
                                    <?php echo __('Excluded service groups'); ?>
                                </label>

                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-danger text-white">
                                            <i class="fas fa-cogs"></i>
                                        </span>
                                    </div>
                                    <select
                                        id="ServiceescalationExcludeServicegroups"
                                        data-placeholder="<?php echo __('Please choose'); ?>"
                                        class="custom-select"
                                        multiple
                                        chosen="servicegroups_excluded"
                                        ng-disabled="post.Serviceescalation.services._ids.length == 0"
                                        ng-options="servicegroup.key as servicegroup.value disable when servicegroup.disabled for servicegroup in servicegroups_excluded"
                                        ng-model="post.Serviceescalation.servicegroups_excluded._ids">
                                    </select>
                                </div>
                                <div ng-repeat="error in errors.servicegroups_excluded">
                                    <div class="help-block text-danger">{{ error }}</div>
                                </div>
                            </div>
                        </div>

                        <div
                            class="col col-12 margin-top-10 margin-bottom-10 bordered-vertical-on-left-primary bg-color-lightGray-with-opacity padding-10">
                            <div class="form-group" ng-class="{'has-error': errors.servicegroups}">
                                <label class="control-label">
                                    <i class="fa fa-plus up" aria-hidden="true"></i>
                                    <?php echo __('Service groups'); ?>
                                </label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-success text-white">
                                            <i class="fas fa-cogs"></i>
                                        </span>
                                    </div>
                                    <select
                                        id="ServiceescalationIncludeServicegroups"
                                        data-placeholder="<?php echo __('Please choose'); ?>"
                                        class="custom-select"
                                        multiple
                                        chosen="servicegroups"
                                        ng-options="servicegroup.key as servicegroup.value disable when servicegroup.disabled for servicegroup in servicegroups"
                                        ng-model="post.Serviceescalation.servicegroups._ids">
                                    </select>
                                </div>
                                <div ng-repeat="error in errors.servicegroups">
                                    <div class="help-block text-danger">{{ error }}</div>
                                </div>
                            </div>

                            <div class="form-group form-group arrowd-vertical-on-right"
                                 ng-class="{'has-error': errors.services_excluded}">
                                <label class="control-label">
                                    <i class="fa fa-minus down" aria-hidden="true"></i>
                                    <?php echo __('Excluded Services'); ?>
                                </label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-danger text-white">
                                            <i class="fas fa-cog"></i>
                                        </span>
                                    </div>
                                    <select
                                        id="ServiceescalationExcludeServices"
                                        data-placeholder="<?php echo __('Please choose'); ?>"
                                        class="custom-select"
                                        multiple
                                        chosen="services_excluded"
                                        callback="loadExcludedServices"
                                        ng-disabled="post.Serviceescalation.servicegroups._ids.length == 0"
                                        ng-options="service.key as (service.value.disabled == 0)?service.value.servicename:service.value.servicename+'🔌' group by service.value._matchingData.Hosts.name disable when service.value.disabled == 1 for service in services_excluded"
                                        ng-model="post.Serviceescalation.services_excluded._ids">
                                    </select>
                                </div>
                                <div ng-repeat="error in errors.services_excluded">
                                    <div class="help-block text-danger">{{ error }}</div>
                                </div>
                            </div>
                        </div>


                        <div class="form-group required" ng-class="{'has-error': errors.first_notification}">
                            <label class="control-label">
                                <?php echo __('First notification'); ?>
                            </label>
                            <input
                                class="form-control"
                                type="number"
                                min="0"
                                placeholder="0"
                                ng-model="post.Serviceescalation.first_notification">
                            <div class="help-block">
                                <?php echo __('Number of notifications that passed before the escalation rule will
                                    overwrite notification settings.'); ?>
                            </div>
                            <div ng-repeat="error in errors.first_notification">
                                <div class="help-block text-danger">{{ error }}</div>
                            </div>
                        </div>

                        <div class="form-group required" ng-class="{'has-error': errors.last_notification}">
                            <label class="control-label">
                                <?php echo __('Last notification'); ?>
                            </label>
                            <input
                                class="form-control"
                                type="number"
                                min="0"
                                placeholder="0"
                                ng-model="post.Serviceescalation.last_notification">
                            <div class="help-block">
                                <?php echo __('If number of last_notification is reached, the notification rule
                                    will be disabled and the notification options of the service will be used again.'); ?>
                            </div>
                            <div ng-repeat="error in errors.last_notification">
                                <div class="help-block text-danger">{{ error }}</div>
                            </div>
                        </div>

                        <div class="form-group required"
                             ng-class="{'has-error': errors.notification_interval}">
                            <label class="control-label">
                                <?php echo __('Notification interval'); ?>
                            </label>
                            <interval-input-directive
                                interval="post.Serviceescalation.notification_interval"></interval-input-directive>
                            <div class="col-xs-12 col-lg-offset-2">
                                <div ng-repeat="error in errors.notification_interval">
                                    <div class="help-block text-danger">{{ error }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="control-label" for="ServiceescalationTimeperiod">
                                <?php if ($this->Acl->hasPermission('edit', 'timeperiods')): ?>
                                    <a ui-sref="TimeperiodsEdit({id:post.Serviceescalation.timeperiod_id})"
                                       ng-if="post.Serviceescalation.timeperiod_id > 0">
                                        <?php echo __('Escalation period'); ?>
                                    </a>
                                    <span
                                        ng-if="!post.Serviceescalation.timeperiod_id"><?php echo __('Escalation period'); ?></span>
                                <?php else: ?>
                                    <?php echo __('Escalation period'); ?>
                                <?php endif; ?>
                            </label>
                            <select
                                id="ServiceescalationTimeperiod"
                                data-placeholder="<?php echo __('Please choose a escalation timeperiod'); ?>"
                                class="form-control"
                                chosen="timeperiods"
                                ng-options="timeperiod.key as timeperiod.value for timeperiod in timeperiods"
                                ng-model="post.Serviceescalation.timeperiod_id">
                                <option></option>
                            </select>
                        </div>

                        <div class="form-group" ng-class="{'has-error': errors.contacts}">
                            <label class="control-label hintmark">
                                <?php echo __('Contacts'); ?>
                            </label>
                            <div class="input-group">
                                <select
                                    id="ServiceescalationContacts"
                                    data-placeholder="<?php echo __('Please choose'); ?>"
                                    class="custom-select"
                                    multiple
                                    chosen="contacts"
                                    ng-options="contact.key as contact.value for contact in contacts"
                                    ng-model="post.Serviceescalation.contacts._ids">
                                </select>
                            </div>
                            <div ng-repeat="error in errors.contacts">
                                <div class="help-block text-danger">{{ error }}</div>
                            </div>
                        </div>

                        <div class="form-group" ng-class="{'has-error': errors.contactgroups}">
                            <label class="control-label hintmark">
                                <?php echo __('Contactgroups'); ?>
                            </label>
                            <div class="input-group">
                                <select
                                    id="ServiceescalationContactgroups"
                                    data-placeholder="<?php echo __('Please choose'); ?>"
                                    class="custom-select"
                                    multiple
                                    chosen="contactgroups"
                                    ng-options="contactgroup.key as contactgroup.value for contactgroup in contactgroups"
                                    ng-model="post.Serviceescalation.contactgroups._ids">
                                </select>
                            </div>
                            <div ng-repeat="error in errors.contactgroups">
                                <div class="help-block text-danger">{{ error }}</div>
                            </div>
                        </div>
                        <fieldset>
                            <legend class="fs-md">
                                <div class="required">
                                    <label>
                                        <?php echo __('Escalation options'); ?>
                                    </label>
                                    <div class="help-block">
                                        <?= __('Enables the escalation for the selected states'); ?>
                                    </div>
                                </div>
                            </legend>
                            <div class="custom-control custom-checkbox margin-bottom-10"
                                 ng-class="{'has-error': errors.escalate_on_recovery}">
                                <input type="checkbox" class="custom-control-input"
                                       ng-true-value="1"
                                       ng-false-value="0"
                                       id="escalate_on_recovery"
                                       ng-model="post.Serviceescalation.escalate_on_recovery">
                                <label class="custom-control-label"
                                       for="escalate_on_recovery">
                                    <span class="badge badge-success notify-label"><?php echo __('Recovery'); ?></span>
                                    <i class="checkbox-success"></i>
                                </label>
                            </div>

                            <div class="custom-control custom-checkbox margin-bottom-10"
                                 ng-class="{'has-error': errors.escalate_on_down}">
                                <input type="checkbox" class="custom-control-input"
                                       ng-true-value="1"
                                       ng-false-value="0"
                                       id="escalate_on_warning"
                                       ng-model="post.Serviceescalation.escalate_on_warning">
                                <label class="custom-control-label"
                                       for="escalate_on_warning">
                                    <span class="badge badge-warning notify-label"><?php echo __('Warning'); ?></span>
                                    <i class="checkbox-warning"></i>
                                </label>
                            </div>

                            <div class="custom-control custom-checkbox margin-bottom-10"
                                 ng-class="{'has-error': errors.escalate_on_critical}">
                                <input type="checkbox" class="custom-control-input"
                                       ng-true-value="1"
                                       ng-false-value="0"
                                       id="escalate_on_critical"
                                       ng-model="post.Serviceescalation.escalate_on_critical">
                                <label class="custom-control-label"
                                       for="escalate_on_critical">
                                    <span class="badge badge-danger notify-label"><?php echo __('Critical'); ?></span>
                                    <i class="checkbox-danger"></i>
                                </label>
                            </div>

                            <div class="custom-control custom-checkbox margin-bottom-10"
                                 ng-class="{'has-error': errors.escalate_on_unknown}">
                                <input type="checkbox" class="custom-control-input"
                                       ng-true-value="1"
                                       ng-false-value="0"
                                       id="escalate_on_unknown"
                                       ng-model="post.Serviceescalation.escalate_on_unknown">
                                <label class="custom-control-label"
                                       for="escalate_on_unknown">
                                    <span class="badge badge-secondary notify-label"><?php echo __('Unknown'); ?></span>
                                    <i class="checkbox-secondary"></i>
                                </label>
                            </div>
                        </fieldset>
                        <div class="card margin-top-10">
                            <div class="card-body">
                                <div class="float-right">
                                    <button class="btn btn-primary"
                                            type="submit"
                                            ng-click="submit()"><?php echo __('Create service escalation'); ?></button>
                                    <a back-button href="javascript:void(0);" fallback-state='ServiceescalationsIndex'
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
