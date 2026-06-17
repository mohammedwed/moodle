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
 * Course renderer override — replaces the default front page body with a
 * marketing-style landing page (hero, programs, platform features,
 * how-it-works, CTA), matching the academy-portal-concept mockup design.
 *
 * @package    theme_veraxity
 */

namespace theme_veraxity\output\core;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/renderer.php');

class course_renderer extends \core_course_renderer {

    public function frontpage() {
        return $this->veraxity_hero()
            . $this->veraxity_strip()
            . $this->veraxity_programs()
            . $this->veraxity_quote()
            . $this->veraxity_why()
            . $this->veraxity_how()
            . $this->veraxity_cta();
    }

    protected function veraxity_hero(): string {
        $stats = '';
        for ($i = 1; $i <= 4; $i++) {
            $stats .= '
        <div class="veraxity-lp-stat">
            <div class="veraxity-lp-stat__label">' . get_string("herostat{$i}label", 'theme_veraxity') . '</div>
            <div class="veraxity-lp-stat__value">' . get_string("herostat{$i}value", 'theme_veraxity') . '</div>
        </div>';
        }

        return '
<header class="veraxity-lp-hero" id="top">
    <div class="veraxity-lp-hero__photo"></div>
    <div class="veraxity-lp-hero__inner">
        <div class="veraxity-lp-hero__content">
            <div class="veraxity-lp-hero__label">' . get_string('herolabel', 'theme_veraxity') . '</div>
            <h1 class="veraxity-lp-hero__title">' . get_string('herotitle', 'theme_veraxity') . '</h1>
            <p class="veraxity-lp-hero__desc">' . get_string('herodesc', 'theme_veraxity') . '</p>
            <div class="veraxity-lp-hero__cta-row">
                <a href="#programs" class="veraxity-lp-btn veraxity-lp-btn--gold">' .
                    get_string('herobrowseprograms', 'theme_veraxity') . '</a>
                <a href="#how-it-works" class="veraxity-lp-btn veraxity-lp-btn--outline-light">' .
                    get_string('herohowitworks', 'theme_veraxity') . '</a>
            </div>
            <div class="veraxity-lp-hero__stats">' . $stats . '
            </div>
        </div>
    </div>
</header>';
    }

    protected function veraxity_strip(): string {
        $icons = [
            '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a13 13 0 0 1 0 18 13 13 0 0 1 0-18Z"/>',
            '<path d="M4 19V6a2 2 0 0 1 2-2h13v15"/><path d="M4 19a2 2 0 0 0 2 2h13"/><path d="M9 7h7M9 11h7"/>',
            '<path d="M3 17l5-5 4 4 8-8"/><path d="M14 8h6v6"/>',
            '<circle cx="12" cy="8" r="5"/><path d="M8 13l-2 8 6-3 6 3-2-8"/>',
            '<rect x="6" y="2" width="12" height="20" rx="2"/><path d="M10 19h4"/>',
        ];

        $pills = '';
        foreach ($icons as $i => $path) {
            $n = $i + 1;
            $pills .= '
        <span class="veraxity-lp-strip__pill">' . $this->veraxity_icon($path) .
                get_string("strip{$n}", 'theme_veraxity') . '</span>';
        }

        return '
<div class="veraxity-lp-strip">
    <div class="veraxity-lp-strip__inner">' . $pills . '
    </div>
</div>';
    }

