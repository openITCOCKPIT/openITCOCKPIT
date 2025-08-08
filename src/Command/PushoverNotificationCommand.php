<?php
// The MIT License
//
// Copyright <2018-present> <Daniel Ziegler>
//
// Permission is hereby granted, free of charge, to any person obtaining a copy of this
// software and associated documentation files (the "Software"), to deal in the Software
// without restriction, including without limitation the rights touse, copy, modify,
// merge, publish, distribute, sublicense, and/or sell copies
// of the Software, and to permit persons to whom the Software is
// furnished to do so, subject to the following conditions:
//
// The above copyright notice and this permission notice shall be
// included in all copies or substantial portions of the Software.
//
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
// INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR
// A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
// HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
// TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR
// THE USE OR OTHER DEALINGS IN THE SOFTWARE.


declare(strict_types=1);

namespace App\Command;

use App\Model\Table\HostsTable;
use App\Model\Table\ProxiesTable;
use App\Model\Table\ServicesTable;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\ORM\TableRegistry;
use GuzzleHttp\Client;
use itnovum\openITCOCKPIT\Core\Views\Host;
use itnovum\openITCOCKPIT\Core\Views\HoststatusIcon;
use itnovum\openITCOCKPIT\Core\Views\Service;
use itnovum\openITCOCKPIT\Core\Views\ServicestatusIcon;

/**
 * PushoverNotification command.
 */
class PushoverNotificationCommand extends Command {
    /**
     * @var bool
     */
    private $useProxy = false;

    private $type = 'host';

    private $hostUuid = '';

    private $serviceUuid = '';

    /**
     * 0 = Up, 1 = Down, 2 = Unreachable
     * 0 = Ok, 1 = Warning, 2 = Critical, 3 = Unknown
     *
     * @var null|int
     */
    private $state = null;

    private $output = '';

    /**
     * PROBLEM", "RECOVERY", "ACKNOWLEDGEMENT", "FLAPPINGSTART", "FLAPPINGSTOP",
     * "FLAPPINGDISABLED", "DOWNTIMESTART", "DOWNTIMEEND", or "DOWNTIMECANCELLED"
     *
     * @var string
     */
    private $notificationtype = '';

    private $ackAuthor = false;

    private $ackComment = false;

    private $pushoverApiToken = '';

    private $pushoverUserToken = '';

    private $pushoverApiUrl = 'https://api.pushover.net/1/messages.json';

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
            'type'                => ['short' => 't', 'help' => __d('oitc_console', 'Type of the notification host or service')],
            'notificationtype'    => ['help' => __d('oitc_console', 'Notification type of monitoring engine => $NOTIFICATIONTYPE$ ')],
            'hostuuid'            => ['help' => __d('oitc_console', 'Host uuid you want to send a notification => $HOSTNAME$')],
            'serviceuuid'         => ['help' => __d('oitc_console', 'Service uuid you want to send a notification => $SERVICEDESC$')],
            'state'               => ['help' => __d('oitc_console', 'current host state => $HOSTSTATEID$/$SERVICESTATEID$')],
            'output'              => ['help' => __d('oitc_console', 'host output => $HOSTOUTPUT$/$SERVICEOUTPUT$')],
            'ackauthor'           => ['help' => __d('oitc_console', 'host acknowledgement author => $NOTIFICATIONAUTHOR$')],
            'ackcomment'          => ['help' => __d('oitc_console', 'host acknowledgement comment => $NOTIFICATIONCOMMENT$')],
            'pushover-api-token'  => ['help' => __d('oitc_console', 'API Token of your Pushover Application => $_CONTACTPUSHOVERAPP$')],
            'pushover-user-token' => ['help' => __d('oitc_console', 'Your Pushover User Key => $_CONTACTPUSHOVERUSER$')],
            'proxy'               => ['help' => __d('oitc_console', 'If set, connection will be established using the proxy server defined in openITCOCKPIT interface.'), 'boolean' => true, 'default' => false]
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

        /** @var HostsTable $HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');

        try {
            $host = $HostsTable->getHostByUuid($this->hostUuid, false);
        } catch (RecordNotFoundException $e) {
            throw new \RuntimeException(sprintf('Host with uuid "%s" could not be found!', $this->hostUuid));
        }

        $Host = new Host($host);

        if ($this->type === 'service') {
            /** @var ServicesTable $ServicesTable */
            $ServicesTable = TableRegistry::getTableLocator()->get('Services');

