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
 */

?>
<ol class="breadcrumb page-breadcrumb">
    <li class="breadcrumb-item">
        <a ui-sref="DashboardsIndex">
            <i class="fa fa-home"></i> <?php echo __('Home'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <a ui-sref="ServicesIndex">
            <i class="fa fa-desktop"></i> <?php echo __('Services'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <i class="fa fa-code-fork"></i> <?php echo __('Used by'); ?>
    </li>
</ol>
<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?php echo __('Service'); ?>
                    <span class="fw-300">
                        <i>
                            <strong>
                                »{{ service.name }}«
                            </strong>
                            <?php echo __('is used by'); ?>
                            {{ total }}
                            <?php echo __('objects.'); ?>
                        </i>
                    </span>
                </h2>
                <div class="panel-toolbar">
                    <?php if ($this->Acl->hasPermission('index', 'services')): ?>
                        <a back-button href="javascript:void(0);" fallback-state='ServicesIndex' class="btn btn-default btn-xs mr-1 shadow-0">
                            <i class="fas fa-long-arrow-alt-left"></i> <?php echo __('Back'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <div class="frame-wrap">
                        <table class="table table-striped m-0 table-bordered table-hover table-sm">
                            <tbody>
                            <tr ng-if="objects.Instantreports.length > 0">
                                <th class="bg-color-lightGray">
                                    <i class="fa fa-file-invoice"></i>
                                    <?php echo __('Instant reports'); ?> ({{objects.Instantreports.length}})
                                </th>
                            </tr>
                            <tr ng-repeat="instantreport in objects.Instantreports">
                                <td>
                                    <?php if ($this->Acl->hasPermission('edit', 'instantreports')): ?>
                                        <a ui-sref="InstantreportsEdit({id: instantreport.id})">
                                            {{ instantreport.name }}
                                        </a>
                                    <?php else: ?>
                                        {{ instantreport.name }}
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr ng-if="objects.Autoreports.length > 0">
                                <th class="bg-color-lightGray">
                                    <i class="fa fa-file-invoice"></i>
                                    <?php echo __('Autoreports'); ?> ({{objects.Autoreports.length}})
                                </th>
                            </tr>
                            <tr ng-repeat="autoreport in objects.Autoreports">
                                <td>
                                    <?php if ($this->Acl->hasPermission('edit', 'autoreports', 'AutoreportModule')): ?>
                                        <a ui-sref="AutoreportsEditStepTwo({id: autoreport.id})">
                                            {{ autoreport.name }}
                                        </a>
                                    <?php else: ?>
                                        {{ autoreport.name }}
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr ng-if="objects.Eventcorrelations.length > 0">
                                <th class="bg-color-lightGray">
                                    <i class="fas fa-sitemap fa-rotate-90"></i>
                                    <?php echo __('Eventcorrelations'); ?> ({{objects.Eventcorrelations.length}})
                                </th>
                            </tr>
                            <tr ng-repeat="eventcorrelation in objects.Eventcorrelations">
                                <td>
                                    <?php if ($this->Acl->hasPermission('editCorrelation', 'eventcorrelations', 'Eventcorrelationmodule')): ?>
                                        <a ui-sref="EventcorrelationsEditCorrelation({id: eventcorrelation.id,  highlightServiceId: service.id})">
                                            {{ eventcorrelation.name }}
                                        </a>
                                    <?php else: ?>
                                        {{ eventcorrelation.name }}
                                    <?php endif; ?>
                                </td>
                            </tr>

                            <!-- Maps -->
                            <tr ng-if="objects.Maps.length > 0">
                                <th class="bg-color-lightGray">
                                    <i class="fas fa-map-marker"></i>
                                    <?php echo __('Maps'); ?> ({{objects.Maps.length}})
                                </th>
                            </tr>
                            <tr ng-repeat="map in objects.Maps">
                                <td>
                                    <?php if ($this->Acl->hasPermission('edit', 'mapeditors', 'MapModule')): ?>
                                        <a ui-sref="MapeditorsEdit({id: map.id})">
                                            {{ map.name }}
                                        </a>
                                    <?php else: ?>
                                        {{ map.name }}
                                    <?php endif; ?>
                                </td>
                            </tr>

                            <!-- ServiceGroups -->
                            <tr ng-if="objects.Servicegroups.length > 0">
                                <th class="bg-color-lightGray">
                                    <i class="fa fa-cogs"></i>
                                    <?php echo __('Service Groups'); ?> ({{objects.Servicegroups.length}})
                                </th>
                            </tr>
                            <tr ng-repeat="servicegroup in objects.Servicegroups">
                                <td>
                                    <?php if ($this->Acl->hasPermission('edit', 'servicegroups')): ?>
                                        <a ui-sref="ServicegroupsEdit({id: servicegroup.id})">
                                            {{ servicegroup.name }}
                                        </a>
                                    <?php else: ?>
                                        {{ servicegroup.name }}
                                    <?php endif; ?>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                        <div class="row margin-top-10 margin-bottom-10" ng-show="total == 0">
                            <div class="col-lg-12 d-flex justify-content-center txt-color-red italic">
                                <?php echo __('This service is not used by any object'); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
