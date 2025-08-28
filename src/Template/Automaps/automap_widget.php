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
            <div class="row">
                <div class="col-lg-4">
                    <a href="javascript:void(0);" class="btn btn-default btn-xs txt-color-blueDark"
                       ng-click="showConfig()" ng-hide="readOnly">
                        <i class="fa fa-cog fa-sm"></i>
                    </a>
                    <span ng-show="automap_id === null" class="text-info padding-left-20">
                        <?php echo __('No element selected'); ?>
                    </span>
                    <span ng-show="automap" class="text-info padding-left-20">
                        <?php if ($this->Acl->hasPermission('edit', 'automaps')): ?>
                            <a ui-sref="AutomapsEdit({id:automap.id})"
                               ng-if="automap.allow_edit">
                                {{automap.name}}
                            </a>
                            <span ng-if="!automap.allow_edit">
                                {{automap.name}}
                            </span>
                        <?php else: ?>
                            {{automap.name}}
                        <?php endif; ?>
                    </span>
                </div>
                <div class="col-lg-8" ng-hide="automap_id === null">
                    <div class="row d-flex justify-content-end">
                        <div class="col-lg-1 offset-lg-6 text-right">
                            <a href="javascript:void(0);" ng-show="useScroll" ng-click="pauseScroll()"
                               title="<?php echo __('Pause scrolling'); ?>"
                               class="btn btn-xs btn-primary">
                                <i class="fa fa-pause"></i>
                            </a>
                            <a href="javascript:void(0);" ng-show="!useScroll"
                               ng-click="startScroll()" title="<?php echo __('Start scrolling'); ?>"
                               class="btn btn-xs btn-primary">
                                <i class="fa fa-play"></i>
                            </a>
                        </div>
                        <div class="col-lg-3">
                            <div class="form-group form-group-slider">
                                <label class="display-inline">
                                    <span><?= __('Refresh interval:'); ?></span>
                                    <span class="note" id="PagingInterval_human">
                                        {{pagingTimeString}}
                                    </span>
                                </label>

                                <div class="slidecontainer">
                                    <input type="range" step="5000" min="0" max="300000" class="slider"
                                           ng-disabled="readOnly"
                                           style="width: 100%"
                                           ng-model="scroll_interval" ng-model-options="{debounce: 500}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <automap
                automap="automap"
                services-by-host="servicesByHost"
                scroll="scroll"
                changepage="changepage"
                only-buttons="onlyButtons">
            </automap>
            <!--end-->
        </flippy-front>
        <flippy-back class="fixFlippy">
            <a href="javascript:void(0);" class="btn btn-default btn-xs txt-color-blueDark" ng-click="hideConfig()">
                <i class="fa fa-eye fa-sm"></i>
            </a>
            <div class="padding-top-10">
                <div class="form-group">
                    <div class="row">
                        <label class="col-xs-12 col-lg-12 control-label">
                            <?php echo __('Automap'); ?>
                        </label>
                        <div class="col-xs-12 col-lg-12">
                            <select data-placeholder="<?php echo __('Please choose'); ?>"
                                    class="form-control"
                                    callback="loadAutomaps"
                                    chosen="automaps"
                                    ng-options="available_automap.key as available_automap.value for available_automap in automaps"
                                    ng-model="automap_id">
                            </select>
                        </div>
                    </div>
                    <br/>
                    <div class="row">
                        <div class="col-xs-12 col-lg-12 margin-bottom-5">
                            <div class="input-group input-group-sm">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">
                                        <i class="icon-prepend fas fa-th"></i>
                                    </span>
                                </div>
                                <input type="number"
                                       class="form-control"
                                       min="1"
                                       placeholder="<?php echo __('Limit per page'); ?>"
                                       ng-model="limit"
                                       ng-model-options="{debounce: 500}">
                            </div>
                            <div class="info-block-helptext font-xs">
                                <?= __('This option has only an effect if "Use pagination" is enabled.'); ?>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-12">
                            <button class="btn btn-primary pull-right" ng-click="saveSettings()">
                                <?php echo __('Save'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </flippy-back>
    </flippy>
</div>
