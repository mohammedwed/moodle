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

        // Anonymous visitors hit a login wall on course/view.php (core's
        // require_login() there has no "just show me what this course is"
        // exception) — send them to course/info.php instead, the one course
        // page core lets a never-logged-in user actually view. Logged-in
        // users go straight to the real course view, same as before.
        $infopage = !isloggedin() ? '/course/info.php' : '/course/view.php';
        $url = (new \moodle_url($infopage, ['id' => $course->id]))->out();
        $name = format_string($course->fullname, true, ['context' => \context_course::instance($course->id)]);

        $summary = '';
        if ($course->has_summary()) {
            $formatted = $chelper->get_course_formatted_summary($course, ['noclean' => false, 'para' => false]);
            $summary = shorten_text(trim(strip_tags($formatted)), 140);
        }
        if ($summary === '') {
            $summary = get_string('programsnosummary', 'theme_veraxity');
        }

        $imageurl = $this->veraxity_course_image_url($course);
        $visual = $imageurl !== null
            ? '<div class="veraxity-lp-program__image"><img src="' . $imageurl . '" alt=""></div>'
            : '<div class="veraxity-lp-program__icon">' . $icon . '</div>';

        return '
            <div class="veraxity-lp-program veraxity-lp-fade-up">' .
                $visual . '
                <h3 class="veraxity-lp-program__title">' . $name . '</h3>
                <p class="veraxity-lp-program__desc">' . $summary . '</p>
                <a href="' . $url . '" class="veraxity-lp-program__link">' .
                    get_string('programlink', 'theme_veraxity') . $arrow . '</a>
            </div>';
    }

    /**
     * URL of the course's "Course summary files" image (Course Settings >
     * Course image), the same field that already powers the catalog cards
     * and the enrolment overview page — no plugin needed, just reused here.
     * Returns null when the course has no image, so callers fall back to
     * the generic icon.
     */
    protected function veraxity_course_image_url(\core_course_list_element $course): ?string {
        global $CFG;

        foreach ($course->get_course_overviewfiles() as $file) {
            if ($file->is_valid_image()) {
                return \moodle_url::make_file_url(
                    "$CFG->wwwroot/pluginfile.php",
                    '/' . $file->get_contextid() . '/' . $file->get_component() . '/' .
                        $file->get_filearea() . $file->get_filepath() . $file->get_filename(),
                    false
                )->out();
            }
        }

        return null;
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

    /**
     * Replaces core's default course_info_box() (used on enrol/index.php
     * and course/info.php for visitors who aren't enrolled yet) with the
     * course-overview-component design: cover image, an instructor/
     * duration/format/language meta row, learning objectives, and the
     * course structure list — all in one card with an enrol CTA footer.
     */
    public function course_info_box(\stdClass $course) {
        $listelement = ($course instanceof \core_course_list_element)
            ? $course
            : new \core_course_list_element($course);

        return $this->veraxity_course_overview_card($listelement);
    }

    protected function veraxity_course_overview_card(\core_course_list_element $course): string {
        $imageurl = $this->veraxity_course_image_url($course);
        $media = $imageurl !== null
            ? '<div class="cov-media"><img src="' . $imageurl . '" alt=""></div>'
            : '';

        $name = format_string($course->fullname, true, ['context' => \context_course::instance($course->id)]);

        $subtitle = '';
        if ($course->has_summary()) {
            $chelper = new \coursecat_helper();
            $formatted = $chelper->get_course_formatted_summary($course, ['noclean' => false, 'para' => false]);
            $subtitle = shorten_text(trim(strip_tags($formatted)), 200);
        }

        $card = '
<section class="cov-card">' .
    $media . '
    <div class="cov-info">
        <h2 class="cov-title">' . $name . '</h2>' .
        ($subtitle !== '' ? '<p class="cov-subtitle">' . $subtitle . '</p>' : '') .
        $this->veraxity_course_meta($course) . '
    </div>
    <div class="cov-footer">
        <a href="' . theme_veraxity_get_enroll_url() . '" class="cov-btn cov-btn-gold">' .
            get_string('ctaenroll', 'theme_veraxity') . '</a>
    </div>
</section>';

        $aside = $this->veraxity_course_aside($course);
        if ($aside === '') {
            // No objectives or structure to show — the card centers itself.
            return $card;
        }

        return '<div class="cov-layout">' . $card . $aside . '</div>';
    }

    protected function veraxity_course_meta(\core_course_list_element $course): string {
        $items = '';

        $contacts = $course->get_course_contacts();
        if (!empty($contacts)) {
            $names = array_map(fn ($contact) => $contact['username'], $contacts);
            $items .= $this->veraxity_meta_item(
                '<circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/>',
                get_string('metainstructor', 'theme_veraxity'),
                implode(', ', $names)
            );
        }

        $duration = $this->veraxity_custom_field_value($course, 'duration');
        if ($duration !== '') {
            $items .= $this->veraxity_meta_item(
                '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/>',
                get_string('metaduration', 'theme_veraxity'),
                $duration
            );
        }

        $format = $this->veraxity_custom_field_value($course, 'format');
        if ($format !== '') {
            $items .= $this->veraxity_meta_item(
                '<path d="M4 19V6a2 2 0 0 1 2-2h13v15"/><path d="M4 19a2 2 0 0 0 2 2h13"/><path d="M9 7h7M9 11h7"/>',
                get_string('metaformat', 'theme_veraxity'),
                $format
            );
        }

        $language = $this->veraxity_custom_field_value($course, 'courselanguage');
        if ($language !== '') {
            $items .= $this->veraxity_meta_item(
                '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a13 13 0 0 1 0 18 13 13 0 0 1 0-18Z"/>',
                get_string('metalanguage', 'theme_veraxity'),
                $language
            );
        }

        if ($items === '') {
            return '';
        }

        return '<div class="cov-meta">' . $items . '</div>';
    }

    protected function veraxity_meta_item(string $iconpath, string $label, string $value): string {
        return '
        <div class="cov-meta-item">
            <div class="cov-meta-icon">' . $this->veraxity_icon($iconpath, '1.8') . '</div>
            <div class="cov-meta-label">' . $label . '</div>
            <div class="cov-meta-value">' . s($value) . '</div>
        </div>';
    }

    /**
     * Looks up a single custom course field's display value by shortname
     * (e.g. "duration", "format", "courselanguage", "objectives") rather
     * than core's generic field-by-field renderer, since each one needs to
     * land in a specific spot in this design (the meta row or the body).
     */
    protected function veraxity_custom_field_value(\core_course_list_element $course, string $shortname): string {
        foreach ($course->get_custom_fields() as $data) {
            if ($data->get_field()->get('shortname') === $shortname) {
                $value = $data->export_value();
                return $value !== null ? trim((string) $value) : '';
            }
        }
        return '';
    }

    /**
     * Learning objectives and course structure as two standalone boxes
     * beside the main card, rather than stacked inside its body — only
     * includes whichever of the two actually has content.
     */
    protected function veraxity_course_aside(\core_course_list_element $course): string {
        $boxes = '';

        $objectives = $this->veraxity_custom_field_value($course, 'objectives');
        if ($objectives !== '') {
            // Already formatted HTML via the textarea field's export_value().
            $boxes .= '
    <div class="cov-aside-box">
        <div class="cov-section-label">' . get_string('covobjectiveslabel', 'theme_veraxity') . '</div>
        <p class="cov-objectives">' . $objectives . '</p>
    </div>';
        }

        $structure = $this->veraxity_course_structure($course);
        if ($structure !== '') {
            $boxes .= '
    <div class="cov-aside-box">' . $structure . '
    </div>';
        }

        if ($boxes === '') {
            return '';
        }

        return '
    <div class="cov-aside">' . $boxes . '
    </div>';
    }

    /**
     * Maximum syllabus entries to list — a defensive cap, not expected to
     * matter for normally-authored courses.
     */
    private const STRUCTURE_MAX_ITEMS = 12;

    protected function veraxity_course_structure(\core_course_list_element $course): string {
        global $DB;

        $sections = $DB->get_records('course_sections', ['course' => $course->id, 'visible' => 1], 'section ASC');

        // Only sections the course creator bothered to name represent real
        // advertised structure — unnamed filler sections (e.g. bulk-created
        // demo content) would otherwise show as a long run of "Topic N".
        $named = array_values(array_filter($sections, fn ($section) => !empty($section->name)));

        if (empty($named)) {
            return '';
        }

        $totalcount = count($named);
        $shown = array_slice($named, 0, self::STRUCTURE_MAX_ITEMS);

        $items = '';
        $i = 0;
        foreach ($shown as $section) {
            $i++;
            $name = format_string(get_section_name($course, $section));

            $summary = '';
            if (!empty(trim(strip_tags($section->summary)))) {
                $formatted = format_text($section->summary, $section->summaryformat, ['context' => \context_course::instance($course->id)]);
                $summary = shorten_text(trim(strip_tags($formatted)), 100);
            }

            $items .= '
            <li class="cov-structure-item">
                <span class="cov-structure-num">' . str_pad((string) $i, 2, '0', STR_PAD_LEFT) . '</span>
                <span class="cov-structure-title">' . $name . '</span>' .
                ($summary !== '' ? '<span class="cov-structure-desc">— ' . $summary . '</span>' : '') . '
            </li>';
        }

        $more = '';
        if ($totalcount > count($shown)) {
            $more = '<div class="cov-structure-more">' .
                get_string('coursestructuremore', 'theme_veraxity', $totalcount - count($shown)) . '</div>';
        }

        return '
        <div class="cov-section-label">' . get_string('coursestructure', 'theme_veraxity') . '</div>
        <ol class="cov-structure">' . $items . '
        </ol>' . $more;
    }
}
