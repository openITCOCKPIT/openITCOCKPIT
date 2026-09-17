<?php
// Copyright (C) <2012>  <it-novum GmbH>
//
// Licensed under The MIT License
//


namespace PuppeteerPdf\Pdf;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\RequestOptions;
use itnovum\openITCOCKPIT\Core\NodeJS\ErrorPdf;

class PuppeteerClient {

    /**
     * @var Client
     */
    private $Client;

    /**
     * @var string
     */
    private $address = 'http://127.0.0.1:7084/';

    public function __construct() {
        $address = env('OITC_PUPPETEER_ADDRESS', null);
        if (!empty($address)) {
            $this->address = $address;
        }

        $this->Client = new Client([
            'base_uri' => $this->address,
            'proxy'    => [
                'http'  => '',
                'https' => ''
            ]
        ]);
    }

    /**
     * @param string html
     * @return string binary PDF file
     */
    public function html2pdf($html) {
        try {
            $response = $this->Client->post('/pdf', [
                RequestOptions::JSON => [
                    'html'     => $html,

                    // Settings gets directly passed to page.pdf() so you can add any settings you want from
                    // https://pptr.dev/#?product=Puppeteer&version=v13.7.0&show=api-pagepdfoptions
                    'settings' => [
                        'format'          => 'A4',
                        'width'           => null,
                        'height'          => null,
                        'landscape'       => false,
                        'printBackground' => true,
                    ]
                ]
            ]);
            return $response->getBody()->getContents();
        } catch (ClientException $e) {
            $response = $e->getResponse();
            $ErrorPdf = new ErrorPdf();
            $ErrorPdf->setHeadline(sprintf(
                'Error: %s %s',
                $response->getStatusCode(),
                $response->getReasonPhrase()
            ));
            //debug(strip_tags($response->getBody()->getContents()));
            $ErrorPdf->setErrorText($response->getBody()->getContents());

            return $ErrorPdf->getPdfAsStream();
        } catch (ConnectException $e) {
            $ErrorPdf = new ErrorPdf();
            $ErrorPdf->setHeadline(sprintf(
                'Error: Could not connect'
            ));
            $ErrorPdf->setErrorText($e->getMessage());
            return $ErrorPdf->getPdfAsStream();
        } catch (\Exception $e) {
            $ErrorPdf = new ErrorPdf();
            $ErrorPdf->setHeadline(sprintf(
                'Unknown error.'
            ));
            $ErrorPdf->setErrorText($e->getMessage());
            return $ErrorPdf->getPdfAsStream();
        }
    }
}
