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
?>
<ol class="breadcrumb page-breadcrumb">
    <li class="breadcrumb-item">
        <a ui-sref="DashboardsIndex">
            <i class="fa fa-home"></i> <?php echo __('Home'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <a ui-sref="AgentconnectorsWizard">
            <i class="fa fa-user-secret"></i> <?php echo __('openITCOCKPIT Agent'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <i class="fa-solid fa-wand-magic-sparkles"></i> <?php echo __('Overview'); ?>
    </li>
</ol>

<massdelete
    help="<?= __('Only the configuration for the openITCOCKPIT Agent will be deleted. Not the host itself.'); ?>"></massdelete>

<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?php echo __('Agents overview'); ?>
                    <span class="fw-300"><i><?php echo __('Pull mode'); ?></i></span>
                </h2>

                <div class="panel-toolbar">
                    <ul class="nav nav-tabs border-bottom-0 nav-tabs-clean" role="tablist">
                        <?php if ($this->Acl->hasPermission('overview', 'agentconnector')): ?>
                            <li class="nav-item">
                                <a class="nav-link active" data-toggle="tab" ui-sref="AgentconnectorsPull" role="tab">
                                    <i class="fas fa-download"></i>&nbsp;
                                    <?php echo __('Pull'); ?>
                                </a>
                            </li>
                        <?php endif; ?>
                        <?php if ($this->Acl->hasPermission('overview', 'agentconnector')): ?>
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="tab" ui-sref="AgentconnectorsPush" role="tab">
                                    <i class="fas fa-upload"></i>&nbsp;
                                    <?php echo __('Push'); ?>
                                </a>
                            </li>
                        <?php endif; ?>
                        <?php if (\Cake\Core\Plugin::isLoaded('DistributeModule') && \Cake\Core\Plugin::isLoaded('ImportModule')): ?>
                            <?php if ($this->Acl->hasPermission('overview', 'agentconnector')): ?>
                                <li class="nav-item">
                                    <a class="nav-link" data-toggle="tab" ui-sref="AgentconnectorsPushSatellite"
                                       role="tab">
                                        <div class="icon-stack">
                                            <i class="fas fa-satellite opacity-100 "></i>
                                            <i class="fas fa-upload opacity-100 fa-xs text-primary cornered cornered-lr"></i>
                                        </div>
                                        &nbsp;
                                        <?php echo __('Push via Satellite (Import Module)'); ?>
                                    </a>
                                </li>
                            <?php endif; ?>
                        <?php endif; ?>
                    </ul>
                    <button class="btn btn-xs btn-default mr-1 shadow-0" ng-click="load()">
                        <i class="fas fa-sync"></i> <?php echo __('Refresh'); ?>
                    </button>
                    <?php if ($this->Acl->hasPermission('wizard', 'agentconnector')): ?>
                        <button class="btn btn-xs btn-success mr-1 shadow-0" ui-sref="AgentconnectorsWizard">
                            <i class="fas fa-plus"></i> <?php echo __('New'); ?>
                        </button>
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
                                                <span class="input-group-text"><i class="fa fa-desktop"></i></span>
                                            </div>
                                            <input type="text" class="form-control form-control-sm"
                                                   placeholder="<?php echo __('Filter by host name'); ?>"
                                                   ng-model="filter.Hosts.name"
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
                    <!-- Filter end -->
                    <div class="frame-wrap">
                        <table class="table table-striped m-0 table-bordered table-hover table-sm">
                            <thead>
                            <tr>
                                <th class="no-sort sorting_disabled width-15">
                                    <i class="fa fa-check-square"></i>
                                </th>
                                <th class="no-sort" ng-click="orderBy('Hosts.name')">
                                    <i class="fa" ng-class="getSortClass('Hosts.name')"></i>
                                    <?php echo __('Host name'); ?>
                                </th>
                                <th class="no-sort" ng-click="orderBy('Agentconfigs.port')">
                                    <i class="fa" ng-class="getSortClass('Agentconfigs.port')"></i>
                                    <?php echo __('Port'); ?>
                                </th>
                                <th class="no-sort">
                                    <?php echo __('Connection type'); ?>
                                </th>

                                <th class="no-sort text-center">
                                    <i class="fa fa-cog"></i>
                                </th>
                            </tr>
                            </thead>

                            <tbody>
                            <tr ng-repeat="agent in agents">
                                <td class="text-center" class="width-15">
                                    <?php if ($this->Acl->hasPermission('delete', 'agentconnector')): ?>
                                        <input type="checkbox"
                                               ng-model="massChange[agent.id]"
                                               ng-show="agent.allow_edit">
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($this->Acl->hasPermission('browser', 'hosts')): ?>
                                        <a ui-sref="HostsBrowser({id:agent.host.id})">
                                            {{ agent.host.name}}
                                        </a>
                                    <?php else: ?>
                                        {{ agent.host.name}}
                                    <?php endif; ?>
                                </td>
                                <td>
                                    {{ agent.port}}
                                </td>
                                <td>
                                    <span class="badge badge-success"
                                          ng-show="agent.use_https && !agent.insecure">
                                        <?php echo __('HTTPS'); ?>
                                    </span>
                                    <span class="badge badge-warning"
                                          ng-show="agent.use_https && agent.insecure">
                                        <?php echo __('HTTPS (insecure)'); ?>
                                    </span>
                                    <span class="badge badge-success"
                                          ng-show="agent.use_autossl && agent.autossl_successful">
                                        <?php echo __('Auto-TLS'); ?>
                                    </span>
                                    <span class="badge badge-danger"
                                          ng-show="agent.use_autossl && !agent.autossl_successful">
                                        <?php echo __('Auto-TLS certificate exchange error'); ?>
                                    </span>
                                    <span class="badge badge-danger"
                                          ng-show="!agent.use_https && !agent.use_autossl">
                                        <?php echo __('HTTP'); ?>
                                    </span>
                                </td>
                                <td class="width-50">
                                    <div class="btn-group btn-group-xs" role="group">
                                        <?php if ($this->Acl->hasPermission('config', 'agentconnector')): ?>
                                            <a ui-sref="AgentconnectorsConfig({hostId: agent.host.id})"
                                               ng-if="agent.allow_edit"
                                               class="btn btn-default btn-lower-padding">
                                                <i class="fa fa-cog"></i>
                                            </a>
                                            <a href="javascript:void(0);"
                                               ng-if="!agent.allow_edit"
                                               class="btn btn-default btn-lower-padding disabled">
                                                <i class="fa fa-cog"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="javascript:void(0);"
                                               class="btn btn-default btn-lower-padding disabled">
                                                <i class="fa fa-cog"></i></a>
                                        <?php endif; ?>
                                        <button type="button"
                                                class="btn btn-default dropdown-toggle btn-lower-padding"
                                                data-toggle="dropdown">
                                            <i class="caret"></i>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-right">
                                            <?php if ($this->Acl->hasPermission('config', 'agentconnector')): ?>
                                                <a ui-sref="AgentconnectorsConfig({hostId: agent.host.id})"
                                                   ng-if="agent.allow_edit"
                                                   class="dropdown-item">
                                                    <i class="fa fa-cog"></i>
                                                    <?php echo __('Edit'); ?>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($this->Acl->hasPermission('showOutput', 'agentconnector')): ?>
                                                <a ui-sref="AgentconnectorsShowOutput({mode: 'pull',id: agent.host.id})"
                                                   class="dropdown-item">
                                                    <i class="fab fa-js"></i>
                                                    <?php echo __('Query Agent'); ?>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($this->Acl->hasPermission('delete', 'agentconnector')): ?>
                                                <div class="dropdown-divider"></div>
                                                <a href="javascript:void(0);"
                                                   ng-if="agent.allow_edit"
                                                   class="txt-color-red dropdown-item"
                                                   ng-click="confirmDelete(getObjectForDelete(agent))">
                                                    <i class="fa fa-trash"></i> <?php echo __('Delete'); ?>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                        <div class="margin-top-10" ng-show="agents.length == 0">
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
                            <?php if ($this->Acl->hasPermission('delete', 'agentconnector')): ?>
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
