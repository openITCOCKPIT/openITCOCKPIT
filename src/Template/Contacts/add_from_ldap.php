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
        <a ui-sref="ContactsIndex">
            <i class="fa fa-user"></i> <?php echo __('Contacts'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <i class="fa fa-plus"></i> <?php echo __('Import from LDAP'); ?>
    </li>
</ol>

<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?php echo __('Import contact from LDAP'); ?>
                </h2>
                <div class="panel-toolbar">
                    <?php if ($this->Acl->hasPermission('index', 'contacts')): ?>
                        <a back-button href="javascript:void(0);" fallback-state='ContactsIndex' class="btn btn-default btn-xs mr-1 shadow-0">
                            <i class="fas fa-long-arrow-alt-left"></i> <?php echo __('Back'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="panel-container show">
                <div class="panel-content">
                    <form ng-submit="submit();" ng-init="successMessage=
            {objectName : '<?php echo __('Contact'); ?>' , message: '<?php echo __('created successfully'); ?>'}">
                        <div class="form-group required" ng-class="{'has-error': errors.containers}">
                            <label class="control-label" for="ContactContainers">
                                <?php echo __('Container'); ?>
                            </label>
                            <select
                                id="ContactContainers"
                                data-placeholder="<?php echo __('Please choose'); ?>"
                                class="form-control"
                                chosen="containers"
                                multiple
                                ng-options="container.key as container.value for container in containers"
                                ng-model="post.Contact.containers._ids">
                            </select>
                            <div ng-repeat="error in errors.containers">
                                <div class="help-block text-danger">{{ error }}</div>
                            </div>
                        </div>

                        <div class="form-group required" ng-class="{'has-error': errors.samaccountname}">
                            <label class="control-label" for="SAMAccountName">
                                <?php echo __('SAM-Account-Name'); ?>
                            </label>
                            <select
                                id="SAMAccountName"
                                data-placeholder="<?php echo __('Please choose'); ?>"
                                class="form-control"
                                chosen="ldapUsers"
                                callback="loadLdapUsersByString"
                                ng-options="key as ldapUser.display_name for (key, ldapUser) in ldapUsers"
                                ng-model="data.selectedSamAccountNameIndex">
                            </select>
                            <div ng-repeat="error in errors.samaccountname">
                                <div class="help-block text-danger">{{ error }}</div>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="display-inline-block position-absolute">
                                <i class="fa fa-info-circle text-info"></i>
                            </div>
                            <div class="text-info display-inline-block margin-left-20">
                                <?php echo __('Connected LDAP server'); ?>:
                                <b>{{ldapConfig.host}}</b>
                                <br/>
                                <?php echo __('Used filter query'); ?>:
                                <b>{{ldapConfig.query}}</b>
                                <br/>
                                <?php echo __('Base DN'); ?>:
                                <b>{{ldapConfig.base_dn}}</b>
                            </div>
                        </div>

                        <div ng-show="data.selectedSamAccountNameIndex">
                            <div class="form-group required" ng-class="{'has-error': errors.name}">
                                <label class="control-label">
                                    <?php echo __('Name'); ?>
                                </label>
                                <input
                                    class="form-control"
                                    type="text"
                                    ng-model="post.Contact.name">
                                <div ng-repeat="error in errors.name">
                                    <div class="help-block text-danger">{{ error }}</div>
                                </div>
                            </div>

                            <div class="form-group" ng-class="{'has-error': errors.description}">
                                <label class="control-label">
                                    <?php echo __('Description'); ?>
                                </label>
                                <input
                                    class="form-control"
                                    type="text"
                                    ng-model="post.Contact.description">
                                <div ng-repeat="error in errors.description">
                                    <div class="help-block text-danger">{{ error }}</div>
                                </div>
                            </div>

                            <div class="form-group" ng-class="{'has-error': errors.email}">
                                <label class="control-label hintmark">
                                    <?php echo __('Email'); ?>
                                </label>
                                <input
                                    class="form-control"
                                    type="email"
                                    placeholder="user@example.org"
                                    readonly="readonly"
                                    ng-model="post.Contact.email">
                                <div ng-repeat="error in errors.email">
                                    <div class="help-block text-danger">{{ error }}</div>
                                </div>
                                <div class="help-block">
                                    <?php echo __('Imported value from LDAP.'); ?>
                                </div>
                            </div>

                            <div class="form-group" ng-class="{'has-error': errors.phone}">
                                <label class="control-label hintmark">
                                    <?php echo __('Phone'); ?>
                                </label>
                                <input
                                    class="form-control"
                                    type="text"
                                    placeholder="0049123456789"
                                    ng-model="post.Contact.phone">
                                <div ng-repeat="error in errors.phone">
                                    <div class="help-block text-danger">{{ error }}</div>
                                </div>
                            </div>

                            <div class="form-group" ng-class="{'has-error': errors.user_id}">
                                <label class="control-label">
                                    <?php echo __('User'); ?>
                                </label>
                                <select
                                    id="ContactUser"
                                    data-placeholder="<?php echo __('Please choose'); ?>"
                                    class="form-control"
                                    chosen="users"
                                    ng-options="user.key as user.value for user in users"
                                    ng-model="post.Contact.user_id">
                                </select>
                                <div ng-repeat="error in errors.user_id">
                                    <div class="help-block text-danger">{{ error }}</div>
                                </div>
                                <div class="help-block">
                                    <?php echo __('For browser notifications, a user needs to be assigned to the contact. User Id will be automatically available as $_CONTACTOITCUSERID$ contact macro.') ?>
                                </div>
                            </div>


                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <i class="fa fa-desktop"></i>
                                            <?php echo __('Host notification'); ?>
                                        </div>
                                        <div class="card-body">
                                            <div class="form-group required"
                                                 ng-class="{'has-error': errors.host_timeperiod_id}">
                                                <label class="control-label">
                                                    <?php echo __('Host time period'); ?>
                                                </label>
                                                <select
                                                    id="HostTimeperiodSelect"
                                                    data-placeholder="<?php echo __('Please choose'); ?>"
                                                    class="form-control"
                                                    chosen="timeperiods"
                                                    ng-options="timeperiod.key as timeperiod.value for timeperiod in timeperiods"
                                                    ng-model="post.Contact.host_timeperiod_id">
                                                </select>
                                                <div ng-repeat="error in errors.host_timeperiod_id">
                                                    <div class="help-block text-danger">{{ error }}</div>
                                                </div>
                                            </div>

                                            <div class="form-group required" ng-class="{'has-error': errors.host_commands}">
                                                <label class="control-label">
                                                    <?php echo __('Host commands'); ?>
                                                </label>
                                                <select
                                                    id="HostCommands"
                                                    data-placeholder="<?php echo __('Please choose'); ?>"
                                                    class="form-control"
                                                    chosen="commands"
                                                    multiple
                                                    ng-options="command.key as command.value for command in commands"
                                                    ng-model="post.Contact.host_commands._ids">
                                                </select>
                                                <div ng-repeat="error in errors.host_commands">
                                                    <div class="help-block text-danger">{{ error }}</div>
                                                </div>
                                            </div>


                                            <div class="custom-control custom-checkbox margin-bottom-10"
                                                 ng-class="{'has-error': errors.host_notifications_enabled}">
                                                <input type="checkbox" class="custom-control-input"
                                                       ng-true-value="1"
                                                       ng-false-value="0"
                                                       id="hostNotificationsEnabled"
                                                       ng-model="post.Contact.host_notifications_enabled">
                                                <label class="custom-control-label"
                                                       for="hostNotificationsEnabled"><?php echo __('Host notifications enabled'); ?></label>
                                            </div>

                                            <div class="custom-control custom-checkbox"
                                                 ng-class="{'has-error': errors.host_push_notifications_enabled}">
                                                <input type="checkbox" class="custom-control-input"
                                                       ng-true-value="1"
                                                       ng-false-value="0"
                                                       id="hostPushNotificationEnabled"
                                                       ng-model="post.Contact.host_push_notifications_enabled">
                                                <label class="custom-control-label" for="hostPushNotificationEnabled">
                                                    <?php echo __('Push notifications to browser'); ?>
                                                    <i class="fa fa-info-circle text-info"
                                                       data-template="<div class='tooltip' role='tooltip'><div class='tooltip-arrow tooltip-arrow-image'></div><div class='tooltip-inner tooltip-inner-image'></div></div>"
                                                       rel="tooltip"
                                                       data-placement="right"
                                                       data-original-title="<img src='/img/browser_notification_bg.png'/>"
                                                       data-html="true"></i>
                                                </label>
                                            </div>


                                            <br class="clearfix"/>

                                            <?php
                                            $hostOptions = [
                                                [
                                                    'field' => 'notify_host_recovery',
                                                    'class' => 'success',
                                                    'text'  => __('Recovery')
                                                ],
                                                [
                                                    'field' => 'notify_host_down',
                                                    'class' => 'danger',
                                                    'text'  => __('Down')
                                                ],
                                                [
                                                    'field' => 'notify_host_unreachable',
                                                    'class' => 'secondary',
                                                    'text'  => __('Unreachable')
                                                ],
                                                [
                                                    'field' => 'notify_host_flapping',
                                                    'class' => 'primary',
                                                    'text'  => __('Flapping')
                                                ],
                                                [
                                                    'field' => 'notify_host_downtime',
                                                    'class' => 'primary',
                                                    'text'  => __('Downtime')
                                                ],
                                            ];
                                            ?>
                                            <hr>
                                            <fieldset>
                                                <legend class="fs-md"
                                                        ng-class="{'has-error-no-form': errors.notify_host_recovery}">
                                                    <div class="required">
                                                        <label>
                                                            <?php echo __('Host notification options'); ?>
                                                        </label>

                                                        <div ng-repeat="error in errors.notify_host_recovery">
                                                            <div class="text-danger">{{ error }}</div>
                                                        </div>

                                                    </div>
                                                </legend>
                                                <?php foreach ($hostOptions as $hostOption): ?>
                                                    <div class="custom-control custom-checkbox margin-bottom-10"
                                                         ng-class="{'has-error': errors.<?php echo $hostOption['field']; ?>}">
                                                        <input type="checkbox" class="custom-control-input"
                                                               ng-true-value="1"
                                                               ng-false-value="0"
                                                               id="<?php echo $hostOption['field']; ?>"
                                                               ng-model="post.Contact.<?php echo $hostOption['field']; ?>">
                                                        <label class="custom-control-label"
                                                               for="<?php echo $hostOption['field']; ?>">
                                                        <span
                                                            class="badge badge-<?php echo $hostOption['class']; ?> notify-label"><?php echo $hostOption['text']; ?></span>
                                                            <i class="checkbox-<?php echo $hostOption['class']; ?>"></i>
                                                        </label>
                                                    </div>
                                                <?php endforeach; ?>
                                            </fieldset>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <i class="fa fa-cog"></i>
                                            <?php echo __('Service notification'); ?>
                                        </div>
                                        <div class="card-body">
                                            <div class="form-group required"
                                                 ng-class="{'has-error': errors.service_timeperiod_id}">
                                                <label class="control-label">
                                                    <?php echo __('Service time period'); ?>
                                                </label>
                                                <select
                                                    id="ServiceTimeperiodSelect"
                                                    data-placeholder="<?php echo __('Please choose'); ?>"
                                                    class="form-control"
                                                    chosen="timeperiods"
                                                    ng-options="timeperiod.key as timeperiod.value for timeperiod in timeperiods"
                                                    ng-model="post.Contact.service_timeperiod_id">
                                                </select>
                                                <div ng-repeat="error in errors.service_timeperiod_id">
                                                    <div class="help-block text-danger">{{ error }}</div>
                                                </div>
                                            </div>

                                            <div class="form-group required"
                                                 ng-class="{'has-error': errors.service_commands}">
                                                <label class="control-label">
                                                    <?php echo __('Service commands'); ?>
                                                </label>
                                                <select
                                                    id="ServiceCommands"
                                                    data-placeholder="<?php echo __('Please choose'); ?>"
                                                    class="form-control"
                                                    chosen="commands"
                                                    multiple
                                                    ng-options="command.key as command.value for command in commands"
                                                    ng-model="post.Contact.service_commands._ids">
                                                </select>
                                                <div ng-repeat="error in errors.service_commands">
                                                    <div class="help-block text-danger">{{ error }}</div>
                                                </div>
                                            </div>

                                            <div class="custom-control custom-checkbox margin-bottom-10"
                                                 ng-class="{'has-error': errors.service_notifications_enabled}">
                                                <input type="checkbox" class="custom-control-input"
                                                       ng-true-value="1"
                                                       ng-false-value="0"
                                                       id="serviceNotificationsEnabled"
                                                       ng-model="post.Contact.service_notifications_enabled">
                                                <label class="custom-control-label"
                                                       for="serviceNotificationsEnabled"><?php echo __('Service notifications enabled'); ?></label>
                                            </div>


                                            <div class="custom-control custom-checkbox"
                                                 ng-class="{'has-error': errors.service_push_notifications_enabled}">
                                                <input type="checkbox" class="custom-control-input"
                                                       ng-true-value="1"
                                                       ng-false-value="0"
                                                       id="servicePushNotificationEnabled"
                                                       ng-model="post.Contact.service_push_notifications_enabled">
                                                <label class="custom-control-label" for="servicePushNotificationEnabled">
                                                    <?php echo __('Push notifications to browser'); ?>
                                                    <i class="fa fa-info-circle text-info"
                                                       data-template="<div class='tooltip' role='tooltip'><div class='tooltip-arrow tooltip-arrow-image'></div><div class='tooltip-inner tooltip-inner-image'></div></div>"
                                                       rel="tooltip"
                                                       data-placement="right"
                                                       data-original-title="<img src='/img/browser_service_notification_bg.png'/>"
                                                       data-html="true"></i>
                                                </label>
                                            </div>

                                            <br class="clearfix"/>

                                            <?php
                                            $serviceOptions = [
                                                [
                                                    'field' => 'notify_service_recovery',
                                                    'class' => 'success',
                                                    'text'  => __('Recovery')
                                                ],
                                                [
                                                    'field' => 'notify_service_warning',
                                                    'class' => 'warning',
                                                    'text'  => __('Warning')
                                                ],
                                                [
                                                    'field' => 'notify_service_critical',
                                                    'class' => 'danger',
                                                    'text'  => __('Critical')
                                                ],
                                                [
                                                    'field' => 'notify_service_unknown',
                                                    'class' => 'secondary',
                                                    'text'  => __('Unknown')
                                                ],
                                                [
                                                    'field' => 'notify_service_flapping',
                                                    'class' => 'primary',
                                                    'text'  => __('Flapping')
                                                ],
                                                [
                                                    'field' => 'notify_service_downtime',
                                                    'class' => 'primary',
                                                    'text'  => __('Downtime')
                                                ],
                                            ];
                                            ?>
                                            <hr>
                                            <fieldset>
                                                <legend class="fs-md"
                                                        ng-class="{'has-error-no-form': errors.notify_service_recovery}">
                                                    <div class="required">
                                                        <label>
                                                            <?php echo __('Service notification options'); ?>
                                                        </label>

                                                        <div ng-repeat="error in errors.notify_service_recovery">
                                                            <div class="text-danger">{{ error }}</div>
                                                        </div>
                                                    </div>
                                                </legend>
                                                <?php foreach ($serviceOptions as $serviceOption): ?>
                                                    <div class="custom-control custom-checkbox margin-bottom-10"
                                                         ng-class="{'has-error': errors.<?php echo $serviceOption['field']; ?>}">
                                                        <input type="checkbox" class="custom-control-input"
                                                               ng-true-value="1"
                                                               ng-false-value="0"
                                                               id="<?php echo $serviceOption['field']; ?>"
                                                               ng-model="post.Contact.<?php echo $serviceOption['field']; ?>">
                                                        <label class="custom-control-label"
                                                               for="<?php echo $serviceOption['field']; ?>">
                                                        <span
                                                            class="badge badge-<?php echo $serviceOption['class']; ?> notify-label"><?php echo $serviceOption['text']; ?></span>
                                                            <i class="checkbox-<?php echo $serviceOption['class']; ?>"></i>
                                                        </label>
                                                    </div>
                                                <?php endforeach; ?>
                                            </fieldset>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?php if ($this->Acl->hasPermission('wiki', 'documentations')): ?>
                                <div class="row padding-bottom-10">
                                    <div class="col-xs-12 col-lg-12 text-info">
                                        <i class="fa fa-info-circle"></i>
                                        <?php echo __('Read more about browser push notification in the'); ?>
                                        <a ui-sref="DocumentationsWiki({documentation:'additional_help:browser_push_notifications'})">
                                            <?php echo __('documentation'); ?>
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <hr>
                            <fieldset class="padding-10 ng-binding">
                                <legend class="fs-md" ng-class="{'has-error-no-form': errors.customvariables_unique}">
                                    <div>
                                        <label>
                                            <?php echo __('Define contact macros'); ?>
                                        </label>

                                        <div ng-repeat="error in errors.customvariables_unique">
                                            <div class="text-danger">{{ error }}</div>
                                        </div>

                                    </div>
                                </legend>
                                <div class="row" ng-repeat="customvariable in post.Contact.customvariables">

                                    <macros-directive macro="customvariable"
                                                      macro-name="'<?php echo __('CONTACT'); ?>'"
                                                      index="$index"
                                                      callback="deleteMacroCallback"
                                                      errors="getMacroErrors($index)"
                                                      class="col-lg-12"
                                    ></macros-directive>
                                </div>

                                <div class="row">
                                    <div class="col-lg-12 padding-top-10 text-right">
                                        <button type="button" class="btn btn-success btn-sm" ng-click="addMacro()">
                                            <i class="fa fa-plus"></i>
                                            <?php echo __('Add new macro'); ?>
                                        </button>
                                    </div>
                                </div>
                            </fieldset>


                            <div class="card margin-top-10">
                                <div class="card-body">
                                    <div class="float-right">
                                        <label>
                                            <input type="checkbox" ng-model="data.createAnother">
                                            <?php echo __('Create another'); ?>
                                        </label>
                                        <button class="btn btn-primary"
                                                type="submit"><?php echo __('Create contact'); ?></button>
                                        <a back-button href="javascript:void(0);" fallback-state='ContactsIndex'
                                           class="btn btn-default"><?php echo __('Cancel'); ?></a>
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
