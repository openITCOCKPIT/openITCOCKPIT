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
        <a ui-sref="ContactgroupsIndex">
            <i class="fa fa-users"></i> <?php echo __('Contact group'); ?>
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
                    <?php echo __('Contact group'); ?>
                    <span class="fw-300"><i>
                        <strong>
                            »{{ contactgroupWithRelations.container.name }}«
                        </strong>
                        <?php echo __('is used by'); ?>
                        {{ total }}
                        <?php echo __('objects.'); ?></i></span>
                </h2>
                <div class="panel-toolbar">
                    <?php if ($this->Acl->hasPermission('index', 'contactgroups')): ?>
                        <a back-button href="javascript:void(0);" fallback-state='ContactgroupsIndex' class="btn btn-default btn-xs mr-1 shadow-0">
                            <i class="fas fa-long-arrow-alt-left"></i> <?php echo __('Back'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <table class="table table-striped m-0 table-bordered table-hover table-sm">
                        <tbody>
                        <tr ng-if="contactgroupWithRelations.hosttemplates.length > 0">
                            <th class="bg-color-lightGray">
                                <i class="fa-solid fa-pen-to-square"></i>
                                <?php echo __('Host template'); ?>
                                ({{contactgroupWithRelations.hosttemplates.length}})
                            </th>
                        </tr>
                        <tr ng-repeat="hosttemplate in contactgroupWithRelations.hosttemplates">
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
                        <tr ng-if="contactgroupWithRelations.hosts.length > 0">
                            <th class="bg-color-lightGray">
                                <i class="fa fa-desktop"></i>
                                <?php echo __('Host'); ?> ({{contactgroupWithRelations.hosts.length}})
                            </th>
                        </tr>
                        <tr ng-repeat="host in contactgroupWithRelations.hosts">
                            <td>
                                <?php if ($this->Acl->hasPermission('edit', 'hosts')): ?>
                                    <a ui-sref="HostsEdit({id:host.id})">
                                        {{ host.name }} ({{ host.address }})
                                    </a>
                                <?php else: ?>
                                    {{ host.name }} ({{ host.address }})
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr ng-if="contactgroupWithRelations.servicetemplates.length > 0">
                            <th class="bg-color-lightGray">
                                <i class="fa-solid fa-pen-to-square"></i>
                                <?php echo __('Service template'); ?>
                                ({{contactgroupWithRelations.servicetemplates.length}})
                            </th>
                        </tr>
                        <tr ng-repeat="servicetemplate in contactgroupWithRelations.servicetemplates">
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
                        <tr ng-if="contactgroupWithRelations.services.length > 0">
                            <th class="bg-color-lightGray">
                                <i class="fa fa-cog"></i>
                                <?php echo __('Service'); ?> ({{contactgroupWithRelations.services.length}})
                            </th>
                        </tr>
                        <tr ng-repeat="service in contactgroupWithRelations.services">
                            <td>
                                <?php if ($this->Acl->hasPermission('edit', 'services')): ?>
                                    <a ui-sref="ServicesEdit({id: service.id})">
                                        {{ service.name }}
                                    </a>
                                <?php else: ?>
                                    {{ service.name }}
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr ng-if="contactgroupWithRelations.hostescalations.length > 0">
                            <th class="bg-color-lightGray">
                                <i class="fa fa-bomb"></i>
                                <?php echo __('Host escalation'); ?>
                                ({{contactgroupWithRelations.hostescalations.length}})
                            </th>
                        </tr>
                        <tr ng-repeat="hostescalation in contactgroupWithRelations.hostescalations">
                            <td>
                                <?php if ($this->Acl->hasPermission('edit', 'hostescalations')): ?>
                                    <a ui-sref="HostescalationsEdit({id: hostescalation.id})">
                                        <?php echo __('Host escalation'); ?> #{{ $index +1 }}
                                    </a>
                                <?php else: ?>
                                    <?php echo __('Host escalation'); ?> #{{ $index +1 }}
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr ng-if="contactgroupWithRelations.serviceescalations.length > 0">
                            <th class="bg-color-lightGray">
                                <i class="fa fa-bomb"></i>
                                <?php echo __('Service escalation'); ?>
                                ({{contactgroupWithRelations.serviceescalations.length}})
                            </th>
                        </tr>
                        <tr ng-repeat="serviceescalation in contactgroupWithRelations.serviceescalations">
                            <td>
                                <?php if ($this->Acl->hasPermission('edit', 'serviceescalations')): ?>
                                    <a ui-sref="ServiceescalationsEdit({id: serviceescalation.id})">
                                        <?php echo __('Service escalation'); ?> #{{ $index +1 }}
                                    </a>
                                <?php else: ?>
                                    <?php echo __('Service escalation'); ?> #{{ $index +1 }}
                                <?php endif; ?>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                    <div class="row margin-top-10 margin-bottom-10">
                        <div class="row margin-top-10 margin-bottom-10" ng-show="contacts.length == 0">
                            <div class="col-xs-12 text-center txt-color-red italic">
                                <?php echo __('No entries match the selection'); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
