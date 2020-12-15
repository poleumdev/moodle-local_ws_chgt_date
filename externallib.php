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
 * External Web Service.
 *
 * @package
 * @copyright  2020 Universite du Mans
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->libdir . "/externallib.php");

/**
 * Definition of web services.
 * @copyright  2020 Universite du Mans
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_wschangedate_external extends external_api {

    /**
     * Define the description of the web service parameters.
     * @return the description of the web service parameters.
     */
    public static function changedate_parameters() {
        $newbegtxt = 'nouvelle date de démarrage du cours yyyy-mm-jj';
        return new external_function_parameters(array(
            'courseid' => new external_value(PARAM_INT, 'identifiant du cours', VALUE_DEFAULT, 1),
            'newbeg' => new external_value(PARAM_TEXT, $newbegtxt, VALUE_DEFAULT, '2000-01-01'),
            ));
    }

    /**
     * The web service method.
     * @param string $courseid The course id.
     * @param String $newbeg new start date for the course.
     * @return result of process, format json true or false with reason.
     */
    public static function changedate($courseid, $newbeg) {
        global $USER;
        global $DB;

        $params = self::validate_parameters (self::changedate_parameters (), array ('courseid' => $courseid,
                    'newbeg' => $newbeg));

        // Processing.
        $ret = self::changedates($courseid, $newbeg);

        return json_encode($ret);
    }

    /**
     * Define return values for changedate methode.
     * @return the description of the returned values.
     */
    public static function changedate_returns() {
        $returntxt = "chaine json exposant le resultat de la tentative de modification de date";
        return new external_value ( PARAM_TEXT, $returntxt);
    }

    /**
     * Change course's dates.
     * clone of https://github.com/moodle/moodle/blob/master/lib/moodlelib.php ::reset_course_userdata($data)
     * @return returns a json structure with the result of the execution.
     */
    public static function changedates($courseid, $newbeg) {
        global $DB, $CFG;
        require_once($CFG->libdir.'/completionlib.php');
        require_once($CFG->dirroot.'/completion/criteria/completion_criteria_date.php');

        $course = $DB->get_record('course', array('id' => $courseid));
        $ret = new stdClass();

        if (!isset($course->id)) {
            $ret->result = "false";
            $ret->raison = "aucun cours ne correspond a id [" . $courseid. "]";
            return $ret;
        }
        if (!isset($course->startdate)) {
            $ret->result = "false";
            $ret->raison = "Le cours ne possède pas de date de depart !!";
            return $ret;
        }
        try {
            // Compute timeshift.
            $origin = new DateTime();
            $origin->setTimestamp($course->startdate);
            $target = new DateTime($newbeg);
            $timeshift = $target->getTimestamp() - $origin->getTimestamp();

            if ($timeshift != 0) {
                // Change course start data.
                $DB->set_field('course', 'startdate', $target->getTimestamp(), array('id' => $courseid));
                if (isset($course->enddate) && $course->enddate > 0) {
                    $DB->set_field('course', 'enddate', $course->enddate + $timeshift, array('id' => $courseid));
                }
                if ($CFG->enableavailability) {
                    \availability_date\condition::update_all_dates($courseid, $timeshift);
                }

                $modinfo = get_fast_modinfo($courseid);
                foreach ($modinfo->get_cms() as $cm) {
                    if ($cm->completion && !empty($cm->completionexpected)) {
                        $DB->set_field('course_modules', 'completionexpected', $cm->completionexpected + $timeshift,
                            array('id' => $cm->id));
                    }
                }
                // Update course date completion criteria.
                \completion_criteria_date::update_date($courseid, $timeshift);

                self::trt_assign($course, $timeshift);
                self::trt_assignment($course, $timeshift);
                self::trt_choice($course, $timeshift);
                self::trt_forum($course, $timeshift);
                self::trt_glossary($course, $timeshift);
                self::trt_lesson($course, $timeshift);
                self::trt_quiz($course, $timeshift);
                self::trt_scorm($course, $timeshift);
                self::trt_workshop($course, $timeshift);
                self::trt_questionnaire($course, $timeshift);
                self::trt_data($course, $timeshift);

                rebuild_course_cache($courseid, true);
                $ret->result = "true";
                $ret->timeshift = $timeshift;
            } else {
                $ret->result = "false";
                $ret->raison = "Interval de temps vaut 0";
            }
        } catch (Exception $e) {
            $ret->result = "false";
            $ret->raison = "Err : " . $e->getMessage();
        }
        return $ret;
    }

    public static function trt_assign($course, $timeshift) {
        global $DB, $CFG;

        if (!$assigns = $DB->get_records('assign', array('course' => $course->id))) {
            return false;
        }

        require_once($CFG->dirroot.'/mod/assign/lib.php');
        foreach ($assigns as $assign) {
            $maj = false;
            if (isset($assign->duedate) && $assign->duedate > 0) {
                $assign->duedate = $assign->duedate + $timeshift;
                $maj = true;
            }
            if (isset($assign->allowsubmissionsfromdate) && $assign->allowsubmissionsfromdate > 0) {
                $assign->allowsubmissionsfromdate = $assign->allowsubmissionsfromdate + $timeshift;
                $maj = true;
            }
            if (isset($assign->gradingduedate) && $assign->gradingduedate > 0) {
                $assign->gradingduedate = $assign->gradingduedate + $timeshift;
                $maj = true;
            }
            if (isset($assign->cutoffdate) && $assign->cutoffdate > 0) {
                $assign->cutoffdate = $assign->cutoffdate + $timeshift;
                $maj = true;
            }
            if ($maj) {
                $DB->update_record('assign', $assign);
            }
        }
        assign_refresh_events($course->id);
    }

    public static function trt_assignment($course, $timeshift) {
        global $DB;

        if (!$assignments = $DB->get_records('assignment', array('course' => $course->id))) {
            return false;
        }
        foreach ($assignments as $assignment) {
            $maj = false;
            if (isset($assignment->timedue) && $assignment->timedue > 0) {
                $assign->timedue = $assign->timedue + $timeshift;
                $maj = true;
            }
            if (isset($assignment->timeavailable) && $assignment->timeavailable > 0) {
                $assignment->timeavailable = $assignment->timeavailable + $timeshift;
                $maj = true;
            }
            if (isset($assignment->timemodified) && $assignment->timemodified > 0) {
                $assignment->timemodified = $assignment->timemodified + $timeshift;
                $maj = true;
            }

            if ($maj) {
                $DB->update_record('assignment', $assignment);
            }
        }
    }

    public static function trt_choice($course, $timeshift) {
        global $DB, $CFG;

        if (!$choices = $DB->get_records('choice', array('course' => $course->id))) {
            return false;
        }

        require_once($CFG->dirroot.'/mod/choice/lib.php');
        foreach ($choices as $mod) {
            $maj = false;
            if (isset($mod->timeopen) && $mod->timeopen > 0) {
                $mod->timeopen = $mod->timeopen + $timeshift;
                $maj = true;
            }
            if (isset($mod->timeclose) && $mod->timeclose > 0) {
                $mod->timeclose = $mod->timeclose + $timeshift;
                $maj = true;
            }

            if ($maj) {
                $DB->update_record('choice', $mod);
            }
        }
        choice_refresh_events($course->id);
    }

    /**
     * Traitement des forums avant Moodle 3.8.
     * En effet l'activité Forum évolue en version 3.8 il disposera de duedate
     * et la mise à jour des calendrier sera en fonction de cette colonne.
     * Il sera alors nécessaire de :
     *  require_once($CFG->dirroot.'/mod/forum/locallib.php');
     * Puis de récupérer cmid avec $cm = get_coursemodule_from_id('forum', $course->id);
     * pour mettre a jours les calendriers avec : forum_update_calendar($mod, $cm->id);
     */
    public static function trt_forum($course, $timeshift) {
        global $DB;

        if (!$forums = $DB->get_records('forum', array('course' => $course->id))) {
            return false;
        }
        foreach ($forums as $mod) {
            $maj = false;
            if (isset($mod->assesstimestart) && $mod->assesstimestart > 0) {
                $mod->assesstimestart = $mod->assesstimestart + $timeshift;
                $maj = true;
            }
            if (isset($mod->assesstimefinish) && $mod->assesstimefinish > 0) {
                $mod->assesstimefinish = $mod->assesstimefinish + $timeshift;
                $maj = true;
            }

            if ($maj) {
                $DB->update_record('forum', $mod);
            }
        }
    }

    public static function trt_glossary($course, $timeshift) {
        global $DB;

        if (!$glossaries = $DB->get_records('glossary', array('course' => $course->id))) {
            return false;
        }
        foreach ($glossaries as $mod) {
            $maj = false;
            if (isset($mod->assesstimestart) && $mod->assesstimestart > 0) {
                $mod->assesstimestart = $mod->assesstimestart + $timeshift;
                $maj = true;
            }
            if (isset($mod->assesstimefinish) && $mod->assesstimefinish > 0) {
                $mod->assesstimefinish = $mod->assesstimefinish + $timeshift;
                $maj = true;
            }

            if ($maj) {
                $DB->update_record('glossary', $mod);
            }
        }
    }

    public static function trt_lesson($course, $timeshift) {
        global $DB, $CFG;

        if (!$lessons = $DB->get_records('lesson', array('course' => $course->id))) {
            return false;
        }

        require_once($CFG->dirroot.'/mod/lesson/lib.php');
        foreach ($lessons as $mod) {
            $maj = false;
            if (isset($mod->available) && $mod->available > 0) {
                $mod->available = $mod->available + $timeshift;
                $maj = true;
            }
            if (isset($mod->deadline) && $mod->deadline > 0) {
                $mod->deadline = $mod->deadline + $timeshift;
                $maj = true;
            }

            if ($maj) {
                $DB->update_record('lesson', $mod);
            }
        }
        lesson_refresh_events($course->id);
    }

    public static function trt_quiz($course, $timeshift) {
        global $DB, $CFG;

        if (!$quizs = $DB->get_records('quiz', array('course' => $course->id))) {
            return false;
        }

        require_once($CFG->dirroot.'/mod/quiz/lib.php');
        foreach ($quizs as $mod) {
            $maj = false;
            if (isset($mod->timeopen) && $mod->timeopen > 0) {
                $mod->timeopen = $mod->timeopen + $timeshift;
                $maj = true;
            }
            if (isset($mod->timeclose) && $mod->timeclose > 0) {
                $mod->timeclose = $mod->timeclose + $timeshift;
                $maj = true;
            }

            if ($maj) {
                $DB->update_record('quiz', $mod);
            }
        }
        quiz_refresh_events($course->id);
    }

    public static function trt_scorm($course, $timeshift) {
        global $DB, $CFG;

        if (!$scorms = $DB->get_records('scorm', array('course' => $course->id))) {
            return false;
        }

        require_once($CFG->dirroot.'/mod/scorm/lib.php');
        foreach ($scorms as $mod) {
            $maj = false;
            if (isset($mod->timeopen) && $mod->timeopen > 0) {
                $mod->timeopen = $mod->timeopen + $timeshift;
                $maj = true;
            }
            if (isset($mod->timeclose) && $mod->timeclose > 0) {
                $mod->timeclose = $mod->timeclose + $timeshift;
                $maj = true;
            }

            if ($maj) {
                $DB->update_record('scorm', $mod);
            }
        }
        scorm_refresh_events($course->id);
    }

    public static function trt_workshop($course, $timeshift) {
        global $DB, $CFG;

        if (!$workshops = $DB->get_records('workshop', array('course' => $course->id))) {
            return false;
        }

        require_once($CFG->dirroot.'/mod/workshop/lib.php');
        foreach ($workshops as $mod) {
            $maj = false;
            if (isset($mod->submissionstart) && $mod->submissionstart > 0) {
                $mod->submissionstart = $mod->submissionstart + $timeshift;
                $maj = true;
            }
            if (isset($mod->submissionend) && $mod->submissionend > 0) {
                $mod->submissionend = $mod->submissionend + $timeshift;
                $maj = true;
            }
            if (isset($mod->assessmentstart) && $mod->assessmentstart > 0) {
                $mod->assessmentstart = $mod->assessmentstart + $timeshift;
                $maj = true;
            }
            if (isset($mod->assessmentend) && $mod->assessmentend > 0) {
                $mod->assessmentend = $mod->assessmentend + $timeshift;
                $maj = true;
            }

            if ($maj) {
                $DB->update_record('workshop', $mod);
            }
        }
        workshop_refresh_events($course->id);
    }

    /**
     * Traitement des Questionnaire (plugin additionnel !).
     * Je ne vois pas de methode de maj des event calendar
     * dans https://github.com/PoetOS/moodle-mod_questionnaire/blob/master/lib.php
     * Aussi uniquement opendate et closedate seront mise a jour
     */
    public static function trt_questionnaire($course, $timeshift) {
        global $DB;
        $pluginman = \core_plugin_manager::instance();
        $pluginfo = $pluginman->get_plugin_info("mod_questionnaire");
        if (!isset($pluginfo)) {
            return false;
        }
        if (!$questionnaires = $DB->get_records('questionnaire', array('course' => $course->id))) {
            return false;
        }
        foreach ($questionnaires as $mod) {
            $maj = false;
            if (isset($mod->opendate) && $mod->opendate > 0) {
                $mod->opendate = $mod->opendate + $timeshift;
                $maj = true;
            }
            if (isset($mod->closedate) && $mod->closedate > 0) {
                $mod->closedate = $mod->closedate + $timeshift;
                $maj = true;
            }

            if ($maj) {
                $DB->update_record('questionnaire', $mod);
            }
        }
    }

    public static function trt_data($course, $timeshift) {
        global $DB, $CFG;
        if (!$datas = $DB->get_records('data', array('course' => $course->id))) {
            return false;
        }

        require_once($CFG->dirroot.'/mod/data/lib.php');
        foreach ($datas as $mod) {
            $maj = false;
            if (isset($mod->timeavailablefrom) && $mod->timeavailablefrom > 0) {
                $mod->timeavailablefrom = $mod->timeavailablefrom + $timeshift;
                $maj = true;
            }
            if (isset($mod->timeavailableto) && $mod->timeavailableto > 0) {
                $mod->timeavailableto = $mod->timeavailableto + $timeshift;
                $maj = true;
            }
            if (isset($mod->timeviewfrom) && $mod->timeviewfrom > 0) {
                $mod->timeviewfrom = $mod->timeviewfrom + $timeshift;
                $maj = true;
            }
            if (isset($mod->timeviewto) && $mod->timeviewto > 0) {
                $mod->timeviewto = $mod->timeviewto + $timeshift;
                $maj = true;
            }
            if (isset($mod->assesstimestart) && $mod->assesstimestart > 0) {
                $mod->assesstimestart = $mod->assesstimestart + $timeshift;
                $maj = true;
            }
            if (isset($mod->assesstimefinish) && $mod->assesstimefinish > 0) {
                $mod->assesstimefinish = $mod->assesstimefinish + $timeshift;
                $maj = true;
            }
            if ($maj) {
                $DB->update_record('data', $mod);
            }
        }
        data_refresh_events($course->id);
    }
}