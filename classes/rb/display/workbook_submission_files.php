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
 * Display class intended for max grade
 *
 * @package mod_workbook
 */
class workbook_submission_files extends base {

    /**
     * Handles the display
     *
     * @param string $value
     * @param string $format
     * @param \stdClass $row
     * @param \rb_column $column
     * @param \reportbuilder $report
     * @return string
     */
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {
        if (empty($row->allowfileuploads)) {
            return '';
        }
        $itemtypeclass = '\mod_workbook\itemtype\\'.$row->itemtype;
        if (!$itemtypeclass::supports_file_uploads()) {
            return '';
        }

        $item = new stdClass();
        $item->pageid = $row->pageid;
        $item->itemtype = $row->itemtype;
        $item->name = $row->name;
        $item->content = $row->content;
        $item->requiredgrade = $row->requiredgrade;
        $item->allowcomments = $row->allowcomments;
        $item->allowfileuploads = $row->allowfileuploads;
        $itemtype = \mod_workbook\helper::get_itemtype_instance($row->workbookid, $item);

        return $itemtype->list_page_item_files($row->userid, $submissionid);
    }

    /**
     * Is this column graphable?
     *
     * @param \rb_column $column
     * @param \rb_column_option $option
     * @param \reportbuilder $report
     * @return bool
     */
    public static function is_graphable(\rb_column $column, \rb_column_option $option, \reportbuilder $report) {
        return false;
    }
}
