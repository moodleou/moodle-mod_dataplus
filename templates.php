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
 * Displays screens for managing templates and processes actions.
 * @package mod_dataplus
 * @copyright 2015 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once("../../config.php");
require_once("locallib.php");


/**
 * generates the screen for creating / editing a template
 */
function dataplus_manage_template() {
    global $dataplusdb, $CFG, $id, $SESSION, $mode;

    require_once('template_view_form.php');

    $reset = optional_param('reset', null, PARAM_TEXT);

    if ($reset == 'true') {
        $dataplusdb->delete_template($mode);
    }

    $editor = optional_param('editor', null, PARAM_TEXT);

    if (!empty($editor)) {
        $SESSION->dataplus_use_editor = $editor;
    } else {
        $SESSION->dataplus_use_editor = 'textarea';
    }

    $murl = $CFG->wwwroot.'/mod/dataplus/templates.php?id='.$id.'&mode='.$mode;
    $mform = new dataplus_template_view_form($murl);

    if ($form = $mform->get_data()) {
        if ($mode == 'addrecord') {
            $templatedata = dataplus_resolve_addrecord_form_data($form);
        } else {
            $templatedata = dataplus_resolve_form_data($form);
        }

        if ($dataplusdb->update_template($templatedata)) {
            dataplus_log('templatesaved');
            $msg = get_string('templateupdated', 'dataplus');
        } else {
            $msg = get_string('templateupdatednot', 'dataplus');
        }
        echo '<div class = "dataplus_update_message">'.$msg.'</div>';
    }

    if ($mode == 'addrecord') {
        $formdata = dataplus_get_addrecord_form_values();
    } else {
        $formdata = dataplus_get_form_values();
    }

    $mform->set_data($formdata);

    echo '<div id="dataplus_templateform">';

    $mform->display();

    $editortype = 'editor_' . md5('record');

    echo "<script type=\"text/javascript\" defer=\"defer\">
            if (typeof({$editortype}) != 'undefined') {currEditor = {$editortype}; }
                var ta = document.getElementById('id_record');
                var attr = document.createAttribute('onmouseup');
                ta.setAttributeNode(attr);
                ta.onmouseup = function(){datapluscursorPosition()}
                ta.onkeyup = function(){datapluscursorPosition()}
        </script>";

    echo '</div>';
}


/**
 * gets existing values for a template form
 */
function dataplus_get_form_values() {
    global $mode, $dataplusdb;

    $template = $dataplusdb->get_template($mode);
    $columns = $dataplusdb->list_dataplus_table_columns(true);
    $functions = dataplus_detail_supporting_functions();
    $actions = dataplus_detail_supporting_actions();
    $infos = dataplus_detail_supporting_record_information();

    $defvals = array();

    if (empty($template->record)) {
        $defvals = array('record' => dataplus_get_default_view_template());
    } else {
        foreach ($columns as $column) {
            $name = $column->name;
            $label = $column->label;

            $template->record = str_replace("[[{$name}]]", "[[{$label}]]", $template->record);
            $template->record = str_replace("##{$name}##", "##{$label}##", $template->record);
        }

        foreach ($actions as $action) {
            $name = $action->name;
            $label = $action->label;

            $template->record = str_replace("##{$name}##", "##{$label}##", $template->record);
        }

        foreach ($infos as $i) {
            $name = $i->name;
            $label = $i->label;

            $template->record = str_replace("##{$name}##", "##{$label}##", $template->record);
        }

        $defvals['record'] = $template->record;
    }

    if (empty($template->header)) {
        $defvals['header'] = dataplus_get_default_header();
    } else {
        $defvals['header'] = $template->header;
    }

    if (empty($template->footer)) {
        $defvals['footer'] = dataplus_get_default_footer();
    } else {
        $defvals['footer'] = $template->footer;
    }

    if (empty($template->comments)) {
        $defvals['comments'] = dataplus_get_default_comments();
    } else {
        $defvals['comments'] = $template->comments;
    }

    foreach ($actions as $action) {
        $name = $action->name;
        $label = $action->label;

        $defvals['comments'] = str_replace("**{$name}**", "**{$label}**", $defvals['comments']);
    }

    foreach ($functions as $function) {
        $name = $function->name;
        $label = $function->label;

        $defvals['header'] = str_replace("##{$name}##", "##{$label}##", $defvals['header']);
        $defvals['footer'] = str_replace("##{$name}##", "##{$label}##", $defvals['footer']);
    }

    if (empty($template->css)) {
        $defvals['css'] = dataplus_get_default_css();
    } else {
        $defvals['css'] = $template->css;
    }

    if (!empty($template->js)) {
        $defvals['javascript'] = $template->js;
    }

    if (!empty($template->jsinit)) {
        $defvals['jsinit'] = $template->jsinit;
    }

    if (!empty($template->sortorder)) {
        $orders = explode(",", $template->sortorder);

        for ($i = 0; $i < count($orders); $i++) {
            $orderparts = explode(" ", $orders[$i]);
            $defvals['sortorder' . ($i + 1)] = $orderparts[0];

            if (count($orderparts) == 2) {
                $defvals['sortoption' . ($i + 1)] = $orderparts[1];
            }
        }
    }

    return $defvals;
}


