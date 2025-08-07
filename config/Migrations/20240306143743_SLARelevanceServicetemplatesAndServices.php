<?php
// Copyright (C) <2015>  <it-novum GmbH>
//
// This file is dual licensed
//
// 1.
//  This program is free software: you can redistribute it and/or modify
//  it under the terms of the GNU General Public License as published by
//  the Free Software Foundation, version 3 of the License.
//
//  This program is distributed in the hope that it will be useful,
//  but WITHOUT ANY WARRANTY; without even the implied warranty of
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//  GNU General Public License for more details.
//
//  You should have received a copy of the GNU General Public License
//  along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
// 2.
//  If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//  under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//  License agreement and license key will be shipped with the order
//  confirmation.

declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * Class SLARelevanceServicetemplatesAndServices
 *
 * Created via:
 * oitc migrations create SLARelevanceServicetemplatesAndServices
 *
 * Run migration:
 * oitc migrations migrate
 *
 */
class SLARelevanceServicetemplatesAndServices extends \Migrations\BaseMigration {

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
    public $autoId = false;

    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function up() {
        $table = $this->table('servicetemplates');
        $table
            ->addColumn('sla_relevant', 'integer', [
                'after'   => 'service_url',
                'default' => 1,
                'limit'   => 1,
                'null'    => false,
            ])
            ->update();

        $table = $this->table('services');
        $table
            ->addColumn('sla_relevant', 'integer', [
                'after'   => 'service_url',
                'default' => null,
                'limit'   => 1,
                'null'    => true,
            ])
            ->update();
    }

    /**
     * Down Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-down-method
     * @return void
     */
    public function down() {
        $this->table('servicetemplates')
            ->removeColumn('sla_relevant')
            ->save();

        $this->table('services')
            ->removeColumn('sla_relevant')
            ->save();
    }
}
