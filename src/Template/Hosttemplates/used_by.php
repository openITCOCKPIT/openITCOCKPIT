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
        <a ui-sref="HosttemplatesIndex">
            <i class="fa-solid fa-pen-to-square"></i> <?php echo __('Host templates'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <i class="fa fa-code-fork"></i> <?php echo __('Used by'); ?>
    </li>
</ol>
<massdelete></massdelete>
<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?php echo __('Host template'); ?>
                    <span class="fw-300">
                        <i>
                            <strong>
                                »{{ hosttemplate.name }}«
                            </strong>
                            <?php echo __('is used by'); ?>
                            {{ total }}
                            <?php echo __('host/s.'); ?>
                        </i>
                    </span>
                </h2>
                <div class="panel-toolbar">
                    <?php if ($this->Acl->hasPermission('index', 'hosttemplates')): ?>
                        <a back-button href="javascript:void(0);" fallback-state='HosttemplatesIndex'
                           class="btn btn-default btn-xs mr-1 shadow-0">
                            <i class="fas fa-long-arrow-alt-left"></i> <?php echo __('Back'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <table id="usedby_list" class="table table-striped m-0 table-bordered table-hover table-sm">
                        <tbody>
                        <tr>
                            <th class="no-sort sorting_disabled width-15">
                                <i class="fa fa-check-square"></i>
                            </th>
                            <th>
                                <?php echo __('Host name'); ?>
                            </th>
                            <th class="no-sort text-center editItemWidth">
                                <i class="fa fa-gear fa-lg"></i>
                            </th>
                        </tr>
                        <tr ng-repeat="host in allHosts">
                            <td class="text-center" class="width-15">
                                <?php if ($this->Acl->hasPermission('edit', 'hosts')): ?>
                                    <input type="checkbox"
                                           ng-if="host.Host.allow_edit"
                                           ng-model="massChange[host.Host.id]">
                                <?php endif; ?>
                            </td>
                            <td class="">
                                <?php if ($this->Acl->hasPermission('edit', 'hosts')): ?>
                                    <a ui-sref="HostsBrowser({id: host.Host.id})">
                                        {{ host.Host.hostname }} ({{ host.Host.address }})

                                        <span ng-show="host.Host.disabled" title="<?php echo __('Disabled'); ?>">
                                            <i class="fa fa-plug"></i>
                                        </span>
                                    </a>
                                <?php else: ?>
                                    {{ host.Host.hostname }} ({{ host.Host.address }})
                                    <span ng-show="host.Host.disabled" title="<?php echo __('Disabled'); ?>">
                                        <i class="fa fa-plug"></i>
                                    </span>
                                <?php endif; ?>
                                <?php if ($this->Acl->hasPermission('serviceList', 'services')): ?>
                                    <a class="pull-right txt-color-blueDark"
                                       ui-sref="ServicesServiceList({id: host.Host.id})">
                                        <i class="fa fa-list" title="<?php echo __('Go to Service list'); ?>"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                            <td class="width-50">
                                <div class="btn-group btn-group-xs" role="group">
                                    <?php if ($this->Acl->hasPermission('edit', 'hosts')): ?>
                                        <a ui-sref="HostsEdit({id: host.Host.id})"
                                           ng-if="host.Host.allow_edit"
                                           class="btn btn-default btn-lower-padding">
                                            <i class="fa fa-cog"></i>
                                        </a>
                                    <?php else: ?>
                                        <a href="javascript:void(0);"
                                           class="btn btn-default btn-lower-padding">
                                            <i class="fa fa-cog"></i></a>
                                    <?php endif; ?>
                                    <button type="button"
                                            class="btn btn-default dropdown-toggle btn-lower-padding"
                                            data-toggle="dropdown">
                                        <i class="caret"></i>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-right">
                                        <?php if ($this->Acl->hasPermission('edit', 'hosts')): ?>
                                            <a ui-sref="HostsEdit({id: host.Host.id})"
                                               ng-if="host.Host.allow_edit"
                                               class="dropdown-item">
                                                <i class="fa fa-cog"></i>
                                                <?php echo __('Edit'); ?>
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($this->Acl->hasPermission('delete', 'hosts')): ?>
                                            <div class="dropdown-divider"></div>
                                            <a ng-click="confirmDelete(getObjectForDelete(host))"
                                               ng-if="host.Host.allow_edit"
                                               href="javascript:void(0);"
                                               class="dropdown-item txt-color-red">
                                                <i class="fa fa-trash"></i>
                                                <?php echo __('Delete'); ?>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                    <div class="margin-top-10" ng-show="allHosts.length == 0">
                        <div class="text-center text-danger italic">
                            <?php echo __('This host template is not used by any host'); ?>
                        </div>
                    </div>
                    <div class="row margin-top-10 margin-bottom-10">
                        <div class="col-xs-12 col-md-2 text-muted text-center">
                            <span ng-show="selectedElements > 0">({{selectedElements}})</span>
                        </div>
                        <div class="col-xs-12 col-md-2">
                            <span ng-click="selectAll()" class="pointer">
                                <i class="fas fa-lg fa-check-square"></i>
                                <?php echo __('Select all'); ?>
                            </span>
                        </div>
                        <div class="col-xs-12 col-md-2">
                            <span ng-click="undoSelection()" class="pointer">
                                <i class="fas fa-lg fa-square"></i>
                                <?php echo __('Undo selection'); ?>
                            </span>
                        </div>
                        <?php if ($this->Acl->hasPermission('delete', 'hosts')): ?>
                            <div class="col-xs-12 col-md-2 txt-color-red">
                                <span ng-click="confirmDelete(getObjectsForDelete())" class="pointer">
                                    <i class="fas fa-trash"></i>
                                    <?php echo __('Delete selected'); ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
