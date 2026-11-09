<?php
// Copyright (C) <2012>  <it-novum GmbH>
//
// Licensed under The MIT License
//

declare(strict_types=1);

namespace PuppeteerPdf;

use Cake\Core\BasePlugin;
use Cake\Core\PluginApplicationInterface;
use Cake\Http\ServerRequest;

/**
 * Plugin for PuppeteerPdf
 */
class PuppeteerPdfPlugin extends BasePlugin {
    /**
     * Load routes or not
     *
     * @var bool
     */
    protected bool $routesEnabled = false;

    /**
     * Enable middleware
     *
     * @var bool
     */
    protected bool $middlewareEnabled = false;

    /**
     * Console middleware
     *
     * @var bool
     */
    protected bool $consoleEnabled = false;

    /**
     * @inheritDoc
     */
    public function bootstrap(PluginApplicationInterface $app): void {
        /**
         * Add a request detector named "pdf" to check whether the request was for a PDF,
         * either through accept header or file extension
         *
         * @link https://book.cakephp.org/4/en/controllers/request-response.html#checking-request-conditions
         */
        ServerRequest::addDetector(
            'pdf',
            [
                'accept' => ['application/pdf'],
                'param'  => '_ext',
                'value'  => 'pdf',
            ],
        );
    }
}
