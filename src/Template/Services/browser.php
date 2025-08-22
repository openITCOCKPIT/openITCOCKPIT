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
 * @var string $username
 * @var bool $blurryCommandLine
 */

use Cake\Core\Plugin;

?>

<query-handler-directive></query-handler-directive>

<div ng-init="flashMshStr='<?php echo __('Command sent successfully. Refresh in 5 seconds'); ?>'"></div>


<service-browser-menu
    ng-if="serviceBrowserMenuConfig"
    config="serviceBrowserMenuConfig"
    last-load-date="lastLoadDate"
    root-copy-to-clipboard="rootCopyToClipboard"></service-browser-menu>


<reschedule-service callback="showFlashMsg"></reschedule-service>
<service-downtime author="<?php echo h($username); ?>" callback="showFlashMsg"></service-downtime>
<mass-delete-service-downtimes delete-url="/downtimes/delete/" callback="showFlashMsg"></mass-delete-service-downtimes>
<acknowledge-service author="<?php echo h($username); ?>" callback="showFlashMsg"></acknowledge-service>
<submit-service-result max-check-attempts="{{mergedService.max_check_attempts}}"
                       callback="showFlashMsg"></submit-service-result>
<enable-service-flap-detection callback="showFlashMsg"></enable-service-flap-detection>
<disable-service-flap-detection callback="showFlashMsg"></disable-service-flap-detection>
<enable-service-flap-detection callback="showFlashMsg"></enable-service-flap-detection>
<disable-service-notifications callback="showFlashMsg"></disable-service-notifications>
<enable-service-notifications callback="showFlashMsg"></enable-service-notifications>
<send-service-notification author="<?php echo h($username); ?>" callback="showFlashMsg"></send-service-notification>
<mass-delete-host-downtimes delete-url="/downtimes/delete/" callback="showFlashMsg"></mass-delete-host-downtimes>
<mass-delete-acknowledgements delete-url="/acknowledgements/delete/"
                              callback="showFlashMsg"></mass-delete-acknowledgements>