            try {
                $service = $ServicesTable->getServiceByUuid($this->serviceUuid, false);
            } catch (RecordNotFoundException $e) {
                throw new \RuntimeException(sprintf('Service with uuid "%s" could not be found!', $this->serviceUuid));
            }

            $Service = new Service($service);
            $this->sendServiceNotification($Host, $Service);
            exit(0);
        }


        $this->sendHostNotification($Host);
        exit(0);
    }

    private function sendHostNotification(Host $Host) {
        $HoststatusIcon = new HoststatusIcon($this->state);

        if ($this->isAcknowledgement()) {
            $title = sprintf(
                'Acknowledgement for %s (%s)',
                $Host->getHostname(),
                $HoststatusIcon->getHumanState()
            );

            $message = sprintf('<b>%s:</b> %s', $this->ackAuthor, $this->ackComment);
            return $this->push($title, $message);
        }

        if ($this->isDowntimeStart()) {
            $title = sprintf(
                'Downtime start for %s',
                $Host->getHostname()
            );

            $message = 'Host has entered a period of scheduled downtime';
            return $this->push($title, $message);
        }

        if ($this->isDowntimeEnd()) {
            $title = sprintf(
                'Downtime end for %s',
                $Host->getHostname()
            );

            $message = 'Host has exited from a period of scheduled downtime';
            return $this->push($title, $message);
        }

        if ($this->isDowntimeCancelled()) {
            $title = sprintf(
                'Downtime cancelled for %s',
                $Host->getHostname()
            );

            $message = 'Scheduled downtime for host has been cancelled';
            return $this->push($title, $message);
        }

        if ($this->isFlappingStart()) {
            $title = sprintf(
                'Flapping started on %s',
                $Host->getHostname()
            );

            $message = 'Host appears to have started flapping';
            return $this->push($title, $message);
        }

        if ($this->isFlappingStop()) {
            $title = sprintf(
                'Flapping stopped on %s',
                $Host->getHostname()
            );

            $message = 'Host appears to have stopped flapping';
            return $this->push($title, $message);
        }

        if ($this->isFlappingDisabled()) {
            $title = sprintf(
                'Disabled flap detection for %s',
                $Host->getHostname()
            );

            $message = 'Flap detection has been disabled';
            return $this->push($title, $message);
        }

        //Default notification
        $title = sprintf(
            '%s: %s is %s!',
            $this->notificationtype,
            $Host->getHostname(),
            $HoststatusIcon->getHumanState()
        );

        $this->push($title, $this->output);
    }

    private function sendServiceNotification(Host $Host, Service $Service) {
        $ServicestatusIcon = new ServicestatusIcon($this->state);

        if ($this->isAcknowledgement()) {
            $title = sprintf(
                'Acknowledgement for %s/%s (%s)',
                $Host->getHostname(),
                $Service->getServicename(),
                $ServicestatusIcon->getHumanState()
            );

            $message = sprintf('<b>%s:</b> %s', $this->ackAuthor, $this->ackComment);
            return $this->push($title, $message);
        }

        if ($this->isDowntimeStart()) {
            $title = sprintf(
                'Downtime start for %s/%s',
                $Host->getHostname(),
                $Service->getServicename()
            );

            $message = 'Service has entered a period of scheduled downtime';
            return $this->push($title, $message);
        }

        if ($this->isDowntimeEnd()) {
            $title = sprintf(
                'Downtime end for %s/%s',
                $Host->getHostname(),
                $Service->getServicename()
            );

            $message = 'Service has exited from a period of scheduled downtime';
            return $this->push($title, $message);
        }

        if ($this->isDowntimeCancelled()) {
            $title = sprintf(
                'Downtime cancelled for %s/%s',
                $Host->getHostname(),
                $Service->getServicename()
            );

            $message = 'Scheduled downtime for service has been cancelled';
            return $this->push($title, $message);
        }

        if ($this->isFlappingStart()) {
            $title = sprintf(
                'Flapping started on %s/%s',
                $Host->getHostname(),
                $Service->getServicename()
            );

            $message = 'Service appears to have started flapping';
            return $this->push($title, $message);
        }

        if ($this->isFlappingStop()) {
            $title = sprintf(
                'Flapping stopped on %s/%s',
                $Host->getHostname(),
                $Service->getServicename()
            );

            $message = 'Service appears to have stopped flapping';
            return $this->push($title, $message);
        }

        if ($this->isFlappingDisabled()) {
            $title = sprintf(
                'Disabled flap detection for %s/%s',
                $Host->getHostname(),
                $Service->getServicename()
            );

            $message = 'Flap detection has been disabled';
            return $this->push($title, $message);
        }

        //Default notification
        $title = sprintf(
            '%s: %s on %s is %s!',
            $this->notificationtype,
            $Service->getServicename(),
            $Host->getHostname(),
            $ServicestatusIcon->getHumanState()
        );

        $this->push($title, $this->output);
    }

    private function push($title, $message) {
        $params = [
            'form_params' => [
                'token'   => $this->pushoverApiToken,
                'user'    => $this->pushoverUserToken,
                'html'    => 1,
                'title'   => $title,
                'message' => $message
            ],
            'proxy'       => [
                'http'  => false,
                'https' => false
            ]
        ];

        $params['proxy'] = [
            'http'  => false,
            'https' => false
        ];
        if ($this->useProxy) {
            /** @var ProxiesTable $ProxiesTable */
            $ProxiesTable = TableRegistry::getTableLocator()->get('Proxies');
            $proxySettings = $ProxiesTable->getSettings();

            if ($proxySettings['enabled']) {
                $params['proxy']['http'] = sprintf('%s:%s', $proxySettings['ipaddress'], $proxySettings['port']);
                $params['proxy']['https'] = $params['proxy']['http'];
            }
        }

        $client = new Client();

        $response = $client->request('POST', $this->pushoverApiUrl, $params);

        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException($response->getBody()->getContents());
        }

        return true;

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
     * @param Arguments $args
     * @throws \itnovum\openITCOCKPIT\ApiShell\Exceptions\MissingParameterExceptions
     */
    private function validateOptions(Arguments $args) {
        $this->useProxy = $args->getOption('proxy');

        if ($args->getOption('type') === '') {
            throw new \itnovum\openITCOCKPIT\ApiShell\Exceptions\MissingParameterExceptions(
                'Option --type is missing'
            );
        }

        $this->state = 2;
        $this->type = strtolower($args->getOption('type'));

        if ($args->getOption('notificationtype') === '') {
            throw new \itnovum\openITCOCKPIT\ApiShell\Exceptions\MissingParameterExceptions(
                'Option --notificationtype is missing'
            );
        }
        $this->notificationtype = $args->getOption('notificationtype');

        if ($args->getOption('hostuuid') === '') {
            throw new \itnovum\openITCOCKPIT\ApiShell\Exceptions\MissingParameterExceptions(
                'Option --hostuuid is missing'
            );
        }
        $this->hostUuid = $args->getOption('hostuuid');

        if ($this->type === 'service') {
            $this->state = 3;
            if ($args->getOption('serviceuuid') === '') {
                throw new \itnovum\openITCOCKPIT\ApiShell\Exceptions\MissingParameterExceptions(
                    'Option --serviceuuid is missing'
                );
            }
            $this->serviceUuid = $args->getOption('serviceuuid');
        }

        if ($args->getOption('state') !== '') {
            //Not all notifications have a state like ack or downtime messages.
            $this->state = (int)$args->getOption('state');
        }

        if ($args->getOption('output') === '') {
            throw new \itnovum\openITCOCKPIT\ApiShell\Exceptions\MissingParameterExceptions(
                'Option --output is missing'
            );
        }
        $this->output = $args->getOption('output');

        if ($args->getOption('ackauthor') === '') {
            $this->ackAuthor = $args->getOption('ackauthor');
        }

        if ($args->getOption('ackcomment') === '') {
            $this->ackComment = $args->getOption('ackcomment');
        }

        if ($args->getOption('pushover-api-token') === '') {
            throw new \itnovum\openITCOCKPIT\ApiShell\Exceptions\MissingParameterExceptions(
                'Option --pushover-api-token is missing'
            );
        }
        $this->pushoverApiToken = $args->getOption('pushover-api-token');

        if ($args->getOption('pushover-user-token') === '') {
            throw new \itnovum\openITCOCKPIT\ApiShell\Exceptions\MissingParameterExceptions(
                'Option --pushover-user-token is missing'
            );
        }
        $this->pushoverUserToken = $args->getOption('pushover-user-token');
    }
}
