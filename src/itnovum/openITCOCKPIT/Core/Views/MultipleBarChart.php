<?php
// Copyright (C) <2015-present>  <it-novum GmbH>
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

namespace itnovum\openITCOCKPIT\Core\Views;


use itnovum\openITCOCKPIT\CakePHP\Folder;

class MultipleBarChart {

    private $img;
    private $width;
    private $height;
    private $maxBars;
    private $scale;
    private $chartDepth3d;
    private $x;
    private $y;
    private $maxLabelLength;
    private $fontFile;


    public function createBarChart($chartData, $maxBars = 10) {
        $filepath = WWW_ROOT . 'img' . DS . 'charts';
        $filename = uniqid() . '.png';
        $tmp_dir = new Folder($filepath, true);

        $width = 870;
        $height = 300;
        $this->img = imagecreatetruecolor($width, $height);
        $this->width = $width * 0.7;
        $this->height = $height * 0.3;
        $this->maxBars = $maxBars;
        $this->scale = $this->height * 0.25;
        $this->chartDepth3d = 5;
        $this->x = 30;
        $this->y = 30;
        $this->maxLabelLength = 25; //hostname as label

        $this->setImageLayout(); //set transparence, colors, fonts, ...
        $this->createChartGrid();
        $this->create($chartData);
        imagepng($this->img, $filepath . DS . $filename, 0);
        imagedestroy($this->img);

        return $filename;
    }

    public function createChartGrid() {
        $colorGray = imagecolorallocate($this->img, 220, 220, 220);
        $colorLightGray = imagecolorallocate($this->img, 250, 250, 250);
        $colorDarkGray = imagecolorallocate($this->img, 180, 180, 180);
        $colorText = imagecolorallocate($this->img, 70, 70, 70);
        $white = imagecolorallocate($this->img, 255, 255, 255);

        $pointsFor3d = [
            $this->x + $this->scale, $this->y - $this->scale,
            $this->x + $this->width + $this->scale, $this->y - $this->scale,
            $this->x + $this->width + $this->scale, $this->y + $this->height - $this->scale,
            $this->x + $this->width, $this->y + $this->height,
            $this->x, $this->y + $this->height,
            $this->x, $this->y,
        ];

        $diff_scale = $this->height / 4;
        if (version_compare(PHP_VERSION, '8.1.0') >= 0) {
            imagefilledpolygon($this->img, $pointsFor3d, $colorGray);
            imagepolygon($this->img, $pointsFor3d, $colorDarkGray);
        } else {
            imagefilledpolygon($this->img, $pointsFor3d, sizeof($pointsFor3d) / 2, $colorGray);
            imagepolygon($this->img, $pointsFor3d, sizeof($pointsFor3d) / 2, $colorDarkGray);
        }

        imagefilledrectangle($this->img, $this->x + $this->scale, $this->y - $this->scale, $this->x + $this->width + $this->scale, $this->y + $this->height - $this->scale, $colorLightGray);
        imagerectangle($this->img, $this->x + $this->scale, $this->y - $this->scale, $this->x + $this->width + $this->scale, $this->y + $this->height - $this->scale, $colorDarkGray);

        for ($i = 0; $i <= 4; $i++) {
            $style = [$colorDarkGray, $colorDarkGray, $colorDarkGray, $colorDarkGray, $colorDarkGray, $white, $white, $white, $white, $white];
            imagesetstyle($this->img, $style);
            imageline($this->img, (int)($this->x + $this->scale), (int)($this->y + ($diff_scale * $i) - $this->scale), (int)($this->x + $this->scale + $this->width), (int)($this->y + ($diff_scale * $i) - $this->scale), (($i === 0 || $i === 4) ? $colorDarkGray : IMG_COLOR_STYLED));
            $style = [$colorDarkGray, $colorDarkGray, $colorDarkGray, $colorDarkGray, $colorDarkGray, $colorGray, $colorGray, $colorGray, $colorGray, $colorGray];
            imagesetstyle($this->img, $style);
            imageline($this->img, (int)$this->x, (int)($this->y + ($diff_scale * $i)), (int)($this->x + $this->scale), (int)($this->y + ($diff_scale * $i) - $this->scale), (($i === 0 || $i === 4) ? $colorDarkGray : IMG_COLOR_STYLED));
            imagettftext($this->img, 8, 0, (int)$this->x - $this->scale, (int)($this->y + ($diff_scale * $i)), $colorText, $this->fontFile, (int)((1 - $diff_scale * $i / $this->height) * 100));
            if ($i == 0) {
                imagettftext($this->img, 8, 0, (int)$this->x - $this->scale, 10, $colorText, $this->fontFile, '%');
            }
        }
    }

