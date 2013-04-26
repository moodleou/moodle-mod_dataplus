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
 * Displays screens for viewing, searching and editing db data and
 * processing actions.
 * @package mod
 * @subpackage dataplus
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once("../../config.php");
require_once("locallib.php");
require_once($CFG->libdir.'/filelib.php');
require_once($CFG->libdir . '/rsslib.php');
require_once($CFG->libdir . '/completionlib.php');

/**
 * gets the record number to start a page from (for paging)
 *
 * @return int
 *
 */
function dataplus_get_page_start() {
    return optional_param('ps', 0, PARAM_INT);

}


/*
 * Gets the values to include any CSS or JavaScript associated with a template
 *
 * @return string
 */
function dataplus_get_css_and_js($template) {
    global $id, $mode, $CFG, $COURSE;

    if ($mode == 'insert' || $mode == 'insertbelowlimit' || $mode == 'edit') {
        $tmode = 'addrecord';
    } else if (strpos($mode, 'search') !== false) {
        $tmode = 'view';
    } else {
        $tmode = $mode;
    }

    if (isset($template->css)) {
        $template->css = "/mod/dataplus/template_jscss_user.php?courseid=".$COURSE->id.
                            "&type=css&id=".$id."&mode=".$tmode;
    } else {
        $template->css = "/mod/dataplus/template_css_{$tmode}.css";
    }

    if (isset($template->js)) {
        $template->js = "/mod/dataplus/template_jscss_user.php?courseid=".$COURSE->id.
                            "&type=js&id=".$id."&mode=".$tmode;
    } else {
        $template->js = null;
    }

    if (!isset($template->jsinit)) {
        $template->jsinit = null;
    }

    return $template;
}


/**
 * Sets up the page for records view or single view
 *
 * @param object $template
 * @param string $msg
 */
function dataplus_records_page_setup($template, $msg) {
    if (empty($template->header)) {
        $template->header = dataplus_get_default_header();
    }

    if (empty($template->footer)) {
        $template->footer = dataplus_get_default_footer();
    }

    if (dataplus_allow_comments() && empty($template->comments)) {
        $template->comments = dataplus_get_default_comments();
    }

    $template = dataplus_get_css_and_js($template);

    dataplus_view_page_setup($template->js, $template->jsinit, $template->css);

    // Print any message that has been set.
    if (!is_null($msg)) {
        echo "<p>{$msg}</p>";
    }
}


/**
 * Sets up the page for amend record
 *
 * @param object $template
 * @param string $msg
 */
function dataplus_amendrecord_page_setup($template) {
    $cssjs = dataplus_get_css_and_js($template);

    dataplus_view_page_setup($cssjs->js, $cssjs->jsinit, $cssjs->css);
}


/**
 * prints out a set maximum number of records from a database for the 'view' or 'search' screens
 *
 * @param string $msg
 * @param array $parameters
 * @param array $order
 */
function dataplus_view_records($msg = null, $parameters = array(), $order = null) {
    global $dataplusdb, $CFG, $cm, $dataplus, $mode, $id, $USER, $groupmode, $currentgroup;

    dataplus_log('view');
    // Look in the database to see if there is a user created template to use for displaying records.
    $template = $dataplusdb->get_template('view');

    // If no, get an automatically generated one.
    if (empty($template)) {
        $template = new stdClass();
        $template->record = dataplus_get_default_view_template(true);
    }

    if (empty($order) && isset($template->sortorder)) {
        $order = dataplus_create_sortarr_from_str($template->sortorder);
    }

    dataplus_records_page_setup($template, $msg);

    // Print the link for amending a search, if in searchresults mode.
    if ($mode == 'searchresults') {
        $stramend = get_string('amendsearch', 'dataplus');

        echo '<div class="dataplus_search_amend">';
        echo '<a title="'.$stramend.'" href="view.php?id='.$id.'&amp;mode=searchamend">';
        echo $stramend;
        echo '</a></div>';
    }

    // Get the start point for records on the page and the upper limit.
    if ($dataplus->listperpage != -1) {
        $limit['start'] = dataplus_get_page_start();
        $limit['number'] = (int) $dataplus->listperpage;
    } else {
        $limit = null;
    }

    // Get any group restrictions for the parameters for queries.
    $colparams = dataplus_get_restricted_groups_parameters();

    // Merge with any specified search parameters.
    $parameters = array_merge($parameters, $colparams);

    // ...and query the database.
    $results = $dataplusdb->query_dataplus_database(null, $parameters, $limit, $order);

    if (!is_array($results)) {
        $results = array($results);
    }

    // Add any rating info.
    $results = dataplus_add_rating($results);

    if ($mode == 'view' && !empty($dataplus->intro)) {
        print '<div id="dataplus_module_intro">';
        print $dataplus->intro;
        print '</div>';
    }

    // Print a message if the query has returned no results.
    if (empty($results)) {
        if ($mode == 'searchresults') {
            $str = get_string('searchempty', 'dataplus');
        } else {
            $str = get_string('dbempty', 'dataplus');
        }

        echo "<div id=\"dataplus_empty_results\">{$str}</div>";

        return;
    }

    // Print the record result.
    dataplus_print_template_headerfooter_output('header',
                                                $template->header,
                                                $parameters,
                                                $colparams);
    dataplus_print_template_output($template->record, $results);
    dataplus_print_template_headerfooter_output('footer',
                                                $template->footer,
                                                $parameters,
                                                $colparams);
}


 /**
  * prints a single record view
  *
  * @param string $msg
  */
