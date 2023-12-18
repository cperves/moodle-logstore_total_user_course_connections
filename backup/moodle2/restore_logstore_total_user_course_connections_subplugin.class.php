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
 * logstore total user connections per course restore implementation
 *
 * @package    logstore_total_user_course_connections
 * @author Céline Pervès <cperves@unistra.fr>
 * @author Matthieu Fuchs <matfuchs@unistra.fr>
 * @copyright Université de Strasbourg 2022 {@link http://unistra.fr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

class restore_logstore_total_user_course_connections_subplugin extends restore_tool_log_logstore_subplugin {

    /**
     * Returns the subplugin structure to attach to the 'logstore' XML element.
     *
     * @return restore_path_element[] array of elements to be processed on restore.
     */
    protected function define_logstore_subplugin_structure() {

        // If the logstore is not enabled we don't add structures for it.
        $enabledlogstores = explode(',', get_config('tool_log', 'enabled_stores'));
        if (!in_array('logstore_total_user_course_connections', $enabledlogstores)) {
            return array(); // The logstore is not enabled, nothing to restore.
        }

        $paths = array();

        $elename = $this->get_namefor('log');
        $elepath = $this->get_pathfor('/logstore_usercourseconn_log');
        $paths[] = new restore_path_element($elename, $elepath);

        return $paths;
    }

    /**
     * Process logstore_standard_log entries.
     *
     * This method proceeds to read, complete, remap and, finally,
     * discard or save every log entry.
     *
     * @param array() $data log entry.
     */
    public function process_logstore_total_user_course_connections_log($data) {
        global $DB;
        $contextrecord =
            $DB->get_record('context', array('instanceid' => $data['courseid'], 'contextlevel' => CONTEXT_COURSE));
        if ($contextrecord) {
            $data['contextid'] = $contextrecord->id;
        }
        $data['other'] = '';
        $data = $this->process_log($data, get_config('logstore_total_user_course_connections', 'jsonformat'));
        if ($data) {
            // At this point contextid changed but not courseid, so need to construct a new object.
            if ($data->contextlevel == CONTEXT_COURSE) {
                $newdata = new stdClass();
                $newdata->userid = $data->userid;
                $newdata->courseid = $data->contextinstanceid;
                $newdata->totalconnections = $data->totalconnections;
                $newdata->dailyconnections = $data->dailyconnections;
                $newdata->lastconnection = $data->lastconnection;
                $DB->insert_record('logstore_usercourseconn', $newdata);
            }
        }
    }
}
