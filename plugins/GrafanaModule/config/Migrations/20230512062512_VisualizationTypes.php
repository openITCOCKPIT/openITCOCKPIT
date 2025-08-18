<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * Class VisualizationTypes
 *
 * Created via:
 * oitc migrations create -p GrafanaModule VisualizationTypes
 *
 * Run migration:
 * oitc migrations migrate -p GrafanaModule
 *
 */
class VisualizationTypes extends \Migrations\BaseMigration {
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function change(): void {

        $this->table('grafana_userdashboard_panels')
            ->addColumn('visualization_type', 'string', [
                'default' => 'timeseries',
                'limit'   => 255,
                'null'    => false,
                'after'   => 'title'
            ])
            ->update();
    }
}
