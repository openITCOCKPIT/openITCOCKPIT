<?php
// Copyright (C) <2015>  <it-novum GmbH>
//
// This file is dual licensed
//
// 1.
//	This program is free software: you can redistribute it and/or modify
//	it under the terms of the GNU General Public License as published by
//	the Free Software Foundation, version 3 of the License.
//
//	This program is distributed in the hope that it will be useful,
//	but WITHOUT ANY WARRANTY; without even the implied warranty of
//	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//	GNU General Public License for more details.
//
//	You should have received a copy of the GNU General Public License
//	along with this program.  If not, see <http://www.gnu.org/licenses/>.
//

// 2.
//	If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//	under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//	License agreement and license key will be shipped with the order
//	confirmation.

declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * Class Initial
 *
 * Created via:
 * oitc migrations create -p GrafanaModule Initial
 *
 * Run migration:
 * oitc migrations migrate -p GrafanaModule
 *
 */
class Initial extends \Migrations\BaseMigration {
    /**
     * Whether the tables created in this migration
     * should auto-create an `id` field or not
     *
     * This option is global for all tables created in the migration file.
     * If you set it to false, you have to manually add the primary keys for your
     * tables using the Migrations\Table::addPrimaryKey() method
     *
     * @var bool
     */
    public bool $autoId = false;

    /**
     * Up Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-up-method
     * @return void
     */
    public function up(): void {
        if (!$this->hasTable('grafana_configurations')) {
            $this->table('grafana_configurations')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('api_url', 'string', [
                    'default' => null,
                    'limit'   => 200,
                    'null'    => false,
                ])
                ->addColumn('api_key', 'string', [
                    'default' => null,
                    'limit'   => 200,
                    'null'    => false,
                ])
                ->addColumn('graphite_prefix', 'string', [
                    'default' => null,
                    'limit'   => 200,
                    'null'    => false,
                ])
                ->addColumn('use_https', 'integer', [
                    'default' => null,
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('use_proxy', 'integer', [
                    'default' => null,
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('ignore_ssl_certificate', 'integer', [
                    'default' => null,
                    'limit'   => 1,
                    'null'    => false,
                ])
                ->addColumn('dashboard_style', 'string', [
                    'default' => null,
                    'limit'   => 200,
                    'null'    => false,
                ])
                ->addColumn('created', 'datetime', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                ])
                ->addColumn('modified', 'datetime', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                ])
                ->create();
        }

        if (!$this->hasTable('grafana_dashboards')) {
            $this->table('grafana_dashboards')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('configuration_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('host_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('host_uuid', 'string', [
                    'default' => null,
                    'limit'   => 200,
                    'null'    => false,
                ])
                ->addColumn('grafana_uid', 'string', [
                    'default' => '',
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->create();
        }

        if (!$this->hasTable('grafana_userdashboard_metrics')) {
            $this->table('grafana_userdashboard_metrics')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('panel_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('metric', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('host_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('service_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->create();
        }

        if (!$this->hasTable('grafana_userdashboard_panels')) {
            $this->table('grafana_userdashboard_panels')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('userdashboard_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('row', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('unit', 'string', [
                    'default' => 'none',
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('title', 'string', [
                    'default' => '',
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->create();
        }

        if (!$this->hasTable('grafana_userdashboards')) {
            $this->table('grafana_userdashboards')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('container_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('configuration_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('name', 'string', [
                    'default' => '',
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('grafana_uid', 'string', [
                    'default' => '',
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('grafana_url', 'string', [
                    'default' => '',
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->create();
        }

        if (!$this->hasTable('hostgroups_to_grafanaconfigurations')) {
            $this->table('hostgroups_to_grafanaconfigurations')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('configuration_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('hostgroup_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('excluded', 'integer', [
                    'default' => null,
                    'limit'   => 1,
                    'null'    => true,
                ])
                ->addIndex(
                    [
                        'configuration_id',
                        'hostgroup_id',
                    ],
                    ['unique' => true]
                )
                ->create();
        }
    }

    /**
     * Down Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-down-method
     * @return void
     */
    public function down(): void {
        $this->table('grafana_configurations')->drop()->save();
        $this->table('grafana_dashboards')->drop()->save();
        $this->table('grafana_userdashboard_metrics')->drop()->save();
        $this->table('grafana_userdashboard_panels')->drop()->save();
        $this->table('grafana_userdashboards')->drop()->save();
        $this->table('hostgroups_to_grafanaconfigurations')->drop()->save();
    }

}
