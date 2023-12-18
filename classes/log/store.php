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
 * logstore total user connections per course store class
 *
 * @package    logstore_total_user_course_connections
 * @author Céline Pervès <cperves@unistra.fr>
 * @author Matthieu Fuchs <matfuchs@unistra.fr>
 * @copyright Université de Strasbourg 2022 {@link http://unistra.fr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace logstore_total_user_course_connections\log;

defined('MOODLE_INTERNAL') || die();

class store implements \tool_log\log\writer, \core\log\sql_reader {
    use \tool_log\helper\store,
        \tool_log\helper\buffered_writer,
        \tool_log\helper\reader;

    public function __construct(\tool_log\log\manager $manager) {
        $this->helper_setup($manager);
        $this->jsonformat = (bool)$this->get_config('jsonformat', false);
    }

    /**
     * Should the event be ignored (== not logged)?
     * @param \core\event\base $event
     * @return bool
     */
    protected function is_event_ignored(\core\event\base $event) {
        $moduleconnections = get_config('logstore_total_user_course_connections', 'module_connections');
        if (!isguestuser()
            && ( $event instanceof \core\event\course_viewed
                || ($moduleconnections && $event instanceof \core\event\course_module_viewed)
            )
            ) {
            return false;
        }
        return true;
    }

    /**
     * Finally store the events into the database.
     *
     * @param array $evententries raw event data
     */
    protected function insert_event_entries($evententries) {
        global $DB, $USER;
        // Simplify datas.
        if (!\core\session\manager::is_loggedinas()) {
            foreach ($evententries as $evententry) {
                // In fact $evententry['contextinstanceid'] is $cmid.
                $datas = new \stdClass();
                $datas->userid = $evententry['userid'];
                if ($evententry['eventname'] == '\core\event\course_viewed') {
                    $datas->courseid = $evententry['contextinstanceid'];
                } else {
                    $cm = $DB->get_record('course_modules', array('id' => $evententry['contextinstanceid']));
                    $datas->courseid = $cm->course;
                }
                $now = time();
                // Check if already exists.
                if ($record = $DB->get_record('logstore_usercourseconn',
                    array('userid' => $datas->userid, 'courseid' => $datas->courseid))) {
                    $datas->id = $record->id;
                    $datas->totalconnections = $record->totalconnections + 1;
                    $previousdayconnection = usergetdate($record->lastconnection);
                    $currentconnection = usergetdate($now);
                    if ($previousdayconnection['year'] == $currentconnection['year']
                        && $previousdayconnection['yday'] == $currentconnection['yday']) {
                        $datas->dailyconnections = $record->dailyconnections;
                    } else {
                        $datas->lastconnection = $now;
                        $datas->dailyconnections = $record->dailyconnections + 1;
                    }
                    $DB->update_record('logstore_usercourseconn', $datas);
                } else {
                    $datas->lastconnection = $now;
                    $datas->totalconnections = 1;
                    $datas->dailyconnections = 1;
                    $DB->insert_record('logstore_usercourseconn', $datas);
                }
            }
        }
    }

    public function get_events_select($selectwhere, array $params, $sort, $limitfrom, $limitnum) {
        return array();
    }


    /**
     * Fetch records using given criteria returning a Traversable object.
     *
     * Note that the traversable object contains a moodle_recordset, so
     * remember that is important that you call close() once you finish
     * using it.
     *
     * @param string $selectwhere
     * @param array $params
     * @param string $sort
     * @param int $limitfrom
     * @param int $limitnum
     * @return \core\dml\recordset_walk|\core\event\base[]
     */
    public function get_events_select_iterator($selectwhere, array $params, $sort, $limitfrom, $limitnum) {
        return new \core\dml\recordset_walk(new empty_recordset(), array($this, 'get_log_event'));
    }

    /**
     * Returns an event from the log data.
     *
     * @param stdClass $data Log data
     * @return \core\event\base
     */
    public function get_log_event($data) {

        $extra = array('origin' => $data->origin, 'ip' => $data->ip, 'realuserid' => $data->realuserid);
        $data = (array)$data;
        $id = $data['id'];
        $data['other'] = unserialize($data['other']);
        if ($data['other'] === false) {
            $data['other'] = array();
        }
        unset($data['origin']);
        unset($data['ip']);
        unset($data['realuserid']);
        unset($data['id']);

        if (!$event = \core\event\base::restore($data, $extra)) {
            return null;
        }

        return $event;
    }

    public function get_events_select_count($selectwhere, array $params) {
        return 0;
    }

    /**
     * Get whether events are present for the given select clause.
     * Since this plugin is not for internal moodle purpose we can return false each time it is called
     * @param string $selectwhere select conditions.
     * @param array $params params.
     *
     * @return bool Whether events available for the given conditions
     */
    public function get_events_select_exists(string $selectwhere, array $params): bool {
        return false;
    }

    /**
     * Are the new events appearing in the reader?
     *
     * @return bool true means new log events are being added, false means no new data will be added
     */
    public function is_logging() {
        // Only enabled stpres are queried,
        // this means we can return true here unless store has some extra switch.
        return false;
    }
}
