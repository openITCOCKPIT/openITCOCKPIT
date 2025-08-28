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
        <a ui-sref="ServicegroupsIndex">
            <i class="fa fa-cogs"></i> <?php echo __('Service groups'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <i class="fas fa-chain"></i> <?php echo __('Append services to service group'); ?>
    </li>
</ol>

<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?php echo __('Append services'); ?>
                    <span class="fw-300"><i><?php echo __('to service group'); ?></i></span>
                </h2>
                <div class="panel-toolbar">
                    <?php if ($this->Acl->hasPermission('index', 'servicegroups')): ?>
                        <a back-button href="javascript:void(0);" fallback-state='ServicegroupsIndex'
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
                             {objectName : '<?php echo __('Service group'); ?>' , message: '<?php echo __('saved successfully'); ?>'}">

                        <div class="form-group required">
                            <label class="control-label" for="servicegroupSelect">
                                <?php echo __('Service group'); ?>
                            </label>
                            <select
                                id="servicegroupSelect"
                                data-placeholder="<?php echo __('Please choose'); ?>"
                                class="form-control"
                                chosen="servicegroups"
                                callback="loadServicegroups"
                                ng-options="servicegroups.key as servicegroups.value for servicegroups in servicegroups"
                                ng-model="post.Servicegroup.id">
                            </select>
                            <div class="text-danger" ng-show="errors.servicegroup">
                                {{errors.servicegroup}}
                            </div>
                        </div>

                        <div class="col-xs-12 col-sm-12 col-md-12 col-lg-8">
                            <div class="text-info">
                                <i class="fa fa-info-circle"></i>
                                <?php echo __('Please notice:'); ?>
                                <?php echo __('Services that could not be assigned to the selected service group due to container permissions, will be removed automatically'); ?>
                            </div>
                        </div>

                        <div class="card margin-top-10">
                            <div class="card-body">
                                <div class="float-right">
                                    <button class="btn btn-primary" type="submit">
                                        <?php echo __('Append to service group'); ?>
                                    </button>
                                    <?php if ($this->Acl->hasPermission('index', 'servicegroups')): ?>
                                        <a back-button href="javascript:void(0);" fallback-state='ServicegroupsIndex'
                                           class="btn btn-default">
                                            <?php echo __('Cancel'); ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
