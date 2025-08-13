<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * Class AddModuleToConfigurationQueue
 *
 * Created:
 * oitc migrations create AddModuleToConfigurationQueue
 *
 * Usage:
 * openitcockpit-update
 */
class AddModuleToConfigurationQueue extends \Migrations\BaseMigration {

    public function up(): void {
        $this->table('configuration_queue')
            ->addColumn('module', 'string', [
                'default' => null,
                'limit'   => 255,
                'null'    => true,
                'after'   => 'json_data'
            ])
            ->update();
    }

    public function down(): void {
        $this->table('configuration_queue')
            ->removeColumn('module')
            ->update();
    }
}
