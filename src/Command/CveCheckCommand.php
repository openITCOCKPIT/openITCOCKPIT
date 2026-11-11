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

use App\Model\Table\CveFeedsTable;
use App\Model\Table\CvePackageStatesTable;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\ORM\TableRegistry;
use itnovum\openITCOCKPIT\Vuln\Linux\LinuxDistribution;
use itnovum\openITCOCKPIT\Vuln\Linux\LinuxRequestBuilder;
use itnovum\openITCOCKPIT\Vuln\Vuls\ReleaseNotInFeedException;
use itnovum\openITCOCKPIT\Vuln\Vuls\VulsClient;
use itnovum\openITCOCKPIT\Vuln\Vuls\VulsException;
use itnovum\openITCOCKPIT\Vuln\Vuls\VulsFeed;
use itnovum\openITCOCKPIT\Vuln\Vuls\VulsReportReader;

/**
 * Checks the package states of all Linux hosts with Vuls.
 *
 * 1. Determine the current Vuls database (identifier and date)
 * 2. Create the package states of the inventory and link the installations to them
 * 3. Send every package state without a result for the current database to Vuls, one group after the other
 * 4. Store the CVEs per package state
 *
 * Package states that were checked with the current database are skipped, so a second run without a new
 * database or new packages sends nothing to Vuls.
 *
 * The Vuls server has to run already, openITCOCKPIT never starts it (systemd or docker compose do).
 * A release the Vuls database does not know yet (e.g. a brand new release) is no error: the hosts are not
 * evaluable until a newer database knows the release.
 *
 * Usage:
 * oitc cve_check
 */
class CveCheckCommand extends Command {

    /** Result of checkGroup(): all package states of the group got a result */
    private const GROUP_CHECKED = 'checked';

    /** Result of checkGroup(): the Vuls database does not know the release, the group is not evaluable */
    private const GROUP_NOT_IN_FEED = 'not_in_feed';

    /** Result of checkGroup(): at least one package state could not be checked */
    private const GROUP_FAILED = 'failed';

    /**
     * @param ConsoleOptionParser $parser
     * @return ConsoleOptionParser
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser {
        $parser = parent::buildOptionParser($parser);
        $parser->setDescription('Checks the installed Linux packages of all hosts for CVEs with Vuls.');

        return $parser;
    }

    /**
     * @param Arguments $args
     * @param ConsoleIo $io
     * @return int
     */
    public function execute(Arguments $args, ConsoleIo $io): int {
        $VulsClient = new VulsClient();
        if (!$VulsClient->isHealthy()) {
            $io->error(sprintf('Vuls is not reachable at %s', $VulsClient->getAddress()));
            return static::CODE_ERROR;
        }

        /** @var CveFeedsTable $CveFeedsTable */
        $CveFeedsTable = TableRegistry::getTableLocator()->get('CveFeeds');
        /** @var CvePackageStatesTable $CvePackageStatesTable */
        $CvePackageStatesTable = TableRegistry::getTableLocator()->get('CvePackageStates');

        // 1. Vuls database
        $io->out('Determine the Vuls database... ', 0);
        try {
            $VulsFeed = new VulsFeed();
            $feedDigest = $VulsFeed->getDigest();
            $CveFeedsTable->setCurrentFeed($feedDigest, $VulsFeed->getFeedDate());
        } catch (VulsException $e) {
            $io->error($e->getMessage());
            return static::CODE_ERROR;
        }
        $io->out(sprintf('%s from %s', $feedDigest, $VulsFeed->getFeedDate()->format('Y-m-d H:i')));

        // 2. Package states of the inventory
        $io->out('Sync package states with the inventory... ', 0);
        $sync = $CvePackageStatesTable->syncFromInventory();
        $io->out(sprintf(
            '%d hosts, %d package states (%d new), %d installations can not be checked',
            $sync['hosts'],
            $sync['states'],
            $sync['new_states'],
            $sync['skipped_installations']
        ));

        // 3. and 4. Check the pending package states group by group
        $groups = $CvePackageStatesTable->getPendingGroups($feedDigest);
        if (empty($groups)) {
            $io->success('All package states are checked with the current Vuls database.');
            return static::CODE_SUCCESS;
        }

        $results = [self::GROUP_CHECKED => 0, self::GROUP_NOT_IN_FEED => 0, self::GROUP_FAILED => 0];
        foreach ($groups as $group) {
            $results[$this->checkGroup($group, $feedDigest, $VulsClient, $CvePackageStatesTable, $io)]++;
        }

        if ($results[self::GROUP_NOT_IN_FEED] > 0) {
            $io->out(sprintf('<warning>%d groups are not evaluable: the Vuls database does not know their release yet.</warning>', $results[self::GROUP_NOT_IN_FEED]));
        }

        if ($results[self::GROUP_FAILED] > 0) {
            $io->error(sprintf('%d of %d groups could not be checked completely.', $results[self::GROUP_FAILED], count($groups)));
            return static::CODE_ERROR;
        }

        $io->success(sprintf('%d groups checked.', $results[self::GROUP_CHECKED]));
        return static::CODE_SUCCESS;
    }

