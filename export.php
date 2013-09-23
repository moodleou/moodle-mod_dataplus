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
 * Generates the export screen.
 * @package mod
 * @subpackage dataplus
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("../../config.php");
require_once("locallib.php");

/**
 * Generate the export screen
 */
function dataplus_export() {
    global $CFG, $id, $context, $USER;

    $strhead = get_string('exportdb', 'dataplus');

    echo "<h2>$strhead</h2>";

    $url = "{$CFG->wwwroot}/mod/dataplus/export_download.php?id={$id}&amp;mode=";

    $strdownload  = get_string('downloaddb', 'dataplus');
    $strdownloadhelp = get_string('downloaddbhelp', 'dataplus');

    echo "<p><a href=\"{$url}simple\">{$strdownload}</a><br/>{$strdownloadhelp}</p>";

    $strdownloadcsv = get_string('downloadcsv', 'dataplus');
    $strdownloadcsvhelp = get_string('downloadcsvhelp', 'dataplus');

    echo "<p><a href=\"{$url}csv\">{$strdownloadcsv}</a><br/>{$strdownloadcsvhelp}</p>";

    if (has_capability('mod/dataplus:downloadfull', $context, $USER->id)) {
        $complexurl = "{$CFG->wwwroot}/mod/dataplus/export_download.php?id={$id}&amp;mode=complex";

        $strdownloadfull = get_string('downloadfulldb', 'dataplus');
        $strdownloadfullhelp = get_string('downloadfulldbhelp', 'dataplus');

        echo "<p><a href=\"{$complexurl}\">{$strdownloadfull}</a><br/>{$strdownloadfullhelp}</p>";
    }
}

dataplus_base_setup('/mod/dataplus/export.php');

$strexp = get_string('export', 'dataplus');
$exportlabel = (empty($dataplus->exporttablabel)) ? $strexp : $dataplus->exporttablabel;

dataplus_page_setup($exportlabel);

$currenttab = 'export';

require_once('tabs.php');

if (has_capability('mod/dataplus:export', $context, $USER->id)) {
    dataplus_export();
} else {
    $url = $CFG->wwwroot.'/mod/dataplus/view.php?mode=view&amp;id='.$id;
    print_error('capablilty_view_export', 'dataplus', $url);
}

echo $OUTPUT->footer();
dataplus_base_close();