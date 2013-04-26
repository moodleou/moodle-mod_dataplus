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
 * @package mod
 * @subpackage dataplus
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once("../../config.php");

$strjsshow = get_string('javascript_show', 'dataplus');
$strjshide = get_string('javascript_hide', 'dataplus');
$strcssshow = get_string('css_show', 'dataplus');
$strcsshide = get_string('css_hide', 'dataplus');

$func = 'dataplusShowHideTemplateFormElement';
$cssfunc = $func."('id_css','dataplus_css_link','".$strcssshow."','".$strcsshide."');";
$jsfunc = $func."('id_javascript','dataplus_js_link','".$strjsshow."','".$strjshide."');";
$jsfunc .= $func."('id_jsinit',false,false,false);";

echo $cssfunc;
echo $jsfunc;