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

/**
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, null if doesn't know
 */
function dataplus_supports($feature) {
    switch($feature) {
        case FEATURE_GROUPS:                  return true;
        case FEATURE_GROUPINGS:               return true;
        case FEATURE_GROUPMEMBERSONLY:        return true;
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_BACKUP_MOODLE2:          return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_COMPLETION_HAS_RULES:    return true;
        case FEATURE_RATE:                    return true;
        case FEATURE_GRADE_HAS_GRADE:         return true;
        case FEATURE_GRADE_OUTCOMES:          return true;

        default: return null;
    }
}

/**
 * add an instance to the dataplus table
 *
 * @param course $dataplus
 * @return int
 */
function dataplus_add_instance($dataplus) {
    global $DB;

    if (empty($dataplus->assessed)) {
        $dataplus->assessed = 0;
    }

    if (empty($dataplus->ratingtime) or empty($dataplus->assessed)) {
        $dataplus->assesstimestart  = 0;
        $dataplus->assesstimefinish = 0;
    }

    $dataplus->timemodified = time();
    if (! $dataplus->id = $DB->insert_record('dataplus', $dataplus)) {
        return false;
    }

    return $dataplus->id;
}


/**
 * update the instance record in the dataplus table and initiate a check of the capabilities
 *
 * @param course $dataplus
 * @return mixed
 */
function dataplus_update_instance($dataplus) {
    global $DB;

    $dataplus->timemodified = time();
    $dataplus->id = $dataplus->instance;

    if (empty($dataplus->ratingtime) or empty($dataplus->assessed)) {
        $dataplus->assesstimestart  = 0;
        $dataplus->assesstimefinish = 0;
    }

    if (!$DB->update_record('dataplus', $dataplus)) {
        return false;
    }

    dataplus_grade_item_update($dataplus);

    return true;
}


/**
 * delete dataplus supporting files.
 *
 * @param int $id
 * @return boolean
 */
function dataplus_delete_instance($id) {
    global $CFG;

    $cm = get_coursemodule_from_instance('dataplus', $id);
    $context = context_module::instance($cm->id);
    $fs = get_file_storage();
    $fs->delete_area_files($context->id);

    return true;
}


/**
 * Return a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 *
 * @param int $course Course id
 * @param int $user User id
 * @param int $mod
 * @param int $scorm The scorm id
 * @return object
 */
function dataplus_user_outline($course, $user, $mod, $dataplus) {
    global $DB;
    $sql = '';
    $actions = dataplus_get_view_actions();
    $result = new stdClass;

    foreach ($actions as $action) {
        $sql .= " (SELECT COUNT(\"action\")
                   FROM \"{log}\"
                   WHERE \"action\" = '{$action}'
                   AND \"course\" = {$course->id}
                   AND \"userid\" = {$user->id}
                   AND \"cmid\" = {$mod->id}) as {$action},";
    }

    $sql = substr($sql, 0, -1);
    $sql = "SELECT DISTINCT userid," . $sql . " FROM \"{log}\" WHERE \"userid\" = {$user->id}";

    if ($actionsresult = $DB->get_record_sql($sql)) {
        $summary = '';

        foreach ($actions as $action) {
            if ($actionsresult->$action > 0) {
                $resact = $actionsresult->$action;
                $summary .= get_string('useroutline_'.$action, 'dataplus').$resact.'<br/>';
            }
        }

        $result->info = $summary;

        $sql = "SELECT DISTINCT time
                FROM \"{log}\"
                WHERE \"course\" = {$course->id}
                AND \"userid\" = {$user->id}
                AND \"cmid\" = {$mod->id}";

        $timeresult = $DB->get_record_sql($sql, null, IGNORE_MULTIPLE);
        $result->time = $timeresult->time;
    } else {
        $result->info = get_string('useroutline_noactivity', 'dataplus');
    }

    return $result;
}


/**
 * Serves associated files
 *
 * @param object $course
 * @param object $cm
 * @param object $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @return mixed
 */