function dataplus_view_single_record($msg = null, $recordid = null) {
    global $dataplusdb, $CFG, $cm, $dataplus, $mode, $id, $USER, $groupmode, $currentgroup;

    dataplus_log('view');

    $changecom = optional_param('changecom', 0, PARAM_INT);

    // Look in the database to see if there is a user created template to use for displaying records.
    $template = $dataplusdb->get_template('single');

    // If no, get an automatically generated one.
    if (empty($template)) {
        $template = new stdClass();
        $template->record = dataplus_get_default_view_template(true);
    }

    dataplus_records_page_setup($template, $msg);

    // Get any group restrictions for the parameters for queries.
    $colparams = dataplus_get_restricted_groups_parameters();

    // Add record id to the query if one is specified.
    if (empty($recordid)) {
        $recordid = optional_param('ri', null, PARAM_INT);
    }

    if (!is_null($recordid)) {
        $parameters[0]->name = 'id';
        $parameters[0]->value = $recordid;
        $parameters[0]->operator ='equals';
        $parameters = array_merge($parameters, $colparams);

        // Get the start point for records on the page and the upper limit.
        $limit['start'] = 0;
    } else {
        $parameters = $colparams;
        $limit['start'] = dataplus_get_page_start();
    }

    $limit['number'] = 1;

    // Find an order from the template if one was specified.
    if (empty($order) && isset($template->sortorder)) {
        $order = dataplus_create_sortarr_from_str($template->sortorder);
    } else {
        $order = null;
    }

    // ...and query the database.
    $results = $dataplusdb->query_dataplus_database(null, $parameters, $limit, $order);

    if (!is_array($results)) {
        $results = array($results);
    }

    // Add any rating info.
    $results = dataplus_add_rating($results);

    if ($changecom == dataplus_get_comment_amend()) {
        dataplus_amend_comment($results[0]->id);
    } else if ($changecom == dataplus_get_comment_delete()) {
        dataplus_delete_comment();
    }

    // Print a message if the query has returned no results.
    if (empty($results)) {
        $str = get_string('dbnotfound', 'dataplus');

        echo "<div id=\"dataplus_empty_results\">{$str}</div>";

        return;
    }

    // Print a message if the record is not accessible to the current group.
    dataplus_check_groups($results[0], false, true);

    // Print the record result.
    dataplus_print_template_headerfooter_output('header', $template->header, $parameters);
    dataplus_print_template_output($template->record, $results);

    if (dataplus_allow_comments()) {
        $cui = $updateid = optional_param('cui', null, PARAM_INT);

        $comments = $template->comments;

        if ($changecom == dataplus_get_comment_form()) {
            dataplus_print_template_comments_output($results[0]->id, $comments);
            dataplus_amend_comment($results[0]->id);
        } else if ($changecom == dataplus_get_comment_edit()) {
            dataplus_print_template_comments_output($comments, $results[0]->id, $cui);
            dataplus_amend_comment($results[0]->id);
            dataplus_print_template_comments_output($comments, $results[0]->id, null, $cui);
        } else if ($changecom == dataplus_get_comment_delete_form()) {
            dataplus_print_template_comments_output($comments, $results[0]->id, $cui);
            dataplus_delete_comment($comments);
            dataplus_print_template_comments_output($comments, $results[0]->id, null, $cui);
        } else {
            dataplus_print_template_comments_output($comments, $results[0]->id);
        }
    }

    dataplus_print_template_headerfooter_output('footer', $template->footer, $parameters);
}


