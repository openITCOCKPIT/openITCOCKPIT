<?php
// Copyright (C) <2015-present>  <it-novum GmbH>
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


use itnovum\openITCOCKPIT\Core\ValueObjects\User;

class UserTime {

    /**
     * @var string
     */
    private $timezone;

    /**
     * @var string
     */
    private $format;

    /**
     * UserTime constructor.
     * @param string $timezone
     * @param string $format
     */
    public function __construct($timezone, $format) {
        $this->timezone = $timezone;
        $this->format = $format;
    }

    /**
     * @param User $User
     * @return UserTime
     */
    public static function fromUser(User $User) {
        return new self(
            $User->getTimezone(),
            $User->getDateformat()
        );
    }

    /**
     * @param int|string $t_time
     * @return string
     */
    public function format($t_time) {
        if (gettype($t_time) === 'object') {
            if ($t_time instanceof \Cake\I18n\DateTime) {
                /** @var \Cake\I18n\DateTime $t_time */
                $t_time = $t_time->timestamp;
            } else if ($t_time instanceof \DateTime) {
                /** @var \DateTime $t_time */
                $t_time = $t_time->getTimestamp();
            }
        }

        if (!is_numeric($t_time) && !is_null($t_time)) {
            $t_time = strtotime($t_time);
        }
        if (!is_numeric($t_time)) {
            $t_time = 0;
        }
        $time = \Cake\I18n\DateTime::createFromTimestamp($t_time, new \DateTimeZone($this->timezone));
        return $time->format($this->format);
    }

    /**
     * @param $format
     * @param $t_time
     * @return string
     */
    public function customFormat($format, $t_time) {

        if (gettype($t_time) === 'object') {
            if ($t_time instanceof \Cake\I18n\DateTime) {
                /** @var \Cake\I18n\DateTime $t_time */
                $t_time = $t_time->timestamp;
            } else if ($t_time instanceof \DateTime) {
                /** @var \DateTime $t_time */
                $t_time = $t_time->getTimestamp();
            }
        }

        if (!is_numeric($t_time) && !is_null($t_time)) {
            $t_time = strtotime($t_time);
        }
        if (!is_numeric($t_time)) {
            $t_time = 0;
        }
        $time = \Cake\I18n\DateTime::createFromTimestamp($t_time, new \DateTimeZone($this->timezone));
        return $time->format($format);
    }

    /**
     *
     * Formats a given value in seconds to a human short readable string with time units
     * Example 58536006 will return:
     * 1Y 10M 8D 12h 0m 6s
     *
     * @param int $duration
     * @return string
     * @throws \Exception
     */
    public function secondsInHumanShort($duration) {

        if ($duration == '') {
            $duration = 0;
        }

        $zero = new \DateTime("@0");
        $seconds = new \DateTime("@$duration");
        $closure = function ($duration) {
            //Check how much "time" we need
            if ($duration >= 31536000) {
                // 1 year or more
                return '%y' . __('Y') . ' %m' . __('M') . ' %d' . __('D') . ' %h' . __('h') . ' %i' . __('m') . ' %s' . __('s');
            } else if ($duration >= 2678400) {
                // 1 month or more
                return '%m' . __('M') . ' %d' . __('D') . ' %h' . __('h') . ' %i' . __('m') . ' %s' . __('s');
            } else if ($duration >= 86400) {
                // 1 day or more
                return '%a' . __('D') . ' %h' . __('h') . ' %i' . __('m') . ' %s' . __('s');
            } else if ($duration >= 3600) {
                // 1 hour or more
                return '%h' . __('h') . ' %i' . __('m') . ' %s' . __('s');
            } else if ($duration >= 60) {
                // 1 minute or more
                return '%i' . __('m') . ' %s' . __('s');
            } else if ($duration >= 0) {
                // 0 second or more
                return '%s' . __('s');
            }
        };

        $format = $closure($duration);

        return $zero->diff($seconds)->format($format);
    }

