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
 * Supporting libraries for DataPlus
 * @package mod
 * @subpackage dataplus
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Page functions.

/**
 * Returns the number of entries in the database and adjust it for pending operations.
 * Used for completion and view limits.  Normally the SQLite3 object count_user_entries() should
 * be used.
 *
 * @return int
 */
function dataplus_get_user_entries_count() {
    global $dataplusdb, $mode;

    $entries = $dataplusdb->count_user_entries();

    // Increase the number if an insert is pending.
    if (!empty($_POST) && ($mode == 'insert' || $mode == 'insertbelowlimit')) {
        $entries++;
    }

    // Decrease the number if a delete is pending.
    if (!empty($_POST) && ($mode == 'delete')) {
        $entries--;
    }

    return $entries;
}


/**
 * Sets up globals used in DataPlus, checks course login and basic $PAGE settings
 * @param string $filepath
 *
 */
function dataplus_base_setup($filepath) {
    require_once('dataplus_file_helper.php');
    require_once('sqlite3_db_dataplus.php');

    global $PAGE, $SESSION, $DB, $id, $mode, $cm, $COURSE, $dataplus, $dataplusfilehelper;
    global $dataplusdb, $context, $currentgroup, $groupmode, $editingmodes;

    $id = required_param('id', PARAM_INT);
    $mode = optional_param('mode', null, PARAM_TEXT);

    if (! $cm = get_coursemodule_from_id('dataplus', $id)) {
        print_error("Course Module ID was incorrect");
    }

    if (! $COURSE = $DB->get_record("course", array("id"=>$cm->course))) {
        print_error("Course is misconfigured");
    }

    if (! $dataplus = $DB->get_record("dataplus", array("id"=>$cm->instance))) {
        print_error("Course module is incorrect");
    }

    $context = get_context_instance(CONTEXT_MODULE, $cm->id);

    $PAGE->set_context($context);
    $PAGE->set_cm($cm);
    $PAGE->set_url($filepath, dataplus_get_querystring_vars());

    require_course_login($COURSE->id, true, $cm);

    // Instantiate the file helper and make sure temp folder is clear.
    $dataplusfilehelper = new dataplus_file_helper($dataplus->id, $context);

    if (isset($SESSION->dataplus_file_to_delete)) {
        $todelete = $SESSION->dataplus_file_to_delete;
        $type = $todelete['type'];
        $filename = $todelete['filename'];
        $itemid = $todelete['itemid'];
        $dataplusfilehelper->delete_file($type, $filename, $itemid);
        unset($SESSION->dataplus_file_to_delete);
    }

    // Check whether we need to lock the database by seeing if any data has been
    // submitted and it's not a search.
    if (empty($_POST) || strstr($mode, 'search')) {
        $lock = false;
    } else {
        $lock = true;
    }

    // Instantiate the SQLite3 database helper.
    $dataplusdb = new sqlite3_db_dataplus($lock);

    $currentgroup = groups_get_activity_group($cm, true);

    if (empty($currentgroup)) {
        $currentgroup = 0;
    }

    $groupmode = groups_get_activity_groupmode($cm);
    $editingmodes = dataplus_get_edit_modes();
}


/**
 * Closes the objects instantiated in setup
 *
 */
function dataplus_base_close() {
    global $dataplusdb, $dataplusfilehelper;

    $dataplusdb->clean_up();
    $dataplusdb = null;

    if (!empty($dataplusfilehelper)) {
        $dataplusfilehelper->close();
    }
}


/**
 * Sets up the main globals used in dataplus and prints the header.
 * Also controls screens when a database needs setup actions
 */
function dataplus_page_setup($pagetitle,
                             $js=null,
                             $jsinit=null,
                             $css=null) {
    global $PAGE, $id, $mode, $cm, $COURSE, $dataplus, $CFG, $OUTPUT;

    $PAGE->set_title($pagetitle);
    $PAGE->set_heading($COURSE->fullname);
    $PAGE->requires->js('/mod/dataplus/dataplus.js');

    if (!is_null($js)) {
        $PAGE->requires->js($js);
    }

    // Replace Moodle information hooks.
    $jsinit = dataplus_print_template_moodle_information($jsinit);

    if (!is_null($jsinit)) {
        $PAGE->requires->js_init_call($jsinit);
    }

    if (!is_null($css)) {
        $PAGE->requires->css($css);
    }

    $fid = optional_param('fid', null, PARAM_TEXT);

    $completion = new completion_info($COURSE);
    $completion->set_module_viewed($cm);

    echo $OUTPUT->header();

    dataplus_check_availability();

    dataplus_check_setup();

    dataplus_required_entries_reminders();

    $scriptpath = substr($_SERVER["SCRIPT_NAME"], strpos($_SERVER["SCRIPT_NAME"], 'mod/dataplus'));
    $url = $CFG->wwwroot.'/'.$scriptpath.'?&amp;mode='.$mode.'&amp;id='.$id;

    if (!empty($fid)) {
        $url .= "&amp;fid=".$fid;
    }

    // Print the groups menu.
    $groups = groups_print_activity_menu($cm, $url, true);

    if (strlen($groups) > 0) {
        echo $groups;
        echo "<br/>";
    }

    $OUTPUT->heading(format_string($dataplus->name));
}


/**
 * Checks to see if a database is available for use according to the available
 * from and to module settings. Users with editing capability can always see it.
 */
function dataplus_check_availability() {
    global $context, $dataplus, $OUTPUT, $USER;

    if (has_capability('mod/dataplus:databaseedit', $context, $USER->id)) {
        return;
    }

    if (!empty($dataplus->timeavailablefrom) && ($dataplus->timeavailablefrom > time())) {
        $timeavailablefrom = date('l, j F Y, g:i A', $dataplus->timeavailablefrom);
        dataplus_print_stop_message(get_string('availableyet', 'dataplus', $timeavailablefrom));
    }

    if (!empty($dataplus->timeavailableto) && ($dataplus->timeavailableto < time())) {
        $timeavailableto = date('l, j F Y', $dataplus->timeavailableto);
        dataplus_print_stop_message(get_string('availablepast', 'dataplus', $timeavailableto));
    }

}


/**
 * Print a message and stop output
 * @param string $msg
 */
function dataplus_print_stop_message($msg) {
    global $OUTPUT;
    echo '<p>'.$msg.'</p>';
    echo $OUTPUT->footer();
    dataplus_base_close();
    exit;
}


/**
 * Check if the database still needs to be setup or imported and print a message if it does.
 */
function dataplus_check_setup() {
    global $dataplusdb, $mode, $cm, $CFG, $OUTPUT, $context, $USER;

    if ($dataplusdb->unused_database() && $mode != 'dbsetup' && $mode != 'dbmanage') {
        echo '<p>'.get_string('databasenotsetup', 'dataplus').'</p>';

        if (isloggedin() && has_capability('mod/dataplus:databaseedit', $context, $USER->id)) {
            $url = $CFG->wwwroot.'/mod/dataplus/manage.php?id='.$cm->id.'&amp;mode=dbsetup';

            echo '<p><a href="'.$url.'">'.get_string('databasesetup', 'dataplus').'</a></p>';

            $url = $CFG->wwwroot.'/mod/dataplus/import.php?id='.$cm->id.'&amp;mode=dbsetup';

            echo '<p><a href="'.$url.'">'.get_string('importdb', 'dataplus').'</a></p>';
        }

        echo $OUTPUT->footer();
        dataplus_base_close();
        exit;
    }
}


