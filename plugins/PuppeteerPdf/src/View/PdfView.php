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

//
// This code is based on the work of FriendsOfCake / CakePdf
// Many thanks!
// https://github.com/FriendsOfCake/CakePdf/blob/master/src/View/PdfView.php
//
// Licensed under The MIT License
//

declare(strict_types=1);

namespace PuppeteerPdf\View;

use Cake\Core\Configure;
use Cake\Event\EventManager;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\View\View;
use PuppeteerPdf\Pdf\PdfRenderer;

class PdfView extends View {
    /**
     * The subdirectory.  PDF views are always in pdf.
     *
     * @var string|null
     */
    protected string $subDir = 'pdf';

    /**
     * The name of the layouts subfolder containing layouts for this View.
     *
     * @var string|null
     */
    protected string $layoutPath = 'pdf';

    /**
     * Default config options.
     *
     * @var array
     */
    protected array $_defaultConfig = [
        'pdfConfig' => [],
    ];

    /**
     * Constructor
     *
     * @param \Cake\Http\ServerRequest $request Request instance.
     * @param \Cake\Http\Response $response Response instance.
     * @param \Cake\Event\EventManager $eventManager Event manager instance.
     * @param array $viewOptions View options. See View::$_passedVars for list of
     *   options which get set as class properties.
     *
     * @throws \Exception
     */
    public function __construct(
        ?ServerRequest $request = null,
        ?Response      $response = null,
        ?EventManager  $eventManager = null,
        array          $viewOptions = []
    ) {
        $this->setConfig('pdfConfig', (array)Configure::read('PuppeteerPdf'));

        parent::__construct($request, $response, $eventManager, $viewOptions);

        if (isset($viewOptions['templatePath']) && $viewOptions['templatePath'] === 'Error') {
            $this->subDir = '';
            $this->layoutPath = '';

            return;
        }

        $this->response = $this->response->withType('pdf');

        $pdfConfig = $this->getConfig('pdfConfig');
        if (empty($pdfConfig)) {
            throw new \Exception('No PDF config set. Use ViewBuilder::setOption(\'pdfConfig\', $config) to do so.');
        }

        $this->renderer($pdfConfig);
    }


    /**
     * Mime-type this view class renders as.
     *
     * @return string The CSV content type.
     */
    public static function contentType(): string {
        return 'application/pdf';
    }

    /**
     * Return PdfRenderer instance, optionally set engine to be used
     *
     * @param array $config Array of pdf configs. When empty PdfRenderer instance will be returned.
     * @return PdfRenderer|null
     */
    public function renderer(?array $config = null): ?PdfRenderer {
        if ($config !== null) {
            $this->_renderer = new PdfRenderer($config);
        }

        return $this->_renderer;
    }

    /**
     * Render a Pdf view.
     *
     * @param string $template The view being rendered.
     * @param bool|string|null $layout The layout being rendered.
     * @return string The rendered view.
     */
    public function render(?string $template = null, string|false|null $layout = null): string {
        $content = parent::render($template, $layout);

        $type = $this->response->getType();
        if ($type === 'text/html') {
            return $content;
        }

        $renderer = $this->renderer();

        if ($renderer === null) {
            $this->response = $this->response->withType('html');

            return $content;
        }

        if ($this->getConfig('pdfConfig.filename') || $this->getConfig('pdfConfig.download')) {
            $this->response = $this->response->withDownload($this->getFilename());
        }

        $this->Blocks->set('content', $renderer->output($content));

        return $this->Blocks->get('content');
    }

    /**
     * Get or build a filename for forced download
     *
     * @return string The filename
     */
    public function getFilename(): string {
        $filename = $this->getConfig('pdfConfig.filename');
        if ($filename) {
            return $filename;
        }

        $id = current($this->request->getParam('pass'));

        return strtolower($this->getTemplatePath()) . $id . '.pdf';
    }
}
