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
 * logstore total user connections per course privacy provider tests
 *
 * @package    logstore_total_user_course_connections
 * @author Céline Pervès <cperves@unistra.fr>
 * @author Matthieu Fuchs <matfuchs@unistra.fr>
 * @copyright Université de Strasbourg 2022 {@link http://unistra.fr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace logstore_total_user_course_connections;

global $CFG;

use context_course;
use context_module;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use core_privacy\tests\provider_testcase;
use logstore_total_user_course_connections\privacy\provider;

require_once($CFG->libdir . '/tests/fixtures/events.php');

class privacy_provider_test extends provider_testcase {

    private $user1;
    private $user2;
    private $course1;
    private $coursecontext1;
    private $course2;
    private $coursecontext2;
    private $resource1;
    private $resourcecontext1;
    private $cmresource1;
    private $resource2;
    private $resourcecontext2;
    private $cmresource2;

    protected function setUp() : void {
        parent::setUp();
        $this->setup_datas();
    }

    /**
     * test get_users_in_context function
     */
    public function test_get_users_in_context() {
        $this->setUser($this->user1);
        course_view($this->coursecontext1);
        $this->setUser($this->user2);
        course_view($this->coursecontext1);
        get_log_manager(true);
        $userlist =
            new userlist($this->coursecontext1, 'logstore_total_user_course_connections');
        provider::get_users_in_context($userlist);
        $users = $userlist->get_users();
        $this->assertCount(2, $users);
        foreach ($users as $user) {
            $this->assertTrue($user->id == $this->user1->id || $user->id == $this->user2->id);
        }
    }

    /**
     * Tets get_contexts_for_userid function.
     * Function that get the list of contexts that contain user information for the specified user.
     * @throws coding_exception
     */
    public function test_user_contextlist() {
        $this->setUser($this->user1);
        course_view($this->coursecontext1);
        course_view($this->coursecontext2);
        get_log_manager(true);
        $contextlist = provider::get_contexts_for_userid($this->user1->id);
        $this->assertCount(2, $contextlist->get_contexts());
        $this->assertContains($this->coursecontext1, $contextlist->get_contexts());
        $this->assertContains($this->coursecontext2, $contextlist->get_contexts());
    }

    /**
     * Test export_all_data_for_user function.
     * funciton that export all data for a component for the specified user.
     * @throws coding_exception
     */
    public function test_export_user_data() {
        set_config('module_connections', 0, 'logstore_total_user_course_connections');
        $this->setUser($this->user1);
        course_view($this->coursecontext1);
        course_view($this->coursecontext2);
        get_log_manager(true);
        $approvedcontextlist = new \core_privacy\tests\request\approved_contextlist(
            $this->user1,
            'logstore_total_user_course_connections',
            [$this->coursecontext1->id, $this->coursecontext2->id]
        );
        provider::export_user_data($approvedcontextlist);
        $writer = writer::with_context($this->coursecontext1);
        $data = $writer->get_data([get_string('pluginname', 'logstore_total_user_course_connections')]);
        $this->assertTrue($writer->has_any_data());
        $this->assertInstanceOf('stdClass'
, $data);
        $this->assertTrue(property_exists($data, 'logstore_usercourseconn_records'));
        $this->assertCount(1, $data->logstore_usercourseconn_records);
        foreach ($data->logstore_usercourseconn_records as $logstoreusercourseconnrecord) {
            $this->assertEquals($this->user1->id, $logstoreusercourseconnrecord->userid);
            $this->assertEquals($this->course1->id, $logstoreusercourseconnrecord->courseid);
        }
        writer::reset();
        provider::export_user_data($approvedcontextlist);
        $writer = writer::with_context($this->coursecontext2);
        $data = $writer->get_data([get_string('pluginname', 'logstore_total_user_course_connections')]);
        $this->assertTrue($writer->has_any_data());
        $this->assertInstanceOf('stdClass'
, $data);
        $this->assertTrue(property_exists($data, 'logstore_usercourseconn_records'));
        $this->assertCount(1, $data->logstore_usercourseconn_records);
        foreach ($data->logstore_usercourseconn_records as $logstoreusercourseconnrecord) {
            $this->assertEquals($this->user1->id, $logstoreusercourseconnrecord->userid);
            $this->assertEquals($this->course2->id, $logstoreusercourseconnrecord->courseid);
        }
    }

    public function test_export_user_data_without_module_connections() {
        set_config('module_connections', 0, 'logstore_total_user_course_connections');
        $this->setUser($this->user1);
        resource_view($this->resource1, $this->course1, $this->cmresource1, $this->resourcecontext1);
        resource_view($this->resource2, $this->course2, $this->cmresource2, $this->resourcecontext2);
        get_log_manager(true);
        $approvedcontextlist = new \core_privacy\tests\request\approved_contextlist(
            $this->user1,
            'logstore_total_user_course_connections',
            [$this->coursecontext1->id, $this->coursecontext2->id]
        );
        provider::export_user_data($approvedcontextlist);
        $writer = writer::with_context($this->coursecontext1);
        $data = $writer->get_data([get_string('pluginname', 'logstore_total_user_course_connections')]);
        $this->assertTrue($writer->has_any_data());
        $this->assertTrue(is_array($data));
        $this->assertCount(0, $data);
    }

    public function test_export_user_data_with_module_connections() {
        global $DB;
        set_config('module_connections', 1, 'logstore_total_user_course_connections');
        $this->setUser($this->user1);
        resource_view($this->resource1, $this->course1, $this->cmresource1, $this->resourcecontext1);
        resource_view($this->resource2, $this->course2, $this->cmresource2, $this->resourcecontext2);
        get_log_manager(true);
        $approvedcontextlist = new \core_privacy\tests\request\approved_contextlist(
            $this->user1,
            'logstore_total_user_course_connections',
            [$this->coursecontext1->id, $this->coursecontext2->id]
        );
        provider::export_user_data($approvedcontextlist);
        $writer = writer::with_context($this->coursecontext1);
        $data = $writer->get_data([get_string('pluginname', 'logstore_total_user_course_connections')]);
        $this->assertTrue($writer->has_any_data());
        $this->assertInstanceOf('stdClass'
, $data);
        $this->assertTrue(property_exists($data, 'logstore_usercourseconn_records'));
        $this->assertCount(1, $data->logstore_usercourseconn_records);
        foreach ($data->logstore_usercourseconn_records as $logstoreusercourseconnrecord) {
            $this->assertEquals($this->user1->id, $logstoreusercourseconnrecord->userid);
            $this->assertEquals($this->course1->id, $logstoreusercourseconnrecord->courseid);
        }
        writer::reset();
        provider::export_user_data($approvedcontextlist);
        $writer = writer::with_context($this->coursecontext2);
        $data = $writer->get_data([get_string('pluginname', 'logstore_total_user_course_connections')]);
        $this->assertTrue($writer->has_any_data());
        $this->assertInstanceOf('stdClass'
, $data);
        $this->assertTrue(property_exists($data, 'logstore_usercourseconn_records'));
        $this->assertCount(1, $data->logstore_usercourseconn_records);
        foreach ($data->logstore_usercourseconn_records as $logstoreusercourseconnrecord) {
            $this->assertEquals($this->user1->id, $logstoreusercourseconnrecord->userid);
            $this->assertEquals($this->course2->id, $logstoreusercourseconnrecord->courseid);
        }
    }

    /**
     * Test Add contexts that contain user information for the specified user.
     * @return void
     */
    public function test_add_contexts_for_userid() {
        $this->setUser($this->user1);
        $addedcontextlist = new contextlist();
        provider::add_contexts_for_userid($addedcontextlist, $this->user1->id);
        $contextlist = provider::get_contexts_for_userid($this->user1->id);
        $this->assertCount(0, $contextlist);
        course_view($this->coursecontext1);
        course_view($this->coursecontext2);
        get_log_manager(true);
        $addedcontextlist = new contextlist();
        provider::add_contexts_for_userid($addedcontextlist, $this->user1->id);
        $contextlist = provider::get_contexts_for_userid($this->user1->id);
        $this->assertCount(2, $contextlist);
        $this->assertContains($this->coursecontext1, $contextlist);
        $this->assertContains($this->coursecontext2, $contextlist);
    }
    /**
     * Test add_userids_for_context function
     *
     * @param userlist $userlist The userlist to add the users to.
     * @return void
     */
    public function test_add_userids_for_context() {
        $userlist = new userlist($this->coursecontext1,
            'logstore_total_user_course_connections');
        $userids = $userlist->get_userids();
        $this->assertEmpty($userids);
        $this->setUser($this->user1);
        course_view($this->coursecontext1);
        $this->setUser($this->user2);
        course_view($this->coursecontext1);
        get_log_manager(true);
        provider::add_userids_for_context($userlist);
        get_log_manager(true);
        $userids = $userlist->get_userids();
        $this->assertCount(2, $userids);
        $this->assertContains((int)$this->user1->id, $userids);
        $this->assertContains((int)$this->user2->id, $userids);
    }

    /**
     * * Test delete_data_for_user function
     */
    public function test_delete_data_for_user() {
        global $DB;
        $this->lauch_course_view_for_users();
        get_log_manager(true);
        $this->assertCount(4, $DB->get_records('logstore_usercourseconn'));
        $this->assertEquals(2, $DB->count_records('logstore_usercourseconn', array('userid' => $this->user1->id)));
        $this->assertEquals(2, $DB->count_records('logstore_usercourseconn', array('userid' => $this->user2->id)));
        provider::delete_data_for_user(
                new approved_contextlist(
                        $this->user1, 'logstore_total_user_course_connections', [$this->coursecontext1->id]
                )
        );
        $this->assertFalse(
                $DB->record_exists('logstore_usercourseconn',
                        array('userid' => $this->user1->id, 'courseid' => $this->course1->id)
                )
        );
        $this->assertEquals(1, $DB->count_records('logstore_usercourseconn', array('userid' => $this->user1->id)));
        $this->assertEquals(2, $DB->count_records('logstore_usercourseconn', array('userid' => $this->user2->id)));
        provider::delete_data_for_user(
                new approved_contextlist(
                        $this->user2, 'logstore_total_user_course_connections',
                        [$this->coursecontext1->id, $this->coursecontext2->id]
                )
        );
        $this->assertFalse($DB->record_exists('logstore_usercourseconn', array('userid' => $this->user2->id)));
        $this->assertEquals(1, $DB->count_records('logstore_usercourseconn', array('userid' => $this->user1->id)));
        $this->assertEquals(0, $DB->count_records('logstore_usercourseconn', array('userid' => $this->user2->id)));
    }

    /**
     * test delete_data_for_all_users_in_context function
     */
    public function test_delete_data_for_all_users_in_context() {
        global $DB;
        $this->lauch_course_view_for_users();
        get_log_manager(true);
        $this->assertCount(4, $DB->get_records('logstore_usercourseconn'));
        provider::delete_data_for_all_users_in_context($this->coursecontext1);
        $this->assertCount(2, $DB->get_records('logstore_usercourseconn'));
        $this->assertFalse($DB->record_exists('logstore_usercourseconn', array('courseid' => $this->course1->id)));
        $this->assertEquals(1, $DB->count_records('logstore_usercourseconn', array('userid' => $this->user1->id)));
        $this->assertEquals(1, $DB->count_records('logstore_usercourseconn', array('userid' => $this->user2->id)));
    }

    /**
     * test delete_data_for_userlist function
     */
    public function test_delete_data_for_userlist() {
        global $DB;
        $this->lauch_course_view_for_users();
        get_log_manager(true);
        $this->assertCount(4, $DB->get_records('logstore_usercourseconn'));
        $userlist = new approved_userlist(
                $this->coursecontext1, 'logstore_total_user_course_connections',
                array($this->user1->id, $this->user2->id)
        );
        provider::delete_data_for_userlist($userlist);
        $this->assertCount(2, $DB->get_records('logstore_usercourseconn'));
        $this->assertFalse($DB->record_exists('logstore_usercourseconn', array('courseid' => $this->course1->id)));
    }

    /**
     * internal function to setu test datas
     * @throws coding_exception
     */
    private function setup_datas() {
        $this->resetAfterTest();
        $this->preventResetByRollback(); // Logging waits till the transaction gets committed.
        $this->set_logstore();
        $this->setAdminUser();
        $this->user1 = $this->getDataGenerator()->create_user();
        $this->user2 = $this->getDataGenerator()->create_user();
        $this->course1 = $this->getDataGenerator()->create_course();
        $this->coursecontext1 = context_course::instance($this->course1->id);
        $this->resource1 = $this->getDataGenerator()->create_module('resource', array('course' => $this->course1));
        $this->resourcecontext1 =  context_module::instance($this->resource1->cmid);
        $this->cmresource1 = get_coursemodule_from_instance('resource', $this->resource1->id);
        $this->course2 = $this->getDataGenerator()->create_course();
        $this->coursecontext2 = context_course::instance($this->course2->id);
        $this->resource2 = $this->getDataGenerator()->create_module('resource', array('course' => $this->course2));
        $this->resourcecontext2 =  context_module::instance($this->resource2->cmid);
        $this->cmresource2 = get_coursemodule_from_instance('resource', $this->resource2->id);
    }

    /**
     * Set up logstore to test
     */
    private function set_logstore() {
        set_config('enabled_stores', '', 'tool_log');
        // Enable logging plugin.
        set_config('enabled_stores', 'logstore_total_user_course_connections', 'tool_log');
        set_config('module_connections', 0, 'logstore_total_user_course_connections');
        // Force reload.
        get_log_manager(true);
    }

    private function lauch_course_view_for_users() {
        $this->setUser($this->user1);
        course_view($this->coursecontext1);
        course_view($this->coursecontext2);
        $this->setUser($this->user2);
        course_view($this->coursecontext1);
        course_view($this->coursecontext2);
    }
}

