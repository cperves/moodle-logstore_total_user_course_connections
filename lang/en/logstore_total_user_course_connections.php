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
 * @package    logstore_total_user_course_connections
 * @author Céline Pervès <cperves@unistra.fr>
 * @author Matthieu Fuchs <matfuchs@unistra.fr>
 * @copyright Université de Strasbourg 2022 {@link http://unistra.fr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'total number of connections of a user in a course log store';
$string['module_connections'] = 'Module connections take into account.';
$string['module_connections_desc'] = 'Module connections are take into account into total course connections.';
$string['privacy:metadata:log'] = 'A collection of total number of connections for a user in a course events';
$string['privacy:metadata:log:userid'] = 'The ID of the user who triggered this event';
$string['privacy:metadata:log:courseid'] = 'The course ID concerned with this event';
$string['privacy:metadata:log:cmid'] = 'The course module id concerned with this event';
$string['privacy:metadata:log:totalconnections'] = 'The total number o fconnections for a given user in a given course';
$string['privacy:metadata:log:dailyconnections'] = 'The number of daily connections for a given user in a given course';
$string['privacy:metadata:log:lastconnection'] = 'Last user connection to a course';
