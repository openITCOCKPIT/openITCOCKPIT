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
    <li class="breadcrumb-item">
        <i class="fa fa-link"></i> <?php echo __('Assignments'); ?>
    </li>
</ol>

<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?php echo __('Assignments'); ?>
                    <span class="fw-300"><i>
                            <?php echo __('overview'); ?></i>
                    </span>
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
                    <div class="frame-wrap">
                        <table class="table table-striped m-0 table-bordered table-hover table-sm">
                            <thead>
                            <tr>
                                <th></th>
                                <th class="no-sort">
                                    <?php echo __('Wizard title'); ?>
                                </th>
                                <th>
                                    <?php echo __('Description'); ?>
                                </th>
                                <th>
                                    <?php echo __('Assignments necessary'); ?>
                                </th>
                                <th class="no-sort text-center">
                                    <i class="fa fa-cog"></i>
                                </th>
                            </tr>
                            </thead>

                            <tbody>
                            <tr ng-repeat="wizard in wizards" ng-show="filterByCategory(wizard.category)">
                                <td align="center">
                                    <div class="wizard-logo-image-small">
                                        <img src="/img/wizards/{{wizard.image}}"/>
                                    </div>
                                </td>
                                <td>{{wizard.title}}</td>
                                <td>{{wizard.description}}</td>
                                <td>
                                    <span class="badge badge-danger ng-hide" ng-hide="wizard.necessity_of_assignment">
                                        <?= __('No'); ?>
                                    </span>
                                    <span class="badge badge-success" ng-show="wizard.necessity_of_assignment">
                                        <?= __('Yes'); ?>
                                    </span>
                                </td>
                                <td class="width-50">
                                    <div class="btn-group btn-group-xs" role="group"
                                         ng-show="wizard.necessity_of_assignment">
                                        <?php if ($this->Acl->hasPermission('edit', 'wizards')): ?>
                                            <a ui-sref="WizardsEdit({uuid: wizard.uuid, typeId: wizard.type_id})"
                                               class="btn btn-default btn-lower-padding">
                                                <i class="fa fa-cog"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="javascript:void(0);"
                                               class="btn btn-default btn-lower-padding disabled">
                                                <i class="fa fa-cog"></i></a>
                                        <?php endif; ?>
                                        <button type="button"
                                                class="btn btn-default dropdown-toggle btn-lower-padding"
                                                data-toggle="dropdown">
                                            <i class="caret"></i>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-right">
                                            <?php if ($this->Acl->hasPermission('edit', 'wizards')): ?>
                                                <a ui-sref="WizardsEdit({uuid: wizard.uuid, typeId: wizard.type_id })"
                                                   class="dropdown-item">
                                                    <i class="fa fa-cog"></i>
                                                    <?php echo __('Edit'); ?>
                                                </a>
                                            <?php endif; ?>

                                        </div>
                                    </div>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                        <div class="margin-top-10" ng-show="wizards.length == 0">
                            <div class="text-center text-danger italic">
                                <?php echo __('No entries match the selection'); ?>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
