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

// 2.
//	If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//	under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//	License agreement and license key will be shipped with the order
//	confirmation.

/**
 * @var \App\View\AppView $this
 */

$Logo = new \itnovum\openITCOCKPIT\Core\Views\Logo();
?>

<div class="p-80 scrollable pos-r login-side-bg" style='min-width: 320px;'>

    <div class="col-12 text-center">
        <img class="img-fluid" src="<?= h($Logo->getLoginLogoHtml()); ?>" style="max-height: 230px;"/>
    </div>

    <div class="col-12">
        <h1>You have reached the openITCOCKPIT backend API.</h1>
        <p>
            Your request is not authorized. Previously, this was the login screen for openITCOCKPIT.
            <br>
            Since openITCOCKPIT 5, the backend provides only API endpoints and no longer serves HTML files.
            <br>
            If you are seeing this page, there may be a misconfiguration in the web server's settings.
            Please contact your system administrator.
            <br>
            If you believe this is a bug, feel free to open an issue at:
            <br>
            <a href="https://github.com/openITCOCKPIT/openITCOCKPIT"
               class="text-white fw-bold"
               target="_blank">https://github.com/openITCOCKPIT/openITCOCKPIT</a>
        </p>
        <h3 class="text-end">
            <a
                href="/a/users/login"
                class="btn btn-primary">
                <i class="fas fa-sign-in-alt"></i>
                Go to login screen
            </a>
        </h3>
    </div>
</div>
