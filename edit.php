<?php
/**
 * This allows you to edit questions for a rquiz
 *
 * @copyright Lê Quốc Huy ( The Jolash )
 * @package mod_rquiz
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

require_once('../../config.php');
global $CFG, $DB, $PAGE, $OUTPUT;
require_once($CFG->dirroot.'/mod/rquiz/lib.php');

$id = optional_param('id', false, PARAM_INT);
$quizid = optional_param('quizid', false, PARAM_INT);
$action = optional_param('action', 'listquestions', PARAM_ALPHA);
$questionid = optional_param('questionid', 0, PARAM_INT);

$addanswers = optional_param('addanswers', false, PARAM_BOOL);
$saveadd = optional_param('saveadd', false, PARAM_BOOL);
$canceledit = optional_param('cancel', false, PARAM_BOOL);

$removeimage = optional_param('removeimage', false, PARAM_BOOL);

if ($id) {
    $cm = get_coursemodule_from_id('rquiz', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $quiz = $DB->get_record('rquiz', array('id' => $cm->instance), '*', MUST_EXIST);
    $quizid = $quiz->id;
} else {
    $quiz = $DB->get_record('rquiz', array('id' => $quizid), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $quiz->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('rquiz', $quiz->id, $course->id, false, MUST_EXIST);
}

$PAGE->set_url(new moodle_url('/mod/rquiz/edit.php', array('id' => $cm->id)));

require_login($course->id, false, $cm);

$PAGE->set_pagelayout('incourse');
if ($CFG->version < 2011120100) {
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
} else {
    $context = context_module::instance($cm->id);
}
require_capability('mod/rquiz:editquestions', $context);

// Log this visit.
if ($CFG->version > 2014051200) { // Moodle 2.7+.
    $params = array(
        'courseid' => $course->id,
        'context' => $context,
        'other' => array(
            'quizid' => $quiz->id
        )
    );
    $event = \mod_rquiz\event\edit_page_viewed::create($params);
    $event->trigger();
} else {
    add_to_log($course->id, "rquiz", "update: $action", "edit.php?quizid=$quizid");
}

// Some useful functions.
/**
 * List all questions
 * @param int $quizid
 * @param object $cm
 * @throws coding_exception
 * @throws dml_exception
 * @throws moodle_exception
 */
function rquiz_list_questions($quizid, $cm) {
    global $DB, $OUTPUT;

    echo '<h2>'.get_string('questionslist', 'rquiz').'</h2>';

    $questions = $DB->get_records('rquiz_question', array('quizid' => $quizid), 'questionnum');
    $questioncount = count($questions);
    $expectednumber = 1;
    echo '<ol>';
    foreach ($questions as $question) {
        // A good place to double-check the question numbers and fix any that are broken.
        if ($question->questionnum != $expectednumber) {
            $question->questionnum = $expectednumber;
            $DB->update_record('rquiz_question', $question);
        }

        $editurl = new moodle_url('/mod/rquiz/editquestion.php', array('quizid' => $quizid, 'questionid' => $question->id));
        $qtext = format_string($question->questiontext);
        echo "<li><span class='rquiz_editquestion'>";
        echo html_writer::link($editurl, $qtext);
        echo " </span><span class='rquiz_editicons'>";
        if ($question->questionnum > 1) {
            $alt = get_string('questionmoveup', 'mod_rquiz', $question->questionnum);
            echo "<a href='edit.php?quizid=$quizid&amp;action=moveup&amp;questionid=$question->id'>";
            echo $OUTPUT->pix_icon('t/up', $alt);
            echo '</a>';
        } else {
            echo $OUTPUT->spacer(['width' => '16px']);
        }
        if ($question->questionnum < $questioncount) {
            $alt = get_string('questionmovedown', 'mod_rquiz', $question->questionnum);
            echo "<a href='edit.php?quizid=$quizid&amp;action=movedown&amp;questionid=$question->id'>";
            echo $OUTPUT->pix_icon('t/down', $alt);
            echo '</a>';
        } else {
            echo $OUTPUT->spacer(['width' => '15px']);
        }
        echo '&nbsp;';
        $alt = get_string('questiondelete', 'mod_rquiz', $question->questionnum);
        echo "<a href='edit.php?quizid=$quizid&amp;action=deletequestion&amp;questionid=$question->id'>";
        echo $OUTPUT->pix_icon('t/delete', $alt);
        echo '</a>';
        echo '</span></li>';
        $expectednumber++;
    }
    echo '</ol>';
    $url = new moodle_url('/mod/rquiz/editquestion.php', array('quizid' => $quizid));
    echo $OUTPUT->single_button($url, get_string('addquestion', 'rquiz'), 'GET');
}

/**
 * Output the 'confirm delete question' HTML.
 * @param int $quizid
 * @param int $questionid
 * @param context $context
 * @throws coding_exception
 * @throws dml_exception
 * @throws moodle_exception
 */
