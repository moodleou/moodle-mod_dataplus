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
 * Generates screens for managing dbs and processes activity
 * @package mod_dataplus
 * @copyright 2015 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once("../../config.php");
require_once("locallib.php");
require_once($CFG->libdir.'/filelib.php');

/**
 * generates the screen for managing the fields in the database.
 *
 * @param string $msg
 */
function dataplus_manage($msg = null) {
    global $dataplusdb, $CFG, $id, $groupmode, $OUTPUT;

    require_once('manage_form.php');

    if (!is_null($msg)) {
        echo '<div class = "dataplus_update_message">'.$msg.'</div>';
    }

    $murl = "{$CFG->wwwroot}/mod/dataplus/manage.php?id={$id}&mode=dbmanage";
    $mform = new dataplus_manage_form($murl);
    $parameters = dataplus_get_restricted_groups_parameters();
    $columns = $dataplusdb->list_dataplus_table_columns(false, $parameters);

    // Mform require the form fields to be defined before it checks post data.
    // If the database has no user defined columns, then generate a form to allow the creation
    // of up to 4 fields, otherwise one field.

    if (empty($columns)) {
        $fieldno = 4;
        $mform->define_fields($fieldno);
    } else {
        $mform->define_fields(1);
    }

    if ($form = $mform->get_data()) {
        dataplus_log('createcolumn');

        $i = 0;
        $exists = false;

        // If there are no fields in the database, check that none of the new fields have
        // the same name as each other.
        if (empty($columns)) {
            for ($i = 0; $i < $fieldno; $i++) {
                $varname = 'fieldname' . $i;
                $v = $i + 1;

                while ($v < $fieldno) {
                    $varname2 = 'fieldname'.$v;

                    if ($form->$varname2 != '' && $form->$varname == $form->$varname2) {
                        $exists = 'FORMFIELDSSAME';
                        break;
                    }
                    $v++;
                }
            }
        } else {
            // If there are fields, check the new/edited fields doesn't have the same name
            // as an existing field.
            while (true) {
                $varname = 'fieldname' . $i;

                if (isset($form->$varname)) {
                    $name = $dataplusdb->create_valid_object_name($form->$varname);
                    if ($dataplusdb->check_dataplus_column_exists($name)) {
                        $exists = 'FIELDEXISTS';
                    }
                    if ($dataplusdb->check_column_name_reserved($name)) {
                        $exists = 'FIELDRESERVED';
                    }
                }

                if ($exists == 'FIELDEXISTS' || !isset($form->$varname)) {
                    break;
                }
                $i++;
            }
        }

        if ($exists == 'FIELDEXISTS') {
            $msg = get_string('labelexists', 'dataplus');
            echo '<div class = "dataplus_update_message">'.$msg.'</div>';
        } else if ($exists == 'FORMFIELDSSAME') {
            $msg = get_string('twosame', 'dataplus');
            echo '<div class = "dataplus_update_message">'.$msg.'</div>';
        } else if ($exists == 'FIELDRESERVED') {
            $msg = get_string('reserved', 'dataplus', $name);
            echo '<div class = "dataplus_update_message">'.$msg.'</div>';
        } else {
            $i = 0;
            // Add new fields until no new fields can be found in $form.
            while (true) {
                $varname = 'fieldname'.$i;
                $vartype = 'fieldtype'.$i;
                $varmultiple = 'fieldmultiple'.$i;
                $varoptions = 'fieldoptions'.$i;
                $vargroupid = 'group_id'.$i;

                $options = null;

                if (isset($form->$varname)) {
                    if ($form->$varname != '') {
                        if ($form->$vartype == 'menu') {
                            if (!empty($form->$varmultiple)) {
                                $form->$vartype = 'menumultiple';
                            } else {
                                $form->$vartype = 'menusingle';
                            }

                            $options = $form->$varoptions;
                        }

                        if (isset($form->$vargroupid)) {
                            $groupid = $form->$vargroupid;
                        } else {
                            $groupid = '0';
                        }

                        $vname = $form->$varname;
                        $vtype = $form->$vartype;
                        $result = $dataplusdb->add_dataplus_column($vname, $vtype, $options, $groupid);
                    }
                } else {
                    break;
                }

                $i++;
            }

            // Trash the form and POST global and create a new instance, this
            // ensures the form will have one field and post data is not displayed.
            $_POST = null;
            $mform = new dataplus_manage_form("{$CFG->wwwroot}/mod/dataplus/manage.php?id={$id}");
            $mform->define_fields(1);
            $columns = $dataplusdb->list_dataplus_table_columns(false, $parameters);
        }
    }

    // Show a table of existing columns.
    if (!empty($columns)) {
        $langedit   = get_string('edit');
        $langdelete = get_string('delete');

        $table = new html_table();

        $langfname = get_string('fieldname', 'dataplus');
        $langftype = get_string('fieldtype', 'dataplus');
        $langactions = get_string('actions', 'dataplus');

        if ($groupmode > 0) {
            $langgroup = get_string('group', 'dataplus');
            $table->head = array($langfname, $langftype, $langgroup, $langactions);
        } else {
            $table->head = array($langfname, $langftype, $langactions);
        }

        foreach ($columns as $column) {
            if (dataplus_check_groups($column, true)) {
                $icon = new pix_icon('t/edit', $langedit, '', array('class' => 'iconsmall'));
                $url = 'manage.php?id='.$id.'&amp;mode=edit&amp;fid='.$column->id;
                $icons = $OUTPUT->action_icon($url, $icon);

                $icon = new pix_icon('t/delete', $langdelete, '', array('class' => 'iconsmall'));
                $url = 'manage.php?id='.$id.'&amp;mode=delete&amp;fid='.$column->id;
                $icons .= $OUTPUT->action_icon($url, $icon);
            } else {
                $icons = '';
            }

            $desc = $dataplusdb->get_field_type_description($column->form_field_type);

            if ($groupmode > 0) {
                $groupname = $groupname = dataplus_get_group_name($column->group_id);
                $table->data[] = array($column->label, $desc, $groupname, $icons);
            } else {
                $table->data[] = array($column->label, $desc, $icons);
            }
        }

        echo html_writer::table($table);
    }

    echo '<br/>';

    $mform->display();
}


