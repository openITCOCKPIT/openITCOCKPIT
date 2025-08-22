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
        <a ui-sref="CommandsIndex">
            <i class="fa fa-terminal"></i> <?php echo __('Commands'); ?>
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
                    <?php echo __('Command'); ?>
                    <span class="fw-300">
                        <i>
                            <strong>
                                »{{ command.name }}«
                            </strong>
                            <?php echo __('is used by'); ?>
                            {{ total }}
                            <?php echo __('objects.'); ?>
                        </i>
                    </span>
                </h2>
                <div class="panel-toolbar">
                    <?php if ($this->Acl->hasPermission('index', 'commands')): ?>
                        <a back-button href="javascript:void(0);" fallback-state='CommandsIndex' class="btn btn-default btn-xs mr-1 shadow-0">
                            <i class="fas fa-long-arrow-alt-left"></i> <?php echo __('Back'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <div class="frame-wrap">
                        <table id="usedby_list" class="table table-striped m-0 table-bordered table-hover table-sm">
                            <tbody>
                            <tr ng-if="objects.Contacts.length > 0">
                                <th colspan="2" class="bg-color-lightGray">
                                    <i class="fa fa-user"></i>
                                    <?php echo __('Contacts'); ?> ({{objects.Contacts.length}})
                                </th>
                            </tr>
                            <tr ng-repeat="contact in objects.Contacts">
                                <td colspan="2">
                                    <?php if ($this->Acl->hasPermission('edit', 'hosttemplates')): ?>
                                        <a ui-sref="ContactsEdit({id: contact.id})">
                                            {{ contact.name }}
                                        </a>
                                    <?php else: ?>
                                        {{ contact.name }}
                                    <?php endif; ?>
                                </td>
                            </tr>


                            <tr ng-if="objects.Hosttemplates.length > 0">
                                <th colspan="2" class="bg-color-lightGray">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                    <?php echo __('Host templates'); ?> ({{objects.Hosttemplates.length}})
                                </th>
                            </tr>
                            <tr ng-repeat="hosttemplate in objects.Hosttemplates">
                                <td colspan="2">
                                    <?php if ($this->Acl->hasPermission('edit', 'hosttemplates')): ?>
                                        <a ui-sref="HosttemplatesEdit({id: hosttemplate.id})">
                                            {{ hosttemplate.name }}
                                        </a>
                                    <?php else: ?>
                                        {{ hosttemplate.name }}
                                    <?php endif; ?>
                                </td>
                            </tr>

                            <tr ng-if="objects.Servicetemplates.length > 0">
                                <th colspan="2" class="bg-color-lightGray">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                    <?php echo __('Service templates'); ?> ({{objects.Servicetemplates.length}})
                                </th>
                            </tr>
                            <tr ng-repeat="servicetemplate in objects.Servicetemplates">
                                <td style="width:50%">
                                    <?php if ($this->Acl->hasPermission('edit', 'servicetemplates')): ?>
                                        <a ui-sref="ServicetemplatesEdit({id: servicetemplate.id})">
                                            {{ servicetemplate.template_name }}
                                        </a>
                                    <?php else: ?>
                                        {{ servicetemplate.template_name }}
                                    <?php endif; ?>
                                </td>
                                <td style="width:50%">
                                    {{ servicetemplate.name }}
                                </td>
                            </tr>

                            <tr ng-if="objects.Hosts.length > 0">
                                <th colspan="2" class="bg-color-lightGray">
                                    <i class="fa fa-desktop"></i>
                                    <?php echo __('Hosts'); ?> ({{objects.Hosts.length}})
                                </th>
                            </tr>
                            <tr ng-repeat="host in objects.Hosts">
                                <td colspan="2">
                                    <?php if ($this->Acl->hasPermission('edit', 'hosts')): ?>
                                        <a ui-sref="HostsEdit({id:host.id})">
                                            {{ host.name }}
                                        </a>
                                    <?php else: ?>
                                        {{ host.name }}
                                    <?php endif; ?>
                                </td>
                            </tr>

                            <tr ng-if="objects.Services.length > 0">
                                <th colspan="2" class="bg-color-lightGray">
                                    <i class="fa fa-cog"></i>
                                    <?php echo __('Services'); ?> ({{objects.Services.length}})
                                </th>
                            </tr>
                            <tr ng-repeat="service in objects.Services">
                                <td colspan="2">
                                    <?php if ($this->Acl->hasPermission('edit', 'services')): ?>
                                        <a ui-sref="ServicesEdit({id:service.id})">
                                            {{ service._matchingData.Hosts.name }} / {{ service.servicename }}
                                        </a>
                                    <?php else: ?>
                                        {{ service._matchingData.Hosts.name }} / {{ service.servicename }}
                                    <?php endif; ?>
                                </td>
                            </tr>

                            </tbody>
                        </table>
                        <div class="row margin-top-10 margin-bottom-10" ng-show="total == 0">
                            <div class="col-lg-12 d-flex justify-content-center txt-color-red italic">
                                <?php echo __('This command is not used by any object'); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
