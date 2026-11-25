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

namespace App\itnovum\openITCOCKPIT\Core;

use App\Model\Table\SystemsettingsTable;
use Cake\Console\ConsoleIo;
use Cake\Mailer\Mailer;
use Cake\ORM\TableRegistry;
use itnovum\openITCOCKPIT\Core\Views\Logo;
use itnovum\openITCOCKPIT\Core\Views\ServicestatusIcon;

/**
 * I will notify about the health of one or more satellites of the DistributeModule.
 * I will only send the notification if at least one satellite was added via addSatelliteState().
 */
class SatelliteHealthNotification {
    private string $recipientEmail;
    private string $recipientName;
    private ConsoleIo $io;

    /**
     * I will hold the state of the notification (CRITICAL, WARNING or OK).
     * @var string
     */
    private string $state = 'unknown';

    /**
     * I will hold the satellite records (as returned by SystemHealthCommand::getSatellitesStatusWithHealth())
     * enriched with the key "state" (ok, warning, critical or unknown).
     *
     * @var array
     */
    private array $satelliteStates = [];


    /**
     * I will create a SatelliteHealthNotification for one recipient.
     *
     * @param string $recipientEmail
     * @param string $recipientName
     * @param ConsoleIo $io
     */
    public function __construct(string $recipientEmail, string $recipientName, ConsoleIo $io) {
        $this->recipientEmail = $recipientEmail;
        $this->recipientName = $recipientName;
        $this->io = $io;
    }


    /**
     * I will return the state of the notification.
     *
     * @return string
     */
    public function getState(): string {
        return $this->state;
    }

    /**
     * I will add a satellite to the notification.
     *
     * @param array $satelliteState Satellite record incl. satellite_status, satellite_information and state
     * @return void
     */
    public function addSatelliteState(array $satelliteState): void {
        $this->satelliteStates[] = $satelliteState;
    }

    /**
     * I will return all satellites added to the notification.
     *
     * @return array
     */
    public function getSatelliteStates(): array {
        return $this->satelliteStates;
    }


    /**
     * I will set the state of the notification.
     *
     * @param string $state
     * @return void
     */
    public function setState(string $state): void {
        $this->state = $state;
    }

    /**
     * I will send the notification, if at least one satellite was added.
     *
     * @return void
     */
    public function sendNotification(): void {
        if (empty($this->satelliteStates)) {
            $this->io->verbose('No satellite states to send, skipping SatelliteHealthNotification for this user.');
            return;
        }
        /** @var SystemsettingsTable $SystemsettingsTable */
        $SystemsettingsTable = TableRegistry::getTableLocator()->get('Systemsettings');
        $systemsettings = $SystemsettingsTable->findAsArray();

        $statusIcon = ServicestatusIcon::fromStatusText($this->state);
        $subject = $statusIcon->getEmoji() . ' ' . __('Satellites System Health is {0}', $this->getState());
        $Logo = new Logo();

        // Formulate Email
        $Mailer = new Mailer();
        $Mailer->setFrom($systemsettings['MONITORING']['MONITORING.FROM_ADDRESS'], $systemsettings['MONITORING']['MONITORING.FROM_NAME']);
        $Mailer->setTo($this->recipientEmail, $this->recipientName);
        $Mailer->setSubject($subject);
        $Mailer->setEmailFormat('both');
        $Mailer->setAttachments([
            'logo.png' => [
                'file'      => $Logo->getSmallLogoDiskPath(),
                'mimetype'  => 'image/png',
                'contentId' => '100'
            ]
        ]);

        $Mailer->viewBuilder()
            ->setTemplate('notification_satellite_health')
            ->setVar('systemname', $systemsettings['FRONTEND']['FRONTEND.SYSTEMNAME'])
            ->setVar('StatusIcon', $statusIcon)
            ->setVar('satellites', $this->getSatelliteStates())
            ->setVar('systemAddress', $systemsettings['SYSTEM']['SYSTEM.ADDRESS']);

        $this->io->verbose(sprintf('Sending SatelliteHealthNotification (%s, %d satellite(s)) to %s', $this->state, count($this->satelliteStates), $this->recipientEmail));
        $Mailer->deliver();
    }


}
