<?php
// Copyright (C) <2015-present>  <it-novum GmbH>
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


declare(strict_types=1);

namespace App\Command;

use App\itnovum\openITCOCKPIT\Core\EmailCharacters;
use App\Model\Table\HostsTable;
use App\Model\Table\ServicesTable;
use App\Model\Table\SystemsettingsTable;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Plugin;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Log\Log;
use Cake\Mailer\Mailer;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use GuzzleHttp\Exception\GuzzleException;
use itnovum\openITCOCKPIT\Core\DbBackend;
use itnovum\openITCOCKPIT\Core\NodeJS\ChartRenderClient;
use itnovum\openITCOCKPIT\Core\PerfdataBackend;
use itnovum\openITCOCKPIT\Core\ServicestatusFields;
use itnovum\openITCOCKPIT\Core\Views\Host;
use itnovum\openITCOCKPIT\Core\Views\HoststatusIcon;
use itnovum\openITCOCKPIT\Core\Views\Logo;
use itnovum\openITCOCKPIT\Core\Views\Service;
use itnovum\openITCOCKPIT\Core\Views\ServicestatusIcon;
use itnovum\openITCOCKPIT\Perfdata\PerfdataLoader;
use Spatie\Emoji\Emoji;

/**
 * NagiosNotification command.
 */
class NagiosXmlNotificationCommand extends Command {

    /**
     * host|service
     * @var string
     */
    private $type = 'host';

    /**
     * PROBLEM", "RECOVERY", "ACKNOWLEDGEMENT", "FLAPPINGSTART", "FLAPPINGSTOP",
     * "FLAPPINGDISABLED", "DOWNTIMESTART", "DOWNTIMEEND", or "DOWNTIMECANCELLED"
     *
     * @var string
     */
    private $notificationtype = '';

    /**
     * Host UUID
     * @var string
     */
    private $hostname;

    /**
     * Service UUID
     * @var string
     */
    private $servicedesc;

    /**
     * Receiver's email address
     * @var string
     */
    private $contactmail;

    /**
     * text, html, both
     * @var string
     */
    private $format = 'text';

    /**
     * Default email template/layout
     * @var string
     */
    private $layout = 'notification';

    /**
     * UP, DOWN or UNREACHABLE
     * @var string
     */
    private $hostState;

    /**
     * OK, WARNING, CRITICAL or UNKNOWN
     * @var string
     */
    private $serviceState;

    private $senderAddress = 'openITCOCKPIT@monitoring.lan';

    private $senderName = 'openITCOCKPIT Notification';

    private $systemname = 'openITCOCKPIT';

    private $systemAddress;

    private $ticketsystemUrl;

    /**
     * @var null|string
     */
    private $replyTo = null;

    private $noAttachments = false;

    private $noEmoji = false;

