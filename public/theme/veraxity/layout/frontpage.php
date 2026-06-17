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
 * Front page layout — a marketing-style nav and footer (matching the
 * academy-portal-concept mockup) replacing Boost's drawers chrome, used only
 * for the 'frontpage' pagelayout. The page body itself comes from
 * theme_veraxity\output\core\course_renderer::frontpage() via main_content().
 *
 * @package    theme_veraxity
 */

defined('MOODLE_INTERNAL') || die();

if (isloggedin() && !isguestuser()) {
    $loginlabel = get_string('myhome');
    $loginurl = (new \moodle_url('/my/'))->out();
} else {
    $loginlabel = get_string('login');
    $loginurl = get_login_url();
}

$templatecontext = [
    'output' => $OUTPUT,
    'bodyattributes' => $OUTPUT->body_attributes(),
    'navwordmark' => get_string('navwordmark', 'theme_veraxity'),
    'loginlabel' => $loginlabel,
    'loginurl' => $loginurl,
    'enrollurl' => theme_veraxity_get_enroll_url(),
    'courseindexurl' => (new \moodle_url('/course/index.php'))->out(),
    'footercopyright' => get_string('footercopyright', 'theme_veraxity', date('Y')),
];

echo $OUTPUT->render_from_template('theme_veraxity/frontpage', $templatecontext);
