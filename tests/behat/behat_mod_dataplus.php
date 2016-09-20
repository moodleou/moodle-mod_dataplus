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
 * Steps definitions related to mod_quiz.
 *
 * @package    mod_dataplus
 * @category   test
 * @copyright  2014 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Gherkin\Node\TableNode as TableNode;

/**
 * Steps definitions related to mod_quiz.
 *
 * @package    mod_dataplus
 * @category   test
 * @copyright  2014 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_mod_dataplus extends behat_base {

    /**
     * Populates a textarea field with text plus line returns.
     *
     * @Given /^the dataplus textarea for "(?P<textareaname_string>(?:[^"]|\\")*)" is set with the following lines:$/
     * @param String $textareaname
     * @param TableNode $data
     */
    public function the_dataplus_textarea_for_textarea_is_set_with_the_following_lines($textareaname, TableNode $data) {

        $rows = $data->getRows();
        $stringwithreturns = '';
        $index = 0;
        foreach ($rows as $rowdata => $item) {
            if ($index > 0) {
                $stringwithreturns .= chr(10) . $item[0];
            } else {
                $stringwithreturns .= $item[0];
            }
            $index++;
        }
        $this->execute('behat_forms::i_set_the_field_to', array($textareaname, $this->escape($stringwithreturns)));
    }

}
