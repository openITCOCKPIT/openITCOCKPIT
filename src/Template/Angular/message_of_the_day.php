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
<div class="btn-group mr-2" role="group" aria-label="" ng-if="messageOtd">
    <button class="btn btn-outline-{{messageOtd.style}} pulse-{{messageOtd.style}}"
            ng-click="openMessageOtdModal()"
            data-original-title="<?= __('Message of the day!'); ?>"
            data-placement="bottom"
            rel="tooltip"
            data-container="body" ng-show="messageOtdAvailable">
        <i class="fas fa-bullhorn"></i>
    </button>
</div>

<div id="angularMessageOtdModal" class="modal" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-{{messageOtd.style}}">
                <h5 class="modal-title text-white">
                    <?= __('Message of the day!'); ?>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="fa fa-times"></i></span>
                </button>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <div class="alert bg-transparent" role="alert">
                        <div class="d-flex">
                            <div class="alert-icon">
                                <span class="icon-stack icon-stack-lg">
                                    <i class="base base-12 icon-stack-3x opacity-100 color-{{messageOtd.style}}-500"></i>
                                    <i class="fas fa-info icon-stack-1x opacity-100 color-white margin-bottom-2"
                                       ng-show="messageOtd.style == 'primary' || messageOtd.style == 'info'"></i>
                                    <i class="fas fa-check icon-stack-1x opacity-100 color-white margin-bottom-2"
                                       ng-show="messageOtd.style == 'success'"></i>
                                    <i class="fas fa-exclamation icon-stack-1x opacity-100 color-white margin-bottom-2"
                                       ng-show="messageOtd.style == 'warning' || messageOtd.style == 'danger'"></i>
                                </span>
                            </div>
                            <div class="flex-1 padding-left-15">
                                <div ng-if="messageOtd.title"
                                     class="h4 text-{{messageOtd.style}} title-border title-border-bottom-{{messageOtd.style}}">
                                    {{messageOtd.title}}
                                </div>
                                <div class="italic">
                                    {{messageOtd.description}}
                                </div>
                                <br>
                                <div style="word-wrap: break-word;"
                                     ng-bind-html="messageOtd.contentHtml | trustAsHtml">
                                    {{messageOtd.contentHtml}}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
