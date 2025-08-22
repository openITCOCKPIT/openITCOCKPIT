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
        <a ui-sref="AutomapsIndex">
            <i class="fa-solid fa-wand-magic-sparkles"></i> <?php echo __('Auto Maps'); ?>
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
                    <?php echo __('Create new auto map'); ?>
                </h2>
                <div class="panel-toolbar">
                    <?php if ($this->Acl->hasPermission('index', 'automap')): ?>
                        <a back-button href="javascript:void(0);" fallback-state='AutomapsIndex' class="btn btn-default btn-xs mr-1 shadow-0">
                            <i class="fas fa-long-arrow-alt-left"></i> <?php echo __('Back'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <form ng-submit="submit();" class="form-horizontal"
                          ng-init="successMessage=
            {objectName : '<?php echo __('Auto Map'); ?>' , message: '<?php echo __('created successfully'); ?>'}">

                        <div class="form-group required" ng-class="{'has-error': errors.container_id}">
                            <label class="control-label" for="ContainerSelect">
                                <?php echo __('Container'); ?>
                            </label>
                            <select
                                id="ContainerSelect"
                                data-placeholder="<?php echo __('Please choose'); ?>"
                                class="form-control"
                                chosen="containers"
                                ng-options="container.key as container.value for container in containers"
                                ng-model="post.Automap.container_id">
                            </select>
                            <div ng-show="post.Automap.container_id < 1" class="warning-glow">
                                <?php echo __('Please select a container.'); ?>
                            </div>
                            <div ng-repeat="error in errors.container_id">
                                <div class="help-block text-danger">{{ error }}</div>
                            </div>
                        </div>


                        <div class="form-group" ng-class="{'has-error': errors.recursive}">
                            <div class="custom-control custom-checkbox"
                                 ng-class="{'has-error': errors.recursive}">

                                <input type="checkbox"
                                       class="custom-control-input"
                                       ng-true-value="1"
                                       ng-false-value="0"
                                       id="recursive"
                                       ng-model="post.Automap.recursive">
                                <label class="custom-control-label" for="recursive">
                                    <?php echo __('Recursive'); ?>
                                </label>
                            </div>
                        </div>


                        <div class="form-group required" ng-class="{'has-error': errors.name}">
                            <label class="control-label">
                                <?php echo __('Name'); ?>
                            </label>
                            <input
                                class="form-control"
                                type="text"
                                ng-model="post.Automap.name">
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
                                ng-model="post.Automap.description">
                            <div ng-repeat="error in errors.description">
                                <div class="help-block text-danger">{{ error }}</div>
                            </div>
                        </div>

                        <div class="form-group required" ng-class="{'has-error': errors.host_regex}">
                            <label class="control-label">
                                <?php echo __('Host RegEx'); ?>
                            </label>
                            <input
                                class="form-control"
                                type="text"
                                ng-model-options="{debounce: 500}"
                                ng-model="post.Automap.host_regex">
                            <div ng-repeat="error in errors.host_regex">
                                <div class="help-block text-danger">{{ error }}</div>
                            </div>
                            <div class="help-block" ng-show="data.hostCount > 0">
                                <?php echo __('{0} hosts matching to regular expression.', '{{data.hostCount}}'); ?>
                            </div>
                        </div>

                        <div class="form-group required" ng-class="{'has-error': errors.service_regex}">
                            <label class="control-label">
                                <?php echo __('Service RegEx'); ?>
                            </label>
                            <input
                                class="form-control"
                                type="text"
                                ng-model-options="{debounce: 500}"
                                ng-model="post.Automap.service_regex">
                            <div ng-repeat="error in errors.service_regex">
                                <div class="help-block text-danger">{{ error }}</div>
                            </div>
                            <div class="help-block" ng-show="data.serviceCount > 0">
                                <?php echo __('{0} services matching to regular expression.', '{{data.serviceCount}}'); ?>
                            </div>
                        </div>

                        <div class="form-group" ng-class="{'has-error': errors.hostgroup_regex}">
                            <label class="control-label">
                                <?php echo __('Host group RegEx'); ?>
                            </label>
                            <input
                                class="form-control"
                                type="text"
                                ng-model-options="{debounce: 500}"
                                ng-model="post.Automap.hostgroup_regex">
                            <div ng-repeat="error in errors.hostgroup_regex">
                                <div class="help-block text-danger">{{ error }}</div>
                            </div>
                            <div class="help-block" ng-show="data.hostgroupCount > 0">
                                <?php echo __('{0} host groups matching to regular expression.', '{{data.hostgroupCount}}'); ?>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-lg-6">
                                <div class="card">
                                    <div class="card-header">
                                        <i class="fa fa-filter"></i>
                                        <?php echo __('Filter and display options'); ?>
                                    </div>
                                    <div class="card-body">
                                        <div class="custom-control custom-checkbox padding-top-20"
                                             ng-class="{'has-error': errors.show_ok}">
                                            <input type="checkbox" class="custom-control-input"
                                                   ng-true-value="1"
                                                   ng-false-value="0"
                                                   id="show_ok"
                                                   ng-model="post.Automap.show_ok">
                                            <label class="custom-control-label"
                                                   for="show_ok">
                                                <span
                                                    class="badge badge-success notify-label"><?php echo __('Show Ok'); ?></span>
                                                <i class="checkbox-success"></i>
                                            </label>
                                        </div>

                                        <div class="custom-control custom-checkbox"
                                             ng-class="{'has-error': errors.show_warning}">
                                            <input type="checkbox" class="custom-control-input"
                                                   ng-true-value="1"
                                                   ng-false-value="0"
                                                   id="show_warning"
                                                   ng-model="post.Automap.show_warning">
                                            <label class="custom-control-label"
                                                   for="show_warning">
                                                <span
                                                    class="badge badge-warning notify-label"><?php echo __('Show Warning'); ?></span>
                                                <i class="checkbox-warning"></i>
                                            </label>
                                        </div>

                                        <div class="custom-control custom-checkbox"
                                             ng-class="{'has-error': errors.show_critical}">
                                            <input type="checkbox" class="custom-control-input"
                                                   ng-true-value="1"
                                                   ng-false-value="0"
                                                   id="show_critical"
                                                   ng-model="post.Automap.show_critical">
                                            <label class="custom-control-label"
                                                   for="show_critical">
                                                <span
                                                    class="badge badge-danger notify-label"><?php echo __('Show Critical'); ?></span>
                                                <i class="checkbox-danger"></i>
                                            </label>
                                        </div>

                                        <div class="custom-control custom-checkbox"
                                             ng-class="{'has-error': errors.show_unknown}">
                                            <input type="checkbox" class="custom-control-input"
                                                   ng-true-value="1"
                                                   ng-false-value="0"
                                                   id="show_unknown"
                                                   ng-model="post.Automap.show_unknown">
                                            <label class="custom-control-label"
                                                   for="show_unknown">
                                                <span
                                                    class="badge badge-secondary notify-label"><?php echo __('Show Unknown'); ?></span>
                                                <i class="checkbox-secondary"></i>
                                            </label>
                                        </div>

                                        <div class="custom-control custom-checkbox"
                                             ng-class="{'has-error': errors.show_downtime}">
                                            <input type="checkbox" class="custom-control-input"
                                                   ng-true-value="1"
                                                   ng-false-value="0"
                                                   id="show_downtime"
                                                   ng-model="post.Automap.show_downtime">
                                            <label class="custom-control-label"
                                                   for="show_downtime">
                                                <span
                                                    class="badge badge-primary notify-label"><?php echo __('Show Downtime'); ?></span>
                                                <i class="checkbox-primary"></i>
                                            </label>
                                        </div>

                                        <div class="custom-control custom-checkbox"
                                             ng-class="{'has-error': errors.show_acknowledged}">
                                            <input type="checkbox" class="custom-control-input"
                                                   ng-true-value="1"
                                                   ng-false-value="0"
                                                   id="show_acknowledged"
                                                   ng-model="post.Automap.show_acknowledged">
                                            <label class="custom-control-label"
                                                   for="show_acknowledged">
                                                <span
                                                    class="badge badge-primary notify-label"><?php echo __('Show Acknowledged'); ?></span>
                                                <i class="checkbox-primary"></i>
                                            </label>
                                        </div>

                                        <hr>
                                        <h5>
                                            <?php echo __('Display options'); ?>
                                        </h5>

                                        <div class="form-group" ng-class="{'has-error': errors.show_label}">
                                            <div
                                                class="custom-control custom-checkbox"
                                                ng-class="{'has-error': errors.show_label}">

                                                <input type="checkbox"
                                                       class="custom-control-input"
                                                       ng-true-value="1"
                                                       ng-false-value="0"
                                                       id="show_label"
                                                       ng-model="post.Automap.show_label">
                                                <label class="custom-control-label" for="show_label">
                                                    <?php echo __('Show Label'); ?>
                                                </label>
                                            </div>
                                        </div>

                                        <div class="form-group" ng-class="{'has-error': errors.group_by_host}">
                                            <div
                                                class="custom-control custom-checkbox"
                                                ng-class="{'has-error': errors.group_by_host}">

                                                <input type="checkbox"
                                                       class="custom-control-input"
                                                       ng-true-value="1"
                                                       ng-false-value="0"
                                                       id="group_by_host"
                                                       ng-model="post.Automap.group_by_host">
                                                <label class="custom-control-label" for="group_by_host">
                                                    <?php echo __('Group by Host'); ?>
                                                </label>
                                            </div>
                                        </div>

                                        <div class="form-group" ng-class="{'has-error': errors.use_paginator}">
                                            <div
                                                class="custom-control custom-checkbox"
                                                ng-class="{'has-error': errors.use_paginator}">

                                                <input type="checkbox"
                                                       class="custom-control-input"
                                                       ng-true-value="1"
                                                       ng-false-value="0"
                                                       id="use_paginator"
                                                       ng-model="post.Automap.use_paginator">
                                                <label class="custom-control-label" for="use_paginator">
                                                    <?php echo __('Use pagination'); ?>
                                                </label>
                                            </div>
                                            <div class="help-block">
                                                <?php echo __('Will may decrease loading performance if disabled.') ?>
                                            </div>
                                        </div>


                                        <div class="form-group required" ng-class="{'has-error': errors.font_size}">
                                            <label class="control-label" for="FontsizeSelect">
                                                <?php echo __('Icon size'); ?>
                                            </label>
                                            <select
                                                id="FontsizeSelect"
                                                data-placeholder="<?php echo __('Please choose'); ?>"
                                                class="form-control"
                                                chosen="{}"
                                                ng-model="post.Automap.font_size">
                                                <option value="1"><?php echo __('Smallest'); ?></option>
                                                <option value="2"><?php echo __('Smaller'); ?></option>
                                                <option value="3"><?php echo __('Small'); ?></option>
                                                <option value="4"><?php echo __('Normal'); ?></option>
                                                <option value="5"><?php echo __('Big'); ?></option>
                                                <option value="6"><?php echo __('Bigger'); ?></option>
                                                <option value="7"><?php echo __('Biggest'); ?></option>
                                            </select>
                                            <div ng-show="post.Automap.font_size < 1" class="warning-glow">
                                                <?php echo __('Please select a icon size.'); ?>
                                            </div>
                                            <div ng-repeat="error in errors.font_size">
                                                <div class="help-block text-danger">{{ error }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="card">
                                    <div class="card-header">
                                        <i class="fa fa-filter"></i>
                                        <?php echo __('Icon Preview'); ?>
                                    </div>
                                    <div class="card-body">
                                        <div class="row"
                                             ng-if="post.Automap.show_label === 1 && post.Automap.group_by_host === 1">

                                            <div class="col-lg-12 padding-top-20">
                                                <h3 class="margin-bottom-5">
                                                    <i class="fa fa-desktop"></i>
                                                    <strong>
                                                        <?php echo __('Example host'); ?>
                                                    </strong>
                                                </h3>
                                            </div>

                                            <div class="col-lg-6 ellipsis" ng-style="getFontsize();">
                                                <i class="fa fa-square up"></i>
                                                <span
                                                    title="<?php echo __('Example host'); ?>/<?php echo __('Service 1'); ?>">
                                                <?php echo __('Example host'); ?>/<?php echo __('Service 1'); ?>
                                            </span>
                                            </div>
                                            <div class="col-lg-6 ellipsis" ng-style="getFontsize();">
                                                <i class="fa fa-square critical"></i>
                                                <span
                                                    title="<?php echo __('Example host'); ?>/<?php echo __('Service 2'); ?>">
                                                <?php echo __('Example host'); ?>/<?php echo __('Service 2'); ?>
                                            </span>
                                            </div>
                                        </div>

                                        <div class="row"
                                             ng-if="post.Automap.show_label === 0 && post.Automap.group_by_host === 1">

                                            <div class="col-lg-12 padding-top-20">
                                                <h3 class="margin-bottom-5">
                                                    <i class="fa fa-desktop"></i>
                                                    <strong>
                                                        <?php echo __('Example host'); ?>
                                                    </strong>
                                                </h3>
                                            </div>

                                            <div class="col-lg-12 ellipsis" ng-style="getFontsize();">
                                                <i class="fa fa-square up"
                                                   title="<?php echo __('Example host'); ?>/<?php echo __('Service 1'); ?>"></i>
                                                <i class="fa fa-square critical"
                                                   title="<?php echo __('Example host'); ?>/<?php echo __('Service 2'); ?>"></i>
                                            </div>
                                        </div>

                                        <div class="row"
                                             ng-if="post.Automap.show_label === 0 && post.Automap.group_by_host === 0">

                                            <div class="col-lg-12 ellipsis padding-top-20" ng-style="getFontsize();">
                                                <i class="fa fa-square up"
                                                   title="<?php echo __('Example host'); ?>/<?php echo __('Service 1'); ?>"></i>
                                                <i class="fa fa-square critical"
                                                   title="<?php echo __('Example host'); ?>/<?php echo __('Service 2'); ?>"></i>
                                            </div>
                                        </div>

                                        <div class="row"
                                             ng-if="post.Automap.show_label === 1 && post.Automap.group_by_host === 0">

                                            <div class="col-lg-6 ellipsis padding-top-20" ng-style="getFontsize();">
                                                <i class="fa fa-square up"></i>
                                                <span
                                                    title="<?php echo __('Example host'); ?>/<?php echo __('Service 1'); ?>">
                                                <?php echo __('Example host'); ?>/<?php echo __('Service 1'); ?>
                                            </span>
                                            </div>
                                            <div class="col-lg-6 ellipsis padding-top-20" ng-style="getFontsize();">
                                                <i class="fa fa-square critical"></i>
                                                <span
                                                    title="<?php echo __('Example host'); ?>/<?php echo __('Service 2'); ?>">
                                                <?php echo __('Example host'); ?>/<?php echo __('Service 2'); ?>
                                            </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card margin-top-10">
                            <div class="card-body">
                                <div class="float-right">
                                    <label>
                                        <input type="checkbox" ng-model="data.createAnother">
                                        <?php echo __('Create another'); ?>
                                    </label>
                                    <button class="btn btn-primary"
                                            type="submit"><?php echo __('Create auto map'); ?></button>
                                    <a back-button href="javascript:void(0);" fallback-state='AutomapsIndex'
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
