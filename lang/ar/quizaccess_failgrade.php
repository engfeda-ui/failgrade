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
 * Arabic language strings for the quizaccess_failgrade plugin.
 *
 * @package   quizaccess_failgrade
 * @copyright  2026 Mahmoud Salem
 * @copyright  based on work by 2026 quizaccess_failgrade contributors
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'تقييد الدرجة';
$string['privacy:metadata'] = 'لا تقوم الإضافة بتخزين أي بيانات شخصية.';

$string['failgradeenabled'] = 'منع المحاولات الإضافية إذا نجح';
$string['failgradeenabled_help'] = 'يمنع المتدرب من الدخول في محاولة إضافية للاختبار بمجرد اجتيازه بنجاح، بناءً على المعيار المختار (الدرجة أو الكفاءات).';

$string['failgradedescription'] = 'المحاولات متاحة حتى الوصول للنجاح.';
$string['preventmoreattempts'] = 'لقد اجتزت هذا الاختبار بنجاح، ولا يمكنك القيام بمحاولات إضافية.';

// Settings options.
$string['failgrademode_disabled']   = 'لا (معطل)';
$string['failgrademode_grade']      = 'نعم (الاعتماد على درجة النجاح)';
$string['failgrademode_competency'] = 'نعم (الاعتماد على الجدارات)';

// Student-facing messages.
$string['listseparator'] = '، ';
$string['missingcompetencies'] = 'عفواً، لم تتقن بعد كافة الجدارات الفنية المطلوبة لإنهاء الاختبار بنجاح. الجدارات التي تحتاج إلى تحسين وإتقان في المحاولة القادمة هي: <br><strong>{$a}</strong>';
$string['allcompetenciesmet']  = 'تهانينا! لقد أتقنت كافة الجدارات الفنية المربوطة بهذا الاختبار بنجاح.';
