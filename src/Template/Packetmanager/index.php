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

/**
 * @var \App\View\AppView $this
 * @var string $systemname
 * @var \itnovum\openITCOCKPIT\Core\RepositoryChecker $RepositoryChecker
 * @var \itnovum\openITCOCKPIT\Core\System\Health\LsbRelease $LsbRelease
 */

$Logo = new \itnovum\openITCOCKPIT\Core\Views\Logo();

?>
<ol class="breadcrumb page-breadcrumb">
    <li class="breadcrumb-item">
        <a ui-sref="DashboardsIndex">
            <i class="fa fa-home"></i> <?php echo __('Home'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <a ui-sref="PackageManagerIndex">
            <i class="fas fa-cubes"></i> <?php echo __('Package manager'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <i class="fa fa-list"></i> <?php echo __('Overview'); ?>
    </li>
</ol>

<?php if (IS_CONTAINER === false): ?>
    <!-- openITCOCKPIT is installed via apt dnf or git -->
    <?php echo $this->element('repository_checker'); ?>

    <?php if ($LsbRelease->getCodename() === 'bionic'): ?>
        <div class="alert alert-danger alert-block">
            <a class="close" data-dismiss="alert" href="javascript:void(0);">×</a>
            <h4 class="alert-heading">
                <i class="fa fa-warning"></i>
                <?php echo __('Ubuntu Bionic 18.04 is end of life soon!'); ?>
            </h4>
            <?php echo __('Official end of life of Ubuntu Bionic scheduled for April 2023.'); ?>
            <?php echo __('Therefore openITCOCKPIT 4.5.5 will be one of the last releases for Ubuntu Bionic. Please update to Ubuntu Focal to receive further updates.'); ?>
            <br/>
            <?php echo __('Need help updating your system? Please don\'t hesitate to contact our enterprise support {0}.', '<a class="txt-color-darken" href="mailto:support@itsm.it-novum.com">support@itsm.it-novum.com</a>'); ?>
        </div>
    <?php endif; ?>

    <?php if ($LsbRelease->getCodename() === 'focal'): ?>
        <div class="alert alert-danger alert-block">
            <a class="close" data-dismiss="alert" href="javascript:void(0);">×</a>
            <h4 class="alert-heading">
                <i class="fa fa-warning"></i>
                <?php echo __('Ubuntu Focal 20.04 is end of life soon!'); ?>
            </h4>
            <?php echo __('Official end of life of Ubuntu Focal scheduled for April 2025.'); ?>
            <?php echo __('Therefore openITCOCKPIT 4.8.x will be one of the last releases for Ubuntu Focal. Please update to Ubuntu Jammy to receive further updates.'); ?>
            <br/>
            <?php echo __('Need help updating your system? Please don\'t hesitate to contact our enterprise support {0}.', '<a class="txt-color-darken" href="https://openitcockpit.io/contact/">https://openitcockpit.io/contact/</a>'); ?>
        </div>
    <?php endif; ?>

    <?php if ($LsbRelease->getCodename() === 'buster'): ?>
        <div class="alert alert-danger alert-block">
            <a class="close" data-dismiss="alert" href="javascript:void(0);">×</a>
            <h4 class="alert-heading">
                <i class="fa fa-warning"></i>
                <?php echo __('Debian Buster 10 end of life!'); ?>
            </h4>
            <?php echo __('Debian Buster is not supported by the Debian security team anymore!'); ?>
            <?php echo __('Therefore openITCOCKPIT 4.5.5 will be one of the last releases for Debian Buster. Please update to Debian Bullseye to receive further updates.'); ?>
            <br/>
            <?php echo __('Need help updating your system? Please don\'t hesitate to contact our enterprise support {0}.', '<a class="txt-color-darken" href="mailto:support@itsm.it-novum.com">support@itsm.it-novum.com</a>'); ?>
        </div>
    <?php endif; ?>

    <?php if ($LsbRelease->getCodename() === 'bullseye'): ?>
        <div class="alert alert-danger alert-block">
            <a class="close" data-dismiss="alert" href="javascript:void(0);">×</a>
            <h4 class="alert-heading">
                <i class="fa fa-warning"></i>
                <?php echo __('Debian Bullseye 11 end of life!'); ?>
            </h4>
            <?php echo __('Debian Bullseye is not supported by the Debian security team anymore!'); ?>
            <?php echo __('Therefore openITCOCKPIT 4.8.x will be one of the last releases for Debian Bullseye. Please update to Debian Bookworm to receive further updates.'); ?>
            <br/>
            <?php echo __('Need help updating your system? Please don\'t hesitate to contact our enterprise support {0}.', '<a class="txt-color-darken" href="https://openitcockpit.io/contact/">https://openitcockpit.io/contact/</a>'); ?>
        </div>
    <?php endif; ?>

<?php else: ?>
    <!-- openITCOCKPIT is running inside a Container like Docker -->
    <div class="alert alert-primary border-faded">
        <div class="d-flex align-items-center">
            <div class="alert-icon">
                <span class="icon-stack icon-stack-md">
                    <i class="base-7 icon-stack-3x color-info-600"></i>
                    <i class="fas fa-info icon-stack-1x text-white"></i>
                </span>
            </div>
            <div class="flex-1 color-info-600">
                <span class="h5 color-info-600">
                    <?= __('Containerized installation'); ?>
                </span>
                <br>
                <?= __('Your installation of {0} is running in a container based environment like Docker.', h($systemname)) ?>
                <?= __('Therefore all available module are installed by default.'); ?>
            </div>

        </div>
    </div>
<?php endif; ?>


<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?php echo __('Packagemanager'); ?>
                </h2>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <div class="col-lg-12" ng-show="error">
                        <div class="alert alert-danger alert-block">
                            <a href="javascript:void(0);" data-dismiss="alert" class="close">×</a>
                            <h5 class="alert-heading"><i
                                    class="fa fa-exclamation-triangle"></i> <?php echo __('Connection error.'); ?>
                            </h5>
                            <p>
                                {{error_msg}}
                            </p>
                        </div>
                    </div>

                    <div class="row">

                        <!-- Current installed version -->
                        <div class="col-xs-12 col-md-6 col-lg-4 padding-bottom-10" ng-show="!newVersion">
                            <div class="card">
                                <div class="card-header community-bg-header text-white">
                                    <h4 class="pm-h4">
                                        <?= \Spatie\Emoji\Emoji::partyingFace(); ?>
                                        <?= __('Your system is on the latest version!'); ?>
                                    </h4>
                                    <div class="float-right italic">
                                        <?= h(OPENITCOCKPIT_VERSION); ?>
                                    </div>
                                </div>
                                <div class="card-body packagemanagerCardBody">
                                    <div class="text text-center">
                                        <img class="img-fluid" alt="Logo" src="<?= $Logo->getLogoForHtml() ?>"
                                             style="max-height: 140px;">
                                    </div>

                                </div>
                                <div class="card-footer">
                                    <div class="row">
                                        <div class="col-lg-12 padding-right-0">

                                            <div class="float-right">

                                                <a href="javascript:void(0);"
                                                   class="btn btn-default"
                                                   data-toggle="modal"
                                                   data-target="#changelogModal">
                                                    <span class="btn-label">
                                                        <i class="fas fa-code-fork"></i>
                                                    </span>
                                                    <?= __('Changelog'); ?>
                                                </a>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- New version available -->
                        <div class="col-xs-12 col-md-6 col-lg-4 padding-bottom-10" ng-show="newVersion">
                            <div class="card">
                                <div class="card-header community-bg-header text-white">
                                    <h4 class="pm-h4">
                                        <?= \Spatie\Emoji\Emoji::partyPopper() ?>
                                        <?= __('New version available!'); ?>
                                    </h4>
                                    <div class="float-right italic">
                                        {{changelog[0].Changelog.version}}
                                    </div>
                                </div>
                                <div class="card-body packagemanagerCardBody">
                                    <div class="text">
                                        <?= __('Please update your {0} installation to the latest version to get new features and latest security fixes.', h($systemname)) ?>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <div class="row">
                                        <div class="col-lg-12 padding-right-0">

                                            <div class="float-right">

                                                <a href="https://openitcockpit.io/2018/01/02/how-to-update-openitcockpit/"
                                                   target="_blank"
                                                   class="btn btn-success">
                                                    <span class="btn-label">
                                                        <i class="fa fa-rocket"></i>
                                                    </span>
                                                    <?= __('How to Update'); ?>
                                                </a>

                                                <a href="javascript:void(0);"
                                                   class="btn btn-default"
                                                   data-toggle="modal"
                                                   data-target="#changelogModal">
                                                    <span class="btn-label">
                                                        <i class="fas fa-code-fork"></i>
                                                    </span>
                                                    <?= __('Changelog'); ?>
                                                </a>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xs-12 col-md-6 col-lg-4 padding-bottom-10" ng-repeat="module in modules">

                            <div class="card">
                                <div class="card-header community-bg-header text-white"
                                     ng-class="{'enterprise-bg-header':module.Module.enterprise}">
                                    <h4 class="pm-h4">
                                        {{module.Module.name}}
                                    </h4>
                                    <div class="float-right italic">
                                        {{module.Module.author}}
                                    </div>
                                    <div class="italic" ng-show="module.Module.enterprise">
                                        <?= __('Enterprise') ?>
                                    </div>
                                </div>
                                <div class="card-body packagemanagerCardBody">
                                    <div class="text">
                                        {{module.Module.description}}
                                    </div>

                                    <div class="padding-top-10">
                                        <i class="fa fa-key"></i>
                                        {{module.Module.license}}
                                    </div>

                                    <!-- V4 tags -->
                                    <div ng-if="isArray(module.Module.tags)">
                                        <i class="fa fa-tags"></i>
                                        <span class="badge badge-secondary margin-right-5"
                                              ng-repeat="tag in module.Module.tags">
                                            {{tag}}
                                        </span>
                                    </div>

                                    <!-- V3 tags -->
                                    <div ng-if="!isArray(module.Module.tags)">
                                        <i class="fa fa-tags"></i>
                                        <span class="badge badge-secondary margin-right-5"
                                              ng-repeat="tag in splitTags(module.Module.tags)">
                                            {{tag}}
                                        </span>
                                    </div>
                                </div>
                                <div class="card-footer">

                                    <div class="row" ng-if="module.Module.license_included">
                                        <div class="col-lg-12 padding-right-0">
                                            <button
                                                type="button"
                                                title="<?= __('This module is already installed') ?>"
                                                ng-show="installedModules[module.Module.apt_name]"
                                                class="btn btn-success float-right">
                                                <span class="btn-label">
                                                    <i class="fa fa-check"></i>
                                                </span>
                                                <?= __('Installed'); ?>
                                            </button>
                                        </div>

                                        <div class="col-lg-12">
                                            <div class="input-group"
                                                 ng-hide="installedModules[module.Module.apt_name]">
                                                <div class="input-group-prepend ml-auto">
                                                    <div class="input-group-text" id="btnGroupAddon">
                                                        <input type="checkbox"
                                                               ng-click="installPackage(module.Module.apt_name)"
                                                               ng-model="modulesToCheckboxesInstall[module.Module.apt_name]">
                                                    </div>
                                                    <button
                                                        type="button"
                                                        class="btn btn-primary"
                                                        style="border-top-right-radius: 4px; border-bottom-right-radius: 4px;"
                                                        ng-click="installPackage(module.Module.apt_name)">
                                                        <?= __('Install'); ?>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row" ng-if="!module.Module.license_included">
                                        <div class="col-lg-12 padding-right-0">
                                            <a
                                                href="https://it-services.it-novum.com/support-2/"
                                                target="_blank"
                                                class="btn btn-primary float-right">
                                                <i class="fas fa-shopping-cart"></i>
                                                <?= __('Request a quote'); ?>
                                            </a>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Changelog modal -->
<div id="changelogModal" class="modal" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-code-fork"></i>
                    <?php echo __('Changelog'); ?>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="fa fa-times"></i></span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-primary">
                    <div class="d-flex flex-start w-100">
                        <div class="mr-2 hidden-md-down">
                            <span class="icon-stack icon-stack-lg">
                                <i class="base base-6 icon-stack-3x opacity-100 color-primary-500"></i>
                                <i class="base base-10 icon-stack-2x opacity-100 color-primary-300 fa-flip-vertical"></i>
                                <i class="ni ni-info icon-stack-1x opacity-100 color-white"></i>
                            </span>
                        </div>
                        <div class="d-flex flex-fill">
                            <div class="flex-fill">
                                <span class="h5">
                                    <?= __('Blog'); ?>
                                </span>
                                <p>
                                    <?= __('News about IT monitoring from the experts behind openITCOCKPIT '); ?>
                                </p>
                                <p class="m-0">
                                    <?= __('Visit our blog for more details:'); ?>
                                    <a href="https://openitcockpit.io/blog/" target="_blank">
                                        https://openitcockpit.io/blog/
                                    </a>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="container">
                    <div class="row">
                        <div class="col-12">
                            <h4>
                                <?= __('Latest version') ?>
                                {{changelog[0].Changelog.version}}
                            </h4>
                            <ul class="timeline">
                                <li ng-repeat="record in changelog">
                                    <span class="text-primary">{{record.Changelog.version}}</span>
                                    <p ng-bind-html="record.Changelog.changes | trustAsHtml"></p>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <?php echo __('Close'); ?>
                </button>
            </div>
        </div>
    </div>
</div>


<!-- Install packages modal -->
<div id="installPackageModal" class="modal" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-code-fork"></i>
                    <?php echo __('Install packages'); ?>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="fa fa-times"></i></span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-12">
                        <?= __('To install the selected packages, please execute the following command on your {0} system.', $systemname) ?>
                    </div>
                </div>

                <div class="row padding-top-5">

                    <!--
                    If you change this command please make also sure to change the command
                    in the PackagemanagerIndexController clipboardCommand function !!
                    -->

                    <div class="col-lg-12 copy-to-clipboard-container" style="display: block; position: relative;">
                        <?php if ($LsbRelease->isDebianBased()): ?>
                            <div class="bg-color-black txt-color-white code-font padding-7 packetmanager-selection">
                                sudo apt-get update && sudo apt-get dist-upgrade
                                <br>
                                sudo apt-get install <span ng-bind-html="getCliCommand() | trustAsHtml"></span>
                                \ <br>
                                && echo "#########################################" \ <br>
                                && echo
                                "<?= __('Installation done. Please reload your {0} web interface.', $systemname) ?>"
                            </div>
                        <?php endif; ?>

                        <?php if ($LsbRelease->isRhelBased()): ?>
                            <div class="bg-color-black txt-color-white code-font padding-7 packetmanager-selection">
                                sudo dnf check-update
                                <br>
                                sudo dnf upgrade
                                <br>
                                sudo dnf install <span ng-bind-html="getCliCommand() | trustAsHtml"></span>
                                \ <br>
                                && echo "#########################################" \ <br>
                                && echo
                                "<?= __('Installation done. Please reload your {0} web interface.', $systemname) ?>"
                            </div>
                        <?php endif; ?>

                        <div
                            class="copy-to-clipboard-btn copy-to-clipboard-btn-top-right"
                            style="right: 20px;"
                            rel="tooltip"
                            data-toggle="tooltip"
                            data-trigger="click"
                            data-placement="left"
                            data-original-title="<?= __('Copied'); ?>">
                            <div
                                class="btn btn-default btn-xs waves-effect waves-themed"
                                ng-click="clipboardCommand()"
                                title="<?php echo __('Copy to clipboard'); ?>">
                                <i class="fa fa-copy"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row padding-top-5">
                    <div class="col-lg-12">
                        <?= __('To install two or more packages at once, close this window and select the next module you like to install. All selected modules will be added to the installation command.') ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <?php echo __('Close'); ?>
                </button>
            </div>
        </div>
    </div>
</div>
