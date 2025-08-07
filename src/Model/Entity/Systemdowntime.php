<?php

namespace App\Model\Entity;

use Cake\ORM\Entity;
use itnovum\openITCOCKPIT\Core\Views\UserTime;

/**
 * Systemdowntime Entity
 *
 * @property int $id
 * @property int|null $objecttype_id
 * @property int|null $object_id
 * @property int|null $downtimetype_id
 * @property string|null $weekdays
 * @property string|null $day_of_month
 * @property string $from_time
 * @property string|null $to_time
 * @property int $duration
 * @property string|null $comment
 * @property string|null $author
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\Object $object
 */
class Systemdowntime extends Entity {
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
        'objecttype_id'   => true,
        'object_id'       => true,
        'downtimetype_id' => true,
        'weekdays'        => true,
        'day_of_month'    => true,
        'from_date'       => true,
        'from_time'       => true,
        'to_date'         => true,
        'to_time'         => true,
        'duration'        => true,
        'comment'         => true,
        'author'          => true,
        'created'         => true,
        'modified'        => true,
        'is_recursive'    => true
    ];

    /**
     * @return array
     */
    public function getWeekdays() {
        if ($this->weekdays === '' || $this->weekdays === null) {
            return [];
        }

        return explode(',', $this->weekdays);
    }

    /**
     * @return bool
     */
    public function hasWeekdays() {
        return !empty($this->getWeekdays());
    }

    /**
     * @return array
     */
    public function getDayOfMonth() {
        if ($this->day_of_month === '' || $this->day_of_month === null) {
            return [];
        }

        return explode(',', $this->day_of_month);
    }

    /**
     * @return bool
     */
    public function hasDayOfMonth() {
        return !empty($this->getDayOfMonth());
    }

    /**
     * @return false|int
     */
    public function getScheduledStartTime() {
        return strtotime($this->from_time);
    }

    /**
     * @return false|int
     */
    public function getScheduledEndTime() {
        return $this->getScheduledStartTime() + ($this->getDuration() * 60);
    }

    /**
     * @return int
     */
    public function getDuration() {
        return (int)$this->duration;
    }

    /**
     * @param int $timestamp
     * @return bool
     */
    public function isTimestampInThePast($timestamp = 0) {
        return $timestamp < time();
    }

    /**
     * @return int
     */
    public function getDowntimetypeId() {
        return (int)$this->downtimetype_id;
    }

    /**
     * @return string
     */
    public function getRecurringDowntimeComment() {
        return 'AUTO[' . $this->id . ']: ' . $this->comment;
    }

    /**
     * @param UserTime $UserTime
     * @return bool
     * @throws \Exception
     */
    public function shiftFromTime(UserTime $UserTime) {
        $offset = $UserTime->getUserTimeToServerOffset();

        $fromTime = strtotime($this->from_time);
        if ($fromTime === false) {
            return false;
        }

        $fromTime = $fromTime - $offset;

        $this->from_time = date('H:i', $fromTime);
        return true;
    }
}
