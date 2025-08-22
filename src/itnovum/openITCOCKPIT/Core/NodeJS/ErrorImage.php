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

namespace itnovum\openITCOCKPIT\Core\NodeJS;

/**
 * Class ErrorImage
 * @package itnovum\openITCOCKPIT\Core\NodeJS
 */
class ErrorImage {

    /**
     * @var int
     */
    private $width = 300;

    /**
     * @var int
     */
    private $height = 150;

    /**
     * @var string
     */
    private $headline;

    /**
     * @var string
     */
    private $errorText;

    /**
     * ErrorImage constructor.
     * @param int $width
     * @param int $height
     */
    public function __construct($width = 300, $height = 150) {
        $this->width = (int)$width;
        $this->height = (int)$height;
    }

    /**
     * @param mixed $headline
     */
    public function setHeadline($headline) {
        $this->headline = $headline;
    }

    /**
     * @param mixed $errorText
     */
    public function setErrorText($errorText) {
        $this->errorText = $errorText;
    }

    /**
     * @return bool|string
     */
    public function getImageAsPngStream() {
        $fileName = md5(rand() . time() . rand()) . '.png';
        $tmp = ROOT . DS . 'tmp';
        if (!is_writable($tmp)) {
            $tmp = '/tmp';
        }
        $fullImagePath = $tmp . DS . $fileName;


        $img = imagecreatetruecolor($this->width, $this->height);
        imagesavealpha($img, true);

        $background = imagecolorallocatealpha($img, 232, 168, 78, 0);
        $textColor = imagecolorallocate($img, 0, 0, 0);
        imagefill($img, 0, 0, $background);

        imagestring($img, 5, 5, 5, sprintf('Chart render server returned with an error.'), $textColor);
        imagestring($img, 5, 5, 25, $this->headline, $textColor);

        $y = 25;
        foreach (str_split($this->errorText, 80) as $line) {
            $y = $y + 15;
            imagestring($img, 5, 5, $y, $line, $textColor);
        }

        imagepng($img, $fullImagePath); //Save image to disk, because php...
        imagedestroy($img);

        $binaryData = file_get_contents($fullImagePath); //Read image to get binary data
        unlink($fullImagePath);
        return $binaryData;
    }

}

