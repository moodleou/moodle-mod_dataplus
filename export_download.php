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
 * Creates and pushes a zip archive containing data for export according
 * to  user selection and capabilities.
 * @package mod
 * @subpackage dataplus
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

header("Cache-Control: no-cache, must-revalidate");

require_once("../../config.php");
require_once('dataplus_file_helper.php');
require_once('sqlite3_db_dataplus.php');
require_once($CFG->libdir.'/filelib.php');

// Stand alone version of the necessary page setup code from dataplus/lib.php.
$id = required_param('id', PARAM_INT);
$mode = optional_param('mode', null, PARAM_TEXT);
$cm = get_coursemodule_from_id('dataplus', $id);
$context = context_module::instance($cm->id);

if ($mode == 'complex' && !has_capability('mod/dataplus:downloadfull', $context, $USER->id)) {
    print_error("Export selections misset or you do not have the correct permissions to proceed");
}

if (!$cm = get_coursemodule_from_id('dataplus', $id)) {
    print_error("Course Module ID was incorrect");
}

if (!$COURSE = $DB->get_record("course", array("id"=>$cm->course))) {
    print_error("Course is misconfigured");
}

if (!$dataplus = $DB->get_record("dataplus", array("id"=>$cm->instance))) {
    print_error("Course module is incorrect");
}

require_login($COURSE);

$dataplusfilehelper = new dataplus_file_helper($dataplus->id, $context);

$temppath = $dataplusfilehelper->get_temp_path();
$imagefileinfo = $dataplusfilehelper->get_image_fileinfo();
$filefileinfo = $dataplusfilehelper->get_file_fileinfo();
$longtextfileinfo = $dataplusfilehelper->get_longtext_fileinfo();
$tozippath = $dataplusfilehelper->get_zip_path();

// Setup a zip archive.
$zipid = $COURSE->id.$USER->id.$dataplus->id;
$zipfilename = 'dataplus'.$dataplus->id.'.zip';
$tozippath = $dataplusfilehelper->get_zip_path();
$zippacker = get_file_packer('application/zip');

$dbfileinfo = $dataplusfilehelper->get_db_fileinfo();

// Make a copy of the database to prepare for download.
$databasefilepath = $tozippath.'/'.'dataplus_db.sqlite';
$dataplusfilehelper->copy($dbfileinfo, $databasefilepath);
$dataplusdb = new sqlite3_db_dataplus(false, $databasefilepath);
$cols = $dataplusdb->list_dataplus_table_columns(true);

// Code for the download of a simple SQLite3 database or a CSV file.
if ($mode == 'simple' || $mode == 'csv') {
    $currentgroup = groups_get_activity_group($cm);

    // If groups are being used, delete columns that are not used by the current group and
    // add files that are part of this group to the archive.

    if ($currentgroup > 0) {
        foreach ($cols as $col) {
            if ($col->group_id != $currentgroup && !empty($col->group_id)) {
                $dataplusdb->delete_dataplus_column($col->id);
            }
        }

        $parameters[0]->name = 'group_id';
        $parameters[0]->value = $currentgroup;
        $parameters[0]->operator = 'notequal';

        $parameters[1]->name = 'group_id';
        $parameters[1]->value = '0';
        $parameters[1]->operator = 'notequal';
        $parameters[1]->andor = 'AND';

        $parameters[2]->name = 'group_id';
        $parameters[2]->value = '';
        $parameters[2]->operator = 'notequal';
        $parameters[2]->andor = 'AND';

        $dataplusdb->delete_dataplus_record($parameters);

    }

    $i = 0;

    // Remove user ids to ensure user identifiable data is not distributed and redundant group_ids.

    $colstorem = array('group_id');

    if (!(has_capability('mod/dataplus:downloadfull', $context, $USER->id))) {
        $colstorem[] = 'creator_id';
        $colstorem[] = 'last_update_id';
    } else {
        $dataplusdb->ids_to_usernames('creator_id');
        $dataplusdb->ids_to_usernames('last_update_id');
    }

    foreach ($cols as $col) {
        if (in_array($col->name, $colstorem)) {
            $dataplusdb->delete_dataplus_column($col->id);
            unset($cols[$i]);
        }

        $i++;
    }

    // Convert dates in the database from seconds from the Unix Epoch to UK date format.
    $dataplusdb->generate_uk_dates();
}

