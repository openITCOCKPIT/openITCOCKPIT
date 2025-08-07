<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * Class Initial
 *
 * Created via:
 * oitc4 migrations create -p MapModule Initial
 *
 * Run migration:
 * oitc4 migrations migrate -p MapModule
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
    public $autoId = false;

    /**
     * Up Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-up-method
     * @return void
     */
    public function up(): void {
        if (!$this->hasTable('map_uploads')) {
            $this->table('map_uploads')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('upload_type', 'integer', [
                    'default' => null,
                    'limit'   => 10,
                    'null'    => true,
                ])
                ->addColumn('upload_name', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('saved_name', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('user_id', 'integer', [
                    'default' => null,
                    'limit'   => 10,
                    'null'    => true,
                ])
                ->addColumn('container_id', 'integer', [
                    'default' => null,
                    'limit'   => 10,
                    'null'    => true,
                ])
                ->addColumn('created', 'datetime', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                ])
                ->addIndex(
                    [
                        'saved_name',
                    ]
                )
                ->addIndex(
                    [
                        'container_id',
                    ]
                )
                ->create();
        }

        if (!$this->hasTable('mapgadgets')) {
            $this->table('mapgadgets')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('map_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('x', 'integer', [
                    'default' => '0',
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('y', 'integer', [
                    'default' => '0',
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('size_x', 'integer', [
                    'default' => '0',
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('size_y', 'integer', [
                    'default' => '0',
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('limit', 'integer', [
                    'default' => '0',
                    'limit'   => 11,
                    'null'    => true,
                ])
                ->addColumn('gadget', 'string', [
                    'default' => null,
                    'limit'   => 128,
                    'null'    => true,
                ])
                ->addColumn('type', 'string', [
                    'default' => null,
                    'limit'   => 20,
                    'null'    => false,
                ])
                ->addColumn('object_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('transparent_background', 'integer', [
                    'default' => '0',
                    'limit'   => 5,
                    'null'    => false,
                ])
                ->addColumn('show_label', 'integer', [
                    'default' => '1',
                    'limit'   => 5,
                    'null'    => false,
                ])
                ->addColumn('font_size', 'integer', [
                    'default' => '13',
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('z_index', 'integer', [
                    'default' => '0',
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('metric', 'string', [
                    'default' => null,
                    'limit'   => 256,
                    'null'    => true,
                ])
                ->addColumn('output_type', 'string', [
                    'default' => null,
                    'limit'   => 256,
                    'null'    => true,
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

        if (!$this->hasTable('mapicons')) {
            $this->table('mapicons')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('map_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('x', 'integer', [
                    'default' => '0',
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('y', 'integer', [
                    'default' => '0',
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('icon', 'string', [
                    'default' => null,
                    'limit'   => 128,
                    'null'    => false,
                ])
                ->addColumn('z_index', 'integer', [
                    'default' => '0',
                    'limit'   => 11,
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

        if (!$this->hasTable('mapitems')) {
            $this->table('mapitems')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('map_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('x', 'integer', [
                    'default' => '0',
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('y', 'integer', [
                    'default' => '0',
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('limit', 'integer', [
                    'default' => '0',
                    'limit'   => 11,
                    'null'    => true,
                ])
                ->addColumn('iconset', 'string', [
                    'default' => null,
                    'limit'   => 128,
                    'null'    => false,
                ])
                ->addColumn('type', 'string', [
                    'default' => null,
                    'limit'   => 20,
                    'null'    => false,
                ])
                ->addColumn('object_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('z_index', 'integer', [
                    'default' => '0',
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('show_label', 'integer', [
                    'default' => '0',
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('label_possition', 'integer', [
                    'default' => '2',
                    'limit'   => 11,
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

        if (!$this->hasTable('maplines')) {
            $this->table('maplines')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('map_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('startX', 'integer', [
                    'default' => '0',
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('startY', 'integer', [
                    'default' => '0',
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('endX', 'integer', [
                    'default' => '0',
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('endY', 'integer', [
                    'default' => '0',
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('limit', 'integer', [
                    'default' => '0',
                    'limit'   => 11,
                    'null'    => true,
                ])
                ->addColumn('iconset', 'string', [
                    'default' => null,
                    'limit'   => 128,
                    'null'    => true,
                ])
                ->addColumn('type', 'string', [
                    'default' => null,
                    'limit'   => 20,
                    'null'    => false,
                ])
                ->addColumn('object_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => true,
                ])
                ->addColumn('z_index', 'integer', [
                    'default' => '0',
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('show_label', 'integer', [
                    'default' => '0',
                    'limit'   => 11,
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

        if (!$this->hasTable('maps')) {
            $this->table('maps')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('name', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('title', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('background', 'string', [
                    'default' => null,
                    'limit'   => 128,
                    'null'    => true,
                ])
                ->addColumn('refresh_interval', 'integer', [
                    'default' => '0',
                    'limit'   => 11,
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

        if (!$this->hasTable('maps_to_containers')) {
            $this->table('maps_to_containers')
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
                ->addColumn('map_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addIndex(
                    [
                        'container_id',
                    ]
                )
                ->addIndex(
                    [
                        'map_id',
                    ]
                )
                ->create();
        }

        if (!$this->hasTable('maps_to_rotations')) {
            $this->table('maps_to_rotations')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('map_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('rotation_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addIndex(
                    [
                        'map_id',
                    ]
                )
                ->addIndex(
                    [
                        'rotation_id',
                    ]
                )
                ->create();
        }

        if (!$this->hasTable('mapsummaryitems')) {
            $this->table('mapsummaryitems')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('map_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('x', 'integer', [
                    'default' => '0',
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('y', 'integer', [
                    'default' => '0',
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('size_x', 'integer', [
                    'default' => '100',
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('size_y', 'integer', [
                    'default' => '100',
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('limit', 'integer', [
                    'default' => '0',
                    'limit'   => 11,
                    'null'    => true,
                ])
                ->addColumn('type', 'string', [
                    'default' => null,
                    'limit'   => 20,
                    'null'    => false,
                ])
                ->addColumn('object_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('z_index', 'integer', [
                    'default' => '0',
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('show_label', 'integer', [
                    'default' => '0',
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('label_possition', 'integer', [
                    'default' => '2',
                    'limit'   => 11,
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

        if (!$this->hasTable('maptexts')) {
            $this->table('maptexts')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('map_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('x', 'integer', [
                    'default' => '0',
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('y', 'integer', [
                    'default' => '0',
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('text', 'string', [
                    'default' => '0',
                    'limit'   => 256,
                    'null'    => false,
                ])
                ->addColumn('font_size', 'integer', [
                    'default' => '11',
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('z_index', 'integer', [
                    'default' => '0',
                    'limit'   => 11,
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
    }

    /**
     * Down Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-down-method
     * @return void
     */
    public function down(): void {
        $this->table('map_uploads')->drop()->save();
        $this->table('mapgadgets')->drop()->save();
        $this->table('mapicons')->drop()->save();
        $this->table('mapitems')->drop()->save();
        $this->table('maplines')->drop()->save();
        $this->table('maps')->drop()->save();
        $this->table('maps_to_containers')->drop()->save();
        $this->table('maps_to_rotations')->drop()->save();
        $this->table('mapsummaryitems')->drop()->save();
        $this->table('maptexts')->drop()->save();
    }
}
