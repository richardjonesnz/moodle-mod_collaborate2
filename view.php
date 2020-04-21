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
 * Prints a particular instance of collaborate
 *
 * @package    mod_collaborate
 * @copyright  2019 Richard Jones richardnz@outlook.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @see https://github.com/moodlehq/moodle-mod_collaborate
 * @see https://github.com/justinhunt/moodle-mod_collaborate */


require_once('../../config.php');
require_once(dirname(__FILE__).'/lib.php');

// We need the course module id (id) or
// the collaborate instance id (n).
$id = optional_param('id', 0, PARAM_INT);
$n  = optional_param('n', 0, PARAM_INT);

if ($id) {
    $cm = get_coursemodule_from_id('collaborate', $id, 0, false,
            MUST_EXIST);
    $course = $DB->get_record('course',
            array('id' => $cm->course), '*', MUST_EXIST);
    $collaborate = $DB->get_record('collaborate',
            array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($n) {
    $collaborate = $DB->get_record('collaborate', array('id' => $n), '*',
            MUST_EXIST);
    $course = $DB->get_record('course',
            array('id' => $collaborate->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('collaborate', $collaborate->id,
            $course->id, false, MUST_EXIST);
}

// Print the page header.
$PAGE->set_url('/mod/collaborate/view.php', array('id' => $cm->id));

require_login($course, true, $cm);

$PAGE->set_title(format_string($collaborate->name));
$PAGE->set_heading(format_string($course->fullname));

// The renderer performs output to the page.
$renderer = $PAGE->get_renderer('mod_collaborate');

// Check for intro page content.
if (!$collaborate->intro) {
    $collaborate->intro = '';
}
// Call the renderer method to display the collaborate intro content.
$renderer->render_view_page_content($collaborate, $cm);
