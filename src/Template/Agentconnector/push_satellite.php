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
    help="<?= __('Warning! You also need to delete the corresponding record on the Satellite System!'); ?>"></massdelete>

<div class="row">
    <div class="col-lg-12">

        <div class="alert alert-primary border-faded">
            <div class="d-flex align-items-center">
                <div class="alert-icon">
                    <span class="icon-stack icon-stack-md">
                        <i class="base-7 icon-stack-3x color-info-600"></i>
                        <i class="fas fa-info icon-stack-1x text-white"></i>
                    </span>
                </div>
                <div class="flex-1 color-info-600">
                    <span class="h5 color-info-600">
                        <?= __('Information may be not complete'); ?>
                    </span>
                    <br>
                    <?= __('This list will only show openITCOCKPIT Monitoring Agents, which are running in push mode and are related to the Import Module.'); ?>
                    <?= __('In case the Import Module is not in use, this list is probably empty.'); ?>
                </div>

            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?php echo __('Agents overview'); ?>
                    <span class="fw-300"><i><?php echo __('Push mode via a Satellite system'); ?></i></span>
                </h2>

                <div class="panel-toolbar">
                    <ul class="nav nav-tabs border-bottom-0 nav-tabs-clean" role="tablist">
                        <?php if ($this->Acl->hasPermission('overview', 'agentconnector')): ?>
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="tab" ui-sref="AgentconnectorsPull" role="tab">
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
                                    <a class="nav-link active" data-toggle="tab" ui-sref="AgentconnectorsPushSatellite"
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
                            <div class="row">
                                <div class="col-xs-12 col-md-6">
                                    <fieldset>
                                        <h5><?php echo __('Host assignments'); ?></h5>
                                        <div class="form-group">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox"
                                                       id="FilterAssigned"
                                                       class="custom-control-input"
                                                       name="checkbox"
                                                       checked="checked"
                                                       ng-model="filter.host_assignment"
                                                       ng-model-options="{debounce: 500}">
                                                <label class="custom-control-label"
                                                       for="FilterAssigned"><?php echo __('Host assigned'); ?></label>
                                            </div>

                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox"
                                                       id="FilterNotAssigned"
                                                       class="custom-control-input"
                                                       name="checkbox"
                                                       checked="checked"
                                                       ng-model="filter.no_host_assignment"
                                                       ng-model-options="{debounce: 500}">
                                                <label class="custom-control-label"
                                                       for="FilterNotAssigned"><?php echo __('Agents witout host assignment'); ?></label>
                                            </div>
                                        </div>
                                    </fieldset>
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
                                    <?php echo __('Assigned host'); ?>
                                </th>
                                <th class="no-sort" ng-click="orderBy('SatellitePushAgents.uuid')">
                                    <i class="fa" ng-class="getSortClass('SatellitePushAgents.uuid')"></i>
                                    <?php echo __('Agent UUID'); ?>
                                </th>
                                <th class="no-sort" ng-click="orderBy('SatellitePushAgents.hostname')">
                                    <i class="fa" ng-class="getSortClass('SatellitePushAgents.hostname')"></i>
                                    <?php echo __('Agent Hostname'); ?>
                                </th>
                                <th class="no-sort" ng-click="orderBy('SatellitePushAgents.ipaddress')">
                                    <i class="fa" ng-class="getSortClass('SatellitePushAgents.ipaddress')"></i>
                                    <?php echo __('Agent IP address'); ?>
                                </th>
                                <th class="no-sort" ng-click="orderBy('SatellitePushAgents.hostname')">
                                    <i class="fa" ng-class="getSortClass('SatellitePushAgents.hostname')"></i>
                                    <?php echo __('Remote address'); ?>
                                </th>

                                <th class="no-sort" ng-click="orderBy('SatellitePushAgents.last_update')">
                                    <i class="fa" ng-class="getSortClass('SatellitePushAgents.last_update')"></i>
                                    <?php echo __('Last update'); ?>
                                </th>

                                <th class="no-sort" ng-click="orderBy('SatellitePushAgents.satellite_id')">
                                    <i class="fa" ng-class="getSortClass('SatellitePushAgents.satellite_id')"></i>
                                    <?php echo __('Satellite'); ?>
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
                                    <span ng-show="agent.Hosts.name">
                                        <?php if ($this->Acl->hasPermission('browser', 'hosts')): ?>
                                            <a ui-sref="HostsBrowser({id:agent.Hosts.id})">
                                                {{ agent.Hosts.name}}
                                            </a>
                                        <?php else: ?>
                                            {{ agent.Hosts.name}}
                                        <?php endif; ?>
                                    </span>
                                    <span ng-show="agent.Hosts.name === null" class="italic text-secondary">
                                        <?= __('No host assignment defined'); ?>
                                    </span>
                                </td>
                                <td>
                                    {{ agent.uuid}}
                                </td>
                                <td>
                                    {{ agent.hostname}}
                                </td>
                                <td>
                                    {{ agent.ipaddress}}
                                </td>
                                <td>
                                    {{agent.http_x_forwarded_for ? agent.http_x_forwarded_for : agent.remote_address}}
                                </td>
                                <td>
                                    {{ agent.last_update}}
                                </td>
                                <td>
                                    <?php if ($this->Acl->hasPermission('edit', 'satellites', 'DistributeModule')): ?>
                                        <a ui-sref="SatellitesEdit({id:agent.Satellites.id})">
                                            {{ agent.Satellites.name}}
                                        </a>
                                    <?php else: ?>
                                        {{ agent.Satellites.name}}
                                    <?php endif; ?>
                                </td>
                                <td class="width-50">
                                    <div class="btn-group btn-group-xs" role="group">
                                        <?php if ($this->Acl->hasPermission('config', 'agentconnector')): ?>
                                            <a ui-sref="AgentconnectorsConfig({hostId: agent.Hosts.id})"
                                               ng-if="agent.allow_edit && agent.Hosts.id"
                                               class="btn btn-default btn-lower-padding">
                                                <i ng-class="{'fa fa-cog': agent.Hosts.id, 'fas fa-link': !agent.Hosts.id}"></i>
                                            </a>
                                            <a href="javascript:void(0);"
                                               ng-if="!agent.allow_edit || !agent.Hosts.id"
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
                                            <?php if ($this->Acl->hasPermission('showOutput', 'agentconnector')): ?>
                                                <a ui-sref="AgentconnectorsShowOutput({mode: 'push_satellite',id: agent.id})"
                                                   class="dropdown-item">
                                                    <i class="fab fa-js"></i>
                                                    <?php echo __('Show received data'); ?>
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
