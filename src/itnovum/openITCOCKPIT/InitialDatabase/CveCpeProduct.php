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

namespace itnovum\openITCOCKPIT\InitialDatabase;

use App\Model\Table\CveCpeProductsTable;
use itnovum\openITCOCKPIT\Vuln\Cpe\CpeCondition;

/**
 * Class CveCpeProduct
 *
 * Default CPE products with their rules for installed applications and their names in CVE descriptions.
 *
 * The seed runs with every update. It only creates products that do not exist yet. Existing products,
 * their rules and names are never touched, so changes of users stay. A deleted default product would come back,
 * so users should disable it instead.
 *
 * @package itnovum\openITCOCKPIT\InitialDatabase
 * @property CveCpeProductsTable $Table
 */
class CveCpeProduct extends Importer {

    /**
     * @return bool
     */
    public function import() {
        foreach ($this->getData() as $record) {
            $exists = $this->Table->exists([
                'CveCpeProducts.vendor'  => $record['vendor'],
                'CveCpeProducts.product' => $record['product'],
            ]);
            if ($exists) {
                continue;
            }

            // One transaction per product, an error must not leave a product without its rules
            $this->Table->getConnection()->transactional(function () use ($record) {
                $product = $this->Table->newEntity([
                    'vendor'  => $record['vendor'],
                    'product' => $record['product'],
                    'enabled' => true,
                ]);
                $product->is_default = true;
                $this->Table->saveOrFail($product);

                foreach ($record['mappings'] as $mapping) {
                    $entity = $this->Table->CveCpeMappings->newEntity($mapping + ['cve_cpe_product_id' => $product->id]);
                    $this->Table->CveCpeMappings->saveOrFail($entity);
                }

                foreach ($record['names'] as $name) {
                    $entity = $this->Table->CveCpeProductNames->newEntity(['cve_cpe_product_id' => $product->id, 'name' => $name]);
                    $this->Table->CveCpeProductNames->saveOrFail($entity);
                }
            });
        }

        return true;
    }

