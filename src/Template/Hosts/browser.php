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
 * @var string $masterInstanceName
 * @var string $username
 * @var bool $blurryCommandLine
 */

use Cake\Core\Plugin;

?>

<query-handler-directive></query-handler-directive>

<div ng-init="flashMshStr='<?php echo __('Command sent successfully. Refresh in 5 seconds'); ?>'"></div>


<host-browser-menu
    ng-if="hostBrowserMenuConfig"
    config="hostBrowserMenuConfig"
    last-load-date="lastLoadDate"
    root-copy-to-clipboard="rootCopyToClipboard"></host-browser-menu>

<massdelete></massdelete>
<massdeactivate></massdeactivate>
<massactivate></massactivate>
<reschedule-host callback="showFlashMsg"></reschedule-host>
<disable-host-notifications callback="showFlashMsg"></disable-host-notifications>
<enable-host-notifications callback="showFlashMsg"></enable-host-notifications>
<acknowledge-host author="<?php echo h($username); ?>" callback="showFlashMsg"></acknowledge-host>
<host-downtime author="<?php echo h($username); ?>" callback="showFlashMsg"></host-downtime>
<submit-host-result max-check-attempts="{{hoststatus.max_check_attempts}}" callback="showFlashMsg"></submit-host-result>
<enable-host-flap-detection callback="showFlashMsg"></enable-host-flap-detection>
<disable-host-flap-detection callback="showFlashMsg"></disable-host-flap-detection>
<send-host-notification author="<?php echo h($username); ?>" callback="showFlashMsg"></send-host-notification>
<mass-delete-host-downtimes delete-url="/downtimes/delete/" callback="showFlashMsg"></mass-delete-host-downtimes>
<mass-delete-acknowledgements delete-url="/acknowledgements/delete/"
                              callback="showFlashMsg"></mass-delete-acknowledgements>
<?php if ($this->Acl->hasPermission('add', 'servicegroups')): ?>
    <add-services-to-servicegroup></add-services-to-servicegroup>
<?php endif; ?>

