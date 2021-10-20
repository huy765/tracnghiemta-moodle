<?php
/**
 * Form to edit a set of questions
 *
 * @copyright Lê Quốc Huy ( The Jolash )
 * @package mod_rquiz
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir.'/formslib.php');

/**
 * Class rquiz_editquestion_form
 */
class rquiz_editquestion_form extends moodleform {

    /**
     * Form definition
     * @throws coding_exception
     */
    protected function definition() {

        $mform = $this->_form;
        $numanswers = $this->_customdata['numanswers'];
        $editoroptions = $this->_customdata['editoroptions'];
        $mform->addElement('hidden', 'quizid');
        $mform->setType('quizid', PARAM_INT);
        $mform->addElement('hidden', 'questionid');
        $mform->setType('questionid', PARAM_INT);
        $mform->addElement('hidden', 'numanswers');
        $mform->setType('numanswers', PARAM_INT);

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('editor', 'questiontext_editor', get_string('questiontext', 'mod_rquiz'), null, $editoroptions);
        $mform->addRule('questiontext_editor', null, 'required', null, 'client');

        $mform->addElement('text', 'questiontime', get_string('editquestiontime', 'mod_rquiz'), 0);
        $mform->setType('questiontime', PARAM_INT);

        // Answers.
        for ($i = 1; $i <= $numanswers; $i++) {
            $ansgroup = array(
                $mform->createElement('radio', 'answercorrect', '', '', $i,
                                      array('class' => 'rquiz_answerradio')),
                $mform->createElement('text', "answertext[$i]", '', array('size' => 30)),
            );
            $mform->addGroup($ansgroup, 'answer', get_string('answer', 'rquiz').$i, array(' '), false);
            $mform->setType("answertext[$i]", PARAM_RAW);
        }
        $mform->addElement('radio', 'answercorrect', get_string('nocorrect', 'rquiz'), '', 0,
                           array('class' => 'rquiz_answerradio'));
        $mform->addElement('submit', 'addanswers', get_string('addanswers', 'rquiz'));

        // Action buttons.
        $actiongrp = array(
            $mform->createElement('submit', 'save', get_string('updatequestion', 'rquiz')),
            $mform->createElement('submit', 'saveadd', get_string('saveadd', 'rquiz')),
            $mform->createElement('cancel'),
        );
        $mform->addGroup($actiongrp, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }

    /**
     * Update form after data has been set.
     */
    public function definition_after_data() {
        // Override any 'numanswers' value from the form submission (as it will be wrong if the 'add answers' button
        // was clicked).
        $mform = $this->_form;
        $numanswers = $this->_customdata['numanswers'];
        $el = $mform->getElement('numanswers');
        $el->setValue($numanswers);
    }
}

