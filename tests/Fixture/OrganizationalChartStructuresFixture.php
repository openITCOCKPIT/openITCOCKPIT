<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * OrganizationalChartStructuresFixture
 */
class OrganizationalChartStructuresFixture extends TestFixture
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
                'parent_id' => 1,
                'lft' => 1,
                'rght' => 1,
                'organizational_chart_id' => 1,
                'container_id' => 1,
            ],
        ];
        parent::init();
    }
}
