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

use itnovum\openITCOCKPIT\Core\Views\Logo;

/**
 * @var \App\View\AppView $this
 */

$logo = new Logo();
?>
<aside id="pageSidebar" class="page-sidebar">
    <div class="page-logo pointer" ui-sref="DashboardsIndex">
        <img src="<?= $logo->getHeaderLogoForHtml(); ?>" class="header-logo" alt="logo" aria-roledescription="logo"
             style="width: 28px;">
        <span class="page-logo-text mr-1">{{systemname}}</span>
        <span class="position-absolute text-white opacity-50 small pos-top pos-right mr-2 mt-n2"></span>
    </div>
    <!-- BEGIN PRIMARY NAVIGATION -->
    <nav id="js-primary-nav" class="primary-nav" role="navigation">
        <div class="nav-filter">
            <div class="position-relative">
                <input type="text" id="nav_filter_input"
                       placeholder="<?= __('Type to search'); ?>"
                       class="form-control"
                       tabindex="0" ng-keydown="navigate($event)">
                <a class="btn-primary btn-search-close js-waves-off" ng-click="clearAndCloseMenu()">
                    <i class="fa fa-chevron-up color-white"></i>
                </a>
            </div>
        </div>
        <div class="info-card">
            <img class="profile-image rounded-circle" alt="me" ng-src="{{userImage}}">
            <div class="info-card-text">
                <a class="d-flex align-items-center text-white" ui-sref="ProfileEdit">
                    <span class="text-truncate text-truncate-sm d-inline-block">
                        {{userFullName}}
                    </span>
                </a>
                <?php if ($hasRootPrivileges === true): ?>
                    <span class="d-inline-block text-truncate text-truncate-sm rootCrown">
                        <i class="fas fa-crown"
                           style="color:#FFD700; text-shadow: 0px 0px 5px rgba(255, 255, 0, 0.50); "
                           id="userRootIcon"
                           data-html="true"
                           data-original-title="<?php echo __('Administrator privileges'); ?>"
                           data-placement="right" rel="tooltip"></i>
                    </span>
                <?php endif; ?>
            </div>
            <img src="/img/menu/denis-degioanni-9wH624ALFQA-unsplash.png" class="cover" alt="cover">
            <a href="javascript:void(0);" class="pull-trigger-btn" data-action="toggle"
               data-class="list-filter-active" data-target=".page-sidebar" data-focus="nav_filter_input">
                <i class="fa fa-angle-down"></i>
            </a>
        </div>

        <ul menu
            id="js-nav-menu"
            class="nav-menu"
            phpplugin="<?= $this->getRequest()->getParam('plugin', '') ?>"
            phpcontroller="<?= $this->getRequest()->getParam('controller', '') ?>"
            phpaction="<?= $this->getRequest()->getParam('action', '') ?>">>
        </ul>
        <div class="filter-message js-filter-message bg-success-600"></div>
    </nav>
    <!-- END PRIMARY NAVIGATION -->
    <!-- NAV FOOTER -->
    <div class="nav-footer shadow-top">
        <a href="javascript:void(0);" data-action="toggle" data-class="nav-function-minify"
           ng-click="setMenuMinify(false)"
           class="hidden-md-down">
            <i class="ni ni-chevron-right"></i>
            <i class="ni ni-chevron-right"></i>
        </a>
        <ul class="list-table m-auto nav-footer-buttons">
            <li>
                <a ui-sref="StatisticsIndex" data-toggle="tooltip" data-placement="top"
                   title="<?php echo __('Anonymous statistics'); ?>">
                    <i class="fa fa-line-chart"></i>
                </a>
            </li>
            <li>
                <a ui-sref="SupportsIssue" data-toggle="tooltip" data-placement="top"
                   title="<?php echo __('Support'); ?>">
                    <i class="fa fa-life-ring"></i>
                </a>
            </li>
            <li>
                <a ui-sref="DocumentationsWiki" data-toggle="tooltip" data-placement="top"
                   title="<?php echo __('Documentation'); ?>">
                    <i class="fa fa-book"></i>
                </a>
            </li>
        </ul>
    </div> <!-- END NAV FOOTER -->
</aside>
