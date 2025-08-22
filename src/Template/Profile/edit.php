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
        <a ui-sref="ProfileEdit">
            <i class="fa fa-user"></i> <?php echo __('Profile'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <i class="fa fa-edit"></i> <?php echo __('Edit'); ?>
    </li>
</ol>

<reload-required></reload-required>


<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?php echo __('Profile'); ?>
                    <span class="fw-300"><i><?php echo __('information'); ?></i></span>
                </h2>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <form ng-submit="submitUser();" class="form-horizontal">
                        <div class="form-group" ng-class="{'has-error': errors.firstname}">
                            <label class="control-label">
                                <?php echo __('First name'); ?>
                            </label>
                            <input
                                class="form-control"
                                type="text"
                                ng-disabled="isLdapUser"
                                ng-model="post.User.firstname">
                            <div ng-repeat="error in errors.firstname">
                                <div class="help-block text-danger">{{ error }}</div>
                            </div>
                            <div class="help-block" ng-show="isLdapUser">
                                <?php echo __('Value imported from LDAP Server'); ?>
                            </div>
                        </div>

                        <div class="form-group" ng-class="{'has-error': errors.lastname}">
                            <label class="control-label">
                                <?php echo __('Last name'); ?>
                            </label>
                            <input
                                class="form-control"
                                type="text"
                                ng-disabled="isLdapUser"
                                ng-model="post.User.lastname">
                            <div ng-repeat="error in errors.lastname">
                                <div class="help-block text-danger">{{ error }}</div>
                            </div>
                            <div class="help-block" ng-show="isLdapUser">
                                <?php echo __('Value imported from LDAP Server'); ?>
                            </div>
                        </div>

                        <div ng-show="isLdapUser" class="form-group" ng-class="{'has-error': errors.samaccountname}">
                            <label class="control-label">
                                <?php echo __('SAM-Account-Name'); ?>
                            </label>
                            <input
                                class="form-control"
                                type="text"
                                ng-disabled="isLdapUser"
                                ng-model="post.User.samaccountname">
                            <div ng-repeat="error in errors.samaccountname">
                                <div class="help-block text-danger">{{ error }}</div>
                            </div>
                            <div class="help-block">
                                <?php echo __('Username for the login'); ?>
                            </div>
                        </div>

                        <div class="form-group" ng-class="{'has-error': errors.email}">
                            <label class="control-label">
                                <?php echo __('Email address'); ?>
                            </label>
                            <input
                                class="form-control"
                                type="text"
                                ng-disabled="isLdapUser"
                                ng-model="post.User.email">
                            <div ng-repeat="error in errors.email">
                                <div class="help-block text-danger">{{ error }}</div>
                            </div>
                            <div class="help-block" ng-show="isLdapUser">
                                <?php echo __('Value imported from LDAP Server'); ?>
                            </div>
                        </div>

                        <div class="form-group" ng-class="{'has-error': errors.phone}">
                            <label class="control-label">
                                <?php echo __('Phone Number'); ?>
                            </label>
                            <input
                                class="form-control"
                                type="text"
                                ng-model="post.User.phone">
                            <div ng-repeat="error in errors.phone">
                                <div class="help-block text-danger">{{ error }}</div>
                            </div>
                        </div>

                        <hr>

                        <div class="form-group" ng-class="{'has-error': errors.showstatsinmenu}">
                            <div class="custom-control custom-checkbox  margin-bottom-10"
                                 ng-class="{'has-error': errors.showstatsinmenu}">

                                <input type="checkbox"
                                       class="custom-control-input"
                                       ng-true-value="1"
                                       ng-false-value="0"
                                       id="userShowstatsinmenu"
                                       ng-model="post.User.showstatsinmenu">
                                <label class="custom-control-label" for="userShowstatsinmenu">
                                    <?php echo __('Show status badges in menu'); ?>
                                </label>
                            </div>
                        </div>

                        <div class="form-group" ng-class="{'has-error': errors.recursive_browser}">
                            <div class="custom-control custom-checkbox  margin-bottom-10"
                                 ng-class="{'has-error': errors.recursive_browser}">

                                <input type="checkbox"
                                       class="custom-control-input"
                                       ng-true-value="1"
                                       ng-false-value="0"
                                       id="recursive_browser"
                                       ng-model="post.User.recursive_browser">
                                <label class="custom-control-label" for="recursive_browser">
                                    <?php echo __('Recursive Browser'); ?>
                                </label>
                            </div>
                        </div>

                        <div class="form-group required" ng-class="{'has-error': errors.paginatorlength}">
                            <label class="control-label">
                                <?php echo __('Length of lists'); ?>
                            </label>
                            <input
                                class="form-control"
                                type="number"
                                ng-model="post.User.paginatorlength">
                            <div ng-repeat="error in  errors.paginatorlength">
                                <div class="help-block text-danger">{{ error }}</div>
                            </div>
                            <div class="help-block text-muted">
                                <?php echo __('This value defines how many records will load per list. You can choose between 1 and 1000'); ?>
                            </div>
                        </div>

                        <hr>


                        <div class="form-group required" ng-class="{'has-error': errors.i18n}">
                            <label class="control-label" for="language">
                                <?php echo __('Language'); ?>
                            </label>
                            <select
                                id="language"
                                data-placeholder="<?php echo __('Please choose'); ?>"
                                class="form-control"
                                chosen="localeOptions"
                                ng-options="value.i18n as value.name for (key, value) in localeOptions"
                                ng-model="post.User.i18n">
                            </select>
                            <div ng-repeat="error in errors.i18n">
                                <div class="help-block text-danger">{{ error }}</div>
                            </div>
                            <div class="help-block">
                                <?php echo __('These options are community translations. Feel free to extend them and open a github pull request.'); ?>
                            </div>
                        </div>


                        <div class="form-group required" ng-class="{'has-error': errors.dateformat}">
                            <label class="control-label" for="UserDateformat">
                                <?php echo __('Date format'); ?>
                            </label>
                            <select
                                id="UserDateformat"
                                data-placeholder="<?php echo __('Please choose'); ?>"
                                class="form-control"
                                chosen="dateformats"
                                ng-options="dateformat.key as dateformat.value for dateformat in dateformats"
                                ng-model="post.User.dateformat">
                            </select>
                            <div ng-repeat="error in errors.dateformat">
                                <div class="help-block text-danger">{{ error }}</div>
                            </div>
                        </div>

                        <div class="form-group required" ng-class="{'has-error': errors.timezone}">
                            <label class="control-label" for="UserTimezone">
                                <?php echo __('Timezone'); ?>
                            </label>
                            <select
                                id="UserTimezone"
                                data-placeholder="<?php echo __('Please choose'); ?>"
                                class="form-control"
                                chosen="{}"
                                ng-model="post.User.timezone">
                                <?php foreach ($timezones as $continent => $continentTimezones): ?>
                                    <optgroup label="<?php echo h($continent); ?>">
                                        <?php foreach ($continentTimezones as $timezoneKey => $timezoneName): ?>
                                            <option
                                                value="<?php echo h($timezoneKey); ?>"><?php echo h($timezoneName); ?></option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>
                            <div ng-repeat="error in errors.User.timezone">
                                <div class="help-block text-danger">{{ error }}</div>
                            </div>

                            <div class="help-block">
                                <br/>
                                <?php echo __('Server timezone is:'); ?>
                                <strong>
                                    <?php echo h(date_default_timezone_get()); ?>
                                </strong>
                                <?php echo __('Current server time:'); ?>
                                <strong>
                                    <?php echo date('d.m.Y H:i:s'); ?>
                                </strong>
                            </div>
                        </div>
                        <div class="card margin-top-10">
                            <div class="card-body">
                                <div class="float-right">
                                    <button class="btn btn-primary"
                                            type="submit"><?php echo __('Update Profile'); ?></button>
                                    <a back-button href="javascript:void(0);" fallback-state='ProfileEdit'
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


<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?php echo __('Profile'); ?>
                    <span class="fw-300"><i><?php echo __('picture'); ?></i></span>
                </h2>
            </div>
            <div class="panel-container show">
                <div class="panel-content">

                    <div class="form-group">
                        <label class="control-label">
                            <?php echo __('Current image'); ?>
                        </label>
                        <div class="col" ng-if="post.User.image == null">
                            <div class="row">
                                <img ng-src="/img/fallback_user.png"
                                     alt="fallback_profile_img"
                                     width="70" height="70">
                            </div>
                            <div class="row">
                                <span ng-if="post.User.image == null" class="text-muted">
                                    <?php echo __('You have no own image uploaded yet'); ?>
                                </span>
                            </div>
                        </div>
                        <div class="col" ng-if="post.User.image != null">
                            <div class="row">
                                <img ng-src="/img/userimages/{{post.User.image}}"
                                     alt="profile_img"
                                     width="70" height="70">
                            </div>
                            <div class="row">
                                <a class="txt-color-red"
                                   href="javascript:void(0);"
                                   class="txt-color-red"
                                   ng-click="deleteUserImage()">
                                    <i class="fa fa-trash"></i> <?php echo __('Delete my image'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col col-md-2 control-label">
                            <?php echo __('Select image'); ?>
                        </label>
                        <div class="col col-xs-10 no-padding padding-top-10">
                            <div class="col-xs-12 text-info">
                                <i class="fa fa-info-circle"></i>
                                <?php echo __('Max allowed file size: '); ?>
                                {{ maxUploadLimit.string }}
                            </div>
                            <div class="col-xs-12">
                                <div class="profileImg-dropzone dropzone dropzoneStyle"
                                     action="/profile/upload_profile_icon.json?angular=true">
                                    <div class="dz-message">
                                        <i class="fas fa-cloud-upload-alt fa-5x text-muted mb-3"></i> <br>
                                        <span class="text-uppercase">Drop files here or click to upload.</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?php echo __('Change Password'); ?>
                </h2>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <form ng-submit="submitPassword();" class="form-horizontal">

                        <div class="row" ng-show="isLdapUser">
                            <div class="col-12 card card-body text-center text-info padding-10" ng-show="isLdapUser">
                                <i class="fa fa-info-circle"></i>
                                <?php echo __('LDAP users need to change their password through the operating system or an LDAP account manager tool.'); ?>
                            </div>
                        </div>

                        <div ng-hide="isLdapUser">
                            <div class="form-group" ng-class="{'has-error': errors.current_password}">
                                <label class="control-label">
                                    <?php echo __('Current password'); ?>
                                </label>
                                <input
                                    class="form-control"
                                    autocomplete="new-password"
                                    type="password"
                                    ng-model="post.Password.current_password">
                                <div ng-repeat="error in errors.current_password">
                                    <div class="help-block text-danger">{{ error }}</div>
                                </div>
                            </div>

                            <div class="form-group" ng-class="{'has-error': errors.password}">
                                <label class="control-label">
                                    <?php echo __('New password'); ?>
                                </label>
                                <input
                                    class="form-control"
                                    type="password"
                                    autocomplete="new-password"
                                    ng-model="post.Password.password">
                                <div ng-repeat="error in errors.password">
                                    <div class="help-block text-danger">{{ error }}</div>
                                </div>
                                <div class="help-block">
                                    <?= __('The password must consist of 6 alphanumeric characters and must contain at least one digit'); ?>
                                </div>
                            </div>

                            <div class="form-group" ng-class="{'has-error': errors.confirm_password}">
                                <label class="control-label">
                                    <?php echo __('Confirm new password'); ?>
                                </label>
                                <input
                                    class="form-control"
                                    type="password"
                                    autocomplete="new-password"
                                    ng-model="post.Password.confirm_password">
                                <div ng-repeat="error in errors.confirm_password">
                                    <div class="help-block text-danger">{{ error }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="card margin-top-10" ng-hide="isLdapUser">
                            <div class="card-body">
                                <div class="float-right">
                                    <button class="btn btn-primary"
                                            type="submit"><?php echo __('Change Password'); ?></button>
                                    <a back-button href="javascript:void(0);" fallback-state='ProfileEdit'
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

<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?php echo __('API keys'); ?>
                </h2>
                <div class="panel-toolbar">

                    <button class="btn btn-xs btn-default mr-1 shadow-0" ng-click="loadApiKey()">
                        <i class="fa fa-refresh"></i>
                        <?php echo __('Refresh'); ?>
                    </button>
                    <button class="btn btn-xs btn-success mr-1 shadow-0" ng-click="createApiKey()">
                        <i class="fa fa-key"></i>
                        <?php echo __('Create new API key'); ?>
                    </button>
                </div>
            </div>
            <div class="panel-container show">
                <div class="panel-content">

                    <div class="row">
                        <div class="col-12 card card-body text-center text-info padding-10"
                             ng-show="apikeys.length == 0">
                            <i class="fa fa-info-circle"></i>
                            <?php echo __('No API keys created yet. You can still use the api using your username and password.'); ?>
                            <br/>
                            <b><?php echo __('It\'s recommended to create a own API key for every external application.'); ?></b>
                        </div>
                    </div>

                    <div class="row" ng-show="apikeys.length > 0">
                        <div class="col-xs-12 col-md-1 bold">
                            <?php echo __('ID'); ?>
                        </div>
                        <div class="col-xs-12 col-md-7 bold">
                            <?php echo __('Description'); ?>
                        </div>
                        <div class="col-xs-12 col-md-2 bold">
                            <?php echo __('Last used'); ?>
                        </div>
                        <div class="col-xs-12 col-md-2 bold">
                            <?php echo __('Show'); ?>
                        </div>
                    </div>
                    <div class="row padding-top-5 padding-bottom-5" ng-repeat="apikey in apikeys">
                        <div class="col-xs-12 col-md-1">
                            {{apikey.id}}
                        </div>
                        <div class="col-xs-12 col-md-7">
                            {{apikey.description}}
                        </div>
                        <div class="col-xs-12 col-md-2">
                            {{apikey.last_use}}
                        </div>
                        <div class="col-xs-12 col-md-2">
                            <button class="btn btn-primary btn-xs btn-block" ng-click="editApiKey(apikey.id)">
                                <i class="fa fa-eye"></i>
                                <?php echo __('Show'); ?>
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<edit-apikey-directive></edit-apikey-directive>

<!-- Create API key modal -->
<div id="angularCreateApiKeyModal" class="modal" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa fa-key"></i>
                    <?php echo __('Create API key'); ?>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="fa fa-times"></i></span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group required" ng-class="{'has-error': errors.description}">
                    <label class="control-label">
                        <?php echo __('Description'); ?>
                    </label>
                    <input
                        class="form-control"
                        type="text"
                        size="255"
                        ng-model="post.Apikey.description">
                    <div ng-repeat="error in errors.description">
                        <div class="help-block text-danger">{{ error }}</div>
                    </div>
                </div>

                <div class="form-group" ng-class="{'has-error': errors.apikey}">
                    <label class="control-label">
                        <?php echo __('API key (read-only)'); ?>
                    </label>
                    <input
                        class="form-control disabled"
                        type="text"
                        readonly
                        ng-model="post.Apikey.apikey">
                    <div ng-repeat="error in errors.apikey">
                        <div class="help-block text-danger">{{ error }}</div>
                    </div>
                </div>

                <div class="row padding-top-10" ng-show="newApiKey">
                    <div class="col-lg-12 padding-bottom-5">
                        <span class="bold">
                            <code>curl</code> <?php echo __('example'); ?>:
                        </span>
                    </div>
                    <div class="col-lg-12">
                        <pre>curl -H \
"Authorization: X-OITC-API {{post.Apikey.apikey}}" \
"https://<?php echo h($_SERVER['SERVER_ADDR']); ?>/hosts/index.json?angular=true"</pre>
                    </div>
                    <div class="col-lg-12">
                        <?php echo __('For self-signed certificates, add'); ?><code>-k</code>.
                    </div>
                </div>

                <div class="row padding-top-10" ng-show="newApiKey">
                    <div class="col-lg-12 padding-bottom-5">
                        <span class="bold">
                            <code>curl</code> <?php echo __('example with JSON processor'); ?>:
                        </span>
                    </div>
                    <div class="col-lg-12">
                        <pre>curl -k -s -H \
"Authorization: X-OITC-API {{post.Apikey.apikey}}" \
"https://<?php echo h($_SERVER['SERVER_ADDR']); ?>/hosts/index.json?angular=true" |jq .</pre>
                    </div>
                </div>

                <div class="row padding-top-10">
                    <img class="mx-auto" ng-src="{{currentQrCode}}">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary mr-auto" ng-click="getNewApiKey()">
                    <i class="fa fa-refresh"></i>
                    <?php echo __('Generate new key'); ?>
                </button>

                <button type="button" class="btn btn-primary" ng-click="saveApiKey()">
                    <?php echo __('Save'); ?>
                </button>
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <?php echo __('Close'); ?>
                </button>
            </div>
        </div>
    </div>
</div>
