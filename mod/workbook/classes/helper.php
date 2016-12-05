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

namespace mod_workbook;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/totara/message/messagelib.php');

class helper {
    static function is_complete($workbookid, $userid) {
        global $DB;

        $sql = "SELECT pi.*
            FROM {workbook_page} p
            JOIN {workbook_page_item} pi ON p.id = pi.pageid AND pi.requiredgrade > 0
            LEFT JOIN {workbook_page_item_submit} pis ON pi.id = pis.pageitemid AND pis.superseded = 0 AND pis.status = ?
            WHERE p.workbookid = ? AND pis.id IS NULL";

        return !$DB->record_exists_sql($sql, array(WORKBOOK_STATUS_PASSED, $workbookid));
    }


    static function get_assessors($workbookid, $courseid, $userid) {
        $cm = get_coursemodule_from_instance('workbook', $workbookid, $courseid, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);

        $groups = groups_get_all_groups($courseid, $userid);
        if (empty($groups) || groups_get_activity_groupmode($cm) == NOGROUPS) {
            // Return all assessors in the course.
            return get_enrolled_users($context, 'mod/workbook:assess');
        }

        // Get all assessors in the user's group.
        $users = array();
        foreach ($groups as $group) {
            $users = $users + get_enrolled_users($context, 'mod/workbook:assess', $group->id);
        }

        return $users;
    }


    static function send_submission_notifications($since) {
        global $DB;

        // Send submission notifications to assessors.
        $sql = "SELECT pis.id AS submissionid, u.*,
                w.id AS workbookid,
                w.name AS workbookname,
                w.course AS courseid,
                p.id AS pageid,
                c.shortname AS courseshortname,
                pi.itemtype
            FROM {workbook} w
            JOIN {workbook_page} p ON w.id = p.workbookid
            JOIN {workbook_page_item} pi ON p.id = pi.pageid
            JOIN {workbook_page_item_submit} pis ON pi.id = pis.pageitemid
            JOIN {user} u ON pis.userid = u.id
            JOIN {course} c ON w.course = c.id
            WHERE pis.status = ? AND pis.timemodified >= ? AND pis.superseded = 0";
        $submissions = $DB->get_records_sql($sql, array(WORKBOOK_STATUS_SUBMITTED, $since));

        $sentcount = 0;
        foreach ($submissions as $usersubmission) {
            $assessors = self::get_assessors($usersubmission->workbookid, $usersubmission->courseid, $usersubmission->id);
            foreach ($assessors as $assessor) {
                $eventdata = new \stdClass();
                $eventdata->userto = $assessor;
                $eventdata->userfrom = $usersubmission;
                $eventdata->icon = 'elearning-complete';
                $eventdata->contexturl = new \moodle_url('/mod/workbook/view.php',
                    array(
                        'userid' => $usersubmission->id,
                        'wid' => $usersubmission->workbookid,
                        'pid' => $usersubmission->pageid
                    )
                );
                $eventdata->contexturl = $eventdata->contexturl->out();
                $strobj = new \stdClass();
                $strobj->user = fullname($usersubmission);
                $strobj->workbook = format_string($usersubmission->workbookname);
                $strobj->courseshortname = format_string($usersubmission->courseshortname);
                $strobj->itemtype = get_string('type'.$usersubmission->itemtype, 'workbook');
                $eventdata->subject = get_string('msg:itemsubmissionsubject', 'workbook', $strobj);
                $eventdata->fullmessage = get_string('msg:itemsubmission', 'workbook', $strobj);
                // $eventdata->sendemail = TOTARA_MSG_EMAIL_NO;

                tm_task_send($eventdata);
                $sentcount++;
            }
        }

        return $sentcount;
    }


    static function send_graded_notifications($since) {
        global $DB;

        // Send graded notifications to students.
        $sql = "SELECT pis.id AS submissionid, u.*,
                w.id AS workbookid,
                w.name AS workbookname,
                w.course AS courseid,
                p.id AS pageid,
                c.shortname AS courseshortname,
                pis.gradedby,
                pi.itemtype
            FROM {workbook} w
            JOIN {workbook_page} p ON w.id = p.workbookid
            JOIN {workbook_page_item} pi ON p.id = pi.pageid
            JOIN {workbook_page_item_submit} pis ON pi.id = pis.pageitemid
            JOIN {user} u ON pis.userid = u.id
            JOIN {course} c ON w.course = c.id
            WHERE pis.status IN (?, ?) AND pis.timegraded >= ? AND pis.superseded = 0";
        $gradings = $DB->get_records_sql($sql, array(WORKBOOK_STATUS_GRADED, WORKBOOK_STATUS_PASSED, $since));

        $sentcount = 0;
        foreach ($gradings as $grading) {
            $eventdata = new \stdClass();
            $eventdata->userto = $grading;
            $eventdata->userfrom = \totara_core\totara_user::get_user($grading->gradedby);
            $eventdata->icon = 'elearning-update';
            $eventdata->contexturl = new \moodle_url('/mod/workbook/view.php',
                array(
                    'userid' => $grading->id,
                    'wid' => $grading->workbookid,
                    'pid' => $grading->pageid
                )
            );
            $eventdata->contexturl = $eventdata->contexturl->out();
            $strobj = new \stdClass();
            $strobj->workbook = format_string($grading->workbookname);
            $strobj->courseshortname = format_string($grading->courseshortname);
            $strobj->itemtype = get_string('type'.$grading->itemtype, 'workbook');
            $eventdata->subject = get_string('msg:itemgradedsubject', 'workbook', $strobj);
            $eventdata->fullmessage = get_string('msg:itemgraded', 'workbook', $strobj);
            // $eventdata->sendemail = TOTARA_MSG_EMAIL_NO;

            tm_alert_send($eventdata);
            $sentcount++;
        }

        return $sentcount;
    }