    /**
     * Hook method for defining this command's option parser.
     *
     * @see https://book.cakephp.org/3.0/en/console-and-shells/commands.html#defining-arguments-and-options
     *
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
     * @return \Cake\Console\ConsoleOptionParser The built parser.
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser {
        $parser = parent::buildOptionParser($parser);

        $parser->addOptions([
            'type'              => ['short' => 't', 'help' => __d('oitc_console', 'Type of the notification host or service'), 'default' => 'host'],
            'notificationtype'  => ['help' => __d('oitc_console', 'Notification type of monitoring engine')],
            'hostname'          => ['help' => __d('oitc_console', 'Host uuid you want to send a notification')],
            'hostdescription'   => ['help' => __d('oitc_console', 'Host description you want to send a notification')],
            'hoststate'         => ['help' => __d('oitc_console', 'current host state')],
            'hostaddress'       => ['help' => __d('oitc_console', 'host address')],
            'hostoutput'        => ['help' => __d('oitc_console', 'host output')],
            'hostlongoutput'    => ['help' => __d('oitc_console', 'host long output')],
            'hostackauthor'     => ['help' => __d('oitc_console', 'host acknowledgement author')],
            'hostackcomment'    => ['help' => __d('oitc_console', 'host acknowledgement comment')],
            'contactmail'       => ['help' => __d('oitc_console', 'recivers mail address')],
            'contactalias'      => ['help' => __d('oitc_console', 'human name of the contact')],
            'servicedesc'       => ['help' => __d('oitc_console', 'Service uuid you want to notify')],
            'servicestate'      => ['help' => __d('oitc_console', 'service state')],
            'serviceoutput'     => ['help' => __d('oitc_console', 'service output')],
            'servicelongoutput' => ['help' => __d('oitc_console', 'service long output')],
            'serviceackauthor'  => ['help' => __d('oitc_console', 'service acknowledgement author')],
            'serviceackcomment' => ['help' => __d('oitc_console', 'service acknowledgement comment')],
            'no-attachments'    => ['help' => __d('oitc_console', 'disable email attachments'), 'boolean' => true, 'default' => false],
            'no-emoji'          => ['help' => __d('oitc_console', 'Disable emojis in subject'), 'boolean' => true, 'default' => false],
            'layout'            => ['help' => __d('oitc_console', 'E-Mail layout/template that should be used'), 'default' => 'notification'],
        ]);

        return $parser;
    }

    /**
     * Implement this method with your command's logic.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return null|void|int The exit code or null for success
     * @throws \itnovum\openITCOCKPIT\ApiShell\Exceptions\MissingParameterExceptions
     */
    public function execute(Arguments $args, ConsoleIo $io) {
        $this->validateOptions($args);

        $Host = $this->getHost($this->hostname);

        /** @var SystemsettingsTable $SystemsettingsTable */
        $SystemsettingsTable = TableRegistry::getTableLocator()->get('Systemsettings');
        $_systemsettings = $SystemsettingsTable->findAsArray();

        $this->senderAddress = $_systemsettings['MONITORING']['MONITORING.FROM_ADDRESS'];
        $this->senderName = $_systemsettings['MONITORING']['MONITORING.FROM_NAME'];
        $this->systemname = $_systemsettings['FRONTEND']['FRONTEND.SYSTEMNAME'];
        $this->systemAddress = $_systemsettings['SYSTEM']['SYSTEM.ADDRESS'];
        $this->ticketsystemUrl = $_systemsettings['TICKET_SYSTEM']['TICKET_SYSTEM.URL'];

        //if (!empty($_systemsettings['MONITORING']['MONITORING.ACK_RECEIVER_ADDRESS'])) {
        //    $this->replyTo = $_systemsettings['MONITORING']['MONITORING.ACK_RECEIVER_ADDRESS'];
        //}


        if ($this->type === 'host') {
            $this->sendHostNotification($Host, $args);
        }

        if ($this->type === 'service') {
            $Service = $this->getService($this->servicedesc);
            $this->sendServiceNotification($Host, $Service, $args);
        }


    }

    /**
     * @param Host $Host
     * @param Arguments $args
     */
    private function sendHostNotification(Host $Host, Arguments $args) {
        $Logo = new Logo();
        $HoststatusIcon = $this->getHoststatusIcon();

        $Mailer = new Mailer();
        $Mailer->setFrom($this->senderAddress, $this->senderName);
        if ($this->replyTo !== null) {
            $Mailer->setReplyTo($this->replyTo);
        }

        $toName = null;
        if ($args->getOption('contactalias') !== '') {
            $toName = $args->getOption('contactalias');
            if (!empty($toName) && is_string($toName)) {
                $toName = EmailCharacters::removeDangerousCharactersForToHeader($toName);
            }
        }
        $Mailer->addTo($this->contactmail, $toName);
        $Mailer->setSubject($this->getHostSubject($Host, $HoststatusIcon));
        $Mailer->setEmailFormat($this->format);

        if ($this->noAttachments === false && $this->format !== 'text') {
            $Mailer->setAttachments([
                'logo.png' => [
                    'file'      => $Logo->getSmallLogoPdfPath(),
                    'mimetype'  => 'image/png',
                    'contentId' => '100'
                ]
            ]);
        }
        $Mailer->viewBuilder()
            ->setTemplate('xml_' . $this->layout . '_' . $this->type)
            ->setVar('systemname', $this->systemname)
            ->setVar('noAttachments', $this->noAttachments)
            ->setVar('noEmoji', $this->noEmoji)
            ->setVar('Host', $Host)
            ->setVar('HoststatusIcon', $HoststatusIcon)
            ->setVar('args', $args)
            ->setVar('systemAddress', $this->systemAddress)
            ->setVar('ticketsystemUrl', $this->ticketsystemUrl);

        $Mailer->deliver();
    }