/**
 * Check with required entries reminders need to be printed.
 */
function dataplus_required_entries_reminders() {
    global $dataplus, $mode, $context, $COURSE, $cm, $USER, $CFG, $OUTPUT;

    if ($dataplus->requiredentries > 0 && has_capability('mod/dataplus:dataeditown',
                                                         $context,
                                                         $USER->id)) {
        $entries = dataplus_get_user_entries_count();
        $completion = new completion_info($COURSE);

        if ($entries < $dataplus->requiredentries) {
            $additions = new stdClass;
            $additions->total = $dataplus->requiredentries;
            $additions->created = $entries;

            $completion->update_state($cm, COMPLETION_INCOMPLETE, $USER->id);
            echo '<p>'.get_string('requiredentriesreminder', 'dataplus', $additions).'</p>';
        } else {
            $completion->update_state($cm, COMPLETION_COMPLETE, $USER->id);
        }
    }

    // Check whther a requiredentriestoview reminder needs to be printed.
    $editowncheck = has_capability('mod/dataplus:dataeditown', $context, $USER->id);
    $editdbcheck = has_capability('mod/dataplus:databaseedit', $context, $USER->id);

    if (!$editdbcheck && $dataplus->requiredentriestoview > 0 && $editowncheck) {
        if (!isset($entries)) {
            $entries = dataplus_get_user_entries_count();
        }

        if ($entries < $dataplus->requiredentriestoview) {
            $additions->total = $dataplus->requiredentriestoview;
            $additions->created = $entries;

            echo '<p>'.get_string('requiredentriestoviewreminder', 'dataplus', $additions).'</p>';
            $url = $CFG->wwwroot.'/mod/dataplus/view.php';
            $qs = '?id=' . $cm->id . '&amp;mode=insertbelowlimit';

            if ($mode != 'insertbelowlimit') {
                echo '<p><a href="'.$url.$qs.'">'.get_string('addrecord', 'dataplus').'</a></p>';
                echo $OUTPUT->footer();
                dataplus_base_close();
                exit;
            }
        } else if ($mode == 'insertbelowlimit') {
            $mode = 'insert';
            $no = $dataplus->requiredentriestoview;
            echo '<p>'.get_string('requiredentriestoviewdone', 'dataplus', $no).'</p>';
        }
    }
}


// Group functions.


/**
 * Check the rights of a group to an item.  By default, checks are for read only view.
 * An error message can be printed if specified
 *
 * @param obj $item
 * @param boolean $editing - if set to false function checks 'read only' rights for a record
 * @param boolean $printerror
 * @return boolean
 */
function dataplus_check_groups($item, $editing = false, $printerror = false) {
    global $currentgroup, $groupmode, $id, $CFG, $context, $USER;

    $itemgroupid = (int) $item->group_id;

    if ((!$editing && ($groupmode == 0 ||
                      $currentgroup == 0 ||
                      $itemgroupid == 0 ||
                      $itemgroupid == '' ||
                      $groupmode == 2 ||
                      $currentgroup == $itemgroupid)) ||
        ($editing && ($groupmode == 0 ||
                      $itemgroupid == 0 ||
                      $itemgroupid == '' ||
                      groups_is_member($currentgroup) ||
                      has_capability('mod/dataplus:databaseedit', $context, $USER->id)))) {
        return true;
    }

    if ($printerror) {
        $url = $CFG->wwwroot.'/mod/dataplus/view.php?mode=view&amp;id=' . $id;
        print_error(get_string('groupmember', 'dataplus'), $url);
    }

    return false;
}


/**
 * Check a user can edit a record from the creator_id of the record
 *
 * @param int $creatorid
 * @return boolean
 */
function dataplus_check_capabilities($creatorid) {
    global $USER, $context;

    $dbedit = has_capability('mod/dataplus:databaseedit', $context, $USER->id);
    $dbeditown = has_capability('mod/dataplus:dataeditown', $context, $USER->id);
    $dbeditothers = has_capability('mod/dataplus:dataeditothers', $context, $USER->id);

    if ($dbedit || ($dbeditown && $USER->id == $creatorid) || $dbeditothers) {
        return true;
    }

    return false;
}


/**
 * Returns the parameters required when making a records query that will only include results
 * from a particular group
 *
 * @param array $parameters
 * @return array
 */
function dataplus_get_restricted_groups_parameters($parameters = array()) {
    global $groupmode, $currentgroup;

    if ($groupmode == 0) {
        return $parameters;
    }

    $i = count($parameters);

    $parameters[$i] = new stdClass();
    $parameters[$i]->sub = array(new stdClass(), new stdClass(), new stdClass());
    $parameters[$i]->sub[0]->name = 'group_id';
    $parameters[$i]->sub[0]->value = $currentgroup;
    $parameters[$i]->sub[0]->operator = 'equals';

    $parameters[$i]->sub[1]->name = 'group_id';
    $parameters[$i]->sub[1]->value = '0';
    $parameters[$i]->sub[1]->operator = 'equals';
    $parameters[$i]->sub[1]->andor = 'OR';

    $parameters[$i]->sub[2]->name = 'group_id';
    $parameters[$i]->sub[2]->value = '';
    $parameters[$i]->sub[2]->operator = 'equals';
    $parameters[$i]->sub[2]->andor = 'OR';

    return $parameters;
}


/**
 * Returns the name of a group from it's id (including 'all participants' if group is 0
 *
 * @param int $id
 * @return string
 */
function dataplus_get_group_name($id) {
    if ($id == 0) {
        $name = get_string('allparticipants');
    } else {
        $group = groups_get_group($id);
        $name = $group->name;
    }

    return $name;
}


// Log functions.


/**
 * Adds an action in the module to the logs table
 *
 * @param string $action
 */
function dataplus_log($action) {
    global $COURSE, $USER, $cm;

    if (is_null($cm)) {
        return;
    }

    add_to_log($COURSE->id, 'dataplus', $action, $_SERVER['REQUEST_URI'], '', $cm->id, $USER->id);
}


// Template helper functions.


/**
 * Get a default presentation template.  Includes all the relevant fields with
 * labels plus edit and delete.  Includes the labels as identifiers unless
 * $usenames is true.
 *
 * @param boolean $usenames
 * @return string
 */
