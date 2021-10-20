<?php

/**
 * Defines backup_rquiz_activity_task class
 *
 * @package     mod_rquiz
 * @category    backup
 * @copyright   Lê Quốc Huy ( The Jolash )
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;

require_once($CFG->dirroot.'/mod/rquiz/backup/moodle2/backup_rquiz_stepslib.php');
require_once($CFG->dirroot.'/mod/rquiz/backup/moodle2/backup_rquiz_settingslib.php');

/**
 * Provides the steps to perform one complete backup of the Forum instance
 */
class backup_rquiz_activity_task extends backup_activity_task {

    /**
     * No specific settings for this activity
     */
    protected function define_my_settings() {
    }

    /**
     * Defines a backup step to store the instance data in the rquiz.xml file
     */
    protected function define_my_steps() {
        $this->add_step(new backup_rquiz_activity_structure_step('rquiz structure', 'rquiz.xml'));
    }

    /**
     * Encodes URLs to the index.php, view.php and discuss.php scripts
     *
     * @param string $content some HTML text that eventually contains URLs to the activity instance scripts
     * @return string the content with the URLs encoded
     */
    public static function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, "/");

        // Link to the list of rquizzes.
        $search = "/(".$base."\/mod\/rquiz\/index.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@rQUIZINDEX*$2@$', $content);

        // Link to rquiz view by moduleid.
        $search = "/(".$base."\/mod\/rquiz\/view.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@rQUIZVIEWBYID*$2@$', $content);

        return $content;
    }
}
