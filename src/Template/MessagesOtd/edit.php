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
        <a ui-sref="MessagesOTDIndex">
            <i class="fas fa-bullhorn"></i> <?php echo __('Messages of the day'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <i class="fas fa-pencil-alt"></i> <?php echo __('Edit'); ?>
    </li>
</ol>

<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?php echo __('Edit new message of the day'); ?>
                </h2>
                <div class="panel-toolbar">
                    <?php if ($this->Acl->hasPermission('index', 'messagesOtd')): ?>
                        <a back-button href="javascript:void(0);" fallback-state='MessagesOTDIndex'
                           class="btn btn-default btn-xs mr-1 shadow-0">
                            <i class="fas fa-long-arrow-alt-left"></i> <?php echo __('Back'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <form ng-submit="submit();" class="form-horizontal"
                          ng-init="successMessage=
            {objectName : '<?php echo __('Message of the day'); ?>' , message: '<?php echo __('created successfully'); ?>'}">

                        <div class="form-group required" ng-class="{'has-error': errors.title}">
                            <label class="control-label">
                                <?php echo __('Title'); ?>
                            </label>
                            <input
                                class="form-control"
                                type="text"
                                ng-model="post.MessagesOtd.title">
                            <div ng-repeat="error in errors.title">
                                <div class="help-block text-danger">{{ error }}</div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="control-label">
                                <?php echo __('Description'); ?>
                            </label>
                            <input
                                class="form-control"
                                type="text"
                                ng-model="post.MessagesOtd.description">
                        </div>

                        <div class="form-group required" ng-class="{'has-error': errors.date}">
                            <label class="control-label">
                                <?php echo __('Date'); ?>
                            </label>
                            <input
                                class="form-control"
                                type="text"
                                placeholder="<?= date('d.m.Y', time()); ?>"
                                ng-model="post.MessagesOtd.date">
                            <div ng-repeat="error in errors.date">
                                <div class="help-block text-danger">{{ error }}</div>
                            </div>
                        </div>


                        <div class="form-group" ng-class="{'required' : post.MessagesOtd.expire}">
                            <label class="control-label" for="Expiration">
                                <?php echo __('Expiration'); ?>
                            </label>
                            <div class="input-group">
                                <div class="input-group-prepend width-180">
                                    <button
                                        class="btn btn-primary text-white text-left btn-outline-secondary dropdown-toggle w-100"
                                        type="button" data-toggle="dropdown"
                                        aria-haspopup="true" aria-expanded="false">
                                        <i ng-class="(post.MessagesOtd.expire == true)?'far fa-clock':'fas fa-infinity'"></i>
                                        {{post.MessagesOtd.expire ? "<?= __('expiration duration'); ?>" : "<?= __('no expiration'); ?>"}}
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="javascript:void(0);"
                                           ng-click="post.MessagesOtd.expire = true">
                                            <i class="far fa-clock"></i>
                                            <?= __('expiration duration'); ?>
                                        </a>

                                        <a class="dropdown-item" href="javascript:void(0);"
                                           ng-click="post.MessagesOtd.expire = false; post.MessagesOtd.expiration_duration = null;">
                                            <i class="fas fa-infinity"></i>
                                            <?= __('no expiration'); ?>
                                        </a>
                                    </div>
                                </div>
                                <input class="form-control"
                                       type="number"
                                       placeholder="{{(post.MessagesOtd.expire)?1:''}}"
                                       ng-disabled="!post.MessagesOtd.expire"
                                       ng-model="post.MessagesOtd.expiration_duration"
                                       min="1"
                                       id="Expiration">
                                <div class="input-group-append">
                                    <span class="input-group-text" id="basic-addon2">
                                        <?= __('in days'); ?>
                                    </span>
                                </div>
                            </div>
                            <div ng-repeat="error in errors.expiration_duration">
                                <div class="help-block text-danger">{{ error }}</div>
                            </div>
                            <div class="help-block">
                                <?= __('Leave this field blank to receive a permanent message of the day. The message will be displayed from the defined date. For temporary messages, set the expiration time in days.'); ?>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label">
                                <?= __('Filter for user groups (optional)'); ?>
                            </label>
                            <select data-placeholder="<?php echo __('Please choose'); ?>"
                                    class="form-control"
                                    ng-options="usergroup.id as usergroup.name for usergroup in usergroups"
                                    chosen="{usergroups}"
                                    multiple
                                    ng-model="post.MessagesOtd.usergroups._ids">
                            </select>
                            <div class="help-block">
                                <?= __('Select user groups for restricted visibility. Leave this field blank to avoid excluding users.'); ?>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox"
                                       id="NotifyUsers"
                                       name="checkbox"
                                       class="custom-control-input"
                                       ng-false-value="0"
                                       ng-true-value="1"
                                       ng-model="post.MessagesOtd.notify_users">
                                <label class="custom-control-label"
                                       for="NotifyUsers"><?php echo __('Notify users'); ?>
                                    <div class="icon-stack margin-bottom-5">
                                        <i class="fas fa-user opacity-100 "></i>
                                        <i class="fas fa-envelope opacity-100 fa-xs text-primary cornered cornered-lr"></i>
                                    </div>
                                </label>
                            </div>
                            <div class="help-block">
                                <?= __('If activated, the system sends notifications via email with the current content of the "Message of the day" to affected users'); ?>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label">
                                <?php echo __('Style'); ?>
                            </label>
                            <select
                                data-placeholder="<?php echo __('Please choose'); ?>"
                                class="form-control"
                                chosen="{}"
                                ng-model="post.MessagesOtd.style">
                                <option value="info"><?= __('Info'); ?></option>
                                <option value="primary"><?= __('Primary'); ?></option>
                                <option value="success"><?= __('Success'); ?></option>
                                <option value="warning"><?= __('Warning'); ?></option>
                                <option value="danger"><?= __('Danger'); ?></option>
                            </select>
                        </div>

                        <div class="form-group required">
                            <label class="control-label">
                                <?php echo __('Text'); ?>
                            </label>
                            <div ng-repeat="error in errors.content">
                                <div class="help-block text-danger">{{ error }}</div>
                            </div>
                        </div>
                        <div class="row padding-top-10">
                            <div class="col-xl-6">
                                <div class="panel" ng-class="{'has-error-div': errors.content}">
                                    <div class="panel-hdr">
                                        <div class="panel-toolbar" style="width: 100%;">
                                            <div class="mr-auto d-flex" role="menu">
                                                <div class="dropdown">
                                                    <button type="button"
                                                            class="btn btn-xs btn-default"
                                                            id="currentColor"
                                                            data-toggle="dropdown" aria-haspopup="true"
                                                            aria-expanded="false"
                                                            color="#404040">
                                                        <i class="fas fa-palette"></i>
                                                    </button>
                                                    <div class="dropdown-menu flex-wrap"
                                                         style="width: 10.2rem; padding: 0.5rem"
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
                                                            id="docuFontSize" data-toggle="dropdown"
                                                            aria-haspopup="true"
                                                            aria-expanded="false">
                                                        <i class="fa fa-font"></i>
                                                        <?php echo __('Font size'); ?>
                                                    </button>
                                                    <div class="dropdown-menu" aria-labelledby="docuFontSize">
                                                        <a class="dropdown-item" href="javascript:void(0);"
                                                           select-fsize="true"
                                                           fsize="xx-small"><?php echo __('Smallest'); ?></a>
                                                        <a class="dropdown-item" href="javascript:void(0);"
                                                           select-fsize="true"
                                                           fsize="x-small"><?php echo __('Smaller'); ?></a>
                                                        <a class="dropdown-item" href="javascript:void(0);"
                                                           select-fsize="true"
                                                           fsize="small"><?php echo __('Small'); ?></a>
                                                        <a class="dropdown-item" href="javascript:void(0);"
                                                           select-fsize="true"
                                                           fsize="large"><?php echo __('Big'); ?></a>
                                                        <a class="dropdown-item" href="javascript:void(0);"
                                                           select-fsize="true"
                                                           fsize="x-large"><?php echo __('Bigger'); ?></a>
                                                        <a class="dropdown-item" href="javascript:void(0);"
                                                           select-fsize="true"
                                                           fsize="xx-large"><?php echo __('Biggest'); ?></a>
                                                    </div>
                                                </div>
                                                <span class="padding-left-10"></span>
                                                <a href="javascript:void(0);" class="btn btn-default btn-xs btn-icon"
                                                   wysiwyg="true"
                                                   task="bold"><i class="fa fa-bold"></i></a>
                                                <a href="javascript:void(0);" class="btn btn-default btn-xs btn-icon"
                                                   wysiwyg="true"
                                                   task="italic"><i class="fa fa-italic"></i></a>
                                                <a href="javascript:void(0);" class="btn btn-default btn-xs btn-icon"
                                                   wysiwyg="true"
                                                   task="underline"><i class="fa fa-underline"></i></a>
                                                <span class="padding-left-10"></span>
                                                <a href="javascript:void(0);" class="btn btn-default btn-xs btn-icon"
                                                   wysiwyg="true"
                                                   task="left"><i class="fa fa-align-left"></i></a>
                                                <a href="javascript:void(0);" class="btn btn-default btn-xs btn-icon"
                                                   wysiwyg="true"
                                                   task="center"><i class="fa fa-align-center"></i></a>
                                                <a href="javascript:void(0);" class="btn btn-default btn-xs btn-icon"
                                                   wysiwyg="true"
                                                   task="right"><i class="fa fa-align-right"></i></a>
                                                <a href="javascript:void(0);" class="btn btn-default btn-xs btn-icon"
                                                   wysiwyg="true"
                                                   task="justify"><i class="fa fa-align-justify"></i></a>
                                                <span class="padding-left-10"></span>
                                                <a href="javascript:void(0);" class="btn btn-default btn-xs btn-icon"
                                                   data-toggle="modal"
                                                   data-target="#hyerlinkModal" ng-click="prepareHyperlinkSelection()"
                                                   id="insert-link"><i
                                                        class="fa fa-link"></i></a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="panel-container show">
                                        <div class="panel-content">
                                            <textarea class="form-control" ng-model="post.MessagesOtd.content"
                                                      style="width: 100%; height: 200px;" id="motdcontent">

                                            </textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div>
                                    <div class="panel-hdr">
                                        <div class="panel-toolbar" style="width: 100%;">
                                            <div class="mr-auto d-flex text-primary bold" role="menu">
                                                <?= __('Preview'); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="panel-container show">
                                        <div class="panel-content">
                                            <div class="alert border-{{post.MessagesOtd.style}} bg-transparent"
                                                 role="alert">
                                                <div class="d-flex">
                                                    <div class="alert-icon">
                                                        <span class="icon-stack icon-stack-lg">
                                                            <i class="base base-12 icon-stack-3x opacity-100 color-{{post.MessagesOtd.style}}-500"></i>
                                                            <i class="fas fa-info icon-stack-1x opacity-100 color-white margin-bottom-2"
                                                               ng-show="post.MessagesOtd.style == 'primary' || post.MessagesOtd.style == 'info'"></i>
                                                            <i class="fas fa-check icon-stack-1x opacity-100 color-white margin-bottom-2"
                                                               ng-show="post.MessagesOtd.style == 'success'"></i>
                                                            <i class="fas fa-exclamation icon-stack-1x opacity-100 color-white margin-bottom-2"
                                                               ng-show="post.MessagesOtd.style == 'warning' || post.MessagesOtd.style == 'danger'"></i>
                                                        </span>
                                                    </div>
                                                    <div class="flex-1 padding-left-15">
                                                        <div ng-if="post.MessagesOtd.title"
                                                             class="h4 text-{{post.MessagesOtd.style}} title-border title-border-bottom-{{post.MessagesOtd.style}}">
                                                            {{post.MessagesOtd.title}}
                                                        </div>
                                                        <div class="italic">
                                                            {{post.MessagesOtd.description}}
                                                        </div>
                                                        <br>
                                                        <div class="text-break"
                                                             ng-bind-html="motdcontentPreview | trustAsHtml">
                                                            {{motdcontentPreview}}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-body">
                                <div class="float-right">
                                    <button class="btn btn-primary" ng-show="post.MessagesOtd.notify_users"
                                            type="submit">
                                        <?php echo __('Update message of the day and notify users'); ?>
                                    </button>
                                    <button class="btn btn-primary" ng-hide="post.MessagesOtd.notify_users"
                                            type="submit">
                                        <?php echo __('Update message of the day'); ?>
                                    </button>
                                    <a back-button href="javascript:void(0);" fallback-state='MessagesOTDIndex'
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