    public function setImageLayout() {
        imagesavealpha($this->img, true);
        $transparent = imagecolorallocatealpha($this->img, 0, 0, 0, 127);
        imagefill($this->img, 0, 0, $transparent);
        putenv('GDFONTPATH=/usr/share/fonts/truetype/dejavu/');
        $this->fontFile = 'DejaVuSans';
    }

    public function create($chartData) {
        $alpha = 5;
        $textColor = imagecolorallocate($this->img, 50, 50, 50);
        $colorsHost = [
            [0, 200, 81, $alpha], [204, 0, 0, $alpha], [114, 123, 132, $alpha],
        ];
        $totalWidth = $this->width + $this->scale;
        $gapX = $totalWidth * 0.6 / $this->maxBars;

        for ($i = 0; $i <= 2; $i++) {
            $colors[$i] = [
                'default' => imagecolorallocatealpha($this->img, $colorsHost[$i][0], $colorsHost[$i][1], $colorsHost[$i][2], $colorsHost[$i][3]),
                'top3d'   => imagecolorallocatealpha($this->img, $colorsHost[$i][0] * 0.75, $colorsHost[$i][1] * 0.75, $colorsHost[$i][2] * 0.75, $colorsHost[$i][3]),
                'left3d'  => imagecolorallocatealpha($this->img, $colorsHost[$i][0] * 0.65, $colorsHost[$i][1] * 0.65, $colorsHost[$i][2] * 0.65, $colorsHost[$i][3]),
            ];

        }
        $tmpX = $this->scale * 0.5;
        foreach ($chartData as $hostName => $data) {
            $y1 = $this->height + $this->y;
            $tmpX += $this->x + $this->scale * 0.7;
            $sumAllValues = array_sum($data);
            foreach ($data as $index => $hostData) {
                $dataInPercent = round($data[$index] / $sumAllValues * 100);
                $tmpY2 = round($y1 - ($dataInPercent / 100 * ($this->height)));
                if ($hostData > 0) {
                    imagefilledrectangle($this->img, $tmpX, $y1, $tmpX + $gapX, $tmpY2, $colors[$index]['default']);
                    $pointsFor3dTop = [
                        $tmpX, $tmpY2,
                        $tmpX + $gapX, $tmpY2,
                        $tmpX + $gapX + $this->scale, $tmpY2 - $this->scale,
                        $tmpX + $this->scale, $tmpY2 - $this->scale,
                    ];
                    if (version_compare(PHP_VERSION, '8.1.0') >= 0) {
                        imagefilledpolygon($this->img, $pointsFor3dTop, $colors[$index]['top3d']);
                    } else {
                        imagefilledpolygon($this->img, $pointsFor3dTop, sizeof($pointsFor3dTop) / 2, $colors[$index]['top3d']);
                    }
                    $pointsFor3dLeft = [
                        $tmpX + $gapX, $y1,
                        $tmpX + $gapX + $this->scale, $y1 - $this->scale,
                        $tmpX + $gapX + $this->scale, $tmpY2 - $this->scale,
                        $tmpX + $gapX, $tmpY2,
                    ];
                    if (version_compare(PHP_VERSION, '8.1.0') >= 0) {
                        imagefilledpolygon($this->img, $pointsFor3dLeft, $colors[$index]['left3d']);
                    } else {
                        imagefilledpolygon($this->img, $pointsFor3dLeft, sizeof($pointsFor3dTop) / 2, $colors[$index]['left3d']);
                    }
                }
                $y1 = $tmpY2;
            }
            $hostName = (strlen($hostName) < $this->maxLabelLength) ? $hostName : (substr($hostName, 0, $this->maxLabelLength)) . '...';
            imagettftext($this->img, 8, -90, $tmpX + $this->x / 2, $this->height + $this->y * 1.3, $textColor, $this->fontFile, $hostName);
        }
    }
}
