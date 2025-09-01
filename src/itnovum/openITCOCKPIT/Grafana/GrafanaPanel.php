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

// 2.
//	If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//	under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//	License agreement and license key will be shipped with the order
//	confirmation.


namespace itnovum\openITCOCKPIT\Grafana;


use App\itnovum\openITCOCKPIT\Grafana\GrafanaColorOverrides;
use itnovum\openITCOCKPIT\Perfdata\PerformanceDataSetup;
use itnovum\openITCOCKPIT\Perfdata\Scale;
use itnovum\openITCOCKPIT\Perfdata\ScaleType;

class GrafanaPanel {

    /**
     * @var string
     */
    private $title;

    /**
     * @var array
     */
    private $targets = [];

    /**
     * @var int
     */
    private $panelId;

    /**
     * @var GrafanaOverrides
     */
    private $Overrides;
    /**
     * @var GrafanaColorOverrides
     */
    private $ColorOverrides;

    /**
     * @var GrafanaThresholdCollection
     */
    private $ThresholdCollection;

    /**
     * @var int
     */
    private $span = 6;

    /**
     * @var int
     */
    private $metricCount = 1;

    /**
     * @var string
     */
    private $visualization_type = 'timeseries';

    /**
     * @var string
     */
    private $stacking_mode = 'none';

    /**
     * @var string|null
     * This unit is used for all metrics in the panel
     */
    private $defaultUnit = null;


    /**
     * @var array
     */
    private $panel = [
        "id"         => null,
        "type"       => "timeseries",
        "title"      => "",
        "datasource" => [
            "type" => "datasource",
            "uid"  => "-- Mixed --"
        ],
        "links"      => [],
        "targets"    => [
            //Insert targets here
        ],
        "options"    => [
            "xTickLabelRotation" => 0,
            "xTickLabelSpacing"  => 100,
            "text"               => [
                "titleSize" => 12
            ],
            "barWidth"           => 1,
            "tooltip"            => [
                "mode" => "multi",
                "sort" => "none"
            ],
            "legend"             => [
                "displayMode" => "table",
                "placement"   => "bottom",
                "calcs"       => [
                    "mean",
                    "lastNotNull",
                    "max",
                    "min",
                ]
            ]
        ],

        "thresholds"    => [
            //Insert thresholds here
        ],
        "timeFrom"      => null,
        "timeShift"     => null,
        "fieldConfig"   => [
            "defaults"  => [
                "custom"     => [
                    "drawStyle"         => "line",
                    "lineInterpolation" => "smooth",
                    "barAlignment"      => 0,
                    "lineWidth"         => 1,
                    "fillOpacity"       => 50,
                    "gradientMode"      => "hue",
                    "spanNulls"         => true,
                    "showPoints"        => "never",
                    "pointSize"         => 5,
                    "stacking"          => [
                        "mode"  => "none", //normal, percent, none
                        "group" => "A"
                    ],

                    "axisPlacement"     => "auto",
                    "axisLabel"         => "",
                    "axisColorMode"     => "text",
                    "scaleDistribution" => [
                        "type" => "linear"
                    ],
                    "axisCenteredZero"  => false,
                    "hideFrom"          => [
                        "tooltip" => false,
                        "viz"     => false,
                        "legend"  => false
                    ],
                    "thresholdsStyle"   => [
                        "mode" => "off"
                    ]
                ],
                "color"      => [
                    "mode" => "palette-classic"
                ],
                "unit"       => null,
                "thresholds" => [
                    "steps" => []
                ]
            ],
            "overrides" => [],
            "mappings"  => []

        ],
        "pluginVersion" => "9.0.2"
    ];
    /**
     * @var Scale|null
     */
    private $scale = null;

    /**
     * GrafanaPanel constructor.
     * @param $panelId
     * @param int $span
     */
    public function __construct($panelId, int $span = 6, $metricCount = 1) {
        $this->panelId = $panelId;
        $span = (int)$span;
        if ($span <= 0) {
            $span = 1;
        }

        if ($span > 12) {
            $span = 12;
        }

        $this->span = $span;
        $this->metricCount = $metricCount;
    }

