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

namespace App\Test\TestCase\Vuln\Policy;

use itnovum\openITCOCKPIT\Vuln\Policy\Action;
use itnovum\openITCOCKPIT\Vuln\Policy\ActionAdvisor;
use itnovum\openITCOCKPIT\Vuln\Policy\Classification;
use itnovum\openITCOCKPIT\Vuln\Policy\FindingClassifier;
use itnovum\openITCOCKPIT\Vuln\Policy\FindingFacts;
use itnovum\openITCOCKPIT\Vuln\Policy\Rule;
use PHPUnit\Framework\TestCase;

class ActionAdvisorTest extends TestCase {

    use FindingFactory;

    private function action(FindingFacts $finding, ?Classification $classification = null): Action {
        $classification ??= (new FindingClassifier())->classify($finding);
        return (new ActionAdvisor())->forFinding($finding, $classification)->action;
    }

    public function testInactiveKernel(): void {
        $this->assertSame(Action::RemoveOrUpdateKernel, $this->action($this->finding(['is_kernel' => true, 'is_running' => false])));
    }

    public function testUpdateWithVersion(): void {
        $finding = $this->finding(['update_available' => true, 'update_version' => '2.0']);
        $recommendation = (new ActionAdvisor())->forFinding($finding, (new FindingClassifier())->classify($finding));

        $this->assertSame(Action::InstallUpdate, $recommendation->action);
        $this->assertSame('2.0', $recommendation->version);
    }

    public function testUpdateDoesNotHelpIfTheVendorSaysNotFixed(): void {
        $this->assertSame(Action::WatchVendor, $this->action($this->finding(['update_available' => true, 'not_fixed_yet' => true])));
    }

    public function testDecidedFindingsNeedAReview(): void {
        $this->assertSame(Action::ReviewDecision, $this->action($this->finding(), new Classification(Rule::Accepted, 0)));
    }

    public function testWindowsUpdate(): void {
        $this->assertSame(Action::InstallOsUpdate, $this->action($this->finding(['component_type' => 'os', 'fixed_in' => 'KB5066835'])));
    }

    public function testApplicationsWithoutFixData(): void {
        $this->assertSame(Action::UpdateToVendorVersion, $this->action($this->finding(['component_type' => 'app'])));
    }

    public function testComponentAcrossHosts(): void {
        $advisor = new ActionAdvisor();

        $this->assertSame(Action::UpdateKernelAndReboot, $advisor->forComponent('package', true, false)->action);
        $this->assertSame(Action::UpdatePackage, $advisor->forComponent('package', false, true)->action);
        $this->assertSame(Action::CheckMitigation, $advisor->forComponent('package', false, false)->action);
        $this->assertSame(Action::UpdateOperatingSystem, $advisor->forComponent('os', false, false)->action);
        $this->assertSame(Action::UpdateApplication, $advisor->forComponent('app', false, false)->action);
    }
}
