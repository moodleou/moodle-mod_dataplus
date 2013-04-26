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
 * @package mod
 * @subpackage dataplus
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class restore_dataplus_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {
        $userinfo = $this->get_setting_value('userinfo');
        $paths = array();
        $paths[] = new restore_path_element('dataplus', '/activity/dataplus');

        if ($userinfo) {
            $paths[] = new restore_path_element('dataplus_rating', '/activity/dataplus/ratings/rating');
        }

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    protected function process_dataplus($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        $data->timeavailablefrom = $this->apply_date_offset($data->timeavailablefrom);
        $data->timeavailableto = $this->apply_date_offset($data->timeavailableto);
        $data->assesstimestart = $this->apply_date_offset($data->assesstimestart);
        $data->assesstimefinish = $this->apply_date_offset($data->assesstimefinish);

        if ($data->scale < 0) { // Scale found, get mapping.
            $data->scale = -($this->get_mappingid('scale', abs($data->scale)));
        }

        // Insert the DataPlus record.
        $newitemid = $DB->insert_record('dataplus', $data);

        // Immediately after inserting "activity" record, call this.
        $this->apply_activity_instance($newitemid);
    }

    protected function after_execute() {
        // Add dataplus related files, no need to match by itemname, just internally handled context.
        $this->add_related_files('mod_dataplus', 'intro', null);
        $this->add_related_files('mod_dataplus', 'dataplus_databases', 'dataplus');
        $this->add_related_files('mod_dataplus', 'image', null);
        $this->add_related_files('mod_dataplus', 'file', null);
        $this->add_related_files('mod_dataplus', 'longtext', null);
    }

    protected function process_dataplus_rating($data) {
        global $DB;

        $data = (object)$data;

        // Cannot use ratings API, cause, it's missing the ability to specify times (modified/created).
        $data->contextid = $this->task->get_contextid();
        if ($data->scaleid < 0) { // Scale found, get mapping.
            $data->scaleid = -($this->get_mappingid('scale', abs($data->scaleid)));
        }
        $data->rating = $data->value;
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        // We need to check that component and ratingarea are both set here.
        if (empty($data->component)) {
            $data->component = 'mod_dataplus';
        }
        if (empty($data->ratingarea)) {
            $data->ratingarea = 'record';
        }

        $newitemid = $DB->insert_record('rating', $data);
    }
}