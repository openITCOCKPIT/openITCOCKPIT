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

namespace App;

use App\Authenticator\ApikeyAuthenticator;
use App\Authenticator\oAuthAuthenticator;
use App\Authenticator\SslAuthenticator;
use App\Identifier\ApikeyIdentifier;
use App\Identifier\LdapIdentifier;
use App\Identifier\oAuthIdentifier;
use App\Identifier\PasswordIdentifier;
use App\Identifier\SslIdentifier;
use App\Identity\AppIdentity;
use App\Lib\PluginManager;
use App\Middleware\AppAuthenticationMiddleware;
use App\Middleware\LdapUsergroupIdMiddleware;
use App\Model\Table\SystemsettingsTable;
use App\Policy\RequestPolicy;
use Authentication\AuthenticationService;
use Authentication\AuthenticationServiceInterface;
use Authentication\AuthenticationServiceProviderInterface;
use Authentication\Identifier\AbstractIdentifier;
use Authorization\AuthorizationService;
use Authorization\AuthorizationServiceInterface;
use Authorization\AuthorizationServiceProviderInterface;
use Authorization\Middleware\AuthorizationMiddleware;
use Authorization\Middleware\RequestAuthorizationMiddleware;
use Authorization\Policy\MapResolver;
use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Core\Exception\MissingPluginException;
use Cake\Error\Middleware\ErrorHandlerMiddleware;
use Cake\Http\BaseApplication;
use Cake\Http\Middleware\BodyParserMiddleware;
use Cake\Http\Middleware\CsrfProtectionMiddleware;
use Cake\Http\MiddlewareQueue;
use Cake\Http\ServerRequest;
use Cake\ORM\TableRegistry;
use Cake\Routing\Middleware\AssetMiddleware;
use Cake\Routing\Middleware\RoutingMiddleware;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Application setup class.
 *
 * This defines the bootstrapping logic and middleware layers you
 * want to use in your application.
 */
class Application extends BaseApplication implements AuthenticationServiceProviderInterface, AuthorizationServiceProviderInterface {

    /**
     * {@inheritDoc}
     */
    public function bootstrap(): void {
        // Call parent to load bootstrap from files.
        parent::bootstrap();

        $PluginManager = new PluginManager($this);
        $PluginManager->loadModules();

        $this->addPlugin('Acl', ['bootstrap' => true]);
        $this->addPlugin('Authentication');
        $this->addPlugin('Authorization');

        $this->addPlugin('PuppeteerPdf');

        $this->addPlugin('CsvView');

        if (PHP_SAPI === 'cli') {
            try {
                $this->addPlugin('Bake');
            } catch (MissingPluginException $e) {
                // Do not halt if the plugin is missing
            }

            $this->addPlugin('Migrations');
        }


        /*
         * Only try to load DebugKit in development mode
         * Debug Kit should not be installed on a production system
         */
        if (Configure::read('debug')) {
            //$this->addPlugin('DebugKit');
        }

        $this->addPlugin('Dbml');
    }

    /**
     * Define the routes for an application.
     *
     * Use the provided RouteBuilder to define an application's routing, register scoped middleware.
     *
     * @param \Cake\Routing\RouteBuilder $routes A route builder to add routes into.
     * @return void
     */
    public function routes($routes): void {
        // Register scoped middleware for use in routes.php
        $routes->registerMiddleware('csrf', new CsrfProtectionMiddleware([
            'httponly' => true,
            'secure'   => true
        ]));

        parent::routes($routes);
    }