function dataplus_get_default_view_template($usenames = false) {
    global $dataplusdb;

    $parameters = dataplus_get_restricted_groups_parameters();
    $fields = $dataplusdb->list_dataplus_table_columns(false, $parameters);

    if (count($fields)==0) {
        return '';
    }

    $html = '<table class="record_template">';

    foreach ($fields as $field) {
        if ($usenames) {
            $hook = $field->name;
        } else {
            $hook = $field->label;
        }

        $html .= "<tr>";
        $html .= "<td><strong>{$field->label}</strong></td>";
        $html .= "<td>";
        $html .= "[[{$hook}]]";
        $html .= "</td></tr>";
    }

    $html .= "<tr>";
    $html .= '<td colspan="2">';

    foreach (dataplus_detail_supporting_actions() as $action) {
        $html .= '**'.$action->label.'**';
    }

    $html .= '</td>';
    $html .= "</tr>";
    $html .= "</table>";

    return $html;
}


/**
 * Get a default header for a template.
 *
 * @return string
 */
function dataplus_get_default_header() {
    return <<<EOF
<div class="dataplus_record_navigation">##record_navigation##</div>
<div class="dataplus_record_count">##record_count##</div>
EOF;
}


/**
 * Get a default footer for a template.
 *
 * @return string
 */
function dataplus_get_default_footer() {
    $html = '';

    if (dataplus_allow_comments()) {
        $html .= '<div class="dataplus_add_comment">##add_comment##</div>';
    }

    $html .= <<<EOF
<div class="dataplus_record_count">##record_count##</div>
<div class="dataplus_record_navigation">##record_navigation##</div>
EOF;

    return $html;
}


/**
 * Get default CSS for a template.
 *
 * @return string
 */
function dataplus_get_default_css() {
    global $CFG, $mode;

    return file_get_contents($CFG->dirroot . '/mod/dataplus/template_css_'.$mode.'.css');
}


/**
 * Get the default comments template.
 *
 * @return string
 */
function dataplus_get_default_comments() {
    return <<<EOF
<div class="dataplus_comment">
    <table class="dataplus_comment_output">
        <tr>
            <td class="dataplus_creator_details">##creator## ##created_time##</td>
        </tr>
        <tr>
           <td class="dataplus_comment_cell">[[comment]]</td>
        </tr>
        <tr>
            <td class="dataplus_comment_actions">**edit** **delete**</td>
        </tr>
    </table>
</div>
EOF;
}


/**
 * Get a default add record template.  Includes all the relevant fields.
 * Includes the labels as identifiers unless $usenames is true.
 *
 * @param boolean $usenames
 * @return string
 */
function dataplus_get_default_addrecord_template($usenames = false, $mode = 'addrecord') {
    global $dataplusdb;

    $parameters = dataplus_get_restricted_groups_parameters();
    $fields = $dataplusdb->list_dataplus_table_columns(false, $parameters);

    if (count($fields)==0) {
        return '';
    }

    $html = '';

    foreach ($fields as $field) {
        if ($usenames) {
            $hook = $field->name;
        } else {
            $hook = $field->label;
        }

        $html .= "[[{$hook}]]";
    }

    $html .= '**save****cancel**';

    return $html;
}


/**
 * get details of supporting functions for template headers
 *
 * @return array
 */
function dataplus_detail_supporting_functions() {
    $functions = array(new stdClass());
    $functions[0]->name = 'record_count';
    $functions[0]->label = get_string('record_count', 'dataplus');
    $functions[1] = new stdClass();
    $functions[1]->name = 'record_navigation';
    $functions[1]->label = get_string('record_navigation', 'dataplus');

    return $functions;
}


/**
 * get details of supporting actions for record templates
 *
 * @return array
 */
function dataplus_detail_supporting_actions() {
    $actions = array(new stdClass());
    $actions[0]->name = 'edit';
    $actions[0]->label = get_string('edit', 'dataplus');
    $actions[1] = new stdClass();
    $actions[1]->name = 'delete';
    $actions[1]->label = get_string('delete', 'dataplus');

    return $actions;
}


/**
 * get details of supporting record information
 *
 * @return array
 */
function dataplus_detail_supporting_record_information() {
    $info = array(new stdClass());
    $info[0]->name = 'record_no';
    $info[0]->label = get_string('record_no', 'dataplus');

    return $info;
}


/**
 * get details of Moodle information - data returned is for the current user only.
 */
function dataplus_get_moodle_information() {
    return array('username',
                 'firstname',
                 'lastname',
                 'courseid',
                 'courseshortname',
                 'coursename',
                 'roles',
                 'rolesjs',
                 'groupids',
                 'groupidsjs',
                 'groupnames',
                 'groupnamesjs');
}


/**
 * Returns HTML for template menu
 *
 * @return string
 */
function dataplus_get_template_menu_html($extraclass) {
    return <<<EOF
<div class="dataplus_templatemenu {$extraclass}">
    <ul>
        [[template content]]
    </ul>
</div>
EOF;
}


/**
 * Prints the record menu used on the template screen
 *
 * @param string $mode
 * @return string
 */
function dataplus_get_template_record_menu($mode = null) {
    global $dataplusdb;

    $parameters = dataplus_get_restricted_groups_parameters();
    $columns = $dataplusdb->list_dataplus_table_columns(false, $parameters);

    $straddfields = get_string('addfieldstorecord', 'dataplus');
    $straddactions = get_string('addactionstorecord', 'dataplus');
    $straddadditional = get_string('addadditionaltorecord', 'dataplus');
    $straddinfo = get_string('addinfo', 'dataplus');

    $class = 'dataplus_template_record';

    $html = "<li><strong>{$straddfields}</strong></li>";

    foreach ($columns as $column) {
        $label = $column->label;
        $js = "dataplusUpdateTextbox('[[{$label}]]','id_record'); return false;";
        $html .= '<li><a onclick="'.$js.'" href="#">[['.$label.']]</a></li>';
    }

    $html .= "<li><br/><strong>{$straddactions}</strong></li>";

    $actions = dataplus_detail_supporting_actions();

    if ($mode == 'view') {
        $actionssize = count($actions);

        $actions[$actionssize] = new stdClass();
        $actions[$actionssize]->name  = 'more';
        $actions[$actionssize]->label = get_string('more', 'dataplus');
    }

    $actionssize = count($actions);
    $actions[$actionssize] = new stdClass();
    $actions[$actionssize]->name  = 'rate';
    $actions[$actionssize]->label = get_string('rate', 'dataplus');

    if ($mode == 'addrecord') {
        $js = "dataplusUpdateTextbox('**save**','id_record'); return false;";
        $html .= '<li><a onclick="'.$js.'" href="#">**save**</a></li>';
        $js = "dataplusUpdateTextbox('**saveandview**','id_record'); return false;";
        $html .= '<li><a onclick="'.$js.'" href="#">**saveandview**</a></li>';
        $js = "dataplusUpdateTextbox('**reset**','id_record'); return false;";
        $html .= '<li><a onclick="'.$js.'" href="#">**reset**</a></li>';
        $js = "dataplusUpdateTextbox('**cancel**','id_record'); return false;";
        $html .= '<li><a onclick="'.$js.'" href="#">**cancel**</a></li>';

        return str_replace('[[template content]]', $html, dataplus_get_template_menu_html($class));
    }

    foreach ($actions as $action) {
        $label = $action->label;
        $js = "dataplusUpdateTextbox('**{$label}**','id_record'); return false;";
        $html .= '<li><a onclick="'.$js.'" href="#">**'.$label.'**</a></li>';
    }

    $html .= "<li><br/><strong>{$straddadditional}</strong></li>";

    $supportingcols = $dataplusdb->detail_content_table_supporting_columns();

    foreach ($supportingcols as $column) {
        if ($column->label == '') {
            continue;
        }

        $label = $column->label;
        $js = "dataplusUpdateTextbox('##{$label}##','id_record'); return false;";
        $html .= '<li><a onclick="'.$js.'" href="#">##'.$label.'##</a></li>';
    }

    foreach (dataplus_detail_supporting_record_information() as $info) {
        $label = $info->label;
        $js = "dataplusUpdateTextbox('##{$label}##','id_record'); return false;";
        $html .= '<li><a onclick="'.$js.'" href="#">##'.$label.'##</a></li>';
    }

    return str_replace('[[template content]]', $html, dataplus_get_template_menu_html($class));
}