function dataplus_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload) {
    global $DB;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_course_login($course, true, $cm);

    $postid = (int)array_shift($args);

    if (!$dataplus= $DB->get_record('dataplus', array('id'=>$cm->instance))) {
        return false;
    }

    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = "/$context->id/mod_dataplus/$filearea/$postid/$relativepath";
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        return false;
    }

    // Make sure groups allow this user to see this file
    // TODO - Security for group access for supporting files.
    // finally send the file
    send_stored_file($file, 0, 0, true); // download MUST be forced - security!
    exit;
}


/**
 * Prints all the records uploaded by this user
 *
 * @global object
 * @param object $course
 * @param object $user
 * @param object $mod
 * @param object $data
 */
function dataplus_user_complete($course, $user, $mod, $dataplus) {
    global $DB, $CFG, $OUTPUT;
    require_once("$CFG->libdir/gradelib.php");

    $grades = grade_get_grades($course->id, 'mod', 'data', $dataplus->id, $user->id);
    if (!empty($grades->items[0]->grades)) {
        $grade = reset($grades->items[0]->grades);
        echo $OUTPUT->container(get_string('grade').': '.$grade->str_long_grade);
        if ($grade->str_feedback) {
            echo $OUTPUT->container(get_string('feedback').': '.$grade->str_feedback);
        }
    }

    echo "<p>";

    $basicconditions = array('cmid'=>$dataplus->id, 'userid'=>$user->id);
    $conditions = array_merge($basicconditions, array('action'=>'view'));

    if ($records = $DB->get_records('log', $conditions, 'time DESC')) {
        echo get_string("usercompleted_view", "dataplus", count($records)).'<br/>';
    } else {
        echo get_string("usercompleted_view", "dataplus", 0).'<br/>';
    }

    $conditions = array_merge($basicconditions, array('action'=>'insert'));

    if ($records = $DB->get_records('log', $conditions, 'time DESC')) {
        echo get_string("usercompleted_insert", "dataplus", count($records)).'<br/>';
    } else {
        echo get_string("usercompleted_insert", "dataplus", 0).'<br/>';
    }

    $conditions = array_merge($basicconditions, array('action'=>'update'));

    if ($records = $DB->get_records('log', $conditions, 'time DESC')) {
        echo get_string("usercompleted_updated", "dataplus", count($records)).'<br/>';
    } else {
        echo get_string("usercompleted_updated", "dataplus", 0).'<br/>';
    }

    $conditions = array_merge($basicconditions, array('action'=>'delete'));

    if ($records = $DB->get_records('log', $conditions, 'time DESC')) {
        echo get_string("usercompleted_deleted", "dataplus", count($records)).'<br/>';
    } else {
        echo get_string("usercompleted_deleted", "dataplus", 0).'<br/>';
    }
    echo "</p>";
}


/**
 * @return array
 */
function dataplus_get_view_actions() {
    return array('view', 'search');
}


/**
 * @return array
 */
function dataplus_get_post_actions() {
    return array('insert', 'update', 'delete');
}


/**
 * Sets the module uservisible to false if the user has not got the view capability
 * @param cm_info $cm
 */
function dataplus_cm_info_dynamic(cm_info $cm) {
    if (!has_capability('mod/dataplus:view',
            context_module::instance($cm->id))) {
        $cm->uservisible = false;
        $cm->set_available(false);
    }
}


/**
 * Return grade for given user or all users.
 *
 * @global object
 * @param object $dataplus
 * @param int $userid optional user id, 0 means all users
 * @return array array of grades, false if none
 */
