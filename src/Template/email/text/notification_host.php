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
 * @var bool $noAttachments
 * @var bool $noEmoji
 * @var \itnovum\openITCOCKPIT\Core\Views\Host $Host
 * @var \itnovum\openITCOCKPIT\Core\Views\HoststatusIcon $HoststatusIcon
 * @var \Cake\Console\Arguments $args
 * @var string $systemAddress
 * @var string $ticketsystemUrl
 *
 */

?>
Host <?= $Host->getHostname(); ?> is <?= $HoststatusIcon->getHumanState() ?>!

Time: <?php echo date('H:i:s T'); ?>

Host description: <?= $Host->getDescription(); ?>

Host address: <?= $Host->getAddress(); ?>

State: <?= $HoststatusIcon->getHumanState(); ?>


Output: <?= $args->getOption('hostoutput'); ?>

<?php if (!empty($args->getOption('hostlongoutput'))): ?>
Long host output: <?php echo str_replace(['\n', '\r\n', '\r'], "\n", $args->getOption('hostlongoutput')); ?>
<?php endif; ?>


--- BEGIN TICKET SYSTEM INFORMATION ---
TICKET_HOSTNAME: <?= $Host->getHostname(); ?>
<?= PHP_EOL ?>
TICKET_HOSTUUID: <?= $Host->getUuid(); ?>
<?= PHP_EOL ?>
TICKET_SERVICEDESC:
TICKET_SERVICEUUID:
TICKET_STATE: <?= $HoststatusIcon->getHumanState(); ?>
<?= PHP_EOL ?>
TICKET_NOTIFICATIONTYPE: HOST
TICKET_COMMAND_NUMBER: 33
--- END TICKET SYSTEM INFORMATION ---

<?php if ($HoststatusIcon->getState() !== 0): ?>
--- BEGIN ACK2 INFORMATION ---
ACK_HOSTNAME: <?= $Host->getHostname(); ?>
<?= PHP_EOL ?>
ACK_HOSTUUID: <?= $Host->getUuid(); ?>
<?= PHP_EOL ?>
ACK_SERVICEDESC:
ACK_SERVICEUUID:
ACK_STATE: <?= $HoststatusIcon->getHumanState(); ?>
<?= PHP_EOL ?>
ACK_NOTIFICATIONTYPE: HOST
--- END ACK2 INFORMATION ---
<?php endif; ?>
<?php //@formatter:on ?>