    /**
     * Returns a service provider instance.
     *
     * @param ServerRequestInterface $request
     * @return AuthenticationServiceInterface
     * @throws \Exception
     */
    public function getAuthenticationService(ServerRequestInterface $request): AuthenticationServiceInterface {
        $service = new AuthenticationService([
            //Do not redirect!
            'unauthenticatedRedirect' => null, //'/users/login',
            'identityClass'           => AppIdentity::class
        ]);
        $fields = [
            AbstractIdentifier::CREDENTIAL_USERNAME => 'email',
            AbstractIdentifier::CREDENTIAL_PASSWORD => 'password'
        ];


        if (Cache::read('isLdapAuth', 'permissions') === null) {
            /** @var SystemsettingsTable $SystemsettingsTable */
            $SystemsettingsTable = TableRegistry::getTableLocator()->get('Systemsettings');
            Cache::write('isLdapAuth', $SystemsettingsTable->isLdapAuth(), 'permissions');
        }
        $isLdapAuth = Cache::read('isLdapAuth', 'permissions');

        // Load Apikey identifier
        $service->loadAuthenticator('Authentication.Apikey', [
            'identifier'   => [
                'Authentication.Apikey' => [
                    'className' => ApikeyIdentifier::class
                ]
            ],
            'className'    => ApikeyAuthenticator::class,
            'queryParam'   => 'apikey',
            'header'       => 'Authorization',
            'apikeyPrefix' => 'X-OITC-API',
        ]);

        // Load SSL identifier
        if (isset($_SERVER['SSL_VERIFIED'])) {
            $service->loadAuthenticator('Authentication.Ssl', [
                'identifier' => [
                    'Authentication.Ssl' => [
                        'className' => SslIdentifier::class
                    ]
                ],
                'className'  => SslAuthenticator::class
            ]);
        }

        // Load oAuth identifier
        if (isset($_GET['code'])) {
            $service->loadAuthenticator('Authentication.oAuth', [
                'identifier' => [
                    'Authentication.oAuth' => [
                        'className' => oAuthIdentifier::class
                    ]
                ],
                'className'  => oAuthAuthenticator::class
            ]);
        }

        $passwordIdentifier = [
            'Authentication.Password' => [
                'className' => PasswordIdentifier::class,
                'fields'    => $fields
            ],
        ];

        if ($isLdapAuth) {
            // Load LDAP identifier
            $passwordIdentifier['Authentication.Ldap'] = [
                'className' => LdapIdentifier::class,
            ];
        }

        // Load the authenticators, you want session first
        $expireAt = new \DateTime();
        $expireAt->setTimestamp(time() + (3600 * 24 * 31)); // In one month
        $service->loadAuthenticator('Authentication.Cookie', [
            'identifier'      => $passwordIdentifier,
            'fields'          => $fields,
            'rememberMeField' => 'remember_me',
            'cookie'          => [
                'expires' => $expireAt
            ]
        ]);
        $service->loadAuthenticator('Authentication.Session', [
            'identifier' => $passwordIdentifier,
        ]);

        // Load identifiers (Username / Password)
        $service->loadAuthenticator('Authentication.Form', [
            'identifier' => $passwordIdentifier,
            'fields'     => $fields,
            'loginUrl'   => '/users/login',
        ]);

        return $service;

    }

    /**
     * @param ServerRequestInterface $request
     * @return AuthorizationServiceInterface
     */
    public function getAuthorizationService(ServerRequestInterface $request): AuthorizationServiceInterface {
        $mapResolver = new MapResolver();
        $mapResolver->map(ServerRequest::class, RequestPolicy::class);
        return new AuthorizationService($mapResolver);
    }

    /**
     * Setup the middleware queue your application will use.
     *
     * @param \Cake\Http\MiddlewareQueue $middlewareQueue The middleware queue to setup.
     * @return \Cake\Http\MiddlewareQueue The updated middleware queue.
     */
    public function middleware($middlewareQueue): MiddlewareQueue {
        $middlewareQueue
            //->add(new CorsMiddleware())

            ->add(new BodyParserMiddleware())

            // Catch any exceptions in the lower layers,
            // and make an error page/response
            ->add(ErrorHandlerMiddleware::class)

            // Handle plugin/theme assets like CakePHP normally does.
            ->add(new AssetMiddleware([
                'cacheTime' => Configure::read('Asset.cacheTime')
            ]))
            // Add routing middleware.
            // Routes collection cache enabled by default, to disable route caching
            // pass null as cacheConfig, example: `new RoutingMiddleware($this)`
            // you might want to disable this cache in case your routing is extremely simple
            ->add(new RoutingMiddleware($this))

            //Add the authentication middleware
            //Response a 403 to .json requests and redirect .html requests to login page
            ->add(new AppAuthenticationMiddleware($this, null, [
                //Only redirect .html requests if login is invalid - no json requests
                'htmlUnauthenticatedRedirect' => '/a/users/login'
            ]))
            ->add(new LdapUsergroupIdMiddleware()) // ITC-2693
            ->add(new AuthorizationMiddleware($this))
            ->add(new RequestAuthorizationMiddleware());

        return $middlewareQueue;
    }
}
