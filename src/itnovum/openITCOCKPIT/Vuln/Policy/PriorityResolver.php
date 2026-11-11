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

namespace itnovum\openITCOCKPIT\Vuln\Policy;

/**
 * Determines the priority of a CVE from a Vuls report.
 *
 * Order: the rating of the vendor of the distribution, then the CVSS score of the other sources, else unknown.
 * Unknown never means harmless, see Rule::PriorityUnknown.
 */
class PriorityResolver {

    /** Vendor of each Vuls family. Families without a vendor rating per CVE (e.g. alma) use their errata */
    private const VENDOR_OF_FAMILY = [
        'ubuntu'                        => PrioritySource::Ubuntu,
        'debian'                        => PrioritySource::Debian,
        'raspbian'                      => PrioritySource::Debian,
        'redhat'                        => PrioritySource::RedHat,
        'centos'                        => PrioritySource::RedHat,
        'windows'                       => PrioritySource::Microsoft,
        'opensuse.leap'                 => PrioritySource::Suse,
        'opensuse'                      => PrioritySource::Suse,
        'suse.linux.enterprise.server'  => PrioritySource::Suse,
        'suse.linux.enterprise.desktop' => PrioritySource::Suse,
    ];

    /** Key of the vendor rating in cveContents of a Vuls report */
    private const CONTENT_OF_VENDOR = [
        'ubuntu'    => 'ubuntu_api',
        'debian'    => 'debian_security_tracker',
        'redhat'    => 'redhat_api',
        'microsoft' => 'microsoft',
        'suse'      => 'suse',
    ];

    /** CVSS sources in cveContents, in this order, if the vendor does not rate the CVE */
    private const CVSS_SOURCES = [
        'nvd'        => PrioritySource::Nvd,
        'vulncheck'  => PrioritySource::VulnCheck,
        'mitre'      => PrioritySource::Mitre,
        'euvd'       => PrioritySource::Euvd,
        'redhat_api' => PrioritySource::RedHatCvss,
    ];

    /**
     * @param array $cve Entry of scannedCves of a Vuls report
     * @param string $family Vuls family: ubuntu, debian, alma, windows, macos, cpe, ...
     * @return Priority
     */
    public function resolve(array $cve, string $family): Priority {
        $priority = $this->fromVendor($cve, $family);
        if ($priority !== null) {
            return $priority;
        }

        $priority = $this->fromCvss($cve);
        if ($priority !== null) {
            return $priority;
        }

        return Priority::unknown();
    }

    private function fromVendor(array $cve, string $family): ?Priority {
        $source = self::VENDOR_OF_FAMILY[$family] ?? null;

        $entries = [];
        if ($source !== null) {
            $entries = $cve['cveContents'][self::CONTENT_OF_VENDOR[$source->value]] ?? [];
        }

        // Errata rate a whole update, not one CVE. They are used if the vendor rates nothing per CVE.
        // Microsoft lists its severity in both places, so Windows always uses both.
        $advisories = $cve['distroAdvisories'] ?? [];
        if ($family === 'windows' || $entries === []) {
            $entries = array_merge($entries, $advisories);
        }

        if ($source === null) {
            $source = $advisories !== [] ? PrioritySource::Erratum : PrioritySource::Vendor;
        }

        return $this->highestOf($entries, $source);
    }

    /**
     * If several entries rate a CVE, the highest one counts.
     *
     * @param array $entries
     * @param PrioritySource $source
     * @return Priority|null null if no entry contains a known term
     */
    private function highestOf(array $entries, PrioritySource $source): ?Priority {
        $highest = null;

        foreach ($entries as $entry) {
            $label = $this->labelOf($entry);
            $severity = Severity::fromLabel($label);
            if ($severity === null) {
                continue;
            }

            if ($highest === null || $severity->isHigherThan($highest->severity)) {
                $highest = new Priority($severity, $source, null, $label);
            }
        }

        return $highest;
    }

    /**
     * Vendors use "severity", some only "cvss3Severity"
     */
    private function labelOf(array $entry): string {
        $label = trim((string)($entry['severity'] ?? ''));
        if ($label === '') {
            $label = trim((string)($entry['cvss3Severity'] ?? ''));
        }
        return $label;
    }

    private function fromCvss(array $cve): ?Priority {
        foreach (self::CVSS_SOURCES as $contentKey => $source) {
            foreach ($cve['cveContents'][$contentKey] ?? [] as $entry) {
                $priority = $this->fromCvssEntry($entry, $source);
                if ($priority !== null) {
                    return $priority;
                }
            }
        }
        return null;
    }

    /**
     * CVSS v4 is preferred over CVSS v3.
     */
    private function fromCvssEntry(array $entry, PrioritySource $source): ?Priority {
        $score = (float)($entry['cvss40Score'] ?? 0);
        if ($score <= 0) {
            $score = (float)($entry['cvss3Score'] ?? 0);
        }
        if ($score > 0) {
            return new Priority(Severity::fromCvssScore($score), $source, $score);
        }

        // Red Hat sometimes only provides a term without a score
        $label = trim((string)($entry['cvss3Severity'] ?? ''));
        $severity = Severity::fromLabel($label);
        if ($severity === null) {
            return null;
        }
        return new Priority($severity, $source, null, $label);
    }
}
