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
 * Navigation tabs
 * @package mod
 * @subpackage dataplus
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// This file to be included so we can assume config.php has already been included.
// We also assume that $user, $course, $currenttab have been set.

if (empty($currenttab) or empty($dataplus) or empty($COURSE)) {
    print_error('You cannot call this script in that way');
}

$row  = array();
$tabs = array();

global $groupmode, $currentgroup, $USER;

$editcheck = has_capability('mod/dataplus:databaseedit', $context, $USER->id);
$editowncheck = has_capability('mod/dataplus:dataeditown', $context, $USER->id);
$viewcheck = has_capability('mod/dataplus:view', $context, $USER->id);
$groupcheck = ($groupmode == 0 || groups_is_member($currentgroup));

if (isloggedin() && $editcheck) {
    $url = $CFG->wwwroot.'/mod/dataplus/manage.php?id='.$cm->id;
    $label = get_string('manage_manage', 'dataplus');
    $row[] = new tabobject('manage', $url, $label);

    $url = $CFG->wwwroot.'/mod/dataplus/templates.php?id='.$cm->id;
    $label = get_string('templates', 'dataplus');
    $row[] = new tabobject('templates', $url, $label);
}

if ($viewcheck) {
    if ($dataplus->viewtabvisible !== '0') {
        $url = $CFG->wwwroot.'/mod/dataplus/view.php?mode=view&amp;id='.$cm->id;
        $labeldefault = get_string('view', 'dataplus');
        $labeluser = $dataplus->viewtablabel;
        $label = (empty($labeluser)) ? $labeldefault : $labeluser;
        $row[] = new tabobject('view', $url, $label);
    }

    if ($dataplus->singlerecordtabvisible !== '0') {
        $url = $CFG->wwwroot.'/mod/dataplus/view.php?mode=single&amp;id='.$cm->id;
        $labeldefault = get_string('single_record', 'dataplus');
        $labeluser = $dataplus->singlerecordtablabel;
        $label = (empty($labeluser)) ? $labeldefault : $labeluser;
        $row[] = new tabobject('single', $url, $label);
    }

    if ($dataplus->searchtabvisible !== '0') {
        $url = $CFG->wwwroot.'/mod/dataplus/view.php?id='.$cm->id.'&amp;mode=search';
        $labeldefault = get_string('search', 'dataplus');
        $labeluser = $dataplus->searchtablabel;
        $label = (empty($labeluser)) ? $labeldefault : $labeluser;
        $row[] = new tabobject('search', $url, $label);
    }
}

$tabvisible = $dataplus->addrecordtabvisible;
if ($tabvisible !== '0' && isloggedin() && $editowncheck && ($groupcheck || $editcheck)) {
    $url = $CFG->wwwroot.'/mod/dataplus/view.php?id='.$cm->id.'&amp;mode=insert';
    $row[] = new tabobject('insert', $url, dataplus_get_add_record_label());
}

if ($dataplus->exporttabvisible !== '0' && $viewcheck) {
    $url = $CFG->wwwroot.'/mod/dataplus/export.php?id='.$cm->id;
    $labeldefault = get_string('export', 'dataplus');
    $labeluser = $dataplus->exporttablabel;
    $label = (empty($labeluser)) ? $labeldefault : $labeluser;
    $row[] = new tabobject('export', $url, $label);
}

$tabs[] = $row;

if ($currenttab == 'templates' || $currenttab == 'manage') {
    if ($currenttab == 'templates') {
        $list = array ('view' => 'templates', 'single' => 'templates', 'addrecord' => 'templates', 'hookshelp' => 'templates');
    } else if ($currenttab == 'manage') {
        $list = array ('manage' => 'manage', 'cleardata' => 'manage', 'import' => 'import');
    }

    $row = array();
    $selecttab ='';

    foreach ($list as $l => $page) {
        $tabname = $currenttab.'_'.$l;
        $label = get_string($tabname, 'dataplus');
        $row[] = new tabobject($tabname, $page.".php?id=$id&amp;mode=$l", $label);
    }

    $tabs[] = $row;
}

print_tabs($tabs, $currenttab);