function dataplus_get_user_grades($dataplus, $userid=0) {
    global $CFG, $DB, $dataplus, $dataplusfilehelper;

    require_once($CFG->dirroot.'/rating/lib.php');

    $options = new stdClass;
    $options->component = 'mod_dataplus';
    $options->ratingarea = 'record';
    $options->modulename = 'dataplus';
    $options->moduleid = $dataplus->id;

    $options->userid = $userid;
    $options->aggregationmethod = $dataplus->assessed;
    $options->scaleid = $dataplus->scale;

    // There now follows a straight lift of get_user_grades() from rating lib but with the requirement
    // for an items table removed.
    $rm = new rating_manager();

    if (!isset($options->component)) {
        throw new coding_exception(
            'The component option is now a required option when getting user grades from ratings.'
        );
    }
    if (!isset($options->ratingarea)) {
        throw new coding_exception(
            'The ratingarea option is now a required option when getting user grades from ratings.'
        );
    }

    $modulename = $options->modulename;
    $moduleid   = intval($options->moduleid);

    // Going direct to the db for the context id seems wrong.
    list($ctxselect, $ctxjoin) = context_instance_preload_sql('cm.id', CONTEXT_MODULE, 'ctx');
    $sql = "SELECT cm.* $ctxselect
            FROM {course_modules} cm
            LEFT JOIN {modules} mo ON mo.id = cm.module
            LEFT JOIN {{$modulename}} m ON m.id = cm.instance $ctxjoin
            WHERE mo.name=:modulename AND
            m.id=:moduleid";
    $contextrecord = $DB->get_record_sql($sql,
        array('modulename'=>$modulename, 'moduleid'=>$moduleid), '*', MUST_EXIST);
    $contextid = $contextrecord->ctxid;
    $context = context::instance_by_id($contextid);

    $params = array();
    $params['contextid'] = $contextid;
    $params['component'] = $options->component;
    $params['ratingarea'] = $options->ratingarea;
    $scaleid = $options->scaleid;
    $aggregationstring = $rm->get_aggregation_method($options->aggregationmethod);
    // If userid is not 0 we only want the grade for a single user.
    $singleuserwhere = '';
    if ($options->userid != 0) {
        // Get the grades for the entries the user is responsible for.
        $dataplusfilehelper = new dataplus_file_helper($dataplus->id, $context);
        $dataplusdb = new sqlite3_db_dataplus();
        $itemids = $dataplusdb->get_user_record_ids($options->userid);
        // Using params causes an SQL error but these values are generated by Moodle.
        $itemidsstr = implode(',', $itemids);
        $singleuserwhere = " AND r.itemid IN ({$itemidsstr})";
        $dataplusdb->clean_up();
        $dataplusfilehelper->close();
    }

    // MDL-24648 The where line used to be "WHERE (r.contextid is null or r.contextid=:contextid)"
    // r.contextid will be null for users who haven't been rated yet
    // no longer including users who haven't been rated to reduce memory requirements.
    $sql = "SELECT DISTINCT r.component AS component, $aggregationstring(r.rating) AS rawgrade
            FROM {rating} r
            WHERE r.contextid = :contextid AND
                  r.component = :component AND
                  r.ratingarea = :ratingarea
                  $singleuserwhere
            GROUP BY component";

    // There's non way of creating a relationship to the userid, so we hack the results to add it.
    $rawresults = $DB->get_records_sql($sql, $params);
    $results = array($userid => $rawresults['mod_dataplus']);
    $results[$userid]->userid = $userid;

    if ($results) {
        $scale = null;
        $max = 0;
        if ($options->scaleid >= 0) {
            // Numeric.
            $max = $options->scaleid;
        } else {
            // Custom scales.
            $scale = $DB->get_record('scale', array('id' => -$options->scaleid));
            if ($scale) {
                $scale = explode(',', $scale->scale);
                $max = count($scale);
            } else {
                debugging(
                    'rating_manager::get_user_grades() received a scale ID that doesnt exist'
                );
            }
        }

        // It could throw off the grading if count and sum returned a rawgrade higher than scale
        // so to prevent it we review the results and ensure that rawgrade does not exceed
        // the scale, if it does we set rawgrade = scale (i.e. full credit).
        foreach ($results as $rid => $result) {
            if ($options->scaleid >= 0) {
                // Numeric.
                if ($result->rawgrade > $options->scaleid) {
                    $results[$rid]->rawgrade = $options->scaleid;
                }
            } else {
                // Scales.
                if (!empty($scale) && $result->rawgrade > $max) {
                    $results[$rid]->rawgrade = $max;
                }
            }
        }
    }
    return $results;
}

/**
 * Update activity grades
 *
 * @global object
 * @global object
 * @param object $data
 * @param int $userid specific user only, 0 means all
 * @param bool $nullifnone
 */
function dataplus_update_grades($dataplus, $userid=0, $nullifnone=true) {
    global $CFG, $DB;
    require_once($CFG->libdir.'/gradelib.php');

    if (!$dataplus->assessed) {
        dataplus_grade_item_update($dataplus);

    } else if ($grades = dataplus_get_user_grades($dataplus, $userid)) {
        dataplus_grade_item_update($dataplus, $grades);

    } else if ($userid and $nullifnone) {
        $grade = new stdClass();
        $grade->userid   = $userid;
        $grade->rawgrade = null;
        dataplus_grade_item_update($dataplus, $grade);

    } else {
        dataplus_grade_item_update($dataplus);
    }
}

