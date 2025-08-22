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
?>

<div ng-show="parentOutages.length == 0 && filter.Host.name == ''" class="text-center text-success">
    <h5 class="padding-top-50">
        <i class="fa fa-check"></i>
        <?php echo __('Currently are no network segment issues'); ?>
    </h5>
</div>

<div class="row" ng-show="parentOutages.length > 0 || filter.Host.name">
    <div class="col-xs-12 col-lg-12 margin-bottom-5">
        <div class="input-group input-group-sm">
            <div class="input-group-prepend">
                <span class="input-group-text"><i class="icon-prepend fa fa-desktop"></i></span>
            </div>
            <input type="text" class="form-control"
                   placeholder="<?php echo __('Filter by host name'); ?>"
                   ng-model="filter.Host.name"
                   ng-model-options="{debounce: 500}">
        </div>
    </div>
</div>

<table class="table table-striped table-hover table-bordered table-sm">
    <tbody>
    <tr ng-repeat="host in parentOutages">
        <td class="text-center">
            <hoststatusicon host="host"></hoststatusicon>
        </td>
        <td class="padding-5">
            <?php if ($this->Acl->hasPermission('browser', 'hosts')): ?>
                <a ui-sref="HostsBrowser({id: host.id})">
                    {{host.name}}
                </a>
            <?php else: ?>
                {{host.name}}
            <?php endif; ?>
        </td>
    </tr>
    </tbody>
</table>
<div class="col-xs-12 text-center txt-color-red italic"
     ng-show="parentOutages.length == 0 && filter.Host.name != ''">
    <?php echo __('No entries match the selection'); ?>
</div>


