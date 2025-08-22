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
?>
<div class="btn-group mr-2" role="group" aria-label="<?= __('Display of storage notifications'); ?>"
     ng-show="showstatsinmenu">
    <button class="btn btn-default"
            data-original-title="<?= __('host notifications'); ?>" data-placement="bottom" rel="tooltip"
            data-container="body"
            ui-sref="HostsIndex({hoststate: [0,1,2], sort: 'Hoststatus.last_state_change', direction: 'desc'})">
        <i class="fa fa-hdd-o"></i>
    </button>
    <button class="btn btn-danger ng-binding" data-original-title="<?= __('critical messages'); ?>"
            data-placement="bottom"
            rel="tooltip" data-container="body"
            ui-sref="HostsIndex({hoststate: [1], sort: 'Hoststatus.last_state_change', direction: 'desc'})">
        {{ hoststatusCount[1] }}
    </button>
    <button class="btn btn-secondary ng-binding" data-original-title="<?= __('warning messages'); ?>"
            data-placement="bottom"
            rel="tooltip" data-container="body"
            ui-sref="HostsIndex({hoststate: [2], sort: 'Hoststatus.last_state_change', direction: 'desc'})">
        {{ hoststatusCount[2] }}
    </button>
</div>
<div class="btn-group mr-2" role="group" aria-label="<?= __('Display of service notifications'); ?>"
     ng-show="showstatsinmenu">
    <button class="btn btn-default" data-original-title="<?= __('service notifications'); ?>" data-placement="bottom"
            rel="tooltip"
            data-container="body"
            ui-sref="ServicesIndex({servicestate: [0,1,2,3], sort: 'Servicestatus.last_state_change', direction: 'desc'})">
        <i class="fas fa-cog"></i></button>
    <button class="btn btn-danger ng-binding"
            data-original-title="<?= __('messages with filter set to criticals only'); ?>"
            data-placement="bottom" rel="tooltip" data-container="body"
            ui-sref="ServicesIndex({servicestate: [2], sort: 'Servicestatus.last_state_change', direction: 'desc'})">
        {{ servicestatusCount[2] }}
    </button>
    <button class="btn btn-warning ng-binding"
            data-original-title="<?= __('messages with filter set to warnings only'); ?>"
            data-placement="bottom" rel="tooltip" data-container="body"
            ui-sref="ServicesIndex({servicestate: [1], sort: 'Servicestatus.last_state_change', direction: 'desc'})">
        {{ servicestatusCount[1] }}
    </button>
    <button class="btn btn-secondary ng-binding"
            data-original-title="<?= __('messages with filter set to unknowns only'); ?>"
            data-placement="bottom" rel="tooltip" data-container="body"
            ui-sref="ServicesIndex({servicestate: [3], sort: 'Servicestatus.last_state_change', direction: 'desc'})">
        {{ servicestatusCount[3] }}
    </button>
</div>
