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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>;.
/**

/**
 * Class for handling student submissions.
 *
 * @package   mod_collaborate
 * @copyright 2018 Richard Jones https://richardnz.net
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_collaborate\local;
use \mod_collaborate\local\collaborate_editor;
use \mod_collaborate\local\debugging;
use \mod_collaborate\local\submission_form;
defined('MOODLE_INTERNAL') || die();
class submissions {
    /**
     * Add a submission record to the DB.
     *
     * @param object $data - the data to add
     * @param object $context - our module context
     * @param int $cid our collaborate instance id.
     * @return int $id - the id of the inserted record
     */
    public static function save_submission($data, $context, $cid, $page) {
        global $DB, $USER;
        $exists = self::get_submission($cid, $USER->id, $page);
        if($exists) {
            $DB->delete_records('collaborate_submissions',
                    ['collaborateid' => $cid, 'userid' => $USER->id, 'page' => $page]);
        }
        $options = collaborate_editor::get_editor_options($context);

        // Insert a dummy record and get the id.
        $data->timecreated = time();
        $data->timemodified = time();
        $data->collaborateid = $cid;
        $data->userid = $USER->id;
        $data->page = $page;
        $data->submission = ' ';
        $data->submissionformat = FORMAT_HTML;
        $dataid = $DB->insert_record('collaborate_submissions', $data);
        $data->id = $dataid;

        // Massage the data into a form for saving.
        $data = file_postupdate_standard_editor(
                $data,
                'submission',
                $options,
                $context,
                'mod_collaborate',
                'submission',
                $data->id);

        // Update the record with full editor data.
        $DB->update_record('collaborate_submissions', $data);
        return $data->id;
    }
    /**
     * Get a user submission given its id.
     * @param object $collaborate the Collaborate instance
     * @param int $sid the submission id
     * @return object the required record
     */
    public static function get_submission_to_grade($collaborate, $sid) {
        global $DB;
        $record = $DB->get_record('collaborate_submissions', ['id' => $sid], '*', MUST_EXIST);
        $data = new \stdClass();
        $data->title = $collaborate->title;
        $data->submission = $record->submission;
        $user = $DB->get_record('user', ['id' => $record->userid], '*', MUST_EXIST);
        $data->firstname = $user->firstname;
        $data->lastname = $user->lastname;
        $data->grade = $record->grade;
        return $data;
    }
    /**
     * retrieve a submission record from the DB.
     *
     * @param int $cid our collaborate instance id.
     * @param int $userid the user making the submission.
     * @param int $page the page identifier (a or b).
     * @return object representing the record or null if it doesn't exist.
     */
    public static function get_submission($cid, $userid, $page) {
        global $DB;
        return $DB->get_record('collaborate_submissions', ['collaborateid' => $cid, 'userid' => $userid, 'page' => $page], '*', IGNORE_MISSING);
    }

    /**
     * Set the headers to match the sql query and required report fields.
     *
     * @return string array of report column headers.
     */
    public static function get_submission_record_headers() {
        return [
            get_string('id', 'mod_collaborate'),
            get_string('title', 'mod_collaborate'),
            get_string('submission','mod_collaborate'),
            get_string('firstname', 'mod_collaborate'),
            get_string('lastname', 'mod_collaborate'),
            get_string('grade',  'mod_collaborate')];
    }

    /**
     * Get the records from the submissions table for this Collaborate instance.
     *
     * @param int $cid our collaborate instance id.
     * @return An array of records.
     */
    public static function get_submission_records($cid) {
        global $DB;

        // Get the list of records as an array of objects.
        $records = $DB->get_records('collaborate_submissions', ['collaborateid' => $cid]);
        // We will need this to get the instance title.
        $collaborate = $DB->get_record('collaborate', ['id' => $cid], '*', MUST_EXIST);

        $submissions = array();

        // Process the records.
        // Note that we don't try to process any media in the submission body.
        foreach ($records as $record) {
            $data = array();
            $data['id'] = $record->id;
            $data['title'] = $collaborate->title;
            $s = \format_string($record->submission);
            $s = \strip_tags($s);
            $data['submission'] = $s;
            $user = $DB->get_record('user', ['id' => $record->userid], '*', MUST_EXIST);
            $data['firstname'] = $user->firstname;
            $data['lastname'] = $user->lastname;
            $data['grade'] = ($record->grade == 0) ? '-' : $record->grade;

            // Add a URL to the grading page.
            $g = new \moodle_url('/mod/collaborate/grading.php', ['cid' => $collaborate->id,
                    'sid' => $record->id]);
            $data['gradelink'] = $g->out(false);
            $data['gradetext'] = get_string('grade', 'mod_collaborate');

            $submissions[] = $data;
        }

        return $submissions;
    }
    /**
     * Given a submission id, update the grade field
     *
     * @param int $sid the submission id.
     * @param int $grade the grade.
     * @return An array of records.
     */
    public static function update_grade($sid, $grade) {
        global $DB;
        $DB->set_field('collaborate_submissions', 'grade', $grade, ['id' => $sid]);
    }
}