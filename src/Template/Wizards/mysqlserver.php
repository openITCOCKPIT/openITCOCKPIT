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
        <a ui-sref="WizardsIndex">
            <i class="fa-solid fa-wand-magic-sparkles"></i> <?php echo __('Wizards'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <i class="fa-solid fa-wand-magic-sparkles"></i> <?php echo __('MySQL'); ?>
    </li>
</ol>

<div class="row">
    <div class="col-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?php echo __('Configuration Wizard: MySQL Server'); ?>
                </h2>
            </div>
            <div class="panel-container show">
                <div class="panel-content fuelux">

                    <form ng-submit="submit();" class="form-horizontal">

                        <div class="wizard">
                            <ul class="nav nav-tabs step-anchor">
                                <li class="active">
                                    <span class="badge badge-info">
                                        <i class="fa fa-info"></i>
                                    </span>
                                    <?php echo __('MySQL Information'); ?>
                                    <span class="chevron"></span>
                                </li>
                            </ul>
                        </div>


                        <div class="step-content">
                            <div class="card margin-top-20 margin-bottom-10">
                                <div class="card-body">
                                    <fieldset class="padding-bottom-20">
                                        <legend class="fs-md fieldset-legend-border-bottom">
                                            <h4>
                                                <?= __('MySQL Server'); ?>
                                            </h4>
                                        </legend>

                                        <div class="accordion accordion-hover padding-bottom-10">
                                            <div class="card border-bottom">
                                                <div class="card-header">
                                                    <a href="javascript:void(0);" class="card-title collapsed"
                                                       data-toggle="collapse" data-target="#accordion-body-1"
                                                       aria-expanded="false">
                                                        <i class="fas fa-life-ring width-2"></i>
                                                        <?= __('MySQL configuration help'); ?>
                                                        <span class="ml-auto">
                                                            <span class="collapsed-reveal">
                                                                <i class="fas fa-chevron-up"></i>
                                                            </span>
                                                            <span class="collapsed-hidden">
                                                                <i class="fas fa-chevron-down"></i>
                                                            </span>
                                                        </span>
                                                    </a>
                                                </div>
                                                <div id="accordion-body-1" class="collapse">
                                                    <div class="card-body padding-10">

                                                        <ol>
                                                            <li>
                                                                <div>
                                                                    <?= __('To enable external databases access you have to change the {0} of your MySQL server.', '<code>bind-address</code>'); ?>
                                                                    <div
                                                                        class="alert border-danger bg-transparent text-danger">
                                                                        <?= __('Changing the bind address can be a potential security issue!'); ?>
                                                                    </div>

                                                                    <?= __(
                                                                        'The bind-address is configured in my.cnf. Most likely this file is located at {0} or {1}',
                                                                        '<code>/etc/mysql/mysql.conf.d/mysqld.cnf</code>',
                                                                        '<code>/etc/mysql/my.cnf</code>'
                                                                    ); ?>
                                                                </div>
                                                            </li>

                                                            <li>
                                                                <div>
                                                                    <?= __('Change the bind-address from 127.0.0.1 to the external address of the system or use 0.0.0.0 to bind to all interfaces.'); ?>
                                                                    <pre>bind-address = 0.0.0.0</pre>
                                                                </div>
                                                            </li>

                                                            <li>
                                                                <div>
                                                                    <?= __('Restart the MySQL server to apply the changes.'); ?>
                                                                    <pre>systemctl restart mysql.service</pre>
                                                                </div>
                                                            </li>

                                                            <li>
                                                                <div>
                                                                    <?= __('Create a new MySQL user for {0}', h($systemname)); ?>
                                                                    <pre>CREATE USER 'monitoring'@'<?= h($_SERVER['SERVER_ADDR']); ?>' IDENTIFIED BY 'secure_password';
GRANT SELECT, SHOW VIEW ON *.* TO 'monitoring'@'<?= h($_SERVER['SERVER_ADDR']); ?>';</pre>
                                                                    <?= __(
                                                                        'Please replace {0} with the address of the {1} server and set a secure password',
                                                                        '<i>' . h($_SERVER['SERVER_ADDR']) . '</i>',
                                                                        h($systemname)
                                                                    ); ?>
                                                                </div>
                                                            </li>

                                                        </ol>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group required" ng-class="{'has-error': errors.username}">
                                            <label class="control-label">
                                                <?php echo __('Username'); ?>
                                            </label>
                                            <input
                                                id="UserName"
                                                class="form-control"
                                                type="text"
                                                ng-model="post.username">
                                            <div ng-repeat="error in errors.username">
                                                <div class="help-block text-danger">{{ error }}</div>
                                            </div>
                                        </div>
                                        <div class="form-group required" ng-class="{'has-error': errors.password}">
                                            <label class="control-label">
                                                <?php echo __('Password'); ?>
                                            </label>
                                            <input
                                                id="UserName"
                                                class="form-control"
                                                type="password"
                                                ng-model="post.password">
                                            <div ng-repeat="error in errors.password">
                                                <div class="help-block text-danger">{{ error }}</div>
                                            </div>
                                        </div>
                                        <div class="form-group required" ng-class="{'has-error': errors.database}">
                                            <label class="control-label">
                                                <?php echo __('Database'); ?>
                                            </label>
                                            <input
                                                id="UserName"
                                                class="form-control"
                                                type="text"
                                                ng-model="post.database">
                                            <div ng-repeat="error in errors.database">
                                                <div class="help-block text-danger">{{ error }}</div>
                                            </div>
                                        </div>
                                    </fieldset>
                                    <fieldset>
                                        <legend class="fs-md fieldset-legend-border-bottom">
                                            <h4>
                                                <?= __('MySQL services'); ?>
                                            </h4>

                                            <div ng-repeat="error in errors.services">
                                                <h5 class="text-danger">{{ error }}</h5>
                                            </div>

                                        </legend>
                                        <wizard-filter></wizard-filter>
                                        <ul class="no-padding">
                                            <ol class="padding-bottom-20 padding-left-0"
                                                ng-repeat="service in (filteredItems = (post.services | filterTagsinput:search.name))">
                                                <div class="form-group">
                                                    <div class="custom-control custom-checkbox">
                                                        <input type="checkbox"
                                                               id="{{service.name}}"
                                                               class="custom-control-input"
                                                               name="checkbox"
                                                               ng-model="service.createService">
                                                        <label class="custom-control-label custom-control-label-ok"
                                                               for="{{service.name}}">
                                                            {{service.name}}
                                                            <span class="help-block italic">
                                                                {{service.description}}
                                                            </span>
                                                        </label>
                                                    </div>
                                                </div>
                                                <div
                                                    class="form-group {{detectColor(commandargument.commandargument.human_name)}}"
                                                    ng-repeat="commandargument in service.servicecommandargumentvalues">
                                                    {{commandargument.commandargument.human_name}}
                                                    <input class="form-control"
                                                           type="text"
                                                           ng-disabled="!service.createService"
                                                           ng-model="commandargument.value">
                                                </div>
                                            </ol>
                                        </ul>
                                    </fieldset>
                                </div>
                            </div>
                        </div>
                        <div class="card margin-top-10">
                            <div class="card-body">
                                <div class="float-right">
                                    <button type="submit" class="btn btn-primary" ng-disabled="disableSubmit">
                                        <span>
                                            <i class="fa fa-spinner fa-spin" ng-show="disableSubmit"></i>
                                        </span>
                                        <?php echo __('Create'); ?>
                                    </button>
                                    <a back-button href="javascript:void(0);" fallback-state='WizardsIndex'
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
