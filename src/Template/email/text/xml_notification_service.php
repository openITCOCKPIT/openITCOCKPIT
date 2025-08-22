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
 * @var \itnovum\openITCOCKPIT\Core\Views\Service $Service
 * @var \itnovum\openITCOCKPIT\Core\Views\ServicestatusIcon $ServicestatusIcon
 * @var \Cake\Console\Arguments $args
 * @var string $systemAddress
 * @var string $ticketsystemUrl
 * @var array $charts
 */

?>
<?xml version="1.0" encoding="UTF-8"?>
<notificationService>
    <eventId><?= \itnovum\openITCOCKPIT\Core\UUID::v4(); ?></eventId>
    <service><?= $Service->getServicename(); ?></service>
    <host><?= $Host->getHostname(); ?></host>
    <serviceDescription><?= $Service->getDescription(); ?></serviceDescription>
    <time><?= date('H:i:s T'); ?></time>
    <hostDescription><?= $Host->getDescription(); ?></hostDescription>
    <hostAddress><?= $Host->getAddress(); ?></hostAddress>
    <state><?= $ServicestatusIcon->getHumanState(); ?></state>
    <output><?= $args->getOption('serviceoutput'); ?></output>
    <?php if (!empty($args->getOption('servicelongoutput'))): ?>
        <longServiceOutput><?= str_replace(['\n', '\r\n', '\r'], "\n", $args->getOption('servicelongoutput')); ?></longServiceOutput>
    <?php endif; ?>
    <ticketHostname><?= $Host->getHostname(); ?></ticketHostname>
    <ticketHostuuid><?= $Host->getUuid(); ?></ticketHostuuid>
    <ticketServicedesc><?= $Service->getServicename(); ?></ticketServicedesc>
    <ticketServiceuuid><?= $Service->getUuid(); ?></ticketServiceuuid>
    <ticketState><?= $ServicestatusIcon->getHumanState(); ?></ticketState>
    <ticketNotificationtype>SERVICE</ticketNotificationtype>
    <ticketCommandNumber>34</ticketCommandNumber>
    <?php if ($ServicestatusIcon->getState() !== 0): ?>
        <ackHostname><?= $Host->getHostname(); ?></ackHostname>
        <ackHostuuid><?= $Host->getUuid(); ?></ackHostuuid>
        <ackServicedesc><?= $Service->getServicename(); ?></ackServicedesc>
        <ackServiceuuid><?= $Service->getUuid(); ?></ackServiceuuid>
        <ackState><?= $ServicestatusIcon->getHumanState(); ?></ackState>
        <ackNotificationtype>SERVICE</ackNotificationtype>
    <?php endif; ?>
</notificationService>