/**
 * get the existing values for an add record template form
 */
function dataplus_get_addrecord_form_values() {
    global $mode, $dataplusdb;

    $template = $dataplusdb->get_template($mode);
    $columns = $dataplusdb->list_dataplus_table_columns(true);

    $defvals = array();

    if (empty($template->record)) {
        $defvals = array('record' => dataplus_get_default_addrecord_template($mode));
    } else {
        foreach ($columns as $column) {
            $name = $column->name;
            $label = $column->label;

            $template->record = str_replace("[[{$name}]]", "[[{$label}]]", $template->record);
        }

        $defvals['record'] = $template->record;
    }

    if (empty($template->css)) {
        $defvals['css'] = dataplus_get_default_css();
    } else {
        $defvals['css'] = $template->css;
    }

    if (!empty($template->js)) {
        $defvals['javascript'] = $template->js;
    }

    if (!empty($template->jsinit)) {
        $defvals['jsinit'] = $template->jsinit;
    }

    return $defvals;
}


/**
 * resolve data from a template form for storage in the database
 * @param object $form
 */
function dataplus_resolve_form_data($form) {
    global $dataplusdb, $mode, $currentgroup;

    $results = array();
    $columns = $dataplusdb->list_dataplus_table_columns(true);
    $functions = dataplus_detail_supporting_functions();
    $actions = dataplus_detail_supporting_actions();
    $infos = dataplus_detail_supporting_record_information();

    foreach ($columns as $column) {
        $name = $column->name;
        $label = $column->label;

        $form->record = str_replace("[[{$label}]]", "[[{$name}]]", $form->record);
        $form->record = str_replace("##{$label}##", "##{$name}##", $form->record);
    }

    foreach ($functions as $function) {
        $name = $function->name;
        $label = $function->label;

        $form->header = str_replace("##{$label}##", "##{$name}##", $form->header);
        $form->footer = str_replace("##{$label}##", "##{$name}##", $form->footer);
    }

    foreach ($actions as $action) {
        $name = $action->name;
        $label = $action->label;

        $form->record = str_replace("**{$label}**", "**{$name}**", $form->record);
    }

    foreach ($infos as $i) {
        $name = $i->name;
        $label = $i->label;

        $form->record = str_replace("##{$label}##", "##{$name}##", $form->record);
    }

    $results[0] = new stdClass();
    $results[0]->name = 'css';
    $results[0]->value = undo_escaping($form->css);

    $results[1] = new stdClass();
    $results[1]->name = 'js';
    $results[1]->value = undo_escaping($form->javascript);

    $results[2] = new stdClass();
    $results[2]->name = 'jsinit';
    $results[2]->value = undo_escaping($form->jsinit);

    $results[3] = new stdClass();
    $results[3]->name = 'header';
    $results[3]->value = undo_escaping($form->header);

    $results[4] = new stdClass();
    $results[4]->name = 'record';
    $results[4]->value = undo_escaping(str_replace('\"', '"', $form->record));

    $results[5] = new stdClass();
    $results[5]->name = 'footer';
    $results[5]->value = undo_escaping($form->footer);

    $results[6] = new stdClass();
    $results[6]->name = 'type';
    $results[6]->value = $mode;

    $results[7] = new stdClass();
    $results[7]->name = 'group_id';
    $results[7]->value = $currentgroup;

    if (dataplus_allow_comments()) {
        $results[8] = new stdClass();
        $results[8]->name = 'comments';
        $results[8]->value = undo_escaping($form->comments);

        foreach ($actions as $action) {
            $name = $function->name;
            $label = $function->label;

            $form->comments = str_replace("**{$label}**", "**{$name}**", $form->comments);
        }
    }

    $n = count($results);
    $results[$n] = new stdClass();
    $results[$n]->name  = 'sortorder';
    $results[$n]->value = dataplus_resolve_sort_order($form);

    return $results;
}


