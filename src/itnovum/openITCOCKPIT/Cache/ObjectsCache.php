<?php
// Copyright (C) 2015-2025  it-novum GmbH
// Copyright (C) 2025-today Allgeier IT Services GmbH
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

namespace itnovum\openITCOCKPIT\Cache;

/**
 * PHP passes objects by reference, instead of copy-on-write.
 * This means that we can pass an instance of this object to a function, and the function
 * can write directly into this object.
 *
 * Would we pass a simple array parameter, and we change the data in the array, php would need to create a copy of the array
 * (copy-on-write).
 * If we do not change the array and only read from it, this is not required as php only has to copy the variable if we modify it.
 * Alternative we could pass the variable as reference (&cache)
 *
 * See this example code: https://gist.github.com/nook24/77781fea5b6959ecfc7fb1caaa7d67eb
 *
 * ---
 * This is more or less the same as \itnovum\openITCOCKPIT\Core\KeyValueStore,
 * but it can hold multiple type of objects.
 * This class got build to speed up the Changelog of openITCOCKPIT.
 */
class ObjectsCache {

    /**
     * @var array
     */
    private $cache = [];

    /**
     * @param int $objectType
     * @param $key
     * @return bool
     */
    public function has(int $objectType, $key): bool {
        return isset($this->cache[$objectType][$key]);
    }

    /**
     * @param int $objectType
     * @param $key
     * @param $data
     * @return void
     */
    public function set(int $objectType, $key, $data): void {
        $this->cache[$objectType][$key] = $data;
    }

    /**
     * @param int $objectType
     * @param $key
     * @return mixed
     * @throws \Exception
     */
    public function get(int $objectType, $key) {
        if (isset($this->cache[$objectType][$key])) {
            return $this->cache[$objectType][$key];
        }

        throw new \Exception(sprintf('Key %s.%s not found in key value store', $objectType, $key));
    }

}