/**
 * Update all grades in gradebook.
 *
 * @global object
 */
function dataplus_upgrade_grades() {
    global $DB;

    $sql = "SELECT COUNT('x')
              FROM {dataplus} d, {course_modules} cm, {modules} m
             WHERE m.name='dataplus' AND m.id=cm.module AND cm.instance=d.id";
    $count = $DB->count_records_sql($sql);

    $sql = "SELECT d.*, cm.idnumber AS cmidnumber, d.course AS courseid
              FROM {dataplus} d, {course_modules} cm, {modules} m
             WHERE m.name='dataplus' AND m.id=cm.module AND cm.instance=d.id";
    $rs = $DB->get_recordset_sql($sql);
    if ($rs->valid()) {
        $pbar = new progress_bar('dataplusupgradegrades', 500, true);
        $i=0;
        foreach ($rs as $data) {
            $i++;
            upgrade_set_timeout(60*5); // Set up timeout, may also abort execution.
            data_update_grades($data, 0, false);
            $pbar->update($i, $count, get_string('updatinggrades', 'dataplus', ($i/$count)));
        }
    }
    $rs->close();
}

/**
 * Update/create grade item for given data
 *
 * @global object
 * @param object $dataplus object with extra cmidnumber
 * @param mixed optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return object grade_item
 */
function dataplus_grade_item_update($dataplus, $grades=null) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');
    $params = array('itemname'=>$dataplus->name, 'idnumber'=>$dataplus->cmidnumber);

    if (!$dataplus->assessed or $dataplus->scale == 0) {
        $params['gradetype'] = GRADE_TYPE_NONE;

    } else if ($dataplus->scale > 0) {
        $params['gradetype'] = GRADE_TYPE_VALUE;
        $params['grademax']  = $dataplus->scale;
        $params['grademin']  = 0;

    } else if ($dataplus->scale < 0) {
        $params['gradetype'] = GRADE_TYPE_SCALE;
        $params['scaleid']   = -$dataplus->scale;
    }

    if ($grades  === 'reset') {
        $params['reset'] = true;
        $grades = null;
    }

    return grade_update('mod/dataplus', $dataplus->course, 'mod', 'dataplus', $dataplus->id, 0,
                        $grades, $params);
}

/**
 * Delete grade item for given data
 *
 * @global object
 * @param object $dataplus object
 * @return object grade_item
 */
function dataplus_grade_item_delete($dataplus) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    return grade_update('mod/dataplus', $dataplus->course, 'mod', 'dataplus',
        $dataplus->id, 0, null, array('deleted'=>1));
}

/**
 * return fields used for storing item data in dataplus db
 */
function dataplus_rating_get_item_fields() {;
    return array(null, 'id', 'creator_id');
}

/**
 * Return rating related permissions
 *
 * @param string $contextid the context id
 * @param string $component the component to get rating permissions for
 * @param string $ratingarea the rating area to get permissions for
 * @return array an associative array of the user's rating permissions
 */
function dataplus_rating_permissions($contextid, $component, $ratingarea) {
    $context = context::instance_by_id($contextid, MUST_EXIST);
    if ($component != 'mod_dataplus' || $ratingarea != 'record') {
        return null;
    }
    return array(
        'view'    => has_capability('mod/dataplus:viewrating', $context),
        'viewany' => has_capability('mod/dataplus:viewanyrating', $context),
        'viewall' => has_capability('mod/dataplus:viewallratings', $context),
        'rate'    => has_capability('mod/dataplus:rate', $context)
    );
}

/**
 * Validates a submitted rating
 * @param array $params submitted data
 *            context => object the context in which the rated items exists [required]
 *            component => The component for this module - should always be mod_forum [required]
 *            ratingarea => object the context in which the rated items exists [required]
 *            itemid => int the ID of the object being rated [required]
 *            scaleid => int the scale from which the user can select a rating. Used for bounds
 *                       checking. [required]
 *            rating => int the submitted rating
 *            rateduserid => int the id of the user whose items have been rated. NOT the user
 *                           who submitted the ratings. 0 to update all. [required]
 *            aggregation => int the aggregation method to apply when calculating grades ie
 *                           RATING_AGGREGATE_AVERAGE [optional]
 * @return boolean true if the rating is valid. Will throw rating_exception if not
 */
