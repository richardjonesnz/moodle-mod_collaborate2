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
 * Timing for the Developers Level 2 scheduled task
 *
 * @package    mod_collaborate
 * @since      Moodle 2.7
 * @copyright  2015 Flash Gordon http://www.flashgordon.com
 *
    @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
$tasks = array(
  // The cron will run the specified task.
  array('classname' => 'mod_collaborate\task\collaborate_scheduled',

    'blocking' => 0,
    'minute' => '*/1',
    'hour' => '*',
    'day' => '*',

    'dayofweek' => '*',
    'month' => '*'
    )
);