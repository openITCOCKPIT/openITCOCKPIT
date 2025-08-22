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
    <div class="col-xs-12 col-md-2 text-muted">
        <center><span id="selectionCount"></span></center>
    </div>
    <div class="col-xs-12 col-md-2 "><span id="selectAll" class="pointer"><i
                    class="fa fa-lg fa-check-square-o"></i> <?php echo __('Select all'); ?></span></div>
    <div class="col-xs-12 col-md-2"><span id="untickAll" class="pointer"><i
                    class="fa fa-lg fa-square-o"></i> <?php echo __('Undo selection'); ?></span></div>
    <div class="col-xs-12 col-md-2">
        <?php if ($this->Acl->hasPermission('copy', 'Hosts', '')): ?>
            <a href="javascript:void(0);" id="copyAll">
                <i class="fa fa-lg fa-files-o"></i> <?php echo __('Copy'); ?></a>
        <?php endif; ?>
    </div>
    <div class="col-xs-12 col-md-2">
        <?php if ($this->Acl->hasPermission('delete', 'Hosts', '')): ?>
            <a href="javascript:void(0);" id="deleteAll" class="txt-color-red" style="text-decoration: none;"> <i
                        class="fa fa-lg fa-trash-o"></i> <?php echo __('Delete'); ?></a>
        <?php endif; ?>
    </div>
    <div class="col-xs-12 col-md-2">
        <div class="btn-group">
            <a href="javascript:void(0);" data-toggle="dropdown" class="btn btn-default"><?php echo __('More'); ?></a>
            <a href="javascript:void(0);" data-toggle="dropdown" class="btn btn-default dropdown-toggle"><span
                        class="caret"></span></a>
            <ul class="dropdown-menu">
                <?php if ($this->Acl->hasPermission('edit_details', 'Hosts', '')): ?>
                    <li>
                        <a href="javascript:void(0);" id="editDetailAll"><i
                                    class="fa fa-cog"></i> <?php echo __('Edit details'); ?></a>
                    </li>
                <?php endif; ?>
                <?php if ($this->Acl->hasPermission('deactivate', 'Hosts', '')): ?>
                    <li>
                        <a href="javascript:void(0);" id="disableAll"><i
                                    class="fa fa-plug"></i> <?php echo __('Disable'); ?></a>
                    </li>
                <?php endif; ?>
                <?php if ($this->Acl->hasPermission('add', 'hostgroups', '')): ?>
                    <li>
                        <a href="javascript:void(0);" id="addToGroupAll"><i
                                    class="fa fa-sitemap"></i> <?php echo __('Add to host group'); ?></a>
                    </li>
                <?php endif; ?>
                <li>
                    <a href="<?php echo Router::url(array_merge(['controller' => 'hosts', 'action' => 'listToPdf'], $this->params['named'])); ?>/.pdf"
                       id="listAsPDF"><i class="fa fa-file-pdf-o"></i> <?php echo __('List as PDF') ?></a>
                </li>
                <?php if ($this->params['controller'] == 'hosts' && $this->params['action'] == 'index'): ?>
                    <li class="divider"></li>
                    <li>
                        <a href="javascript:void(0);" data-toggle="modal" data-target="#nag_command_reschedule"><i
                                    class="fa fa-refresh"></i> <?php echo __('Reset check time'); ?></a>
                    </li>
                    <li>
                        <a href="javascript:void(0);" data-toggle="modal"
                           data-target="#nag_command_schedule_downtime"><i
                                    class="fa fa-clock-o"></i> <?php echo __('Set planned maintenance times'); ?></a>
                    </li>
                    <li>
                        <a href="javascript:void(0);" data-toggle="modal" data-target="#nag_command_ack_state"><i
                                    class="fa fa-user"></i> <?php echo __('Acknowledge host status'); ?></a>
                    </li>
                    <li>
                        <a href="javascript:void(0);" data-toggle="modal"
                           data-target="#nag_command_disable_notifications"><i
                                    class="fa fa-envelope-o"></i> <?php echo __('Disable notification'); ?></a>
                    </li>
                    <li>
                        <a href="javascript:void(0);" data-toggle="modal"
                           data-target="#nag_command_enable_notifications"><i
                                    class="fa fa-envelope"></i> <?php echo __('Enable notifications'); ?></a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
    <!-- hidden fields for multi language -->
    <input type="hidden" id="delete_message_h1" value="<?php echo __('Attention!'); ?>"/>
    <input type="hidden" id="delete_message_h2"
           value="<?php echo __('Do you really want delete the selected hosts?'); ?>"/>
    <input type="hidden" id="disable_message_h1" value="<?php echo __('Notice!'); ?>"/>
    <input type="hidden" id="disable_message_h2"
           value="<?php echo __('Do you really want disable the selected hosts?'); ?>"/>
    <input type="hidden" id="message_yes" value="<?php echo __('Yes'); ?>"/>
    <input type="hidden" id="message_no" value="<?php echo __('No'); ?>"/>
</div>