/**
 * Works out which view to go to after a record update
 *
 * @param string $msg
 * @param bool $cancel
 */
function resolve_view($msg = null, $cancel = false) {
    global $mode, $SESSION;

    $returnsearch = optional_param('rs', null, PARAM_TEXT);

    if ($returnsearch == 'true') {
        $mode = 'searchresults';
        $parameters = $SESSION->dataplus_search_parameters;
        $order = $SESSION->dataplus_search_order;

        dataplus_view_records($msg, $parameters, $order);
    } else {
        $oldmode = optional_param('oldmode', 'view', PARAM_TEXT);
        $ri = optional_param('ri', 0, PARAM_INT);

        if ($mode == 'delete' && $oldmode == 'single'  && $ri != 0 && !$cancel) {
            $mode = 'view';
        } else {
            $mode = $oldmode;
        }

        if ($mode == 'single') {
            dataplus_view_single_record($msg);
        } else {
            dataplus_view_records($msg);
        }
    }
}


/**
 * generates screen for adding or editing a record and handles form submission
 */
function dataplus_amend_record() {
    global $dataplusdb, $CFG, $id, $mode, $dataplusfilehelper,
           $currentgroup, $context, $groupmode, $USER;

    // Check to see if there is a max number of records allowed and whether it has been reached.
    $maxentry = dataplus_maximum_entry_limit_reached();

    if (($mode == 'insert' || $mode == 'insertbelowlimit') && $maxentry !== false) {
        dataplus_view_page_setup();

        echo '<p>' . get_string('maxentriesreached', 'dataplus', $maxentry) . '</p>';

        return;
    }

    // Check to see if there is an id for a record to update
    // (must have this for edit mode not to be ignored).
    $updateid = optional_param('ui', null, PARAM_INT);
    $template = $dataplusdb->get_template('addrecord');

    // If no, get an automatically generated one.
    if (empty($template)) {
        $template = new stdClass();
        $template->record = dataplus_get_default_addrecord_template(true);
    }

    $template->record = dataplus_print_template_moodle_information($template->record);

    // Add some parameters that will eventually be used in the update SQL.
    if (!empty($updateid)) {
        $parameters = array(new stdClass());
        $parameters[0]->name = 'id';
        $parameters[0]->value = $updateid;
        $parameters[0]->operator ='equals';

        $prevresult = $dataplusdb->query_dataplus_database_single(null, $parameters);

        // If the current group doesn't have the right to alter the record, return.
        if (!dataplus_check_groups($prevresult, true, true)) {
            print_error(get_string('group_edit_record', 'dataplus'));
        }

        // If the user can't edit the record...
        if (!dataplus_check_capabilities($prevresult->creator_id)) {
            print_error(get_string('capablilty_edit_record', 'dataplus'));
        }
    }

    require_once('record_form.php');

    if (!is_null($updateid)) {
        dataplus_log('update');

        // Find out if the user has come from a search.
        $returnsearch = optional_param('rs', null, PARAM_TEXT);
        $oldmode = optional_param('oldmode', 'view', PARAM_TEXT);
        $ri = optional_param('ri', 0, PARAM_INT);

        $pagestart = dataplus_get_page_start();

        $url = $CFG->wwwroot.'/mod/dataplus/view.php';
        $url .= "?id={$id}&mode={$mode}&ui={$updateid}&ps={$pagestart}&oldmode={$oldmode}";
        if (!empty($ri)) {
            $url .= '&ri='.$ri;
        }

        if ($returnsearch == 'true') {
            $url .= '&rs=true';
        }

        if ($oldmode !='single') {
            $url .= "#{$updateid}";
        }
    } else {
        dataplus_log('insert');

        $url = "{$CFG->wwwroot}/mod/dataplus/view.php?id={$id}&mode={$mode}";
    }

    $mform = new dataplus_record_form($url);

    // Get the fields that will be included in the form taking into account any group restrictions.
    $colparams = dataplus_get_restricted_groups_parameters();
    $columns = $dataplusdb->list_dataplus_table_columns(false, $colparams);

    if (!is_null($updateid)) {
        $mform->define_fields($columns, $mode, $template->record, $prevresult);
    } else {
        $mform->define_fields($columns, $mode, $template->record);
    }

    if ($mform->is_cancelled()) {
        resolve_view();
        return;
    }

    // Handle form submission.
    if (!$mform->is_cancelled() && $form = $mform->get_data()) {
        $textspecialcases = array('file', 'image', 'menumultiple', 'longtext');
        $results = array();

        // Itterate through all the columns included in the form and add data to the results
        // array for use in generating update SQL.

        $i = 0;

        foreach ($columns as $column) {
            $colname = $column->name;
            $filearea = $column->form_field_type;
            $itemid = $dataplusfilehelper->get_itemid($column->name, $updateid);

            if ($column->form_field_type == 'image' || $column->form_field_type == 'file') {
                $draftitemid = file_get_submitted_draft_itemid($column->name);
                $filename = $dataplusfilehelper->get_draft_file_name($draftitemid);

                if ($filename) {
                    if (!isset($results[$i])) {
                        $results[$i] = new stdClass();
                    }
                    $results[$i]->value = $filename;

                    file_save_draft_area_files($draftitemid,
                                               $context->id,
                                               'mod_dataplus',
                                               $filearea,
                                               $itemid);
                }
            }
            // Convert the array for menumultiple fields into suitable from for the database
            // and MoodleForms.
            if ($column->form_field_type == 'menumultiple' && isset($form->$colname)) {
                if (!isset($results[$i])) {
                    $results[$i] = new stdClass();
                }
                $results[$i]->value = '';

                // Uses <<MM>> to divide multiple values, everything else tried upset PHP,
                // or Smarty, or...
                foreach ($form->$colname as $v) {
                    if ($results[$i]->value != '') {
                        $results[$i]->value .= '<<MM>>';
                    }
                    $results[$i]->value .= $v;
                }
            }

            if ($column->form_field_type == 'longtext') {
                $draftitemid = file_get_submitted_draft_itemid($column->name);
                $valuearray = $form->$colname;

                if (!empty($valuearray['itemid'])) {
                    $draftitemid = $valuearray['itemid'];
                }
                if (!isset($results[$i])) {
                    $results[$i] = new stdClass();
                }
                $results[$i]->value = undo_escaping($valuearray['text']);
                $results[$i]->value = file_save_draft_area_files($draftitemid,
                                                                 $context->id,
                                                                 'mod_dataplus',
                                                                 $filearea,
                                                                 $itemid,
                                                                 null,
                                                                 $results[$i]->value);
            }

            // For all other form fields, just add the value to results.
            if (!in_array($column->form_field_type, $textspecialcases) && isset($form->$colname)) {
                if (!isset($results[$i])) {
                    $results[$i] = new stdClass();
                }
                $results[$i]->value = undo_escaping($form->$colname);
            }

            // If a value has been set, add a name.
            if (isset($results[$i])) {
                $results[$i]->name = $column->name;
                $i++;
            }

            // Check for supporting fields for form_field_types that have them
            // and add value to $results.
            if (in_array($column->form_field_type, $dataplusdb->get_combi_fields_types())) {
                $fieldtype = $column->form_field_type;
                $extraname = $dataplusdb->get_supporting_field_name($column->name, $fieldtype);

                if (isset($form->$extraname)) {
                    if (!isset($results[$i])) {
                        $results[$i] = new stdClass();
                    }
                    $results[$i]->name = $extraname;
                    $results[$i]->value = $form->$extraname;
                    $i++;
                }
            }
        }

        // Add group info if applicable.
        if (isset($form->group_id) || is_null($updateid)) {
            $i = count($results);
            $results[$i] = new stdClass();
            $results[$i]->name  = 'group_id';

            if (isset($form->group_id)) {
                $results[$i]->value = $form->group_id;
            } else if (is_null($updateid)) {
                $results[$i]->value = $currentgroup;
            }
        }

        // If there's an update id available, use the results array for an SQL update,
        // otherwise and insert.
        if (!dataplus_flood_control()) {
            if (!is_null($updateid)) {
                $dataplusdb->update_dataplus_record($results, $parameters);
                resolve_view();
                return;
            } else {
                $dataplusdb->insert_dataplus_record($results);
                $msg = "<p>".get_string('recordadded', 'dataplus')."</p>";
            }
        }

        // Set the mode to single and show single record if a save and view button was used.
        if (isset($form->saveandviewbutton)) {
            if ($groupmode > 0 && has_capability('mod/dataplus:databaseedit',
                                                 $context,
                                                 $USER->id)) {
                $groupmode = $form->group_id;
            }
            $mode = 'single';
            dataplus_view_single_record($msg, $dataplusdb->get_user_last_record_id());
            return;
        }

        $_POST = null;
        $mform = new dataplus_record_form($url);
        $mform->define_fields($columns, $mode, $template->record);
    }

    dataplus_amendrecord_page_setup($template);

    // If editing, include previous values in the form.
    if (!is_null($updateid)) {
        $displayvals = new stdClass();
        foreach ($prevresult as $name => $value) {
            // If the field has multiple values, explode by the divider.
            if (strstr($value, '<<MM>>') !== false) {
                $value = explode("<<MM>>", $value);

                for ($i = 0; $i < count($value); $i++) {
                    $value[$i] = dataplus_prepare_value($value[$i]);
                }
            } else {
                $value = dataplus_prepare_value($value);
            }

            $longtext = false;

            foreach ($columns as $col) {
                if ($col->name == $name) {
                    if ($col->form_field_type == 'longtext') {
                        $itemid = $dataplusfilehelper->get_itemid($col->name, $updateid);
                        $draftitemid = 0;
                        $value = file_prepare_draft_area($draftitemid,
                                                $context->id,
                                                'mod_dataplus',
                                                $col->form_field_type,
                                                $itemid,
                                                null,
                                                $value);

                        $value = array('text' => $value,
                                       'format' => editors_get_preferred_format(),
                                       'itemid' => $draftitemid);
                    }
                    break;
                }
            }

            $displayvals->$name = $value;
        }

        $mform->set_data($displayvals);
    }

    if (isset($msg)) {
        print $msg;
    }

    $mform->display();
}


