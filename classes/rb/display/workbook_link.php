<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Pramith Dayananda <pramith.dayananda@catalyst.net.nz>
 * @package mod_workbook
 */

namespace mod_workbook\rb\display;
use totara_reportbuilder\rb\display\base;

/**
 * Display class intended for workbook lik
 *
 * @package mod_workbook
 */
class workbook_link extends base {

    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {
        $extrafields = self::get_extrafields_row($row,$column);

        return \html_writer::link(new \moodle_url('/mod/workbook/view.php',
            array('userid' => $extrafields->userid, 'wid' => $extrafields->workbookid)), $value);
    }

    public static function is_graphable(\rb_column $column, \rb_column_option $option, \reportbuilder $report) {
        return false;
    }
}