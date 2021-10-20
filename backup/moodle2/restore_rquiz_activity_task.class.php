<?php
/**
 * Restore a rquiz
 * @package mod_rquiz
 * @subpackage backup-moodle2
 * @copyright Lê Quốc Huy ( The Jolash )
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot.'/mod/rquiz/backup/moodle2/restore_rquiz_stepslib.php'); // Because it exists (must).

/**
 * rquiz restore task that provides all the settings and steps to perform one complete restore of the activity
 */
class restore_rquiz_activity_task extends restore_activity_task {

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity.
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        // Choice only has one structure step.
        $this->add_step(new restore_rquiz_activity_structure_step('rquiz_structure', 'rquiz.xml'));
    }

    /**
     * Define the contents in the activity that must be
     * processed by the link decoder
     */
    public static function define_decode_contents() {
        $contents = array();

        $contents[] = new restore_decode_content('rquiz', array('intro'));
        $contents[] = new restore_decode_content('rquiz_question', array('questiontext'));

        return $contents;
    }

    /**
     * Define the decoding rules for links belonging
     * to the activity to be executed by the link decoder
     */
    public static function define_decode_rules() {
        $rules = array();

        // List of rquizs in course.
        $rules[] = new restore_decode_rule('rQUIZINDEX', '/mod/rquiz/index.php?id=$1', 'course');
        // rquiz by cm->id and rquiz->id.
        $rules[] = new restore_decode_rule('rQUIZVIEWBYID', '/mod/rquiz/view.php?id=$1', 'course_module');

        return $rules;
    }

    /**
     * Define the restore log rules that will be applied
     * by the restore_logs_processor when restoring
     * rquiz logs. It must return one array
     * of restore_log_rule objects
     * @return restore_log_rule[]
     */
    public static function define_restore_log_rules() {
        $rules = array();

        return $rules;
    }

    /**
     * Define the restore log rules that will be applied
     * by the restore_logs_processor when restoring
     * course logs. It must return one array
     * of restore_log_rule objects
     *
     * Note this rules are applied when restoring course logs
     * by the restore final task, but are defined here at
     * activity level. All them are rules not linked to any module instance (cmid = 0)
     * @return restore_log_rule[]
     */
    public static function define_restore_log_rules_for_course() {
        $rules = array();

        return $rules;
    }
}
