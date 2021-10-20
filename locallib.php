<?php
/**
 * Internal functions
 *
 * @package   mod_rquiz
 * @copyright Lê Quốc Huy ( The Jolash )
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/** Quiz not running */
define('rQUIZ_STATUS_NOTRUNNING', 0);
/** Quiz ready to start */
define('rQUIZ_STATUS_READYTOSTART', 10);
/** Quiz showing 'review question' page */
define('rQUIZ_STATUS_PREVIEWQUESTION', 15);
/** Quiz showing a question */
define('rQUIZ_STATUS_SHOWQUESTION', 20);
/** Quiz showing results */
define('rQUIZ_STATUS_SHOWRESULTS', 30);
/** Quiz showing the final results */
define('rQUIZ_STATUS_FINALRESULTS', 40);

/**
 * Output the response start
 */
function rquiz_start_response() {
    header('content-type: text/xml');
    echo '<?xml version="1.0" ?><rquiz>';
}

/**
 * Output the response end
 */
function rquiz_end_response() {
    echo '</rquiz>';
}

/**
 * Send the given error messsage
 * @param string $msg
 */
function rquiz_send_error($msg) {
    echo "<status>error</status><message><![CDATA[{$msg}]]></message>";
}

/**
 * Send the question details
 * @param int $quizid
 * @param context $context
 * @param bool $preview
 * @throws coding_exception
 * @throws dml_exception
 */
function rquiz_send_question($quizid, $context, $preview = false) {
    global $DB;

    if (!$quiz = $DB->get_record('rquiz', array('id' => $quizid))) {
        rquiz_send_error(get_string('badquizid', 'rquiz').$quizid);
    } else {
        $questionid = $quiz->currentquestion;
        if (!$question = $DB->get_record('rquiz_question', array('id' => $questionid))) {
            rquiz_send_error(get_string('badcurrentquestion', 'rquiz').$questionid);
        } else {
            $answers = $DB->get_records('rquiz_answer', array('questionid' => $questionid), 'id');
            $questioncount = $DB->count_records('rquiz_question', array('quizid' => $quizid));
            echo '<status>showquestion</status>';
            echo "<question><questionnumber>{$question->questionnum}</questionnumber>";
            echo "<questioncount>{$questioncount}</questioncount>";
            $questiontext = format_text($question->questiontext, $question->questiontextformat);
            $questiontext = file_rewrite_pluginfile_urls($questiontext, 'pluginfile.php', $context->id, 'mod_rquiz',
                                                         'question', $questionid);
            echo "<questiontext><![CDATA[{$questiontext}]]></questiontext>";
            if ($preview) {
                $previewtime = $quiz->nextendtime - time();
                if ($previewtime > 0) {
                    echo "<delay>{$previewtime}</delay>";
                }
                $questiontime = $question->questiontime;
                if ($questiontime == 0) {
                    $questiontime = $quiz->questiontime;
                }
                echo "<questiontime>{$questiontime}</questiontime>";
            } else {
                $questiontime = $quiz->nextendtime - time();
                if ($questiontime < 0) {
                    $questiontime = 0;
                }
                echo "<questiontime>{$questiontime}</questiontime>";
            }
            echo '<answers>';
            foreach ($answers as $answer) {
                $answertext = $answer->answertext;
                echo "<answer id='{$answer->id}'><![CDATA[{$answertext}]]></answer>";
            }
            echo '</answers>';
            echo '</question>';
        }
    }
}

/**
 * Send the result details
 * @param int $quizid
 * @throws coding_exception
 * @throws dml_exception
 */
function rquiz_send_results($quizid) {
    global $DB;

    if (!$quiz = $DB->get_record('rquiz', array('id' => $quizid))) {
        rquiz_send_error(get_string('badquizid', 'rquiz').$quizid);
    } else {
        $questionid = $quiz->currentquestion;
        if (!$question = $DB->get_record('rquiz_question', array('id' => $questionid))) {
            rquiz_send_error(get_string('badcurrentquestion', 'rquiz').$questionid);
        } else {
            // Do not worry about question number not matching request
            // client should sort out correct state, if they do not match
            // just get on with sending current results.
            $totalanswers = 0;
            $totalcorrect = 0;
            $answers = $DB->get_records('rquiz_answer', array('questionid' => $questionid), 'id');
            echo '<status>showresults</status>';
            echo '<questionnum>'.$question->questionnum.'</questionnum>';
            echo '<results>';
            $numberofcorrectanswers = 0; // To detect questions that have no 'correct' answers.
            foreach ($answers as $answer) {
                $result = $DB->count_records('rquiz_submitted', array(
                    'questionid' => $questionid, 'answerid' => $answer->id, 'sessionid' => $quiz->currentsessionid
                ));
                $totalanswers += $result;
                $correct = 'false';
                if ($answer->correct == 1) {
                    $correct = 'true';
                    $totalcorrect += $result;
                    $numberofcorrectanswers++;
                }
                echo "<result id='{$answer->id}' correct='{$correct}'>{$result}</result>";
            }
            if ($numberofcorrectanswers == 0) {
                $newresult = 100;
            } else if ($totalanswers > 0) {
                $newresult = intval((100 * $totalcorrect) / $totalanswers);
            } else {
                $newresult = 0;
            }
            if ($newresult != $quiz->questionresult) {
                $quiz->questionresult = $newresult;
                $upd = new stdClass;
                $upd->id = $quiz->id;
                $upd->questionresult = $quiz->questionresult;
                $DB->update_record('rquiz', $upd);
            }
            $classresult = intval(($quiz->classresult + $quiz->questionresult) / $question->questionnum);
            echo '</results>';
            if ($numberofcorrectanswers == 0) {
                echo '<nocorrect/>';
            }
            echo '<statistics>';
            echo '<questionresult>'.$quiz->questionresult.'</questionresult>';
            echo '<classresult>'.$classresult.'</classresult>';
            echo '</statistics>';
        }
    }
}