    /**
     * Live catalog: every visible course (the site course itself is never
     * returned by core_course_category::top()) becomes a program card,
     * newest-id first up to $CFG->frontpagecourselimit. No curated content
     * — title/description come straight from the course.
     */
    protected function veraxity_programs(): string {
        global $CFG;

        $chelper = new \coursecat_helper();
        $chelper->set_show_courses(self::COURSECAT_SHOW_COURSES_EXPANDED)
            ->set_courses_display_options([
                'recursive' => true,
                'limit' => $CFG->frontpagecourselimit,
                'sort' => ['id' => 1],
            ]);
        $options = $chelper->get_courses_display_options();
        $courses = \core_course_category::top()->get_courses($options);
        $totalcount = \core_course_category::top()->get_courses_count($options);

        $arrowpath = right_to_left() ? 'M19 12H5M11 6l-6 6 6 6' : 'M5 12h14M13 6l6 6-6 6';
        $arrow = $this->veraxity_icon($arrowpath, '2');
        $courseicon = $this->veraxity_icon(
            '<path d="M12 14l9-5-9-5-9 5 9 5Z"/><path d="M12 14v7"/><path d="M3 9v6c0 1.5 4 3 9 3s9-1.5 9-3V9"/>'
        );

        if (empty($courses)) {
            $cards = '
            <div class="veraxity-lp-program veraxity-lp-program--placeholder veraxity-lp-fade-up">
                <h3 class="veraxity-lp-program__title">' . get_string('programsemptytitle', 'theme_veraxity') . '</h3>
                <p class="veraxity-lp-program__desc">' . get_string('programsemptydesc', 'theme_veraxity') . '</p>
            </div>';
        } else {
            $cards = '';
            foreach ($courses as $course) {
                $cards .= $this->veraxity_program_card($chelper, $course, $courseicon, $arrow);
            }
            if ($totalcount > count($courses)) {
                $viewallurl = (new \moodle_url('/course/index.php'))->out();
                $cards .= '
            <a href="' . $viewallurl . '" class="veraxity-lp-program veraxity-lp-program--placeholder veraxity-lp-fade-up">
                <h3 class="veraxity-lp-program__title">' . get_string('programviewall', 'theme_veraxity') . '</h3>
            </a>';
            }
        }

        return '
<section class="veraxity-lp-section" id="programs">
    <div class="veraxity-lp-section__inner">
        <div class="veraxity-lp-section__head veraxity-lp-fade-up">
            <div class="veraxity-lp-section__eyebrow">' . get_string('programseyebrow', 'theme_veraxity') . '</div>
            <h2 class="veraxity-lp-section__title">' . get_string('programstitle', 'theme_veraxity') . '</h2>
            <p class="veraxity-lp-section__sub">' . get_string('programssub', 'theme_veraxity') . '</p>
        </div>
        <div class="veraxity-lp-programs">' . $cards . '
        </div>
    </div>
</section>';
    }

    protected function veraxity_program_card(\coursecat_helper $chelper, $course, string $icon, string $arrow): string {
        if ($course instanceof \stdClass) {
            $course = new \core_course_list_element($course);
        }

        $url = (new \moodle_url('/course/view.php', ['id' => $course->id]))->out();
        $name = format_string($course->fullname, true, ['context' => \context_course::instance($course->id)]);

        $summary = '';
        if ($course->has_summary()) {
            $formatted = $chelper->get_course_formatted_summary($course, ['noclean' => false, 'para' => false]);
            $summary = shorten_text(trim(strip_tags($formatted)), 140);
        }
        if ($summary === '') {
            $summary = get_string('programsnosummary', 'theme_veraxity');
        }

        return '
            <div class="veraxity-lp-program veraxity-lp-fade-up">
                <div class="veraxity-lp-program__icon">' . $icon . '</div>
                <h3 class="veraxity-lp-program__title">' . $name . '</h3>
                <p class="veraxity-lp-program__desc">' . $summary . '</p>
                <a href="' . $url . '" class="veraxity-lp-program__link">' .
                    get_string('programlink', 'theme_veraxity') . $arrow . '</a>
            </div>';
    }

    protected function veraxity_quote(): string {
        return '
<div class="veraxity-lp-quote">
    <p class="veraxity-lp-quote__text veraxity-lp-fade-up">' . get_string('quotetext', 'theme_veraxity') . '</p>
    <div class="veraxity-lp-quote__attr">' . get_string('quoteattr', 'theme_veraxity') . '</div>
</div>';
    }

