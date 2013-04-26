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
 * Moodle form for db searches.
 * @package mod
 * @subpackage dataplus
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->dirroot.'/course/moodleform_mod.php');

// Form for searching the database.
class dataplus_search_form extends moodleform {

    /**
     * moodleforms requires a definition(), but I don't want the fields defined when the
     * class is instantiated, so this one does nothing
     */
    public function definition() {
        return;
    }

    // Three functions for defining types of form fields that are used more than once.

    /**
     * sort fields for the advanced search form
     *
     * @param obj $mform
     * @param int $no
     * @param array $sortchoices
     */
    public function define_sort_field($mform, $no, $sortchoices) {
        $strsort = get_string('sort' . ($no+1), 'dataplus');
        $mform->addElement('select', 'sort' . $no, $strsort, $sortchoices);

        $fname = 'sort_options' . $no;
        $langasc = get_string('ascending', 'dataplus');
        $langdesc = get_string('descending', 'dataplus');

        $sortoptions = array();
        $sortoptions[] = &$mform->createElement('radio', $fname, '', $langasc, 'ASC');
        $sortoptions[] = &$mform->createElement('radio', $fname, '', $langdesc, 'DESC');
        $mform->addGroup($sortoptions, $fname, '', array(' '), false);
    }


    /**
     * before, equal or since for date fields on the advanced form
     *
     * @param obj $field
     * @return array
     */
    protected function define_date_options($field, &$mform) {
        $name = $field->name.'_arrow';
        $langb4 = get_string('before', 'dataplus');
        $langequ = get_string('equals', 'dataplus');
        $langsince = get_string('since', 'dataplus');

        $opts = array();
        $opts[] = &$mform->createElement('radio', $name, '', $langb4, 'lessthan');
        $opts[] = &$mform->createElement('radio', $name, '', $langequ, 'equals');
        $opts[] = &$mform->createElement('radio', $name, '', $langsince, 'greaterthan');

        return $opts;
    }


    /**
     * add a text field, with advanced search options if required.
     *
     * @param obj $mform
     * @param string $formtype
     * @param string $name
     * @param string $label
     */
    protected function define_text_field($mform, $formtype, $name, $label) {
        $strcontain = get_string('contains', 'dataplus');
        $strequals = get_string('equals', 'dataplus');

        $mform->addElement('text', $name, format_string($label));

        if ($formtype == 'searchadvanced') {
            $name = $name . '_specificity';
            $opts = array();
            $opts[] = &$mform->createElement('radio', $name, '', $strcontain, 'contains');
            $opts[] = &$mform->createElement('radio', $name, '', $strequals, 'equals');
            $mform->addGroup($opts, $name .  '_specificity', '', array(' '), false);
        }
    }


