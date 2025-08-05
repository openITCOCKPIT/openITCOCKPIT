<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\OrganizationalChartStructuresTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\OrganizationalChartStructuresTable Test Case
 */
class OrganizationalChartStructuresTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\OrganizationalChartStructuresTable
     */
    protected $OrganizationalChartStructures;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected $fixtures = [
        'app.OrganizationalChartStructures',
        'app.OrganizationalCharts',
        'app.Containers',
        'app.UsersToOrganizationalChartStructures',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('OrganizationalChartStructures') ? [] : ['className' => OrganizationalChartStructuresTable::class];
        $this->OrganizationalChartStructures = $this->getTableLocator()->get('OrganizationalChartStructures', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->OrganizationalChartStructures);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @uses \App\Model\Table\OrganizationalChartStructuresTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @uses \App\Model\Table\OrganizationalChartStructuresTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
