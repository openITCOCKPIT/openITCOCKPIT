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

        <flippy-front class="fixFlippy">
            <a href="javascript:void(0);" class="btn btn-default btn-xs txt-color-blueDark" ng-click="showConfig()" ng-hide="readOnly">
                <i class="fa fa-cog fa-sm"></i>
            </a>
            <span ng-show="tacho.service_id === null" class="text-info padding-left-20">
            <?php echo __('No element selected'); ?>
        </span>
            <div class="no-padding">
                <center>
                    <?php if ($this->Acl->hasPermission('browser', 'services') || $this->Acl->hasPermission('view', 'eventcorrelations', 'EventcorrelationModule')): ?>
                        <a ng-href="{{tachoHref}}">
                            <canvas id="tacho-{{widget.id}}"></canvas>
                        </a>
                    <?php else: ?>
                        <canvas id="tacho-{{widget.id}}"></canvas>
                    <?php endif; ?>
                </center>
            </div>
        </flippy-front>
        <flippy-back class="fixFlippy">
            <a href="javascript:void(0);" class="btn btn-default btn-xs txt-color-blueDark" ng-click="hideConfig()">
                <i class="fa fa-eye fa-sm"></i>
            </a>
            <div class="col-lg-12">
                <div class="form-group" style="width: 100%;">
                    <label class="control-label">
                        <?php echo __('Service'); ?>
                    </label>
                    <select data-placeholder="<?php echo __('Please choose'); ?>"
                            class="form-control"
                            chosen="services"
                            callback="loadServices"
                            ng-options="+(service.value.Service.id) as service.value.Host.name + '/' +((service.value.Service.name)?service.value.Service.name:service.value.Servicetemplate.name) group by service.value.Host.name for service in services"
                            ng-model="tacho.service_id">

                    </select>
                </div>

                <div class="form-group" style="width: 100%;">
                    <label class="control-label">
                        <?php echo __('Select metric'); ?>
                    </label>
                    <select data-placeholder="<?php echo __('Please choose'); ?>"
                            class="form-control"
                            chosen="metrics"
                            callback="loadServices"
                            ng-options="key as value for (key , value) in metrics"
                            ng-model="tacho.metric">

                    </select>
                </div>

                <div class="form-group">
                    <div class="custom-control custom-checkbox  margin-bottom-10">
                        <input type="checkbox"
                               class="custom-control-input"
                               id="showLabel{{widget.id}}"
                               ng-model="tacho.show_label">
                        <label class="custom-control-label" for="showLabel{{widget.id}}">
                            <?php echo __('Show label'); ?>
                        </label>
                    </div>
                </div>
                <div class="col-xs-12">
                    <button class="btn btn-primary float-right"
                            ng-click="saveTacho()">
                        <?php echo __('Save'); ?>
                    </button>
                </div>
            </div>
        </flippy-back>
    </flippy>
</div>
