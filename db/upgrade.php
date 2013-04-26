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

function xmldb_dataplus_upgrade($oldversion=0) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2010012500) {
        // Define field format to be added to data_comments.
        $table = new xmldb_table('dataplus');
        $field = new xmldb_field('navigationlimit');
        $type = XMLDB_TYPE_INTEGER;
        $prev = 'savebuttonlabel';
        $field->set_attributes($type, '2', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '15', $prev);

        // Launch add field format.
        $dbman->add_field($table, $field);
    }

    if ($oldversion < 2010012501) {
        $table = new xmldb_table('dataplus');
        $field = new xmldb_field('viewtabvisible');
        $type = XMLDB_TYPE_INTEGER;
        $prev = 'viewtablabel';
        $field->set_attributes($type, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '15', $prev);

        $dbman->add_field($table, $field);
    }

    if ($oldversion < 2010012501) {
        $table = new xmldb_table('dataplus');
        $field = new xmldb_field('singlerecordtablabel');
        $type = XMLDB_TYPE_TEXT;
        $prev = 'viewtabvisible';
        $field->set_attributes($type, 'small', null, null, null, null, $prev);

        $dbman->add_field($table, $field);
    }

    if ($oldversion < 2010012501) {
        $table = new xmldb_table('dataplus');
        $field = new xmldb_field('singlerecordtabvisible');
        $type = XMLDB_TYPE_INTEGER;
        $prev = 'singlerecordtablabel';
        $field->set_attributes($type, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '15', $prev);

        $dbman->add_field($table, $field);
    }

    if ($oldversion < 2010012501) {
        $table = new xmldb_table('dataplus');
        $field = new xmldb_field('searchtablabel');
        $type = XMLDB_TYPE_TEXT;
        $prev = 'singlerecordtabvisible';
        $field->set_attributes($type, 'small', null, null, null, null, $prev);

        $dbman->add_field($table, $field);
    }

    if ($oldversion < 2010012501) {
        $table = new xmldb_table('dataplus');
        $field = new xmldb_field('searchtabvisible');
        $type = XMLDB_TYPE_INTEGER;
        $prev = 'searchtablabel';
        $field->set_attributes($type, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '15', $prev);

        $dbman->add_field($table, $field);
    }

    if ($oldversion < 2010012502) {
        $table = new xmldb_table('dataplus');
        $field = new xmldb_field('addrecordtablabel');
        $type = XMLDB_TYPE_TEXT;
        $prev = 'searchtabvisible';
        $field->set_attributes($type, 'small', null, null, null, null, $prev);

        $dbman->add_field($table, $field);
    }

    if ($oldversion < 2010012502) {
        $table = new xmldb_table('dataplus');
        $field = new xmldb_field('addrecordtabvisible');
        $type = XMLDB_TYPE_INTEGER;
        $prev = 'addrecordtablabel';
        $field->set_attributes($type, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '15', $prev);

        $dbman->add_field($table, $field);
    }

    if ($oldversion < 2010012502) {
        $table = new xmldb_table('dataplus');
        $field = new xmldb_field('exporttablabel');
        $type = XMLDB_TYPE_TEXT;
        $prev = 'addrecordtabvisible';
        $field->set_attributes($type, 'small', null, null, null, null, $prev);

        $dbman->add_field($table, $field);
    }

    if ($oldversion < 2010012502) {
        $table = new xmldb_table('dataplus');
        $field = new xmldb_field('exporttabvisible');
        $type = XMLDB_TYPE_INTEGER;
        $prev = 'exporttablabel';
        $field->set_attributes($type, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '15', $prev);

        $dbman->add_field($table, $field);
    }

    if ($oldversion < 2010102902) {
        $table = new xmldb_table('dataplus');
        $field = new xmldb_field('maxentriesperuser');
        $type = XMLDB_TYPE_INTEGER;
        $prev = 'maxentriesperuser';
        $field->set_attributes($type, '0', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '15', $prev);

        $dbman->add_field($table, $field);
    }

    if ($oldversion < 2010121401) {
        $table = new xmldb_table('dataplus');
        $field = new xmldb_field('allowcomments');
        $type = XMLDB_TYPE_INTEGER;
        $prev = 'allowcomments';
        $field->set_attributes($type, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '1', $prev);

        $dbman->add_field($table, $field);
    }

    if ($oldversion < 2011121602) {
        $table = new xmldb_table('dataplus');
        $field = new xmldb_field('version');
        $type = XMLDB_TYPE_INTEGER;
        $prev = 'timemodified';
        $field->set_attributes($type, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '1', $prev);

        $dbman->add_field($table, $field);
    }

    if ($oldversion < 2012012704) {
        $table = new xmldb_table('dataplus');

        $field = new xmldb_field('saveandviewbuttonlabel');
        $type = XMLDB_TYPE_CHAR;
        $prev = 'savebuttonlabel';
        $default = get_string('saveandview', 'dataplus');
        $field->set_attributes($type, null, null, null, null, $default, $prev);
        $dbman->add_field($table, $field);

        $field = new xmldb_field('cancelbuttonlabel');
        $type = XMLDB_TYPE_CHAR;
        $prev = 'saveandviewbuttonlabel';
        $default = get_string('cancel', 'dataplus');
        $field->set_attributes($type, null, null, null, null, $default, $prev);
        $dbman->add_field($table, $field);
    }

    if ($oldversion < 2012032201) {
        $table = new xmldb_table('dataplus');
        $field = new xmldb_field('assessed');
        $type = XMLDB_TYPE_INTEGER;
        $prev = 'version';
        $field->set_attributes($type, null, null, null, null, '0', $prev);
        $dbman->add_field($table, $field);

        $field = new xmldb_field('assesstimestart');
        $type = XMLDB_TYPE_INTEGER;
        $prev = 'assessed';
        $field->set_attributes($type, null, null, null, null, '0', $prev);
        $dbman->add_field($table, $field);

        $field = new xmldb_field('assesstimefinish');
        $type = XMLDB_TYPE_INTEGER;
        $prev = 'assesstimestart';
        $field->set_attributes($type, null, null, null, null, '0', $prev);
        $dbman->add_field($table, $field);

        $field = new xmldb_field('scale');
        $type = XMLDB_TYPE_INTEGER;
        $prev = 'assesstimefinish';
        $field->set_attributes($type, null, null, null, null, '0', $prev);
        $dbman->add_field($table, $field);
    }

    return true;
}
