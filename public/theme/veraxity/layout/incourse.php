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
 * Incourse layout override — of the pages using this layout, the only one a
 * never-logged-in visitor can actually reach is course/info.php (everything
 * else, including enrol/index.php, redirects anonymous users to login
 * before this layout ever runs). For that visitor-facing case, swap in the
 * same marketing nav + footer chrome as the front page; logged-in users
 * (including guest sessions, who are "logged in" too) keep Boost's normal
 * drawers layout untouched.
 *
 * @package    theme_veraxity
 */

defined('MOODLE_INTERNAL') || die();

if (isloggedin()) {
    require($CFG->dirroot . '/theme/boost/layout/drawers.php');
    return;
}

$templatecontext = [
    'output' => $OUTPUT,
    'bodyattributes' => $OUTPUT->body_attributes(),
    'navwordmark' => get_string('navwordmark', 'theme_veraxity'),
    'loginlabel' => get_string('login'),
    'loginurl' => get_login_url(),
    'enrollurl' => theme_veraxity_get_enroll_url(),
    'courseindexurl' => (new \moodle_url('/course/index.php'))->out(),
    'homeurl' => (new \moodle_url('/'))->out(),
    'footercopyright' => get_string('footercopyright', 'theme_veraxity', date('Y')),
];

echo $OUTPUT->render_from_template('theme_veraxity/visitorcourse', $templatecontext);
