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
<div>
    <flippy vertical
            class="col-lg-12"
            flip="['custom:FLIP_EVENT_OUT']"
            flip-back="['custom:FLIP_EVENT_IN']"
            duration="800"
            timing-function="ease-in-out">

        <flippy-front
            class="bg-service-{{filter.Servicestatus.current_state}} bg-service-background-icon bg-service-front-{{filter.Servicestatus.current_state}} fixFlippy">
            <div class="row">
                <div class="col-xs-12 col-lg-12">
                    <a href="javascript:void(0);" class="btn btn-default btn-xs txt-color-blueDark"
                       ng-click="showConfig()"  ng-hide="readOnly">
                        <i class="fa fa-cog fa-sm"></i>
                    </a>
                    <span class="pr-2 text-white italic pull-right font-weight-light font-md">
                        <i class="fa-solid fa-business-time"></i> <?= __('State older than'); ?>:
                        <span ng-show="filter.Servicestatus.state_older_than"
                              ng-switch="filter.Servicestatus.state_older_than_unit">
                            {{filter.Servicestatus.state_older_than}}
                            <span ng-switch-when="SECOND">
                                <?= __('second(s)'); ?>
                            </span>
                            <span ng-switch-when="MINUTE">
                                <?= __('minute(s)'); ?>
                            </span>
                            <span ng-switch-when="HOUR">
                                <?= __('hour(s)'); ?>
                            </span>
                            <span ng-switch-when="DAY">
                                <?= __('day(s)'); ?>
                            </span>
                        </span>
                        <span ng-hide="filter.Servicestatus.state_older_than">
                            <i class="fa-solid fa-infinity"></i>
                        </span>
                    </span>
                </div>
                <div class="col col-lg-12">
                    <div class="padding-5 text-center" style="font-size:{{fontSize}}px;">
                        <?php if ($this->Acl->hasPermission('index', 'services')): ?>
                            <a ng-click="statusCount > 0 && goToState()" class="text-white"
                               ng-class="{'pointer': statusCount > 0}">
                                {{ statusCount }}
                            </a>
                        <?php else: ?>
                            {{ statusCount }}
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </flippy-front>
        <flippy-back class="fixFlippy">
            <a href="javascript:void(0);" class="btn btn-default btn-xs txt-color-blueDark" ng-click="hideConfig()">
                <i class="fa fa-eye fa-sm"></i>
            </a>
            <div class="padding-top-10">
                <div class="row">
                    <div class="col-xs-12 col-lg-12 margin-bottom-5">
                        <div class="input-group input-group-sm">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="icon-prepend fa fa-desktop"></i></span>
                            </div>
                            <input type="text" class="form-control"
                                   placeholder="<?php echo __('Filter by host name'); ?>"
                                   ng-model="filter.Host.name"
                                   ng-model-options="{debounce: 500}">
                            <div class="input-group-append">
                                    <span class="input-group-text pt-0 pb-0">
                                        <label>
                                            <?= __('Enable RegEx'); ?>
                                            <input type="checkbox"
                                                   ng-model="filter.Host.name_regex">
                                        </label>
                                        <regex-helper-tooltip class="pl-1 pb-1"></regex-helper-tooltip>
                                    </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-xs-12 col-lg-12 margin-bottom-5">
                        <div class="input-group input-group-sm">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="icon-prepend fa fa-filter"></i></span>
                            </div>
                            <input type="text" class="form-control form-control-sm"
                                   placeholder="<?php echo __('Filter by service name'); ?>"
                                   ng-model="filter.Service.servicename"
                                   ng-model-options="{debounce: 500}">
                            <div class="input-group-append">
                                <span class="input-group-text pt-0 pb-0">
                                    <label>
                                        <?= __('Enable RegEx'); ?>
                                        <input type="checkbox"
                                               ng-model="filter.Service.servicename_regex">
                                    </label>
                                    <regex-helper-tooltip class="pl-1 pb-1"></regex-helper-tooltip>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-xs-12 col-lg-12 margin-bottom-5">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <div class="input-group-text">
                                    <div class="icon-stack icon-prepend">
                                        <i class="fas fa-desktop"></i>
                                        <i class="fa-solid fa-tags fa-xs text-success cornered cornered-lr"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="col tagsinputFilter">
                                <input type="text"
                                       class="form-control form-control-sm"
                                       data-role="tagsinput"
                                       id="HostsKeywordsInput{{widget.id}}"
                                       placeholder="<?php echo __('Filter by host tags'); ?>"
                                       ng-model="filter.Host.keywords"
                                       ng-model-options="{debounce: 500}"
                                       style="display: none;">
                            </div>
                        </div>
                    </div>
                    <div class="col-xs-12 col-lg-12 margin-bottom-5">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <div class="input-group-text">
                                    <div class="icon-stack icon-prepend">
                                        <i class="fas fa-desktop"></i>
                                        <i class="fa-solid fa-tags fa-xs text-danger cornered cornered-lr"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="col tagsinputFilter">
                                <input type="text" class="input-sm"
                                       data-role="tagsinput"
                                       id="HostsNotKeywordsInput{{widget.id}}"
                                       placeholder="<?php echo __('Filter by excluded host tags'); ?>"
                                       ng-model="filter.Host.not_keywords"
                                       ng-model-options="{debounce: 500}">
                            </div>
                        </div>
                    </div>
                    <div class="col-xs-12 col-lg-12 margin-bottom-5">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <div class="input-group-text">
                                    <div class="icon-stack icon-prepend">
                                        <i class="fa fa-cogs"></i>
                                        <i class="fa-solid fa-tags fa-xs text-success cornered cornered-lr"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="col tagsinputFilter">
                                <input type="text"
                                       class="form-control form-control-sm"
                                       data-role="tagsinput"
                                       id="ServicesKeywordsInput{{widget.id}}"
                                       placeholder="<?php echo __('Filter by service tags'); ?>"
                                       ng-model="filter.Service.keywords"
                                       ng-model-options="{debounce: 500}"
                                       style="display: none;">
                            </div>
                        </div>
                    </div>
                    <div class="col-xs-12 col-lg-12 margin-bottom-5">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <div class="input-group-text">
                                    <div class="icon-stack icon-prepend">
                                        <i class="fa fa-cogs"></i>
                                        <i class="fa-solid fa-tags fa-xs text-danger cornered cornered-lr"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="col tagsinputFilter">
                                <input type="text" class="input-sm"
                                       data-role="tagsinput"
                                       id="ServicesNotKeywordsInput{{widget.id}}"
                                       placeholder="<?php echo __('Filter by excluded service tags'); ?>"
                                       ng-model="filter.Service.not_keywords"
                                       ng-model-options="{debounce: 500}">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row padding-bottom-5">
                    <div class="col-xs-12 col-lg-12">
                        <fieldset>
                            <h5><?php echo __('Service groups'); ?></h5>
                            <div class="form-group smart-form">
                                <select
                                    id="Servicegroup"
                                    data-placeholder="<?php echo __('Filter by service groups'); ?>"
                                    class="form-control"
                                    chosen="servicegroups"
                                    callback="loadServicegroups"
                                    multiple
                                    ng-options="servicegroup.key as servicegroup.value for servicegroup in servicegroups"
                                    ng-model="filter.Servicegroup._ids"
                                    ng-model-options="{debounce: 500}">
                                </select>
                            </div>
                        </fieldset>
                    </div>
                </div>
                <div class="row">
                    <div class="col-xs-12 col-lg-6">
                        <h5><?php echo __('Service status'); ?></h5>
                        <div class="custom-control custom-radio custom-control-left margin-right-10">
                            <input type="radio"
                                   class="custom-control-input"
                                   id="widget-radio0-{{widget.id}}"
                                   ng-value="0"
                                   ng-model="filter.Servicestatus.current_state"
                                   ng-model-options="{debounce: 500}">
                            <label class="custom-control-label custom-control-label-ok"
                                   for="widget-radio0-{{widget.id}}">
                                <?php echo __('Ok'); ?>
                            </label>
                        </div>
                        <div class="custom-control custom-radio custom-control-left margin-right-10">
                            <input type="radio"
                                   class="custom-control-input"
                                   id="widget-radio1-{{widget.id}}"
                                   ng-value="1"
                                   ng-model="filter.Servicestatus.current_state"
                                   ng-model-options="{debounce: 500}">
                            <label class="custom-control-label custom-control-label-warning"
                                   for="widget-radio1-{{widget.id}}">
                                <?php echo __('Warning'); ?>
                            </label>
                        </div>
                        <div class="custom-control custom-radio custom-control-left margin-right-10">
                            <input type="radio"
                                   class="custom-control-input"
                                   id="widget-radio2-{{widget.id}}"
                                   ng-value="2"
                                   ng-model="filter.Servicestatus.current_state"
                                   ng-model-options="{debounce: 500}">
                            <label class="custom-control-label custom-control-label-critical"
                                   for="widget-radio2-{{widget.id}}">
                                <?php echo __('Critical'); ?>
                            </label>
                        </div>
                        <div class="custom-control custom-radio custom-control-left margin-right-10">
                            <input type="radio"
                                   class="custom-control-input"
                                   id="widget-radio3-{{widget.id}}"
                                   ng-value="3"
                                   ng-model="filter.Servicestatus.current_state"
                                   ng-model-options="{debounce: 500}">
                            <label class="custom-control-label custom-control-label-unknown"
                                   for="widget-radio3-{{widget.id}}">
                                <?php echo __('Unknown'); ?>
                            </label>
                        </div>
                    </div>

                    <div class="col-xs-12 col-sm-6" ng-show="filter.Servicestatus.current_state > 0">
                        <h5><?php echo __('Acknowledgements'); ?></h5>
                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox"
                                       class="custom-control-input"
                                       id="isAck_{{widget.id}}"
                                       ng-model="filter.Servicestatus.acknowledged"
                                       ng-model-options="{debounce: 500}">
                                <label class="custom-control-label" for="isAck_{{widget.id}}">
                                    <?php echo __('Acknowledged'); ?>
                                </label>
                            </div>

                            <div class="custom-control custom-checkbox">
                                <input type="checkbox"
                                       class="custom-control-input"
                                       id="isNotAck_{{widget.id}}"
                                       ng-model="filter.Servicestatus.not_acknowledged"
                                       ng-model-options="{debounce: 500}">
                                <label class="custom-control-label" for="isNotAck_{{widget.id}}">
                                    <?php echo __('Not Acknowledged'); ?>
                                </label>
                            </div>
                        </div>
                        <h5><?php echo __('Downtimes'); ?></h5>
                        <div class="form-group smart-form">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox"
                                       class="custom-control-input"
                                       id="inDowntime_{{widget.id}}"
                                       ng-model="filter.Servicestatus.in_downtime"
                                       ng-model-options="{debounce: 500}">
                                <label class="custom-control-label" for="inDowntime_{{widget.id}}">
                                    <?php echo __('In Downtime'); ?>
                                </label>
                            </div>

                            <div class="custom-control custom-checkbox">
                                <input type="checkbox"
                                       class="custom-control-input"
                                       id="notInDowntime_{{widget.id}}"
                                       ng-model="filter.Servicestatus.not_in_downtime"
                                       ng-model-options="{debounce: 500}">
                                <label class="custom-control-label" for="notInDowntime_{{widget.id}}">
                                    <?php echo __('Not in Downtime'); ?>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-xs-12 col-lg-12">
                        <h5 class="pt-1">
                            <?= __('Status older than'); ?>
                        </h5>
                        <div class="input-group input-group-sm" ng-if="filter.Servicestatus">
                            <div class="input-group-prepend input-group-append">
                                <span class="input-group-text">
                                    <i class="far fa-clock fa-lg"></i>
                                </span>
                            </div>
                            <input ng-model="filter.Servicestatus.state_older_than"
                                   placeholder="<?= __('Leave empty for all'); ?>"
                                   class="form-control" type="number" min="1">
                            <div class="input-group-append">
                                <button class="btn btn-outline-primary dropdown-toggle"
                                        ng-switch="filter.Servicestatus.state_older_than_unit"
                                        type="button" data-toggle="dropdown"
                                        aria-haspopup="true" aria-expanded="false">
                                    <span ng-switch-when="SECOND">
                                        <?= __('seconds'); ?>
                                    </span>
                                    <span ng-switch-when="MINUTE">
                                        <?= __('minutes'); ?>
                                    </span>
                                    <span ng-switch-when="HOUR">
                                        <?= __('hours'); ?>
                                    </span>
                                    <span ng-switch-when="DAY">
                                        <?= __('days'); ?>
                                    </span>
                                </button>
                                <div class="dropdown-menu">
                                    <a class="dropdown-item" href="javascript:void(0);"
                                       ng-click="filter.Servicestatus.state_older_than_unit = 'SECOND'">
                                        <?= __('seconds'); ?>
                                    </a>
                                    <a class="dropdown-item" href="javascript:void(0);"
                                       ng-click="filter.Servicestatus.state_older_than_unit = 'MINUTE'">
                                        <?= __('minutes'); ?>
                                    </a>
                                    <a class="dropdown-item" href="javascript:void(0);"
                                       ng-click="filter.Servicestatus.state_older_than_unit = 'HOUR'">
                                        <?= __('hours'); ?>
                                    </a>
                                    <a class="dropdown-item" href="javascript:void(0);"
                                       ng-click="filter.Servicestatus.state_older_than_unit = 'DAY'">
                                        <?= __('days'); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row py-2">
                    <div class="col-lg-12">
                        <button class="btn btn-primary float-right" ng-click="saveServicestatusOverviewExtended()">
                            <?php echo __('Save'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </flippy-back>
    </flippy>
</div>
