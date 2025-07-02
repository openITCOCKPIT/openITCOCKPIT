<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Table\ContainersTable;
use App\Model\Table\UsersTable;
use itnovum\openITCOCKPIT\Core\AngularJS\Api;

/**
 * OrganizationalChartNodes Controller
 *
 * @property \App\Model\Table\OrganizationalChartNodesTable $OrganizationalChartNodes
 * @method \App\Model\Entity\OrganizationalChartNode[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class OrganizationalChartNodesController extends AppController {
    public function loadUsers($containerIds = null) {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var $ContainersTable ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');
        /** @var $UsersTable UsersTable */
        $UsersTable = TableRegistry::getTableLocator()->get('Users');

        $containerIds = $ContainersTable->resolveChildrenOfContainerIds($containerIds);
        $users = $UsersTable->usersByContainerId($containerIds, 'list');
        $users = Api::makeItJavaScriptAble($users);

        $data = [
            'users'    => $users,
            'managers' => $users
        ];
        $this->set($data);
        $this->viewBuilder()->setOption('serialize', array_keys($data));
    }
}
