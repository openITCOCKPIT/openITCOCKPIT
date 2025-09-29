<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\StatuspagegroupCategoriesTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\StatuspagegroupCategoriesTable Test Case
 */
class StatuspagegroupCategoriesTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\StatuspagegroupCategoriesTable
     */
    protected $StatuspagegroupCategories;

    /**
     * Fixtures
     *
     * @var list<string>
     */
    protected array $fixtures = [
        'app.StatuspagegroupCategories',
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
        $config = $this->getTableLocator()->exists('StatuspagegroupCategories') ? [] : ['className' => StatuspagegroupCategoriesTable::class];
        $this->StatuspagegroupCategories = $this->getTableLocator()->get('StatuspagegroupCategories', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->StatuspagegroupCategories);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @link \App\Model\Table\StatuspagegroupCategoriesTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @link \App\Model\Table\StatuspagegroupCategoriesTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
