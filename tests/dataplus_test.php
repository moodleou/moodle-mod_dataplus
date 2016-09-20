<?php
use MyProject\Proxies\__CG__\OtherProject\Proxies\__CG__\stdClass;
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
 * Base class for unit tests for mod_dataplus.
 *
 * Note to run this on a dev PC you will need to enable the pdo_sqlite driver in php.ini locally.
 *
 * @package    mod_dataplus
 * @category   phpunit
 * @copyright  2015 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/dataplus/locallib.php');
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->dirroot . '/mod/dataplus/dataplus_file_helper.php');
require_once($CFG->dirroot . '/mod/dataplus/sqlite3_db_dataplus.php');
require_once($CFG->dirroot . '/mod/dataplus/sqlite3_db.php');

class mod_dataplus_testcase extends advanced_testcase {

    /**
     * Setup a dataplus database for testing.
     */
    protected function setUp() {
        // Unfortunately dataplus is dependent on these globals.
        global $CFG, $DB, $mode, $cm, $COURSE, $dataplus, $dataplusfilehelper;
        global $dataplusdb, $context, $currentgroup, $groupmode, $editingmodes;

        $this->resetAfterTest(true);

        $COURSE = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_dataplus');
        $params = array('course' => $COURSE->id, 'name' => 'phpunittest', 'listperpage' => 2);
        $instance = $generator->create_instance($params);
        $cm = get_coursemodule_from_instance('dataplus', $instance->id);
        $context = context_module::instance($cm->id);
        $groupmode = $currentgroup = 0;
        $editingmodes = dataplus_get_edit_modes();
        $mode = null; //'searchresults'
        $dataplus = $DB->get_record("dataplus", array("id" => $cm->instance));
        $dataplusfilehelper = new dataplus_file_helper($dataplus->id, $context);
        $dpzip = $CFG->dirroot . '/mod/dataplus/tests/fixtures/dataplus3.zip';
        $dbname = 'dataplus_db30.sqlite';
        $zippath = $dataplusfilehelper->get_zip_path();
        $fp = get_file_packer();
        $fp->extract_to_pathname($dpzip, $zippath);
        $dataplusdb = new sqlite3_db_dataplus(false, $zippath . '/' . $dbname, true);
    }

    /**
     * Tears down the fixture.
     */
    protected function tearDown() {
        dataplus_base_close();
    }

    public function test_validate_database() {
        global $dataplusdb;
        $valid = $dataplusdb->validate_database();
        $this->assertEquals(true, $valid);
    }

    public function test_sorting() {
        global $dataplusdb;
        // Base check, no limits, no ordering, no searches.
        $parameters = array();
        $limit = null;
        $order = null;
        $results = $dataplusdb->query_dataplus_database(null, $parameters, $limit, $order);
        if (!is_array($results)) {
            $results = array($results);
        }
        $this->assertEquals(5, count($results));
        // Limit to 1 item.
        $limit['start'] = 1;
        $limit['number'] = 1;
        $results = $dataplusdb->query_dataplus_database(null, $parameters, $limit, $order);
        if (!is_array($results)) {
            $results = array($results);
        }
        $this->assertEquals(1, count($results));
        // Check last page only has 1 entry (note only 2 items per page).
        $limit['start'] = 4; // Note the start here means starting with item 4 where first item is 0.
        $limit['number'] = 2;
        $results = $dataplusdb->query_dataplus_database(null, $parameters, $limit, $order);
        if (!is_array($results)) {
            $results = array($results);
        }
        $this->assertEquals(1, count($results));
        // Check odering on itemid.
        $limit = null;
        $sortorder = 'itemid ASC';
        $order = dataplus_create_sortarr_from_str($sortorder);
        $results = $dataplusdb->query_dataplus_database(null, $parameters, $limit, $order);
        if (!is_array($results)) {
            $results = array($results);
        }
        $this->assertEquals(1, $results[0]->itemid);
        // Check odering on itemid in reverse.
        $sortorder = 'itemid DESC';
        $order = dataplus_create_sortarr_from_str($sortorder);
        $results = $dataplusdb->query_dataplus_database(null, $parameters, $limit, $order);
        if (!is_array($results)) {
            $results = array($results);
        }
        $this->assertEquals(5, $results[0]->itemid);
        // Check odering on two fields.
        $sortorder = 'truefalse ASC,number ASC';
        $order = dataplus_create_sortarr_from_str($sortorder);
        $results = $dataplusdb->query_dataplus_database(null, $parameters, $limit, $order);
        if (!is_array($results)) {
            $results = array($results);
        }
        $this->assertEquals(4, $results[0]->itemid);
        $this->assertEquals(2, $results[1]->itemid);
        $this->assertEquals(1, $results[2]->itemid);
        // Odering on date fields.
        $sortorder = 'datefield ASC';
        $order = dataplus_create_sortarr_from_str($sortorder);
        $results = $dataplusdb->query_dataplus_database(null, $parameters, $limit, $order);
        if (!is_array($results)) {
            $results = array($results);
        }
        $this->assertEquals(4, $results[0]->itemid);
        $this->assertEquals(3, $results[1]->itemid);
        $this->assertEquals(5, $results[4]->itemid);
        // Odering on datetime fields.
        $sortorder = 'datetimefield ASC';
        $order = dataplus_create_sortarr_from_str($sortorder);
        $results = $dataplusdb->query_dataplus_database(null, $parameters, $limit, $order);
        if (!is_array($results)) {
            $results = array($results);
        }
        $this->assertEquals(2, $results[2]->itemid);
        $this->assertEquals(5, $results[3]->itemid);
        // Odering on text fields.
        $sortorder = 'textfield DESC';
        $order = dataplus_create_sortarr_from_str($sortorder);
        $results = $dataplusdb->query_dataplus_database(null, $parameters, $limit, $order);
        if (!is_array($results)) {
            $results = array($results);
        }
        $this->assertEquals(4, $results[4]->itemid);
    }

    public function test_searching() {
        global $dataplusdb;
        // Cursory check of searches, as this is covered in Behat.
        $search = new \stdClass();
        $search->name = 'multipletextfield';
        $search->value = 'line one';
        $parameters = array($search);
        $limit = null;
        $order = null;
        $results = $dataplusdb->query_dataplus_database(null, $parameters, $limit, $order);
        if (!is_array($results)) {
            $results = array($results);
        }
        $this->assertEquals(1, count($results));
        $this->assertEquals(1, $results[0]->itemid);
        // Check of search with multiple items.
        $search1 = new \stdClass();
        $search1->name = 'textfield';
        $search1->value = 'text'; // 4 items with this.
        $search2 = new \stdClass();
        $search2->name = 'datefield';
        $search2->value = '1420070400'; // 1 Jan 2015
        $search2->operator = 'greaterthan';
        $parameters = array($search1, $search2);
        $limit = null;
        $order = null;
        $results = $dataplusdb->query_dataplus_database(null, $parameters, $limit, $order);
        if (!is_array($results)) {
            $results = array($results);
        }
        $this->assertEquals(2, count($results));
        $this->assertEquals(2, $results[0]->itemid);
    }
}
