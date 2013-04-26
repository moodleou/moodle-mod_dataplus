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
 * Moodle form for editing templates
 * @package mod
 * @subpackage dataplus
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot.'/course/moodleform_mod.php');

class dataplus_template_view_form extends moodleform {

    public function definition() {
        global $CFG, $id, $SESSION, $dataplusdb, $mode;
        $opteditor = $SESSION->dataplus_use_editor;

        $mform =&$this->_form;

        $strcss = get_string('css', 'dataplus');
        $strcssshow = get_string('css_show', 'dataplus');
        $strcsshide = get_string('css_hide', 'dataplus');
        $strjs = get_string('javascript', 'dataplus');
        $strjsshow = get_string('javascript_show', 'dataplus');
        $strjshide = get_string('javascript_hide', 'dataplus');
        $strjsinit = get_string('jsinit', 'dataplus');
        $strresettemp = get_string('resettemplate', 'dataplus');
        $strhead = get_string('header', 'dataplus');
        $strrecord = get_string('record', 'dataplus');
        $strcomments = get_string('comments', 'dataplus');
        $strfooter = get_string('footer', 'dataplus');
        $strasc = get_string('ascending', 'dataplus');
        $strdesc = get_string('descending', 'dataplus');

        $func = 'dataplusShowHideTemplateFormElement';

        $cssfunc = $func."('id_css','dataplus_css_link','".$strcssshow."','".$strcsshide."');";
        $jsfunc = $func."('id_javascript','dataplus_js_link','".$strjsshow."','".$strjshide."');";
        $jsfunc .= $func."('id_jsinit',false,false,false);";

        $reseturl = $CFG->wwwroot.'/mod/dataplus/templates.php';
        $reseturl .= '?id='.$id.'&amp;reset=true&amp;mode='.$mode;
        $resetlink = "window.location.href='".$reseturl."'";

        if ($opteditor == 'textarea') {
            $editor     = 'htmleditor';
            $streditor = get_string('enable_editor', 'dataplus');
        } else {
            $editor = 'textarea';
            $streditor = get_string('disable_editor', 'dataplus');
        }

        $editorurl = $CFG->wwwroot.'/mod/dataplus/templates.php';
        $editorurl .= '?id='.$id.'&amp;editor='.$editor.'&amp;mode='.$mode;
        $editorlink  = "window.location.href='".$editorurl."'";

        $tophtml = '<div class="dataplus_template_buttons">';

        $tophtml .= '<input type="button" id="dataplus_css_link"';
        $tophtml .= ' onclick="'.$cssfunc.'" value="'.$strcssshow.'"/>';

        $tophtml .= '<input type="button" id="dataplus_js_link"';
        $tophtml .= ' onclick="'.$jsfunc.'" value="'.$strjsshow.'"/>';

        $tophtml .= '<input type="button" id="dataplus_reset_link"';
        $tophtml .= ' onclick="'.$resetlink.'" value="'.$strresettemp.'"/>';

        $tophtml .= '<input type="button" id="dataplus_editor_link"';
        $tophtml .= ' onclick="'.$editorlink.'" value="'.$streditor.'"/>';

        $tophtml .= '</div>';

        $mform->addElement('html', $tophtml);
        $mform->addElement('textarea', 'css', $strcss, array('rows'=>25, 'cols' => '60'));
        $mform->addElement('textarea', 'javascript', $strjs, array('rows'=>25, 'cols' => '60'));
        $mform->addElement('textarea', 'jsinit', $strjsinit, array('rows'=>4, 'cols' => '60'));

        if ($mode != 'addrecord') {
            $menu = dataplus_get_template_headerfooter_menu('header');
            $mform->addElement('html', $menu);
            $mform->addElement($opteditor, 'header', $strhead, array('rows'=>12, 'cols' => '70'));
        }

        $mform->addElement('html', dataplus_get_template_record_menu($mode));

        $colcount = $dataplusdb->count_dataplus_database_query();

        $attributes = array('rows'=>$colcount + 15,
                            'cols' => '60',
                            'onclick'=>'datapluscursorPosition()',
                            'onkeyup'=>'datapluscursorPosition()');
        $mform->addElement($opteditor, 'record', $strrecord, $attributes);

        if (dataplus_allow_comments()) {
            $mform->addElement('html', dataplus_get_template_comments_menu());

            $attributes = array('rows'=>20, 'cols' => '60');
            $mform->addElement($opteditor, 'comments', $strcomments, $attributes);
        }

        if ($mode != 'addrecord') {
            $menu = dataplus_get_template_headerfooter_menu('footer');
            $mform->addElement('html', $menu);

            $attributes = array('rows'=>12, 'cols' => '60');
            $mform->addElement($opteditor, 'footer', $strfooter, $attributes);
        }

        if ($mode != 'addrecord') {
            $parameters = dataplus_get_restricted_groups_parameters();
            $columns = $dataplusdb->list_dataplus_table_columns_array(true, $parameters);
            $columns = array_merge(array('na'=>get_string('na', 'dataplus')), $columns);

            $mform->addElement('static', 'so', get_string('sortorder', 'dataplus'));

            for ($i=1; $i<=dataplus_sort_order_limit(); $i++) {
                $mform->addElement('select', 'sortorder'.$i, '', $columns);

                $sortopts = array();
                $sortopts[] = &$mform->createElement('radio',
                                                              'sortoption'.$i,
                                                              '',
                                                              $strasc,
                                                              'ASC');
                $sortopts[] = &$mform->createElement('radio',
                                                              'sortoption'.$i,
                                                              '',
                                                              $strdesc,
                                                              'DESC');
                $mform->addGroup($sortopts, 'sortoption' . $i, '', array(' '), false);
            }
        }

        $mform->addElement('submit', 'submitbutton', get_string('savechanges'));
    }
}