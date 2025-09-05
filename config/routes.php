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

use Cake\Http\Middleware\CsrfProtectionMiddleware;
use Cake\Routing\Route\InflectedRoute;
use Cake\Routing\RouteBuilder;

/*
 * The default class to use for all routes
 *
 * The following route classes are supplied with CakePHP and are appropriate
 * to set as the default:
 *
 * - Route
 * - InflectedRoute
 * - DashedRoute
 *
 * If no call is made to `Router::defaultRouteClass()`, the class used is
 * `Route` (`Cake\Routing\Route\Route`)
 *
 * Note that `Route` does not do any inflections on URLs which will result in
 * inconsistently cased URLs when used with `:plugin`, `:controller` and
 * `:action` markers.
 */
/** @var \Cake\Routing\RouteBuilder $routes */
// If you need lowercased and underscored URLs while migrating from a CakePHP 2.x application, you can instead use the InflectedRoute class.
// https://book.cakephp.org/3/en/development/routing.html#route-elements
$routes->setRouteClass(InflectedRoute::class);

//CakePHP 4 default
//$routes->setRouteClass(DashedRoute::class);

// CakePHP 5              ✅      ✅     ✅     ✅     ✅
$routes->setExtensions(['json', 'html', 'pdf', 'png', 'zip']);

$routes->scope('/', function (RouteBuilder $builder) {
    // Register scoped middleware for in scopes.
    $csrf = new CsrfProtectionMiddleware([
        'httponly' => true,
        'secure'   => true
    ]);

    // Token check will be skipped when callback returns `true`.
    $csrf->skipCheckCallback(function ($request) {
        // Skip token check for API URLs.
        if (get_class($request) !== 'Cake\\Http\\ServerRequest') {
            // I'm not sure if $request can be something else because the docs is not clear about this
            return false;
        }

        if (strtolower($request->getParam('controller')) === 'hosts' && $request->getParam('action') === 'index') {
            // Disable CSRF check for /hosts/index ITC-2640
            return true;
        }

        if (strtolower($request->getParam('controller')) === 'services' && $request->getParam('action') === 'index') {
            // Disable CSRF check for /services/index ITC-3349
            return true;
        }

        // Keep CSRF checks enabled
        return false;
    });

    $builder->registerMiddleware('csrf', $csrf);

    /*
     * Apply a middleware to the current route scope.
     * Requires middleware to be registered through `Application::routes()` with `registerMiddleware()`
     */
    if (
        (!isset($_SERVER['HTTP_AUTHORIZATION']) || (isset($_SERVER['HTTP_AUTHORIZATION']) && strpos($_SERVER['HTTP_AUTHORIZATION'], 'X-OITC-API') === false)) &&
        (!isset($_SERVER['QUERY_STRING']) || (isset($_SERVER['QUERY_STRING']) && strpos($_SERVER['QUERY_STRING'], 'apikey') === false))) {

        $builder->applyMiddleware('csrf');
    }

    /*
     * Fireup the AngularJS application
     */
    $builder->connect('/', ['controller' => 'Pages', 'action' => 'index']);

    /*
     * Backwards compatibility because Firefox has cached /ng redirect forever
     */
    $builder->connect('/ng', ['controller' => 'Pages', 'action' => 'index']);

    /*
     * ...and connect the rest of 'Pages' controller's URLs.
     */
    $builder->connect('/pages/*', ['controller' => 'Pages', 'action' => 'display']);

    /*
     * Connect catchall routes for all controllers.
     *
     * The `fallbacks` method is a shortcut for
     *
     * ```
     * $builder->connect('/:controller', ['action' => 'index']);
     * $builder->connect('/:controller/:action/*', []);
     * ```
     *
     * You can remove these routes once you've connected the
     * routes you want in your application.
     */
    $builder->fallbacks();
});

/*
 * If you need a different set of middleware or none at all,
 * open new scope and define routes there.
 *
 * ```
 * $routes->scope('/api', function (RouteBuilder $builder) {
 *     // No $builder->applyMiddleware() here.
 *     // Connect API actions here.
 * });
 * ```
 */
