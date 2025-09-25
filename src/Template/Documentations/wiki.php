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
        <a ui-sref="DocumentationsWiki">
            <i class="fa fa-book"></i> <?php echo __('Documentation'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <i class="fa fa-list"></i> <?php echo __('Wiki'); ?>
    </li>
</ol>

<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?php echo __('Documentation'); ?>
                    <span class="fw-300"><i><?php echo __('wiki'); ?></i></span>
                </h2>
            </div>
            <div class="panel-container show">
                <div class="panel-content">

                    <div class="row">
                        <div class="col-lg-12">
                            <h2><?php echo __('New to openITCOCKPIT?'); ?></h2>
                        </div>
                        <div class="col-lg-12">
                            <?php echo __('We recommend every new user to read our'); ?>
                            <a href="https://docs.openitcockpit.io/en/beginners/beginners/" target="_blank">
                                <?php echo __('beginners guide.'); ?>
                            </a>
                            <?php echo __('This guide provides information about the basic concept of openITCOCKPIT and how to monitor your first host.'); ?>
                        </div>
                        <div class="col-lg-12 text-center">
                            <a href="https://docs.openitcockpit.io/en/beginners/beginners/" class="btn btn-default" target="_blank">
                                <i class="fas fa-external-link-alt"></i>
                                <?php echo __('Beginners guide.'); ?>
                            </a>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-12">
                            <h2><?php echo __('Technical documentation'); ?></h2>
                        </div>
                        <div class="col-lg-12">
                            <?php echo __('The technical documentation of openITCOCKPIT containers detailed information about additional Modules, background processes, the usage of the JSON-API and so on.'); ?>
                        </div>
                        <div class="col-lg-12 text-center">
                            <div class="btn-group" role="group">
                                <a href="https://openitcockpit.io/documentation/" class="btn btn-default" target="_blank">
                                    <i class="fas fa-external-link-alt"></i>
                                    <?php echo __('Documentation'); ?>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-12">
                            <h2><?php echo __('Additional help'); ?></h2>
                        </div>

                        <div class="col-lg-12"
                             ng-repeat="(key, documentation) in documentations.additional_help.children">
                            <h4><a href="javascript:void(0);"
                                   ng-click="showDocumentation('additional_help', key)">
                                    {{documentation.name}}
                                </a></h4>
                            <div>
                                <p class="description">
                                    {{documentation.description}}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<div id="angularDocumentationContentModal" class="modal" role="dialog">
    <div class="modal-dialog oitc-modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="{{currentDocumentation.icon}}"></i>
                    {{currentDocumentation.name}}
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="fa fa-times"></i></span>
                </button>
            </div>
            <div class="modal-body">
                <div ng-bind-html="currentDocumentationHtml | trustAsHtml"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <?php echo __('Close'); ?>
                </button>
            </div>
        </div>
    </div>
</div>
