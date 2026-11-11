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

namespace itnovum\openITCOCKPIT\Vuln\Evaluation;

use itnovum\openITCOCKPIT\Vuln\Policy\EvaluationStatus;

/**
 * Result of the evaluation of one host before the policy classifies the findings.
 */
final class HostEvaluation {

    /**
     * @param EvaluationStatus $status
     * @param array<int, array> $findings Findings with the evaluation fields of CveHostFindingsTable, without classification
     * @param string[] $reasons CoverageReason::* codes
     * @param array<string, string[]> $coverage Values per CveHostCoverage::TYPE_*, without the reasons
     * @param int $installations Installed packages of the host
     * @param int $evaluatedInstallations Installed packages with a result of Vuls
     * @param bool $canResolve True if every package that has been checked before has a result of the current Vuls
     *                         database. Only then a finding that is not reported anymore is really gone.
     * @param string $fingerprint Changes whenever the evaluation could change, see LinuxFindingBuilder
     */
    public function __construct(
        public readonly EvaluationStatus $status,
        public readonly array            $findings,
        public readonly array            $reasons,
        public readonly array            $coverage,
        public readonly int              $installations,
        public readonly int              $evaluatedInstallations,
        public readonly bool             $canResolve,
        public readonly string           $fingerprint
    ) {
    }

    /**
     * Evaluation without any result, e.g. for an unsupported distribution
     *
     * @param string $reason CoverageReason::*
     * @param string $fingerprint
     * @param int $installations
     * @return self
     */
    public static function notEvaluable(string $reason, string $fingerprint, int $installations = 0): self {
        return new self(EvaluationStatus::NotEvaluable, [], [$reason], [], $installations, 0, false, $fingerprint);
    }
}
