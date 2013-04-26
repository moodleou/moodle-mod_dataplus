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
 * Class for managing files, the Moodle repository, the file system
 * and temp files.
 * @package mod
 * @subpackage dataplus
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class dataplus_file_helper {
    private $imagefileinfo;
    private $filefileinfo;
    private $zipfileinfo;
    private $longtextfileinfo;
    private $dbfileinfo;
    private $lockfileinfo;
    private $temppath;
    private $idspacer = '0000';
    private $zippath;

    /**
     * Sets instance variables for holding file information and creates a temp directory
     *
     * @param int $modinstid
     * @param string $temppath_sub
     */
    public function __construct($modinstid, $context) {
        global $CFG, $dataplus;

        require_once($CFG->libdir . '/filelib.php');

        $fileinfo = array(
            'component' => 'mod_dataplus',
            'filearea' => 'dataplus',
            'contextid' => $context->id,
            'filepath' => '/');

        $this->imagefileinfo = $fileinfo;
        $this->imagefileinfo['filearea'] = 'image';

        $this->filefileinfo = $fileinfo;
        $this->filefileinfo['filearea'] = 'file';

        $this->longtextfileinfo = $fileinfo;
        $this->longtextfileinfo['filearea'] = 'longtext';

        $this->zipfileinfo = $fileinfo;
        $this->zipfileinfo['filearea'] = 'zip';

        $this->dbfileinfo = $fileinfo;
        $this->dbfileinfo['filearea'] = 'dataplus_databases';
        $this->dbfileinfo['itemid'] = $modinstid;
        $this->dbfileinfo['filename'] = 'dataplus_db'.$dataplus->version.'.sqlite';

        $this->lockfileinfo = $fileinfo;
        $this->lockfileinfo['filearea'] = 'lock';
        $this->lockfileinfo['itemid'] = $modinstid;
        $this->lockfileinfo['filename'] = 'lock.txt';

    }


    /**
     * Deletes the temp directory when the helper is no longer needed
     */
    public function close() {
        fulldelete($this->temppath);
    }


    /**
     * Returns an item id to use when saving a file to the repository
     * @param string $colname
     * @param int $id
     *
     * @return int
     */
    public function get_itemid($colname, $id = null) {
        global $dataplusdb;
        $inc = $dataplusdb->get_content_column_id($colname);

        if (empty($id)) {
            $id = $dataplusdb->get_last_record_id();
        }

        return (int) $id . $this->idspacer . $inc;
    }


    /**
     * Return the database row id from an item id
     * @param int $itemid
     *
     * @return int
     */
    public function get_rowid($itemid) {
        $eles = explode($this->idspacer, $itemid);

        return (int) $eles[0];
    }


    /**
     * returns a file path based on fileinfo
     * @param array $fileinfo
     * @return string
     */
    private function get_file_path($fileinfo) {
        global $CFG;

        $url = $CFG->wwwroot.'/pluginfile.php/';
        $contextid = $fileinfo['contextid'];
        $component = $fileinfo['component'];
        $filearea = $fileinfo['filearea'];
        $itemid = $fileinfo['itemid'];
        $filepath = $fileinfo['filepath'];
        $filename = $fileinfo['filename'];

        return $url.$contextid.'/'.$component.'/'.$filearea.$filepath.$itemid.'/'.$filename;
    }


    /**
     * return the path for storing images for this module instance
     *
     * @return string
     */
    public function get_image_fileinfo() {
        return $this->imagefileinfo;
    }


    /**
     * return the path for an image file
     * @string $filename
     * @int $id
     * @string $colname
     *
     * @return string
     */
    public function get_image_file_path($filename, $id, $colname) {
        $fileinfo = $this->get_image_fileinfo();
        $fileinfo['itemid'] = $this->get_itemid($colname, $id);
        $fileinfo['filename'] = $filename;

        return $this->get_file_path($fileinfo);
    }


    /**
     * return the path for storing files for this module instance
     *
     * @return string
     */
    public function get_file_fileinfo() {
        return $this->filefileinfo;
    }


    /**
     * return the path for a file file
     * @string $filename
     * @int $id
     * @string $colname
     *
     * @return string
     */
    public function get_file_file_path($filename, $id, $colname) {
        $fileinfo = $this->get_file_fileinfo();
        $fileinfo['itemid'] = $this->get_itemid($colname, $id);
        $fileinfo['filename'] = $filename;

        return $this->get_file_path($fileinfo);
    }

    /**
     * return the path for storing files from longtext fields
     *
     * @return string
     */
    public function get_longtext_fileinfo() {
        return $this->longtextfileinfo;
    }


    /**
     * return the path for files from longtext fields
     * @string $filename
     * @int $id
     * @string $colname
     *
     * @return string
     */
    public function get_longext_file_path($filename, $id, $colname) {
        $fileinfo = $this->get_file_fileinfo();
        $fileinfo['itemid'] = $this->get_itemid($colname, $id);
        $fileinfo['filename'] = $filename;

        return $this->get_file_path($fileinfo);
    }

    /**
     * return the path for storing zip files for this module instance
     *
     * @return string
     */
    public function get_zip_fileinfo() {
        return $this->zipfileinfo;
    }


    /**
     * return the path for a zip file
     * @string $filename
     * @int $id
     *
     * @return string
     */
    public function get_zip_file_path($filename, $id) {
        $fileinfo = $this->get_zip_fileinfo();
        $fileinfo['itemid'] = $id;
        $fileinfo['filename'] = $filename;

        return $this->get_file_path($fileinfo);
    }

    /**
     * return the fileinfo for storing database files for this module instance
     *
     * @return string
     */
    public function get_db_fileinfo() {
        return $this->dbfileinfo;
    }


    /**
     * return the path for a database
     *
     * @return string
     */
    public function get_db_file_path() {
        $fileinfo = $this->get_db_fileinfo();

        return $this->get_file_path($fileinfo);
    }


    /**
     * return the fileinfo for storing lock files for this module instance
     *
     * @return string
     */
    public function get_lock_fileinfo() {
        return $this->lockfileinfo;
    }


    /**
     * check the temp directory for this instance exists, if not create it and return the path
     *
     * @return string
     */
    public function get_temp_path() {
        global $CFG, $USER;
        if (empty($this->temppath)) {
            $this->temppath = $CFG->dataroot . '/temp/dataplus';

            if (!file_exists($this->temppath)) {
                mkdir($this->temppath);
            }

            $this->temppath .= '/'. rand(100000, 999999) . '0' . $USER->id . '0' .
                    $this->lockfileinfo['itemid'];

            if (file_exists($this->temppath)) {
                fulldelete($this->temppath);
            }

            mkdir($this->temppath);
        }
        return $this->temppath;
    }


    /**
     * check the tozip directory for this instance exists, if not create it and return the path
     *
     * @return string
     */
    public function get_zip_path() {
        global $USER;

        if (empty($this->zippath)) {
            $this->zippath = $this->get_temp_path().'/zip'.rand(100000, 999999).$USER->id;
            $this->create_dir($this->zippath);
        }

        return $this->zippath;
    }


    /**
     * check the tozip image directory for this instance exists, if not create it
     * and return the path
     *
     * @return string
     */
    public function get_zip_images_path() {
        $path = $this->get_zip_path().'/images';

        $this->create_dir($path);

        return $path;
    }


    /**
     * check the tozip file directory for this instance exists, if not create it
     * and return the path
     *
     * @return string
     */
    public function get_zip_files_path() {
        $path = $this->get_zip_path().'/files';

        $this->create_dir($path);

        return $path;
    }

    /**
     * check the tozip longtext directory for this instance exists, if not create it
     * and return the path
     *
     * @return string
     */
    public function get_zip_longtext_path() {
        $path = $this->get_zip_path().'/longtext';

        $this->create_dir($path);

        return $path;
    }

    /**
     * Copy a directory including any subdirs, either from filesystem of Moodle file area.
     *
     * @param mixed $from
     * @param mixed $to
     * @param array $exclude
     * @param boolean $idinfilename - only applicable when copying to a filearea from a filesystem
     * @return boolean
     */
    public function copy($from, $to, $exclude = array()) {
        if (!is_array($from) && !is_array($to)) {
            return $this->copy_filesystem_to_filesystem($from, $to, $exclude);
        } else if (is_array($from) && !is_array($to)) {
            return $this->copy_filearea_to_filesystem($from, $to, $exclude);
        } else if (!is_array($from) && is_array($to)) {
            return $this->copy_filesystem_to_filearea($from, $to, $exclude);
        } else {
            return $this->copy_filearea_to_filearea($from, $to, $exclude);
        }
    }


    /**
     * Copy a directory of file from one location in the file system to another
     *
     * @param string $from = the source directory
     * @param string $to = the destination directory
     * @param array $exclude = array of filenames to exclude from the copy
     * @return boolean
     */
    private function copy_filesystem_to_filesystem($from, $to, $exclude) {
        if (!file_exists($from)) {
            return false;
        }
        if (is_file($from)) {
            return copy($from, $to);
        }

        $hande = opendir($from);

        while (false !== ($file = readdir($hande))) {
            if ($file == '.' || $file == '..' || in_array($file, $exclude)) {
                continue;
            } else if (is_dir($from.'/'.$file)) {
                $dir = $to.'/'.$file;

                if (file_exists($dir)) {
                    fulldelete($dir);
                }

                mkdir($dir);
                $this->copy_filesystem_to_filesystem($from.'/'.$file, $dir, $exclude);
            } else {
                if (!copy($from.'/'.$file, $to.'/'.$file)) {
                    return false;
                }
            }
        }

        return true;
    }


    /**
     * Copy a directory or file from the Moodle file area to the file system
     *
     * @param array $from = the source directory
     * @param string $to = the destination directory
     * @param array $exclude = array of filenames to exclude from the copy
     * @return boolean
     */
    private function copy_filearea_to_filesystem($from, $to, $exclude) {
        if ($this->filearea_file_exists($from)) {
            $f = $this->filearea_get_file($from);
            return $f->copy_content_to($to);
        }

        if (!empty($from['itemid'])) {
            return false;
        }

        $fs = get_file_storage();

        $files = $fs->get_area_files($from['contextid'], $from['component'], $from['filearea']);

        if (empty($files)) {
            return true;
        }

        foreach ($files as $f) {
            if ($from['filepath']!= '/' && $from['filepath'] != $f['filepath']) {
                continue;
            }

            $excludefile = in_array($f->get_filename(), $exclude);

            if ($excludefile || $f->get_filename() == '.' || $f->get_filename() == '..') {
                continue;
            }

            $path = $to.$f->get_filepath();

            if (!$f->copy_content_to($path.'/'.$f->get_filename())) {
                return false;
            }
        }

        return true;
    }


    /**
     * Copy a directory or file from the file system to the Moodle file area
     *
     * @param string $from = the source directory
     * @param string $to = the destination directory
     * @param array $exclude = array of filenames to exclude from the copy
     * @return boolean
     */
    private function copy_filesystem_to_filearea($from, $to, $exclude) {
        if (!file_exists($from)) {
            return false;
        }

        $fs = get_file_storage();

        if (is_file($from)) {
            $delete = $this->delete_file($to['filearea'], $to['filename'], $to['itemid']);

            if (!$delete) {
                return false;
            }

            return $fs->create_file_from_pathname($to, $from);
        }

        $hande = opendir($from);

        while (false !== ($file = readdir($hande))) {
            if ($file == '.' || $file == '..' || in_array($file, $exclude)) {
                continue;
            }

            $path = $from.'/'.$file;

            if (is_dir($path)) {
                $this->copy_filesystem_to_filearea($path, $to, $exclude);
            } else {
                $hyphenpos = strpos($file, '-');
                $to['filename'] = substr($file, $hyphenpos+1);
                $to['itemid'] = substr($file, 0, $hyphenpos);

                $this->delete_file($to['filearea'], $to['filename'], $to['itemid']);

                if (!$fs->create_file_from_pathname($to, $path)) {
                    return false;
                }
            }
        }

        return true;
    }


    /**
     * Resolve file path for use in fileinfo
     *
     * @param string $fileareapath
     * return string
     */
    public function resolve_fileinfo_filepath($fileareapath) {
        if (empty($fileareapath)) {
            $fileareapath = '/';
        }

        if (substr($fileareapath, 0, 1) != '/') {
            $fileareapath = '/' . $fileareapath;
        }

        if (substr($fileareapath, -1) != '/') {
            $fileareapath .= '/';
        }

        return $fileareapath;
    }


    /**
     * Copy a directory from one location in the Moodle file area to another
     * (note - causes a new file to be created not just another db record)
     *
     * @param string $from = the source directory
     * @param string $to = the destination directory
     * @param array $exclude = array of filenames to exclude from the copy
     * @return boolean
     */
    private function copy_filearea_to_filearea($from, $to, $exclude) {
        if ($this->filearea_file_exists($from)) {
            $f = $this->filearea_get_file($from);
            return $f->copy_content_to($to);
        }

        $fs = get_file_storage();
        $files = $fs->get_area_files($from['contextid'], $from['component'], $from['filearea']);

        if (empty($files)) {
            return true;
        }

        foreach ($files as $f) {
            if ($from['filepath'] != '/' && $from['filepath'] != $f['filepath']) {
                continue;
            }

            if (in_array($f->filename, $exclude)) {
                continue;
            }

            $fileinfo = $to;
            $fileinfo['filepath'] = $f->filepath;
            $fileinfo['filename'] = $f->filename;

            $this->delete_file($fileinfo['filearea'], $fileinfo['filename'], $fileinfo['itemid']);

            $fs->create_file_from_string($fileinfo, $f->get_content());
        }

        return true;
    }


    /**
     * delete all the files referenced in a database column for files or images
     * (used when such a column is deleted or the type is changed)
     *
     * @param string $colname
     * @param string $type
     */
    public function delete_column_files($colname, $type) {
        global $dataplusdb;

        $columns = array('id', $colname);
        $results = $dataplusdb->query_dataplus_database($columns);

        foreach ($results as $result) {
            if (!empty($result->$colname)) {
                $id = $this->get_itemid($colname, $result->id);
                $this->delete_file($type, $result->$colname, $id);
            }
        }
    }


    /**
     * delete a supporting file or image
     *
     * @param string $name, the filename
     * @param string $type, image or file
     * @return mixed
     */
    public function delete_file($type, $name = null, $id = null) {
        $fs = get_file_storage();

        if ($type != 'db' && (empty($name) || empty ($id))) {
            return false;
        }

        if ($type == 'image') {
            $fileinfo = $this->get_image_fileinfo();
        } else if ($type == 'db' || $type == 'dataplus_databases') {
            $fileinfo = $this->get_db_fileinfo();
            $name = $fileinfo['filename'];
            $id = $fileinfo['itemid'];
        } else {
            $fileinfo = $this->get_file_fileinfo();
        }

        $contextid = $fileinfo['contextid'];
        $component = $fileinfo['component'];
        $filearea = $fileinfo['filearea'];
        $filepath = $fileinfo['filepath'];

        $file = $fs->get_file($contextid, $component, $filearea, $id, $filepath, $name);

        if ($file) {
            $file->delete();
        }

        if ($fs->file_exists($contextid, $component, $filearea, $id, $filepath, $name)) {
            $count = 0;

            while ($fs->file_exists($contextid, $component, $filearea, $id, $filepath, $name) &&
                $count < 5) {
                sleep(1);
                $count++;
            }

            if ($fs->file_exists($contextid, $component, $filearea, $id, $filepath, $name)) {
                return false;
            }
        }

        return true;
    }


    /**
     * create a directory if it doesn't exist.
     *
     * @param string $path
     * @return boolean
     */
    public function create_dir($path) {
        if (!file_exists($path)) {
            return mkdir($path);
        }

        return false;
    }

    /**
     * get the filename of a draft file
     *
     * @param $draftid
     * @return string
     */
    public function get_draft_file_name($draftid) {
        global $USER;

        $usercontext = get_context_instance(CONTEXT_USER, $USER->id);
        $fs = get_file_storage();
        $files = $fs->get_area_files($usercontext->id, 'user', 'draft', $draftid);

        foreach ($files as $file) {
            $filename = $file->get_filename();

            if (empty($filename) || $filename == '.') {
                continue;
            }

            return $file->get_filename();
        }

        return false;
    }


    /**
     * Check whether a file exists in the repository
     *
     * @param array
     * @return boolean
     */
    public function filearea_file_exists($fileinfo) {
        $fs = get_file_storage();

        $contextid = $fileinfo['contextid'];
        $component = $fileinfo['component'];
        $filearea = $fileinfo['filearea'];
        $itemid = $fileinfo['itemid'];
        $filepath = $fileinfo['filepath'];
        $filename = $fileinfo['filename'];

        return $fs->file_exists($contextid, $component, $filearea, $itemid, $filepath, $filename);
    }


    /**
     * Return a file from the repository
     *
     * @param array
     * @return obj
     */
    public function filearea_get_file($fileinfo) {
        $fs = get_file_storage();

        $contextid = $fileinfo['contextid'];
        $component = $fileinfo['component'];
        $filearea = $fileinfo['filearea'];
        $itemid = $fileinfo['itemid'];
        $filepath = $fileinfo['filepath'];
        $filename = $fileinfo['filename'];

        return $fs->get_file($contextid, $component, $filearea, $itemid, $filepath, $filename);
    }


    /**
     * Renames files in the filesystem that don't have ids in their name.
     * This is a nasty hack function to provide designed to provide support to
     * import pre-moodle 2.0 databases.
     *
     * @param string $type
     * @param string $path
     */
    public function add_ids_to_import_files($type, $path) {
        global $dataplusdb;

        $cols = $dataplusdb->list_columns_of_type($type);

        if (!empty($cols) && file_exists($path)) {
            $cols[] = 'id';
            $records = $dataplusdb->query_dataplus_database($cols);

            $hande = opendir($path);

            while (false !== ($file = readdir($hande))) {
                if ($file == '.' || $file == '..') {
                    continue;
                }

                $found = false;

                foreach ($records as $record) {
                    foreach ($cols as $col) {
                        if ($record->$col == $file) {
                            $fitemid = $this->get_itemid($col, $record->id);
                            rename($path . '/' . $file, $path . '/' . $fitemid .'-' . $file);
                            $found = true;
                            break;
                        }
                    }

                    if ($found) {
                        break;
                    }
                }
            }
        }
    }
}