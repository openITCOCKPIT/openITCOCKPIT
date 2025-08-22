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

$timezones = \Cake\I18n\DateTime::listTimezones();
?>
<ol class="breadcrumb page-breadcrumb">
    <li class="breadcrumb-item">
        <a ui-sref="DashboardsIndex">
            <i class="fa fa-home"></i> <?php echo __('Home'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <a ui-sref="ContainersIndex">
            <i class="fa fa-link"></i> <?php echo __('Containers'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <i class="fa fa-sitemap"></i> <?php echo __('Overview'); ?>
    </li>
</ol>


<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?php echo __('Containers'); ?>
                    <span class="fw-300"><i><?php echo __('overview'); ?></i></span>
                </h2>
            </div>
            <div class="panel-container">
                <div class="panel-content">
                    <form class="form-horizontal">
                        <div class="form-group">
                            <label class="control-label">
                                <?php echo __('Container'); ?>
                            </label>
                            <select
                                data-placeholder="<?php echo __('Please choose'); ?>"
                                class="form-control"
                                chosen="containers"
                                ng-model="selectedContainer.id"
                                ng-options="container.key as container.value for container in containers">
                            </select>
                        </div>
                    </form>
                </div>
                <div class="panel-content pull-right">
                    <div class="frame-wrap margin-bottom-10">
                        <div>
                            <i class="fa fa-globe"></i>
                            <em class="padding-right-20">
                                <?php echo __('Global'); ?>
                            </em>
                            <i class="fa fa-home"></i>
                            <em class="padding-right-20">
                                <?php echo __('Tenant'); ?>
                            </em>
                            <i class="fa fa-location-arrow"></i>
                            <em class="padding-right-20">
                                <?php echo __('Location'); ?>
                            </em>
                            <i class="fa fa-link"></i>
                            <em class="padding-right-20">
                                <?php echo __('Node'); ?>
                            </em>
                            <i class="fa fa-users"></i>
                            <em class="padding-right-20">
                                <?php echo __('Contactgroup'); ?>
                            </em>
                            <i class="fa fa-server"></i>
                            <em class="padding-right-20">
                                <?php echo __('Hostgroup'); ?>
                            </em>
                            <i class="fa fa-cogs"></i>
                            <em class="padding-right-20">
                                <?php echo __('Servicegroup'); ?>
                            </em>
                            <i class="fa-solid fa-pen-to-square"></i>
                            <em class="padding-right-20">
                                <?php echo __('Servicetemplategroup'); ?>
                            </em>
                            <?php if (Plugin::isLoaded('ScmModule')): ?>
                                <i class="fas fa-user-group"></i>
                                <em>
                                    <?php echo __('Resourcegroup'); ?>
                                </em>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class=" panel-content" ng-if="subcontainers">
                    <div class="row padding-top-15">
                        <div class="col col-sm-12 col-lg-12">
                            <header>
                                <h4>
                                    <i class="fa fa-link"></i>
                                    <?php echo __('Tree'); ?>
                                </h4>
                            </header>
                        </div>
                    </div>
                </div>
                <div class="panel-content">
                    <div class="row padding-top-15">
                        <div class="col col-sm-12 col-lg-12">
                            <div ng-if="subcontainers" ng-nestable ng-model="subcontainers">
                                <div>
                                    <div class="nodes-container-name" title="{{ $Container.name }}"
                                         ng-switch="$Container.containertype_id">
                                        <span class="ellipsis" ng-switch-when="<?php echo CT_GLOBAL; ?>">
                                            <i class="fa fa-globe"></i>
                                            {{ $Container.name }}

                                            <?php if ($this->Acl->hasPermission('add', 'containers')): ?>
                                                <a class="txt-color-green padding-left-10 font-xs pointer"
                                                   ng-if="$Container.allowEdit"
                                                   ng-click="openAddNodeModal($Container)">
                                                    <i class="fa fa-plus"></i>
                                                    <?php echo __('Add'); ?>
                                                </a>
                                            <?php endif; ?>
                                        </span>

                                        <span class="ellipsis" ng-switch-when="<?php echo CT_TENANT; ?>">
                                            <i class="fa fa-home"></i>
                                            <?php if ($this->Acl->hasPermission('edit', 'containers')): ?>
                                                <a ui-sref="TenantsEdit({id: $Container.linkedId})"
                                                   ng-if="$Container.allowEdit">
                                                    {{ $Container.name }}
                                                </a>
                                                <span ng-if="$Container.allowEdit === true">
                                                    <a class="txt-color-red padding-left-10 font-xs pointer"
                                                       ng-click="openEditNodeModal($Container)">
                                                        <i class="fas fa-pencil-alt"></i>
                                                        <?php echo __('Edit'); ?>
                                                    </a>
                                                </span>
                                                <span ng-if="!$Container.allowEdit">
                                                    {{ $Container.name }}
                                                </span>
                                            <?php else: ?>
                                                {{ $Container.name }}
                                            <?php endif; ?>

                                            <?php if ($this->Acl->hasPermission('add', 'containers')): ?>
                                                <a class="txt-color-green padding-left-10 font-xs pointer"
                                                   ng-if="$Container.allowEdit"
                                                   ng-click="openAddNodeModal($Container)">
                                                    <i class="fa fa-plus"></i>
                                                    <?php echo __('Add'); ?>
                                                </a>
                                            <?php endif; ?>

                                            <?php if ($this->Acl->hasPermission('showDetails', 'containers')): ?>
                                                <a class="text-info padding-left-10 font-xs pointer"
                                                   ui-sref="ContainersShowDetails({id:$Container.id})">
                                                    <i class="fa fa-info"></i>
                                                    <?php echo __('Show details'); ?>
                                                </a>
                                            <?php endif; ?>
                                        </span>

                                        <span class="ellipsis" ng-switch-when="<?php echo CT_LOCATION; ?>">
                                            <i class="fa fa-location-arrow"></i>
                                            <?php if ($this->Acl->hasPermission('edit', 'locations')): ?>
                                                <a ui-sref="LocationsEdit({id: $Container.linkedId})"
                                                   ng-if="$Container.allowEdit">
                                                    {{ $Container.name }}
                                                </a>
                                                <span ng-if="$Container.allowEdit === true">
                                                    <a class="txt-color-red padding-left-10 font-xs pointer"
                                                       ng-click="openEditNodeModal($Container)">
                                                        <i class="fas fa-pencil-alt"></i>
                                                        <?php echo __('Edit'); ?>
                                                    </a>
                                                </span>
                                                <span ng-if="!$Container.allowEdit">
                                                    {{ $Container.name }}
                                                </span>
                                            <?php else: ?>
                                                {{ $Container.name }}
                                            <?php endif; ?>

                                            <?php if ($this->Acl->hasPermission('add', 'containers')): ?>
                                                <a class="txt-color-green padding-left-10 font-xs pointer"
                                                   ng-if="$Container.allowEdit"
                                                   ng-click="openAddNodeModal($Container)">
                                                    <i class="fa fa-plus"></i>
                                                    <?php echo __('Add'); ?>
                                                </a>
                                            <?php endif; ?>

                                            <?php if ($this->Acl->hasPermission('showDetails', 'containers')): ?>
                                                <a class="text-info padding-left-10 font-xs pointer"
                                                   ui-sref="ContainersShowDetails({id:$Container.id})">
                                                    <i class="fa fa-info"></i>
                                                    <?php echo __('Show details'); ?>
                                                </a>
                                            <?php endif; ?>
                                        </span>

                                        <span class="ellipsis" ng-switch-when="<?php echo CT_NODE; ?>">
                                            <i class="fa fa-link"></i>
                                            <?php if ($this->Acl->hasPermission('edit', 'containers')): ?>
                                                <a ui-sref="ContainersIndex({id: $Container.linkedId})"
                                                   ng-if="$Container.allowEdit">
                                                    {{ $Container.name }}
                                                </a>
                                                <span ng-if="$Container.allowEdit === true">
                                                    <a class="txt-color-red padding-left-10 font-xs pointer"
                                                       ng-click="openEditNodeModal($Container)">
                                                        <i class="fas fa-pencil-alt"></i>
                                                        <?php echo __('Edit'); ?>
                                                    </a>
                                                </span>
                                                <span ng-if="!$Container.allowEdit">
                                                    {{ $Container.name }}
                                                </span>
                                            <?php else: ?>
                                                {{ $Container.name }}
                                            <?php endif; ?>

                                            <?php if ($this->Acl->hasPermission('add', 'containers')): ?>
                                                <a class="txt-color-green padding-left-10 font-xs pointer"
                                                   ng-if="$Container.allowEdit"
                                                   ng-click="openAddNodeModal($Container)">
                                                    <i class="fa fa-plus"></i>
                                                    <?php echo __('Add'); ?>
                                                </a>
                                            <?php endif; ?>

                                            <?php if ($this->Acl->hasPermission('showDetails', 'containers')): ?>
                                                <a class="text-info padding-left-10 font-xs pointer"
                                                   ui-sref="ContainersShowDetails({id:$Container.id})">
                                                    <i class="fa fa-info"></i>
                                                    <?php echo __('Show details'); ?>
                                                </a>
                                            <?php endif; ?>
                                        </span>

                                        <span class="ellipsis" ng-switch-when="<?php echo CT_CONTACTGROUP; ?>">
                                            <i class="fa fa-users"></i>
                                            <?php if ($this->Acl->hasPermission('edit', 'contactgroups')): ?>
                                                <a ui-sref="ContactgroupsEdit({id: $Container.linkedId})"
                                                   ng-if="$Container.allowEdit">
                                                    {{ $Container.name }}
                                                </a>
                                                <span ng-if="!$Container.allowEdit">
                                                    {{ $Container.name }}
                                                </span>
                                            <?php else: ?>
                                                {{ $Container.name }}
                                            <?php endif; ?>
                                        </span>

                                        <span class="ellipsis" ng-switch-when="<?php echo CT_HOSTGROUP; ?>">
                                            <i class="fa fa-server"></i>
                                            <?php if ($this->Acl->hasPermission('edit', 'hostgroups')): ?>
                                                <a ui-sref="HostgroupsEdit({id: $Container.linkedId})"
                                                   ng-if="$Container.allowEdit">
                                                    {{ $Container.name }}
                                                </a>
                                                <span ng-if="!$Container.allowEdit">
                                                    {{ $Container.name }}
                                                </span>
                                            <?php else: ?>
                                                {{ $Container.name }}
                                            <?php endif; ?>
                                        </span>

                                        <span class="ellipsis" ng-switch-when="<?php echo CT_SERVICEGROUP; ?>">
                                            <i class="fa fa-cogs"></i>
                                            <?php if ($this->Acl->hasPermission('edit', 'servicegroups')): ?>
                                                <a ui-sref="ServicegroupsEdit({id: $Container.linkedId})"
                                                   ng-if="$Container.allowEdit">
                                                    {{ $Container.name }}
                                                </a>
                                                <span ng-if="!$Container.allowEdit">
                                                    {{ $Container.name }}
                                                </span>
                                            <?php else: ?>
                                                {{ $Container.name }}
                                            <?php endif; ?>
                                        </span>

                                        <span class="ellipsis"
                                              ng-switch-when="<?php echo CT_SERVICETEMPLATEGROUP; ?>">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                            <?php if ($this->Acl->hasPermission('edit', 'servicetemplategroups')): ?>
                                                <a ui-sref="ServicetemplategroupsEdit({id: $Container.linkedId})"
                                                   ng-if="$Container.allowEdit">
                                                    {{ $Container.name }}
                                                </a>
                                                <span ng-if="!$Container.allowEdit">
                                                    {{ $Container.name }}
                                                </span>
                                            <?php else: ?>
                                                {{ $Container.name }}
                                            <?php endif; ?>
                                        </span>

                                        <?php if (Plugin::isLoaded('ScmModule')): ?>
                                            <span class="ellipsis"
                                                  ng-switch-when="<?php echo CT_RESOURCEGROUP; ?>">
                                                <i class="fas fa-user-group"></i>
                                                <?php if ($this->Acl->hasPermission('edit', 'resourcegroups', 'ScmModule')): ?>
                                                    <a ui-sref="ResourcegroupsEdit({id: $Container.linkedId})"
                                                       ng-if="$Container.allowEdit">
                                                        {{ $Container.name }}
                                                    </a>
                                                    <span ng-if="!$Container.allowEdit">
                                                        {{ $Container.name }}
                                                    </span>
                                                <?php else: ?>
                                                    {{ $Container.name }}
                                                <?php endif; ?>
                                            </span>
                                        <?php endif; ?>

                                    </div>

                                    <span class="badge border border-primary text-primary pull-right"
                                          ng-if="($Container.containertype_id == <?php echo CT_GLOBAL; ?> ||
                                                  $Container.containertype_id == <?php echo CT_TENANT; ?> ||
                                                  $Container.containertype_id == <?php echo CT_LOCATION; ?> ||
                                                  $Container.containertype_id == <?php echo CT_NODE; ?>)">
                                        <?= __('Sub containers:') ?> {{ $Container.elements }}
                                    </span>

                                    <span class="pull-right"
                                          ng-if="$Container.containertype_id == <?php echo CT_CONTACTGROUP; ?>">
                                        <i class="fa fa-user" title="<?= __('Contacts') ?>"></i> {{
                                        $Container.contacts }}
                                    </span>

                                    <span class="pull-right"
                                          ng-if="$Container.containertype_id == <?php echo CT_HOSTGROUP; ?>">
                                        <i class="fa fa-desktop" title="<?= __('Hosts') ?>"></i> {{
                                        $Container.hosts }}
                                        <i class="fa-solid fa-pen-to-square"
                                           title="<?= __('Host templates') ?>"></i> {{ $Container.hosttemplates
                                        }}
                                    </span>

                                    <span class="pull-right"
                                          ng-if="$Container.containertype_id == <?php echo CT_SERVICEGROUP; ?>">
                                        <i class="fa fa-cog" title="<?= __('Services') ?>"></i> {{
                                        $Container.services }}
                                        <i class="fa-solid fa-pen-to-square"
                                           title="<?= __('Service templates') ?>"></i> {{
                                        $Container.servicetemplates }}
                                    </span>

                                    <span class="pull-right" title="<?= __('Service template groups') ?>"
                                          ng-if="$Container.containertype_id == <?php echo CT_SERVICETEMPLATEGROUP; ?>">
                                        <i class="fa-solid fa-pen-to-square"
                                           title="<?= __('Service templates') ?>"></i> {{
                                        $Container.servicetemplates }}
                                    </span>
                                    <?php if (Plugin::isLoaded('ScmModule')): ?>
                                        <span class="pull-right" title="<?= __('resource groups') ?>"
                                              ng-if="$Container.containertype_id == <?php echo CT_RESOURCEGROUP; ?>">
                                            <i class="fa-solid fa-users"
                                               title="<?= __('Users') ?>"></i> {{
                                            $Container.users }}
                                            <i class="fas fa-chalkboard-user"
                                               title="<?= __('Resources') ?>"></i> {{
                                            $Container.resources }}
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="angularAddNodeModal" class="modal" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form ng-submit="submit()">
                <div class="modal-header">
                    <h4 class="modal-title"><?php echo __('Add new container'); ?></h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true"><i class="fa fa-times"></i></span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row padding-bottom-20">
                        <div class="col-lg-12">
                            <div class="form-group">
                                <label class="col-xs-12 control-label">
                                    <?php echo __('Select container type'); ?>
                                </label>
                                <div class="col-xs-12"
                                     ng-show="selectedContainerTypeId == <?php echo CT_GLOBAL; ?>">
                                    <select class="form-control" ng-model="post.Container.containertype_id"
                                            chosen="{}">
                                        <?php if ($this->Acl->hasPermission('add', 'tenants')): ?>
                                            <option value="<?php echo CT_TENANT; ?>">
                                                <?php echo __('Tenant'); ?>
                                            </option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                <div class="col-xs-12"
                                     ng-show="selectedContainerTypeId !== <?php echo CT_GLOBAL; ?>">
                                    <select class="form-control" ng-model="post.Container.containertype_id"
                                            chosen="{}">

                                        <?php if ($this->Acl->hasPermission('add', 'locations')): ?>
                                            <option value="<?php echo CT_LOCATION; ?>">
                                                <?php echo __('Location'); ?>
                                            </option>
                                        <?php endif; ?>
                                        <?php if ($this->Acl->hasPermission('add', 'containers')): ?>
                                            <option value="<?php echo CT_NODE; ?>" selected="selected">
                                                <?php echo __('Node'); ?>
                                            </option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row" ng-class="{'has-error': errors.Container.name || errors.name}"
                         ng-show="post.Container.containertype_id==5">
                        <div class="col-xs-12 col-md-12">
                            <div class="form-group">
                                <label class="col-xs-12 form-label">
                                    <?php echo __('Name'); ?>
                                </label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text width-40">
                                            <i class="fa fa-folder-open"></i>
                                        </span>
                                    </div>
                                    <input type="text" class="form-control"
                                           placeholder="<?php echo __('Container name'); ?>"
                                           ng-model="post.Container.name">
                                </div>
                            </div>
                        </div>
                        <div class="col-xs-12 col-md-12">
                            <div ng-repeat="error in errors.Container.name">
                                <div class="help-block font-xs text-danger">{{ error }}</div>
                            </div>
                            <div ng-repeat="error in errors.name">
                                <div class="help-block font-xs text-danger">{{ error }}</div>
                            </div>
                        </div>
                    </div>
                    <fieldset class="margin-top-10" ng-show="post.Container.containertype_id==3">
                        <div class="row" ng-class="{'has-error': errors.container.name || errors.name}">
                            <div class="col-xs-12 col-md-12">
                                <div class="form-group">
                                    <label class="col-xs-12 form-label">
                                        <?php echo __('Name'); ?>
                                    </label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text width-40">
                                                <i class="fa fa-folder-open"></i>
                                            </span>
                                        </div>
                                        <input type="text" class="form-control"
                                               placeholder="<?php echo __('Location name'); ?>"
                                               ng-model="post.Location.container.name">
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-12 col-md-12">
                                <div ng-repeat="error in errors.container.name">
                                    <div class="help-block font-xs text-danger">{{ error }}</div>
                                </div>
                                <div ng-repeat="error in errors.name">
                                    <div class="help-block font-xs text-danger">{{ error }}</div>
                                </div>
                            </div>
                        </div>
                        <legend>
                            <h3><?php echo __('Optional fields for location'); ?></h3>
                        </legend>
                        <div class="row">
                            <div class="col-xs-12 col-md-12">
                                <div class="form-group">
                                    <label class="col-xs-12 form-label">
                                        <?php echo __('Description'); ?>
                                    </label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text width-40">
                                                <i class="fa fa-info"></i>
                                            </span>
                                        </div>
                                        <input type="text" class="form-control"
                                               placeholder="<?php echo __('Description'); ?>"
                                               ng-model="post.Location.description">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div>
                            <div class="row" ng-class="{'has-error': errors.latitude}">
                                <div class="col-xs-12 col-md-12">
                                    <div class="form-group">
                                        <label class="col-xs-12 form-label">
                                            <?php echo __('Latitude'); ?>
                                        </label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text width-40">
                                                    <i class="fa fa-map-marker"></i>
                                                </span>
                                            </div>
                                            <input type="text" class="form-control"
                                                   placeholder="<?php echo '50.5558095'; ?>"
                                                   ng-model="post.Location.latitude">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xs-12 col-md-12">
                                    <div class="info-block-helptext font-xs">
                                        <?php echo __(' Latitude must be a number between -90 and 90 degree inclusive.'); ?>
                                    </div>
                                    <div ng-repeat="error in errors.latitude">
                                        <div class="help-block font-xs text-danger">{{ error }}</div>
                                    </div>
                                </div>
                            </div>


                            <div class="row" ng-class="{'has-error': errors.longitude}">
                                <div class="col-xs-12 col-md-12">
                                    <div class="form-group">
                                        <label class="col-xs-12 form-label">
                                            <?php echo __('Longitude'); ?>
                                        </label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text width-40">
                                                    <i class="fa fa-map-marker"></i>
                                                </span>
                                            </div>
                                            <input type="text" class="form-control"
                                                   placeholder="<?php echo '9.6808449'; ?>"
                                                   ng-model="post.Location.longitude">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xs-12 col-md-12">
                                    <div class="info-block-helptext font-xs">
                                        <?php echo __('Longitude must be a number -180 and 180 degree inclusive.'); ?>
                                    </div>
                                    <div ng-repeat="error in errors.longitude">
                                        <div class="help-block font-xs text-danger">{{ error }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="form-group">
                                    <label class="col-xs-12 form-label">
                                        <?php echo __('Timezone'); ?>
                                    </label>
                                    <div class="col col-xs-12 no-padding">
                                        <select class="form-control"
                                                chosen="{}"
                                                ng-model="post.Location.timezone">
                                            <?php foreach ($timezones as $continent => $continentTimezons): ?>
                                                <optgroup label="<?php echo h($continent); ?>">
                                                    <?php foreach ($continentTimezons as $timezoneKey => $timezoneName): ?>
                                                        <option
                                                            value="<?php echo h($timezoneKey); ?>"><?php echo h($timezoneName); ?></option>
                                                    <?php endforeach; ?>
                                                </optgroup>
                                            <?php endforeach;; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </fieldset>
                    <fieldset class="margin-top-10" ng-show="post.Container.containertype_id==2">
                        <legend><?php echo __('Optional fields for tenant'); ?></legend>

                        <div class="row" ng-class="{'has-error': errors.container.name || errors.name}">
                            <div class="col-xs-12 col-md-12">
                                <div class="form-group">
                                    <label class="col-xs-12 form-label">
                                        <?php echo __('Name'); ?>
                                    </label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text width-40">
                                                <i class="fa fa-folder-open"></i>
                                            </span>
                                        </div>
                                        <input type="text" class="form-control"
                                               placeholder="<?php echo __('Tenant name'); ?>"
                                               ng-model="post.Tenant.container.name">
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-12 col-md-12">
                                <div ng-repeat="error in errors.container.name">
                                    <div class="help-block font-xs text-danger">{{ error }}</div>
                                </div>
                                <div ng-repeat="error in errors.name">
                                    <div class="help-block font-xs text-danger">{{ error }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-xs-12 col-md-12">
                                <div class="form-group">
                                    <label class="col-xs-12 form-label">
                                        <?php echo __('Description'); ?>
                                    </label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text width-40">
                                                <i class="fa fa-info"></i>
                                            </span>
                                        </div>
                                        <input type="text" class="form-control"
                                               placeholder="<?php echo __('Description'); ?>"
                                               ng-model="post.Tenant.description">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-xs-12 col-md-12">
                                <div class="form-group">
                                    <label class="col-xs-12 form-label">
                                        <?php echo __('First name'); ?>
                                    </label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text width-40">
                                                <i class="fa fa-user"></i>
                                            </span>
                                        </div>
                                        <input type="text" class="form-control"
                                               placeholder="<?php echo __('John'); ?>"
                                               ng-model="post.Tenant.firstname">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-xs-12 col-md-12">
                                <div class="form-group">
                                    <label class="col-xs-12 form-label">
                                        <?php echo __('Last name'); ?>
                                    </label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text width-40">
                                                <i class="fa fa-user"></i>
                                            </span>
                                        </div>
                                        <input type="text" class="form-control"
                                               placeholder="<?php echo __('Doe'); ?>"
                                               ng-model="post.Tenant.lastname">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-xs-12 col-md-12">
                                <div class="form-group">
                                    <label class="col-xs-12 form-label">
                                        <?php echo __('Street'); ?>
                                    </label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text width-40">
                                                <i class="fa fa-road"></i>
                                            </span>
                                        </div>
                                        <input type="text" class="form-control"
                                               placeholder="<?php echo __('Any street'); ?>"
                                               ng-model="post.Tenant.street">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-xs-12 col-md-12">
                                <div class="form-group">
                                    <label class="col-xs-12 form-label">
                                        <?php echo __('Zip code'); ?>
                                    </label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text width-40">
                                                <i class="fas fa-building"></i>
                                            </span>
                                        </div>
                                        <input type="text" class="form-control"
                                               placeholder="<?php echo __('12345'); ?>"
                                               ng-model="post.Tenant.zipcode">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-xs-12 col-md-12">
                                <div class="form-group">
                                    <label class="col-xs-12 form-label">
                                        <?php echo __('City'); ?>
                                    </label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text width-40">
                                                <i class="fa-solid fa-tree-city"></i>
                                            </span>
                                        </div>
                                        <input type="text" class="form-control"
                                               placeholder="<?php echo __('Any city'); ?>"
                                               ng-model="post.Tenant.city">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </fieldset>
                </div>
                <div class="modal-footer">
                    <div class="pull-left" ng-repeat="error in errors.id">
                        <div class="help-block text-danger">{{ error }}</div>
                    </div>
                    <button type="submit" class="btn btn-primary"
                            ng-show="post.Container.containertype_id==<?php echo CT_NODE; ?>">
                        <?php echo __('Create new node'); ?>
                    </button>
                    <button type="submit" class="btn btn-primary"
                            ng-show="post.Container.containertype_id==<?php echo CT_TENANT; ?>">
                        <?php echo __('Create new tenant'); ?>
                    </button>
                    <button type="submit" class="btn btn-primary"
                            ng-show="post.Container.containertype_id==<?php echo CT_LOCATION; ?>">
                        <?php echo __('Create new location'); ?>
                    </button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        <?php echo __('Cancel'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="angularEditNodeModal" class="modal" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form onsubmit="return false;">
                <div class="modal-header">
                    <h4 class="modal-title"><?php echo __('Edit container name'); ?></h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true"><i class="fa fa-times"></i></span>
                    </button>
                </div>
                <div class="modal-body" ng-class="{'has-error': errors.name}">
                    <div class="row">
                        <div class="col-xs-12 col-md-12">
                            <div class="form-group">
                                <label class="col-xs-12 form-label">
                                    <?php echo __('Name'); ?>
                                </label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text width-40">
                                            <i class="fa fa-folder-open"></i>
                                        </span>
                                    </div>
                                    <input type="text" class="form-control"
                                           placeholder="<?php echo __('Container name'); ?>"
                                           ng-model="post.Container.name">
                                </div>
                            </div>
                        </div>
                        <div class="col-xs-12 col-md-12">
                            <div ng-repeat="error in errors.name">
                                <div class="help-block text-danger">{{ error }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12" ng-show="containerNotEmpty">
                        <span class="text-danger">{{message}}</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="mr-auto">
                        <button type="button" class="btn btn-danger" ng-click="deleteNode()"
                                ng-class="{'has-error': errors.id}">
                            <i class="fa fa-refresh fa-spin" ng-if="isDeleting" ng-show="isDeleting"
                               ng-hide="containerNotEmpty"></i>
                            <i class="fas fa-exclamation-triangle" ng-show="containerNotEmpty"></i>
                            <?php echo __('Delete'); ?>
                        </button>
                        <button type="button" class="btn btn-primary" ng-show="containerNotEmpty"
                                ng-click="showDetailedView(containerId)"><?= __('Show Details'); ?></button>
                        <div ng-repeat="error in errors.id">
                            <div class="help-block text-danger">{{ error }}</div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary" ng-click="updateNode()">
                        <?php echo __('Save'); ?>
                    </button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        <?php echo __('Cancel'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
