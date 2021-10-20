<?php
/**
 * Settings for module rquiz
 *
 * @copyright Lê Quốc Huy ( The Jolash )
 * @package mod_rquiz
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot.'/mod/rquiz/adminlib.php');

if ($ADMIN->fulltree) {

    $settings->add(new rquiz_awaittime_setting('rquiz/awaittime',
                                                      new lang_string('awaittime', 'rquiz'),
                                                      new lang_string('awaittimedesc', 'rquiz'), 2, PARAM_INT));
}
