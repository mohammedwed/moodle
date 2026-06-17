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
 * Builds the "course overview card" markup (mockups/course-overview-
 * component.html, -ar.html): cover image, an instructor/duration/format/
 * language meta list, learning objectives and course structure boxes, and
 * an enrol CTA. A plain class with no core renderer/override involved, so
 * it can be called equally from theme_veraxity\output\core\course_renderer
 * ::course_info_box() (enrol/index.php, course/info.php — the logged-in
 * flow) and from theme/veraxity/coursedetails.php (the standalone
 * never-logged-in visitor page) without either depending on the other.
 *
 * @package    theme_veraxity
 */

namespace theme_veraxity\output;

defined('MOODLE_INTERNAL') || die();

// coursecat_helper isn't autoloaded — it's an old-style class defined
// directly in course/renderer.php alongside core_course_renderer. Normally
// whatever first instantiates a course renderer pulls this in as a side
// effect; this class is also used standalone (theme/veraxity/
// coursedetails.php never touches the renderer factory), so it can't rely
// on that happening first.
require_once($CFG->dirroot . '/course/renderer.php');

class course_overview_card {

    public function __construct(private \core_course_list_element $course) {
    }

    public function render(): string {
        $imageurl = self::image_url($this->course);
        $media = $imageurl !== null
            ? '<div class="cov-media"><img src="' . $imageurl . '" alt=""></div>'
            : '';

        $name = format_string($this->course->fullname, true, ['context' => \context_course::instance($this->course->id)]);

        $subtitle = '';
        if ($this->course->has_summary()) {
            $chelper = new \coursecat_helper();
            $formatted = $chelper->get_course_formatted_summary($this->course, ['noclean' => false, 'para' => false]);
            $subtitle = shorten_text(trim(strip_tags($formatted)), 200);
        }

        $card = '
<section class="cov-card">' .
    $media . '
    <div class="cov-info">
        <h2 class="cov-title">' . $name . '</h2>' .
        ($subtitle !== '' ? '<p class="cov-subtitle">' . $subtitle . '</p>' : '') .
        $this->meta() . '
    </div>
    <div class="cov-cta">
        <a href="' . theme_veraxity_get_enroll_url() . '" class="cov-btn cov-btn-gold">' .
            get_string('ctaenroll', 'theme_veraxity') . '</a>
    </div>
</section>';

        $side = $this->side();
        if ($side === '') {
            // No objectives or structure to show — the lone card centers itself.
            return '<div class="cov-embed">' . $card . '</div>';
        }

        return '<div class="cov-embed"><div class="cov-row">' . $card . $side . '</div></div>';
    }

    /**
     * URL of the course's "Course summary files" image (Course Settings >
     * Course image) — shared by the front-page program cards
     * (theme_veraxity\output\core\course_renderer::veraxity_program_card())
     * and this card. Returns null when the course has no image, so callers
     * fall back to a generic icon/no media block.
     */
    public static function image_url(\core_course_list_element $course): ?string {
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

    protected function meta(): string {
        $items = '';

        $contacts = $this->course->get_course_contacts();
        if (!empty($contacts)) {
            $names = array_map(fn ($contact) => $contact['username'], $contacts);
            $items .= $this->meta_item(
                '<circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/>',
                get_string('metainstructor', 'theme_veraxity'),
                implode(', ', $names)
            );
        }

        $duration = $this->custom_field_value('duration');
        if ($duration !== '') {
            $items .= $this->meta_item(
                '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/>',
                get_string('metaduration', 'theme_veraxity'),
                $duration
            );
        }

        $format = $this->custom_field_value('format');
        if ($format !== '') {
            $items .= $this->meta_item(
                '<path d="M4 19V6a2 2 0 0 1 2-2h13v15"/><path d="M4 19a2 2 0 0 0 2 2h13"/><path d="M9 7h7M9 11h7"/>',
                get_string('metaformat', 'theme_veraxity'),
                $format
            );
        }

        $language = $this->custom_field_value('courselanguage');
        if ($language !== '') {
            $items .= $this->meta_item(
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

    protected function meta_item(string $iconpath, string $label, string $value): string {
        return '
        <div class="cov-meta-row">
            <div class="cov-meta-icon">' . $this->icon($iconpath) . '</div>
            <div>
                <div class="cov-meta-label">' . $label . '</div>
                <div class="cov-meta-value">' . s($value) . '</div>
            </div>
        </div>';
    }

    /**
     * Looks up a single custom course field's display value by shortname
     * (e.g. "duration", "format", "courselanguage", "objectives") rather
     * than core's generic field-by-field renderer, since each one needs to
     * land in a specific spot in this design (the meta row or the body).
     */
    protected function custom_field_value(string $shortname): string {
        foreach ($this->course->get_custom_fields() as $data) {
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
    protected function side(): string {
        $boxes = '';

        $objectives = $this->custom_field_value('objectives');
        if ($objectives !== '') {
            // Already formatted HTML via the textarea field's export_value().
            $boxes .= '
    <section class="cov-objectives-box">
        <div class="cov-section-label">' . get_string('covobjectiveslabel', 'theme_veraxity') . '</div>
        <p class="cov-objectives">' . $objectives . '</p>
    </section>';
        }

        $structure = $this->structure();
        if ($structure !== '') {
            $boxes .= '
    <section class="cov-structure-box">' . $structure . '
    </section>';
        }

        if ($boxes === '') {
            return '';
        }

        return '
    <div class="cov-side">' . $boxes . '
    </div>';
    }

    /**
     * Maximum syllabus entries to list — a defensive cap, not expected to
     * matter for normally-authored courses.
     */
    private const STRUCTURE_MAX_ITEMS = 12;

    protected function structure(): string {
        global $DB;

        $sections = $DB->get_records('course_sections', ['course' => $this->course->id, 'visible' => 1], 'section ASC');

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
            $name = format_string(get_section_name($this->course, $section));

            $summary = '';
            if (!empty(trim(strip_tags($section->summary)))) {
                $formatted = format_text(
                    $section->summary,
                    $section->summaryformat,
                    ['context' => \context_course::instance($this->course->id)]
                );
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

    /**
     * Decorative inline SVG icon for the meta rows.
     */
    protected function icon(string $innermarkup): string {
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" ' .
            'aria-hidden="true">' . $innermarkup . '</svg>';
    }
}
