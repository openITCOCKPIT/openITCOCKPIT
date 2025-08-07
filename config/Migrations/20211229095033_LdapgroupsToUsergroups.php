<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * Class LdapgroupsToUsergroups
 *
 * Created:
 * oitc migrations create LdapgroupsToUsergroups
 *
 * Usage:
 * openitcockpit-update
 */
class LdapgroupsToUsergroups extends \Migrations\BaseMigration {
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
        $this->table('ldapgroups_to_usergroups')
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
            ->addColumn('usergroup_id', 'integer', [
                'default' => null,
                'limit'   => 11,
                'null'    => false,
            ])
            ->create();
    }
}
