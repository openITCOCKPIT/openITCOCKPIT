<?php declare(strict_types=1);
// Copyright (C) 2015-2025  it-novum GmbH
// Copyright (C) 2025-today AVENDIS GmbH
//
// This file is dual licensed
//
// 1.
//     This program is free software: you can redistribute it and/or modify
//     it under the terms of the GNU General Public License as published by
//     the Free Software Foundation, version 3 of the License.
//
//     This program is distributed in the hope that it will be useful,
//     but WITHOUT ANY WARRANTY; without even the implied warranty of
//     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//     GNU General Public License for more details.
//
//     You should have received a copy of the GNU General Public License
//     along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
// 2.
//     If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//     under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//     License agreement and license key will be shipped with the order
//     confirmation.
//

namespace App\Model\Behavior;

use App\Controller\AppController;
use App\itnovum\openITCOCKPIT\Core\Permissions\MyRightsFactory;
use ArrayObject;
use Cake\Cache\Cache;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\Http\Exception\UnauthorizedException;
use Cake\ORM\Behavior;
use Cake\Routing\Router;


/**
 * ContainerOwned Behavior
 *
 * I am the verification layer that makes it easy to limit write-access to Entities that belong to a single container.
 *
 */
class ContainerOwnedBehavior extends Behavior {
    public const SKIP_OWNERSHIP_CHECK = 'skip_ownership_check';

    /**
     * I will verify that the given $entity can be modified by the logged-in user.
     * @throws UnauthorizedException In case the User has no permission to modify elements based on their container_id.
     * @see ContainerOwnedBehavior::canEditEntity()
     */
    public function beforeDelete(EventInterface $event, EntityInterface $entity, ArrayObject $options): void {
        if (!$this->canEditEntity($event, $entity, $options)) {
            throw new UnauthorizedException(__('Deleting not permitted: You do not have write permissions to the container_id') . ' #' . $entity->container_id);
        }
    }

    /**
     * I will verify that the given $entity can be modified by the logged-in user.
     * @throws UnauthorizedException In case the User has no permission to modify elements based on their container_id.
     * @see ContainerOwnedBehavior::canEditEntity()
     */
    public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void {
        if (!$this->canEditEntity($event, $entity, $options)) {
            throw new UnauthorizedException(__('Editing not permitted: You do not have write permissions to the container_id') . ' #' . $entity->container_id);
        }
    }

    /**
     * I will verify that the given $entity can be modified by the logged-in user.
     * @param EventInterface $event
     * @param EntityInterface $entity
     * @param ArrayObject $options
     * @return bool TRUE: The current user is allowed to edit the entity. FALSE: He is not.
     */
    private function canEditEntity(EventInterface $event, EntityInterface $entity, ArrayObject $options): bool {
        // Skippable: CLI / cronjobs / setup / migrations and if you pass it explicitly.
        if (($options[self::SKIP_OWNERSHIP_CHECK] ?? false) === true) {
            return true;
        }

        // If container_id is not set, do not verify.
        $containerId = (int)($entity->get('container_id') ?? 0);
        if (!$containerId) {
            return true;
        }

        $permissions = $this->getPermissions();
        if ($permissions === null) {
            // No request (CLI) - nothing to check
            return true;
        }

        // hasRootPrivileges?
        if (($permissions['hasRootPrivileges'] ?? false) === true) {
            return true;
        }

        $containerIdsToCheck = [$containerId];

        // Moving an entity out of a container requires write permissions to the old container as well.
        if (!$entity->isNew() && $entity->isDirty('container_id')) {
            $originalContainerId = (int)($entity->getOriginal('container_id') ?? 0);
            if ($originalContainerId > 0 && $originalContainerId !== $containerId) {
                $containerIdsToCheck[] = $originalContainerId;
            }
        }

        foreach ($containerIdsToCheck as $containerIdToCheck) {
            $permissionLevel = $permissions['MY_RIGHTS_LEVEL'][$containerIdToCheck] ?? null;
            if ((int)$permissionLevel !== WRITE_RIGHT) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array|null
     */
    private function getPermissions(): ?array {
        $identity = Router::getRequest()?->getAttribute('identity');
        if ($identity === null) {
            return null;
        }

        /**
         * This is the same key as here:
         * @see AppController::beforeFilter()
         */
        // Same cache key and cache config as used by AppController::beforeFilter()
        $cacheKey = 'userPermissions_' . $identity->get('id');
        $permissions = Cache::read($cacheKey, 'permissions');

        // Fallback to put the userPermissions tot he cache if not cached previously.
        if ($permissions === null) {
            $permissions = MyRightsFactory::getUserPermissions($identity->get('id'), $identity->get('usergroup_id'));
            Cache::write($cacheKey, $permissions, 'permissions');
        }

        return $permissions;
    }

}