function dataplus_rating_validate($params) {
    global $DB, $USER, $CFG, $dataplus, $dataplusfilehelper;

    // Check the component is mod_dataplus.
    if ($params['component'] != 'mod_dataplus') {
        throw new rating_exception('invalidcomponent');
    }
    // Check the ratingarea is post (the only rating area in forum).
    if ($params['ratingarea'] != 'record') {
        throw new rating_exception('invalidratingarea');
    }

    // Check the rateduserid is not the current user .. you can't rate your own posts.
    if ($params['rateduserid'] == $USER->id) {
        throw new rating_exception('nopermissiontorate');
    }

    $sql = "SELECT d.*
            FROM {dataplus} as d
            INNER JOIN {course_modules} as cm
            ON cm.instance = d.id
            INNER JOIN {modules} as m
            ON cm.module = m.id
            WHERE cm.id = :itemid AND m.name = 'dataplus'";
    $sqlparams = array('itemid' => $params['context']->__get('instanceid'));

    if (! $dataplus = $DB->get_record_sql($sql, $sqlparams)) {
        print_error("Course module is incorrect");
    }

    require_once($CFG->dirroot . '/mod/dataplus/sqlite3_db_dataplus.php');
    require_once($CFG->dirroot . '/mod/dataplus/dataplus_file_helper.php');

    $dataplusfilehelper = new dataplus_file_helper($dataplus->id, $params['context']);
    $dataplusdb = new sqlite3_db_dataplus();

    $rowid = $dataplusfilehelper->get_rowid($params['itemid']);
    $parameters = array();
    $parameters[0] = new stdClass();
    $parameters[0]->name = 'id';
    $parameters[0]->value = $rowid;
    $parameters[0]->operator = 'equals';
    $row = $dataplusdb->query_dataplus_database_single(null, $parameters);

    if (!$row) {
        // Item doesn't exist.
        throw new rating_exception('invaliditemid');
    }

    if ($dataplus->scale != $params['scaleid']) {
        // The scale being submitted doesnt match the one in the database.
        throw new rating_exception('invalidscaleid');
    }

    // Check that the submitted rating is valid for the scale.

    // Lower limit.
    if ($params['rating'] < 0  && $params['rating'] != RATING_UNSET_RATING) {
        throw new rating_exception('invalidnum');
    }

    // Upper limit.
    if ($dataplus->scale < 0) {
        // Its a custom scale.
        $scalerecord = $DB->get_record('scale', array('id' => -$dataplus->scale));
        if ($scalerecord) {
            $scalearray = explode(',', $scalerecord->scale);
            if ($params['rating'] > count($scalearray)) {
                throw new rating_exception('invalidnum');
            }
        } else {
            $dataplusdb->clean_up();
            throw new rating_exception('invalidscaleid');
        }
    } else if ($params['rating'] > $dataplus->scale) {
        // If its numeric and submitted rating is above maximum.
        $dataplusdb->clean_up();  // Delete tmp db copy.
        throw new rating_exception('invalidnum');
    }

    // Check the item we're rating was created in the assessable time window.
    if (!empty($dataplus->assesstimestart) && !empty($dataplus->assesstimefinish)) {
        if ($row->timecreated < $dataplus->assesstimestart ||
            $row->timecreated > $dataplus->assesstimefinish) {
            $dataplusdb->clean_up(); // Delete tmp db copy.
            throw new rating_exception('notavailable');
        }
    }

    $cm = get_coursemodule_from_instance('dataplus', $dataplus->id, $dataplus->course, false, MUST_EXIST);
    $context = context_module::instance($cm->id, MUST_EXIST);

    // If the supplied context doesnt match the item's context.
    if ($context->id != $params['context']->id) {
        $dataplusdb->clean_up(); // Delete tmp db copy.
        throw new rating_exception('invalidcontext');
    }

    return true;
}

