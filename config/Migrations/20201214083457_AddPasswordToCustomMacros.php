<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * Class AddPasswordToCustomMacros
 *
 * Created:
 * oitc migrations create AddPasswordToCustomMacros
 *
 * Usage:
 * openitcockpit-update
 */
class AddPasswordToCustomMacros extends \Migrations\BaseMigration {
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function change(): void {
        $table = $this->table('customvariables');
        $table
            ->addColumn('password', 'integer', [
                'after'   => 'value',
                'default' => 0,
                'limit'   => 2,
                'null'    => false
            ])
            ->update();
    }
}
