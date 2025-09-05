<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * OrganizationalChartsFixture
 */
class OrganizationalChartsFixture extends TestFixture
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
                'name' => 'Lorem ipsum dolor sit amet',
                'description' => 'Lorem ipsum dolor sit amet',
                'modified' => '2025-06-17 11:04:43',
                'created' => '2025-06-17 11:04:43',
            ],
        ];
        parent::init();
    }
}
