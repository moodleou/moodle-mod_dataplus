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
 * The data_cleared event.
 *
 * @package    mod_dataplus
 * @copyright  2015 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_dataplus\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The data_cleared event class.
 *
 * @property-read array $other {
 *      Extra information about event.
 *
 *      'itemurl' - old style url only for legacy logs
 * }
 *
 * @since     Moodle 2.7
 * @copyright 2015 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class data_cleared extends \core\event\base {
    protected function init() {
        $this->data['crud'] = 'd';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'dataplus';
    }

    public static function get_name() {
        return get_string('eventdatacleared', 'mod_dataplus');
    }

    public function get_description() {
        return "The user with id {$this->userid} cleared data within a 'dataplus' activity with course module id {$this->contextinstanceid}.";
    }

    public function get_url() {
        return new \moodle_url('/mod/dataplus/manage.php', array('id' => $this->contextinstanceid));
    }

    public function get_legacy_logdata() {
        return array($this->courseid, 'dataplus', 'cleardata', $this->other['itemurl'],
                $this->objectid, $this->contextinstanceid);
    }
}