/**
 * generates the screen for deleting a record
 */
function dataplus_delete_record() {
    global $dataplusdb, $CFG, $id, $dataplusfilehelper, $SESSION, $mode;

    dataplus_log('delete');

    require_once('delete_form.php');

    // Get the id of the record to delete.
    $updateid = required_param('ui', PARAM_INT);

    // Find out if the user has come from a search.
    $returnsearch = optional_param('rs', null, PARAM_TEXT);

    // Get the page start so we can take the user back where they came from when a delete is
    // complete or cancelled.
    $pagestart = dataplus_get_page_start();

    // Check the user can delete the record.
    $parameters = array(new stdClass());
    $parameters[0]->name = 'id';
    $parameters[0]->value = $updateid;
    $parameters[0]->operator ='equals';

    $result = $dataplusdb->query_dataplus_database_single(null, $parameters);

    // If the current group doesn't have the right to alter the record, error.
    if (!dataplus_check_groups($result, true, true)) {
        print_error(get_string('group_delete_record', 'dataplus'));
    }

    // If the user can't edit the record...
    if (!dataplus_check_capabilities($result->creator_id)) {
        print_error(get_string('capablilty_delete_record', 'dataplus'));
    }

    // This cures a bug whereby if the current record is the only one on a page and the last in
    // a resultset, the view screen displays a message to see the database is empty because the
    // page_start variable being higher than the number of records causes an empty resultset to
    // be returned.
    if ($returnsearch == 'true') {
        $viewparams = $SESSION->dataplus_search_parameters;
    } else {
        $viewparams = dataplus_get_restricted_groups_parameters();
    }

    $currentrecords = $dataplusdb->count_dataplus_database_query($viewparams);

    if ($pagestart == ($currentrecords-1)) {
        $pagestart = 0;
    }

    $oldmode = optional_param('oldmode', 'view', PARAM_TEXT);
    $ri = optional_param('ri', 0, PARAM_INT);

    $url = $CFG->wwwroot.'/mod/dataplus/view.php';
    $url .= '?id='.$id.'&mode=delete&ui='.$updateid.'&ps='.$pagestart.'&oldmode='.$oldmode;

    if (!empty($ri)) {
        $url .= '&ri='.$ri;
    }
    if (!is_null($returnsearch)) {
        $url .= "&rs=true";
    }

    $mform = new dataplus_delete_form($url);

    // If form submitted and cancelled, return to the view screen.
    if ($mform->is_cancelled()) {
        resolve_view(null, true);
        return;
    }

    // Get details for the columns in the record.
    $columns = $dataplusdb->list_dataplus_table_columns();

    // Get the record as it currently stands.
    $parameters[0]->name     = 'id';
    $parameters[0]->value    = $updateid;
    $parameters[0]->operator = 'equals';

    $result = $dataplusdb->query_dataplus_database_single(null, $parameters);

    // If the group does not have the rights to delete the record, return.
    if (!dataplus_check_groups($result, true, true)) {
        return;
    }

    // If form is submitted then try and delete the record.
    if ($form = $mform->get_data()) {
        // Delete the record, display msg according to success or not.
        if ($dataplusdb->delete_dataplus_record($parameters)) {
            // Check to see if any of the columns are files or images and delete the files from
            // the file system.
            foreach ($result as $name => $value) {
                foreach ($columns as $col) {
                    $name = $col->name;
                    $fieldtype = $col->form_field_type;

                    if ($name == $name && ($fieldtype == 'image' || $fieldtype == 'file')) {
                        $dataplusfilehelper->delete_file($fieldtype, $value, $result->id);
                    }
                }
            }

            $msg = get_string('recorddeleted', 'dataplus');
        } else {
            $msg = get_string('actionfailed', 'dataplus');
        }

        // When the delete is complete, return the user to the view screen, respecting
        // any search results.
        resolve_view($msg);

        return;
    }

    // If no form submission, then display the record to be deleted and the delete form.
    $template = $dataplusdb->get_template($oldmode);

    if (empty($template)) {
        $template = new stdClass();
        $template->record = dataplus_get_default_view_template(true);
    }

    dataplus_view_page_setup();
    echo "<p><strong>" . get_string('deleterecord', 'dataplus') . "</strong></p>";
    dataplus_print_template_output($template->record, $result, true);
    $mform->display();
}


