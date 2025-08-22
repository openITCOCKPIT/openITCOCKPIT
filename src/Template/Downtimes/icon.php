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

<i
    ng-if="downtime.isRunning"
    title="<?php echo __('Downtime currently running') ?>"
    class="fa fa-power-off txt-color-green"
></i>

<i
    ng-if="downtime.isExpired"
    title="<?php echo __('Downtime is expired') ?>"
    class="fa fa-power-off txt-color-red"
></i>

<i
    ng-if="downtime.wasCancelled"
    title="<?php echo __('Downtime was cancelled') ?>"
    class="fa fa-power-off txt-color-red"
></i>

<i
    ng-if="!downtime.wasCancelled && !downtime.wasStarted && !downtime.isExpired"
    title="<?php echo __('Downtime not started yet') ?>"
    class="fa fa-power-off text-primary"
></i>
