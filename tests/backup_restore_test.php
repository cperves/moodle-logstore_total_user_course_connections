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
 * logstore total user connections per course backup restore testcases
 *
 * @package    logstore_total_user_course_connections
 * @author Céline Pervès <cperves@unistra.fr>
 * @author Matthieu Fuchs <matfuchs@unistra.fr>
 * @copyright Université de Strasbourg 2022 {@link http://unistra.fr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace logstore_total_user_course_connections;

global $CFG;

use advanced_testcase;
use backup;
use backup_controller;
use restore_controller;
use restore_controller_exception;
use restore_dbops;
use stdClass;

require_once($CFG->dirroot . '/backup/controller/tests/controller_test.php');

class backup_restore_test extends advanced_testcase {
    private $course;
    private $module;

    public function test_backup_restore() {
        global $DB, $USER;
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->setup_datas();
        // Add log datas.
        $record = new  stdClass();
        $record->courseid = $this->course->id;
        $record->userid = $USER->id;
        $now = time();
        $record->lastconnection = $now;
        $record->totalconnections = 20;
        $record->dailyconnections = 2;
        $DB->insert_record('logstore_usercourseconn', $record);
        $newcourseid = $this->backup_restore_course();
        $dbrecords = $DB->get_records('logstore_usercourseconn', array('courseid' => $newcourseid, 'userid' => $USER->id));
        $this->assertTrue(is_array($dbrecords));
        $this->assertCount(1, $dbrecords);
        $dbrecord = array_shift($dbrecords);
        $this->assertEquals(20, $dbrecord->totalconnections);
        $this->assertEquals(2, $dbrecord->dailyconnections);
        $this->assertEquals($now, $dbrecord->lastconnection);
    }

    private function setup_datas() {
        global $DB;
        set_config('enabled_stores', 'logstore_total_user_course_connections', 'tool_log');
        get_log_manager(true);
        $this->course = $this->getDataGenerator()->create_course();
        $this->module = $this->getDataGenerator()->create_module('workshop', array('course' => $this->course->id));
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->getDataGenerator()->enrol_user(2, $this->course->id, $studentrole->id);
        get_log_manager(true);
    }

    /**
     * @param $CFG
     * @param object $USER
     * @return int
     * @throws restore_controller_exception
     */
    private function backup_restore_course() {
        global $CFG, $USER;
        $CFG->keeptempdirectoriesonbackup = 1;
        set_config('backup_general_logs', 1, 'backup');
        set_config('backup_general_users', 1, 'backup');

        make_backup_temp_directory('');
        $bc = new backup_controller(backup::TYPE_1COURSE, $this->course->id, backup::FORMAT_MOODLE,
            backup::INTERACTIVE_NO, backup::MODE_GENERAL, $USER->id);
        $bc->get_plan()->get_setting('users')->set_value(true);
        $bc->get_plan()->get_setting('logs')->set_value(true);
        $backupid = $bc->get_backupid();
        $bc->execute_plan();
        $result = $bc->get_results();
        $bc->destroy();

        // Set up restore.
        $newcourseid = restore_dbops::create_new_course('Test fullname', 'Test shortname',
            $this->course->category);
        $rc = new restore_controller($backupid, $newcourseid,
            backup::INTERACTIVE_NO, backup::MODE_SAMESITE, $USER->id,
            backup::TARGET_NEW_COURSE);
        $rc->get_plan()->get_setting('logs')->set_value(true);
        $rc->get_plan()->get_setting('users')->set_value(true);
        $rc->execute_precheck();

        // Execute restore.
        $rc->execute_plan();
        $rc->destroy();
        return $newcourseid;
    }
}
