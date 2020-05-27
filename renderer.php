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
 * Custom renderer for output of pages
 *
 * @package    mod_collaborate
 * @copyright  2019 Richard Jones richardnz@outlook.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @see https://github.com/moodlehq/moodle-mod_newmodule
 * @see https://github.com/justinhunt/moodle-mod_collaborate
 */
use \mod_collaborate\local\debugging;
defined('MOODLE_INTERNAL') || die();

/**
 * Renderer for collaborate mod.
 */
class mod_collaborate_renderer extends plugin_renderer_base {

    /**
     * Displays the main view page content.
     *
     * @param $collaborate the collaborate instance std Object
     * @param $cm the course module std Object
     * @return none
     */
    public function render_view_page_content($collaborate, $cm, $reportstab) {

        $data = new stdClass();

        $data->heading = $collaborate->title;
        // Moodle handles processing of std intro field.
        $data->body = format_module_intro('collaborate',
                $collaborate, $cm->id);

        // Show reports tab?
        $data->reportstab = $reportstab;

        // Set up the user page URLs.
        $a = new \moodle_url('/mod/collaborate/showpage.php', ['cid' => $collaborate->id,
            'page' => 'a']);
        $b = new \moodle_url('/mod/collaborate/showpage.php', ['cid' => $collaborate->id,
            'page' => 'b']);
        $data->url_a = $a->out(false);
        $data->url_b = $b->out(false);

        // Add links to reports tabs, if enabled.
        if ($reportstab) {
            $r = new \moodle_url('/mod/collaborate/reports.php',
                    ['cid' => $collaborate->id]);
            $v = new \moodle_url('/mod/collaborate/view.php', ['id' => $cm->id]);
            $data->url_reports = $r->out(false);
            $data->url_view = $v->out(false);
        }

        // Display the view page content.
        echo $this->output->header();
        echo $this->render_from_template('mod_collaborate/view', $data);
        echo $this->output->footer();
    }
    /**
     * Displays the main instructions page content with a form for a submission.
     *
     * @param $collaborate the collaborate instance std Object
     * @param $cm the course module std Object
     * @param $page Indicates if we are dealing with student A or student B
     * @param $form A Moodle form object
     * @return none
     */
    public function render_page_content($collaborate, $cm, $page, $form) {

        $data = new stdClass();

        $data->heading = $collaborate->title;

        $data->user = 'User: '. strtoupper($page);

        // Get the content from the database.
        $content = ($page == 'a') ? $collaborate->instructionsa : $collaborate->instructionsb;
        $filearea = 'instructions' . $page;
        $context = context_module::instance($cm->id);
        $content = file_rewrite_pluginfile_urls($content, 'pluginfile.php', $context->id,
                'mod_collaborate', $filearea, $collaborate->id);

        // Run the content through format_text to enable streaming video etc.
        $formatoptions = new stdClass;
        $formatoptions->overflowdiv = true;
        $formatoptions->context = $context;
        $format = ($page == 'a') ? $collaborate->instructionsaformat :
                $collaborate->instructionsbformat;

        $data->body = format_text($content, $format, $formatoptions);

        // Get the form html.
        $data->form = $form->render();

        // Get a return url back to view page.
        $urlv = new \moodle_url('/mod/collaborate/view.php', ['id' => $cm->id]);
        $data->url_view = $urlv->out(false);

        // Display the show page content.
        echo $this->output->header();
        echo $this->render_from_template('mod_collaborate/show', $data);
        echo $this->output->footer();
    }

    /**
     * Displays the reports page (reports.php).
     *
     * @param object $collaborate the collaborate instance std Object
     * @param object $cm the course module std Object
     * @param array $submissions 2D array of submission records
     * @param headers $headers the strings for the column headers
     * @return none
     */
    public function render_reports_page_content($collaborate, $cm, $submissions, $headers) {

        $data = new stdClass();

        $data->heading = get_string('submissions', 'mod_collaborate');
        $data->headers = $headers;
        $data->submissions = $submissions;

        // The tabs.
        $r = new \moodle_url('/mod/collaborate/reports.php', ['cid' => $collaborate->id]);
        $v = new \moodle_url('/mod/collaborate/view.php', ['id' => $cm->id]);
        $data->url_reports = $r->out(false);
        $data->url_view = $v->out(false);

        // Display the page content.
        echo $this->output->header();
        echo $this->render_from_template('mod_collaborate/reports', $data);
        echo $this->output->footer();
    }
    /**
     * Displays the grading form.
     *
     * @param object $submission the submission to grade
     * @param object $context module context
     * @param int $sid - the submission id
     * @return html representation of the page
     */
    public function render_submission_to_grade($submission, $context, $sid) {

        $data = new stdClass();
        $data->pageheader =  get_string('gradingheader', 'mod_collaborate');
        $data->title = $submission->title;
        $data->firstname = $submission->firstname;
        $data->lastname = $submission->lastname;
        $data->grade = $submission->grade;

        // Submission.
        $content = file_rewrite_pluginfile_urls($submission->submission, 'pluginfile.php', $context->id,
                'mod_collaborate', 'submission', $sid);

        // Format submission.
        $formatoptions = new stdClass;
        $formatoptions->noclean = true;
        $formatoptions->overflowdiv = true;
        $formatoptions->context = $context;
        $format = $submission->submission;
        $data->submission = format_text($content, $format, $formatoptions);

        return $this->render_from_template('mod_collaborate/submissiontograde', $data);

    }

    /**
     * Displays the form to change a module name.
     *
     * @param object $mform the form to display
     * @return none
     */
    public function render_namechange_form($mform) {

        $data = new stdClass();
        $data->form = $mform->render();

        // Display the page content.
        echo $this->output->header();
        echo $this->render_from_template('mod_collaborate/namechangeform', $data);
        echo $this->output->footer();
    }

    /**
     * Displays the list of collaborate modules in a course.
     *
     * @param array of objects $collaborates list of collaborate modules in course
     * @param object $course the course
     * @return none
     */
    public function show_namechanger_page($collaborates, $course) {

        $data = new stdClass();

        $data->heading = get_string('modulenameplural', 'mod_collaborate');

        // Table headers.
        $headers = array();

        if ($course->format == 'weeks') {
            $headers[] = get_string('week');

        } elseif ($course->format == 'topics') {
            $headers[] = get_string('topic');
        } else {
            $headers[] = ' ';
        }
        $headers[] = get_string('name');
        $headers[] = get_string('action', 'mod_collaborate');
        $data->headers = $headers;

        $data->rows = array();

        // Table rows.
        foreach ($collaborates as $collaborate) {
            $row = array();
            $row['name'] = format_string($collaborate->name, true);
            // Dim link if hidden item.
            $row['class'] = (!$collaborate->visible) ? 'text-muted' : ' ';
            // Section number or week may be present
            $row['section'] = $collaborate->section;
            // Link to edit form.
            $editlink = new \moodle_url($this->page->url, ['action' => 'edit', 'actionitem' => $collaborate->id]);
            $row['editurl'] = $editlink->out(false);

            $data->rows[] = $row;
        }
        // Display the table.
        echo $this->output->header();
        echo $this->render_from_template('mod_collaborate/namechangepage', $data);
        echo $this->output->footer();

    }
}