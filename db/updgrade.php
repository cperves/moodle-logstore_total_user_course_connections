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
 * logstore total user connections per course
 *
 * @package    logstore_total_user_connection_per_course
 * @author Céline Pervès <cperves@unistra.fr>
 * @author Matthieu Fuchs <matfuchs@unistra.fr>
 * @copyright Université de Strasbourg 2022 {@link http://unistra.fr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_logstore_total_user_source_connections_upgrade($oldversion) {
    global $DB;
    if ($oldversion < 2022021600) {
        // For existing installations, set the new jsonformat option to off (no behaviour change).
        // New installations default to on.
        set_config('jsonformat', 0, 'logstore_last_updated_course_module');
        $dbman = $DB->get_manager();
        $table = new xmldb_table('logstore_usercourseconn');
        if ($dbman->table_exists($table)) {
            $table->add_field('dailyconnections', XMLDB_TYPE_INTEGER, '10', null, false, false, 0);
            $table->add_field('lastconnection', XMLDB_TYPE_INTEGER, '10', null, false, false, 0);
        }
        upgrade_plugin_savepoint(true, 2022021600, 'logstore', 'total_user_source_connections');
    }
    if ($oldversion < 2022021701) {
        // Rename field totalconnections on table logstore_usercourseconn to totalconnections.
        $table = new xmldb_table('logstore_usercourseconn');
        $field = new xmldb_field('totalconnections', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'userid');

        // Launch rename field totalconnections.
        $dbman->rename_field($table, $field, 'totalconnections');

        // Total_user_course_connections savepoint reached.
        upgrade_plugin_savepoint(true, 2022021701, 'logstore', 'total_user_course_connections');
    }
    return true;
}