    /**
     * @param Host $Host
     * @param Service $Service
     * @param Arguments $args
     * @throws \App\Lib\Exceptions\MissingDbBackendException
     */
    private function sendServiceNotification(Host $Host, Service $Service, Arguments $args) {
        $Logo = new Logo();
        $HoststatusIcon = $this->getHoststatusIcon();
        $ServicestatusIcon = $this->getServicestatusIcon();

        $Mailer = new Mailer();
        $Mailer->setFrom($this->senderAddress, $this->senderName);
        if ($this->replyTo !== null) {
            $Mailer->setReplyTo($this->replyTo);
        }

        $toName = null;
        if ($args->getOption('contactalias') !== '') {
            $toName = $args->getOption('contactalias');
            if (!empty($toName) && is_string($toName)) {
                $toName = EmailCharacters::removeDangerousCharactersForToHeader($toName);
            }
        }
        $Mailer->addTo($this->contactmail, $toName);
        $Mailer->setSubject($this->getServiceSubject(
            $Host,
            $Service,
            $ServicestatusIcon
        ));
        $Mailer->setEmailFormat($this->format);

        $charts = [];
        $evcTree = null;
        if ($this->noAttachments === false && $this->format !== 'text') {
            $charts = $this->getServiceCharts($Host, $Service);

            //Add Logo to attachments
            $attachments = $charts;
            $attachments['logo.png'] = [
                'file'      => $Logo->getSmallLogoPdfPath(),
                'mimetype'  => 'image/png',
                'contentId' => '100'
            ];
            $Mailer->setAttachments($attachments);


            if ($Service->getServiceType() === EVK_SERVICE) {
                if (Plugin::isLoaded('EventcorrelationModule')) {
                    $DbBackend = new DbBackend();
                    $ServicestatusTable = $DbBackend->getServicestatusTable();

                    $ServicestatusFields = new ServicestatusFields($DbBackend);
                    $ServicestatusFields
                        ->currentState()
                        ->isHardstate();

                    /** @var \EventcorrelationModule\Model\Table\EventcorrelationSettingsTable $EventcorrelationSettingsTable */
                    $EventcorrelationSettingsTable = TableRegistry::getTableLocator()->get('EventcorrelationModule.EventcorrelationSettings');
                    $currentEventcorrelationSettings = $EventcorrelationSettingsTable->getCurrentEventcorrelationSettings();
                    $defaultEventcorrelationSettings = $EventcorrelationSettingsTable->getDefaultEventcorrelationSettings();

                    $considerStateType = ($defaultEventcorrelationSettings['EVC_CONSIDER_STATETYPE']['value'] & $currentEventcorrelationSettings->get('configuration_option'));


                    try {
                        /** @var \EventcorrelationModule\Model\Table\EventcorrelationsTable $EventcorrelationsTable */
                        $EventcorrelationsTable = TableRegistry::getTableLocator()->get('EventcorrelationModule.Eventcorrelations');
                        $evcTree = $EventcorrelationsTable->getEvcTreeData($Service->getHostId(), []);
                        $serviceUuids = array_unique(Hash::extract($evcTree, '{n}.{*}.{n}.service.uuid'));
                        $servicestatus = $ServicestatusTable->byUuid($serviceUuids, $ServicestatusFields);

                    } catch (RecordNotFoundException $e) {
                        $evcTree = [];
                    }

                    foreach ($evcTree as $layerNumber => $layer) {
                        foreach ($layer as $parentId => $childNodes) {
                            foreach ($childNodes as $childIndex => $childNode) {
                                $Servicestatus = new \itnovum\openITCOCKPIT\Core\Servicestatus([]);
                                if (!empty($servicestatus[$childNode['service']['uuid']])) {
                                    $Servicestatus = new \itnovum\openITCOCKPIT\Core\Servicestatus(
                                        $servicestatus[$childNode['service']['uuid']]['Servicestatus']
                                    );
                                }

                                if ($considerStateType && !$Servicestatus->isHardState()) {
                                    $Servicestatus->setCurrentState($Servicestatus->getLastHardState());
                                }

                                $evcTree[$layerNumber][$parentId][$childIndex]['service']['servicestatus'] = $Servicestatus->toArray();
                            }
                        }
                    }
                }
            }
        }

        $Mailer->viewBuilder()
            ->setTemplate('xml_' . $this->layout . '_' . $this->type)
            ->setVar('systemname', $this->systemname)
            ->setVar('noAttachments', $this->noAttachments)
            ->setVar('noEmoji', $this->noEmoji)
            ->setVar('Host', $Host)
            ->setVar('HoststatusIcon', $HoststatusIcon)
            ->setVar('Service', $Service)
            ->setVar('ServicestatusIcon', $ServicestatusIcon)
            ->setVar('args', $args)
            ->setVar('systemAddress', $this->systemAddress)
            ->setVar('ticketsystemUrl', $this->ticketsystemUrl)
            ->setVar('charts', $charts)
            ->setVar('evcTree', $evcTree);

        $Mailer->deliver();
    }

