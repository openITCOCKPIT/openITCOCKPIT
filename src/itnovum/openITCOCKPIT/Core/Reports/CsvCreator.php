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

namespace itnovum\openITCOCKPIT\Core\Reports;


class CsvCreator {
    /**
     * @var string
     */
    private $delimiter = ';';
    /**
     * @var string
     */
    private $enclosure = ' ';
    /**
     * @var string
     */
    private $filename = 'Export.csv';
    /**
     * @var array
     */
    private $line = [];
    /**
     * @var null
     */
    private $buffer = null;


    function setPath($path) {
        $this->buffer = fopen($path, 'w');
    }

    function closeFile() {
        fclose($this->buffer);
    }

    function createZip($zipPath, $files) {
        if (file_exists($zipPath)) {
            unlink($zipPath);
        }

        $zipArchive = new \ZipArchive();


        if ($zipArchive->open($zipPath, \ZipArchive::CREATE) !== true) {
            exit('Cant create zip file');
        }

        foreach ($files as $file) {
            $zipArchive->addFile($file, basename($file));
        }
        $zipArchive->close();

        foreach ($files as $file) {
            unlink($file);
        }
        $basename = basename($zipPath, '.zip');
        $zipName = preg_replace("/[^a-zA-Z0-9_]+/", "", $basename) . '.zip';
        return $zipName;
    }

    function clear() {
        $this->line = [];
        $this->buffer = fopen('php://temp/maxmemory:' . (5 * 1024 * 1024), 'r+');
    }

    function addField($value) {
        $this->line[] = $value;
    }

    function endRow() {
        $this->addRow($this->line);
        $this->line = [];
    }

    function addRow($row) {
        fputcsv($this->buffer, $row, $this->delimiter, $this->enclosure);
    }

    function renderHeaders() {
        header('Content-Type: text/csv');
        header("Content-type:application/vnd.ms-excel");
        header("Content-disposition:attachment;filename=" . $this->filename);
    }

    function setFilename($filename) {
        $this->filename = $filename;
        if (strtolower(substr($this->filename, -4)) != '.csv') {
            $this->filename .= '.csv';
        }
    }
}
