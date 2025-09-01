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


use Spatie\Emoji\Emoji;

class ServicestatusIcon {

    /**
     * @var int|null
     */
    private $state;

    /**
     * @var string
     */
    private $href;

    /**
     * @var string
     */
    private $style;

    /**
     * @var array
     */
    private $humanStates = [];

    private $stateColors = [
        0 => 'success',
        1 => 'warning',
        2 => 'danger',
        3 => 'default',
        4 => 'primary' //Not found in monitoring
    ];

    private $stateIcons = [
        0 => 'fas fa-check',
        1 => 'fas fa-exclamation',
        2 => 'fas fa-exclamation',
        3 => 'fas fa-question-circle',
        4 => 'fas fa-question-circle' //Not found in monitoring
    ];

    private $pushIcon = [
        0 => 'ServicePushIconOK',
        1 => 'ServicePushIconWARNING',
        2 => 'ServicePushIconCRITICAL',
        3 => 'ServicePushIconUNKNOWN',
        4 => null //Not found in monitoring
    ];

    /**
     * ServicestatusIcon constructor.
     * @param null $state
     * @param string $href
     * @param string $style
     */
    public function __construct($state = null, $href = 'javascript:void(0)', $style = '') {
        $this->state = $state;
        $this->href = $href;
        $this->style = $style;


        $this->humanStates = [
            0 => __('Ok'),
            1 => __('Warning'),
            2 => __('Critical'),
            3 => __('Unknown')
        ];

    }

    /**
     * @return int|null
     */
    public function getState() {
        if ($this->state === null) {
            return null; //Not found in Monitoring
        }

        return $this->state;
    }

    /**
     * @return string
     */
    public function getTextColor() {
        if ($this->state === null) {
            return 'txt-primary';
        }

        switch ($this->state) {
            case 0:
                return 'ok';

            case 1:
                return 'warning';

            case 2:
                return 'critical';

            default:
                return 'unknown';
        }
    }

    /**
     * @return string
     */
    public function getBgColor() {
        if ($this->state === null) {
            return 'bg-primary';
        }

        switch ($this->state) {
            case 0:
                return 'bg-ok';

            case 1:
                return 'bg-warning';

            case 2:
                return 'bg-critical';

            default:
                return 'bg-unknown';
        }
    }

    /**
     * @return string
     */
    public function getHumanState() {
        if ($this->state === null) {
            return __('Not found in monitoring');
        }

        return $this->humanStates[$this->state];
    }

    /**
     * @return string
     */
    public function getHtmlIcon() {
        $template = '<a href="%s" class="btn btn-%s status-circle" style="padding:0;%s"></a>';

        $state = $this->state;
        if ($state === null) {
            $state = 4;
        }
        return sprintf($template, $this->href, $this->stateColors[$state], $this->style);
    }

    /**
     * @return string
     */
    public function getPdfIcon() {
        $template = '<i class="fa-solid fa-square %s"></i>';
        return sprintf($template, $this->getTextColor());
    }

    /**
     * @return string
     */
    public function getIcon() {
        $state = $this->state;
        if ($state === null) {
            $state = 4;
        }

        return $this->stateIcons[$state];
    }

    /**
     * @return string
     */
    public function getNotificationIcon() {
        $icon = $this->pushIcon[$this->state];
        if ($icon === null) {
            return null;
        }

        return sprintf(
            '/img/push_notifications/wh/%s.png',
            $icon
        );
    }

    /**
     * @return string
     */
    public function getEmoji() {
        $state = $this->state;
        if ($state === null) {
            return Emoji::blueCircle(); //Not found in monitoring
        }

        $state = (int)$state;
        if ($state === 0) {
            return Emoji::checkMarkButton(); // Ok
        }

        if ($state === 1) {
            return Emoji::warning(); // Warning
        }

        if ($state === 2) {
            return Emoji::policeCarLight(); // Critical
        }

        return Emoji::exclamationQuestionMark(); // Unknown
    }

    public function asArray() {
        return [
            'state'       => $this->state,
            'human_state' => $this->getHumanState(),
            'html_icon'   => $this->getHtmlIcon(),
            'icon'        => $this->getIcon(),
            'emoji'       => $this->getEmoji()
        ];
    }

}
