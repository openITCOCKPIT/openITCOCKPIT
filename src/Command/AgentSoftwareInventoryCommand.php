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

declare(strict_types=1);

namespace App\Command;

use App\itnovum\openITCOCKPIT\Agent\AgentSoftwareInventory;
use App\Model\Table\AgentconfigsTable;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\ORM\TableRegistry;
use itnovum\openITCOCKPIT\Agent\AgentHttpClient;
use itnovum\openITCOCKPIT\Core\Interfaces\CronjobInterface;

/**
 * AgentSoftwareInventory command.
 */
class AgentSoftwareInventoryCommand extends Command implements CronjobInterface {

    /**
     * Hook method for defining this command's option parser.
     *
     * @link https://book.cakephp.org/5/en/console-commands/commands.html#defining-arguments-and-options
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
     * @return int|null|void The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io) {
        $io->out('Fetch software inventory of openITCOCKPIT Agents (Pull Mode)...', 0);

        $this->fetchSoftwareInventory($io);

        $io->success('   Ok');
        $io->hr();
    }

    public function fetchSoftwareInventory(ConsoleIo $io): void {
        /** @var AgentconfigsTable $AgentconfigsTable */
        $AgentconfigsTable = TableRegistry::getTableLocator()->get('Agentconfigs');

        $AgentSoftwareInventory = new AgentSoftwareInventory();

        $io->out(''); // missing new line from a message above
        foreach ($AgentconfigsTable->getConfigForSoftwareInventory() as $agentconfig) {
            try {
                $io->out(sprintf('     Fetching software inventory from Host [%d]%s ...', $agentconfig->host->id, $agentconfig->host->name));
                $AgentHttpClient = new AgentHttpClient($agentconfig, $agentconfig->host->address);
                $result = $AgentHttpClient->getPackages();
                if (!empty($result)) {
                    $AgentSoftwareInventory->processAgentInventoryResponse($agentconfig->host->id, $result);
                }


            } catch (\Exception $e) {
                $io->err('     Error: ' . $e->getMessage());
            }
        }

        $AgentSoftwareInventory->cleanupUnusedPackages();
    }
}
