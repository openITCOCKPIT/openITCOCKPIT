<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * UsersToOrganizationalChartStructuresFixture
 */
class UsersToOrganizationalChartStructuresFixture extends TestFixture
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
                'user_id' => 1,
                'organizational_chart_structure_id' => 1,
                'is_manager' => 1,
                'user_role' => 1,
            ],
        ];
        parent::init();
    }
}
