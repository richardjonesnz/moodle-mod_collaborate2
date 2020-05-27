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
 * This is a one page wonder name changing page
 * Created by Justin Hunt for an earlier version of this course
 * Modified by Richard Jones
 *
 * @package    mod_collaborate
 * @copyright  2015 Flash Gordon http://www.flashgordon.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once($CFG->libdir . '/formslib.php');


/**
 * Define a form that acts on just one field, "name", in an existing table "mdl_collaborate"
 */
class collaborate_namechanger_form extends moodleform {

    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;

        // Adding the standard "name" field
        $mform->addElement('text', 'name', get_string('collaboratename', 'mod_collaborate'), array('size'=>'64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEAN);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'collaboratename', 'mod_collaborate');

        $mform->addElement('hidden','courseid');
        $mform->setType('courseid',PARAM_INT);
        $mform->addElement('hidden','id');
        $mform->setType('id',PARAM_INT);
        $this->add_action_buttons();

    }
}

//fetch URL parameters
$courseid = required_param('courseid', PARAM_INT);   // course
$action = optional_param('action','list',PARAM_TEXT);
$actionitem = optional_param('actionitem',0,PARAM_INT);

//Set course related variables
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
require_course_login($course);
$coursecontext = context_course::instance($course->id);

//set up the page
$PAGE->set_url('/mod/collaborate/namechanger.php', array('courseid' => $courseid));
$PAGE->set_title(format_string($course->fullname));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($coursecontext);
$PAGE->set_pagelayout('course');

// Get the name_changer form.
$mform = new collaborate_namechanger_form();

// Cancelled, redirect.
if ($mform->is_cancelled()) {
    redirect($PAGE->url, get_string('cancelled'),2);
    exit;
}

// If we have data save it and return.
if ($data = $mform->get_data()) {
        $DB->update_record('collaborate',$data);
        redirect($PAGE->url, get_string('updated', 'core', $data->name), 2);
}

$renderer = $PAGE->get_renderer('mod_collaborate');

// If the action is "edit" show the edit form.
if($action =="edit"){
    // Create some data for our form.
    $data = new stdClass();
    $data->courseid = $courseid;
    $collaborate = $DB->get_record('collaborate', ['id'=>$actionitem]);
    if(!$collaborate) {
        redirect($PAGE->url,'nodata',2);
    }
    $data->id = $collaborate->id;
    $data->name = $collaborate->name;

    // Set data to form.
    $mform->set_data($data);

    // Output page and form.
    $renderer->render_namechange_form($mform);
}

// Check if this course has collaborate modules present.
if (!$collaborates = get_all_instances_in_course('collaborate', $course)) {
    notice(get_string('nocollaborates', 'collaborate'), new moodle_url('/course/view.php', array('id' => $course->id)));
}

// Call renderer to output list of collaborate activities.
$renderer->show_namechanger_page($collaborates, $course);