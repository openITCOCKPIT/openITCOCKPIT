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

<host-browser-menu
    ng-if="type === 'host' && hostBrowserMenuConfig"
    config="hostBrowserMenuConfig"
    last-load-date="0"
    root-copy-to-clipboard="rootCopyToClipboard"></host-browser-menu>

<service-browser-menu
    ng-if="type === 'service' && serviceBrowserMenuConfig"
    config="serviceBrowserMenuConfig"
    last-load-date="0"
    root-copy-to-clipboard="rootCopyToClipboard"></service-browser-menu>

<div class="row" ng-if="type === 'hosttemplate'">
    <div class="col-xs-12 col-sm-7 col-md-6 col-lg-6">
        <h1 class="status_headline">
            <?php echo __('Host template:'); ?> {{objectName}}
        </h1>
    </div>

    <div class="col-xs-12 col-sm-5 col-md-6 col-lg-6 margin-top-10">
        <div class="pull-right">
            <a back-button href="javascript:void(0);" fallback-state='HosttemplatesIndex'
               class="btn btn-default btn-xs mr-1 shadow-0">
                <i class="fas fa-long-arrow-alt-left"></i> <?php echo __('Back'); ?>
            </a>
        </div>
    </div>
</div>

<div class="row" ng-if="type === 'servicetemplate'">
    <div class="col-xs-12 col-sm-7 col-md-6 col-lg-6">
        <h1 class="status_headline">
            <?php echo __('Service template:'); ?> {{objectName}}
        </h1>
    </div>

    <div class="col-xs-12 col-sm-5 col-md-6 col-lg-6 margin-top-10">
        <div class="pull-right">
            <a back-button href="javascript:void(0);" fallback-state='ServicetemplatesIndex'
               class="btn btn-default btn-xs mr-1 shadow-0">
                <i class="fas fa-long-arrow-alt-left"></i> <?php echo __('Back'); ?>
            </a>
        </div>
    </div>
</div>


