<?php declare(strict_types=1);
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


use Migrations\AbstractMigration;

class GrafanaDashboardSettings extends \Migrations\BaseMigration {
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function change(): void {
        if ($this->hasTable('grafana_userdashboards')) {
            $this->table('grafana_userdashboards')
                ->addColumn('refresh', 'string', [
                    'default' => '1m',
                    'limit'   => 255,
                    'null'    => false,
                    'after'   => 'name'
                ])
                ->addColumn('range', 'string', [
                    'default' => 'now-3h',
                    'limit'   => 255,
                    'null'    => false,
                    'after'   => 'name'
                ])
                ->addColumn('tooltip', 'integer', [
                    'default' => 0,
                    'limit'   => 3,
                    'null'    => false,
                    'after'   => 'name'
                ])
                ->update();
        }
    }
}
