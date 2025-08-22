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
        <i class="fa fa-code-fork"></i> <?php echo __('Used by'); ?>
    </li>
</ol>
<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?php echo __('Contacts'); ?>
                    <span class="fw-300"><i><?php echo __('Used by...'); ?></i></span>
                </h2>
                <div class="panel-toolbar">
                    <?php if ($this->Acl->hasPermission('index', 'contacts')): ?>
                        <a back-button href="javascript:void(0);" fallback-state='ContactsIndex'
                           class="btn btn-default btn-xs mr-1 shadow-0">
                            <i class="fas fa-long-arrow-alt-left"></i> <?php echo __('Back'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <table class="table table-striped m-0 table-bordered table-hover table-sm">
                        <thead>
                        <tr ng-if="objects.Contactgroups.length > 0">
                            <th class="card-header">
                                <i class="fa fa-users"></i>
                                <?php echo __('Contact groups'); ?> ({{objects.Contactgroups.length}})
                            </th>
                        </tr>
                        <tr ng-repeat="contactgroup in objects.Contactgroups">
                            <td>
                                <?php if ($this->Acl->hasPermission('edit', 'contactgroups')): ?>
                                    <a ui-sref="ContactgroupsEdit({id: contactgroup.id})">
                                        {{ contactgroup.container.name }}
                                    </a>
                                <?php else: ?>
                                    {{ contactgroup.container.name }}
                                <?php endif; ?>
                                <div class="icon-stack margin-left-5"
                                     ng-show="containerIds.length > 0 && containerIds.indexOf(contactgroup.container.parent_id) !== -1">
                                    <i class="fas fa-lock lock-icon-gradient"></i>
                                    <i class="fas fa-exclamation-triangle fa-xs text-danger cornered cornered-lr"></i>
                                </div>
                            </td>
                        </tr>


                        <tr ng-if="objects.Hosttemplates.length > 0">
                            <th class="card-header">
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
                                <div class="icon-stack margin-left-5"
                                     ng-show="containerIds.length > 0 && containerIds.indexOf(hosttemplate.container_id) !== -1">
                                    <i class="fas fa-lock lock-icon-gradient"></i>
                                    <i class="fas fa-exclamation-triangle fa-xs text-danger cornered cornered-lr"></i>
                                </div>
                            </td>
                        </tr>

                        <tr ng-if="objects.Servicetemplates.length > 0">
                            <th class="card-header">
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
                                <div class="icon-stack margin-left-5"
                                     ng-show="containerIds.length > 0 && containerIds.indexOf(servicetemplate.container_id) !== -1">
                                    <i class="fas fa-lock lock-icon-gradient"></i>
                                    <i class="fas fa-exclamation-triangle fa-xs text-danger cornered cornered-lr"></i>
                                </div>
                            </td>
                        </tr>

                        <tr ng-if="objects.Hosts.length > 0">
                            <th class="card-header">
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
                                <div class="icon-stack margin-left-5"
                                     ng-show="containerIds.length > 0 && containerIds.indexOf(host.container_id) !== -1">
                                    <i class="fas fa-lock lock-icon-gradient"></i>
                                    <i class="fas fa-exclamation-triangle fa-xs text-danger cornered cornered-lr"></i>
                                </div>
                            </td>
                        </tr>

                        <tr ng-if="objects.Services.length > 0">
                            <th class="card-header">
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

                        <tr ng-if="objects.Hostescalations.length > 0">
                            <th class="card-header">
                                <i class="fa fa-bomb"></i>
                                <?php echo __('Host escalations'); ?> ({{objects.Hostescalations.length}})
                            </th>
                        </tr>
                        <tr ng-repeat="hostescalation in objects.Hostescalations">
                            <td>
                                <?php if ($this->Acl->hasPermission('edit', 'hostescalations')): ?>
                                    <a ui-sref="HostescalationsEdit({id:hostescalation.id})">
                                        <?php echo __('Host escalation'); ?> #{{ $index + 1 }}
                                    </a>
                                <?php else: ?>
                                    <?php echo __('Host escalation'); ?> #{{ $index +1 }}
                                <?php endif; ?>
                                <div class="icon-stack margin-left-5"
                                     ng-show="containerIds.length > 0 && containerIds.indexOf(hostescalation.container_id) !== -1">
                                    <i class="fas fa-lock lock-icon-gradient"></i>
                                    <i class="fas fa-exclamation-triangle fa-xs text-danger cornered cornered-lr"></i>
                                </div>
                            </td>
                        </tr>

                        <tr ng-if="objects.Serviceescalations.length > 0">
                            <th class="card-header">
                                <i class="fa fa-bomb"></i>
                                <?php echo __('Service escalations'); ?> ({{objects.Serviceescalations.length}})
                            </th>
                        </tr>
                        <tr ng-repeat="serviceescalation in objects.Serviceescalations">
                            <td>
                                <?php if ($this->Acl->hasPermission('edit', 'serviceescalations')): ?>
                                    <a ui-sref="ServiceescalationsEdit({id:serviceescalation.id})">
                                        <?php echo __('Service escalation'); ?> #{{ $index + 1 }}
                                    </a>
                                <?php else: ?>
                                    <?php echo __('Service escalation'); ?> #{{ $index +1 }}
                                <?php endif; ?>
                                <div class="icon-stack margin-left-5"
                                     ng-show="containerIds.length > 0 && containerIds.indexOf(hostescalation.container_id) !== -1">
                                    <i class="fas fa-lock lock-icon-gradient"></i>
                                    <i class="fas fa-exclamation-triangle fa-xs text-danger cornered cornered-lr"></i>
                                </div>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                    <div class="row margin-top-10 margin-bottom-10">
                        <div class="row margin-top-10 margin-bottom-10" ng-show="contacts.length == 0">
                            <div class="col-xs-12 text-center txt-color-red italic">
                                <?php echo __('This contact is not used by any object'); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
