<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\OrganizationalChartConnectionsTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\OrganizationalChartConnectionsTable Test Case
 */
class OrganizationalChartConnectionsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\OrganizationalChartConnectionsTable
     */
    protected $OrganizationalChartConnections;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected $fixtures = [
        'app.OrganizationalChartConnections',
        'app.OrganizationalCharts',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('OrganizationalChartConnections') ? [] : ['className' => OrganizationalChartConnectionsTable::class];
        $this->OrganizationalChartConnections = $this->getTableLocator()->get('OrganizationalChartConnections', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->OrganizationalChartConnections);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @uses \App\Model\Table\OrganizationalChartConnectionsTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @uses \App\Model\Table\OrganizationalChartConnectionsTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
