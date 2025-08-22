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
 * Class ErrorPdf
 * @package itnovum\openITCOCKPIT\Core\NodeJS
 */
class ErrorPdf {

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
    public function __construct() {
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
    public function getPdfAsStream() {
        $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('openITCOCKPIT');
        $pdf->SetTitle('Error while creating PDF file');
        $pdf->SetSubject('Error while creating PDF file');

        // remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        // set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);

        // set auto page breaks
        $pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);

        // set font
        $pdf->SetFont('helvetica', '', 20);

        // add a page
        $pdf->AddPage();

        $pdf->Write(0, $this->headline, '', 0, 'C', true, 0, false, false, 0);
        $pdf->Write(10, $this->errorText, '', 0, 'C', true, 0, false, false, 0);

        return $pdf->Output('error.pdf' /* gets ignored */, 'S');
    }

}