    /**
     * @return array
     */
    public function getPanelAsArray() {
        $this->panel['id'] = $this->panelId;
        $this->panel['title'] = $this->title;
        $this->panel['targets'] = $this->targets;
        $this->panel['span'] = $this->span;

        $this->panel['type'] = $this->visualization_type;
        if ($this->visualization_type === 'bargaugeretro') {
            $this->panel['type'] = 'bargauge';
            $this->panel['options']['displayMode'] = 'lcd';
        }

        if ($this->stacking_mode) {
            $this->panel['fieldConfig']['defaults']['custom']['stacking']['mode'] = $this->stacking_mode;
        }

        if ($this->Overrides->hasOverrides()) {
            $this->panel['fieldConfig']['overrides'] = $this->Overrides->getOverrides();
        }

        $thresholdsAsArray = $this->ThresholdCollection->getThresholds();
        if (!empty($thresholdsAsArray) && empty($this->panel['fieldConfig']['overrides']) && sizeof($this->panel['targets']) === 1) {
            $this->panel['fieldConfig']['defaults']['thresholds'] = [
                'mode' => 'absolute',
                'steps' => $thresholdsAsArray
            ];


            if ($this->getMetricCount() > 1 && in_array($this->visualization_type, ['timeseries'])) {
                // show threshold lines in chart with more than one metric - can be used for all charts if needed
                $this->panel['fieldConfig']['defaults']['custom']['thresholdsStyle'] = [
                    'mode' => 'line'
                ];
            } else {
                $this->panel['fieldConfig']['defaults']['custom']['gradientMode'] = 'scheme';
                $this->panel['fieldConfig']['defaults']['color']['mode'] = 'thresholds';
            }
        }
        if ($this->ColorOverrides->hasOverrides() && in_array($this->visualization_type, ['timeseries', 'bargauge'])) {
            // This overrides the colour that comes from thresholds. Maybe just dont do that if we have thresholds in a timeseries type display?
            $this->panel['fieldConfig']['overrides'] = $this->ColorOverrides->getOverrides();
        }

        if (sizeof($this->panel['targets']) >= 3 || $this->getMetricCount() >= 3) {
            $this->panel['fieldConfig']['defaults']['custom']['fillOpacity'] = 10;
        }

        $this->panel['fieldConfig']['defaults']['unit'] = $this->defaultUnit;

        return $this->panel;
    }

    /**
     * @param $title
     */
    public function setTitle($title) {
        $this->title = $title;
    }

    /**
     * @param $visualizationType
     */
    public function setVisualizationType($visualizationType) {
        $this->visualization_type = $visualizationType;
    }

    /**
     * @param $stackingMode
     */
    public function setStackingMode($stackingMode) {
        $this->stacking_mode = $stackingMode;
    }

    /**
     * @param GrafanaTargetCollection $grafanaTargetCollection
     * @param GrafanaOverrides $Overrides
     * @param GrafanaThresholdCollection $ThresholdCollection
     */
    public function addTargets(
        GrafanaTargetCollection    $grafanaTargetCollection,
        GrafanaOverrides           $Overrides,
        GrafanaColorOverrides      $ColorOverrides,
        GrafanaThresholdCollection $ThresholdCollection
    ) {

        if ($grafanaTargetCollection->canDisplayUnits()) {
            $units = $grafanaTargetCollection->getUnits();
            // Set the first unit as default unit for the panel
            $this->defaultUnit = $units[0] ?? null;
        }

        // If the targets share the same Setup, we'll use the scale from the setup for this panel.
        foreach ($grafanaTargetCollection->getTargets() as $grafanaTarget) {
            $setup = $grafanaTarget->getSetup();
            if ($setup !== null) {
                $this->scale = $setup->scale;
            }
        }

        $this->targets = $grafanaTargetCollection->getTargetsAsArray();
        $this->Overrides = $Overrides;
        $this->ColorOverrides = $ColorOverrides;
        $this->ThresholdCollection = $ThresholdCollection;
    }

    /**
     * @param $metricCount
     * @return void
     */
    public function setMetricCount($metricCount) {
        $this->metricCount = $metricCount;
    }

    /**
     * @return int|mixed
     */
    private function getMetricCount() {
        return $this->metricCount;
    }
}