    /**
     * Sends the pending package states of one group to Vuls and stores the result.
     *
     * @param array $group Row of CvePackageStatesTable::getPendingGroups()
     * @param string $feedDigest
     * @param VulsClient $VulsClient
     * @param CvePackageStatesTable $CvePackageStatesTable
     * @param ConsoleIo $io
     * @return string One of the GROUP_* constants
     */
    private function checkGroup(array $group, string $feedDigest, VulsClient $VulsClient, CvePackageStatesTable $CvePackageStatesTable, ConsoleIo $io): string {
        $label = sprintf('%s %s', $group['os_name'], $group['os_version']);
        if ($group['is_kernel']) {
            $label .= ' kernel ' . ($group['kernel_release'] ?? 'unknown');
        }
        $io->out(sprintf('  %s: %d package states... ', $label, $group['pending']), 0);
        $start = microtime(true);

        $states = $CvePackageStatesTable->getStatesOfGroup(
            $group['os_name'],
            $group['os_version'],
            $group['is_kernel'],
            $group['kernel_release'],
            $feedDigest
        );
        $stateIds = array_map(fn($state) => $state->id, $states);

        $distribution = LinuxDistribution::fromInventory($group['os_name'], $group['os_version']);
        if ($distribution === null) {
            $CvePackageStatesTable->saveCheckError($stateIds, 'Vuls does not support this distribution');
            $io->out('<warning>distribution not supported by Vuls</warning>');
            return self::GROUP_FAILED;
        }

        $releasePackages = [];
        if ($distribution->getReleasePackageName() !== null) {
            $releasePackages = $CvePackageStatesTable->getStatesByNames($group['os_name'], $group['os_version'], [$distribution->getReleasePackageName()]);
        }

        $plan = (new LinuxRequestBuilder())->build($distribution, $group['is_kernel'] ? $group['kernel_release'] : null, $states, $releasePackages);

        $isComplete = true;
        foreach ($plan->getSkipped() as $stateId => $reason) {
            $CvePackageStatesTable->saveCheckError([$stateId], $reason);
            $isComplete = false;
        }

        $Reader = new VulsReportReader();
        $cveCount = 0;
        $unassigned = 0;
        foreach ($plan->getRequests() as $request) {
            try {
                $report = $VulsClient->scan($request->toArray());
            } catch (ReleaseNotInFeedException $e) {
                // No request of this group can succeed
                $CvePackageStatesTable->saveCheckError($stateIds, $e->getMessage());
                $io->out(sprintf('<warning>%s</warning>', $e->getMessage()));
                return self::GROUP_NOT_IN_FEED;
            } catch (VulsException $e) {
                $CvePackageStatesTable->saveCheckError($request->getStateIds(), mb_substr($e->getMessage(), 0, 1000));
                $io->err(sprintf('    %s: %s', $request->serverName, $e->getMessage()));
                $isComplete = false;
                continue;
            }

            $result = $Reader->read($report, $request);
            $CvePackageStatesTable->saveCheckResults($feedDigest, $result['cves'], $result['matches']);
            $cveCount += count($result['cves']);
            $unassigned += count($result['unassigned']);
        }

        $io->out(sprintf(
            '%d requests, %d CVEs%s, %.1f s',
            count($plan->getRequests()),
            $cveCount,
            $unassigned > 0 ? sprintf(' (%d without matching package)', $unassigned) : '',
            microtime(true) - $start
        ));

        if (!empty($plan->getSkipped())) {
            $io->out(sprintf('    <warning>%d package states skipped: %s</warning>', count($plan->getSkipped()), implode(', ', array_unique($plan->getSkipped()))));
        }

        return $isComplete ? self::GROUP_CHECKED : self::GROUP_FAILED;
    }
}
