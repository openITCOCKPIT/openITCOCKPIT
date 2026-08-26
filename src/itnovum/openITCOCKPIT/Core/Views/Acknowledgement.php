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

namespace itnovum\openITCOCKPIT\Core\Views;

abstract class Acknowledgement {


    /**
     * @var int
     */
    private $acknowledgement_type;

    /**
     * @var string
     */
    private $author_name;

    /**
     * @var string
     */
    private $comment_data;

    /**
     * @var int|string
     */
    private $entry_time;


    /**
     * @var bool
     */
    private $is_sticky;

    /**
     * @var bool
     */
    private $notify_contacts;

    /**
     * @var bool
     */
    private $persistent_comment;

    /**
     * @var int
     */
    private $state;

    /**
     * @var int|string
     */
    private $end_time;

    /**
     * @var UserTime|null
     */
    private $UserTime;

    /**
     * @var bool
     */
    private $allowEdit = false;

    /**
     * Acknowledgement constructor.
     * @param $data
     * @param null $UserTime
     * @param false $allowEdit
     */
    public function __construct($data, $UserTime = null, $allowEdit = false) {
        if (isset($data['acknowledgement_type'])) {
            $this->acknowledgement_type = (int)$data['acknowledgement_type'];
        }

        if (isset($data['author_name'])) {
            $this->author_name = $data['author_name'];
        }

        if (isset($data['comment_data'])) {
            $this->comment_data = $data['comment_data'];
        }

        if (isset($data['entry_time'])) {
            $this->entry_time = $data['entry_time'];
        }

        if (isset($data['is_sticky'])) {
            $this->is_sticky = (bool)$data['is_sticky'];
        }

        if (isset($data['notify_contacts'])) {
            $this->notify_contacts = (bool)$data['notify_contacts'];
        }

        if (isset($data['persistent_comment'])) {
            $this->persistent_comment = (bool)$data['persistent_comment'];
        }

        if (isset($data['state'])) {
            $this->state = (int)$data['state'];
        }

        $this->end_time = 0;
        if (isset($data['end_time'])) {
            $this->end_time = (int)$data['end_time'];
        }

        $this->UserTime = $UserTime;

        $this->allowEdit = $allowEdit;
    }

    /**
     * In the statusengine_host_acknowledgements and statusengine_service_acknowledgements tables the field acknowledgement_type
     * determines if the acknowledgement corresponds to a host or service
     * 0 = HOST_ACKNOWLEDGEMENT
     * 1 = SERVICE_ACKNOWLEDGEMENT
     *
     * This is a different behavior than the statusengine_hoststatus and statusengine_servicestatus tables have
     * This is already a mess in the Naemon Core itself :(
     *
     * @return int
     */
    public function getAcknowledgementType() {
        return $this->acknowledgement_type;
    }

    /**
     * @return string
     */
    public function getAuthorName() {
        return $this->author_name;
    }

    /**
     * @return string
     */
    public function getCommentData() {
        return $this->comment_data;
    }

    /**
     * @return int|string
     */
    public function getEntryTime() {
        if (!is_numeric($this->entry_time)) {
            if ($this->entry_time instanceof \Cake\I18n\DateTime) {
                $this->entry_time = $this->entry_time->timestamp;
            } else {
                $this->entry_time = strtotime($this->entry_time);
            }
        }

        return $this->entry_time;
    }

    /**
     * @return int|string
     */
    public function getEndTime() {
        if (!is_numeric($this->end_time)) {
            // This should never happen as end_time is a new bigint field in the database and did not exist back in the NDO days.
            if ($this->end_time instanceof \Cake\I18n\DateTime) {
                $this->end_time = $this->end_time->timestamp;
            } else {
                $this->end_time = strtotime($this->end_time);
            }
        }

        return $this->end_time;
    }

    /**
     * @return bool
     */
    public function hasEndTime(): bool {
        return $this->end_time > 0;
    }

    /**
     * This field is only available in the statusengine_host_acknowledgements and statusengine_service_acknowledgements tables
     *
     * @return boolean
     */
    public function isSticky() {
        return $this->is_sticky;
    }

    /**
     * @return boolean
     */
    public function hasNotifyContacts() {
        return $this->notify_contacts;
    }

    /**
     * @return boolean
     */
    public function isPersistentComment() {
        return $this->persistent_comment;
    }

    /**
     * @return int
     */
    public function getState() {
        return $this->state;
    }

    /**
     * @return bool
     */
    public function allowEdit() {
        return $this->allowEdit;
    }

    /**
     * @return array
     */
    public function toArray() {
        $arr = get_object_vars($this);
        if (isset($arr['UserTime'])) {
            unset($arr['UserTime']);
        }

        if ($this->UserTime !== null) {
            $arr['entry_time'] = $this->UserTime->format($this->getEntryTime());
        } else {
            $arr['entry_time'] = $this->getEntryTime();
        }

        if ($this->UserTime !== null) {
            $arr['end_time'] = $this->UserTime->format($this->getEndTime());
        } else {
            $arr['end_time'] = $this->getEndTime();
        }
        $arr['hasEndTime'] = $this->hasEndTime();

        return $arr;
    }
}