    /**
     * @return HoststatusIcon
     */
    private function getHoststatusIcon() {
        switch ($this->hostState) {
            case 'UP':
                $stateId = 0;
                break;
            case 'DOWN':
                $stateId = 1;
                break;
            default:
                $stateId = 2;
                break;
        }

        return new HoststatusIcon($stateId);
    }


    /**
     * @return ServicestatusIcon
     */
    private function getServicestatusIcon() {
        switch ($this->serviceState) {
            case 'OK':
                $stateId = 0;
                break;
            case 'WARNING':
                $stateId = 1;
                break;
            case 'CRITICAL':
                $stateId = 2;
                break;
            default:
                $stateId = 3;
                break;
        }

        return new ServicestatusIcon($stateId);
    }

    /**
     * @param Host $Host
     * @return string
     */
    private function getHostSubject(Host $Host, HoststatusIcon $HoststatusIcon) {
        if ($this->isAcknowledgement()) {
            $title = sprintf(
                'Acknowledgement for %s (%s)',
                $Host->getHostname(),
                $HoststatusIcon->getHumanState()
            );

            if ($this->noEmoji === false) {
                $title = Emoji::speechBalloon() . ' ' . $title;
            }
            return $title;
        }

        if ($this->isDowntimeStart()) {
            $title = sprintf(
                'Host %s has entered a period of scheduled downtime',
                $Host->getHostname()
            );

            if ($this->noEmoji === false) {
                $title = Emoji::zzz() . ' ' . $title;
            }
            return $title;
        }

        if ($this->isDowntimeEnd()) {
            $title = sprintf(
                'Host %s has exited from a period of scheduled downtime',
                $Host->getHostname()
            );

            if ($this->noEmoji === false) {
                $title = Emoji::eightSpokedAsterisk() . ' ' . $title;
            }
            return $title;
        }

        if ($this->isDowntimeCancelled()) {
            $title = sprintf(
                'Scheduled downtime has been cancelled for %s',
                $Host->getHostname()
            );

            if ($this->noEmoji === false) {
                $title = Emoji::wastebasket() . ' ' . $title;
            }
            return $title;
        }

        if ($this->isFlappingStart()) {
            $title = sprintf(
                'Host %s appears to have started flapping',
                $Host->getHostname()
            );

            return $title;
        }

        if ($this->isFlappingStop()) {
            $title = sprintf(
                'Host %s appears to have stopped flapping',
                $Host->getHostname()
            );

            return $title;
        }

        if ($this->isFlappingDisabled()) {
            $title = sprintf(
                'Flap detection has been disabled for %s',
                $Host->getHostname()
            );

            return $title;
        }

        //Default notification
        $title = sprintf(
            '%s: %s is %s!',
            $this->notificationtype,
            $Host->getHostname(),
            $HoststatusIcon->getHumanState()
        );

        if ($this->noEmoji === false) {
            $title = $HoststatusIcon->getEmoji() . ' ' . $title;
        }
        return $title;
    }

