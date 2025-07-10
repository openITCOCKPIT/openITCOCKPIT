<?php
// Copyright (C) <2015>  <it-novum GmbH>
//
// This file is dual licensed
//
// 1.
//  This program is free software: you can redistribute it and/or modify
//  it under the terms of the GNU General Public License as published by
//  the Free Software Foundation, version 3 of the License.
//
//  This program is distributed in the hope that it will be useful,
//  but WITHOUT ANY WARRANTY; without even the implied warranty of
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//  GNU General Public License for more details.
//
//  You should have received a copy of the GNU General Public License
//  along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
// 2.
//  If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//  under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//  License agreement and license key will be shipped with the order
//  confirmation.

namespace itnovum\openITCOCKPIT\Core\Views;


use itnovum\openITCOCKPIT\Core\LoginBackgrounds;

class Logo {
    public const TYPE_INTERFACE_PDF = 0;
    public const TYPE_NOTIFICATIONS = 1;
    public const TYPE_INTERFACE_BIG = 2;
    public const TYPE_HEADER = 3;
    public const TYPE_LOGIN_BACKGROUND = 4;
    public const TYPE_STATUS_PAGE_HEADER = 5;

    public const TYPES = [
        self::TYPE_INTERFACE_PDF,
        self::TYPE_NOTIFICATIONS,
        self::TYPE_INTERFACE_BIG,
        self::TYPE_HEADER,
        self::TYPE_LOGIN_BACKGROUND,
        self::TYPE_STATUS_PAGE_HEADER
    ];

    private $logoName = 'logo.png';
    private $smallLogoName = 'logo_small.png';

    private $logoBasePath = '%s/img/logos/%s';
    private $logoBaseForAbsolutePath = '%simg/logos/%s';
    private $logoPdfPath = '%s/img/%s';

    private $customLogoName = 'logo_custom.png';
    private $customSmallLogoName = 'logo_small_custom.png';

    private $headerLogoName = 'logo_header.png';
    private $customHeaderLogoName = 'logo_custom_header.png';

    private $customLoginBackgroundName = 'custom_login_background.png';

    private $customStatusPageHeaderName = 'custom_status_page_header.png';

    /**
     * @return string
     */
    public function getLogoName() {
        if ($this->isCustomLogo()) {
            return $this->customLogoName;
        }

        return $this->logoName;
    }

    /**
     * @return string
     */
    public function getSmallLogoName() {
        if ($this->isCustomSmallLogo()) {
            return $this->customSmallLogoName;
        }

        return $this->smallLogoName;
    }

    /**
     * @return string
     */
    public function getLogoForHtml() {
        return sprintf($this->logoBasePath, '', $this->getLogoName());
    }

    /**
     * @return string
     */
    public function getLogoForHtmlHelper() {
        return $this->getLogoName();
    }

    /**
     * @return string
     */
    public function getSmallLogoForHtml() {
        return sprintf($this->logoBasePath, '', $this->getSmallLogoName());
    }

    /**
     * @return string
     */
    public function getSmallLogoForHtmlHelper() {
        return $this->getSmallLogoName();
    }

    /**
     * @return bool
     */
    public function isCustomLogo() {
        $file = sprintf($this->logoBaseForAbsolutePath, WWW_ROOT, $this->customLogoName);
        return file_exists($file);
    }

    /**
     * @return bool
     */
    public function isCustomSmallLogo() {
        $file = sprintf($this->logoBaseForAbsolutePath, WWW_ROOT, $this->customSmallLogoName);
        return file_exists($file);
    }

    /**
     * @return string
     */
    public function getLogoDiskPath() {
        return sprintf($this->logoBasePath, APP, $this->getLogoName());
    }

    /**
     * @return string
     */
    public function getSmallLogoDiskPath() {
        return sprintf($this->logoBasePath, WWW_ROOT, $this->getSmallLogoName());
    }

    /**
     * @return string
     */
    public function getDefaultLogoDiskPath() {
        return sprintf($this->logoBasePath, APP, $this->logoName);
    }

    /**
     * @return string
     */
    public function getDefaultSmallLogoDiskPath() {
        return sprintf($this->logoBasePath, APP, $this->smallLogoName);
    }

    /**
     * @return string
     */
    public function getCustomLogoDiskPath() {
        return sprintf($this->logoBaseForAbsolutePath, WWW_ROOT, $this->customLogoName);
    }

    /**
     * @return string
     */
    public function getCustomSmallLogoDiskPath() {
        return sprintf($this->logoBaseForAbsolutePath, WWW_ROOT, $this->customSmallLogoName);
    }

    /**
     * @return string
     */
    public function getPdfLogoName() {
        if ($this->isCustomPdfLogo()) {
            return $this->customLogoName;
        }
        return $this->logoName;
    }

    /**
     * @return string
     */
    public function getSmallPdfLogoName() {
        if ($this->isCustomSmallPdfLogo()) {
            return $this->customSmallLogoName;
        }
        return $this->smallLogoName;
    }

    /**
     * @return bool
     */
    public function isCustomPdfLogo() {
        $file = sprintf($this->logoPdfPath, WWW_ROOT, $this->customLogoName);
        return file_exists($file);
    }