/**
 * generates the screen for searching records (advanced or simple)
 */
function dataplus_search_records() {
    global $dataplusdb, $CFG, $id, $SESSION, $mode, $cm, $id;

    dataplus_log('search');

    require_once('search_form.php');

    $murl = $CFG->wwwroot.'/mod/dataplus/view.php?id='.$id.'&mode='.$mode;
    $mform = new dataplus_search_form($murl);

    // Get the group restriction parameters for use in queries.
    $colparams = dataplus_get_restricted_groups_parameters();

    // Get the columns and supporting fields to be displayed in the search form (taking
    // group restrictions into account).
    $columns = $dataplusdb->list_dataplus_table_columns(false, $colparams);
    $supportingfields = $dataplusdb->detail_content_table_supporting_columns();

    // If we're amending a search, get the search type used previously from the session,
    // otherwise use the mode.
    if ($mode == 'searchamend' &&
        property_exists($SESSION, 'dataplus_formtype') &&
        $SESSION->dataplus_formtype == 'searchadvanced') {
        $formtype = $SESSION->dataplus_formtype;
    } else {
        $formtype = $mode;
    }

    // Set the session form type.
    if ($mode =='search' || $mode == 'searchadvanced') {
        $SESSION->dataplus_formtype = $formtype;
    }

    // Define the fields used in the search form.
    $mform->define_fields($columns, $supportingfields, $cm, $id, $formtype);

    // If the search form is cancelled, go to the view screen.
    if ($mform->is_cancelled()) {
        $mode = 'view';

        dataplus_view_records();

        return;
    }

    $parameters = array();
    $order = array();

    // If a search form has been submitted...
    if ($form = $mform->get_data()) {
        $colnames = $dataplusdb->list_table_columns_names(true, $colparams);
        $datecols = $dataplusdb->list_table_datetime_column_names();

        // Build the parameters to be used in the SQL query.
        foreach ($form as $name => $value) {
            $value = trim($value);
            if (in_array($name, $colnames)  && $value!="null" && $value != '') {
                if (in_array($name, $datecols)) {
                    if ($value == '-3600' || $value == '39600') {
                        continue;
                    }
                }

                $i = count($parameters);
                $parameters[$i] = new stdClass();
                $parameters[$i]->name     = $name;
                $parameters[$i]->value    = $value;

                $specificityname = $name.'gle';

                if (isset($form->$specificityname)) {
                    $parameters[$i]->operator = $form->$specificityname;
                }

                $arrowname = $name.'_arrow';

                if (isset($form->$arrowname)) {
                    $parameters[$i]->operator = $form->$arrowname;
                }
            }
        }

        $i = 0;
        $fname = 'sort' . $i;

        // Build the sort if any is available or used.
        while (isset($form->$fname)) {
            if (!empty($form->$fname)) {
                $sortname = 'sort_options' . $i;

                $order[$form->$fname]->name = $form->$fname;

                if (isset($form->$sortname)) {
                    $order[$form->$fname]->sort = $form->$sortname;
                }
            }

            $i++;
            $fname = 'sort'.$i;
        }

        // Put all the search info in session for use if the search is amended.
        $SESSION->dataplus_search_parameters = $parameters;
        $SESSION->dataplus_search_order = $order;
        $SESSION->dataplus_formdata = $form;

        $mode = 'searchresults';

        // Call the view screen.  The search parameters are used to query the database here.
        dataplus_view_records(null, $parameters, $order);

        return;
    }

    // If no search has been submitted, display the search form.
    if ($mode == 'searchamend' && !empty($SESSION->dataplus_formdata)) {
        $mform->set_data($SESSION->dataplus_formdata);
    }

    dataplus_view_page_setup();
    $mform->display();
}