    /**
     * Products found on real hosts. Names in CVE descriptions are only given where real CVEs name platforms.
     *
     * @return array
     */
    public function getData() {
        return [
            [
                'vendor'   => 'google',
                'product'  => 'chrome',
                'mappings' => [
                    $this->windows(CpeCondition::FIELD_NAME, CpeCondition::MATCH_EQUALS, 'Google Chrome', CpeCondition::FIELD_PUBLISHER, CpeCondition::MATCH_CONTAINS, 'Google'),
                    $this->macos(CpeCondition::FIELD_BUNDLE_ID, CpeCondition::MATCH_EQUALS, 'com.google.Chrome'),
                    // Agents older than 3.8.0 report no bundle identifier
                    $this->macos(CpeCondition::FIELD_NAME, CpeCondition::MATCH_EQUALS, 'Google Chrome'),
                ],
                'names'    => ['Google Chrome'],
            ],
            [
                'vendor'   => 'mozilla',
                'product'  => 'firefox',
                'mappings' => [
                    // e.g. "Mozilla Firefox (x64 de)", but not "Mozilla Maintenance Service"
                    $this->windows(CpeCondition::FIELD_NAME, CpeCondition::MATCH_STARTS_WITH, 'Mozilla Firefox', CpeCondition::FIELD_PUBLISHER, CpeCondition::MATCH_CONTAINS, 'Mozilla'),
                    $this->macos(CpeCondition::FIELD_BUNDLE_ID, CpeCondition::MATCH_EQUALS, 'org.mozilla.firefox'),
                    $this->macos(CpeCondition::FIELD_NAME, CpeCondition::MATCH_EQUALS, 'Firefox'),
                ],
                // "Firefox Focus" is listed so "Firefox and Firefox Focus for Android" counts as mention of Firefox
                'names'    => ['Firefox', 'Firefox Focus'],
            ],
            [
                'vendor'   => 'microsoft',
                'product'  => 'edge_chromium',
                'mappings' => [
                    $this->windows(CpeCondition::FIELD_NAME, CpeCondition::MATCH_EQUALS, 'Microsoft Edge', CpeCondition::FIELD_PUBLISHER, CpeCondition::MATCH_CONTAINS, 'Microsoft'),
                    $this->macos(CpeCondition::FIELD_BUNDLE_ID, CpeCondition::MATCH_EQUALS, 'com.microsoft.edgemac'),
                    $this->macos(CpeCondition::FIELD_NAME, CpeCondition::MATCH_EQUALS, 'Microsoft Edge'),
                ],
                'names'    => ['Microsoft Edge', 'Microsoft Edge (Chromium-based)'],
            ],
            [
                'vendor'   => 'docker',
                'product'  => 'desktop',
                'mappings' => [
                    $this->windows(CpeCondition::FIELD_NAME, CpeCondition::MATCH_EQUALS, 'Docker Desktop'),
                    $this->macos(CpeCondition::FIELD_BUNDLE_ID, CpeCondition::MATCH_EQUALS, 'com.docker.docker'),
                    $this->macos(CpeCondition::FIELD_NAME, CpeCondition::MATCH_EQUALS, 'Docker'),
                ],
                'names'    => ['Docker Desktop'],
            ],
            [
                'vendor'   => '7-zip',
                'product'  => '7-zip',
                'mappings' => [
                    // The space at the end is on purpose: "7-Zip 25.01 (x64)", but not "7-Zip-zstd"
                    $this->windows(CpeCondition::FIELD_NAME, CpeCondition::MATCH_STARTS_WITH, '7-Zip ', CpeCondition::FIELD_PUBLISHER, CpeCondition::MATCH_CONTAINS, 'Igor Pavlov'),
                ],
                'names'    => ['7-Zip'],
            ],
            [
                'vendor'   => 'microsoft',
                'product'  => 'visual_studio_code',
                'mappings' => [
                    // e.g. "Microsoft Visual Studio Code (User)"
                    $this->windows(CpeCondition::FIELD_NAME, CpeCondition::MATCH_STARTS_WITH, 'Microsoft Visual Studio Code'),
                    $this->macos(CpeCondition::FIELD_BUNDLE_ID, CpeCondition::MATCH_EQUALS, 'com.microsoft.VSCode'),
                    $this->macos(CpeCondition::FIELD_NAME, CpeCondition::MATCH_EQUALS, 'Visual Studio Code'),
                ],
                'names'    => [],
            ],
            [
                'vendor'   => 'python',
                'product'  => 'python',
                'mappings' => [
                    // The installer adds several entries like "Python 3.13.7 Core Interpreter (64-bit)". They all have
                    // the same version, so they result in the same CVEs
                    $this->windows(CpeCondition::FIELD_NAME, CpeCondition::MATCH_STARTS_WITH, 'Python 3.', CpeCondition::FIELD_PUBLISHER, CpeCondition::MATCH_CONTAINS, 'Python Software Foundation'),
                    // The installers of python.org set no bundle identifier
                    $this->macos(CpeCondition::FIELD_NAME, CpeCondition::MATCH_EQUALS, 'Python'),
                ],
                'names'    => [],
            ],
            [
                'vendor'   => 'microsoft',
                'product'  => 'powershell',
                'mappings' => [
                    // e.g. "PowerShell 7.6.4.0-x64" or "PowerShell 7-preview-x64"
                    $this->windows(CpeCondition::FIELD_NAME, CpeCondition::MATCH_STARTS_WITH, 'PowerShell 7', CpeCondition::FIELD_PUBLISHER, CpeCondition::MATCH_CONTAINS, 'Microsoft'),
                ],
                'names'    => [],
            ],
            [
                'vendor'   => 'git-scm',
                'product'  => 'git',
                'mappings' => [
                    $this->windows(CpeCondition::FIELD_NAME, CpeCondition::MATCH_EQUALS, 'Git', CpeCondition::FIELD_PUBLISHER, CpeCondition::MATCH_CONTAINS, 'Git Development Community'),
                ],
                'names'    => [],
            ],
            [
                'vendor'   => 'mozilla',
                'product'  => 'thunderbird',
                'mappings' => [
                    $this->windows(CpeCondition::FIELD_NAME, CpeCondition::MATCH_STARTS_WITH, 'Mozilla Thunderbird', CpeCondition::FIELD_PUBLISHER, CpeCondition::MATCH_CONTAINS, 'Mozilla'),
                ],
                'names'    => [],
            ],
            [
                'vendor'   => 'nodejs',
                'product'  => 'node.js',
                'mappings' => [
                    $this->windows(CpeCondition::FIELD_NAME, CpeCondition::MATCH_EQUALS, 'Node.js'),
                ],
                'names'    => [],
            ],
            [
                'vendor'   => 'notepad-plus-plus',
                'product'  => 'notepad++',
                'mappings' => [
                    // e.g. "Notepad++ (64-bit x64)"
                    $this->windows(CpeCondition::FIELD_NAME, CpeCondition::MATCH_STARTS_WITH, 'Notepad++ '),
                ],
                'names'    => [],
            ],
            [
                'vendor'   => 'obsproject',
                'product'  => 'obs_studio',
                'mappings' => [
                    $this->windows(CpeCondition::FIELD_NAME, CpeCondition::MATCH_EQUALS, 'OBS Studio', CpeCondition::FIELD_PUBLISHER, CpeCondition::MATCH_CONTAINS, 'OBS Project'),
                    $this->macos(CpeCondition::FIELD_BUNDLE_ID, CpeCondition::MATCH_EQUALS, 'com.obsproject.obs-studio'),
                ],
                'names'    => [],
            ],
            [
                'vendor'   => 'vmware',
                'product'  => 'workstation',
                'mappings' => [
                    $this->windows(CpeCondition::FIELD_NAME, CpeCondition::MATCH_EQUALS, 'VMware Workstation', CpeCondition::FIELD_PUBLISHER, CpeCondition::MATCH_CONTAINS, 'VMware'),
                ],
                'names'    => [],
            ],
            [
                'vendor'   => 'sublimetext',
                'product'  => 'sublime_text',
                'mappings' => [
                    $this->macos(CpeCondition::FIELD_NAME, CpeCondition::MATCH_EQUALS, 'Sublime Text'),
                ],
                'names'    => [],
            ],
        ];
    }

    private function windows(string $firstField, string $firstMatch, string $firstValue, ?string $secondField = null, ?string $secondMatch = null, ?string $secondValue = null): array {
        return $this->mapping('windows', $firstField, $firstMatch, $firstValue, $secondField, $secondMatch, $secondValue);
    }

    private function macos(string $firstField, string $firstMatch, string $firstValue, ?string $secondField = null, ?string $secondMatch = null, ?string $secondValue = null): array {
        return $this->mapping('macos', $firstField, $firstMatch, $firstValue, $secondField, $secondMatch, $secondValue);
    }

    private function mapping(string $platform, string $firstField, string $firstMatch, string $firstValue, ?string $secondField, ?string $secondMatch, ?string $secondValue): array {
        return [
            'platform'     => $platform,
            'first_field'  => $firstField,
            'first_match'  => $firstMatch,
            'first_value'  => $firstValue,
            'second_field' => $secondField,
            'second_match' => $secondMatch,
            'second_value' => $secondValue,
        ];
    }
}
