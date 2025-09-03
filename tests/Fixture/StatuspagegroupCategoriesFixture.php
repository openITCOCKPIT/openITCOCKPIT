<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * StatuspagegroupCategoriesFixture
 */
class StatuspagegroupCategoriesFixture extends TestFixture
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
                'statuspagegroup_id' => 1,
                'name' => 'Lorem ipsum dolor sit amet',
                'modified' => '2025-09-03 08:13:38',
                'created' => '2025-09-03 08:13:38',
            ],
        ];
        parent::init();
    }
}
