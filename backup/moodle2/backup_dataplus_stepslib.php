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

class backup_dataplus_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {
        $userinfo = $this->get_setting_value('userinfo');

        $dataplus = new backup_nested_element('dataplus', array('id'), array(
            'name', 'intro', 'introformat', 'timeavailablefrom',
            'timeavailableto', 'timeviewfrom', 'timeviewto', 'requiredentries',
            'requiredentriestoview', 'maxentries', 'maxentriesperuser', 'allowcomments',
            'viewtablabel', 'viewtabvisible', 'singlerecordtablabel', 'singlerecordtabvisible',
            'searchtablabel', 'searchtabvisible', 'addrecordtablabel', 'addrecordtabvisible',
            'exporttablabel', 'exporttabvisible', 'savebuttonlabel', 'navigationlimit',
            'listperpage', 'assessed', 'assesstimestart', 'assesstimefinish',
            'scale', 'rssarticle', 'timemodified', 'version'));

        $dataplus->set_source_table('dataplus', array('id' => backup::VAR_ACTIVITYID));

        $dataplus->annotate_files('mod_dataplus', 'intro', null);
        $dataplus->annotate_files('mod_dataplus', 'dataplus_databases', 'id');
        $dataplus->annotate_files('mod_dataplus', 'image', null);
        $dataplus->annotate_files('mod_dataplus', 'file', null);
        $dataplus->annotate_files('mod_dataplus', 'longtext', null);

        $ratings = new backup_nested_element('ratings');

        $rating = new backup_nested_element('rating', array('id'), array(
            'component', 'ratingarea', 'itemid', 'scaleid', 'value', 'userid', 'timecreated',
            'timemodified'));

        $dataplus->add_child($ratings);
        $ratings->add_child($rating);

        // All the rest of elements only happen if we are including user info.
        if ($userinfo) {
            $rating->set_source_table('rating', array('contextid'  => backup::VAR_CONTEXTID,
                                                      'component'  => backup_helper::is_sqlparam('mod_dataplus'),
                                                      'ratingarea' => backup_helper::is_sqlparam('record')));
            $rating->set_source_alias('rating', 'value');
        }

        $rating->annotate_ids('scale', 'scaleid');

        $rating->annotate_ids('user', 'userid');

        return $this->prepare_activity_structure($dataplus);
    }
}