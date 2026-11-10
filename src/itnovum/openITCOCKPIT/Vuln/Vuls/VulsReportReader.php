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

namespace itnovum\openITCOCKPIT\Vuln\Vuls;

use itnovum\openITCOCKPIT\Vuln\Policy\PriorityResolver;

/**
 * Reads a report of Vuls: the master data of every CVE and which package state it affects.
 *
 * The result fits CvePackageStatesTable::saveCheckResults(). Every package state of the request gets an entry,
 * also without CVEs, so it counts as checked.
 */
final class VulsReportReader {

    /**
     * Ready to use exploits. Proof of concepts of other sources (GitHub, Trickest) are counted separately,
     * see itnovum\openITCOCKPIT\Vuln\Policy\Reason::PROOF_OF_CONCEPT
     */
    private const VERIFIED_EXPLOIT_SOURCES = ['OffensiveSecurity', 'Nuclei'];

    /** Order in which the description, the date and the link of a CVE are taken */
    private const DESCRIPTION_SOURCES = ['ubuntu_api', 'debian_security_tracker', 'alma', 'microsoft', 'nvd', 'vulncheck', 'mitre', 'redhat_api'];

    /** Sources whose CVSS score is used for the master data, in this order. Microsoft comes first for Windows */
    private const CVSS_SOURCES = ['microsoft', 'nvd', 'vulncheck', 'redhat_api'];

    private PriorityResolver $PriorityResolver;

    public function __construct(?PriorityResolver $PriorityResolver = null) {
        $this->PriorityResolver = $PriorityResolver ?? new PriorityResolver();
    }

    /**
     * @param array $report Report of Vuls for the request
     * @param VulsRequest $request
     * @return array{cves: array<string, array>, matches: array<int, array>, unassigned: string[]}
     *         cves: master data indexed by CVE id, matches: CVEs per package state id,
     *         unassigned: CVEs whose affected package is not part of the request
     */
    public function read(array $report, VulsRequest $request): array {
        $cves = [];
        $matches = [];
        $unassigned = [];

        foreach ($request->getStateIds() as $stateId) {
            $matches[$stateId] = [];
        }

        foreach ($report['scannedCves'] ?? [] as $cveId => $cve) {
            $cveId = (string)$cveId;
            $cves[$cveId] = $this->getMasterData($cve);

            // The severity depends on the distribution (e.g. Ubuntu priority), not on the package
            $priority = $this->PriorityResolver->resolve($cve, $request->family);

            $isAssigned = false;
            foreach ($cve['affectedPackages'] ?? [] as $affected) {
                $stateId = $request->stateIdsByPackage[(string)($affected['name'] ?? '')] ?? null;
                if ($stateId === null) {
                    continue;
                }

                $isAssigned = true;
                $matches[$stateId][] = [
                    'cve_id'          => $cveId,
                    'fixed_in'        => $this->stringOrNull($affected['fixedIn'] ?? null),
                    'fix_state'       => $this->stringOrNull($affected['fixState'] ?? null),
                    'not_fixed_yet'   => !empty($affected['notFixedYet']),
                    'severity'        => $priority->level(),
                    'severity_source' => $priority->source->value,
                ];
            }

            if (!$isAssigned) {
                $unassigned[] = $cveId;
            }
        }

        return [
            'cves'       => $cves,
            'matches'    => $matches,
            'unassigned' => $unassigned,
        ];
    }

    /**
     * Master data of a CVE as stored in the table cves
     *
     * @param array $cve Entry of scannedCves
     * @return array
     */
    public function getMasterData(array $cve): array {
        $summary = null;
        $published = null;
        $link = null;
        foreach (self::DESCRIPTION_SOURCES as $source) {
            foreach ($cve['cveContents'][$source] ?? [] as $entry) {
                $summary ??= $this->stringOrNull($entry['summary'] ?? null);
                $link ??= $this->stringOrNull($entry['sourceLink'] ?? null);

                // Vuls uses 0001-01-01 and 1000-01-01 for an unknown date
                $date = (string)($entry['published'] ?? '');
                if ($published === null && $date !== '' && !str_starts_with($date, '0001-') && !str_starts_with($date, '1000-')) {
                    $published = substr(str_replace('T', ' ', $date), 0, 19);
                }
            }
        }

        [$cvssScore, $cvssVector, $cvssSource] = $this->getCvss($cve);

        $isCisaKev = false;
        $cisaKevAdded = null;
        $isVulnCheckKev = false;
        foreach ($cve['kevs'] ?? [] as $kev) {
            if (($kev['type'] ?? '') === 'cisa') {
                $isCisaKev = true;
                $added = (string)($kev['date_added'] ?? '');
                if (preg_match('/^\d{4}-\d{2}-\d{2}/', $added) === 1) {
                    $cisaKevAdded = substr($added, 0, 10);
                }
            }
            if (($kev['type'] ?? '') === 'vulncheck') {
                $isVulnCheckKev = true;
            }
        }

        $verifiedExploits = 0;
        $proofOfConcepts = 0;
        foreach ($cve['exploits'] ?? [] as $exploit) {
            if (in_array($exploit['exploitType'] ?? '', self::VERIFIED_EXPLOIT_SOURCES, true)) {
                $verifiedExploits++;
            } else {
                $proofOfConcepts++;
            }
        }

        return [
            'summary'                => $summary === null ? null : mb_substr($summary, 0, 8000),
            'published'              => $published,
            'cvss_score'             => $cvssScore,
            'cvss_vector'            => $cvssVector,
            'cvss_source'            => $cvssSource,
            'kev_cisa'               => $isCisaKev,
            'kev_cisa_added'         => $cisaKevAdded,
            'kev_vulncheck'          => $isVulnCheckKev,
            'metasploit_count'       => count($cve['metasploits'] ?? []),
            'verified_exploit_count' => $verifiedExploits,
            'poc_exploit_count'      => $proofOfConcepts,
            'source_link'            => $link,
        ];
    }

    /**
     * The first CVSS score of Microsoft, NVD, VulnCheck or Red Hat. CVSS v3 is used before v4,
     * like in the prototype, so the scores stay comparable.
     *
     * @param array $cve
     * @return array{0: float|null, 1: string|null, 2: string|null} Score, vector, source
     */
    private function getCvss(array $cve): array {
        foreach (self::CVSS_SOURCES as $source) {
            foreach ($cve['cveContents'][$source] ?? [] as $entry) {
                $score = (float)($entry['cvss3Score'] ?? 0);
                $vector = (string)($entry['cvss3Vector'] ?? '');
                if ($score <= 0) {
                    $score = (float)($entry['cvss40Score'] ?? 0);
                    $vector = (string)($entry['cvss40Vector'] ?? '');
                }

                if ($score > 0) {
                    return [$score, $vector === '' ? null : $vector, $source];
                }
            }
        }

        return [null, null, null];
    }

    /**
     * @param mixed $value
     * @return string|null
     */
    private function stringOrNull(mixed $value): ?string {
        $value = trim((string)$value);
        return $value === '' ? null : $value;
    }
}
