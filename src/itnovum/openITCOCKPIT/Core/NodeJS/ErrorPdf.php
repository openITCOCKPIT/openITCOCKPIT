<?php
// Copyright (C) 2015-2025  it-novum GmbH
// Copyright (C) 2025-today AVENDIS GmbH
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

use Com\Tecnick\Pdf\Tcpdf;

/**
 * Class ErrorPdf
 * @package itnovum\openITCOCKPIT\Core\NodeJS
 */
class ErrorPdf {

    /**
     * Page margin in mm
     */
    private const MARGIN = 15;

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
     * Returns a simple PDF document (A4 portrait) containing the headline and the error text.
     * Used as fallback if the Puppeteer service is not able to render the requested PDF.
     *
     * @return string Raw PDF data
     */
    public function getPdfAsStream(): string {
        // Unit mm, unicode, no font subsetting, compressed
        $pdf = new Tcpdf('mm', true, false, true);

        // set document information
        $pdf->setCreator('openITCOCKPIT');
        $pdf->setAuthor('openITCOCKPIT');
        $pdf->setTitle('Error while creating PDF file');
        $pdf->setSubject('Error while creating PDF file');

        // tc-lib-pdf has no default header/footer, so nothing to disable here.
        // Page breaks are handled automatically by addTextCell().
        $pdf->addPage([
            'format'      => 'A4',
            'orientation' => 'P',
            'margin'      => [
                'PL' => self::MARGIN,
                'PR' => self::MARGIN,
                'CT' => self::MARGIN,
                'CB' => self::MARGIN,
            ],
        ]);

        // Headline (helvetica is one of the PDF core fonts and does not need to be embedded)
        $font = $pdf->font->insert($pdf->pon, 'helvetica', '', 20);
        $pdf->page->addContent($font['out']);
        $pdf->addTextCell(
            txt: (string)$this->headline,
            valign: 'T',
            halign: 'C',
            drawcell: false
        );

        // Error text below the headline
        $headlineBox = $pdf->getLastBBox();
        $font = $pdf->font->insert($pdf->pon, 'helvetica', '', 12);
        $pdf->page->addContent($font['out']);
        $pdf->addTextCell(
            txt: (string)$this->errorText,
            posy: ($headlineBox['y'] + $headlineBox['h'] + 10) - self::MARGIN,
            valign: 'T',
            halign: 'C',
            drawcell: false
        );

        return $pdf->getOutPDFString();
    }

}

