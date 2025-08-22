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

namespace itnovum\openITCOCKPIT\Perfdata;

/*
// Test Examples:
$gauge = [
    'datasource' => [
        'ds'    => 'ms',
        'name'  => 'ms',
        'label' => 'ms',
        'unit'  => 'ms',
        'warn'  => 1000,
        'crit'  => 5000,
        'min'   => 0,
        'max'   => null
    ],
    'data'       => [
        0.001,
        0.002,
        0.003,
        1000,
        100000000000
    ]
];

$gauge = [
    'datasource' => [
        'ds'    => 'd',
        'name'  => 'd',
        'label' => 'd',
        'unit'  => 'd',
        'warn'  => 1000,
        'crit'  => 5000,
        'min'   => 0,
        'max'   => null
    ],
    'data'       => [
        //0.001694444,  // 2,43999936 minuten
        //0.000694444,  // 1 minute
        //0.003694444,  // 5,31999936 minuten,
        1.1574e-6
    ]
];


$UnitScaler = new UnitScaler($gauge);
*/

/**
 * Class UnitScaler
 * @package itnovum\openITCOCKPIT\Perfdata
 *
 */
class UnitScaler {

    /**
     * @var array
     */
    private $gauge;

    /**
     * @var string
     */
    private $unit;

    /**
     * Max value in perfdata array
     * we use to know if scaling is required or not
     * @var mixed
     */
    private $maxValue = null;

    public function __construct($gauge) {
        $this->gauge = $gauge;
        $this->unit = $gauge['datasource']['unit'];
        if (!empty($gauge['data'])) {
            $this->maxValue = max($gauge['data']);
        }
    }

    public function scale() {
        if ($this->maxValue === null) {
            return $this->gauge;
        }

        $targetUnit = $this->getTargetUnit();
        if ($targetUnit === false) {
            return $this->gauge;
        }

        if ($targetUnit['factor'] === 1) {
            //Nothing to scale
            return $this->gauge;
        }

        foreach ($this->gauge['data'] as $timestamp => $value) {
            $this->gauge['data'][$timestamp] = $this->shiftValue($value, $targetUnit);
        }

        foreach (['min', 'max', 'warn', 'crit'] as $key) {
            if (!empty($this->gauge['datasource'][$key])) {
                // todo: add support for ranges !
                // At the moment, the openITCOCKPIT frontend does not support to display range thresholds in the charts / graphs
                // For this reason, also the UnitScaler does not support it - at least for now.
                // The workaround is, to cast the thresholds "10:50" to a float to only get 10
                $value = floatval($this->gauge['datasource'][$key]);
                $this->gauge['datasource'][$key] = $this->shiftValue($value, $targetUnit);
            }
        }


        $this->gauge['datasource']['unit'] = $targetUnit['unit'];
        return $this->gauge;
    }

    /**
     * @param string $unit
     * @return array
     */
    public function scaleToUnit(?string $forcedUnit) {
        if ($this->maxValue === null) {
            return $this->gauge;
        }

        if ($this->unit === $forcedUnit) {
            return $this->gauge;
        }

        $targetUnit = $this->getForcedTargetUnit($forcedUnit);
        if ($targetUnit === false) {
            return $this->gauge;
        }

        if ($targetUnit['factor'] === 1) {
            //Nothing to scale
            return $this->gauge;
        }

        foreach ($this->gauge['data'] as $timestamp => $value) {
            $this->gauge['data'][$timestamp] = $this->shiftValue($value, $targetUnit);
        }

        foreach (['min', 'max', 'warn', 'crit'] as $key) {
            if (!empty($this->gauge['datasource'][$key])) {
                // todo: add support for ranges !
                // At the moment, the openITCOCKPIT frontend does not support to display range thresholds in the charts / graphs
                // For this reason, also the UnitScaler does not support it - at least for now.
                // The workaround is, to cast the thresholds "10:50" to a float to only get 10
                $value = floatval($this->gauge['datasource'][$key]);
                $this->gauge['datasource'][$key] = $this->shiftValue($value, $targetUnit);
            }
        }


        $this->gauge['datasource']['unit'] = $targetUnit['unit'];
        return $this->gauge;
    }