/**
 * Prints the comment menu used on the template screen
 *
 * @return string
 */
function dataplus_get_template_comments_menu() {
    global $dataplusdb;

    $strcomment = get_string('comment', 'dataplus');
    $straddcomment = get_string('addcomment', 'dataplus');
    $straddactions = get_string('addactionstorecord', 'dataplus');
    $straddadditional = get_string('addadditionaltorecord', 'dataplus');

    $html = "<li><strong>{$straddcomment}</strong></li>";
    $js = "dataplusUpdateTextbox('[[{$strcomment}]]','id_comments'); return false;";
    $html .= '<li><a onclick="'.$js.'" href="#">[['.$strcomment.']]</a></li>';
    $html .= "<li><br/><strong>{$straddactions}</strong></li>";

    foreach (dataplus_detail_supporting_actions() as $action) {
        $label = $action->label;
        $js = "dataplusUpdateTextbox('**{$label}**','id_comments'); return false;";
        $html .= '<li><a onclick="'.$js.'" href="#">**'.$label.'**</a></li>';
    }

    $html .= "<li><br/><strong>{$straddadditional}</strong></li>";

    $supportingcols = $dataplusdb->define_editor_columns();

    foreach ($supportingcols as $column) {
        if ($column->label == '') {
            continue;
        }

        $label = $column->label;
        $js = "dataplusUpdateTextbox('##{$label}##','id_comments'); return false;";
        $html .= '<li><a onclick="'.$js.'" href="#">##'.$label.'##</a></li>';
    }

    $class = 'dataplus_template_comment';
    return str_replace('[[template content]]', $html, dataplus_get_template_menu_html($class));
}


/**
 * Prints the header/footer menu used on the template screen
 *
 * @return string
 */
function dataplus_get_template_headerfooter_menu($position) {
    $straddrecordfunctions = get_string('addrecordfunctions', 'dataplus');

    $html = "<li><strong>{$straddrecordfunctions}</strong></li>";

    foreach (dataplus_detail_supporting_functions() as $function) {
        if ($function->label == '') {
            continue;
        }

        $label = $function->label;
        $js = "dataplusUpdateTextbox('##{$label}##','id_{$position}'); return false;";
        $html .= '<li><a onclick="'.$js.'" href="#">##'.$label.'##</a></li>';
    }

    $menu = dataplus_get_template_menu_html('dataplus_template_headerfooter');

    return str_replace('[[template content]]', $html, $menu);
}


/**
 * Removes any escapes, etc, from values to be displayed
 *
 * @param string $value
 * @return string
 */
function dataplus_prepare_value($value) {
    // Replace the muliple value divider with line breaks.
    $value = str_replace('<<MM>>', '<br/>', $value);
    $value = str_replace("\'", "'", $value);
    return $value;
}


/**
 * Prints Moodle info into template for output
 * @param string $template - the template with any hooks for Moodle info
 * @return string the content of $template with hooks replaced with Moodle info
 */
function dataplus_print_template_moodle_information($template) {
    global $USER, $COURSE, $context;

    $template = str_replace('--username--', $USER->username, $template);
    $template = str_replace('--firstname--', $USER->firstname, $template);
    $template = str_replace('--lastname--', $USER->lastname, $template);
    $template = str_replace('--courseid--', $COURSE->id, $template);
    $template = str_replace('--coursename--', $COURSE->fullname, $template);
    $template = str_replace('--courseshortname--', $COURSE->shortname, $template);

    // Create a comma delimited list of user's roles and JS array of user's roles if needed.
    if (strpos($template, '--role') !== false) {
        $roles = get_user_roles($context);
        $rolescomma = '';
        $rolesjs = 'new Array(';
        foreach ($roles as $role) {
            if (!empty($rolescomma)) {
                $rolescomma .= ',';
                $rolesjs .= ', ';
            }
            $rolescomma .= $role->name;
            $rolesjs .= '"'.$role->name.'"';
        }
        $rolesjs .= ')';
        $template = str_replace('--roles--', $rolescomma, $template);
        $template = str_replace('--rolesjs--', $rolesjs, $template);
    }

    // Create comma delimited lists of groupids and groupnames and JS arrays of the same if needed.
    if (strpos($template, '--group') !== false) {
        $namecheck = (strpos($template, '--groupnames') !== false);
        $usergroups = groups_get_user_groups($COURSE->id);
        $groupids = '';
        $groupidsjs = 'new Array(';
        $groupnames = '';
        $groupnamesjs = 'new Array(';
        foreach ($usergroups[0] as $group) {
            if (!empty($groupids)) {
                $groupids .= ',';
                $groupidsjs .= ', ';
                $groupnames .= ',';
                $groupnamesjs .= ', ';
            }
            $groupids .= $group;
            $groupidsjs .= $group;
            if ($namecheck) {
                $groupname = groups_get_group_name($group);
                $groupnames .= $groupname;
                $groupnamesjs .= '"'.$groupname.'"';
            }
        }
        $groupidsjs .= ')';
        $groupnamesjs .= ')';
        $template = str_replace('--groupids--', $groupids, $template);
        $template = str_replace('--groupidsjs--', $groupidsjs, $template);
        if ($namecheck) {
            $template = str_replace('--groupnames--', $groupnames, $template);
            $template = str_replace('--groupnamesjs--', $groupnamesjs, $template);
        }
    }

    return $template;
}

/**
 * Takes a results of a database query and a template and prints the output for each record.
 * $clearactions can be used to remove edit and delete links if required.
 *
 * @param string $template
 * @param array/obj $results
 * @param boolean $clearactions
 */
