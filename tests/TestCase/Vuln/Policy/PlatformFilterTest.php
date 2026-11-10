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

use itnovum\openITCOCKPIT\Vuln\Policy\PlatformFilter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Descriptions of real CVEs
 */
class PlatformFilterTest extends TestCase {

    /**
     * The products the migration CvePlatformProducts creates
     */
    private const DEFAULT_PRODUCTS = [
        'Google Chrome',
        'Firefox',
        'Firefox Focus',
        'Microsoft Edge',
        'Microsoft Edge (Chromium-based)',
        'Docker Desktop',
        '7-Zip',
    ];

    /**
     * @return array<string, array{0: string, 1: string, 2: bool}> host platform, description, filtered
     */
    public static function descriptions(): array {
        return [
            'Chrome on Android on Windows'             => ['windows', 'Uninitialized Use in GPU in Google Chrome on Android prior to 148.0.7778.216 allowed', true],
            'Typo "on on" of Google'                   => ['windows', 'in ANGLE in Google Chrome on on iOS prior to 154.0.8037.92 allowed', true],
            'Works in both directions'                 => ['macos', 'Use after free in Passwords in Google Chrome on Windows prior to 148.0.7778.216', true],
            'Own platform stays'                       => ['windows', 'in Passwords in Google Chrome on Windows prior to 148.0.7778.216', false],
            'List with own platform stays'             => ['macos', 'Out of bounds write in Media in Google Chrome on Mac, iOS prior to 148.0.7778.96', false],
            'List without own platform'                => ['windows', 'Out of bounds write in Media in Google Chrome on Mac, iOS prior to 148.0.7778.96', true],
            'List with "and"'                          => ['macos', 'Heap buffer overflow in Google Chrome on Mac and iOS prior to 148.0.7778.168', false],
            'Without platform stays'                   => ['windows', 'Heap buffer overflow in Dawn in Google Chrome prior to 148.0.7778.168', false],
            'Unclear text stays'                       => ['windows', 'Use after free in iOS in Google Chrome prior to 147.0.7727.138', false],
            'Unknown platform stays'                   => ['windows', 'in Google Chrome on Banana prior to 1.0', false],
            'Firefox for Android'                      => ['windows', 'Clickjacking issue in the WebExtensions component in Firefox for Android. This vulnerability was fixed in Firefox 153.', true],
            'Firefox for iOS'                          => ['windows', 'This vulnerability was fixed in Firefox for iOS 152.3.', true],
            'Firefox iOS'                              => ['windows', 'under the address bar in Firefox iOS, allowing attacker-controlled pages', true],
            'Comma after the platform ends the list'   => ['windows', 'before a response is received in Firefox iOS, allowing attackers to spoof', true],
            'Desktop Firefox can be affected'          => ['windows', 'Sandbox escape in Firefox and Firefox Focus for Android. This vulnerability was fixed in Firefox 151.', false],
            'Mixed case stays'                         => ['windows', 'This vulnerability affects Firefox < 150, Firefox ESR < 140.9, and Firefox for Android < 150.', false],
            'Docker for Windows on Mac'                => ['macos', 'Docker Desktop for Windows contains multiple incorrect permission assignments', true],
            'Docker for Windows on Windows'            => ['windows', 'Docker Desktop for Windows contains multiple incorrect permission assignments', false],
            'Edge for Android'                         => ['windows', 'Improper handling in Microsoft Edge for Android allows an unauthorized attacker', true],
            'Edge Chromium for Android'                => ['windows', 'Microsoft Edge (Chromium-based) for Android Spoofing Vulnerability', true],
            'Edge without platform stays'              => ['windows', 'Microsoft Edge (Chromium-based) Spoofing Vulnerability', false],
            'Only listed products'                     => ['windows', 'The issue is that python on Windows uses a bad location', false],
        ];
    }

    #[DataProvider('descriptions')]
    public function testForeignPlatforms(string $hostPlatform, string $summary, bool $filtered): void {
        $this->assertSame($filtered, (new PlatformFilter(self::DEFAULT_PRODUCTS))->foreignPlatforms($hostPlatform, $summary) !== []);
    }

    public function testNamedPlatformsAreReturned(): void {
        $platforms = (new PlatformFilter(self::DEFAULT_PRODUCTS))->foreignPlatforms('windows', 'in Google Chrome on Mac, iOS prior to 148.0');

        sort($platforms);
        $this->assertSame(['ios', 'macos'], $platforms);
    }

    public function testWithoutProductsNothingIsFiltered(): void {
        $this->assertSame([], (new PlatformFilter([]))->foreignPlatforms('windows', 'in Google Chrome on Android prior to 148.0'));
    }

    public function testOnlyListedProductsAreFiltered(): void {
        $this->assertSame([], (new PlatformFilter(['Firefox']))->foreignPlatforms('windows', 'in Google Chrome on Android prior to 148.0'));
    }

    public function testCustomProduct(): void {
        $filter = new PlatformFilter(['Acme Viewer']);

        $this->assertSame(['android'], $filter->foreignPlatforms('windows', 'A flaw in Acme Viewer for Android allows remote attackers'));
        $this->assertSame([], $filter->foreignPlatforms('windows', 'A flaw in Acme Viewer allows remote attackers'));
    }

    public function testProductWithSpecialCharactersIsNoRegularExpression(): void {
        $filter = new PlatformFilter(['Notepad++', 'Viewer (Beta)']);

        $this->assertSame(['android'], $filter->foreignPlatforms('windows', 'Buffer overflow in Notepad++ for Android'));
        $this->assertSame(['ios'], $filter->foreignPlatforms('windows', 'Spoofing in Viewer (Beta) for iOS'));
        // "+" must not act as quantifier: "Notepadd" is no match for "Notepad++"
        $this->assertSame([], $filter->foreignPlatforms('windows', 'Buffer overflow in Notepadd for Android'));
    }

    public function testProductNamesMatchCaseInsensitive(): void {
        $this->assertSame(['android'], (new PlatformFilter(['acme viewer']))->foreignPlatforms('windows', 'A flaw in Acme Viewer for Android'));
    }

    public function testLongerProductNameWins(): void {
        // "Firefox Focus for Android" must not be read as "Firefox" followed by the unknown word "Focus"
        $this->assertSame(['android'], (new PlatformFilter(['Firefox', 'Firefox Focus']))->foreignPlatforms('windows', 'Spoofing in Firefox Focus for Android'));
    }

    public function testEmptyAndDuplicateNamesAreIgnored(): void {
        $this->assertSame(['android'], (new PlatformFilter(['', ' ', 'Firefox', 'Firefox']))->foreignPlatforms('windows', 'in Firefox for Android'));
    }
}
