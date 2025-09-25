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

$interval = [
    [
        'interval' => 30,
        'short'    => __('30s'),
        'long'     => __('30 seconds')
    ],
    [
        'interval' => 60,
        'short'    => __('1m'),
        'long'     => __('1 minute')
    ],
    [
        'interval' => (2 * 60),
        'short'    => __('2m'),
        'long'     => __('2 minutes')
    ],
    [
        'interval' => (5 * 60),
        'short'    => __('5m'),
        'long'     => __('5 minutes')
    ],
    [
        'interval' => (10 * 60),
        'short'    => __('10m'),
        'long'     => __('10 minutes')
    ],
    [
        'interval' => (15 * 60),
        'short'    => __('15m'),
        'long'     => __('15 minutes')
    ],
    [
        'interval' => (30 * 60),
        'short'    => __('30m'),
        'long'     => __('30 minutes')
    ],
    [
        'interval' => (45 * 60),
        'short'    => __('45m'),
        'long'     => __('45 minutes')
    ],
    [
        'interval' => 3600,
        'short'    => __('1h'),
        'long'     => __('1 hour')
    ],
    [
        'interval' => (1800 + 3600),
        'short'    => __('1.5h'),
        'long'     => __('1.5 hours')
    ],
    [
        'interval' => (2 * 3600),
        'short'    => __('2h'),
        'long'     => __('2 hours')
    ],
    [
        'interval' => (4 * 3600),
        'short'    => __('4h'),
        'long'     => __('4 hours')
    ]
];
?>

<div class="row">
    <div class="col-xs-12 col-lg-6">
        <div class="btn-group flex-wrap">
            <?php foreach ($interval as $intervalArray): ?>
                <button
                    type="button"
                    class="btn btn-default"
                    title="<?php echo h($intervalArray['long']); ?>"
                    ng-click="changeInterval(<?php echo h($intervalArray['interval']); ?>)"
                    ng-class="{'active': interval == <?php echo h($intervalArray['interval']); ?>}" )>
                    <?php echo h($intervalArray['short']); ?>
                </button>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="col-xs-12 col-lg-6">
        <div class="input-group">
            <input
                class="form-control"
                type="number"
                min="<?php echo \Cake\Core\Configure::read('NagiosModule.SLIDER_MIN'); ?>"
                max="<?php echo \Cake\Core\Configure::read('NagiosModule.SLIDER_MAX'); ?>"
                placeholder="<?php echo __('Interval in seconds'); ?>"
                ng-model="interval">

            <template-diff ng-show="templateId"
                           value="interval"
                           template-value="templateValue"></template-diff>
        </div>
        <div class="help-block margin-bottom-0">
            <human-time-directive seconds="interval"></human-time-directive>
        </div>
    </div>
</div>
