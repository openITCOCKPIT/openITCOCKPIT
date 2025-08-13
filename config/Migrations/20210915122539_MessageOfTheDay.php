<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class MessageOfTheDay extends \Migrations\BaseMigration {
    /**
     * Change Method.
     * Class MessageOfTheDay
     *
     * Created:
     * oitc migrations create MessageOfTheDay
     *
     * Run migration:
     * oitc migrations migrate
     *
     * Usage:
     * openitcockpit-update
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public bool $autoId = false;

    public function change(): void {
        if (!$this->hasTable('messages_otd')) {
            $this->table('messages_otd')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('title', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('description', 'string', [
                    'default' => null,
                    'limit'   => 1000,
                    'null'    => false,
                ])
                ->addColumn('content', 'text', [
                    'default' => null,
                    'limit'   => \Phinx\Db\Adapter\MysqlAdapter::TEXT_REGULAR,
                    'null'    => true,
                ])
                ->addColumn('style', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('user_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('date', 'date', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('expiration_duration', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => true,
                ])
                ->addColumn('notify_users', 'integer', [
                    'default' => '0',
                    'limit'   => 1,
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
                ->addIndex(['date'], ['unique' => true])
                ->create();

            if (!$this->hasTable('messages_otd_to_usergroups')) {
                $this->table('messages_otd_to_usergroups')
                    ->addColumn('id', 'integer', [
                        'autoIncrement' => true,
                        'default'       => null,
                        'limit'         => 11,
                        'null'          => false,
                    ])
                    ->addPrimaryKey(['id'])
                    ->addColumn('message_otd_id', 'integer', [
                        'limit' => 11,
                        'null'  => false,
                    ])
                    ->addColumn('usergroup_id', 'integer', [
                        'limit' => 11,
                        'null'  => false,
                    ])
                    ->create();
            }
        }
    }
}
