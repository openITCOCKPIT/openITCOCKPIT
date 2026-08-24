<?php
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

// 2.
//	If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//	under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//	License agreement and license key will be shipped with the order
//	confirmation.

namespace MapModule\Controller;

use App\itnovum\openITCOCKPIT\Core\Permissions\MapContainersPermissions;
use App\Model\Table\ContainersTable;
use Authentication\IdentityInterface;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use itnovum\openITCOCKPIT\CakePHP\Folder;
use itnovum\openITCOCKPIT\Core\AngularJS\Api;
use itnovum\openITCOCKPIT\Core\UUID;
use itnovum\openITCOCKPIT\Core\ValueObjects\User;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\GenericFilter;
use MapModule\Model\Entity\Mapicon;
use MapModule\Model\Entity\MapUpload;
use MapModule\Model\Table\MapiconsTable;
use MapModule\Model\Table\MapsTable;
use MapModule\Model\Table\MapUploadsTable;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use ZipArchive;


/**
 * Class BackgroundUploadsController
 * @property MapUpload $MapUpload
 * @property Mapicon $Mapicon
 */
class BackgroundUploadsController extends AppController {
    //Only for ACLs
    public function index(): void {
        return;
    }

    public function backgrounds(): void {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }

        $GenericFilter = new GenericFilter($this->request);
        $GenericFilter->setFilters([
            'like' => [
                'MapUploads.upload_name'
            ]
        ]);

        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $GenericFilter->getPage());

        $MY_RIGHTS = $this->MY_RIGHTS;
        if ($this->hasRootPrivileges) {
            $MY_RIGHTS = [];
        }

        /** @var MapUploadsTable $MapUploadsTable */
        $MapUploadsTable = TableRegistry::getTableLocator()->get('MapModule.MapUploads');

        /** @var MapsTable $MapsTable */
        $MapsTable = TableRegistry::getTableLocator()->get('MapModule.Maps');

        $mapsWithBackgrounds = $MapsTable->getMapsWithBackgroundOnly($MY_RIGHTS);
        $mapsGroupedByBackground = [];
        foreach ($mapsWithBackgrounds as $mapsWithBackground) {
            $mapsGroupedByBackground[$mapsWithBackground['background']][] = [
                'id'   => $mapsWithBackground['id'],
                'name' => $mapsWithBackground['name'],
            ];
        }

        $backgrounds = $MapUploadsTable->getMapUploadsByTypeIndex(
            $GenericFilter,
            [MapUpload::TYPE_BACKGROUND],
            $PaginateOMat,
            $MY_RIGHTS
        );

        $backgroundsWithContainers = [];

        foreach ($backgrounds as $key => $background) {
            $backgroundsWithContainers[$background['id']] = [];
            foreach ($background['containers'] as $container) {
                $backgroundsWithContainers[$background['id']][] = $container['id'];
            }


            $backgrounds[$key]['allowEdit'] = true;
            if ($this->hasRootPrivileges === false) {
                $backgrounds[$key]['allowEdit'] = false;
                if (!empty(array_intersect($backgroundsWithContainers[$background['id']], $this->getWriteContainers()))) {
                    $backgrounds[$key]['allowEdit'] = true;
                }
            }
            $backgrounds[$key]['maps'] = $mapsGroupedByBackground[$background['saved_name']] ?? [];
        }
        $backgrounds = Hash::remove($backgrounds, '{n}.containers');

        $this->set('all_backgrounds', $backgrounds);
        $this->viewBuilder()->setOption('serialize', ['all_backgrounds']);
    }

    public function icons(): void {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }

        $GenericFilter = new GenericFilter($this->request);
        $GenericFilter->setFilters([
            'like' => [
                'MapUploads.upload_name'
            ]
        ]);

        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $GenericFilter->getPage());

        $MY_RIGHTS = $this->MY_RIGHTS;
        if ($this->hasRootPrivileges) {
            $MY_RIGHTS = [];
        }

        /** @var MapUploadsTable $MapUploadsTable */
        $MapUploadsTable = TableRegistry::getTableLocator()->get('MapModule.MapUploads');

        $mapsIconsWithMaps = $MapUploadsTable->getUsedMapIconsWithMapContainerIds($MY_RIGHTS);

        $mapsGroupedByIcon = [];
        foreach ($mapsIconsWithMaps as $mapsIconWithMaps) {
            $mapsGroupedByIcon[$mapsIconWithMaps['saved_name']][$mapsIconWithMaps['Mapicons']['map_id']] = [
                'id'   => $mapsIconWithMaps['Mapicons']['map_id'],
                'name' => $mapsIconWithMaps['Maps']['name'],
            ];
        }
        // reformat for andular
        foreach ($mapsGroupedByIcon as $key => $mapGroupedByIcon) {
            $mapsGroupedByIcon[$key] = Hash::extract($mapGroupedByIcon, '{n}');
        }

        $icons = $MapUploadsTable->getMapUploadsByTypeIndex(
            $GenericFilter,
            [MapUpload::TYPE_ICON],
            $PaginateOMat,
            $MY_RIGHTS
        );

        $iconsWithContainers = [];

        foreach ($icons as $key => $icon) {
            $iconsWithContainers[$icon['id']] = [];
            foreach ($icon['containers'] as $container) {
                $iconsWithContainers[$icon['id']][] = $container['id'];
            }


            $icons[$key]['allowEdit'] = true;
            if ($this->hasRootPrivileges === false) {
                $icons[$key]['allowEdit'] = false;
                if (!empty(array_intersect($iconsWithContainers[$icon['id']], $this->getWriteContainers()))) {
                    $icons[$key]['allowEdit'] = true;
                }
            }
            $icons[$key]['maps'] = $mapsGroupedByIcon[$icon['saved_name']] ?? [];
        }
        $icons = Hash::remove($icons, '{n}.containers');

        $this->set('all_icons', $icons);
        $this->viewBuilder()->setOption('serialize', ['all_icons']);
    }

    public function iconsets(): void {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }

        $GenericFilter = new GenericFilter($this->request);
        $GenericFilter->setFilters([
            'like' => [
                'MapUploads.upload_name'
            ]
        ]);

        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $GenericFilter->getPage());

        $MY_RIGHTS = $this->MY_RIGHTS;
        if ($this->hasRootPrivileges) {
            $MY_RIGHTS = [];
        }

        /** @var MapUploadsTable $MapUploadsTable */
        $MapUploadsTable = TableRegistry::getTableLocator()->get('MapModule.MapUploads');

        $mapsItemsWithMaps = $MapUploadsTable->getUsedMapItemsWithMapContainerIds($MY_RIGHTS);

        $mapsGroupedByItem = [];

        foreach ($mapsItemsWithMaps as $mapsItemWithMap) {
            $mapsGroupedByItem[$mapsItemWithMap['saved_name']][$mapsItemWithMap['Mapitems']['map_id']] = [
                'id'   => $mapsItemWithMap['Mapitems']['map_id'],
                'name' => $mapsItemWithMap['Maps']['name'],
            ];
        }

        // reformat for andular
        $reformatMapsGroupedByItem = [];
        foreach ($mapsGroupedByItem as $key => $mapGroupedByItem) {
            $reformatMapsGroupedByItem[$key] = Hash::extract($mapGroupedByItem, '{n}');
        }
        $mapGroupedByItem = $reformatMapsGroupedByItem;

        $items = $MapUploadsTable->getMapUploadsByTypeIndex(
            $GenericFilter,
            [MapUpload::TYPE_ICON_SET],
            $PaginateOMat,
            $MY_RIGHTS
        );

        $itemsWithContainers = [];

        foreach ($items as $key => $item) {
            $itemsWithContainers[$item['id']] = [];
            foreach ($item['containers'] as $container) {
                $itemsWithContainers[$item['id']][] = $container['id'];
            }


            $items[$key]['allowEdit'] = true;
            if ($this->hasRootPrivileges === false) {
                $items[$key]['allowEdit'] = false;
                if (!empty(array_intersect($itemsWithContainers[$item['id']], $this->getWriteContainers()))) {
                    $items[$key]['allowEdit'] = true;
                }
            }
            $items[$key]['maps'] = $mapGroupedByItem[$item['saved_name']] ?? [];
        }
        $items = Hash::remove($items, '{n}.containers');

        $this->set('all_items', $items);
        $this->viewBuilder()->setOption('serialize', ['all_items']);
    }

    public function editContainers($id = null): void {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var MapUploadsTable $MapUploadsTable */
        $MapUploadsTable = TableRegistry::getTableLocator()->get('MapModule.MapUploads');

        /** @var MapsTable $MapsTable */
        $MapsTable = TableRegistry::getTableLocator()->get('MapModule.Maps');

        if (!$MapUploadsTable->existsById($id)) {
            throw new NotFoundException(__('Uploaded file not found'));
        }

        $uploadedFile = $MapUploadsTable->getMapUploadById($id);
        $uploadedFile['containers'] = [
            '_ids' => Hash::extract($uploadedFile['containers'], '{n}.id')
        ];

        switch ($uploadedFile['upload_type']) {
            case MapUpload::TYPE_BACKGROUND:
                $type = 'backgrounds';
                break;
            case MapUpload::TYPE_ICON:
                $type = 'icons';
                break;
            case MapUpload::TYPE_ICON_SET:
                $type = 'items';
                break;
            default:
                $type = 'backgrounds';
        }

        $uploadedFile['path'] = sprintf('/map_module/img/%s/%s', $type, $uploadedFile['saved_name']);

        $requiredContainerIds = [];

        switch ($uploadedFile['upload_type']) {
            case MapUpload::TYPE_BACKGROUND:
                $requiredContainerIds = array_unique(
                    Hash::extract(
                        $MapsTable->getMapsWithMapContainerIdsByBackground($uploadedFile['saved_name']),
                        '{n}.containers.{n}.id'
                    )
                );
                break;
            case MapUpload::TYPE_ICON:
                $requiredContainerIds = array_unique(
                    Hash::extract(
                        $MapsTable->getMapsWithMapContainerIdsByIcon($uploadedFile['saved_name']),
                        '{n}.containers.{n}.id'
                    )
                );
                break;
            case MapUpload::TYPE_ICON_SET:
                $requiredContainerIds = array_unique(
                    Hash::extract(
                        $MapsTable->getMapsWithMapContainerIdsByItem($uploadedFile['saved_name']),
                        '{n}.containers.{n}.id'
                    )
                );
                break;
        }


        $MapContainersPermissions = new MapContainersPermissions(
            $requiredContainerIds,
            $this->getWriteContainers(),
            $this->hasRootPrivileges
        );

        if ($this->request->is('get')) {
            $this->set('areContainersChangeable', $MapContainersPermissions->areContainersChangeable());
            $this->set('requiredContainers', $requiredContainerIds);
            $this->set('uploadedFile', $uploadedFile);
            $this->viewBuilder()->setOption('serialize', [
                'uploadedFile', 'areContainersChangeable', 'requiredContainers'
            ]);
            return;
        }

        if ($this->request->is('post') || $this->request->is('put')) {
            $data = $this->request->getData('MapUpload', []);
            if ($MapContainersPermissions->areContainersChangeable() === false) {
                //Overwrite post data. User is not permitted to change container ids!
                $data['MapUpload']['containers']['_ids'] = $uploadedFile['MapUpload']['containers']['_ids'];
            }
            if (!empty($requiredContainers)) {
                //autofill required containers
                foreach ($requiredContainers as $requiredContainerId) {
                    $data['MapUpload']['containers']['_ids'][] = $requiredContainerId;
                }
            }

            $uploadedFileEntity = $MapUploadsTable->get($id);
            $uploadedFileEntity->setAccess('id', false);
            $uploadedFileEntity = $MapUploadsTable->patchEntity($uploadedFileEntity, $data);

            $MapUploadsTable->save($uploadedFileEntity);
            if ($uploadedFileEntity->hasErrors()) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', $uploadedFileEntity->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            } else {
                //No errors
                if ($this->isJsonRequest()) {
                    $this->serializeCake4Id($uploadedFileEntity); // REST API ID serialization
                    return;
                }
            }
            $this->set('uploadedFile', $uploadedFileEntity);
            $this->viewBuilder()->setOption('serialize', ['uploadedFile']);
        }
    }

    /**
     * @param $id
     * @return void
     */
    public function upload($id = null): void {
        $mapId = (int)$id;
        if (empty($_FILES)) {
            $response = [
                'success' => false,
                'message' => __('There is no file to store')
            ];
            $this->set('response', $response);
            $this->viewBuilder()->setOption('serialize', ['response']);
            return;
        }

        /** @var MapUploadsTable $MapUploadsTable */
        $MapUploadsTable = TableRegistry::getTableLocator()->get('MapModule.MapUploads');

        /** @var MapsTable $MapsTable */
        $MapsTable = TableRegistry::getTableLocator()->get('MapModule.Maps');

        $response = $MapUploadsTable->getUploadResponse($_FILES['file']['error']);
        if ($_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $backgroundImgDirectory = APP . '../' . 'plugins' . DS . 'MapModule' . DS . 'webroot' . DS . 'img' . DS . 'backgrounds';
            //check if upload folder exist
            if (!is_dir($backgroundImgDirectory)) {
                mkdir($backgroundImgDirectory);
            }

            $backgroundFolder = new Folder($backgroundImgDirectory);
            $fileExtension = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);

            if (!$MapUploadsTable->isFileExtensionSupported($fileExtension)) {
                $response = [
                    'success' => false,
                    'message' => __('File extension ".{0}" not supported!', $fileExtension)
                ];
                $this->set('response', $response);
                $this->viewBuilder()->setOption('serialize', ['response']);
                return;
            }

            $uploadFilename = str_replace('.' . $fileExtension, '', pathinfo($_FILES['file']['name'], PATHINFO_BASENAME));
            $saveFilename = UUID::v4();
            $fullFilePath = $backgroundFolder->path . DS . $saveFilename . '.' . $fileExtension;
            try {
                if (!move_uploaded_file($_FILES['file']['tmp_name'], $fullFilePath)) {
                    throw new \Exception(__('Cannot move uploaded file'));
                }

                $User = new User($this->getUser());
                // use map containers as initial map uploads containers
                $map = $MapsTable->getMapForEdit($mapId);
                $mapContainers = $map['Map']['containers']['_ids'];
                if (!$this->hasRootPrivileges) {
                    $mapContainers = array_intersect($mapContainers, $this->MY_RIGHTS);
                }

                if (empty($mapContainers)) {
                    $response = [
                        'success' => false,
                        'message' => __('Missing container permissions for upload!')
                    ];
                    $this->set('response', $response);
                    $this->viewBuilder()->setOption('serialize', ['response']);
                    return;
                }

                $imageConfig = [
                    'fullPath'      => $fullFilePath,
                    'uuidFilename'  => $saveFilename,
                    'fileExtension' => $fileExtension
                ];
                $MapUploadsTable->createThumbnailsFromBackgrounds($imageConfig, $backgroundFolder);
                $mapUpload = $MapUploadsTable->newEmptyEntity();
                $mapUpload = $MapUploadsTable->patchEntity($mapUpload, [
                    'upload_type' => MapUpload::TYPE_BACKGROUND,
                    'upload_name' => $uploadFilename . '.' . $fileExtension,
                    'saved_name'  => $saveFilename . '.' . $fileExtension,
                    'user_id'     => $User->getId(),
                    'containers'  => [
                        '_ids' => $mapContainers
                    ]
                ]);
                $MapUploadsTable->save($mapUpload);

                $response = [
                    'success'  => true,
                    'message'  => __('File uploaded successfully'),
                    'filename' => $saveFilename . '.' . $fileExtension
                ];
            } catch (\Exception $e) {
                $response = [
                    'success' => false,
                    'message' => __('Upload failed: {0}', $e->getMessage())
                ];
            }
        }


        $this->response->withStatus(200);
        if (!$response['success']) {
            $this->response->withStatus(500);
        }
        $this->set('response', $response);
        $this->viewBuilder()->setOption('serialize', ['response']);
    }

    public function delete() {
        if (!$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

        $filename = $this->request->getData('filename');

        /** @var MapsTable $MapsTable */
        $MapsTable = TableRegistry::getTableLocator()->get('MapModule.Maps');

        /** @var MapUploadsTable $MapUploadsTable */
        $MapUploadsTable = TableRegistry::getTableLocator()->get('MapModule.MapUploads');

        $MY_RIGHTS = $this->MY_RIGHTS;
        if ($this->hasRootPrivileges) {
            $MY_RIGHTS = [];
        }

        $background = $MapUploadsTable->getByFilename($filename, MapUpload::TYPE_BACKGROUND, $MY_RIGHTS);
        if (empty($background)) {
            throw new NotFoundException();
        }

        $backgroundEntity = $MapUploadsTable->get($background['id'], contain: [
            'Containers'
        ]);

        if (!$backgroundEntity) {
            throw new NotFoundException();
        }

        $uploadContainerIds = Hash::extract(
            $backgroundEntity,
            'containers.{n}.id'
        );

        $MapUploadContainersPermissions = new MapContainersPermissions(
            $uploadContainerIds,
            $this->getWriteContainers(),
            $this->hasRootPrivileges
        );

        // check map uploaded file permissions
        if (!$MapUploadContainersPermissions->areContainersChangeable()) {
            $response = [
                'success' => false,
                'message' => __('You do not have permissions to delete this file.')
            ];
            $this->set('response', $response);
            $this->viewBuilder()->setOption('serialize', ['response']);
            return;
        }

        if ($backgroundEntity['upload_type'] == MapUpload::TYPE_BACKGROUND) {
            $requiredContainerIds = array_unique(
                Hash::extract(
                    $MapsTable->getMapsWithMapContainerIdsByBackground($filename),
                    '{n}.containers.{n}.id'
                )
            );
            $MapContainersPermissions = new MapContainersPermissions(
                $requiredContainerIds,
                $this->getWriteContainers(),
                $this->hasRootPrivileges
            );

            // check used maps container permissions
            if (!$MapContainersPermissions->areContainersChangeable()) {
                $response = [
                    'success' => false,
                    'message' => __('You do not have permissions to delete this file, because it is used in maps with containers you have no write permissions for.')
                ];
                $this->set('response', $response);
                $this->viewBuilder()->setOption('serialize', ['response']);
                return;
            }
        }


        $MapsTable->updateAll([
            'background' => null
        ], [
            'background' => $background['saved_name']
        ]);

        $savedFileName = $backgroundEntity->saved_name;
        $MapUploadsTable->delete($backgroundEntity);
        if (!$backgroundEntity->hasErrors() && !empty($savedFileName)) {
            $backgroundImgDirectory = APP . '../' . 'plugins' . DS . 'MapModule' . DS . 'webroot' . DS . 'img' . DS . 'backgrounds';

            if (file_exists($backgroundImgDirectory . DS . $savedFileName)) {
                unlink($backgroundImgDirectory . DS . $savedFileName);
            }

            if (file_exists($backgroundImgDirectory . DS . 'thumb' . DS . 'thumb_' . $savedFileName)) {
                unlink($backgroundImgDirectory . DS . 'thumb' . DS . 'thumb_' . $savedFileName);
            }

            $response = [
                'success' => true,
                'message' => __('Background deleted successfully.')
            ];
            $this->set('response', $response);
            $this->viewBuilder()->setOption('serialize', ['response']);
            return;
        }

        $this->response->withStatus(500);
        $response = [
            'success' => false,
            'message' => __('Error while deleting background.')
        ];
        $this->set('response', $response);
        $this->viewBuilder()->setOption('serialize', ['response']);
    }

    /**
     * @param $id
     * @return void
     */
    public function icon($id = null): void {
        $mapId = (int)$id;
        if (empty($_FILES)) {
            $response = [
                'success' => false,
                'message' => __('There is no file to store')
            ];
            $this->set('response', $response);
            $this->viewBuilder()->setOption('serialize', ['response']);
            return;
        }

        /** @var MapUploadsTable $MapsTable */
        $MapUploadsTable = TableRegistry::getTableLocator()->get('MapModule.MapUploads');

        /** @var MapsTable $MapsTable */
        $MapsTable = TableRegistry::getTableLocator()->get('MapModule.Maps');

        $response = $MapUploadsTable->getUploadResponse($_FILES['file']['error']);
        if ($_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $iconImgDirectory = APP . '../' . 'plugins' . DS . 'MapModule' . DS . 'webroot' . DS . 'img' . DS . 'icons';

            //$iconFolder = new Folder($iconImgDirectory);
            $fileExtension = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);

            if (!$MapUploadsTable->isFileExtensionSupported($fileExtension)) {
                $response = [
                    'success' => false,
                    'message' => __('File extension ".{0}" not supported!', $fileExtension)
                ];
                $this->set('response', $response);
                $this->viewBuilder()->setOption('serialize', ['response']);
                return;
            }

            $fileName = preg_replace('/[^a-zA-Z0-9\.]+/', '', $_FILES['file']['name']);
            $uploadFilename = str_replace('.' . $fileExtension, '', pathinfo($_FILES['file']['name'], PATHINFO_BASENAME));
            $saveFilename = UUID::v4();
            $saveFilename = $saveFilename . '.' . $fileExtension;

            try {
                //check if icon folder exist
                if (!is_dir($iconImgDirectory)) {
                    mkdir($iconImgDirectory);
                }

                if (!move_uploaded_file($_FILES['file']['tmp_name'], $iconImgDirectory . DS . $saveFilename)) {
                    throw new \Exception(__('Cannot move uploaded file'));
                }


                $User = new User($this->getUser());
                // use map containers as initial map uploads containers
                $map = $MapsTable->getMapForEdit($mapId);
                $mapContainers = $map['Map']['containers']['_ids'];
                if (!$this->hasRootPrivileges) {
                    $mapContainers = array_intersect($mapContainers, $this->MY_RIGHTS);
                }

                if (empty($mapContainers)) {
                    $response = [
                        'success' => false,
                        'message' => __('Missing container permissions for upload!')
                    ];
                    $this->set('response', $response);
                    $this->viewBuilder()->setOption('serialize', ['response']);
                    return;
                }

                $mapUpload = $MapUploadsTable->newEmptyEntity();
                $mapUpload = $MapUploadsTable->patchEntity($mapUpload, [
                    'upload_type' => MapUpload::TYPE_ICON,
                    'upload_name' => $uploadFilename . '.' . $fileExtension,
                    'saved_name'  => $saveFilename,
                    'user_id'     => $User->getId(),
                    'containers'  => [
                        '_ids' => $mapContainers
                    ]
                ]);
                $MapUploadsTable->save($mapUpload);

                $response = [
                    'success'  => true,
                    'message'  => __('File uploaded successfully'),
                    'filename' => $saveFilename
                ];
            } catch (\Exception $e) {
                $response = [
                    'success' => false,
                    'message' => __('Upload failed: {0}', $e->getMessage())
                ];
            }
        }

        $this->response->withStatus(200);
        if (!$response['success']) {
            $this->response->withStatus(500);
        }
        $this->set('response', $response);
        $this->viewBuilder()->setOption('serialize', ['response']);
    }

    public function deleteIcon() {
        if (!$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

        $iconImgDirectory = APP . '../' . 'plugins' . DS . 'MapModule' . DS . 'webroot' . DS . 'img' . DS . 'icons';
        $filename = $this->request->getData('filename');

        /** @var MapUploadsTable $MapUploadsTable */
        $MapUploadsTable = TableRegistry::getTableLocator()->get('MapModule.MapUploads');

        $MY_RIGHTS = $this->MY_RIGHTS;
        if ($this->hasRootPrivileges) {
            $MY_RIGHTS = [];
        }

        $icon = $MapUploadsTable->getByFilename($filename, MapUpload::TYPE_ICON, $MY_RIGHTS);
        if (empty($icon)) {
            throw new NotFoundException();
        }

        $iconEntity = $MapUploadsTable->get($icon['id'], contain: [
            'Containers'
        ]);

        if (!$iconEntity) {
            throw new NotFoundException();
        }

        $uploadContainerIds = Hash::extract(
            $iconEntity,
            'containers.{n}.id'
        );

        $MapUploadContainersPermissions = new MapContainersPermissions(
            $uploadContainerIds,
            $this->getWriteContainers(),
            $this->hasRootPrivileges
        );
        // check map uploaded file permissions
        if (!$MapUploadContainersPermissions->areContainersChangeable()) {
            $response = [
                'success' => false,
                'message' => __('You do not have permissions to delete this file.')
            ];
            $this->set('response', $response);
            $this->viewBuilder()->setOption('serialize', ['response']);
            return;
        }

        /** @var MapsTable $MapsTable */
        $MapsTable = TableRegistry::getTableLocator()->get('MapModule.Maps');

        $requiredContainerIds = array_unique(
            Hash::extract(
                $MapsTable->getMapsWithMapContainerIdsByIcon($filename),
                '{n}.containers.{n}.id'
            )
        );


        $MapContainersPermissions = new MapContainersPermissions(
            $requiredContainerIds,
            $this->getWriteContainers(),
            $this->hasRootPrivileges
        );

        // check used maps container permissions
        if (!$MapContainersPermissions->areContainersChangeable()) {
            $response = [
                'success' => false,
                'message' => __('You do not have permissions to delete this file, because it is used in maps with containers you have no write permissions for.')
            ];
            $this->set('response', $response);
            $this->viewBuilder()->setOption('serialize', ['response']);
            return;
        }

        /** @var MapiconsTable $MapiconsTable */
        $MapiconsTable = TableRegistry::getTableLocator()->get('MapModule.Mapicons');

        $MapiconsTable->deleteAll(['Mapicon.icon' => $filename]);
        if ($MapUploadsTable->delete($iconEntity)) {
            $response = [
                'success' => true,
                'message' => __('Icon deleted successfully.')
            ];

            $savedFileName = $iconEntity->saved_name;
            $MapUploadsTable->delete($iconEntity);

            if (!$iconEntity->hasErrors() && !empty($savedFileName)) {
                $fullFilePath = $iconImgDirectory . DS . $savedFileName;

                if (file_exists($fullFilePath)) {
                    unlink($fullFilePath);
                }

                $response = [
                    'success' => true,
                    'message' => __('Icon deleted successfully.')
                ];
                $this->set('response', $response);
                $this->viewBuilder()->setOption('serialize', ['response']);
                return;
            }

            $this->set('response', $response);
            $this->viewBuilder()->setOption('serialize', ['response']);
            return;
        }

        $this->response->withStatus(500);
        $response = [
            'success' => false,
            'message' => __('Error while deleting icon.')
        ];
        $this->set('response', $response);
        $this->viewBuilder()->setOption('serialize', ['response']);
    }

    /**
     * @param $id
     * @return void
     */
    public function iconset($id = null): void {
        $mapId = (int)$id;
        if (empty($_FILES)) {
            $response = [
                'success' => false,
                'message' => __('There is no file to store')
            ];
            $this->set('response', $response);
            $this->viewBuilder()->setOption('serialize', ['response']);
            return;
        }

        /** @var MapUploadsTable $MapUploadsTable */
        $MapUploadsTable = TableRegistry::getTableLocator()->get('MapModule.MapUploads');

        /** @var MapsTable $MapsTable */
        $MapsTable = TableRegistry::getTableLocator()->get('MapModule.Maps');

        $response = $MapUploadsTable->getUploadResponse($_FILES['file']['error']);
        if ($_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $iconsetImgDirectory = APP . '../' . 'plugins' . DS . 'MapModule' . DS . 'webroot' . DS . 'img' . DS . 'items';
            $tempZipsDirectory = APP . '../' . 'plugins' . DS . 'MapModule' . DS . 'webroot' . DS . 'img' . DS . 'temp';

            if (!is_dir($tempZipsDirectory)) {
                mkdir($tempZipsDirectory);
            }

            //$iconFolder = new Folder($iconImgDirectory);
            $fileExtension = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);

            if ($fileExtension !== 'zip') {
                $response = [
                    'success' => false,
                    'message' => __('Iconsets needs to be packed as an .zip file.', $fileExtension)
                ];
                $this->set('response', $response);
                $this->viewBuilder()->setOption('serialize', ['response']);
                return;
            }

            $fileName = preg_replace('/[^a-zA-Z0-9\.\_]+/', '', $_FILES['file']['name']);

            try {
                //check if iconset folder exist
                if (!is_dir($iconsetImgDirectory)) {
                    mkdir($iconsetImgDirectory);
                }

                if (!move_uploaded_file($_FILES['file']['tmp_name'], $tempZipsDirectory . DS . $fileName)) {
                    throw new \Exception(__('Cannot move uploaded file'));
                }

                $zipFile = new ZipArchive();
                $openZip = $zipFile->open($tempZipsDirectory . DS . $fileName);
                if (!$openZip) {
                    throw new \Exception(__('Could not open uploaded zip file.'));
                }

                $unzipDirectory = $tempZipsDirectory . DS . 'uploaded_' . str_replace('.zip', '', $fileName);

                if (!is_dir($unzipDirectory)) {
                    mkdir($unzipDirectory);
                }
                $zipFile->extractTo($unzipDirectory);
                $zipFile->close();

                //Remove uploaded zip file
                unlink($tempZipsDirectory . DS . $fileName);

                $finder = new Finder();
                $finder->directories()->in($unzipDirectory);

                $hasDirectory = false;
                $iconsetName = null;
                $iconsetIcons = [];

                /** @var SplFileInfo $folder */
                foreach ($finder as $folder) {
                    //In the folder was a zip with the icons
                    $hasDirectory = true;
                    $uploadedIconsetDirectoryName = $folder->getFilename();
                    $iconsetName = preg_replace('/[^a-zA-Z0-9\.\_]+/', '', $uploadedIconsetDirectoryName);

                    /** @var SplFileInfo $image */
                    foreach ($finder->files()->in($unzipDirectory . DS . $uploadedIconsetDirectoryName) as $image) {
                        // We only need that whitelist of files.
                        if (!in_array($image->getFilename(), $MapUploadsTable->getIconsNames(), true)) {
                            //Remove tmp directory
                            $fs = new Filesystem();
                            $fs->remove($unzipDirectory);
                            throw new \Exception(sprintf(
                                'Additional files found. "%s" does not belong in an icon set.',
                                $image->getFilename()
                            ));
                        }
                        $iconsetIcons[$image->getFilename()] = [
                            'filename' => $image->getFilename(),
                            'path'     => $image->getPath(),
                            'full'     => $image->getPath() . DS . $image->getFilename()
                        ];
                    }
                    break; //Only one loop to get to the directory name
                }


                if ($hasDirectory === false) {
                    $iconsetName = preg_replace('/[^a-zA-Z0-9\.\_]+/', '', str_replace('.zip', '', $fileName));
                    //May be inside of the zip are only icons. (Not folder with icons)
                    /** @var SplFileInfo $image */
                    foreach ($finder->files()->in($unzipDirectory) as $image) {
                        // We only need that whitelist of files.
                        if (!in_array($image->getFilename(), $MapUploadsTable->getIconsNames(), true)) {
                            //Remove tmp directory
                            $fs = new Filesystem();
                            $fs->remove($unzipDirectory);
                            throw new \Exception(sprintf(
                                'Additional files found. "%s" does not belong in an icon set.',
                                $image->getFilename()
                            ));
                        }
                        $iconsetIcons[$image->getFilename()] = [
                            'filename' => $image->getFilename(),
                            'path'     => $image->getPath(),
                            'full'     => $image->getPath() . DS . $image->getFilename()
                        ];
                    }
                }

                if ($iconsetName === null || $iconsetName === '') {
                    //Remove tmp directory
                    $fs = new Filesystem();
                    $fs->remove($unzipDirectory);

                    throw new \Exception('Icon set name is empty');
                }

                //Check if all required icons exists and make sure the images are PNGs
                $missingIcons = [];
                $notAPng = [];
                foreach ($MapUploadsTable->getIconsNames() as $iconsName) {
                    if (!isset($iconsetIcons[$iconsName])) {
                        $missingIcons[] = $iconsName;
                    } else {
                        //Make sure we have a png
                        if (exif_imagetype($iconsetIcons[$iconsName]['full']) !== IMAGETYPE_PNG) {
                            $notAPng[] = $iconsName;
                        }
                    }
                }

                if (!empty($missingIcons) || !empty($notAPng)) {
                    $error = '';
                    if (!empty($missingIcons)) {
                        $error .= sprintf(
                            'Thow following icons are missing in uploaded zip archive: %s',
                            implode(', ', $missingIcons)
                        );
                    }

                    if (!empty($notAPng)) {
                        $error .= sprintf(
                            'The following icons are not a PNG image: %s',
                            implode(', ', $notAPng)
                        );
                    }

                    //Remove tmp directory
                    $fs = new Filesystem();
                    $fs->remove($unzipDirectory);

                    throw new \Exception($error);

                }

                //Copy new icons into iconsets directory
                $destinationDirectory = $iconsetImgDirectory . DS . $iconsetName;
                if (is_dir($destinationDirectory)) {
                    throw new \Exception(sprintf(
                        'Iconset "%s" already exists',
                        $iconsetName
                    ));
                }

                mkdir($destinationDirectory);
                if (!is_dir($destinationDirectory)) {

                    //Remove tmp directory
                    $fs = new Filesystem();
                    $fs->remove($unzipDirectory);
                    throw new \Exception('Could not create directory: ' . $destinationDirectory);
                }


                foreach ($iconsetIcons as $icon) {
                    copy($icon['full'], $destinationDirectory . DS . $icon['filename']);
                }

                //Remove tmp directory
                $fs = new Filesystem();
                $fs->remove($unzipDirectory);
                $User = new User($this->getUser());
                // use map containers as initial map uploads containers
                $map = $MapsTable->getMapForEdit($mapId);
                $mapContainers = $map['Map']['containers']['_ids'];
                if (!$this->hasRootPrivileges) {
                    $mapContainers = array_intersect($mapContainers, $this->MY_RIGHTS);
                }

                if (!empty($mapContainers)) {
                    $mapUpload = $MapUploadsTable->newEmptyEntity();
                    $mapUpload = $MapUploadsTable->patchEntity($mapUpload, [
                        'upload_type' => MapUpload::TYPE_ICON_SET,
                        'upload_name' => $iconsetName,
                        'saved_name'  => $iconsetName,
                        'user_id'     => $User->getId(),
                        'containers'  => [
                            '_ids' => $mapContainers
                        ]
                    ]);
                    $MapUploadsTable->save($mapUpload);
                }

                $response = [
                    'success'     => true,
                    'message'     => __('File uploaded successfully'),
                    'iconsetname' => $iconsetName
                ];
            } catch (\Exception $e) {
                $response = [
                    'success' => false,
                    'message' => __('Upload failed: {0}', $e->getMessage())
                ];
            }
        }

        $this->response->withStatus(200);
        if (!$response['success']) {
            $this->response->withStatus(500);
        }
        $this->set('response', $response);
        $this->viewBuilder()->setOption('serialize', ['response']);
    }

    /**
     * @return IdentityInterface|null
     */
    public function getUser() {
        return $this->Authentication->getIdentity();
    }

    public function loadContainers() {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var ContainersTable $ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');

        if ($this->hasRootPrivileges === true) {
            $containers = $ContainersTable->easyPath($this->MY_RIGHTS, OBJECT_HOST, [], $this->hasRootPrivileges, [CT_HOSTGROUP]);
        } else {
            $containers = $ContainersTable->easyPath($this->getWriteContainers(), OBJECT_HOST, [], $this->hasRootPrivileges, [CT_HOSTGROUP]);
        }


        $this->set('containers', Api::makeItJavaScriptAble($containers));
        $this->viewBuilder()->setOption('serialize', ['containers']);
    }
}
