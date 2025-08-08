<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * Class SaveMapeditorSettings
 * Created:
 * oitc migrations create -p MapModule SaveMapeditorSettings
 *
 * Usage:
 * openitcockpit-update
 */
class SaveMapeditorSettings extends \Migrations\BaseMigration {
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function change(): void {
        $table = $this->table('maps');
        $table->addColumn('json_data', 'string', [
            'after'   => 'refresh_interval',
            'default' => null,
            'limit'   => 2000,
            'null'    => true,
        ])->update();
    }
}
