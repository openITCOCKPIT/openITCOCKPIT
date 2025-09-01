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

class HostAndServiceSummaryIcon {

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
    private $minWidth = 32;

    /**
     * @var int
     */
    private $sizeInnerCircle;

    /**
     * @var int
     */
    private $sizeOuterCircle;

    /**
     * @var int
     */
    private $sizeBorderInnerCircle;

    /**
     * @var int
     */
    private $sizeBordeOuterCircle;

    /**
     * @var int
     */
    private $center;

    /**
     * @var int
     */
    private $border = 3;

    /**
     * @var float
     * 90°    -> Radians 1.58
     */
    private $CHART_START;

    /**
     * @var float
     * 360°   -> Radians 6.38
     *
     */
    private $CHART_END;

    public function __construct($width) {
        $this->width = (int)$width;
        if ($this->width < $this->minWidth) {
            $this->width = $this->minWidth;
        }
        $this->center = $this->width / 2 - $this->border;

        $this->sizeInnerCircle = $this->center - $this->border;
        $this->sizeOuterCircle = $this->sizeInnerCircle * 2;

        $this->sizeBorderInnerCircle = $this->sizeInnerCircle + $this->border;
        $this->sizeBordeOuterCircle = $this->sizeOuterCircle + $this->border;

        $this->CHART_START = deg2rad(90); //90°    Radians 1.58
        $this->CHART_END = deg2rad(360);  //360°   Radians 6.38
    }

    /**
     * @param $bitMaskHostState
     * @param $bitMaskServiceState
     */
    public function createSummaryIcon($bitMaskHostState, $bitMaskServiceState) {

        $this->image = imagecreatetruecolor($this->width, $this->width);
        $this->setImageLayout(); //set transparency, colors, fonts, ...
        $this->addServiceCircle($bitMaskServiceState);
        $this->addHostCircle($bitMaskHostState);
        $this->addHostAndServiceIcons();
    }

    /**
     * @return resource
     * @throws Exception
     */
    public function getImage() {
        // resource with php5 and php7, GdImage Object with php8
        if (!is_resource($this->image) && gettype($this->image) !== 'object') {
            throw new Exception('Image not created yet');
        }
        return $this->image;
    }

    private function setImageLayout() {
        imagealphablending($this->image, true);
        imagesavealpha($this->image, true);
        $transparent = imagecolorallocatealpha($this->image, 0, 0, 0, 127);
        imagefill($this->image, 0, 0, $transparent);
    }

    private function addHostCircle($bitMaskHostState) {
        $colors = $this->getDefaultColors();

        imageSmoothArc( // <-- circle border
            $this->image,
            $this->center,
            $this->center,
            $this->sizeBorderInnerCircle,
            $this->sizeBorderInnerCircle,
            $colors['lightgray'],
            0,
            $this->CHART_END
        );

        $statusHostUp = 1 << 0;
        $statusHostDown = 1 << 1;
        $statusHostUnreachable = 1 << 2;
        if ($bitMaskHostState & $statusHostUnreachable) {
            $hostStateColorArray[] = $colors['default'];
        }
        if ($bitMaskHostState & $statusHostDown) {
            $hostStateColorArray[] = $colors['danger'];
        }
        if ($bitMaskHostState & $statusHostUp) {
            $hostStateColorArray[] = $colors['success'];
        }

        if (empty($hostStateColorArray)) {
            $hostStateColorArray[] = $colors['primary'];
        }
        $sizeOfSegments = sizeof($hostStateColorArray);

        $start = $this->CHART_START;
        $PiGap = 2 * M_PI / $sizeOfSegments;

        if ($sizeOfSegments === 1) {
            $start = 0;
            $PiGap = 2 * M_PI;
        }

        foreach ($hostStateColorArray as $key => $colorArray) {
            $end = $start + $PiGap;
            if ($end > 2 * M_PI) {
                $end = M_PI / 2;
            }
            imageSmoothArc(
                $this->image,
                $this->center,
                $this->center,
                $this->sizeInnerCircle,
                $this->sizeInnerCircle,
                $colorArray,
                $start,
                $end
            );
            $start = $end;
        }
    }

    private function addServiceCircle($bitMaskServiceState) {
        $colors = $this->getDefaultColors();

        $statusServiceOk = 1 << 0;
        $statusServiceWarning = 1 << 1;
        $statusServiceCritical = 1 << 2;
        $statusServiceUnknown = 1 << 3;

        if ($bitMaskServiceState & $statusServiceUnknown) {
            $serviceStateColorArray[] = $colors['default'];
        }
        if ($bitMaskServiceState & $statusServiceCritical) {
            $serviceStateColorArray[] = $colors['danger'];
        }
        if ($bitMaskServiceState & $statusServiceWarning) {
            $serviceStateColorArray[] = $colors['warning'];
        }
        if ($bitMaskServiceState & $statusServiceOk) {
            $serviceStateColorArray[] = $colors['success'];
        }
        if (empty($serviceStateColorArray)) {
            $serviceStateColorArray[] = $colors['primary'];
        }

        $sizeOfSegments = sizeof($serviceStateColorArray);

        $start = $this->CHART_START;
        $PiGap = 2 * M_PI / $sizeOfSegments;

        if ($sizeOfSegments === 1) {
            $start = 0;
            $PiGap = 2 * M_PI;
        }

        foreach ($serviceStateColorArray as $key => $colorArray) {
            $end = $start + $PiGap;
            if ($end > 2 * M_PI) {
                $end = M_PI / 2;
            }
            imageSmoothArc(
                $this->image,
                $this->center,
                $this->center,
                $this->sizeOuterCircle,
                $this->sizeOuterCircle,
                $colorArray,
                $start,
                $end
            );
            $start = $end;
        }
    }

    private function getDefaultColors() {
        return [
            'success'   => [2, 184, 92, 1],
            'warning'   => [240, 173, 78, 1],
            'danger'    => [217, 83, 79, 1],
            'default'   => [183, 183, 183, 1],
            'primary'   => [66, 139, 202, 1],
            'lightgray' => [220, 220, 220, 1] //for circle border

        ];
    }

    private function addHostAndServiceIcons() {
        if ($this->width < 90) {
            return;
        }
        $white = imagecolorallocatealpha($this->image, 255, 255, 255, 70);
        ImageTTFText(
            $this->image,
            (int)($this->sizeInnerCircle / 5),
            0,
            intval($this->center - ($this->sizeInnerCircle / 2) + $this->border * 2),
            intval($this->center + $this->sizeInnerCircle / 10),
            $white,
            WWW_ROOT . "/node_modules/font-awesome/fonts/fontawesome-webfont.ttf",
            '&#xf108;'
        );
        ImageTTFText(
            $this->image,
            (int)($this->sizeInnerCircle / 5),
            0,
            intval($this->center - $this->sizeOuterCircle / 2 + $this->border * 2),
            intval($this->center + $this->sizeInnerCircle / 10),
            $white,
            WWW_ROOT . "/node_modules/font-awesome/fonts/fontawesome-webfont.ttf",
            '&#xf013;'
        );
    }
}
