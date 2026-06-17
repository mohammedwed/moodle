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
 * Veraxity theme config — a Boost child theme styled after veraxity.dev.
 *
 * @package    theme_veraxity
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/lib.php');

$THEME->name = 'veraxity';
$THEME->parents = ['boost'];
$THEME->sheets = [];
$THEME->editor_sheets = [];
$THEME->usefallback = true;

// Custom marketing-style nav/footer chrome, used on the front page always,
// and on the 'incourse' layout only for never-logged-in visitors (see
// layout/incourse.php) — everywhere else keeps Boost's standard drawers
// layout unchanged.
$THEME->layouts = [
    'frontpage' => [
        'file' => 'frontpage.php',
        'regions' => [],
        'options' => ['nonavbar' => true],
    ],
    'incourse' => [
        'file' => 'incourse.php',
        'regions' => ['side-pre'],
        'defaultregion' => 'side-pre',
    ],
];

$THEME->scss = function($theme) {
    return theme_veraxity_get_main_scss_content($theme);
};
$THEME->extrascsscallback = 'theme_veraxity_get_extra_scss';
$THEME->prescsscallback = 'theme_veraxity_get_pre_scss';

$THEME->enable_dock = false;
$THEME->yuicssmodules = [];
$THEME->rendererfactory = 'theme_overridden_renderer_factory';
$THEME->requiredblocks = '';
$THEME->addblockposition = BLOCK_ADDBLOCK_POSITION_FLATNAV;
$THEME->iconsystem = \core\output\icon_system::FONTAWESOME;
$THEME->haseditswitch = true;
$THEME->usescourseindex = true;
$THEME->activityheaderconfig = [
    'notitle' => true,
];
