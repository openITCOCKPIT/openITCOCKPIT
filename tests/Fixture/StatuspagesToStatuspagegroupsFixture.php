<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * StatuspagesToStatuspagegroupsFixture
 */
class StatuspagesToStatuspagegroupsFixture extends TestFixture
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
                'collection_id' => 1,
                'category_id' => 1,
                'statuspage_id' => 1,
                'modified' => '2025-09-03 08:04:21',
                'created' => '2025-09-03 08:04:21',
            ],
        ];
        parent::init();
    }
}
