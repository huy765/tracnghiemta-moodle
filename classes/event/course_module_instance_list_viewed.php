<?php
/**
 * The mod_rquiz instance list viewed event.
 *
 * @package    mod_rquiz
 * @copyright  Lê Quốc Huy ( The Jolash )
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_rquiz\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The mod_rquiz instance list viewed event class.
 *
 * @package    mod_rquiz
 * @since      Moodle 2.7
 * @copyright  Lê Quốc Huy ( The Jolash )
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_module_instance_list_viewed extends \core\event\course_module_instance_list_viewed {
    // No code required here as the parent class handles it all.
}