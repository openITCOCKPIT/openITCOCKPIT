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
 * @var boolean $isLdapAuth
 */

?>
<ol class="breadcrumb page-breadcrumb">
    <li class="breadcrumb-item">
        <a ui-sref="DashboardsIndex">
            <i class="fa fa-home"></i> <?php echo __('Home'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <a ui-sref="EventlogsIndex">
            <i class="fa fa-file-text"></i> <?php echo __('Event Log'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <i class="fa fa-list"></i> <?php echo __('Overview'); ?>
    </li>
</ol>

<!-- ANGAULAR DIRECTIVES -->
<massdelete></massdelete>

<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?php echo __('Event Log'); ?>
                    <span class="fw-300"><i><?php echo __('overview'); ?></i></span>
                </h2>
                <div class="panel-toolbar">
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
                                                <span class="input-group-text"><i class="fa fa-filter"></i></span>
                                            </div>
                                            <input type="text" class="form-control form-control-sm"
                                                   placeholder="<?php echo __('Filter by name'); ?>"
                                                   ng-model="filter.name"
                                                   ng-model-options="{debounce: 500}">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xs-12 col-md-6 margin-bottom-10">
                                    <div class="form-group">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                            </div>
                                            <input type="text" class="form-control form-control-sm"
                                                   placeholder="<?php echo __('Filter by email'); ?>"
                                                   ng-model="filter.user_email"
                                                   ng-model-options="{debounce: 500}">
                                        </div>
                                        <div class="help-block text-info">
                                            <i class="fa fa-info-circle"></i>
                                            <?php echo __('E-mail filter does not search for deleted users!'); ?>
                                        </div>
                                    </div>
                                </div>
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
                                        <h5><?php echo __('Log type'); ?></h5>


                                        <?php
                                        $types = [
                                            'login'                => __('User Login'),
                                            'user_delete'          => __('User Delete'),
                                            'user_password_change' => __('User Password Change'),
                                        ];
                                        ?>

                                        <?php foreach ($types as $type => $name): ?>
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox"
                                                       id="Filter<?= $type ?>"
                                                       class="custom-control-input"
                                                       name="checkbox"
                                                       checked="checked"
                                                       ng-false-value="0"
                                                       ng-true-value="1"
                                                       ng-model="filter.Types.<?= $type ?>"
                                                       ng-model-options="{debounce: 500}">
                                                <label class="custom-control-label"
                                                       for="Filter<?= $type ?>"><?php echo h($name); ?></label>
                                            </div>
                                        <?php endforeach; ?>

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
                    <!-- Filter End -->

                    <div class="frame-wrap">
                        <table class="table table-striped m-0 table-bordered table-hover table-sm">
                            <thead>
                            <tr>
                                <th colspan="2" class="no-sort" ng-click="orderBy('Eventlogs.type')">
                                    <i class="fa" ng-class="getSortClass('Eventlogs.type')"></i>
                                    <?php echo __('Event Type'); ?>
                                </th>
                                <th class="no-sort" ng-click="orderBy('name')">
                                    <i class="fa" ng-class="getSortClass('name')"></i>
                                    <?php echo __('Name'); ?>
                                </th>
                                <th class="no-sort" ng-click="orderBy('user_email')"
                                    ng-show="logTypes.includes('login') || logTypes.includes('user_delete') || logTypes.includes('user_password_change')">
                                    <i class="fa" ng-class="getSortClass('user_email')"></i>
                                    <?php echo __('Email'); ?>
                                </th>
                                <th class="no-sort" ng-click="orderBy('Eventlogs.created')">
                                    <i class="fa" ng-class="getSortClass('Eventlogs.created')"></i>
                                    <?php echo __('Date'); ?>
                                </th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr ng-repeat="event in events">
                                <td>
                                    <i class="{{typeIconClasses[event.type]}}" title="{{typeTranslations[event.type]}}">
                                </td>
                                <td>{{typeTranslations[event.type]}}</td>
                                <td>
                                    <span ng-if="event.recordExists">
                                        {{event.name}}
                                    </span>
                                    <span ng-if="!event.recordExists">
                                        <s>{{event.name}}</s>
                                    </span>
                                </td>
                                <td ng-show="logTypes.includes('login') || logTypes.includes('user_delete') || logTypes.includes('user_password_change')">
                                    <span ng-if="event.recordExists">
                                        {{event.user_email}}
                                    </span>
                                    <span ng-if="!event.recordExists">
                                        <s>{{event.data['user_email']}}</s>
                                    </span>
                                </td>
                                <td>{{event.time}}</td>
                            </tr>
                            </tbody>
                        </table>
                        <div class="margin-top-10" ng-show="events.length == 0">
                            <div class="text-center text-danger italic">
                                <?php echo __('No entries match the selection'); ?>
                            </div>
                        </div>
                        <div class="padding-right-10">
                            <div class="row margin-top-10 margin-bottom-10">
                                <div class="col-md-12">
                                    <div class="btn-group btn-group-sm float-right">
                                        <button class="btn btn-default dropdown-toggle waves-effect waves-themed"
                                                type="button"
                                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            <?php echo __('More actions'); ?>
                                        </button>
                                        <div class="dropdown-menu" x-placement="bottom-start"
                                             style="position: absolute; will-change: top, left; top: 37px; left: 0px;">
                                            <a ng-href="{{ linkFor('pdf') }}" class="dropdown-item">
                                                <i class="fa fa-file-pdf-o"></i> <?php echo __('List as PDF'); ?>
                                            </a>
                                            <a ng-href="{{ linkFor('csv') }}" class="dropdown-item">
                                                <i class="fa-solid fa-file-csv"></i> <?php echo __('List as CSV'); ?>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <scroll scroll="scroll" click-action="changepage" ng-if="scroll"></scroll>
                        <paginator paging="paging" click-action="changepage" ng-if="paging"></paginator>
                        <?php echo $this->element('paginator_or_scroll'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
