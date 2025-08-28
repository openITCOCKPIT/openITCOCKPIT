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
        <i class="fa fa-list"></i> <?php echo __('index'); ?>
    </li>
</ol>
<massdelete></massdelete>
<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?php echo __('Contacts'); ?>
                    <span class="fw-300"><i><?php echo __('overview'); ?></i></span>
                </h2>
                <div class="panel-toolbar">
                    <button class="btn btn-xs btn-default mr-1 shadow-0" ng-click="load()">
                        <i class="fas fa-sync"></i> <?php echo __('Refresh'); ?>
                    </button>
                    <?php if ($this->Acl->hasPermission('add', 'contacts')): ?>
                        <button class="btn btn-xs btn-success mr-1 shadow-0" ui-sref="ContactsAdd">
                            <i class="fas fa-plus"></i> <?php echo __('New'); ?>
                        </button>
                    <?php endif; ?>
                    <?php if ($this->Acl->hasPermission('addFromLdap', 'contacts')): ?>
                        <?php if ($isLdapAuth): ?>
                            <button class="btn btn-xs btn-warning mr-1 shadow-0" ui-sref="ContactsLdap">
                                <i class="fas fa-plus"></i> <?php echo __('Import from LDAP'); ?>
                            </button>
                        <?php endif; ?>
                    <?php endif; ?>
                    <button class="btn btn-xs btn-primary shadow-0" ng-click="triggerFilter()">
                        <i class="fas fa-filter"></i> <?php echo __('Filter'); ?>
                    </button>
                </div>
            </div>
            <div class="panel-container show">
                <div class="panel-content">

                    <!-- Start Filter -->
                    <div class="list-filter card margin-bottom-10" ng-show="showFilter">
                        <div class="card-header">
                            <i class="fa fa-filter"></i> <?php echo __('Filter'); ?>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-xs-12 col-md-6 margin-bottom-10">
                                    <div class="form-group">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fa fa-filter"></i></span>
                                            </div>
                                            <input type="text" class="form-control form-control-sm"
                                                   placeholder="<?php echo __('Filter by contact name'); ?>"
                                                   ng-model="filter.Contacts.name"
                                                   ng-model-options="{debounce: 500}">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xs-12 col-md-6 margin-bottom-10">
                                    <div class="form-group">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fa fa-envelope"></i></span>
                                            </div>
                                            <input type="text" class="form-control form-control-sm"
                                                   placeholder="<?php echo __('Filter by contact email'); ?>"
                                                   ng-model="filter.Contacts.email"
                                                   ng-model-options="{debounce: 500}">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xs-12 col-md-6 margin-bottom-10">
                                    <div class="form-group">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fa fa-phone"></i></span>
                                            </div>
                                            <input type="text" class="form-control form-control-sm"
                                                   placeholder="<?php echo __('Filter by contact phone'); ?>"
                                                   ng-model="filter.Contacts.phone"
                                                   ng-model-options="{debounce: 500}">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="float-right">
                                <button type="button" ng-click="resetFilter()"
                                        class="btn btn-xs btn-danger">
                                    <?php echo __('Reset Filter'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                    <!-- End Filter -->

                    <div class="frame-wrap">
                        <table class="table table-striped m-0 table-bordered table-hover table-sm">
                            <thead>
                            <tr>
                                <th class="no-sort width-5">
                                    <i class="fas fa-check-square"></i>
                                </th>
                                <th class="no-sort" ng-click="orderBy('Contacts.name')">
                                    <i class="fa" ng-class="getSortClass('Contacts.name')"></i>
                                    <?php echo __('Contact name'); ?>
                                </th>
                                <th class="no-sort" ng-click="orderBy('Contacts.description')">
                                    <i class="fa" ng-class="getSortClass('Contacts.description')"></i>
                                    <?php echo __('Description'); ?>
                                </th>
                                <th class="no-sort" ng-click="orderBy('Contacts.email')">
                                    <i class="fa" ng-class="getSortClass('Contacts.email')"></i>
                                    <?php echo __('Email'); ?>
                                </th>
                                <th class="no-sort" ng-click="orderBy('Contacts.phone')">
                                    <i class="fa" ng-class="getSortClass('Contacts.phone')"></i>
                                    <?php echo __('Phone'); ?>
                                </th>
                                <th class="no-sort" colspan="2">
                                    <?php echo __('Host notifications'); ?>
                                </th>
                                <th class="no-sort" colspan="2">
                                    <?php echo __('Service notifications'); ?>
                                </th>
                                <th class="no-sort text-center">
                                    <i class="fas fa-cog"></i>
                                </th>
                            </tr>
                            </thead>

                            <tbody>
                            <tr ng-repeat="contact in contacts">
                                <td class="width-5">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input position-static"
                                               ng-model="massChange[contact.Contact.id]"
                                               ng-show="contact.Contact.allow_edit">
                                    </div>
                                </td>
                                <td>{{contact.Contact.name}}</td>
                                <td>{{contact.Contact.description}}</td>
                                <td>{{contact.Contact.email}}</td>
                                <td>{{contact.Contact.phone}}</td>
                                <td>
                                    <span class="badge badge-danger"
                                          ng-hide="contact.Contact.host_notifications_enabled">
                                        <?php echo __('Disabled'); ?>
                                    </span>
                                    <span class="badge badge-success"
                                          ng-show="contact.Contact.host_notifications_enabled">
                                        <?php echo __('Enabled'); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-success margin-right-5"
                                          title="<?php echo __('Recovery'); ?>"
                                          ng-show="contact.Contact.notify_host_recovery">
                                        <?php echo __('R'); ?>
                                    </span>
                                    <span class="badge badge-danger margin-right-5"
                                          title="<?php echo __('Down'); ?>"
                                          ng-show="contact.Contact.notify_host_down">
                                        <?php echo __('D'); ?>
                                    </span>
                                    <span class="badge badge-secondary margin-right-5"
                                          title="<?php echo __('Unreachable'); ?>"
                                          ng-show="contact.Contact.notify_host_unreachable">
                                        <?php echo __('U'); ?>
                                    </span>
                                    <span class="badge badge-primary margin-right-5"
                                          title="<?php echo __('Flapping'); ?>"
                                          ng-show="contact.Contact.notify_host_flapping">
                                        <i class="fas fa-circle"></i>
                                        <i class="far fa-circle"></i>
                                    </span>
                                    <span class="badge badge-primary"
                                          title="<?php echo __('Downtime'); ?>"
                                          ng-show="contact.Contact.notify_host_downtime">
                                        <i class="fa fa-power-off"></i>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-danger"
                                          ng-hide="contact.Contact.service_notifications_enabled">
                                        <?php echo __('Disabled'); ?>
                                    </span>
                                    <span class="badge badge-success"
                                          ng-show="contact.Contact.service_notifications_enabled">
                                        <?php echo __('Enabled'); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-success margin-right-5"
                                          title="<?php echo __('Recovery'); ?>"
                                          ng-show="contact.Contact.notify_service_recovery">
                                        <?php echo __('R'); ?>
                                    </span>
                                    <span class="badge badge-warning margin-right-5"
                                          title="<?php echo __('Warning'); ?>"
                                          ng-show="contact.Contact.notify_service_warning">
                                        <?php echo __('W'); ?>
                                    </span>
                                    <span class="badge badge-danger margin-right-5"
                                          title="<?php echo __('Critical'); ?>"
                                          ng-show="contact.Contact.notify_service_critical">
                                        <?php echo __('C'); ?>
                                    </span>
                                    <span class="badge badge-secondary margin-right-5"
                                          title="<?php echo __('Unknown'); ?>"
                                          ng-show="contact.Contact.notify_service_unknown">
                                        <?php echo __('U'); ?>
                                    </span>
                                    <span class="badge badge-primary margin-right-5"
                                          title="<?php echo __('Flapping'); ?>"
                                          ng-show="contact.Contact.notify_service_flapping">
                                        <i class="fas fa-circle"></i>
                                        <i class="far fa-circle"></i>
                                    </span>
                                    <span class="badge badge-primary"
                                          title="<?php echo __('Downtime'); ?>"
                                          ng-show="contact.Contact.notify_service_downtime">
                                        <i class="fa fa-power-off"></i>
                                    </span>
                                </td>

                                <td class="width-50">
                                    <div class="btn-group btn-group-xs" role="group">
                                        <?php if ($this->Acl->hasPermission('edit', 'contacts')): ?>
                                            <a ui-sref="ContactsEdit({id: contact.Contact.id})"
                                               ng-if="contact.Contact.allow_edit"
                                               class="btn btn-default btn-lower-padding">
                                                <i class="fa fa-cog"></i>
                                            </a>
                                            <a href="javascript:void(0);"
                                               ng-if="!contact.Contact.allow_edit"
                                               class="btn btn-default disabled btn-lower-padding">
                                                <i class="fa fa-cog"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="javascript:void(0);"
                                               class="btn btn-default disabled btn-lower-padding">
                                                <i class="fa fa-cog"></i></a>
                                        <?php endif; ?>
                                        <button type="button"
                                                class="btn btn-default dropdown-toggle btn-lower-padding"
                                                data-toggle="dropdown">
                                            <i class="caret"></i>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-right">
                                            <?php if ($this->Acl->hasPermission('edit', 'contacts')): ?>
                                                <a ui-sref="ContactsEdit({id:contact.Contact.id})"
                                                   ng-if="contact.Contact.allow_edit"
                                                   class="dropdown-item">
                                                    <i class="fa fa-cog"></i>
                                                    <?php echo __('Edit'); ?>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($this->Acl->hasPermission('index', 'changelogs')): ?>
                                                <a ui-sref="ChangelogsEntity({objectTypeId: 'contact', objectId: contact.Contact.id})"
                                                   class="dropdown-item">
                                                    <i class="fa-solid fa-timeline fa-rotate-90"></i>
                                                    <?php echo __('Changelog'); ?>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($this->Acl->hasPermission('usedBy', 'contacts')): ?>
                                                <a ui-sref="ContactsUsedBy({id:contact.Contact.id})"
                                                   class="dropdown-item">
                                                    <i class="fa fa-reply-all fa-flip-horizontal"></i>
                                                    <?php echo __('Used by'); ?>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($this->Acl->hasPermission('copy', 'contacts')): ?>
                                                <div class="dropdown-divider"></div>
                                                <a ui-sref="ContactsCopy({ids: contact.Contact.id})"
                                                   class="dropdown-item">
                                                    <i class="fas fa-files-o"></i>
                                                    <?php echo __('Copy'); ?>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($this->Acl->hasPermission('delete', 'contacts')): ?>
                                                <div class="dropdown-divider"></div>
                                                <a href="javascript:void(0);"
                                                   ng-if="contact.Contact.allow_edit"
                                                   class="txt-color-red dropdown-item"
                                                   ng-click="confirmDelete(getObjectForDelete(contact))">
                                                    <i class="fa fa-trash"></i> <?php echo __('Delete'); ?>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                        <div class="margin-top-10" ng-show="contacts.length == 0">
                            <div class="text-center text-danger italic">
                                <?php echo __('No entries match the selection'); ?>
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
                            <?php if ($this->Acl->hasPermission('copy', 'contacts')): ?>
                                <div class="col-xs-12 col-md-2">
                                    <a ui-sref="ContactsCopy({ids: linkForCopy()})" class="a-clean">
                                        <i class="fas fa-lg fa-files-o"></i>
                                        <?php echo __('Copy'); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                            <?php if ($this->Acl->hasPermission('delete', 'contacts')): ?>
                                <div class="col-xs-12 col-md-4 txt-color-red">
                                    <span ng-click="confirmDelete(getObjectsForDelete())" class="pointer">
                                        <i class="fas fa-trash"></i>
                                        <?php echo __('Delete selected'); ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <scroll scroll="scroll" click-action="changepage" ng-if="scroll"></scroll>
                        <paginator paging="paging" click-action="changepage" ng-if="paging"></paginator>
                        <?php echo $this->element('paginator_or_scroll'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