    /**
     * @return bool
     */
    public function isCustomSmallPdfLogo() {
        $file = sprintf($this->logoPdfPath, WWW_ROOT, $this->customSmallLogoName);
        return file_exists($file);
    }

    /**
     * @return string
     */
    public function getLogoPdfForHtml() {
        return sprintf($this->logoPdfPath, '', $this->getPdfLogoName());
    }

    /**
     * @return string
     */
    public function getSmallLogoPdfForHtml() {
        return sprintf($this->logoPdfPath, '', $this->getSmallPdfLogoName());
    }

    /**
     * @return string
     */
    public function getLogoPdfPath() {
        return sprintf($this->logoPdfPath, WWW_ROOT, $this->getPdfLogoName());
    }

    /**
     * @return string
     */
    public function getCustomLogoPdfPath() {
        return sprintf($this->logoPdfPath, WWW_ROOT, $this->customLogoName);
    }

    /**
     * @return string
     */
    public function getSmallLogoPdfPath() {
        return sprintf($this->logoPdfPath, WWW_ROOT, $this->getSmallPdfLogoName());
    }

    /**
     * @return string
     */
    public function getCustomSmallLogoPdfPath() {
        return sprintf($this->logoPdfPath, WWW_ROOT, $this->customSmallLogoName);
    }

    /**
     * @return string
     */
    public function getHeaderLogoName() {
        if ($this->isCustomHeaderLogo()) {
            return $this->customHeaderLogoName;
        }
        return $this->headerLogoName;
    }

    /**
     * @return bool
     */
    public function isCustomHeaderLogo() {
        $file = sprintf($this->logoBasePath, WWW_ROOT, $this->customHeaderLogoName);
        return file_exists($file);
    }

    /**
     * @return string
     */
    public function getHeaderLogoForHtml() {
        return sprintf($this->logoBasePath, '', $this->getHeaderLogoName());
    }

    /**
     * @return string
     */
    public function getCustomHeaderLogoDiskPath() {
        return sprintf($this->logoBasePath, WWW_ROOT, $this->customHeaderLogoName);
    }

    /**
     * @return string
     */
    public function getLoginLogoHtml() {
        if ($this->isCustomLogo()) {
            return $this->getLogoForHtml();
        }

        return '/img/logos/openitcockpit-logo-url-light.png';
    }

    /**
     * @return bool
     */
    public function isCustomLoginBackground() {
        $file = sprintf($this->logoBaseForAbsolutePath, WWW_ROOT, $this->customLoginBackgroundName);
        return file_exists($file);
    }

    /**
     * @return bool
     */
    public function isCustomStatusPageHeader(): bool {
        $file = sprintf($this->logoBaseForAbsolutePath, WWW_ROOT, $this->customStatusPageHeaderName);
        return file_exists($file);
    }

    /**
     * @return string
     */
    public function getCustomLoginBackgroundDiskPath() {
        return sprintf($this->logoBasePath, WWW_ROOT, $this->customLoginBackgroundName);
    }

    /**
     * @return string
     */
    public function getCustomStatusPageHeaderDiskPath() {
        return sprintf($this->logoBasePath, WWW_ROOT, $this->customStatusPageHeaderName);
    }

    /**
     * @return string
     */
    public function getCustomLoginBackgroundHtml() {
        if ($this->isCustomLoginBackground()) {
            return sprintf($this->logoBasePath, '', $this->customLoginBackgroundName);
        }

        // No custom login background - return the current login image
        $LoginBackgrounds = new LoginBackgrounds();
        $images = $LoginBackgrounds->getImages();

        return sprintf('/img/login/%s', $images['images'][0]['image']);
    }

    public function getCustomStatusPageHeaderHtml(): string {
        if ($this->isCustomStatusPageHeader()) {
            return sprintf($this->logoBasePath, '', $this->customStatusPageHeaderName);
        }

        // No custom status page header - So there is NO header image.
    }

    /**
     * From the given $logoType, I will return the path to the actual local file.
     * This is regardless of whether the file exists or not.
     *
     * @param int $logoType I am the type of the image you want to get.
     * @return string
     * @throws \Exception In case the given $logoType is unknown.
     * @see  \itnovum\openITCOCKPIT\Core\Views\Logo::TYPES
     */
    public function getLocalPath(int $logoType): string {
        return match ($logoType) {
            self::TYPE_INTERFACE_PDF => $this->getCustomLogoPdfPath(),
            self::TYPE_NOTIFICATIONS => $this->getCustomSmallLogoPdfPath(),
            self::TYPE_INTERFACE_BIG => $this->getCustomLogoDiskPath(),
            self::TYPE_HEADER => $this->getCustomHeaderLogoDiskPath(),
            self::TYPE_LOGIN_BACKGROUND => $this->getCustomLoginBackgroundDiskPath(),
            self::TYPE_STATUS_PAGE_HEADER => $this->getCustomStatusPageHeaderDiskPath(),
            default => throw new \Exception('Logo Type not known'),
        };
    }
}