    /**
     * @param Host $Host
     * @param Service $Service
     * @param ServicestatusIcon $ServicestatusIcon
     * @return string
     */
    private function getServiceSubject(Host $Host, Service $Service, ServicestatusIcon $ServicestatusIcon) {
        if ($this->isAcknowledgement()) {
            $title = sprintf(
                'Acknowledgement for %s/%s (%s)',
                $Host->getHostname(),
                $Service->getServicename(),
                $ServicestatusIcon->getHumanState()
            );

            if ($this->noEmoji === false) {
                $title = Emoji::speechBalloon() . ' ' . $title;
            }
            return $title;
        }

        if ($this->isDowntimeStart()) {
            $title = sprintf(
                'Service %s/%s has entered a period of scheduled downtime',
                $Host->getHostname(),
                $Service->getServicename()
            );

            if ($this->noEmoji === false) {
                $title = Emoji::zzz() . ' ' . $title;
            }
            return $title;
        }

        if ($this->isDowntimeEnd()) {
            $title = sprintf(
                'Service %s/%s has exited from a period of scheduled downtime',
                $Host->getHostname(),
                $Service->getServicename()
            );

            if ($this->noEmoji === false) {
                $title = Emoji::eightSpokedAsterisk() . ' ' . $title;
            }
            return $title;
        }

        if ($this->isDowntimeCancelled()) {
            $title = sprintf(
                'Scheduled downtime has been cancelled for %s/%s',
                $Host->getHostname(),
                $Service->getServicename()
            );

            if ($this->noEmoji === false) {
                $title = Emoji::wastebasket() . ' ' . $title;
            }
            return $title;
        }

        if ($this->isFlappingStart()) {
            $title = sprintf(
                'Service %s/%s appears to have started flapping',
                $Host->getHostname(),
                $Service->getServicename()
            );

            return $title;
        }

        if ($this->isFlappingStop()) {
            $title = sprintf(
                'Service %s/%s appears to have stopped flapping',
                $Host->getHostname(),
                $Service->getServicename()
            );

            return $title;
        }

        if ($this->isFlappingDisabled()) {
            $title = sprintf(
                'Flap detection has been disabled for %s/%s',
                $Host->getHostname(),
                $Service->getServicename()
            );

            return $title;
        }

        //Default notification
        $title = sprintf(
            '%s: %s on %s is %s!',
            $this->notificationtype,
            $Service->getServicename(),
            $Host->getHostname(),
            $ServicestatusIcon->getHumanState()
        );

        if ($this->noEmoji === false) {
            $title = $ServicestatusIcon->getEmoji() . ' ' . $title;
        }
        return $title;
    }

    /**
     * @param Host $Host
     * @param Service $Service
     * @return array
     * @throws \App\Lib\Exceptions\MissingDbBackendException
     */
    private function getServiceCharts(Host $Host, Service $Service) {
        $DbBackend = new DbBackend();
        $PerfdataBackend = new PerfdataBackend();
        $PerfdataLoader = new PerfdataLoader($DbBackend, $PerfdataBackend);

        $attachments = [];

        try {
            $graphStart = (time() - (4 * 3600));
            $graphData = $PerfdataLoader->getPerfdataByUuid(
                $Host->getUuid(),
                $Service->getUuid(),
                $graphStart,
                time(),
                false,
                'avg'
            );

            if (!empty($graphData)) {
                //Render graph data to png image blobs for pdf
                $PuppeteerChartRenderClient = new ChartRenderClient();
                $PuppeteerChartRenderClient->setGraphStartTimestamp($graphStart);
                $PuppeteerChartRenderClient->setGraphEndTimestamp(time());
                $PuppeteerChartRenderClient->setHeight(180);
                $PuppeteerChartRenderClient->setWidth(560);
                $PuppeteerChartRenderClient->setTitle(
                    sprintf(
                        '%s - %s',
                        $Host->getHostname(),
                        $Service->getServicename()
                    ));

                // Render two gauges per chart
                $id = 200;
                foreach (array_chunk($graphData, 2) as $graphDataChunk) {
                    $fileName = sprintf('Chart_%s.png', $id);
                    $attachments[$fileName] = [
                        'data'      => $PuppeteerChartRenderClient->getAreaChartAsPngStream($graphDataChunk),
                        'mimetype'  => 'image/png',
                        'contentId' => 'cid' . $id //Needs to be a string because of CakePHP
                    ];
                    $id++;
                }
            }
        } catch (GuzzleException $e) {
            Log::error('Notification: Error while fetching service chart data');
            Log::error($e->getMessage());
        }

        return $attachments;
    }

    private function isAcknowledgement() {
        return $this->notificationtype === 'ACKNOWLEDGEMENT';
    }

    private function isFlappingStart() {
        return $this->notificationtype === 'FLAPPINGSTART';
    }

