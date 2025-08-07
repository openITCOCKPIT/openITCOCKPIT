<?php

namespace App\Model\Entity;

use App\Model\Table\ContainersTable;
use App\Model\Table\TimeperiodsTable;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;

/**
 * Calendar Entity
 *
 * @property int $id
 * @property string $name
 * @property string $description
 * @property int $container_id
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\CalendarHoliday[] $calendar_holidays
 */
class Calendar extends Entity {
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array
     */
    protected array $_accessible = [
        'name'              => true,
        'description'       => true,
        'container_id'      => true,
        'created'           => true,
        'modified'          => true,
        'calendar_holidays' => true
    ];

    /**
     * I will check where this calendar is in use while the containerId is fixed within a connection.
     * If you want to change the containerId, I may return a connection that will get broken as soon as the container is changed.
     * @return array[]
     */
    public function canMoveToContainer(int $newContainerId): bool {
        if (empty($this->getTimePeriods())) {
            return true;
        }

        /** @var ContainersTable $ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');
        return $ContainersTable->isNewContainerInPathOfOldContainer($newContainerId, $this->container_id);
    }


    /**
     * I will solely return the array of TimePeriods this Calendar is used with.
     * @return array
     */
    private function getTimePeriods(): array {
        /** @var TimeperiodsTable $TimePeriodsTable */
        $TimePeriodsTable = TableRegistry::getTableLocator()->get('Timeperiods');

        return $TimePeriodsTable->find()->where(['calendar_id' => $this->id])->toArray();
    }
}
