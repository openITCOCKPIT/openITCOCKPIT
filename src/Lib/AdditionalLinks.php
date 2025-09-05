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

namespace App\Lib;

use Acl\Model\Table\AcosTable;
use App\View\AppView;
use App\View\Helper\AclHelper;

/**
 * Class AdditionalLinks
 * @package App\Lib
 */
class AdditionalLinks {

    /**
     * @var array
     */
    private $links = [];

    /**
     * @var AppView
     */
    private $AppView;

    /**
     * AdditionalLinks constructor.
     */
    public function __construct(AppView $AppView) {
        $this->AppView = $AppView;

        //Load Plugin ALC Dependencies
        foreach (PluginManager::getAvailablePlugins() as $pluginName) {
            $className = sprintf('\%s\Lib\AdditionalLinks', $pluginName);
            if (class_exists($className)) {
                /** @var PluginAdditionalLinks $PluginAdditionalLinks */
                $PluginAdditionalLinks = new $className();

                $links = $PluginAdditionalLinks->getLinks();
                foreach ($links as $link) {
                    if ($this->hasPermission($link['acl']['action'], $link['acl']['controller'], $pluginName)) {
                        $this->links[$link['controller']][$link['action']][$link['position']][] = $link;
                    }
                }
            }
        }
    }

    /**
     * @param string $controller
     * @param string $action
     * @param string $position
     * @return bool
     */
    public function hasLinks(string $controller, string $action, string $position) {
        if (isset($this->links[$controller][$action][$position])) {
            return !empty($this->links[$controller][$action][$position]);
        }

        return false;
    }

    /**
     * @param string $controller
     * @param string $action
     * @param string $position
     * @param string|null $template
     * @return array
     */
    public function getLinksAsHtmlArray(string $controller, string $action, string $position, ?string $template = null) {
        if ($template === null) {
            $template = '<a ui-sref="%s" %s><i class="%s"></i> %s</a>';
        }

        if (!$this->hasLinks($controller, $action, $position)) {
            return [];
        }

        $links = [];
        foreach ($this->links[$controller][$action][$position] as $link) {
            $html = sprintf(
                $template,
                $link['ngState'],
                $link['ngIf'] ? sprintf('ng-if="%s"', $link['ngIf']) : '',
                $link['icon'],
                $link['text']
            );
            $links[] = $html;
        }
        return $links;
    }

    /**
     * @param string $controller
     * @param string $action
     * @param string $position
     * @param string|null $template
     * @return string
     */
    public function getLinksAsHtml(string $controller, string $action, string $position, ?string $template = null) {
        return implode(PHP_EOL, $this->getLinksAsHtmlArray($controller, $action, $position, $template));
    }

    /**
     * @param string $controller
     * @param string $action
     * @param string $position
     * @param string|null $template
     * @return string
     */
    public function getLinksAsHtmlList(string $controller, string $action, string $position, ?string $template = null) {
        if ($template === null) {
            $template = '<a ui-sref="%s" %s class="dropdown-item"><i class="%s"></i> %s</a>';
        }

        return $this->getLinksAsHtml($controller, $action, $position, $template);
    }

    /**
     * @param string $action
     * @param string $controller
     * @param string $plugin
     * @return bool
     */
    public function hasPermission(string $action, string $controller, string $plugin = '') {
        /** @var AclHelper $Acl */
        return $this->AppView->Acl->hasPermission($action, $controller, $plugin);
    }

}

