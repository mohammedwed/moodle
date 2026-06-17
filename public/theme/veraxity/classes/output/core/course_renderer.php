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
 * Course renderer override — a masthead hero and the course list rendered as
 * a terminal directory listing, since Veraxity is a software architect's
 * academy and that's the artifact that subject actually produces, rather
 * than a generic stat-block + card-grid.
 *
 * @package    theme_veraxity
 */

namespace theme_veraxity\output\core;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/renderer.php');

class course_renderer extends \core_course_renderer {

    public function frontpage() {
        return $this->veraxity_hero() . $this->veraxity_values() . parent::frontpage();
    }

    protected function veraxity_hero(): string {
        $courseindexurl = (new \moodle_url('/course/index.php'))->out();

        // Posts to the same endpoint core's course_search_form() would use
        // (falls back to course/search.php when no search engine is
        // configured, so this needs zero extra setup) but with custom
        // markup: the mustache template's placeholder ("Search courses")
        // collided visually with a literal "search" in the prompt text.
        $searchurl = \core_search\manager::get_course_search_url()->out();
        $searchlabel = get_string('searchcourses');
        $searchicon = $this->pix_icon('a/search', $searchlabel, 'core');
        $inputid = \html_writer::random_id('veraxity-hero-search');

        return '
<div class="veraxity-hero">
    <div class="veraxity-hero__inner">
        <div class="veraxity-hero__eyebrow">' . get_string('herotagline', 'theme_veraxity') . '</div>
        <h1 class="veraxity-hero__title">' . get_string('herotitle', 'theme_veraxity') . '</h1>
        <p class="veraxity-hero__subtitle">' . get_string('herosubtitle', 'theme_veraxity') . '</p>
        <form class="veraxity-hero__search" action="' . $searchurl . '" method="get">
            <label for="' . $inputid . '" class="visually-hidden">' . $searchlabel . '</label>
            <input type="text" id="' . $inputid . '" name="q" class="veraxity-hero__search-input"
                placeholder="' . get_string('herosearchplaceholder', 'theme_veraxity') . '" autocomplete="off">
            <button type="submit" class="veraxity-hero__search-submit">
                ' . $searchicon . '
                <span class="visually-hidden">' . $searchlabel . '</span>
            </button>
        </form>
        <div class="veraxity-hero__divider"><span>' . get_string('herodivider', 'theme_veraxity') . '</span></div>
        <a class="veraxity-hero__cta" href="' . $courseindexurl . '">' . get_string('herocta', 'theme_veraxity') . '</a>
    </div>
</div>';
    }

    /**
     * A short "why us" section between the hero and the course listing.
     * Reuses the same folio-numbered visual grammar as the terminal course
     * entries below (01/, 02/, 03/) instead of introducing an unrelated
     * icon-card pattern.
     */
    protected function veraxity_values(): string {
        $items = '';
        for ($i = 1; $i <= 3; $i++) {
            $items .= '
        <div class="veraxity-values__item">
            <span class="veraxity-values__index" dir="ltr">' . str_pad((string) $i, 2, '0', STR_PAD_LEFT) . '/</span>
            <h3 class="veraxity-values__title">' . get_string("value{$i}title", 'theme_veraxity') . '</h3>
            <p class="veraxity-values__body">' . get_string("value{$i}body", 'theme_veraxity') . '</p>
        </div>';
        }

        return '
<div class="veraxity-values">
    <h2 class="visually-hidden">' . get_string('valuesheading', 'theme_veraxity') . '</h2>
    <div class="veraxity-values__grid">' . $items . '</div>
</div>';
    }

