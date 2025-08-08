<?php
declare(strict_types=1);

namespace App\Command;

use App\Model\Table\HostsTable;
use App\Model\Table\ServicesTable;
use App\Model\Table\UsersTable;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\ORM\TableRegistry;
use itnovum\openITCOCKPIT\Core\Views\Host;
use itnovum\openITCOCKPIT\Core\Views\HoststatusIcon;
use itnovum\openITCOCKPIT\Core\Views\Service;
use itnovum\openITCOCKPIT\Core\Views\ServicestatusIcon;

/**
 * SendPushNotification command.
 *
 * Usage:
 * oitc send_push_notification --type Host --notificationtype PROBLEM --hostuuid c36b8048-93ce-4385-ac19-ab5c90574b77 --state 1 --output "This host is down right now" --ackauthor "" --ackcomment "" --user-id 1
 */
class SendPushNotificationCommand extends Command {

    private $type = 'host';

    private $hostUuid = '';

    private $serviceUuid = '';

    //0 = Up, 1 = Down, 2 = Unreachable
    //0 = Ok, 1 = Warning, 2 = Critical, 3 = Unknown
    private $state = null;

    private $output = '';

    //PROBLEM", "RECOVERY", "ACKNOWLEDGEMENT", "FLAPPINGSTART", "FLAPPINGSTOP",
    //"FLAPPINGDISABLED", "DOWNTIMESTART", "DOWNTIMEEND", or "DOWNTIMECANCELLED"
    private $notificationtype = '';

    private $ackAuthor = false;

    private $ackComment = false;

    /**
     * @var
     */
    private $userId;

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
            'type'             => ['short' => 't', 'help' => __d('oitc_console', 'Type of the notification host or service')],
            'notificationtype' => ['help' => __d('oitc_console', 'Notification type of monitoring engine => $NOTIFICATIONTYPE$ ')],
            'hostuuid'         => ['help' => __d('oitc_console', 'Host uuid you want to send a notification => $HOSTNAME$')],
            'serviceuuid'      => ['help' => __d('oitc_console', 'Service uuid you want to send a notification => $SERVICEDESC$')],
            'state'            => ['help' => __d('oitc_console', 'current host state => $HOSTSTATEID$/$SERVICESTATEID$')],
            'output'           => ['help' => __d('oitc_console', 'host output => $HOSTOUTPUT$/$SERVICEOUTPUT$')],
            'ackauthor'        => ['help' => __d('oitc_console', 'host acknowledgement author => $NOTIFICATIONAUTHOR$')],
            'ackcomment'       => ['help' => __d('oitc_console', 'host acknowledgement comment => $NOTIFICATIONCOMMENT$')],
            'user-id'          => ['help' => __d('oitc_console', 'openITCOCKPIT User Id')],
        ]);

        return $parser;
    }

    /**
     * @param Arguments $args
     * @param ConsoleIo $io
     * @return int|void|null
     * @throws \itnovum\openITCOCKPIT\ApiShell\Exceptions\MissingParameterExceptions
     */
    public function execute(Arguments $args, ConsoleIo $io) {
        $this->validateOptions($args);

        if ($this->userId === 0) {
            exit(0);
        }
        /** @var UsersTable $UsersTable */
        $UsersTable = TableRegistry::getTableLocator()->get('Users');

        if (!$UsersTable->existsById($this->userId)) {
            throw new \RuntimeException(sprintf('User with id "%s" could not be found!', $this->userId));
        }

        $Host = $this->getHost($this->hostUuid);

        if ($this->type === 'service') {
            $Service = $this->getService($this->serviceUuid);
            $this->sendServiceNotification($Host, $Service);
            exit(0);
        }


        $this->sendHostNotification($Host);
        exit(0);
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

    private function sendHostNotification(Host $Host) {
        $HoststatusIcon = new HoststatusIcon($this->state);

        if ($this->isAcknowledgement()) {
            $title = sprintf(
                'Acknowledgement for %s (%s)',
                $Host->getHostname(),
                $HoststatusIcon->getHumanState()
            );

            $message = sprintf('%s: %s', $this->ackAuthor, $this->ackComment);
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

        $this->push($title, $this->output, $HoststatusIcon->getNotificationIcon());
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

            $message = sprintf('%s: %s', $this->ackAuthor, $this->ackComment);
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

        $this->push($title, $this->output, $ServicestatusIcon->getNotificationIcon());
    }

    private function push($title, $message, $icon = null) {
        Configure::load('gearman');
        $config = Configure::read('gearman');

        $GearmanClient = new \GearmanClient();
        $GearmanClient->addServer($config['address'], $config['port']);

        $GearmanClient->doBackground('oitc_push_notifications', json_encode([
            'timestamp'   => time(),
            'userId'      => $this->userId,
            'title'       => $title,
            'message'     => $message,
            'type'        => $this->type,
            'hostUuid'    => $this->hostUuid,
            'serviceUuid' => $this->serviceUuid,
            'icon'        => $icon
        ]));
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

        if ($args->getOption('hostuuid') === '') {
            throw new \itnovum\openITCOCKPIT\ApiShell\Exceptions\MissingParameterExceptions(
                'Option --hostuuid is missing'
            );
        }
        $this->hostUuid = $args->getOption('hostuuid');

        if ($this->type === 'service') {
            if ($args->getOption('serviceuuid') === '') {
                throw new \itnovum\openITCOCKPIT\ApiShell\Exceptions\MissingParameterExceptions(
                    'Option --serviceuuid is missing'
                );
            }
            $this->serviceUuid = $args->getOption('serviceuuid');
        }

        if ($args->getOption('state') === '') {
            throw new \itnovum\openITCOCKPIT\ApiShell\Exceptions\MissingParameterExceptions(
                'Option --state is missing'
            );
        }
        $this->state = (int)$args->getOption('state');

        $this->output = $args->getOption('output');

        if ($args->getOption('ackauthor') !== '') {
            $this->ackAuthor = $args->getOption('ackauthor');
        }

        if ($args->getOption('ackcomment') !== '') {
            $this->ackComment = $args->getOption('ackcomment');
        }

        if ($args->getOption('user-id') === '') {
            throw new \itnovum\openITCOCKPIT\ApiShell\Exceptions\MissingParameterExceptions(
                'Option --user-id is missing'
            );
        }
        $this->userId = (int)$args->getOption('user-id');
    }
}