function dataplus_print_template_output($template, $results, $clearactions = false) {
    global $dataplusdb, $dataplusfilehelper, $context;

    $parameters = dataplus_get_restricted_groups_parameters();
    $cols = $dataplusdb->list_dataplus_table_columns(true, $parameters);

    if (count($cols) == 0) {
        return;
    }

    $rec = 1;

    if (!is_array($results)) {
        $results = array($results);
    }

    $template = dataplus_print_template_moodle_information($template);

    foreach ($results as $result) {
        $recordtemplate = $template;

        $cgroups = dataplus_check_groups($result, true);
        $ccapabilitites = dataplus_check_capabilities($result->creator_id);

        if (!$clearactions && $cgroups && $ccapabilitites) {
            $finalclearactions = false;
        } else {
            $finalclearactions = true;
        }

        foreach (dataplus_detail_supporting_actions() as $action) {
            $recordtemplate = dataplus_create_record_icon($action->name,
                                                          $action->label,
                                                          $recordtemplate,
                                                          $result->id,
                                                          $result->creator_id,
                                                          $finalclearactions);
        }

        $recordtemplate = dataplus_create_more_link($result->id, $recordtemplate);
        $recordtemplate = dataplus_create_rating($result, $recordtemplate);

        foreach (dataplus_detail_supporting_record_information() as $info) {
            $name = $info->name;
            $recordtemplate = dataplus_create_supporting_information($name, $rec, $recordtemplate);
        }

        foreach ($result as $name => $value) {
            if ($name == 'moodledprating') {
                continue;
            }
            $formfieldtype = null;
            $type = null;
            $value = dataplus_prepare_value($value);

            foreach ($cols as $col) {
                if ($col->name == $name) {
                    $formfieldtype = $col->form_field_type;
                    $type = $col->type;
                    break;
                }
            }

            // As well as checking $formfieldtype is set, this has the effect of
            // stoping fields being printed if not in the correct group.
            if (empty($type)) {
                $recordtemplate = str_replace("[[{$name}]]", '', $recordtemplate);
                $recordtemplate = str_replace("##{$name}##", '', $recordtemplate);
                continue;
            }

            if ($formfieldtype == 'date' && !empty($value)) {
                $value = date('d F Y', $value);
            } else if (($formfieldtype == 'datetime' ||
                        (empty($formfieldtype) && $type == 'date')) &&
                        !empty($value)) {
                $value = date('d F Y H:i', $value);
            } else if ($formfieldtype == 'image' && !empty($value)) {
                $path = $dataplusfilehelper->get_image_file_path($value, $result->id, $name);

                $altname = $dataplusdb->get_supporting_field_name($name, $formfieldtype);

                foreach ($result as $n => $v) {
                    if ($n == $altname) {
                        $alt = $v;
                        break;
                    }
                }

                $value = "<img src=\"{$path}\" alt=\"{$alt}\" title=\"{$alt}\" />";
            } else if ($formfieldtype == 'file' && !empty($value)) {
                $path = $dataplusfilehelper->get_file_file_path($value, $result->id, $name);
                $value = "<a href=\"{$path}\">".$value."</a>";
            } else if ($formfieldtype == 'url' && !empty($value)) {
                $labelname = $dataplusdb->get_supporting_field_name($name, $formfieldtype);

                foreach ($result as $n => $v) {
                    if ($n == $labelname) {
                        $label = $v;
                        break;
                    }
                }

                if (empty($label)) {
                    $label = $value;
                }

                if (strtolower(substr($value, 0, 7)) != 'http://') {
                    $value = 'http://' . $value;
                }

                $js = "this.target='link{$rec}'; return openpopup('{$value}','link{$rec}');";
                $value = "<a onclick = \"{$js}\" href=\"{$value}\">{$label}</a>";
            } else if ($formfieldtype == 'boolean') {
                if ($value == 1) {
                    $value = get_string('true', 'dataplus');
                } else if ($value == 0) {
                    $value = get_string('false', 'dataplus');
                }
            } else if ($formfieldtype == 'longtext') {
                    $itemid = $dataplusfilehelper->get_itemid($name, $result->id);
                    $value= format_text($value, FORMAT_MOODLE);
                    $value = file_rewrite_pluginfile_urls($value,
                                                 'pluginfile.php',
                                                 $context->id,
                                                 'mod_dataplus',
                                                 $formfieldtype,
                                                 $itemid);
            }

            $recordtemplate = str_replace("[[{$name}]]", $value, $recordtemplate);
            $recordtemplate = str_replace("##{$name}##", $value, $recordtemplate);
            $esca = str_replace("'", "\'", $value);
            $recordtemplate = str_replace("[[{$name}_esca]]", $esca, $recordtemplate);
        }

        $id = $result->id;
        $record = "<a name=\"{$id}\"></a><div class=\"dataplus_record\">{$recordtemplate}</div>";
        print $record;
        $rec++;
    }
}


/**
 * Takes a template header or footer, adds divs, record counts and navigation
 * if required and displays it.
 * @param string $position - 'header' or 'footer'
 * @param string $input
 * @param array $parameters
 * @param array $colparameters
 */
function dataplus_print_template_headerfooter_output($position,
                                                     $template,
                                                     $parameters,
                                                     $colparameters = null) {
    global $mode;

    $template = dataplus_print_template_moodle_information($template);
    $template = str_replace('##record_count##',
                                dataplus_print_record_count($parameters), $template);
    $nav = dataplus_print_record_navigation($parameters, $colparameters);
    $template = str_replace('##record_navigation##', $nav, $template);

    $changecom = optional_param('changecom', 0, PARAM_INT);

    if ($mode == 'single') {
        if ($changecom == 0 || $changecom == 2 || $changecom == 5) {
            $template = str_replace('##add_comment##', dataplus_print_add_comment(), $template);
        } else {
            $template = str_replace('##add_comment##', '', $template);
        }
    }

    $template = "<div id=\"dataplus_template_{$position}\">{$template}</div>";
    print $template;
}


/**
 * Print comment template output
 *
 * @param string template for comments
 * @param int $rid
 * @param int $ignoreend
 * @param int $ignorestart
 * @param int $single
 * @param boolean $returnhtml
 */
function dataplus_print_template_comments_output($template,
                                                 $rid = null,
                                                 $ignoreend = null,
                                                 $ignorestart = null,
                                                 $single = null,
                                                 $returnhtml = false) {
    global $dataplusdb;

    $output = '';

    if (is_null($single)) {
        $params = dataplus_get_restricted_groups_parameters();
        $raw = $dataplusdb->get_record_comments($rid, $ignoreend, $ignorestart, $params);
    } else {
        $raw = $dataplusdb->get_comment($single, dataplus_get_restricted_groups_parameters());
    }

    $output .="<a name=\"comments\"></a>";

    if (empty($raw)) {
        return $output;
    }

    if (is_null($single)) {
        foreach ($raw as $comment) {
            $output .= dataplus_get_comment_html($template, $comment);
        }
    } else {
        $output = dataplus_get_comment_html($template, $raw);
    }

    $output = dataplus_print_template_moodle_information($output);

    if (!$returnhtml) {
        print $output;
    } else {
        return $output;
    }
}


/**
 * Return comment HTML
 *
 * @param string $input
 * @param array obj $comment
 * @return string
 */
