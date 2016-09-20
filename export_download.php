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
 * @package mod_dataplus
 * @copyright 2015 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

header("Cache-Control: no-cache, must-revalidate");

require_once("../../config.php");
require_once('dataplus_file_helper.php');
require_once('sqlite3_db_dataplus.php');
require_once($CFG->libdir.'/filelib.php');
require_once('exportlib.php');

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

if (!$COURSE = $DB->get_record("course", array("id" => $cm->course))) {
    print_error("Course is misconfigured");
}

if (!$dataplus = $DB->get_record("dataplus", array("id" => $cm->instance))) {
    print_error("Course module is incorrect");
}

require_login($COURSE);

list($zipfilename, $zipid, $filearea, $downloadurl) = dataplus_export_create($mode, $cm);

$SESSION->dataplus_file_to_delete = array('filename' => $zipfilename,
                                          'itemid' => $zipid,
                                          'type' => $filearea);
header('Location:' . $downloadurl);