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
require_once($CFG->dirroot.'/course/moodleform_mod.php');

class mod_dataplus_mod_form extends moodleform_mod {

    public function definition() {
        global $CFG, $COURSE;

        $mform =&$this->_form;
        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('name'), array('size'=>'64'));

        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEAN);
        }

        $mform->addRule('name', null, 'required', null, 'client');

        $this->add_intro_editor(false, get_string('intro', 'dataplus'));

        $mform->addElement('date_selector',
                           'timeavailablefrom',
                            get_string('availablefromdate',
                            'dataplus'),
                            array('optional'=>true));
        $mform->addElement('date_selector',
                           'timeavailableto',
                           get_string('availabletodate', 'dataplus'),
                           array('optional'=>true));

        $sizes = array(0=>get_string('none'), 1=>1, 2=>2, 3=>3, 4=>4,
                       5=>5, 6=>6, 7=>7, 8=>8, 9=>9, 10=>10, 15=>15,
                       20=>20, 30=>30, 40=>40, 50=>50, 100=>100,
                       200=>200, 300=>300, 400=>400, 500=>500, 1000=>1000);

        $mform->addElement('select',
                           'requiredentries',
                           get_string('requiredentries',
                           'dataplus'), $sizes);
        $mform->addHelpButton('requiredentries', 'requiredentries', 'dataplus');

        $mform->addElement('select',
                           'requiredentriestoview',
                           get_string('requiredentriestoview', 'dataplus'),
                           $sizes);
        $mform->addHelpButton('requiredentriestoview', 'requiredentriestoview', 'dataplus');

        $mform->addElement('select', 'maxentries', get_string('maxentries', 'dataplus'), $sizes);
        $mform->addHelpButton('maxentries', 'maxentries', 'dataplus');

        $mform->addElement('select',
                           'maxentriesperuser',
                           get_string('maxentriesperuser', 'dataplus'),
                           $sizes);
        $mform->addHelpButton('maxentriesperuser', 'maxentriesperuser', 'dataplus');

        $ynoptions = array(1 => get_string('yes'), 0 => get_string('no'));

        $mform->addElement('select',
                           'allowcomments',
                           get_string('allowcomments', 'dataplus'),
                           $ynoptions);
        $mform->addHelpButton('allowcomments', 'allowcomments', 'dataplus');

        $limits = array();

        for ($i=3; $i<=25; $i = $i+2) {
            $limits[$i] = $i;
        }

        $mform->addElement('select',
                           'navigationlimit',
                           get_string('navigationlimit', 'dataplus'),
                           $limits);
        $mform->setDefault('navigationlimit', 15);
        $mform->addHelpButton('navigationlimit', 'navigationlimit', 'dataplus');

        $sizes[-1] = get_string('unlimited', 'dataplus');
        $mform->addElement('select', 'listperpage', get_string('listperpage', 'dataplus'), $sizes);
        $mform->setDefault('listperpage', 10);
        $mform->addHelpButton('listperpage', 'listperpage', 'dataplus');

        $mform->addElement('header', 'labels', get_string('labels', 'dataplus'));
        $mform->addElement('text',
                           'viewtablabel',
                           get_string('viewtablabel', 'dataplus'),
                           array('size'=>'20'));
        $mform->setDefault('viewtablabel', get_string('view', 'dataplus'));
        $mform->addHelpButton('viewtablabel', 'tablabel', 'dataplus');

        $mform->addElement('select',
                           'viewtabvisible',
                           get_string('viewtabvisible', 'dataplus'),
                           $ynoptions);
        $mform->addHelpButton('viewtabvisible', 'tabvisible', 'dataplus');

        $mform->addElement('text',
                           'singlerecordtablabel',
                           get_string('singlerecordtablabel', 'dataplus'),
                           array('size'=>'20'));
        $mform->setDefault('singlerecordtablabel', get_string('single_record', 'dataplus'));
        $mform->addHelpButton('singlerecordtablabel', 'tablabel', 'dataplus');

        $mform->addElement('select',
                           'singlerecordtabvisible',
                           get_string('singlerecordtabvisible', 'dataplus'),
                           $ynoptions);
        $mform->addHelpButton('singlerecordtabvisible', 'tabvisible', 'dataplus');

        $mform->addElement('text',
                           'searchtablabel',
                           get_string('searchtablabel', 'dataplus'),
                           array('size'=>'20'));
        $mform->setDefault('searchtablabel', get_string('search', 'dataplus'));
        $mform->addHelpButton('searchtablabel', 'tablabel', 'dataplus');

        $mform->addElement('select',
                           'searchtabvisible',
                           get_string('searchtabvisible', 'dataplus'),
                           $ynoptions);
        $mform->addHelpButton('searchtabvisible', 'tabvisible', 'dataplus');

        $mform->addElement('text',
                           'addrecordtablabel',
                           get_string('addrecordtablabel', 'dataplus'),
                           array('size'=>'20'));
        $mform->setDefault('addrecordtablabel', get_string('addrecord', 'dataplus'));
        $mform->addHelpButton('addrecordtablabel', 'tablabel', 'dataplus');

        $mform->addElement('select',
                           'addrecordtabvisible',
                           get_string('addrecordtabvisible', 'dataplus'),
                           $ynoptions);
        $mform->addHelpButton('addrecordtabvisible', 'tabvisible', 'dataplus');

        $mform->addElement('text',
                           'exporttablabel',
                           get_string('exporttablabel', 'dataplus'),
                           array('size'=>'20'));
        $mform->setDefault('exporttablabel', get_string('export', 'dataplus'));
        $mform->addHelpButton('exporttablabel', 'tablabel', 'dataplus');

        $mform->addElement('select',
                           'exporttabvisible',
                           get_string('exporttabvisible', 'dataplus'),
                           $ynoptions);
        $mform->addHelpButton('exporttabvisible', 'tabvisible', 'dataplus');

        $mform->addElement('text',
                           'savebuttonlabel',
                           get_string('savebuttonlabel', 'dataplus'),
                           array('size'=>'20'));
        $mform->setDefault('savebuttonlabel', get_string('save', 'dataplus'));
        $mform->addHelpButton('savebuttonlabel', 'savebuttonlabel', 'dataplus');

        $mform->addElement('text',
                           'saveandviewbuttonlabel',
                           get_string('saveandviewbuttonlabel', 'dataplus'),
                           array('size'=>'20'));
        $mform->setDefault('saveandviewbuttonlabel', get_string('saveandview', 'dataplus'));
        $mform->addHelpButton('saveandviewbuttonlabel', 'saveandviewbuttonlabel', 'dataplus');

        $mform->addElement('text',
                           'cancelbuttonlabel',
                           get_string('cancelbuttonlabel', 'dataplus'),
                           array('size'=>'20'));
        $mform->setDefault('cancelbuttonlabel', get_string('cancel', 'dataplus'));
        $mform->addHelpButton('cancelbuttonlabel', 'cancelbuttonlabel', 'dataplus');

        $this->standard_coursemodule_elements(array('groups'=>true,
                                                    'groupings'=>true,
                                                    'groupmembersonly'=>true));

        $this->add_action_buttons();
    }
}