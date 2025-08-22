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
use App\Model\Table\CommandsTable;
use App\Model\Table\ContainersTable;
use App\Model\Table\HostgroupsTable;
use App\Model\Table\HostsTable;
use App\Model\Table\HosttemplatesTable;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\TableRegistry;
use itnovum\openITCOCKPIT\Cache\ObjectsCache;
use itnovum\openITCOCKPIT\Core\Comparison\HostComparisonForSave;
use itnovum\openITCOCKPIT\Core\Merger\HostMergerForView;

/**
 * HostgroupContainerPermissions command.
 * @deprecated since ITC-2819
 * See https://github.com/openITCOCKPIT/openITCOCKPIT/pull/1377/files?diff=split&w=0 how to restore <= 4.4.1 behavior
 */
class HostgroupContainerPermissionsCommand extends Command {
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
        $io->info('Execution of this command is disabled! ITC-2819');
        exit(0);

        // Old ITC-2800 code below

        $lockfile = '/opt/openitc/var/hostgroup_container_permissions.lock';

        if (file_exists($lockfile)) {
            $io->success('Update has already been done');
            exit(0);
        }


        /** @var $HostsTable HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
        /** @var $HosttemplatesTable HosttemplatesTable */
        $HosttemplatesTable = TableRegistry::getTableLocator()->get('Hosttemplates');
        /** @var $ContainersTable ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');
        /** @var $HostgroupsTable HostgroupsTable */
        $HostgroupsTable = TableRegistry::getTableLocator()->get('Hostgroups');
        /** @var  ChangelogsTable $ChangelogsTable */
        $ChangelogsTable = TableRegistry::getTableLocator()->get('Changelogs');

        $hosttemplates = $HosttemplatesTable->find()
            ->select([
                'Hosttemplates.id',
                'Hosttemplates.name'
            ])
            ->all();

        foreach ($hosttemplates as $hosttemplate) {
            $hosttemplate = $HosttemplatesTable->getHosttemplateForEdit($hosttemplate->id);
            if (empty($hosttemplate['Hosttemplate']['hostgroups']['_ids'])) {
                // Hosttemplate has no own hostgroups
                continue;
            }
            $hosttemplateForChangeLog = $hosttemplate;


            $io->out(__('Perform hostgroup container permission update on hosttemplate: "{0}". ContainerId: {1}', $hosttemplate['Hosttemplate']['name'], $hosttemplate['Hosttemplate']['container_id']));

            $currentHostgroupIds = $hosttemplate['Hosttemplate']['hostgroups']['_ids'];

            $visibleContainerIds = $ContainersTable->resolveContainerIdForGroupPermissions($hosttemplate['Hosttemplate']['container_id']);
            $visibleHostgroups = $HostgroupsTable->getHostgroupsByContainerId($visibleContainerIds, 'list', 'id');

            //remove disallowed host groups from hosttemplate configuration
            if (!empty($hosttemplate['Hosttemplate']['hostgroups']['_ids'])) {
                $hosttemplate['Hosttemplate']['hostgroups']['_ids'] = array_intersect(
                    array_keys($visibleHostgroups),
                    $hosttemplate['Hosttemplate']['hostgroups']['_ids']
                );
            }

            $io->error('Old hostgroups: ' . json_encode($currentHostgroupIds));
            $io->success('New hostgroups: ' . json_encode(array_values($hosttemplate['Hosttemplate']['hostgroups']['_ids'])));

            $hosttemplateEntity = $HosttemplatesTable->get($hosttemplate['Hosttemplate']['id']);
            $hosttemplateEntity->setAccess('uuid', false);
            $hosttemplateEntity = $HosttemplatesTable->patchEntity($hosttemplateEntity, $hosttemplate['Hosttemplate']);
            $HosttemplatesTable->save($hosttemplateEntity);
            if (!$hosttemplateEntity->hasErrors()) {
                $changelog_data = $ChangelogsTable->parseDataForChangelog(
                    'edit',
                    'hosttemplates',
                    $hosttemplateEntity->id,
                    OBJECT_HOSTTEMPLATE,
                    $hosttemplateEntity->get('container_id'),
                    null,
                    $hosttemplateEntity->name,
                    array_merge($HosttemplatesTable->resolveDataForChangelog($hosttemplate), $hosttemplate),
                    array_merge($HosttemplatesTable->resolveDataForChangelog($hosttemplateForChangeLog), $hosttemplateForChangeLog)
                );
                if ($changelog_data) {
                    /** @var Changelog $changelogEntry */
                    $changelogEntry = $ChangelogsTable->newEntity($changelog_data);
                    $ChangelogsTable->save($changelogEntry);
                }
            }

            $io->out('');
        }