    /**
     * Overrides the default boxed course list with a terminal-style
     * directory listing instead of a card grid. Surgical override: only
     * this part of frontpage() changes, so site news / category names /
     * search box still render normally via the parent if Site home
     * settings ever enable them.
     */
    public function frontpage_available_courses() {
        global $CFG, $DB;

        $chelper = new \coursecat_helper();
        $chelper->set_show_courses(self::COURSECAT_SHOW_COURSES_EXPANDED)
            ->set_courses_display_options([
                'recursive' => true,
                'limit' => $CFG->frontpagecourselimit,
                // A real `ls` prints entries in a stable, predictable order —
                // Moodle's default 'sortorder' doesn't match the folio numbers
                // (course ids) the listing actually displays, so it's forced
                // to id order here instead.
                'sort' => ['id' => 1],
            ]);

        $options = $chelper->get_courses_display_options();
        $courses = \core_course_category::top()->get_courses($options);
        $totalcount = \core_course_category::top()->get_courses_count($options);

        if (!$totalcount && !$this->page->user_is_editing() &&
                has_capability('moodle/course:create', \context_system::instance())) {
            return $this->add_new_course_button();
        }

        if (empty($courses)) {
            return '';
        }

        $coursecount = $DB->count_records_select('course', 'id <> :siteid AND visible = 1', ['siteid' => SITEID]);
        $categorycount = $DB->count_records('course_categories', ['visible' => 1]);
        $statusline = get_string('herostatusline', 'theme_veraxity', (object) [
            'courses' => $coursecount,
            'categories' => $categorycount,
        ]);

        $entries = '';
        foreach ($courses as $course) {
            $entries .= $this->veraxity_course_entry($chelper, $course);
        }

        $viewall = '';
        if ($totalcount > count($courses)) {
            $viewallurl = (new \moodle_url('/course/index.php'))->out();
            $viewall = '
        <div class="veraxity-terminal__line" dir="ltr">
            <span class="veraxity-terminal__prompt">veraxity@academy:~$</span>
            <a class="veraxity-terminal__viewall" href="' . $viewallurl . '">' . get_string('viewallcourses', 'theme_veraxity') . '</a>
        </div>';
        }

        // The chrome and shell lines are literal command syntax — always LTR,
        // the same way a real terminal doesn't mirror for RTL locales.
        // The status comment is translated prose, so it keeps natural
        // paragraph direction instead of being forced.
        return '
<div class="veraxity-terminal">
    <div class="veraxity-terminal__chrome" dir="ltr">
        <span class="veraxity-terminal__dot veraxity-terminal__dot--1"></span>
        <span class="veraxity-terminal__dot veraxity-terminal__dot--2"></span>
        <span class="veraxity-terminal__dot veraxity-terminal__dot--3"></span>
        <span class="veraxity-terminal__title">veraxity@academy &mdash; courses</span>
    </div>
    <div class="veraxity-terminal__body">
        <h2 class="visually-hidden">' . get_string('featuredheading', 'theme_veraxity') . '</h2>
        <div class="veraxity-terminal__line" dir="ltr">
            <span class="veraxity-terminal__prompt">veraxity@academy:~$</span> ls courses/
        </div>
        <div class="veraxity-terminal__comment"># ' . $statusline . '</div>
        <div class="veraxity-terminal__entries">' . $entries . '</div>' . $viewall . '
        <div class="veraxity-terminal__line" dir="ltr">
            <span class="veraxity-terminal__prompt">veraxity@academy:~$</span>
            <span class="veraxity-cursor"></span>
        </div>
    </div>
</div>';
    }

    protected function veraxity_course_entry(\coursecat_helper $chelper, $course): string {
        if ($course instanceof \stdClass) {
            $course = new \core_course_list_element($course);
        }

        $url = (new \moodle_url('/course/view.php', ['id' => $course->id]))->out();
        $name = format_string($course->fullname, true, ['context' => \context_course::instance($course->id)]);
        $folio = str_pad((string) $course->id, 3, '0', STR_PAD_LEFT);

        $summary = '';
        if ($course->has_summary()) {
            $formatted = $chelper->get_course_formatted_summary($course, ['noclean' => false, 'para' => false]);
            $summary = shorten_text(trim(strip_tags($formatted)), 140);
        }

        return '
<a class="veraxity-terminal__entry" href="' . $url . '">
    <span class="veraxity-terminal__entry-path" dir="ltr">' . $folio . '/</span>
    <span class="veraxity-terminal__entry-body">
        <span class="veraxity-terminal__entry-title">' . $name . '</span>
        <span class="veraxity-terminal__entry-summary">' . $summary . '</span>
    </span>
</a>';
    }
}
