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
<?php $this->Paginator->options(['url' => $this->params['named']]); ?>
<div class="row">
    <div class="col-xs-12 col-sm-7 col-md-7 col-lg-4">
        <h1 class="page-title txt-color-blueDark">
            <i class="fa fa-file-text-o fa-fw "></i>
            <?php echo __('Reporting'); ?>
            <span>>
                <?php echo __('Instant Report'); ?>
            </span>
        </h1>
    </div>
</div>

<section id="widget-grid" class="">
    <div class="row">
        <article class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
            <div class="jarviswidget jarviswidget-color-blueDark" id="wid-id-1" data-widget-editbutton="false">
                <header>
                    <div class="widget-toolbar" role="menu">
                        <?php
                        if ($this->Acl->hasPermission('add')):
                            echo $this->Html->link(__('New'), '/' . $this->params['controller'] . '/add', ['class' => 'btn btn-xs btn-success', 'icon' => 'fa fa-plus']);
                            echo " "; //Fix HTML
                        endif;
                        ?>
                    </div>
                    <span class="widget-icon hidden-mobile"> <i class="fa fa-file-image-o"></i> </span>
                    <h2 class="hidden-mobile"><?php echo __('Instant Reports'); ?></h2>
                    <ul class="nav nav-tabs pull-right" id="widget-tab-1">
                        <li>
                            <a href="/instantreports/index"><i class="fa fa-archive"></i> <span
                                    class="hidden-mobile hidden-tablet"> <?php echo __('Saved'); ?> </span> </a>
                        </li>
                        <?php if ($this->Acl->hasPermission('sendEmailsList')): ?>
                            <li class="active">
                                <a href="/instantreports/sendEmailsList"><i class="fa fa-paper-plane"></i> <span
                                        class="hidden-mobile hidden-tablet"> <?php echo __('Send Emails'); ?> </span></a>
                            </li>
                        <?php endif; ?>

                    </ul>
                </header>
                <div>
                    <div class="widget-body no-padding">
                        <div class="mobile_table">
                            <table id="timeperiod_list"
                                   class="table table-striped table-hover table-bordered smart-form" style="">
                                <thead>
                                <tr>
                                    <?php $order = $this->Paginator->param('order'); ?>
                                    <th class="select_datatable no-sort"><?php echo $this->Utils->getDirection($order, 'Instantreport.name');
                                        echo $this->Paginator->sort('Instantreport.name', 'Name'); ?></th>
                                    <th class="select_datatable no-sort"><?php echo $this->Utils->getDirection($order, 'Instantreport.evaluation');
                                        echo $this->Paginator->sort('Instantreport.evaluation', 'Evaluation'); ?></th>
                                    <th class="no-sort"><?php echo $this->Utils->getDirection($order, 'Instantreport.type');
                                        echo $this->Paginator->sort('Instantreport.type', 'Type'); ?></th>
                                    <th class="no-sort"><?php echo $this->Utils->getDirection($order, 'Instantreport.timeperiod_id');
                                        echo $this->Paginator->sort('Instantreport.timeperiod_id', 'Time period'); ?></th>
                                    <th class="no-sort"><?php echo $this->Utils->getDirection($order, 'Instantreport.summary');
                                        echo $this->Paginator->sort('Instantreport.summary', 'Summary display'); ?></th>
                                    <th class="no-sort"><?php echo $this->Utils->getDirection($order, 'Instantreport.send_interval');
                                        echo $this->Paginator->sort('Instantreport.send_interval', 'Send interval'); ?></th>
                                    <th class="no-sort" style="width:150px;"><?= __('Send to'); ?></th>
                                    <th class="no-sort text-center" style="width:52px;"><i class="fa fa-gear fa-lg"></i>
                                    </th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($allInstantReports as $instantReport): ?>
                                    <?php
                                    $allowEdit = $this->Acl->isWritableContainer($instantReport['Instantreport']['container_id']);
                                    $usersText = '';
                                    if (!empty($instantReport['User'])) {
                                        foreach ($instantReport['User'] as $user) {
                                            $usersText .= '<div>' . $user['firstname'] . ' ' . $user['lastname'] . '</div>';
                                        }
                                    }
                                    ?>
                                    <tr>
                                        <td><?= $instantReport['Instantreport']['name']; ?></td>
                                        <td><?= '<i class="fa fa-' . $evaluations[$instantReport['Instantreport']['evaluation']]['icon'] . '"></i> ' . $evaluations[$instantReport['Instantreport']['evaluation']]['label']; ?></td>
                                        <td><?= $types[$instantReport['Instantreport']['type']]; ?></td>
                                        <td><?= $instantReport['Timeperiod']['name']; ?></td>
                                        <td class="text-center"><?= $instantReport['Instantreport']['summary'] === '1' ?
                                                '<i class="fa fa-check fa-lg txt-color-green"></i>' : '<i class="fa fa-times fa-lg txt-color-red"></i>' ?></td>
                                        <td class="text-center"><?= $instantReport['Instantreport']['send_interval'] > 0 ?
                                                $sendIntervals[$instantReport['Instantreport']['send_interval']] : '<i class="fa fa-times fa-lg txt-color-red"></i>' ?></td>
                                        <td class="text-center"><?= $usersText !== '' ? $usersText : '<i class="fa fa-times fa-lg txt-color-red"></i>' ?></td>
                                        <td>
                                            <div class="btn-group" style="width:52px;">
                                                <?php if ($this->Acl->hasPermission('edit') && $allowEdit): ?>
                                                    <a href="<?php echo Router::url(['action' => 'edit', $instantReport['Instantreport']['id']]); ?>"
                                                       class="btn btn-default">&nbsp;<i class="fa fa-cog"></i>&nbsp;</a>
                                                <?php else: ?>
                                                    <a href="javascript:void(0);" class="btn btn-default">&nbsp;<i
                                                            class="fa fa-cog"></i>&nbsp;</a>
                                                <?php endif; ?>
                                                <a href="javascript:void(0);" data-toggle="dropdown"
                                                   class="btn btn-default dropdown-toggle"><span
                                                        class="caret"></span></a>
                                                <ul class="dropdown-menu pull-right">
                                                    <?php if ($this->Acl->hasPermission('edit') && $allowEdit): ?>
                                                        <li>
                                                            <a href="<?php echo Router::url(['action' => 'edit', $instantReport['Instantreport']['id']]); ?>"><i
                                                                    class="fa fa-cog"></i> <?php echo __('Edit'); ?>
                                                            </a>
                                                        </li>
                                                    <?php endif; ?>
                                                    <?php if ($this->Acl->hasPermission('generate')): ?>
                                                        <li>
                                                            <a href="<?php echo Router::url(['action' => 'generate', $instantReport['Instantreport']['id']]); ?>"><i
                                                                    class="fa fa-file-image-o"></i> <?php echo __('Generate'); ?>
                                                            </a>
                                                        </li>
                                                    <?php endif; ?>
                                                    <?php if ($this->Acl->hasPermission('delete') && $allowEdit): ?>
                                                        <li class="divider"></li>
                                                        <li>
                                                            <?php echo $this->Form->postLink('<i class="fa fa-trash-o"></i> ' . __('Delete'), ['controller' => 'instantreports', 'action' => 'delete', $instantReport['Instantreport']['id']], ['class' => 'txt-color-red', 'escape' => false]); ?>
                                                        </li>
                                                    <?php endif; ?>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if (empty($allInstantReports)): ?>
                            <div class="noMatch">
                                <center>
                                    <span
                                        class="txt-color-red italic"><?php echo __('No entries match the selection'); ?></span>
                                </center>
                            </div>
                        <?php endif; ?>

                        <div style="padding: 5px 10px;">
                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="dataTables_info" style="line-height: 32px;"
                                         id="datatable_fixed_column_info"><?php echo $this->Paginator->counter(__('Page') . ' {:page} ' . __('of') . ' {:pages}, ' . __('Total') . ' {:count} ' . __('entries')); ?></div>
                                </div>
                                <div class="col-sm-6 text-right">
                                    <div class="dataTables_paginate paging_bootstrap">
                                        <?php echo $this->Paginator->pagination([
                                            'ul' => 'pagination',
                                        ]); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </article>
    </div>
</section>
