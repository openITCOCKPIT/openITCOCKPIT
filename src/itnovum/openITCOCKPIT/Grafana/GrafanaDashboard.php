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


use App\itnovum\openITCOCKPIT\Grafana\GrafanaTooltip;

class GrafanaDashboard {

    /**
     * @var string
     */
    private $title;

    /**
     * @var int
     * @see GrafanaTooltip::ALL
     */
    private $graphTooltip = GrafanaTooltip::DEFAULT;

    /**
     * @var array
     */
    private $rows = [];

    /**
     * @var bool
     */
    private $editable = false;


    /**
     * @var array
     */
    private $tags = [];

    private $uid = null;

    /**
     * @var array
     */

    private $grafanaDashboardDataArray = [
        'annotations'          => [
            'list' => []
        ],
        "editable"             => false, // testing true
        "fiscalYearStartMonth" => 0,
        "graphTooltip"         => GrafanaTooltip::DEFAULT,
        "id"                   => null,
        "links"                => [],
        "liveNow"              => false,
        "panels"               => [],


        "uid"        => null,
        "title"      => "",
        "tags"       => [],
        //"style" => "light",
        "timezone"   => "browser",
        "time"       => [
            "from" => "now-6h",
            "to"   => "now"
        ],
        "timepicker" => [
            "refresh_intervals" => [
                "5s",
                "10s",
                "30s",
                "1m",
                "5m",
                "15m",
                "30m",
                "1h",
                "2h",
                "1d"
            ],
            "time_options"      => [
                "5m",
                "15m",
                "1h",
                "6h",
                "12h",
                "24h",
                "2d",
                "7d",
                "30d"
            ],
        ],
        "templating" => [
            "list" => []
        ],
        "rows"       => [
            //Insert rows here
        ],
        "version"    => 0,
    ];

    /**
     * @return string
     */
    public function getGrafanaDashboardJson() {
        $this->grafanaDashboardDataArray['title'] = $this->title;
        $this->grafanaDashboardDataArray['rows'] = $this->rows;
        $this->grafanaDashboardDataArray['editable'] = $this->editable;
        $this->grafanaDashboardDataArray['tags'] = $this->tags;
        $this->grafanaDashboardDataArray['uid'] = $this->uid;

        // Decide some things basedon the setup.
        $this->grafanaDashboardDataArray['graphTooltip'] = $this->graphTooltip;
        return json_encode([
            'dashboard' => $this->grafanaDashboardDataArray,
            'overwrite' => true
            /*'inputs' => $additional*/
        ], JSON_PRETTY_PRINT);
    }

    /**
     * @param string $title
     */
    public function setTitle($title) {
        $this->title = $title;
    }

    public function setUid($uid) {
        $this->uid = $uid;
    }

    public function setTooltip(int $graphTooltip) {
        if (!in_array($graphTooltip, GrafanaTooltip::ALL, true)) {
            return;
        }
        $this->graphTooltip = $graphTooltip;
    }

    public function addRow(GrafanaRow $grafanaRow) {
        $this->rows[] = $grafanaRow->getRowAsArray();
    }

    public function setTags($tags) {
        $this->tags[] = $tags;
    }

    public function setAutoRefresh($value) {
        //Possible values: 5s, 10s, 30s, 1m, 5m 15m, 30m, 1h, 2h, 1d
        $this->grafanaDashboardDataArray['refresh'] = $value;
    }

    public function setTimeRange(string $value) {
        $this->grafanaDashboardDataArray['time'] = [
            "from" => $value,
            "to"   => "now"
        ];
    }

    /**
     * @param bool $editable
     */
    public function setEditable($editable) {
        $this->editable = $editable;
    }
}
