<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * OrganizationalChartConnectionsFixture
 */
class OrganizationalChartConnectionsFixture extends TestFixture
{
    /**
     * Init method
     *
     * @return void
     */
    public function init(): void
    {
        $this->records = [
            [
                'id' => 1,
                'organizational_chart_id' => 1,
                'organizational_chart_input_node_id' => 1,
                'organizational_chart_output_node_id' => 1,
                'modified' => '2025-07-02 11:16:05',
                'created' => '2025-07-02 11:16:05',
            ],
        ];
        parent::init();
    }
}
