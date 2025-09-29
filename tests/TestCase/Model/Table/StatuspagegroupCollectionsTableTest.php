<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\StatuspagegroupCollectionsTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\StatuspagegroupCollectionsTable Test Case
 */
class StatuspagegroupCollectionsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\StatuspagegroupCollectionsTable
     */
    protected $StatuspagegroupCollections;

    /**
     * Fixtures
     *
     * @var list<string>
     */
    protected array $fixtures = [
        'app.StatuspagegroupCollections',
        'app.Statuspagegroups',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('StatuspagegroupCollections') ? [] : ['className' => StatuspagegroupCollectionsTable::class];
        $this->StatuspagegroupCollections = $this->getTableLocator()->get('StatuspagegroupCollections', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->StatuspagegroupCollections);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @link \App\Model\Table\StatuspagegroupCollectionsTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @link \App\Model\Table\StatuspagegroupCollectionsTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
