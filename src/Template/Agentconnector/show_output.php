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
<ol class="breadcrumb page-breadcrumb">
    <li class="breadcrumb-item">
        <a ui-sref="DashboardsIndex">
            <i class="fa fa-home"></i> <?php echo __('Home'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <a ui-sref="AgentconnectorsWizard">
            <i class="fa fa-user-secret"></i> <?php echo __('openITCOCKPIT Agent'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <i class="fab fa-js"></i> <?php echo __('Output'); ?>
    </li>
</ol>

<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?= __('openITCOCKPIT Agent Output of:'); ?>
                    <span class="fw-300" ng-show="pushAgentUuid">
                        <i>
                            <?= __('Agent: '); ?>{{pushAgentUuid}}
                        </i>
                    </span>
                    <span class="fw-300" ng-show="host">
                        <i>
                            {{host.name}} ({{host.address}})
                        </i>
                    </span>
                </h2>
                <div class="panel-toolbar">
                    <?php if ($this->Acl->hasPermission('overview', 'agentconnector')): ?>
                        <a back-button href="javascript:void(0);" fallback-state='AgentconnectorsPull'
                           class="btn btn-default btn-xs mr-1 shadow-0">
                            <i class="fas fa-long-arrow-alt-left"></i> <?php echo __('Back'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="row">
                <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
                    <div class="panel-container show">
                        <div class="panel-content">
                            <div class="card padding-bottom-20">
                                <div class="card-body">
                                    <fieldset>
                                        <legend class="fs-md fieldset-legend-border-bottom margin-top-10">
                                            <h4 class="required">
                                                <?= __('openITCOCKPIT Monitoring Agent Output'); ?>
                                            </h4>
                                        </legend>

                                        <div id="jsoneditor" class="margin-top-10"></div>
                                    </fieldset>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
