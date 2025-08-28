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

namespace itnovum\openITCOCKPIT\Core\Views;


class ListSettingsRenderer {

    /**
     * @var array
     */
    private $Listsettings;

    /**
     * @var int
     */
    private $userDefaultLimit = 25;

    /**
     * @var null|\PaginatorHelper
     */
    private $paginatorHelper = null;

    public function __construct($Listsettings = []) {
        $this->Listsettings = $Listsettings;
    }


    public function setPaginator(\PaginatorHelper $paginatorHelper) {
        $this->paginatorHelper = $paginatorHelper;
        $paging = $this->paginatorHelper->params();
        if (isset($paging['limit'])) {
            $this->userDefaultLimit = (int)$paging['limit'];
        }
    }

    /**
     * @return string
     */
    public function getLimitSelect() {
        $limits = [
            30  => 30,
            50  => 50,
            100 => 100,
            300 => 300
        ];
        if (!isset($limits[$this->userDefaultLimit])) {
            $limits[$this->userDefaultLimit] = $this->userDefaultLimit;
            ksort($limits);
        }


        $selected = $this->userDefaultLimit;
        if (isset($this->Listsettings['limit'])) {
            $selected = (int)$this->Listsettings['limit'];
        }

        $html = '<div class="btn-group">';
        $html .= '<button data-toggle="dropdown" class="btn dropdown-toggle btn-xs btn-default">';
        $html .= '<span id="listoptions_limit">' . h($selected) . '</span> <i class="fa fa-caret-down"></i>';
        $html .= '</button>';
        $html .= '<ul class="dropdown-menu pull-right stayOpenOnClick">';

        foreach ($limits as $limit => $human) {
            $html .= '<li>';
            $html .= '<a href="javascript:void(0);" class="listoptions_action"';
            $html .= 'selector="#listoptions_limit"';
            $html .= 'submit_target="#listoptions_hidden_limit"';
            $html .= 'value="' . $limit . '">' . h($human) . '</a>';
            $html .= '</li>';
        }
        $html .= '</ul>';
        $html .= '<input type="hidden"';
        $html .= 'value="' . $selected . '"';
        $html .= 'id="listoptions_hidden_limit" name="data[Listsettings][limit]"/>';
        $html .= '</div>';

        return $html;
    }

    /**
     * @param null $label
     * @return string
     */
    public function getApply($label = null) {
        if ($label === null) {
            $label = __('Apply');
        }
        $html = '&nbsp;<button class="btn btn-xs btn-success toggle">';
        $html .= '<i class="fa fa-check"></i> ' . h($label) . '</button>';
        return $html;
    }

    /**
     * @return string
     */
    public function getFromInput() {
        $html = '<div class="widget-toolbar pull-left" role="menu">';
        $html .= '<span style="line-height: 32px;" class="pull-left">' . __('From:') . '</span>';
        $html .= '<input class="form-control text-center pull-left margin-left-10" style="width: 78%;"';
        $html .= 'type="text" maxlength="255"';
        $html .= 'value="' . $this->Listsettings['from'] . '" ';
        $html .= 'name="data[Listsettings][from]">';
        $html .= '</div>';

        return $html;
    }

    /**
     * @return string
     */
    public function getToInput() {
        $html = '<div class="widget-toolbar pull-left" role="menu">';
        $html .= '<span style="line-height: 32px;" class="pull-left">' . __('To:') . '</span>';
        $html .= '<input class="form-control text-center pull-left margin-left-10" style="width: 85%;"';
        $html .= 'type="text" maxlength="255"';
        $html .= 'value="' . $this->Listsettings['to'] . '" ';
        $html .= 'name="data[Listsettings][to]">';
        $html .= '</div>';

        return $html;
    }


}
