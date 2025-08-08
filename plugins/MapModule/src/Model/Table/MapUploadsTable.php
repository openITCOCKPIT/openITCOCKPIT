<?php
// Copyright (C) <2015>  <it-novum GmbH>
//
// This file is dual licensed
//
// 1.
//	This program is free software: you can redistribute it and/or modify
//	it under the terms of the GNU General Public License as published by
//	the Free Software Foundation, version 3 of the License.
//
//	This program is distributed in the hope that it will be useful,
//	but WITHOUT ANY WARRANTY; without even the implied warranty of
//	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//	GNU General Public License for more details.
//
//	You should have received a copy of the GNU General Public License
//	along with this program.  If not, see <http://www.gnu.org/licenses/>.
//

// 2.
//	If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//	under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//	License agreement and license key will be shipped with the order
//	confirmation.

declare(strict_types=1);

namespace MapModule\Model\Table;

use App\Model\Table\ContainersTable;
use App\Model\Table\UsersTable;
use Cake\Core\Exception\Exception;
use Cake\Datasource\EntityInterface;
use itnovum\openITCOCKPIT\CakePHP\Folder;
use Cake\ORM\Association\BelongsTo;
use Cake\ORM\Behavior\TimestampBehavior;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use MapModule\Model\Entity\MapUpload;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * MapUploads Model
 *
 * @property UsersTable&BelongsTo $Users
 * @property ContainersTable&BelongsTo $Containers
 *
 * @method MapUpload get($primaryKey, $options = [])
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

    public $supportedFileExtensions = ['jpg', 'gif', 'png', 'jpeg'];
    public $TYPE_BACKGROUND = 1;
    public $TYPE_ICON_SET = 2;

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

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'className'  => 'Users',
        ]);
        $this->belongsTo('Containers', [
            'foreignKey' => 'container_id',
            'className'  => 'Containers',
        ]);
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
     * @param array $MY_RIGHTS
     * @return array|EntityInterface|null
     */
    public function getByFilename($filename, $MY_RIGHTS = []) {
        if (!is_array($MY_RIGHTS)) {
            $MY_RIGHTS = [$MY_RIGHTS];
        }

        $query = $this->find()
            ->where([
                'MapUploads.saved_name' => $filename,
            ])
            ->contain([
                'Containers'
            ])
            ->innerJoinWith('Containers', function (Query $query) use ($MY_RIGHTS) {
                if (!empty($MY_RIGHTS)) {
                    return $query->where(['Containers.id IN' => $MY_RIGHTS]);
                }
                return $query;
            })
            ->disableHydration()
            ->first();
        return $query;
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
     * @return array
     */
    public function getIcons() {
        $basePath = APP . '../' . 'plugins' . DS . 'MapModule' . DS . 'webroot' . DS . 'img' . DS . 'icons';
        if (!is_dir($basePath)) {
            return [];
        }

        $finder = new Finder();
        $finder->files()->in($basePath);
        $icons = [];

        /** @var SplFileInfo $file */
        foreach ($finder as $file) {
            if (in_array($file->getExtension(), $this->supportedFileExtensions, true)) {
                $icons[] = $file->getFilename();
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
        imagedestroy($destImg);
    }

    /**
     * @return array
     */
    public function getIconSets() {
        $basePath = APP . '../' . 'plugins' . DS . 'MapModule' . DS . 'webroot' . DS . 'img' . DS . 'items';
        $finder = new Finder();
        $finder->directories()->in($basePath);

        $allIconsets = $this->find()->where([
            'MapUploads.upload_type' => $this->TYPE_ICON_SET
        ])->all()->toArray();

        $availableIconsets = [];
        foreach ($allIconsets as $iconset) {
            if (file_exists($basePath . DS . $iconset['saved_name'] . DS . 'ok.png')) {
                $availableIconsets[$iconset['saved_name']] = ['MapUpload' => $iconset];
            }
        }

        /** @var SplFileInfo $folder */
        foreach ($finder as $folder) {
            $dirName = $folder->getFilename();

            //Does icon set exists in database?
            if (!isset($availableIconsets[$dirName])) {
                if (file_exists($basePath . DS . $dirName . DS . 'ok.png')) {
                    //Icon set is missing in database, add it
                    $data = [
                        'upload_type'  => $this->TYPE_ICON_SET,
                        'upload_name'  => $dirName,
                        'saved_name'   => $dirName,
                        'user_id'      => null,
                        'container_id' => 1
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
}
