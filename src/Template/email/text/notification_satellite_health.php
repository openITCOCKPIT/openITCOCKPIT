<?php
// Copyright (C) 2015-2025  it-novum GmbH
// Copyright (C) 2025-today AVENDIS GmbH
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
 * @var AppView $this
 * @var string $systemname
 * @var ServicestatusIcon $StatusIcon
 * @var string $systemAddress
 * @var array $satellites One or more satellites (incl. satellite_status, satellite_information and state)
 *
 */
use App\View\AppView;use itnovum\openITCOCKPIT\Core\Views\ServicestatusIcon;

?>
<?= __('Satellites System Health') ?> is <?= $StatusIcon->getHumanState() ?>!
<?= PHP_EOL ?>
Time: <?php echo date('H:i:s T'); ?>
<?= PHP_EOL ?>
State: <?= $StatusIcon->getHumanState(); ?>
<?= PHP_EOL ?>
<?= __n('Satellite', 'Satellites', count($satellites)) ?>:
<?php foreach ($satellites as $satellite): ?>
- <?= h($satellite['name']); ?> (<?= h(ServicestatusIcon::fromStatusText($satellite['state'] ?? 'unknown')->getHumanState()); ?>)
<?php endforeach; ?>
<?= PHP_EOL ?>
<?php if ($StatusIcon->getState() === 1 || $StatusIcon->getState() === 2): ?>
Output:
<?php foreach ($satellites as $satellite): ?>
<?php if ((int)($satellite['satellite_status']['status'] ?? 0) !== 1): ?>
<?= h($satellite['name']); ?>, <?php echo __('Sync status'); ?> <?php echo __('failed') ?>, <?php echo __('last seen') ?> <?= h($satellite['satellite_status']['last_seen'] ?? ''); ?>
<?= PHP_EOL ?>
<?php endif; ?>
<?php if (isset($satellite['satellite_information']['system_health'])): ?>
<?php $satellite_health = $satellite['satellite_information']['system_health']; ?>
<?php if (($satellite_health['memory']['memory']['state'] ?? 'ok') !== 'ok'): ?>
<?= h($satellite['name']); ?>, <?php echo __('High memory usage.'); ?> <?= h($satellite_health['memory']['memory']['percentage']); ?>%
<?= PHP_EOL ?>
<?php endif; ?>
<?php if (($satellite_health['memory']['swap']['state'] ?? 'ok') !== 'ok'): ?>
<?= h($satellite['name']); ?>, <?php echo __('High Swap usage'); ?> <?= h($satellite_health['memory']['swap']['percentage']); ?>%
<?= PHP_EOL ?>
<?php endif; ?>
<?php foreach ($satellite_health['disks'] ?? [] as $disk): ?>
<?php if (($disk['state'] ?? 'ok') !== 'ok'): ?>
<?= h($satellite['name']); ?>, <?php echo __('Low disk space left for mountpoint:'); ?> "<?= h($disk['mountpoint']); ?>" <?= h($disk['use_percentage']); ?>%
<?= PHP_EOL ?>
<?php endif; ?>
<?php endforeach; ?>
<?php if (($satellite_health['cpu_state'] ?? 'ok') !== 'ok'): ?>
<?= h($satellite['name']); ?>, <?php echo __('Current CPU load is too high!'); ?> <?= h($satellite_health['cpu_load1']); ?>, <?= h($satellite_health['cpu_load5']); ?>, <?= h($satellite_health['cpu_load15']); ?>
<?= PHP_EOL ?>
<?php endif; ?>
<?php endif; ?>
<?php endforeach; ?>
<?php endif; ?>

<?php //@formatter:on ?>
