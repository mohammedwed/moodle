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
 * Veraxity theme functions.
 *
 * @package    theme_veraxity
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Returns the main SCSS content — reuses Boost's own preset so we inherit
 * the full Bootstrap + Moodle component styles, then layer brand overrides
 * on top via the pre/extra SCSS callbacks below.
 *
 * @param theme_config $theme
 * @return string
 */
function theme_veraxity_get_main_scss_content($theme) {
    global $CFG;
    return file_get_contents($CFG->dirroot . '/theme/boost/scss/preset/default.scss');
}

/**
 * SCSS prepended before Bootstrap/Boost compiles — this is where Bootstrap
 * variables must be overridden for them to take effect.
 *
 * @param theme_config $theme
 * @return string
 */
function theme_veraxity_get_pre_scss($theme) {
    global $CFG;
    return file_get_contents($CFG->dirroot . '/theme/veraxity/scss/brand-variables.scss');
}

/**
 * SCSS appended after everything else — used for the front-page hero and
 * any rules that need to win over the default Boost/Bootstrap cascade.
 *
 * @param theme_config $theme
 * @return string
 */
function theme_veraxity_get_extra_scss($theme) {
    global $CFG;
    return file_get_contents($CFG->dirroot . '/theme/veraxity/scss/brand-custom.scss');
}
