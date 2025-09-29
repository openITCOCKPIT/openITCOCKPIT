<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\StatuspagegroupsTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\StatuspagegroupsTable Test Case
 */
class StatuspagegroupsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\StatuspagegroupsTable
     */
    protected $Statuspagegroups;

    /**
     * Fixtures
     *
     * @var list<string>
     */
    protected array $fixtures = [
        'app.Statuspagegroups',
        'app.Containers',
        'app.StatuspagegroupCategories',
        'app.StatuspagegroupCollections',
        'app.StatuspagesToStatuspagegroups',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('Statuspagegroups') ? [] : ['className' => StatuspagegroupsTable::class];
        $this->Statuspagegroups = $this->getTableLocator()->get('Statuspagegroups', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->Statuspagegroups);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @link \App\Model\Table\StatuspagegroupsTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @link \App\Model\Table\StatuspagegroupsTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
