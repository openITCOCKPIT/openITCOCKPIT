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

declare(strict_types=1);

namespace App\Command;

use App\Model\Entity\Agentconfig;
use App\Model\Table\AgentconfigsTable;
use App\Model\Table\HostsTable;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\ORM\TableRegistry;
use itnovum\openITCOCKPIT\Agent\AgentCertificateData;
use itnovum\openITCOCKPIT\Agent\AgentConfiguration;

/**
 * Class AgentCommand
 * @package App\Command
 */
class AgentCommand extends Command {

    const RC_UP = 0;
    const RC_DOWN = 1;
    const RC_UNREACHABLE = 2;

    const RC_OK = 0;
    const RC_WARNING = 1;
    const RC_CRITICAL = 2;
    const RC_UNKNOWN = 3;

    /**
     * @param ConsoleOptionParser $parser
     * @return ConsoleOptionParser
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser {
        $parser = parent::buildOptionParser($parser);

        // Options to migrate 1.x config for Agent version 3.x
        $parser->addOption('migrate', [
            'help'    => 'Migrate Agent 1.x database records for Agent 3.x',
            'boolean' => true
        ]);

        // Options to generate the server certificate if it is missing
        $parser->addOption('generate-server-ca', [
            'help'    => 'Generate a new server certificate (does nothing if /opt/openitc/agent/server_ca.pem exists)',
            'boolean' => true
        ]);

        // Freshness check options
        $parser->addOption('check', [
            'help'    => 'Determines the host state of an host running in Push Mode by evaluating the timestamp of the last received check  results.',
            'boolean' => true
        ]);
        $parser->addOption('host-check', [
            'short'   => 'H',
            'help'    => 'Determines if this check runs as host or service check. If set, only the critical value will be evaluated.',
            'boolean' => true
        ]);
        $parser->addOption('hostuuid', [
            'help' => 'The UUID of the host to evaluate'
        ]);
        $parser->addOption('warning', [
            'short'   => 'w',
            'help'    => 'Warning threshold in seconds.',
            'default' => '60'
        ]);
        $parser->addOption('critical', [
            'short'   => 'c',
            'help'    => 'Critical threshold in seconds.',
            'default' => '120'
        ]);


        return $parser;
    }

    /**
     * @param Arguments $args
     * @param ConsoleIo $io
     * @return int|void|null
     */
    public function execute(Arguments $args, ConsoleIo $io) {
        if ($args->getOption('migrate')) {
            $this->migrateAgentConfig();
        }

        if ($args->getOption('generate-server-ca')) {
            $this->generateServerCA($args, $io);
        }

        if ($args->getOption('check')) {
            $this->checkPushAgentFreshness($args, $io);
        }
    }

    private function migrateAgentConfig() {
        // Migrate Agent configuration from Agent 1.x for 3.x
        /** @var AgentconfigsTable $AgentconfigsTable */
        $AgentconfigsTable = TableRegistry::getTableLocator()->get('Agentconfigs');

        foreach ($AgentconfigsTable->getOldConfigsThatNeedsMigration() as $record) {
            /** @var Agentconfig $record */
            $AgentConfiguration = new AgentConfiguration();
            $config = $AgentConfiguration->unmarshal('');

            // Migrate old config from agent 1.x to 3.x
            $config['int']['bind_port'] = (int)$record->port;
            $config['bool']['use_http_basic_auth'] = $record->basic_auth;
            $config['string']['username'] = $record->username;
            $config['string']['password'] = $record->password;
            $config['bool']['use_proxy'] = $record->proxy;
            $config['bool']['enable_push_mode'] = false;
            $autosslSuccessful = false;
            if ($record->push_noticed) {
                $config['bool']['enable_push_mode'] = true;
            }

            if ($config['bool']['enable_push_mode'] === false) {
                $config['bool']['use_autossl'] = true;
                $autosslSuccessful = true;
            }

            $data = [
                'port'               => $config['int']['bind_port'],
                'basic_auth'         => $config['bool']['use_http_basic_auth'],
                'username'           => $config['bool']['use_http_basic_auth'] ? $config['string']['username'] : '',
                'password'           => $config['bool']['use_http_basic_auth'] ? $config['string']['password'] : '',
                'proxy'              => $config['bool']['use_proxy'],
                'insecure'           => true, // Validate TLS certificate in PULL mode
                'use_https'          => false, // Use own TLS certificate for the agent like Let's Encrypt
                'use_autossl'        => $config['bool']['use_autossl'], // New field with agent 3.x
                'use_push_mode'      => $config['bool']['enable_push_mode'], // New field with agent 3.x
                'autossl_successful' => $autosslSuccessful, // New field with agent 3.x
                'config'             => $AgentConfiguration->marshal(), // New field with agent 3.x
            ];

            $record = $AgentconfigsTable->patchEntity($record, $data);
            $AgentconfigsTable->save($record);
        }

        exit(0);
    }

