<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package    mod_istudyrollsheets
 * @copyright  2024 Jesus Ramirez <jram1993@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_istudyrollsheets;

use stdClass;

/**
 * Class student_info
 *
 * @package    mod_istudyrollsheets
 * @copyright  2024 Jesus Ramirez <jram1993@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class student_info {

    // Get student attendance information.
    public static function get_student_att_info($student_id, $name_of_session, $name_of_session_credits) {
        global $DB;
        // Student Info
        $student_info = new stdClass();
        $student_info->id = $student_id;
        $student_info->school_id = utils::get_field_data($student_id, utils::get_fieldid('schoolid'));
        $student_temp = $DB->get_record('user', ['id' => $student_id] , 'firstname, lastname, deleted, suspended');
        $student_info->student_name = $student_temp->lastname . ', ' . $student_temp->firstname;
        $student_info->deleted = $student_temp->deleted;
        $student_info->suspended = $student_temp->suspended;
        // Find the course and break it up into its components -- example: Spanish IA/Ramirez-Jesus/650512/304.
        $this_course_temp =  utils::get_mycourse(utils::get_field_data($student_id, utils::get_fieldid($name_of_session)));
        $student_info->courseid = $this_course_temp->section;
        $student_info->course_name = $this_course_temp->subject;
        $student_info->teacher = $this_course_temp->teacher;
        $student_info->credits = utils::get_field_data($student_id, utils::get_fieldid($name_of_session_credits));
        $student_info->grade = utils::get_gradelevel(utils::get_field_data($student_id, utils::get_fieldid('graduationyear')));
        return $student_info;
    }

    // Get all of the students for the advisor by day of the week (Monday-Friday).
    public static function get_students_by_session($this_session, $school_advisor, $school_term, $school_day_of_week) {
        global $DB;
        $mystudent_list = [];
        $count = -1;
        $week_day[1] = 'monday';
        $week_day[2] = 'tuesday';
        $week_day[3] = 'wednesday';
        $week_day[4] = 'thursday';
        $week_day[5] = 'friday';
        $this_school_term = utils::get_term($school_term);
        $advisor_name = $school_advisor; // $school_advisor is a number that identifies the advisor
        // Check for a daily schedule. If 'No', student schedules are different each day of the week.
        // If 'Yes', Mondays schedule is applied to each day of the week (Monday-Friday).
        if ($school_term == 1 OR $school_term == 2) {
            $dailyschedule = get_config('mod_istudy', 'dailyschedule');
        }
        else {
            $dailyschedule = get_config('mod_istudy', 'summer_dailyschedule');
        }
        if ($dailyschedule == 'Yes') {
            $school_day_of_week = 1;
        }
        $name_of_session = $this_school_term . $week_day[$school_day_of_week] . 'session' .  $this_session;
        $name_of_session_credits = $this_school_term . $week_day[$school_day_of_week] . 'creditssession' .  $this_session;
        $course_id = utils::get_fieldid($name_of_session);  // fallmondaysession1
        $select = "SELECT * FROM {user_info_data} WHERE fieldid = " . $course_id . " AND data LIKE '%" . $advisor_name . "%'";
        $mystudents = $DB->get_records_sql($select, null, 0,0);
        // Get the data that identifies the student.
        foreach ($mystudents as $mystudent) {
            $temp_info = student_info::get_student_att_info($mystudent->userid, $name_of_session, $name_of_session_credits);
            // Don't include students that have been deleted or suspended.
            if (($temp_info->deleted == false) and ($temp_info->suspended == false)){
                $count = $count + 1;
                $mystudent_list[$count] = new stdClass();
                $mystudent_list[$count]->id = $mystudent->userid;
                $mystudent_list[$count]->school_id = $temp_info->school_id;
                $mystudent_list[$count]->student_name = $temp_info->student_name;
                $mystudent_list[$count]->grade = $temp_info->grade;
                $mystudent_list[$count]->courseid = $temp_info->courseid;
                $mystudent_list[$count]->course_name = $temp_info->course_name;
                $mystudent_list[$count]->credits = $temp_info->credits;
            }
        }
        // If no students were found, then return.
        if ($count == -1) {
            return 0;
        }
        // Sort the list by student ID.
        for ($i=0; $i<=$count; $i++){
            $val = $mystudent_list[$i];
            $j = $i-1;
            while($j>=0 && $mystudent_list[$j]->school_id > $val->school_id){
                $mystudent_list[$j+1] = $mystudent_list[$j];
                $j--;
            }
            $mystudent_list[$j+1] = $val;
        }
        // Sort by student name.
        for ($i=0; $i<=$count; $i++){
            $val = $mystudent_list[$i];
            $j = $i-1;
            while($j>=0 && $mystudent_list[$j]->student_name > $val->student_name){
                $mystudent_list[$j+1] = $mystudent_list[$j];
                $j--;
            }
            $mystudent_list[$j+1] = $val;
        }
        return $mystudent_list;
    }

    // Get the student info and course info.
    public static function get_student_info($student_id, $course_info, $name_of_session_credits) {
        global $DB;

        $student_info = new stdClass();
        $student_info->student_id = $student_id;
        $student_info->school_id = utils::get_field_data($student_id, utils::get_fieldid('schoolid'));
        $student_temp = $DB->get_record('user', ['id' => $student_id] , 'firstname, lastname, deleted, suspended');
        $student_info->deleted = $student_temp->deleted;
        $student_info->suspended = $student_temp->suspended;
        $student_info->student_name = $student_temp->lastname . ', ' . $student_temp->firstname;
        $student_info->grade = utils::get_gradelevel(utils::get_field_data($student_id, utils::get_fieldid('graduationyear')));
        $temp_course = new stdClass();
        $temp_course = utils::get_mycourse($course_info);
        $student_info->courseid = $temp_course->section;
        $student_info->course_name =  $temp_course->subject;
        $credits_needed = utils::get_field_data($student_id, utils::get_fieldid($name_of_session_credits));
        $student_info->credits = $credits_needed;
        $student_info->distance_learning = utils::get_field_data($student_id, utils::get_fieldid('distancelearningonly'));
        return $student_info;
    }
}