<div class="row tab-content">
    <!-- View tab -->
    <div ng-show="docu.displayView" class="col-xl-12 tab-pane active">
        <div class="panel">
            <div class="panel-hdr">
                <h2>
                    <?php echo __('Object'); ?>
                    <span class="fw-300"><i><?php echo __('documentation'); ?></i></span>
                </h2>
                <div class="panel-toolbar">
                    <div>
                        <?php echo __('Last update'); ?>: {{ lastUpdate }}
                    </div>&nbsp;
                    <button class="btn btn-xs btn-default mr-1 shadow-0" ng-show="allowEdit" ng-click="showEdit()">
                        <i class="fas fa-pencil-alt"></i> <?php echo __('Edit'); ?>
                    </button>
                </div>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <div ng-hide="docuExists">
                        <i class="fa fa-exclamation-triangle fa-lg txt-color-red"></i>
                        <span class="italic">
                            <?php echo __('No documentation has been written yet for this object. Click on "Edit" to start writing.'); ?>
                        </span>
                    </div>
                    <div ng-show="docuExists">
                        <div style="word-wrap: break-word;"
                             ng-bind-html="html | trustAsHtml">

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Edit tab -->
    <div ng-show="!docu.displayView" class="col-xl-12 tab-pane active">
        <div class="panel">
            <div class="panel-hdr">
                <div class="panel-toolbar" style="width: 100%;">
                    <div class="mr-auto d-flex" role="menu">

                        <div class="dropdown">
                            <button type="button"
                                    class="btn btn-xs btn-default"
                                    id="currentColor"
                                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                                    color="#404040">
                                <i class="fas fa-palette"></i>
                            </button>
                            <div class="dropdown-menu flex-wrap" style="width: 10.2rem; padding: 0.5rem"
                                 aria-labelledby="currentColor">

                                <?php
                                $colors = [
                                    '#00C851',
                                    '#ffbb33',
                                    '#CC0000',
                                    '#727b84',
                                    '#9ccc65',
                                    '#ffd54f',
                                    '#ff4444',
                                    '#33b5e5',
                                    '#007E33',
                                    '#FF8800',
                                    '#ff5722',
                                    '#0099CC',
                                    '#2E2E2E',
                                    '#4B515D',
                                    '#aa66cc',
                                    '#4285F4'
                                ];
                                ?>
                                <?php foreach ($colors as $color): ?>
                                    <button type="button"
                                            class="btn d-inline-block width-2 height-2 p-0 rounded-0 js-panel-color hover-effect-dot waves-effect waves-themed dropdown-item dashboardColorPickerBorder"
                                            data-panel-setstyle="bg-widget-statusGreen-gradient"
                                            select-color="true" color="<?= h($color) ?>"
                                            style="margin:1px; background-color:<?= h($color) ?>"></button>
                                <?php endforeach; ?>

                            </div>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-xs btn-default dropdown-toggle" type="button"
                                    id="docuFontSize" data-toggle="dropdown" aria-haspopup="true"
                                    aria-expanded="false">
                                <i class="fa fa-font"></i>
                                <?php echo __('Font size'); ?>
                            </button>
                            <div class="dropdown-menu" aria-labelledby="docuFontSize">
                                <a class="dropdown-item" href="javascript:void(0);" select-fsize="true"
                                   fsize="xx-small"><?php echo __('Smallest'); ?></a>
                                <a class="dropdown-item" href="javascript:void(0);" select-fsize="true"
                                   fsize="x-small"><?php echo __('Smaller'); ?></a>
                                <a class="dropdown-item" href="javascript:void(0);" select-fsize="true"
                                   fsize="small"><?php echo __('Small'); ?></a>
                                <a class="dropdown-item" href="javascript:void(0);" select-fsize="true"
                                   fsize="large"><?php echo __('Big'); ?></a>
                                <a class="dropdown-item" href="javascript:void(0);" select-fsize="true"
                                   fsize="x-large"><?php echo __('Bigger'); ?></a>
                                <a class="dropdown-item" href="javascript:void(0);" select-fsize="true"
                                   fsize="xx-large"><?php echo __('Biggest'); ?></a>
                            </div>
                        </div>
                        <span class="padding-left-10"></span>
                        <a href="javascript:void(0);" class="btn btn-default btn-xs btn-icon" wysiwyg="true"
                           task="bold"><i class="fa fa-bold"></i></a>
                        <a href="javascript:void(0);" class="btn btn-default btn-xs btn-icon" wysiwyg="true"
                           task="italic"><i class="fa fa-italic"></i></a>
                        <a href="javascript:void(0);" class="btn btn-default btn-xs btn-icon" wysiwyg="true"
                           task="underline"><i class="fa fa-underline"></i></a>
                        <span class="padding-left-10"></span>
                        <a href="javascript:void(0);" class="btn btn-default btn-xs btn-icon" wysiwyg="true"
                           task="left"><i class="fa fa-align-left"></i></a>
                        <a href="javascript:void(0);" class="btn btn-default btn-xs btn-icon" wysiwyg="true"
                           task="center"><i class="fa fa-align-center"></i></a>
                        <a href="javascript:void(0);" class="btn btn-default btn-xs btn-icon" wysiwyg="true"
                           task="right"><i class="fa fa-align-right"></i></a>
                        <a href="javascript:void(0);" class="btn btn-default btn-xs btn-icon" wysiwyg="true"
                           task="justify"><i class="fa fa-align-justify"></i></a>
                        <span class="padding-left-10"></span>
                        <a href="javascript:void(0);" class="btn btn-default btn-xs btn-icon" data-toggle="modal"
                           data-target="#hyerlinkModal" ng-click="prepareHyperlinkSelection()" id="insert-link"><i
                                class="fa fa-link"></i></a>
                    </div>
                    <div class="ml-auto" role="menu">
                        <button type="button" class="btn btn-default btn-xs" ng-click="showView()">
                            <i class="fa fa-times"></i> <?php echo __('Cancel'); ?>
                        </button>
                        <button type="button" class="btn btn-success btn-xs" ng-click="saveText()">
                            <i class="fa fa-save"></i> <?php echo __('Save'); ?>
                        </button>
                    </div>
                </div>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <div ng-class="{'has-error': errors.text}">
                        <textarea class="form-control" ng-model="bbcode"
                                  style="width: 100%; height: 200px;" id="docuText"></textarea>
                    </div>
                    <div ng-repeat="error in errors.text">
                        <div class="help-block text-danger">{{ error }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="hyerlinkModal" class="modal" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa fa-edit"></i>
                    <?php echo __('Insert hyperlink'); ?>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="fa fa-times"></i></span>
                </button>
            </div>
            <div class="modal-body">
                <div class="col-lg-12">
                    <label class="control-label" for="modalLinkUrl">
                        <?php echo __('URL'); ?>
                    </label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                                    <span class="input-group-text">
                                        <i class="icon-prepend fas fa-external-link-alt"></i>
                                    </span>
                        </div>
                        <input type="text"
                               class="form-control"
                               id="modalLinkUrl"
                               ng-model="docu.hyperlink"
                               placeholder="https://openitcockpit.io">
                    </div>
                </div>
                <div class="col-lg-12 margin-top-10">
                    <label class="control-label hintmark_red" for="modalLinkDescription">
                        <?php echo __('Description Text'); ?>
                    </label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                                    <span class="input-group-text">
                                        <i class="icon-prepend fa fa-tag"></i>
                                    </span>
                        </div>
                        <input type="text"
                               class="form-control"
                               placeholder="<?php echo __('Official page for openITCOCKPIT'); ?>"
                               ng-model="docu.hyperlinkDescription"
                               id="modalLinkDescription">
                    </div>
                </div>
                <div class="form-group col-lg-12 margin-top-10">
                    <div class="custom-control custom-checkbox  margin-bottom-10">

                        <input type="checkbox"
                               class="custom-control-input"
                               name="checkbox"
                               id="modalLinkNewTab">
                        <label class="custom-control-label" for="modalLinkNewTab">
                            <?php echo __('Open in new tab'); ?>
                        </label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" ng-click="insertWysiwygHyperlink()" data-dismiss="modal">
                    <?php echo __('Insert'); ?>
                </button>
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <?php echo __('Close'); ?>
                </button>
            </div>
        </div>
    </div>
</div>
