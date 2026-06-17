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
 * Standalone "course details" page for never-logged-in visitors — built
 * directly from mockups/course-overview-component.html (and -ar.html) via
 * theme_veraxity\output\course_overview_card, not via core's course/
 * info.php or the core_course_renderer::course_info_box() override (those
 * stay as they were, for the logged-in/guest enrolment flow). Front-page
 * program cards link anonymous visitors here instead of course/view.php,
 * which would otherwise force them through a login wall.
 *
 * @package    theme_veraxity
 */

require_once(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);
$course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);

if ($course->id == SITEID) {
    redirect(new moodle_url('/'));
}

// This page exists only for the "haven't logged in yet" visitor experience
// — anyone with a session, including an explicit guest login, belongs on
// the real course page instead.
if (isloggedin()) {
    redirect(new moodle_url('/course/view.php', ['id' => $course->id]));
}

if ($CFG->forcelogin) {
    require_login();
}

$context = context_course::instance($course->id);
if (!core_course_category::can_view_course_info($course) && !is_enrolled($context, null, '', true)) {
    throw new moodle_exception('cannotviewcategory', '', $CFG->wwwroot . '/');
}

$PAGE->set_course($course);
$PAGE->set_pagelayout('coursedetails');
$PAGE->set_url('/theme/veraxity/coursedetails.php', ['id' => $course->id]);

$coursename = format_string($course->fullname, true, ['context' => $context]);
$strcourseinfo = get_string('courseinfo');
$PAGE->set_title(implode(moodle_page::TITLE_SEPARATOR, [$strcourseinfo, $coursename]));
$PAGE->set_heading($strcourseinfo);

$listelement = new core_course_list_element($course);

echo $OUTPUT->header();
echo (new \theme_veraxity\output\course_overview_card($listelement))->render();

\core\event\course_information_viewed::create([
    'context' => $context,
    'objectid' => $course->id,
])->trigger();

echo $OUTPUT->footer();