function dataplus_view_page_setup($js = null, $jsinit = null, $css = null) {
    global $mode, $COURSE, $dataplus, $context, $CFG, $cm;

    $userviewlabel = $dataplus->viewtablabel;
    $defviewlabel = get_string('view', 'dataplus');
    $viewlabel = (empty($userviewlabel)) ? $defviewlabel : $userviewlabel;
    $url = '/mod/dataplus/view.php';
    $qs = dataplus_get_querystring_vars();

    dataplus_page_setup($url, $qs, $viewlabel, $js, $jsinit, $css);

    $group = optional_param('group', null, PARAM_TEXT);
    $oldmode = optional_param('oldmode', 'view', PARAM_TEXT);
    $editingmodes = dataplus_get_edit_modes();

    // If a user hasn't yet submitted enough records, don't let them view the
    // database (as per mod settings).
    if ($mode != 'insertbelowlimit') {
        if ($mode == 'insert'  && is_null($group)) {
            $currenttab = 'insert';
        } else if ((in_array($mode, dataplus_get_search_modes()) ||
                   in_array($oldmode, dataplus_get_search_modes())) &&
                   is_null($group)) {
            $currenttab = 'search';
        } else if ($mode == 'single') {
            $currenttab = 'single';
        } else {
            $currenttab = $oldmode;
        }

        include('tabs.php');
    }
}


