<?php
/**
 * Edit a single question
 *
 * @package   mod_rquiz
 * @copyright Lê Quốc Huy ( The Jolash )
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__).'/../../config.php');
global $DB, $CFG, $OUTPUT, $PAGE;
require_once($CFG->dirroot.'/mod/rquiz/editquestion_form.php');

$quizid = required_param('quizid', PARAM_INT);
$questionid = optional_param('questionid', 0, PARAM_INT);
$numanswers = optional_param('numanswers', 4, PARAM_INT);
$addanswers = optional_param('addanswers', false, PARAM_BOOL);
if ($addanswers) {
    $numanswers += 3;
}

$quiz = $DB->get_record('rquiz', array('id' => $quizid), '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('rquiz', $quiz->id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

$url = new moodle_url('/mod/rquiz/editquestion.php', array('quizid' => $quizid));
if ($questionid) {
    $url->param('questionid', $questionid);
}
$PAGE->set_url($url);

require_login($course, false, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/rquiz:editquestions', $context);

if ($questionid) {
    $question = $DB->get_record('rquiz_question', array('id' => $questionid, 'quizid' => $quizid), '*', MUST_EXIST);
    $question->questionid = $question->id;
    $question->answers = $DB->get_records('rquiz_answer', array('questionid' => $question->id), 'id');
    $question->answercorrect = 0;
    $question->answertext = array();
    $question->answerid = array();
    $i = 1;
    foreach ($question->answers as $answer) {
        if ($answer->correct) {
            $question->answercorrect = $i;
        }
        $question->answertext[$i] = $answer->answertext;
        $question->answerid[$i] = $answer->id;
        $i++;
    }
    $heading = get_string('edittingquestion', 'mod_rquiz');
} else {
    $question = new stdClass();
    $question->id = 0;
    $question->quizid = $quiz->id;
    $question->questionnum = $DB->count_records('rquiz_question', array('quizid' => $quiz->id)) + 1;
    $question->questiontext = '';
    $question->questiontextformat = FORMAT_HTML;
    $question->questiontime = 0;
    $question->answers = array();
    $question->answercorrect = 1;
    $heading = get_string('addingquestion', 'mod_rquiz');
}

$maxbytes = get_max_upload_file_size($CFG->maxbytes, $course->maxbytes);
$editoroptions = array(
    'subdirs' => false, 'maxbytes' => $maxbytes, 'maxfiles' => -1,
    'changeformat' => 0, 'context' => $context, 'noclean' => 0,
    'trusttext' => true
);

$numanswers = max(count($question->answers), $numanswers);
$form = new rquiz_editquestion_form(null, array('editoroptions' => $editoroptions, 'numanswers' => $numanswers));

$question = file_prepare_standard_editor($question, 'questiontext', $editoroptions, $context,
                                         'mod_rquiz', 'question', $question->id);
$form->set_data($question);

$return = new moodle_url('/mod/rquiz/edit.php', array('quizid' => $quiz->id));
if ($form->is_cancelled()) {
    redirect($return);
}

if ($data = $form->get_data()) {
    if (isset($data->save) || isset($data->saveadd)) {
        $updquestion = (object)array(
            'quizid' => $quizid,
            'questionnum' => $question->questionnum,
            'questiontext' => 'toupdate',
            'questiontextformat' => FORMAT_HTML,
            'questiontime' => $data->questiontime,
        );

        if (!empty($question->id)) {
            $updquestion->id = $question->id;
        } else {
            $updquestion->id = $DB->insert_record('rquiz_question', $updquestion);
        }

        // Save the attached files (now we know we have got a question id).
        $data = file_postupdate_standard_editor($data, 'questiontext', $editoroptions, $context, 'mod_rquiz',
                                                'question', $updquestion->id);
        $updquestion->questiontext = $data->questiontext;
        $updquestion->questiontextformat = $data->questiontextformat;

        $DB->update_record('rquiz_question', $updquestion);

        // Save each of the answers.
        foreach ($data->answertext as $pos => $answertext) {
            $updanswer = new stdClass();
            $updanswer->answertext = $answertext;
            $updanswer->correct = ($pos == $data->answercorrect) ? 1 : 0;
            if (!empty($question->answerid[$pos])) {
                $updanswer->id = $question->answerid[$pos];
                $oldanswer = $question->answers[$updanswer->id];
                $changed = $updanswer->answertext != $oldanswer->answertext;
                $changed = $changed || $updanswer->correct != $oldanswer->correct;
                if ($changed) {
                    if ($updanswer->answertext === "") {
                        $DB->delete_records('rquiz_answer', array('id' => $updanswer->id));
                    } else {
                        $DB->update_record('rquiz_answer', $updanswer);
                    }
                }
            } else if ($updanswer->answertext !== "") {
                $updanswer->questionid = $updquestion->id;
                $updanswer->id = $DB->insert_record('rquiz_answer', $updanswer);
            }
        }

        if (isset($data->saveadd)) {
            redirect(new moodle_url('/mod/rquiz/editquestion.php', array('quizid' => $quizid)));
        }

        redirect($return);
    }
}

$jsmodule = array(
    'name' => 'mod_rquiz',
    'fullpath' => new moodle_url('/mod/rquiz/editquestions.js'),
);
$PAGE->requires->js_init_call('M.mod_rquiz.init_editpage', array(), false, $jsmodule);

$PAGE->set_heading($heading.$question->questionnum);
$PAGE->set_title(get_string('pluginname', 'mod_rquiz'));

echo $OUTPUT->header();
echo $OUTPUT->heading($heading.$question->questionnum);

$form->display();

echo $OUTPUT->footer();