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
 * Generates the import screen and processes import of DataPlus dbs.
 * @package mod_dataplus
 * @copyright 2015 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("../../config.php");
require_once("locallib.php");
require_once($CFG->libdir.'/filelib.php');

/*
 * Generate the import screen and process form submissions
 */
function dataplus_import() {
    global $CFG, $id, $dataplusdb, $dataplus, $dataplusfilehelper, $mode, $context;

    require_once('import_form.php');

    $url = "{$CFG->wwwroot}/mod/dataplus/import.php?id={$id}";

    // If the user is setting up the instance and has not tried to import an existing db...
    if ($mode == 'dbsetup' && empty($_POST)) {
        $url .= "&mode={$mode}";
    }

    $mform = new dataplus_import_form($url);

    if (!$mform->is_cancelled() && $form = $mform->get_data()) {
        // If something has been uploaded, put it in the temp directory...
        $draftitemid = file_get_submitted_draft_itemid('importfile');
        $zipname = $dataplusfilehelper->get_draft_file_name($draftitemid);

        if (substr($zipname, -3) != 'zip') {
            // If the uploaded file does not appear to be a zip archive, get an error message.
            $invalid = get_string('validate_file_suffix', 'dataplus');
        } else {
            $copyto['contextid'] = $contextid = $context->id;
            $copyto['component'] = $component = 'mod_dataplus';
            $copyto['filearea'] = $filearea = 'unzip';
            $copyto['itemid'] = $dataplus->id;
            $copyto['filepath'] = '/';
            $copyto['filename'] = $zipname;

            $dbid = $dataplus->id;

            file_save_draft_area_files($draftitemid, $contextid, $component, $filearea, $dbid);

            $temppath = $dataplusfilehelper->get_temp_path() . '/' . $zipname;

            $dataplusfilehelper->copy($copyto, $temppath);

            $zippath = $dataplusfilehelper->get_zip_path();
            $fp = get_file_packer();
            $fp->extract_to_pathname($temppath, $zippath);

            $hande = opendir($zippath);

            $dbname = null;

            // Find the name of the SQLite3 database.
            while (false !== ($file = readdir($hande))) {
                if (substr($file, -6) == 'sqlite') {
                    $dbname = $file;
                    if ($dbname != 'dataplus_db.sqlite') {
                        $premoodle2 = true;
                    }
                    break;
                }
            }

            if (!is_null($dbname)) {
                // Run the database validation (sets locking to false to prevent performance gotcha).
                $dataplusdb->clean_up();
                try {
                    $dataplusdb = new sqlite3_db_dataplus(false, $zippath . '/' . $dbname, true);
                } catch (Exception $e) {
                    $invalid = get_string('errorimportfailed', 'dataplus');
                }

                if (!isset($invalid)) {
                    $column = array(new stdClass());

                    if (isset($form->remgroups) && $form->remgroups == 1) {
                        $column[0]->name = 'group_id';
                        $column[0]->value = '';

                        $dataplusdb->update_dataplus_record($column);
                    }

                    if ($CFG->wwwroot != $dataplusdb->get_file_db_domain()) {
                        $column[0]->name = 'last_update_id';
                        $column[0]->value = '-1';

                        $dataplusdb->update_dataplus_record($column);

                        $column[0]->name = 'creator_id';
                        $column[0]->value = '-1';

                        $dataplusdb->update_dataplus_record($column);
                    }
                }
            } else {
                $invalid = get_string('validate_no_db', 'dataplus');
            }
        }

        // If the database is valid, copy it from the temp dir to the file repository
        // and print a confirmation.
        if (!isset($invalid)) {
            $imagepath = $zippath . '/images';
            $filepath = $zippath . '/files';
            $longtextpath = $zippath . '/longtext';

            if (!empty($premoodle2)) {
                $dataplusfilehelper->add_ids_to_import_files('image', $imagepath);
                $dataplusfilehelper->add_ids_to_import_files('file', $filepath);
                $dataplusfilehelper->add_ids_to_import_files('longtext', $longtextpath);

                $cols = $dataplusdb->list_dataplus_table_columns();

                foreach ($cols as $col) {
                    $oldname = $col->name.$dataplusdb->get_supporting_suffix();
                    if ($dataplusdb->check_dataplus_column_exists($oldname)) {
                        $name = $col->name;
                        $dataplusdb->rename_supporting_column($name, $col->form_field_type, $name);
                    }
                }
            }

            $ipath = $zippath . '/images';
            $dataplusfilehelper->copy($ipath, $dataplusfilehelper->get_image_fileinfo(), array());
            $fpath = $zippath . '/files';
            $dataplusfilehelper->copy($fpath, $dataplusfilehelper->get_file_fileinfo(), array());
            $fpath = $zippath . '/longtext';
            $dataplusfilehelper->copy($fpath, $dataplusfilehelper->get_longtext_fileinfo(), array());

            print html_writer::start_div('dataplus_import_complete');
            print get_string('importcomplete', 'dataplus');
            print html_writer::empty_tag('br');
            print html_writer::tag('a', get_string('viewdb', 'dataplus'), array('href' => $CFG->wwwroot.'/mod/dataplus/view.php?mode=view&id='.$id));
            print html_writer::end_div();
        } else {
            // If it's not valid, print an error message.
            print html_writer::div($invalid);
        }
    }

    $mform->display();
}

dataplus_base_setup('/mod/dataplus/import.php');
dataplus_page_setup(get_string('manage_import', 'dataplus'));

// Don't show the navigation tabs if we're in setup mode.
if ($mode != 'dbsetup') {
    $currenttab = 'manage';
    include('tabs.php');
}

if (isloggedin() && has_capability('mod/dataplus:databaseedit', $context, $USER->id)) {
    dataplus_import();
} else {
    $epath = $CFG->wwwroot.'/mod/dataplus/view.php?mode=view&amp;id=' . $id;
    print_error('capablilty_edit_template', 'dataplus', $epath);
}

echo $OUTPUT->footer();
dataplus_base_close();