<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * FilterBokkmark Entity
 *
 * @property int $id
 * @property string $uuid
 * @property string $plugin
 * @property string $controller
 * @property string $action
 * @property string $name
 * @property string $filter
 * @property bool $favorite
 **/
class FilterBookmark extends Entity {
    protected array $_accessible = [
        'uuid'       => true,
        'plugin'     => true,
        'controller' => true,
        'action'     => true,
        'name'       => true,
        'user_id'    => true,
        'filter'     => true,
        'favorite'   => true,
    ];

    /**
     * @return array
     */
    public function jsonSerialize(): array {
        $data = $this->extract($this->getVisible());
        $data['fav_group'] = __('Filters');
        if ($this->favorite) {
            $data['fav_group'] = __('Favorites');
        }
        return $data;
    }

}
