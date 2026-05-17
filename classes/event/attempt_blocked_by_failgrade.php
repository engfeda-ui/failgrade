<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace quizaccess_failgrade\event;

use core\event\base;

/**
 * Event fired when a student is blocked from attempting a quiz because they already passed.
 *
 * @package    quizaccess_failgrade
 * @copyright  2026 Mahmoud Salem
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class attempt_blocked_by_failgrade extends base {
    /**
     * Init method.
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'quiz';
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventattemptblocked', 'quizaccess_failgrade');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '{$this->userid}' was blocked from attempting the quiz " .
               "with course module id '{$this->contextinstanceid}' because they have " .
               "already satisfied the passing criteria (mode: {$this->other['mode']}).";
    }

    /**
     * Return the legacy event log data.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/mod/quiz/view.php', ['id' => $this->contextinstanceid]);
    }
}
