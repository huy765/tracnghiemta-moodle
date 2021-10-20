<?php
/**
 * This page prints a particular instance of rquiz
 *
 * @copyright Lê Quốc Huy ( The Jolash )
 * @package mod_rquiz
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

require_once("../../config.php");
global $CFG, $DB, $PAGE, $OUTPUT, $USER;
require_once($CFG->dirroot.'/mod/rquiz/lib.php');
require_once($CFG->dirroot.'/mod/rquiz/locallib.php');

$id = optional_param('id', 0, PARAM_INT); // Course Module ID, or ...
$a = optional_param('a', 0, PARAM_INT);  // rquiz ID.

if ($id) {
    $cm = get_coursemodule_from_id('rquiz', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $rquiz = $DB->get_record('rquiz', array('id' => $cm->instance), '*', MUST_EXIST);
} else {
    $rquiz = $DB->get_record('rquiz', array('id' => $bid), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('rquiz', $rquiz->id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $id = $cm->id;
}

$PAGE->set_url(new moodle_url('/mod/rquiz/view.php', array('id' => $cm->id)));

require_login($course->id, false, $cm);
$PAGE->set_pagelayout('incourse');

if ($CFG->version < 2011120100) {
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
} else {
    $context = context_module::instance($cm->id);
}

$questioncount = $DB->count_records('rquiz_question', array('quizid' => $rquiz->id));
if ($questioncount == 0 && has_capability('mod/rquiz:editquestions', $context)) {
    redirect('edit.php?id='.$id);
}

require_capability('mod/rquiz:attempt', $context);

if ($CFG->version > 2014051200) { // Moodle 2.7+.
    $params = array(
        'context' => $context,
        'objectid' => $rquiz->id
    );
    $event = \mod_rquiz\event\course_module_viewed::create($params);
    $event->add_record_snapshot('rquiz', $rquiz);
    $event->trigger();
} else {
    add_to_log($course->id, 'rquiz', 'view all', "index.php?id=$course->id", "");
}

$quizstatus = rquiz_update_status($rquiz->id, $rquiz->status);

// Print the page header.

$strrquizzes = get_string("modulenameplural", "rquiz");
$strrquiz = get_string("modulename", "rquiz");

$PAGE->set_title(strip_tags($course->shortname.': '.$strrquiz.': '.format_string($rquiz->name, true)));
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($rquiz->name));

if (class_exists('\core_completion\activity_custom_completion')) {
    // Render the activity information.
    $modinfo = get_fast_modinfo($course);
    $cminfo = $modinfo->get_cm($cm->id);
    $completiondetails = \core_completion\cm_completion_details::get_instance($cminfo, $USER->id);
    $activitydates = \core\activity_dates::get_dates_for_module($cminfo, $USER->id);
    echo $OUTPUT->activity_information($cminfo, $completiondetails, $activitydates);
}

rquiz_view_tabs('view', $cm->id, $context);

echo format_text($rquiz->intro, $rquiz->introformat);

// Print the main part of the page.

if ($CFG->version < 2013111800) {
    $tickimg = $OUTPUT->pix_url('i/tick_green_big');
    $crossimg = $OUTPUT->pix_url('i/cross_red_big');
    $spacer = $OUTPUT->pix_url('spacer');
} else if ($CFG->branch < 33) {
    $tickimg = $OUTPUT->pix_url('i/grade_correct');
    $crossimg = $OUTPUT->pix_url('i/grade_incorrect');
    $spacer = $OUTPUT->pix_url('spacer');
} else {
    $tickimg = $OUTPUT->image_url('i/grade_correct');
    $crossimg = $OUTPUT->image_url('i/grade_incorrect');
    $spacer = $OUTPUT->image_url('spacer');
}

echo $OUTPUT->box_start('generalbox boxwidthwide boxaligncenter rquizbox');
?>
    <div id="questionarea"></div>
    <!--    <div id="debugarea" style="border: 1px dashed black; width: 600px; height: 100px; overflow: scroll; "></div>
        <button onclick="rquiz_debug_stopall();">Stop</button> -->
    <script type="text/javascript" src="<?php echo $CFG->wwwroot; ?>/mod/rquiz/view_student.js"></script>
    <script type="text/javascript">
        rquiz_set_maxanswers(10);
        rquiz_set_quizid(<?php echo $rquiz->id; ?>);
        rquiz_set_userid(<?php echo $USER->id; ?>);
        rquiz_set_sesskey('<?php echo sesskey(); ?>');
        rquiz_set_coursepage('<?php echo "$CFG->wwwroot/course/view.php?id=$course->id"; ?>');
        rquiz_set_siteroot('<?php echo "$CFG->wwwroot"; ?>');
        rquiz_set_running(<?php echo(rquiz_is_running($quizstatus) ? 'true' : 'false'); ?>);

        rquiz_set_image('tick', "<?php echo $tickimg ?>");
        rquiz_set_image('cross', "<?php echo $crossimg ?>");
        rquiz_set_image('blank', "<?php echo $spacer ?>");

        //Pass all the text strings into the javascript (to allow for translation)
        // Used by view_student.js
        rquiz_set_text('joinquiz', "<?php print_string('joinquiz', 'rquiz') ?>");
        rquiz_set_text('joininstruct', "<?php print_string('joininstruct', 'rquiz') ?>");
        rquiz_set_text('waitstudent', "<?php print_string('waitstudent', 'rquiz') ?>");
        rquiz_set_text('clicknext', "<?php print_string('clicknext', 'rquiz') ?>");
        rquiz_set_text('waitfirst', "<?php print_string('waitfirst', 'rquiz') ?>");
        rquiz_set_text('question', "<?php print_string('question', 'rquiz') ?>");
        rquiz_set_text('invalidanswer', "<?php print_string('invalidanswer', 'rquiz') ?>");
        rquiz_set_text('finalresults', "<?php print_string('finalresults', 'rquiz') ?>");
        rquiz_set_text('quizfinished', "<?php print_string('quizfinished', 'rquiz') ?>");
        rquiz_set_text('classresult', "<?php print_string('classresult', 'rquiz') ?>");
        rquiz_set_text('classresultcorrect', "<?php print_string('classresultcorrect', 'rquiz') ?>");
        rquiz_set_text('questionfinished', "<?php print_string('questionfinished', 'rquiz') ?>");
        rquiz_set_text('httprequestfail', "<?php print_string('httprequestfail', 'rquiz') ?>");
        rquiz_set_text('noquestion', "<?php print_string('noquestion', 'rquiz') ?>");
        rquiz_set_text('tryagain', "<?php print_string('tryagain', 'rquiz') ?>");
        rquiz_set_text('resultthisquestion', "<?php print_string('resultthisquestion', 'rquiz') ?>");
        rquiz_set_text('resultoverall', "<?php print_string('resultoverall', 'rquiz') ?>");
        rquiz_set_text('resultcorrect', "<?php print_string('resultcorrect', 'rquiz') ?>");
        rquiz_set_text('answersent', "<?php print_string('answersent', 'rquiz') ?>");
        rquiz_set_text('quiznotrunning', "<?php print_string('quiznotrunning', 'rquiz') ?>");
        rquiz_set_text('servererror', "<?php print_string('servererror', 'rquiz') ?>");
        rquiz_set_text('badresponse', "<?php print_string('badresponse', 'rquiz') ?>");
        rquiz_set_text('httperror', "<?php print_string('httperror', 'rquiz') ?>");
        rquiz_set_text('yourresult', "<?php print_string('yourresult', 'rquiz') ?>");

        rquiz_set_text('timeleft', "<?php print_string('timeleft', 'rquiz') ?>");
        rquiz_set_text('displaynext', "<?php print_string('displaynext', 'rquiz') ?>");
        rquiz_set_text('sendinganswer', "<?php print_string('sendinganswer', 'rquiz') ?>");
        rquiz_set_text('tick', "<?php print_string('tick', 'rquiz') ?>");
        rquiz_set_text('cross', "<?php print_string('cross', 'rquiz') ?>");

        // Used by view_teacher.js
        rquiz_set_text('joinquizasstudent', "<?php print_string('joinquizasstudent', 'rquiz') ?>");
        rquiz_set_text('next', "<?php print_string('next', 'rquiz') ?>");
        rquiz_set_text('startquiz', "<?php print_string('startquiz', 'rquiz') ?>");
        rquiz_set_text('startnewquiz', "<?php print_string('startnewquiz', 'rquiz') ?>");
        rquiz_set_text('startnewquizconfirm', "<?php print_string('startnewquizconfirm', 'rquiz') ?>");
        rquiz_set_text('studentconnected', "<?php print_string('studentconnected', 'rquiz') ?>");
        rquiz_set_text('studentsconnected', "<?php print_string('studentsconnected', 'rquiz') ?>");
        rquiz_set_text('teacherstartinstruct', "<?php print_string('teacherstartinstruct', 'rquiz') ?>");
        rquiz_set_text('teacherstartnewinstruct', "<?php print_string('teacherstartnewinstruct', 'rquiz') ?>");
        rquiz_set_text('teacherjoinquizinstruct', "<?php print_string('teacherjoinquizinstruct', 'rquiz') ?>");
        rquiz_set_text('reconnectquiz', "<?php print_string('reconnectquiz', 'rquiz') ?>");
        rquiz_set_text('reconnectinstruct', "<?php print_string('reconnectinstruct', 'rquiz') ?>");
    </script>

<?php

if (has_capability('mod/rquiz:control', $context)) {
    ?>
    <script type="text/javascript" src="<?php echo $CFG->wwwroot; ?>/mod/rquiz/view_teacher.js"></script>
    <script type="text/javascript">
        rquiz_init_teacher_view();
    </script>
    <?php
} else {
    echo '<script type="text/javascript">rquiz_init_student_view();</script>';
}

echo $OUTPUT->box_end();

// Finish the page.
echo $OUTPUT->footer();