    /**
     *
     * ### Options:
     *
     * - `from` => another Time object representing the "now" time
     * - `format` => a fall back format if the relative time is longer than the duration specified by end
     * - `accuracy` => Specifies how accurate the date should be described (array)
     *
     *    - year =>   The format if years > 0   (default "day")
     *    - month =>  The format if months > 0  (default "day")
     *    - week =>   The format if weeks > 0   (default "day")
     *    - day =>    The format if weeks > 0   (default "hour")
     *    - hour =>   The format if hours > 0   (default "minute")
     *    - minute => The format if minutes > 0 (default "minute")
     *    - second => The format if seconds > 0 (default "second")
     *
     * - `end` => The end of relative time telling
     * - `relativeString` => The `printf` compatible string when outputting relative time
     * - `absoluteString` => The `printf` compatible string when outputting absolute time
     * - `timezone` => The user timezone the timestamp should be formatted in.
     *
     * Relative dates look something like this:
     *
     * - 3 weeks, 4 days ago
     * - 15 seconds ago
     *
     * Default date formatting is d/M/YY e.g: on 18/2/09. Formatting is done internally using
     * `i18nFormat`, see the method for the valid formatting strings
     *
     * The returned string includes 'ago' or 'on' and assumes you'll properly add a word
     * like 'Posted ' before the function output.
     *
     * NOTE: If the difference is one week or more, the lowest level of accuracy is day
     *
     * @param int $t_time
     * @return string
     */
    public function timeAgoInWords($t_time, $options = []) {
        if (gettype($t_time) === 'object') {
            if ($t_time instanceof \Cake\I18n\DateTime) {
                /** @var \Cake\I18n\DateTime $t_time */
                $t_time = $t_time->timestamp;
            } else if ($t_time instanceof \DateTime) {
                /** @var \DateTime $t_time */
                $t_time = $t_time->getTimestamp();
            }
        }

        if (!is_numeric($t_time) && !is_null($t_time)) {
            $t_time = strtotime($t_time);
        }
        if (!is_numeric($t_time)) {
            $t_time = 0;
        }
        $time = \Cake\I18n\DateTime::createFromTimestamp($t_time, new \DateTimeZone($this->timezone));
        return $time->timeAgoInWords($options);
    }

    /**
     * @return bool|int
     * @throws \Exception
     */
    public function getUserTimeToServerOffset() {
        $ServerTime = new \DateTime();
        $ServerTimeZone = new \DateTimeZone($ServerTime->getTimezone()->getName());

        return $this->get_timezone_offset($ServerTimeZone->getName(), $this->timezone);
    }

    /**
     * @return int
     * @throws \Exception
     */
    public function getUserTimeOffset() {
        $UserTime = new \DateTime($this->timezone);
        return $UserTime->getOffset();
    }

    /**
     * @return int
     * @throws \Exception
     */
    public function getServerTimeOffset() {
        $ServerTime = new \DateTime();
        return $ServerTime->getOffset();
    }

    /**
     * @return string
     */
    public function getUserTimezone(): string {
        return $this->timezone;
    }


    /**
     * @param $remote_tz
     * @param null $origin_tz
     * @return bool|int
     * @throws \Exception
     */
    private function get_timezone_offset($remote_tz, $origin_tz = null) {
        if ($origin_tz === null) {
            if (!is_string($origin_tz = date_default_timezone_get())) {
                return false; // A UTC timestamp was returned -- bail out!
            }
        }
        $origin_dtz = new \DateTimeZone($origin_tz);
        $remote_dtz = new \DateTimeZone($remote_tz);
        $origin_dt = new \DateTime("now", $origin_dtz);
        $remote_dt = new \DateTime("now", $remote_dtz);
        $offset = $origin_dtz->getOffset($origin_dt) - $remote_dtz->getOffset($remote_dt);
        return $offset;
    }

    public function getFormatString() {
        return $this->format;
    }

}
