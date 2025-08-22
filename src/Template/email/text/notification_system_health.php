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

// Disable PhpStorm code reformatting
// You need to enable "Enable formatter markers in comments" in your PhpStorm settings!!!
// See: https://stackoverflow.com/a/24438712
//
// @formatter:off

/**
 * @var \App\View\AppView $this
 * @var string $systemname
 * @var \itnovum\openITCOCKPIT\Core\Views\HoststatusIcon $StatusIcon
 * @var string $systemAddress
 * @var array $systemHealth
 *
 */

?>
System health is <?= $StatusIcon->getHumanState() ?>!
<?= PHP_EOL ?>
Time: <?php echo date('H:i:s T'); ?>
<?= PHP_EOL ?>
State: <?= $StatusIcon->getHumanState(); ?>
<?= PHP_EOL ?>
Output:
<?php if ($systemHealth['state'] == 'warning' || $systemHealth['state'] == 'critical'): ?>
<?php if (!$systemHealth['isNagiosRunning']): ?>
<?php echo __('Monitoring engine is not running!'); ?>
<?= PHP_EOL ?>
<?php endif; ?>
<?php if (!$systemHealth['gearman_reachable']): ?>
<?php echo __('Gearman job server not reachable!'); ?>
<?= PHP_EOL ?>
<?php endif; ?>
<?php if (!$systemHealth['gearman_worker_running']): ?>
<?php echo __('Service gearman_worker is not running!'); ?>
<?= PHP_EOL ?>
<?php endif; ?>
<?php if ($systemHealth['isNdoInstalled'] && !$systemHealth['isNdoRunning']): ?>
<?php echo __('Database connector NDOUtils is not running!'); ?>
<?= PHP_EOL ?>
<?php endif; ?>
<?php if ($systemHealth['isStatusengineInstalled'] && !$systemHealth['isStatusengineRunning']): ?>
<?php echo __('Database connector Statusengine is not running!'); ?>
<?= PHP_EOL ?>
<?php endif; ?>
<?php if ($systemHealth['isStatusenginePerfdataProcessor'] && !$systemHealth['isStatusengineRunning']): ?>
<?php echo __('Performance data processer Statusengine is not running!'); ?>
<?= PHP_EOL ?>
<?php endif; ?>
<?php if (!$systemHealth['isStatusenginePerfdataProcessor'] && !$systemHealth['isNpcdRunning']): ?>
<?php echo __('Performance data processer NPCD is not running!'); ?>
<?= PHP_EOL ?>
<?php endif; ?>
<?php if (!$systemHealth['isSudoServerRunning']): ?>
<?php echo __('Service sudo_server is not running!'); ?>
<?= PHP_EOL ?>
<?php endif; ?>
<?php if (!$systemHealth['isOitcCmdRunning']): ?>
<?php echo __('Service oitc_cmd is not running!'); ?>
<?= PHP_EOL ?>
<?php endif; ?>
<?php if (!$systemHealth['isPushNotificationRunning']): ?>
<?php echo __('Service push_notification is not running!'); ?>
<?= PHP_EOL ?>
<?php endif; ?>
<?php if (!$systemHealth['isNodeJsServerRunning']): ?>
<?php echo __('Nodejs backend is not running'); ?>
<?= PHP_EOL ?>
<?php endif; ?>
<?php if ($systemHealth['load']['state'] !== 'ok'): ?>
<?php echo __('Current CPU load is too high!'); ?>
<?= PHP_EOL ?>
<?= h($systemHealth['load']['load1']); ?>, <?= h($systemHealth['load']['load5']); ?>, <?= h(
$systemHealth['load']['load15']); ?>
<?= PHP_EOL ?>
<?php endif; ?>
<?php foreach ($systemHealth['satellites'] as $satellite): ?>
<?php if ($satellite['satellite_status']['status'] !== 1): ?>
<?php echo __('Sync status'); ?><?php echo __('failed') ?>
<?= PHP_EOL ?>
<?= h($satellite['name']); ?>, <?php echo __('last seen') ?> <?= h($satellite['satellite_status']['last_seen']); ?>
<?= PHP_EOL ?>
<?php endif; ?>
<?php endforeach; ?>
<?php if ($systemHealth['memory_usage']['memory']['state'] !== 'ok'): ?>
<?php echo __('High memory usage.'); ?> <?= h($systemHealth['memory_usage']['memory']['percentage']); ?>%
<?= PHP_EOL ?>
<?php endif; ?>
<?php if ($systemHealth['memory_usage']['swap']['state'] !== 'ok'): ?>
<?php echo __('High Swap usage'); ?> <?= h($systemHealth['memory_usage']['swap']['percentage']); ?>%
<?= PHP_EOL ?>
<?php endif; ?>
<?php foreach ($systemHealth['disk_usage'] as $disk): ?>
<?php if ($disk['state'] !== 'ok'): ?>
<?php echo __('Low disk space left for mountpoint:'); ?>
"<?= h($disk['mountpoint']); ?>"
<?= h($disk['use_percentage']); ?>%
<?php endif; ?>
<?= PHP_EOL ?>
<?php endforeach; ?>
<?php if ($systemHealth['isDistributeModuleInstalled'] && !$systemHealth['isNstaRunning']): ?>
<?php echo __('Service NSTA is not running!'); ?>
<?php endif; ?>
<?= PHP_EOL ?>
<?php endif; ?>
<?php if ($systemHealth['state'] === 'unknown'): ?>
<?php if (!$systemHealth['isNagiosRunning']): ?>
<?php echo __('Unknown'); ?>
<?= PHP_EOL ?>
<?php echo __('Could not detect system health status.'); ?>
<?php endif; ?>
<?= PHP_EOL ?>
<?php endif; ?>

<?php //@formatter:on ?>