$zipimagepath = $dataplusfilehelper->get_zip_images_path();
$zipfilepath = $dataplusfilehelper->get_zip_files_path();
$ziplongtextpath = $dataplusfilehelper->get_zip_longtext_path();

// Copy files and images to be included.
foreach ($cols as $col) {
    if ($col->form_field_type == 'file' || $col->form_field_type == 'image') {
        $colname = $col->name;
        $filenames = $dataplusdb->query_dataplus_database(array('id', $colname));

        foreach ($filenames as $fn) {
            if (!empty($fn->$colname)) {
                $from['itemid'] = $dataplusfilehelper->get_itemid($colname, $fn->id);

                if ($col->form_field_type == 'image') {
                    $from = array_merge($from, $imagefileinfo);
                    $to = $zipimagepath . "/" . $from['itemid'] . '-' . $fn->$colname;
                } else {
                    $from = array_merge($from, $filefileinfo);
                    $to = $zipfilepath . "/"  . $from['itemid'] . '-' . $fn->$colname;
                }

                $from['filename'] = $fn->$colname;

                $dataplusfilehelper->copy($from, $to);
            }
        }
    } else if ($col->form_field_type == 'longtext') {
        $colname = $col->name;
        $fields = $dataplusdb->query_dataplus_database(array('id', $colname));
        $from = $longtextfileinfo;

        foreach ($fields as $field) {
            preg_match_all('/@@PLUGINFILE@@\/([^"]*)/', $field->$colname,
                            $filenames, PREG_PATTERN_ORDER);
            $from['itemid'] = $dataplusfilehelper->get_itemid($colname, $field->id);

            foreach ($filenames[1] as $fn) {
                $from['filename'] = $fn;
                $to = $ziplongtextpath . "/"  . $from['itemid'] . '-' . $fn;
                $dataplusfilehelper->copy($from, $to);
            }
        }
    }
}

// If csv is selected, generate the csv file.
if ($mode == 'csv') {
    $allrecords = $dataplusdb->query_dataplus_database();
    $outputfilename = $dataplus->id.'.csv';
    $outputpath = $tozippath.'/'.$outputfilename;
    $content = '';

    foreach ($cols as $col) {
        $content .= $col->label . ",";
    }

    $content = substr($content, 0, (strlen($content)-1));
    $content .= "\r\n";

    foreach ($allrecords as $record) {
        $row = '';

        foreach ($record as $field) {
            $field = str_replace(",", "�", $field);
            $row .= $field . ",";
        }

        $row = str_replace("\r\n", "", $row);
        $row = substr($row, 0, (strlen($row)-1));
        $row .= "\r\n";
        $content .= $row;
    }

    fulldelete($tozippath.'/'.$dataplus->id.'.sqlite');
    file_put_contents($outputpath, $content);
} else if ($mode == 'simple') {
    // If simple, drop the supporting tables leaving only the core module data.
    $dataplusdb->drop_table('column');
    $dataplusdb->drop_table('templates');
    $dataplusdb->drop_table('supportinginfo');
}

if (!isset($outputpath)) {
    $outputpath = $databasefilepath;
}

if (!isset($outputfilename)) {
    $outputfilename = $dataplusdb->get_db_file_name();
}

$filetozip['images'] = $zipimagepath;
$filetozip['files'] = $zipfilepath;
$filetozip['longtext'] = $ziplongtextpath;
$filetozip[$outputfilename] = $outputpath;

$zipfileinfo = $dataplusfilehelper->get_zip_fileinfo();
$contextid = $zipfileinfo['contextid'];
$component = $zipfileinfo['component'];
$filearea = $zipfileinfo['filearea'];
$filepath = $zipfileinfo['filepath'];

$zip = $zippacker->archive_to_storage($filetozip,
                               $contextid,
                               $component,
                               $filearea,
                               $zipid,
                               $filepath,
                               $zipfilename);

// Generate the url for the archive and trigger download.
$downloadurl = $dataplusfilehelper->get_zip_file_path($zipfilename, $zipid);

$SESSION->dataplus_file_to_delete = array('filename' => $zipfilename,
                                          'itemid' => $zipid,
                                          'type' => $filearea);
$dataplusdb->clean_up();
$dataplusfilehelper->close();
header('Location:' . $downloadurl);