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
$systemname = h($systemname);
?>
<ol class="breadcrumb page-breadcrumb">
    <li class="breadcrumb-item">
        <a ui-sref="DashboardsIndex">
            <i class="fa fa-home"></i> <?php echo __('Home'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <a ui-sref="StatisticsIndex">
            <i class="fa fa-line-chart"></i> <?php echo __('{0} metrics', $systemname); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <i class="fa fa-info-circle"></i> <?php echo __('Info'); ?>
    </li>
</ol>

<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?php echo __('Prometheus metrics export of {0} internal metrics', $systemname); ?>
                    <span class="fw-300"><i></i></span>
                </h2>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <div class="frame-wrap">

                        <div class="alert alert-info">
                            <i class="fa fa-info-circle"></i>
                            <?= __('This Prometheus Exporter can be used to monitor internal metrics of {0} with a second instance of {1} or with Prometheus.', $systemname, $systemname); ?>
                        </div>


                        <div class="col-xs-12 margin-top-10">
                            <h4><?php echo __('How to use'); ?></h4>
                        </div>
                        <div class="col-xs-12">
                            <?= __('This exporter can be scraped by any Prometheus compatible client.'); ?>

                            <dl class="row">
                                <dt class="col-xs-12 col-sm-1"><?= __('Port'); ?></dt>
                                <dd class="col-xs-12 col-sm-11">443</dd>

                                <dt class="col-xs-12 col-sm-1"><?= __('Metric path'); ?></dt>
                                <dd class="col-xs-12 col-sm-11">/metrics</dd>

                                <dt class="col-xs-12 col-sm-1"><?= __('Full path'); ?></dt>
                                <dd class="col-xs-12 col-sm-11 help-text">
                                    <?php printf('https://%s/metrics?apikey=YOUR_API_KEY_HERE', h($_SERVER['SERVER_ADDR'])); ?>
                                </dd>

                                <dt class="col-xs-12 col-sm-1"><?= __('Additional config'); ?></dt>
                                <dd class="col-xs-12 col-sm-11">
<pre>
params:
    apikey: ["YOUR_API_KEY_HERE"]
scheme: https
tls_config:
    insecure_skip_verify: true
</pre>
                                </dd>
                            </dl>

                        </div>


                        <div class="col-xs-12 margin-top-10">
                            <h4><?php echo __('Available metrics'); ?></h4>
                        </div>
                        <div class="col-xs-12">
                            <?= __('This list shows all metrics that are exported.'); ?>
                        </div>

                        <div class="col-xs-12">
                            <div class="form-group">
                            <textarea class="form-control" rows="15"
                                      readonly><?php echo h($metrics); ?></textarea>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
