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
        <a ui-sref="StatuspagesIndex">
            <i class="fas fa-info-circle"></i> <?php echo __('Status pages'); ?>
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
                    <?php echo __('Create new Status page'); ?>
                </h2>
                <div class="panel-toolbar">
                    <?php if ($this->Acl->hasPermission('index', 'statuspages')): ?>
                        <a back-button href="javascript:void(0);" fallback-state='StatuspagesIndex'
                           class="btn btn-default btn-xs mr-1 shadow-0">
                            <i class="fas fa-long-arrow-alt-left"></i> <?php echo __('Back'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <form ng-submit="submit();" class="form-horizontal" ng-init="successMessage=
                        {objectName : '<?php echo __('Status page'); ?>' , message: '<?php echo __('created successfully'); ?>'}">
                        <div class="form-group required" ng-class="{'has-error': errors.container_id}">
                            <label class="control-label" for="ContainersSelect">
                                <?php echo __('Container'); ?>
                            </label>
                            <select
                                id="ContainersSelect"
                                data-placeholder="<?php echo __('Please choose'); ?>"
                                class="form-control"
                                chosen="containers"
                                ng-options="container.key as container.value for container in containers"
                                ng-model="post.Statuspage.container_id">
                            </select>
                            <div ng-show="post.Statuspage.container_id === null" class="warning-glow">
                                <?php echo __('Please select a container.'); ?>
                            </div>
                            <div ng-repeat="error in errors.container_id">
                                <div class="help-block text-danger">{{ error }}</div>
                            </div>
                        </div>

                        <div class="form-group required" ng-class="{'has-error': errors.name}">
                            <label class="control-label">
                                <?php echo __('Name'); ?>
                            </label>
                            <input
                                class="form-control"
                                type="text"
                                ng-model="post.Statuspage.name">
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
                                ng-model="post.Statuspage.description">
                            <div ng-repeat="error in errors.description">
                                <div class="help-block text-danger">{{ error }}</div>
                            </div>
                        </div>

                        <fieldset class="pb-1">
                            <legend class="fs-md fieldset-legend-border-bottom"
                                    ng-class="{'fieldset-legend-border-bottom-danger': noItemsSelected}">
                                <h5>
                                    <?= __('Access control'); ?>
                                </h5>
                            </legend>
                        </fieldset>
                        <div class="form-group" ng-class="{'has-error': errors.public}">
                            <div class="custom-control custom-checkbox"
                                 ng-class="{'has-error': errors.public}">

                                <input type="checkbox"
                                       class="custom-control-input"
                                       id="public"
                                       ng-model="post.Statuspage.public">
                                <label class="custom-control-label" for="public">
                                    <?php echo __('Public'); ?>
                                </label>
                            </div>
                            <div class="help-block mt-0">
                                <span class="font-lg"
                                      ng-class="{'warning-icon-gray' : post.Statuspage.public == 0}">⚠️</span>
                                <?= __('Grant unrestricted access to this Statuspage without user authentication.'); ?>
                            </div>
                        </div>


                        <fieldset class="pb-1">
                            <legend class="fs-md fieldset-legend-border-bottom"
                                    ng-class="{'fieldset-legend-border-bottom-danger': noItemsSelected}">
                                <h5>
                                    <?= __('Downtime and Acknowledgements'); ?>
                                </h5>
                            </legend>
                        </fieldset>
                        <div class="row">
                            <div class="col-xs-12 col-md-6">
                                <!-- Downtimes -->
                                <div class="form-group" ng-class="{'has-error': errors.show_downtimes}">
                                    <div class="custom-control custom-checkbox"
                                         ng-class="{'has-error': errors.show_downtimes}">
                                        <input type="checkbox"
                                               class="custom-control-input"
                                               id="showDowntimes"
                                               ng-model="post.Statuspage.show_downtimes">
                                        <label class="custom-control-label" for="showDowntimes">
                                            <?php echo __('Show downtimes'); ?>
                                        </label>
                                    </div>
                                    <div class="help-block mt-0">
                                        <?= __('Determines if running and planed downtimes are displayed on the status page'); ?>
                                    </div>
                                </div>

                                <div class="form-group" ng-class="{'has-error': errors.show_downtime_comments}">
                                    <div class="custom-control custom-checkbox"
                                         ng-class="{'has-error': errors.show_downtime_comments}">
                                        <input type="checkbox"
                                               class="custom-control-input"
                                               id="showDowntimeComments"
                                               ng-disabled="!post.Statuspage.show_downtimes"
                                               ng-model="post.Statuspage.show_downtime_comments">
                                        <label class="custom-control-label" for="showDowntimeComments">
                                            <?php echo __('Show downtime comment'); ?>
                                        </label>
                                    </div>
                                    <div class="help-block mt-0">
                                        <?= __('Determines if the comments of the downtime are displayed on the status page.'); ?>
                                    </div>
                                </div>
                            </div>

                            <div class="col-xs-12 col-md-6">
                                <!-- Acknowledgements -->
                                <div class="form-group" ng-class="{'has-error': errors.show_acknowledgements}">
                                    <div class="custom-control custom-checkbox"
                                         ng-class="{'has-error': errors.show_acknowledgements}">
                                        <input type="checkbox"
                                               class="custom-control-input"
                                               id="showAcknowledgements"
                                               ng-model="post.Statuspage.show_acknowledgements">
                                        <label class="custom-control-label" for="showAcknowledgements">
                                            <?php echo __('Show acknowledgements'); ?>
                                        </label>
                                    </div>
                                    <div class="help-block mt-0">
                                        <?= __('Determines if acknowledgements are displayed on the status page'); ?>
                                    </div>
                                </div>

                                <div class="form-group" ng-class="{'has-error': errors.show_acknowledgement_comments}">
                                    <div class="custom-control custom-checkbox"
                                         ng-class="{'has-error': errors.show_acknowledgement_comments}">
                                        <input type="checkbox"
                                               class="custom-control-input"
                                               id="showAcknowledgementComments"
                                               ng-disabled="!post.Statuspage.show_acknowledgements"
                                               ng-model="post.Statuspage.show_acknowledgement_comments">
                                        <label class="custom-control-label" for="showAcknowledgementComments">
                                            <?php echo __('Show acknowledgement comments'); ?>
                                        </label>
                                    </div>
                                    <div class="help-block mt-0">
                                        <?= __('Determines if the comments of the acknowledgements are displayed on the status page.'); ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <fieldset class="pb-1">
                            <legend class="fs-md fieldset-legend-border-bottom"
                                    ng-class="{'fieldset-legend-border-bottom-danger': noItemsSelected}">
                                <h5>
                                    <?= __('Status page items'); ?>
                                    <span class="text-danger font-xs pl-1 fw-300"
                                          ng-show="noItemsSelected">
                                        <?= __('You must select at least one configuration item for status page.'); ?>
                                    </span>
                                </h5>
                            </legend>
                        </fieldset>

                        <div class="form-group">
                            <label class="control-label">
                                <i class="fas fa-server"></i>
                                <?= __('Host groups'); ?>
                            </label>
                            <select
                                id="HostgroupsSelect"
                                data-placeholder="<?php echo __('Please choose'); ?>"
                                class="form-control"
                                chosen="hostgroups"
                                callback="loadHostgroups"
                                multiple
                                ng-options="hostgroup.key as hostgroup.value for hostgroup in hostgroups"
                                ng-model="post.Statuspage.selected_hostgroups._ids">
                            </select>
                        </div>
                        <div class="form-group" ng-if="post.Statuspage.selected_hostgroups._ids.length > 0">
                            <div class="row pb-2">
                                <div class="col-4 bold">
                                    <?= __('Host group name'); ?>
                                </div>
                                <div class="col-8 bold">
                                    <?= __('Display name'); ?>
                                </div>
                            </div>
                            <div ng-repeat="hostgroup in hostgroups track by $index" class="row form-group"
                                 ng-if="post.Statuspage.selected_hostgroups._ids.indexOf(hostgroup.id) !== -1">
                                <div class="col-4 statuspage-item-box">
                                    <?php if ($this->Acl->hasPermission('extended', 'hostgroups')): ?>
                                        <a ui-sref="HostgroupsExtended({id: hostgroup.id})"
                                           class="text-primary">{{hostgroup.value}}</a>
                                    <?php else: ?>
                                        {{hostgroup.value}}
                                    <?php endif; ?>
                                </div>
                                <div class="col-8">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">
                                                <i class="fa-solid fa-tag text-primary"></i>
                                            </span>
                                        </div>
                                        <input class="form-control form-control-sm" type="text"
                                               placeholder="<?= __("Set alias for host group"); ?>"
                                               ng-model="hostgroups[$index]._joinData.display_alias">
                                    </div>
                                </div>
                            </div>
                        </div>


                        <div class="form-group">
                            <label class="control-label">
                                <i class="fa fa-cogs"></i>
                                <?= __('Service groups'); ?>
                            </label>
                            <select
                                id="ServicegroupsSelect"
                                data-placeholder="<?php echo __('Please choose'); ?>"
                                class="form-control"
                                chosen="servicegroups"
                                callback="loadServicegroups"
                                multiple
                                ng-options="servicegroup.key as servicegroup.value for servicegroup in servicegroups"
                                ng-model="post.Statuspage.selected_servicegroups._ids">
                            </select>
                        </div>
                        <div class="form-group" ng-if="post.Statuspage.selected_servicegroups._ids.length > 0">
                            <div class="row pb-2">
                                <div class="col-4 bold">
                                    <?= __('Service group name'); ?>
                                </div>
                                <div class="col-8 bold">
                                    <?= __('Display name'); ?>
                                </div>
                            </div>
                            <div ng-repeat="servicegroup in servicegroups track by $index" class="row form-group"
                                 ng-if="post.Statuspage.selected_servicegroups._ids.indexOf(servicegroup.id) !== -1">
                                <div class="col-4 statuspage-item-box">
                                    <?php if ($this->Acl->hasPermission('extended', 'servicegroups')): ?>
                                        <a ui-sref="ServicegroupsExtended({id: servicegroup.id})"
                                           class="text-primary">{{servicegroup.value}}</a>
                                    <?php else: ?>
                                        {{servicegroup.value}}
                                    <?php endif; ?>
                                </div>
                                <div class="col-8">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">
                                                <i class="fa-solid fa-tag text-primary"></i>
                                            </span>
                                        </div>
                                        <input class="form-control form-control-sm" type="text"
                                               placeholder="<?= __("Set alias for service group"); ?>"
                                               ng-model="servicegroups[$index]._joinData.display_alias">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label">
                                <i class="fa fa-desktop"></i>
                                <?= __('Hosts'); ?>
                            </label>
                            <select
                                id="HostsSelect"
                                data-placeholder="<?php echo __('Please choose'); ?>"
                                class="form-control"
                                chosen="hosts"
                                callback="loadHosts"
                                multiple
                                ng-options="host.key as host.value for host in hosts"
                                ng-model="post.Statuspage.selected_hosts._ids">
                            </select>
                        </div>
                        <div class="form-group" ng-if="post.Statuspage.selected_hosts._ids.length > 0">
                            <div class="row pb-2">
                                <div class="col-4 bold">
                                    <?= __('Host name'); ?>
                                </div>
                                <div class="col-8 bold">
                                    <?= __('Display name'); ?>
                                </div>
                            </div>
                            <div ng-repeat="host in hosts track by $index" class="row form-group"
                                 ng-if="post.Statuspage.selected_hosts._ids.indexOf(host.id) !== -1">
                                <div class="col-4 statuspage-item-box">
                                    <?php if ($this->Acl->hasPermission('browser', 'hosts')): ?>
                                        <a ui-sref="HostsBrowser({id: host.id})"
                                           class="text-primary">{{host.value}}</a>
                                    <?php else: ?>
                                        {{host.value}}
                                    <?php endif; ?>
                                </div>
                                <div class="col-8">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">
                                                <i class="fa-solid fa-tag text-primary"></i>
                                            </span>
                                        </div>
                                        <input class="form-control form-control-sm" type="text"
                                               placeholder="<?= __("Set alias for host"); ?>"
                                               ng-model="hosts[$index]._joinData.display_alias">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="control-label">
                                <i class="fa fa-cog"></i>
                                <?= __('Services'); ?>
                            </label>
                            <select
                                id="ServicesSelect"
                                data-placeholder="<?php echo __('Please choose'); ?>"
                                class="form-control"
                                chosen="services"
                                callback="loadServices"
                                multiple
                                ng-options="service.key as service.value.servicename group by service.value._matchingData.Hosts.name disable when service.disabled for service in services"
                                ng-model="post.Statuspage.selected_services._ids">
                            </select>
                        </div>
                        <div class="form-group" ng-if="post.Statuspage.selected_services._ids.length > 0">
                            <div class="row pb-2">
                                <div class="col-4 bold">
                                    <?= __('Service name'); ?>
                                </div>
                                <div class="col-8 bold">
                                    <?= __('Display name'); ?>
                                </div>
                            </div>
                            <div ng-repeat="service in services track by $index" class="row form-group"
                                 ng-if="post.Statuspage.selected_services._ids.indexOf(service.id) !== -1">
                                <div class="col-4 statuspage-item-box">
                                    <?php if ($this->Acl->hasPermission('browser', 'services')): ?>
                                        <a ui-sref="ServicesBrowser({id: service.id})"
                                           class="text-primary">{{service.value.servicename}}</a>
                                    <?php else: ?>
                                        {{service.value.servicename}}
                                    <?php endif; ?>
                                </div>
                                <div class="col-8">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">
                                                <i class="fa-solid fa-tag text-primary"></i>
                                            </span>
                                        </div>
                                        <input class="form-control form-control-sm" type="text"
                                               placeholder="<?= __("Set alias for service"); ?>"
                                               ng-model="services[$index]._joinData.display_alias">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card margin-top-10">
                            <div class="card-body">
                                <div class="float-right">
                                    <a back-button href="javascript:void(0);" fallback-state='StatuspagesIndex'
                                       class="btn btn-default"><?php echo __('Cancel'); ?>
                                    </a>
                                    <?php if ($this->Acl->hasPermission('add', 'statuspages')): ?>
                                        <button class="btn btn-primary" type="submit">
                                            <?php echo __('Save'); ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