/**
 * generates the screen for editing a field
 *
 */
function dataplus_edit() {
    global $dataplusdb, $CFG, $id, $dataplusfilehelper;

    require_once('manage_form.php');

    $fid = optional_param('fid', null, PARAM_INT);
    $murl = $CFG->wwwroot.'/mod/dataplus/manage.php?id='.$id.'&mode=editsubmit&fid='.$fid;
    $mform = new dataplus_manage_form($murl);

    $mform->define_fields(1, 'edit');

    // If the editing form is cancelled, go back to the manage screen.
    if ($mform->is_cancelled() || empty($fid)) {
        $_POST = null;
        dataplus_manage();
        return;
    }

    // Check the current group can edit this field.
    $existing = $dataplusdb->get_column_details($fid);

    if (!dataplus_check_groups($existing, true, true)) {
        return;
    }

    // If form submitted, alter the existing column and return to the manage screen.
    if ($form = $mform->get_data()) {
        dataplus_log('editcolumn');

        $name = $existing->name;
        $type = $existing->form_field_type;

        if ($type != $form->fieldtype0) {
            if ($type == 'file' || $type == 'image') {
                $dataplusfilehelper->delete_column_files($name, $type);
            }
        }

        $details = new stdClass();
        $details->id = $fid;
        $details->label = $form->fieldname0;

        if ($form->fieldtype0 == 'menu') {
            if (!empty($form->fieldmultiple0)) {
                $details->form_field_type = 'menumultiple';
            } else {
                $details->form_field_type = 'menusingle';
            }

            $details->form_field_options = $form->fieldoptions0;
        } else {
            $details->form_field_type = $form->fieldtype0;
        }

        if (isset($form->group_id0)) {
            $details->group_id = $form->group_id0;
        }

        $result = $dataplusdb->alter_dataplus_column($details);

        if ($result === "COLUMNEXISTS") {
            $msg = get_string('samename', 'dataplus');
        } else {
            $msg = get_string('fieldedited', 'dataplus');
        }

        $_POST = null;

        dataplus_manage($msg);
        return;
    }

    // Display the editing form.
    $columndetails = $dataplusdb->get_column_details($fid);

    if ($columndetails->form_field_type == 'menumultiple') {
        $columndetails->multiple = 'checked';
    }

    $defaultvals = array('fieldname0' => $columndetails->label);

    if (substr($columndetails->form_field_type, 0, 4) == 'menu') {
        $defaultvals['fieldtype0'] = 'menu';
    } else {
        $defaultvals['fieldtype0'] = $columndetails->form_field_type;
    }

    if (isset($columndetails->multiple)) {
        $defaultvals['fieldmultiple0'] = $columndetails->multiple;
    }

    if (isset($columndetails->form_field_options)) {
        $coloptions = $columndetails->form_field_options;
        $defaultvals['fieldoptions0'] = preg_replace("/^\r/", " \r", $coloptions);
    }

    if (isset($columndetails->group_id)) {
        $defaultvals['group_id0'] = $columndetails->group_id;
    }

    $mform->set_data($defaultvals);
    $mform->display();
}


