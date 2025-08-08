<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * Class StackingMode
 *
 * Created via:
 * oitc migrations create -p GrafanaModule StackingMode
 *
 * Run migration:
 * oitc migrations migrate -p GrafanaModule
 *
 */
class StackingMode extends \Migrations\BaseMigration {
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function change(): void {

        $this->table('grafana_userdashboard_panels')
            ->addColumn('stacking_mode', 'string', [
                'default' => 'none',
                'limit'   => 255,
                'null'    => false,
                'after'   => 'visualization_type'
            ])
            ->update();
    }
}