function dataplus_get_comment_html($input, $comment) {
    global $dataplusdb, $context, $USER;

    $editorcols = $dataplusdb->define_editor_columns();
    $commenthtml = $input;

    foreach ($editorcols as $ec) {
        $ecname = $ec->name;

        if ($ec->type == 'date' || $ec->type == 'datetime') {
            $value = date('d F Y H:i:s', $comment->$ecname);
        } else {
            $value = $comment->$ecname;
        }

        $rep = $ec->label.": ".$value;
        $commenthtml = str_replace('##'.$ecname.'##', $rep, $commenthtml);
    }

    $commenthtml = str_replace('[[comment]]', $comment->comment, $commenthtml);

    $capcheck = has_capability('mod/dataplus:databaseedit', $context, $USER->id);

    if ($capcheck || $USER->id == $comment->creator_id) {
        $editcom = dataplus_print_edit_comment($comment->id);
        $commenthtml = str_replace("**edit**", $editcom, $commenthtml);
        $delcom = dataplus_print_delete_comment($comment->id);
        $commenthtml = str_replace("**delete**", $delcom, $commenthtml);
    } else {
        $commenthtml = str_replace("**edit**", "", $commenthtml);
        $commenthtml = str_replace("**delete**", "", $commenthtml);
    }

     return $commenthtml;
}



/**
 * Print paging links for a set of query results (prints nothing if the results fit on one page).
 *
 * @param array $parameters
 * @param array $colparameters
 * @return mixed
 */
function dataplus_print_record_navigation($parameters = null, $colparameters = array()) {
    global $dataplusdb, $dataplus, $CFG, $cm, $mode;

    $output = '';

    $cols = $dataplusdb->list_dataplus_table_columns(false, $colparameters);

    // If there are no user specified cols, do nothing.
    if (count($cols) == 0) {
        return;
    }

    // Count the number of records returned by the query parameters.
    $count = $dataplusdb->count_dataplus_database_query($parameters);

    if ($mode == 'single') {
        $pagelimit = 1;
    } else {
        if ($dataplus->listperpage == -1) {
            $pagelimit = $count;
        } else {
            $pagelimit = (int) $dataplus->listperpage;
        }
    }

    // Find out the number of the first record on the page.
    $pagestart = dataplus_get_page_start();

    // Find out the number of pages.
    $pages = intval($count/$pagelimit);

    // If the number of pages is higher, up the number of pages by 1.
    if ($count % $pagelimit) {
        $pages++;
    }

    // Check whether there is somewhere to go back to...
    if ($pagestart != 0) {
        $backpage = $pagestart - $pagelimit;
    } else {
        $backpage = null;
    }

    // Check whether there is somewhere to go forwards to...
    if (((($pagestart+$pagelimit) / $pagelimit) < $pages) && $pages != 1) {
        $nextpage = $pagestart + $pagelimit;
    } else {
        $nextpage = null;
    }

    // If everything fits on one page, do nothing.
    if (is_null($backpage)  && is_null($nextpage)) {
        return;
    }

    // Otherwise start printing paging.
    $langnext = get_string('next', 'dataplus');
    $langprevious = get_string('previous', 'dataplus');

    $url = $CFG->wwwroot.'/mod/dataplus/view.php?id='.$cm->id.'&amp;mode=' . $mode . '&amp;ps=';

    // Find out what the total number (limit) of page links that can be displayed in the navigation.

    if (empty($dataplus->navigationlimit)) {
        $navigationlimit = 15;
    } else {
        $navigationlimit = $dataplus->navigationlimit;
    }

    // If the limit on links is greater than the number of pages of results,
    // the first page is 0 and the last is the total number of pages...
    if ($pages <= $navigationlimit) {
        $start = 0;
        $stop  = $pages;
    } else {
        // Otherwise the number of the current page should be central on the navigation list,
        // so work out what (limit -1)/2 is ($navigationlimit is always an odd number)...
        $page = $pagestart / $pagelimit;
        $diff = (($navigationlimit-1)/2);

        // Then set the start to be the current page minus the diff and the stop to be the
        // current page + diff...
        $start = $page - $diff;
        $stop  = ($page + $diff)+1;

        // If the $page - $start is less than zero or $stop is greater than the number of pages,
        // then the page number cannot be central
        // and display the number of pages set in the limit
        // so abandon the attempt and set the start to 0 and the stop to the limit.

        if ($start<0) {
            $start = 0;
            $stop = $navigationlimit;
        }

        if ($stop>$pages) {
            $start = $start - ($stop - $pages);
            $stop = $pages;
        }

        // ...and finally display a link to return to the first record.
        $langfirst = get_string('first', 'dataplus');

        $class = 'dataplus_nav_first';

        if ($pagestart>0) {
            $output .= '<span class="'.$class.'"><a href="'.$url.'0">'.$langfirst.'</a> </span>';
        } else {
            $output .= '<span class="'.$class.'">'.$langfirst.' </span>';
        }
    }

    $class = 'dataplus_nav_previous';

    if (!is_null($backpage)) {
        $link = '<a href="'.$url.$backpage.'">'.$langprevious.'</a>';
        $output .= '<span class="'.$class.'">'.$link.' </span>';
    } else {
        $output .= '<span class="'.$class.'">'.$langprevious.' </span>';
    }

    $output .= "&nbsp;";

    $class = 'dataplus_nav_page';

    for ($i=$start; $i<$stop; $i++) {
        $start = $i * $pagelimit;
        $pageno = $i+1;

        if ($pagestart == $start) {
            $output .= '<span class="'.$class.'">'.$pageno.' </span>';
        } else {
            $output .= '<span class="'.$class.'"><a href="'.$url.$start.'">'.$pageno.'</a> </span>';
        }
    }

    $class = 'dataplus_nav_next';

    if (!is_null($nextpage)) {
        $link = '<a href="'.$url.$nextpage.'">'.$langnext.'</a>';
        $output .= '<span class="'.$class.'">'.$link.' </span>';
    } else {
        $output .= "<span class=\"{$class}\">" . $langnext . " </span>";
    }

    // Add a link to the last page.
    if ($pages > $navigationlimit) {
        $lastrecord1 = ($pages - 1) * $pagelimit;
        $langlast = get_string('last', 'dataplus');
        $class = 'dataplus_nav_last';

        if ($pagestart < $lastrecord1) {
            $link = '<a href="'.$url.$lastrecord1.'">'.$langlast.'</a>';
            $output .= '<span class="'.$class.'">'.$link.'</span>';
        } else {
            $output .= "<span class=\"{$class}\">" . $langlast . "</span>";
        }
    }

    return $output;
}


/**
 * print the start and end numbers of records printed on a screen and the record total
 *
 * @param array $parameters
 */
function dataplus_print_record_count($parameters = null) {
    global $dataplusdb, $dataplus, $mode;

    $count = $dataplusdb->count_dataplus_database_query($parameters);

    if ($dataplus->listperpage == -1) {
        $limit = $count;
    } else {
        $limit = (int) $dataplus->listperpage;
    }

    $pagestart = dataplus_get_page_start() + 1;
    $pageend = $pagestart + ($limit-1);

    if ($pageend>$count) {
        $pageend = $count;
    }

    $additions = new stdClass();
    if ($count == $pagestart || $mode == 'single') {
        $additions->start = $pagestart;
        $additions->end = $count;

        $langrecordcount = get_string('recordcountsingle', 'dataplus', $additions);
    } else {
        $additions->start = $pagestart;
        $additions->end = $pageend;
        $additions->total = $count;

        $langrecordcount = get_string('recordcount', 'dataplus', $additions);
    }

    return $langrecordcount;
}