/**
 * generates screen for adding or editing a record and handles form submission
 */
function dataplus_amend_comment($rid) {
    global $dataplusdb, $CFG, $id, $dataplus;

    // Check to see if there is an id for a record to update (must have this for
    // edit mode not to be ignored).
    $updateid = optional_param('cui', null, PARAM_INT);
    $ri = optional_param('ri', null, PARAM_INT);

    // Add some parameters that will eventually be used in the update SQL.
    if (!empty($updateid)) {
        $prevresult = $dataplusdb->get_comment($updateid);

        // If the current group doesn't have the right to alter the record, return.
        if (!dataplus_check_groups($prevresult, true, true)) {
            return;
        }
    }

    require_once('comment_form.php');

    if (!is_null($updateid)) {
        dataplus_log('update comment');
    } else {
        dataplus_log('insert comment');
    }

    $url = $CFG->wwwroot.'/mod/dataplus/view.php';
    $url .= '?id='.$id.'&mode=single&changecom='.dataplus_get_comment_amend();
    $url .= '&ps='.dataplus_get_page_start();

    if (!is_null($ri)) {
        $url .= '&ri='.$ri;
    }

    if (!is_null($updateid)) {
        $url .= "&cui=" . $updateid;
    }

    $url .= "#comments";

    $mform = new dataplus_comment_form($url);

    if ($mform->is_cancelled()) {
        return;
    }

    // Handle form submission.
    if ($form = $mform->get_data()) {
        // If there's an update id available, use the results array for an SQL update,
        // otherwise and insert.
        if (!dataplus_flood_control()) {
            if (!is_null($updateid)) {
                $dataplusdb->update_comment($updateid, undo_escaping($form->comment));
            } else {
                $dataplusdb->insert_comment($rid, undo_escaping($form->comment));
            }
        }

        return;
    }

    // If editing, include previous values in the form.
    if (!is_null($updateid)) {
        $displayvals['comment'] = $prevresult->comment;

        $mform->set_data($displayvals);
    }

    $mform->display();
}


/**
 * generates the screen for deleting a record
 */
