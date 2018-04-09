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
 * Initially developped for :
 * Université de Cergy-Pontoise
 * 33, boulevard du Port
 * 95011 Cergy-Pontoise cedex
 * FRANCE
 *
 * Adds information to the profile page of users.
 *
 * @package   local_extendedprofile
 * @copyright 2017 Laurent Guillet <laurent.guillet@u-cergy.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * File : courbeconnexionsemaine.php
 * Display the curve of time spent on site.
 */

defined('MOODLE_INTERNAL') || die;

require_once(__DIR__.'/lib.php');

function logincurve($id) {

    $xaxis = array();
    $yaxis = array();
    $i = 0;

    global $USER, $DB;

    $context = context_system::instance();

    if ($id == $USER->id || has_capability('local/extendedprofile:viewinfo', $context)) {

        $xaxis = array();
        $yaxis = array();
        $timestampatmidnight = strtotime(date("d-m-Y", time()));
        $endperiod = time();
        $day = 0;
        $loginsql = "SELECT * from {logstore_standard_log} WHERE userid = ? AND action = ?"
                . "AND timecreated >= ? AND timecreated < ? ORDER BY timecreated ASC";

        for ($day == 0; $day < 7; $day ++) {

            $durationonday = 0;

            $listlogins = $DB->get_recordset_sql($loginsql,
                    array($id, 'loggedin', $timestampatmidnight, $endperiod));

            foreach ($listlogins as $login) {

                $sqlnextlogin = "SELECT MIN(timecreated) from {logstore_standard_log} "
                        . "WHERE userid = ? AND action = ? "
                        . "AND timecreated > ? AND timecreated < ?";

                $nextlogintime = $DB->get_field_sql($sqlnextlogin,
                        array($id, 'loggedin', $login->timecreated, $endperiod));

                if (isset($nextlogintime)) {

                    $sqllastaction = "SELECT MAX(timecreated) from {logstore_standard_log} "
                            . "WHERE userid = ? AND timecreated > ? AND timecreated < ?";

                    $lastactiontime = $DB->get_field_sql($sqllastaction,
                            array($id, $login->timecreated, $nextlogintime));
                } else {

                    $sqllastaction = "SELECT MAX(timecreated) from {logstore_standard_log} "
                            . "WHERE userid = ? AND timecreated > ? AND timecreated < ?";

                    $lastactiontime = $DB->get_field_sql($sqllastaction,
                            array($id, $login->timecreated, $endperiod));
                }

                if (isset($lastactiontime)) {

                    $durationonday += $lastactiontime - $login->timecreated;
                } else {

                    $durationonday += 900;
                }

                if (!isset($nextlogintime) && isset($lastactiontime)) {

                    $durationonday += 900;
                }

                unset($nextlogintime);
                unset($lastactiontime);
            }

            $listlogins->close();

            $yaxis[] = round($durationonday / 60);
            $xaxis[] = date("d/m", $timestampatmidnight);

            $endperiod = $timestampatmidnight;
            $timestampatmidnight = $timestampatmidnight - 24 * 3600;
        }

        $orderedyaxis = array();
        $orderedxaxis = array();

        for ($i = 0; $i < 7; $i++) {

            $orderedxaxis[$i] = $xaxis[6 - $i];
            $orderedyaxis[$i] = $yaxis[6 - $i];
        }

        $chart = new \core\chart_line();
        $series = new \core\chart_series(get_string('timeperday', 'local_extendedprofile'), $orderedyaxis);
        $chart->get_yaxis(0, true)->set_label(get_string('timeinminutes', 'local_extendedprofile'));
        $chart->add_series($series);
        $chart->set_labels($orderedxaxis);
        $chart->set_title(get_string('logingraph', 'local_extendedprofile'));

        return $chart;
    }

    return null;
}