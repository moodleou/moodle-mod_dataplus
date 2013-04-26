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
 * Moodle form for adding or editing db records.
 * @package mod
 * @subpackage dataplus
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->dirroot.'/course/moodleform_mod.php');

class dataplus_record_form extends moodleform {
    /**
     * moodleforms requires a definition(), but I don't want the fields defined when the
     * class is instantiated, so this one does nothing
     */
    public function definition() {
        return;
    }


    /**
     * this really defines the fields
     *
     * @param array $fields - fields to be included on the form.
     * @param string $mode - to 'add' or 'edit' a record
     * @param obj $result - if editing, the result object for use here
     */
    public function define_fields($fields, $mode, $template, $result = null) {
        global $dataplusdb, $dataplusfilehelper, $dataplus, $groupmode;
        global $currentgroup, $cm, $context, $USER;

        $mform = &$this->_form;
        // This forces the creation of a fieldset preventing validation errors.
        $mform->addElement('header', 'addrecord', '');

        if ($groupmode > 0 && has_capability('mod/dataplus:databaseedit',
            $context,
            $USER->id)) {
            $groups["0"] = get_string('allparticipants');
            $groupsdata = groups_get_all_groups($cm->course, 0, $cm->groupingid);

            if ($groupsdata !== false) {
                foreach ($groupsdata as $gd) {
                    $groups["{$gd->id}"] = $gd->name;
                }

                $langgroup = get_string('group', 'dataplus');
                $mform->addElement('select', 'group_id', $langgroup, $groups);
                $mform->setDefault('group_id', $currentgroup);
            }
        }

        $buttons = array('**save**',
                         '**saveandview**',
                         '**reset**',
                         '**cancel**',
                         '**addcancel**');

        $templatesections = explode(']]', $template);

        foreach ($templatesections as $ts) {
            $tseles = explode('[[', $ts);

            foreach ($buttons as $button) {
                if (strpos($tseles[0], $button) !== false) {
                    $buttonsinsection[] = $button;
                }
            }

            if (empty($buttonsinsection) && !empty($tseles[0])) {
                $mform->addElement('html', $tseles[0]);
            } else if (!empty($tseles[0])) {
                $mixedoutput[] = $tseles[0];
                $i = 0;

                foreach ($buttonsinsection as $button) {
                    $newoutput = array();
                    foreach ($mixedoutput as $m) {
                        if (strpos($m, $button) !== false) {
                            $segments = explode($button, $m);
                            for ($i = 0; $i<count($segments); $i++) {
                                $newoutput[] = $segments[$i];
                                if ($i < count($segments)-1) {
                                    $newoutput[] = $button;
                                }
                            }
                        } else {
                            $newoutput[] = $m;
                        }
                    }
                    $mixedoutput = $newoutput;
                }

                foreach ($mixedoutput as $m) {
                    if ($m == '**save**') {
                        $buttonarray[] = &$mform->createElement('submit', 'savebutton',
                                                                $dataplus->savebuttonlabel);
                    } else if ($m == '**saveandview**') {
                        $buttonarray[] = &$mform->createElement('submit', 'saveandviewbutton',
                                                                $dataplus->saveandviewbuttonlabel);
                    } else if ($m == '**reset**') {
                         $buttonarray[] = &$mform->createElement('reset', 'resetbutton',
                                                                get_string('reset', 'dataplus'));
                    } else if ($m == '**cancel**') {
                         $buttonarray[] = &$mform->createElement('cancel', 'cancelbutton',
                                                                $dataplus->cancelbuttonlabel);
                    } else if ($m == '**addcancel**') {
                        if ($mode == 'edit') {
                            $this->add_action_buttons(true);
                        } else {
                            $this->add_action_buttons(true, $dataplus->savebuttonlabel);
                        }
                    } else if (!empty($m)) {
                        if (!empty($buttonarray)) {
                            $mform->addGroup($buttonarray, '', '', array(' '), false);
                            $buttonarray = array();
                        }
                        $mform->addElement('html', $m);
                    }
                }

                if (!empty($buttonarray)) {
                    $mform->addGroup($buttonarray, '', '', array(' '), false);
                }
            }

            if (!isset($tseles[1])) {
                continue;
            }

            $found = false;

            foreach ($fields as $field) {
                if ($field->name == $tseles[1]) {
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                continue;
            }

            $fieldtype = $field->form_field_type;
            $fieldlabel = format_string($field->label);

            if ($fieldtype == 'smalltext' || $fieldtype == 'number') {
                $mform->addElement('text', $field->name, $fieldlabel);
            } else if ($fieldtype == 'longtext') {
                $editoroptions = array('maxfiles' => EDITOR_UNLIMITED_FILES, 'trusttext'=>true);
                $mform->addElement('editor', $field->name, $fieldlabel, null, $editoroptions);
            } else if ($fieldtype == 'date') {
                $mform->addElement('date_selector', $field->name, $fieldlabel);
            } else if ($fieldtype == 'datetime') {
                $mform->addElement('date_time_selector', $field->name, $fieldlabel);
            } else if ($fieldtype == 'image' || $fieldtype == 'file') {
                if (!empty($result)) {
                    foreach ($result as $name => $val) {
                        if ($name == $field->name) {
                            if (empty($val)) {
                                break;
                            }
                            if ($fieldtype == 'image') {
                                $id = $result->id;
                                $path = $dataplusfilehelper->get_image_file_path($val, $id, $name);
                                $altname = $field->name.'000alt';
                                $alt = null;

                                foreach ($result as $n => $v) {
                                    if ($n == $altname) {
                                        $alt = $v;
                                        break;
                                    }
                                }

                                $html = "<img src=\"{$path}\" alt=\"{$alt}\"/>";

                                $mform->addElement('static', 'image' . $field->name, '', $html);
                                break;
                            } else if ($fieldtype == 'file') {
                                $id = $result->id;
                                $path = $dataplusfilehelper->get_file_file_path($val, $id, $name);
                                $html = "<a href=\"{$path}\">{$val}</a>";
                                $mform->addElement('static', 'file' . $field->name, '', $html);
                                break;
                            }
                        }
                    }
                }

                $mform->addElement('filepicker', $field->name, format_string($field->label));

                if ($fieldtype == 'image') {
                    $sname = $dataplusdb->get_supporting_field_name($field->name, $fieldtype);
                    $langsuppdesc = get_string('suppdesc', 'dataplus', $field->label);
                    $mform->addElement('text', $sname, $langsuppdesc);
                }
            } else if ($fieldtype == 'url') {
                $mform->addElement('text', $field->name, format_string($field->label));
                $sname = $dataplusdb->get_supporting_field_name($field->name, $fieldtype);
                $langsuppdesc = get_string('suppdesc', 'dataplus', $field->label);
                $mform->addElement('text', $sname, $langsuppdesc);
            } else if ($fieldtype == 'boolean') {
                $langtrue = get_string('true', 'dataplus');
                $langfal = get_string('false', 'dataplus');
                $fname = $field->name;

                $radioarray   = array();
                $radioarray[] = &$mform->createElement('radio', $fname, '', $langtrue, 1);
                $radioarray[] = &$mform->createElement('radio', $fname, '', $langfal, 0);
                $mform->addGroup($radioarray, $field->name, $field->label, array(' '), false);
                $mform->setDefault($field->name, 2);
            } else if ($fieldtype == 'menusingle' || $fieldtype == 'menumultiple') {
                $options = array();
                $fieldoptions = explode("\r\n", $field->form_field_options);

                if (count($fieldoptions) <= 1) {
                    $fieldoptions = explode("\r", $field->form_field_options);
                }

                if (count($fieldoptions) <= 1) {
                    $fieldoptions = explode("\n", $field->form_field_options);
                }

                foreach ($fieldoptions as $fieldoption) {
                    $options[$fieldoption] = $fieldoption;
                }

                $flabel = format_string($field->label);
                $select = $mform->addElement('select', $field->name, $flabel, $options);

                if ($fieldtype == 'menumultiple') {
                    $select->setMultiple(true);
                }
            }
        }
    }
}