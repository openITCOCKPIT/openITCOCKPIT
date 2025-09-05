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

namespace App\Lib\Traits;

/**
 * Add Bit-Flags support to an Entity
 * Requires the Entity class to define self::FLAG_BLANK as constant.
 */
trait BitFlagTrait {

    /**
     * Add a new flag to the entity object
     * See self::FLAG_ constants for a list of available flags
     *
     * @param int $flag
     * @return bool
     * @throws \Exception
     */
    public function addFlag(int $flag): bool {
        if ($this->isNew()) {
            // New entities does not have any flags
            $this->set('flags', $flag);
        }

        $flags = $this->flags;

        if ($flags === null) {
            throw new \Exception('Entity was fetched without flags field!');
        }

        if ($flag & $flags) {
            // Entity already has the given flag
            return true;
        } else {
            // Add new flag to entity flags
            $this->set('flags', ($flags + $flag));
        }

        return true;
    }


    /**
     * Remove a new flag to the entity object
     * See self::FLAG_ constants for a list of available flags
     *
     * @param int $flag
     * @return true
     * @throws \Exception
     */
    public function removeFlag(int $flag): bool {
        $flags = $this->flags;

        if ($flags === null) {
            throw new \Exception('Entity was fetched without flags field!');
        }

        if ($flags & $flag) {
            // Remove flag from Entity
            $flags = $flags - $flag;

            if ($flags < 0) {
                $flags = self::FLAG_BLANK;
            }

            $this->set('flags', $flags);
        }
        return true;
    }

    /**
     * Checks if a flag is set to the entity
     *
     * @param int $flag
     * @return bool
     * @throws \Exception
     */
    public function hasFlag(int $flag): bool {
        if ($this->flags === null) {
            throw new \Exception('Entity was fetched without flags field!');
        }

        return ($this->flags & $flag) > 0;
    }

    /**
     * Remove ALL flags from the entity
     *
     * @return true
     * @throws \Exception
     */
    public function removeAllFlags(): bool {
        if ($this->flags === null) {
            throw new \Exception('Entity was fetched without flags field!');
        }

        $this->set('flags', self::FLAG_BLANK);
        return true;
    }

}