<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?php echo __('Host'); ?>:
                    <span class="fw-300"><i>{{ mergedHost.name }}</i></span>
                </h2>
                <div class="panel-toolbar">
                    <button class="btn btn-xs btn-default mr-1 shadow-0" ng-click="loadHost()">
                        <i class="fas fa-sync"></i> <?php echo __('Refresh'); ?>
                    </button>
                    <?php if ($this->Acl->hasPermission('index', 'hosts')): ?>
                        <a back-button href="javascript:void(0);" fallback-state='HostsIndex'
                           class="btn btn-default btn-xs mr-1 shadow-0">
                            <i class="fas fa-long-arrow-alt-left"></i> <?php echo __('Back'); ?>
                        </a>
                    <?php endif; ?>
                    <ul class="nav nav-tabs border-bottom-0 nav-tabs-clean flex-column flex-sm-row" role="tablist">
                        <li class="nav-item pointer">
                            <a class="nav-link"
                               ng-class="{'active': selectedTab == 'tab1'}"
                               data-toggle="tab" ng-click="selectedTab = 'tab1'; hideTimeline()"
                               role="tab">
                                <i class="fa fa-info">&nbsp;</i> <?php echo __('Status information'); ?>
                            </a>
                        </li>
                        <li class="nav-item pointer">
                            <a class="nav-link"
                               data-toggle="tab" ng-click="selectedTab = 'tab2'; hideTimeline()"
                               role="tab">
                                <i class="fa fa-hdd-o">&nbsp;</i> <?php echo __('Device information'); ?>
                            </a>
                        </li>
                        <?php if ($this->Acl->hasPermission('timeline', 'hosts')): ?>
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
                        <?php if (Plugin::isLoaded('GrafanaModule')): ?>
                            <li class="nav-item pointer" ng-show="GrafanaDashboardExists">
                                <a class="nav-link" data-toggle="tab" ng-click="selectedTab = 'tab5'; hideTimeline()"
                                   role="tab">
                                    <i class="far fa-chart-bar"></i>&nbsp;<?php echo __('Grafana'); ?>
                                </a>
                            </li>
                        <?php endif; ?>
                        <?php if (Plugin::isLoaded('ImportModule') && $this->Acl->hasPermission('additionalHostInformation', 'ExternalSystems', 'ImportModule')): ?>
                            <li class="nav-item pointer" ng-show="AdditionalInformationExists">
                                <a class="nav-link"
                                   ng-class="{'active': selectedTab == 'tab6'}"
                                   data-toggle="tab" ng-click="selectedTab = 'tab6'; hideTimeline()"
                                   role="tab">
                                    <i class="fas fa-database">&nbsp;</i> <?php echo __('CMDB'); ?>
                                </a>
                            </li>
                        <?php endif; ?>
                        <?php if (Plugin::isLoaded('IsarFlowModule') && $this->Acl->hasPermission('isarFlowHostInformation', 'IsarflowHosts', 'IsarFlowModule')): ?>
                            <li class="nav-item pointer" ng-show="isarFlowInformationExists">
                                <a class="nav-link"
                                   ng-class="{'active': selectedTab == 'tab8'}"
                                   data-toggle="tab" ng-click="selectedTab = 'tab8'; hideTimeline()"
                                   role="tab">
                                    <i class="fa-solid fa-square-poll-horizontal">&nbsp;</i> <?php echo __('IsarFlow'); ?>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
                <?php if (Plugin::isLoaded('SLAModule')): ?>
                    <div ng-show="slaOverview" ng-click="selectedTab = 'tab7'; hideTimeline()">
                        <button
                            class="btn btn-labeled btn-{{slaOverview.state}} btn-xs btn-w-m waves-effect waves-themed"
                            ng-hide="slaOverview.state === 'not_available'">
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
                    </div>
                <?php endif; ?>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <!-- Status information start -->
                    <div ng-show="selectedTab == 'tab1'" class="tab-pane">
                        <!-- status overview for small resolutions -->
                        <div class="d-sm-none"
                             ng-class="{'browser-state-green': stateIsUp(), 'browser-state-red': stateIsDown(), 'browser-state-gray': stateIsUnreachable(), 'browser-state-blue': stateIsNotInMonitoring()}"
                             ng-if="hoststatus">
                            <div class="row">
                                <div class="col-6 padding-left-25">
                                    <?php echo __('State'); ?>
                                </div>
                                <div class="col-6">
                                    {{ hoststatus.currentState | hostStatusName }}
                                </div>
                            </div>

                            <div ng-show="hoststatus.isInMonitoring">
                                <div class="row">
                                    <div class="col-6 padding-left-25">
                                        <?php echo __('State since'); ?>
                                    </div>
                                    <div class="col-6" title="{{ hoststatus.last_state_change_user }}">
                                        {{ hoststatus.last_state_change }}
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-6 padding-left-25">
                                        <?php echo __('Last check'); ?>
                                    </div>
                                    <div class="col-6" title="{{ hoststatus.lastCheckUser }}">
                                        {{ hoststatus.lastCheck }}
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-6 padding-left-25">
                                        <?php echo __('Next check'); ?>
                                    </div>
                                    <div class="col-6">
                                        <span
                                            ng-if="mergedHost.active_checks_enabled && mergedHost.is_satellite_host === false"
                                            title="{{ hoststatus.nextCheckUser }}">
                                            {{ hoststatus.nextCheck }}
                                        </span>
                                        <span
                                            ng-if="mergedHost.active_checks_enabled === false || mergedHost.is_satellite_host === true">
                                            <?php echo __('n/a'); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="row" ng-show="hoststatus.isHardstate">
                                    <div class="col-6 padding-left-25">
                                        <?php echo __('State type'); ?>
                                    </div>
                                    <div class="col-6">
                                        <?php echo __('Hard state'); ?>
                                        ({{hoststatus.current_check_attempt}}/{{hoststatus.max_check_attempts}})
                                    </div>
                                </div>
                                <div class="row" ng-show="!hoststatus.isHardstate">
                                    <div class="col-6 padding-left-25">
                                        <?php echo __('State type'); ?>
                                    </div>
                                    <div class="col-6">
                                        <?php echo __('Soft state'); ?>
                                        ({{hoststatus.current_check_attempt}}/{{hoststatus.max_check_attempts}})
                                    </div>
                                </div>

                                <div class="row justify-content-center padding-top-10 padding-bottom-10"
                                     ng-show="canSubmitExternalCommands && mergedHost.allowEdit">
                                    <div class="btn-group" role="group" aria-label="Basic example">
                                        <button type="button"
                                                class="btn btn-default"
                                                ng-click="rescheduleHost(getObjectsForExternalCommand())">
                                            <i class="fa fa-refresh"></i>
                                        </button>

                                        <button type="button"
                                                class="btn btn-default"
                                                ng-click="hostDowntime(getObjectsForExternalCommand())">
                                            <i class="fa fa-power-off"></i>
                                        </button>

                                        <button type="button"
                                                class="btn btn-default"
                                                ng-show="hoststatus.currentState > 0"
                                                ng-click="acknowledgeHost(getObjectsForExternalCommand())">
                                            <i class="fa fa-user"></i>
                                        </button>

                                        <button type="button"
                                                class="btn btn-default"
                                                ng-click="submitHostResult(getObjectsForExternalCommand())">
                                            <i class="fa fa-download"></i>
                                        </button>

                                        <button type="button"
                                                class="btn btn-default"
                                                ng-click="enableHostFlapDetection(getObjectsForExternalCommand())"
                                                ng-show="!hoststatus.flap_detection_enabled">
                                            <i class="fa fa-adjust"></i>
                                        </button>
                                        <button type="button"
                                                class="btn btn-default"
                                                ng-click="disableHostFlapDetection(getObjectsForExternalCommand())"
                                                ng-show="hoststatus.flap_detection_enabled">
                                            <i class="fa fa-adjust"></i>
                                        </button>

                                        <button type="button"
                                                class="btn btn-default"
                                                ng-click="enableHostNotifications(getObjectsForExternalCommand())"
                                                ng-show="!hoststatus.notifications_enabled">
                                            <i class="fa fa-envelope"></i>
                                        </button>
                                        <button type="button"
                                                class="btn btn-default"
                                                ng-click="disableHostNotifications(getObjectsForExternalCommand())"
                                                ng-show="hoststatus.notifications_enabled">
                                            <i class="fa fa-envelope"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div
                                class="col-xs-12 col-sm-6 col-md-7 col-lg-9 padding-bottom-10 padding-left-10 padding-right-10">
                                <div class="alert alert-danger opacity-80 margin-bottom-5" role="alert"
                                     ng-show="mergedHost.disabled">
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
                                            <?= __('This host is currently disabled!'); ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-12">
                                        <h3 class="margin-top-5"><?php echo __('Status overview'); ?></h3>
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
                                                            <?php echo __('The host is currently in a planned maintenance period'); ?>
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
                                                            ng-click="confirmHostDowntimeDelete(getObjectForDowntimeDelete())">
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
                                                               ng-show="!acknowledgement.is_sticky"></i>
                                                            <i class="fas fa-user"
                                                               ng-show="acknowledgement.is_sticky"></i>
                                                            <?php echo __('State of host is acknowledged'); ?>
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
                                                            ng-click="confirmAcknowledgementsDelete(getObjectForAcknowledgementDelete())">
                                                            <i class="fa fa-trash"></i> <?php echo __('Delete'); ?>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row" ng-show="hoststatus.isFlapping">
                                    <div class="col-lg-12 margin-bottom-10">
                                        <div class="browser-border padding-10" style="width: 100%;">
                                            <div>
                                                <h4 class="no-padding txt-color-orangeDark">
                                                    <i class="fa fa-exclamation-triangle"></i>
                                                    <?php echo __('The state of this host is currently flapping!'); ?>
                                                </h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row" ng-show="hasParentHostProblems">
                                    <div class="col-lg-12 margin-bottom-10">
                                        <div class="browser-border padding-10" style="width: 100%;">
                                            <div>
                                                <h4 class="no-padding text-info">
                                                    <i class="fa fa-exclamation-triangle"></i>
                                                    <?php echo __('Problem with parent host detected!'); ?>
                                                </h4>
                                            </div>
                                            <div>
                                                <ul>
                                                    <li ng-repeat="parentHostProblem in parentHostProblems">
                                                        <a ui-sref="HostsBrowser({id:parentHostProblem.id})">
                                                            {{parentHostProblem.name}}
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-xs-12 col-sm-12 col-md-9">
                                        <table class="table table-bordered table-sm">
                                            <?php if ($this->Acl->hasPermission('checkcommand', 'hosts')): ?>
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
                                                            {{ mergedHost.hostCommandLine }}
                                                        </code>

                                                        <span
                                                            ng-click="rootCopyToClipboard(mergedHost.hostCommandLine, $event)"
                                                            class="copy-action text-primary animated copy-action-top-right"
                                                            data-copied="<?= __('Copied'); ?>"
                                                            data-copy="<?= __('Copy'); ?>">
                                                            <?= __('Copy'); ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>

                                            <tr>
                                                <td><?php echo __('Output'); ?></td>
                                                <td>
                                                    <div class="code-font" ng-class="hostStatusTextClass"
                                                         ng-bind-html="hoststatus.outputHtml | trustAsHtml"></div>
                                                </td>
                                            </tr>
                                            <tr ng-show="hoststatus.currentState > 0">
                                                <td>
                                                    <?php echo __('Last time'); ?>
                                                    <span class="badge badge-success" style="margin-right: 2px;">
                                                        UP
                                                    </span>
                                                </td>
                                                <td>
                                                    {{ hoststatus.last_time_up }}
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
                                                    {{ mergedHost.checkIntervalHuman }}
                                                </td>
                                            </tr>

                                            <tr>
                                                <td><?php echo __('Retry interval'); ?></td>
                                                <td>
                                                    {{ mergedHost.retryIntervalHuman }}
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>

                                <div class="row padding-bottom-10" ng-show="hoststatus.longOutputHtml">
                                    <div class="col-12">
                                        <h5 class="margin-top-5"><?php echo __('Long output'); ?></h5>
                                    </div>
                                    <div class="col-12">
                                        <div class="card bg-light">
                                            <div class="card-body"
                                                 ng-bind-html="hoststatus.longOutputHtml | trustAsHtml"></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row" ng-show="parenthosts.length">
                                    <div class="col-lg-12">
                                        <h3 class="margin-top-5"><?php echo __('Parent host overview'); ?></h3>
                                    </div>


                                    <div class="col-xs-12 col-sm-12">
                                        <table class="table table-bordered table-sm">
                                            <tr>
                                                <th class="width-130"><?php echo __('Parent host state'); ?></th>
                                                <th><?php echo __('Parent host name'); ?></th>
                                                <th><?php echo __('Last state change'); ?></th>
                                            </tr>
                                            <tr ng-repeat="parenthost in parenthosts">
                                                <td class="text-center">
                                                    <hoststatusicon
                                                        state="parentHoststatus[parenthost.uuid].currentState"></hoststatusicon>
                                                </td>
                                                <td>
                                                    <a ui-sref="HostsBrowser({id:parenthost.id})">
                                                        {{ parenthost.name }}
                                                    </a>
                                                </td>
                                                <td>
                                                    {{ parentHoststatus[parenthost.uuid].last_state_change }}
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
                                        ng-hide="areContactsFromHost">
                                        <?php echo __('Contacts and contact groups got inherited from'); ?>
                                        <span ng-show="areContactsInheritedFromHosttemplate" class="bold">

                                            <?php if ($this->Acl->hasPermission('edit', 'hosttemplates')): ?>
                                                <a ui-sref="HosttemplatesEdit({id: mergedHost.hosttemplate_id})">
                                                    <?php echo __('host template'); ?>
                                                </a>
                                            <?php else: ?>
                                                <?php echo __('host template'); ?>
                                            <?php endif; ?>
                                        </span>
                                        .
                                    </div>

                                </div>

                                <div class="row">
                                    <div class="col-xs-12 col-sm-12 col-md-6">
                                        <table class="table table-bordered table-sm">
                                            <tr ng-show="mergedHost.contacts.length">
                                                <td><?php echo __('Contacts'); ?></td>
                                                <td>
                                                    <div ng-repeat="contact in mergedHost.contacts">
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

                                            <tr ng-show="mergedHost.contactgroups.length">
                                                <td><?php echo __('Contact groups'); ?></td>
                                                <td>
                                                    <div ng-repeat="contactgroup in mergedHost.contactgroups">
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
                                                <td>{{ mergedHost.notificationIntervalHuman }}</td>
                                            </tr>

                                            <tr>
                                                <td><?php echo __('Notifications enabled'); ?></td>
                                                <td>
                                                    <span class="badge badge-success"
                                                          ng-show="hoststatus.notifications_enabled">
                                                        <?php echo __('Yes'); ?>
                                                    </span>

                                                    <span class="badge badge-danger"
                                                          ng-show="!hoststatus.notifications_enabled">
                                                        <?php echo __('No'); ?>
                                                    </span>
                                                </td>
                                            </tr>

                                            <tr>
                                                <td><?php echo __('Notify on'); ?></td>
                                                <td>
                                                    <span class="badge badge-success"
                                                          ng-show="mergedHost.notify_on_recovery"
                                                          style="margin-right: 2px;">
                                                        <?php echo __('Recover'); ?>
                                                    </span>

                                                    <span class="badge badge-danger"
                                                          ng-show="mergedHost.notify_on_down"
                                                          style="margin-right: 2px;">
                                                        <?php echo __('Down'); ?>
                                                    </span>

                                                    <span class="badge badge-secondary"
                                                          ng-show="mergedHost.notify_on_unreachable"
                                                          style="margin-right: 2px;">
                                                        <?php echo __('Unreachable'); ?>
                                                    </span>

                                                    <span class="badge badge-primary"
                                                          ng-show="mergedHost.notify_on_flapping"
                                                          style="margin-right: 2px;">
                                                        <?php echo __('Flapping'); ?>
                                                    </span>

                                                    <span class="badge badge-primary"
                                                          ng-show="mergedHost.notify_on_downtime"
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
                                 ng-class="{'browser-state-green': stateIsUp(), 'browser-state-red': stateIsDown(),
                                 'browser-state-gray': stateIsUnreachable(), 'browser-state-blue browser-not-monitored': stateIsNotInMonitoring()}"
                                 ng-if="hoststatus">

                                <div class="text-center txt-color-white">
                                    <h1 class="font-size-50">
                                        {{ hoststatus.currentState | hostStatusName }}
                                    </h1>
                                </div>

                                <div ng-show="hoststatus.isInMonitoring">
                                    <div class="text-center txt-color-white">
                                        <div><?php echo __('State since'); ?></div>
                                        <h3 class="margin-top-0" title="{{ hoststatus.last_state_change_user }}">
                                            {{ hoststatus.last_state_change }}
                                        </h3>
                                    </div>

                                    <div class="text-center txt-color-white">
                                        <div><?php echo __('Last check'); ?></div>
                                        <h3 class="margin-top-0" title="{{ hoststatus.lastCheckUser }}">
                                            {{ hoststatus.lastCheck }}
                                        </h3>
                                    </div>

                                    <div class="text-center txt-color-white">
                                        <div><?php echo __('Next check'); ?></div>
                                        <h3 class="margin-top-0">
                                            <span
                                                ng-if="mergedHost.active_checks_enabled && mergedHost.is_satellite_host === false"
                                                title="{{ hoststatus.nextCheckUser }}">
                                                {{ hoststatus.nextCheck }}
                                                <small style="color: #333;"
                                                       ng-show="hoststatus.latency > 1">(+ {{ hoststatus.latency }})
                                                </small>
                                            </span>
                                            <span
                                                ng-if="mergedHost.active_checks_enabled === false || mergedHost.is_satellite_host === true">
                                                <?php echo __('n/a'); ?>
                                            </span>
                                        </h3>
                                    </div>

                                    <div class="text-center txt-color-white">
                                        <div><?php echo __('State type'); ?></div>
                                        <h3 class="margin-top-0" ng-show="hoststatus.isHardstate">
                                            <?php echo __('Hard state'); ?>
                                            ({{hoststatus.current_check_attempt}}/{{hoststatus.max_check_attempts}})
                                        </h3>

                                        <h3 class="margin-top-0" ng-show="!hoststatus.isHardstate">
                                            <?php echo __('Soft state'); ?>
                                            ({{hoststatus.current_check_attempt}}/{{hoststatus.max_check_attempts}})
                                        </h3>
                                    </div>

                                    <div ng-if="canSubmitExternalCommands && mergedHost.allowEdit">
                                        <div class="browser-action"
                                             ng-click="rescheduleHost(getObjectsForExternalCommand())">
                                            <i class="fa fa-refresh"></i>
                                            <?php echo __('Reset check time '); ?>
                                        </div>

                                        <div class="browser-action margin-top-10"
                                             ng-click="hostDowntime(getObjectsForExternalCommand())">
                                            <i class="fa fa-power-off"></i>
                                            <?php echo __('Schedule maintenance'); ?>
                                        </div>

                                        <div class="browser-action margin-top-10"
                                             ng-show="hoststatus.currentState > 0"
                                             ng-click="acknowledgeHost(getObjectsForExternalCommand())">
                                            <i class="fa fa-user"></i>
                                            <?php echo __('Acknowledge host status'); ?>
                                        </div>

                                        <div class="browser-action margin-top-10"
                                             ng-click="submitHostResult(getObjectsForExternalCommand())">
                                            <i class="fa fa-download"></i>
                                            <?php echo __('Passive transfer check result'); ?>
                                        </div>

                                        <div class="browser-action margin-top-10"
                                             ng-click="enableHostFlapDetection(getObjectsForExternalCommand())"
                                             ng-show="!hoststatus.flap_detection_enabled">
                                            <i class="fa fa-adjust"></i>
                                            <?php echo __('Enable flap detection'); ?>
                                        </div>

                                        <div class="browser-action margin-top-10"
                                             ng-click="disableHostFlapDetection(getObjectsForExternalCommand())"
                                             ng-show="hoststatus.flap_detection_enabled">
                                            <i class="fa fa-adjust"></i>
                                            <?php echo __('Disable flap detection'); ?>
                                        </div>

                                        <div class="browser-action margin-top-10"
                                             ng-click="enableHostNotifications(getObjectsForExternalCommand())"
                                             ng-show="!hoststatus.notifications_enabled">
                                            <i class="fa fa-envelope"></i>
                                            <?php echo __('Enable notifications'); ?>
                                        </div>

                                        <div class="browser-action margin-top-10"
                                             ng-click="disableHostNotifications(getObjectsForExternalCommand())"
                                             ng-show="hoststatus.notifications_enabled">
                                            <i class="fa fa-envelope"></i>
                                            <?php echo __('Disable notifications'); ?>
                                        </div>

                                        <div class="browser-action margin-top-10"
                                             ng-click="submitHostNotification(getObjectsForExternalCommand())">
                                            <i class="fa fa-envelope"></i>
                                            <?php echo __('Send custom host notification '); ?>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>

                    </div>
                    <!-- Status information end -->
                    <!-- Device information start -->
                    <div ng-show="selectedTab == 'tab2'" class="tab-pane">
                        <div class="row">
                            <div class="col-lg-12 padding-10">
                                <div class="row">

                                    <div class="col-lg-12">
                                        <h3 class="margin-top-0"><?php echo __('Host overview'); ?></h3>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-6">
                                        <table class="table table-bordered table-sm">
                                            <tr>
                                                <td><?php echo __('IP address'); ?></td>
                                                <td>{{ mergedHost.address }}</td>
                                            </tr>

                                            <tr>
                                                <td><?php echo __('Flap detection enabled'); ?></td>
                                                <td>
                                                    <span class="badge badge-danger"
                                                          ng-show="hoststatus.flap_detection_enabled">
                                                        <?php echo __('Yes'); ?>
                                                    </span>

                                                    <span class="badge badge-success"
                                                          ng-show="!hoststatus.flap_detection_enabled">
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
                                                    <i class="fa fa-fire"
                                                       ng-repeat="priority in priorities"
                                                       ng-class="{'{{priorityClasses[mergedHost.priority]}}': priority,'text-muted': !priority}"
                                                       style="font-size:17px;margin-left:2px;">
                                                    </i>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td><?php echo __('UUID'); ?></td>
                                                <td class="copy-to-clipboard-container-text">
                                                    <code>{{ mergedHost.uuid }}</code>
                                                    <span ng-click="rootCopyToClipboard(mergedHost.uuid, $event)"
                                                          class="copy-action-visibility text-primary animated"
                                                          data-copied="<?= __('Copied'); ?>"
                                                          data-copy="<?= __('Copy'); ?>"
                                                    >
                                                        <?= __('Copy'); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>

                                    <div class="col-lg-12">
                                        <table class="table table-striped m-0 table-bordered table-hover table-sm">
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

                                            <tr ng-show="mergedHost.is_satellite_host">
                                                <td><?php echo __('Satellite'); ?></td>
                                                <td>
                                                    <satellite-name
                                                        satellite-id="mergedHost.satellite_id"
                                                        ng-if="mergedHost.is_satellite_host"
                                                    ></satellite-name>
                                                </td>
                                            </tr>

                                            <tr ng-show="mergedHost.is_satellite_host === false">
                                                <td><?php echo __('Satellite'); ?></td>
                                                <td>
                                                    <?php if (isset($masterInstanceName)) echo h($masterInstanceName); ?>
                                                </td>
                                            </tr>

                                            <tr ng-show="mergedHost.notes">
                                                <td><?php echo __('Notes'); ?></td>
                                                <td>
                                                    {{mergedHost.notes}}
                                                </td>
                                            </tr>
                                            <tr ng-show="mergedHost.description">
                                                <td><?php echo __('Description'); ?></td>
                                                <td>
                                                    {{mergedHost.description}}
                                                </td>
                                            </tr>
                                            <tr ng-if="objects.Autoreports || objects.Eventcorrelations || objects.hostGroups || objects.instantReports || objects.maps">
                                                <td><?php echo __('Used by'); ?></td>
                                                <td>
                                                    <?php if ($this->Acl->hasPermission('usedBy', 'hosts')): ?>

                                                        <a ng-if="objects.Instantreports.length > 0"
                                                           ui-sref="HostsUsedBy({id: mergedHost.id})">
                                                            <span
                                                                class="badge border margin-right-10 border-generic text-generic">
                                                                <i class="fa fa-file-invoice"></i> <?php echo __('Instant reports'); ?>
                                                                ({{objects.Instantreports.length}})
                                                            </span>
                                                        </a>

                                                        <?php if (Plugin::isLoaded('AutoreportModule')): ?>
                                                            <a ng-if="objects.Autoreports.length > 0"
                                                               ui-sref="HostsUsedBy({id: mergedHost.id})">
                                                                <span
                                                                    class="badge border margin-right-10 border-generic text-generic">
                                                                    <i class="fa fa-file-invoice"></i> <?= __('Autoreports') ?>
                                                                    ({{objects.Autoreports.length}})
                                                                </span>
                                                            </a>
                                                        <?php endif; ?>
                                                        <?php if (Plugin::isLoaded('EventcorrelationModule')): ?>
                                                            <a ng-if="objects.Eventcorrelations.length > 0"
                                                               ui-sref="HostsUsedBy({id: mergedHost.id})">
                                                                <span
                                                                    class="badge border margin-right-10 border-generic text-generic">
                                                                    <i class="fas fa-sitemap fa-rotate-90"></i> <?= __('Event Correlation') ?>
                                                                    ({{objects.Eventcorrelations.length}})
                                                                </span>
                                                            </a>
                                                        <?php endif; ?>

                                                        <?php if (Plugin::isLoaded('MapModule')): ?>
                                                            <a ng-if="objects.Maps.length > 0"
                                                               ui-sref="HostsUsedBy({id: mergedHost.id})">
                                                                <span
                                                                    class="badge border margin-right-10 border-generic text-generic">
                                                                    <i class="fa fa-map-marker"></i> <?= __('Map') ?>
                                                                    ({{objects.Maps.length}})
                                                                </span>
                                                            </a>
                                                        <?php endif; ?>

                                                        <a ng-if="objects.Hostgroups.length > 0"
                                                           ui-sref="HostsUsedBy({id: mergedHost.id})">
                                                            <span
                                                                class="badge border margin-right-10 border-generic text-generic">
                                                                <i class="fas fa-server"></i> <?php echo __('Host Groups'); ?>
                                                                ({{objects.Hostgroups.length}})
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
                    <!-- Device information end -->
                    <!-- Timeline start -->
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
                                                {{(failureDurationInPercent) ? failureDurationInPercent + ' %' :
                                                '<?= __('
                                                No
                                                data
                                                available
                                                !'); ?>'}}
                                            </span>
                                        </h3>
                                    </div>
                                    <div class="col-12">
                                        <div id="visualization"></div>
                                    </div>

                                    <div class="col-lg-12">
                                        <div class="row">
                                            <div class="col-lg-12 bold"><?php echo __('Legend'); ?></div>
                                            <div class="col-lg-12">
                                                <?php echo __('State types'); ?>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-xs-12 col-lg-3">
                                                <i class="fa fa-square up-soft"></i>
                                                <?php echo __('Up soft'); ?>
                                            </div>
                                            <div class="col-xs-12 col-lg-3 ">
                                                <i class="fa fa-square down-soft"></i>
                                                <?php echo __('Down soft'); ?>
                                            </div>
                                            <div class="col-xs-12 col-lg-3 ">
                                                <i class="fa fa-square unreachable-soft"></i>
                                                <?php echo __('Unreachable soft'); ?>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-xs-12 col-lg-3">
                                                <i class="fa fa-square up"></i>
                                                <?php echo __('Up hard'); ?>
                                            </div>
                                            <div class="col-xs-12 col-lg-3 ">
                                                <i class="fa fa-square down"></i>
                                                <?php echo __('Down hard'); ?>
                                            </div>
                                            <div class="col-xs-12 col-lg-3 ">
                                                <i class="fa fa-square unreachable"></i>
                                                <?php echo __('Unreachable hard'); ?>
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
                    </div>
                    <!-- Timeline end -->
                    <!-- Servicenow Module start -->
                    <div class="" ng-show="selectedTab == 'tab4'">
                        <div class="jarviswidget margin-bottom-0 padding-10" id="wid-id-0">
                            <?php if (Plugin::isLoaded('ServicenowModule') && $this->Acl->hasPermission('host_configuration', 'elements', 'servicenowModule')): ?>
                                <servicenow-host-element last-load="{{ lastLoadDate }}"
                                                         host-uuid="{{ mergedHost.uuid }}"
                                                         editable="<?php echo $this->Acl->hasPermission('edit', 'hosts'); ?>">
                                </servicenow-host-element>
                            <?php else: ?>
                                <label class="text-danger">
                                    <?php echo __('No permissions'); ?>
                                </label>
                            <?php endif; ?>
                        </div>
                    </div>
                    <!-- Servicenow Module end -->
                    <!-- Grafana Module start -->
                    <div ng-if="GrafanaDashboardExists && selectedTab == 'tab5'">
                        <div class="widget-toolbar text-right padding-bottom-5">
                            <grafana-timepicker callback="grafanaTimepickerCallback"></grafana-timepicker>
                        </div>
                        <iframe-directive url="GrafanaIframeUrl"
                                          ng-if="GrafanaDashboardExists && selectedTab == 'tab5'"></iframe-directive>
                    </div>
                    <!-- Grafana Module end -->
                    <!-- Import Module start -->
                    <div ng-show="selectedTab == 'tab6'" ng-if="AdditionalInformationExists && selectedTab == 'tab6'">
                        <?php if (Plugin::isLoaded('ImportModule') && $this->Acl->hasPermission('additionalHostInformation', 'ExternalSystems', 'ImportModule')): ?>
                            <cmdb-additional-information-element host-id="{{mergedHost.id}}">

                            </cmdb-additional-information-element>
                        <?php else: ?>
                            <label class="text-danger">
                                <?php echo __('No permissions'); ?>
                            </label>
                        <?php endif; ?>
                    </div>
                    <!-- Import Module end -->
                    <!-- IsarFlow Module start -->
                    <div ng-show="selectedTab == 'tab8'" ng-if="isarFlowInformationExists && selectedTab == 'tab8'">
                        <?php if (Plugin::isLoaded('IsarFlowModule') && $this->Acl->hasPermission('isarFlowHostInformation', 'IsarflowHosts', 'IsarFlowModule')): ?>
                            <isar-flow-host-information-element
                                host-id="{{mergedHost.id}}"></isar-flow-host-information-element>
                        <?php else: ?>
                            <label class="text-danger">
                                <?php echo __('No permissions'); ?>
                            </label>
                        <?php endif; ?>
                    </div>
                    <!-- IsarFlow Module end -->
                    <!-- SLA Module start -->
                    <div ng-show="selectedTab == 'tab7'" ng-if="slaOverview && selectedTab == 'tab7'">
                        <?php if (Plugin::isLoaded('SLAModule') && $this->Acl->hasPermission('slaHostInformation', 'Slas', 'SLAModule')): ?>
                            <sla-host-information-element host-id="{{mergedHost.id}}"></sla-host-information-element>
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

