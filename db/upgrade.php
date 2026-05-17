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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * quizaccess_failgrade plugin upgrade steps.
 *
 * @package    quizaccess_failgrade
 * @copyright  2026 quizaccess_failgrade contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade the plugin from an old version.
 *
 * @param int $oldversion the version we are upgrading from.
 * @return bool always true.
 */
function xmldb_quizaccess_failgrade_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026051500) {
        // The failgradeenabled column previously stored only 0 or 1 (boolean).
        // As of v2.0.0 it stores 0, 1, or 2 to support three modes: 0 (disabled), 1 (grade-based), and 2 (competency-based).
        // The existing INT(2) column already fits values 0-2 without a schema change, so no DDL alteration is needed.
        // We just bump the savepoint.
        upgrade_plugin_savepoint(true, 2026051500, 'quizaccess', 'failgrade');
    }

    return true;
}
