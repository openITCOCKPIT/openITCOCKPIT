<?php
// Copyright (C) <2015>  <it-novum GmbH>
//
// This file is dual licensed
//
// 1.
//	This program is free software: you can redistribute it and/or modify
//	it under the terms of the GNU General Public License as published by
//	the Free Software Foundation, version 3 of the License.
//
//	This program is distributed in the hope that it will be useful,
//	but WITHOUT ANY WARRANTY; without even the implied warranty of
//	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//	GNU General Public License for more details.
//
//	You should have received a copy of the GNU General Public License
//	along with this program.  If not, see <http://www.gnu.org/licenses/>.
//

// 2.
//	If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//	under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//	License agreement and license key will be shipped with the order
//	confirmation.
$timezones = \Cake\I18n\DateTime::listTimezones();
?>
<ol class="breadcrumb page-breadcrumb">
    <li class="breadcrumb-item">
        <a ui-sref="DashboardsIndex">
            <i class="fa fa-home"></i> <?php echo __('Home'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <a ui-sref="LocationsIndex">
            <i class="fa fa-cog"></i> <?php echo __('Locations'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <i class="fa fa-edit"></i> <?php echo __('Edit'); ?>
    </li>
</ol>


<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?php echo __('Edit location:'); ?>
                    <span class="fw-300"><i>{{ post.container.name }}</i></span>
                </h2>
                <div class="panel-toolbar">
                    <?php if ($this->Acl->hasPermission('index', 'locations')): ?>
                        <a back-button href="javascript:void(0);" fallback-state='LocationsIndex' class="btn btn-default btn-xs mr-1 shadow-0">
                            <i class="fas fa-long-arrow-alt-left"></i> <?php echo __('Back'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <form ng-submit="submit();" class="form-horizontal"
                          ng-init="successMessage=
            {objectName : '<?php echo __('Location'); ?>' , message: '<?php echo __('updated successfully'); ?>'}">

                        <div class="form-group required" ng-class="{'has-error': errors.containers}">
                            <label class="control-label" for="LocationParentContainer">
                                <?php echo __('Container'); ?>
                            </label>
                            <select
                                id="LocationParentContainer"
                                data-placeholder="<?php echo __('Please choose'); ?>"
                                class="form-control"
                                chosen="containers"
                                disabled
                                ng-options="container.key as container.value for container in containers"
                                ng-model="post.container.parent_id">
                            </select>
                            <div ng-repeat="error in errors.container.parent_id">
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
                                ng-model="post.container.name">
                            <div ng-repeat="error in errors.container.name">
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
                                ng-model="post.description">
                            <div ng-repeat="error in errors.description">
                                <div class="help-block text-danger">{{ error }}</div>
                            </div>
                        </div>

                        <div class="form-group" ng-class="{'has-error': (errors.latitude || errors.longitude.custom)}">
                            <label class="control-label">
                                <?php echo __('Latitude'); ?>
                            </label>
                            <input
                                class="form-control"
                                type="text"
                                ng-model="post.latitude"
                                placeholder="<?php echo '50.5558095'; ?>">
                            <div class="info-block-helptext">
                                <?php echo __(' Latitude must be a number between -90 and 90 degree inclusive.'); ?>
                            </div>
                            <div ng-repeat="error in errors.latitude">
                                <div class="help-block text-danger">{{ error }}</div>
                            </div>
                            <div ng-show="(!errors.latitude.custom && errors.longitude.custom)">
                                <div class="help-block text-danger">{{ errors.longitude.custom }}</div>
                            </div>
                        </div>

                        <div class="form-group" ng-class="{'has-error': (errors.longitude || errors.longitude.custom)}">
                            <label class="control-label">
                                <?php echo __('Longitude'); ?>
                            </label>
                            <input
                                class="form-control"
                                type="text"
                                ng-model="post.longitude"
                                placeholder="<?php echo '9.6808449'; ?>">
                            <div class="info-block-helptext">
                                <?php echo __(' Latitude must be a number between -180 and 180 degree inclusive.'); ?>
                            </div>
                            <div ng-repeat="error in errors.longitude">
                                <div class="help-block text-danger">{{ error }}</div>
                            </div>
                            <div ng-show="(!errors.longitude.custom && errors.longitude.custom)">
                                <div class="help-block text-danger">{{ errors.longitude.custom }}</div>
                            </div>
                        </div>

                        <div class="form-group required" ng-class="{'has-error': errors.timezone}">
                            <label class="control-label" for="LocationDateformat">
                                <?php echo __('Timezone'); ?>
                            </label>
                            <select
                                id="LocationDateformat"
                                data-placeholder="<?php echo __('Please choose'); ?>"
                                class="form-control"
                                chosen="{}"
                                ng-init="post.timezone = post.timezone || 'Europe/Berlin'"
                                ng-model="post.timezone">
                                <?php foreach ($timezones as $continent => $continentTimezons): ?>
                                    <optgroup label="<?php echo h($continent); ?>">
                                        <?php foreach ($continentTimezons as $timezoneKey => $timezoneName): ?>
                                            <option
                                                value="<?php echo h($timezoneKey); ?>"><?php echo h($timezoneName); ?></option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach;; ?>
                            </select>
                            <div ng-repeat="error in errors.timezone">
                                <div class="help-block text-danger">{{ error }}</div>
                            </div>
                        </div>

                        <div class="col-xs-12 col-lg-10 col-lg-offset-2">
                            <div id="mapDiv" class="vector-map"></div>
                        </div>

                        <div class="card margin-top-10">
                            <div class="card-body">
                                <div class="float-right">
                                    <button class="btn btn-primary"
                                            type="submit"><?php echo __('Update location'); ?></button>
                                    <a back-button href="javascript:void(0);" fallback-state='LocationsIndex'
                                       class="btn btn-default"><?php echo __('Cancel'); ?></a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