/**
 * Record the answer given
 * @param int $quizid
 * @param int $questionnum
 * @param int $userid
 * @param int $answerid
 * @param context $context
 * @throws coding_exception
 * @throws dml_exception
 */
function rquiz_record_answer($quizid, $questionnum, $userid, $answerid, $context) {
    global $DB;

    $quiz = $DB->get_record('rquiz', array('id' => $quizid));
    $question = $DB->get_record('rquiz_question', array('id' => $quiz->currentquestion));
    $answer = $DB->get_record('rquiz_answer', array('id' => $answerid));

    if (($answer->questionid == $quiz->currentquestion)
        && ($question->questionnum == $questionnum)
    ) {
        $conditions = array(
            'questionid' => $question->id, 'sessionid' => $quiz->currentsessionid, 'userid' => $userid
        );
        if (!$DB->record_exists('rquiz_submitted', $conditions)) {
            // If we already have an answer from them, do not send error, as this is likely to be the
            // result of lost network packets & resends, just ignore silently.
            $submitted = new stdClass;
            $submitted->questionid = $question->id;
            $submitted->sessionid = $quiz->currentsessionid;
            $submitted->userid = $userid;     // FIXME: make sure the userid is on the course.
            $submitted->answerid = $answerid;
            $DB->insert_record('rquiz_submitted', $submitted);
        }
        echo '<status>answerreceived</status>';

    } else {

        // Answer is not for the current question - so send the current question.
        rquiz_send_question($quizid, $context);
    }
}

/**
 * Count the number of students connected
 * @param int $quizid
 * @throws coding_exception
 * @throws dml_exception
 */
function rquiz_number_students($quizid) {
    global $CFG, $DB, $USER;
    if ($rquiz = $DB->get_record("rquiz", array('id' => $quizid))) {
        if ($course = $DB->get_record("course", array('id' => $rquiz->course))) {
            if ($cm = get_coursemodule_from_instance("rquiz", $rquiz->id, $course->id)) {
                if ($CFG->version < 2011120100) {
                    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
                } else {
                    $context = context_module::instance($cm->id);
                }
                // Is it a student and not a teacher?
                if (!has_capability('mod/rquiz:control', $context, $USER->id)) {
                    $cond = array(
                        'userid' => $USER->id, 'questionid' => 0, 'answerid' => 0,
                        'sessionid' => $rquiz->currentsessionid,
                    );
                    if (!$DB->record_exists("rquiz_submitted", $cond)) {
                        $data = new stdClass();
                        $data->questionid = 0;
                        $data->userid = $USER->id;
                        $data->answerid = 0;
                        $data->sessionid = $rquiz->currentsessionid;
                        $DB->insert_record('rquiz_submitted', $data);
                    }
                }
            }
        }
        echo "<numberstudents>";
        echo ($DB->count_records('rquiz_submitted', array(
            'questionid' => 0, 'answerid' => 0, 'sessionid' => $rquiz->currentsessionid
        )));
        echo "</numberstudents>";
    }
}

/**
 * Send 'quiz running' status.
 */
function rquiz_send_running() {
    echo '<status>quizrunning</status>';
}

/**
 * Send 'quiz not running' status.
 */
function rquiz_send_not_running() {
    echo '<status>quiznotrunning</status>';
}

/**
 * Send 'waiting for question to start' status.
 * @throws dml_exception
 */
function rquiz_send_await_question() {
    $waittime = get_config('rquiz', 'awaittime');
    echo '<status>waitforquestion</status>';
    echo "<waittime>{$waittime}</waittime>";
}

/**
 * Send 'waiting for results' status.
 * @param int $timeleft
 * @throws dml_exception
 */
function rquiz_send_await_results($timeleft) {
    $waittime = (int)get_config('rquiz', 'awaittime');
    // We need to randomise the waittime a little, otherwise all clients will
    // start sending 'waitforquestion' simulatiniously after the first question -
    // it can cause a problem is there is a large number of clients.
    // If waittime is 1 sec, there is no point to randomise it.
    $waittime = mt_rand(1, $waittime) + $timeleft;
    echo '<status>waitforresults</status>';
    echo "<waittime>{$waittime}</waittime>";
}

