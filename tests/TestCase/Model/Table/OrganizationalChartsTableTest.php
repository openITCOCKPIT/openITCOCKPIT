<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\OrganizationalChartsTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\OrganizationalChartsTable Test Case
 */
class OrganizationalChartsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\OrganizationalChartsTable
     */
    protected $OrganizationalCharts;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected $fixtures = [
        'app.OrganizationalCharts',
        'app.OrganizationalChartStructures',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('OrganizationalCharts') ? [] : ['className' => OrganizationalChartsTable::class];
        $this->OrganizationalCharts = $this->getTableLocator()->get('OrganizationalCharts', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->OrganizationalCharts);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @uses \App\Model\Table\OrganizationalChartsTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
