<?php
/**
 * Restore a rquiz.
 * @package mod_rquiz
 * @subpackage backup-moodle2
 * @copyright Lê Quốc Huy ( The Jolash )
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Define all the restore steps that will be used by the restore_rquiz_activity_task
 */

/**
 * Structure step to restore one rquiz activity
 */
class restore_rquiz_activity_structure_step extends restore_activity_structure_step {

    /**
     * Define the restore structure
     * @return mixed
     * @throws base_step_exception
     */
    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('rquiz', '/activity/rquiz');
        $paths[] = new restore_path_element('rquiz_question', '/activity/rquiz/questions/question');
        $paths[] = new restore_path_element('rquiz_answer',
                                            '/activity/rquiz/questions/question/answers/answer');
        if ($userinfo) {
            $paths[] = new restore_path_element('rquiz_session', '/activity/rquiz/sessions/session');
            $paths[] = new restore_path_element('rquiz_submission',
                                                '/activity/rquiz/questions/question/submissions/submission');
        }

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Create a rquiz record.
     * @param object|array $data
     * @throws base_step_exception
     * @throws dml_exception
     */
    protected function process_rquiz($data) {
        global $DB;

        $data = (object)$data;
        $data->course = $this->get_courseid();

        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $newitemid = $DB->insert_record('rquiz', $data);
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Create a question record.
     * @param object|array $data
     * @throws base_step_exception
     * @throws dml_exception
     * @throws restore_step_exception
     */
    protected function process_rquiz_question($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();
        $data->quizid = $this->get_new_parentid('rquiz');

        $newitemid = $DB->insert_record('rquiz_question', $data);
        $this->set_mapping('rquiz_question', $oldid, $newitemid, true);
    }

    /**
     * Create an answer record.
     * @param object|array $data
     * @throws dml_exception
     * @throws restore_step_exception
     */
    protected function process_rquiz_answer($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->questionid = $this->get_new_parentid('rquiz_question');

        $newitemid = $DB->insert_record('rquiz_answer', $data);
        $this->set_mapping('rquiz_answer', $oldid, $newitemid);
    }

    /**
     * Create a session record
     * @param object|array $data
     * @throws dml_exception
     * @throws restore_step_exception
     */
    protected function process_rquiz_session($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->quizid = $this->get_new_parentid('rquiz');
        $data->timestamp = $this->apply_date_offset($data->timestamp);

        $newitemid = $DB->insert_record('rquiz_session', $data);
        $this->set_mapping('rquiz_session', $oldid, $newitemid, true);
    }

    /**
     * Create a submission record.
     * @param object|array $data
     * @throws dml_exception
     */
    protected function process_rquiz_submission($data) {
        global $DB;

        $data = (object)$data;

        $data->questionid = $this->get_new_parentid('rquiz_question');
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->answerid = $this->get_mappingid('rquiz_answer', $data->answerid);

        if ($data->answerid) {
            // Skip any user responses that don't match an existing answer.
            $DB->insert_record('rquiz_submitted', $data);
        }
    }

    /**
     * Extra steps after we've finished the restore.
     */
    protected function after_execute() {
        // Add question files.
        $this->add_related_files('mod_rquiz', 'question', 'rquiz_question');
    }
}
