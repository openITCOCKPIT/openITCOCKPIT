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
<header id="header" class="page-header" role="banner">
    <menu-control></menu-control>
    <div class="search hidden-lg-down" top-search=""></div>
    <div class="ml-auto d-flex">

        <?php if ($hasSubscription === false): ?>
            <div class="header-icon padding-left-10 padding-right-5 hidden-md-down">
                <a class="btn btn-outline-danger waves-effect waves-themed"
                   href="https://openitcockpit.io/editions/"
                   target="_blank"
                   data-original-title="<?= __('No active subscription'); ?>"
                   data-placement="bottom"
                   rel="tooltip">
                    <?= __('No active subscription'); ?>
                </a>
            </div>
        <?php endif; ?>

        <?php if ($hasSubscription === true && $isCommunityEdition === true): ?>
            <div class="header-icon padding-left-10 padding-right-5 hidden-md-down">
                <a class="btn btn-outline-primary waves-effect waves-themed"
                   href="https://openitcockpit.io/editions/"
                   target="_blank"
                   data-original-title="<?= __('Community Edition'); ?>"
                   data-placement="bottom"
                   rel="tooltip">
                    <?= __('CE'); ?>
                </a>
            </div>
        <?php endif; ?>


        <div class="header-icon padding-left-5 hidden-md-down">
            <menustats></menustats>
        </div>
        <div class="header-icon hidden-md-down">
            <system-health></system-health>
        </div>
        <div class="header-icon hidden-md-down">
            <span id="global_ajax_loader" style="display: none;">
                <div class="spinner-border spinner-border-sm" role="status">
                    <span class="sr-only">
                        <?= __('Loading...'); ?>
                    </span>
                </div>
            </span>
        </div>

        <div class="header-icon">
            <span>
                <?php if ($exportRunningHeaderInfo === false): ?>
                    <a class="btn btn-default waves-effect waves-themed"
                       ui-sref="ExportsIndex"
                       sudo-server-connect=""
                       data-original-title="<?= __('Refresh monitoring configuration'); ?>"
                       data-placement="bottom"
                       rel="tooltip"
                       data-container="body">
                        <i class="fa fa-retweet"></i>
                    </a>
                <?php else: ?>
                    <a class="btn btn-default waves-effect waves-themed"
                       ui-sref="ExportsIndex"
                       export-status=""
                       data-original-title="<?= __('Refresh monitoring configuration'); ?>"
                       data-placement="bottom"
                       rel="tooltip"
                       data-container="body">
                        <i class="fa fa-retweet" ng-hide="exportRunning"></i>
                        <i class="fa fa-refresh fa-spin txt-color-red" ng-show="exportRunning"></i>
                    </a>
                <?php endif; ?>
            </span>
        </div>
        <div class="header-icon padding-left-5 hidden-md-down">
            <version-check></version-check>
        </div>
        <div class="header-icon padding-left-5">
            <message-otd></message-otd>
        </div>
        <div class="header-icon">
            <server-time></server-time>
        </div>

        <div class="header-icon btn-group input-group-btn input-group-prepend mr-2">
            <button type="button"
                    class="btn btn-default dropdown-toggle no-border"
                    data-toggle="dropdown" aria-expanded="false">
                <span class="<?= h($language['flag']) ?>"></span>
                <span class="caret"></span>
            </button>
            <ul class="dropdown-menu" style="line-height: 1.47;" change-language="">
                <?php foreach ($localeOptions as $localeOption): ?>
                    <li class="dropdown-item" ng-click="changeLanguage('<?= h($localeOption['i18n']) ?>')">
                        <a href="javascript:void(0)">
                            <span class="<?= h($localeOption['flag']) ?>"></span>
                            <?= h($localeOption['label']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="header-icon">
            <div class="btn-group mr-2" role="group" aria-label="">
                <a class="btn btn-default waves-effect waves-themed"
                   href="/users/logout"
                   data-original-title="<?= __('Sign out'); ?>"
                   data-placement="bottom"
                   rel="tooltip"
                   data-container="body">
                    <i class="fa fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
        <push-notifications></push-notifications>
    </div>
</header>

<script>
    $(document).ready(function(){
        setTimeout(function(){
            jQuery(document).find("[rel=tooltip]").tooltip();
        }, 150);
    });
</script>
