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

namespace itnovum\openITCOCKPIT\Core\System;


class FileUploadSize {

    private $maxSize = 0;
    private $unit = '';

    public function __construct() {
        $maxPostSize = $this->parseSize(ini_get('post_max_size'));
        $maxUploadSize = $this->parseSize(ini_get('upload_max_filesize'));

        $maxPostSize = $this->convertToMegabyte($maxPostSize);
        $maxUploadSize = $this->convertToMegabyte($maxUploadSize);

        if ($maxUploadSize['value'] === 0) {
            //No limit for max upload size
            $this->maxSize = $maxPostSize['value'];
            $this->unit = $maxPostSize['unit'];
            return;
        }

        if ($maxUploadSize['value'] < $maxPostSize['value']) {
            $this->maxSize = $maxUploadSize['value'];
            $this->unit = $maxUploadSize['unit'];
            return;
        }

        $this->maxSize = $maxPostSize['value'];
        $this->unit = $maxPostSize['unit'];
    }

    /**
     * @param array $iniValue
     * @return array
     */
    public function parseSize($iniValue) {
        $value = '';
        $unit = '';
        foreach (str_split($iniValue) as $char) {
            if (is_numeric($char)) {
                $value .= $char;
            } else {
                $unit = $char;
            }
        }

        return [
            'value' => (int)$value,
            'unit'  => strtoupper($unit)
        ];
    }

    /**
     * @param array $parsedValue
     * @return array
     */
    public function convertToMegabyte($parsedValue) {
        switch ($parsedValue['unit']) {
            case 'B':
                $parsedValue['value'] = $parsedValue['value'] / 1024 / 1024 / 1024;
                break;

            case 'K':
                $parsedValue['value'] = $parsedValue['value'] / 1024 / 1024;
                break;

            case 'G':
                $parsedValue['value'] = $parsedValue['value'] * 1024;
                break;
        }

        return $parsedValue;
    }

    /**
     * @return int
     */
    public function getMaxUploadValue() {
        return $this->maxSize;
    }

    /**
     * @return string
     */
    public function getMaxUploadUnit() {
        return $this->unit;
    }

    /**
     * @return string
     */
    public function getMaxUpoadValueAsString() {
        return $this->maxSize . $this->unit;
    }

    /**
     * @return array
     */
    public function toArray() {
        return [
            'value'  => $this->maxSize,
            'unit'   => $this->unit,
            'string' => $this->getMaxUpoadValueAsString()
        ];
    }

}
