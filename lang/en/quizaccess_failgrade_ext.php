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
 * Strings for the quizaccess_failgrade_ext plugin.
 *
 * @package quizaccess
 * @subpackage failgrade
 * @copyright  2026 Mahmoud Salem
 * @copyright  based on work by 2020 Alexandre Paes RigÃ£o <rigao.com.br>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Fail grade';
$string['privacy:metadata'] = 'The Fail grade plugin does not store any personal data.';

$string['failgradeenabled'] = 'Block extra attempts if passing grade';
$string['failgradeenabled_help'] = 'Prevent user from attempting the quiz again once they have received a passing grade.';

$string['failgradedescription'] = 'Attempts available until reaching passing grade.';
$string['preventmoreattempts'] = 'You have already passed this quiz, and may not make further attempts.';

// Settings options.
$string['failgrademode_disabled'] = 'No (Disabled)';
$string['failgrademode_grade'] = 'Yes (Rely on passing grade)';
$string['failgrademode_competency'] = 'Yes (Rely on competencies)';
$string['failgrademode_combined'] = 'Yes (Rely on BOTH passing grade and competencies)';


// Student-facing messages.
$string['listseparator'] = ', ';
$string['missingcompetencies'] = 'Sorry, you have not yet mastered all the required technical competencies to complete this quiz successfully. The competencies that still need improvement are: <br><strong>{$a}</strong>';
$string['allcompetenciesmet'] = 'Congratulations! You have successfully mastered all the required competencies for this quiz.';
$string['eventattemptblocked'] = 'Quiz attempt blocked by failgrade rule';
$string['competencythreshold'] = 'Competency success threshold (%)';
$string['competencythreshold_help'] = 'Specify the minimum percentage rate required for each competency in this quiz to achieve mastery (0 to 100). If set to 0, the global success threshold defined in the competency report settings will be used instead.';
$string['competencyprogress'] = '{$a->name} ({$a->rate}% achieved, {$a->threshold}% required)';
$string['competencytable_heading'] = 'Quiz Competency Progress';
$string['competencytable_comp'] = 'Competency';
$string['competencytable_required'] = 'Required Threshold';
$string['competencytable_achieved'] = 'Your Score';
$string['competencytable_status'] = 'Status';
$string['competencytable_passed'] = 'Passed';
$string['competencytable_failed'] = 'Needs Improvement';
$string['noattempts'] = 'No attempts yet';
