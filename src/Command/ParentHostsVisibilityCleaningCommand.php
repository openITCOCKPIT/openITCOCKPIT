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

use App\Model\Entity\Changelog;
use App\Model\Table\ChangelogsTable;
use App\Model\Table\HostsTable;
use App\Model\Table\HosttemplatesTable;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use itnovum\openITCOCKPIT\Core\Comparison\HostComparisonForSave;
use itnovum\openITCOCKPIT\Core\HostConditions;
use itnovum\openITCOCKPIT\Core\Merger\HostMergerForView;

/**
 * ParentHostsVisibilityCleaning command.
 */
class ParentHostsVisibilityCleaningCommand extends Command {
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
        $lockfile = '/opt/openitc/var/parent_hosts_cleaning.lock';

        if (file_exists($lockfile)) {
            $io->success('Update has already been done');
            exit(0);
        }


        /** @var HostsTable $HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
        /** @var HosttemplatesTable $HosttemplatesTable */
        $HosttemplatesTable = TableRegistry::getTableLocator()->get('Hosttemplates');
        /** @var  ChangelogsTable $ChangelogsTable */
        $ChangelogsTable = TableRegistry::getTableLocator()->get('Changelogs');


        $hosts = $HostsTable->getHostsAsList();
        $HostConditions = new HostConditions();
        foreach ($hosts as $hostId => $hostName) {
            $host = $HostsTable->getHostForEdit($hostId);
            if ($host['Host']['satellite_id'] === 0 || empty($host['Host']['parenthosts']['_ids'])) {
                continue;
            }
            $HostConditions->setHostIds($host['Host']['parenthosts']['_ids']);
            $parentHosts = $HostsTable->getHostsByHostConditions($HostConditions);
            $parenHostFilteredIds = Hash::extract($parentHosts, '{n}.Host[satellite_id=' . $host['Host']['satellite_id'] . '].id');

            $parentHostsDifference = array_diff($host['Host']['parenthosts']['_ids'], $parenHostFilteredIds);
            if (!empty($parentHostsDifference)) {
                $io->info('Hostname: ' . $host['Host']['name']);

                $hosttemplate = $HosttemplatesTable->getHosttemplateForDiff($host['Host']['hosttemplate_id']);
                //update host
                $HostMergerForView = new HostMergerForView($host, $hosttemplate);
                $mergedHost = $HostMergerForView->getDataForView();
                $hostForChangelog = $mergedHost;

                $mergedHost['Host']['parenthosts']['_ids'] = $parenHostFilteredIds;
                $HostComparisonForSave = new HostComparisonForSave($mergedHost, $hosttemplate);
                $dataForSave = $HostComparisonForSave->getDataForSaveForAllFields();
                //Add required fields for validation
                $dataForSave['hosttemplate_flap_detection_enabled'] = $hosttemplate['Hosttemplate']['flap_detection_enabled'];
                $dataForSave['hosttemplate_flap_detection_on_up'] = $hosttemplate['Hosttemplate']['flap_detection_on_up'];
                $dataForSave['hosttemplate_flap_detection_on_down'] = $hosttemplate['Hosttemplate']['flap_detection_on_down'];
                $dataForSave['hosttemplate_flap_detection_on_unreachable'] = $hosttemplate['Hosttemplate']['flap_detection_on_unreachable'];


                $hostEntity = $HostsTable->get($host['Host']['id']);
                $hostEntity = $HostsTable->patchEntity($hostEntity, $dataForSave);

                $HostsTable->save($hostEntity);
                //No errors
                if (!$hostEntity->hasErrors()) {
                    $io->error('Old parent hosts: ' . json_encode($host['Host']['parenthosts']['_ids']));
                    $io->success('New parent hosts: ' . json_encode($parenHostFilteredIds));

                    $changelog_data = $ChangelogsTable->parseDataForChangelog(
                        'edit',
                        'hosts',
                        $hostEntity->get('id'),
                        OBJECT_HOST,
                        $hostEntity->get('container_id'),
                        0,
                        $hostEntity->get('name'),
                        array_merge($HostsTable->resolveDataForChangelog($mergedHost), $mergedHost),
                        array_merge($HostsTable->resolveDataForChangelog($hostForChangelog), $hostForChangelog)
                    );
                    if ($changelog_data) {
                        /** @var Changelog $changelogEntry */
                        $changelogEntry = $ChangelogsTable->newEntity($changelog_data);
                        $ChangelogsTable->save($changelogEntry);
                    }
                }
            }
            $io->out('');

        }
        file_put_contents($lockfile, 'Parent hosts  fix was running at: ' . date('Y-m-d H:i:s'));
    }
}
