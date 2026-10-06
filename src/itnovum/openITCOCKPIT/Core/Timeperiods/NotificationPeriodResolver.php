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

namespace itnovum\openITCOCKPIT\Core\Timeperiods;

use App\Model\Table\TimeperiodsTable;

/**
 * Resolves timeperiods for the notification period overview (frontend: notification-period-overview).
 *
 * Every timeperiod is resolved incl. its recursive excludes (like Naemon does: the exclude timeperiod
 * itself is only active during its own time ranges minus its own exclude, and so on) into two lists:
 *  - effective: time ranges in which the timeperiod is active AFTER excludes were applied
 *  - excluded:  time ranges of the base timeperiod that were removed by an exclude
 *
 * Calendars (holidays) are not taken into account, as the overview only shows a generic week.
 */
class NotificationPeriodResolver {

    private const MINUTES_PER_DAY = 1440;

    /**
     * @var TimeperiodsTable
     */
    private $TimeperiodsTable;

    /**
     * Raw timeperiods by id (null = timeperiod does not exist)
     * @var array
     */
    private $timeperiods = [];

    /**
     * Resolved timeperiods by id
     * @var array
     */
    private $resolved = [];

    /**
     * @param TimeperiodsTable $TimeperiodsTable
     */
    public function __construct(TimeperiodsTable $TimeperiodsTable) {
        $this->TimeperiodsTable = $TimeperiodsTable;
    }

    /**
     * @param int|null $id
     * @return array{id: int, name: string, excludes: string[], effective: array, excluded: array}
     */
    public function resolve($id): array {
        $id = (int)$id;
        if (isset($this->resolved[$id])) {
            return $this->resolved[$id];
        }

        $timeperiod = $this->getTimeperiod($id);
        if ($timeperiod === null) {
            return [
                'id'        => $id,
                'name'      => '',
                'excludes'  => [], //names of exluded timeperiod
                'effective' => [], //serialized timeperiod resolved by exludes
                'excluded'  => [] //serialized timeperiod
            ];
        }

        $base = $this->toIntervals($timeperiod);
        $excludes = [];
        $excludeActive = [];

        $excludeId = (int)$timeperiod['exclude_timeperiod_id'];
        $exclude = $this->getTimeperiod($excludeId);
        if ($exclude !== null) {
            $excludes[] = $exclude['name'];
            $excludeActive = $this->getActiveIntervals($excludeId, [$id => true]);
        }

        $this->resolved[$id] = [
            'id'        => $id,
            'name'      => $timeperiod['name'],
            'excludes'  => $excludes,
            'effective' => $this->toRanges($this->subtract($base, $excludeActive)),
            'excluded'  => $this->toRanges($this->intersect($excludeActive, $base))
        ];

        return $this->resolved[$id];
    }

    /**
     * Time ranges in which the timeperiod is active, incl. its recursive excludes.
     *
     * @param int $id
     * @param array $visited ids of the timeperiods already on the exclude chain (loop protection)
     * @return array
     */
    private function getActiveIntervals(int $id, array $visited): array {
        $timeperiod = $this->getTimeperiod($id);
        if ($timeperiod === null || isset($visited[$id])) {
            return [];
        }

        $intervals = $this->toIntervals($timeperiod);

        $excludeId = (int)$timeperiod['exclude_timeperiod_id'];
        if ($excludeId > 0) {
            $visited[$id] = true;
            $intervals = $this->subtract($intervals, $this->getActiveIntervals($excludeId, $visited));
        }

        return $intervals;
    }

    /**
     * @param int $id
     * @return array|null
     */
    private function getTimeperiod(int $id): ?array {
        if ($id <= 0) {
            return null;
        }

        if (!array_key_exists($id, $this->timeperiods)) {
            $this->timeperiods[$id] = $this->TimeperiodsTable->find()
                ->select([
                    'Timeperiods.id',
                    'Timeperiods.name',
                    'Timeperiods.exclude_timeperiod_id'
                ])
                ->contain([
                    'TimeperiodTimeranges'
                ])
                ->where([
                    'Timeperiods.id' => $id
                ])
                ->disableHydration()
                ->first();
        }

        return $this->timeperiods[$id];
    }

