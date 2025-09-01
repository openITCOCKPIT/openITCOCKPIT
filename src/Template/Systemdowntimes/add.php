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
    <div class="col-xs-12 col-sm-7 col-md-7 col-lg-4">
        <h1 class="page-title txt-color-blueDark">
            <i class="fa fa-power-off fa-fw "></i>
            <?php echo __('Downtime'); ?>
            <span>>
                <?php echo __('create'); ?>
			</span>
            <div class="third_level"> <?php echo ucfirst($this->params['action']); ?></div>
        </h1>
    </div>
</div>


<div class="jarviswidget" id="wid-id-0">
    <header>
        <span class="widget-icon hidden-mobile hidden-tablet"> <i class="fa fa-power-off"></i> </span>
        <h2 class="hidden-mobile hidden-tablet"><?php echo __('Create downtime'); ?></h2>
        <div class="widget-toolbar" role="menu">
            <?php echo $this->Utils->backButton(__('Back'), $back_url); ?>
        </div>
        <ul class="nav nav-tabs pull-right" id="widget-tab-1">
            <li class="active">
                <a href="#tab1" data-toggle="tab"> <i class="fa fa-lg fa-desktop"></i> <span
                            class="hidden-mobile hidden-tablet"> <?php echo __('Host downtime'); ?></span> </a>
            </li>
            <li class="">
                <a href="#tab2" data-toggle="tab"> <i class="fa fa-lg fa-sitemap"></i> <span
                            class="hidden-mobile hidden-tablet"> <?php echo __('Hostgroup downtime'); ?> </span></a>
            </li>
            <li class="">
                <a href="#tab3" data-toggle="tab"> <i class="fa fa-lg fa-cog"></i> <span
                            class="hidden-mobile hidden-tablet"> <?php echo __('Service downtime'); ?> </span></a>
            </li>
        </ul>
    </header>
    <div>
        <div class="widget-body">
            <div class="row">
                <div class="col-xs-12 col-md-9 col-lg-7">
                    <div class="tab-content">
                        <div id="tab1" class="tab-pane fade active in">
                            <?php
                            echo $this->Form->create('Hostdowntime', [
                                'class' => 'form-horizontal clear',
                            ]);

                            $hostdowntimetyps = [
                                0 => __('Individual host'),
                                1 => __('Host and dependent Hosts (triggered)'),
                                2 => __('Host and dependent Hosts (non-triggered)'),
                            ];
                            ?>
                            <?php echo $this->Form->input('Hostdowntime.host_id', ['options' => $hosts, 'multiple' => true, 'label' => __('Host'), 'class' => 'chosen col col-xs-12']); ?>
                            <?php echo $this->Form->input('Hostdowntime.downtimetype_id', ['options' => $hostdowntimetyps, 'label' => __('Maintenance period for'), 'class' => 'chosen col col-xs-12']); ?>
                            <?php echo $this->Form->input('Hostdowntime.comment', ['value' => __('In maintenance'), 'label' => __('Comment')]); ?>

                            <?php
                            echo $this->Form->fancyCheckbox('Hostdowntime.is_recurring', [
                                'caption'          => __('Recurring downtime'),
                                'captionGridClass' => 'col col-md-2',
                                'captionClass'     => 'control-label',
                                'wrapGridClass'    => 'col col-md-10',
                                'class'            => 'onoffswitch-checkbox notification_control',
                                'checked'          => false,
                            ]);
                            ?>
                            <div class="padding-20"><!-- spacer --></div>
                            <div id="recurringHost_settings" style="display:none;">
                                <?php
                                $weekdays = [
                                    1 => __('Monday'),
                                    2 => __('Tuesday'),
                                    3 => __('Wednesday'),
                                    4 => __('Thursday'),
                                    5 => __('Friday'),
                                    6 => __('Saturday'),
                                    7 => __('Sunday'),
                                ];

                                echo $this->Form->input('Hostdowntime.recurring_day_id', ['options' => $weekdays, 'multiple' => true, 'label' => __('Weekdays'), 'class' => 'chosen col col-xs-12']);
                                echo $this->Form->input('Hostdowntime.recurring_days_month', ['placeholder' => __('1,2,3,4,5 or <blank>'), 'label' => __('Days of month')]);
                                ?>
                            </div>
                            <!-- from -->
                            <div class="form-group">
                                <label class="col col-md-2 control-label"
                                       for="HostdowntimeFromDate"><?php echo __('From'); ?>:</label>
                                <div class="col col-xs-5" style="padding-right: 0px;">
                                    <input type="text" id="HostdowntimeFromDate" value="<?php echo date('d.m.Y'); ?>"
                                           class="form-control" name="data[Hostdowntime][from_date]">
                                </div>
                                <div class="col col-xs-5" style="padding-left: 0px;">
                                    <input type="text" id="HostdowntimeFromTime" value="<?php echo date('h:m'); ?>"
                                           class="form-control" name="data[Hostdowntime][from_time]">
                                </div>
                            </div>

                            <!-- to -->
                            <div class="form-group">
                                <label class="col col-md-2 control-label"
                                       for="HostdowntimeToDate"><?php echo __('To'); ?>:</label>
                                <div class="col col-xs-5" style="padding-right: 0px;">
                                    <input type="text" id="HostdowntimeToDate"
                                           value="<?php echo date('d.m.Y', strtotime('+3 days')); ?>"
                                           class="form-control" name="data[Hostdowntime][to_date]">
                                </div>
                                <div class="col col-xs-5" style="padding-left: 0px;">
                                    <input type="text" id="HostdowntimeToTime" value="<?php echo date('h:m'); ?>"
                                           class="form-control" name="data[Hostdowntime][to_time]">
                                </div>
                            </div>
                            <?php echo $this->Form->formActions(); ?>
                        </div>

                        <div id="tab2" class="tab-pane fade">
                            <?php
                            echo $this->Form->create('Hostgroupdowntime', [
                                'class' => 'form-horizontal clear',
                            ]);

                            $hostdowntimetyps = [
                                0 => __('Individual host'),
                                1 => __('Host and dependent Hosts (triggered)'),
                                2 => __('Host and dependent Hosts (non-triggered)'),
                            ];
                            ?>
                            <?php echo $this->Form->input('Hostgroupdowntime.hostgroup_id', ['options' => $hostgroups, 'multiple' => true, 'label' => __('Hostgroup'), 'class' => 'chosen col col-xs-12']); ?>
                            <?php echo $this->Form->input('Hostgroupdowntime.downtimetype_id', ['options' => $hostdowntimetyps, 'label' => __('Maintenance period for'), 'class' => 'chosen col col-xs-12']); ?>
                            <?php echo $this->Form->input('Hostgroupdowntime.comment', ['value' => __('In maintenance'), 'label' => __('Comment')]); ?>

                            <?php
                            echo $this->Form->fancyCheckbox('Hostgroupdowntime.is_recurring', [
                                'caption'          => __('Recurring downtime'),
                                'captionGridClass' => 'col col-md-2',
                                'captionClass'     => 'control-label',
                                'wrapGridClass'    => 'col col-md-10',
                                'class'            => 'onoffswitch-checkbox notification_control',
                                'checked'          => false,
                            ]);
                            ?>
                            <div class="padding-20"><!-- spacer --></div>
                            <div id="recurringHostgroup_settings" style="display:none;">
                                <?php
                                $weekdays = [
                                    1 => __('Monday'),
                                    2 => __('Tuesday'),
                                    3 => __('Wednesday'),
                                    4 => __('Thursday'),
                                    5 => __('Friday'),
                                    6 => __('Saturday'),
                                    7 => __('Sunday'),
                                ];

                                echo $this->Form->input('Hostgroupdowntime.recurring_day_id', ['options' => $weekdays, 'multiple' => true, 'label' => __('Weekdays'), 'class' => 'chosen col col-xs-12']);
                                echo $this->Form->input('Hostgroupdowntime.recurring_days_month', ['placeholder' => __('1,2,3,4,5 or <blank>'), 'label' => __('Days of month')]);
                                ?>
                            </div>
                            <!-- from -->
                            <div class="form-group">
                                <label class="col col-md-2 control-label"
                                       for="HostgroupdowntimeFromDate"><?php echo __('From'); ?>:</label>
                                <div class="col col-xs-5" style="padding-right: 0px;">
                                    <input type="text" id="HostgroupdowntimeFromDate"
                                           value="<?php echo date('d.m.Y'); ?>" class="form-control"
                                           name="data[Hostgroupdowntime][from_date]">
                                </div>
                                <div class="col col-xs-5" style="padding-left: 0px;">
                                    <input type="text" id="HostgroupdowntimeFromTime" value="<?php echo date('h:m'); ?>"
                                           class="form-control" name="data[Hostgroupdowntime][from_time]">
                                </div>
                            </div>

                            <!-- to -->
                            <div class="form-group">
                                <label class="col col-md-2 control-label"
                                       for="HostgroupdowntimeToDate"><?php echo __('To'); ?>:</label>
                                <div class="col col-xs-5" style="padding-right: 0px;">
                                    <input type="text" id="HostgroupdowntimeToDate"
                                           value="<?php echo date('d.m.Y', strtotime('+3 days')); ?>"
                                           class="form-control" name="data[Hostgroupdowntime][to_date]">
                                </div>
                                <div class="col col-xs-5" style="padding-left: 0px;">
                                    <input type="text" id="HostgroupdowntimeToTime" value="<?php echo date('h:m'); ?>"
                                           class="form-control" name="data[Hostgroupdowntime][to_time]">
                                </div>
                            </div>
                            <?php echo $this->Form->formActions(); ?>
                        </div>

                        <div id="tab3" class="tab-pane fade">
                            service downtime
                        </div>


                    </div> <!-- close tab-content -->
                </div> <!-- close col -->
            </div> <!-- close row-->
        </div> <!-- close widget body -->
    </div>
</div> <!-- end jarviswidget -->