    /**
     * @param Arguments $args
     * @param ConsoleIo $io
     */
    private function checkPushAgentFreshness(Arguments $args, ConsoleIo $io) {
        $uuid = $args->getOption('hostuuid');
        $warning = (int)$args->getOption('warning');
        $critical = (int)$args->getOption('critical');
        $isHostcheck = $args->getOption('host-check') === true;

        if (empty($uuid)) {
            $rc = self::RC_UNREACHABLE;
            if (!$isHostcheck) {
                $rc = self::RC_UNKNOWN;
            }

            $this->nagOutput('No host uuid given', $rc);
        }

        /** @var $HostsTable HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');

        $record = $HostsTable->getPushAgentRecordByHostUuidForFreshnessCheck($uuid, false);
        if (empty($record)) {
            $rc = self::RC_UNREACHABLE;
            if (!$isHostcheck) {
                $rc = self::RC_UNKNOWN;
            }

            $this->nagOutput('No push agent configured for given host.', $rc);
        }

        $lastUpdate = null;
        if (isset($record['_matchingData']['PushAgents']['last_update'])) {
            $lastUpdate = $record['_matchingData']['PushAgents']['last_update']->getTimestamp();
        }

        if ($lastUpdate === null) {
            if (empty($record)) {
                if ($isHostcheck) {
                    $this->nagOutput('No last update timestamp found in database', self::RC_UNREACHABLE);
                }
                $this->nagOutput('No last update timestamp found in database', self::RC_UNKNOWN);
            }
        }

        $now = time();
        $diff = $now - $lastUpdate;

        $rc = self::RC_UP;
        $output = sprintf('Up: Last check update received from Agent at: %s | age=%ss',
            date('c', $lastUpdate),
            $diff
        );
        if ($isHostcheck === true) {
            if ($diff >= $critical) {
                $output = sprintf('Down: Last received check update from Agent is older than %s seconds: %s | age=%ss',
                    $diff,
                    date('c', $lastUpdate),
                    $diff
                );
                $rc = self::RC_DOWN;
            }

            $this->nagOutput($output, $rc);
        }

        // Running as service check
        $rc = self::RC_OK;
        $output = sprintf('Ok: Last check update received from Agent at: %s | age=%ss;%s;%s',
            date('c', $lastUpdate),
            $diff,
            $warning,
            $critical
        );
        if ($diff >= $critical) {
            $output = sprintf('Critical: Last received check update from Agent is older than %s seconds: %s | age=%ss;%s;%s',
                $diff,
                date('c', $lastUpdate),
                $diff,
                $warning,
                $critical
            );
            $rc = self::RC_CRITICAL;
            $this->nagOutput($output, $rc);
        }

        $this->nagOutput($output, $rc);

        if ($diff >= $warning) {
            $output = sprintf('Warning: Last received check update from Agent is older than %s seconds: %s | age=%ss;%s;%s',
                $diff,
                date('c', $lastUpdate),
                $diff,
                $warning,
                $critical
            );
            $rc = self::RC_WARNING;
            $this->nagOutput($output, $rc);
        }

        // Ok
        $this->nagOutput($output, $rc);
    }

    /**
     * @param string $output
     * @param int $rc
     */
    private function nagOutput($output, $rc) {
        echo $output;
        echo "\n";
        exit($rc);

    }

    /**
     * @param Arguments $args
     * @param ConsoleIo $io
     */
    private function generateServerCA(Arguments $args, ConsoleIo $io) {
        $AgentCertificateData = new AgentCertificateData();
        if (!is_file($AgentCertificateData->getCaCertFile())) {
            $AgentCertificateData->generateServerCA();
        }
    }
}
