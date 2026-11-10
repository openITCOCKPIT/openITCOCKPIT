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

namespace itnovum\openITCOCKPIT\Vuln\Cpe;

/**
 * Finds the CPE products of installed applications.
 *
 * An application without a matching rule is not checked for CVEs. It gets listed in the coverage
 * of the host as unmapped application, so 0 CVEs is never mistaken for "secure".
 */
class CpeMatcher {

    /**
     * @param CpeRule[] $rules Rules of one platform, e.g. from CveCpeProductsTable::getRulesOfPlatform()
     */
    public function __construct(
        private array $rules
    ) {
    }

    /**
     * Several rules can point to the same product, e.g. one by bundle identifier and one by name.
     * The product is returned only once.
     *
     * @param InstalledApp $app
     * @return CpeRule[] One matching rule per product, indexed by "vendor:product". Empty if no rule matches
     */
    public function findProducts(InstalledApp $app): array {
        $products = [];

        foreach ($this->rules as $rule) {
            if ($rule->matches($app) && !isset($products[$rule->cpeKey()])) {
                $products[$rule->cpeKey()] = $rule;
            }
        }

        return $products;
    }
}
