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
            <i class="fa fa-home"></i>
            <?= __('Home'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <a ui-sref="WizardsIndex">
            <i class="fa-solid fa-wand-magic-sparkles"></i> <?php echo __('Wizards'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <i class="fa-solid fa-wand-magic-sparkles"></i> {{title}}
    </li>
</ol>

<div class="row">
    <div class="col-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?php echo __('Configuration Wizard: '); ?>{{title}}
                </h2>
            </div>
            <div class="panel-container show">
                <div class="panel-content fuelux">

                    <form ng-submit="submit();" class="form-horizontal">

                        <div class="wizard">
                            <ul class="nav nav-tabs step-anchor">
                                <li class="active">
                                    <span class="badge badge-info">
                                        <i class="fa fa-info"></i>
                                    </span>
                                    <?php echo __('Host Information'); ?>
                                    <span class="chevron"></span>
                                </li>
                            </ul>
                            <div class="pull-right margin-right-5" style="margin-top: -39px;">
                                <div class="actions" style="position: relative; display: inline;">
                                    <button type="submit" class="btn btn-success btn-sm waves-effect waves-themed">
                                        <?php echo __('Next'); ?> <i class="fa fa-arrow-right"></i>
                                    </button>
                                </div>
                            </div>
                        </div>


                        <div class="step-content">
                            <div class="card margin-top-20 margin-bottom-10">
                                <div class="card-header">
                                    <i class="fa-solid fa-wand-magic-sparkles"></i> <?php echo __('Basic configuration'); ?>
                                </div>
                                <div class="card-body">
                                    <div class="form-group padding-top-10">
                                        <div class="col-12 no-padding">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox"
                                                       id="useExistingHost"
                                                       name="checkbox"
                                                       ng-checked="typeId === 'prometheus'"
                                                       ng-disabled="typeId === 'prometheus'"
                                                       class="custom-control-input"
                                                       ng-model="useExistingHost">
                                                <label class="custom-control-label"
                                                       for="useExistingHost">
                                                    <?php echo __('Use existing host'); ?></label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group required" ng-class="{'has-error': errors.Host}"
                                         ng-show="useExistingHost">
                                        <label class="col col-2 control-label">
                                            <?php echo __('Host'); ?>
                                        </label>
                                        <div class="col col-12">
                                            <select id="Hosts"
                                                    data-placeholder="<?php echo __('Please choose'); ?>"
                                                    class="form-control"
                                                    chosen="hosts"
                                                    callback="loadHosts"
                                                    ng-options="host.key as host.value disable when host.disabled for host in hosts"
                                                    ng-model="hostId">
                                            </select>
                                            <div ng-repeat="error in errors.Host">
                                                <div class="help-block text-danger">{{ error }}</div>
                                            </div>
                                        </div>
                                        <div class="col col-12" ng-show="additionalInfo">
                                            <div class="help-block text-info">
                                                <i class="fa fa-info-circle"></i>
                                                {{additionalInfo}}
                                            </div>
                                        </div>
                                    </div>
                                    <div ng-hide="useExistingHost">
                                        <div class="form-group required" ng-class="{'has-error': errors.container_id}">
                                            <label class="control-label" for="HostContainer">
                                                <?php echo __('Container'); ?>
                                            </label>
                                            <select
                                                id="HostContainer"
                                                data-placeholder="<?php echo __('Please choose'); ?>"
                                                class="form-control"
                                                chosen="containers"
                                                ng-options="container.key as container.value for container in containers"
                                                ng-model="post.Host.container_id">
                                            </select>
                                            <div ng-show="post.Host.container_id < 1" class="warning-glow">
                                                <?php echo __('Please select a container.'); ?>
                                            </div>
                                            <div ng-repeat="error in errors.container_id">
                                                <div class="help-block text-danger">{{ error }}</div>
                                            </div>
                                        </div>

                                        <div class="form-group required"
                                             ng-class="{'has-error': errors.hosttemplate_id}">
                                            <label class="control-label" for="HostTemplate">
                                                <?php echo __('Host template'); ?>
                                            </label>
                                            <select
                                                id="HostTemplate"
                                                data-placeholder="<?php echo __('Please choose'); ?>"
                                                class="form-control"
                                                chosen="hosttemplates"
                                                ng-options="hosttemplate.key as hosttemplate.value for hosttemplate in hosttemplates"
                                                ng-model="post.Host.hosttemplate_id">
                                            </select>
                                            <div ng-show="post.Host.hosttemplate_id < 1" class="warning-glow">
                                                <?php echo __('Please select a host template.'); ?>
                                            </div>
                                            <div ng-repeat="error in errors.hosttemplate_id">
                                                <div class="help-block text-danger">{{ error }}</div>
                                            </div>
                                        </div>

                                        <div class="form-group required" ng-class="{'has-error': errors.name}">
                                            <label class="control-label">
                                                <?php echo __('Host name'); ?>
                                            </label>
                                            <input
                                                id="HostName"
                                                class="form-control"
                                                type="text"
                                                ng-model="post.Host.name"
                                                ng-blur="runDnsLookup(true); checkForDuplicateHostname();">
                                            <div ng-repeat="error in errors.name">
                                                <div class="help-block text-danger">{{ error }}</div>
                                            </div>
                                            <div class="text-warning" ng-show="data.dnsHostnameNotFound">
                                                <i class="fa fa-exclamation-triangle"></i>
                                                <?php echo __('Could not resolve hostname.'); ?>
                                            </div>
                                        </div>

                                        <div class="alert alert-warning" role="alert" ng-show="isHostnameInUse">
                                            <div class="d-flex align-items-center">
                                                <div class="alert-icon width-3">
                                                    <div class='icon-stack  icon-stack-sm'>
                                                        <i class="base base-9 icon-stack-3x opacity-100 color-warning-600"></i>
                                                        <i class="fas fa-exclamation-circle icon-stack-1x opacity-100 color-white"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-1">
                                                    <?= __('A host with the name <strong>{0}</strong> already exists. Duplicate host names could lead to confusion.', '{{ checkedName }}'); ?>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <div class="custom-control custom-checkbox  margin-bottom-10">
                                                <input type="checkbox"
                                                       class="custom-control-input"
                                                       id="HostDNSLookup"
                                                       ng-model="data.dnsLookUp">
                                                <label class="custom-control-label" for="HostDNSLookup">
                                                    <?php echo __('DNS Lookup'); ?>
                                                </label>
                                            </div>
                                        </div>

                                        <div class="form-group required" ng-class="{'has-error': errors.address}">
                                            <label class="control-label">
                                                <?php echo __('Host address'); ?>
                                            </label>
                                            <input
                                                id="HostAddress"
                                                class="form-control"
                                                type="text"
                                                placeholder="<?php echo __('IPv4/IPv6 address or FQDN'); ?>"
                                                ng-model="post.Host.address"
                                                ng-blur="runDnsLookup(false)">
                                            <div ng-repeat="error in errors.address">
                                                <div class="help-block text-danger">{{ error }}</div>
                                            </div>
                                            <div class="text-warning" ng-show="data.dnsAddressNotFound">
                                                <i class="fa fa-exclamation-triangle"></i>
                                                <?php echo __('Could not resolve address.'); ?>
                                            </div>
                                        </div>
                                        <div ng-show="post.Host.hosttemplate_id">
                                            <div class="form-group" ng-class="{'has-error': errors.description}">
                                                <label class="control-label">
                                                    <?php echo __('Description'); ?>
                                                </label>
                                                <div class="input-group">
                                                    <input
                                                        class="form-control"
                                                        type="text"
                                                        ng-model="post.Host.description">

                                                    <template-diff ng-show="post.Host.hosttemplate_id"
                                                                   value="post.Host.description"
                                                                   template-value="hosttemplate.Hosttemplate.description"></template-diff>
                                                </div>
                                                <div ng-repeat="error in errors.description">
                                                    <div class="help-block text-danger">{{ error }}</div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php if (Plugin::isLoaded('DistributeModule')): ?>
                                            <div class="form-group padding-top-10 padding-bottom-10"
                                                 ng-class="{'has-error': errors.satellite_id}">
                                                <label class="control-label" for="SatellitesSelect">
                                                    <?php if ($this->Acl->hasPermission('edit', 'satellites', 'DistributeModule')): ?>
                                                        <a ui-sref="SatellitesEdit({id:post.Host.satellite_id})"
                                                           ng-if="post.Host.satellite_id > 0">
                                                            <?= __('Satellite'); ?>
                                                        </a>
                                                        <span
                                                            ng-if="post.Host.satellite_id == 0"><?php echo __('Satellite'); ?></span>
                                                    <?php else: ?>
                                                        <?= __('Satellite'); ?>
                                                    <?php endif; ?>
                                                </label>
                                                <select
                                                    id="SatellitesSelect"
                                                    data-placeholder="<?php echo __('Please choose'); ?>"
                                                    class="form-control"
                                                    chosen="satellites"
                                                    ng-options="satellite.key as satellite.value for satellite in satellites"
                                                    ng-model="post.Host.satellite_id">
                                                </select>
                                                <div ng-repeat="error in errors.satellite_id">
                                                    <div class="help-block text-danger">{{ error }}</div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
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