function dataplus_delete_comment($commenttemplate = null) {
    global $dataplusdb, $CFG, $id, $SESSION, $mode;

    dataplus_log('delete comment');

    require_once('delete_comment_form.php');

    // Get the id of the record to delete.
    $updateid = required_param('cui', PARAM_INT);
    $ri = optional_param('ri', 0, PARAM_INT);
    $url = $CFG->wwwroot.'/mod/dataplus/view.php?';
    $url .= 'id='.$id.'&mode='.$mode.'&cui='.$updateid.'&ps='.dataplus_get_page_start();
    $url .= '&changecom='.dataplus_get_comment_delete();
    if (!empty($ri)) {
        $url .= '&ri='.$ri;
    }
    $url .= '#comments';
    $mform = new dataplus_delete_comment_form($url);

    if (!is_null($commenttemplate)) {
        ob_start();
        dataplus_print_template_comments_output($commenttemplate,
                                                null,
                                                null,
                                                null,
                                                $updateid,
                                                false);
        $mform->message = ob_get_contents();
        ob_end_clean();
    }

    $mform->define_fields();

    // If form submitted and cancelled, return to the view screen.
    if ($mform->is_cancelled()) {
        return;
    }

    if ($form = $mform->get_data()) {
        $dataplusdb->delete_comment($updateid);
        return;
    }

    // Get the record as it currently stands.
    $parameters[0]->name = 'id';
    $parameters[0]->value = $updateid;
    $parameters[0]->operator ='equals';

    $prevresult = $dataplusdb->get_comment($updateid);

    // If the group does not have the rights to delete the record, return.
    if (!dataplus_check_groups($prevresult, true, true)) {
        return;
    }

    // If no form submission, then display the record to be deleted and the delete form.
    $mform->display();
}

dataplus_base_setup();

$group = optional_param('group', 0, PARAM_TEXT);

// According to the mode call the appropriate function, or display an error if the user doesn't
// have correct capabilities.  empty($group) checks to prevent problems with interface when the
// group selected is changed.

$capabilitycheck = (has_capability('mod/dataplus:dataeditown', $context, $USER->id) ||
                    has_capability('mod/dataplus:dataeditothers', $context, $USER->id));
$groupcheck = ($groupmode == 0 || groups_is_member($currentgroup));
$editcheck = has_capability('mod/dataplus:databaseedit', $context, $USER->id);

if (($mode == 'delete' || $mode == 'deletesubmit') && empty($group)) {
    if ($editcheck || ($groupcheck && $capabilitycheck)) {
        dataplus_delete_record();
    } else {
        $url = $CFG->wwwroot.'/mod/dataplus/view.php?mode=view&amp;id='.$id;
        print_error(get_string('capablilty_delete_database', 'dataplus'));
    }
} else if (in_array($mode, $editingmodes) && empty($group)) {
    if ($editcheck || ($groupcheck && $capabilitycheck)) {
        dataplus_amend_record();
    } else {
        $url = $CFG->wwwroot.'/mod/dataplus/view.php?mode=view&amp;id=' . $id;
        print_error(get_string('capablilty_insert_database', 'dataplus'));
    }
} else if (($mode == 'single') && empty($group)) {
    if (has_capability('mod/dataplus:view', $context, $USER->id)) {
        dataplus_view_single_record();
    } else {
        $url = $CFG->wwwroot.'/mod/dataplus/view.php?mode=view&amp;id=' . $id;
        print_error(get_string('capablilty_view_database', 'dataplus'));
    }
} else if (in_array($mode, array('search', 'searchadvanced', 'searchamend')) && empty($group)) {
    if (has_capability('mod/dataplus:view', $context, $USER->id)) {
        dataplus_search_records();
    } else {
        $url = $CFG->wwwroot.'/mod/dataplus/view.php?mode=view&amp;id=' . $id;
        print_error(get_string('capablilty_view_database', 'dataplus'));
    }
} else if ($mode == 'searchresults' && empty($group)) {
    if (has_capability('mod/dataplus:view', $context, $USER->id)) {
        $parameters = $SESSION->dataplus_search_parameters;
        $order = $SESSION->dataplus_search_order;

        dataplus_view_records(null, $parameters, $order);
    } else {
        $url = $CFG->wwwroot.'/mod/dataplus/view.php?mode=view&amp;id=' . $id;
        print_error(get_string('capablilty_view_database', 'dataplus'));
    }
} else {
    if (has_capability('mod/dataplus:view', $context, $USER->id)) {
        $mode = 'view';
        dataplus_view_records();
    } else {
        $url = $CFG->wwwroot.'/mod/dataplus/view.php?mode=view&amp;id=' . $id;
        print_error(get_string('capablilty_view_database', 'dataplus'));
    }
}

echo $OUTPUT->footer();
dataplus_base_close();