/**
 * Send the final results details.
 * @param int $quizid
 * @throws dml_exception
 */
function rquiz_send_final_results($quizid) {
    global $DB;

    $quiz = $DB->get_record('rquiz', array('id' => $quizid));
    $questionnum = $DB->get_field('rquiz_question', 'questionnum', array('id' => $quiz->currentquestion));
    echo '<status>finalresults</status>';
    echo '<classresult>'.intval($quiz->classresult / $questionnum).'</classresult>';
}

/**
 * Check if the current status should change due to a timeout.
 * @param int $quizid
 * @param int $status
 * @return int|mixed
 * @throws dml_exception
 */
function rquiz_update_status($quizid, $status) {
    global $DB;

    if ($status == rQUIZ_STATUS_PREVIEWQUESTION) {
        $quiz = $DB->get_record('rquiz', array('id' => $quizid));
        if ($quiz->nextendtime < time()) {
            $questiontime = $DB->get_field('rquiz_question', 'questiontime', array('id' => $quiz->currentquestion));
            if ($questiontime == 0) {
                $questiontime = $quiz->questiontime;
            }
            $timeleft = $quiz->nextendtime - time() + $questiontime;
            if ($timeleft > 0) {
                $quiz->status = rQUIZ_STATUS_SHOWQUESTION;
                $quiz->nextendtime = time() + $timeleft;
            } else {
                $quiz->status = rQUIZ_STATUS_SHOWRESULTS;
            }
            $upd = new stdClass;
            $upd->id = $quiz->id;
            $upd->status = $quiz->status;
            $upd->nextendtime = $quiz->nextendtime;
            $DB->update_record('rquiz', $upd);

            $status = $quiz->status;
        }
    } else if ($status == rQUIZ_STATUS_SHOWQUESTION) {
        $nextendtime = $DB->get_field('rquiz', 'nextendtime', array('id' => $quizid));
        if ($nextendtime < time()) {
            $status = rQUIZ_STATUS_SHOWRESULTS;
            $DB->set_field('rquiz', 'status', $status, array('id' => $quizid));
        }
    } else if (($status != rQUIZ_STATUS_NOTRUNNING) && ($status != rQUIZ_STATUS_READYTOSTART)
        && ($status != rQUIZ_STATUS_SHOWRESULTS) && ($status != rQUIZ_STATUS_FINALRESULTS)) {
        // Bad status = probably should set it back to 0.
        $status = rQUIZ_STATUS_NOTRUNNING;
        $DB->set_field('rquiz', 'status', rQUIZ_STATUS_NOTRUNNING, array('id' => $quizid));
    }

    return $status;
}

/**
 * Is the quiz currently running?
 * @param int $status
 * @return bool
 */
function rquiz_is_running($status) {
    return ($status > rQUIZ_STATUS_NOTRUNNING && $status < rQUIZ_STATUS_FINALRESULTS);
}

/**
 * Check the question requested matches the current question.
 * @param int $quizid
 * @param int $questionnumber
 * @return bool
 * @throws dml_exception
 */
function rquiz_current_question($quizid, $questionnumber) {
    global $DB;

    $questionid = $DB->get_field('rquiz', 'currentquestion', array('id' => $quizid));
    if (!$questionid) {
        return false;
    }
    if ($questionnumber != $DB->get_field('rquiz_question', 'questionnum', array('id' => $questionid))) {
        return false;
    }

    return true;
}

/**
 * Go to the requested question.
 * @param context $context
 * @param int $quizid
 * @param int $questionnum
 * @throws coding_exception
 * @throws dml_exception
 */
function rquiz_goto_question($context, $quizid, $questionnum) {
    global $DB;

    if (has_capability('mod/rquiz:control', $context)) {
        $quiz = $DB->get_record('rquiz', array('id' => $quizid));
        // Update the question statistics.
        $quiz->classresult += $quiz->questionresult;
        $quiz->questionresult = 0;
        $questionid = $DB->get_field('rquiz_question', 'id', array('quizid' => $quizid, 'questionnum' => $questionnum));
        if ($questionid) {
            $quiz->currentquestion = $questionid;
            $quiz->status = rQUIZ_STATUS_PREVIEWQUESTION;
            $quiz->nextendtime = time() + 2;    // Give everyone a chance to get the question before starting.
            $DB->update_record('rquiz', $quiz); // FIXME - not update all fields?
            rquiz_send_question($quizid, $context, true);
        } else { // Assume we have run out of questions.
            $quiz->status = rQUIZ_STATUS_FINALRESULTS;
            $DB->update_record('rquiz', $quiz); // FIXME - not update all fields?
            rquiz_send_final_results($quizid);
        }
    } else {
        rquiz_send_error(get_string('notauthorised', 'rquiz'));
    }
}