/**
 * generates the screen to delete fields.
 *
 */
function dataplus_delete() {
    global $dataplusdb, $CFG, $id, $groupmode, $dataplusfilehelper;

    require_once('delete_form.php');

    $fid = optional_param('fid', null, PARAM_INT);

    // Check the current group can delete this field.
    $coldetails = $dataplusdb->get_column_details($fid);

    if (!dataplus_check_groups($coldetails, true, true)) {
        return;
    }

    $murl = $CFG->wwwroot.'/mod/dataplus/manage.php?id='.$id.'&mode=deletesubmit&fid='.$fid;
    $mform = new dataplus_delete_form($murl);

    if ($mform->is_cancelled() || empty($fid)) {
        dataplus_manage();
        return;
    }

    // If the delete form has been submitted...
    if ($form = $mform->get_data()) {
        dataplus_log('deletecolumn');

        $formfieldtype = $coldetails->form_field_type;

        // If the field had supporting files, delete them.
        if ($formfieldtype == 'image' || $formfieldtype == 'file') {
            $cname = $coldetails->name;
            $ctype = $coldetails->form_field_type;

            $dataplusfilehelper->delete_column_files($cname, $ctype);
        }

        $del = $dataplusdb->delete_dataplus_column($fid);

        if (!$del) {
            $msg = get_string('actionfailed', 'dataplus');
            dataplus_manage($msg);
            return;
        }

        $msg = get_string('fielddeleted', 'dataplus');

        // When delete is complete, go to the manage screen...
        dataplus_manage($msg);
        return;
    }

    $columndetails = $dataplusdb->get_column_details($fid);

    $table = new html_table();

    // No form has been submitted, so display a table with the column detail and the delete form.
    $langfname = get_string('fieldname', 'dataplus');
    $langftype = get_string('fieldtype', 'dataplus');

    if ($groupmode > 0) {
        $table->head = array($langfname, $langftype, get_string('group', 'dataplus'));
    } else {
        $table->head = array($langfname, $langftype);
    }

    $desc = $dataplusdb->get_field_type_description($columndetails->form_field_type);

    if ($groupmode > 0) {
        $groupname = dataplus_get_group_name($columndetails->group_id);
        $table->data[] = array($columndetails->label, $desc, $groupname);
    } else {
        $table->data[] = array($columndetails->label, $desc);
    }

    echo html_writer::table($table);
    echo '<br/>';

    $mform->display();
}


function dataplus_clear_data() {
    global $dataplusdb, $CFG, $id;

    require_once('clear_data_form.php');

    $murl = $CFG->wwwroot.'/mod/dataplus/manage.php?id='.$id.'&mode=cleardata';
    $mform = new dataplus_clear_data_form($murl);

    if ($mform->is_cancelled()) {
        echo '<div id="dataplus_clear_msg">'.get_string('cleardatacancelled', 'dataplus').'</div>';
        return;
    }

    if ($form = $mform->get_data()) {
        dataplus_log('cleardata');

        $cols = $dataplusdb->list_dataplus_table_columns();

        foreach ($cols as $col) {
            if ($col->type == 'image' || $col->type == 'file') {
                $this->delete_column_files($col->name, $col->type);
            }
        }

        echo '<div id="dataplus_clear_msg">';

        if ($dataplusdb->delete_dataplus_record()) {
            echo get_string('cleardatadone', 'dataplus');
        } else {
            echo get_string('cleardatafailed', 'dataplus');
        }

        echo '</div>';
    } else {
        $mform->display();
    }
}


dataplus_base_setup('/mod/dataplus/manage.php');
dataplus_page_setup(get_string('manage_manage', 'dataplus'));

// If we're in dbsetup mode, don't show navigational tabs.
if ($mode != 'dbsetup') {
    $currenttab = 'manage';

    include('tabs.php');
}

if (isloggedin() && has_capability('mod/dataplus:databaseedit', $context, $USER->id)) {
    $group = optional_param('group', null, PARAM_TEXT);

    if ($mode == 'edit' || ($mode == 'editsubmit' && is_null($group))) {
        dataplus_edit();
    } else if ($mode == 'delete' || ($mode == 'deletesubmit' && is_null($group))) {
        dataplus_delete();
    } else if ($mode == 'cleardata') {
        dataplus_clear_data();
    } else {
        dataplus_manage();
    }
} else {
    $url = $CFG->wwwroot.'/mod/dataplus/view.php?mode=view&amp;id='.$id;
    print_error('capablilty_manage_database', 'dataplus', $url);
}

echo $OUTPUT->footer();
dataplus_base_close();