function rquiz_confirm_deletequestion($quizid, $questionid, $context) {
    global $DB;

    $question = $DB->get_record('rquiz_question', array('id' => $questionid, 'quizid' => $quizid), '*', MUST_EXIST);

    echo '<center><h2>'.get_string('deletequestion', 'rquiz').'</h2>';
    echo '<p>'.get_string('checkdelete', 'rquiz').'</p><p>';
    $questiontext = format_text($question->questiontext, $question->questiontextformat);
    $questiontext = file_rewrite_pluginfile_urls($questiontext, 'pluginfile.php', $context->id, 'mod_rquiz',
                                                 'question', $questionid);
    echo $questiontext;
    echo '</p>';

    $url = new moodle_url('/mod/rquiz/edit.php', array('quizid' => $quizid));
    echo '<form method="post" action="'.$url.'">';
    echo '<input type="hidden" name="action" value="dodeletequestion" />';
    echo '<input type="hidden" name="questionid" value="'.$questionid.'" />';
    echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
    echo '<input type="submit" name="yes" value="'.get_string('yes').'" /> ';
    echo '<input type="submit" name="no" value="'.get_string('no').'" />';
    echo '</form></center>';
}

// Back to the main code.
$strrquizzes = get_string("modulenameplural", "rquiz");
$strrquiz = get_string("modulename", "rquiz");

$PAGE->set_title(strip_tags($course->shortname.': '.$strrquiz.': '.format_string($quiz->name, true)));
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($quiz->name));

if (class_exists('\core_completion\activity_custom_completion')) {
    // Render the activity information.
    $modinfo = get_fast_modinfo($course);
    $cminfo = $modinfo->get_cm($cm->id);
    $completiondetails = \core_completion\cm_completion_details::get_instance($cminfo, $USER->id);
    $activitydates = \core\activity_dates::get_dates_for_module($cminfo, $USER->id);
    echo $OUTPUT->activity_information($cminfo, $completiondetails, $activitydates);
}

rquiz_view_tabs('edit', $cm->id, $context);

echo $OUTPUT->box_start('generalbox boxwidthwide boxaligncenter rquizbox');

if ($action == 'dodeletequestion') {

    require_sesskey();

    if (optional_param('yes', false, PARAM_BOOL)) {
        if ($question = $DB->get_record('rquiz_question', array('id' => $questionid, 'quizid' => $quiz->id))) {
            $answers = $DB->get_records('rquiz_answer', array('questionid' => $question->id));
            if (!empty($answers)) {
                foreach ($answers as $answer) { // Get each answer for that question.
                    // Delete any submissions for that answer.
                    $DB->delete_records('rquiz_submitted', array('answerid' => $answer->id));
                }
            }
            $DB->delete_records('rquiz_answer', array('questionid' => $question->id)); // Delete each answer.
            $DB->delete_records('rquiz_question', array('id' => $question->id));

            // Delete files embedded in the heading.
            $fs = get_file_storage();
            $fs->delete_area_files($context->id, 'mod_rquiz', 'question', $questionid);
            // Questionnumbers sorted out when we display the list of questions.
        }
    }

    $action = 'listquestions';

} else if ($action == 'moveup') {

    $thisquestion = $DB->get_record('rquiz_question', array('id' => $questionid));
    if ($thisquestion) {
        $questionnum = $thisquestion->questionnum;
        if ($questionnum > 1) {
            $swapquestion = $DB->get_record('rquiz_question', array(
                'quizid' => $quizid, 'questionnum' => ($questionnum - 1)
            ));
            if ($swapquestion) {
                $upd = new stdClass;
                $upd->id = $thisquestion->id;
                $upd->questionnum = $questionnum - 1;
                $DB->update_record('rquiz_question', $upd);

                $upd = new stdClass;
                $upd->id = $swapquestion->id;
                $upd->questionnum = $questionnum;
                $DB->update_record('rquiz_question', $upd);
            }
        }
    }

    $action = 'listquestions';

} else if ($action == 'movedown') {
    $thisquestion = $DB->get_record('rquiz_question', array('id' => $questionid));
    if ($thisquestion) {
        $questionnum = $thisquestion->questionnum;
        $swapquestion = $DB->get_record('rquiz_question', array('quizid' => $quizid, 'questionnum' => ($questionnum + 1)));
        if ($swapquestion) {
            $upd = new stdClass;
            $upd->id = $thisquestion->id;
            $upd->questionnum = $questionnum + 1;
            $DB->update_record('rquiz_question', $upd);

            $upd = new stdClass;
            $upd->id = $swapquestion->id;
            $upd->questionnum = $questionnum;
            $DB->update_record('rquiz_question', $upd);
        }
    }

    $action = 'listquestions';
}

switch ($action) {

    case 'listquestions': // Show all the currently available questions.
        rquiz_list_questions($quizid, $cm);
        break;

    case 'deletequestion': // Deleting a question - ask 'Are you sure?'.
        rquiz_confirm_deletequestion($quizid, $questionid, $context);
        break;

}

echo $OUTPUT->box_end();

// Finish the page.
echo $OUTPUT->footer();