    static function get_workbook_for_pageitem($pageitemid) {
        global $DB;

        $sql = "SELECT w.*, pi.pageid
            FROM {workbook_page_item} pi
            JOIN {workbook_page} p ON pi.pageid = p.id
            JOIN {workbook} w ON p.workbookid = w.id
            WHERE pi.id = ?";

        return $DB->get_record_sql($sql, array($pageitemid), MUST_EXIST);
    }


    static function send_comment_notifications($since) {
        global $DB;

        $commentarea = 'workbook_page_item_';

        // Get all recent comments.
        $sql = "SELECT *
            FROM {comments} c
            WHERE commentarea LIKE '{$commentarea}%' AND timecreated >= ?";
        $comments = $DB->get_records_sql($sql, array($since));

        $sentcount = 0;
        foreach ($comments as $comment) {
            $commentuser = \core_user::get_user($comment->userid);
            $pageitemid = substr($comment->commentarea, strlen($commentarea));
            $workbookuserid = $comment->itemid;
            if (empty($pageitemid)) {
                // Something's not right - skip this one.
                continue;
            }
            $workbook = self::get_workbook_for_pageitem($pageitemid);

            if ($workbookuserid == $comment->userid) {
                // Workbook owner comment - send notification to other participants.
                $sql = "SELECT *
                    FROM {user}
                    WHERE id IN (
                        SELECT DISTINCT userid
                        FROM {comments} c
                        WHERE commentarea = ? AND itemid = ? AND userid != ?
                    )";
                $participants = $DB->get_records_sql($sql, array($comment->commentarea, $comment->userid, $comment->userid));
                if (empty($participants)) {
                    // No comment participants yet - send notification to assessors.
                    $assessors = self::get_assessors($workbook->id, $workbook->course, $workbookuserid);
                    foreach ($assessors as $assessor) {
                        self::send_comment_notification($commentuser, $assessor, $workbook, $workbookuserid);
                        $sentcount++;
                    }
                } else {
                    foreach ($participants as $participant) {
                        self::send_comment_notification($commentuser, $participant, $workbook, $workbookuserid);
                        $sentcount++;
                    }
                }
            } else {
                // Send notification to workbook user.
                $workbookuser = \core_user::get_user($comment->itemid);
                self::send_comment_notification($commentuser, $workbookuser, $workbook, $workbookuserid);
                $sentcount++;
            }
        }

        return $sentcount;
    }


    static function send_comment_notification($userfrom, $userto, $workbook, $workbookuserid) {
        $eventdata = new \stdClass();
        $eventdata->userto = $userto;
        $eventdata->userfrom = $userfrom;
        $eventdata->icon = 'elearning-newcomment';
        $eventdata->contexturl = new \moodle_url('/mod/workbook/view.php',
            array('wid' => $workbook->id, 'userid' => $workbookuserid, 'pid' => $workbook->pageid));
        $eventdata->contexturl = $eventdata->contexturl->out();
        $strobj = new \stdClass();
        $strobj->user = fullname($userfrom);
        $strobj->workbook = format_string($workbook->name);
        $eventdata->subject = get_string('msg:commentsubject', 'workbook', $strobj);
        $eventdata->fullmessage = get_string('msg:comment', 'workbook', $strobj);
        // $eventdata->sendemail = TOTARA_MSG_EMAIL_NO;

        tm_alert_send($eventdata);
    }


    static function get_navigation_block($userworkbook, $currentpageid, $renderer) {
        $bc = new \block_contents();
        $bc->attributes['id'] = 'mod_workbook_navblock';
        $bc->title = get_string('workbooknavigation', 'workbook');
        $bc->content = $renderer->navigation($userworkbook, $currentpageid);

        return $bc;
    }


    static function get_itemtype_instance($workbookid, $item) {
        $itemclass = '\mod_workbook\itemtype\\'.$item->itemtype;
        return new $itemclass($workbookid, $item);
    }

    static function can_assess(\context_module $context, $workbookuserid) {
        return is_enrolled($context, $workbookuserid) && has_capability('mod/workbook:assess', $context);
    }

    static function can_submit(\context_module $context, $workbookuserid) {
        global $USER;

        return is_enrolled($context, $workbookuserid) && $USER->id == $workbookuserid && has_capability('mod/workbook:view', $context);
    }

}
