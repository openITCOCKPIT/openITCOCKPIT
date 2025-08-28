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
        <a ui-sref="SystemsettingsIndex">
            <i class="fa fa-wrench"></i> <?php echo __('System Settings'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <i class="fa fa-list"></i> <?php echo __('index'); ?>
    </li>
</ol>

<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true"><i class="fas fa-times"></i></span>
    </button>
    <div class="d-flex align-items-center">
        <div class="alert-icon width-3">
            <div class='icon-stack icon-stack-sm'>
                <i class="base base-7 icon-stack-3x opacity-100 color-danger-400"></i>
                <i class="base base-7 icon-stack-2x opacity-100 color-danger-100"></i>
                <i class="fa fa-exclamation icon-stack-1x opacity-100 color-white"></i>
            </div>
        </div>
        <div class="flex-1">
            <span class="h5 m-0 fw-700"><?php echo __('Attention!'); ?></span>
            <?php echo __("Do not change values, where you don't know what you are doing!"); ?>
        </div>
    </div>
</div>

<reload-required></reload-required>

<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?= __('System Settings'); ?>
                </h2>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <form ng-submit="submit();">
                        <div class="frame-wrap">
                            <table class="table table-striped m-0 table-bordered table-hover table-sm">
                                <thead>
                                <tr>
                                    <th><?php echo __('Key'); ?></th>
                                    <th><?php echo __('Value'); ?></th>
                                    <th class="text-center"><?php echo __('Info'); ?></th>
                                </tr>
                                </thead>

                                <tbody ng-repeat="(key, value) in systemsettings">
                                <tr>
                                    <td class="service_table_host_header text-primary" colspan="3">
                                        <strong>{{ key }}</strong>
                                    </td>
                                </tr>
                                <tr ng-repeat="systemsetting in value">
                                    <td>
                                        {{systemsetting.alias}}
                                    </td>
                                    <td>
                                        <div class="form-group">
                                            <div class="col col-xs-12">
                                                <div ng-switch="systemsetting.key">
                                                    <div ng-switch-when="MONITORING.HOST.INITSTATE">
                                                        <select class="form-control systemsetting-input"
                                                                ng-model="systemsetting.value">
                                                            <option value="o"><?php echo __('Up'); ?></option>
                                                            <option value="d"><?php echo __('Down'); ?></option>
                                                            <option value="u"><?php echo __('Unreachable'); ?></option>
                                                        </select>
                                                    </div>
                                                    <div ng-switch-when="MONITORING.SERVICE.INITSTATE">
                                                        <select class="form-control systemsetting-input"
                                                                ng-model="systemsetting.value">
                                                            <option value="o"><?php echo __('Ok'); ?></option>
                                                            <option value="w"><?php echo __('Warning'); ?></option>
                                                            <option value="c"><?php echo __('Critical'); ?></option>
                                                            <option value="u"><?php echo __('Unknown'); ?></option>
                                                        </select>
                                                    </div>
                                                    <div ng-switch-when="FRONTEND.SHOW_EXPORT_RUNNING">
                                                        <select class="form-control systemsetting-input"
                                                                ng-model="systemsetting.value">
                                                            <option value="yes"><?php echo __('True'); ?></option>
                                                            <option value="no"><?php echo __('False'); ?></option>
                                                        </select>
                                                    </div>
                                                    <div ng-switch-when="FRONTEND.AUTH_METHOD">
                                                        <select class="form-control systemsetting-input"
                                                                ng-model="systemsetting.value">
                                                            <option
                                                                value="session"><?php echo __('PHP session'); ?></option>
                                                            <option value="ldap"><?php echo __('PHP LDAP'); ?></option>
                                                            <option value="sso"><?php echo __('SSO oAuth2'); ?></option>
                                                            <option value="sso_ldap"><?php echo __('SSO oAuth2 and LDAP'); ?></option>
                                                        </select>
                                                    </div>
                                                    <div ng-switch-when="FRONTEND.LDAP.TYPE">
                                                        <select class="form-control systemsetting-input"
                                                                ng-model="systemsetting.value">
                                                            <option
                                                                value="adldap"><?php echo __('Active Directory LDAP'); ?></option>
                                                            <option
                                                                value="openldap"><?php echo __('OpenLDAP'); ?></option>
                                                        </select>
                                                    </div>
                                                    <div
                                                        ng-switch-when="FRONTEND.LDAP.PASSWORD|MONITORING.ACK_RECEIVER_PASSWORD|FRONTEND.SSO.CLIENT_SECRET"
                                                        ng-switch-when-separator="|">
                                                        <input type="password"
                                                               ng-model="systemsetting.value"
                                                               class="form-control systemsetting-input">
                                                    </div>
                                                    <div
                                                        ng-switch-when="MONITORING.SINGLE_INSTANCE_SYNC|MONITORING.HOST_CHECK_ACTIVE_DEFAULT|MONITORING.SERVICE_CHECK_ACTIVE_DEFAULT|FRONTEND.HIDDEN_USER_IN_CHANGELOG|FRONTEND.DISABLE_LOGIN_ANIMATION|FRONTEND.REPLACE_USER_MACROS|FRONTEND.REPLACE_PASSWORD_IN_OBJECT_MACROS|FRONTEND.ENABLE_IFRAME_IN_DASHBOARDS|FRONTEND.SSO.FORCE_USER_TO_LOGINPAGE|FRONTEND.BLUR_COMMAND_LINE_IN_BROWSER"
                                                        ng-switch-when-separator="|">
                                                        <select class="form-control systemsetting-input"
                                                                ng-model="systemsetting.value">
                                                            <option value="0"><?php echo __('False'); ?></option>
                                                            <option value="1"><?php echo __('True'); ?></option>
                                                        </select>
                                                    </div>
                                                    <div ng-switch-when="FRONTEND.LDAP.USE_TLS">
                                                        <select class="form-control systemsetting-input"
                                                                ng-model="systemsetting.value">
                                                            <option value="0"><?php echo __('Plain'); ?></option>
                                                            <option value="1"><?php echo __('StartTLS'); ?></option>
                                                            <option value="2"><?php echo __('TLS (ldaps)'); ?></option>
                                                        </select>
                                                    </div>
                                                    <div ng-switch-when="FRONTEND.PRESELECTED_DOWNTIME_OPTION">
                                                        <select class="form-control systemsetting-input"
                                                                ng-model="systemsetting.value">
                                                            <option
                                                                value="0"><?php echo __('Individual host'); ?></option>
                                                            <option
                                                                value="1"><?php echo __('Host including services'); ?></option>
                                                        </select>
                                                    </div>
                                                    <div
                                                        ng-switch-when="ARCHIVE.AGE.SERVICECHECKS|ARCHIVE.AGE.HOSTCHECKS|ARCHIVE.AGE.STATEHISTORIES|ARCHIVE.AGE.NOTIFICATIONS|ARCHIVE.AGE.LOGENTRIES|ARCHIVE.AGE.CONTACTNOTIFICATIONS|ARCHIVE.AGE.CONTACTNOTIFICATIONMETHODS"
                                                        ng-switch-when-separator="|">
                                                        <select class="form-control systemsetting-input"
                                                                ng-options="i as i for i in dropdownOptionSequence"
                                                                ng-model="systemsetting.value" convert-to-number>
                                                        </select>
                                                    </div>
                                                    <div ng-switch-when="SYSTEM.ANONYMOUS_STATISTICS">
                                                        <input type="text"
                                                               ng-value="getAnonymousStatisticsValue(systemsetting.value)"
                                                               class="form-control systemsetting-input"
                                                               disabled="disabled" readonly="readonly">
                                                        <?php if ($this->Acl->hasPermission('index', 'statistics')): ?>
                                                            <br/>
                                                            <a ui-sref="StatisticsIndex">
                                                                <?php echo __('Click for more information.'); ?>
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div ng-switch-when="FRONTEND.SSO.AUTH_PROVIDER">
                                                        <select class="form-control systemsetting-input"
                                                                ng-model="systemsetting.value">
                                                            <option value="generic"><?php echo __('Generic'); ?></option>
                                                            <option value="PingIdentity"><?php echo __('Ping Identity'); ?></option>
                                                            <option value="AzureActiveDirectory"><?php echo __('Azure Active Directory'); ?></option>
                                                        </select>
                                                    </div>
                                                    <div ng-switch-when="FRONTEND.LDAP.OPENLDAP_GROUP_SCHEMA">
                                                        <select class="form-control systemsetting-input"
                                                                ng-model="systemsetting.value">
                                                            <option value="memberUid"><?php echo __('memberUid (deprecated)'); ?></option>
                                                            <option value="uniqueMember"><?php echo __('uniqueMember (RFC 4519 2.40)'); ?></option>
                                                        </select>
                                                    </div>
                                                    <div ng-switch-default>
                                                        <input type="text"
                                                               ng-model="systemsetting.value"
                                                               class="form-control systemsetting-input">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <a href="javascript:void(0);"
                                           data-original-title="{{systemsetting.info}}"
                                           data-placement="left" rel="tooltip"
                                           data-container="body">
                                            <i class="padding-top-5 fa fa-info-circle fa-2x"></i>
                                        </a>
                                    </td>
                                </tr>
                                </tbody>
                            </table>

                            <div class="card margin-top-10">
                                <div class="card-body">
                                    <div class="float-right">
                                        <button class="btn btn-primary" type="submit">Save</button>
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
