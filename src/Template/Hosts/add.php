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
        <a ui-sref="HostsIndex">
            <i class="fa fa-desktop"></i> <?php echo __('Hosts'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <i class="fa fa-plus"></i> <?php echo __('Add'); ?>
    </li>
</ol>

<div class="alert alert-warning alert-dismissible fade show ng-hide" role="alert" ng-show="showRootAlert">
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true"><i class="fas fa-times"></i></span>
    </button>
    <div class="d-flex align-items-center">
        <div class="alert-icon width-3">
            <div class='icon-stack  icon-stack-sm'>
                <i class="base base-9 icon-stack-3x opacity-100 color-warning-600"></i>
                <i class="fas fa-exclamation-circle icon-stack-1x opacity-100 color-white"></i>
            </div>
        </div>
        <div class="flex-1">
            <span class="h5 m-0 fw-700"><?php echo __('/root container selected!'); ?></span>
            <?php echo __('Choosing a tenant container is recommended for later permission purposes'); ?>
        </div>
    </div>
</div>


<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?php echo __('Create new host'); ?>
                </h2>
                <div class="panel-toolbar">
                    <?php if ($this->Acl->hasPermission('index', 'hosts')): ?>
                        <a back-button href="javascript:void(0);" fallback-state='HostsIndex'
                           class="btn btn-default btn-xs mr-1 shadow-0">
                            <i class="fas fa-long-arrow-alt-left"></i> <?php echo __('Back'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <form ng-submit="submit();" class="form-horizontal"
                          ng-init="successMessage=
                            {objectName : '<?php echo __('Host'); ?>' , message: '<?php echo __('created successfully'); ?>', allocate_message: '<?php echo __('+ %s Services created successfully'); ?>', allocate_warning: '<?php echo __('. %s service template groups has been removed due to insufficient permissions'); ?>'}">

                        <!-- BASIC CONFIGURATION START -->

                        <div class="card margin-bottom-10">
                            <div class="card-header">
                                <i class="fa-solid fa-wand-magic-sparkles"></i> <?php echo __('Basic configuration'); ?>
                            </div>
                            <div class="card-body">
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
                                    <div ng-show="post.Host.container_id === 1" class="help-block text-warning">
                                        <i class="fas fa-exclamation"></i>
                                        <?php echo __('Hosts in /root can\'t be moved to other containers later'); ?>
                                    </div>
                                    <div ng-repeat="error in errors.container_id">
                                        <div class="help-block text-danger">{{ error }}</div>
                                    </div>
                                </div>

                                <div class="form-group"
                                     ng-class="{'has-error': errors.hosts_to_containers_sharing}">
                                    <label class="control-label" for="HostSharingContainer">
                                        <?php echo __('Shared containers'); ?>
                                    </label>
                                    <select
                                        id="HostSharingContainer"
                                        data-placeholder="<?php echo __('Please choose'); ?>"
                                        class="form-control"
                                        chosen="sharingContainers"
                                        multiple
                                        ng-options="container.key as container.value for container in sharingContainers"
                                        ng-model="post.Host.hosts_to_containers_sharing._ids">
                                    </select>
                                    <div ng-repeat="error in errors.hosts_to_containers_sharing">
                                        <div class="help-block text-danger">{{ error }}</div>
                                    </div>
                                </div>

                                <div class="form-group required" ng-class="{'has-error': errors.hosttemplate_id}">
                                    <label class="control-label" for="HostTemplate">
                                        <?php if ($this->Acl->hasPermission('edit', 'hosttemplates')): ?>
                                            <a ui-sref="HosttemplatesEdit({id:post.Host.hosttemplate_id})"
                                               ng-if="post.Host.hosttemplate_id > 0">
                                                <?= __('Host template'); ?>
                                            </a>
                                            <span
                                                ng-if="post.Host.hosttemplate_id == 0"><?php echo __('Host template'); ?></span>
                                        <?php else: ?>
                                            <?= __('Host template'); ?>
                                        <?php endif; ?>
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


                                    <div class="form-group"
                                         ng-class="{'has-error': errors.hostgroups}">
                                        <label class="control-label">
                                            <?php echo __('Host groups'); ?>
                                        </label>
                                        <div class="input-group" style="width: 100%;">
                                            <select
                                                id="HostgroupsSelect"
                                                data-placeholder="<?php echo __('Please choose'); ?>"
                                                class="form-control"
                                                chosen="hostgroups"
                                                multiple
                                                ng-options="hostgroup.key as hostgroup.value for hostgroup in hostgroups"
                                                ng-model="post.Host.hostgroups._ids">
                                            </select>
                                            <template-diff ng-show="post.Host.hosttemplate_id"
                                                           value="post.Host.hostgroups._ids"
                                                           template-value="hosttemplate.Hosttemplate.hostgroups._ids"></template-diff>
                                        </div>
                                        <div ng-repeat="error in errors.hostgroups">
                                            <div class="help-block text-danger">{{ error }}</div>
                                        </div>
                                    </div>


                                    <div class="form-group" ng-class="{'has-error': errors.parenthosts}">
                                        <label class="control-label" for="ParentHostsSelect">
                                            <?php echo __('Parent hosts'); ?>
                                        </label>
                                        <select
                                            id="ParentHostsSelect"
                                            data-placeholder="<?php echo __('Please choose'); ?>"
                                            class="form-control"
                                            chosen="parenthosts"
                                            callback="loadParentHosts"
                                            multiple
                                            ng-options="parenthost.key as parenthost.value for parenthost in parenthosts"
                                            ng-model="post.Host.parenthosts._ids">
                                        </select>
                                        <div ng-repeat="error in errors.parenthosts">
                                            <div class="help-block text-danger">{{ error }}</div>
                                        </div>
                                    </div>

                                    <div class="form-group" ng-class="{'has-error': errors.tags}">
                                        <label class="control-label">
                                            <?php echo __('Tags'); ?>
                                        </label>
                                        <div class="input-group">
                                            <div class="col-lg padding-left-0 padding-right-23">
                                                <input class="form-control tagsinput"
                                                       data-role="tagsinput"
                                                       type="text"
                                                       id="HostTagsInput"
                                                       ng-model="post.Host.tags">

                                            </div>
                                            <div style="margin-left: -23px;">
                                                <template-diff ng-show="post.Host.hosttemplate_id"
                                                               value="post.Host.tags"
                                                               template-value="hosttemplate.Hosttemplate.tags"
                                                               callback="restoreTemplateTags"></template-diff>
                                            </div>
                                        </div>
                                        <div ng-repeat="error in errors.tags">
                                            <div class="help-block text-danger">{{ error }}</div>
                                        </div>
                                        <div class="help-block">
                                            <?php echo __('Press return to separate tags'); ?>
                                        </div>
                                    </div>

                                    <div class="form-group" ng-class="{'has-error': errors.priority}">
                                        <label class="control-label">
                                            <?php echo __('Priority'); ?>
                                        </label>
                                        <div class="col-xs-12 col-lg-2">
                                            <priority-directive priority="post.Host.priority"
                                                                callback="setPriority"></priority-directive>
                                            <template-diff-button ng-show="post.Host.hosttemplate_id"
                                                                  value="post.Host.priority"
                                                                  template-value="hosttemplate.Hosttemplate.priority">
                                            </template-diff-button>
                                        </div>
                                    </div>

                                    <?php if (\Cake\Core\Plugin::isLoaded('DistributeModule')): ?>
                                        <div class="form-group" ng-class="{'has-error': errors.satellite_id}">
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
                        <!-- BASIC CONFIGURATION END -->

                        <!-- CHECK CONFIGURATION START -->
                        <div class="card margin-bottom-10" ng-show="post.Host.hosttemplate_id">
                            <div class="card-header">
                                <i class="fa fa-terminal"></i> <?php echo __('Check configuration'); ?>
                            </div>
                            <div class="card-body">
                                <div class="form-group"
                                     ng-class="{'has-error': errors.check_period_id}">
                                    <label class="control-label">
                                        <?php if ($this->Acl->hasPermission('edit', 'timeperiods')): ?>
                                            <a ui-sref="TimeperiodsEdit({id:post.Host.check_period_id})">
                                                <?= __('Check period'); ?>
                                            </a>
                                        <?php else: ?>
                                            <?= __('Check period'); ?>
                                        <?php endif; ?>
                                    </label>
                                    <div class="input-group" style="width: 100%;">
                                        <select
                                            id="CheckPeriodSelect"
                                            data-placeholder="<?php echo __('Please choose'); ?>"
                                            class="form-control"
                                            chosen="checkperiods"
                                            ng-options="checkperiod.key as checkperiod.value for checkperiod in checkperiods"
                                            ng-model="post.Host.check_period_id">
                                        </select>
                                        <template-diff ng-show="post.Host.hosttemplate_id"
                                                       value="post.Host.check_period_id"
                                                       template-value="hosttemplate.Hosttemplate.check_period_id"></template-diff>
                                    </div>
                                    <div class="help-block" ng-hide="post.Host.check_period_id">
                                        <?php echo __('Due to active checking is disabled, this command will only be used as freshness check command.'); ?>
                                    </div>
                                    <div ng-repeat="error in errors.check_period_id">
                                        <div class="help-block text-danger">{{ error }}</div>
                                    </div>
                                </div>

                                <div class="form-group" ng-class="{'has-error': errors.active_checks_enabled}">
                                    <div class="custom-control custom-checkbox  margin-bottom-10"
                                         ng-class="{'has-error': errors.active_checks_enabled}">

                                        <input type="checkbox"
                                               class="custom-control-input"
                                               id="activeChecksEnabled"
                                               ng-true-value="1"
                                               ng-false-value="0"
                                               ng-model="post.Host.active_checks_enabled">
                                        <label class="custom-control-label" for="activeChecksEnabled">
                                            <?php echo __('Enable active checks'); ?>
                                        </label>
                                        <template-diff-button ng-show="post.Host.hosttemplate_id"
                                                              value="post.Host.active_checks_enabled"
                                                              template-value="hosttemplate.Hosttemplate.active_checks_enabled">
                                        </template-diff-button>
                                    </div>

                                    <div class="col col-xs-12 col-md-offset-2 help-block">
                                        <?php echo __('If disabled the check command won\'t be executed. This is useful if an external program sends state data to openITCOCKPIT.'); ?>
                                    </div>
                                </div>

                                <div class="form-group" ng-class="{'has-error': errors.freshness_checks_enabled}"
                                     ng-show="post.Host.active_checks_enabled == 0">
                                    <div class="custom-control custom-checkbox  margin-bottom-10"
                                         ng-class="{'has-error': errors.freshness_checks_enabled}">
                                        <input type="checkbox"
                                               class="custom-control-input"
                                               ng-true-value="1"
                                               ng-false-value="0"
                                               id="freshness_checks_enabled"
                                               ng-model="post.Host.freshness_checks_enabled">
                                        <label class="custom-control-label" for="freshness_checks_enabled">
                                            <?php echo __('Enable freshness check'); ?>
                                        </label>
                                        <template-diff-button ng-show="post.Host.hosttemplate_id"
                                                              value="post.Host.freshness_checks_enabled"
                                                              template-value="hosttemplate.Hosttemplate.freshness_checks_enabled">
                                        </template-diff-button>
                                    </div>
                                    <div class="help-block" ng-hide="post.Host.active_checks_enabled">
                                        <?php echo __('Due to active checking is disabled, this command will only be used as freshness check command.'); ?>
                                    </div>
                                </div>

                                <div class="form-group required" ng-class="{'has-error': errors.freshness_threshold}"
                                     ng-show="post.Host.active_checks_enabled == 0 && post.Host.freshness_checks_enabled == 1">
                                    <label class="col-xs-12 col-lg-2 control-label">
                                        <?php echo __('Freshness threshold'); ?>
                                    </label>
                                    <interval-input-directive
                                        interval="post.Host.freshness_threshold"></interval-input-directive>
                                    <div class="col-xs-12 col-lg-offset-2">
                                        <div ng-repeat="error in errors.freshness_threshold">
                                            <div class="help-block text-danger">{{ error }}</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group"
                                     ng-class="{'has-error': errors.command_id}">
                                    <label class="control-label">
                                        <?php if ($this->Acl->hasPermission('edit', 'commands')): ?>
                                            <a ui-sref="CommandsEdit({id:post.Host.command_id})">
                                                <?= __('Check command'); ?>
                                            </a>
                                        <?php else: ?>
                                            <?= __('Check command'); ?>
                                        <?php endif; ?>
                                    </label>
                                    <div class="input-group" style="width: 100%;">
                                        <select
                                            id="HostCheckCommandSelect"
                                            data-placeholder="<?php echo __('Please choose'); ?>"
                                            class="form-control"
                                            chosen="commands"
                                            ng-options="command.key as command.value for command in commands"
                                            ng-model="post.Host.command_id">
                                        </select>
                                        <template-diff ng-show="post.Host.hosttemplate_id"
                                                       value="post.Host.command_id"
                                                       template-value="hosttemplate.Hosttemplate.command_id"></template-diff>
                                    </div>
                                    <div class="help-block" ng-hide="post.Host.active_checks_enabled">
                                        <?php echo __('Due to active checking is disabled, this command will only be used as freshness check command.'); ?>
                                    </div>
                                    <div ng-repeat="error in errors.command_id">
                                        <div class="help-block text-danger">{{ error }}</div>
                                    </div>
                                </div>


                                <div class="form-group"
                                     ng-class="{'has-error': errors.hostcommandargumentvalues}"
                                     ng-repeat="hostcommandargumentvalue in post.Host.hostcommandargumentvalues">
                                    <label class="col-xs-12 col-lg-offset-2 col-lg-10 control-label text-purple">
                                        {{hostcommandargumentvalue.commandargument.human_name}}
                                    </label>
                                    <div class="col-xs-12 col-lg-8">
                                        <div class="input-group">
                                            <input
                                                class="form-control"
                                                type="text"
                                                ng-model="hostcommandargumentvalue.value">
                                            <template-diff
                                                value="hostcommandargumentvalue.value"
                                                template-value="hosttemplate.Hosttemplate.hosttemplatecommandargumentvalues[$index].value"></template-diff>
                                        </div>
                                        <div ng-repeat="error in errors.hostcommandargumentvalues">
                                            <div class="help-block text-danger">{{ error }}</div>
                                        </div>
                                        <div class="help-block">
                                            {{hostcommandargumentvalue.commandargument.name}}
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group"
                                     ng-show="post.Host.command_id > 0 && post.Host.hostcommandargumentvalues.length == 0">
                                    <div class="col-xs-12 col-lg-offset-2 text-info">
                                        <i class="fa fa-info-circle"></i>
                                        <?php echo __('This command does not have any parameters.'); ?>
                                    </div>
                                </div>

                                <div class="form-group required" ng-class="{'has-error': errors.check_interval}">
                                    <label class="col-xs-12 col-lg-2 control-label">
                                        <?php echo __('Check interval'); ?>
                                    </label>
                                    <interval-input-with-differ-directive
                                        template-id="post.Host.hosttemplate_id"
                                        interval="post.Host.check_interval"
                                        template-value="hosttemplate.Hosttemplate.check_interval"></interval-input-with-differ-directive>
                                    <div class="col-xs-12 col-lg-offset-2">
                                        <div ng-repeat="error in errors.check_interval">
                                            <div class="help-block text-danger">{{ error }}</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group required" ng-class="{'has-error': errors.retry_interval}">
                                    <label class="col-xs-12 col-lg-2 control-label">
                                        <?php echo __('Retry interval'); ?>
                                    </label>
                                    <interval-input-with-differ-directive
                                        template-id="post.Host.hosttemplate_id"
                                        interval="post.Host.retry_interval"
                                        template-value="hosttemplate.Hosttemplate.retry_interval"></interval-input-with-differ-directive>

                                    <div class="col-xs-12 col-lg-offset-2">
                                        <div ng-repeat="error in errors.retry_interval">
                                            <div class="help-block text-danger">{{ error }}</div>
                                        </div>
                                    </div>
                                </div>


                                <div class="form-group"
                                     ng-class="{'has-error': errors.max_check_attempts}">
                                    <label class="col-xs-12 col-lg-2 control-label">
                                        <?php echo __('Max. number of check attempts'); ?>
                                    </label>
                                    <div class="row">
                                        <div class="col-xs-12 col-lg-6">
                                            <div class="btn-group flex-wrap">
                                                <?php for ($i = 1; $i <= 10; $i++): ?>
                                                    <button
                                                        type="button"
                                                        class="btn btn-default"
                                                        ng-click="post.Host.max_check_attempts = <?php echo h($i) ?>"
                                                        ng-class="{'active': post.Host.max_check_attempts == <?php echo h($i); ?>}">
                                                        <?php echo h($i); ?>
                                                    </button>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                        <div class="col-xs-12 col-lg-6">
                                            <div class="input-group" style="width: 100%;">
                                                <input
                                                    class="form-control"
                                                    type="number"
                                                    min="0"
                                                    ng-model="post.Host.max_check_attempts">
                                                <template-diff ng-show="post.Host.hosttemplate_id"
                                                               value="post.Host.max_check_attempts"
                                                               template-value="hosttemplate.Hosttemplate.max_check_attempts"></template-diff>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-lg-offset-2 col-lg-12">
                                        <div class="help-block">
                                            <?php echo __('Number of failed attempts before the host will switch into hard state.'); ?>
                                        </div>
                                        <div class="help-block">
                                            <?php echo __('Worst case time delay until notification command gets executed after state hits a non ok state: '); ?>
                                            <human-time-directive
                                                seconds="(post.Host.check_interval + (post.Host.max_check_attempts -1) * post.Host.retry_interval)"></human-time-directive>
                                        </div>
                                        <div ng-repeat="error in errors.max_check_attempts">
                                            <div class="help-block text-danger">{{ error }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- CHECK CONFIGURATION END -->


                        <!-- NOTIFICATION CONFIGURATION START -->
                        <div class="card margin-bottom-10" ng-show="post.Host.hosttemplate_id">
                            <div class="card-header">
                                <i class="fa fa-envelope-open"></i> <?php echo __('Notification configuration'); ?>
                            </div>
                            <div class="card-body">
                                <div class="custom-control custom-checkbox"
                                     ng-class="{'has-error': errors.notifications_enabled}">
                                    <input type="checkbox"
                                           id="notificationsEnabled"
                                           class="custom-control-input"
                                           name="checkbox"
                                           checked="checked"
                                           ng-true-value="1"
                                           ng-false-value="0"
                                           ng-model="post.Host.notifications_enabled">
                                    <label class="custom-control-label"
                                           for="notificationsEnabled"><?php echo __('Enable notifications'); ?></label>
                                    <template-diff-button ng-show="post.Host.hosttemplate_id"
                                                          value="post.Host.notifications_enabled"
                                                          template-value="hosttemplate.Hosttemplate.notifications_enabled">
                                    </template-diff-button>
                                    <div class="help-block">
                                        <?php echo __('This option is used to determine whether or not notifications are enabled for this host.'); ?>
                                    </div>
                                </div>
                                <div class="form-group required"
                                     ng-class="{'has-error': errors.notify_period_id}">
                                    <label class="control-label">
                                        <?php if ($this->Acl->hasPermission('edit', 'timeperiods')): ?>
                                            <a ui-sref="TimeperiodsEdit({id:post.Host.notify_period_id})">
                                                <?= __('Notification period'); ?>
                                            </a>
                                        <?php else: ?>
                                            <?= __('Notification period'); ?>
                                        <?php endif; ?>
                                    </label>
                                    <div class="input-group" style="width: 100%;">
                                        <select
                                            id="HostCheckCommandSelect"
                                            data-placeholder="<?php echo __('Please choose'); ?>"
                                            class="form-control"
                                            chosen="timeperiods"
                                            ng-options="timeperiod.key as timeperiod.value for timeperiod in timeperiods"
                                            ng-model="post.Host.notify_period_id">
                                        </select>
                                        <template-diff ng-show="post.Host.hosttemplate_id"
                                                       value="post.Host.notify_period_id"
                                                       template-value="hosttemplate.Hosttemplate.notify_period_id"></template-diff>
                                    </div>
                                    <div ng-repeat="error in errors.notify_period_id">
                                        <div class="help-block text-danger">{{ error }}</div>
                                    </div>
                                </div>

                                <div class="form-group required"
                                     ng-class="{'has-error': errors.notification_interval}">
                                    <label class="col-xs-12 col-lg-2 control-label">
                                        <?php echo __('Notification interval'); ?>
                                    </label>
                                    <interval-input-with-differ-directive
                                        template-id="post.Host.hosttemplate_id"
                                        interval="post.Host.notification_interval"
                                        template-value="hosttemplate.Hosttemplate.notification_interval"></interval-input-with-differ-directive>
                                    <div class="col-xs-12 col-lg-offset-2">
                                        <div ng-repeat="error in errors.notification_interval">
                                            <div class="help-block text-danger">{{ error }}</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group"
                                     ng-class="{'has-error': errors.contacts}">
                                    <label class="control-label">
                                        <?php echo __('Contacts'); ?>
                                    </label>
                                    <div class="input-group" style="width: 100%;">
                                        <select
                                            id="ContactsPeriodSelect"
                                            data-placeholder="<?php echo __('Please choose'); ?>"
                                            class="form-control"
                                            chosen="contacts"
                                            multiple
                                            ng-options="contact.key as contact.value for contact in contacts"
                                            ng-model="post.Host.contacts._ids">
                                        </select>
                                        <template-diff ng-show="post.Host.hosttemplate_id"
                                                       value="post.Host.contacts._ids"
                                                       template-value="hosttemplate.Hosttemplate.contacts._ids"></template-diff>
                                    </div>
                                    <div ng-repeat="error in errors.contacts">
                                        <div class="help-block text-danger">{{ error }}</div>
                                    </div>
                                </div>

                                <div class="form-group"
                                     ng-class="{'has-error': errors.contactgroups}">
                                    <label class="control-label">
                                        <?php echo __('Contact groups'); ?>
                                    </label>
                                    <div class="input-group" style="width: 100%;">
                                        <select
                                            id="ContactgroupsSelect"
                                            data-placeholder="<?php echo __('Please choose'); ?>"
                                            class="form-control"
                                            chosen="contactgroups"
                                            multiple
                                            ng-options="contactgroup.key as contactgroup.value for contactgroup in contactgroups"
                                            ng-model="post.Host.contactgroups._ids">
                                        </select>
                                        <template-diff ng-show="post.Host.hosttemplate_id"
                                                       value="post.Host.contactgroups._ids"
                                                       template-value="hosttemplate.Hosttemplate.contactgroups._ids"></template-diff>
                                    </div>
                                    <div ng-repeat="error in errors.contactgroups">
                                        <div class="help-block text-danger">{{ error }}</div>
                                    </div>
                                </div>


                                <?php
                                $hostOptions = [
                                    [
                                        'field' => 'notify_on_recovery',
                                        'class' => 'success',
                                        'text'  => __('Recovery')
                                    ],
                                    [
                                        'field' => 'notify_on_down',
                                        'class' => 'danger',
                                        'text'  => __('Down')
                                    ],
                                    [
                                        'field' => 'notify_on_unreachable',
                                        'class' => 'secondary',
                                        'text'  => __('Unreachable')
                                    ],
                                    [
                                        'field' => 'notify_on_flapping',
                                        'class' => 'primary',
                                        'text'  => __('Flapping')
                                    ],
                                    [
                                        'field' => 'notify_on_downtime',
                                        'class' => 'primary',
                                        'text'  => __('Downtime')
                                    ],
                                ];
                                ?>
                                <fieldset>
                                    <legend class="fs-sm"
                                            ng-class="{'has-error-no-form': errors.notify_on_recovery}">
                                        <div class="required">
                                            <label class="fs-sm">
                                                <?php echo __('Host notification options'); ?>
                                            </label>

                                            <div ng-repeat="error in errors.notify_on_recovery">
                                                <div class="text-danger">{{ error }}</div>
                                            </div>
                                        </div>
                                    </legend>
                                    <div class="row">
                                        <?php foreach ($hostOptions as $hostOption): ?>
                                            <div
                                                class="custom-control custom-checkbox margin-bottom-10 custom-control-right"
                                                ng-class="{'has-error': errors.<?php echo $hostOption['field']; ?>}">
                                                <input type="checkbox"
                                                       class="custom-control-input"
                                                       ng-true-value="1"
                                                       ng-false-value="0"
                                                       id="<?php echo $hostOption['field']; ?>"
                                                       ng-model="post.Host.<?php echo $hostOption['field']; ?>">
                                                <label for="<?php echo $hostOption['field']; ?>"
                                                       class="col col-md-6 custom-control-label custom-control-label-<?php echo $hostOption['class']; ?> padding-top-0 margin-right-10 ">
                                                        <span
                                                            class="badge badge-<?php echo $hostOption['class']; ?> notify-label-small">
                                                            <?php echo $hostOption['text']; ?>
                                                        </span>
                                                </label>
                                                <span class="margin-left-15">
                                                    <template-diff-button ng-show="post.Host.hosttemplate_id"
                                                                          value="post.Host.<?php echo $hostOption['field']; ?>"
                                                                          template-value="hosttemplate.Hosttemplate.<?php echo $hostOption['field']; ?>">
                                                </template-diff-button>
                                                </span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </fieldset>
                            </div>
                        </div>
                        <!-- NOTIFICATION CONFIGURATION END -->

                        <!-- MISC CONFIGURATION START -->
                        <div class="card margin-bottom-10" ng-show="post.Host.hosttemplate_id">
                            <div class="card-header">
                                <i class="fa fa-wrench"></i> <?php echo __('Misc. configuration'); ?>
                            </div>
                            <div class="card-body">

                                <div class="form-group" ng-class="{'has-error': errors.host_url}">
                                    <label class="control-label">
                                        <?php echo __('Host URL'); ?>
                                    </label>
                                    <div class="input-group">
                                        <input
                                            class="form-control"
                                            type="text"
                                            placeholder="https://issues.example.org?host=$HOSTNAME$"
                                            ng-model="post.Host.host_url">

                                        <template-diff ng-show="post.Host.hosttemplate_id"
                                                       value="post.Host.host_url"
                                                       template-value="hosttemplate.Hosttemplate.host_url"></template-diff>
                                    </div>
                                    <div class="help-block">
                                        <?php echo __('The macros $HOSTID$, $HOSTNAME$, $HOSTDISPLAYNAME$ and $HOSTADDRESS$ will be replaced'); ?>
                                    </div>
                                    <div ng-repeat="error in errors.host_url">
                                        <div class="help-block text-danger">{{ error }}</div>
                                    </div>
                                </div>

                                <div class="form-group" ng-class="{'has-error': errors.notes}">
                                    <label class="control-label">
                                        <?php echo __('Notes'); ?>
                                    </label>
                                    <div class="input-group">
                                        <input
                                            class="form-control"
                                            type="text"
                                            ng-model="post.Host.notes">
                                        <template-diff ng-show="post.Host.hosttemplate_id"
                                                       value="post.Host.notes"
                                                       template-value="hosttemplate.Hosttemplate.notes"></template-diff>
                                    </div>
                                    <div ng-repeat="error in errors.notes">
                                        <div class="help-block text-danger">{{ error }}</div>
                                    </div>
                                </div>


                                <?php
                                $hostFlapOptions = [
                                    [
                                        'field' => 'flap_detection_on_up',
                                        'class' => 'success',
                                        'text'  => __('Recovery')
                                    ],
                                    [
                                        'field' => 'flap_detection_on_down',
                                        'class' => 'danger',
                                        'text'  => __('Down')
                                    ],
                                    [
                                        'field' => 'flap_detection_on_unreachable',
                                        'class' => 'secondary',
                                        'text'  => __('Unreachable')
                                    ]
                                ];
                                ?>
                                <div class="form-group" ng-class="{'has-error': errors.flap_detection_enabled}">
                                    <div class="custom-control custom-checkbox margin-bottom-10 "
                                         ng-class="{'has-error': errors.flap_detection_enabled}">
                                        <input type="checkbox"
                                               class="custom-control-input"
                                               id="flapDetectionEnabled"
                                               ng-true-value="1"
                                               ng-false-value="0"
                                               ng-model="post.Host.flap_detection_enabled">
                                        <label class="custom-control-label" for="flapDetectionEnabled">
                                            <?php echo __('Flap detection enabled'); ?>
                                        </label>
                                        <template-diff-button ng-show="post.Host.hosttemplate_id"
                                                              value="post.Host.flap_detection_enabled"
                                                              template-value="hosttemplate.Hosttemplate.flap_detection_enabled">
                                        </template-diff-button>
                                    </div>
                                </div>

                                <fieldset ng-show="post.Host.flap_detection_enabled">
                                    <legend class="fs-sm"
                                            ng-class="{'has-error-no-form': errors.flap_detection_on_up}">
                                        <div ng-class="{'required':post.Host.flap_detection_enabled}">
                                            <label class="fs-sm">
                                                <?php echo __('Flap detection options'); ?>
                                            </label>

                                            <div ng-repeat="error in errors.flap_detection_on_up">
                                                <div class="text-danger">{{ error }}</div>
                                            </div>
                                        </div>
                                    </legend>
                                    <div class="row">
                                        <?php foreach ($hostFlapOptions as $hostFlapOption): ?>
                                            <div
                                                class="custom-control custom-checkbox margin-bottom-10 custom-control-right"
                                                ng-class="{'has-error': errors.<?php echo $hostFlapOption['field']; ?>}">
                                                <input type="checkbox" name="checkbox"
                                                       class="custom-control-input"
                                                       ng-true-value="1"
                                                       ng-false-value="0"
                                                       ng-disabled="!post.Host.flap_detection_enabled"
                                                       id="<?php echo $hostFlapOption['field']; ?>"
                                                       ng-model="post.Host.<?php echo $hostFlapOption['field']; ?>">
                                                <label for="<?php echo $hostFlapOption['field']; ?>"
                                                       class="col col-md-6 custom-control-label custom-control-label-<?php echo $hostFlapOption['class']; ?> padding-top-0 margin-right-10">
                                                        <span
                                                            class="badge badge-<?php echo $hostFlapOption['class']; ?> notify-label-small">
                                                            <?php echo $hostFlapOption['text']; ?>
                                                        </span>
                                                </label>
                                                <span class="margin-left-15">
                                                    <template-diff-button ng-show="post.Host.hosttemplate_id"
                                                                          value="post.Host.<?php echo $hostFlapOption['field']; ?>"
                                                                          template-value="hosttemplate.Hosttemplate.<?php echo $hostFlapOption['field']; ?>">
                                                    </template-diff-button>
                                                </span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </fieldset>

                            </div>
                        </div>
                        <!-- MISC CONFIGURATION END -->

                        <!-- HOST MACRO CONFIGURATION START -->
                        <div class="card margin-bottom-10" ng-show="post.Host.hosttemplate_id">
                            <div class="card-header">
                                <i class="fa fa-usd"></i> <?php echo __('Host macro configuration'); ?>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div ng-repeat="error in errors.customvariables_unique">
                                        <div class=" col-xs-12 text-danger">{{ error }}</div>
                                    </div>
                                </div>

                                <div class="row" ng-repeat="customvariable in post.Host.customvariables">
                                    <macros-directive macro="customvariable"
                                                      macro-name="'<?php echo __('HOST'); ?>'"
                                                      index="$index"
                                                      callback="deleteMacroCallback"
                                                      errors="getMacroErrors($index)"
                                                      class="col-lg-12"
                                    ></macros-directive>
                                </div>

                                <div class="row">
                                    <div class="col-xs-12 col-lg-12 padding-top-10 text-info"
                                         ng-show="post.Host.customvariables.length > 0">
                                        <i class="fa fa-info-circle"></i>
                                        <?php echo __('Macros in green color are inherited from the host template.'); ?>
                                    </div>
                                    <div class="col-lg-12 col-md-offset-2 padding-top-10 text-right">
                                        <button type="button" class="btn btn-success btn-sm"
                                                ng-click="addMacro()">
                                            <i class="fa fa-plus"></i>
                                            <?php echo __('Add new macro'); ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- HOST MACRO CONFIGURATION END -->

                        <?php if (\Cake\Core\Plugin::isLoaded('PrometheusModule')): ?>
                            <!-- PROMETHEUS CONFIGURATION START -->
                            <div class="card margin-bottom-10" ng-show="post.Host.hosttemplate_id">
                                <div class="card-header">
                                    <i class="fas fa-broadcast-tower"></i> <?php echo __('Prometheus Exporters'); ?>
                                </div>

                                <div class="card-body">
                                    <div class="form-group" ng-class="{'has-error': errors.prometheus_exporters}">
                                        <label class="control-label" for="ExportersSelect">
                                            <?php echo __('Exporters'); ?>
                                        </label>

                                        <div class="input-group" style="width: 100%;">
                                            <select
                                                id="ExportersSelect"
                                                data-placeholder="<?php echo __('Please choose'); ?>"
                                                class="form-control"
                                                chosen="exporters"
                                                multiple
                                                ng-options="exporter.key as exporter.value for exporter in exporters"
                                                ng-model="post.Host.prometheus_exporters._ids">
                                            </select>
                                            <template-diff ng-show="post.Host.hosttemplate_id"
                                                           value="post.Host.prometheus_exporters._ids"
                                                           template-value="hosttemplate.Hosttemplate.prometheus_exporters._ids"></template-diff>
                                        </div>
                                        <div class="help-block">
                                            <?php echo __('To monitor this host using Prometheus please select the exporters that are installed on the host.'); ?>
                                            <br/>
                                            <?php echo __('Before you could query the host through Prometheus, you need to refresh the monitoring configuration.'); ?>
                                        </div>
                                        <div ng-repeat="error in errors.prometheus_exporters">
                                            <div class="help-block text-danger">{{ error }}</div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                            <!-- PROMETHEUS CONFIGURATION END -->
                        <?php endif; ?>

                        <?php if (\Cake\Core\Plugin::isLoaded('SLAModule')): ?>
                            <!-- SLA CONFIGURATION START -->
                            <div class="card margin-bottom-10">
                                <div class="card-header">
                                    <i class="fa-solid fa-award"></i> <?= __('Service Level Agreement'); ?>
                                </div>
                                <div class="card-body">
                                    <div class="form-group" ng-class="{'has-error': errors.slas}">
                                        <label class="control-label" for="SlasSelect">
                                            <?php if ($this->Acl->hasPermission('edit', 'slas', 'SLAModule')): ?>
                                                <a ui-sref="SlasEdit({id:post.Host.sla_id})"
                                                   ng-show="post.Host.sla_id">
                                                    <?= __('Service class'); ?>
                                                </a>
                                                <span ng-hide="post.Host.sla_id">
                                                        <?= __('Service class'); ?>
                                                    </span>
                                            <?php else: ?>
                                                <?= __('Service class'); ?>
                                            <?php endif; ?>
                                        </label>
                                        <div class="input-group" style="width: 100%;">
                                            <select
                                                id="SlasSelect"
                                                data-placeholder="<?php echo __('Please choose'); ?>"
                                                class="form-control"
                                                chosen="slas"
                                                ng-options="sla.key as sla.value for sla in slas"
                                                ng-model="post.Host.sla_id">
                                                <option></option>
                                            </select>
                                            <template-diff ng-show="post.Host.hosttemplate_id"
                                                           value="post.Host.sla_id"
                                                           template-value="hosttemplate.Hosttemplate.sla_id"></template-diff>
                                        </div>

                                        <div class="help-block">
                                            <?= __('You can define service levels for this host.'); ?>
                                        </div>
                                        <div ng-repeat="error in errors.slas">
                                            <div class="help-block text-danger">{{ error }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- SLA CONFIGURATION END -->
                        <?php endif; ?>

                        <div class="card margin-top-10">
                            <div class="card-body">
                                <div class="float-right">
                                    <label>
                                        <input type="checkbox" ng-model="data.createAnother">
                                        <?php echo __('Create another'); ?>
                                    </label>

                                    <?php if ($this->Acl->hasPermission('config', 'agentconnector')): ?>
                                        <div class="btn-group" ng-if="!data.createAnother">
                                            <a onclick="return false;" ng-click="submit('AgentconnectorsWizard')"
                                               class="btn btn-primary waves-effect waves-themed text-white">
                                                <?php echo __('Create host and setup agent'); ?>
                                            </a>
                                        </div>
                                    <?php endif; ?>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary waves-effect waves-themed">
                                            <?php echo __('Create host'); ?>
                                        </button>
                                        <button type="button"
                                                class="btn btn-primary dropdown-toggle dropdown-toggle-split waves-effect waves-themed"
                                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            <span class="sr-only">
                                                Toggle Dropdown
                                            </span>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-right">
                                            <?php if ($this->Acl->hasPermission('add', 'services')): ?>
                                                <a class="dropdown-item" href="javascript:void(0);"
                                                   ng-click="submit('ServicesAdd')">
                                                    <i class="fa fa fa-gear"></i>
                                                    <?php echo __('Save and create service'); ?>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($this->Acl->hasPermission('config', 'agentconnector')): ?>
                                                <a class="dropdown-item" href="javascript:void(0);"
                                                   ng-click="submit('AgentconnectorsWizard')"
                                                   ng-if="!data.createAnother">
                                                    <i class="fa fa-user-secret"></i>
                                                    <?php echo __('Save and setup agent'); ?>
                                                </a>
                                            <?php endif; ?>

                                            <?php if ($this->Acl->hasPermission('add', 'services')): ?>
                                                <a class="dropdown-item" href="javascript:void(0);"
                                                   ng-click="submitSaveHostAndAssignMatchingServicetemplateGroups()"
                                                   ng-if="!data.createAnother">
                                                    <i class="fa fa-external-link-alt"></i>
                                                    <?php echo __('Save host and assign matching service template groups'); ?>
                                                </a>
                                            <?php endif; ?>

                                            <?php if (\Cake\Core\Plugin::isLoaded('CheckmkModule') && $this->Acl->hasPermission('index', 'scans', 'CheckmkModule')): ?>
                                                <a class="dropdown-item" href="javascript:void(0);"
                                                   ng-click="submit('ScansIndex')">
                                                    <i class="fa fa fa-share-alt"></i>
                                                    <?php echo __('Save and run Checkmk discovery'); ?>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <a back-button href="javascript:void(0);" fallback-state='HostsIndex'
                                       class="btn btn-default">
                                        <?php echo __('Cancel'); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
