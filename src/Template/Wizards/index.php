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
        <i class="fa-solid fa-wand-magic-sparkles"></i> <?php echo __('Wizards'); ?>
    </li>
</ol>


<div class="row">
    <div class="col-xl-12">
        <div id="widget-container" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?php echo __('Configuration Wizards'); ?>
                    <div class="badge border border-info text-info margin-left-10"><?= __('Beta'); ?></div>
                </h2>
                <div class="panel-toolbar">
                    <span class="padding-right-10">
                        <i class="fas fa-filter text-primary"></i> <?= __('Filter'); ?>
                    </span>
                    <div class="btn-group btn-group-xs margin-right-10">
                        <button class="btn"
                                ng-class="{'btn-primary': filter.Category.linux, 'btn-default': !filter.Category.linux}"
                                ng-click="filter.Category.linux=!filter.Category.linux">
                            <i class="fab fa-linux"></i> <?= ('Linux'); ?>
                        </button>
                        <button class="btn"
                                ng-class="{'btn-primary': filter.Category.windows, 'btn-default': !filter.Category.windows}"
                                ng-click="filter.Category.windows=!filter.Category.windows">
                            <i class="fab fa-windows"></i> <?= ('Windows'); ?>
                        </button>
                        <button class="btn"
                                ng-class="{'btn-primary': filter.Category.database, 'btn-default': !filter.Category.database}"
                                ng-click="filter.Category.database=!filter.Category.database">
                            <i class="fa fa-database"></i> <?= ('Database'); ?>
                        </button>
                        <button class="btn"
                                ng-class="{'btn-primary': filter.Category.mail, 'btn-default': !filter.Category.mail}"
                                ng-click="filter.Category.mail=!filter.Category.mail">
                            <i class="fas fa-mail-bulk"></i> <?= ('Email'); ?>
                        </button>
                        <button class="btn"
                                ng-class="{'btn-primary': filter.Category.network, 'btn-default': !filter.Category.network}"
                                ng-click="filter.Category.network=!filter.Category.network">
                            <i class="fa fa-sitemap"></i> <?= ('Network'); ?>
                        </button>
                        <button class="btn"
                                ng-class="{'btn-primary': filter.Category.docker, 'btn-default': !filter.Category.docker}"
                                ng-click="filter.Category.docker=!filter.Category.docker">
                            <i class="fab fa-docker"></i> <?= ('Docker'); ?>
                        </button>
                        <button class="btn"
                                ng-class="{'btn-primary': filter.Category.macos, 'btn-default': !filter.Category.macos}"
                                ng-click="filter.Category.macos=!filter.Category.macos">
                            <i class="fab fa-apple"></i> <?= ('macOS'); ?>
                        </button>
                        <button class="btn"
                                ng-class="{'btn-primary': filter.Category.virtualization, 'btn-default': !filter.Category.virtualization}"
                                ng-click="filter.Category.virtualization=!filter.Category.virtualization">
                            <i class="fas fa-cloud"></i> <?= ('Virtualization'); ?>
                        </button>
                        <button class="btn"
                                ng-class="{'btn-primary': filter.Category.hardware, 'btn-default': !filter.Category.hardware}"
                                ng-click="filter.Category.hardware=!filter.Category.hardware">
                            <i class="fas fa-server"></i> <?= ('Hardware'); ?>
                        </button>
                    </div>
                </div>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <div class="row wizard-card-group">
                        <!-- Real Wizards -->
                        <div class="col-xs-12 col-md-6 col-lg-4 col-xl-3" ng-repeat="wizard in wizards"
                             ng-show="filterByCategory(wizard.category)">
                            <div class="card mb-2 wizard-logo-card-height-150">
                                <div class="card-body">
                                    <a ui-sref="WizardHostConfiguration({state: wizard.state,
                                    selectedOs: wizard.selected_os, typeId: wizard.type_id, title: wizard.title})"
                                       class="d-flex flex-row align-items-start">
                                        <div class="wizard-logo-image">
                                            <img ng-src="/img/wizards/{{wizard.image}}"/>
                                        </div>
                                        <div class="ml-3">
                                            <strong class="font-md">
                                                {{wizard.title}}
                                            </strong>
                                            <br>
                                            {{wizard.description}}
                                        </div>
                                    </a>
                                </div>
                                <div class="card-footer no-border border-bottom">
                                    <div
                                        class="wizard-info-tags font-italic font-xs text-right padding-top-10 notify-label-small">
                                        <i class="fas fa-tags wizard-info-tags"></i>
                                        <i ng-repeat="category in wizard.category">{{category}}{{$last ? '' : ', '}}</i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Disabled placeholder wizards because of required Module is not loaded -->
                        <div class="col-xs-12 col-md-6 col-lg-4 col-xl-3" ng-repeat="possibleWizard in possibleWizards"
                             ng-show="filterByCategory(possibleWizard.category)">
                            <div class="card mb-2 wizard-logo-card-height-150 bg-placeholder-wizard">
                                <div class="card-body card-body-wizard">
                                    <span class="d-flex flex-row align-items-start">
                                        <div class="wizard-logo-image wizard-logo-image-placeholder">
                                            <img ng-src="/img/wizards/{{possibleWizard.image}}"/>
                                        </div>
                                        <div class="ml-3">
                                            <strong class="font-md">
                                                {{possibleWizard.title}}
                                            </strong>
                                            <br>
                                            {{possibleWizard.description}}
                                        </div>
                                    </span>
                                </div>
                                <div class="card-footer no-border border-bottom">

                                    <div class="row">
                                        <div class="col-xs-12 col-md-3">
                                            <span class="badge border margin-right-10 border-warning text-warning">
                                                <i class="fas fa-puzzle-piece"></i>
                                                <?= __('Require module'); ?>
                                            </span>
                                        </div>
                                        <div class="col-xs-12 col-md-9">
                                            <div
                                                class="wizard-info-tags font-italic font-xs text-right padding-top-10 notify-label-small">
                                                <i class="fas fa-tags wizard-info-tags"></i>
                                                <i ng-repeat="category in possibleWizard.category">{{category}}{{$last ?
                                                        '' : ', '}}</i>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
