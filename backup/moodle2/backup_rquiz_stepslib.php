<?php

/**
 * Backup a rquiz
 * @package mod_rquiz
 * @subpackage backup-moodle2
 * @copyright Lê Quốc Huy ( The Jolash )
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Define all the backup steps that will be used by the backup_rquiz_activity_task
 */

/**
 * Define the complete rquiz structure for backup, with file and id annotations
 */
class backup_rquiz_activity_structure_step extends backup_activity_structure_step {

    /**
     * Define the backup structure
     * @return backup_nested_element
     * @throws base_element_struct_exception
     * @throws base_step_exception
     */
    protected function define_structure() {

        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated.

        $rquiz = new backup_nested_element('rquiz', array('id'), array(
            'name', 'intro', 'introformat', 'timecreated', 'timemodified', 'questiontime'
        ));

        $sessions = new backup_nested_element('sessions');
        $session = new backup_nested_element('session', array('id'), array(
            'name', 'timestamp'
        ));

        $questions = new backup_nested_element('questions');
        $question = new backup_nested_element('question', array('id'), array(
            'questionnum', 'questiontext', 'questiontextformat', 'questiontime'
        ));

        $answers = new backup_nested_element('answers');
        $answer = new backup_nested_element('answer', array('id'), array(
            'answertext', 'correct'
        ));

        $submissions = new backup_nested_element('submissions');
        $submission = new backup_nested_element('submission', array('id'), array(
            'sessionid', 'userid', 'answerid'
        ));

        // Build the tree.
        if ($userinfo) {
            // Sessions need to be backed up (& restored) before the questions (with the submissions within them).
            $rquiz->add_child($sessions);
            $sessions->add_child($session);
        }

        $rquiz->add_child($questions);
        $questions->add_child($question);

        $question->add_child($answers);
        $answers->add_child($answer);

        if ($userinfo) {
            $question->add_child($submissions);
            $submissions->add_child($submission);
        }

        // Define sources.

        $rquiz->set_source_table('rquiz', array('id' => backup::VAR_ACTIVITYID));
        $question->set_source_table('rquiz_question', array('quizid' => backup::VAR_PARENTID));
        $answer->set_source_table('rquiz_answer', array('questionid' => backup::VAR_PARENTID));

        if ($userinfo) {
            $session->set_source_table('rquiz_session', array('quizid' => backup::VAR_PARENTID));
            $submission->set_source_table('rquiz_submitted', array('questionid' => backup::VAR_PARENTID));
        }

        // Define id annotations.
        if ($userinfo) {
            $submission->annotate_ids('user', 'userid');
        }

        // Define file annotations.
        $rquiz->annotate_files('mod_rquiz', 'intro', null); // This file area hasn't itemid.
        $question->annotate_files('mod_rquiz', 'question', 'id');

        // Return the root element (rquiz), wrapped into standard activity structure.
        return $this->prepare_activity_structure($rquiz);
    }

}
