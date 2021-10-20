<?php
/**
 * This page lists all the instances of rquiz in a particular course
 *
 * @copyright Lê Quốc Huy ( The Jolash )
 * @package mod_rquiz
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

require_once(__DIR__."/../../config.php");
global $CFG, $PAGE, $OUTPUT, $DB;
require_once($CFG->dirroot.'/mod/rquiz/lib.php');


$id = required_param('id', PARAM_INT);   // Course.
$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);

$PAGE->set_url(new moodle_url('/mod/rquiz/index.php', array('id' => $course->id)));
require_course_login($course);
$PAGE->set_pagelayout('incourse');

if ($CFG->version > 2014051200) { // Moodle 2.7+.
    $params = array(
        'context' => context_course::instance($course->id)
    );
    $event = \mod_rquiz\event\course_module_instance_list_viewed::create($params);
    $event->add_record_snapshot('course', $course);
    $event->trigger();
} else {
    add_to_log($course->id, "rquiz", "view all", "index.php?id=$course->id", "");
}

// Get all required strings.

$strrquizzes = get_string("modulenameplural", "rquiz");
$strrquiz = get_string("modulename", "rquiz");

$PAGE->navbar->add($strrquizzes);
$PAGE->set_title(strip_tags($course->shortname.': '.$strrquizzes));
echo $OUTPUT->header();

// Get all the appropriate data.

if (!$rquizs = get_all_instances_in_course("rquiz", $course)) {
    notice("There are no rquizes", "../../course/view.php?id=$course->id");
    die;
}

// Print the list of instances (your module will probably extend this).

$timenow = time();
$strname = get_string("name");
$strweek = get_string("week");
$strtopic = get_string("topic");

$table = new html_table();

if ($course->format == "weeks") {
    $table->head = array($strweek, $strname);
    $table->align = array("center", "left");
} else if ($course->format == "topics") {
    $table->head = array($strtopic, $strname);
    $table->align = array("center", "left");
} else {
    $table->head = array($strname);
    $table->align = array("left", "left");
}

foreach ($rquizs as $rquiz) {
    $url = new moodle_url('/mod/rquiz/view.php', array('id' => $rquiz->coursemodule));
    if (!$rquiz->visible) {
        // Show dimmed if the mod is hidden.
        $link = '<a class="dimmed" href="'.$url.'">'.$rquiz->name.'</a>';
    } else {
        // Show normal if the mod is visible.
        $link = '<a href="'.$url.'">'.$rquiz->name.'</a>';
    }

    if ($course->format == 'weeks' or $course->format == 'topics') {
        $table->data[] = array($rquiz->section, $link);
    } else {
        $table->data[] = array($link);
    }
}

echo html_writer::table($table);

// Finish the page.

echo $OUTPUT->footer();