    private function getTargetUnit() {
        $unitDetails = $this->getUnitDetails($this->unit);
        if ($unitDetails === false) {
            return false;
        }

        $maxValue = $this->maxValue;
        $targetUnit = [
            'factor' => 1,
            'type'   => 'division',
            'unit'   => $unitDetails['units'][$unitDetails['index']]['unit'][0]
        ];

        if ($maxValue >= 1 || $maxValue <= -1) {
            // Scales 100000ms to 69.444444 days

            if ($this->isNegativeValue($maxValue)) {
                $maxValue = $maxValue * -1;
            }

            while (isset($unitDetails['units'][$unitDetails['index'] + 1]) && $maxValue >= $unitDetails['units'][$unitDetails['index'] + 1]['factor']) {
                $currentUnit = $unitDetails['units'][$unitDetails['index']];

                $unitDetails['index']++; //shift to the next greater unit
                $nextUnit = $unitDetails['units'][$unitDetails['index']];

                $maxValue = $maxValue / $currentUnit['factor'];
                $unit = $nextUnit['unit'][0];

                $targetUnit['factor'] = $targetUnit['factor'] * $currentUnit['factor'];
                $targetUnit['unit'] = $unit;
                $targetUnit['type'] = 'division';
            }
        } else {
            // Scales 0.000694444 days to 1 minute
            while (isset($unitDetails['units'][$unitDetails['index'] - 1]) && $maxValue < 1 && $maxValue > -1) {
                $currentUnit = $unitDetails['units'][$unitDetails['index']];

                $unitDetails['index']--; //shift to the next smaller unit
                $previousUnit = $unitDetails['units'][$unitDetails['index']];

                $maxValue = $maxValue * $previousUnit['factor'];


                $unit = $previousUnit['unit'][0];

                $targetUnit['factor'] = $targetUnit['factor'] * $previousUnit['factor'];
                $targetUnit['unit'] = $unit;
                $targetUnit['type'] = 'multiplication';
            }
        }

        return $targetUnit;
    }

    /**
     * @param string $forcedUnit
     * @return array|bool
     */
    private function getForcedTargetUnit(string $forcedUnit) {
        $currentUnitDetails = $this->getUnitDetails($this->unit);
        $forcedUnitDetails = $this->getUnitDetails($forcedUnit);
        if ($currentUnitDetails === false || $forcedUnitDetails === false) {
            return false;
        }

        $currentUnitIndex = $currentUnitDetails['index'];
        $forcedUnitIndex = $forcedUnitDetails['index'];

        $targetUnit = [
            'factor' => 1,
            'type'   => 'division',
            'unit'   => $currentUnitDetails['units'][$currentUnitIndex]['unit'][0]
        ];

        if ($currentUnitIndex < $forcedUnitIndex) {
            while ($currentUnitIndex < $forcedUnitIndex) {
                // Scales 100000ms to 69.444444 days (small to large)
                $currentUnit = $currentUnitDetails['units'][$currentUnitIndex];

                $currentUnitIndex++; //shift to the next greater unit
                $nextUnit = $currentUnitDetails['units'][$currentUnitIndex];

                $unit = $nextUnit['unit'][0];
                $targetUnit['factor'] = $targetUnit['factor'] * $currentUnit['factor'];
                $targetUnit['unit'] = $unit;
                $targetUnit['type'] = 'division';
            }
        } else {
            while ($currentUnitIndex > $forcedUnitIndex) {
                // Scales 0.000694444 days to 1 minute (large to small)
                $currentUnitIndex--; //shift to the next smaller unit
                $previousUnit = $currentUnitDetails['units'][$currentUnitIndex];

                $unit = $previousUnit['unit'][0];
                $targetUnit['factor'] = $targetUnit['factor'] * $previousUnit['factor'];
                $targetUnit['unit'] = $unit;
                $targetUnit['type'] = 'multiplication';
            }
        }

        return $targetUnit;
    }

    private function shiftValue($value, array $targetUnit) {
        if ($targetUnit['type'] === 'multiplication') {
            return $value * $targetUnit['factor'];
        }

        return $value / $targetUnit['factor'];
    }

