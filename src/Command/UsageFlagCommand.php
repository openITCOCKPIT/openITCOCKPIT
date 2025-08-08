<?php
declare(strict_types=1);

namespace App\Command;

use App\Model\Table\HostsTable;
use App\Model\Table\ServicesTable;
use AutoreportModule\Model\Table\AutoreportsHostsMembershipsTable;
use AutoreportModule\Model\Table\AutoreportsServicesMembershipsTable;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Plugin;
use Cake\ORM\TableRegistry;
use EventcorrelationModule\Model\Table\EventcorrelationsTable;

/**
 * UsageFlag command.
 */
class UsageFlagCommand extends Command {
    /**
     * Hook method for defining this command's option parser.
     *
     * @see https://book.cakephp.org/4/en/console-commands/commands.html#defining-arguments-and-options
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
        $io->out('Checking usage flag...');

        // Reset all usage_flag records to 0
        $this->truncateUsageFlags();

        if (Plugin::isLoaded('AutoreportModule')) {
            $this->setUsageFlagForAutoreports($io);
        }

        if (Plugin::isLoaded('EventcorrelationModule')) {
            $this->setUsageFlagForEventcorrelations($io);
        }
    }

    private function truncateUsageFlags() {
        /** @var HostsTable $HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
        /** @var ServicesTable $ServicesTable */
        $ServicesTable = TableRegistry::getTableLocator()->get('Services');

        $HostsTable->updateAll(['usage_flag' => 0], ['usage_flag >' => 0]);
        $ServicesTable->updateAll(['usage_flag' => 0], ['usage_flag >' => 0]);
    }

    private function setUsageFlagForAutoreports(ConsoleIo $io) {
        $io->out('Check usage_flag for AutoreportModule', 0);

        /** @var HostsTable $HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
        /** @var ServicesTable $ServicesTable */
        $ServicesTable = TableRegistry::getTableLocator()->get('Services');

        /** @var AutoreportsHostsMembershipsTable $AutoreportsHostsMembershipsTable */
        $AutoreportsHostsMembershipsTable = TableRegistry::getTableLocator()->get('AutoreportModule.AutoreportsHostsMemberships');

        /** @var AutoreportsServicesMembershipsTable $AutoreportsServicesMembershipsTable */
        $AutoreportsServicesMembershipsTable = TableRegistry::getTableLocator()->get('AutoreportModule.AutoreportsServicesMemberships');

        $hosts = $AutoreportsHostsMembershipsTable->find()
            ->select([
                'id',
                'host_id'
            ])
            ->groupBy([
                'host_id'
            ])
            ->disableHydration()
            ->all();


        foreach ($hosts as $host) {
            try {
                $HostsTable->setUsageFlagById($host['host_id'], AUTOREPORT_MODULE);
            } catch (\Exception $e) {
                //Host not found - ignore
            }
        }

        $services = $AutoreportsServicesMembershipsTable->find()
            ->select([
                'id',
                'service_id'
            ])
            ->groupBy([
                'service_id'
            ])
            ->disableHydration()
            ->all();

        foreach ($services as $service) {
            try {
                $ServicesTable->setUsageFlagById($service['service_id'], AUTOREPORT_MODULE);
            } catch (\Exception $e) {
                //Service not found - ignore
            }
        }

        $io->success('    Ok');
    }

    private function setUsageFlagForEventcorrelations(ConsoleIo $io) {
        $io->out('Check usage_flag for EventcorrelationModule', 0);

        /** @var HostsTable $HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
        /** @var ServicesTable $ServicesTable */
        $ServicesTable = TableRegistry::getTableLocator()->get('Services');

        /** @var EventcorrelationsTable $EventcorrelationsTable */
        $EventcorrelationsTable = TableRegistry::getTableLocator()->get('EventcorrelationModule.Eventcorrelations');

        $hosts = $EventcorrelationsTable->find()
            ->select([
                'id',
                'host_id'
            ])
            ->groupBy([
                'host_id'
            ])
            ->disableHydration()
            ->all();


        foreach ($hosts as $host) {
            try {
                $HostsTable->setUsageFlagById($host['host_id'], EVENTCORRELATION_MODULE);
            } catch (\Exception $e) {
                //Host not found - ignore
            }
        }

        $services = $EventcorrelationsTable->find()
            ->select([
                'id',
                'service_id'
            ])
            ->groupBy([
                'service_id'
            ])
            ->disableHydration()
            ->all();


        foreach ($services as $service) {
            try {
                $ServicesTable->setUsageFlagById($service['service_id'], EVENTCORRELATION_MODULE);
            } catch (\Exception $e) {
                //Service not found - ignore
            }
        }

        $io->success('    Ok');
    }
}
