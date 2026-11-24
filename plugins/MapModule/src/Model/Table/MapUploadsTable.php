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

declare(strict_types=1);

namespace MapModule\Model\Table;

use App\Lib\Traits\PaginationAndScrollIndexTrait;
use App\Model\Table\ContainersTable;
use App\Model\Table\UsersTable;
use Cake\Core\Exception\Exception;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Association\BelongsTo;
use Cake\ORM\Association\BelongsToMany;
use Cake\ORM\Behavior\TimestampBehavior;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Utility\Hash;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\CakePHP\Folder;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\GenericFilter;
use MapModule\Model\Entity\MapUpload;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * MapUploads Model
 *
 * @property UsersTable&BelongsTo $Users
 * @property ContainersTable&BelongsToMany $Containers
 *
 * @method MapUpload get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method MapUpload newEntity($data = null, array $options = [])
 * @method MapUpload[] newEntities(array $data, array $options = [])
 * @method MapUpload|false save(EntityInterface $entity, $options = [])
 * @method MapUpload saveOrFail(EntityInterface $entity, $options = [])
 * @method MapUpload patchEntity(EntityInterface $entity, array $data, array $options = [])
 * @method MapUpload[] patchEntities($entities, array $data, array $options = [])
 * @method MapUpload findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin TimestampBehavior
 */
class MapUploadsTable extends Table {
    use PaginationAndScrollIndexTrait;


