<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AllocatedFilterBookmarks extends BaseMigration
{
    public bool $autoId = false;

    /**
     * Up Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-up-method
     * @return void
     */
    public function up(): void {
        if (!$this->hasTable('filter_bookmark_allocations')) {
            $this->table('filter_bookmark_allocations')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('name', 'string', [
                    'limit' => 255,
                    'null'  => false,
                ])
                ->addColumn('filter_bookmark_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('container_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('user_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                    'comment' => 'user which created the allocation'
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
                ->addIndex(
                    [
                        'name',
                    ]
                )
                ->addIndex(
                    [
                        'filter_bookmark_id',
                    ]
                )
                ->addIndex(
                    [
                        'user_id',
                    ]
                )

                ->create();
        }
        if (!$this->hasTable('usergroups_to_filter_bookmark_allocations')) {
            $this->table('usergroups_to_filter_bookmark_allocations')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('usergroup_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('filter_bookmark_allocation_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addIndex(
                    [
                        'usergroup_id',
                    ]
                )
                ->addIndex(
                    [
                        'filter_bookmark_allocation_id',
                    ]
                )
                ->create();
        }
        if (!$this->hasTable('users_to_filter_bookmark_allocations')) {
            $this->table('users_to_filter_bookmark_allocations')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('user_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('filter_bookmark_allocation_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addIndex(
                    [
                        'user_id',
                    ]
                )
                ->addIndex(
                    [
                        'filter_bookmark_allocation_id',
                    ]
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
        $this->table('usergroups_to_filter_bookmark_allocations')->drop()->save();
        $this->table('users_to_filter_bookmark_allocations')->drop()->save();
    }
}