/**
 * @global object
 * @param int $dataplusid
 * @param int $scaleid
 * @return bool
 */
function dataplus_scale_used ($dataplusid, $scaleid) {
    // This function returns if a scale is being used by one glossary.
    global $DB;

    $return = false;

    $rec = $DB->get_record("dataplus", array("id"=>$glossaryid, "scale"=>-$scaleid));

    if (!empty($rec)  && !empty($scaleid)) {
        $return = true;
    }

    return $return;
}

/**
 * Checks if scale is being used by any instance of dataplus
 *
 * This is used to find out if scale used anywhere
 *
 * @global object
 * @param int $scaleid
 * @return boolean True if the scale is used by any glossary
 */
function dataplus_scale_used_anywhere($scaleid) {
    global $DB;

    if ($scaleid and $DB->record_exists('dataplus', array('scale'=>-$scaleid))) {
        return true;
    } else {
        return false;
    }
}

/**
 * Returns all other caps used in module
 */
function dataplus_get_extra_capabilities() {
    return array('moodle/site:accessallgroups');
}


/**
 * File browsing support for dataplus.
 * @param object $browser
 * @param object $areas
 * @param object $course
 * @param object $cm
 * @param object $context
 * @param string $filearea
 * @param int $itemid
 * @param string $filepath
 * @param string $filename
 * @return file_info instance Representing an actual file or folder (null if not found
 * or cannot access)
 */
function dataplus_get_file_info($browser, $areas, $course, $cm, $dpcontext, $filearea,
        $itemid, $filepath, $filename) {
    global $CFG, $DB, $dataplusfilehelper, $dataplus, $context;

    $context = context_module::instance($cm->id);

    if ($context->contextlevel != CONTEXT_MODULE) {
        return null;
    }

    $fileareas = array('image', 'file');
    if (!in_array($filearea, $fileareas)) {
        return null;
    }

    if (!has_capability('mod/dataplus:view', $context)) {
        return null;
    }

    require_once($CFG->dirroot . '/mod/dataplus/sqlite3_db_dataplus.php');
    require_once($CFG->dirroot . '/mod/dataplus/dataplus_file_helper.php');

    if (! $dataplus = $DB->get_record("dataplus", array("id"=>$cm->instance))) {
        print_error("Course module is incorrect");
    }
    $dataplusfilehelper = new dataplus_file_helper($dataplus->id, $context);
    $dataplusdb = new sqlite3_db_dataplus();

    $rowid = $dataplusfilehelper->get_rowid($itemid);
    $parameters = array(new stdClass());
    $parameters[0]->name = 'id';
    $parameters[0]->value = $rowid;
    $parameters[0]->operator = 'equals';
    $row = $dataplusdb->query_dataplus_database_single(null, $parameters);

    // Make sure groups allow this user to see this file.
    if (!empty($row->group_id)) {
        if (groups_get_activity_groupmode($cm, $course) == SEPARATEGROUPS) {
            // Groups are being used.
            if (!groups_group_exists($row->group_id)) {
                // Can't find group.
                $dataplusdb->clean_up();
                return null;
            }
            if (!has_capability('moodle/site:accessallgroups', $context) &&
                    !groups_is_member($row->group_id)) {
                $dataplusdb->clean_up();
                return null;
            }
        }
    }

    $fs = get_file_storage();
    $filepath = is_null($filepath) ? '/' : $filepath;
    $filename = is_null($filename) ? '.' : $filename;

    if (!($storedfile = $fs->get_file($context->id, 'mod_dataplus', $filearea, $itemid,
            $filepath, $filename))) {
        $dataplusdb->clean_up();
        return null;
    }

    $urlbase = $CFG->wwwroot . '/pluginfile.php';
    $dataplusdb->clean_up();
    return new file_info_stored($browser, $context, $storedfile, $urlbase, $filearea,
            $itemid, true, true, false);
}

/**
 * Periodic cleanup task.
 */
function dataplus_cron() {
    global $CFG;
    require_once($CFG->dirroot . '/mod/dataplus/locallib.php');
    mtrace('  Removing old temporary dataplus files.');
    $removed = dataplus_remove_temp_files();
    mtrace('  Removed ' . $removed . ' old temporary dataplus files.');
    return true;
}