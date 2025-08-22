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

/**
 * @var \App\View\AppView $this
 * @var \App\View\Helper\AclHelper $Acl
 */

use Cake\Core\Plugin;

?>
<ol class="breadcrumb page-breadcrumb">
    <li class="breadcrumb-item">
        <a ui-sref="DashboardsIndex">
            <i class="fa fa-home"></i> <?php echo __('Home'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <a ui-sref="ChangelogsIndex">
            <i class="fa fa-code-fork"></i> <?php echo __('Change log'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <i class="fa fa-list"></i> <?php echo __('Overview'); ?>
    </li>
</ol>

<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?php echo __('Change log'); ?>
                    <span class="fw-300"><i><?php echo __('overview'); ?></i></span>
                </h2>
                <div class="panel-toolbar">
                    <ul class="nav nav-tabs border-bottom-0 nav-tabs-clean" role="tablist">
                        <?php if ($this->Acl->hasPermission('index', 'changelogs')): ?>
                            <li class="nav-item">
                                <a class="nav-link active" data-toggle="tab" ui-sref="ChangelogsIndex" role="tab">
                                    <i class="fa-solid fa-timeline fa-rotate-90">&nbsp;</i> <?php echo __('Main overview'); ?>
                                </a>
                            </li>
                        <?php endif; ?>
                        <?php if (Plugin::isLoaded('ImportModule') && $this->Acl->hasPermission('index', 'importchangelogs', 'ImportModule')): ?>
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="tab" ui-sref="ImportChangelogsIndex" role="tab">
                                    <i class="fas fa-file-import">&nbsp;</i> <?php echo __('Import Module Changes'); ?>
                                </a>
                            </li>
                        <?php endif; ?>
                        <?php if (Plugin::isLoaded('ScmModule') && $this->Acl->hasPermission('index', 'scmchangelogs', 'ScmModule')): ?>
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="tab" ui-sref="ScmChangelogsIndex" role="tab">
                                    <i class="fas fa-people-roof">&nbsp;</i> <?php echo __('Scm Module Changes'); ?>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                    <button class="btn btn-xs btn-default mr-1 shadow-0" ng-click="load()">
                        <i class="fas fa-sync"></i> <?php echo __('Refresh'); ?>
                    </button>
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
                                                <span
                                                    class="input-group-text filter-text"><?php echo __('From'); ?></span>
                                            </div>
                                            <input type="datetime-local" class="form-control form-control-sm"
                                                   style="padding:0.5rem 0.875rem;"
                                                   placeholder="<?php echo __('From date'); ?>"
                                                   ng-model="from_time"
                                                   ng-model-options="{debounce: 500, timeSecondsFormat:'ss', timeStripZeroSeconds: true}">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xs-12 col-md-6 margin-bottom-10">
                                    <div class="form-group">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fa fa-filter"></i></span>
                                            </div>
                                            <input type="text" class="form-control form-control-sm"
                                                   placeholder="<?php echo __('Filter by name'); ?>"
                                                   ng-model="filter.Changelogs.name"
                                                   ng-model-options="{debounce: 500}">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xs-12 col-md-6 margin-bottom-10">
                                    <div class="form-group">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span
                                                    class="input-group-text filter-text"><?php echo __('To'); ?></span>
                                            </div>
                                            <input type="datetime-local" class="form-control form-control-sm"
                                                   style="padding:0.5rem 0.875rem;"
                                                   placeholder="<?php echo __('To date'); ?>"
                                                   ng-model="to_time"
                                                   ng-model-options="{debounce: 500, timeSecondsFormat:'ss', timeStripZeroSeconds: true}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-12 col-lg-3">
                                    <fieldset>
                                        <h5><?php echo __('Object type'); ?></h5>


                                        <?php
                                        $models = [
                                            'Command'              => __('Commands'),
                                            'Contact'              => __('Contacts'),
                                            'Contactgroup'         => __('Contact groups'),
                                            'Host'                 => __('Hosts'),
                                            'Hostgroup'            => __('Host groups'),
                                            'Hosttemplate'         => __('Host templates'),
                                            'Service'              => __('Services'),
                                            'Servicegroup'         => __('Service groups'),
                                            'Servicetemplate'      => __('Service templates'),
                                            'Servicetemplategroup' => __('Service template groups'),
                                            'Timeperiod'           => __('Time periods'),
                                            'Location'             => __('Locations'),
                                            'Tenant'               => __('Tenants'),
                                            'Container'            => __('Nodes'),
                                            'Export'               => __('Refresh configuration'),
                                            'User'                 => __('Users')
                                        ];
                                        ?>

                                        <?php foreach ($models as $model => $name): ?>
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox"
                                                       id="Filter<?= $model ?>"
                                                       class="custom-control-input"
                                                       name="checkbox"
                                                       checked="checked"
                                                       ng-false-value="0"
                                                       ng-true-value="1"
                                                       ng-model="filter.Models.<?= $model ?>"
                                                       ng-model-options="{debounce: 500}">
                                                <label class="custom-control-label"
                                                       for="Filter<?= $model ?>"><?php echo h($name); ?></label>
                                            </div>
                                        <?php endforeach; ?>

                                    </fieldset>
                                </div>
                                <div class="col-xs-12 col-lg-3">
                                    <fieldset>
                                        <h5><?php echo __('Actions'); ?></h5>
                                        <div class="form-group">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox"
                                                       id="FilterAdd"
                                                       class="custom-control-input"
                                                       name="checkbox"
                                                       checked="checked"
                                                       ng-false-value="0"
                                                       ng-true-value="1"
                                                       ng-model="filter.Actions.add"
                                                       ng-model-options="{debounce: 500}">
                                                <label class="custom-control-label custom-control-label-up"
                                                       for="FilterAdd"><?php echo __('Add'); ?></label>
                                            </div>

                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox"
                                                       id="FilterEdit"
                                                       class="custom-control-input"
                                                       name="checkbox"
                                                       checked="checked"
                                                       ng-false-value="0"
                                                       ng-true-value="1"
                                                       ng-model="filter.Actions.edit"
                                                       ng-model-options="{debounce: 500}">
                                                <label class="custom-control-label custom-control-label-warning"
                                                       for="FilterEdit"><?php echo __('Edit'); ?></label>
                                            </div>

                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox"
                                                       id="FilterCopy"
                                                       class="custom-control-input"
                                                       name="checkbox"
                                                       checked="checked"
                                                       ng-false-value="0"
                                                       ng-true-value="1"
                                                       ng-model="filter.Actions.copy"
                                                       ng-model-options="{debounce: 500}">
                                                <label class="custom-control-label"
                                                       for="FilterCopy"><?php echo __('Copy'); ?></label>
                                            </div>
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox"
                                                       id="FilterDelete"
                                                       class="custom-control-input"
                                                       name="checkbox"
                                                       checked="checked"
                                                       ng-false-value="0"
                                                       ng-true-value="1"
                                                       ng-model="filter.Actions.delete"
                                                       ng-model-options="{debounce: 500}">
                                                <label class="custom-control-label custom-control-label-down"
                                                       for="FilterDelete"><?php echo __('Delete'); ?></label>
                                            </div>
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox"
                                                       id="FilterActivate"
                                                       class="custom-control-input"
                                                       name="checkbox"
                                                       checked="checked"
                                                       ng-false-value="0"
                                                       ng-true-value="1"
                                                       ng-model="filter.Actions.activate"
                                                       ng-model-options="{debounce: 500}">
                                                <label class="custom-control-label"
                                                       for="FilterActivate"><?php echo __('Activate'); ?></label>
                                            </div>
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox"
                                                       id="FilterDeactivate"
                                                       class="custom-control-input"
                                                       name="checkbox"
                                                       checked="checked"
                                                       ng-false-value="0"
                                                       ng-true-value="1"
                                                       ng-model="filter.Actions.deactivate"
                                                       ng-model-options="{debounce: 500}">
                                                <label class="custom-control-label"
                                                       for="FilterDeactivate"><?php echo __('Deactivate'); ?></label>
                                            </div>
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox"
                                                       id="FilterActionExport"
                                                       class="custom-control-input"
                                                       name="checkbox"
                                                       checked="checked"
                                                       ng-false-value="0"
                                                       ng-true-value="1"
                                                       ng-model="filter.Actions.export"
                                                       ng-model-options="{debounce: 500}">
                                                <label class="custom-control-label"
                                                       for="FilterActionExport"><?php echo __('Export'); ?></label>
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
                    <!-- END FILTER -->

                    <div class="margin-top-10" ng-show="changes.length === 0">
                        <div class="text-center text-danger italic">
                            <?php echo __('No entries match the selection'); ?>
                        </div>
                    </div>

                    <div class="frame-wrap">
                        <div class="col-lg-12">
                            <ul class="cbp_tmtimeline">
                                <li ng-repeat="changeLogEntry in changes">
                                    <change-log-entry changelogentry="changeLogEntry"></change-log-entry>
                                </li>
                            </ul>

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