<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?php echo __('Service'); ?>:
                    <span class="fw-300"><i>{{ host.Host.hostname }} / {{ mergedService.name }}</i></span>
                </h2>
                <div class="panel-toolbar">
                    <button class="btn btn-xs btn-default mr-1 shadow-0" ng-click="load()">
                        <i class="fas fa-sync"></i> <?php echo __('Refresh'); ?>
                    </button>
                    <?php if ($this->Acl->hasPermission('index', 'services')): ?>
                        <a back-button href="javascript:void(0);" fallback-state='ServicesIndex'
                           class="btn btn-default btn-xs mr-1 shadow-0">
                            <i class="fas fa-long-arrow-alt-left"></i> <?php echo __('Back'); ?>
                        </a>
                    <?php endif; ?>
                    <ul class="nav nav-tabs border-bottom-0 nav-tabs-clean flex-column flex-sm-row" role="tablist">
                        <li class="nav-item pointer">
                            <a class="nav-link active" data-toggle="tab" ng-click="selectedTab = 'tab1'; hideTimeline()"
                               role="tab">
                                <i class="fa fa-info">&nbsp;</i> <?php echo __('Status information'); ?>
                            </a>
                        </li>
                        <li class="nav-item pointer">
                            <a class="nav-link" data-toggle="tab" ng-click="selectedTab = 'tab2'; hideTimeline()"
                               role="tab">
                                <i class="fa fa-hdd-o">&nbsp;</i> <?php echo __('Service information'); ?>
                            </a>
                        </li>
                        <?php if ($this->Acl->hasPermission('timeline', 'services')): ?>
                            <li class="nav-item pointer">
                                <a class="nav-link" data-toggle="tab" ng-click="selectedTab = 'tab3'; showTimeline()"
                                   role="tab">
                                    <i class="fa fa-clock-o">&nbsp;</i> <?php echo __('Timeline'); ?>
                                </a>
                            </li>
                        <?php endif; ?>
                        <?php if (Plugin::isLoaded('ServicenowModule')): ?>
                            <li class="nav-item pointer">
                                <a class="nav-link" data-toggle="tab" ng-click="selectedTab = 'tab4'; hideTimeline()"
                                   role="tab">
                                    <i class="fa fa-user-circle">&nbsp;</i> <?php echo __('ServiceNow'); ?>
                                </a>
                            </li>
                        <?php endif; ?>
                        <?php if (Plugin::isLoaded('CustomalertModule')): ?>
                            <li class="nav-item pointer" ng-show="CustomalertsExists">
                                <a class="nav-link" data-toggle="tab" ng-click="selectedTab = 'tab5'; hideTimeline()"
                                   role="tab">
                                    <i class="fa-solid fa-bullhorn">&nbsp;</i> <?php echo __('Custom alerts'); ?>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
                <?php if (Plugin::isLoaded('SLAModule')): ?>
                    <div ng-show="slaOverview" ng-click="selectedTab = 'tab6'; hideTimeline()">
                        <button
                            class="btn btn-labeled btn-{{slaOverview.state}} btn-xs btn-w-m waves-effect waves-themed"
                            ng-hide="slaOverview.state === 'not_available' || slaOverview.state === 'not_sla_relevant'">
                            <span class="btn-label-bootstrap-5">
                                <i class="fa-lg" ng-class="{'fa fa-check':slaOverview.state === 'success',
                                'fa-solid fa-triangle-exclamation':slaOverview.state === 'warning',
                                'fa-solid fa-bolt': slaOverview.state === 'danger'}"></i>
                            </span>{{slaOverview.determined_availability_percent}} %
                        </button>
                        <button
                            class="btn btn-labeled btn-primary btn-xs btn-w-m waves-effect waves-themed"
                            ng-show="slaOverview.state === 'not_available'">
                            <span class="btn-label-bootstrap-5">
                                <i class="fas fa-question fa-lg"></i>
                            </span><?= __('Not available'); ?>
                        </button>
                        <button ng-disabled="true"
                                class="btn btn-labeled btn-secondary btn-xs btn-w-m waves-effect waves-themed"
                                ng-show="slaOverview.state === 'not_sla_relevant'">
                            <span class="btn-label-bootstrap-5">
                                <i class="fa-solid fa-xmark fa-lg"></i>
                            </span><?= __('Not SLA relevant'); ?>
                        </button>
                    </div>
                <?php endif; ?>

            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <!-- Status information tab start -->
                    <div ng-show="selectedTab == 'tab1'" class="tab-panel">
                        <!-- status overview for small resolutions -->
                        <div class="d-sm-none"
                             ng-class="{'browser-state-green': stateIsOk(), 'browser-state-yellow': stateIsWarning(),
                             'browser-state-red': stateIsCritical(), 'browser-state-gray': stateIsUnknown(),
                             'browser-state-blue': stateIsNotInMonitoring()}"
                             ng-if="servicestatus">
                            <div class="row">
                                <div class="col-6 padding-left-25">
                                    <?php echo __('State'); ?>
                                </div>
                                <div class="col-6">
                                    {{ servicestatus.currentState | serviceStatusName }}
                                </div>
                            </div>

                            <div ng-show="servicestatus.isInMonitoring">
                                <div class="row">
                                    <div class="col-6 padding-left-25">
                                        <?php echo __('State since'); ?>
                                    </div>
                                    <div class="col-6" title="{{ servicestatus.last_state_change_user }}">
                                        {{ servicestatus.last_state_change }}
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-6 padding-left-25">
                                        <?php echo __('Last check'); ?>
                                    </div>
                                    <div class="col-6" title="{{ servicestatus.lastCheckUser }}">
                                        {{ servicestatus.lastCheck }}
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-6 padding-left-25">
                                        <?php echo __(' Next check'); ?>
                                    </div>
                                    <div class="col-6">
                                            <span
                                                ng-if="mergedService.active_checks_enabled && host.Host.is_satellite_host === false"
                                                title="{{ servicestatus.nextCheckUser }}">
                                                {{ servicestatus.nextCheck }}
                                            </span>
                                        <span
                                            ng-if="mergedService.active_checks_enabled == 0 || host.Host.is_satellite_host === true">
                                            <?php echo __('n/a'); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="row" ng-show="servicestatus.isHardstate">
                                    <div class="col-6 padding-left-25">
                                        <?php echo __('State type'); ?>
                                    </div>
                                    <div class="col-6">
                                        <?php echo __('Hard state'); ?>
                                        ({{servicestatus.current_check_attempt}}/{{servicestatus.max_check_attempts}})
                                    </div>
                                </div>
                                <div class="row" ng-show="!servicestatus.isHardstate">
                                    <div class="col-6 padding-left-25">
                                        <?php echo __('State type'); ?>
                                    </div>
                                    <div class="col-6">
                                        <?php echo __('Soft state'); ?>
                                        ({{servicestatus.current_check_attempt}}/{{servicestatus.max_check_attempts}})
                                    </div>
                                </div>

                                <div class="row justify-content-center padding-top-10 padding-bottom-10"
                                     ng-show="canSubmitExternalCommands && mergedService.allowEdit">
                                    <div class="btn-group" role="group" aria-label="Basic example">
                                        <button type="button"
                                                class="btn btn-default"
                                                ng-click="reschedule(getObjectsForExternalCommand())">
                                            <i class="fa fa-refresh"></i>
                                        </button>

                                        <button type="button"
                                                class="btn btn-default"
                                                ng-click="serviceDowntime(getObjectsForExternalCommand())">
                                            <i class="fa fa-power-off"></i>
                                        </button>

                                        <button type="button"
                                                class="btn btn-default"
                                                ng-show="servicestatus.currentState > 0"
                                                ng-click="acknowledgeService(getObjectsForExternalCommand())">
                                            <i class="fa fa-user"></i>
                                        </button>

                                        <button type="button"
                                                class="btn btn-default"
                                                ng-click="submitServiceResult(getObjectsForExternalCommand())">
                                            <i class="fa fa-download"></i>
                                        </button>

                                        <button type="button"
                                                class="btn btn-default"
                                                ng-click="enableServiceFlapDetection(getObjectsForExternalCommand())"
                                                ng-show="!servicestatus.flap_detection_enabled">
                                            <i class="fa fa-adjust"></i>
                                        </button>
                                        <button type="button"
                                                class="btn btn-default"
                                                ng-click="disableServiceFlapDetection(getObjectsForExternalCommand())"
                                                ng-show="servicestatus.flap_detection_enabled">
                                            <i class="fa fa-adjust"></i>
                                        </button>

                                        <button type="button"
                                                class="btn btn-default"
                                                ng-click="enableServiceNotifications(getObjectsForExternalCommand())"
                                                ng-show="!servicestatus.notifications_enabled">
                                            <i class="fa fa-envelope"></i>
                                        </button>
                                        <button type="button"
                                                class="btn btn-default"
                                                ng-click="disableServiceNotifications(getObjectsForExternalCommand())"
                                                ng-show="servicestatus.notifications_enabled">
                                            <i class="fa fa-envelope"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>


                        <div class="row" style="display: flex;">
                            <div
                                class="col-xs-12 col-sm-6 col-md-7 col-lg-9 padding-bottom-10 padding-left-10 padding-right-10">
                                <div class="alert alert-danger opacity-80 margin-bottom-5" role="alert"
                                     ng-show="mergedService.disabled">
                                    <div class="d-flex align-items-center">
                                        <div class="alert-icon width-3">
                                            <div class="icon-stack  icon-stack-sm">
                                                <i class="base base-9 icon-stack-3x opacity-100 text-danger"></i>
                                                <i class="fa fa-plug icon-stack-1x opacity-100 color-white"></i>
                                            </div>
                                        </div>
                                        <div class="flex-1">
                                            <span class="h5">
                                                <?= __('Attention!'); ?>
                                            </span>
                                            <?= __('This service is currently disabled!'); ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-lg-12">
                                        <h3 class="margin-top-5"><?php echo __('Status overview'); ?></h3>
                                    </div>
                                </div>

                                <div class="row" ng-show="servicestatus.scheduledDowntimeDepth > 0">
                                    <div class="col-lg-12 margin-bottom-10">
                                        <div class="browser-border padding-10" style="width: 100%;">

                                            <div class="row">
                                                <div class="col-lg-12">
                                                    <div>
                                                        <h4 class="no-padding">
                                                            <i class="fa fa-power-off"></i>
                                                            <?php echo __('The service is currently in a planned maintenance period'); ?>
                                                        </h4>
                                                    </div>
                                                    <div class="padding-top-5">
                                                        <?php echo __('Downtime was set by'); ?>
                                                        <b>{{downtime.authorName}}</b>
                                                        <?php echo __('with an duration of'); ?>
                                                        <b>{{downtime.durationHuman}}</b>.
                                                    </div>
                                                    <div class="padding-top-5">
                                                        <small>
                                                            <?php echo __('Start time:'); ?>
                                                            {{ downtime.scheduledStartTime }}
                                                            <?php echo __('End time:'); ?>
                                                            {{ downtime.scheduledEndTime }}
                                                        </small>
                                                    </div>
                                                    <div class="padding-top-5">
                                                        <?php echo __('Comment: '); ?>
                                                        {{downtime.commentData}}
                                                    </div>
                                                </div>

                                                <div class="col-lg-12">
                                                    <?php if ($this->Acl->hasPermission('delete', 'downtimes')): ?>
                                                        <button
                                                            class="btn btn-xs btn-danger float-right"
                                                            ng-if="downtime.allowEdit && downtime.isCancellable"
                                                            ng-click="confirmServiceDowntimeDelete(getObjectForDowntimeDelete())">
                                                            <i class="fa fa-trash"></i> <?php echo __('Delete'); ?>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row" ng-show="servicestatus.problemHasBeenAcknowledged">
                                    <div class="col-lg-12 margin-bottom-10">
                                        <div class="browser-border padding-10" style="width: 100%;">

                                            <div class="row">
                                                <div class="col-12">
                                                    <div>
                                                        <h4 class="no-padding">
                                                            <i class="far fa-user"
                                                               ng-show="!acknowledgement.is_sticky"></i>
                                                            <i class="fas fa-user"
                                                               ng-show="acknowledgement.is_sticky"></i>
                                                            <?php echo __('State of service is acknowledged'); ?>
                                                            <span ng-show="acknowledgement.is_sticky">
                                                            (<?php echo __('Sticky'); ?>)
                                                        </span>
                                                        </h4>
                                                    </div>
                                                    <div class="padding-top-5">
                                                        <?php echo __('Acknowledgement was set by'); ?>
                                                        <b>{{acknowledgement.author_name}}</b>
                                                        <?php echo __('at'); ?>
                                                        {{acknowledgement.entry_time}}
                                                    </div>
                                                    <div class="padding-top-5">
                                                        <?php echo __('Comment: '); ?>
                                                        <div style="display:inline"
                                                             ng-bind-html="acknowledgement.commentDataHtml | trustAsHtml"></div>
                                                    </div>
                                                </div>

                                                <div class="col-12">
                                                    <?php if ($this->Acl->hasPermission('delete', 'acknowledgements')): ?>
                                                        <button
                                                            class="btn btn-xs btn-danger float-right"
                                                            ng-if="acknowledgement.allowEdit"
                                                            ng-click="confirmAcknowledgementsDelete(getObjectForServiceAcknowledgementDelete())">
                                                            <i class="fa fa-trash"></i> <?php echo __('Delete'); ?>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>

                                            </div>


                                        </div>
                                    </div>
                                </div>

                                <div class="row" ng-show="servicestatus.isFlapping">
                                    <div class="col-lg-12 margin-bottom-10">
                                        <div class="browser-border padding-10" style="width: 100%;">
                                            <div>
                                                <h4 class="no-padding txt-color-orangeDark">
                                                    <i class="fa fa-exclamation-triangle"></i>
                                                    <?php echo __('The state of this service is currently flapping!'); ?>
                                                </h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row" ng-show="hoststatus.currentState > 0">
                                    <div class="col-lg-12 margin-bottom-10">
                                        <div class="alert alert-block alert-info">
                                            <h4 class="alert-heading">
                                                <i class="fa fa-exclamation-triangle"></i>
                                                <?php echo __('Problem with host detected!'); ?>
                                            </h4>

                                            <?php if ($this->Acl->hasPermission('browser', 'hosts')): ?>
                                                <a ui-sref="HostsBrowser({id:host.Host.id})">
                                                    {{host.Host.hostname}}
                                                </a>
                                            <?php else: ?>
                                                {{host.Host.hostname}}
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="row" ng-show="hoststatus.scheduledDowntimeDepth > 0">
                                    <div class="col-lg-12 margin-bottom-10">
                                        <div class="browser-border padding-10" style="width: 100%;">

                                            <div class="row">
                                                <div class="col-lg-12">
                                                    <div>
                                                        <h4 class="no-padding">
                                                            <i class="fa fa-power-off"></i>
                                                            <?php echo __('The host of this service is currently in a planned maintenance period'); ?>
                                                        </h4>
                                                    </div>
                                                    <div class="padding-top-5">
                                                        <?php echo __('Downtime was set by'); ?>
                                                        <b>{{hostDowntime.authorName}}</b>
                                                        <?php echo __('with an duration of'); ?>
                                                        <b>{{hostDowntime.durationHuman}}</b>.
                                                    </div>
                                                    <div class="padding-top-5">
                                                        <small>
                                                            <?php echo __('Start time:'); ?>
                                                            {{ hostDowntime.scheduledStartTime }}
                                                            <?php echo __('End time:'); ?>
                                                            {{ hostDowntime.scheduledEndTime }}
                                                        </small>
                                                    </div>
                                                    <div class="padding-top-5">
                                                        <?php echo __('Comment: '); ?>
                                                        {{hostDowntime.commentData}}
                                                    </div>
                                                </div>

                                                <div class="col-lg-12">
                                                    <?php if ($this->Acl->hasPermission('delete', 'downtimes')): ?>
                                                        <button
                                                            class="btn btn-xs btn-danger float-right"
                                                            ng-if="hostDowntime.allowEdit && hostDowntime.isCancellable"
                                                            ng-click="confirmHostDowntimeDelete(getObjectForHostDowntimeDelete())">
                                                            <i class="fa fa-trash"></i> <?php echo __('Delete'); ?>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row" ng-show="hoststatus.problemHasBeenAcknowledged">
                                    <div class="col-lg-12 margin-bottom-10">
                                        <div class="browser-border padding-10" style="width: 100%;">

                                            <div class="row">
                                                <div class="col-12">
                                                    <div>
                                                        <h4 class="no-padding">
                                                            <i class="far fa-user"
                                                               ng-show="!hostAcknowledgement.is_sticky"></i>
                                                            <i class="fas fa-user"
                                                               ng-show="hostAcknowledgement.is_sticky"></i>
                                                            <?php echo __('State of host is acknowledged'); ?>
                                                            <span ng-show="hostAcknowledgement.is_sticky">
                                                            (<?php echo __('Sticky'); ?>)
                                                        </span>
                                                        </h4>
                                                    </div>
                                                    <div class="padding-top-5">
                                                        <?php echo __('Acknowledgement was set by'); ?>
                                                        <b>{{hostAcknowledgement.author_name}}</b>
                                                        <?php echo __('at'); ?>
                                                        {{hostAcknowledgement.entry_time}}
                                                    </div>
                                                    <div class="padding-top-5">
                                                        <?php echo __('Comment: '); ?>
                                                        <div style="display:inline"
                                                             ng-bind-html="hostAcknowledgement.commentDataHtml | trustAsHtml"></div>
                                                    </div>
                                                </div>

                                                <div class="col-12">
                                                    <?php if ($this->Acl->hasPermission('delete', 'acknowledgements')): ?>
                                                        <button
                                                            class="btn btn-xs btn-danger float-right"
                                                            ng-if="hostAcknowledgement.allowEdit"
                                                            ng-click="confirmAcknowledgementsDelete(getObjectForHostAcknowledgementDelete())">
                                                            <i class="fa fa-trash"></i> <?php echo __('Delete'); ?>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- status overview table -->
                                <div class="row">
                                    <div class="col-xs-12 col-sm-12 col-md-9">
                                        <table class="table table-bordered table-sm">
                                            <?php if ($this->Acl->hasPermission('checkcommand', 'services')): ?>
                                                <tr>
                                                    <td><?php echo __('Check command'); ?></td>
                                                    <td>
                                                        <?php if ($this->Acl->hasPermission('edit', 'commands')): ?>
                                                            <a ui-sref="CommandsEdit({id: checkCommand.Command.id})">
                                                                {{ checkCommand.Command.name }}
                                                            </a>
                                                        <?php else: ?>
                                                            {{ checkCommand.Command.name }}
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>

                                                <tr>
                                                    <td><?php echo __('Command line'); ?></td>
                                                    <td class="copy-to-clipboard-container-text"
                                                        style="display: block; position: relative;">
                                                        <code
                                                            class="no-background <?php echo $blurryCommandLine ? 'unblur-on-hover' : '' ?>">
                                                            {{ mergedService.serviceCommandLine }}
                                                        </code>

                                                        <span
                                                            ng-click="rootCopyToClipboard(mergedService.serviceCommandLine, $event)"
                                                            class="copy-action text-primary animated copy-action-top-right"
                                                            data-copied="<?= __('Copied'); ?>"
                                                            data-copy="<?= __('Copy'); ?>"
                                                        >
                                                            <?= __('Copy'); ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>

                                            <tr>
                                                <td><?php echo __('Output'); ?></td>
                                                <td>
                                                    <div class="code-font" ng-class="serviceStatusTextClass"
                                                         ng-bind-html="servicestatus.outputHtml | trustAsHtml"></div>
                                                </td>
                                            </tr>

                                            <tr ng-show="servicestatus.perfdata">
                                                <td><?php echo __('Performance data'); ?></td>
                                                <td>
                                                    <code class="no-background" ng-class="serviceStatusTextClass">
                                                        {{ servicestatus.perfdata }}
                                                    </code>
                                                </td>
                                            </tr>
                                            <tr ng-show="servicestatus.currentState > 0">
                                                <td>
                                                    <?php echo __('Last time'); ?>
                                                    <span class="badge badge-success" style="margin-right: 2px;">
                                                        OK
                                                    </span>
                                                </td>
                                                <td>
                                                    {{ servicestatus.last_time_ok }}
                                                </td>
                                            </tr>
                                        </table>
                                    </div>

                                    <div class="col-xs-12 col-sm-12 col-md-3">
                                        <table class="table table-bordered table-sm">
                                            <tr>
                                                <td><?php echo __('Check period'); ?></td>
                                                <td>
                                                    {{ checkPeriod.name }}
                                                </td>
                                            </tr>

                                            <tr>
                                                <td><?php echo __('Check interval'); ?></td>
                                                <td>
                                                    {{ mergedService.checkIntervalHuman }}
                                                </td>
                                            </tr>

                                            <tr>
                                                <td><?php echo __('Retry interval'); ?></td>
                                                <td>
                                                    {{ mergedService.retryIntervalHuman }}
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>

                                <div class="row padding-bottom-10" ng-show="servicestatus.longOutputHtml">
                                    <div class="col-12">
                                        <h5 class="margin-top-5"><?php echo __('Long output'); ?></h5>
                                    </div>
                                    <div class="col-12">
                                        <div class="card bg-light">
                                            <div class="card-body"
                                                 ng-bind-html="servicestatus.longOutputHtml | trustAsHtml"></div>
                                        </div>
                                    </div>
                                </div>

                                <?php if (\Cake\Core\Plugin::isLoaded('PrometheusModule')): ?>
                                    <!-- Prometheus state overview table -->
                                    <prometheus-service-browser
                                        ng-if="mergedService.service_type === <?= PROMETHEUS_SERVICE ?>"
                                        service-id="mergedService.id"
                                        last-load="lastLoadDate"></prometheus-service-browser>
                                <?php endif; ?>

                                <!-- Host state overview table -->
                                <div class="row">
                                    <div class="col-lg-12">
                                        <h3 class="margin-top-5"><?php echo __('Host state overview'); ?></h3>
                                    </div>


                                    <div class="col-xs-12 col-sm-12">
                                        <table class="table table-bordered table-sm">
                                            <tr>
                                                <th class="width-130"><?php echo __('Host state'); ?></th>
                                                <th><?php echo __('Host name'); ?></th>
                                                <th><?php echo __('Last state change'); ?></th>
                                            </tr>
                                            <tr>
                                                <td class="text-center">
                                                    <hoststatusicon
                                                        ng-if="hoststatus"
                                                        state="hoststatus.currentState"
                                                    ></hoststatusicon>
                                                </td>
                                                <td>
                                                    <?php if ($this->Acl->hasPermission('browser', 'hosts')): ?>
                                                        <a ui-sref="HostsBrowser({id:host.Host.id})">
                                                            {{ host.Host.hostname }}
                                                        </a>
                                                    <?php else: ?>
                                                        {{ host.Host.hostname }}
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    {{ hoststatus.last_state_change }}
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-lg-12">
                                        <h3 class="margin-top-5"><?php echo __('Notification overview'); ?></h3>
                                    </div>


                                    <div
                                        class="col-xs-12 col-sm-12 col-md-6 text-info"
                                        ng-hide="areContactsFromService">
                                        <?php echo __('Contacts and contact groups got inherited from'); ?>

                                        <span
                                            ng-class="{'bold': areContactsInheritedFromServicetemplate}">
                                                <?php if ($this->Acl->hasPermission('edit', 'servicetemplates')): ?>
                                                    <a ui-sref="ServicetemplatesEdit({id: mergedService.servicetemplate_id})">
                                                        <?php echo __('service template'); ?>
                                                    </a>
                                                <?php else: ?>
                                                    <?php echo __('service template'); ?>
                                                <?php endif; ?>
                                            </span>

                                        <span ng-show="!areContactsInheritedFromServicetemplate"
                                              ng-class="{'bold': areContactsInheritedFromHost}">

                                                <i class="fa fa-angle-double-right"></i>

                                                <?php if ($this->Acl->hasPermission('edit', 'hosts')): ?>
                                                    <a ui-sref="HostsEdit({id: mergedService.host_id})">
                                                        <?php echo __('host'); ?>
                                                    </a>
                                                <?php else: ?>
                                                    <?php echo __('host'); ?>
                                                <?php endif; ?>
                                            </span>

                                        <span ng-show="areContactsInheritedFromHosttemplate" class="bold">

                                                <i class="fa fa-angle-double-right"></i>

                                                <?php if ($this->Acl->hasPermission('edit', 'hosttemplates')): ?>
                                                    <a ui-sref="HosttemplatesEdit({id: host.Host.hosttemplate_id})">
                                                        <?php echo __('host template'); ?>
                                                    </a>
                                                <?php else: ?>
                                                    <?php echo __('host template'); ?>
                                                <?php endif; ?>
                                            </span>
                                        .
                                    </div>
                                </div>
                                <!-- Notification overview table -->
                                <div class="row">
                                    <div class="col-xs-12 col-sm-12 col-md-6">
                                        <table class="table table-bordered table-sm">
                                            <tr ng-show="mergedService.contacts.length">
                                                <td><?php echo __('Contacts'); ?></td>
                                                <td>
                                                    <div ng-repeat="contact in mergedService.contacts">
                                                        <?php if ($this->Acl->hasPermission('edit', 'contacts')): ?>
                                                            <a ng-if="contact.allowEdit"
                                                               ui-sref="ContactsEdit({id: contact.id})">
                                                                {{contact.name}}
                                                            </a>
                                                            <span ng-if="!contact.allowEdit">{{contact.name}}</span>
                                                        <?php else: ?>
                                                            {{contact.name}}
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>

                                            <tr ng-show="mergedService.contactgroups.length">
                                                <td><?php echo __('Contact groups'); ?></td>
                                                <td>
                                                    <div ng-repeat="contactgroup in mergedService.contactgroups">
                                                        <?php if ($this->Acl->hasPermission('edit', 'contactgroups')): ?>
                                                            <a ng-if="contactgroup.allowEdit"
                                                               ui-sref="ContactgroupsEdit({id: contactgroup.id})">
                                                                {{contactgroup.container.name}}
                                                            </a>
                                                            <span ng-if="!contactgroup.allowEdit">
                                                                    {{contactgroup.container.name}}
                                                                </span>
                                                        <?php else: ?>
                                                            {{contactgroup.container.name}}
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>

                                    <div class="col-xs-12 col-sm-12 col-md-6">
                                        <table class="table table-bordered table-sm">
                                            <tr>
                                                <td><?php echo __('Notification period'); ?></td>
                                                <td>{{ notifyPeriod.name }}</td>
                                            </tr>

                                            <tr>
                                                <td><?php echo __('Notification interval'); ?></td>
                                                <td>{{ mergedService.notificationIntervalHuman }}</td>
                                            </tr>

                                            <tr>
                                                <td><?php echo __('Notifications enabled'); ?></td>
                                                <td>
                                                        <span class="badge badge-success"
                                                              ng-show="servicestatus.notifications_enabled">
                                                            <?php echo __('Yes'); ?>
                                                        </span>

                                                    <span class="badge badge-danger"
                                                          ng-show="!servicestatus.notifications_enabled">
                                                            <?php echo __('No'); ?>
                                                        </span>
                                                </td>
                                            </tr>

                                            <tr>
                                                <td><?php echo __('Notify on'); ?></td>
                                                <td>
                                                        <span class="badge badge-success"
                                                              ng-show="mergedService.notify_on_recovery"
                                                              style="margin-right: 2px;">
                                                            <?php echo __('Recover'); ?>
                                                        </span>

                                                    <span class="badge badge-warning"
                                                          ng-show="mergedService.notify_on_warning"
                                                          style="margin-right: 2px;">
                                                            <?php echo __('Warning'); ?>
                                                        </span>

                                                    <span class="badge badge-danger"
                                                          ng-show="mergedService.notify_on_critical"
                                                          style="margin-right: 2px;">
                                                            <?php echo __('Critical'); ?>
                                                        </span>

                                                    <span class="badge badge-secondary"
                                                          ng-show="mergedService.notify_on_unknown"
                                                          style="margin-right: 2px;">
                                                            <?php echo __('Unknown'); ?>
                                                        </span>

                                                    <span class="badge badge-primary"
                                                          ng-show="mergedService.notify_on_flapping"
                                                          style="margin-right: 2px;">
                                                            <?php echo __('Flapping'); ?>
                                                        </span>

                                                    <span class="badge badge-primary"
                                                          ng-show="mergedService.notify_on_downtime"
                                                          style="margin-right: 2px;">
                                                            <?php echo __('Downtime'); ?>
                                                        </span>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>

                            </div>
                            <div class="col-sm-6 col-md-5 col-lg-3 no-padding d-none d-sm-block"
                                 ng-class="{'browser-state-green': stateIsOk(), 'browser-state-yellow': stateIsWarning(),
                                 'browser-state-red': stateIsCritical(), 'browser-state-gray': stateIsUnknown(),
                                 'browser-state-blue browser-not-monitored': stateIsNotInMonitoring()}"
                                 ng-if="servicestatus">

                                <div class="text-center txt-color-white">
                                    <h1 class="font-size-50">
                                        {{ servicestatus.currentState | serviceStatusName }}
                                    </h1>
                                </div>

                                <div ng-show="servicestatus.isInMonitoring">
                                    <div class="text-center txt-color-white">
                                        <div><?php echo __('State since'); ?></div>
                                        <h3 class="margin-top-0" title="{{ servicestatus.last_state_change_user }}">
                                            {{ servicestatus.last_state_change }}
                                        </h3>
                                    </div>

                                    <div class="text-center txt-color-white">
                                        <div><?php echo __('Last check'); ?></div>
                                        <h3 class="margin-top-0" title="{{ servicestatus.lastCheckUser }}">
                                            {{ servicestatus.lastCheck }}
                                        </h3>
                                    </div>

                                    <div class="text-center txt-color-white">
                                        <div><?php echo __('Next check'); ?></div>
                                        <h3 class="margin-top-0">
                                                <span
                                                    ng-if="mergedService.active_checks_enabled && host.Host.is_satellite_host === false"
                                                    title="{{ servicestatus.nextCheckUser }}">
                                                    {{ servicestatus.nextCheck }}
                                                    <small style="color: #333;" ng-show="servicestatus.latency > 1">
                                                        (+ {{ servicestatus.latency }})
                                                    </small>
                                                </span>
                                            <span
                                                ng-if="mergedService.active_checks_enabled == 0 || host.Host.is_satellite_host === true">
                                            <?php echo __('n/a'); ?>


                                        </h3>
                                    </div>

                                    <div class="text-center txt-color-white">
                                        <div><?php echo __('State type'); ?></div>
                                        <h3 class="margin-top-0" ng-show="servicestatus.isHardstate">
                                            <?php echo __('Hard state'); ?>
                                            ({{servicestatus.current_check_attempt}}/{{servicestatus.max_check_attempts}})
                                        </h3>

                                        <h3 class="margin-top-0" ng-show="!servicestatus.isHardstate">
                                            <?php echo __('Soft state'); ?>
                                            ({{servicestatus.current_check_attempt}}/{{servicestatus.max_check_attempts}})
                                        </h3>
                                    </div>

                                    <div ng-if="canSubmitExternalCommands && mergedService.allowEdit">
                                        <div class="browser-action"
                                             ng-show="mergedService.service_type !== <?= PROMETHEUS_SERVICE ?>"
                                             ng-click="reschedule(getObjectsForExternalCommand())">
                                            <i class="fa fa-refresh"></i>
                                            <?php echo __('Reset check time '); ?>
                                        </div>

                                        <div class="browser-action margin-top-10"
                                             ng-click="serviceDowntime(getObjectsForExternalCommand())">
                                            <i class="fa fa-power-off"></i>
                                            <?php echo __('Schedule maintenance'); ?>
                                        </div>

                                        <div class="browser-action margin-top-10"
                                             ng-show="servicestatus.currentState > 0"
                                             ng-click="acknowledgeService(getObjectsForExternalCommand())">
                                            <i class="fa fa-user"></i>
                                            <?php echo __('Acknowledge service status'); ?>
                                        </div>

                                        <div class="browser-action margin-top-10"
                                             ng-click="submitServiceResult(getObjectsForExternalCommand())">
                                            <i class="fa fa-download"></i>
                                            <?php echo __('Passive transfer check result'); ?>
                                        </div>

                                        <div class="browser-action margin-top-10"
                                             ng-click="enableServiceFlapDetection(getObjectsForExternalCommand())"
                                             ng-show="!servicestatus.flap_detection_enabled">
                                            <i class="fa fa-adjust"></i>
                                            <?php echo __('Enable flap detection'); ?>
                                        </div>

                                        <div class="browser-action margin-top-10"
                                             ng-click="disableServiceFlapDetection(getObjectsForExternalCommand())"
                                             ng-show="servicestatus.flap_detection_enabled">
                                            <i class="fa fa-adjust"></i>
                                            <?php echo __('Disable flap detection'); ?>
                                        </div>

                                        <div class="browser-action margin-top-10"
                                             ng-click="enableServiceNotifications(getObjectsForExternalCommand())"
                                             ng-show="!servicestatus.notifications_enabled">
                                            <i class="fa fa-envelope"></i>
                                            <?php echo __('Enable notifications'); ?>
                                        </div>

                                        <div class="browser-action margin-top-10"
                                             ng-click="disableServiceNotifications(getObjectsForExternalCommand())"
                                             ng-show="servicestatus.notifications_enabled">
                                            <i class="fa fa-envelope"></i>
                                            <?php echo __('Disable notifications'); ?>
                                        </div>

                                        <div class="browser-action margin-top-10"
                                             ng-click="submitServiceNotification(getObjectsForExternalCommand())">
                                            <i class="fa fa-envelope"></i>
                                            <?php echo __('Send custom service notification '); ?>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                    <!-- Status information tab end -->
                    <!-- Service information tab start -->
                    <div ng-show="selectedTab == 'tab2'" class="tab-pane">
                        <div class="row">
                            <div class="col-lg-12 padding-10">
                                <div class="row">

                                    <div class="col-lg-12">
                                        <h3 class="margin-top-0"><?php echo __('Service overview'); ?></h3>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-6">
                                        <table class="table table-bordered table-sm">
                                            <tr>
                                                <td><?php echo __('Host ip address'); ?></td>
                                                <td>{{ host.Host.address }}</td>
                                            </tr>

                                            <tr>
                                                <td><?php echo __('Flap detection enabled'); ?></td>
                                                <td>
                                                        <span class="badge badge-danger"
                                                              ng-show="servicestatus.flap_detection_enabled">
                                                            <?php echo __('Yes'); ?>
                                                        </span>

                                                    <span class="badge badge-success"
                                                          ng-show="!servicestatus.flap_detection_enabled">
                                                            <?php echo __('No'); ?>
                                                        </span>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>

                                    <div class="col-xs-12 col-sm-12 col-md-6">
                                        <table class="table table-bordered table-sm">
                                            <tr>
                                                <td><?php echo __('Priority'); ?></td>
                                                <td>
                                                    <i class="fa fa-fire fa-lg pointer text-muted"
                                                       ng-class="{'ok-soft': mergedService.priority == 1, 'ok': mergedService.priority == 2, 'warning': mergedService.priority == 3, 'critical-soft': mergedService.priority == 4, 'critical': mergedService.priority == 5}"></i>
                                                    <i class="fa fa-fire fa-lg pointer text-muted"
                                                       ng-class="{'ok': mergedService.priority == 2, 'warning': mergedService.priority == 3, 'critical-soft': mergedService.priority == 4, 'critical': mergedService.priority == 5}"></i>
                                                    <i class="fa fa-fire fa-lg pointer text-muted"
                                                       ng-class="{'warning': mergedService.priority == 3, 'critical-soft': mergedService.priority == 4, 'critical': mergedService.priority == 5}"></i>
                                                    <i class="fa fa-fire fa-lg pointer text-muted"
                                                       ng-class="{'critical-soft': mergedService.priority == 4, 'critical': mergedService.priority == 5}"></i>
                                                    <i class="fa fa-fire fa-lg pointer text-muted"
                                                       ng-class="{'critical': mergedService.priority == 5}"></i>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td><?php echo __('Host UUID'); ?></td>
                                                <td class="copy-to-clipboard-container-text">
                                                    <code>{{ host.Host.uuid }}</code>
                                                    <span ng-click="rootCopyToClipboard(host.Host.uuid, $event)"
                                                          class="copy-action-visibility text-primary animated"
                                                          data-copied="<?= __('Copied'); ?>"
                                                          data-copy="<?= __('Copy'); ?>"
                                                    >
                                                        <?= __('Copy'); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td><?php echo __('Service UUID'); ?></td>
                                                <td class="copy-to-clipboard-container-text">
                                                    <code>{{ mergedService.uuid }}</code>
                                                    <span ng-click="rootCopyToClipboard(mergedService.uuid, $event)"
                                                          class="copy-action-visibility text-primary animated"
                                                          data-copied="<?= __('Copied'); ?>"
                                                          data-copy="<?= __('Copy'); ?>"
                                                    >
                                                        <?= __('Copy'); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td><?php echo __('Service type'); ?></td>
                                                <td>
                                                    <span
                                                        class="badge border margin-right-10 {{serviceType.class}} {{serviceType.color}}">
                                                        <i class="{{serviceType.icon}}"></i>
                                                        {{serviceType.title}}
                                                    </span>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>

                                    <div class="col-lg-6">
                                        <table class="table table-bordered table-sm">
                                            <tr>
                                                <td><?php echo __('Container'); ?></td>

                                                <td>
                                                    <?php if ($this->Acl->hasPermission('index', 'browsers')): ?>
                                                        <span ng-repeat="container in mainContainer">
                                                                /
                                                                <a ui-sref="BrowsersIndex({containerId: container.id})"
                                                                   ng-if="container.id != null">
                                                                    {{container.name}}
                                                                </a>

                                                                <span ng-if="container.id === null">
                                                                    {{container.name}}
                                                                </span>
                                                            </span>
                                                    <?php else: ?>
                                                        <span ng-repeat="container in mainContainer">
                                                                /
                                                            {{container.name}}
                                                        </span>
                                                    <?php endif; ?>
                                                </td>

                                            </tr>
                                            <tr>
                                                <td><?php echo __('Shared containers'); ?></td>
                                                <td>

                                                    <?php if ($this->Acl->hasPermission('index', 'browsers')): ?>
                                                        <div ng-repeat="sharing in sharedContainers">
                                                            <span ng-repeat="container in sharing">
                                                                /
                                                                <a ui-sref="BrowsersIndex({containerId: container.id})"
                                                                   ng-if="container.id != null">
                                                                    {{container.name}}
                                                                </a>

                                                                <span ng-if="container.id === null">
                                                                    {{container.name}}
                                                                </span>
                                                            </span>
                                                        </div>
                                                    <?php else: ?>
                                                        <div ng-repeat="sharing in sharedContainers">
                                                            <span ng-repeat="container in sharing">
                                                                /
                                                                {{container.name}}
                                                            </span>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <tr ng-show="tags.length">
                                                <td><?php echo __('Tags'); ?></td>
                                                <td>
                                                        <span class="badge badge-primary"
                                                              ng-repeat="tag in tags"
                                                              style="margin-right: 2px;">{{tag}}</span>
                                                </td>
                                            </tr>

                                            <tr ng-show="host.Host.is_satellite_host">
                                                <td><?php echo __('Satellite'); ?></td>
                                                <td>
                                                    <satellite-name
                                                        satellite-id="host.Host.satelliteId"
                                                        ng-if="host.Host.is_satellite_host"
                                                    ></satellite-name>
                                                </td>
                                            </tr>

                                            <tr ng-show="host.Host.is_satellite_host === false">
                                                <td><?php echo __('Satellite'); ?></td>
                                                <td>
                                                    <?php if (isset($masterInstanceName)) echo h($masterInstanceName); ?>
                                                </td>

                                            <tr ng-show="mergedService.notes">
                                                <td><?php echo __('Notes'); ?></td>
                                                <td>
                                                    {{mergedService.notes}}
                                                </td>
                                            </tr>
                                            <tr ng-show="mergedService.description">
                                                <td><?php echo __('Description'); ?></td>
                                                <td>
                                                    {{mergedService.description}}
                                                </td>
                                            </tr>
                                            <tr ng-if="objects.Autoreports || objects.Eventcorrelations || objects.serviceGroups || objects.instantReports || objects.maps">
                                                <td><?php echo __('Used by'); ?></td>
                                                <td>
                                                    <?php if ($this->Acl->hasPermission('usedBy', 'services')): ?>

                                                        <a ng-if="objects.Instantreports.length > 0"
                                                           ui-sref="ServicesUsedBy({id: mergedService.id})">
                                                           <span
                                                               class="badge border margin-right-10 border-generic text-generic">
                                                                <i class="fa fa-file-invoice"></i> <?php echo __('Instant reports'); ?> ({{objects.Instantreports.length}})
                                                           </span>
                                                        </a>

                                                        <?php if (Plugin::isLoaded('AutoreportModule')): ?>
                                                            <a ng-if="objects.Autoreports.length > 0"
                                                               ui-sref="ServicesUsedBy({id: mergedService.id})">
                                                               <span
                                                                   class="badge border margin-right-10 border-generic text-generic">
                                                                    <i class="fa fa-file-invoice"></i> <?= __('Autoreports') ?> ({{objects.Autoreports.length}})
                                                               </span>
                                                            </a>
                                                        <?php endif; ?>
                                                        <?php if (Plugin::isLoaded('EventcorrelationModule')): ?>
                                                            <a ng-if="objects.Eventcorrelations.length > 0"
                                                               ui-sref="ServicesUsedBy({id: mergedService.id})">
                                                                <span
                                                                    class="badge border margin-right-10 border-generic text-generic">
                                                                    <i class="fas fa-sitemap fa-rotate-90"></i> <?= __('Event Correlation') ?> ({{objects.Eventcorrelations.length}})
                                                                </span>
                                                            </a>
                                                        <?php endif; ?>

                                                        <?php if (Plugin::isLoaded('MapModule')): ?>
                                                            <a ng-if="objects.Maps.length > 0"
                                                               ui-sref="ServicesUsedBy({id: mergedService.id})">
                                                               <span
                                                                   class="badge border margin-right-10 border-generic text-generic">
                                                                    <i class="fa fa-map-marker"></i> <?= __('Map') ?> ({{objects.Maps.length}})
                                                               </span>
                                                            </a>
                                                        <?php endif; ?>

                                                        <a ng-if="objects.Servicegroups.length > 0"
                                                           ui-sref="ServicesUsedBy({id: mergedService.id})">
                                                           <span
                                                               class="badge border margin-right-10 border-generic text-generic">
                                                                <i class="fas fa-server"></i> <?php echo __('Service Groups'); ?> ({{objects.Servicegroups.length}})
                                                           </span>
                                                        </a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Service information tab end -->
                    <!-- Timeline tab start -->
                    <div ng-show="showTimelineTab && selectedTab == 'tab3'">
                        <div class="row">
                            <div class="col-lg-12 padding-10">
                                <div class="row">
                                    <div class="col-lg-12">
                                        <h3 class="margin-top-0">
                                            <?php echo __('Outages: '); ?>
                                            <span ng-hide="failureDurationInPercent">
                                                    <i class="fa fa-refresh fa-spin txt-primary"></i>
                                                </span>
                                            <span ng-show="failureDurationInPercent">
                                                {{ (failureDurationInPercent) ? failureDurationInPercent + ' %' : '<?= __('
                                                No
                                                data
                                                available
                                                !'); ?>'}}
                                            </span>
                                        </h3>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-12">
                                        <div id="visualization"></div>
                                    </div>

                                    <div class="col-lg-12">
                                        <div class="row margin-top-10">
                                            <div class="col-lg-12 bold">
                                                <?= __('Legend'); ?>
                                                <span class="fw-300">
                                                    <i class="ng-binding">
                                                         <?= __('State types'); ?>
                                                    </i>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="row margin-top-5">
                                            <div class="col-xs-12 col-lg-3">
                                                <i class="fa fa-square ok-soft"></i>
                                                <?php echo __('Ok soft'); ?>
                                            </div>
                                            <div class="col-xs-12 col-lg-3 ">
                                                <i class="fa fa-square warning-soft"></i>
                                                <?php echo __('Warning soft'); ?>
                                            </div>
                                            <div class="col-xs-12 col-lg-3 ">
                                                <i class="fa fa-square critical-soft"></i>
                                                <?php echo __('Critical soft'); ?>
                                            </div>
                                            <div class="col-xs-12 col-lg-3 ">
                                                <i class="fa fa-square unknown-soft"></i>
                                                <?php echo __('Unknown soft'); ?>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-xs-12 col-lg-3">
                                                <i class="fa fa-square ok"></i>
                                                <?php echo __('Ok'); ?>
                                            </div>
                                            <div class="col-xs-12 col-lg-3 ">
                                                <i class="fa fa-square warning"></i>
                                                <?php echo __('Warning'); ?>
                                            </div>
                                            <div class="col-xs-12 col-lg-3 ">
                                                <i class="fa fa-square critical"></i>
                                                <?php echo __('Critical'); ?>
                                            </div>
                                            <div class="col-xs-12 col-lg-3 ">
                                                <i class="fa fa-square unknown"></i>
                                                <?php echo __('Unknown'); ?>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-xs-12 col-lg-3">
                                                <i class="fa fa-square text-primary"></i>
                                                <?php echo __('Downtime'); ?>
                                            </div>
                                            <div class="col-xs-12 col-lg-3 ">
                                                <i class="fa fa-square txt-ack"></i>
                                                <?php echo __('Acknowledged'); ?>
                                            </div>
                                            <div class="col-xs-12 col-lg-3 ">
                                                <i class="fa fa-square txt-notification"></i>
                                                <?php echo __('Notification'); ?>
                                            </div>
                                            <div class="col-xs-12 col-lg-3 ">
                                                <i class="fa fa-square txt-timerange"></i>
                                                <?php echo __('Check period'); ?>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-xs-12 col-lg-3">
                                                <i class="fa fa-square txt-downtime-cancelled"></i>
                                                <?php echo __('Downtime cancelled'); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row" ng-show="mergedService.has_graph">
                            <div class="col-lg-12 padding-10">
                                <div class="row">
                                    <div class="col-lg-12">
                                        <div class="form-group">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox"
                                                       class="custom-control-input"
                                                       id="SynchronizeTimes"
                                                       ng-model="synchronizeTimes">
                                                <label class="custom-control-label no-margin"
                                                       for="SynchronizeTimes">
                                                    <?= __('Synchronize times for timeline and service graph'); ?>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Timeline tab end -->
                    <!-- ServiceNow tab start -->
                    <div ng-show="selectedTab == 'tab4'">
                        <div class="jarviswidget margin-bottom-0 padding-10" id="wid-id-0">
                            <?php if (Plugin::isLoaded('ServicenowModule') && $this->Acl->hasPermission('service_configuration', 'elements', 'servicenowModule')): ?>
                                <servicenow-service-element last-load="{{ lastLoadDate }}"
                                                            service-uuid="{{ mergedService.uuid }}"
                                                            editable="<?php echo $this->Acl->hasPermission('edit', 'services'); ?>">
                                </servicenow-service-element>
                            <?php else: ?>
                                <label class="text-danger">
                                    <?php echo __('No permissions'); ?>
                                </label>
                            <?php endif; ?>
                        </div>
                    </div>
                    <!-- ServiceNow tab end -->
                    <!-- Customalert tab start -->
                    <div ng-show="selectedTab == 'tab5'">
                        <div class="jarviswidget margin-bottom-0 padding-10" id="wid-id-0">
                            <?php if (Plugin::isLoaded('CustomalertModule') && $this->Acl->hasPermission('history', 'customalerts', 'CustomalertModule')): ?>
                                <customalerts-history service-id="mergedService.id"></customalerts-history>
                            <?php endif; ?>
                        </div>
                    </div>
                    <!-- Customalert tab end -->
                    <!-- SLA Module start -->
                    <div ng-show="selectedTab == 'tab6'" ng-if="slaOverview && selectedTab == 'tab6'">
                        <?php if (Plugin::isLoaded('SLAModule') && $this->Acl->hasPermission('slaServiceInformation', 'Slas', 'SLAModule')): ?>
                            <sla-service-information-element
                                service-id="{{mergedService.id}}"></sla-service-information-element>
                        <?php else: ?>
                            <label class="text-danger">
                                <?php echo __('No permissions'); ?>
                            </label>
                        <?php endif; ?>
                    </div>
                    <!-- SLA Module end -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Service Graphs -->
