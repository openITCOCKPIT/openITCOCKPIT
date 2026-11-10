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
            'Unknown application'               => ['windows', new InstalledApp('Acme Tool', '1.0', 'Acme Inc.'), null],
            'Own agent has no CPE'              => ['windows', new InstalledApp('openITCOCKPIT Monitoring Agent', '3.8.0', 'it-novum'), null],
            'Citrix Workspace main entry'       => ['windows', new InstalledApp('Citrix Workspace 2409', '24.9.10.28', 'Citrix Systems, Inc.'), 'citrix:workspace'],
            'Citrix component is no product'    => ['windows', new InstalledApp('Citrix Workspace (USB)', '24.9.10.28', 'Citrix Systems, Inc.'), null],
            'FileZilla client'                  => ['windows', new InstalledApp('FileZilla 3.69.5', '3.69.5', 'Tim Kosse'), 'filezilla-project:filezilla_client'],
            'FileZilla Server is no client'     => ['windows', new InstalledApp('FileZilla Server', '1.10.3', 'Tim Kosse'), null],
            'Teams add-in is no Teams'          => ['windows', new InstalledApp('Microsoft Teams Meeting Add-in for Microsoft Office', '1.25', 'Microsoft'), null],
            'RealVNC Viewer'                    => ['windows', new InstalledApp('RealVNC Viewer 7.13.1', '7.13.1', 'RealVNC'), 'realvnc:vnc_viewer'],
            'FortiClient VPN'                   => ['windows', new InstalledApp('FortiClient VPN', '7.4.3', 'Fortinet Technologies Inc'), 'fortinet:forticlient'],
            'draw.io'                           => ['windows', new InstalledApp('draw.io 28.2.8', '28.2.8', 'JGraph'), 'diagrams:drawio'],
            'Go'                                => ['windows', new InstalledApp('Go Programming Language amd64 go1.25.0', '1.25.0', 'https://go.dev'), 'golang:go'],
            'OneDrive'                          => ['windows', new InstalledApp('Microsoft OneDrive', '25.180', 'Microsoft Corporation'), 'microsoft:onedrive'],
            'Photoshop on Windows'              => ['windows', new InstalledApp('Adobe Photoshop 2026', '27.1.0', 'Adobe Inc.'), 'adobe:photoshop'],
            'Photoshop Elements is no Photoshop' => ['windows', new InstalledApp('Adobe Photoshop Elements 2025', '23.0', 'Adobe Inc.'), null],
            'InDesign on Windows'               => ['windows', new InstalledApp('Adobe InDesign 2026', '21.3', 'Adobe Inc.'), 'adobe:indesign'],
            'RustDesk on Windows'               => ['windows', new InstalledApp('RustDesk', '1.4.9', 'RustDesk'), 'rustdesk:rustdesk'],
            'Chrome on macOS by bundle'         => ['macos', new InstalledApp('Google Chrome', '157.0.8103.39', null, 'com.google.Chrome'), 'google:chrome'],
            'Edge on macOS by bundle'           => ['macos', new InstalledApp('Microsoft Edge', '156.0', null, 'com.microsoft.edgemac'), 'microsoft:edge_chromium'],
            'Translated name, bundle matches'   => ['macos', new InstalledApp('Docker Desktop (de)', '4.97.0', null, 'com.docker.docker'), 'docker:desktop'],
            'Agent before 3.8.0, no bundle'     => ['macos', new InstalledApp('Docker', '4.92.0'), null],
            'Name alone never matches on macOS' => ['macos', new InstalledApp('Google Chrome', '157.0'), null],
            'Python of the Command Line Tools'  => ['macos', new InstalledApp('Python', '3.9.6', null, 'com.apple.python3'), 'python:python'],
            'Python of python.org'              => ['macos', new InstalledApp('Python', '3.12.5', null, 'org.python.python'), 'python:python'],
            'Apple apps need no rule'           => ['macos', new InstalledApp('Kalender', '27.0', null, 'com.apple.iCal'), null],
            'FortiClient on macOS'              => ['macos', new InstalledApp('FortiClient', '7.4.3', null, 'com.fortinet.FortiClient'), 'fortinet:forticlient'],
            'FortiClient uninstaller is no app' => ['macos', new InstalledApp('FortiClientUninstaller', '7.4.3'), null],
            'PhpStorm by bundle'                => ['macos', new InstalledApp('PhpStorm', '2026.2', null, 'com.jetbrains.PhpStorm'), 'jetbrains:phpstorm'],
            'IntelliJ community edition'        => ['macos', new InstalledApp('IntelliJ IDEA CE', '2026.2', null, 'com.jetbrains.intellij.ce'), 'jetbrains:intellij_idea'],
            'Zoom on macOS'                     => ['macos', new InstalledApp('zoom.us', '6.6.0', null, 'us.zoom.xos'), 'zoom:workplace'],
            'Affinity by Canva on Windows'      => ['windows', new InstalledApp('Affinity', '3.3.1', 'Canva'), 'canva:affinity'],
            'Affinity Photo 2 is no Canva app'  => ['macos', new InstalledApp('Affinity Photo 2', '2.6.5', null, 'com.seriflabs.affinityphoto2'), null],
            'Affinity Designer 2 is no Canva app' => ['macos', new InstalledApp('Affinity Designer 2', '2.6.5', null, 'com.seriflabs.affinitydesigner2'), null],
            'Photoshop on macOS'                => ['macos', new InstalledApp('Adobe Photoshop 2026', '27.1.0', null, 'com.adobe.Photoshop'), 'adobe:photoshop'],
            'RustDesk on macOS'                 => ['macos', new InstalledApp('RustDesk', '1.4.9', null, 'com.carriez.rustdesk'), 'rustdesk:rustdesk'],
        ];
    }

    #[DataProvider('applications')]
    public function testDefaultRules(string $platform, InstalledApp $app, ?string $expected): void {
        $products = (new CpeMatcher(self::defaultRules($platform)))->findProducts($app);

        if ($expected === null) {
            $this->assertSame([], array_keys($products));
            return;
        }
        // Some applications belong to two CPE products, e.g. zoom:workplace and zoom:zoom
        $this->assertContains($expected, array_keys($products));
    }

    public function testApplicationsWithTwoCpeProducts(): void {
        $windows = new CpeMatcher(self::defaultRules('windows'));

        $this->assertSame(['adobe:acrobat_reader_dc', 'adobe:acrobat_reader'], array_keys($windows->findProducts(new InstalledApp('Adobe Acrobat Reader (64-bit)', '25.001.20630', 'Adobe'))));
        $this->assertSame(['adobe:acrobat_dc', 'adobe:acrobat'], array_keys($windows->findProducts(new InstalledApp('Adobe Acrobat (64-bit)', '25.001.20630', 'Adobe'))));
        $this->assertSame(['zoom:workplace', 'zoom:zoom'], array_keys($windows->findProducts(new InstalledApp('Zoom Workplace (64-bit)', '6.6.0', 'Zoom Video Communications, Inc.'))));
    }

    public function testEveryDefaultProductIsUnique(): void {
        /** @var CveCpeProduct $importer */
        $importer = (new ReflectionClass(CveCpeProduct::class))->newInstanceWithoutConstructor();
        $keys = array_map(fn($product) => $product['vendor'] . ':' . $product['product'], $importer->getData());

        $this->assertSame(count($keys), count(array_unique($keys)));
    }

    public function testEveryDefaultRuleOnlyUsesFieldsOfItsPlatform(): void {
        // Same as CveCpeMappingsTable: macOS rules only check the bundle identifier
        $fieldsOfPlatform = [
            'windows' => [CpeCondition::FIELD_NAME, CpeCondition::FIELD_PUBLISHER],
            'macos'   => [CpeCondition::FIELD_BUNDLE_ID],
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
        $rules = [
            new CpeRule('acme', 'viewer', 'macos', new CpeCondition('bundle_id', 'equals', 'com.acme.viewer')),
            new CpeRule('acme', 'viewer', 'macos', new CpeCondition('bundle_id', 'starts_with', 'com.acme.')),
        ];

        $products = (new CpeMatcher($rules))->findProducts(new InstalledApp('Acme Viewer', '1.0', null, 'com.acme.viewer'));

        $this->assertSame(['acme:viewer'], array_keys($products));
    }
}
