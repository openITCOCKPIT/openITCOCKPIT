<?php
declare(strict_types=1);

namespace App\Command;

use Cake\Console\Arguments;
use Cake\Command\Command;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Log\Log;
use itnovum\openITCOCKPIT\Ratchet\Overwrites\HttpServerSize;
use itnovum\openITCOCKPIT\WebSockets\PushNotificationsMessageInterface;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use React\Socket\SocketServer;

/**
 * PushNotifications command.
 */
class PushNotificationsCommand extends Command {
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

        return $parser;
    }

    /**
     * Implement this method with your command's logic.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return null|void|int The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io) {
        $io->out('Starting push notifications service');
        $io->info('Exit with [STRG] + [C]');
        $this->fireUpWebSocketServer($io);
    }

    private function fireUpWebSocketServer(ConsoleIo $io) {
        $MessageInterface = new PushNotificationsMessageInterface($io);
        $loop = \React\EventLoop\Loop::get();
        $loop->addPeriodicTimer(0.01, [$MessageInterface, 'eventLoop']);
        $Server = new IoServer(
            new HttpServerSize(
                new WsServer($MessageInterface)
            ),
            new SocketServer(sprintf('%s:%s', '0.0.0.0', 8083), [], $loop),
            $loop
        );
        try {
            $Server->run();
        } catch (\Exception $e) {
            Log::error('PushNotificationCommand: ' . $e->getMessage());
        }
    }
}
