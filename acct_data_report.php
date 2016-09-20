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
 * Report showing the use of customised CSS and / or JS in a Moodle instance for OSEP checking.
 * Only for use on ACCT or dev.  Not intended to be used external to the OU.
 * @package mod
 * @subpackage dataplus
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("../../config.php");
require_once('dataplus_file_helper.php');
require_once('sqlite3_db_dataplus.php');

require_login();

class sqlite3_db_acct_report extends sqlite3_db {
    public function get_templates() {
        $columns = array('id', 'header', 'record', 'footer', 'type', 'group_id', 'comments', 'js', 'css', 'jsinit');
        $templates = $this->query_database('templates', $columns);
        return $templates;
    }
}

if (!is_siteadmin()) {
    echo 'This script is only accessible to admin users';
    exit;
}
if (strpos($CFG->wwwroot, 'acct.open.ac.uk') === false &&
        strpos($CFG->wwwroot, 'vledev3.open.ac.uk') === false) {
    echo 'This script is only usable on vledev3 and acct.';
    exit;
}

set_time_limit(10800);

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/mod/dataplus/acct_data_report.php');
echo $OUTPUT->header();

global $dataplusfilehelper, $dataplus;

$sql = "SELECT d.id as id, d.name, d.version, cm.id as cmid, c.shortname
        FROM {dataplus} d
        INNER JOIN {course_modules} cm ON d.id = cm.instance
        INNER JOIN {course} c ON cm.course = c.id
        INNER JOIN {modules} m ON m.id = cm.module
        WHERE m.name = 'dataplus'
        ORDER BY c.shortname, d.name";
$datapluses = $DB->get_records_sql($sql);

$table = new html_table();
$headings = new html_table_row();
$headings->cells = array();
$headings->cells[] = new html_table_cell(html_writer::tag('strong', 'Shortname'));
$headings->cells[] = new html_table_cell(html_writer::tag('strong', 'Dataplus name'));
$headings->cells[] = new html_table_cell(html_writer::tag('strong', 'Custom view'));
$headings->cells[] = new html_table_cell(html_writer::tag('strong', 'Custom single'));
$headings->cells[] = new html_table_cell(html_writer::tag('strong', 'Custom add / edit'));
$table->data = array($headings);

foreach ($datapluses as $dataplus) {
    $context = context_module::instance($dataplus->cmid);

    $row = new html_table_row();
    $row->cells = array();
    $row->cells[0] = new html_table_cell($dataplus->shortname);
    $link = html_writer::link($CFG->wwwroot . '/mod/dataplus/view.php?id=' . $dataplus->cmid, $dataplus->name);
    $row->cells[1] = new html_table_cell($link);

    $dataplusfilehelper = new dataplus_file_helper($dataplus->id, $context);

    try {
        $dataplusdb = new sqlite3_db_acct_report();
    } catch (exception $e) {
        $row->cells[2] = new html_table_cell('This DataPlus instance has thrown an exception (it might be because
            a file is missing on the acct server).  Message is: ' . $e->getMessage());
        $row->cells[2]->colspan = 3;
        $table->data[] = $row;
        continue;
    }

    $templates = $dataplusdb->get_templates();

    if ($templates) {
        foreach ($templates as $template) {
            $defaultcss = file_get_contents($CFG->dirroot . '/mod/dataplus/template_css_' . $template->type . '.css');
            $csschange = false;
            if (!empty($template->css) && $template->css != $defaultcss) {
                $csschange = true;
            }
            $content = 'none';
            if ($csschange && !empty($template->js)) {
                $content = 'CSS & JS';
            } else {
                if ($csschange) {
                    $content = 'CSS';
                } else {
                    if (!empty($template->js)) {
                        $content = 'JS';
                    }
                }
            }
            switch ($template->type) {
                case 'view':
                    $insert = 2;
                    break;
                case 'single':
                    $insert = 3;
                    break;
                case 'addrecord':
                    $insert = 4;
                    break;
            }
            $row->cells[$insert] = new html_table_cell($content);
        }
    }
    // Pad out the row so it looks OK.
    for ($i = 2; $i < 5; $i++) {
        if (!isset($row->cells[$i])) {
            $row->cells[$i] = new html_table_cell('none');
        }
    }
    $table->data[] = $row;
}
echo html_writer::table($table);
echo $OUTPUT->footer();