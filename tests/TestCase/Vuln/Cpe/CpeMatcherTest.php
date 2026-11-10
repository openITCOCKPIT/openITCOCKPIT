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

namespace App\Test\TestCase\Vuln\Cpe;

use itnovum\openITCOCKPIT\InitialDatabase\CveCpeProduct;
use itnovum\openITCOCKPIT\Vuln\Cpe\CpeCondition;
use itnovum\openITCOCKPIT\Vuln\Cpe\CpeMatcher;
use itnovum\openITCOCKPIT\Vuln\Cpe\CpeRule;
use itnovum\openITCOCKPIT\Vuln\Cpe\InstalledApp;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class CpeMatcherTest extends TestCase {

    /**
     * The default rules of the seed, without database
     *
     * @return CpeRule[]
     */
    private static function defaultRules(string $platform): array {
        /** @var CveCpeProduct $importer */
        $importer = (new ReflectionClass(CveCpeProduct::class))->newInstanceWithoutConstructor();

        $rules = [];
        foreach ($importer->getData() as $product) {
            foreach ($product['mappings'] as $mapping) {
                if ($mapping['platform'] !== $platform) {
                    continue;
                }
                $second = null;
                if ($mapping['second_field'] !== null) {
                    $second = new CpeCondition($mapping['second_field'], $mapping['second_match'], $mapping['second_value']);
                }
                $rules[] = new CpeRule($product['vendor'], $product['product'], $platform,
                    new CpeCondition($mapping['first_field'], $mapping['first_match'], $mapping['first_value']), $second);
            }
        }
        return $rules;
    }

    /**
     * Applications as reported by real agents
     *
     * @return array<string, array{0: string, 1: InstalledApp, 2: string|null}> platform, application, expected "vendor:product"
     */
    public static function applications(): array {
        return [
            'Firefox on Windows'                => ['windows', new InstalledApp('Mozilla Firefox (x64 de)', '146.0.1', 'Mozilla'), 'mozilla:firefox'],
            'Mozilla service is no Firefox'     => ['windows', new InstalledApp('Mozilla Maintenance Service', '146.0.1', 'Mozilla'), null],
            'Chrome on Windows'                 => ['windows', new InstalledApp('Google Chrome', '143.0.7499.170', 'Google LLC'), 'google:chrome'],
            'Chrome needs the publisher'        => ['windows', new InstalledApp('Google Chrome', '143.0', 'Unknown'), null],
            'Edge on Windows'                   => ['windows', new InstalledApp('Microsoft Edge', '143.0.3650.139', 'Microsoft Corporation'), 'microsoft:edge_chromium'],
            '7-Zip'                             => ['windows', new InstalledApp('7-Zip 25.01 (x64)', '25.01', 'Igor Pavlov'), '7-zip:7-zip'],
            '7-Zip fork is no 7-Zip'            => ['windows', new InstalledApp('7-Zip-zstd 24.09 (x64)', '24.09', 'Igor Pavlov'), null],
            'Notepad++, broken in the prototype' => ['windows', new InstalledApp('Notepad++ (64-bit x64)', '8.8', 'Notepad++ Team'), 'notepad-plus-plus:notepad++'],
            'Python main entry'                 => ['windows', new InstalledApp('Python 3.13.7 (64-bit)', '3.13.7', 'Python Software Foundation'), 'python:python'],
            'Python part, same version'         => ['windows', new InstalledApp('Python 3.13.7 Core Interpreter (64-bit)', '3.13.7', 'Python Software Foundation'), 'python:python'],
            'PowerShell 7'                      => ['windows', new InstalledApp('PowerShell 7.6.4.0-x64', '7.6.4.0', 'Microsoft Corporation'), 'microsoft:powershell'],
            'Visual Studio Code on Windows'     => ['windows', new InstalledApp('Microsoft Visual Studio Code (User)', '1.105.1', 'Microsoft Corporation'), 'microsoft:visual_studio_code'],
            'Unknown application'               => ['windows', new InstalledApp('Discord', '1.0', 'Discord Inc.'), null],
            'Chrome on macOS by bundle'         => ['macos', new InstalledApp('Google Chrome', '157.0.8103.39', null, 'com.google.Chrome'), 'google:chrome'],
            'Edge on macOS by bundle'           => ['macos', new InstalledApp('Microsoft Edge', '156.0', null, 'com.microsoft.edgemac'), 'microsoft:edge_chromium'],
            'Translated name, bundle matches'   => ['macos', new InstalledApp('Docker Desktop (de)', '4.97.0', null, 'com.docker.docker'), 'docker:desktop'],
            'Old agent without bundle'          => ['macos', new InstalledApp('Docker', '4.92.0'), 'docker:desktop'],
            'Python of python.org'              => ['macos', new InstalledApp('Python', '3.12.5'), 'python:python'],
            'Apple apps need no rule'           => ['macos', new InstalledApp('Kalender', '27.0', null, 'com.apple.iCal'), null],
        ];
    }

    #[DataProvider('applications')]
    public function testDefaultRules(string $platform, InstalledApp $app, ?string $expected): void {
        $products = (new CpeMatcher(self::defaultRules($platform)))->findProducts($app);

        $this->assertSame($expected === null ? [] : [$expected], array_keys($products));
    }

    public function testEveryDefaultRuleOnlyUsesFieldsOfItsPlatform(): void {
        $fieldsOfPlatform = [
            'windows' => [CpeCondition::FIELD_NAME, CpeCondition::FIELD_PUBLISHER],
            'macos'   => [CpeCondition::FIELD_NAME, CpeCondition::FIELD_BUNDLE_ID],
        ];

        foreach (['windows', 'macos'] as $platform) {
            foreach (self::defaultRules($platform) as $rule) {
                $this->assertContains($rule->firstCondition->field, $fieldsOfPlatform[$platform], $rule->cpeKey());
                if ($rule->secondCondition !== null) {
                    $this->assertContains($rule->secondCondition->field, $fieldsOfPlatform[$platform], $rule->cpeKey());
                }
            }
        }
    }

    public function testComparisons(): void {
        $app = new InstalledApp('Acme Viewer 2 (x64)', '2.0', 'Acme Inc.');

        $this->assertTrue((new CpeCondition('name', 'equals', 'acme viewer 2 (x64)'))->matches($app), 'Upper and lower case do not matter');
        $this->assertFalse((new CpeCondition('name', 'equals', 'Acme Viewer'))->matches($app));
        $this->assertTrue((new CpeCondition('name', 'starts_with', 'Acme Viewer'))->matches($app));
        $this->assertTrue((new CpeCondition('name', 'contains', 'Viewer 2'))->matches($app));
        $this->assertTrue((new CpeCondition('name', 'ends_with', '(x64)'))->matches($app));
        $this->assertFalse((new CpeCondition('name', 'ends_with', '(x86)'))->matches($app));
    }

    public function testValuesAreNoRegularExpressions(): void {
        $app = new InstalledApp('Notepadd 8', '8');

        $this->assertFalse((new CpeCondition('name', 'starts_with', 'Notepad++'))->matches($app));
        $this->assertFalse((new CpeCondition('name', 'contains', '.*'))->matches($app));
    }

    public function testMissingFieldNeverMatches(): void {
        $app = new InstalledApp('Google Chrome', '1.0');

        $this->assertFalse((new CpeCondition('bundle_id', 'contains', ''))->matches($app));
        $this->assertFalse((new CpeCondition('publisher', 'contains', 'Google'))->matches($app));
    }

    public function testBothConditionsHaveToMatch(): void {
        $rule = new CpeRule('acme', 'viewer', 'windows', new CpeCondition('name', 'starts_with', 'Acme'), new CpeCondition('publisher', 'equals', 'Acme Inc.'));

        $this->assertTrue($rule->matches(new InstalledApp('Acme Viewer', '1', 'Acme Inc.')));
        $this->assertFalse($rule->matches(new InstalledApp('Acme Viewer', '1', 'Someone else')));
    }

    public function testAProductIsReturnedOnceEvenIfSeveralRulesMatch(): void {
        $rules = self::defaultRules('macos');

        $products = (new CpeMatcher($rules))->findProducts(new InstalledApp('Google Chrome', '1.0', null, 'com.google.Chrome'));

        $this->assertSame(['google:chrome'], array_keys($products));
    }
}