<div class="row" ng-show="mergedService.has_graph">
    <div class="col-xl-12">
        <div id="panel-2" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?php echo __('Service'); ?>
                    <span class="fw-300"><i><?php echo __('graphs'); ?></i></span>
                </h2>
                <div class="panel-toolbar">
                    <div class="panel-toolbar">

                        <div class="form-group panelToolbarInput">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox"
                                       class="custom-control-input"
                                       id="ServiceGraphSmoothInterpolation"
                                       ng-model="graph.smoothInterpolation">
                                <label class="custom-control-label no-margin" for="ServiceGraphSmoothInterpolation">
                                    <?php echo __('Smooth'); ?>
                                </label>
                            </div>
                        </div>

                        <div class="form-group panelToolbarInput">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox"
                                       class="custom-control-input"
                                       id="ServiceGraphShowDatapoints"
                                       ng-model="graph.showDatapoints">
                                <label class="custom-control-label no-margin" for="ServiceGraphShowDatapoints">
                                    <?php echo __('Show data points'); ?>
                                </label>
                            </div>
                        </div>

                        <div class="form-group panelToolbarInput">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox"
                                       class="custom-control-input"
                                       id="ServiceGraphAutoreferesh"
                                       ng-model="graph.graphAutoRefresh">
                                <label class="custom-control-label no-margin" for="ServiceGraphAutoreferesh">
                                    <?php echo __('Auto refresh'); ?>
                                </label>
                            </div>
                        </div>
                        <div class="btn-group btn-group-xs panelToolbarInput">
                            <button class="btn btn-default dropdown-toggle waves-effect waves-themed" type="button"
                                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <?php echo __('Timerange: '); ?>{{availableTimeranges[currentSelectedTimerange]}}
                            </button>
                            <div class="dropdown-menu" x-placement="bottom-start"
                                 style="position: absolute; will-change: top, left; top: 37px; left: 0px;">

                                <a class="dropdown-item dropdown-item-xs"
                                   ng-repeat="(timerange, timerangeName) in availableTimeranges"
                                   href="javascript:void(0);" ng-click="changeGraphTimespan(timerange)">
                                    {{timerangeName}}
                                </a>
                            </div>
                        </div>

                        <div class="btn-group btn-group-xs panelToolbarInput">
                            <button class="btn btn-default dropdown-toggle waves-effect waves-themed" type="button"
                                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <?php echo __('Datasource: '); ?>
                                {{ currentDataSource | limitTo: 15 }}{{currentDataSource.length > 15 ? '...' : ''}}
                            </button>
                            <div class="dropdown-menu dropdown-menu-right" x-placement="bottom-start"
                                 style="position: absolute; will-change: top, left; top: 37px; left: 0px;">

                                <a class="dropdown-item dropdown-item-xs" ng-repeat="ds in dataSources"
                                   ng-click="changeDataSource(ds.key)" href="javascript:void(0);">
                                    {{ds.displayName}}
                                </a>
                            </div>
                        </div>

                        <div class="btn-group btn-group-xs panelToolbarInput"
                             ng-show="mergedService.service_type !== <?= PROMETHEUS_SERVICE ?>">
                            <button class="btn btn-default dropdown-toggle waves-effect waves-themed" type="button"
                                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <?php echo __('Aggregation: '); ?>
                                <span ng-show="currentAggregation === 'min'"><?= __('Minimum'); ?></span>
                                <span ng-show="currentAggregation === 'avg'"><?= __('Average'); ?></span>
                                <span ng-show="currentAggregation === 'max'"><?= __('Maximum'); ?></span>
                            </button>
                            <div class="dropdown-menu dropdown-menu-right" x-placement="bottom-start"
                                 style="position: absolute; will-change: top, left; top: 37px; left: 0px;">

                                <a class="dropdown-item dropdown-item-xs"
                                   ng-click="changeAggregation('min')" href="javascript:void(0);">
                                    <?= __('Minimum'); ?>
                                </a>

                                <a class="dropdown-item dropdown-item-xs"
                                   ng-click="changeAggregation('avg')" href="javascript:void(0);">
                                    <?= __('Average'); ?>
                                </a>

                                <a class="dropdown-item dropdown-item-xs"
                                   ng-click="changeAggregation('max')" href="javascript:void(0);">
                                    <?= __('Maximum'); ?>
                                </a>

                            </div>
                        </div>

                        <button class="btn btn-xs btn-default mr-1 shadow-0" ng-click="load()">
                            <i class="fas fa-sync"></i> <?php echo __('Refresh'); ?>
                        </button>
                    </div>
                </div>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <div id="graph_data_tooltip"></div>
                    <div id="graphCanvas" style="height: 300px;"></div>
                </div>
            </div>
        </div>
    </div>
</div>
