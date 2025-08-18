<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * Class MysqlWizardMigration
 *
 * Created via:
 * oitc migrations create MysqlWizardMigration
 *
 * Run migration:
 * oitc migrations migrate
 *
 */
class MysqlWizardMigration extends \Migrations\BaseMigration {

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
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function change(): void {
        $this->table('wizard_assignments')
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
            ->addColumn('type_id', 'string', [
                'default' => null,
                'limit'   => 37,
                'null'    => false,
            ])
            ->addIndex(
                [
                    'uuid',
                ],
                ['unique' => true]
            )
            ->create();

        $this->table('servicetemplates_to_wizard_assignments')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default'       => null,
                'limit'         => 11,
                'null'          => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('servicetemplate_id', 'integer', [
                'default' => null,
                'limit'   => 11,
                'null'    => false,
            ])
            ->addColumn('wizard_assignment_id', 'integer', [
                'default' => null,
                'limit'   => 11,
                'null'    => false,
            ])
            ->addIndex(
                [
                    'wizard_assignment_id',
                ]
            )
            ->addIndex(
                [
                    'servicetemplate_id',
                ]
            )
            ->create();
    }
}