    /**
     * this functions actually defines the search form
     *
     * @param array $fields
     * @param array $supportingfields
     * @param obj $cm
     * @param int $id
     * @param string $formtype
     */
    public function define_fields($fields, $supportingfields, $cm, $id, $formtype = 'search') {
        global $CFG, $dataplusdb;

        $mform =&$this->_form;

        $mform->addElement('header', 'general', get_string('search', 'dataplus'));

        $searchurl = "{$CFG->wwwroot}/mod/dataplus/view.php?id={$id}&amp;mode=";

        // If on an advanced search, add a link to simple search and vice versa.
        if ($formtype=='searchadvanced') {
            $str = get_string('simplesearch', 'dataplus');
            $link = '<a href="'.$searchurl.'search">'.$str.'</a>';
            $html = '<div class="dataplus_search_type">'.$link.'</div>';
            $mform->addElement('static', 'link', '', $html);
        } else {
            $str = get_string('advancedsearch', 'dataplus');
            $link = '<a href="'.$searchurl.'searchadvanced">'.$str.'</a>';
            $html = '<div class="dataplus_search_type">'.$link.'</div>';
            $mform->addElement('static', 'link', '', $html);
        }

        $textsearchtypes = array('smalltext', 'url', 'longtext');
        $sortchoices = array (''=>'');

        // Itterate through each field and display according to the form_field_type.
        foreach ($fields as $field) {
            $fieldtype = $field->form_field_type;
            $fieldlabel = format_string($field->label);
            $fieldname = $field->name;

            if (in_array($fieldtype, $textsearchtypes)) {
                $name = $fieldname;
                $label = $field->label;

                if ($fieldtype == 'url' && $formtype != 'searchadvanced') {
                    $name = $dataplusdb->get_supporting_field_name($name, $fieldtype);
                    $langsuppdesc = get_string('suppdesc', 'dataplus', $fieldlabel);
                    $this->define_text_field($mform, $formtype, $name, $langsuppdesc);
                } else if ($fieldtype != 'url' || $formtype == 'searchadvanced') {
                    $this->define_text_field($mform, $formtype, $name, $label);
                }
            } else if ($fieldtype == 'number') {
                $mform->addElement('text', $fieldname, $fieldlabel);

                $name = $fieldname.'_arrow';
                $strless = get_string('lessthan', 'dataplus');
                $strequal = get_string('equals', 'dataplus');
                $strgreat = get_string('greaterthan', 'dataplus');
                $lt = 'lessthan';
                $e = 'equals';
                $gt = 'greaterthan';

                $opts = array();
                $opts[] = &$mform->createElement('radio', $name, '', $strless, $lt);
                $opts[] = &$mform->createElement('radio', $name, '', $strequal, $e);
                $opts[] = &$mform->createElement('radio', $name, '', $strgreat, $gt);

                $mform->addElement('html', '<div class="search_form_group">');
                $mform->addGroup($opts, $fieldname . '_arrow', '', array(' '), false);
                $mform->addElement('html', '</div>');
            } else if ($fieldtype == 'date' || $fieldtype == 'datetime') {
                if ($fieldtype == 'datetime') {
                    $type = 'date_time_selector';
                } else {
                    $type = 'date_selector';
                }

                $mform->addElement($type, $fieldname, $fieldlabel);
                $mform->setDefault($fieldname, 39600);

                $dateoptions = $this->define_date_options($field, $mform);
                $mform->addElement('html', '<div class="search_form_group">');
                $mform->addGroup($dateoptions, $fieldname . '_arrow', '', array(' '), false);
                $mform->addElement('html', '</div>');
                $mform->setDefault($fieldname . '_arrow', 'greaterthan');
            } else if ($fieldtype == 'boolean') {
                $strtrue = get_string('true', 'dataplus');
                $strfalse = get_string('false', 'dataplus');
                $stri = get_string('boo_ignore', 'dataplus');
                $name = $fieldname;

                $radioarray   = array();
                $radioarray[] = &$mform->createElement('radio', $name, '', $strtrue, 1);
                $radioarray[] = &$mform->createElement('radio', $name, '', $strfalse, 0);
                $radioarray[] = &$mform->createElement('radio', $name, '', $stri, 'null');

                $mform->addGroup($radioarray, $fieldname, $fieldlabel, array(''), false);
                $mform->setDefault($fieldname, 'null');
            } else if ($fieldtype == 'menusingle' || $fieldtype == 'menumultiple') {
                $options = array();

                $fieldoptions = explode("\r\n", $field->form_field_options);

                if (count($fieldoptions) <= 1) {
                    $fieldoptions = explode("\r", $field->form_field_options);
                }

                if (count($fieldoptions) <= 1) {
                    $fieldoptions = explode("\n", $field->form_field_options);
                }

                if (!empty($fieldoptions[0])) {
                    $fieldoptions = array_merge(array(' '), $fieldoptions);
                }

                foreach ($fieldoptions as $fieldoption) {
                    $options[$fieldoption] = $fieldoption;
                }

                $select = $mform->addElement('select', $fieldname, $fieldlabel, $options);
            } else {
                continue;
            }

            if ($formtype == 'searchadvanced') {
                $sortchoices[$fieldname] = $field->label;
            }
        }

        if ($formtype == 'searchadvanced') {
            foreach ($supportingfields as $field) {
                $name = $field->name;
                $label = $field->label;

                if (!$field->hidden) {
                    if ($field->type == 'text') {
                        $mform->addElement('static', 'break', '', '<br/>');
                        $mform->addElement('text', $name, $label);
                    } else if ($field->type == 'date') {
                        $mform->addElement('date_selector', $name, $label);
                        $mform->setDefault($name, $cm->added);

                        $dateoptions = $this->define_date_options($field, $mform);
                        $mform->addGroup($dateoptions, $name . '_arrow', '', array(' '), false);
                        $mform->setDefault($name . '_arrow', 'greaterthan');
                    }

                    $sortchoices[$name] = $field->label;
                }
            }

            $sorthtml = '<br/><strong>' . get_string('sort', 'dataplus') . '</strong>';
            $mform->addElement('static', 'break', '', $sorthtml);

            if ((count($sortchoices)-1)<3) {
                $levels = count($sortchoices)-1;
            } else {
                $levels = 3;
            }

            for ($i=0; $i<$levels; $i++) {
                $this->define_sort_field($mform, $i, $sortchoices);
            }
        }

        $this->add_action_buttons(true, get_string('search', 'dataplus'));
    }
}