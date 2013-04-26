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
 * Form for adding columns.
 * @package mod
 * @subpackage dataplus
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->dirroot.'/course/moodleform_mod.php');

class dataplus_manage_form extends moodleform {

    /**
     * moodleforms requires a definition(), but I don't want the fields defined when the
     * class is instantiated, so this one does nothing
     */
    public function definition() {
        return;
    }

    /**
     * this function actually defines the form fields
     *
     * @param int $itterations number of fields that can be created from this instance of a form
     * @param string $formcontext create a form to 'add' fields or 'edit' fields
     */
    public function define_fields($itterations, $formcontext = 'add') {
        global $dataplusdb, $groupmode, $cm, $currentgroup;

        $mform =&$this->_form;

        if ($formcontext == 'edit') {
            $mform->addElement('header', 'general', get_string('editfield', 'dataplus'));
        } else {
            $mform->addElement('header', 'general', get_string('addfields', 'dataplus'));
        }

        for ($i = 0; $i < $itterations; $i++) {
            $mform->addElement('static', 'br' . $i, '', '<br/>');

            $langfname = get_string('fieldname', 'dataplus');
            $mform->addElement('text', 'fieldname' .  $i, $langfname, array('size'=>'64'));

            $options = $dataplusdb->get_field_types();

            unset($options['menusingle']);
            unset($options['menumultiple']);

            $options['menu'] = get_string('field_menu', 'dataplus');

            $langftype = get_string('fieldtype', 'dataplus');
            $mform->addElement('select', 'fieldtype' .  $i, $langftype, $options);

            $langmult = get_string('allowmultiple', 'dataplus');
            $mform->addElement('checkbox', 'fieldmultiple' .  $i, $langmult);

            $mform->disabledIf('fieldmultiple' .  $i, 'fieldtype' .  $i, '', 'menu');

            $langopt = get_string('options', 'dataplus');
            $mform->addElement('textarea', 'fieldoptions' .  $i, $langopt, 'rows="5" cols="40"');

            $mform->disabledIf('fieldoptions' .  $i, 'fieldtype' .  $i, '', 'menu');

            if ($groupmode > 0) {
                $groups["0"] = get_string('allparticipants');
                $groupsdata = groups_get_all_groups($cm->course, 0, $cm->groupingid);

                if ($groupsdata !== false) {
                    foreach ($groupsdata as $gd) {
                        $groups["{$gd->id}"] = $gd->name;
                    }

                    $langgroup = get_string('group', 'dataplus');
                    $mform->addElement('select', 'group_id' . $i, $langgroup, $groups);
                    $mform->setDefault('group_id' . $i, $currentgroup);
                }
            }
        }

        $mform->addElement('static', 'brend', '', '<br/>');
        $this->add_action_buttons(true, get_string('savechanges'));
    }
}