    public $supportedFileExtensions = ['jpg', 'gif', 'png', 'jpeg'];

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('map_uploads');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsToMany('Containers', [
            'className'        => 'Containers',
            'foreignKey'       => 'mapupload_id',
            'targetForeignKey' => 'container_id',
            'joinTable'        => 'mapuploads_to_containers'
        ]);
        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'className'  => 'Users',
        ]);
    }

    public function bindCoreAssociations(Table $coreTable) {
        switch ($coreTable->getAlias()) {
            case 'Containers':
                if (!$coreTable->hasAssociation('MapUploads')) {
                    $coreTable->belongsToMany('MapUploads', [
                        'className'        => 'MapModule.MapUploads',
                        'foreignKey'       => 'container_id',
                        'targetForeignKey' => 'mapupload_id',
                        'joinTable'        => 'mapuploads_to_containers',
                        'joinType'         => 'INNER',
                    ]);
                }
                break;
            case 'Users':
                if (!$coreTable->hasAssociation('MapUploads')) {
                    $coreTable->hasMany('MapUploads', [
                        'className'  => 'MapModule.MapUploads',
                        'foreignKey' => 'user_id',
                        'joinType'   => 'INNER'
                    ]);
                }
                break;
        }
    }

    /**
     * Default validation rules.
     *
     * @param Validator $validator Validator instance.
     * @return Validator
     */
    public function validationDefault(Validator $validator): Validator {
        $validator
            ->integer('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->integer('upload_type')
            ->allowEmptyString('upload_type');

        $validator
            ->scalar('upload_name')
            ->maxLength('upload_name', 255)
            ->requirePresence('upload_name', 'create')
            ->notEmptyString('upload_name');

        $validator
            ->scalar('saved_name')
            ->maxLength('saved_name', 255)
            ->requirePresence('saved_name', 'create')
            ->notEmptyString('saved_name');

        $validator
            ->requirePresence('containers', true, __('You have to choose at least one container.'))
            ->allowEmptyString('containers', null, false)
            ->multipleOptions('containers', [
                'min' => 1
            ], __('You have to choose at least one container.'));

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param RulesChecker $rules The rules object to be modified.
     * @return RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker {
        $rules->add($rules->existsIn(['user_id'], 'Users'));
        $rules->add($rules->existsIn(['container_id'], 'Containers'));

        return $rules;
    }

    /**
     * @param $filename
     * @param int $type
     * @param $MY_RIGHTS
     * @return mixed
     */
    public function getByFilename($filename, int $type, $MY_RIGHTS = []) {
        if (!is_array($MY_RIGHTS)) {
            $MY_RIGHTS = [$MY_RIGHTS];
        }
        return $this->unhydratedFind()
            ->where([
                'MapUploads.saved_name' => $filename,
            ])
            ->contain([
                'Containers'
            ])
            ->innerJoinWith('Containers', function (SelectQuery $query) use ($MY_RIGHTS) {
                if (!empty($MY_RIGHTS)) {
                    return $query->where(['Containers.id IN' => $MY_RIGHTS]);
                }
                return $query;
            })
            ->where([
                'MapUploads.upload_type' => $type
            ])
            ->groupBy([
                'MapUploads.id'
            ])
            ->first();
    }

    /**
     * @return array
     */
    public function getIconsNames() {
        return [
            'ack.png',
            'critical.png',
            'down.png',
            'downtime_ack.png',
            'downtime.png',
            'error.png',
            'ok.png',
            'sack.png',
            'sdowntime_ack.png',
            'sdowntime.png',
            'unknown.png',
            'unreachable.png',
            'up.png',
            'warning.png'
        ];
    }

    /**
     * @param bool $hasRootPrivileges
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getIcons(bool $hasRootPrivileges, array $MY_RIGHTS = []): array {
        $basePath = APP . '../' . 'plugins' . DS . 'MapModule' . DS . 'webroot' . DS . 'img' . DS . 'icons';
        if (!is_dir($basePath)) {
            return [];
        }

        $finder = new Finder();
        $finder->files()->in($basePath);
        //Get all icons that a not root user is allowed to see.
        $permittedIcons = $this->getMapUploadsIcons(
            $MY_RIGHTS
        );

        $icons = [];
        foreach ($permittedIcons as $iconSavedName) {
            $iconPath = $basePath . DS . $iconSavedName;
            if (file_exists($iconPath)) {
                $icons[] = $iconSavedName;
            }
        }

        return $icons;
    }

    /**
     * @param $error
     * @return array
     */
    public function getUploadResponse($error) {
        switch ($error) {
            case UPLOAD_ERR_OK:
                $response = [
                    'success' => true,
                    'message' => __('File uploaded successfully')
                ];
                break;

            case UPLOAD_ERR_INI_SIZE:
                $response = [
                    'success' => false,
                    'message' => __('The uploaded file exceeds the upload_max_filesize directive in php.ini')
                ];
                break;

            case UPLOAD_ERR_FORM_SIZE:
                $response = [
                    'success' => false,
                    'message' => __('The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form')
                ];
                break;

            case UPLOAD_ERR_PARTIAL:
                $response = [
                    'success' => false,
                    'message' => __('The uploaded file was only partially uploaded')
                ];
                break;

            case UPLOAD_ERR_NO_FILE:
                $response = [
                    'success' => false,
                    'message' => __('No file was uploaded')
                ];
                break;

            case UPLOAD_ERR_NO_TMP_DIR:
                $response = [
                    'success' => false,
                    'message' => __('Missing a temporary folder.')
                ];
                break;

            case UPLOAD_ERR_CANT_WRITE:
                $response = [
                    'success' => false,
                    'message' => __('Failed to write file to disk.')
                ];
                break;

            case UPLOAD_ERR_EXTENSION:
                $response = [
                    'success' => false,
                    'message' => __('A PHP extension stopped the file upload.')
                ];
                break;

            default:
                $response = [
                    'success' => false,
                    'message' => __('Unknown upload error.')
                ];
                break;
        }
        return $response;
    }

    /**
     * @param $fileExtension
     * @return bool
     */
    public function isFileExtensionSupported($fileExtension) {
        return in_array(strtolower(trim($fileExtension)), $this->supportedFileExtensions, true);
    }

    /**
     * @param $imageConfig
     * @param Folder $Folder
     * @throws Exception
     */
    public function createThumbnailsFromBackgrounds($imageConfig, Folder $Folder) {

        $file = $imageConfig['fullPath'];

        //check if thumb folder exist
        if (!is_dir($Folder->path . DS . 'thumb')) {
            mkdir($Folder->path . DS . 'thumb');
        }

        $imgsize = getimagesize($file);
        $width = $imgsize[0];
        $height = $imgsize[1];
        $imgtype = $imgsize[2];
        $aspectRatio = $width / $height;

        $thumbnailWidth = 150;
        $thumbnailHeight = 150;


        switch ($imgtype) {
            /**
             * 1 => GIF
             * 2 => JPG
             * 3 => PNG
             * 4 => SWF
             * 5 => PSD
             * 6 => BMP
             * 7 => TIFF(intel byte order)
             * 8 => TIFF(motorola byte order)
             * 9 => JPC
             * 10 => JP2
             * 11 => JPX
             * 12 => JB2
             * 13 => SWC
             * 14 => IFF
             * 15 => WBMP
             * 16 => XBM
             */
            case 1:
                $srcImg = imagecreatefromgif($file);
                break;
            case 2:
                $srcImg = imagecreatefromjpeg($file);
                break;
            case 3:
                $srcImg = imagecreatefrompng($file);
                break;
            default:
                throw new \Exception('Filetype not supported!');
                break;
        }

        //calculate the new height or width and keep the aspect ration
        if ($aspectRatio == 1) {
            //source image X = Y
            $newWidth = $thumbnailWidth;
            $newHeight = $thumbnailHeight;
        } else if ($aspectRatio > 1) {
            //source image X > Y
            $newWidth = $thumbnailWidth;
            $newHeight = ($thumbnailHeight / $aspectRatio);
        } else {
            //source image X < Y
            $newWidth = ($thumbnailWidth * $aspectRatio);
            $newHeight = $thumbnailHeight;
        }

        $destImg = imagecreatetruecolor(intval($newWidth), intval($newHeight));
        $transparent = imagecolorallocatealpha($destImg, 0, 0, 0, 127);
        imagefill($destImg, 0, 0, $transparent);
        imageCopyResized($destImg, $srcImg, 0, 0, 0, 0, intval($newWidth), intval($newHeight), $width, $height);
        imagealphablending($destImg, false);
        imagesavealpha($destImg, true);


        //Save image to disk
        switch ($imgtype) {
            /**
             * 1 => GIF
             * 2 => JPG
             * 3 => PNG
             * 4 => SWF
             * 5 => PSD
             * 6 => BMP
             * 7 => TIFF(intel byte order)
             * 8 => TIFF(motorola byte order)
             * 9 => JPC
             * 10 => JP2
             * 11 => JPX
             * 12 => JB2
             * 13 => SWC
             * 14 => IFF
             * 15 => WBMP
             * 16 => XBM
             */
            case 1:
                imagegif($destImg, $Folder->path . DS . 'thumb' . DS . 'thumb_' . $imageConfig['uuidFilename'] . '.' . $imageConfig['fileExtension']);
                break;
            case 2:
                imagejpeg($destImg, $Folder->path . DS . 'thumb' . DS . 'thumb_' . $imageConfig['uuidFilename'] . '.' . $imageConfig['fileExtension']);
                break;
            case 3:
                imagepng($destImg, $Folder->path . DS . 'thumb' . DS . 'thumb_' . $imageConfig['uuidFilename'] . '.' . $imageConfig['fileExtension']);
                break;
            default:
                throw new \Exception('Filetype not supported!');
                break;
        }
    }

    /**
     * @param bool $hasRootPrivileges
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getIconSets(bool $hasRootPrivileges, array $MY_RIGHTS = []) {
        $basePath = APP . '../' . 'plugins' . DS . 'MapModule' . DS . 'webroot' . DS . 'img' . DS . 'items';
        $finder = new Finder();
        $finder->directories()->in($basePath);
        $availableIconsets = [];

        $allIconsets = $this->getMapUploadsIconSets(
            $MY_RIGHTS
        );
        foreach ($allIconsets as $iconSet) {
            if (file_exists($basePath . DS . $iconSet['saved_name'] . DS . 'ok.png')) {
                $containerIds = Hash::extract($iconSet['containers'], '{n}.id') ?? [ROOT_CONTAINER];
                $iconSet = Hash::remove($iconSet, '{n}.containers');
                $iconSet['containers'] = $containerIds;
                $availableIconsets[$iconSet['saved_name']] = ['MapUpload' => $iconSet];

            }
        }

        /** @var SplFileInfo $folder */
        foreach ($finder as $folder) {
            $dirName = $folder->getFilename();

            //Does icon set exists in database?
            if (!isset($availableIconsets[$dirName])) {
                if (file_exists($basePath . DS . $dirName . DS . 'ok.png') &&
                    $this->existsBySavedNameAndType($dirName, MapUpload::TYPE_ICON_SET) === false) {
                    //Icon set is missing in database, add it
                    $data = [
                        'upload_type' => MapUpload::TYPE_ICON_SET,
                        'upload_name' => $dirName,
                        'saved_name'  => $dirName,
                        'user_id'     => null,
                        'containers'  => [
                            '_ids' => [ROOT_CONTAINER]
                        ]
                    ];
                    $mapUploadEntity = $this->newEntity($data);
                    $this->save($mapUploadEntity);
                    if (!$mapUploadEntity->hasErrors()) {
                        $data['id'] = $mapUploadEntity->id;
                        $availableIconsets[$dirName] = $data;
                    }

                }
            }
        }
        return array_values($availableIconsets);
    }

    /**
     * @return array
     */
    public function getMapUploads() {
        return $this->unhydratedFind('all')
            ->disableAutoFields()
            ->toArray();
    }

    /**
     * @param GenericFilter $GenericFilter
     * @param array $types
     * @param PaginateOMat|null $PaginateOMat
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getMapUploadsByTypeIndex(GenericFilter $GenericFilter, array $types = [], ?PaginateOMat $PaginateOMat = null, array $MY_RIGHTS = []): array {
        if (!is_array($MY_RIGHTS)) {
            $MY_RIGHTS = [$MY_RIGHTS];
        }
        if (!empty($types) && !is_array($types)) {
            $types = [$types];
        }

        $query = $this->unhydratedFind('all')
            ->select([
                'MapUploads.id',
                'MapUploads.upload_name',
                'MapUploads.saved_name'
            ])
            ->where($GenericFilter->genericFilters())
            ->contain(['Containers'])
            ->innerJoinWith('Containers', function (SelectQuery $query) use ($MY_RIGHTS) {
                if (!empty($MY_RIGHTS)) {
                    return $query->where(['Containers.id IN' => $MY_RIGHTS]);
                }
                return $query;
            });
        if (!empty($types)) {
            $query->whereInList('MapUploads.upload_type', $types);
        }
        $query->groupBy(['MapUploads.id'])
            ->orderBy($GenericFilter->getOrderForPaginator('MapUploads.name', 'asc'));

        if ($PaginateOMat === null) {
            //Just execute query
            $result = $query->toArray();
        } else {
            if ($PaginateOMat->useScroll()) {
                $result = $this->scrollCake4($query, $PaginateOMat->getHandler());
            } else {
                $result = $this->paginateCake4($query, $PaginateOMat->getHandler());
            }
        }

        return $result;
    }

    /**
     * @param int|string $id
     * @return bool
     */
    public function existsById(int|string $id): bool {
        return $this->exists(['MapUploads.id' => $id]);
    }

    /**
     * @param int|string $id
     * @return array
     */
    public function getMapUploadById(int|string $id): array {
        $query = $this->unhydratedFind()
            ->contain([
                'Containers'
            ])
            ->where([
                'MapUploads.id' => $id
            ])
            ->first();
        return $this->emptyArrayIfNull($query);
    }


    public function getUsedMapIconsWithMapContainerIds(array $MY_RIGHTS = []) {
        $query = $this->unhydratedFind()
            ->select([
                'MapUploads.id',
                'MapUploads.upload_name',
                'MapUploads.saved_name',
                'Mapicons.id',
                'Mapicons.map_id',
                'Maps.name'
            ])
            ->contain([
                'Containers'
            ])
            ->innerJoinWith('Containers', function (SelectQuery $query) use ($MY_RIGHTS) {
                if (!empty($MY_RIGHTS)) {
                    return $query->where(['Containers.id IN' => $MY_RIGHTS]);
                }
                return $query;
            })
            ->innerJoin(
                ['Mapicons' => 'mapicons'],
                [
                    'Mapicons.icon = MapUploads.saved_name'
                ]
            )
            ->innerJoin(
                ['Maps' => 'maps'],
                [
                    'Maps.id = Mapicons.map_id'
                ]
            )
            ->where([
                'MapUploads.upload_type' => MapUpload::TYPE_ICON
            ]);
        if (!empty($MY_RIGHTS)) {
            $query->innerJoin(
                ['MapsToContainers' => 'maps_to_containers'],
                [
                    'MapsToContainers.map_id = Mapicons.map_id',
                    'MapsToContainers.container_id IN' => $MY_RIGHTS
                ]
            );
        }
        $query->groupBy(['Mapicons.id']);

        return $this->emptyArrayIfNull($query->toArray());
    }

    public function getUsedMapItemsWithMapContainerIds(array $MY_RIGHTS = []) {
        $query = $this->unhydratedFind()
            ->select([
                'MapUploads.id',
                'MapUploads.upload_name',
                'MapUploads.saved_name',
                'Mapitems.id',
                'Mapitems.map_id',
                'Maps.name'
            ])
            ->contain([
                'Containers'
            ])
            ->innerJoinWith('Containers', function (SelectQuery $query) use ($MY_RIGHTS) {
                if (!empty($MY_RIGHTS)) {
                    return $query->where(['Containers.id IN' => $MY_RIGHTS]);
                }
                return $query;
            })
            ->innerJoin(
                ['Mapitems' => 'mapitems'],
                [
                    'Mapitems.iconset = MapUploads.saved_name'
                ]
            )
            ->innerJoin(
                ['Maps' => 'maps'],
                [
                    'Maps.id = Mapitems.map_id'
                ]
            )
            ->where([
                'MapUploads.upload_type' => MapUpload::TYPE_ICON_SET
            ]);
        if (!empty($MY_RIGHTS)) {
            $query->innerJoin(
                ['MapsToContainers' => 'maps_to_containers'],
                [
                    'MapsToContainers.map_id = Mapitems.map_id',
                    'MapsToContainers.container_id IN' => $MY_RIGHTS
                ]
            );
        }
        $query->groupBy(['Mapitems.id']);

        return $this->emptyArrayIfNull($query->toArray());
    }

    /**
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getMapUploadsBackgrounds(array $MY_RIGHTS = []): array {
        if (!is_array($MY_RIGHTS)) {
            $MY_RIGHTS = [$MY_RIGHTS];
        }
        $query = $this->find('list', valueField: 'saved_name')
            ->contain(['Containers'])
            ->innerJoinWith('Containers', function (SelectQuery $query) use ($MY_RIGHTS) {
                if (!empty($MY_RIGHTS)) {
                    return $query->where(['Containers.id IN' => $MY_RIGHTS]);
                }
                return $query;
            })
            ->where([
                'MapUploads.upload_type' => MapUpload::TYPE_BACKGROUND
            ])
            ->groupBy(['MapUploads.saved_name']);


        return $query->toArray();
    }

    /**
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getMapUploadsIcons(array $MY_RIGHTS = []): array {
        if (!is_array($MY_RIGHTS)) {
            $MY_RIGHTS = [$MY_RIGHTS];
        }

        return $this->unhydratedFind('list', valueField: 'saved_name')
            ->select([
                'MapUploads.id',
                'MapUploads.upload_name',
                'MapUploads.saved_name'
            ])
            ->contain(['Containers'])
            ->innerJoinWith('Containers', function (SelectQuery $query) use ($MY_RIGHTS) {
                if (!empty($MY_RIGHTS)) {
                    return $query->where(['Containers.id IN' => $MY_RIGHTS]);
                }
                return $query;
            })->where(['MapUploads.upload_type' => MapUpload::TYPE_ICON])
            ->groupBy(['MapUploads.id'])
            ->toArray();
    }

    /**
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getMapUploadsIconSets(array $MY_RIGHTS = []): array {
        if (!is_array($MY_RIGHTS)) {
            $MY_RIGHTS = [$MY_RIGHTS];
        }

        return $this->unhydratedFind()
            ->select([
                'MapUploads.id',
                'MapUploads.upload_name',
                'MapUploads.saved_name'
            ])
            ->contain(['Containers'])
            ->innerJoinWith('Containers', function (SelectQuery $query) use ($MY_RIGHTS) {
                if (!empty($MY_RIGHTS)) {
                    return $query->where(['Containers.id IN' => $MY_RIGHTS]);
                }
                return $query;
            })->where(['MapUploads.upload_type' => MapUpload::TYPE_ICON_SET])
            ->groupBy(['MapUploads.id'])
            ->toArray();
    }

    /**
     * @param string $savedName
     * @param int $type
     * @return bool
     */
    public function existsBySavedNameAndType(string $savedName, int $type): bool {
        return $this->exists([
            'MapUploads.saved_name'  => $savedName,
            'MapUploads.upload_type' => $type
        ]);
    }
}
