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
 * File : lib.php
 * Library file
 */

defined('MOODLE_INTERNAL') || die;
require_once($CFG->dirroot . '/my/lib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->libdir.'/filelib.php');
require_once('courbeconnexionsemaine.php');


function local_extendedprofile_myprofile_navigation (core_user\output\myprofile\tree $tree, $user) {

    if (isloggedin()) {

        global $DB, $CFG, $USER, $OUTPUT, $SITE, $PAGE;

        $context = context_system::instance();

        $pictureheight = 100;
        $userpicture = $OUTPUT->user_picture($user,
                array('size' => $pictureheight, 'alttext' => false, 'link' => false));
        $picturearray = explode('"', $userpicture);
        $pictureurl = $picturearray[1];

        $contactwithimage = get_string('contactinfo', 'local_extendedprofile').
                "<br><br><img src =  $pictureurl />";

        $categorycontactinfo = new core_user\output\myprofile\category('contactinfo',
                    $contactwithimage, 'contact');
        $tree->add_category($categorycontactinfo);

        $name = get_string('name', 'local_extendedprofile')." : $user->lastname";
        $firstname = get_string('firstname', 'local_extendedprofile')." : $user->firstname";
        $mail = get_string('mail', 'local_extendedprofile')." : $user->email";

        // Ne l'afficher que si utilisateur ou admin.

        $rolestudentid = $DB->get_record('role', array('shortname' => 'localstudent'))->id;
        $roleteacherid = $DB->get_record('role', array('shortname' => 'localteacher'))->id;
        $rolestaffid = $DB->get_record('role', array('shortname' => 'localstaff'))->id;
        $contextsystemid = context_system::instance()->id;

        if ($DB->record_exists('role_assignments',
                array('userid' => $user->id, 'roleid' => $roleteacherid,
                    'contextid' => $contextsystemid)) || $DB->record_exists('role_assignments',
                array('userid' => $user->id, 'roleid' => $roleteacherid,
                    'contextid' => $contextsystemid))) {

            $login = "";
        } else {

            $login = get_string('login', 'local_extendedprofile')." : $user->username";
        }

        if ($DB->record_exists('role_assignments',
                array('userid' => $user->id, 'roleid' => $rolestudentid,
                    'contextid' => $contextsystemid))) {

            $idnumber = get_string('idnumberstudent', 'local_extendedprofile')." : $user->idnumber";

        } else if ($DB->record_exists('role_assignments',
                array('userid' => $user->id, 'roleid' => $roleteacherid,
                    'contextid' => $contextsystemid)) || $DB->record_exists('role_assignments',
                array('userid' => $user->id, 'roleid' => $roleteacherid,
                    'contextid' => $contextsystemid))) {

            $idnumber = "";

        } else {

            $idnumber = get_string('idnumber', 'local_extendedprofile')." : $user->idnumber";
        }

        $dbman = $DB->get_manager();

        $nodename = new core_user\output\myprofile\node('contactinfo', 'name', $name);
        $nodefirstname = new core_user\output\myprofile\node('contactinfo', 'firstname', $firstname);
        $nodemail = new core_user\output\myprofile\node('contactinfo', 'mail', $mail);
        $nodelogin = new core_user\output\myprofile\node('contactinfo', 'login', $login);
        $nodeidnumber = new core_user\output\myprofile\node('contactinfo', 'idnumber', $idnumber);

        if (!isset($categorycontactinfo->nodes[$nodename->name])) {
            $categorycontactinfo->add_node($nodename);
        }
        if (!isset($categorycontactinfo->nodes[$nodefirstname->name])) {
            $categorycontactinfo->add_node($nodefirstname);
        }
        if (!isset($categorycontactinfo->nodes[$nodemail->name])) {
            $categorycontactinfo->add_node($nodemail);
        }
        if (!isset($categorycontactinfo->nodes[$nodelogin->name])) {
            $categorycontactinfo->add_node($nodelogin);
        }

        if ($dbman->table_exists('local_usercreation_type')) {

            if ($DB->record_exists('local_usercreation_type', array('userid' => $user->id))) {

//                $listtypeteacher = $DB->get_records('local_usercreation_type', array('userid' => $user->id));
//
//                $htmllistteachertype = "<ul>";
//                foreach ($listtypeteacher as $typeteacher) {
//
//                    $htmllistteachertype .= "<li>".$typeteacher->typeteacher."</li>";
//                }
//                $htmllistteachertype .= "</ul>";
//                $typeteacherstring = get_string('typeteacher', 'local_extendedprofile').$htmllistteachertype;
//                $nodetypeteacher = new core_user\output\myprofile\node('contactinfo', 'typeteacher',
//                    $typeteacherstring);
//
//                if (!isset($categorycontactinfo->nodes[$nodetypeteacher->name])) {
//
//                    $categorycontactinfo->add_node($nodetypeteacher);
//                }
            }
        }

        $studentrole = $DB->get_record('role', array('shortname' => 'localstudent'))->id;
        $systemcontextid = $DB->get_record('context', array('contextlevel' => CONTEXT_SYSTEM))->id;

        if ($DB->record_exists('role_assignments',
                array('roleid' => $studentrole, 'contextid' => $systemcontextid))) {

            if (!isset($categorycontactinfo->nodes[$nodeidnumber->name])) {
                $categorycontactinfo->add_node($nodeidnumber);
            }

            if ($dbman->table_exists('local_usercreation_vet')) {

                $listvets = $DB->get_records('local_usercreation_vet', array('studentid' => $user->id));
                $nbrevets = 0;

                $listvetsstring = get_string('listvets', 'local_extendedprofile')." : ";
                $tabvetstrings = array();

                foreach ($listvets as $vet) {

                    $yearindex = substr($vet->vetcode, 1, 4);

                    if (is_numeric($yearindex) && substr($vet->vetcode, 0, 1) == 'Y') {

                        $nextyearindex = $yearindex + 1;

						if (isset($tabvetstrings[$yearindex])) {

							$tabvetstrings[$yearindex] .= "<br>&nbsp&nbsp&nbsp&nbsp$yearindex-$nextyearindex :"
                                . " $vet->vetname";
						} else {

							$tabvetstrings[$yearindex] = "<br>&nbsp&nbsp&nbsp&nbsp$yearindex-$nextyearindex :"
                                . " $vet->vetname";
						}
                    } else {

                        $tabvetstrings[99999] .= "<br>&nbsp&nbsp&nbsp&nbsp".
                                get_string('other', 'local_extendedprofile')." : $vet->vetname";
                    }

                    $nbrevets++;
                }

                foreach ($tabvetstrings as $vetstring) {

                    $listvetsstring .= $vetstring;
                }

                if ($nbrevets == 0) {

                    $listvetsstring .= get_string('novet', 'local_extendedprofile');
                }

                $nodelistvets = new core_user\output\myprofile\node('contactinfo',
                        'listvets', $listvetsstring, 'idnumber', null, null);

                if (!isset($categorycontactinfo->nodes[$nodelistvets->name])) {
                    $categorycontactinfo->add_node($nodelistvets);
                }
            }

            $sqlcohorts = "SELECT * FROM {cohort} WHERE id IN "
                    . "(SELECT cohortid FROM {cohort_members} WHERE userid = $user->id) AND visible = 1";

            $listcohorts = $DB->get_records_sql($sqlcohorts);

            $listcohortsstring = get_string('cohorts', 'local_extendedprofile')." : ";

            foreach ($listcohorts as $cohort) {

                $listcohortsstring .= "<br>&nbsp&nbsp&nbsp&nbsp$cohort->name";
            }

            $listcohortsstring .= "";

            if (!isset($listcohorts)) {

                $listcohortsstring .= get_string('nocohort', 'local_extendedprofile');
            }

            $nodelistcohorts = new core_user\output\myprofile\node('contactinfo',
                        'listcohorts', $listcohortsstring, 'listvets', null, null);

            if (!isset($categorycontactinfo->nodes[$nodelistcohorts->name])) {
                $categorycontactinfo->add_node($nodelistcohorts);
            }
        }

        // Isoler les PERSO-COLLAB et les traiter différemment.

        $sqlrootcoursescategories = "SELECT * FROM {course_categories} WHERE parent = 0 AND visible = 1 AND"
                . " idnumber NOT LIKE 'PERSO' AND idnumber NOT LIKE 'COLLAB'";

        $listrootcoursecategories = $DB->get_records_sql($sqlrootcoursescategories);
        $previouscategory = null;

        if ($user->id == $USER->id || has_capability('local/extendedprofile:viewinfo', $context)) {

            $rolestudentid = $DB->get_record('role', array('shortname' => 'student'))->id;
            $roleeditingteacherid = $DB->get_record('role', array('shortname' => 'editingteacher'))->id;
            $roleteacherid = $DB->get_record('role', array('shortname' => 'teacher'))->id;

            foreach ($listrootcoursecategories as $rootcoursecategory) {

                $subcategorysql = "SELECT * FROM {course_categories} WHERE"
                        . " path LIKE '".$rootcoursecategory->path."' OR "
                        . "path LIKE '".$rootcoursecategory->path."/%'";

                $listsubcategories = $DB->get_records_sql($subcategorysql);

                $categoryteachedidentifier = "teachedcourses".$rootcoursecategory->idnumber;
                $categoryfollowedidentifier = "followedcourses".$rootcoursecategory->idnumber;

                if (is_numeric(substr($rootcoursecategory->idnumber, 1))) {

                    $year = substr($rootcoursecategory->idnumber, 1);
                    $nextyear = $year + 1;

                    $categoryteachedname = get_string('teachedcourses', 'local_extendedprofile')
                            ." ".$year."-".$nextyear;
                    $categoryfollowedname = get_string('followedcourses', 'local_extendedprofile')
                            ." ".$year."-".$nextyear;
                } else {

                    $categoryteachedname = $rootcoursecategory->name." ".get_string('teached',
                            'local_extendedprofile');
                    $categoryfollowedname = $rootcoursecategory->name." ".get_string('followed',
                            'local_extendedprofile');
                }

                unset($listteachednodes);
                unset($listfollowednodes);

                foreach ($listsubcategories as $subcategory) {

                    unset($listteachedcourses);
                    unset($listfollowedcourses);

                    $sqlcontext = "SELECT * FROM {context} WHERE contextlevel = ".CONTEXT_COURSE." AND"
                            . " instanceid IN (SELECT id FROM {course} WHERE category = $subcategory->id)";
                    $listcontexts = $DB->get_records_sql($sqlcontext);

                    foreach ($listcontexts as $context) {

                        $isstudent = $DB->record_exists('role_assignments',
                                array('userid' => $user->id, 'contextid' => $context->id,
                                    'roleid' => $rolestudentid));

                        $iseditingteacher = $DB->record_exists('role_assignments',
                                array('userid' => $user->id, 'contextid' => $context->id,
                                    'roleid' => $roleeditingteacherid));

                        $isteacher = $DB->record_exists('role_assignments',
                                array('userid' => $user->id, 'contextid' => $context->id,
                                    'roleid' => $roleteacherid));

                        $course = $DB->get_record('course', array('id' => $context->instanceid));

                        if ($isstudent) {

                            $listfollowedcourses[] = $course;
                        }

                        if ($iseditingteacher || $isteacher) {

                            $listteachedcourses[] = $course;
                        }
                    }

                    if (isset($listteachedcourses)) {

                        // Créer le noeud.

                        $content = "";
                        $first = true;

                        foreach ($listteachedcourses as $teachedcourse) {

                            if (!$first) {

                                $content .= "<br>";
                            }

                            $courseurl = new moodle_url('/course/view.php',
                                    array('id' => $teachedcourse->id));
                            $content .= "<a href=$courseurl>".$teachedcourse->shortname."</a>";

                            $first = false;
                        }

                        $nodeidentifier = "teached".$subcategory->idnumber;

                        $node = new core_user\output\myprofile\node($categoryteachedidentifier,
                                    $nodeidentifier, $subcategory->name, null, null, $content);

                        $listteachednodes[] = $node;
                    }

                    if (isset($listfollowedcourses)) {

                        // Créer le noeud.

                        $content = "";
                        $first = true;

                        foreach ($listfollowedcourses as $followedcourse) {

                            if (!$first) {

                                $content .= "<br>";
                            }

                            $courseurl = new moodle_url('/course/view.php',
                                    array('id' => $followedcourse->id));
                            $content .= "<a href=$courseurl>".$followedcourse->shortname."</a>";

                            $first = false;
                        }

                        $nodeidentifier = "followed".$subcategory->idnumber;

                        $node = new core_user\output\myprofile\node($categoryfollowedidentifier,
                                    $nodeidentifier, $subcategory->name, null, null, $content);

                        $listfollowednodes[] = $node;
                    }
                }

                // Créer les catégories ici.

                if (isset($listteachednodes)) {

                    $newcategory = new core_user\output\myprofile\category($categoryteachedidentifier,
                            $categoryteachedname, $previouscategory);
                    $tree->add_category($newcategory);

                    foreach ($listteachednodes as $node) {

                        $newcategory->add_node($node);
                    }

                    $previouscategory = $categoryteachedidentifier;
                }

                if (isset($listfollowednodes)) {

                    $newcategory = new core_user\output\myprofile\category($categoryfollowedidentifier,
                            $categoryfollowedname, $previouscategory);
                    $tree->add_category($newcategory);

                    foreach ($listfollowednodes as $node) {

                        $newcategory->add_node($node);
                    }

                    $previouscategory = $categoryfollowedidentifier;
                }
            }

            // Espaces collaboratifs.

            $collabcategory = $DB->get_record('course_categories', array('idnumber' => 'COLLAB'));

            $listcollabcourses = $DB->get_records('course', array('category' => $collabcategory->id));

            $contentcollab = "";
            $firstcollab = true;

            foreach ($listcollabcourses as $collabcourse) {

                $coursecontext = $DB->get_record('context',
                        array('contextlevel' => CONTEXT_COURSE, 'instanceid' => $collabcourse->id));

                if ($DB->record_exists('role_assignments',
                        array('userid' => $user->id, 'contextid' => $coursecontext->id))) {

                    if (!$firstcollab) {

                        $contentcollab .= "<br>";
                    }

                    $courseurl = new moodle_url('/course/view.php',
                            array('id' => $collabcourse->id));
                    $contentcollab .= "<a href=$courseurl>".$collabcourse->shortname."</a>";

                    $firstcollab = false;
                }
            }

            if ($contentcollab != "") {

                $newcategory = new core_user\output\myprofile\category('collabcourses',
                        get_string('collabcourses', 'local_extendedprofile'), $previouscategory);
                $tree->add_category($newcategory);

                $node = new core_user\output\myprofile\node('collabcourses',
                        'collabnode', $collabcategory->name, null, null, $contentcollab);

                $newcategory->add_node($node);

                $previouscategory = 'collabcourses';
            }

            // Espaces personnels.

            $persocategory = $DB->get_record('course_categories', array('idnumber' => 'PERSO'));

            $listpersocourses = $DB->get_records('course', array('category' => $persocategory->id));

            $contentperso = "";
            $firstperso = true;

            foreach ($listpersocourses as $persocourse) {

                $coursecontext = $DB->get_record('context',
                        array('contextlevel' => CONTEXT_COURSE, 'instanceid' => $persocourse->id));

                if ($DB->record_exists('role_assignments',
                        array('userid' => $user->id, 'contextid' => $coursecontext->id))) {

                    if (!$firstperso) {

                        $contentperso .= "<br>";
                    }

                    $courseurl = new moodle_url('/course/view.php',
                            array('id' => $persocourse->id));
                    $contentperso .= "<a href=$courseurl>".$persocourse->shortname."</a>";

                    $firstperso = false;
                }
            }

            if ($contentperso != "") {

                $newcategory = new core_user\output\myprofile\category('persocourses',
                        get_string('persocourses', 'local_extendedprofile'), $previouscategory);
                $tree->add_category($newcategory);

                $node = new core_user\output\myprofile\node('persocourses',
                        'personode', $persocategory->name, null, null, $contentperso);

                $newcategory->add_node($node);

                $previouscategory = 'persocourses';
            }

            $chart = logincurve($user->id);

            if ($chart) {

                $categorylogingraph = new core_user\output\myprofile\category('logingraph',
                       get_string('logingraph', 'local_extendedprofile'));
                $tree->add_category($categorylogingraph);

                $graphnode = new core_user\output\myprofile\node('logingraph',
                                'graph', $OUTPUT->render($chart));

                if (!isset($categorylogingraph->nodes[$graphnode->name])) {
                    $categorylogingraph->add_node($graphnode);
                }
            }

            foreach ($listrootcoursecategories as $rootcoursecategory) {

                $subcategorysql = "SELECT * FROM {course_categories} WHERE"
                        . " path LIKE '".$rootcoursecategory->path."' OR "
                        . "path LIKE '".$rootcoursecategory->path."/%'";

                $listsubcategories = $DB->get_records_sql($subcategorysql);

                $categorytableidentifier = "tablecourses".$rootcoursecategory->idnumber;

                if (is_numeric(substr($rootcoursecategory->idnumber, 1))) {

                    $year = substr($rootcoursecategory->idnumber, 1);
                    $nextyear = $year + 1;

                    $categorytablename = get_string('tablecourses', 'local_extendedprofile')
                            ." ".$year."-".$nextyear;
                } else {

                    $categorytablename = get_string('tablecourses', 'local_extendedprofile')
                            ." ".$rootcoursecategory->name;
                }

                $categorytablecourses = new core_user\output\myprofile\category($categorytableidentifier,
                        $categorytablename, null);
                $tree->add_category($categorytablecourses);

                $tablecontent = createcoursetable($rootcoursecategory, $user);

                if ($tablecontent != null) {

                    $tablenode = new core_user\output\myprofile\node($categorytableidentifier,
                        get_string('table', 'local_extendedprofile'), html_writer::table($tablecontent));

                    $categorytablecourses->add_node($tablenode);
                }
            }
        }
    }
}

function createcoursetable($category, $user) {

    global $DB;

    $table = new html_table();
    $table->head  = array(get_string('coursename', 'local_extendedprofile'),
            get_string('teachers', 'local_extendedprofile'), get_string('chatactivity', 'local_extendedprofile'),
            get_string('assignmentsdelivered', 'local_extendedprofile'),
            get_string('quiz', 'local_extendedprofile'), get_string('activities', 'local_extendedprofile'),
            get_string('minimumconsultationtime', 'local_extendedprofile'),
            get_string('lastconnexiondate', 'local_extendedprofile'));
    $table->colclasses = array('leftalign coursename', 'leftalign teachers', 'leftalign chat',
        'leftalign assign', 'leftalign quiz', 'leftalign workshop',
        'leftalign timespent', 'leftalign lastlogin');
    $table->id = 'table'.$category->id;
    $table->attributes['class'] = 'admintable generaltable';

    $subcategorysql = "SELECT * FROM {course_categories} WHERE path LIKE '".$category->path."' OR "
            . "path LIKE '".$category->path."/%'";

    $listsubcategories = $DB->get_records_sql($subcategorysql);

    $rolestudentid = $DB->get_record('role', array('shortname' => 'student'))->id;

    $listfollowedcourses = array();

    foreach ($listsubcategories as $subcategory) {

        $sqlcontext = "SELECT * FROM {context} WHERE contextlevel = ".CONTEXT_COURSE." AND"
                . " instanceid IN (SELECT id FROM {course} WHERE category = $subcategory->id)";
        $listcontexts = $DB->get_records_sql($sqlcontext);

        foreach ($listcontexts as $context) {

            $isstudent = $DB->record_exists('role_assignments',
                    array('userid' => $user->id, 'contextid' => $context->id,
                        'roleid' => $rolestudentid));

            $course = $DB->get_record('course', array('id' => $context->instanceid));

            if ($isstudent) {

                $listfollowedcourses[] = $course;
            }
        }
    }

    $totalchat = 0;
    $totalusedchat = 0;
    $totalassign = 0;
    $totalusedassign = 0;
    $totalquiz = 0;
    $totalusedquiz = 0;
    $totalworkshop = 0;
    $totalusedworkshop = 0;
    $totaltimespent = 0;

    foreach ($listfollowedcourses as $followedcourse) {

        $line = array();

        // Course name.

        $line[] = $followedcourse->fullname;

        // Teachers list.

        $coursecontext = $DB->get_record('context',
                array('contextlevel' => CONTEXT_COURSE, 'instanceid' => $followedcourse->id));

        $editingteacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'))->id;
        $teacherrole = $DB->get_record('role', array('shortname' => 'teacher'))->id;

        $sqlteacherrole = "SELECT * FROM {role_assignments} WHERE contextid = $coursecontext->id AND "
                . "(roleid = $editingteacherrole OR roleid = $teacherrole)";

        $listteachersrole = $DB->get_records_sql($sqlteacherrole);

        if (isset($listteachersrole)) {

            $teacherline = "<li>";

            foreach ($listteachersrole as $teacherrole) {

                $teacher = $DB->get_record('user', array('id' => $teacherrole->userid));

                $teacherline .= "<ul>$teacher->firstname $teacher->lastname</ul>";
            }

            $teacherline .= "</li>";

            $line[] = $teacherline;
        } else {

            $line[] = "";
        }

        // Chat.

        $listchats = $DB->get_records('chat', array('course' => $followedcourse->id));

        $localchat = 0;
        $localusedchat = 0;

        foreach ($listchats as $chat) {

            $localchat++;
            $totalchat++;

            if ($DB->record_exists('chat_messages', array('chatid' => $chat->id,
                'userid' => $user->id, 'issystem' => 0))) {

                $localusedchat++;
                $totalusedchat++;
            }
        }

        $line[] = maketablecell($localusedchat, $localchat);

        // Assign.

        $listassigns = $DB->get_records('assign', array('course' => $followedcourse->id));

        $localassign = 0;
        $localusedassign = 0;

        foreach ($listassigns as $assign) {

            $localassign++;
            $totalassign++;

            if ($DB->record_exists('assign_submission', array('assignment' => $assign->id,
                'userid' => $user->id, 'status' => 'submitted'))) {

                $localusedassign++;
                $totalusedassign++;
            }
        }

        $line[] = maketablecell($localusedassign, $localassign);

        // Quiz.

        $listquizs = $DB->get_records('assign', array('course' => $followedcourse->id));

        $localquiz = 0;
        $localusedquiz = 0;

        foreach ($listquizs as $quiz) {

            $localquiz++;
            $totalquiz++;

            if ($DB->record_exists('quiz_attempts', array('quiz' => $quiz->id,
                'userid' => $user->id, 'state' => 'finished'))) {

                $localusedquiz++;
                $totalusedquiz++;
            }
        }

        $line[] = maketablecell($localusedquiz, $localquiz);

        // Workshop.

        $listworkshops = $DB->get_records('workshop', array('course' => $followedcourse->id));

        $localworkshop = 0;
        $localusedworkshop = 0;

        foreach ($listworkshops as $workshop) {

            $localworkshop++;
            $totalworkshop++;

            if ($DB->record_exists('workshop_submissions', array('workshopid' => $workshop->id,
                'authorid' => $user->id))) {

                $localusedworkshop++;
                $totalusedworkshop++;
            }
        }

        $line[] = maketablecell($localusedworkshop, $localworkshop);

        // Connection time.

        $timespent = report_consultation_totale_course($followedcourse->id, $user->id);
        $totaltimespent += $timespent;

        $line[] = $timespent;

        // Last login.

        $sqllog = "select max(timecreated) as temps from mdl_logstore_standard_log where"
                . " userid = $user->id and courseid = $followedcourse->id";
        $resultlog = $DB->get_record_sql($sqllog);
        $datederniereconnexion = date('d/m/Y', $resultlog->temps);
        $heurederniereconnexion = date('H:i:s', $resultlog->temps);

        if (isset($resultlog->temps)) {

            $line[] = "$datederniereconnexion $heurederniereconnexion";
        } else {

            $line[] = get_string('never', 'local_extendedprofile');
        }
        $data[] = $row = new html_table_row($line);
    }

    if (!isset($data)) {

        return null;
    } else {

        $lastline[] = get_string('average', 'local_extendedprofile');
        $lastline[] = "";
        $lastline[] = maketablecell($totalusedchat, $totalchat);
        $lastline[] = maketablecell($totalusedassign, $totalassign);
        $lastline[] = maketablecell($totalusedquiz, $totalquiz);
        $lastline[] = maketablecell($totalusedworkshop, $totalworkshop);
        $lastline[] = $totaltimespent;
        $lastline[] = "";
        $data[] = $row = new html_table_row($lastline);

        $table->data = $data;

        return $table;
    }
}

function maketablecell ($data1, $data2) {

    if (is_numeric($data1) && is_numeric($data2)) {

        if ($data2 == 0) {

            return "";
        } else {

            $percent = round(($data1 / $data2) * 100, 1);

            if ($percent == 100) {

                return "<FONT COLOR='#66CD00'><strong>$data1/$data2 ($percent%)</strong>";
            } else {

                return "<FONT COLOR='#FF0000'><strong>$data1/$data2 ($percent%)</strong>";
            }
        }
    } else {

        return "";
    }
}

// Tableau cours détaillé => étudiant.
function report_consultation_totale_course($courseid, $userid) {
    global $DB;

    $inthiscourse = 0;
    $timespent = 0;
    $previoustime = -1;
    $timeout = 15 * 60;

    $sql = "SELECT timecreated, courseid FROM mdl_logstore_standard_log WHERE userid = $userid "
            . "AND courseid = $courseid ORDER BY timecreated ASC";

    $useractions = $DB->get_recordset_sql($sql);
    unset($sql);

    foreach ($useractions as $useraction) {

        if ($previoustime == -1) {

            $previoustime = $useraction->timecreated;
        }
        if (($useraction->timecreated - $previoustime) > $timeout) {

            $timespent += $timeout;
        } else {

            $timespent += ($useraction->timecreated - $previoustime);
        }

        $previoustime = $useraction->timecreated;
    }

    if ($useractions->valid()) {

        $timespent += $timeout;
    }

    $useractions->close();

    return round($timespent / 60, 0);
}
