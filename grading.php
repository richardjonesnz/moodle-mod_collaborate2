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
 * Prints the submission grading page and form.
 *
 * @package    mod_collaborate
 * @copyright  2019 Richard Jones richardnz@outlook.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @see https://github.com/moodlehq/moodle-mod_newmodule
 * @see https://github.com/justinhunt/moodle-mod_collaborate */

use \core\output\notification;
use \mod_collaborate\local\submissions;

require_once('../../config.php');

// The form class.

class collaborate_grading_form extends moodleform {

    public function definition() {
        global $CFG;
        $mform = $this->_form;

        // grades available.
        $grades = array();
        for ($m = 0; $m <= 100; $m++) {
            $grades[$m] = '' . $m;
        }
        $mform->addElement('select', 'grade',
                get_string('allocate_grade', 'mod_collaborate'),
                $grades);

        $mform->addElement('hidden', 'cid',
                $this->_customdata['cid']);
        $mform->addElement('hidden', 'sid',
                $this->_customdata['sid']);

        $mform->setType('cid', PARAM_INT);
        $mform->setType('sid', PARAM_INT);

        $this->add_action_buttons();
    }
}

// Page starts here.
// We will need the collaborate instance and the submission ID.
$cid = required_param('cid', PARAM_INT);
$sid = required_param('sid', PARAM_INT);

// Get the information required to check the user can access this page.
$collaborate = $DB->get_record('collaborate', ['id' => $cid], '*', MUST_EXIST);
$courseid = $collaborate->course;
$cm = get_coursemodule_from_instance('collaborate', $cid, $courseid, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

// Set the page URL.
$PAGE->set_url('/mod/collaborate/grading.php', ['cid' => $cid, 'sid' => $sid]);

// Check the user is logged on.
require_login($course, true, $cm);

// Set the page information.
$PAGE->set_title(format_string($collaborate->name));
$PAGE->set_heading(format_string($course->fullname));

require_capability('mod/collaborate:gradesubmission', $context);

$reportsurl = new moodle_url('/mod/collaborate/reports.php', ['cid' => $cid]);

// Get the submission information.
$submission = submissions::get_submission_to_grade($collaborate, $sid);
$mform = new collaborate_grading_form(null, ['cid' => $cid,'sid' => $sid]);

if ($mform->is_cancelled()) {
    redirect($reportsurl, get_string('cancelled'), 2, notification::NOTIFY_INFO);
}

if ($data = $mform->get_data()) {
    // Set any existing grade to the form.
    $mform->set_data($data);
    // Update the submission data.
    submissions::update_grade($sid, $data->grade);
    redirect($reportsurl, get_string('grade_saved', 'mod_collaborate'), 2,
            notification::NOTIFY_SUCCESS);
}

// Call the renderer to get the html for the page.
$renderer = $PAGE->get_renderer('mod_collaborate');
echo $OUTPUT->header();
echo $renderer->render_submission_to_grade($submission, $context, $cid, $sid);

// Show the grading form.
$mform->display();
echo $OUTPUT->footer();