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


$weekdays = [
    1 => __('Monday'),
    2 => __('Tuesday'),
    3 => __('Wednesday'),
    4 => __('Thursday'),
    5 => __('Friday'),
    6 => __('Saturday'),
    7 => __('Sunday')
];
?>
<ol class="breadcrumb page-breadcrumb">
    <li class="breadcrumb-item">
        <a ui-sref="DashboardsIndex">
            <i class="fa fa-home"></i> <?php echo __('Home'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <a ui-sref="TimeperiodsIndex">
            <i class="fa fa-clock-o"></i> <?php echo __('Time periods'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <i class="fa fa-plus"></i> <?php echo __('Add'); ?>
    </li>
</ol>


<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?php echo __('Create new time period'); ?>
                </h2>
                <div class="panel-toolbar">
                    <?php if ($this->Acl->hasPermission('index', 'timeperiods')): ?>
                        <a back-button href="javascript:void(0);" fallback-state='TimeperiodsIndex'
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
            {objectName : '<?php echo __('Time period'); ?>' , message: '<?php echo __('created successfully'); ?>'}">

                        <div class="form-group required" ng-class="{'has-error': errors.containers}">
                            <label class="control-label" for="ContactContainers">
                                <?php echo __('Container'); ?>
                            </label>
                            <select
                                id="ContactContainers"
                                data-placeholder="<?php echo __('Please choose'); ?>"
                                class="form-control"
                                chosen="containers"
                                ng-options="container.key as container.value for container in containers"
                                ng-model="post.Timeperiod.container_id">
                            </select>
                            <div ng-show="post.Timeperiod.container_id < 1" class="warning-glow">
                                <?php echo __('Please select a container.'); ?>
                            </div>
                            <div ng-repeat="error in errors.containers">
                                <div class="help-block text-danger">{{ error }}</div>
                            </div>
                        </div>

                        <div class="form-group required" ng-class="{'has-error': errors.name}">
                            <label class="control-label">
                                <?php echo __('Name'); ?>
                            </label>
                            <input
                                class="form-control"
                                type="text"
                                ng-model="post.Timeperiod.name">
                            <div ng-repeat="error in errors.name">
                                <div class="help-block text-danger">{{ error }}</div>
                            </div>
                        </div>

                        <div class="form-group" ng-class="{'has-error': errors.description}">
                            <label class="control-label">
                                <?php echo __('Description'); ?>
                            </label>
                            <input
                                class="form-control"
                                type="text"
                                ng-model="post.Timeperiod.description">
                            <div ng-repeat="error in errors.description">
                                <div class="help-block text-danger">{{ error }}</div>
                            </div>
                        </div>

                        <div class="form-group required" ng-class="{'has-error': errors.containers}">
                            <label class="control-label" for="ContactContainers">
                                <?php if ($this->Acl->hasPermission('edit', 'calendars')): ?>
                                    <a ui-sref="CalendarsEdit({id:post.Timeperiod.calendar_id})"
                                       ng-if="post.Timeperiod.calendar_id > 0">
                                        <?php echo __('Calendar'); ?>
                                    </a>
                                    <span ng-if="!post.Timeperiod.calendar_id"><?php echo __('Calendar'); ?></span>
                                <?php else: ?>
                                    <?php echo __('Calendar'); ?>
                                <?php endif; ?>
                            </label>
                            <select
                                id="ContactContainers"
                                data-placeholder="<?php echo __('Please choose'); ?>"
                                class="form-control"
                                chosen="calendars"
                                ng-options="calendar.key as calendar.value for calendar in calendars"
                                ng-model="post.Timeperiod.calendar_id">
                                <option></option>
                            </select>
                            <div ng-repeat="error in errors.containers">
                                <div class="help-block text-danger">{{ error }}</div>
                            </div>
                            <span class="help-block">
                                <?php echo __('In addition to the interval defined by the given time ranges, you are able to add 24x7 days using a calendar. This will only affect the monitoring engine.'); ?>
                            </span>
                        </div>

                        <legend class="font-sm margin-top-30">
                            <div>
                                <label ng-class="{'text-danger': errors.validate_timeranges}">
                                    <?php echo __('Time ranges:'); ?>
                                </label>
                            </div>
                            <div class="text-danger" ng-show="errors.validate_timeranges">
                                <?php echo __('Do not enter overlapping timeframes'); ?>
                            </div>
                        </legend>
                        <hr>
                        <div ng-repeat="range in timeperiod.ranges" class="row margin-bottom-5">
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i
                                                    class="fas fa-calendar-day padding-top-3 padding-bottom-3"></i></span>
                                        </div>
                                        <select class="form-control input-sm select" chosen="" id="tp_day_{{$index}}"
                                                ng-model="timeperiod.ranges[$index].day">
                                            <?php foreach ($weekdays as $day => $weekday):
                                                printf('<option value="%s">%s</option>>', $day, $weekday);
                                            endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="form-group" ng-class="{'has-error': errors.name}">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="far fa-clock-o"></i></span>
                                        </div>
                                        <input id="tp_start_{{$index}}" class="form-control" required
                                               ng-class="{'border-color-error': hasTimeRange(errors, range) ||
                                                       errors.timeperiod_timeranges[range.index].start}"
                                               placeholder="<?php echo __('Start');
                                               echo ' ';
                                               echo __('(00:00)'); ?>"
                                               type="text"
                                               size="5"
                                               maxlength="5"
                                               ng-model="range.start">
                                    </div>
                                    <div ng-repeat="error in errors.timeperiod_timeranges[range.index].start">
                                        <div class="help-block text-danger font-xs">{{ error }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="form-group" ng-class="{'has-error': errors.name}">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="far fa-clock-o"></i></span>
                                        </div>
                                        <input id="tp_end_{{$index}}" class="form-control" required
                                               ng-class="{'border-color-error': hasTimeRange(errors, range) ||
                                                       errors.timeperiod_timeranges[range.index].end}"
                                               placeholder="<?php echo __('End');
                                               echo ' ';
                                               echo __('(24:00)'); ?>"
                                               type="text"
                                               size="5"
                                               maxlength="5"
                                               ng-model="range.end">
                                    </div>
                                    <div ng-repeat="error in errors.timeperiod_timeranges[range.index].end">
                                        <div class="help-block text-danger font-xs">{{ error }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-1 padding-top-3">
                                <a class="btn btn-danger btn-sm waves-effect waves-themed"
                                   href="javascript:void(0);"
                                   ng-click="removeTimerange(range.index)">
                                    <i class="fa fa-trash fa-lg"></i>
                                </a>
                            </div>
                        </div>
                        <div class="col-lg-12 text-right">
                            <a href="javascript:void(0);" class="btn btn-success btn-sm" ng-click="addTimerange()">
                                <i class="fa fa-plus"></i>
                                <?php echo __('Add'); ?>
                            </a>
                        </div>

                        <div class="card margin-top-10">
                            <div class="card-body">
                                <div class="float-right">
                                    <button class="btn btn-primary" type="submit">
                                        <?php echo __('Create time period'); ?>
                                    </button>
                                    <a back-button href="javascript:void(0);" fallback-state='TimeperiodsIndex'
                                       class="btn btn-default"><?php echo __('Cancel'); ?>
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
