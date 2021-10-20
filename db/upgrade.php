<?php
/**
 * Upgrade steps
 *
 * @copyright Lê Quốc Huy ( The Jolash )
 * @package mod_rquiz
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade steps
 * @param int $oldversion
 * @return bool
 * @throws ddl_exception
 * @throws ddl_table_missing_exception
 * @throws downgrade_exception
 * @throws upgrade_exception
 */
function xmldb_rquiz_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    // Add fields that were missing in the Moodle 1.9 version of this plugin.
    if ($oldversion < 2012102100) {

        $table = new xmldb_table('rquiz');

        $field = new xmldb_field('intro', XMLDB_TYPE_TEXT, null, null, null, null, null, 'name');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('introformat', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, FORMAT_HTML, 'intro');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'introformat');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'timecreated');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2012102100, 'rquiz');
    }

    if ($oldversion < 2012102101) {

        // Define field questiontextformat to be added to rquiz_question.
        $table = new xmldb_table('rquiz_question');
        $field = new xmldb_field('questiontextformat', XMLDB_TYPE_INTEGER, FORMAT_PLAIN, null, null, null, '1', 'questiontext');

        // Conditionally launch add field questiontextformat.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // rquiz savepoint reached.
        upgrade_mod_savepoint(true, 2012102101, 'rquiz');
    }

    return true;
}