    /**
     * this method converts a day-based time period structure back into a flat, sorted array of continuous minute-based intervals
     *
     * @param array $timeperiod
     * @return array
     */
    private function toIntervals(array $timeperiod): array {
        $intervals = [];
        foreach ($timeperiod['timeperiod_timeranges'] ?? [] as $timerange) {
            $offset = ((int)$timerange['day'] - 1) * self::MINUTES_PER_DAY;
            $start = $offset + $this->parseTime($timerange['start']);
            $end = $offset + $this->parseTime($timerange['end']);
            if ($end > $start) {
                $intervals[] = [
                    'start'  => $start,
                    'end'    => $end,
                    'source' => $timeperiod['name']
                ];
            }
        }

        usort($intervals, function ($a, $b) {
            if ($a['start'] !== $b['start']) {
                return $a['start'] <=> $b['start'];
            }
            return $a['end'] <=> $b['end'];
        });
        return $intervals;
    }

    /**
     * This method processes an array of time intervals (measured in minutes from a starting point).
     * It sorts the intervals chronologically and converts them into a day-based format.
     *
     *
     * @param array $intervals
     * @return array
     */
    private function toRanges(array $intervals): array {
        usort($intervals, function ($a, $b) {
            if ($a['start'] !== $b['start']) {
                //spaceship-operator for sorting: a < b = -1; a == b = 0; a > b = 1
                return $a['start'] <=> $b['start'];
            }
            return $a['end'] <=> $b['end'];
        });

        $ranges = [];
        foreach ($intervals as $interval) {
            $dayIndex = intdiv($interval['start'], self::MINUTES_PER_DAY); //integer division with integer result, no fractional part
            $offset = $dayIndex * self::MINUTES_PER_DAY;
            $ranges[] = [
                'day'        => $dayIndex + 1,
                'start'      => $this->formatTime($interval['start'] - $offset),
                'end'        => $this->formatTime($interval['end'] - $offset),
                'timeperiod' => $interval['source']
            ];
        }
        return $ranges;
    }

    /**
     * Merges overlapping / touching intervals. Sources get lost – only use for calculations.
     *
     * @param array $intervals
     * @return array
     */
    private function union(array $intervals): array {
        usort($intervals, fn($a, $b) => $a['start'] <=> $b['start']);

        $result = [];
        foreach ($intervals as $interval) {
            $last = count($result) - 1;
            if ($last >= 0 && $interval['start'] <= $result[$last]['end']) {
                $result[$last]['end'] = max($result[$last]['end'], $interval['end']);
            } else {
                $result[] = [
                    'start'  => $interval['start'],
                    'end'    => $interval['end'],
                    'source' => ''
                ];
            }
        }
        return $result;
    }

    /**
     * This method performs a set subtraction (or difference) between two sets of time intervals: $A - B$.
     * $a: timeranges of a timeperiod; $b the exlude
     * It removes (cuts out) all time windows in $b from the intervals in $a, returning whatever remains of $a.
     * @param array $a
     * @param array $b
     * @return array
     */
    private function subtract(array $a, array $b): array {
        $cuts = $this->union($b);

        $result = [];
        foreach ($a as $interval) {
            $parts = [$interval];
            foreach ($cuts as $cut) {
                $next = [];
                foreach ($parts as $part) {
                    if ($cut['end'] <= $part['start'] || $cut['start'] >= $part['end']) {
                        $next[] = $part;
                        continue;
                    }
                    if ($cut['start'] > $part['start']) {
                        $next[] = ['start' => $part['start'], 'end' => $cut['start'], 'source' => $part['source']];
                    }
                    if ($cut['end'] < $part['end']) {
                        $next[] = ['start' => $cut['end'], 'end' => $part['end'], 'source' => $part['source']];
                    }
                }
                $parts = $next;
            }
            array_push($result, ...$parts);
        }
        return $result;
    }

    /**
     * This method calculates the intersection (overlap) between two sets of time intervals, $a and $b.
     *
     * @param array $a
     * @param array $b
     * @return array
     */
    private function intersect(array $a, array $b): array {
        $other = $this->union($b);

        $result = [];
        foreach ($a as $interval) {
            foreach ($other as $o) {
                $start = max($interval['start'], $o['start']);
                $end = min($interval['end'], $o['end']);
                if ($end > $start) {
                    $result[] = ['start' => $start, 'end' => $end, 'source' => $interval['source']];
                }
            }
        }
        return $result;
    }

    /**
     * @param string $value "HH:MM"
     * @return int
     */
    private function parseTime($value): int {
        $parts = explode(':', (string)$value);
        return (int)$parts[0] * 60 + (int)($parts[1] ?? 0);
    }

    /**
     * @param int $minutes minutes since 00:00 (1440 = "24:00")
     * @return string
     */
    private function formatTime(int $minutes): string {
        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }
}
