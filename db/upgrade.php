<?php
/*
 * Copyright (C) 2015 onwards Catalyst IT
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author  Eugene Venter <eugene@catalyst.net.nz>
 * @package mod_workbook
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * This file keeps track of upgrades to the workbook module
 *
 * Sometimes, changes between versions involve alterations to database
 * structures and other major things that may break installations. The upgrade
 * function in this file will attempt to perform all the necessary actions to
 * upgrade your older installation to the current version. If there's something
 * it cannot do itself, it will tell you what you need to do.  The commands in
 * here will all be database-neutral, using the functions defined in DLL libraries.
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute workbook upgrade from the given old version
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_workbook_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager(); // Loads ddl manager and xmldb classes.

    if ($oldversion < 2016060904) {

        // Define field allowfileuploads to be added to workbook_page_item.
        $table = new xmldb_table('workbook_page_item');
        $field = new xmldb_field('allowfileuploads', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'allowcomments');

        // Conditionally launch add field allowfileuploads.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Workbook savepoint reached.
        upgrade_mod_savepoint(true, 2016060904, 'workbook');
    }

    if ($oldversion < 2019093000) {

        // Define field notifyonsubmission to be added to workbook.
        $table = new xmldb_table('workbook');
        $field = new xmldb_field('notifyonsubmission', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'timemodified');

        // Conditionally launch add field notifyonsubmission.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Workbook savepoint reached.
        upgrade_mod_savepoint(true, 2019093000, 'workbook');
    }


    return true;
}
