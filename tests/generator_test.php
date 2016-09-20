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

defined('MOODLE_INTERNAL') || die();

/**
 * Dataplus generator unit test.
 *
 * @package mod_dataplus
 * @copyright 2015 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_dataplus_generator_testcase extends advanced_testcase {
    public function test_generator() {
        global $DB, $SITE;

        $this->resetAfterTest(true);

        $this->assertEquals(0, $DB->count_records('dataplus'));

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_dataplus');
        $this->assertInstanceOf('mod_dataplus_generator', $generator);
        $this->assertEquals('dataplus', $generator->get_modulename());

        $course = $this->getDataGenerator()->create_course();
        $dataplus = $generator->create_instance(array('course' => $course->id));
        $this->assertEquals(1, $DB->count_records('dataplus'));
        $dataplusid = $dataplus->id;
        $cm = get_coursemodule_from_instance('dataplus', $dataplusid);
        $this->assertEquals($dataplusid, $cm->instance);
        $this->assertEquals('dataplus', $cm->modname);
        $this->assertEquals($course->id, $cm->course);

        $context = context_module::instance($cm->id);
        $this->assertEquals($dataplus->cmid, $context->instanceid);

        $this->assertEquals(15, $dataplus->navigationlimit);
        $dataplus1 = $generator->create_instance(array('course' => $course->id, 'navigationlimit' => 4));
        $this->assertEquals(4, $dataplus1->navigationlimit);
    }
}
