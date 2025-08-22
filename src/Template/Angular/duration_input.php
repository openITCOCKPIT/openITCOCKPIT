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

$duration = [
    [
        'duration' => 1,
        'short'    => __('1m'),
        'long'     => __('1 minute')
    ],
    [
        'duration' => 2,
        'short'    => __('2m'),
        'long'     => __('2 minutes')
    ],
    [
        'duration' => 5,
        'short'    => __('5m'),
        'long'     => __('5 minutes')
    ],
    [
        'duration' => 10,
        'short'    => __('10m'),
        'long'     => __('10 minutes')
    ],
    [
        'duration' => 15,
        'short'    => __('15m'),
        'long'     => __('15 minutes')
    ],
    [
        'duration' => 30,
        'short'    => __('30m'),
        'long'     => __('30 minutes')
    ],
    [
        'duration' => 45,
        'short'    => __('45m'),
        'long'     => __('45 minutes')
    ],
    [
        'duration' => 60,
        'short'    => __('1h'),
        'long'     => __('1 hour')
    ],
    [
        'duration' => (1.5 * 60),
        'short'    => __('1.5h'),
        'long'     => __('1.5 hours')
    ],
    [
        'duration' => (2 * 60),
        'short'    => __('2h'),
        'long'     => __('2 hours')
    ],
    [
        'duration' => (4 * 60),
        'short'    => __('4h'),
        'long'     => __('4 hours')
    ],
    [
        'duration' => (8 * 60),
        'short'    => __('8h'),
        'long'     => __('8 hours')
    ]
];
?>


<div class="row">
    <div class="col-xs-12 col-lg-6">
        <div class="btn-group flex-wrap">
            <?php foreach ($duration as $idurationArray): ?>
                <button
                    type="button"
                    class="btn btn-default"
                    title="<?php echo h($idurationArray['long']); ?>"
                    ng-click="changeDuration(<?php echo h($idurationArray['duration']); ?>)"
                    ng-class="{'active': duration == <?php echo h($idurationArray['duration']); ?>}" )>
                    <?php echo h($idurationArray['short']); ?>
                </button>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="col-xs-12 col-lg-3">
        <input
            class="form-control"
            type="number"
            placeholder="<?php echo __('Duration in minutes'); ?>"
            ng-model="duration">
        <div class="help-block margin-bottom-0">
            <human-time-directive seconds="duration*60"></human-time-directive>
        </div>
    </div>
</div>
