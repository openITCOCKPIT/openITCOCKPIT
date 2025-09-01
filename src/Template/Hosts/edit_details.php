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
        <a ui-sref="HostsIndex">
            <i class="fa fa-desktop"></i> <?php echo __('Hosts'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <i class="fa fa-edit"></i> <?php echo __('Edit host detail'); ?>
    </li>
</ol>


<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?php echo __('Edit host detail'); ?>
                </h2>
                <div class="panel-toolbar">
                    <?php if ($this->Acl->hasPermission('index', 'hosts')): ?>
                        <a back-button href="javascript:void(0);" fallback-state='HostsIndex'
                           class="btn btn-default btn-xs mr-1 shadow-0">
                            <i class="fas fa-long-arrow-alt-left"></i> <?php echo __('Back'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <div class="card margin-bottom-10">
                        <div class="card-header">
                            <i class="fa-solid fa-wand-magic-sparkles"></i>
                            <?php echo __('Basic configuration'); ?>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="form-group col-lg-12"
                                     ng-class="{'has-error': errors.hosts_to_containers_sharing}">
                                    <label class="control-label" for="HostContainer">
                                        <button class="btn btn-xs btn-icon width-25"
                                                ng-class="{ 'btn-primary': !post.editSharedContainers, 'btn-success': post.editSharedContainers }"
                                                ng-click="post.editSharedContainers = !post.editSharedContainers"
                                                name="post.editSharedContainers"
                                                title="<?php echo __('Unlock for edit'); ?>">
                                            <i class="fa fa-lock fa-lock"
                                               ng-class="{ 'fa-lock': !post.editSharedContainers, 'fa-unlock': post.editSharedContainers }"></i>
                                        </button>
                                        <?php echo __('Shared containers'); ?>
                                    </label>
                                    <select
                                        id="HostContainer"
                                        data-placeholder="<?php echo __('Please choose'); ?>"
                                        class="form-control"
                                        multiple
                                        chosen="sharingContainers"
                                        ng-disabled="!post.editSharedContainers"
                                        ng-options="container.key as container.value for container in sharingContainers"
                                        ng-model="post.Host.hosts_to_containers_sharing._ids">
                                    </select>
                                    <div class="form-group">
                                        <label
                                            class="checkbox small-checkbox-label display-inline margin-right-5 padding-top-0">
                                            <input type="checkbox" name="checkbox"
                                                   id="keepExistingSharedContainers"
                                                   ng-true-value="1"
                                                   ng-false-value="0"
                                                   ng-disabled="!post.editSharedContainers"
                                                   ng-model="post.keepSharedContainers">
                                            <i class="checkbox-primary disabled"></i>
                                            <?php echo __('Keep existing'); ?>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="form-group col-lg-12">
                                    <label for="HostDetailDescription" class="control-label">
                                        <button class="btn btn-xs btn-icon"
                                                ng-class="{ 'btn-primary': !post.editDescription, 'btn-success': post.editDescription }"
                                                ng-click="post.editDescription = !post.editDescription"
                                                title="<?php echo __('Unlock for edit'); ?>">
                                            <i class="fa fa-lock fa-lock"
                                               ng-class="{ 'fa-lock': !post.editDescription, 'fa-unlock': post.editDescription }"></i>
                                        </button>
                                        <?php echo __('Description'); ?>
                                    </label>
                                    <input
                                        ng-class="{ 'not-edit-area': !post.editDescription}"
                                        class="form-control"
                                        type="text"
                                        ng-disabled="!post.editDescription"
                                        ng-model="post.Host.description"
                                        id="HostDetailDescription">
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="form-group col-lg-12">
                                    <label class="control-label">
                                        <button class="btn btn-xs btn-icon"
                                                name="post.editTags"
                                                ng-class="{ 'btn-primary': !post.editTags, 'btn-success': post.editTags }"
                                                ng-click="post.editTags = !post.editTags"
                                                title="<?php echo __('Unlock for edit'); ?>">
                                            <i class="fa fa-lock fa-lock"
                                               ng-class="{ 'fa-lock': !post.editTags, 'fa-unlock': post.editTags }"></i>
                                        </button>
                                        <?php echo __('Tags'); ?>
                                    </label>
                                    <div class="input-group" ng-class="{ 'not-edit-area': !post.editTags}">
                                        <input class="form-control tagsinput"
                                               data-role="tagsinput"
                                               type="text"
                                               id="HostTagsInput"
                                               ng-disabled="!post.editTags"
                                               ng-model="post.Host.tags">
                                    </div>
                                    <div class="help-block">
                                        <?php echo __('Press return to separate tags'); ?>
                                    </div>
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="form-group col-lg-12">
                                    <label class="control-label">
                                        <button class="btn btn-xs btn-icon"
                                                name="editPriority"
                                                ng-class="{ 'btn-primary': !post.editPriority, 'btn-success': post.editPriority }"
                                                ng-click="post.editPriority = !post.editPriority"
                                                title="<?php echo __('Unlock for edit'); ?>">
                                            <i class="fa fa-lock fa-lock"
                                               ng-class="{ 'fa-lock': !post.editPriority, 'fa-unlock': post.editPriority }"></i>
                                        </button>
                                        <?php echo __('Priority'); ?>
                                    </label>
                                    <div class="col-xs-12 col-lg-2" ng-class="{ 'not-edit-area': !post.editPriority}">
                                        <priority-directive priority="post.Host.priority"
                                                            callback="setPriority"></priority-directive>
                                    </div>
                                </div>
                            </div>
                            <?php if (\Cake\Core\Plugin::isLoaded('DistributeModule')): ?>
                                <hr>
                                <div class="row">
                                    <div class="form-group col-lg-12">
                                        <label class="control-label" for="SatellitesSelect">
                                            <button class="btn btn-xs btn-icon width-25"
                                                    ng-class="{ 'btn-primary': !post.editSatellites, 'btn-success': post.editSatellites }"
                                                    ng-click="post.editSatellites = !post.editSatellites"
                                                    name="post.editSatellites"
                                                    title="<?php echo __('Unlock for edit'); ?>">
                                                <i class="fa fa-lock fa-lock"
                                                   ng-class="{ 'fa-lock': !post.editSatellites, 'fa-unlock': post.editSatellites }"></i>
                                            </button>
                                            <?php echo __('Satellite'); ?>
                                        </label>
                                        <select
                                            id="SatellitesSelect"
                                            data-placeholder="<?php echo __('Please choose'); ?>"
                                            class="form-control"
                                            chosen="satellites"
                                            ng-disabled="!post.editSatellites"
                                            ng-options="satellite.key as satellite.value for satellite in satellites"
                                            ng-model="post.Host.satellite_id">
                                        </select>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card margin-bottom-10">
                        <div class="card-header">
                            <i class="fa fa-terminal"></i>
                            <?php echo __('Check configuration'); ?>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label class="control-label">
                                    <button class="btn btn-xs btn-icon"
                                            name="editCheckInterval"
                                            ng-class="{ 'btn-primary': !post.editCheckInterval, 'btn-success': post.editCheckInterval }"
                                            ng-click="post.editCheckInterval = !post.editCheckInterval"
                                            title="<?php echo __('Unlock for edit'); ?>">
                                        <i class="fa fa-lock fa-lock"
                                           ng-class="{ 'fa-lock': !post.editCheckInterval, 'fa-unlock': post.editCheckInterval }"></i>
                                    </button>
                                    <?php echo __('Check interval'); ?>
                                </label>
                                <div ng-class="{ 'not-edit-area': !post.editCheckInterval}">
                                    <interval-input-directive
                                        interval="post.Host.check_interval"></interval-input-directive>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label">
                                    <button class="btn btn-xs btn-icon"
                                            name="post.editRetryInterval"
                                            ng-class="{ 'btn-primary': !post.editRetryInterval, 'btn-success': post.editRetryInterval }"
                                            ng-click="post.editRetryInterval = !post.editRetryInterval"
                                            title="<?php echo __('Unlock for edit'); ?>">
                                        <i class="fa fa-lock fa-lock"
                                           ng-class="{ 'fa-lock': !post.editRetryInterval, 'fa-unlock': post.editRetryInterval }"></i>
                                    </button>
                                    <?php echo __('Retry interval'); ?>
                                </label>
                                <div ng-class="{ 'not-edit-area': !post.editRetryInterval}">
                                    <interval-input-directive
                                        interval="post.Host.retry_interval"></interval-input-directive>
                                </div>
                            </div>

                            <div class="form-group required">
                                <label class="control-label">
                                    <button class="btn btn-xs btn-icon"
                                            name="editMaxNumberOfCheckAttempts"
                                            ng-class="{ 'btn-primary': !post.editMaxNumberOfCheckAttempts, 'btn-success': post.editMaxNumberOfCheckAttempts }"
                                            ng-click="post.editMaxNumberOfCheckAttempts = !post.editMaxNumberOfCheckAttempts"
                                            title="<?php echo __('Unlock for edit'); ?>">
                                        <i class="fa fa-lock fa-lock"
                                           ng-class="{ 'fa-lock': !post.editMaxNumberOfCheckAttempts, 'fa-unlock': post.editMaxNumberOfCheckAttempts }"></i>
                                    </button>
                                    <?php echo __('Max. number of check attempts'); ?>
                                </label>
                                <div class="row">
                                    <div class="col-xs-12 col-lg-6"
                                         ng-class="{ 'not-edit-area': !post.editMaxNumberOfCheckAttempts}">
                                        <div class="btn-group flex-wrap">
                                            <?php for ($i = 1; $i <= 10; $i++): ?>
                                                <button
                                                    type="button"
                                                    class="btn btn-default"
                                                    ng-click="post.Host.max_check_attempts = <?php echo h($i) ?>"
                                                    ng-class="{'active': post.Host.max_check_attempts == <?php echo h($i); ?>}">
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
                                                ng-disabled="!post.editMaxNumberOfCheckAttempts"
                                                ng-model="post.Host.max_check_attempts">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xs-12 col-lg-offset-2 col-lg-12"
                                     ng-show="post.Host.check_interval && post.Host.max_check_attempts && post.Host.retry_interval">
                                    <div class="help-block">
                                        <?php echo __('Number of failed attempts before the host will switch into hard state.'); ?>
                                    </div>
                                    <div class="help-block">
                                        <?php echo __('Worst case time delay until notification command gets executed after state hits a non ok state: '); ?>
                                        <human-time-directive
                                            seconds="(post.Host.check_interval + (post.Host.max_check_attempts -1) * post.Host.retry_interval)"></human-time-directive>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="card margin-bottom-10">
                        <div class="card-header">
                            <i class="fa fa-envelope"></i>
                            <?php echo __('Notification configuration'); ?>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label class="control-label">
                                    <button class="btn btn-xs btn-icon"
                                            name="editNotificationInterval"
                                            ng-class="{ 'btn-primary': !post.editNotificationInterval, 'btn-success': post.editNotificationInterval }"
                                            ng-click="post.editNotificationInterval = !post.editNotificationInterval"
                                            title="<?php echo __('Unlock for edit'); ?>">
                                        <i class="fa fa-lock fa-lock"
                                           ng-class="{ 'fa-lock': !post.editNotificationInterval, 'fa-unlock': post.editNotificationInterval }"></i>
                                    </button>
                                    <?php echo __('Notification interval'); ?>
                                </label>
                                <div ng-class="{ 'not-edit-area': !post.editNotificationInterval}">
                                    <interval-input-directive
                                        interval="post.Host.notification_interval"></interval-input-directive>
                                </div>
                            </div>
                            <div class="form-group required">
                                <label class="control-label">
                                    <button class="btn btn-xs btn-icon"
                                            name="editContacts"
                                            ng-class="{ 'btn-primary': !post.editContacts, 'btn-success': post.editContacts }"
                                            ng-click="post.editContacts = !post.editContacts"
                                            title="<?php echo __('Unlock for edit'); ?>">
                                        <i class="fa fa-lock fa-lock"
                                           ng-class="{ 'fa-lock': !post.editContacts, 'fa-unlock': post.editContacts }"></i>
                                    </button>
                                    <?php echo __('Contacts'); ?>
                                </label>
                                <div class="input-group" style="width: 100%;"
                                     ng-class="{ 'not-edit-area': !post.editContacts}">
                                    <select
                                        id="ContactsPeriodSelect"
                                        data-placeholder="<?php echo __('Please choose'); ?>"
                                        class="form-control"
                                        chosen="contacts"
                                        ng-disabled="!post.editContacts"
                                        multiple
                                        ng-options="contact.key as contact.value for contact in contacts"
                                        ng-model="post.Host.contacts._ids">
                                    </select>
                                </div>
                                <div class="help-block">
                                    <div class="form-group">
                                        <label
                                            class="checkbox small-checkbox-label display-inline margin-right-5 padding-top-0">
                                            <input type="checkbox" name="checkbox"
                                                   id="keepExistingContacts"
                                                   ng-true-value="1"
                                                   ng-false-value="0"
                                                   ng-disabled="!post.editContacts"
                                                   ng-model="post.keepContacts">
                                            <i class="checkbox-primary disabled"></i>
                                            <?php echo __('Keep existing'); ?>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group required">
                                <label class="control-label">
                                    <button class="btn btn-xs btn-icon"
                                            name="editContactgroups"
                                            ng-class="{ 'btn-primary': !post.editContactgroups, 'btn-success': post.editContactgroups }"
                                            ng-click="post.editContactgroups = !post.editContactgroups"
                                            title="<?php echo __('Unlock for edit'); ?>">
                                        <i class="fa fa-lock fa-lock"
                                           ng-class="{ 'fa-lock': !post.editContactgroups, 'fa-unlock': post.editContactgroups }"></i>
                                    </button>
                                    <?php echo __('Contact groups'); ?>
                                </label>
                                <div class="input-group" style="width: 100%;"
                                     ng-class="{ 'not-edit-area': !post.editContactgroups}">
                                    <select
                                        id="ContactgroupsSelect"
                                        data-placeholder="<?php echo __('Please choose'); ?>"
                                        class="form-control"
                                        chosen="contactgroups"
                                        ng-disabled="!post.editContactgroups"
                                        multiple
                                        ng-options="contactgroup.key as contactgroup.value for contactgroup in contactgroups"
                                        ng-model="post.Host.contactgroups._ids">
                                    </select>

                                </div>
                                <div class="help-block">
                                    <div class="form-group">
                                        <label
                                            class="checkbox small-checkbox-label display-inline margin-right-5 padding-top-0">
                                            <input type="checkbox" name="checkbox"
                                                   id="keepExistingContactgroups"
                                                   ng-true-value="1"
                                                   ng-false-value="0"
                                                   ng-disabled="!post.editContactgroups"
                                                   ng-model="post.keepContactgroups">
                                            <i class="checkbox-primary disabled"></i>
                                            <?php echo __('Keep existing'); ?>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card margin-bottom-10">
                        <div class="card-header">
                            <i class="fa fa-envelope"></i>
                            <?php echo __('Misc. configuration'); ?>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label for="HostDetailURL" class="control-label">
                                    <button class="btn btn-xs btn-icon"
                                            name="editHostUrl"
                                            ng-class="{ 'btn-primary': !post.editHostUrl, 'btn-success': post.editHostUrl }"
                                            ng-click="post.editHostUrl = !post.editHostUrl"
                                            title="<?php echo __('Unlock for edit'); ?>">
                                        <i class="fa fa-lock fa-lock"
                                           ng-class="{ 'fa-lock': !post.editHostUrl, 'fa-unlock': post.editHostUrl }"></i>
                                    </button>
                                    <?php echo __('Host URL'); ?>
                                </label>
                                <input
                                    id="HostDetailURL"
                                    ng-class="{ 'not-edit-area': !post.editHostUrl}"
                                    class="form-control"
                                    placeholder="https://issues.example.org?host=$HOSTNAME$"
                                    ng-disabled="!post.editHostUrl"
                                    type="text"
                                    ng-model="post.Host.host_url">
                                <div class="help-block">
                                    <?php echo __('The macros $HOSTID$, $HOSTNAME$, $HOSTDISPLAYNAME$ and $HOSTADDRESS$ will be replaced'); ?>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="HostDetailNotes" class="control-label">
                                    <button class="btn btn-xs btn-icon"
                                            name="editNotes"
                                            ng-class="{ 'btn-primary': !post.editNotes, 'btn-success': post.editNotes }"
                                            ng-click="post.editNotes = !post.editNotes"
                                            title="<?php echo __('Unlock for edit'); ?>">
                                        <i class="fa fa-lock fa-lock"
                                           ng-class="{ 'fa-lock': !post.editNotes, 'fa-unlock': post.editNotes }"></i>
                                    </button>
                                    <?php echo __('Notes'); ?>
                                </label>
                                <input
                                    ng-class="{ 'not-edit-area': !post.editNotes}"
                                    id="HostDetailNotes"
                                    class="form-control"
                                    ng-disabled="!post.editNotes"
                                    type="text"
                                    ng-model="post.Host.notes">
                            </div>
                        </div>
                    </div>
                    <div class="card margin-top-10">
                        <div class="card-body">
                            <div class="float-right">
                                <button class="btn btn-primary" ng-click="editDetails()">
                                    <?php echo __('Save details'); ?>
                                </button>
                                <?php if ($this->Acl->hasPermission('index', 'Hosts')): ?>
                                    <a back-button href="javascript:void(0);" fallback-state='HostsIndex'
                                       class="btn btn-default"><?php echo __('Cancel'); ?></a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