    private function isFlappingStop() {
        return $this->notificationtype === 'FLAPPINGSTOP';
    }

    private function isFlappingDisabled() {
        return $this->notificationtype === 'FLAPPINGDISABLED';
    }

    private function isDowntimeStart() {
        return $this->notificationtype === 'DOWNTIMESTART';
    }

    private function isDowntimeEnd() {
        return $this->notificationtype === 'DOWNTIMEEND';
    }

    private function isDowntimeCancelled() {
        return $this->notificationtype === 'DOWNTIMECANCELLED';
    }

    /**
     * @param string $hostUuid
     * @return Host
     */
    public function getHost(string $hostUuid) {
        /** @var HostsTable $HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');

        try {
            $host = $HostsTable->getHostByUuid($hostUuid, false);
        } catch (RecordNotFoundException $e) {
            throw new \RuntimeException(sprintf('Host with uuid "%s" could not be found!', $hostUuid));
        }

        return new Host($host);
    }

    /**
     * @param string $serviceUuid
     * @return Service
     */
    public function getService(string $serviceUuid) {
        /** @var ServicesTable $ServicesTable */
        $ServicesTable = TableRegistry::getTableLocator()->get('Services');

        try {
            $service = $ServicesTable->getServiceByUuid($serviceUuid, false);
        } catch (RecordNotFoundException $e) {
            throw new \RuntimeException(sprintf('Service with uuid "%s" could not be found!', $serviceUuid));
        }

        return new Service($service);
    }

    /**
     * @param Arguments $args
     * @throws \itnovum\openITCOCKPIT\ApiShell\Exceptions\MissingParameterExceptions
     */
    private function validateOptions(Arguments $args) {

        if ($args->getOption('type') === '') {
            throw new \itnovum\openITCOCKPIT\ApiShell\Exceptions\MissingParameterExceptions(
                'Option --type is missing'
            );
        }

        $this->type = strtolower($args->getOption('type'));

        if ($args->getOption('notificationtype') === '') {
            throw new \itnovum\openITCOCKPIT\ApiShell\Exceptions\MissingParameterExceptions(
                'Option --notificationtype is missing'
            );
        }
        $this->notificationtype = $args->getOption('notificationtype');

        if ($args->getOption('hostname') === '') {
            throw new \itnovum\openITCOCKPIT\ApiShell\Exceptions\MissingParameterExceptions(
                'Option --hostname is missing'
            );
        }
        $this->hostname = $args->getOption('hostname');

        if ($args->getOption('hoststate') === '') {
            throw new \itnovum\openITCOCKPIT\ApiShell\Exceptions\MissingParameterExceptions(
                'Option --hoststate is missing'
            );
        }
        $this->hostState = $args->getOption('hoststate');


        if ($this->type === 'service') {
            if ($args->getOption('servicedesc') === '') {
                throw new \itnovum\openITCOCKPIT\ApiShell\Exceptions\MissingParameterExceptions(
                    'Option --servicedesc is missing'
                );
            }
            $this->servicedesc = $args->getOption('servicedesc');

            if ($args->getOption('servicestate') === '') {
                throw new \itnovum\openITCOCKPIT\ApiShell\Exceptions\MissingParameterExceptions(
                    'Option --servicestate is missing'
                );
            }
            $this->serviceState = $args->getOption('servicestate');
        }

        if ($args->getOption('contactmail') === '') {
            throw new \itnovum\openITCOCKPIT\ApiShell\Exceptions\MissingParameterExceptions(
                'Option --contactmail is missing'
            );
        }
        $this->contactmail = $args->getOption('contactmail');


        if ($args->getOption('layout') !== 'default') {
            $layout = $args->getOption('layout');
            $xmlTemplateExists = false;


            $filename = sprintf('%s_%s.php', $layout, $this->type);

            if (file_exists(APP . 'Template' . DS . 'email' . DS . 'text' . DS . 'xml_' . $filename)) {
                $xmlTemplateExists = true;
            }

            if ($xmlTemplateExists) {
                $this->layout = $layout;
            } else {
                Log::error('Notification: XML layout "' . $layout . '" not found. Using default.');
            }

        }

        $this->noAttachments = $args->getOption('no-attachments');
        $this->noEmoji = $args->getOption('no-emoji');
    }
}
