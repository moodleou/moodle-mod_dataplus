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
 * Moodle form for imports
 * @package mod
 * @subpackage dataplus
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot.'/course/moodleform_mod.php');

// Form for importing a Dataplus database.
class dataplus_import_form extends moodleform {

    public function definition() {
        $mform = &$this->_form;
        $mform->addElement('header', 'general', get_string('importdb', 'dataplus'));
        $mform->addElement('static', 'warning', '', get_string('importwarning', 'dataplus'));
        $mform->addElement('filepicker', 'importfile', get_string('database', 'dataplus'));
        $mform->addElement('checkbox', 'remgroups', get_string('importremovegroup', 'dataplus'));
        $mform->setDefault('remgroups', 1);

        $this->add_action_buttons(true, get_string('manage_import', 'dataplus'));
    }
}