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


<div class="row">
    <div class="col-xs-12 col-sm-7 col-md-6 col-lg-6">
        <h1 class="status_headline" ng-class="hostStatusTextClass" ng-if="config.includeHoststatus">

            <span class="flapping_airport stateClass" ng-show="hoststatus.isFlapping">
                <i ng-class="flappingState === 1 ? 'fa fa-circle' : 'far fa-circle'"></i>
                <i ng-class="flappingState === 0 ? 'fa fa-circle' : 'far fa-circle'"></i>
            </span>

            <i class="fa fa-desktop fa-fw"></i>
            {{ config.hostName }}
            <span ng-click="rootCopyToClipboard(config.hostAddress, $event)"
                  class="copy-to-clipboard-container-text pointer">
                (
                {{ config.hostAddress }}
                <span ng-click="rootCopyToClipboard(config.hostAddress, $event)"
                      class="copy-action text-primary animated"
                      data-copied="<?= __('Copied'); ?>"
                      data-copy="<?= __('Copy'); ?>"
                >
                    <?= __('Copy'); ?>
                </span>
                )
            </span>
        </h1>
    </div>
    <div class="col-xs-12 col-sm-5 col-md-6 col-lg-6">

        <div class="pull-right">
            <?php if ($this->Acl->hasPermission('browser', 'hosts')): ?>
                <button ng-if="config.showBackButton"
                        ui-sref="HostsBrowser({id:config.hostId})"
                        class="btn btn-primary btn-sm">
                    <i class="fa fa-arrow-circle-left"></i> <?php echo __('Back to overview'); ?>
                </button>
            <?php endif; ?>

            <button ng-if="config.showReschedulingButton"
                    title="<?php echo __('Reset check time'); ?>"
                    class="btn btn-default btn-sm"
                    ng-click="config.rescheduleCallback()">
                <i class="fa fa-refresh fa-lg"></i>
            </button>

            <?php if ($this->Acl->hasPermission('view', 'documentations')): ?>
                <div style="display: inline; position:relative;">
                    <a
                        ui-sref="DocumentationsView({uuid:config.hostUuid, type:'host'})"
                        title="<?php echo __('Object documentation'); ?>"
                        class="btn btn-default btn-sm">
                        <i class="fa fa-book fa-lg">
                            <span ng-show="config.docuExists" class="badge bg-up badge-icon">
                                <i class="fa fa-check"></i>
                            </span>
                        </i>
                    </a>
                </div>
            <?php endif; ?>

            <?php if ($this->Acl->hasPermission('hostNotification', 'notifications')): ?>
                <a ui-sref="NotificationsHostNotification({id:config.hostId})"
                   title="<?php echo __('Notifications'); ?>"
                   class="btn btn-default btn-sm">
                    <i class="fa fa-envelope fa-lg"></i>
                </a>
            <?php endif; ?>

            <?php if ($this->Acl->hasPermission('index', 'hostchecks')): ?>
                <a ui-sref="HostchecksIndex({id:config.hostId})"
                   title="<?php echo __('Check history'); ?>"
                   class="btn btn-default btn-sm">
                    <i class="fa fa-check-square fa-lg"></i>
                </a>
            <?php endif; ?>

            <?php if ($this->Acl->hasPermission('host', 'statehistories')): ?>
                <a ui-sref="StatehistoriesHost({id:config.hostId})"
                   title="<?php echo __('State history'); ?>"
                   class="btn btn-default btn-sm">
                    <i class="fa fa-history fa-lg"></i>
                </a>
            <?php endif; ?>

            <?php if ($this->Acl->hasPermission('host', 'acknowledgements')): ?>
                <a ui-sref="AcknowledgementsHost({id:config.hostId})"
                   title="<?php echo __('Acknowledgement history'); ?>"
                   class="btn btn-default btn-sm">
                    <i class="fa fa-user fa-lg"></i>
                </a>
            <?php endif; ?>

            <a ng-show="config.hostUrl" href="{{ config.hostUrl }}"
               title="<?php echo __('External link'); ?>"
               target="_blank"
               class="btn btn-default btn-sm">
                <i class="fas fa-external-link-alt fa-lg">
                </i>
            </a>

            <?php if ($this->Acl->hasPermission('edit', 'hosts')): ?>
                <a ui-sref="HostsEdit({id:config.hostId})" ng-if="config.allowEdit"
                   title="<?php echo __('Edit host'); ?>"
                   class="btn btn-default btn-sm">
                    <i class="fa fa-cog fa-lg"></i>
                </a>
            <?php endif; ?>

            <div class="btn-group btn-group-sm">
                <button class="btn btn-default dropdown-toggle waves-effect waves-themed" type="button"
                        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <?php echo __('More actions'); ?>
                </button>
                <div class="dropdown-menu dropdown-menu-right" x-placement="bottom-start"
                     style="position: absolute; will-change: top, left; top: 37px; left: 0px;">
                    <?php if ($this->Acl->hasPermission('serviceList', 'services')): ?>
                        <a class="dropdown-item"
                           ui-sref="ServicesServiceList({id: config.hostId})">
                            <i class="fa fa-cog"></i>
                            <?php echo __('Service list'); ?>
                        </a>
                    <?php endif; ?>
                    <?php if ($this->Acl->hasPermission('index', 'changelogs')): ?>
                        <a ui-sref="ChangelogsEntity({objectTypeId: 'host', objectId: config.hostId})"
                           class="dropdown-item">
                            <i class="fa-solid fa-timeline fa-rotate-90"></i>
                            <?php echo __('Changelog'); ?>
                        </a>
                    <?php endif; ?>
                    <?php if ($this->Acl->hasPermission('allocateToHost', 'servicetemplategroups')): ?>
                        <a class="dropdown-item"
                           ui-sref="ServicetemplategroupsAllocateToHost({id: 0, hostId: config.hostId})">
                            <i class="fas fa-external-link-alt"></i>
                            <?php echo __('Allocate service template group'); ?>
                        </a>
                    <?php endif; ?>
                    <?php if ($this->Acl->hasPermission('wizard', 'agentconnector')): ?>
                        <a ui-sref="AgentconnectorsWizard({hostId: config.hostId})"
                           class="dropdown-item">
                            <i class="fa fa-user-secret"></i>
                            <?php echo __('openITCOCKPIT Agent discovery'); ?>
                        </a>
                    <?php endif; ?>
                    <?php
                    $AdditionalLinks = new \App\Lib\AdditionalLinks($this);
                    echo $AdditionalLinks->getLinksAsHtmlList('hosts', 'browser', 'list');
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

