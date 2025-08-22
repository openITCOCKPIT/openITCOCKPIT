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

namespace itnovum\openITCOCKPIT\Core\Views;


use Exception;
use Symfony\Component\Filesystem\Filesystem;

class BarChart {

    /**
     * @var resource
     */
    private $image;

    /**
     * @var int
     */
    private $width;

    /**
     * @var int
     */
    private $padding = 10;

    /**
     * @var int
     */
    private $height;

    /**
     * @var float|int
     */
    private $x;

    /**
     * @var float|int
     */
    private $y;

    /**
     * @var float|int
     */
    private $chartDepth3d;

    /**
     * @var boolean
     */
    private $vertical = false;


    /**
     * BarChart constructor.
     * @param int $width
     * @param int $height
     */
    public function __construct($width = 300, $height = 20) {
        $this->width = $width;
        $this->height = $height;
    }

    /**
     * @param array $chart_data [0 => 10, 1 => 5, 2 => 15]
     * @param bool $vertical
     */
    public function createBarChart($chart_data, $vertical = false) {
        $this->chartDepth3d = 10;
        $this->x = 0;
        $this->y = 5;
        $this->image = imagecreatetruecolor($this->width + $this->padding * 2, $this->height + $this->padding);
        $this->setImageLayout(); //set transparence, colors, fonts, ...
        $this->create($chart_data);
    }


    /**
     * @return resource
     * @throws Exception
     */
    public function getImage() {
        // resource with php5 and php7, GdImage Object with php8
        if (!is_resource($this->image) && gettype($this->image) !== 'object') {
            throw new \Exception('Image not created yet');
        }
        return $this->image;
    }

    private function setImageLayout() {
        imagesavealpha($this->image, true);
        $transparent = imagecolorallocatealpha($this->image, 0, 0, 0, 127);
        imagefill($this->image, 0, 0, $transparent);
    }

    /**
     * @param $chartData
     */
    private function create($chartData) {
        $alpha = 5;
        $colors_host = [
            [0, 200, 81, $alpha], [204, 0, 0, $alpha], [114, 123, 132, $alpha],
        ];
        $colors_service = [
            [0, 200, 81, $alpha], [255, 187, 51, $alpha], [204, 0, 0, $alpha], [114, 123, 132, $alpha],
        ];
        $stateTotal = array_sum($chartData);
        $colors = (sizeof($chartData) === 3) ? $colors_host : $colors_service;
        $chartDataArray = [];
        $end = $this->x;
        foreach ($chartData as $state => $stateCount) {
            $stateDataPer['size'] = $stateCount / $stateTotal * 100;
            if ($stateDataPer['size'] === 0) {
                continue;
            }
            $start = $end;
            $end = $start + $this->width * $stateDataPer['size'] / 100;
            $chartDataArray[$state] = ['color' => $colors[$state], 'start' => $start, 'end' => $end];
        }
        if (!$this->vertical) {
            $this->bar3dHorizontal($chartDataArray);
            $this->barHorizontal($chartDataArray);
        }
    }

    /**
     * @param $chartDataArray
     */
    private function barHorizontal($chartDataArray) {
        foreach ($chartDataArray as $key => $dataArr) {
            $background = imagecolorallocatealpha($this->image, $dataArr['color'][0], $dataArr['color'][1], $dataArr['color'][2], $dataArr['color'][3]);
            imagefilledrectangle($this->image, (int)($dataArr['start']), (int)($this->y), (int)($dataArr['end']), (int)($this->y + $this->height), (int)($background));
        }
    }

    /**
     * @param $chartDataArray
     */
    private function bar3dHorizontal($chartDataArray) {
        foreach ($chartDataArray as $key => $dataArr) {
            $background = imagecolorallocatealpha($this->image, (int)($dataArr['color'][0] * 0.75), (int)($dataArr['color'][1] * 0.75), (int)($dataArr['color'][2] * 0.75), (int)($dataArr['color'][3]));
            //top 3d
            if (version_compare(PHP_VERSION, '8.1.0') >= 0) {
                imagefilledpolygon($this->image, [
                    $dataArr['start'], $this->y,
                    $dataArr['start'] + $this->chartDepth3d, $this->y - $this->chartDepth3d,
                    $dataArr['end'] + $this->chartDepth3d, $this->y - $this->chartDepth3d,
                    $dataArr['end'], $this->y,
                ], $background);
            } else {
                imagefilledpolygon($this->image, [
                    $dataArr['start'], $this->y,
                    $dataArr['start'] + $this->chartDepth3d, $this->y - $this->chartDepth3d,
                    $dataArr['end'] + $this->chartDepth3d, $this->y - $this->chartDepth3d,
                    $dataArr['end'], $this->y,
                ], 4, $background);
            }

            //left 3d
            if (version_compare(PHP_VERSION, '8.1.0') >= 0) {
                imagefilledpolygon($this->image, [
                    $dataArr['end'], $this->y + $this->height,
                    $dataArr['end'], $this->y,
                    $dataArr['end'] + $this->chartDepth3d, $this->y - $this->chartDepth3d,
                    $dataArr['end'] + $this->chartDepth3d, $this->y + $this->height - $this->chartDepth3d,
                ], $background);
            } else {
                imagefilledpolygon($this->image, [
                    $dataArr['end'], $this->y + $this->height,
                    $dataArr['end'], $this->y,
                    $dataArr['end'] + $this->chartDepth3d, $this->y - $this->chartDepth3d,
                    $dataArr['end'] + $this->chartDepth3d, $this->y + $this->height - $this->chartDepth3d,
                ], 4, $background);
            }
        }
    }

    /**
     * @param $chartData
     * @return string
     * @throws Exception
     */
    public static function createBarChartOnDisk($chartData) {
        $BarChart = new self();
        $BarChart->createBarChart($chartData);

        if (!is_dir(WWW_ROOT . 'img' . DS . 'charts')) {
            $fs = new Filesystem();
            $fs->mkdir(WWW_ROOT . 'img' . DS . 'charts');
        }
        $filepath = WWW_ROOT . 'img' . DS . 'charts';
        $filename = uniqid() . '.png';

        $image = $BarChart->getImage();
        imagepng($image, $filepath . DS . $filename, 0);
        imagedestroy($image);

        return $filename;
    }
}