        $hosts = $HostsTable->find()
            ->select([
                'Hosts.id',
                'Hosts.name'
            ])
            ->all();

        $ObjectsCacheChangelog = new ObjectsCache();
        foreach ($hosts as $host) {
            $host = $HostsTable->getHostForEdit($host->id);
            if (empty($host['Host']['hostgroups']['_ids'])) {
                // Host has no own hostgroups
                continue;
            }

            $hosttemplate = $HosttemplatesTable->getHosttemplateForDiff($host['Host']['hosttemplate_id']);
            $HostMergerForView = new HostMergerForView($host, $hosttemplate);
            $mergedHost = $HostMergerForView->getDataForView();
            $hostForChangelog = $mergedHost;

            $io->out(__('Perform hostgroup container permission update on host: "{0}". ContainerId: {1}', $host['Host']['name'], $host['Host']['container_id']));

            $currentHostgroupIds = $mergedHost['Host']['hostgroups']['_ids'];

            $visibleContainerIds = $ContainersTable->resolveContainerIdForGroupPermissions($host['Host']['container_id']);
            $visibleHostgroups = $HostgroupsTable->getHostgroupsByContainerId($visibleContainerIds, 'list', 'id');

            //remove disallowed host groups from host configuration and temporarily from host template if container rights are not correct
            if (!empty($mergedHost['Host']['hostgroups']['_ids'])) {
                $mergedHost['Host']['hostgroups']['_ids'] = array_intersect(
                    array_keys($visibleHostgroups),
                    $mergedHost['Host']['hostgroups']['_ids']
                );
            }
            if (!empty($hosttemplate['Hosttemplate']['hostgroups']['_ids'])) {
                $hosttemplate['Hosttemplate']['hostgroups']['_ids'] = array_intersect(
                    array_keys($visibleHostgroups),
                    $hosttemplate['Hosttemplate']['hostgroups']['_ids']
                );
            }

            $HostComparisonForSave = new HostComparisonForSave($mergedHost, $hosttemplate);

            $dataForSave = $HostComparisonForSave->getDataForSaveForAllFields();
            //Add required fields for validation
            $dataForSave['hosttemplate_flap_detection_enabled'] = $hosttemplate['Hosttemplate']['flap_detection_enabled'];
            $dataForSave['hosttemplate_flap_detection_on_up'] = $hosttemplate['Hosttemplate']['flap_detection_on_up'];
            $dataForSave['hosttemplate_flap_detection_on_down'] = $hosttemplate['Hosttemplate']['flap_detection_on_down'];
            $dataForSave['hosttemplate_flap_detection_on_unreachable'] = $hosttemplate['Hosttemplate']['flap_detection_on_unreachable'];

            $io->error('Old hostgroups: ' . json_encode($currentHostgroupIds));
            $io->success('New hostgroups: ' . json_encode(array_values($dataForSave['hostgroups']['_ids'])));
            $io->out('');

            $hostEntity = $HostsTable->get($mergedHost['Host']['id']);
            $hostEntity = $HostsTable->patchEntity($hostEntity, $dataForSave);
            $HostsTable->save($hostEntity);
            if (!$hostEntity->hasErrors()) {
                $changelog_data = $ChangelogsTable->parseDataForChangelog(
                    'edit',
                    'hosts',
                    $hostEntity->get('id'),
                    OBJECT_HOST,
                    $hostEntity->get('container_id'),
                    null,
                    $hostEntity->get('name'),
                    array_merge($HostsTable->resolveDataForChangelog($mergedHost, $ObjectsCacheChangelog), $mergedHost),
                    array_merge($HostsTable->resolveDataForChangelog($hostForChangelog, $ObjectsCacheChangelog), $hostForChangelog)
                );
                if ($changelog_data) {
                    /** @var Changelog $changelogEntry */
                    $changelogEntry = $ChangelogsTable->newEntity($changelog_data);
                    $ChangelogsTable->save($changelogEntry);
                }

            }

        }

        file_put_contents($lockfile, 'Hostgroup Container permission fix was running at: ' . date('Y-m-d H:i:s'));
    }
}