    protected function veraxity_why(): string {
        $icons = [
            '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a13 13 0 0 1 0 18 13 13 0 0 1 0-18Z"/>',
            '<circle cx="12" cy="8" r="4"/><path d="M4 21v-2a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v2"/>',
            '<path d="M3 17l5-5 4 4 8-8"/><path d="M14 8h6v6"/>',
            '<path d="M12 2 4 6v6c0 5 3.5 8 8 10 4.5-2 8-5 8-10V6l-8-4Z"/><path d="M9 12l2 2 4-4"/>',
            '<rect x="6" y="2" width="12" height="20" rx="2"/><path d="M10 19h4"/>',
            '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>' .
                '<rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
        ];

        $items = '';
        foreach ($icons as $i => $path) {
            $n = $i + 1;
            $items .= '
        <div class="veraxity-lp-feature veraxity-lp-fade-up">
            <div class="veraxity-lp-feature__icon">' . $this->veraxity_icon($path) . '</div>
            <div class="veraxity-lp-feature__title">' . get_string("why{$n}title", 'theme_veraxity') . '</div>
            <div class="veraxity-lp-feature__desc">' . get_string("why{$n}desc", 'theme_veraxity') . '</div>
        </div>';
        }

        return '
<section class="veraxity-lp-section veraxity-lp-section--dark" id="why">
    <div class="veraxity-lp-section__inner">
        <div class="veraxity-lp-section__head veraxity-lp-fade-up">
            <div class="veraxity-lp-section__eyebrow">' . get_string('whyeyebrow', 'theme_veraxity') . '</div>
            <h2 class="veraxity-lp-section__title">' . get_string('whytitle', 'theme_veraxity') . '</h2>
            <p class="veraxity-lp-section__sub">' . get_string('whysub', 'theme_veraxity') . '</p>
        </div>
        <div class="veraxity-lp-feature-grid">' . $items . '
        </div>
    </div>
</section>';
    }

    protected function veraxity_how(): string {
        $steps = '';
        for ($i = 1; $i <= 4; $i++) {
            $steps .= '
        <div class="veraxity-lp-step veraxity-lp-fade-up">
            <div class="veraxity-lp-step__num">' . str_pad((string) $i, 2, '0', STR_PAD_LEFT) . '</div>
            <div class="veraxity-lp-step__title">' . get_string("step{$i}title", 'theme_veraxity') . '</div>
            <div class="veraxity-lp-step__desc">' . get_string("step{$i}desc", 'theme_veraxity') . '</div>
        </div>';
        }

        return '
<section class="veraxity-lp-section" id="how-it-works">
    <div class="veraxity-lp-section__inner">
        <div class="veraxity-lp-section__head veraxity-lp-fade-up">
            <div class="veraxity-lp-section__eyebrow">' . get_string('howeyebrow', 'theme_veraxity') . '</div>
            <h2 class="veraxity-lp-section__title">' . get_string('howtitle', 'theme_veraxity') . '</h2>
            <p class="veraxity-lp-section__sub">' . get_string('howsub', 'theme_veraxity') . '</p>
        </div>
        <div class="veraxity-lp-steps">' . $steps . '
        </div>
    </div>
</section>';
    }

    protected function veraxity_cta(): string {
        $enrollurl = theme_veraxity_get_enroll_url();

        return '
<div class="veraxity-lp-cta" id="contact">
    <div class="veraxity-lp-cta__ring"></div>
    <div class="veraxity-lp-cta__inner">
        <h2 class="veraxity-lp-cta__title">' . get_string('ctatitle', 'theme_veraxity') . '</h2>
        <p class="veraxity-lp-cta__desc">' . get_string('ctadesc', 'theme_veraxity') . '</p>
        <a href="' . $enrollurl . '" class="veraxity-lp-btn veraxity-lp-btn--gold">' .
            get_string('ctaenroll', 'theme_veraxity') . '</a>
    </div>
</div>';
    }

    /**
     * Decorative inline SVG icon shared by the strip, program, and feature
     * sections — only the inner path/shape markup differs between callers.
     */
    protected function veraxity_icon(string $innermarkup, string $strokewidth = '1.6'): string {
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="' . $strokewidth . '" ' .
            'aria-hidden="true">' . $innermarkup . '</svg>';
    }
}
