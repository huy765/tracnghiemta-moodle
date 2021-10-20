<?php
/**
 * The mod_rquiz course module viewed event.
 *
 * @package    mod_rquiz
 * @copyright  Lê Quốc Huy ( The Jolash )
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_rquiz\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The mod_rquiz course module viewed event class.
 *
 * @package    mod_rquiz
 * @since      Moodle 2.7
 * @copyright  Lê Quốc Huy ( The Jolash )
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_module_viewed extends \core\event\course_module_viewed {

    /**
     * Init method.
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'rquiz';
    }
}