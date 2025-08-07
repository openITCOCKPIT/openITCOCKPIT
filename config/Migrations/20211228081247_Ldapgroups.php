<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * Class Ldapgroups
 *
 * Created:
 * oitc migrations create Ldapgroups
 *
 * Usage:
 * openitcockpit-update
 */
class Ldapgroups extends \Migrations\BaseMigration {
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
    public function change(): void {

        $this->table('ldapgroups')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default'       => null,
                'limit'         => 11,
                'null'          => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('cn', 'string', [
                'default' => null,
                'limit'   => 255,
                'null'    => false,
            ])
            ->addColumn('dn', 'string', [
                'default' => null,
                'limit'   => 512,
                'null'    => false,
            ])
            ->addColumn('description', 'string', [
                'default' => null,
                'limit'   => 512,
                'null'    => false,
            ])
            ->addIndex(
                [
                    'dn',
                    'cn'
                ]
            )
            ->create();

        $this->table('ldapgroups_to_usercontainerroles')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default'       => null,
                'limit'         => 11,
                'null'          => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('ldapgroup_id', 'integer', [
                'default' => null,
                'limit'   => 11,
                'null'    => false,
            ])
            ->addColumn('usercontainerrole_id', 'integer', [
                'default' => null,
                'limit'   => 11,
                'null'    => false,
            ])
            ->create();

        $this->table('users_to_usercontainerroles')
            ->addColumn('through_ldap', 'boolean', [
                'default' => 0,
                'limit'   => 1,
                'null'    => false,
                'after'   => 'usercontainerrole_id'
            ])
            ->update();

    }
}
