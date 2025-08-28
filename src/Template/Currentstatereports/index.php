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
        <a ui-sref="CurrentstatereportsIndex">
            <i class="fa fa-file-invoice"></i> <?php echo __('Current state report'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <i class="fa fa-edit"></i> <?php echo __('Overview'); ?>
    </li>
</ol>

<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?php echo __('Create new current state report'); ?>
                </h2>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <form class="form-horizontal">
                        <div class="form-group required" ng-class="{'has-error': errors.services}" ng-init="reportMessage=
                                {successMessage : '<?php echo __('Report created successfully'); ?>' , errorMessage: '<?php echo __('Report could not be created'); ?>'}">

                            <div class="form-group required" ng-class="{'has-error': errors.services}">
                                <label class="control-label" for="ServicesSelect">
                                    <?php echo __('Services'); ?>
                                </label>
                                <select
                                    id="ServicesSelect"
                                    data-placeholder="<?php echo __('Please choose'); ?>"
                                    class="form-control"
                                    chosen="services"
                                    callback="loadServices"
                                    ng-options="service.value.Service.id as service.value.Host.name + '/' +((service.value.Service.name)?service.value.Service.name:service.value.Servicetemplate.name) group by service.value.Host.name for service in services"
                                    ng-model="post.services"
                                    multiple>
                                </select>
                                <div ng-repeat="error in errors.services">
                                    <div class="help-block text-danger">{{ error }}</div>
                                </div>
                            </div>

                            <div class="form-group required" ng-class="{'has-error': errors.report_format}">
                                <label class="control-label" for="FormatSelect">
                                    <?php echo __('Report format'); ?>
                                </label>
                                <select
                                    id="FormatSelect"
                                    data-placeholder="<?php echo __('Please choose'); ?>"
                                    class="form-control"
                                    chosen="{}"
                                    ng-model="post.report_format">
                                    <option value="1"><?php echo __('PDF'); ?></option>
                                    <option value="2"><?php echo __('HTML'); ?></option>
                                </select>
                                <div ng-repeat="error in errors.report_format">
                                    <div class="help-block text-danger">{{ error }}</div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-xs-12 col-lg-3">
                                    <fieldset>
                                        <h5><?php echo __('Service status'); ?></h5>
                                        <div class="form-group" ng-class="{'has-error': errors.current_state}">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox"
                                                       id="statusFilterOk"
                                                       class="custom-control-input"
                                                       name="checkbox"
                                                       checked="checked"
                                                       ng-model="post.current_state.ok"
                                                       ng-model-options="{debounce: 500}">
                                                <label class="custom-control-label custom-control-label-ok"
                                                       for="statusFilterOk"><?php echo __('Ok'); ?></label>
                                            </div>

                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox"
                                                       id="statusFilterWarning"
                                                       class="custom-control-input"
                                                       name="checkbox"
                                                       checked="checked"
                                                       ng-model="post.current_state.warning"
                                                       ng-model-options="{debounce: 500}">
                                                <label class="custom-control-label custom-control-label-warning"
                                                       for="statusFilterWarning"><?php echo __('Warning'); ?></label>
                                            </div>

                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox"
                                                       id="statusFilterCritical"
                                                       class="custom-control-input"
                                                       name="checkbox"
                                                       checked="checked"
                                                       ng-model="post.current_state.critical"
                                                       ng-model-options="{debounce: 500}">
                                                <label class="custom-control-label custom-control-label-critical"
                                                       for="statusFilterCritical"><?php echo __('Critical'); ?></label>
                                            </div>

                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox"
                                                       id="statusFilterUnknown"
                                                       class="custom-control-input"
                                                       name="checkbox"
                                                       checked="checked"
                                                       ng-model="post.current_state.unknown"
                                                       ng-model-options="{debounce: 500}">
                                                <label class="custom-control-label custom-control-label-unknown"
                                                       for="statusFilterUnknown"><?php echo __('Unknown'); ?></label>
                                            </div>
                                        </div>
                                    </fieldset>
                                </div>


                                <div class="col-xs-12 col-lg-3">
                                    <fieldset>
                                        <h5><?php echo __('Acknowledgements'); ?></h5>
                                        <div class="form-group smart-form">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox"
                                                       id="ackFilterAck"
                                                       class="custom-control-input"
                                                       name="checkbox"
                                                       checked="checked"
                                                       ng-model="filter.Servicestatus.acknowledged"
                                                       ng-model-options="{debounce: 500}">
                                                <label class="custom-control-label"
                                                       for="ackFilterAck"><?php echo __('Acknowledge'); ?></label>
                                            </div>

                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox"
                                                       id="ackFilterNotAck"
                                                       class="custom-control-input"
                                                       name="checkbox"
                                                       checked="checked"
                                                       ng-model="filter.Servicestatus.not_acknowledged"
                                                       ng-model-options="{debounce: 500}">
                                                <label class="custom-control-label"
                                                       for="ackFilterNotAck"><?php echo __('Not acknowledged'); ?></label>
                                            </div>
                                        </div>
                                    </fieldset>
                                </div>

                                <div class="col-xs-12 col-lg-3">
                                    <fieldset>
                                        <h5><?php echo __('Downtimes'); ?></h5>
                                        <div class="form-group smart-form">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox"
                                                       id="downtimwFilterInDowntime"
                                                       class="custom-control-input"
                                                       name="checkbox"
                                                       checked="checked"
                                                       ng-model="filter.Servicestatus.in_downtime"
                                                       ng-model-options="{debounce: 500}">
                                                <label class="custom-control-label"
                                                       for="downtimwFilterInDowntime"><?php echo __('In downtime'); ?></label>
                                            </div>

                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox"
                                                       id="downtimwFilterNotInDowntime"
                                                       class="custom-control-input"
                                                       name="checkbox"
                                                       checked="checked"
                                                       ng-model="filter.Servicestatus.not_in_downtime"
                                                       ng-model-options="{debounce: 500}">
                                                <label class="custom-control-label"
                                                       for="downtimwFilterNotInDowntime"><?php echo __('Not in downtime'); ?></label>
                                            </div>
                                        </div>
                                    </fieldset>
                                </div>

                                <div class="col-xs-12 col-lg-3">
                                    <fieldset>
                                        <h5><?php echo __('Check type'); ?></h5>
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox"
                                                   id="checkTypeFilterActive"
                                                   class="custom-control-input"
                                                   name="checkbox"
                                                   checked="checked"
                                                   ng-model="filter.Servicestatus.active"
                                                   ng-model-options="{debounce: 500}">
                                            <label class="custom-control-label"
                                                   for="checkTypeFilterActive"><?php echo __('Active service'); ?></label>
                                        </div>

                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox"
                                                   id="checkTypeFilterPassive"
                                                   class="custom-control-input"
                                                   name="checkbox"
                                                   checked="checked"
                                                   ng-model="filter.Servicestatus.passive"
                                                   ng-model-options="{debounce: 500}">
                                            <label class="custom-control-label"
                                                   for="checkTypeFilterPassive"><?php echo __('Passive service'); ?></label>
                                        </div>
                                    </fieldset>
                                </div>
                            </div>

                        </div>
                        <div class="row">
                            <div class="alert alert-info" ng-show="generatingReport">
                                <i class="fa fa-spin fa-refresh"></i>
                                <?php echo __('Generating report...'); ?>
                            </div>
                        </div>
                        <!-- HTML report start -->
                        <div ng-repeat="servicestatusObject in servicestatus" class="margin-10">
                            <div>
                                <div class="row bg-{{servicestatusObject.Hoststatus.humanState}} padding-10">
                                    <div class="col-lg-10 font-md">
                                        <span class="txt-color-white"
                                              style="font-size:20px;text-shadow: 2px 4px 3px rgba(0,0,0,0.3);">
                                            <i class="fa fa-lg fa-desktop"></i>
                                            <?php if ($this->Acl->hasPermission('browser', 'hosts')): ?>
                                                <a ui-sref="HostsBrowser({id: servicestatusObject.Host.id})"
                                                   class="txt-color-white">
                                                    {{servicestatusObject.Host.hostname}}
                                                    ({{servicestatusObject.Host.address}})
                                                </a>
                                            <?php else: ?>
                                                {{servicestatusObject.Host.hostname}} ({{servicestatusObject.Host.address}})
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                    <div class="col-lg-2 font-md txt-color-white text-right">
                                        <i class="fa fa-calendar"></i>
                                        {{servicestatusObject.Hoststatus.lastHardStateChange}}
                                    </div>
                                </div>
                                <div class="padding-10">
                                    <div class="row font-sm">
                                        <div class="col-lg-2">
                                            <?php echo __('Last check'); ?>
                                        </div>
                                        <div class="col-lg-2">
                                            <?php echo __('Next check'); ?>
                                        </div>
                                        <div class="col-lg-2">
                                            <?php echo __('State type'); ?>
                                        </div>
                                    </div>
                                    <div class="row font-md">
                                        <div class="col-lg-2">
                                            {{servicestatusObject.Hoststatus.lastCheck}}
                                        </div>
                                        <div class="col-lg-2">
                                            <span
                                                ng-if="servicestatusObject.Hoststatus.activeChecksEnabled && servicestatusObject.Host.is_satellite_host === false">{{ servicestatusObject.Hoststatus.nextCheck
                                                }}</span>
                                            <span
                                                ng-if="servicestatusObject.Hoststatus.activeChecksEnabled === false || servicestatusObject.Host.is_satellite_host === true">
                                                <?php echo __('n/a'); ?>
                                            </span>
                                        </div>
                                        <div class="col-lg-2">
                                            <div class="row"
                                                 ng-show="servicestatusObject.Hoststatus.isHardstate">
                                                <?php echo __('Hard state'); ?>
                                                ({{servicestatusObject.Hoststatus.current_check_attempt}}/{{servicestatusObject.Hoststatus.max_check_attempts}})
                                            </div>
                                            <div class="row"
                                                 ng-show="!servicestatusObject.Hoststatus.isHardstate">
                                                <?php echo __('Soft state'); ?>
                                                ({{servicestatusObject.Hoststatus.current_check_attempt}}/{{servicestatusObject.Hoststatus.max_check_attempts}})
                                            </div>
                                        </div>
                                        <div class="col-lg-1">
                                            <div>
                                                <i class="fa fa-user"
                                                   ng-show="servicestatusObject.Hoststatus.problemHasBeenAcknowledged"
                                                   ng-if="servicestatusObject.Hoststatus.acknowledgement_type == 1"></i>
                                                <i class="fa fa-user-o"
                                                   ng-show="servicestatusObject.Hoststatus.problemHasBeenAcknowledged"
                                                   ng-if="servicestatusObject.Hoststatus.acknowledgement_type == 2"
                                                   title="<?php echo __('Sticky Acknowledgedment'); ?>"></i>
                                                <i class="fa fa-power-off"
                                                   ng-show="servicestatusObject.Hoststatus.scheduledDowntimeDepth > 0"></i>
                                                <span title="<?php echo __('Passively transferred service'); ?>"
                                                      ng-show="servicestatusObject.Host.active_checks_enabled === false || servicestatusObject.Host.is_satellite_host === true">
                                                    P
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col-lg-5 text-right">
                                            {{servicestatusObject.Hoststatus.output}}
                                        </div>
                                    </div>
                                </div>

                                <table class="table table-hover m-0 table-sm"
                                       ng-if="servicestatusObject.Services">
                                    <thead>
                                    <tr>
                                        <th><?php echo __('State'); ?></th>
                                        <th></th>
                                        <th><?php echo __('Service'); ?></th>
                                        <th><?php echo __('State since'); ?></th>
                                        <th><?php echo __('Last check'); ?></th>
                                        <th><?php echo __('Next check'); ?></th>
                                        <th><?php echo __('State type'); ?></th>
                                        <th><?php echo __('Output'); ?></th>
                                        <th style="max-width:300px;"><?php echo __('Performance data'); ?></th>
                                        <th></th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr ng-repeat="serviceDetails in servicestatusObject.Services"
                                        ng-init="showDetails[serviceDetails.Service.id] = false">
                                        <td>
                                            <span class="fa-stack">
                                                <i class="fa fa-gear fa-stack-2x txt-color-blueLight"
                                                   style="text-shadow: 1px 2px 1px rgba(0,0,0,0.3);"></i>
                                                <i class="fa fa-heartbeat fa-stack-1x cornered cornered-lr {{serviceDetails.Servicestatus.humanState}} font-sm"
                                                   style="text-shadow: 1px 2px 1px rgba(0,0,0,0.3);"></i>
                                            </span>
                                            <span
                                                class="badge bg-{{serviceDetails.Servicestatus.humanState}} text-uppercase padding-top-2 padding-bottom-2 text-white">
                                                {{serviceDetails.Servicestatus.humanState}}
                                            </span>
                                        </td>
                                        <td>
                                            <i class="fa fa-user"
                                               ng-show="serviceDetails.Servicestatus.problemHasBeenAcknowledged"
                                               ng-if="serviceDetails.Servicestatus.acknowledgement_type == 1"></i>
                                            <i class="fa fa-user-o"
                                               ng-show="serviceDetails.Servicestatus.problemHasBeenAcknowledged"
                                               ng-if="serviceDetails.Servicestatus.acknowledgement_type == 2"
                                               title="<?php echo __('Sticky Acknowledgedment'); ?>"></i>
                                            <i class="fa fa-power-off"
                                               ng-show="serviceDetails.Servicestatus.scheduledDowntimeDepth > 0"></i>
                                            <span title="<?php echo __('Passively transferred service'); ?>"
                                                  ng-show="serviceDetails.Service.active_checks_enabled === false || servicestatusObject.Host.is_satellite_host === true">
                                                P
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($this->Acl->hasPermission('browser', 'services')): ?>
                                                <a ui-sref="ServicesBrowser({id:serviceDetails.Service.id})">
                                                    {{serviceDetails.Service.servicename}}
                                                </a>
                                            <?php else: ?>
                                                {{serviceDetails.Service.servicename}}
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            {{serviceDetails.Servicestatus.lastHardStateChange}}
                                        </td>
                                        <td>
                                            {{ serviceDetails.Servicestatus.lastCheck }}
                                        </td>
                                        <td>
                                            <span
                                                ng-if="serviceDetails.Service.active_checks_enabled && servicestatusObject.Host.is_satellite_host === false">{{ serviceDetails.Servicestatus.nextCheck
                                                }}</span>
                                            <span
                                                ng-if="serviceDetails.Service.active_checks_enabled === false || servicestatusObject.Host.is_satellite_host === true">
                                                <?php echo __('n/a'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span ng-show="serviceDetails.Servicestatus.isHardstate">
                                                <?php echo __('Hard state'); ?>
                                                ({{serviceDetails.Servicestatus.current_check_attempt}}/{{serviceDetails.Servicestatus.max_check_attempts}})
                                            </span>
                                            <span ng-show="!serviceDetails.Servicestatus.isHardstate">
                                                <?php echo __('Soft state'); ?>
                                                ({{serviceDetails.Servicestatus.current_check_attempt}}/{{serviceDetails.Servicestatus.max_check_attempts}})
                                            </span>
                                        </td>
                                        <td>
                                            {{serviceDetails.Servicestatus.output}}
                                        </td>
                                        <td>
                                            <span ng-if="serviceDetails.Servicestatus.perfdataArray"
                                                  ng-repeat="(label, perfdata) in serviceDetails.Servicestatus.perfdataArray"
                                                  ng-attr-title="{{getProgressbarData(perfdata, label).perfdataString}}">
                                                <span ng-if="$index === 0">
                                                    <div class="progress progress-md bg-primary position-relative"
                                                         style="margin-bottom: 0px;">
                                                        <div
                                                            style="width: {{getProgressbarData(perfdata, label).currentPercentage}}%; position: unset;"
                                                            class="progress-bar bg-{{getProgressbarData(perfdata, label).backgroundColorClass}}">
                                                            <span
                                                                class="justify-content-center d-flex position-absolute w-100">{{getProgressbarData(perfdata, label).perfdataString}}</span>
                                                        </div>
                                                    </div>
                                                </span>

                                                <span ng-if="$index > 0"
                                                      ng-hide="!showDetails[serviceDetails.Service.id]">
                                                    <div class="progress progress-md bg-primary position-relative"
                                                         style="margin-top: 4px;">
                                                        <div
                                                            style="width: {{getProgressbarData(perfdata, label).currentPercentage}}%; position: unset;"
                                                            class="progress-bar bg-{{getProgressbarData(perfdata, label).backgroundColorClass}}">
                                                            <span
                                                                class="justify-content-center d-flex position-absolute w-100 ellipsis">{{getProgressbarData(perfdata, label).perfdataString}}</span>
                                                        </div>
                                                    </div>
                                                </span>
                                            </span>
                                            <span
                                                ng-if="serviceDetails.Servicestatus.perfdataArrayCounter === 0"
                                                class="italic font-xs">
                                                <i class="fa fa-info-circle"></i>
                                                <?php echo __('No performance data available'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span ng-show="serviceDetails.Servicestatus.perfdataArrayCounter > 1">
                                                <i class="far fa-plus-square font-md pointer"
                                                   ng-show="showDetails[serviceDetails.Service.id]==false"
                                                   ng-click="showDetails[serviceDetails.Service.id] = !showDetails[serviceDetails.Service.id]"></i>
                                                <i class=" far fa-minus-square font-md pointer"
                                                   ng-show="showDetails[serviceDetails.Service.id] == true"
                                                   ng-click="showDetails[serviceDetails.Service.id] = !showDetails[serviceDetails.Service.id]"></i>
                                            </span>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card margin-top-10">
                            <div class="card-body">
                                <div class="float-right">
                                    <button class="btn btn-primary" ng-click="createCurrentStateReport()" type="button">
                                        <?php echo __('Create current state report'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

