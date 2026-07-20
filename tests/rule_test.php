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
 * Unit tests for the quizaccess_failgrade_ext plugin.
 *
 * @package    quizaccess_failgrade_ext
 * @copyright  2026 Mahmoud Salem
 * @copyright  based on work by 2020 Alexandre Paes RigÃ£o <rigao.com.br>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quizaccess_failgrade_ext;

use advanced_testcase;
use quizaccess_failgrade_ext;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/mod/quiz/accessrule/failgrade_ext/rule.php');

// This work-around is required until Moodle 4.2 is the lowest version we support.
if (class_exists('\mod_quiz\local\access_rule_base')) {
    // Use aliases at class_loader level to maintain compatibility.
    \class_alias('\mod_quiz\quiz_attempt', '\quiz_attempt');
}

/**
 * Unit tests for the quizaccess_failgrade_ext plugin.
 *
 * @copyright  2026 Mahmoud Salem
 * @copyright  based on work by 2020 Alexandre Paes RigÃ£o <rigao.com.br>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rule_test extends advanced_testcase {
    public function test_setting() {
        global $CFG;

        $this->resetAfterTest();

        // Setup.
        $CFG->enablecompletion = true;
        $CFG->enableavailability = true;
        $generator = $this->getDataGenerator();

        $course = $generator->create_course(
            ['numsections' => 1, 'enablecompletion' => 1],
            ['createsections' => true]
        );

        $user = $generator->create_user();
        $generator->enrol_user($user->id, $course->id);
        $this->setUser($user);

        $group = $generator->create_group(['courseid' => $course->id]);
        groups_add_member($group, $user);

        $quizgenerator = $generator->get_plugin_generator('mod_quiz');

        // Test 1.
        $quiz = $quizgenerator->create_instance([
            'course' => $course->id,
            'questionsperpage' => 0,
            'grade' => 10.0,
            'sumgrades' => 2,
            'attempts' => 5,
            'name' => 'Quiz!',
            'grademethod' => QUIZ_GRADEHIGHEST,
            'failgradeenabled' => 0,
        ]);
        $quizobj = \quiz::create($quiz->id, $user->id);

        $rule = quizaccess_failgrade_ext::make($quizobj, 0, false);
        $this->assertNull($rule);

        // Test 2.
        $quiz = $quizgenerator->create_instance([
            'course' => $course->id,
            'questionsperpage' => 0,
            'grade' => 10.0,
            'sumgrades' => 2,
            'attempts' => 5,
            'name' => 'Quiz!',
            'grademethod' => QUIZ_GRADEHIGHEST,
            'failgradeenabled' => 1,
        ]);
        $quizobj = \quiz::create($quiz->id, $user->id);

        $rule = quizaccess_failgrade_ext::make($quizobj, 0, false);
        $this->assertInstanceOf('quizaccess_failgrade_ext', $rule);
        $this->assertFalse($rule->is_finished(0, null));
        $this->assertEmpty($rule->prevent_new_attempt(0, null));
    }

    public function test_grade_highest() {
        global $CFG;

        $this->resetAfterTest();

        // Setup.
        $CFG->enablecompletion = true;
        $CFG->enableavailability = true;
        $generator = $this->getDataGenerator();

        $course = $generator->create_course(
            ['numsections' => 1, 'enablecompletion' => 1],
            ['createsections' => true]
        );

        $user = $generator->create_user();
        $generator->enrol_user($user->id, $course->id);
        $this->setUser($user);

        $group = $generator->create_group(['courseid' => $course->id]);
        groups_add_member($group, $user);

        $quizgenerator = $generator->get_plugin_generator('mod_quiz');

        $quiz = $quizgenerator->create_instance([
            'course' => $course->id,
            'questionsperpage' => 0,
            'grade' => 10.0,
            'sumgrades' => 2,
            'attempts' => 5,
            'name' => 'Quiz!',
            'grademethod' => QUIZ_GRADEHIGHEST,
            'failgradeenabled' => 1,
        ]);
        $quizobj = \quiz::create($quiz->id, $user->id);

        $item = \grade_item::fetch([
            'courseid' => $course->id,
            'itemtype' => 'mod',
            'itemmodule' => 'quiz',
            'iteminstance' => $quiz->id,
            'outcomeid' => null,
        ]);
        $item->gradepass = 6;
        $item->update();

        $questiongenerator = $generator->get_plugin_generator('core_question');
        $cat = $questiongenerator->create_question_category();
        $numq = $questiongenerator->create_question('numerical', null, ['category' => $cat->id]);
        quiz_add_quiz_question($numq->id, $quiz);
        $numq = $questiongenerator->create_question('numerical', null, ['category' => $cat->id]);
        quiz_add_quiz_question($numq->id, $quiz);

        // Fail.
        $quba = \question_engine::make_questions_usage_by_activity('mod_quiz', $quizobj->get_context());
        $quba->set_preferred_behaviour($quizobj->get_quiz()->preferredbehaviour);
        $timenow = time();
        $attempt = quiz_create_attempt($quizobj, 1, false, $timenow, false, $user->id);
        quiz_start_new_attempt($quizobj, $quba, $attempt, 1, $timenow);
        quiz_attempt_save_started($quizobj, $quba, $attempt);
        $attemptobj = \quiz_attempt::create($attempt->id);
        $attemptobj->process_submitted_actions($timenow, false, [1 => ['answer' => '3.14']]);
        $attemptobj->process_finish($timenow, false);

        $rule = quizaccess_failgrade_ext::make($quizobj, 0, false);
        $this->assertFalse($rule->is_finished(1, $attempt));
        $this->assertEmpty($rule->prevent_new_attempt(1, $attempt));

        // Pass.
        $quba = \question_engine::make_questions_usage_by_activity('mod_quiz', $quizobj->get_context());
        $quba->set_preferred_behaviour($quizobj->get_quiz()->preferredbehaviour);
        $timenow = time();
        $attempt = quiz_create_attempt($quizobj, 2, false, $timenow, false, $user->id);
        quiz_start_new_attempt($quizobj, $quba, $attempt, 2, $timenow);
        quiz_attempt_save_started($quizobj, $quba, $attempt);
        $attemptobj = \quiz_attempt::create($attempt->id);
        $attemptobj->process_submitted_actions($timenow, false, [1 => ['answer' => '3.14'], 2 => ['answer' => '3.14']]);
        $attemptobj->process_finish($timenow, false);

        $rule = quizaccess_failgrade_ext::make($quizobj, 0, false);
        $this->assertTrue($rule->is_finished(2, $attempt));
        $this->assertNotEmpty($rule->prevent_new_attempt(2, $attempt));

        // Fail.
        $quba = \question_engine::make_questions_usage_by_activity('mod_quiz', $quizobj->get_context());
        $quba->set_preferred_behaviour($quizobj->get_quiz()->preferredbehaviour);
        $timenow = time();
        $attempt = quiz_create_attempt($quizobj, 3, false, $timenow, false, $user->id);
        quiz_start_new_attempt($quizobj, $quba, $attempt, 3, $timenow);
        quiz_attempt_save_started($quizobj, $quba, $attempt);
        $attemptobj = \quiz_attempt::create($attempt->id);
        $attemptobj->process_submitted_actions($timenow, false, [1 => ['answer' => '3.14']]);
        $attemptobj->process_finish($timenow, false);

        $rule = quizaccess_failgrade_ext::make($quizobj, 0, false);
        $this->assertTrue($rule->is_finished(3, $attempt));
        $this->assertNotEmpty($rule->prevent_new_attempt(3, $attempt));
    }

    public function test_grade_firstattempt() {
        global $CFG;

        $this->resetAfterTest();

        // Setup.
        $CFG->enablecompletion = true;
        $CFG->enableavailability = true;
        $generator = $this->getDataGenerator();

        $course = $generator->create_course(
            ['numsections' => 1, 'enablecompletion' => 1],
            ['createsections' => true]
        );

        $user = $generator->create_user();
        $generator->enrol_user($user->id, $course->id);
        $this->setUser($user);

        $group = $generator->create_group(['courseid' => $course->id]);
        groups_add_member($group, $user);

        $quizgenerator = $generator->get_plugin_generator('mod_quiz');

        // Fail.
        $quiz = $quizgenerator->create_instance([
            'course' => $course->id,
            'questionsperpage' => 0,
            'grade' => 10.0,
            'sumgrades' => 2,
            'attempts' => 5,
            'name' => 'Quiz!',
            'grademethod' => QUIZ_ATTEMPTFIRST,
            'failgradeenabled' => 1,
        ]);
        $quizobj = \quiz::create($quiz->id, $user->id);

        $item = \grade_item::fetch([
            'courseid' => $course->id,
            'itemtype' => 'mod',
            'itemmodule' => 'quiz',
            'iteminstance' => $quiz->id,
            'outcomeid' => null,
        ]);
        $item->gradepass = 6;
        $item->update();

        $questiongenerator = $generator->get_plugin_generator('core_question');
        $cat = $questiongenerator->create_question_category();
        $numq = $questiongenerator->create_question('numerical', null, ['category' => $cat->id]);
        quiz_add_quiz_question($numq->id, $quiz);
        $numq = $questiongenerator->create_question('numerical', null, ['category' => $cat->id]);
        quiz_add_quiz_question($numq->id, $quiz);

        $quba = \question_engine::make_questions_usage_by_activity('mod_quiz', $quizobj->get_context());
        $quba->set_preferred_behaviour($quizobj->get_quiz()->preferredbehaviour);
        $timenow = time();
        $attempt = quiz_create_attempt($quizobj, 1, false, $timenow, false, $user->id);
        quiz_start_new_attempt($quizobj, $quba, $attempt, 1, $timenow);
        quiz_attempt_save_started($quizobj, $quba, $attempt);
        $attemptobj = \quiz_attempt::create($attempt->id);
        $attemptobj->process_submitted_actions($timenow, false, [1 => ['answer' => '3.14']]);
        $attemptobj->process_finish($timenow, false);

        $rule = quizaccess_failgrade_ext::make($quizobj, 0, false);
        $this->assertFalse($rule->is_finished(1, $attempt));
        $this->assertEmpty($rule->prevent_new_attempt(1, $attempt));

        $quba = \question_engine::make_questions_usage_by_activity('mod_quiz', $quizobj->get_context());
        $quba->set_preferred_behaviour($quizobj->get_quiz()->preferredbehaviour);
        $timenow = time();
        $attempt = quiz_create_attempt($quizobj, 2, false, $timenow, false, $user->id);
        quiz_start_new_attempt($quizobj, $quba, $attempt, 2, $timenow);
        quiz_attempt_save_started($quizobj, $quba, $attempt);
        $attemptobj = \quiz_attempt::create($attempt->id);
        $attemptobj->process_submitted_actions($timenow, false, [1 => ['answer' => '3.14'], 2 => ['answer' => '3.14']]);
        $attemptobj->process_finish($timenow, false);

        $rule = quizaccess_failgrade_ext::make($quizobj, 0, false);
        $this->assertFalse($rule->is_finished(2, $attempt));
        $this->assertEmpty($rule->prevent_new_attempt(2, $attempt));

        // Pass.
        $quiz = $quizgenerator->create_instance([
            'course' => $course->id,
            'questionsperpage' => 0,
            'grade' => 10.0,
            'sumgrades' => 2,
            'attempts' => 5,
            'name' => 'Quiz!',
            'grademethod' => QUIZ_ATTEMPTFIRST,
            'failgradeenabled' => 1,
        ]);
        $quizobj = \quiz::create($quiz->id, $user->id);

        $item = \grade_item::fetch([
            'courseid' => $course->id,
            'itemtype' => 'mod',
            'itemmodule' => 'quiz',
            'iteminstance' => $quiz->id,
            'outcomeid' => null,
        ]);
        $item->gradepass = 6;
        $item->update();

        $questiongenerator = $generator->get_plugin_generator('core_question');
        $cat = $questiongenerator->create_question_category();
        $numq = $questiongenerator->create_question('numerical', null, ['category' => $cat->id]);
        quiz_add_quiz_question($numq->id, $quiz);
        $numq = $questiongenerator->create_question('numerical', null, ['category' => $cat->id]);
        quiz_add_quiz_question($numq->id, $quiz);

        $quba = \question_engine::make_questions_usage_by_activity('mod_quiz', $quizobj->get_context());
        $quba->set_preferred_behaviour($quizobj->get_quiz()->preferredbehaviour);
        $timenow = time();
        $attempt = quiz_create_attempt($quizobj, 1, false, $timenow, false, $user->id);
        quiz_start_new_attempt($quizobj, $quba, $attempt, 1, $timenow);
        quiz_attempt_save_started($quizobj, $quba, $attempt);
        $attemptobj = \quiz_attempt::create($attempt->id);
        $attemptobj->process_submitted_actions($timenow, false, [1 => ['answer' => '3.14'], 2 => ['answer' => '3.14']]);
        $attemptobj->process_finish($timenow, false);

        $rule = quizaccess_failgrade_ext::make($quizobj, 0, false);
        $this->assertTrue($rule->is_finished(1, $attempt));
        $this->assertNotEmpty($rule->prevent_new_attempt(1, $attempt));
    }

    public function test_grade_lastattempt() {
        global $CFG;

        $this->resetAfterTest();

        // Setup.
        $CFG->enablecompletion = true;
        $CFG->enableavailability = true;
        $generator = $this->getDataGenerator();

        $course = $generator->create_course(
            ['numsections' => 1, 'enablecompletion' => 1],
            ['createsections' => true]
        );

        $user = $generator->create_user();
        $generator->enrol_user($user->id, $course->id);
        $this->setUser($user);

        $group = $generator->create_group(['courseid' => $course->id]);
        groups_add_member($group, $user);

        $quizgenerator = $generator->get_plugin_generator('mod_quiz');

        // Fail then Pass.

        $quiz = $quizgenerator->create_instance([
            'course' => $course->id,
            'questionsperpage' => 0,
            'grade' => 10.0,
            'sumgrades' => 2,
            'attempts' => 5,
            'name' => 'Quiz!',
            'grademethod' => QUIZ_ATTEMPTLAST,
            'failgradeenabled' => 1,
        ]);
        $quizobj = \quiz::create($quiz->id, $user->id);

        $item = \grade_item::fetch([
            'courseid' => $course->id,
            'itemtype' => 'mod',
            'itemmodule' => 'quiz',
            'iteminstance' => $quiz->id,
            'outcomeid' => null,
        ]);
        $item->gradepass = 6;
        $item->update();

        $questiongenerator = $generator->get_plugin_generator('core_question');
        $cat = $questiongenerator->create_question_category();
        $numq = $questiongenerator->create_question('numerical', null, ['category' => $cat->id]);
        quiz_add_quiz_question($numq->id, $quiz);
        $numq = $questiongenerator->create_question('numerical', null, ['category' => $cat->id]);
        quiz_add_quiz_question($numq->id, $quiz);

        $quba = \question_engine::make_questions_usage_by_activity('mod_quiz', $quizobj->get_context());
        $quba->set_preferred_behaviour($quizobj->get_quiz()->preferredbehaviour);
        $timenow = time();
        $attempt = quiz_create_attempt($quizobj, 1, false, $timenow, false, $user->id);
        quiz_start_new_attempt($quizobj, $quba, $attempt, 1, $timenow);
        quiz_attempt_save_started($quizobj, $quba, $attempt);
        $attemptobj = \quiz_attempt::create($attempt->id);
        $attemptobj->process_submitted_actions($timenow, false, [1 => ['answer' => '3.14']]);
        $attemptobj->process_finish($timenow, false);

        $rule = quizaccess_failgrade_ext::make($quizobj, 0, false);
        $this->assertFalse($rule->is_finished(1, $attempt));
        $this->assertEmpty($rule->prevent_new_attempt(1, $attempt));

        $quba = \question_engine::make_questions_usage_by_activity('mod_quiz', $quizobj->get_context());
        $quba->set_preferred_behaviour($quizobj->get_quiz()->preferredbehaviour);
        $timenow = time();
        $attempt = quiz_create_attempt($quizobj, 2, false, $timenow, false, $user->id);
        quiz_start_new_attempt($quizobj, $quba, $attempt, 2, $timenow);
        quiz_attempt_save_started($quizobj, $quba, $attempt);
        $attemptobj = \quiz_attempt::create($attempt->id);
        $attemptobj->process_submitted_actions($timenow, false, [1 => ['answer' => '3.14'], 2 => ['answer' => '3.14']]);
        $attemptobj->process_finish($timenow, false);

        $rule = quizaccess_failgrade_ext::make($quizobj, 0, false);
        $this->assertTrue($rule->is_finished(2, $attempt));
        $this->assertNotEmpty($rule->prevent_new_attempt(2, $attempt));
    }

    public function test_grade_average() {
        global $CFG;

        $this->resetAfterTest();

        // Setup.
        $CFG->enablecompletion = true;
        $CFG->enableavailability = true;
        $generator = $this->getDataGenerator();

        $course = $generator->create_course(
            ['numsections' => 1, 'enablecompletion' => 1],
            ['createsections' => true]
        );

        $user = $generator->create_user();
        $generator->enrol_user($user->id, $course->id);
        $this->setUser($user);

        $group = $generator->create_group(['courseid' => $course->id]);
        groups_add_member($group, $user);

        $quizgenerator = $generator->get_plugin_generator('mod_quiz');

        $quiz = $quizgenerator->create_instance([
            'course' => $course->id,
            'questionsperpage' => 0,
            'grade' => 10.0,
            'sumgrades' => 2,
            'attempts' => 0,
            'name' => 'Quiz!',
            'grademethod' => QUIZ_GRADEAVERAGE,
            'failgradeenabled' => 1,
        ]);
        $quizobj = \quiz::create($quiz->id, $user->id);

        $item = \grade_item::fetch([
            'courseid' => $course->id,
            'itemtype' => 'mod',
            'itemmodule' => 'quiz',
            'iteminstance' => $quiz->id,
            'outcomeid' => null,
        ]);
        $item->gradepass = 6;
        $item->update();

        $questiongenerator = $generator->get_plugin_generator('core_question');
        $cat = $questiongenerator->create_question_category();
        $numq = $questiongenerator->create_question('numerical', null, ['category' => $cat->id]);
        quiz_add_quiz_question($numq->id, $quiz);
        $numq = $questiongenerator->create_question('numerical', null, ['category' => $cat->id]);
        quiz_add_quiz_question($numq->id, $quiz);

        $quba = \question_engine::make_questions_usage_by_activity('mod_quiz', $quizobj->get_context());
        $quba->set_preferred_behaviour($quizobj->get_quiz()->preferredbehaviour);
        $timenow = time();
        $attempt = quiz_create_attempt($quizobj, 1, false, $timenow, false, $user->id);
        quiz_start_new_attempt($quizobj, $quba, $attempt, 1, $timenow);
        quiz_attempt_save_started($quizobj, $quba, $attempt);
        $attemptobj = \quiz_attempt::create($attempt->id);
        $attemptobj->process_submitted_actions($timenow, false, [1 => ['answer' => '3.14']]);
        $attemptobj->process_finish($timenow, false);

        $rule = quizaccess_failgrade_ext::make($quizobj, 0, false);
        $this->assertFalse($rule->is_finished(1, $attempt));
        $this->assertEmpty($rule->prevent_new_attempt(1, $attempt));

        $quba = \question_engine::make_questions_usage_by_activity('mod_quiz', $quizobj->get_context());
        $quba->set_preferred_behaviour($quizobj->get_quiz()->preferredbehaviour);
        $timenow = time();
        $attempt = quiz_create_attempt($quizobj, 2, false, $timenow, false, $user->id);
        quiz_start_new_attempt($quizobj, $quba, $attempt, 2, $timenow);
        quiz_attempt_save_started($quizobj, $quba, $attempt);
        $attemptobj = \quiz_attempt::create($attempt->id);
        $attemptobj->process_submitted_actions($timenow, false, [1 => ['answer' => '3.14'], 2 => ['answer' => '3.14']]);
        $attemptobj->process_finish($timenow, false);

        $rule = quizaccess_failgrade_ext::make($quizobj, 0, false);
        $this->assertTrue($rule->is_finished(2, $attempt));
        $this->assertNotEmpty($rule->prevent_new_attempt(2, $attempt));
    }

    /**
     * Test mode=2 (competency-based): student has NOT achieved all competencies -> is_finished returns false.
     */
    public function test_competency_mode_not_all_achieved() {
        global $CFG;

        $this->resetAfterTest();

        // Competency subsystem must be enabled.
        $CFG->enablecompletion  = true;
        $CFG->enableavailability = true;

        $generator = $this->getDataGenerator();

        $course = $generator->create_course(
            ['numsections' => 1, 'enablecompletion' => 1],
            ['createsections' => true]
        );

        $user = $generator->create_user();
        $generator->enrol_user($user->id, $course->id);

        $this->setAdminUser(); // Set admin to manage competencies.

        // Create a competency framework and two competencies.
        $lpgenerator = $generator->get_plugin_generator('core_competency');
        $framework   = $lpgenerator->create_framework();
        $comp1       = $lpgenerator->create_competency(['competencyframeworkid' => $framework->get('id')]);
        $comp2       = $lpgenerator->create_competency(['competencyframeworkid' => $framework->get('id')]);

        $quizgenerator = $generator->get_plugin_generator('mod_quiz');
        $quiz = $quizgenerator->create_instance([
            'course'           => $course->id,
            'questionsperpage' => 0,
            'grade'            => 10.0,
            'sumgrades'        => 2,
            'attempts'         => 0,
            'name'             => 'Competency Quiz',
            'grademethod'      => QUIZ_GRADEHIGHEST,
            'failgradeenabled' => 2,
            'competencythreshold' => 60,
        ]);
        $quizobj = \quiz::create($quiz->id, $user->id);

        // CORRECT ORDER: 1. Add course-level competency entries for the user first.
        \core_competency\api::add_competency_to_course($course->id, $comp1->get('id'));
        \core_competency\api::add_competency_to_course($course->id, $comp2->get('id'));

        // CORRECT ORDER: 2. Then link both competencies to the quiz course module.
        $cm = get_coursemodule_from_instance('quiz', $quiz->id, $course->id);
        \core_competency\api::add_competency_to_course_module($cm, $comp1->get('id'));
        \core_competency\api::add_competency_to_course_module($cm, $comp2->get('id'));

        $this->setUser($user); // Now switch back to user.

        // Setup mock rates: student achieved 50% on both competencies (threshold is 60%).
        $rule = new class ($quizobj, 0) extends \quizaccess_failgrade_ext {
            /** @var array Mocked rates for competencies. */
            public $mockrates = [];

            /**
             * Override get_user_competency_rate to return mock rates instead of running DB queries.
             *
             * @param int $userid The user ID.
             * @param int $competencyid The competency ID.
             * @return float|null The competency rate, or null if none.
             */
            protected function get_user_competency_rate($userid, $competencyid) {
                if (isset($this->mockrates[$competencyid])) {
                    return $this->mockrates[$competencyid];
                }
                return null;
            }
        };
        $rule->mockrates = [
            $comp1->get('id') => 50.0,
            $comp2->get('id') => 50.0,
        ];
        $this->assertInstanceOf('quizaccess_failgrade_ext', $rule);

        // Simulate one completed quiz attempt.
        $attempt = (object)['userid' => $user->id];

        // No competency is marked proficient -> quiz must remain open (is_finished = false).
        $this->assertFalse($rule->is_finished(1, $attempt));
        $this->assertFalse($rule->prevent_new_attempt(1, $attempt));
    }

    /**
     * Test mode=2 (competency-based): student HAS achieved ALL competencies -> is_finished returns true.
     */
    public function test_competency_mode_all_achieved() {
        global $CFG;

        $this->resetAfterTest();

        $CFG->enablecompletion   = true;
        $CFG->enableavailability = true;

        $generator = $this->getDataGenerator();

        $course = $generator->create_course(
            ['numsections' => 1, 'enablecompletion' => 1],
            ['createsections' => true]
        );

        $user = $generator->create_user();
        $generator->enrol_user($user->id, $course->id);

        $this->setAdminUser(); // Set admin to manage competencies.

        // Create framework + two competencies.
        $lpgenerator = $generator->get_plugin_generator('core_competency');
        $framework   = $lpgenerator->create_framework();
        $comp1       = $lpgenerator->create_competency(['competencyframeworkid' => $framework->get('id')]);
        $comp2       = $lpgenerator->create_competency(['competencyframeworkid' => $framework->get('id')]);

        $quizgenerator = $generator->get_plugin_generator('mod_quiz');
        $quiz = $quizgenerator->create_instance([
            'course'           => $course->id,
            'questionsperpage' => 0,
            'grade'            => 10.0,
            'sumgrades'        => 2,
            'attempts'         => 0,
            'name'             => 'Competency Quiz',
            'grademethod'      => QUIZ_GRADEHIGHEST,
            'failgradeenabled' => 2,
            'competencythreshold' => 60,
        ]);
        $quizobj = \quiz::create($quiz->id, $user->id);

        // CORRECT ORDER: 1. Add course-level competencies first.
        \core_competency\api::add_competency_to_course($course->id, $comp1->get('id'));
        \core_competency\api::add_competency_to_course($course->id, $comp2->get('id'));

        // CORRECT ORDER: 2. Then link both competencies to the course module.
        $cm = get_coursemodule_from_instance('quiz', $quiz->id, $course->id);
        \core_competency\api::add_competency_to_course_module($cm, $comp1->get('id'));
        \core_competency\api::add_competency_to_course_module($cm, $comp2->get('id'));

        $this->setUser($user); // Now switch back to user.

        // Setup mock rates: student achieved 75% and 80% (both >= 60% threshold).
        $rule = new class ($quizobj, 0) extends \quizaccess_failgrade_ext {
            /** @var array Mocked rates for competencies. */
            public $mockrates = [];

            /**
             * Override get_user_competency_rate to return mock rates instead of running DB queries.
             *
             * @param int $userid The user ID.
             * @param int $competencyid The competency ID.
             * @return float|null The competency rate, or null if none.
             */
            protected function get_user_competency_rate($userid, $competencyid) {
                if (isset($this->mockrates[$competencyid])) {
                    return $this->mockrates[$competencyid];
                }
                return null;
            }
        };
        $rule->mockrates = [
            $comp1->get('id') => 75.0,
            $comp2->get('id') => 80.0,
        ];
        $this->assertInstanceOf('quizaccess_failgrade_ext', $rule);

        // Simulate one completed quiz attempt.
        $attempt = (object)['userid' => $user->id];

        // Both competencies achieved -> quiz must be finished (is_finished = true).
        $this->assertTrue($rule->is_finished(1, $attempt));
        $this->assertNotEmpty($rule->prevent_new_attempt(1, $attempt));
    }

    /**
     * Test Mode=0 (disabled): the rule must not be instantiated at all.
     */
    public function test_mode_disabled() {
        $this->resetAfterTest();

        $generator     = $this->getDataGenerator();
        $course        = $generator->create_course(['numsections' => 1], ['createsections' => true]);
        $user          = $generator->create_user();
        $generator->enrol_user($user->id, $course->id);
        $this->setUser($user);

        $quizgenerator = $generator->get_plugin_generator('mod_quiz');
        $quiz = $quizgenerator->create_instance([
            'course'           => $course->id,
            'grade'            => 10.0,
            'sumgrades'        => 2,
            'failgradeenabled' => 0,
        ]);
        $quizobj = \quiz::create($quiz->id, $user->id);

        // Mode=0 means make() must return null - the rule is not active.
        $rule = quizaccess_failgrade_ext::make($quizobj, 0, false);
        $this->assertNull($rule);
    }
}