<!-- Service list -->
<div class="row">
    <div class="col-xl-12">
        <div id="panel-2" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?php echo __('Service'); ?>
                    <span class="fw-300"><i><?php echo __('overview'); ?></i></span>

                    <div ng-show="hoststatus.currentState > 0"
                         class="badge badge-pill bg-primary-100 padding-0 font-weight-normal font-sm margin-left-5">
                        <div class="d-flex align-items-center padding-right-10">
                            <div class="alert-icon">
                                <span class="icon-stack icon-stack-md">
                                    <i class="base base-7 icon-stack-2x opacity-100 color-warning-700"></i>
                                    <i class="base base-7 icon-stack-1x opacity-100 color-warning-900"></i>
                                    <i class="ni ni-bell icon-stack-1x opacity-100 color-white"></i>
                                </span>
                            </div>
                            <div class="flex-1">
                                <span class="bold">
                                    <?= __('Problem with host detected.'); ?>
                                </span>
                                <span class="fw-300">
                                    <u>
                                        <?= __('Services output may not as expected!'); ?>
                                    </u>
                                </span>
                            </div>
                        </div>
                    </div>


                </h2>
                <div class="panel-toolbar">
                    <ul class="nav nav-tabs border-bottom-0 nav-tabs-clean flex-column flex-sm-row" role="tablist">
                        <li class="nav-item pointer">
                            <a class="nav-link active" data-toggle="tab" ng-click="changeTab('active')" role="tab">
                                <i class="fa fa-stethoscope">&nbsp;</i> <?php echo __('Active'); ?>
                            </a>
                        </li>
                        <li class="nav-item pointer">
                            <a class="nav-link" data-toggle="tab" ng-click="changeTab('notMonitored')" role="tab">
                                <i class="fa fa-user-md">&nbsp;</i> <?php echo __('Not monitored'); ?>
                            </a>
                        </li>
                        <li class="nav-item pointer">
                            <a class="nav-link" data-toggle="tab" ng-click="changeTab('disabled')" role="tab">
                                <i class="fa fa-plug">&nbsp;</i> <?php echo __('Disabled'); ?>
                            </a>
                        </li>
                    </ul>
                    <button class="btn btn-xs btn-default mr-1 shadow-0" ng-click="load()">
                        <i class="fas fa-sync"></i> <?php echo __('Refresh'); ?>
                    </button>
                    <?php if ($this->Acl->hasPermission('add', 'services')): ?>
                        <button ui-sref="ServicesAdd({hostId: mergedHost.id})" ng-if="mergedHost.allowEdit"
                                class="btn btn-xs btn-success">
                            <i class="fa fa-plus"></i>
                            <?php echo __('Add'); ?>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <div id="serviceTab1" class="tab-pane" ng-if="activeTab === 'active'">
                        <table class="table table-striped m-0 table-bordered table-hover table-sm">
                            <thead>
                            <tr>
                                <th colspan="2" class="no-sort" ng-click="orderBy('Servicestatus.current_state')">
                                    <i class="fa"
                                       ng-class="getSortClass('Servicestatus.current_state')"></i>
                                    <?php echo __('Service status'); ?>
                                </th>

                                <th class="no-sort text-center"
                                    ng-click="orderBy('Servicestatus.acknowledgement_type')">
                                    <i class="fa" ng-class="getSortClass('Servicestatus.acknowledgement_type')"></i>
                                    <i class="fa fa-user"
                                       title="<?php echo __('Acknowledgedment'); ?>"></i>
                                </th>

                                <th class="no-sort text-center"
                                    ng-click="orderBy('Servicestatus.scheduled_downtime_depth')">
                                    <i class="fa" ng-class="getSortClass('Servicestatus.scheduled_downtime_depth')"></i>
                                    <i class="fa fa-power-off"
                                       title="<?php echo __('in Downtime'); ?>"></i>
                                </th>

                                <th class="no-sort text-center"
                                    ng-click="orderBy('Servicestatus.notifications_enabled')">
                                    <i class="fa" ng-class="getSortClass('Servicestatus.notifications_enabled')"></i>
                                    <i class="fas fa-envelope" title="<?php echo __('Notifications enabled'); ?>">
                                    </i>
                                </th>

                                <th class="no-sort text-center">
                                    <i class="fa fa fa-area-chart"
                                       title="<?php echo __('Grapher'); ?>"></i>
                                </th>

                                <th class="no-sort text-center">
                                    <strong title="<?php echo __('Passively transferred service'); ?>">
                                        P
                                    </strong>
                                </th>

                                <th class="no-sort" ng-click="orderBy('servicename')">
                                    <i class="fa" ng-class="getSortClass('servicename')"></i>
                                    <?php echo __('Service name'); ?>
                                </th>

                                <th class="no-sort">
                                    <?php echo __('Service type'); ?>
                                </th>

                                <th class="no-sort tableStatewidth"
                                    ng-click="orderBy('Servicestatus.last_state_change')">
                                    <i class="fa"
                                       ng-class="getSortClass('Servicestatus.last_state_change')"></i>
                                    <?php echo __('Last state change'); ?>
                                </th>

                                <th class="no-sort" ng-click="orderBy('Servicestatus.output')">
                                    <i class="fa" ng-class="getSortClass('Servicestatus.output')"></i>
                                    <?php echo __('Service output'); ?>
                                </th>

                                <th class="no-sort text-center width-50">
                                    <i class="fa fa-gear"></i>
                                </th>
                            </tr>
                            <tr>
                                <th colspan="2">
                                    <div class="custom-control-inline">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox"
                                                   id="statusFilterOk"
                                                   class="custom-control-input"
                                                   name="checkbox"
                                                   checked="checked"
                                                   ng-model-options="{debounce: 500}"
                                                   ng-model="activeServiceFilter.Servicestatus.current_state.ok">
                                            <label
                                                class="custom-control-label custom-control-label-ok no-margin"
                                                for="statusFilterOk">&nbsp;</label>
                                        </div>
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox"
                                                   id="statusFilterWarning"
                                                   class="custom-control-input"
                                                   name="checkbox"
                                                   checked="checked"
                                                   ng-model-options="{debounce: 500}"
                                                   ng-model="activeServiceFilter.Servicestatus.current_state.warning">
                                            <label
                                                class="custom-control-label custom-control-label-warning no-margin"
                                                for="statusFilterWarning">&nbsp;</label>
                                        </div>
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox"
                                                   id="statusFilterCritical"
                                                   class="custom-control-input"
                                                   name="checkbox"
                                                   checked="checked"
                                                   ng-model-options="{debounce: 500}"
                                                   ng-model="activeServiceFilter.Servicestatus.current_state.critical">
                                            <label
                                                class="custom-control-label custom-control-label-critical no-margin"
                                                for="statusFilterCritical">&nbsp;</label>
                                        </div>
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox"
                                                   id="statusFilterUnknown"
                                                   class="custom-control-input"
                                                   name="checkbox"
                                                   checked="checked"
                                                   ng-model-options="{debounce: 500}"
                                                   ng-model="activeServiceFilter.Servicestatus.current_state.unknown">
                                            <label
                                                class="custom-control-label custom-control-label-unknown no-margin"
                                                for="statusFilterUnknown">&nbsp;</label>
                                        </div>
                                    </div>
                                </th>

                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>

                                <th>
                                    <div class="form-group">
                                        <div class="input-group input-group-sm">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fa fa-cog"></i></span>
                                            </div>
                                            <input type="text" class="form-control form-control-sm"
                                                   placeholder="<?php echo __('Filter by service name'); ?>"
                                                   ng-model="activeServiceFilter.Service.name"
                                                   ng-model-options="{debounce: 500}">
                                            <div class="input-group-append">
                                                <span class="input-group-text pt-0 pb-0">
                                                    <label>
                                                        <?= __('Enable RegEx'); ?>
                                                        <input type="checkbox"
                                                               ng-model="activeServiceFilter.Service.name_regex">
                                                    </label>
                                                    <regex-helper-tooltip class="pl-1 pb-1"></regex-helper-tooltip>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </th>

                                <th></th>
                                <th></th>

                                <th>
                                    <div class="form-group">
                                        <div class="input-group input-group-sm">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fa fa-filter"></i></span>
                                            </div>
                                            <input type="text" class="form-control form-control-sm"
                                                   placeholder="<?php echo __('Filter by output'); ?>"
                                                   ng-model="activeServiceFilter.Servicestatus.output"
                                                   ng-model-options="{debounce: 500}">
                                        </div>
                                    </div>
                                </th>
                                <th></th>

                            </tr>
                            </thead>
                            <tbody>

                            <tr ng-repeat="service in services">
                                <td class="width-5">
                                    <input type="checkbox"
                                           ng-model="massChange[service.Service.id]"
                                           ng-show="service.Service.allow_edit">
                                </td>
                                <td class="text-center">
                                    <servicestatusicon service="service"></servicestatusicon>
                                </td>

                                <td class="text-center">
                                    <?php if ($this->Acl->hasPermission('browser', 'services')): ?>
                                        <i class="far fa-user"
                                           ng-show="service.Servicestatus.problemHasBeenAcknowledged"
                                           id="ackServicetip_{{service.Service.id}}"
                                           ng-mouseenter="enterAckEl($event, 'services', service.Service.id)"
                                           ng-mouseleave="leaveAckEl()"
                                           ng-if="service.Servicestatus.acknowledgement_type == 1">
                                        </i>
                                        <i class="fas fa-user"
                                           ng-show="service.Servicestatus.problemHasBeenAcknowledged"
                                           id="ackServicetip_{{service.Service.id}}"
                                           ng-mouseenter="enterAckEl($event, 'services', service.Service.id)"
                                           ng-mouseleave="leaveAckEl()"
                                           ng-if="service.Servicestatus.acknowledgement_type == 2">
                                        </i>
                                    <?php else: ?>
                                        <i class="far fa-user"
                                           ng-show="service.Servicestatus.problemHasBeenAcknowledged"
                                           ng-if="service.Servicestatus.acknowledgement_type == 1">
                                        </i>
                                        <i class="fas fa-user"
                                           ng-show="service.Servicestatus.problemHasBeenAcknowledged"
                                           ng-if="service.Servicestatus.acknowledgement_type == 2"
                                           title="<?php echo __('Sticky Acknowledgedment'); ?>">
                                        </i>
                                    <?php endif; ?>
                                </td>

                                <td class="text-center">
                                    <i class="fa fa-power-off"
                                        <?php if ($this->Acl->hasPermission('browser', 'services')): ?>
                                            id="downtimeServicetip_{{service.Service.id}}"
                                            ng-mouseenter="enterDowntimeEl($event, 'services', service.Service.id)"
                                            ng-mouseleave="leaveDowntimeEl()"
                                        <?php endif; ?>
                                       ng-show="service.Servicestatus.scheduledDowntimeDepth > 0"></i>
                                </td>

                                <td class="text-center">
                                    <div class="icon-stack margin-right-5"
                                         title="<?= __('Notifications enabled'); ?>"
                                         ng-show="service.Servicestatus.notifications_enabled">
                                        <i class="fas fa-envelope opacity-100 "></i>
                                        <i class="fas fa-check opacity-100 fa-xs text-success cornered cornered-lr"></i>
                                    </div>
                                    <div class="icon-stack margin-right-5"
                                         title="<?= __('Notifications disabled'); ?>"
                                         ng-hide="service.Servicestatus.notifications_enabled">
                                        <i class="fas fa-envelope opacity-100 "></i>
                                        <i class="fas fa-times opacity-100 fa-xs text-danger cornered cornered-lr"></i>
                                    </div>
                                </td>

                                <td class="text-center">
                                    <?php if ($this->Acl->hasPermission('browser', 'services')): ?>
                                        <a ui-sref="ServicesBrowser({id:service.Service.id})"
                                           class="txt-color-blueDark"
                                           ng-mouseenter="mouseenter($event, mergedHost.uuid, service.Service.uuid)"
                                           ng-mouseleave="mouseleave()"
                                           ng-if="service.Service.has_graph">
                                            <i class="fa fa-lg fa-area-chart">
                                            </i>
                                        </a>
                                    <?php else: ?>
                                        <div ng-mouseenter="mouseenter($event, mergedHost.uuid, service.Service.uuid)"
                                             ng-mouseleave="mouseleave()"
                                             ng-if="service.Service.has_graph">
                                            <i class="fa fa-lg fa-area-chart">
                                            </i>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <td class="text-center">
                                    <strong title="<?php echo __('Passively transferred service'); ?>"
                                            ng-show="service.Service.active_checks_enabled === false || mergedHost.is_satellite_host === true">
                                        P
                                    </strong>
                                </td>

                                <td>
                                    <?php if ($this->Acl->hasPermission('browser', 'services')): ?>
                                        <a ui-sref="ServicesBrowser({id:service.Service.id})">
                                            {{ service.Service.servicename }}
                                        </a>
                                    <?php else: ?>
                                        {{ service.Service.servicename }}
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <span
                                        class="badge border margin-right-10 {{service.ServiceType.class}} {{service.ServiceType.color}}">
                                        <i class="{{service.ServiceType.icon}}"></i>
                                        {{service.ServiceType.title}}
                                    </span>
                                </td>

                                <td>
                                    {{ service.Servicestatus.last_state_change }}
                                </td>

                                <td>
                                    <div class="word-break"
                                         ng-bind-html="service.Servicestatus.outputHtml | trustAsHtml"></div>
                                </td>

                                <td class="width-50">
                                    <div class="btn-group btn-group-xs" role="group">
                                        <?php if ($this->Acl->hasPermission('edit', 'services')): ?>
                                            <a ui-sref="ServicesEdit({id: service.Service.id})"
                                               ng-if="service.Service.allow_edit"
                                               class="btn btn-default btn-lower-padding">
                                                <i class="fa fa-cog"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="javascript:void(0);"
                                               class="btn btn-default btn-lower-padding">
                                                <i class="fa fa-cog"></i></a>
                                        <?php endif; ?>
                                        <button type="button"
                                                class="btn btn-default dropdown-toggle btn-lower-padding"
                                                data-toggle="dropdown">
                                            <i class="caret"></i>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-right">
                                            <?php if ($this->Acl->hasPermission('edit', 'services')): ?>
                                                <a ui-sref="ServicesEdit({id: service.Service.id})"
                                                   ng-if="service.Service.allow_edit"
                                                   class="dropdown-item">
                                                    <i class="fa fa-cog"></i>
                                                    <?php echo __('Edit'); ?>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($this->Acl->hasPermission('copy', 'services')): ?>
                                                <a ui-sref="ServicesCopy({ids: service.Service.id})"
                                                   ng-if="service.Service.allow_edit"
                                                   class="dropdown-item">
                                                    <i class="fas fa-files-o"></i>
                                                    <?php echo __('Copy'); ?>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($this->Acl->hasPermission('deactivate', 'services')): ?>
                                                <a ng-click="confirmDeactivate(getObjectForDelete(mergedHost.name, service))"
                                                   ng-if="service.Service.allow_edit"
                                                   href="javascript:void(0);"
                                                   class="dropdown-item">
                                                    <i class="fa fa-plug"></i>
                                                    <?php echo __('Disable'); ?>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($this->Acl->hasPermission('index', 'changelogs')): ?>
                                                <a ui-sref="ChangelogsEntity({objectTypeId: 'service', objectId: service.Service.id})"
                                                   class="dropdown-item">
                                                    <i class="fa-solid fa-timeline fa-rotate-90"></i>
                                                    <?php echo __('Changelog'); ?>
                                                </a>
                                            <?php endif; ?>
                                            <?php
                                            $AdditionalLinks = new \App\Lib\AdditionalLinks($this);
                                            echo $AdditionalLinks->getLinksAsHtmlList('services', 'index', 'list');
                                            ?>
                                            <?php if ($this->Acl->hasPermission('delete', 'services')): ?>
                                                <div class="dropdown-divider"></div>
                                                <a ng-click="confirmDelete(getObjectForDelete(mergedHost.name, service))"
                                                   ng-if="service.Service.allow_edit"
                                                   class="dropdown-item txt-color-red">
                                                    <i class="fa fa-trash"></i>
                                                    <?php echo __('Delete'); ?>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                        <div class="margin-top-10" ng-show="services.length == 0">
                            <div class="text-center text-danger italic">
                                <?php echo __('No entries match the selection'); ?>
                            </div>
                        </div>

                        <div class="row margin-top-10 margin-bottom-10">
                            <div class="col-xs-12 col-md-2 text-muted text-center">
                                <span ng-show="selectedElements > 0">({{selectedElements}})</span>
                            </div>
                            <div class="col-xs-12 col-md-2">
                                <span ng-click="selectAll()" class="pointer">
                                    <i class="fas fa-lg fa-check-square"></i>
                                    <?php echo __('Select all'); ?>
                                </span>
                            </div>
                            <div class="col-xs-12 col-md-2">
                                <span ng-click="undoSelection()" class="pointer">
                                    <i class="fas fa-lg fa-square"></i>
                                    <?php echo __('Undo selection'); ?>
                                </span>
                            </div>

                            <?php if ($this->Acl->hasPermission('copy', 'services')): ?>
                                <div class="col-xs-12 col-md-2">
                                    <a ui-sref="ServicesCopy({ids: linkForCopy()})" class="a-clean">
                                        <i class="fas fa-lg fa-files-o"></i>
                                        <?php echo __('Copy'); ?>
                                    </a>
                                </div>
                            <?php endif; ?>

                            <?php if ($this->Acl->hasPermission('delete', 'services')): ?>
                                <div class="col-xs-12 col-md-2 txt-color-red">
                                    <span ng-click="confirmDelete(getServiceObjectsForDelete())" class="pointer">
                                        <i class="fas fa-trash"></i>
                                        <?php echo __('Delete selected'); ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-default dropdown-toggle waves-effect waves-themed" type="button"
                                        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <?php echo __('More actions'); ?>
                                </button>
                                <div class="dropdown-menu dropdown-menu-right" x-placement="bottom-start"
                                     style="position: absolute; will-change: top, left; top: 37px; left: 0px;">
                                    <?php if ($this->Acl->hasPermission('deactivate', 'services')): ?>
                                        <a
                                            class="dropdown-item"
                                            href="javascript:void(0);"
                                            ng-click="confirmDeactivate(getServiceObjectsForDelete())">
                                            <i class="fa fa-plug"></i>
                                            <?php echo __('Disable services'); ?>
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($this->Acl->hasPermission('add', 'servicegroups')): ?>
                                        <a class="dropdown-item" href="javascript:void(0);"
                                           ng-click="confirmAddServicesToServicegroup(getServiceObjectsForDelete())">
                                            <i class="fa fa-cogs"></i>
                                            <?php echo __('Add to service group'); ?>
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($this->Acl->hasPermission('externalcommands', 'hosts')): ?>
                                        <a class="dropdown-item" href="javascript:void(0);"
                                           ng-click="reschedule(getServiceObjectsForExternalCommand())">
                                            <i class="fa fa-refresh"></i> <?php echo __('Reset check time'); ?>
                                        </a>
                                        <a class="dropdown-item" href="javascript:void(0);"
                                           ng-click="disableNotifications(getServiceObjectsForExternalCommand())">
                                            <i class="fa fa-envelope"></i> <?php echo __('Disable notification'); ?>
                                        </a>
                                        <a class="dropdown-item" href="javascript:void(0);"
                                           ng-click="enableNotifications(getServiceObjectsForExternalCommand())">
                                            <i class="fa fa-envelope"></i> <?php echo __('Enable notifications'); ?>
                                        </a>
                                        <a class="dropdown-item" href="javascript:void(0);"
                                           ng-click="serviceDowntime(getServiceObjectsForExternalCommand())">
                                            <i class="fa fa-clock-o"></i> <?php echo __('Set planned maintenance times'); ?>
                                        </a>
                                        <a class="dropdown-item" href="javascript:void(0);"
                                           ng-click="acknowledgeService(getServiceObjectsForExternalCommand())">
                                            <i class="fa fa-user"></i> <?php echo __('Acknowledge status'); ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <scroll scroll="scroll" click-action="changepage" ng-if="scroll"></scroll>
                        <paginator paging="paging" click-action="changepage" ng-if="paging"></paginator>
                        <?php echo $this->element('paginator_or_scroll'); ?>

                        <popover-graph-directive></popover-graph-directive>

                    </div>

                    <div id="serviceTab2" class="tab-pane" ng-if="activeTab === 'notMonitored'">
                        <table class="table table-striped m-0 table-bordered table-hover table-sm">
                            <thead>
                            <tr>
                                <th class="no-sort width-90">
                                    <?php echo __('Servicestatus'); ?>
                                </th>


                                <th class="no-sort">
                                    <?php echo __('Service name'); ?>
                                </th>

                                <th class="no-sort text-center width-50">
                                    <i class="fa fa-gear"></i>
                                </th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr ng-repeat="service in services">
                                <td class="text-center width-90">
                                    <servicestatusicon service="fakeServicestatus"></servicestatusicon>
                                </td>

                                <td>
                                    <?php if ($this->Acl->hasPermission('browser', 'services')): ?>
                                        <a ui-sref="ServicesBrowser({id:service.Service.id})">
                                            {{ service.Service.servicename }}
                                        </a>
                                    <?php else: ?>
                                        {{ service.Service.servicename }}
                                    <?php endif; ?>
                                </td>

                                <td class="width-50">
                                    <div class="btn-group btn-group-xs" role="group">
                                        <?php if ($this->Acl->hasPermission('edit', 'services')): ?>
                                            <a ui-sref="ServicesEdit({id: service.Service.id})"
                                               ng-if="service.Service.allow_edit"
                                               class="btn btn-default btn-lower-padding">
                                                <i class="fa fa-cog"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="javascript:void(0);"
                                               class="btn btn-default btn-lower-padding">
                                                <i class="fa fa-cog"></i></a>
                                        <?php endif; ?>
                                        <button type="button"
                                                class="btn btn-default dropdown-toggle btn-lower-padding"
                                                data-toggle="dropdown">
                                            <i class="caret"></i>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-right">
                                            <?php if ($this->Acl->hasPermission('edit', 'services')): ?>
                                                <a ui-sref="ServicesEdit({id: service.Service.id})"
                                                   ng-if="service.Service.allow_edit"
                                                   class="dropdown-item">
                                                    <i class="fa fa-cog"></i>
                                                    <?php echo __('Edit'); ?>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($this->Acl->hasPermission('deactivate', 'services')): ?>
                                                <a ng-click="confirmDeactivate(getObjectForDelete(mergedHost.name, service))"
                                                   ng-if="service.Service.allow_edit"
                                                   href="javascript:void(0);"
                                                   class="dropdown-item">
                                                    <i class="fa fa-plug"></i>
                                                    <?php echo __('Disable'); ?>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($this->Acl->hasPermission('index', 'changelogs')): ?>
                                                <a ui-sref="ChangelogsEntity({objectTypeId: 'service', objectId: service.Service.id})"
                                                   class="dropdown-item">
                                                    <i class="fa-solid fa-timeline fa-rotate-90"></i>
                                                    <?php echo __('Changelog'); ?>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($this->Acl->hasPermission('copy', 'services')): ?>
                                                <div class="dropdown-divider"></div>
                                                <a ui-sref="ServicesCopy({ids: service.Service.id})"
                                                   ng-if="service.Service.allow_edit"
                                                   class="dropdown-item">
                                                    <i class="fas fa-files-o"></i>
                                                    <?php echo __('Copy'); ?>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($this->Acl->hasPermission('delete', 'services')): ?>
                                                <div class="dropdown-divider"></div>
                                                <a ng-click="confirmDelete(getObjectForDelete(mergedHost.name, service))"
                                                   ng-if="service.Service.allow_edit"
                                                   class="dropdown-item txt-color-red">
                                                    <i class="fa fa-trash"></i>
                                                    <?php echo __('Delete'); ?>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                        <div class="margin-top-10" ng-show="services.length == 0">
                            <div class="text-center text-danger italic">
                                <?php echo __('No entries match the selection'); ?>
                            </div>
                        </div>

                        <scroll scroll="scroll" click-action="changepage" ng-if="scroll"></scroll>
                        <paginator paging="paging" click-action="changepage" ng-if="paging"></paginator>
                        <?php echo $this->element('paginator_or_scroll'); ?>
                    </div>

                    <div id="serviceTab3" class="tab-pane" ng-if="activeTab === 'disabled'">
                        <table class="table table-striped m-0 table-bordered table-hover table-sm">
                            <thead>
                            <tr>
                                <th class="no-sort width-90" colspan="2">
                                    <?php echo __('Servicestatus'); ?>
                                </th>


                                <th class="no-sort">
                                    <?php echo __('Service name'); ?>
                                </th>

                                <th class="no-sort text-center width-50">
                                    <i class="fa fa-gear"></i>
                                </th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr ng-repeat="service in services">
                                <td class="width-5">
                                    <input type="checkbox"
                                           ng-model="massChange[service.Service.id]"
                                           ng-show="service.Service.allow_edit">
                                </td>
                                <td class="text-center width-90">
                                    <servicestatusicon service="fakeServicestatus"></servicestatusicon>
                                </td>

                                <td>
                                    <?php if ($this->Acl->hasPermission('browser', 'services')): ?>
                                        <a ui-sref="ServicesBrowser({id:service.Service.id})">
                                            {{ service.Service.servicename }}
                                        </a>
                                    <?php else: ?>
                                        {{ service.Service.servicename }}
                                    <?php endif; ?>
                                </td>

                                <td class="width-50">
                                    <div class="btn-group btn-group-xs" role="group">
                                        <?php if ($this->Acl->hasPermission('edit', 'services')): ?>
                                            <a ui-sref="ServicesEdit({id: service.Service.id})"
                                               ng-if="service.Service.allow_edit"
                                               class="btn btn-default btn-lower-padding">
                                                <i class="fa fa-cog"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="javascript:void(0);"
                                               class="btn btn-default btn-lower-padding">
                                                <i class="fa fa-cog"></i></a>
                                        <?php endif; ?>
                                        <button type="button"
                                                class="btn btn-default dropdown-toggle btn-lower-padding"
                                                data-toggle="dropdown">
                                            <i class="caret"></i>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-right">
                                            <?php if ($this->Acl->hasPermission('edit', 'services')): ?>
                                                <a ui-sref="ServicesEdit({id: service.Service.id})"
                                                   ng-if="service.Service.allow_edit"
                                                   class="dropdown-item">
                                                    <i class="fa fa-cog"></i>
                                                    <?php echo __('Edit'); ?>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($this->Acl->hasPermission('enable', 'services')): ?>
                                                <a ng-click="confirmActivate(getObjectForDelete(mergedHost.name, service))"
                                                   ng-if="service.Service.allow_edit"
                                                   href="javascript:void(0);"
                                                   class="dropdown-item">
                                                    <i class="fa fa-plug"></i>
                                                    <?php echo __('Enable'); ?>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($this->Acl->hasPermission('index', 'changelogs')): ?>
                                                <a ui-sref="ChangelogsEntity({objectTypeId: 'service', objectId: service.Service.id})"
                                                   class="dropdown-item">
                                                    <i class="fa-solid fa-timeline fa-rotate-90"></i>
                                                    <?php echo __('Changelog'); ?>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($this->Acl->hasPermission('copy', 'services')): ?>
                                                <div class="dropdown-divider"></div>
                                                <a ui-sref="ServicesCopy({ids: service.Service.id})"
                                                   ng-if="service.Service.allow_edit"
                                                   class="dropdown-item">
                                                    <i class="fas fa-files-o"></i>
                                                    <?php echo __('Copy'); ?>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($this->Acl->hasPermission('delete', 'services')): ?>
                                                <div class="dropdown-divider"></div>
                                                <a ng-click="confirmDelete(getObjectForDelete(mergedHost.name, service))"
                                                   ng-if="service.Service.allow_edit"
                                                   class="dropdown-item txt-color-red">
                                                    <i class="fa fa-trash"></i>
                                                    <?php echo __('Delete'); ?>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                        <div class="margin-top-10" ng-show="services.length == 0">
                            <div class="text-center text-danger italic">
                                <?php echo __('No entries match the selection'); ?>
                            </div>
                        </div>

                        <div class="row margin-top-10 margin-bottom-10">
                            <div class="col-xs-12 col-md-2 text-muted text-center">
                                <span ng-show="selectedElements > 0">({{selectedElements}})</span>
                            </div>
                            <div class="col-xs-12 col-md-2">
                                <span ng-click="selectAll()" class="pointer">
                                    <i class="fas fa-lg fa-check-square"></i>
                                    <?php echo __('Select all'); ?>
                                </span>
                            </div>
                            <div class="col-xs-12 col-md-2">
                                <span ng-click="undoSelection()" class="pointer">
                                    <i class="fas fa-lg fa-square"></i>
                                    <?php echo __('Undo selection'); ?>
                                </span>
                            </div>

                            <?php if ($this->Acl->hasPermission('enable', 'services')): ?>
                                <div class="col-xs-12 col-md-2">
                                    <a ng-click="confirmActivate(getServiceObjectsForDelete())" class="a-clean"
                                       href="javascript:void(0);">
                                        <i class="fa fa-lg fa-plug"></i>
                                        <?php echo __('Enable'); ?>
                                    </a>
                                </div>
                            <?php endif; ?>

                            <?php if ($this->Acl->hasPermission('delete', 'services')): ?>
                                <div class="col-xs-12 col-md-2 txt-color-red">
                                    <span ng-click="confirmDelete(getServiceObjectsForDelete())" class="pointer">
                                        <i class="fas fa-trash"></i>
                                        <?php echo __('Delete selected'); ?>
                                    </span>
                                </div>
                            <?php endif; ?>

                        </div>

                        <scroll scroll="scroll" click-action="changepage" ng-if="scroll"></scroll>
                        <paginator paging="paging" click-action="changepage" ng-if="paging"></paginator>
                        <?php echo $this->element('paginator_or_scroll'); ?>
                    </div>
                </div>
            </div>
            <ack-tooltip></ack-tooltip>
            <downtime-tooltip></downtime-tooltip>
        </div>
    </div>
</div>

<!-- Service mass change directives -->
<reschedule-service></reschedule-service>
<disable-notifications></disable-notifications>
<enable-notifications></enable-notifications>
<acknowledge-service author="<?php echo h($username); ?>"></acknowledge-service>
<service-downtime author="<?php echo h($username); ?>"></service-downtime>
