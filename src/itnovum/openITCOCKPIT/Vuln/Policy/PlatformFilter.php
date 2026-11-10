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
 * Product matches (CPE) know no operating system: "Chrome on Android" also matches the Chrome installed
 * on Windows. The restriction is only written in the description of the CVE.
 *
 * A CVE gets filtered only if the description limits the product to other platforms exclusively.
 * In case of doubt the finding stays: a false alarm costs time, a swallowed finding leaves a hole open.
 * Findings of the operating system and Linux packages are never filtered.
 *
 * The names come from the table cve_cpe_product_names: the names of the CPE product whose CVEs get filtered,
 * e.g. "Google Chrome" for google:chrome. Customers can add their own software. Without names nothing gets filtered.
 *
 * How a description is read, for every product the same way:
 * 1. Find "<product> <platforms>", e.g. "Firefox for Android", "Google Chrome on Mac, iOS",
 *    "Firefox iOS" or "Google Chrome on on iOS" (typo of Google)
 * 2. Keep the finding if the product is also named without platform, e.g. "Google Chrome prior to 148"
 *    next to "Google Chrome on Android". Versions like "fixed in Firefox 153" do not count as mention.
 * 3. Keep the finding if one of the named platforms is the platform of the host
 */
class PlatformFilter {

    /** Names of platforms in descriptions and the platform they stand for */
    private const PLATFORM_BY_NAME = [
        'android'  => 'android',
        'ios'      => 'ios',
        'ipados'   => 'ios',
        'windows'  => 'windows',
        'mac'      => 'macos',
        'macos'    => 'macos',
        'linux'    => 'linux',
        'chromeos' => 'chromeos',
        'lacros'   => 'chromeos',
        'fuchsia'  => 'fuchsia',
    ];

    /** Words between product and platforms: "for", "on" and the typo "on on" */
    private const CONNECTOR = '(?: for| on| on on)?';

    /** Separates lists of platforms like "Mac, iOS" or "Mac and iOS" */
    private const LIST_SEPARATOR = '(?:\s*,\s*|\s+and\s+)';

    /**
     * Start and end of a product name. Unlike \b they also work for names that start or end with
     * a special character, e.g. "Notepad++" or "Microsoft Edge (Chromium-based)".
     */
    private const NAME_START = '(?<!\w)';
    private const NAME_END = '(?!\w)';

    /** Regular expression that matches any listed product, null if no product is listed */
    private ?string $productPattern = null;

    /** Regular expression that matches the name of one platform */
    private string $platformPattern;

    /**
     * @param string[] $productNames Products as written in CVE descriptions, e.g. "Google Chrome" or "Firefox Focus"
     */
    public function __construct(array $productNames) {
        $this->productPattern = $this->alternativesPattern($productNames);
        $this->platformPattern = $this->alternativesPattern(array_keys(self::PLATFORM_BY_NAME));
    }

    /**
     * @param string $hostPlatform windows or macos
     * @param string $summary Description of the CVE
     * @return string[] Other platforms the description limits the product to. Empty means: keep the finding
     */
    public function foreignPlatforms(string $hostPlatform, string $summary): array {
        if ($this->productPattern === null) {
            return [];
        }

        // 1. "<product> <platforms>". Group 1 is the product, group 2 the list of platforms.
        // The list ends with the first word that is no platform, e.g. "Firefox iOS, allowing ..." names iOS.
        $productWithPlatforms = '/' . self::NAME_START . '(' . $this->productPattern . ')' . self::CONNECTOR . ' '
            . '(' . $this->platformPattern . '(?:' . self::LIST_SEPARATOR . $this->platformPattern . ')*)\b/';

        if (preg_match_all($productWithPlatforms, $summary, $matches) === 0) {
            return [];
        }

        // 2. The product is also named without platform, so every platform can be affected
        if ($this->mentionsProductWithoutPlatform($summary, $productWithPlatforms)) {
            return [];
        }

        $platforms = [];
        foreach ($matches[2] as $platformList) {
            $names = preg_split('/' . self::LIST_SEPARATOR . '/', $platformList, -1, PREG_SPLIT_NO_EMPTY);
            foreach ($names as $name) {
                $platforms[self::PLATFORM_BY_NAME[strtolower($name)]] = true;
            }
        }

        // 3. The platform of the host is one of the named platforms, so the host is affected
        if (isset($platforms[$hostPlatform])) {
            return [];
        }

        return array_keys($platforms);
    }

    /**
     * Removes every "<product> <platforms>" and every "fixed in <product> <version>" from the description
     * and checks if the product is still named.
     */
    private function mentionsProductWithoutPlatform(string $summary, string $productWithPlatforms): bool {
        $rest = (string)preg_replace($productWithPlatforms, '', $summary);
        $rest = (string)preg_replace('/\bfixed in (?:' . $this->productPattern . ') [\d.]+/', '', $rest);

        return preg_match('/' . self::NAME_START . '(?:' . $this->productPattern . ')' . self::NAME_END . '/', $rest) === 1;
    }

    /**
     * Builds "Firefox Focus|Firefox|..." from a list of names. Special characters like "(" or "+" are escaped,
     * so a name never acts as regular expression. Longer names come first, so "Firefox Focus" wins over "Firefox".
     * The names match case-insensitive, descriptions do not always use the same spelling.
     *
     * @param string[] $names
     * @return string|null null if the list is empty
     */
    private function alternativesPattern(array $names): ?string {
        $names = array_unique(array_filter(array_map('trim', $names), fn($name) => $name !== ''));
        if (empty($names)) {
            return null;
        }

        usort($names, fn($a, $b) => strlen($b) <=> strlen($a));

        $escaped = array_map(fn($name) => preg_quote($name, '/'), $names);
        return '(?i:' . implode('|', $escaped) . ')';
    }
}