/**
 * Print and add comment link
 *
 * @param int $rid
 * @return string
 */
function dataplus_print_add_comment() {
    global $cm, $CFG;

    $ps = optional_param('ps', 0, PARAM_INT);
    $ri = optional_param('ri', null, PARAM_INT);
    $url = $CFG->wwwroot.'/mod/dataplus/view.php';
    $form = dataplus_get_comment_form();
    $qs = '?id='.$cm->id.'&amp;mode=single&amp;changecom='.$form.'&amp;ps='.$ps;
    if (!is_null($ri)) {
        $qs .= '&ri='.$ri;
    }
    $qs .= '#amendcomment';
    $langaddcomment = get_string('addcomment', 'dataplus');

    return '<a href="'.$url.$qs.'">'.$langaddcomment.'</a>';
}


/**
 * Returns an edit comment link
 *
 * @return string
 */
function dataplus_print_edit_comment($cui) {
    return dataplus_get_comment_link('edit', dataplus_get_comment_edit(), $cui, '#amendcomment');
}


/**
 * Returns a delete comment link
 *
 * @return string
 */
function dataplus_print_delete_comment($cui) {
    $form = dataplus_get_comment_delete_form();
    return dataplus_get_comment_link('delete', $form, $cui, '#deletecomment');
}


/*
 * Returns a comment link
 *
 * @return string
 */
function dataplus_get_comment_link($type, $typeno, $cui, $name = null) {
    global $cm, $CFG;

    $ps = optional_param('ps', 0, PARAM_INT);
    $ri = optional_param('ri', null, PARAM_INT);

    $url = $CFG->wwwroot.'/mod/dataplus/view.php?';

    $qs = 'id='.$cm->id.'&amp;mode=single&amp;changecom='.$typeno.'&amp;cui='.$cui.'&amp;ps='.$ps;

    if (!is_null($ri)) {
        $qs .= '&ri='.$ri;
    }

    if (!empty($name)) {
        $qs .= $name;
    }

    $actions = dataplus_detail_supporting_actions();

    foreach ($actions as $action) {
        if ($action->name == $type) {
            $label = $action->label;
            break;
        }
    }

    $img = '<img src="'.$CFG->wwwroot.'/pix/t/'.$type.'.gif" class="iconsmall" alt="'.$label.'" />';

    return '<a href="'.$url.$qs.'">'.$img.'</a>';
}


/**
 * Checks whether edit or delete icons should be printed in template output
 *
 * @param string $action
 * @param string $str
 * @param string $template
 * @param int $ui
 * @param int $creatorid
 * @param int $group_id
 * @param boolean $clearactions
 */
function dataplus_create_record_icon($action,
                                     $str,
                                     $template,
                                     $ui,
                                     $creatorid,
                                     $clearactions = false) {
    global $id, $CFG, $USER, $context, $mode, $groupmode, $currentgroup;

    $ri = optional_param('ri', 0, PARAM_INT);

    if ($creatorid == $USER->id && !has_capability('mod/dataplus:dataeditown',
                                                   $context,
                                                   $USER->id)) {
        $clearactions = true;
    }

    if ($creatorid != $USER->id && !has_capability('mod/dataplus:dataeditothers',
                                                   $context,
                                                   $USER->id)) {
        $clearactions = true;
    }

    $capcheck = has_capability('mod/dataplus:databaseedit', $context, $USER->id);

    if ($groupmode>0 && $currentgroup>0 && !groups_is_member($currentgroup) && !$capcheck) {
        $clearactions = true;
    }

    $pagestart = dataplus_get_page_start();

    $fi = 'view.php';
    $url = "{$fi}?id={$id}&amp;mode={$action}&amp;ui={$ui}&amp;ps={$pagestart}&amp;oldmode={$mode}";

    if (!empty($ri)) {
        $url .= '&ri='.$ri;
    }

    if ($mode == 'searchresults') {
        $url .= "&rs=true";
    }

    if (!$clearactions) {
        $imageurl = $CFG->wwwroot.'/pix/t/'.$action.'.gif';
        $irep = '<img src="' . $imageurl . '" class="iconsmall" alt="' . $str . '" />';
        $rep = '<a title="'.$str.'" href="'.$url.'">'.$irep.'</a>&nbsp;';
    } else {
        $rep = '';
    }

    return str_replace("**{$str}**", $rep, $template);
}


/**
 * Generates a 'more' link for templates.
 * @param int $ri
 * @param string $template
 * @return string
 */
function dataplus_create_more_link($ri, $template) {
    global $id;

    $langmore = get_string('more', 'dataplus');
    $url = 'view.php?mode=single&amp;id='.$id.'&amp;ri='.$ri;
    $html = '<a class="dataplus_more_link" href="'.$url.'">'.$langmore.'</a>';

    return str_replace("**more**", $html, $template);
}


/**
 * Generates rating interface for templates.
 * @param obj $result
 * @param string $template
 * @return string
 */
function dataplus_create_rating($result, $template) {
    global $id, $OUTPUT;

    if (empty($result->moodledprating)) {
        $template = str_replace('**rate**', '', $template);
        return $template;
    }
    if (strpos($template, '**rate**') === false) {
        return $template;
    }

    $html = $OUTPUT->render($result->moodledprating);

    return str_replace('**rate**', $html, $template);
}


/**
 * Adds supporting record information not included in the database query
 *
 * @param string $name
 * @param string $int
 * @param string $template
 * @return string
 */
function dataplus_create_supporting_information($name, $recno, $template) {
    // Legacy from when the hook was '++record_no++'.
    $template = str_replace("++record_no++", $recno, $template);
    $template = str_replace("##record_no##", $recno, $template);

    return $template;
}


/**
 * Removes escaping in results from MoodleForms
 *
 * @param string $val
 * @return string
 */
function undo_escaping($val) {
    $val = str_replace("\'", "'", $val);
    $val = str_replace('\"', '"', $val);
    return $val;
}


/**
 * Find the number of columns to allow for search orders.
 *
 * @return int
 */
function dataplus_sort_order_limit() {
    global $dataplusdb;

    $parameters = dataplus_get_restricted_groups_parameters();

    $columns = $dataplusdb->list_dataplus_table_columns_array(true, $parameters);
    $nocols = count($columns);

    if ($nocols>5) {
        $nocols = 5;
    }

    return $nocols;
}


/**
 * Convert a search string from the templates table to an object for use in queries
 * @param string $str
 */
function dataplus_create_sortarr_from_str($str) {
    if (empty($str)) {
        return null;
    }

    $orders = explode(",", $str);

    for ($i=0; $i < count($orders); $i++) {
        $orderparts = explode(" ", $orders[$i]);

        $arr[$orderparts[0]] = new stdClass();
        $arr[$orderparts[0]]->name = $orderparts[0];

        if (count($orderparts) == 2) {
            $arr[$orderparts[0]]->sort = $orderparts[1];
        }
    }

    return $arr;
}