function dataplus_resolve_addrecord_form_data($form) {
    global $dataplusdb, $mode, $currentgroup;

    $results = array();
    $columns = $dataplusdb->list_dataplus_table_columns(true);

    foreach ($columns as $column) {
        $name = $column->name;
        $label = $column->label;

        $form->record = str_replace("[[{$label}]]", "[[{$name}]]", $form->record);
    }

    $results[0] = new stdClass();
    $results[0]->name = 'css';
    $results[0]->value = undo_escaping($form->css);

    $results[1] = new stdClass();
    $results[1]->name = 'js';
    $results[1]->value = undo_escaping($form->javascript);

    $results[2] = new stdClass();
    $results[2]->name = 'jsinit';
    $results[2]->value = undo_escaping($form->jsinit);

    $results[3] = new stdClass();
    $results[3]->name = 'record';
    $results[3]->value = undo_escaping(str_replace('\"', '"', $form->record));

    $results[5] = new stdClass();
    $results[5]->name = 'type';
    $results[5]->value = $mode;

    $results[6] = new stdClass();
    $results[6]->name = 'group_id';
    $results[6]->value = $currentgroup;

    return $results;
}


function dataplus_resolve_sort_order($form) {
    $sortorder = '';

    for ($i = 1; $i <= dataplus_sort_order_limit(); $i++) {
        $sortordername = "sortorder" . $i;
        $sortoptionname = "sortoption" . $i;

        if ($form->$sortordername != 'na') {
            if (strlen($sortorder) > 0) {
                $sortorder .= ',';
            }

            $sortorder .= $form->$sortordername;

            if (isset($form->$sortoptionname)) {
                if ($form->$sortoptionname == 'DESC') {
                    $sortorder .= ' DESC';
                } else {
                    $sortorder .= ' ASC';
                }
            }
        }
    }

    return $sortorder;
}


function dataplus_show_template_instructions() {
    global $OUTPUT;

    echo '<div class="dataplus_instructions">';
    echo get_string('templates_hookstext', 'dataplus');
    echo '</div>';
}

$strtemplates = get_string('templates', 'dataplus');

dataplus_base_setup('/mod/dataplus/templates.php');
dataplus_page_setup($strtemplates, '/mod/dataplus/templates_js.php');

$currenttab = 'templates';

if (empty($mode)) {
    $mode = 'view';
}

require_once('tabs.php');

if (isloggedin() && has_capability('mod/dataplus:databaseedit', $context, $USER->id)) {
    if ($mode == 'hookshelp') {
        dataplus_show_template_instructions();
    } else {
        dataplus_manage_template();
    }
} else {
    $url = $CFG->wwwroot.'/mod/dataplus/view.php?mode=view&amp;id=' . $id;
    print_error('capablilty_edit_template', 'dataplus', $url);
}

echo $OUTPUT->footer();

dataplus_base_close();