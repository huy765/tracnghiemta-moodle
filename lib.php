<?php
/**
 * Library of functions and constants for module rquiz
 *
 * @copyright Lê Quốc Huy ( The Jolash )
 * @package mod_rquiz
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

defined('MOODLE_INTERNAL') || die();

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod.html) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $rquiz An object from the form in mod.html
 * @return int The id of the newly inserted rquiz record
 **/
function rquiz_add_instance($rquiz) {
    global $DB;

    $rquiz->timemodified = time();
    $rquiz->timecreated = time();

    $rquiz->status = 0;
    $rquiz->currentquestion = 0;
    $rquiz->nextendtime = 0;
    $rquiz->currentsessionid = 0;
    $rquiz->classresult = 0;
    $rquiz->questionresult = 0;

    return $DB->insert_record('rquiz', $rquiz);
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod.html) this function
 * will update an existing instance with new data.
 *
 * @param object $rquiz An object from the form in mod.html
 * @return boolean Success/Fail
 **/
function rquiz_update_instance($rquiz) {
    global $DB;

    $rquiz->timemodified = time();
    $rquiz->id = $rquiz->instance;

    $rquiz->status = 0;
    $rquiz->currentquestion = 0;
    $rquiz->nextendtime = 0;
    $rquiz->currentsessionid = 0;
    $rquiz->classresult = 0;
    $rquiz->questionresult = 0;

    return $DB->update_record('rquiz', $rquiz);
}

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 **/
function rquiz_delete_instance($id) {
    global $DB;

    if (!$rquiz = $DB->get_record('rquiz', array('id' => $id))) {
        return false;
    }

    $result = true;

    $questions = $DB->get_records('rquiz_question', array('quizid' => $id));
    foreach ($questions as $question) { // Get each question.
        $answers = $DB->get_records('rquiz_answer', array('questionid' => $question->id));
        foreach ($answers as $answer) { // Get each answer for that question
            // Delete each submission for that answer.
            $DB->delete_records('rquiz_submitted', array('answerid' => $answer->id));
        }
        $DB->delete_records('rquiz_answer', array('questionid' => $question->id)); // Delete each answer.
    }
    $DB->delete_records('rquiz_question', array('quizid' => $id)); // Delete each question.
    $DB->delete_records('rquiz_session', array('quizid' => $id)); // Delete each session.
    $DB->delete_records('rquiz', array('id' => $rquiz->id));

    return $result;
}

/**
 * Return a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @param object $course
 * @param object $user
 * @param object $mod
 * @param object $rquiz
 * @return null
 */
function rquiz_user_outline($course, $user, $mod, $rquiz) {
    return null;
}

/**
 * Print a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @param object $course
 * @param object $user
 * @param object $mod
 * @param object $rquiz
 * @return boolean
 */
function rquiz_user_complete($course, $user, $mod, $rquiz) {
    return true;
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in rquiz activities and print it out.
 * Return true if there was output, or false is there was none.
 *
 * @param object $course
 * @param bool $isteacher
 * @param int $timestart
 * @return boolean
 */
function rquiz_print_recent_activity($course, $isteacher, $timestart) {
    return false;  // True if anything was printed, otherwise false.
}

/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * @return boolean
 **/
function rquiz_cron() {
    return true;
}

/**
 * Must return an array of grades for a given instance of this module,
 * indexed by user.  It also returns a maximum allowed grade.
 *
 * Example:
 *    $return->grades = array of grades;
 *    $return->maxgrade = maximum allowed grade;
 *
 *    return $return;
 *
 * @param int $rquizid ID of an instance of this module
 * @return mixed Null or object with an array of grades and with the maximum grade
 **/
function rquiz_grades($rquizid) {
    return null;
}

/**
 * Must return an array of user records (all data) who are participants
 * for a given instance of rquiz. Must include every user involved
 * in the instance, independient of his role (student, teacher, admin...)
 * See other modules as example.
 *
 * @param int $rquizid ID of an instance of this module
 * @return mixed boolean/array of students
 **/
function rquiz_get_participants($rquizid) {
    return false;
}

/**
 * This function returns if a scale is being used by one rquiz
 * it it has support for grading and scales. Commented code should be
 * modified if necessary. See forum, glossary or journal modules
 * as reference.
 *
 * @param int $rquizid ID of an instance of this module
 * @param int $scaleid
 * @return mixed
 **/
function rquiz_scale_used($rquizid, $scaleid) {
    $return = false;

    return $return;
}

/**
 * Is the given scale used?
 * @param int $scaleid
 * @return false
 */
function rquiz_scale_used_anywhere($scaleid) {
    return false;
}

// Any other rquiz functions go here.  Each of them must have a name that
// starts with rquiz_ .

/**
 * Output the tabs.
 * @param string $currenttab
 * @param int $cmid
 * @param context $context
 * @throws coding_exception
 * @throws moodle_exception
 */
function rquiz_view_tabs($currenttab, $cmid, $context) {
    $tabs = array();
    $row = array();
    $inactive = array();
    $activated = array();

    if (has_capability('mod/rquiz:attempt', $context)) {
        $row[] = new tabobject('view', new moodle_url('/mod/rquiz/view.php', array('id' => $cmid)),
                               get_string('view', 'rquiz'));
    }
    if (has_capability('mod/rquiz:editquestions', $context)) {
        $row[] = new tabobject('edit', new moodle_url('/mod/rquiz/edit.php', array('id' => $cmid)),
                               get_string('edit', 'rquiz'));
    }
    if (has_capability('mod/rquiz:seeresponses', $context)) {
        $row[] = new tabobject('responses', new moodle_url('/mod/rquiz/responses.php', array('id' => $cmid)),
                               get_string('responses', 'rquiz'));
    }

    if ($currenttab == 'view' && count($row) == 1) {
        // No tabs for students.
        echo '<br />';
    } else {
        $tabs[] = $row;
    }

    if ($currenttab == 'responses') {
        $activated[] = 'responses';
    }

    if ($currenttab == 'edit') {
        $activated[] = 'edit';
    }

    if ($currenttab == 'view') {
        $activated[] = 'view';
    }

    print_tabs($tabs, $currenttab, $inactive, $activated);
}

/**
 * Serve the requested file
 * @param object $course
 * @param object $cm
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return false
 * @throws coding_exception
 * @throws dml_exception
 */
function rquiz_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {
    global $DB;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    if ($filearea != 'question') {
        return false;
    }

    require_course_login($course, true, $cm);

    $questionid = (int)array_shift($args);

    if (!$quiz = $DB->get_record('rquiz', array('id' => $cm->instance))) {
        return false;
    }

    if (!$question = $DB->get_record('rquiz_question', array('id' => $questionid, 'quizid' => $cm->instance))) {
        return false;
    }

    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = "/$context->id/mod_rquiz/$filearea/$questionid/$relativepath";
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        return false;
    }

    // Finally send the file.
    send_stored_file($file);
    return false;
}

/**
 * Features supported by this plugin
 * @param string $feature
 * @return bool|null
 */
function rquiz_supports($feature) {

    if (!defined('FEATURE_PLAGIARISM')) {
        define('FEATURE_PLAGIARISM', 'plagiarism');
    }

    switch ($feature) {
        case FEATURE_GROUPS:
            return false;
        case FEATURE_GROUPINGS:
            return true;
        case FEATURE_GROUPMEMBERSONLY:
            return true;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return false;
        case FEATURE_COMPLETION_HAS_RULES:
            return false;
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_GRADE_OUTCOMES:
            return false;
        case FEATURE_RATE:
            return false;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_PLAGIARISM:
            return false;

        default:
            return null;
    }
}
