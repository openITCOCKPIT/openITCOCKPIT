<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\OrganizationalChartNodesController;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\OrganizationalChartStructuresController Test Case
 *
 * @uses \App\Controller\OrganizationalChartNodesController
 */
class OrganizationalChartStructuresControllerTest extends TestCase
{
    use IntegrationTestTrait;

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
     * Test index method
     *
     * @return void
     * @uses \App\Controller\OrganizationalChartNodesController::index()
     */
    public function testIndex(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test view method
     *
     * @return void
     * @uses \App\Controller\OrganizationalChartNodesController::view()
     */
    public function testView(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test add method
     *
     * @return void
     * @uses \App\Controller\OrganizationalChartNodesController::add()
     */
    public function testAdd(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test edit method
     *
     * @return void
     * @uses \App\Controller\OrganizationalChartNodesController::edit()
     */
    public function testEdit(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test delete method
     *
     * @return void
     * @uses \App\Controller\OrganizationalChartNodesController::delete()
     */
    public function testDelete(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
