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
 * Serves javascript and CSS from templates.
 * @package mod
 * @subpackage dataplus
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once("../../config.php");
require_once('sqlite3_db_dataplus.php');
require_once('dataplus_file_helper.php');
require_once('locallib.php');

$id = required_param('id', PARAM_INT);
$mode = required_param('mode', PARAM_TEXT);
$type = required_param('type', PARAM_TEXT);
$courseid = required_param('courseid', PARAM_INT);

if (! $cm = get_coursemodule_from_id('dataplus', $id)) {
    print_error("Course Module ID was incorrect");
}

if (! $dataplus = $DB->get_record("dataplus", array("id"=>$cm->instance))) {
    print_error("Course module is incorrect");
}

$context = get_context_instance(CONTEXT_MODULE, $cm->id);

if (! $cm = get_coursemodule_from_id('dataplus', $id)) {
    print_error("Course Module ID was incorrect");
}

if (! $dataplus = $DB->get_record("dataplus", array("id"=>$cm->instance))) {
    print_error("Course module is incorrect");
}

if (! $COURSE = $DB->get_record("course", array("id"=>$courseid))) {
    print_error("Course is misconfigured");
}

$dataplusfilehelper = new dataplus_file_helper($dataplus->id, $context);
$dataplusdb = new sqlite3_db_dataplus();
$template = $dataplusdb->get_template($mode);

if ($type == 'css') {
    header('Content-Type: text/css; charset=utf-8');
}
if (!empty($template)) {
    $template->$type = dataplus_print_template_moodle_information($template->$type);
    print $template->$type;
}

$dataplusdb->clean_up();
$dataplusfilehelper->close();
