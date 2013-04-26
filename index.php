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
 * @package mod
 * @subpackage dataplus
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("../../config.php");
require_once("lib.php");
require_once("$CFG->libdir/rsslib.php");
require_once("$CFG->dirroot/course/lib.php");

$id = required_param('id', PARAM_INT); // Course.
$PAGE->set_url('/mod/dataplus/index.php', array('id'=>$id));

if (! $course = $DB->get_record("course", array('id'=>$id))) {
    print_error("Course ID is incorrect");
}

require_course_login($course);
$context = get_context_instance(CONTEXT_COURSE, $course->id);
$PAGE->set_pagelayout('incourse');
add_to_log($course->id, "dataplus", "view all", "index.php?id=$course->id", "");


$strdataplus = get_string("modulename", "dataplus");
$strname = get_string("name");
$strweek  = get_string("week");
$strtopic = get_string("topic");
$strsize = get_string("size", "dataplus");

$PAGE->navbar->add($strdataplus);

echo $OUTPUT->header();

if (!$datapluss = get_all_instances_in_course("dataplus", $course)) {
    $npath = "../../course/view.php?mode=view&amp;id=$course->id";
    notice(get_string('thereareno', 'moodle', $strdataplus), $npath);
    die;
}

$table = new html_table();

$headings[] = get_string('sectionname', 'format_'.$course->format);
$headings[] = get_string('name');

$table->head = $headings;

$rss = (!empty($CFG->enablerssfeeds) && !empty($CFG->data_enablerssfeeds));

if ($rss) {
    require_once($CFG->libdir."/rsslib.php");
    array_push($table->head, 'RSS');
    array_push($table->align, 'center');
}

$currentsection = "";

foreach ($datapluss as $dataplus) {
    if (!$dataplus->visible && has_capability('moodle/course:viewhiddenactivities',
                                              $context,
                                              $USER->id)) {
        // Show dimmed if the mod is hidden.
        $name = format_string($dataplus->name, true);
        $url = 'view.php?mode=view&amp;id='.$dataplus->coursemodule;
        $link = "<a class=\"dimmed\" href=\"".$url."\">".$name."</a>";
    } else if ($dataplus->visible) {
        // Show normal if the mod is visible.
        $url = 'view.php?mode=view&amp;id='.$dataplus->coursemodule;
        $link = "<a href=\"".$url."\">".format_string($dataplus->name, true)."</a>";
    } else {
        continue;
    }

    $printsection = "";

    if ($dataplus->section !== $currentsection) {
        if ($dataplus->section) {
            $printsection = $dataplus->section;
        }
        $currentsection = $dataplus->section;
    }

    if ($rss && $dataplus->rssarticles > 0) {
        $rsslink = '';
        $rsslink = rss_get_link($course->id, $USER->id, 'data', $data->id, 'RSS');
    }

    $linedata = array ($printsection, $link);

    if ($rss) {
        array_push($row, $rsslink);
    }

    $table->data[] = $linedata;
}

echo "<br />";
echo html_writer::table($table);
echo $OUTPUT->footer();