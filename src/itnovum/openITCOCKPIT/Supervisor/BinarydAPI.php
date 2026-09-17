<?php
// Copyright (C) <2012>  <it-novum GmbH>
//
// Licensed under The MIT License
//


namespace App\itnovum\openITCOCKPIT\Supervisor;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\RequestOptions;
use itnovum\openITCOCKPIT\Core\NodeJS\ErrorPdf;

class BinarydAPI {

    /**
     * @var Client
     */
    private $Client;

    public function __construct(string $address) {
        $this->Client = new Client([
            'base_uri' => $address,
            'proxy'    => [
                'http'  => '',
                'https' => ''
            ]
        ]);
    }

    /**
     * Executes the command and returns the RAW output
     *
     * @param string $programName
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function execute(string $programName) {
        $response = $this->Client->get('/' . $programName);
        return $response->getBody()->getContents();
    }


    /**
     * Executes the command via the /json/ API of binaryd
     * This returns the output and return code
     *
     * @param string $programName
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function executeJson(string $programName) {
        $response = $this->Client->get('/json/' . $programName);
        $result = $response->getBody()->getContents();
        return json_decode($result, true);
    }
}
