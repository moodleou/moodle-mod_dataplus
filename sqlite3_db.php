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
 * Class for undertaking general actions on a db (queries, structure
 * changes, etc).
 * @package mod_dataplus
 * @copyright 2015 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sqlite3_db {
    private $conn;
    private $fileinfo;
    private $tempdbpath;
    private $temppath;
    private $write;
    private $import;

    /**
     * Maximum number version field can store.
     */
    const DATAPLUS_VERSIONMAX = 32767;
    /**
     * Range of version numbers to search for load balanced system.
     */
    const DATAPLUS_VERSIONSEARCHSIZE = 11;

    /**
     * Create a PDO connection to an SQLite database (which is created in the file
     * system if does not exist).
     * Sets the new_database variable if the database had to be created.
     *
     * @param boolean $write - is there an alteration to the db / should it be locked?
     * @param string $path - alternate path to the db.
     * @param boolean $import - is this being used for a database import.
     */
    public function __construct($write = false, $path = null, $import = false) {
        global $context, $CFG, $USER, $dataplusfilehelper, $dataplus;

        if ($write) {
            if (!$this->database_lock()) {
                global $id;
                $url = $CFG->wwwroot.'/mod/dataplus/view.php?mode=view&amp;id='.$id;
                print_error('lockerror', 'dataplus', $url);
            }
        }

        $this->write = $write;
        $this->import = $import;

        $this->fileinfo = $dataplusfilehelper->get_db_fileinfo();

        if (empty($path)) {
            $this->create_temporary_copy();
        } else {
            $this->tempdbpath = $path;
        }

        $this->conn = new PDO('sqlite:'.$this->tempdbpath);

        if (!$this->table_exists('columns')) {
            $colcolumns = $this->get_columns_table_column_details();
            $this->create_table("column", $colcolumns);
        }
    }


    /**
     * Cleans up the file system and save the database if necessary
     */
    public function clean_up() {
        global $dataplusfilehelper, $DB, $dataplus;
        $this->conn = null;

        if ($this->import ||
            (file_exists($this->tempdbpath) && $this->write && $this->database_locked())) {
            $dataplus->version++;
            // If about to hit DB field length limit loop back to 2.
            if ($dataplus->version > self::DATAPLUS_VERSIONMAX) {
                $dataplus->version = 2;
            }
            $this->fileinfo['filename'] = 'dataplus_db'.$dataplus->version.'.sqlite';
            $copy = $dataplusfilehelper->copy($this->tempdbpath, $this->fileinfo);
            if ($copy) {
                $DB->update_record('dataplus', $dataplus);
            }
            $dataplusfilehelper->delete_file('db');
            $this->database_unlock();
        }
        if (file_exists($this->tempdbpath)) {
            fulldelete($this->tempdbpath);
        }
        if (file_exists($this->temppath)) {
            fulldelete($this->temppath);
        }
    }

    /**
     * Creates a temporary folder structure for the sqlite db and copies to it from the repository
     *
     * @return boolean
     */
    private function create_temporary_copy() {
        global $CFG, $USER, $dataplusfilehelper, $dataplus, $COURSE;

        $fs = get_file_storage();
        $cid = $this->fileinfo['contextid'];

        $this->temppath = $CFG->dataroot.'/temp/dataplus-'.
            rand(100000, 999999).'-'.$cid.'-'.$USER->id;

        if (!file_exists($this->temppath)) {
            mkdir($this->temppath);
        }

        $this->tempdbpath = $this->temppath.'/'.$this->get_db_file_name();

        $file = $fs->get_file($this->fileinfo['contextid'],
                              $this->fileinfo['component'],
                              $this->fileinfo['filearea'],
                              $this->fileinfo['itemid'],
                              $this->fileinfo['filepath'],
                              $this->fileinfo['filename']);

        if (empty($file) && $dataplus->version == 1) {
            $file = $fs->get_file($this->fileinfo['contextid'],
                                  $this->fileinfo['component'],
                                  $this->fileinfo['filearea'],
                                  $this->fileinfo['itemid'],
                                  $this->fileinfo['filepath'],
                                  'dataplus_db.sqlite');
        }

        if (empty($file)) {
            $filename = str_replace('.sqlite', 'sqlite', $this->fileinfo['filename']);
            $file = $fs->get_file($this->fileinfo['contextid'],
                                  $this->fileinfo['component'],
                                  $this->fileinfo['filearea'],
                                  $this->fileinfo['itemid'],
                                  $this->fileinfo['filepath'],
                                  $filename);
        }

        // The version number might have recently changed. Usually a problem only on a load balanced system.
        // Let's sleep and look a little (repeating the earlier check is intentional)...
        if (empty($file)) {
            sleep(1);
            // Check next versions remembering to loop back around once DATAPLUS_VERSIONMAX hit.
            $start = $dataplus->version;
            for ($i = 0; $i <= self::DATAPLUS_VERSIONSEARCHSIZE; $i++) {
                $dataplus->version = ($i + $start) % self::DATAPLUS_VERSIONMAX;
                $this->fileinfo['filename'] = 'dataplus_db' . $dataplus->version . '.sqlite';
                $file = $fs->get_file($this->fileinfo['contextid'],
                    $this->fileinfo['component'],
                    $this->fileinfo['filearea'],
                    $this->fileinfo['itemid'],
                    $this->fileinfo['filepath'],
                    $this->fileinfo['filename']);
                if (!empty($file)) {
                    break;
                }
            }
        }
        if (!empty($file)) {
            return $file->copy_content_to($this->tempdbpath);
        }

        return false;
    }

    /**
     * returns the filename of the database
     *
     * @return string
     */
    public function get_db_file_name() {
        return $this->fileinfo['filename'];
    }


    /**
     * executes a SQL statement
     *
     * @param string $sql
     * @return boolean
     */
    private function execute_sql($sql) {
        if (is_null($this->conn)) {
            return false;
        }

        $querycheck = strtolower(substr($sql, 0, 6));

        if ($querycheck == 'select' || $querycheck == 'pragma') {
            $result = $this->conn->query($sql);

            if (empty($result)) {
                return false;
            }

            return $result;
        } else {
            $result = $this->conn->exec($sql);

            if (is_int($result)) {
                return true;
            } else {
                return false;
            }
        }
    }


    /**
     * creates a valid name for an object - must be 20 chars or less, start with a letter
     * and only contain alphanumeric characters based on a name given for use
     *
     * @param string $name
     * @return string
     */
    public function create_valid_object_name($name) {
        $name = preg_replace('/(^[^a-zA-Z])/', 'L\1', $name);

        // This fixes an odd bug whereby naming a field 'link' was causing the app to sometimes
        // behave as though two fields exist with the same name.
        if (strtolower($name) == 'link') {
            $name = 'link1234';
        }

        return substr(preg_replace('/[^a-zA-Z0-9]/', '', $name), 0, 20);
    }


    /**
     * Returns the lock file object
     *
     * @return obj
     */
    private function get_lock_file() {
        global $dataplusfilehelper;

        $fileinfo = $dataplusfilehelper->get_lock_fileinfo();
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
     * If the timestamp in lock.txt is greater than 120 seconds, set a time stamp and
     * return true to indicate the database is under control of this process.  If less
     * than 120 seconds, then wait to until 60 seconds passes or it is released
     *
     * @return boolean
     */
    private function database_lock() {
        global $dataplusfilehelper, $dataplus;

        $file = $this->get_lock_file();
        $fs = get_file_storage();
        $fileinfo = $dataplusfilehelper->get_lock_fileinfo();

        if ($file) {
            $date = (int) $file->get_content();

            if ($date > time() - 120) {
                while ($date > time() - 120) {
                    sleep(5);
                    $date = (int) $file->get_content();
                }
            }

            $file->delete();
        }

        if ($file = $fs->create_file_from_string($fileinfo, time())) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * checks whether the database is locked
     *
     * @return boolean
     */
    private function database_locked() {
        global $dataplusfilehelper;
        $fs = get_file_storage();
        $fileinfo = $dataplusfilehelper->get_lock_fileinfo();
        $contextid = $fileinfo['contextid'];
        $component = $fileinfo['component'];
        $filearea = $fileinfo['filearea'];
        $itemid = $fileinfo['itemid'];
        $filepath = $fileinfo['filepath'];
        $filename = $fileinfo['filename'];

        return $fs->file_exists($contextid, $component, $filearea, $itemid, $filepath, $filename);
    }

    /**
     * Used for setting the timestamp in lock.txt to 0 when an operation is complete.
     *
     * @return boolean
     */
    private function database_unlock() {
        $file = $this->get_lock_file();

        if (!$file || $file->delete()) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * returns an array of the columns in the 'column' table suitable for use in
     * table creation or queries
     *
     * @return array
     */
    protected function get_columns_table_column_details() {
        $columns = array();

        $columns[0] = new stdClass();
        $columns[0]->name = 'id';
        $columns[0]->type = 'integer';
        $columns[0]->primary_key = true;
        $columns[0]->autoincrement = true;
        $columns[0]->notnull = true;

        $columns[1] = new stdClass();
        $columns[1]->name = 'name';
        $columns[1]->type = 'text';

        $columns[2] = new stdClass();
        $columns[2]->name = 'label';
        $columns[2]->type = 'text';

        $columns[3] = new stdClass();
        $columns[3]->name = 'type';
        $columns[3]->type = 'text';

        $columns[4] = new stdClass();
        $columns[4]->name = 'primary_key';
        $columns[4]->type = 'boolean';

        $columns[5] = new stdClass();
        $columns[5]->name = 'autoincrement';
        $columns[5]->type = 'boolean';

        $columns[6] = new stdClass();
        $columns[6]->name = 'not_null';
        $columns[6]->type = 'boolean';

        $columns[7] = new stdClass();
        $columns[7]->name = 'table_name';
        $columns[7]->type = 'text';

        return $columns;
    }


    /**
     * returns a list of the names of columns in the 'column' table
     *
     * @return array
     */
    public function get_columns_table_column_list() {
        $cols = $this->get_columns_table_column_details();

        $list = array();

        foreach ($cols as $col) {
            $list[] = $col->name;
        }

        return $list;
    }


    /**
     * validates an SQLite database for use in DataPlus by checking the 'column' table exists
     *
     * @return mixed
     */
    public function validate_database() {
        $columnexits = $this->table_exists('column');

        if ($columnexits) {
            return true;
        }

        return false;
    }


    /**
     * Handles any escapes, etc, needed in values.
     *
     * @param string $value
     * @return string
     */
    public function prepare_value($value) {
        $value = str_replace("'", "''", $value);

        return $value;
    }


    /**
     * generate the SQL statement to create a table and execute it
     *
     * @param string $tablename
     * @param array $columns
     * @return boolean
     */
    public function create_table($tablename, $columns) {
        $columnssql = '';

        $tablename = $this->create_valid_object_name($tablename);

        foreach ($columns as $column) {
            if (!empty($columnssql)) {
                $columnssql .= ',';
            }

            $columnssql .= "\"{$column->name}\"";
            $type = $column->type;
            $columnssql .= " {$type}";

            if (isset($column->primary_key) && $column->primary_key == true) {
                $columnssql .= " PRIMARY KEY";
            }

            if (isset($column->autoincrement) && $column->autoincrement == true) {
                $columnssql .= " AUTOINCREMENT";
            }

            if (isset($column->not_null) && $column->not_null == true) {
                $columnssql .= " NOT NULL";
            }
        }

        $sql = "CREATE TABLE \"{$tablename}\" ({$columnssql})";
        $result = $this->execute_sql($sql);

        return $result;
    }


    /**
     * generate an SQL statement to drop a table and execute it
     *
     * @param string $tablename
     * @return boolean
     */
    public function drop_table($tablename) {
        $sql = "DROP TABLE \"{$tablename}\";";
        $result = $this->execute_sql($sql);

        return $result;
    }


    /**
     * return an array with all the details of the columns from a table.
     * Parameters can be specified to restrict the columns returned.
     * These two functions exist because autoincrement info is not included
     * in PRAGMA table_info in SQLite
     *
     * @param string $tablename
     * @param array $orgparams
     * @return array
     */
    public function list_table_columns($tablename, $orgparams = array()) {
        $columns = $this->get_columns_table_column_list();

        $parameters = array(new stdClass());
        $parameters[0]->name = 'table_name';
        $parameters[0]->value = $tablename;
        $parameters[0]->operator = 'equals';

        if (count($orgparams) > 0) {
            $parameters[0]->sub = $orgparams;
        }

        return $this->query_database('column', $columns, $parameters);
    }


    /**
     * return just the names of the columns in a table
     *
     * @param string $tablename
     * @return array
     */
    public function list_table_columns_names($tablename) {
        $columns = $this->list_table_columns($tablename);

        $names = array();
        foreach ($columns as $column) {
            $names[] = $column->name;
        }

        return $names;
    }


    /**
     * return an array with all the output of table_info for a table.
     *
     * @param string $tablename
     * @return array
     */
    public function pragma_table($tablename) {
        $records = $this->execute_sql("PRAGMA table_info({$tablename});");

        return $this->convert_pdo_to_array($records);
    }


    /**
     * get the details of one of the fields associated with a table column from the 'column' table
     *
     * @param string $tablename
     * @param string/int $id
     * @param string $field
     * @return mixed
     */
    public function get_column_field($tablename, $id, $field) {
        $parameters = array(new stdClass());
        $parameters[0]->name = 'id';
        $parameters[0]->value = $id;
        $parameters[0]->operator = 'equals';

        $col = $this->list_table_columns($tablename, $parameters);

        if (count($col) == 0) {
            return false;
        }

        return $col[0]->$field;
    }


    /**
     * get the name of a table column, as stored in the 'column' table, by it's id
     *
     * @param string $tablename
     * @param string/int $id
     * @return object
     */
    public function get_column_field_name($tablename, $id) {
        return $this->get_column_field($tablename, $id, 'name');
    }


    /**
     * get the details of an individual column, as stored in the 'column' table, by it's id
     *
     * @param string/int $id
     * @return object
     */
    public function get_column_details($id) {
        $columns = $this->get_columns_table_column_list();
        $parameters = array(new stdClass());
        $parameters[0]->name = 'id';
        $parameters[0]->value = $id;
        $result = $this->query_database('column', $columns, $parameters);

        return $result[0];
    }


    /**
     * get the details of an individual column, as stored in the 'column' table, by it's name
     *
     * @param string $name
     * @return object
     */
    public function get_column_details_by_name($name) {
        $columns = $this->get_columns_table_column_list();
        $parameters = array(new stdClass());
        $parameters[0]->name = 'name';
        $parameters[0]->value = $name;
        $parameters[0]->operator = 'equals';
        $result = $this->query_database('column', $columns, $parameters);

        if (empty($result)) {
            return false;
        }

        return $result[0];
    }


    /**
     * check a column exists in table, according to the information stored in the 'column' table
     *
     * @param string $tablename
     * @param string $columnname
     * @return boolean
     */
    public function check_column_exists($tablename, $columnname) {
        $columns = $this->pragma_table($tablename);

        foreach ($columns as $column) {
            if (isset($column->name) && $column->name == $columnname) {
                return true;
            }
        }

        return false;
    }


    /**
     * add a column to a table and supporting data to the 'column' table
     *
     * @param string $columnlabel
     * @param string $type
     * @return boolean
     */
    protected function add_column($columnlabel, $type) {
        $columnname = $this->create_valid_object_name($columnlabel);
        $result = $this->add_column_query("content", $columnname, $type);

        if ($result === true) {
            $columns = array(new stdClass());
            $columns[0]->name = 'name';
            $columns[0]->value = $columnname;

            $columns[1] = new stdClass();
            $columns[1]->name = 'label';
            $columns[1]->value = $columnlabel;

            $columns[2] = new stdClass();
            $columns[2]->name = 'type';
            $columns[2]->value = $type;

            $columns[3] = new stdClass();
            $columns[3]->name = 'table_name';
            $columns[3]->value = 'content';

            $result = $this->insert_record("column", $columns);
        }

        return $columnname;
    }


    /**
     * generate and execute the SQL statement for adding a column
     *
     * @param string $tablename
     * @param string $columnname
     * @param string $columntype
     * @param boolean $autoincrement
     * @param boolean $primarykey
     * @param boolean $notnull
     * @return result
     */
    protected function add_column_query($tablename,
                                        $columnname,
                                        $columntype,
                                        $autoincrement = false,
                                        $primarykey = false,
                                        $notnull = false) {
        $columnname = $this->create_valid_object_name($columnname);

        $columnexists = $this->check_column_exists($tablename, $columnname);

        if ($columnexists) {
            return "COLUMNEXISTS";
        }

        if (!empty($autoincrement)) {
            $autoincrement = 'AUTOINCREMENT';
        }

        if (!empty($primarykey)) {
            $primarykey = 'PRIMARY KEY';
        }

        if (!empty($notnull)) {
            $primarykey = 'NOT NULL';
        }

        $sql = 'ALTER TABLE '.$tablename.'
               ADD COLUMN "'.$columnname.'" '.$columntype.' '.
               $primarykey.' '.$autoincrement.' '.$notnull;
        $result = $this->execute_sql($sql);

        return $result;
    }


    /**
     * delete a column from a table
     *
     * @param string $tablename
     * @param int $columnid
     * @return boolean
     */
    protected function delete_column($tablename, $columnid) {
        $result = $this->delete_column_query($tablename, $columnid);

        if ($result) {
            $result = $this->delete_column_record($tablename, $columnid);
        }

        return $result;
    }


    /**
     * delete the record for a column from the 'column' table
     *
     * @param string $tablename
     * @param int $id
     * @return boolean
     */
    protected function delete_column_record($tablename, $id) {
        $deleteparams = array(new stdClass());

        $deleteparams[0]->name = 'id';
        $deleteparams[0]->value = $id;
        $deleteparams[0]->operator = 'equals';

        $deleteparams[1] = new stdClass();
        $deleteparams[1]->name = 'table_name';
        $deleteparams[1]->value = $tablename;
        $deleteparams[1]->operator = 'equals';

        return $this->delete_record('column', $deleteparams);
    }


    /**
     * call all the functions for generating and executing SQL for deleting columns
     * (note - there is no easy way to delete a column in SQLite)
     *
     * @param string $tablename
     * @param int $columnid
     * @return boolean
     */
    protected function delete_column_query($tablename, $columnid) {
        $columns = $this->list_table_columns($tablename);
        $i = 0;

        foreach ($columns as $column) {
            if ($column->id == $columnid) {
                unset($columns[$i]);
                break;
            }

            $i++;
        }

        $tempname = "deletebackup" . mt_rand(1, 1000000);

        $results = array();
        $results[] = $this->create_table($tempname, $columns);
        $results[] = $this->copy_table_data($tablename, $tempname, $columns);
        $results[] = $this->drop_table($tablename);
        $results[] = $this->create_table($tablename, $columns);
        $results[] = $this->copy_table_data($tempname, $tablename, $columns);
        $results[] = $this->drop_table($tempname);

        foreach ($results as $result) {
            if ($result !== true ) {
                return $result;
            }
        }

        return true;
    }


    /**
     * alter a table column, including altering the record in the 'column' table
     *
     * @param string $tablename
     * @param obj $columndetails
     * @return mixed
     */
    protected function alter_column($tablename, $columndetails) {
        $update = array();
        $i = 0;
        $storedcolumn = $this->get_column_details($columndetails->id);

        if ($columndetails->label !== $storedcolumn->label) {
            $name = $this->create_valid_object_name($columndetails->label);
            $label = $columndetails->label;

            $columndetails->new_name = $name;

            $update[$i] = new stdClass();
            $update[$i]->name = 'label';
            $update[$i]->value = $label;
            $i++;

            $update[$i] = new stdClass();
            $update[$i]->name = 'name';
            $update[$i]->value = $name;
            $i++;
        }

        if ($columndetails->type !== $storedcolumn->type) {
            $update[$i]->name = 'type';
            $update[$i]->value = $columndetails->type;
        }

        $result = $this->alter_column_query($tablename, $columndetails);

        if ($result === "COLUMNEXISTS" || $result === false) {
            return $result;
        }

        if (count($update) == 0) {
            return 'NOTHINGTODO';
        }

        $parameters = array(new stdClass());
        $parameters[0]->name = 'id';
        $parameters[0]->value = $columndetails->id;
        $parameters[0]->operator = 'equals';

        $parameters[1] = new stdClass();
        $parameters[1]->name = 'table_name';
        $parameters[1]->value = 'content';
        $parameters[1]->operator = 'equals';

        $result = $this->update_record('column', $update, $parameters);

        return $result;
    }


    /**
     * call all the functions for generating and executing SQL statements for altering a column
     * (this being long and complicated due to SQLite not supporting the alteration of columns)
     *
     * @param string $tablename
     * @param string $columndetails
     * @return boolean
     */
    protected function alter_column_query($tablename, $columndetails) {
        if (isset($columndetails->new_name)) {
            $newcolexists = $this->check_column_exists($tablename, $columndetails->new_name);

            if ($newcolexists) {
                return "COLUMNEXISTS";
            }
        }

        $oldcols = $this->list_table_columns($tablename);
        $newcols = $this->list_table_columns($tablename);

        $i = 0;

        foreach ($newcols as $newcol) {
            if ($newcol->id == $columndetails->id) {
                if (isset($columndetails->new_name)) {
                    $newcols[$i]->name = $columndetails->new_name;
                }

                if (isset($detail->type)) {
                    $newcols[$i]->type = $columndetails->type;
                }
                break;
            }

            $i++;
        }

        $tempname = 'renamebackup' . mt_rand(1, 1000000);

        $results = array();
        $results[] = $this->create_table($tempname, $oldcols);
        $results[] = $this->copy_table_data($tablename, $tempname, $oldcols);
        $results[] = $this->drop_table($tablename);
        $results[] = $this->create_table($tablename, $newcols);
        $results[] = $this->copy_table_data($tempname, $tablename, $oldcols);
        $results[] = $this->drop_table($tempname);

        foreach ($results as $result) {
            if ($result === false ) {
                return false;
            }
        }

        return true;
    }


    /**
     * insert a record to a table
     *
     * @param string $tablename
     * @param array $columns
     * @return boolean
     */
    protected function insert_record($tablename, $columns) {
        $columnssql = '';
        $valuessql = '';

        foreach ($columns as $column) {
            if (!empty($columnssql)) {
                $columnssql .= ", ";
            }

            $columnssql .= "\"{$column->name}\"";

            if (!empty($valuessql)) {
                $valuessql .= ", ";
            }

            $value = $this->prepare_value($column->value);
            $valuessql .= "'{$value}'";
        }

        $sql = "INSERT INTO \"{$tablename}\" ({$columnssql}) VALUES ({$valuessql})";
        $result = $this->execute_sql($sql);

        return $result;
    }


    /**
     * copy all the data in all the specified columns from one table to another
     *
     * @param string $sourcetable
     * @param string $desttable
     * @param array $columns
     * @return boolean
     */
    protected function copy_table_data($sourcetable, $desttable, $columns) {
        $columnssql = '';

        foreach ($columns as $column) {
            if (!empty($columnssql)) {
                $columnssql .= ', ';
            }

            $columnssql .= "\"{$column->name}\"";
        }

        $sql = 'INSERT INTO "'.$desttable.'"
               SELECT '.$columnssql.' FROM "'.$sourcetable.'";';
        $result = $this->execute_sql($sql);

        return $result;
    }


    /**
     * generates basic 'where' clauses for use in queries.
     * Subs can be used for parts to be contained in brackets
     *
     * @param array $parameters
     */
    protected function get_where_clause($parameters) {
        $paramssql = '';

        foreach ($parameters as $parameter) {
            if (!empty($paramssql)) {
                if (!isset($parameter->andor)) {
                    $parameter->andor = 'AND';
                }
                $paramssql .= " {$parameter->andor} ";
            }

            if (isset($parameter->sub)) {
                if (!empty($parameter->sub)) {
                    $paramssql .= '('.$this->get_where_clause($parameter->sub).')';
                }

                continue;
            }

            $value = $this->prepare_value($parameter->value);
            $name = $parameter->name;

            if (!isset($parameter->operator) || $parameter->operator == 'contains') {
                $paramssql .= "\"{$name}\" LIKE '%{$value}%'";
            } else if ($parameter->operator == 'equals') {
                $paramssql .= "\"{$name}\" = '{$value}'";
            } else if ($parameter->operator == 'notequal') {
                $paramssql .= "\"{$name}\" != '{$value}'";
            } else if ($parameter->operator == 'lessthan') {
                $paramssql .= "\"{$name}\" < {$value}";
            } else if ($parameter->operator == 'greaterthan') {
                $paramssql .= "(\"{$name}\" > {$value} AND {$name} != '')";
            }
        }

        return $paramssql;
    }

    /**
     * Delete the record/s from a table.  If no parameters are set, all the data is deleted
     *
     * @param string $tablename
     * @param array $parameters
     * @return boolean
     */
    protected function delete_record($tablename, $parameters = null) {
        $sql = "DELETE FROM \"{$tablename}\"";

        if (!is_null($parameters)) {
            $paramssql = $this->get_where_clause($parameters);
            $sql .= " WHERE {$paramssql}";
        }

        $result = $this->execute_sql($sql);

        return $result;
    }


    /**
     * update record/s.  If no parameters are set, all records will be updated
     *
     * @param string$tablename
     * @param array $columns
     * @param array $parameters
     * @return boolean
     */
    protected function update_record($tablename, $columns, $parameters = array()) {
        $columnssql = '';

        foreach ($columns as $column) {
            if (!empty($columnssql)) {
                $columnssql .= ', ';
            }

            $value = $this->prepare_value($column->value);
            $columnssql .= "\"{$column->name}\" = '{$value}'";
        }

        $sql = "UPDATE \"{$tablename}\" SET {$columnssql}";
        $paramssql = $this->get_where_clause($parameters);

        if (!empty($paramssql)) {
            $sql .= " WHERE {$paramssql}";
        }

        $result = $this->execute_sql($sql);

        return $result;
    }


    /**
     * Converts a PDO object to an array
     *
     * @param object $pdo
     * @return array
     */
    private function convert_pdo_to_array($pdo) {
        $details = array();
        $i = 0;

        while ($record = $pdo->fetch(PDO::FETCH_ASSOC)) {
            $details[$i] = new stdClass();
            foreach ($record as $name => $value) {
                $details[$i]->$name = $value;
            }

            $i++;
        }

        return $details;
    }


    /**
     * query the a table in the database.
     * If no parameters or limit is set, all records are returned.
     *
     * @param string $tablename
     * @param array $columns
     * @param array $parameters
     * @param array $limit
     * @param array $order
     * @return array
     */
    protected function query_database($tablename,
                                   $columns,
                                   $parameters = null,
                                   $limit = null,
                                   $order = null) {
        $columnssql = '';

        foreach ($columns as $column) {
            if (!empty($columnssql)) {
                $columnssql .= ', ';
            }

            $columnssql .= "\"{$column}\"";
        }

        $sql = "SELECT {$columnssql} FROM \"{$tablename}\"";

        if (!empty($parameters)) {
            $paramssql = $this->get_where_clause($parameters);
            $sql .= " WHERE {$paramssql}";
        }

        if (!empty($order)) {
            $sql .= " ORDER BY";
            $i = 0;

            foreach ($order as $o) {
                if (empty($o->type) || $o->type == 'text') {
                    $sql .= " UPPER(\"{$o->name}\")";
                } else {
                    $sql .= '"'.$o->name.'"';
                }

                if (isset($o->sort) && $o->sort == 'DESC') {
                    $sql .= " DESC";
                }

                $i++;

                if ($i < count($order)) {
                    $sql .= ',';
                }
            }
        }

        if (!empty($limit)) {
            $sql .= " LIMIT {$limit['start']},{$limit['number']}";
        }

        $records = $this->execute_sql($sql);

        if (!$records) {
            return false;
        }

        return $this->convert_pdo_to_array($records);
    }


    /**
     * return a single result to a query.
     * If the query returns more than one result, the first is returned.
     *
     * @param string $tablename
     * @param array $columns
     * @param array $parameters
     * @param array $order
     * @return mixed
     */
    protected function query_database_single($tablename,
                                          $columns,
                                          $parameters = null,
                                          $order = null) {
        $limit['start'] = 0;
        $limit['number'] = 1;

        $results = $this->query_database($tablename, $columns, $parameters, $limit, $order);

        if (empty($results)) {
            return false;
        }

        return $results[0];
    }


    /**
     * count the number of results in a database query
     *
     * @param string $tablename
     * @param array $parameters
     * @return mixed
     */
    protected function count_database_query($tablename, $parameters = null) {
        $sql = "SELECT COUNT(*) AS count FROM \"{$tablename}\"";

        if (!empty($parameters)) {
            $paramssql = $this->get_where_clause($parameters);
            $sql .= " WHERE {$paramssql}";
        }

        $result = $this->execute_sql($sql);

        if (!$result) {
            return false;
        }

        foreach ($result as $r) {
            return (int) $r["count"];
        }
    }


    /**
     * check a table with a given name exists
     *
     * @param string $tablename
     * @return boolean
     */
    protected function table_exists($tablename) {
        $parameters = array(new stdClass());
        $parameters[0]->name  = 'tbl_name';
        $parameters[0]->value = $tablename;
        $parameters[0]->operator = 'equals';

        $count = $this->count_database_query('sqlite_master', $parameters);

        if ($count == 1) {
            return true;
        }

        return false;
    }


    /**
     * get columns information for a table
     *
     * @param string $tablename
     * @return mixed
     */
    protected function get_table_info($tablename) {
        $sql = "PRAGMA table_info('{$tablename}')";
        $info = $this->execute_sql($sql);

        if (!$info) {
            return false;
        }

        $details = array();
        $i = 0;

        while ($record = $info->fetch(PDO::FETCH_ASSOC)) {
            foreach ($record as $name => $value) {
                $details[$i]->$name = $value;
            }

            $i++;
        }

        return $details;
    }


    /**
     * get information on a particular column from a table
     *
     * @param string $tablename
     * @param string $colname
     */
    protected function get_column_info($tablename, $colname) {
        $cols = $this->get_table_info($tablename);

        foreach ($cols as $col) {
            if ($col->name == $colname) {
                return $col;
            }
        }

        return false;
    }
}