// Mode helper functions.


/**
 * List of different edit modes
 *
 * @return array
 */
function dataplus_get_edit_modes() {
    return array('edit', 'editsubmit', 'insert', 'insertbelowlimit');
}


/**
 * List of different search modes
 *
 * @return array
 */
function dataplus_get_search_modes() {
    return array('search', 'searchadvanced', 'searchamend', 'searchresults');
}


// Commenting helper functions.


/*
 * checks whether commenting can be used
 *
 * @return boolean
 */
function dataplus_allow_comments() {
    global $dataplus, $mode;

    if ((!isset($dataplus->allowcomments) || $dataplus->allowcomments == 1) && $mode == 'single') {
        return true;
    }

    return false;
}


/*
 * get value representing action or output (comment applies to next 5 functions)
 *
 * @return int
 */
function dataplus_get_comment_form() {
    return 1;
}


function dataplus_get_comment_amend() {
    return 2;
}


function dataplus_get_comment_edit() {
    return 3;
}

function dataplus_get_comment_delete_form() {
    return 4;
}

function dataplus_get_comment_delete() {
    return 5;
}


// Commenting helper functions.


/*
 * Find out if the user is impacted by a limit on the number of entries allowed
 *
 * @return mixed
 */

function dataplus_maximum_entry_limit_reached() {
    global $dataplusdb, $dataplus, $context, $USER;

    if (has_capability('mod/dataplus:databaseedit', $context, $USER->id)) {
        return false;
    }

    $entries = $dataplusdb->count_dataplus_database_query();

    if (!empty($dataplus->maxentries) && $entries >= $dataplus->maxentries) {
        return $dataplus->maxentries;
    }

    $userentries = $dataplusdb->count_user_entries();

    if (!empty($dataplus->maxentriesperuser) && $userentries >= $dataplus->maxentriesperuser) {
        return $dataplus->maxentriesperuser;
    }

    return false;
}


// Rating helper functions.


/*
 * Add a ratings object to result if required
 * @param obj $result;
 * @return $result
 */
function dataplus_add_rating($results) {
    global $dataplus, $context, $USER, $CFG, $mode;

    // If there's a rating var move it to a temporary variable.
    foreach ($results as $result) {
        if (isset($result->rating)) {
            $result->ratingdptemp = $result->rating;
        }
    }

    require_once($CFG->dirroot.'/rating/lib.php');

    if ($dataplus->assessed == RATING_AGGREGATE_NONE) {
        return $results;
    }

    $id = required_param('id', PARAM_INT);
    $pageurl = 'view.php?id=' . $id . '&mode=' . $mode . '&ps=' . dataplus_get_page_start();

    $ratingoptions = new stdClass;
    $ratingoptions->context = $context;
    $ratingoptions->component = 'mod_dataplus';
    $ratingoptions->ratingarea = 'record';
    $ratingoptions->aggregate = $dataplus->assessed;
    $ratingoptions->scaleid = $dataplus->scale;
    $ratingoptions->items = $results;
    // Hack because the sqlite3db has the field created_time not timecreated.
    foreach ($ratingoptions->items as $i) {
        $i->timecreated = $i->created_time;
    }
    $ratingoptions->returnurl = $CFG->wwwroot.'/mod/dataplus/'.$pageurl;
    $ratingoptions->assesstimestart = $dataplus->assesstimestart;
    $ratingoptions->assesstimefinish = $dataplus->assesstimefinish;

    $rm = new rating_manager();
    $results = $rm->get_ratings($ratingoptions);

    // Check there's no rating field.
    foreach ($results as $result) {
        if (isset($result->rating)) {
            $result->moodledprating = $result->rating;
            unset($result->rating);
        }
        if (isset($result->ratingdptemp)) {
            $result->rating = $result->ratingdptemp;
        }
    }

    return $results;
}


// Querystring helper functions.


/*
 * Returns an array of the querystring variables used in dataplus
 *
 * @return array
 */
function dataplus_get_querystring_list() {
    return array(
        "id" => PARAM_TEXT,
        "ui" => PARAM_INT,
        "cui" => PARAM_INT,
        "mode" => PARAM_TEXT,
        "fid" => PARAM_INT,
        "changecom" => PARAM_INT,
        "ps" => PARAM_INT,
        "group" => PARAM_TEXT,
        "reset" => PARAM_TEXT,
        "editor" => PARAM_TEXT,
        "ri" => PARAM_INT,
        "rs" => PARAM_TEXT,
        "view" => PARAM_TEXT,
        "oldmode" => PARAM_TEXT);
}


/*
 * Returns an array of the values of all querystring variables defined when the current page
 * was called.
 *
 * @return array
 */
function dataplus_get_querystring_vars() {
    $list = dataplus_get_querystring_list();

    $vals = array();

    foreach ($list as $l => $v) {
        $var = optional_param($l, null, $v);

        if (!is_null($var)) {
            $vals[$l] = $var;
        }
    }

    return $vals;
}


// Flood control helper functions.


/*
 * Stops extra empty records being added to the db
 * @return boolean - true if the flood control check has failed.
 */
function dataplus_flood_control() {
    global $SESSION;

    $now = microtime(true);

    if (empty($SESSION->dataplus_flood)) {
        $SESSION->dataplus_flood = $now;
        return false;
    }

    if ($SESSION->dataplus_flood >= $now - 0.2) {
        // Don't set dataplus_flood as no insert or update should happen.
        return true;
    }

    $SESSION->dataplus_flood = $now;
    return false;
}



/**
 * Called only by cron, this will remove temporary files older than 1 day.
 * @return int $count the number of files deleted.
 */
function dataplus_remove_temp_files() {
    global $CFG;
    require_once($CFG->libdir . '/filelib.php');
    $count = 0;
    $time = time() - 60*60*24; // 1 day ago.
    $temppath = $CFG->dataroot . '/temp';
    if (!file_exists($temppath)) {
        // Nothing to do.
        return $count;
    }
    // First clear out the temporary database files.
    $dirs = glob($temppath . '/dataplus-*');
    if ($dirs) {
        foreach ($dirs as $dir) {
            // We only want to remove dataplus folders.
            if (is_dir($dir)) {
                if ($time > filemtime($dir)) {
                    fulldelete($dir);
                    $count++;
                }
            }
        }
    }
    // Next clear out the temporary zip files.
    $dirs = glob($temppath . '/dataplus/*');
    if ($dirs) {
        foreach ($dirs as $dir) {
            if ($time > filemtime($dir)) {
                fulldelete($dir);
                $count++;
            }
        }
    }
    return $count;
}


/**
 * Get the add record label
 * @return string
 */
function dataplus_get_add_record_label() {
    global $dataplus;
    $labeldefault = get_string('addrecord', 'dataplus');
    $labeluser = $dataplus->addrecordtablabel;
    return (empty($labeluser)) ? $labeldefault : $labeluser;
}