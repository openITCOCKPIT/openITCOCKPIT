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
 * @var \itnovum\openITCOCKPIT\Core\Views\ServicestatusIcon $StatusIcon
 * @var string $systemAddress
 * @var array $systemHealth
 *
 */

echo $this->element('emails/style');

?>
<!-- ########## EMAIL CONTENT ############### -->

<body bgcolor="#FFFFFF">

<!-- HEADER -->
<table class="head-wrap">
    <tr>
        <td></td>
        <td class="header container">

            <div class="content">
                <table>
                    <tr>
                        <td>
                            <img src="cid:100" width="60"/>
                        </td>
                        <td align="right">
                            <h6><?= __('{0} notification', $systemname) ?></h6>
                        </td>
                    </tr>
                    <tr>
                        <td>&nbsp;</td>
                        <td align="right" class="notification_type">
                            <?= __('System health') ?>
                        </td>
                    </tr>
                </table>
            </div>

        </td>
        <td></td>
    </tr>
</table>
<!-- /HEADER -->

<!-- BODY -->
<table class="body-wrap">
    <tr>
        <td></td>
        <td class="container" bgcolor="#FFFFFF">

            <div class="content">
                <table>
                    <tr>
                        <td>
                            <h5>
                                <?= $StatusIcon->getEmoji() ?>
                                &nbsp;
                                <span>
                                    <a href="<?php printf('https://%s/a/administrators/debug', $systemAddress); ?>"
                                       style="text-decoration:none"
                                       class="<?= strtoupper($StatusIcon->getTextColor()) ?>">
                                        <?= __('System health') ?>
                                    </a>
                                    <?= __(' is {0}', $StatusIcon->getHumanState()); ?>
                                </span>
                            </h5>
                            <hr noshade width="560" size="3" align="left">
                            <br>
                            <table width="100%">
                                <tr>
                                    <td><strong><?php echo __('Time'); ?>:</strong></td>
                                    <td><?php echo date('H:i:s T'); ?></td>
                                </tr>
                                <tr>
                                    <td><strong><?php echo __('State'); ?>:</strong></td>
                                    <td class=<?= strtoupper($StatusIcon->getTextColor()) ?>>
                                        <?= h($StatusIcon->getHumanState()); ?>
                                    </td>
                                </tr>
                            </table>
                            <br/>
                            <strong><?php echo __('Output'); ?>:</strong>

                            <?php if ($systemHealth['state'] == 'warning' || $systemHealth['state'] == 'critical'): ?>

                                <ul class="padding-5 list-unstyled system-health-item notification-message fs-sm"
                                    style="width: 100%;">
                                    <?php if (!$systemHealth['isNagiosRunning']): ?>
                                        <li>
                                            <span>
                                                <div class="padding-5">
                                                    <h6>
                                                        <i class="fa fa-warning down"></i>
                                                        <?php echo __('Critical'); ?>
                                                    </h6>
                                                    <i><?php echo __('Monitoring engine is not running!'); ?></i>
                                                </div>
                                            </span>
                                        </li>
                                    <?php endif; ?>

                                    <?php if (!$systemHealth['gearman_reachable']): ?>
                                        <li>
                                            <span>
                                                <div class="padding-5">
                                                    <h6>
                                                        <i class="fa fa-warning down"></i>
                                                        <?php echo __('Critical'); ?>
                                                    </h6>
                                                    <i><?php echo __('Gearman job server not reachable!'); ?></i>
                                                </div>
                                            </span>
                                        </li>
                                    <?php endif; ?>

                                    <?php if (!$systemHealth['gearman_worker_running']): ?>
                                        <li>
                                            <span>
                                                <div class="padding-5">
                                                    <h6>
                                                        <i class="fa fa-warning down"></i>
                                                        <?php echo __('Critical'); ?>
                                                    </h6>
                                                    <i><?php echo __('Service gearman_worker is not running!'); ?></i>
                                                </div>
                                            </span>
                                        </li>
                                    <?php endif; ?>

                                    <?php if ($systemHealth['isNdoInstalled'] && !$systemHealth['isNdoRunning']): ?>
                                        <li>
                                            <span>
                                                <div class="padding-5">
                                                    <h6>
                                                        <i class="fa fa-warning down"></i>
                                                        <?php echo __('Critical'); ?>
                                                    </h6>
                                                    <i><?php echo __('Database connector NDOUtils is not running!'); ?></i>
                                                </div>
                                            </span>
                                        </li>
                                    <?php endif; ?>

                                    <?php if ($systemHealth['isStatusengineInstalled'] && !$systemHealth['isStatusengineRunning']): ?>
                                        <li>
                                            <span>
                                                <div class="padding-5">
                                                    <h6>
                                                        <i class="fa fa-warning down"></i>
                                                        <?php echo __('Critical'); ?>
                                                    </h6>
                                                    <i><?php echo __('Database connector Statusengine is not running!'); ?></i>
                                                </div>
                                            </span>
                                        </li>
                                    <?php endif; ?>

                                    <?php if ($systemHealth['isStatusenginePerfdataProcessor'] && !$systemHealth['isStatusengineRunning']): ?>
                                        <li>
                                            <span>
                                                <div class="padding-5">
                                                    <h6>
                                                        <i class="fa fa-warning warning"></i>
                                                        <?php echo __('Warning'); ?>
                                                    </h6>
                                                    <i><?php echo __('Performance data processer Statusengine is not running!'); ?></i>
                                                </div>
                                            </span>
                                        </li>
                                    <?php endif; ?>

                                    <?php if (!$systemHealth['isStatusenginePerfdataProcessor'] && !$systemHealth['isNpcdRunning']): ?>
                                        <li>
                                            <span>
                                                <div class="padding-5">
                                                    <h6>
                                                        <i class="fa fa-warning warning"></i>
                                                        <?php echo __('Warning'); ?>
                                                    </h6>
                                                    <i><?php echo __('Performance data processer NPCD is not running!'); ?></i>
                                                </div>
                                            </span>
                                        </li>
                                    <?php endif; ?>

                                    <?php if (!$systemHealth['isSudoServerRunning']): ?>
                                        <li>
                                            <span>
                                                <div class="padding-5">
                                                    <h6>
                                                        <i class="fa fa-warning warning"></i>
                                                        <?php echo __('Warning'); ?>
                                                    </h6>
                                                    <i><?php echo __('Service sudo_server is not running!'); ?></i>
                                                </div>
                                            </span>
                                        </li>
                                    <?php endif; ?>

                                    <?php if (!$systemHealth['isOitcCmdRunning']): ?>
                                        <li>
                                            <span>
                                                <div class="padding-5">
                                                    <h6>
                                                        <i class="fa fa-warning warning"></i>
                                                        <?php echo __('Warning'); ?>
                                                    </h6>
                                                    <i><?php echo __('Service oitc_cmd is not running!'); ?></i>
                                                </div>
                                            </span>
                                        </li>
                                    <?php endif; ?>

                                    <?php if (!$systemHealth['isPushNotificationRunning']): ?>
                                        <li>
                                            <span>
                                                <div class="padding-5">
                                                    <h6>
                                                        <i class="fa fa-warning warning"></i>
                                                        <?php echo __('Warning'); ?>
                                                    </h6>

                                                    <i><?php echo __('Service push_notification is not running!'); ?></i>
                                                </div>
                                            </span>
                                        </li>
                                    <?php endif; ?>

                                    <?php if (!$systemHealth['isNodeJsServerRunning']): ?>
                                        <li>
                                            <span>
                                                <div class="padding-5">
                                                    <h6>
                                                        <i class="fa fa-warning warning"></i>
                                                        <?php echo __('Warning'); ?>
                                                    </h6>

                                                    <i><?php echo __('Nodejs backend is not running'); ?></i>
                                                </div>
                                            </span>
                                        </li>
                                    <?php endif; ?>

                                    <?php if ($systemHealth['load']['state'] !== 'ok'): ?>
                                        <li>
                                            <span>
                                                <div class="padding-5">
                                                    <p class="margin-bottom-5">
                                                        <i><?php echo __('Current CPU load is too high!'); ?></i>
                                                        <br/>
                                                        <i><?= h($systemHealth['load']['load1']); ?>
                                                            , <?= h($systemHealth['load']['load5']); ?>, <?= h(
                                                                $systemHealth['load']['load15']); ?></i>
                                                    </p>
                                                </div>
                                            </span>
                                        </li>
                                    <?php endif; ?>


                                    <?php foreach ($systemHealth['satellites'] as $satellite): ?>
                                        <?php if ($satellite['satellite_status']['status'] !== 1): ?>
                                            <li>
                                                <span>
                                                    <div class="padding-5">
                                                        <p class="margin-bottom-5">
                                                            <i><?php echo __('Sync status'); ?><?php echo __('failed') ?></i>
                                                            <br/>
                                                            <i><?= h($satellite['name']); ?>
                                                                , <?php echo __('last seen') ?> <?= h($satellite['satellite_status']['last_seen']); ?></i>
                                                        </p>
                                                    </div>
                                                </span>
                                            </li>
                                        <?php endif; ?>
                                    <?php endforeach; ?>

                                    <?php if ($systemHealth['memory_usage']['memory']['state'] !== 'ok'): ?>
                                        <li>
                                            <span>
                                                <div class="padding-5">
                                                    <p class="margin-bottom-5">
                                                        <i><?php echo __('High memory usage.'); ?></i>
                                                        <span class="pull-right semi-bold text-muted">
                                                            <?= h($systemHealth['memory_usage']['memory']['percentage']); ?>
                                                            %
                                                        </span>
                                                    </p>
                                                    <div class="progress progress-sm">
                                                        <div class="progress-bar bg-color-darken"
                                                             style="width: <?= h($systemHealth['memory_usage']['memory']['percentage']); ?>%;"></div>
                                                    </div>
                                                </div>
                                            </span>
                                        </li>
                                    <?php endif; ?>

                                    <?php if ($systemHealth['memory_usage']['swap']['state'] !== 'ok'): ?>
                                        <li>
                                            <span>
                                                <div class="padding-5">
                                                    <p class="margin-bottom-5">
                                                        <i><?php echo __('High Swap usage'); ?></i>
                                                        <span class="pull-right semi-bold text-muted">
                                                            <?= h($systemHealth['memory_usage']['swap']['percentage']); ?>
                                                            %
                                                        </span>
                                                    </p>
                                                    <div class="progress progress-sm">
                                                        <div class="progress-bar bg-color-darken"
                                                             style="width: <?= h($systemHealth['memory_usage']['swap']['percentage']); ?>%;"></div>
                                                    </div>
                                                </div>
                                            </span>
                                        </li>
                                    <?php endif; ?>

                                    <?php foreach ($systemHealth['disk_usage'] as $disk): ?>
                                        <?php if ($disk['state'] !== 'ok'): ?>
                                            <li>
                                                <span>
                                                    <div class="padding-5">
                                                        <p class="margin-bottom-5">
                                                            <i><?php echo __('Low disk space left for mountpoint:'); ?></i>
                                                            <br/>
                                                            <i>"<?= h($disk['mountpoint']); ?>"</i>
                                                            <span class="pull-right semi-bold text-muted">
                                                                <?= h($disk['use_percentage']); ?>%
                                                            </span>
                                                        </p>
                                                        <div class="progress progress-sm">
                                                            <div class="progress-bar bg-color-darken"
                                                                 style="width: <?= h($disk['use_percentage']); ?>%;"></div>
                                                        </div>
                                                    </div>
                                                </span>
                                            </li>
                                        <?php endif; ?>
                                    <?php endforeach; ?>

                                    <?php if ($systemHealth['isDistributeModuleInstalled'] && !$systemHealth['isNstaRunning']): ?>
                                        <li>
                                            <span>
                                                <div class="padding-5">
                                                    <h6>
                                                        <i class="fa fa-warning warning"></i>
                                                        <?php echo __('Warning'); ?>
                                                    </h6>
                                                    <i><?php echo __('Service NSTA is not running!'); ?></i>
                                                </div>
                                            </span>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            <?php endif; ?>

                            <?php if ($systemHealth['state'] === 'unknown'): ?>
                                <ul class="padding-5 list-unstyled system-health-item notification-message fs-sm"
                                    style="width: 100%;">
                                    <?php if (!$systemHealth['isNagiosRunning']): ?>
                                        <li>
                                            <span>
                                                <div class="padding-5">
                                                    <p class="margin-bottom-5">
                                                        <i class="fa fa-question-circle text-primary"></i>
                                                        <strong><?php echo __('Unknown'); ?></strong>
                                                        <br/>
                                                        <i><?php echo __('Could not detect system health status.'); ?></i>
                                                    </p>
                                                </div>
                                            </span>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            <?php endif; ?>

                            <br/>

                        </td>
                    </tr>
                </table>
            </div>
        </td>
        <td></td>
    </tr>
</table>
<table class="footer-wrap">
    <tr>
        <td></td>
        <td class="container">
            <div class="content">
                <table>
                    <tr>
                        <hr noshade width="560" size="3" align="left">
                        <br>
                        <td align="center">
                            <p>
                                <a href="https://openitcockpit.io/"><?php echo __('openITCOCKPIT'); ?></a>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td align="center">
                            <p>
                                <?php echo date('l jS \of F Y'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
        </td>
        <td></td>
    </tr>
</table>

<?php

// Disable PhpStorm code reformatting
// You need to enable "Enable formatter markers in comments" in your PhpStorm settings!!!
// See: https://stackoverflow.com/a/24438712
//
// @formatter:off
?>

<?php //@formatter:on ?>
