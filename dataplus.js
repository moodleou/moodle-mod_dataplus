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
 * Class for managing files, the Moodle repository, the file system
 * and temp files.
 * @package mod
 * @subpackage dataplus
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function dataplusisdefined(object, variable){
    return (typeof(eval(object)[variable]) != 'undefined');
}

var dataplusCursorStart = null;
var dataplusCursorEnd = null;

function datapluscursorPosition() {
    obj = document.getElementById('id_record');

    if (dataplusisdefined(document,'selection')) {
        var bookmark = document.selection.createRange().getBookmark();

        obj.selection = obj.createTextRange();  // create in textarea object and
        obj.selection.moveToBookmark(bookmark);  // match to document.selection
        obj.selLeft = obj.createTextRange(); // create textrange object

        obj.selLeft.collapse(true);           // for left amount of textarea &
        obj.selLeft.setEndPoint("EndToStart", obj.selection); // align them

        dataplusCursorStart = obj.selLeft.text.length;
        dataplusCursorEnd = obj.selLeft.text.length +  obj.selection.text.length;
    } else if (dataplusisdefined(obj, 'selectionStart')) {
        dataplusCursorStart = obj.selectionStart;
        dataplusCursorEnd = obj.selectionEnd;
    } else {
        dataplusCursorStart = null;
    }
}


function dataplusUpdateTextbox(str,id) {
    if (typeof(currEditor) != 'undefined' && currEditor._editMode == 'wysiwyg') {
        currEditor.focusEditor();
        currEditor.insertHTML(str);
    } else if (typeof(tinyMCE) != 'undefined'){
        tinyMCE.getInstanceById(id).execCommand('mceInsertContent',false,str);
    } else {
        var textbox = document.getElementById(id);
        var currentVal = textbox.value;
        var scroll = textbox.scrollTop;

        if (dataplusCursorStart == null) {
            document.getElementById(id).value = currentVal + str;
            return;
        }

        start = currentVal.substring(0,dataplusCursorStart);
        end   = currentVal.substring(dataplusCursorEnd,currentVal.length);

        document.getElementById(id).value = start + str + end;

        dataplusCursorStart = dataplusCursorStart + str.length;
        dataplusCursorEnd   = dataplusCursorStart;

        textbox.scrollTop = scroll;

        if (dataplusisdefined(document,'selection')) {
            var range = textbox.createTextRange();
            range.move("character",dataplusCursorStart);
            range.select();
        } else {
            textbox.select();
            textbox.setSelectionRange(dataplusCursorStart,dataplusCursorEnd);
        }
    }
}


function dataplusShowHideTemplateFormElement(id,link_id,showStr,hideStr) {
    var form_element_grandparent = document.getElementById(id).parentNode.parentNode;
    var link = document.getElementById(link_id);

    if (form_element_grandparent.style.display == 'none') {
        form_element_grandparent.style.display = 'block';

        if (link) {
            link.value = hideStr;
        }
    } else {
        form_element_grandparent.style.display = 'none';

        if (link) {
            link.value = showStr;
        }
    }
}