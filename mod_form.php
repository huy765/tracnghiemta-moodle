<?php
/**
 * Instance settings form
 *
 * @copyright Lê Quốc Huy ( The Jolash )
 * @package mod_rquiz
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot.'/course/moodleform_mod.php');

/**
 * Class mod_rquiz_mod_form
 */
class mod_rquiz_mod_form extends moodleform_mod {

    /**
     * Form definition
     * @throws coding_exception
     */
    protected function definition() {
        global $CFG;

        $mform = $this->_form;

        // Adding the "general" fieldset, where all the common settings are showed.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('modulename', 'rquiz'), array('size' => '64'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        if ($CFG->branch < 29) {
            $this->add_intro_editor(true, get_string('rquizintro', 'rquiz'));
        } else {
            $this->standard_intro_elements(get_string('rquizintro', 'rquiz'));
        }

        $mform->addElement('header', 'rquizsettings', get_string('rquizsettings', 'rquiz'));

        $mform->addElement('text', 'questiontime', get_string('questiontime', 'rquiz'));
        $mform->addRule('questiontime', null, 'numeric', null, 'client');
        $mform->setDefault('questiontime', 30);
        $mform->setType('questiontime', PARAM_INT);
        $mform->addHelpButton('questiontime', 'questiontime', 'rquiz');

        // Add standard elements, common to all modules.
        $this->standard_coursemodule_elements();

        // Add standard buttons, common to all modules.
        $this->add_action_buttons();
    }
}
