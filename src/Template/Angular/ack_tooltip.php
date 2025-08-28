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
<div class="popup-tooltip-container" id="tooltipContainer-{{ackPopoverId}}">
    <div>
    <h3 ng-if="displayType === 'service'"><?= __('State of service is acknowledged'); ?> <span ng-show="sticky">(<?php echo __('Sticky'); ?>)</span> </h3>
    <h3 ng-if="displayType === 'host'"><?= __('State of host is acknowledged'); ?> <span ng-show="sticky">(<?php echo __('Sticky'); ?>)</span> </h3>
    </div>
    <div class="popover-body">
        <div><?= __('Set by'); ?>: {{author}}</div>
        <div><?= __('Set at'); ?>: {{entry}}</div>
        <div><?= __('Comment'); ?>: {{comment}}</div>
    </div>

</div>


