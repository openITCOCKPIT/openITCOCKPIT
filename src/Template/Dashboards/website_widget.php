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
<div>
    <flippy vertical
            class="col-lg-12"
            flip="['custom:FLIP_EVENT_OUT']"
            flip-back="['custom:FLIP_EVENT_IN']"
            duration="800"
            timing-function="ease-in-out">

        <flippy-front class="fixFlippy">
            <a href="javascript:void(0);" class="btn btn-default btn-xs txt-color-blueDark" ng-click="hideConfig()" ng-hide="readOnly">
                <i class="fa fa-cog fa-sm"></i>
            </a>
            <div class="padding-10">
                <iframe-height-directive
                        height="widgetHeight"
                        url="widget.WidgetWebsite.url"
                ></iframe-height-directive>
            </div>
        </flippy-front>
        <flippy-back class="fixFlippy">
            <a href="javascript:void(0);" class="btn btn-default btn-xs txt-color-blueDark" ng-click="showConfig()">
                <i class="fa fa-eye fa-sm"></i>
            </a>
            <div class="row">
                <div class="col-12 padding-top-10">
                    <div class="alert border border-info bg-transparent text-info">
                        <i class="fas fa-info-circle"></i>
                        <?= __('To embed a website the {0} HTML tag is used.', '<code>&lt;iframe&gt;</code>'); ?>
                        <br />
                        <?= __('Most websites have restrictions for embedding and my not work. Depending on the security settings of the web browser mixing of http and https connections can cause issues.'); ?>
                    </div>
                </div>
                <div class="col-12">
                    <div class="form-group" style="width: 100%;">
                        <label class="control-label">
                            <?php echo __('URL'); ?>
                        </label>
                        <input type="text"
                               class="form-control"
                               ng-model="widget.WidgetWebsite.url"
                               ng-model-options="{debounce: 500}">
                    </div>
                </div>
            </div>
        </flippy-back>
    </flippy>
</div>
