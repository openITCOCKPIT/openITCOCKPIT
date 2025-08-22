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
        <a ui-sref="TimeperiodsIndex">
            <i class="fa fa-clock-o"></i> <?php echo __('Time periods'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <i class="fa fa-code-fork"></i> <?php echo __('Used by'); ?>
    </li>
</ol>
<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?php echo __('Time period'); ?>
                    <span class="fw-300">
                        <i>
                            <strong>
                                »{{ timeperiod.name }}«
                            </strong>
                            <?php echo __('is used by'); ?>
                            {{ total }}
                            <?php echo __('objects.'); ?>
                        </i>
                    </span>
                </h2>
                <div class="panel-toolbar">
                    <?php if ($this->Acl->hasPermission('index', 'timeperiods')): ?>
                        <a back-button href="javascript:void(0);" fallback-state='TimeperiodsIndex' class="btn btn-default btn-xs mr-1 shadow-0">
                            <i class="fas fa-long-arrow-alt-left"></i> <?php echo __('Back'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <div class="frame-wrap">
                        <table class="table table-striped m-0 table-bordered table-hover table-sm">
                            <tbody>
                            <tr ng-if="objects.Contacts.length > 0">
                                <th class="bg-color-lightGray">
                                    <i class="fa fa-user"></i>
                                    <?php echo __('Contacts'); ?> ({{objects.Contacts.length}})
                                </th>
                            </tr>
                            <tr ng-repeat="contact in objects.Contacts">
                                <td>
                                    <?php if ($this->Acl->hasPermission('edit', 'contacts')): ?>
                                        <a ui-sref="ContactsEdit({id: contact.id})">
                                            {{ contact.name }}
                                        </a>
                                    <?php else: ?>
                                        {{ contact.name }}
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr ng-if="objects.Hostdependencies.length > 0">
                                <th class="bg-color-lightGray">
                                    <i class="fa fa-sitemap"></i>
                                    <?php echo __('Host dependencies'); ?> ({{objects.Hostdependencies.length}})
                                </th>
                            </tr>
                            <tr ng-repeat="hostdependency in objects.Hostdependencies">
                                <td>
                                    <?php if ($this->Acl->hasPermission('edit', 'hostdependencies')): ?>
                                        <a ui-sref="HostdependenciesEdit({id:hostdependency.id})">
                                            <?php echo __('Host dependency'); ?> #{{ $index +1 }}
                                        </a>
                                    <?php else: ?>
                                        <?php echo __('Host dependency'); ?> #{{ $index +1 }}
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr ng-if="objects.Hostescalations.length > 0">
                                <th class="bg-color-lightGray">
                                    <i class="fa fa-bomb"></i>
                                    <?php echo __('Host escalations'); ?> ({{objects.Hostescalations.length}})
                                </th>
                            </tr>
                            <tr ng-repeat="hostescalation in objects.Hostescalations">
                                <td>
                                    <?php if ($this->Acl->hasPermission('edit', 'hostescalations')): ?>
                                        <a ui-sref="HostescalationsEdit({id:hostescalation.id})">
                                            <?php echo __('Host escalation'); ?> #{{ $index +1 }}
                                        </a>
                                    <?php else: ?>
                                        <?php echo __('Host escalation'); ?> #{{ $index +1 }}
                                    <?php endif; ?>
                                </td>
                            </tr>

                            <tr ng-if="objects.Hosts.length > 0">
                                <th class="bg-color-lightGray">
                                    <i class="fa fa-desktop"></i>
                                    <?php echo __('Hosts'); ?> ({{objects.Hosts.length}})
                                </th>
                            </tr>
                            <tr ng-repeat="host in objects.Hosts">
                                <td>
                                    <?php if ($this->Acl->hasPermission('edit', 'hosts')): ?>
                                        <a ui-sref="HostsEdit({id:host.id})">
                                            {{ host.name }}
                                        </a>
                                    <?php else: ?>
                                        {{ host.name }}
                                    <?php endif; ?>
                                </td>
                            </tr>

                            <tr ng-if="objects.Hosttemplates.length > 0">
                                <th class="bg-color-lightGray">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                    <?php echo __('Host templates'); ?> ({{objects.Hosttemplates.length}})
                                </th>
                            </tr>
                            <tr ng-repeat="hosttemplate in objects.Hosttemplates">
                                <td>
                                    <?php if ($this->Acl->hasPermission('edit', 'hosttemplates')): ?>
                                        <a ui-sref="HosttemplatesEdit({id: hosttemplate.id})">
                                            {{ hosttemplate.name }}
                                        </a>
                                    <?php else: ?>
                                        {{ hosttemplate.name }}
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr ng-if="objects.Instantreports.length > 0">
                                <th class="bg-color-lightGray">
                                    <i class="fa fa-file-invoice"></i>
                                    <?php echo __('Instant reports'); ?> ({{objects.Instantreports.length}})
                                </th>
                            </tr>
                            <tr ng-repeat="instantreport in objects.Instantreports">
                                <td>
                                    <?php if ($this->Acl->hasPermission('edit', 'instantreports')): ?>
                                        <a ui-sref="InstantreportsEdit({id: instantreport.id})">
                                            {{ instantreport.name }}
                                        </a>
                                    <?php else: ?>
                                        {{ instantreport.name }}
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr ng-if="objects.Servicedependencies.length > 0">
                                <th class="bg-color-lightGray">
                                    <i class="fa fa-sitemap"></i>
                                    <?php echo __('Service dependencies'); ?> ({{objects.Servicedependencies.length}})
                                </th>
                            </tr>
                            <tr ng-repeat="servicedependency in objects.Servicedependencies">
                                <td>
                                    <?php if ($this->Acl->hasPermission('edit', 'servicedependencies')): ?>
                                        <a ui-sref="ServicedependenciesEdit({id:servicedependency.id})">
                                            <?php echo __('Service dependency'); ?> #{{ $index +1 }}
                                        </a>
                                    <?php else: ?>
                                        <?php echo __('Service dependency'); ?> #{{ $index +1 }}
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr ng-if="objects.Serviceescalations.length > 0">
                                <th class="bg-color-lightGray">
                                    <i class="fa fa-bomb"></i>
                                    <?php echo __('Service escalations'); ?> ({{objects.Serviceescalations.length}})
                                </th>
                            </tr>
                            <tr ng-repeat="serviceescalation in objects.Serviceescalations">
                                <td>
                                    <?php if ($this->Acl->hasPermission('edit', 'serviceescalations')): ?>
                                        <a ui-sref="ServiceescalationsEdit({id:serviceescalation.id})">
                                            <?php echo __('Service escalation'); ?> #{{ $index +1 }}
                                        </a>
                                    <?php else: ?>
                                        <?php echo __('Service escalation'); ?> #{{ $index +1 }}
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr ng-if="objects.Services.length > 0">
                                <th class="bg-color-lightGray">
                                    <i class="fa fa-cog"></i>
                                    <?php echo __('Services'); ?> ({{objects.Services.length}})
                                </th>
                            </tr>
                            <tr ng-repeat="service in objects.Services">
                                <td>
                                    <?php if ($this->Acl->hasPermission('edit', 'services')): ?>
                                        <a ui-sref="ServicesEdit({id:service.id})">
                                            {{ service._matchingData.Hosts.name }} / {{ service.servicename }}
                                        </a>
                                    <?php else: ?>
                                        {{ service._matchingData.Hosts.name }} / {{ service.servicename }}
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr ng-if="objects.Servicetemplates.length > 0">
                                <th class="bg-color-lightGray">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                    <?php echo __('Service templates'); ?> ({{objects.Servicetemplates.length}})
                                </th>
                            </tr>
                            <tr ng-repeat="servicetemplate in objects.Servicetemplates">
                                <td>
                                    <?php if ($this->Acl->hasPermission('edit', 'servicetemplates')): ?>
                                        <a ui-sref="ServicetemplatesEdit({id: servicetemplate.id})">
                                            {{ servicetemplate.name }}
                                        </a>
                                    <?php else: ?>
                                        {{ servicetemplate.name }}
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr ng-if="objects.Autoreports.length > 0">
                                <th class="bg-color-lightGray">
                                    <i class="fa fa-file-invoice"></i>
                                    <?php echo __('Autoreports'); ?> ({{objects.Autoreports.length}})
                                </th>
                            </tr>
                            <tr ng-repeat="autoreport in objects.Autoreports">
                                <td>
                                    <?php if ($this->Acl->hasPermission('edit', 'autoreports', 'AutoreportModule')): ?>
                                        <a ui-sref="AutoreportsEditStepOne({id: autoreport.id})">
                                            {{ autoreport.name }}
                                        </a>
                                    <?php else: ?>
                                        {{ autoreport.name }}
                                    <?php endif; ?>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                        <div class="row margin-top-10 margin-bottom-10" ng-show="total == 0">
                            <div class="col-lg-12 d-flex justify-content-center txt-color-red italic">
                                <?php echo __('This timeperiod is not used by any object'); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
