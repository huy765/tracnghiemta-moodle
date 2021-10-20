<?php
/**
 * This implements admin settings in rquiz
 *
 * @copyright Lê Quốc Huy ( The Jolash )
 * @package mod_rquiz
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

defined('MOODLE_INTERNAL') || die;

/**
 * Admin setting for code source, adds validation.
 *
 * @package    mod_rquiz
 * @copyright  Lê Quốc Huy ( The Jolash )
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rquiz_awaittime_setting extends admin_setting_configtext {

    /**
     * Validate data.
     *
     * @param string $data
     * @return mixed True on success, else error message
     */
    public function validate($data) {
        $result = parent::validate($data);
        if ($result !== true) {
            return $result;
        }
        if ((int)$data < 1) {
            return get_string('awaittimeerror', 'rquiz');
        }

        return true;
    }
}
