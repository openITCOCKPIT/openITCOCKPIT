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

// 2.
//	If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//	under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//	License agreement and license key will be shipped with the order
//	confirmation.

namespace App\Middleware;

use Authentication\AuthenticationServiceInterface;
use Authentication\AuthenticationServiceProviderInterface;
use Authentication\Authenticator\UnauthenticatedException;
use Authentication\Middleware\AuthenticationMiddleware;
use Cake\Core\ContainerInterface;
use Cake\Http\Response;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AppAuthenticationMiddleware extends AuthenticationMiddleware implements MiddlewareInterface {
    /**
     * @var string|null
     */
    private $htmlUnauthenticatedRedirect = null;

    /**
     * Constructor
     *
     * @param \Authentication\AuthenticationServiceInterface|\Authentication\AuthenticationServiceProviderInterface $subject Authentication service or application instance.
     * @param \Cake\Core\ContainerInterface|null $container The container instance from the application.
     */
    public function __construct(
        AuthenticationServiceInterface|AuthenticationServiceProviderInterface $subject,
        ?ContainerInterface                                                   $container = null,
        array                                                                 $config = [],
    ) {
        parent::__construct($subject, $container);

        if (isset($config['htmlUnauthenticatedRedirect'])) {
            $this->htmlUnauthenticatedRedirect = $config['htmlUnauthenticatedRedirect'];
        }
    }

    /**
     * Callable implementation for the middleware stack.
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
        try {
            $response = parent::process($request, $handler);
        } catch (UnauthenticatedException $e) {
            $header = $request->getHeader('Accept');
            if (is_array($header) && isset($header[0])) {
                $header = $header[0];
            } else {
                $header = '';
            }

            $isJson = $header === 'application/json';
            if (!$isJson) {
                //Search URL for .json
                $url = $request->getUri();
                $isJson = mb_substr($url->getPath(), -5) === '.json';
            }
            if ($isJson) {
                //Do not redirect json requests
                $response = new Response();
                $response = $response->withStatus(401);
                return $response;
            }
            if ($this->htmlUnauthenticatedRedirect) {
                //Redirct .html requests
                return new RedirectResponse($this->htmlUnauthenticatedRedirect);
            }
            //Not json request or no htmlUnauthenticatedRedirect given so throw exception
            throw $e;
        }
        return $response;
    }
}
