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
<div id="angularServiceStatusDetailsModal" class="modal" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa fa-cogs"></i>
                    <?php echo __('Service status details'); ?>
                </h5>
                <div class="ml-auto">
                    <button class="btn btn-default btn-xs" ng-click="loadServicestatusDetails(currentServiceDetailsId)">
                        <i class="fa fa-refresh" ng-class="{'fa-spin': isLoading}"></i>
                        <?php echo __('Refresh'); ?>
                    </button>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true"><i class="fa fa-times"></i></span>
                    </button>
                </div>
            </div>
            <div class="modal-body">
                <div ng-init="flashMshStr='<?php echo __('Command sent successfully. Refresh in 5 seconds'); ?>'"></div>

                <div class="txt-color-white padding-10"
                     ng-class="{'browser-state-green': stateIsOk(), 'browser-state-yellow': stateIsWarning(), 'browser-state-red': stateIsCritical(), 'browser-state-gray': stateIsUnknown(), 'browser-state-blue': stateIsNotInMonitoring()}"
                     ng-if="servicestatus">

                    <div class="row">
                        <div class="col-xs-12 col-md-2">
                            <strong><?php echo __('Host'); ?></strong>
                        </div>
                        <div class="col-xs-12 col-md-10">
                            <strong>{{host.Host.name}}</strong>
                        </div>
                    </div>


                    <div class="row">
                        <div class="col-xs-12 col-md-2">
                            <strong><?php echo __('Service'); ?></strong>
                        </div>
                        <div class="col-xs-12 col-md-10">
                            <strong>{{mergedService.name}}</strong>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-xs-12 col-md-2">
                            <?php echo __('State'); ?>
                        </div>
                        <div class="col-xs-12 col-md-10">
                            {{ servicestatus.currentState | serviceStatusName }}
                        </div>
                    </div>

                    <div ng-show="servicestatus.isInMonitoring">
                        <div class="row">
                            <div class="col-xs-12 col-md-2">
                                <?php echo __('State since'); ?>
                            </div>
                            <div class="col-xs-12 col-md-10">
                                {{ servicestatus.last_state_change }}
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-xs-12 col-md-2">
                                <?php echo __('Last check'); ?>
                            </div>
                            <div class="col-xs-12 col-md-10">
                                {{ servicestatus.lastCheck }}
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-xs-12 col-md-2">
                                <?php echo __(' Next check'); ?>
                            </div>
                            <div class="col-xs-12 col-md-10">
                                {{ servicestatus.nextCheck }}
                            </div>
                        </div>
                        <div class="row" ng-show="servicestatus.isHardstate">
                            <div class="col-xs-12 col-md-2">
                                <?php echo __('State type'); ?>
                            </div>
                            <div class="col-xs-12 col-md-10">
                                <?php echo __('Hard state'); ?>
                                ({{servicestatus.current_check_attempt}}/{{servicestatus.max_check_attempts}})
                            </div>
                        </div>
                        <div class="row" ng-show="!servicestatus.isHardstate">
                            <div class="col-xs-12 col-md-2">
                                <?php echo __('State type'); ?>
                            </div>
                            <div class="col-xs-12 col-md-10">
                                <?php echo __('Soft state'); ?>
                                ({{servicestatus.current_check_attempt}}/{{servicestatus.max_check_attempts}})
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-xs-12 col-md-2">
                                <?php echo __('Output'); ?>
                            </div>
                            <div class="col-xs-12 col-md-10">
                                <div
                                    ng-bind-html="servicestatus.outputHtml | trustAsHtml"></div>
                            </div>
                        </div>

                        <div class="row" ng-show="servicestatus.perfdata">
                            <div class="col-xs-12 col-md-2">
                                <?php echo __('Perfdata'); ?>
                            </div>
                            <div class="col-xs-12 col-md-10">
                                {{ servicestatus.perfdata }}
                            </div>
                        </div>

                        <div class="row d-flex justify-content-center margin-top-10"
                             ng-show="canSubmitExternalCommands && mergedService.allowEdit">
                            <div class="btn-group" role="group" aria-label="Basic example">
                                <button type="button"
                                        class="btn btn-default"
                                        title="<?php echo __('Reset check time'); ?>"
                                        ng-click="reschedule(getObjectsForExternalCommand())">
                                    <i class="fa fa-refresh"></i>
                                </button>

                                <button type="button"
                                        class="btn btn-default"
                                        title="<?php echo __('Schedule maintenance'); ?>"
                                        ng-click="serviceDowntime(getObjectsForExternalCommand())">
                                    <i class="fa fa-power-off"></i>
                                </button>

                                <button type="button"
                                        class="btn btn-default"
                                        title="<?php echo __('Acknowledge service status'); ?>"
                                        ng-show="servicestatus.currentState > 0"
                                        ng-click="acknowledgeService(getObjectsForExternalCommand())">
                                    <i class="fa fa-user"></i>
                                </button>

                                <button type="button"
                                        class="btn btn-default"
                                        title="<?php echo __('Passive transfer check result'); ?>"
                                        ng-click="submitServiceResult(getObjectsForExternalCommand())">
                                    <i class="fa fa-download"></i>
                                </button>

                                <button type="button"
                                        class="btn btn-default"
                                        title="<?php echo __('Enable flap detection'); ?>"
                                        ng-click="enableServiceFlapDetection(getObjectsForExternalCommand())"
                                        ng-show="!servicestatus.flap_detection_enabled">
                                    <i class="fa fa-adjust"></i>
                                </button>
                                <button type="button"
                                        class="btn btn-default"
                                        title="<?php echo __('Disable flap detection'); ?>"
                                        ng-click="disableServiceFlapDetection(getObjectsForExternalCommand())"
                                        ng-show="servicestatus.flap_detection_enabled">
                                    <i class="fa fa-adjust"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row" ng-show="servicestatus.scheduledDowntimeDepth > 0">
                    <div class="col-lg-12 padding-10">
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
                                            class="btn btn-xs btn-danger"
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
                    <div class="col-lg-12 padding-10">
                        <div class="browser-border padding-10" style="width: 100%;">
                            <div>
                                <h4 class="no-padding">
                                    <i class="fa fa-user" ng-show="!acknowledgement.is_sticky"></i>
                                    <i class="far fa-user" ng-show="acknowledgement.is_sticky"></i>
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
                    </div>
                </div>


                <div class="row" ng-show="servicestatus.isFlapping">
                    <div class="col-lg-12 padding-10">
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

                <div class="row padding-top-10" ng-show="mergedService.has_graph">
                    <div class="col-lg-12">
                        <h5>
                            <i class="fa fa-area-chart"></i>
                            <?php echo __('Service graph'); ?>
                        </h5>
                    </div>
                </div>

                <div class="row" ng-show="mergedService.has_graph">
                    <div class="col-lg-12 padding-10">
                        <div id="graph_legend" class="graph_legend"></div>
                        <div id="graph_data_tooltip"></div>
                        <div id="graphCanvas" class="graph-placeholder"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <?php if ($this->Acl->hasPermission('browser', 'services')): ?>
                    <button type="button" class="btn btn-success"
                            data-dismiss="modal"
                            ui-sref="ServicesBrowser({id:currentServiceDetailsId})">
                        <?php echo __('Open details'); ?>
                    </button>
                <?php endif; ?>
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <?php echo __('Close'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<reschedule-service callback="showServiceDetailsFlashMsg"></reschedule-service>
<service-downtime author="<?php echo h($username); ?>" callback="showServiceDetailsFlashMsg"></service-downtime>
<mass-delete-service-downtimes delete-url="/downtimes/delete/"
                               callback="showServiceDetailsFlashMsg"></mass-delete-service-downtimes>
<acknowledge-service author="<?php echo h($username); ?>"
                     callback="showServiceDetailsFlashMsg"></acknowledge-service>
<submit-service-result max-check-attempts="{{mergedService.max_check_attempts}}"
                       callback="showServiceDetailsFlashMsg"></submit-service-result>
<enable-service-flap-detection callback="showServiceDetailsFlashMsg"></enable-service-flap-detection>
<disable-service-flap-detection callback="showServiceDetailsFlashMsg"></disable-service-flap-detection>
