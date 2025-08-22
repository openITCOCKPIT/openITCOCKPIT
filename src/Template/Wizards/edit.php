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
                            <?php echo __('edit service templates assignments'); ?></i>
                    </span>
                </h2>
                <div class="panel-toolbar">

                </div>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <form ng-submit="submit();" class="form-horizontal"
                          ng-init="successMessage=
            {objectName : '<?php echo __('Wizard assignments'); ?>' , message: '<?php echo __('updated successfully'); ?>'}">
                        <div class="row padding-10">
                            <div class="col-12">
                                <div class="row">
                                    <div class="col-1">
                                        <center>
                                            <div class="wizard-logo-image">
                                                <img src="/img/wizards/{{wizardAssignments.image}}"/>
                                            </div>
                                        </center>
                                    </div>
                                    <div class="col-11 wizard-assignments-border-left">
                                        <h3 class="text-primary">{{wizardAssignments.title}}</h3>
                                        <span class="help-block">{{wizardAssignments.description}}</span>
                                        <div class="form-group required padding-bottom-20"
                                             ng-class="{'has-error': errors.servicetemplates}">
                                            <label class="control-label padding-top-10" for="ServicetemplatesSelect">
                                                <?php echo __('Service templates'); ?>
                                            </label>
                                            <select
                                                id="ServicetemplatesSelect"
                                                data-placeholder="<?php echo __('Please choose'); ?>"
                                                class="form-control"
                                                chosen="servicetemplates"
                                                multiple
                                                ng-options="servicetemplate.key as servicetemplate.value for servicetemplate in servicetemplates"
                                                ng-model="wizardAssignments.servicetemplates._ids">
                                            </select>
                                            <div ng-repeat="error in errors.servicetemplates">
                                                <div class="help-block text-danger">{{ error }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>


                        <div class="card margin-top-10">
                            <div class="card-body">
                                <div class="float-right">
                                    <button class="btn btn-primary" type="submit">
                                        <?php echo __('Save assignments'); ?>
                                    </button>
                                    <a back-button href="javascript:void(0);" fallback-state='WizardsAssignments'
                                       class="btn btn-default">
                                        <?php echo __('Cancel'); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
