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
 * Implementation of the quizaccess_failgrade plugin.
 *
 * @package    quizaccess_failgrade
 * @copyright  2026 Mahmoud Salem
 * @copyright  based on work by 2020 Alexandre Paes RigÃ£o <rigao.com.br>
 * @copyright  2026 Mahmoud Salem
 * @copyright  based on work by 2026 quizaccess_failgrade contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/gradelib.php');

// This work-around is required until Moodle 4.2 is the lowest version we support.
if (class_exists('\mod_quiz\local\access_rule_base')) {
    // Use aliases at class_loader level to maintain compatibility.
    \class_alias('\mod_quiz\local\access_rule_base', 'quiz_access_rule_base');
    \class_alias('\mod_quiz\quiz_settings', 'quiz');
} else {
    require_once($CFG->dirroot . '/mod/quiz/accessrule/accessrulebase.php');
}

/**
 * A rule that blocks further attempts once the student has passed — either by
 * reaching the quiz passing grade (mode 1) or by achieving proficiency in all
 * competencies linked to the quiz course-module (mode 2).
 *
 * @package    quizaccess_failgrade
 * @copyright  2026 Mahmoud Salem
 * @copyright  based on work by 2020 Alexandre Paes RigÃ£o <rigao.com.br>
 * @copyright  2026 Mahmoud Salem
 * @copyright  based on work by 2026 quizaccess_failgrade contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quizaccess_failgrade extends quiz_access_rule_base {
    /** @var array Cache for is_finished calculations */
    protected $isfinishedcache = [];

    /** @var array Cache for description calculations */
    protected $descriptioncache = null;

    /**
     * Return an appropriately configured instance of this rule, if it is applicable
     * to the given quiz, otherwise return null.
     * @param quiz $quizobj information about the quiz in question.
     * @param int $timenow the time that should be considered as 'now'.
     * @param bool $canignoretimelimits whether the current user is exempt from
     * time limits by the mod/quiz:ignoretimelimits capability.
     * @return quiz_access_rule_base|null the rule, if applicable, else null.
     */
    public static function make(quiz $quizobj, $timenow, $canignoretimelimits) {
        if (empty($quizobj->get_quiz()->failgradeenabled)) {
            return null;
        }

        return new self($quizobj, $timenow);
    }

    /**
     * Whether or not a user should be allowed to start a new attempt at this quiz now.
     * @param int $numprevattempts the number of previous attempts this user has made.
     * @param object $lastattempt information about the user's last completed attempt.
     * @return string|false false if access should be allowed, a message explaining the
     * reason if access should be prevented.
     */
    public function prevent_new_attempt($numprevattempts, $lastattempt) {
        if ($this->is_finished($numprevattempts, $lastattempt)) {
            // Trigger an event to log this block.
            $event = \quizaccess_failgrade\event\attempt_blocked_by_failgrade::create([
                'context' => \context_module::instance($this->quizobj->get_cmid()),
                'objectid' => $this->quizobj->get_quizid(),
                'other' => [
                    'mode' => isset($this->quiz->failgradeenabled) ? $this->quiz->failgradeenabled : 1,
                ],
            ]);
            $event->trigger();

            return get_string('preventmoreattempts', 'quizaccess_failgrade');
        }

        return false;
    }

    /**
     * Information, such as might be shown on the quiz view page, relating to this restriction.
     * There is no obligation to return anything. If it is not appropriate to tell students
     * about this rule, then just return ''.
     * @return mixed a message, or array of messages, explaining the restriction
     * (may be '' if no message is appropriate).
     */
    public function description() {
        global $USER;

        if ($this->descriptioncache !== null) {
            return $this->descriptioncache;
        }

        $mode = isset($this->quiz->failgradeenabled) ? $this->quiz->failgradeenabled : 1;

        if ($mode == 2 && \core_competency\api::is_enabled()) {
            $cmid = $this->quizobj->get_cmid();
            $userid = $USER->id;

            $cmcompetencies = \core_competency\api::list_course_module_competencies($cmid);
            if (count($cmcompetencies) > 0) {
                $threshold = isset($this->quiz->competencythreshold) ? (int) $this->quiz->competencythreshold : 0;
                if ($threshold <= 0) {
                    $threshold = (int) get_config('local_competency_report', 'success_threshold');
                    if ($threshold <= 0) {
                        $threshold = 60; // Default fallback.
                    }
                }

                $missingcompetencies = [];
                foreach ($cmcompetencies as $cmcomp) {
                    // Safe property extraction to support both arrays and objects.
                    $competencyid = null;
                    if (is_array($cmcomp)) {
                        if (isset($cmcomp['competencyid'])) {
                            $competencyid = $cmcomp['competencyid'];
                        } else if (isset($cmcomp['competency'])) {
                            if (is_array($cmcomp['competency'])) {
                                $competencyid = $cmcomp['competency']['id'] ?? null;
                            } else if (is_object($cmcomp['competency']) && method_exists($cmcomp['competency'], 'get')) {
                                $competencyid = $cmcomp['competency']->get('id');
                            } else if (is_object($cmcomp['competency'])) {
                                $competencyid = $cmcomp['competency']->id ?? null;
                            }
                        }
                    } else if (method_exists($cmcomp, 'get')) {
                        $competencyid = $cmcomp->get('competencyid');
                    } else {
                        $competencyid = $cmcomp->competencyid;
                    }

                    $rate = $this->get_user_competency_rate($userid, $competencyid);
                    $isproficient = ($rate !== null && $rate >= $threshold);

                    if (!$isproficient) {
                        $competency = new \core_competency\competency($competencyid);
                        $rateval = ($rate !== null) ? sprintf('%.1f', $rate) : '0.0';
                        $missingcompetencies[] = get_string('competencyprogress', 'quizaccess_failgrade', [
                            'name' => $competency->get('shortname'),
                            'rate' => $rateval,
                            'threshold' => $threshold,
                        ]);
                    }
                }

                // Build the Competency Progress Table.
                $tablehtml = '';
                if (has_capability('mod/quiz:attempt', $this->quizobj->get_context())) {
                    $tablehtml .= '<div class="competency-results-table mt-4">';
                    $tablehtml .= '<h3>' . get_string('competencytable_heading', 'quizaccess_failgrade') . '</h3>';
                    $tablehtml .= '<table class="table table-striped table-bordered table-hover mt-2">';
                    $tablehtml .= '<thead>';
                    $tablehtml .= '<tr>';
                    $tablehtml .= '<th>' . get_string('competencytable_comp', 'quizaccess_failgrade') . '</th>';
                    $tablehtml .= '<th>' . get_string('competencytable_required', 'quizaccess_failgrade') . '</th>';
                    $tablehtml .= '<th>' . get_string('competencytable_achieved', 'quizaccess_failgrade') . '</th>';
                    $tablehtml .= '<th>' . get_string('competencytable_status', 'quizaccess_failgrade') . '</th>';
                    $tablehtml .= '</tr>';
                    $tablehtml .= '</thead>';
                    $tablehtml .= '<tbody>';

                    foreach ($cmcompetencies as $cmcomp) {
                        $competencyid = null;
                        if (is_array($cmcomp)) {
                            if (isset($cmcomp['competencyid'])) {
                                $competencyid = $cmcomp['competencyid'];
                            } else if (isset($cmcomp['competency'])) {
                                if (is_array($cmcomp['competency'])) {
                                    $competencyid = $cmcomp['competency']['id'] ?? null;
                                } else if (is_object($cmcomp['competency']) && method_exists($cmcomp['competency'], 'get')) {
                                    $competencyid = $cmcomp['competency']->get('id');
                                } else if (is_object($cmcomp['competency'])) {
                                    $competencyid = $cmcomp['competency']->id ?? null;
                                }
                            }
                        } else if (method_exists($cmcomp, 'get')) {
                            $competencyid = $cmcomp->get('competencyid');
                        } else {
                            $competencyid = $cmcomp->competencyid;
                        }

                        $rate = $this->get_user_competency_rate($userid, $competencyid);
                        $isproficient = ($rate !== null && $rate >= $threshold);
                        $competency = new \core_competency\competency($competencyid);

                        $rateval = ($rate !== null) ? sprintf('%.1f', $rate) . '%' : '0.0%';
                        $thresholdval = $threshold . '%';

                        if ($isproficient) {
                            $statusbadge = '<span class="badge badge-success bg-success text-white p-2">' .
                                get_string('competencytable_passed', 'quizaccess_failgrade') . '</span>';
                        } else {
                            $statusbadge = '<span class="badge badge-danger bg-danger text-white p-2">' .
                                get_string('competencytable_failed', 'quizaccess_failgrade') . '</span>';
                        }

                        $tablehtml .= '<tr>';
                        $tablehtml .= '<td><strong>' . s($competency->get('shortname')) . '</strong></td>';
                        $tablehtml .= '<td>' . $thresholdval . '</td>';
                        $tablehtml .= '<td>' . $rateval . '</td>';
                        $tablehtml .= '<td>' . $statusbadge . '</td>';
                        $tablehtml .= '</tr>';
                    }

                    $tablehtml .= '</tbody>';
                    $tablehtml .= '</table>';
                    $tablehtml .= '</div>';
                }

                if (!empty($missingcompetencies)) {
                    $separator = get_string('listseparator', 'quizaccess_failgrade');
                    $namesstring = implode($separator, $missingcompetencies);
                    $message = get_string('missingcompetencies', 'quizaccess_failgrade', $namesstring);
                    return $this->descriptioncache = [
                        '<div class="alert alert-warning" role="alert">' .
                        '<i class="fa fa-exclamation-triangle"></i> ' . $message . '</div>',
                        $tablehtml,
                    ];
                }

                // If they have all competencies, we only show success if they have taken the quiz.
                // Doing this check last saves DB queries for students who are missing competencies.
                $attempts = quiz_get_user_attempts($this->quizobj->get_quizid(), $userid, 'finished', true);
                if (!empty($attempts)) {
                    $successmessage = get_string('allcompetenciesmet', 'quizaccess_failgrade');
                    return $this->descriptioncache = [
                        '<div class="alert alert-success" role="alert">' .
                        '<i class="fa fa-check-circle"></i> ' . $successmessage . '</div>',
                        $tablehtml,
                    ];
                }

                // Has competencies but no attempts yet — show the generic description.
                return $this->descriptioncache = [
                    get_string('failgradedescription', 'quizaccess_failgrade'),
                    $tablehtml,
                ];
            }
        }

        // Fallback: Grade mode, or competency mode with no linked competencies.
        return $this->descriptioncache = [get_string('failgradedescription', 'quizaccess_failgrade')];
    }

    /**
     * Calculate user competency rate based on attempts for this specific quiz.
     *
     * @param int $userid
     * @param int $competencyid
     * @return float|null
     */
    protected function get_user_competency_rate($userid, $competencyid) {
        global $DB;
        $sql = "SELECT CAST(SUM(qa.maxfraction) AS DECIMAL(12,1)) AS questions,
                       CAST(SUM(qas.fraction) AS DECIMAL(12,1)) AS correct
                  FROM {quiz_attempts} quiza
                  JOIN {question_usages} qu ON qu.id = quiza.uniqueid
                  JOIN {question_attempts} qa ON qa.questionusageid = qu.id
                  JOIN {qbank_competency_qmap} m ON m.questionid = qa.questionid
                  JOIN (
                       SELECT MAX(fraction) AS fraction, questionattemptid
                         FROM {question_attempt_steps}
                     GROUP BY questionattemptid
                  ) qas ON qas.questionattemptid = qa.id
                 WHERE quiza.quiz = :quizid
                   AND quiza.userid = :userid
                   AND m.competencyid = :competencyid";

        $row = $DB->get_record_sql($sql, [
            'quizid' => $this->quizobj->get_quizid(),
            'userid' => $userid,
            'competencyid' => $competencyid,
        ]);
        if ($row && $row->questions > 0) {
            return ($row->correct / $row->questions) * 100;
        }
        return null;
    }

    /**
     * If this rule can determine that this user will never be allowed another attempt at
     * this quiz, then return true. This is used so we can know whether to display a
     * final grade on the view page. This will only be called if there is not a currently
     * active attempt for this user.
     * @param int $numprevattempts the number of previous attempts this user has made.
     * @param object $lastattempt information about the user's last completed attempt.
     * @return bool true if this rule means that this user will never be allowed another
     * attempt at this quiz.
     */
    public function is_finished($numprevattempts, $lastattempt) {
        global $USER;

        if ($numprevattempts === 0) {
            return false;
        }

        $userid = isset($lastattempt->userid) ? $lastattempt->userid : $USER->id;

        if (isset($this->isfinishedcache[$userid])) {
            return $this->isfinishedcache[$userid];
        }

        $mode = isset($this->quiz->failgradeenabled) ? (int) $this->quiz->failgradeenabled : 1;

        if ($mode == 2 && \core_competency\api::is_enabled()) {
            $cmid = $this->quizobj->get_cmid();
            $cmcompetencies = \core_competency\api::list_course_module_competencies($cmid);
            $totalcompetencies = count($cmcompetencies);

            if ($totalcompetencies > 0) {
                $threshold = isset($this->quiz->competencythreshold) ? (int) $this->quiz->competencythreshold : 0;
                if ($threshold <= 0) {
                    $threshold = (int) get_config('local_competency_report', 'success_threshold');
                    if ($threshold <= 0) {
                        $threshold = 60; // Default fallback.
                    }
                }

                $achievedcompetencies = 0;
                foreach ($cmcompetencies as $cmcomp) {
                    // Safe property extraction to support both arrays and objects.
                    $competencyid = null;
                    if (is_array($cmcomp)) {
                        if (isset($cmcomp['competencyid'])) {
                            $competencyid = $cmcomp['competencyid'];
                        } else if (isset($cmcomp['competency'])) {
                            if (is_array($cmcomp['competency'])) {
                                $competencyid = $cmcomp['competency']['id'] ?? null;
                            } else if (is_object($cmcomp['competency']) && method_exists($cmcomp['competency'], 'get')) {
                                $competencyid = $cmcomp['competency']->get('id');
                            } else if (is_object($cmcomp['competency'])) {
                                $competencyid = $cmcomp['competency']->id ?? null;
                            }
                        }
                    } else if (method_exists($cmcomp, 'get')) {
                        $competencyid = $cmcomp->get('competencyid');
                    } else {
                        $competencyid = $cmcomp->competencyid;
                    }

                    $rate = $this->get_user_competency_rate($userid, $competencyid);
                    if ($rate !== null && $rate >= $threshold) {
                        $achievedcompetencies++;
                    }
                }

                if ($achievedcompetencies == $totalcompetencies) {
                    return $this->isfinishedcache[$userid] = true;
                }
                return $this->isfinishedcache[$userid] = false;
            }
        }

        // Grade Mode (mode 1) or fallback.
        $item = \grade_item::fetch([
            'courseid' => $this->quizobj->get_courseid(),
            'itemtype' => 'mod',
            'itemmodule' => 'quiz',
            'iteminstance' => $this->quizobj->get_quizid(),
            'outcomeid' => null,
        ]);

        if ($item) {
            $grades = \grade_grade::fetch_users_grades($item, [$userid], false);
            $grade = $grades[$userid];

            if (!empty($grade)) {
                return $this->isfinishedcache[$userid] = $grade->is_passed($item);
            }
        }

        return $this->isfinishedcache[$userid] = false;
    }

    /**
     * Add any fields that this rule requires to the quiz settings form. This
     * method is called from {@link mod_quiz_mod_form::definition()}, while the
     * security seciton is being built.
     * @param \mod_quiz\form\setup $quizform the quiz settings form that is being built.
     * @param \MoodleQuickForm $mform the wrapped MoodleQuickForm.
     */
    public static function add_settings_form_fields(
        $quizform,
        \MoodleQuickForm $mform
    ) {
        $options = [
            0 => get_string('failgrademode_disabled', 'quizaccess_failgrade'),
            1 => get_string('failgrademode_grade', 'quizaccess_failgrade'),
            2 => get_string('failgrademode_competency', 'quizaccess_failgrade'),
        ];

        $mform->addElement(
            'select',
            'failgradeenabled',
            get_string('failgradeenabled', 'quizaccess_failgrade'),
            $options
        );
        $mform->addHelpButton('failgradeenabled', 'failgradeenabled', 'quizaccess_failgrade');

        $mform->addElement(
            'text',
            'competencythreshold',
            get_string('competencythreshold', 'quizaccess_failgrade'),
            ['size' => '3', 'maxlength' => '3']
        );
        $mform->setType('competencythreshold', PARAM_INT);
        $mform->setDefault('competencythreshold', 0);
        $mform->addHelpButton('competencythreshold', 'competencythreshold', 'quizaccess_failgrade');
        $mform->hideIf('competencythreshold', 'failgradeenabled', 'neq', 2);
    }

    /**
     * Save any submitted settings when the quiz settings form is submitted. This
     * is called from {@link quiz_after_add_or_update()} in lib.php.
     * @param object $quiz the data from the quiz form, including $quiz->id
     * which is the id of the quiz being saved.
     */
    public static function save_settings($quiz) {
        global $DB;

        if (empty($quiz->failgradeenabled) || $quiz->failgradeenabled == 0) {
            $DB->delete_records('quizaccess_failgrade', ['quizid' => $quiz->id]);
        } else {
            $competencythreshold = isset($quiz->competencythreshold) ? (int) $quiz->competencythreshold : 0;
            if (!$DB->record_exists('quizaccess_failgrade', ['quizid' => $quiz->id])) {
                $record = new \stdClass();
                $record->quizid = $quiz->id;
                $record->failgradeenabled = $quiz->failgradeenabled;
                $record->competencythreshold = $competencythreshold;
                $DB->insert_record('quizaccess_failgrade', $record);
            } else {
                $record = $DB->get_record('quizaccess_failgrade', ['quizid' => $quiz->id]);
                $record->failgradeenabled = $quiz->failgradeenabled;
                $record->competencythreshold = $competencythreshold;
                $DB->update_record('quizaccess_failgrade', $record);
            }
        }
    }

    /**
     * Delete any rule-specific settings when the quiz is deleted. This is called
     * from {@link quiz_delete_instance()} in lib.php.
     * @param object $quiz the data from the database, including $quiz->id
     * which is the id of the quiz being deleted.
     * @since Moodle 2.7.1, 2.6.4, 2.5.7
     */
    public static function delete_settings($quiz) {
        global $DB;

        $DB->delete_records('quizaccess_failgrade', ['quizid' => $quiz->id]);
    }

    /**
     * Return the bits of SQL needed to load all the settings from all the access
     * plugins in one DB query. The easiest way to understand what you need to do
     * here is probalby to read the code of {@link quiz_access_manager::load_settings()}.
     *
     * @param int $quizid the id of the quiz we are loading settings for. This
     * can also be accessed as quiz.id in the SQL. (quiz is a table alisas for {quiz}.)
     * @return array with three elements:
     */
    public static function get_settings_sql($quizid) {
        return [
            'failgradeenabled, competencythreshold',
            'LEFT JOIN {quizaccess_failgrade} failgrade ON failgrade.quizid = quiz.id',
            [],
        ];
    }
}