    /**
     * @param string $unit
     * @return array|false
     */
    public function getUnitDetails(?string $unit) {
        if (empty($unit) || $unit === 'k' || $unit === 'M') {
            // for numbers, unit is empty on load.
            // However, the auto refresh will reload the data and forces the unit to be 'k' or 'M'
            $numbers = [
                0 => [
                    'unit'   => [''],
                    'factor' => 1000
                ],
                1 => [
                    'unit'   => ['k'],
                    'factor' => 1000
                ],
                2 => [
                    'unit'   => ['M'],
                    'factor' => 1000
                ]
            ];

            if (empty($unit)) {
                return [
                    'units' => $numbers,
                    'index' => 0
                ];
            }

            foreach ($numbers as $index => $numberUnit) {
                if (in_array($unit, $numberUnit['unit'], true)) {
                    return [
                        'units' => $numbers,
                        'index' => $index
                    ];
                }
            }

        }

        // is time?
        $time = [
            0 => [
                'unit'   => ['ns'],
                'factor' => 1000
            ],
            1 => [
                'unit'   => ['µs', 'us'],
                'factor' => 1000
            ],
            2 => [
                'unit'   => ['ms'],
                'factor' => 1000
            ],
            3 => [
                'unit'   => ['s', 'sec', 'seconds'],
                'factor' => 60
            ],
            4 => [
                'unit'   => ['m', 'min'],
                'factor' => 60
            ],
            5 => [
                'unit'   => ['h'],
                'factor' => 24
            ],
            6 => [
                'unit'   => ['d'],
                'factor' => 365
            ]
        ];

        foreach ($time as $index => $timeUnit) {
            if (in_array($unit, $timeUnit['unit'], true)) {
                return [
                    'units' => $time,
                    'index' => $index
                ];
            }
        }

        // Define SI Units

        // is data (IEC)
        $data = [
            0 => [
                'unit'   => ['B', 'bytes'],
                'factor' => 1024
            ],
            1 => [
                'unit'   => ['KiB'],
                'factor' => 1024
            ],
            2 => [
                'unit'   => ['MiB'],
                'factor' => 1024
            ],
            3 => [
                'unit'   => ['GiB'],
                'factor' => 1024
            ],
            4 => [
                'unit'   => ['TiB'],
                'factor' => 1024
            ],
            5 => [
                'unit'   => ['PiB'],
                'factor' => 1024
            ],
            6 => [
                'unit'   => ['EiB'],
                'factor' => 1024
            ]
        ];
        foreach ($data as $index => $dataUnit) {
            if (in_array($unit, $dataUnit['unit'], true)) {
                return [
                    'units' => $data,
                    'index' => $index
                ];
            }
        }

        // is data (SI)
        $data = [
            0 => [
                'unit'   => ['b'],
                'factor' => 1000
            ],
            1 => [
                'unit'   => ['KB'],
                'factor' => 1000
            ],
            2 => [
                'unit'   => ['MB'],
                'factor' => 1000
            ],
            3 => [
                'unit'   => ['GB'],
                'factor' => 1000
            ],
            4 => [
                'unit'   => ['TB'],
                'factor' => 1000
            ],
            5 => [
                'unit'   => ['PB'],
                'factor' => 1000
            ],
            6 => [
                'unit'   => ['EiB'],
                'factor' => 1000
            ]
        ];
        foreach ($data as $index => $dataUnit) {
            if (in_array($unit, $dataUnit['unit'], true)) {
                return [
                    'units' => $data,
                    'index' => $index
                ];
            }
        }

        // is data rate?
        $dataRates = [
            0 => [
                'unit'   => ['B/s'],
                'factor' => 1000
            ],
            1 => [
                'unit'   => ['KB/s'],
                'factor' => 1000
            ],
            2 => [
                'unit'   => ['MB/s'],
                'factor' => 1000
            ],
            3 => [
                'unit'   => ['GB/s'],
                'factor' => 1000
            ],
            4 => [
                'unit'   => ['TB/s'],
                'factor' => 1000
            ],
            5 => [
                'unit'   => ['EB/s'],
                'factor' => 1000
            ],
            6 => [
                'unit'   => ['EiB'],
                'factor' => 1000
            ]
        ];
        foreach ($dataRates as $index => $dataRate) {
            if (in_array($unit, $dataRate['unit'], true)) {
                return [
                    'units' => $dataRates,
                    'index' => $index
                ];
            }
        }

        // is data rate?
        $dataRates = [
            0 => [
                'unit'   => ['B/s'],
                'factor' => 8
            ],
            1 => [
                'unit'   => ['Bit/s', 'bit/s', 'bps'],
                'factor' => 1000
            ],
            2 => [
                'unit'   => ['kbit/s', 'kbits', 'kbps'],
                'factor' => 1000
            ],
            3 => [
                'unit'   => ['Mbit/s', 'mbit/s', 'mbits', 'Mbps'],
                'factor' => 1000
            ],
            4 => [
                'unit'   => ['Gbit/s', 'gbit/s', 'gbits', 'Gbps'],
                'factor' => 1000
            ],
            5 => [
                'unit'   => ['Tbit/s', 'tbit/s', 'tbits', 'Tbps'],
                'factor' => 1000
            ]
        ];
        foreach ($dataRates as $index => $dataRate) {
            if (in_array($unit, $dataRate['unit'], true)) {
                return [
                    'units' => $dataRates,
                    'index' => $index
                ];
            }
        }

        // is herz ?
        $herzRates = [
            0 => [
                'unit'   => ['Hz', 'hz'],
                'factor' => 1000
            ],
            1 => [
                'unit'   => ['khz'],
                'factor' => 1000
            ],
            2 => [
                'unit'   => ['Mhz', 'mhz'],
                'factor' => 1000
            ],
            3 => [
                'unit'   => ['Ghz', 'ghz'],
                'factor' => 1000
            ],
            4 => [
                'unit'   => ['Thz', 'thz'],
                'factor' => 1000
            ]
        ];
        foreach ($herzRates as $index => $herzRate) {
            if (in_array($unit, $herzRate['unit'], true)) {
                return [
                    'units' => $herzRates,
                    'index' => $index
                ];
            }
        }

        // SI Units
        $unitsToGenerate = [
            'ops',  // Operations per second
            'iops', // IO operations per second
            'rps',  // Reads per per second
            'wps',  // Writes per second

            'W',       // Watt
            'Wh',      // Watthour
            'V',       // Volt
            'volts',   // Prometheus
            'A',       // Amper
            'amperes', // Prometheus
            'C',       // Celsius
            '°C',      // Celsius,
            'celsius', // Prometheus
            'F',       // Farenheit
            '°F',      // Farenheit
            'K',       // Kelvin
            'J',       // Joul
            'joules',  // Prometheus
        ];

        foreach ($unitsToGenerate as $siUnit) {
            $siUnits = [];
            foreach (['n', 'µ', 'm', '', 'k', 'M', 'G'] as $index => $si) {
                $siUnits[$index] = [
                    'unit'   => [$si . $siUnit],
                    'factor' => 1000
                ];
            }

            foreach ($siUnits as $index => $siUnit) {
                if (in_array($unit, $siUnit['unit'], true)) {
                    return [
                        'units' => $siUnits,
                        'index' => $index
                    ];
                }
            }
        }

        // is unit of mass
        $massUnits = [
            0 => [
                'unit'   => ['ng'],
                'factor' => 1000
            ],
            1 => [
                'unit'   => ['µg', 'ug'],
                'factor' => 1000
            ],
            2 => [
                'unit'   => ['mg'],
                'factor' => 1000
            ],
            3 => [
                'unit'   => ['g', 'grams'],
                'factor' => 1000
            ],
            4 => [
                'unit'   => ['kg', 'kilo'],
                'factor' => 1000
            ],
            5 => [
                'unit'   => ['t'],
                'factor' => 1000
            ]
        ];
        foreach ($massUnits as $index => $massUnit) {
            if (in_array($unit, $massUnit['unit'], true)) {
                return [
                    'units' => $massUnits,
                    'index' => $index
                ];
            }
        }

        // is unit of lengths
        $lengthsUnits = [
            0 => [
                'unit'   => ['nm'],
                'factor' => 1000
            ],
            1 => [
                'unit'   => ['µm', 'um'],
                'factor' => 1000
            ],
            2 => [
                'unit'   => ['mm'],
                'factor' => 1000
            ],
            3 => [
                'unit'   => ['cm'],
                'factor' => 10
            ],
            4 => [
                'unit'   => ['m'],
                'factor' => 100
            ],
            5 => [
                'unit'   => ['km'],
                'factor' => 1000
            ]
        ];
        foreach ($lengthsUnits as $index => $lengthsUnit) {
            if (in_array($unit, $lengthsUnit['unit'], true)) {
                return [
                    'units' => $lengthsUnits,
                    'index' => $index
                ];
            }
        }


        return false;
    }

    /**
     * @param $value
     * @return bool
     */
    public function isNegativeValue($value) {
        return $value < 0;
    }

}

