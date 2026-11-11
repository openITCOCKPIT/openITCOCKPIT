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

use PHPUnit\Framework\TestCase;

/**
 * The policy must work without CakePHP, database or Vuls, so it can be tested and explained on its own.
 */
class PolicyIndependenceTest extends TestCase {

    private const POLICY_DIRECTORY = __DIR__ . '/../../../../src/itnovum/openITCOCKPIT/Vuln/Policy';

    public function testPolicyOnlyUsesItsOwnClassesAndPhp(): void {
        $files = glob(self::POLICY_DIRECTORY . '/*.php');
        $this->assertNotEmpty($files);

        foreach ($files as $file) {
            preg_match_all('/^use ([^;]+);/m', (string)file_get_contents($file), $matches);
            foreach ($matches[1] as $import) {
                $this->assertContains($import, ['DateTimeImmutable', 'DateTimeInterface'], basename($file) . ' imports ' . $import);
            }
        }
    }

    public function testPolicyRunsWithoutAutoloader(): void {
        $code = '$files = glob(' . var_export(realpath(self::POLICY_DIRECTORY), true) . ' . "/*.php");'
            . ' spl_autoload_register(function ($class) use ($files) {'
            . '   require dirname($files[0]) . "/" . substr(strrchr($class, "\\\\"), 1) . ".php";'
            . ' });'
            . ' echo (new itnovum\openITCOCKPIT\Vuln\Policy\PriorityResolver())->resolve([], "macos")->source->value;';

        $output = shell_exec(PHP_BINARY . ' -n -r ' . escapeshellarg($code));

        $this->assertSame('unknown', $output);
    }
}
