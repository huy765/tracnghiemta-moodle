<?php
/**
 * This dynamically sends quiz data to clients
 *
 * @copyright Lê Quốc Huy ( The Jolash )
 * @package mod_rquiz
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

define('AJAX_SCRIPT', true);

require_once('../../config.php');
global $CFG, $DB, $USER, $PAGE;
require_once($CFG->dirroot.'/mod/rquiz/lib.php');
require_once($CFG->dirroot.'/mod/rquiz/locallib.php');
require_once($CFG->libdir.'/filelib.php');

require_login();
require_sesskey();
$requesttype = required_param('requesttype', PARAM_ALPHA);
$quizid = required_param('quizid', PARAM_INT);

/***********************************************************
 * End of functions - start of main code
 ***********************************************************/

rquiz_start_response();

if (!$rquiz = $DB->get_record("rquiz", array('id' => $quizid))) {
    rquiz_send_error("Quiz ID incorrect");
    rquiz_end_response();
    die();
}
if (!$course = $DB->get_record("course", array('id' => $rquiz->course))) {
    rquiz_send_error("Course is misconfigured");
    rquiz_end_response();
    die();
}
if (!$cm = get_coursemodule_from_instance("rquiz", $rquiz->id, $course->id)) {
    rquiz_send_error("Course Module ID was incorrect");
    rquiz_end_response();
    die();
}
if ($CFG->version < 2011120100) {
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
} else {
    $context = context_module::instance($cm->id);
}
$PAGE->set_context($context);

if (!has_capability('mod/rquiz:attempt', $context)) {
    rquiz_send_error(get_string('notallowedattempt', 'rquiz'));
    rquiz_end_response();
    die();
}

$status = $rquiz->status;
if ($status === false) {
    rquiz_send_error(get_string('badquizid', 'rquiz').$quizid);
} else {
    $status = rquiz_update_status($quizid, $status); // Check if the current status should change due to a timeout.

    if ($requesttype == 'quizrunning') {
        if (($status == rQUIZ_STATUS_NOTRUNNING) || ($status == rQUIZ_STATUS_FINALRESULTS)) {
            // Not running / finished.
            rquiz_send_not_running();
        } else {
            rquiz_send_running();
        }
    } else if ($requesttype == 'startquiz') {
        if (has_capability('mod/rquiz:control', $context)) {
            $session = new stdClass();
            $session->timestamp = time();
            $session->name = optional_param('sessionname', '', PARAM_TEXT);
            $session->quizid = $quizid;
            $session->id = $DB->insert_record('rquiz_session', $session);

            $quiz = $DB->get_record('rquiz', array('id' => $quizid));
            $quiz->currentsessionid = $session->id;
            $quiz->status = rQUIZ_STATUS_READYTOSTART;
            $quiz->currentquestion = 0;
            $quiz->classresult = 0;
            $quiz->questionresult = 0;
            $DB->update_record('rquiz', $quiz);

            rquiz_send_running();
        } else {
            rquiz_send_error(get_string('notauthorised', 'rquiz'));
        }

    } else {

        switch ($status) {

            case rQUIZ_STATUS_NOTRUNNING:   // Quiz is not running.
                rquiz_send_not_running(); // We don't care what they asked for.
                break;

            case rQUIZ_STATUS_READYTOSTART: // Quiz is ready to start.
                if ($requesttype == 'nextquestion') {
                    rquiz_goto_question($context, $quizid, 1);
                } else {
                    rquiz_send_await_question(); // Don't care what they asked for.
                    rquiz_number_students($quizid);
                }
                break;

            case rQUIZ_STATUS_PREVIEWQUESTION: // Previewing question (send it out, but ask them to wait before showing).
                rquiz_send_question($quizid, $context, true); // We don't care what they asked for.
                break;

            case rQUIZ_STATUS_SHOWQUESTION: // Question being displayed.
                if ($requesttype == 'getquestion' || $requesttype == 'nextquestion' || $requesttype == 'teacherrejoin') {
                    // Student asked for a question - so send it.
                    rquiz_send_question($quizid, $context);

                } else if ($requesttype == 'postanswer') {
                    $questionnum = required_param('question', PARAM_INT);
                    $userid = $USER->id;
                    $answerid = required_param('answer', PARAM_INT);
                    rquiz_record_answer($quizid, $questionnum, $userid, $answerid, $context);

                } else if ($requesttype == 'getresults') {
                    $questionnum = required_param('question', PARAM_INT);
                    if (rquiz_current_question($quizid, $questionnum)) {
                        $timeleft = $DB->get_field('rquiz', 'nextendtime', array('id' => $quizid)) - time();
                        if ($timeleft < 0) {
                            $timeleft = 0;
                        }
                        rquiz_send_await_results($timeleft); // Results not yet ready.
                    } else {
                        rquiz_send_question($quizid, $context); // Asked for results for wrong question.
                    }

                } else {
                    rquiz_send_error(get_string('unknownrequest', 'rquiz').$requesttype.'\'');
                }
                break;

            case rQUIZ_STATUS_SHOWRESULTS: // Results being displayed.
                if ($requesttype == 'getquestion') { // Asking for the next question.
                    rquiz_send_await_question();

                } else if ($requesttype == 'postanswer' || $requesttype == 'getresults' || $requesttype == 'teacherrejoin') {
                    rquiz_send_results($quizid);

                } else if ($requesttype == 'nextquestion') {
                    $clientquestionnum = required_param('currentquestion', PARAM_INT);
                    $questionid = $DB->get_field('rquiz', 'currentquestion', array('id' => $quizid));
                    $questionnum = $DB->get_field('rquiz_question', 'questionnum', array('id' => $questionid));
                    if ($clientquestionnum != $questionnum) {
                        rquiz_send_results($quizid);
                    } else {
                        $questionnum++;
                        rquiz_goto_question($context, $quizid, $questionnum);
                    }

                } else {
                    rquiz_send_error(get_string('unknownrequest', 'rquiz').$requesttype.'\'');
                }
                break;

            case rQUIZ_STATUS_FINALRESULTS: // Showing the final totals, etc.
                rquiz_send_final_results($quizid);
                break;

            default:
                rquiz_send_error(get_string('incorrectstatus', 'rquiz').$status.'\'');
                break;
        }
    }
}

rquiz_end_response();
