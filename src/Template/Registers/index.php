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

$Logo = new \itnovum\openITCOCKPIT\Core\Views\Logo();
?>
<ol class="breadcrumb page-breadcrumb">
    <li class="breadcrumb-item">
        <a ui-sref="DashboardsIndex">
            <i class="fa fa-home"></i> <?php echo __('Home'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <a ui-sref="RegistersIndex">
            <i class="fa fa-check-square"></i> <?php echo __('Registration'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <i class="fas fa-certificate"></i> <?php echo __('License'); ?>
    </li>
</ol>

<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?php echo __('Register this openICOCKPIT instance'); ?>
                </h2>
                <div class="panel-toolbar">
                    <button type="button" class="btn btn-xs btn-default mr-1 shadow-0"
                            ng-click="toggleFullscreenMode();">
                        <i class="fa fa-heart"></i>
                        <?php echo __('Credits'); ?>
                    </button>
                </div>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <form ng-submit="submit();" class="form-horizontal">
                        <div class="form-group" ng-class="{'has-error': errors.license}">
                            <label class="control-label">
                                <?php echo __('License key'); ?>
                            </label>
                            <input
                                class="form-control"
                                type="text"
                                autocomplete="off"
                                ng-model="post.Registers.license">
                            <div ng-repeat="error in errors.license">
                                <div class="help-block text-danger">{{ error }}</div>
                            </div>
                            <div class="help-block">
                                <a href="https://openitcockpit.io/editions/" target="_blank">
                                    <i class="fas fa-external-link-alt"></i>
                                    <?php echo __('Get your openITCOCKPIT Enterprise Subscription today'); ?>
                                </a>
                            </div>
                        </div>


                        <div ng-show="valid">
                            <div class="col-xs-12 col-md-12 col-lg-12">

                                <div class="row" ng-show="hasLicense">
                                    <div class="col-xs-12 col-sm-12 col-md-12 col-lg-6"
                                         style="background-image: url('/img/certs/{{certImage}}'); min-height: 667px; background-repeat: no-repeat; background-size: 100%;">

                                        <div class="row">
                                            <dl class="dl-horizontal col-lg-6" style="padding-top: 30%;">
                                                <dt><?php echo __('First name'); ?>:</dt>
                                                <dd class="code-font text-info">{{license.firstname}}</dd>

                                                <dt><?php echo __('Last name'); ?>:</dt>
                                                <dd class="code-font text-info">{{license.lastname}}</dd>

                                                <dt><?php echo __('Expires'); ?>:</dt>
                                                <dd class="code-font text-info">{{license.expire}}</dd>

                                                <dt><?php echo __('License key'); ?>:</dt>
                                                <dd class="code-font text-info">{{license.license}}</dd>
                                            </dl>

                                            <dl class="dl-horizontal col-lg-6" style="padding-top: 30%;">
                                                <dt><?php echo __('Email'); ?>:</dt>
                                                <dd class="code-font text-info">{{license.email}}</dd>

                                                <dt><?php echo __('Company'); ?>:</dt>
                                                <dd class="code-font text-info">{{license.company}}</dd>
                                            </dl>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <div class="card margin-top-10">
                            <div class="card-body">
                                <div class="float-right">
                                    <button class="btn btn-primary"
                                            type="submit"><?php echo __('Register'); ?></button>
                                </div>
                            </div>
                        </div>


                    </form>
                </div>
            </div>
        </div>
    </div>
</div>


<div class="credits-container" id="credits-container" style="display:none;">
    <div id="credits">
        <h1 class="credits-h1">openITCOCKPIT</h1>
        <br/>

        <h2 class="credits-h2">Developers</h2>
        <dl>
            <dd>Irina Bering</dd>
            <dd>Daniel Ziegler</dd>
            <dd>Maximilian Pappert</dd>
            <dd>Timo Triebensky</dd>
        </dl>

        <h2 class="credits-h2">Special Thanks</h2>
        <p>Stephan Kraus, Oliver Müller, Laziz Karimov, Robert Braun,<br/>
            René Kleffel, Michael Ziegler, Jonas Rottmann,<br/>
            Johannes Drummer, Jens Michelsons, Jeremy Eder and more...</p>

        <p>Thanks to all the developers for the beautiful libraries.</p>

        <h2 class="credits-h2">Powered by</h2>
        <p>
            <img src="/img/logos/3rd/php.png">
        </p>

        <p>
            <img src="/img/logos/3rd/We-bake-with-CakePHP.png" style="width: 300px;">
        </p>

        <p>
            <img src="/img/logos/3rd/jquery.png">
        </p>

        <p>
            <img src="/img/logos/3rd/jquery_ui.png">
        </p>

        <p>
            <img src="/img/logos/3rd/AngularJS-medium.png">
        </p>

        <p class="padding-top-80">
            <img src="/img/logos/3rd/Statusengine_dark.png">
        </p>

        <div class="credits-fineprint">All trademarks are the property of the trademark owners.</div>

        <br/>
        <br/>
        <h2 class="credits-h2">Image Credits</h2>
        <p>Milky Way Photo by Denis Degioanni on Unsplash</p>
        <br/>
        <br/>
        <h2 class="credits-h2">Sponsored by it-novum</h2>
        <p>
            <img src="/img/logos/it-novum.png">
        </p>

        <br/>
        <br/>
        <br/>
        <p id="credits-oitc-logo">
            <img alt="Logo" src="/img/logos/logo_small.png" style="max-height: 250px;">
        </p>

        <br/>
        <br/>
        <div class="credits-fineprint">Press ESC to exit</div>

    </div>
</div>
