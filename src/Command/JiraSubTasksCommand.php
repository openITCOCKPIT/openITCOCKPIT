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

declare(strict_types=1);

namespace App\Command;

use Acl\Model\Table\AcosTable;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\ORM\TableRegistry;
use GuzzleHttp\Client;

/**
 * JiraSubTasks command.
 *
 * This command creates a new Jira SubTask for each existing Controller
 * A script like this was used for the CakePHP 4 migration and for the Angular rewirte
 *
 * Usage:
 * export JIRA_API_TOKEN=your_token_here
 * oitc jira_sub_tasks
 *
 */
class JiraSubTasksCommand extends Command {
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

        $jira_api_token = getenv('JIRA_API_TOKEN');
        if (empty($jira_api_token)) {
            die('JIRA_API_TOKEN not set in env. Execute export JIRA_API_TOKEN=your_token_here');
        }

        /** @var AcosTable $AcosTable */
        $AcosTable = TableRegistry::getTableLocator()->get('Acl.Acos');

        $acos = $AcosTable->find('threaded')
            ->disableHydration()
            ->all();

        foreach ($acos as $aco) {
            foreach ($aco['children'] as $child) {
                $module = 'Core';
                $controller = $child['alias'];
                if (substr($controller, -6) === 'Module') {
                    // This is a Module.
                    $module = $controller;
                    foreach ($child['children'] as $moduleChild) {
                        $moduleController = $moduleChild['alias'];
                        $this->createIssue($module, $moduleController, $jira_api_token);
                    }
                } else {
                    // Core controller
                    $this->createIssue($module, $controller, $jira_api_token);
                }
            }
        }
    }

    private function createIssue(string $module, string $controller, string $jira_api_token) {
        printf('%s: %s%s', $module, $controller, PHP_EOL);

        // Jira docs: https://developer.atlassian.com/cloud/jira/platform/rest/v2/api-group-issues/#api-rest-api-2-issue-post
        $data = [
            'fields' => [
                'project'   => [
                    'id' => '10006'
                ],
                'issuetype' => [
                    'id' => '10008'
                ],
                "parent"    => [
                    "id" => "13321"
                ],
                'summary'   => sprintf('%s: %s', $module, $controller),
                //'description' => '',
            ]
        ];

        $client = new Client(['base_uri' => 'https://openitcockpit.atlassian.net']);
        $client->request('POST', '/rest/api/2/issue', [
            'header' => [
                'Content-Type' => 'application/json',
            ],
            'auth'   => [
                'daniel.ziegler@it-novum.com', $jira_api_token
            ],
            'json'   => $data
        ]);
    }
}
