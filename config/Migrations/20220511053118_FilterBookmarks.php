<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class FilterBookmarks extends \Migrations\BaseMigration {
    public $autoId = false;

    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function change(): void {
        $this->table('filter_bookmarks')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default'       => null,
                'limit'         => 11,
                'null'          => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('uuid', 'string', [
                'default' => null,
                'limit'   => 37,
                'null'    => false,
            ])
            ->addColumn('plugin', 'string', [
                'default' => null,
                'null'    => true,
            ])
            ->addColumn('controller', 'string', [
                'default' => null,
                'null'    => false,
            ])
            ->addColumn('action', 'string', [
                'default' => null,
                'null'    => false,
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'null'    => false,
            ])
            ->addColumn('user_id', 'integer', [
                'default' => null,
                'limit'   => 11,
                'null'    => false,
            ])
            ->addColumn('filter', 'text', [
                'default' => null,
                'null'    => false,
            ])
            ->addColumn('favorite', 'boolean', [
                'default' => '0',
                'limit'   => 1,
                'null'    => true,
            ])
            ->addIndex(
                [
                    'uuid',
                ],
                ['unique' => true]
            )
            ->create();
    }
}
