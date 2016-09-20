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

defined('MOODLE_INTERNAL') || die();

/**
 * Dataplus generator for unit test.
 *
 * @package mod_dataplus
 * @copyright 2015 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_dataplus_generator extends testing_module_generator {

    /**
     * Create a new dataplus instance
     *
     * @param array|stdClass $record The record to insert into dataplus table
     * @param array $options Optional parameters
     * @return stdClass record from dataplus table with additional field cmid
     */
    public function create_instance($record = null, array $options = null) {
        $record = (object)(array)$record;

        $defaultsettings = array(
            'requiredentries' => 0,
            'requiredentriestoview' => 0,
            'maxentries' => 0,
            'maxentriesperuser' => 0,
            'allowcomments' => 1,
            'navigationlimit' => 15,
            'listperpage' => 10,
            'viewtablabel' => get_string('view', 'dataplus'),
            'viewtabvisible' => 1,
            'singlerecordtablabel' => get_string('single_record', 'dataplus'),
            'singlerecordtabvisible' => 1,
            'searchtablabel' => get_string('search', 'dataplus'),
            'searchtabvisible' => 1,
            'addrecordtablabel' => get_string('addrecord', 'dataplus'),
            'addrecordtabvisible' => 1,
            'exporttablabel' => get_string('export', 'dataplus'),
            'exporttabvisible' => 1,
            'savebuttonlabel' => get_string('save', 'dataplus'),
            'saveandviewbuttonlabel' => get_string('saveandview', 'dataplus'),
            'cancelbuttonlabel' => get_string('cancel', 'dataplus')
        );

        foreach ($defaultsettings as $name => $value) {
            if (!isset($record->{$name})) {
                $record->{$name} = $value;
            }
        }

        return parent::create_instance($record, (array)$options);
    }
}
