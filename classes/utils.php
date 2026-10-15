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

/**
 * Class utils
 *
 * @package    mod_istudyrollsheets
 * @copyright  2024 Jesus Ramirez <jram1993@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class utils {

    // Replace the dash in the teacher name with a comma and space.
    public static function put_comma_in_name($myteacher) {
        $postion = strrpos($myteacher,"-");
        $name_with_dash = substr($myteacher, $postion);
        $name_with_comma = ', ' . substr($name_with_dash,1);
        $new_name = str_replace($name_with_dash,$name_with_comma,$myteacher);
        return $new_name;
    }

    // Find Monday for the date chosen.
    public static function get_monday($my_date) {
        $my_date_adjustment['Monday'] = 0;
        $my_date_adjustment['Tuesday'] = -1;
        $my_date_adjustment['Wednesday'] = -2;
        $my_date_adjustment['Thursday'] = -3;
        $my_date_adjustment['Friday'] = -4;
        $my_date_adjustment['Saturday'] = 2;
        $my_date_adjustment['Sunday'] = 1;
        $date_chosen = date("l", $my_date);
        $adjusted_date = strtotime("+" . $my_date_adjustment[$date_chosen] . " day", $my_date);
        return $adjusted_date;
    }

    // Set the time to 6:00 pm for this day..
    public static function set_to_12pm($mydatetoday) {
        // Adjust the day so that the time is set to 12:00 pm.
        // $mydatetoday = ((intval($mydatetoday/86400))*86400)+(86400/2);
        return $mydatetoday;
    }

     // Return the term according to the date chosen (fall=1, spring=2, and summer=3).
    public static function get_this_term($date_chosen) {
        // Get the starting dates of each week for months 10 and 11.
        for ($m = 10; $m <= 11; $m++) {
            for ($w = 1; $w <= 4; $w++) {
                $week_start_date[$m][$w] = utils::set_to_12pm(strtotime(get_config('mod_istudy', 'month' . $m . 'week0' . $w . 'date')));
                $week_points[$m][$w] = get_config('mod_istudy', 'month' . $m . 'week0' . $w );
            }
        }

        // Find the last week of school.
        $last_week_found = false;
        $last_month = 11;
        $last_week = 4;
        $one_day = 86400;
        $four_days = 86400+86400+86400+86400;
        for ($m = 11; $m >= 10; $m--) {
            for ($w = 4; $w >= 1; $w--) {
                if (($week_points[$m][$w] > 0) and ($last_week_found == false)){
                    $last_month = $m;
                    $last_week = $w;
                    $last_week_found = true;
                }
            }
        }
        $spring_start_date_text = get_config('mod_istudy', 'spring_start_date');
        $spring_start_date = utils::set_to_12pm(strtotime($spring_start_date_text)) - $one_day;
        $last_school_day = $week_start_date[$last_month][$last_week]+ $four_days;

        // The default term is 1 (fall).
        $this_term = 1;
        // Check the date against the spring start date.
        if ($date_chosen > $spring_start_date) {
            $this_term = 2;
        }
        // Check the date against the summer start date.
        if ($date_chosen > $last_school_day) {
            $this_term = 3;
        }
        return $this_term;
    }

    // Return the term chosen - 1=Fall, 2=Spring, 3=Summer.
    public static function get_mycourse($mycourse) {
        $course_info = new \stdClass();
        $course_info->subject = strstr($mycourse, '/', true);
        $mycourse = strstr($mycourse, "/");
        $mycourse = substr($mycourse, 1);
        $course_info->teacher = strstr($mycourse, '/', true);
        $mycourse = strstr($mycourse, "/");
        $mycourse = substr($mycourse, 1);
        $course_info->section = strstr($mycourse, '/', true);
        $course_info->room = teacher_info::get_advisor_room($course_info->teacher);
        return $course_info;
    }

    // Return the school course number.
    public static function get_mycourseid($mycourse_name) {
        global $DB;
        $table = 'istudycourse_hours';
        $select = "subject = :subject";
        $params =['subject'=>$mycourse_name];
        if ($mycourse = $DB->get_record_select($table, $select, $params)) {
            return $mycourse->section;
        }
        else {
            return 0;
        }
    }

    // Return the term chosen - 1=Fall, 2=Spring, 3=Summer.
    public static function get_term($term_choice) {
        $schoolterms[1] = 'fall';
        $schoolterms[2] = 'spring';
        $schoolterms[3] = 'summer';
        return $schoolterms[$term_choice];
    }

    // Calculate the grade level from the graduation year.
    public static function get_gradelevel($grad_year) {
        $currentmonth = date('m');
        $currentyear = date('Y');
        if ($grad_year <= $currentyear) {
            $years_left = 0;
        }
        else if ($currentmonth > 7) {
            $years_left = $grad_year - ($currentyear + 1);
        }
        else {
            $years_left = $grad_year - $currentyear;
        }
        $my_grade_level = 12 - $years_left;
        return $my_grade_level;
    }

    // Get the user_info_field id to use in searching user_info_data.
    public static function get_fieldid($item_name) {
        global $DB;
        $item = $DB->get_record('user_info_field', ['shortname' => $item_name], 'id');
        return $item->id;
    }

    // Search user_info_data for student data using the student id and user_info_field id.
    public static function get_field_data($student_id, $itemid) {
        global $DB;
        if ($DB->record_exists('user_info_data', ['userid'=>$student_id, 'fieldid'=>$itemid])) {
            $search_results = $DB->get_record('user_info_data', ['userid'=>$student_id, 'fieldid'=>$itemid], 'data');
            return $search_results->data;
        }
        else {
            return '';
        }
    }

    // Sign in sheet title.
    public static function get_title_sign_in_sheets($school_advisor, $school_startofweek, $school_day_of_week) {
        // Page Title - School Information
        $adjusted_date = strtotime("+" . ($school_day_of_week - 1) . " day", $school_startofweek);
        $this_term_text = strtoupper((utils::get_term(utils::get_this_term($school_startofweek))));
        $title_info = '<h1>' . get_config('mod_istudy', 'schoolname') . '</h1>' .
            '<h2>' . utils::put_comma_in_name($school_advisor) . ' - ' . $this_term_text . ' Sign In Sheet</h2><h3>' . date("l, M d, Y ", $adjusted_date) . '</h3><br>';
        // Table heading
        $title_info = $title_info . '<table><tr class="cellborderall">' .
            '<th width="2%">#</th><th width="30%" class="cellborderall">STUDENT NAME</th><th width="2%" class="cellborderleft">A</th>' .
            '<th width="2%">T</th><th width="2%" class="cellborderleft">P</th><th width="2%">D</th>' .
            '<th width="28%" class="cellborderleft">COURSE NAME</th><th width="24%">SIGNATURE</th>' .
            '<th width="4%" class="cellborderleft">IN</th><th width="4%">OUT</th></tr>';
        return $title_info;
    }

    // Get the session title - includes the period number and the start and end time.
    public static function get_header_sign_in_sheets($session_number, $session_time) {
        $header_info = '<tr><td colspan="10" class="mycell"><center><b>' . $session_number . '</b> (' . $session_time . ')</center></td></tr>';
        return $header_info;
    }

    // Start a new page since the end of the page has been reached.
    public static function start_new_page($school_advisor, $school_startofweek, $myday, $mysession, $time_session) {
        // Initialize the variable.
        $new_page_text = '';
        $new_page_text = $new_page_text . '</table>';
        // Add the legend at the end.
        $new_page_text = $new_page_text . '<h4>A = Absent, T = Tardy, P = Present in person, D = Distance learner, * = Advisory Student</h4>';
        // Add a page break - start a new page.
        $new_page_text = $new_page_text . '<P style="page-break-before: always">';
        $new_page_text = $new_page_text . utils::get_title_sign_in_sheets($school_advisor, $school_startofweek, $myday);
        $new_page_text = $new_page_text . utils::get_header_sign_in_sheets($mysession, $time_session);
        return $new_page_text;
    }

    // Print the sign in sheets for one teacher.
    public static function get_my_sign_in_sheets($school_advisor, $school_startofweek, $school_day_of_week) {
        // The total number of rows per page, this does not include the page title.
        $total_page_count = 55;
        $page_count = 0;
        $count = 0;
        $week_day[1] = 'monday';
        $week_day[2] = 'tuesday';
        $week_day[3] = 'wednesday';
        $week_day[4] = 'thursday';
        $week_day[5] = 'friday';
        $number_of_sessions = get_config('mod_istudy', 'number_of_sessions');
        $school_term = utils::get_this_term($school_startofweek);
        $number_of_blank_rows = 10;
        // Get the start and end times for each session.
        $summer = '';
        if ($school_term == 3) {$summer = 'summer_';}
        $time_session[][] = array() ;
        for ($d = 1; $d <= 5; $d++) {
            for ($t = 1; $t <= $number_of_sessions; $t++) {
                $time_session[$d][$t] = get_config('mod_istudy', $summer . $week_day[$d] . 'session' . $t);
            }
        }
        // Initialize html page.
        $advisor_info = '<link rel="stylesheet" type="text/css" href="styles-roll_sheets.css"><html><head><title>Sign-in Sheets</title></head><body>';
        // Print the daily student roster for each day ($myday) - Monday=1, Tuesday=2, Wednesday=3, Thursday=4, & Friday=5.
        for ($myday = 1; $myday <= 5; $myday++) {
            // Print all sheets for Monday-Friday, if == 6 otherwise just print the day of the week that was chosen.
            // $page_count counts the number of students per session, it is reset at the start of a new session.
            $page_count = 0;
            if ($school_day_of_week == 6 OR $school_day_of_week == $myday) {
                $advisor_info = $advisor_info . utils::get_title_sign_in_sheets($school_advisor, $school_startofweek, $myday);
                for ($mysession = 1; $mysession <= $number_of_sessions; $mysession++) {
                    $advisor_info = $advisor_info . utils::get_header_sign_in_sheets('PERIOD ' . $mysession, $time_session[$myday][$mysession]);
                    // The header above counts as another row on the table.
                    $page_count = $page_count + 1;
                    // Get the students names - find all students that have the teacher on the day of the week and during current session.
                    $all_students = student_info::get_students_by_session($mysession, $school_advisor, $school_term, $myday);
                    if ($all_students == 0) {
                        $temp_student_info_row = '<tr><td colspan="10" class="cellbordersides"><b><center>NONE</center></b></td></tr>';
                        $advisor_info = $advisor_info . $temp_student_info_row;
                        $page_count = $page_count + 1;
                        // If we have reached the bottom of the page, start a new page.
                        if ($page_count >= $total_page_count) {
                            $advisor_info = $advisor_info . utils::start_new_page($school_advisor, $school_startofweek, $myday, $mysession, $time_session[$myday][$mysession]);
                            $page_count = 0;
                        }
                    }
                    else {
                        // This term has a different schedules each day - similar to a college schedule.
                        $student_count = 0;
                        $temp_student_info_row = '';
                        foreach ($all_students as $all_student) {
                            $student_id = $all_student->id;
                            // Initialize new row on the table: contains student information - name, grade, subject, and credits.
                            $temp_student_info_row = '';
                            // Check to see if this is an advisory student for teacher.
                            $my_advisory_student = '';
                            $my_advisor = utils::get_field_data($student_id, utils::get_fieldid('advisor'));
                            if ($my_advisor == $school_advisor) {
                                $my_advisory_student = '*';
                            }
                            // Distance learning students (100% of the time) - change color of the text and background.
                            $distance_learning_yes = '';
                            $distance_learning_only = utils::get_field_data($student_id, utils::get_fieldid('distancelearningonly'));
                            if ($distance_learning_only == 'Yes'){
                                $distance_learning_yes = ' style="background-color:#B9B9B9;"';
                            }
                            $student_name = '<b' . $distance_learning_yes . '>' . $all_student->student_name . ' (' . $all_student->grade . ')</b>' . $my_advisory_student;
                            $count = $count + 1;
                            $student_count = $student_count + 1;
                            $temp_student_info_row = $temp_student_info_row .
                                '<tr class="cell_student"><td class="mycellnum">' . $student_count . '</td><td><p class="cut_text_student">' . $student_name .
                                '</p></td><td class="cellborderleft"></td><td></td><td class="cellborderleft"></td><td class="cellborderright"></td>';
                            $temp_student_info_row = $temp_student_info_row . '<td><i><p class="cut_text_course">' . $all_student->course_name;
                            $temp_student_info_row = $temp_student_info_row . ' [' . $all_student->credits . ']' . '</p></i></td>';
                            // Add the cells for signature, time in, and time out.
                            $temp_student_info_row = $temp_student_info_row . '<td></td><td class="cellborderleft"></td><td></td></tr>';
                            $page_count = $page_count + 1;
                            // If we have reached the bottom of the page, start a new page.
                            if ($page_count >= $total_page_count and $student_count > 1) {
                                $advisor_info = $advisor_info . utils::start_new_page($school_advisor, $school_startofweek, $myday, 'PERIOD ' . $mysession, $time_session[$myday][$mysession]);
                                $page_count = 1;
                            }
                            $advisor_info = $advisor_info . $temp_student_info_row;
                        }
                    }
                }
                // Add blank rows at the end of the table - this is for student attending class, but are not scheduled for that time.
                $advisor_info = $advisor_info . '<tr><td colspan="10" class="mycell"><center>(Students not Scheduled)</center></td></tr>';
                $page_count = $page_count + 1;
                for ($blank_row = 1; $blank_row <= $number_of_blank_rows; $blank_row++) {
                    // If we have not reached the last blank line then continue, else print the last blank line with a dark border on the bottom.
                    if ($blank_row < $number_of_blank_rows) {
                        $page_count = $page_count + 1;
                        // If we have reached the bottom of the page, start a new page.
                        if ($page_count >= $total_page_count and $blank_row > 1) {
                            $advisor_info = $advisor_info . utils::start_new_page($school_advisor, $school_startofweek, $myday, '', 'Students not Scheduled');
                            $page_count = 1;
                        }
                        $advisor_info = $advisor_info . '<tr><td class="mycellnum">' . $blank_row .
                            '</td><td></td><td class="cellborderleft"></td><td></td><td class="cellborderleft"></td><td class="cellborderright"></td><td></td><td></td><td class="cellborderleft"></td><td></td></tr>';
                        }
                    // Last line printed will have solid boder along the bottom.
                    else {
                        $advisor_info = $advisor_info . '<tr class="cellborderbottom"><td class="mycellnum">' . $blank_row .
                            '</td><td></td><td class="cellborderleft"></td><td></td><td class="cellborderleft"></td><td class="cellborderright"></td><td></td><td></td><td class="cellborderleft"></td><td></td></tr>';
                    }
                }
                $advisor_info = $advisor_info . '</table>';
                // Add the legend at the end.
                $advisor_info = $advisor_info . '<h4>A = Absent, T = Tardy, P = Present in person, D = Distance learner, * = Advisory Student</h4>';
                if (($school_day_of_week == 6) AND ($myday < 5)) {
                    $advisor_info = $advisor_info . '<P style="page-break-before: always">';
                }
            }
        }
        $advisor_info = $advisor_info . '</body></html>';
        // Print the advisor and students.
        $advisor_print_newtab = "<script>
                var newWindow = window.open('');
                newWindow.document.write('$advisor_info');
            </script>";
        echo $advisor_print_newtab;
        return;
    }

    // Get the start date for this term - fall, spring, or summer.
    public static function term_start_date($this_schoolterm) {
        // Get the starting dates for the Fall and Spring semesters
        for ($m = 1; $m <= 11; $m++) {
            // For months 1-9, zero must be inserted after month (e.g., month09), sloppy programming.
            if ($m < 10) {
                $school_month_temp = 'month0' . $m;
            }
            else {
                $school_month_temp = 'month' . $m;
            }
            for ($w = 1; $w <= 4; $w++) {
                $week_start_date[$m][$w] = strtotime(get_config('mod_istudy', $school_month_temp . 'week0' . $w . 'date'));
                $week_points[$m][$w] = get_config('mod_istudy', $school_month_temp . 'week0' . $w );
            }
        }
        // Find the last week of school.
        $last_month = 11;
        $last_week = 4;
        for ($m = 1; $m <= 11; $m++) {
            for ($w = 1; $w <= 4; $w++) {
                if ($week_points[$m][$w] > 0){
                    $last_month = $m;
                    $last_week = $w;
                }
            }
        }
        // Fall term - return the first week of school.
        if ($this_schoolterm == 1) {
            return $week_start_date[1][1];
        }
        // Spring term - return the first week in January.
        else if ($this_schoolterm == 2) {
            $first_spring_day = $week_start_date[6][1];
            // If the first day of the Spring semester starts during month5, adjust the date.
            for ($w = 4; $w >= 1; $w--) {
                if (date("n", $week_start_date[5][$w]) == '1'){
                    $first_spring_day = $week_start_date[5][$w];
                }
            }
            return $first_spring_day;
        }
        // Summer term - return the Monday after the last week of school.
        else {
            $adjusted_date =  strtotime("+1 week", $week_start_date[$last_month][$last_week]);
            return $adjusted_date;
        }
    }

    // Get the starting and ending dates for each term (fall, spring, and summer).
    public static function get_term_dates() {
        $monday_dates_by_week = [];
        $monday_credits_by_week = [];
        $month[1] = 'month01';
        $month[2] = 'month02';
        $month[3] = 'month03';
        $month[4] = 'month04';
        $month[5] = 'month05';
        $month[6] = 'month06';
        $month[7] = 'month07';
        $month[8] = 'month08';
        $month[9] = 'month09';
        $month[10] = 'month10';
        $month[11] = 'month11';
        $week[1] = 'week01date';
        $week[2] = 'week02date';
        $week[3] = 'week03date';
        $week[4] = 'week04date';
        // There are 86400 seconds in a day.
        $seven_days = 86400+86400+86400+86400+86400+86400+86400;

        // Get all of the Monday dates.
        $count = 0;
        for ($i = 1; $i <= 11; $i++) {
            for ($j = 1; $j <= 4; $j++) {
                $count++;
                $monday_dates_by_week[$count] = get_config('mod_istudy', $month[$i] . $week[$j]);
                $monday_credits_by_week[$count] = get_config('mod_istudy', $month[$i] . 'week0' . $j);
            }
        }
        $fall_start_date = get_config('mod_istudy', $month[1] . $week[1]);
        $spring_start_date = get_config('mod_istudy', 'spring_start_date');

        // Find the fall end date.
        // Get the default fall end date.
        $fall_end_date = get_config('mod_istudy', 'month05week04date');
        for ($i = 1; $i <= 11; $i++) {
            if ($monday_dates_by_week[$i] == $spring_start_date) {
                $fall_end_date = $monday_dates_by_week[$i-1];
            }
        }
        // Get the spring end date.
        // Get the default spring end date.
        $spring_end_date = get_config('mod_istudy', 'month10week04date');
        $end_date_not_found = true;
        for ($i = 44; $i <= 1; $i--) {
            if ($monday_credits_by_week[$i] > 0 AND $end_date_not_found) {
                $spring_end_date = $monday_dates_by_week[$i];
                $end_date_not_found = false;
            }
        }
		$term_dates = new \stdClass();
        $term_dates->fall_start = utils::set_to_12pm(strtotime($fall_start_date));
        $term_dates->fall_end = utils::set_to_12pm(strtotime($fall_end_date));
        $term_dates->spring_start = utils::set_to_12pm(strtotime($spring_start_date));
        $term_dates->spring_end = utils::set_to_12pm(strtotime($spring_end_date));
        $term_dates->summer_start = $term_dates->spring_end + $seven_days;
        // Add 9 weeks to get the end date.
        $term_dates->summer_end = $term_dates->summer_start + ($seven_days * 9); // Add 9 weeks to get the end date.
		return $term_dates;
    }

    // Get dates for 20 weeks according to starting date and day of the week for each week.
    public static function get_attendance_weeks($current_term, $this_day_of_week) {
        $dates_by_week = [];
        // Initialize dates list.
        for ($i = 1; $i <= 22; $i++) {
            $dates_by_week[$i] = '';
        }
        $month[1] = 'month01';
        $month[2] = 'month02';
        $month[3] = 'month03';
        $month[4] = 'month04';
        $month[5] = 'month05';
        $month[6] = 'month06';
        $month[7] = 'month07';
        $month[8] = 'month08';
        $month[9] = 'month09';
        $month[10] = 'month10';
        $month[11] = 'month11';
        $week[1] = 'week01date';
        $week[2] = 'week02date';
        $week[3] = 'week03date';
        $week[4] = 'week04date';
        // Adjust Monday so that the time is set to 12:00 pm.
        $day_of_week['1'] = 0; // Monday
        $day_of_week['2'] = 86400; // Tuesday
        $day_of_week['3'] = 86400+86400; // Wednesday
        $day_of_week['4'] = 86400+86400+86400; // Thursday
        $day_of_week['5'] = 86400+86400+86400+86400; // Friday
        // There are 86400 seconds in a day. Add 7 days to find the date next week.
        $seven_days = 86400+86400+86400+86400+86400+86400+86400;
        $this_term = utils::get_term_dates();
        // Assign the start and end dates of the term.
        $not_a_summer_term = true;
        if ($current_term == 1) {
            $term_start = $this_term->fall_start;
            $term_end = $this_term->fall_end;
        }
        elseif ($current_term == 2) {
            $term_start = $this_term->spring_start;
            $term_end = $this_term->spring_end;
        }
        // Create the list for the summer dates.
        else{
            // Add the summer dates.
            $not_a_summer_term = false;
            $this_summer_week =  $this_term->summer_start;
            for ($i = 1; $i <= 10; $i++) {
                $dates_by_week[$i] = date("m/d", $this_summer_week + $day_of_week[$this_day_of_week]);
                $this_summer_week = $this_summer_week + $seven_days;
            }
        }
        if ($not_a_summer_term) {
            $count = 0;
            // Get all of the dates for the fall term or spring term.
            for ($i = 1; $i <= 11; $i++) {
                for ($j = 1; $j <= 4; $j++) {
                    $temp_monday_date_text = get_config('mod_istudy', $month[$i] . $week[$j]);
                    $temp_monday_date = utils::set_to_12pm(strtotime($temp_monday_date_text));
                    if (($temp_monday_date >= $term_start) AND ($temp_monday_date <= $term_end)) {
                        $count++;
                        $dates_by_week[$count] = date("m/d", $temp_monday_date + $day_of_week[$this_day_of_week]);
                    }
                }
            }
        }
        return $dates_by_week;
    }

    // Get the monthly sign in sheet title.
    public static function get_title_monthly_sheets($school_advisor, $school_term, $school_day_of_week) {
        $week_day[1] = 'monday';
        $week_day[2] = 'tuesday';
        $week_day[3] = 'wednesday';
        $week_day[4] = 'thursday';
        $week_day[5] = 'friday';
        $this_term[1] = "Fall";
        $this_term[2] = "Spring";
        $this_term[3] = "Summer";
        // Page Title - School Information
        $title_info = '<h1>' . get_config('mod_istudy', 'schoolname') . '</h1><h2>' . utils::put_comma_in_name($school_advisor) . ' - ' . $this_term[$school_term] . ' Roll Sheets</h2>'.
            '<h3>' . strtoupper($week_day[$school_day_of_week]) . ' Classes' . '</h3><br>';
        // Table heading
        $title_info = $title_info . '<table><tr>' .
            '<th width="13px" class="cellbordernotright">#</th><th width="106px" class="cellbordernotleft">STUDENT NAME</th>' .
            '<th width="20px" class="cellbordernotright">GR</th><th width="106px" class="cellbordernotleft">COURSE NAME</th>' .
            '<th width="20px" class="cellbordernotright">CR</th>' .
            '<th width="100px" class="cellborderall" colspan="5"></th><th width="100px" class="cellborderall" colspan="5"></th>' .
            '<th width="100px" class="cellborderall" colspan="5"></th><th width="100px" class="cellborderall" colspan="5"></th>' .
            '<th width="40px" class="cellborderall" colspan="5"></th></tr>';
        return $title_info;
    }

    // Monthly - Get the session title - includes the period number, the start and end time, and the dates for the next 20 weeks.
    public static function get_header_monthly_sheets($myday, $school_term, $session_number, $time_session) {
        // Initialize the counting variables.
        $my_day = [];
        // Get the dates for the heading.
        $my_day = utils::get_attendance_weeks($school_term, $myday);
        // Print the title for this session as long as it's not the blank lines at the end.
        if ($session_number <> '-1') {
            $header_info = '<tr class="fontfordates"><td colspan="5" class="mycell"><center><b>PERIOD '. $session_number . '</b> (' . $time_session . ')</center></td>';
        }
        // Print the title for the blank lines.
        else {
            $header_info = '<tr class="fontfordates"><td colspan="5" class="mycell"><center><b>' . $time_session . '</b></center></td>';
        }
        for ($d = 1; $d <= 20; $d+=5) {
            $header_info = $header_info . '<td width="2%" class="leftborder_dark"><div class="rotate_date">' . $my_day[$d] . '</div></td>' .
                '<td width="2%" class="cell_width"><div class="rotate_date">' . $my_day[$d+1] . '</div></td>' .
                '<td width="2%" class="cell_width"><div class="rotate_date">' . $my_day[$d+2] . '</div></td>' .
                '<td width="2%" class="cell_width"><div class="rotate_date">'.  $my_day[$d+3] . '</div></td>' .
                '<td width="2%" class="cell_width"><div class="rotate_date">' . $my_day[$d+4] . '</div></td>';
        }
        // Add weeks 21 and 22.
        $header_info = $header_info . '<td width="2%" class="leftborder_dark"><div class="rotate_date">' . $my_day[21] . '</div></td>' .
            '<td width="2%" class="cell_width"><div class="rotate_date">' . $my_day[22] . '</div></td>';
        $header_info = $header_info . '</tr>';
        return $header_info;
    }

    // Start a new monthly page since the end of the page has been reached.
    public static function start_new_page_monthly($school_advisor, $myday, $mysession, $time_session, $school_term) {
        // Initialize the variable.
        $new_page_text = '';
        $new_page_text = $new_page_text . '</table>';
        // Add a page break - start a new page.
        $new_page_text = $new_page_text . '<P style="page-break-before: always">';
        $new_page_text = $new_page_text . utils::get_title_monthly_sheets($school_advisor, $school_term, $myday);
        $new_page_text = $new_page_text . utils::get_header_monthly_sheets($myday, $school_term, $mysession, $time_session);
        return $new_page_text;
    }

    // Print the roll sheets for one teacher.
    public static function get_monthly_roll_sheets($school_advisor, $school_term, $school_startofweek, $school_day_of_week) {
        // The total number of rows per page, this does not include the page title.
        $total_page_count = 55;
        $page_count = 0;
        $student_count = 0;
        $total_blank_rows = 2;
        $week_day[1] = 'monday';
        $week_day[2] = 'tuesday';
        $week_day[3] = 'wednesday';
        $week_day[4] = 'thursday';
        $week_day[5] = 'friday';
        $number_of_sessions = get_config('mod_istudy', 'number_of_sessions');
        // Get the times for each session.
        $summer = '';
        if ($school_term == 3) {$summer = 'summer_';}
        $time_session[][] = array() ;
        for ($d = 1; $d <= 5; $d++) {
            for ($t = 1; $t <= $number_of_sessions; $t++) {
                $time_session[$d][$t] = get_config('mod_istudy', $summer . $week_day[$d] . 'session' . $t);
            }
        }
        // Check for a daily schedule. If 'No', student schedules are different each day of the week.
        // If 'Yes', Mondays schedule is applied to each day of the week (Monday-Friday).
        if ($school_term == 1 OR $school_term == 2) {
            $dailyschedule = get_config('mod_istudy', 'dailyschedule');
        }
        else {
            $dailyschedule = get_config('mod_istudy', 'summer_dailyschedule');
        }

        // Initialize html page.
        $advisor_info = '<link rel="stylesheet" type="text/css" href="styles-roll_sheets.css"><html><head><title>Monthly Attendance</title></head><body>';

        // Get all of the students for the specified teacher.
        for ($myday = 1; $myday <= 5; $myday++) {
            // Print all sheets for Monday-Friday, if == 6 otherwise just print the day of the week that was chosen.
            // $page_count counts the number of students per session, it is reset at the start of a new session.
            $page_count = 0;
            if ($school_day_of_week == 6 OR $school_day_of_week == $myday) {
                $advisor_info = $advisor_info . utils::get_title_monthly_sheets($school_advisor, $school_term, $school_day_of_week);
                for ($mysession = 1; $mysession <= $number_of_sessions; $mysession++) {
                    $advisor_info = $advisor_info . utils::get_header_monthly_sheets($myday, $school_term, $mysession, $time_session[$myday][$mysession], $school_startofweek, $dailyschedule);
                    // The header above counts as another 2 rows on the table.
				    $page_count = $page_count + 2;
                    // Get the students names - find all students that have the teacher on the day of the week and during current session.
                    $all_students = student_info::get_students_by_session($mysession, $school_advisor, $school_term, $myday);
                    if ($all_students <> 0) {
                        $student_count = 0;
                        foreach ($all_students as $all_student) { // Check to see if this is an advisory student for teacher.
                            $my_advisory_student = '';
                            $my_advisor = utils::get_field_data($all_student->id, utils::get_fieldid('advisor'));
                            if ($my_advisor == $school_advisor) {
                                $my_advisory_student = '*';
                            }
                            // Distance learning students (100% of the time) - change color of the text and background.
                            $distance_learning_yes = '';
                            $distance_learning_only = utils::get_field_data($all_student->id, utils::get_fieldid('distancelearningonly'));
                            if ($distance_learning_only == 'Yes'){
                                $distance_learning_yes = ' style="background-color:#B9B9B9;"';
                            }
                            $student_count = $student_count + 1;
                            $page_count = $page_count +1;
                            // If we have reached the bottom of the page, start a new page.
                            if ($page_count >= $total_page_count and $student_count > 1) {
                                $advisor_info = $advisor_info . utils::start_new_page_monthly($school_advisor, $myday, $mysession, $time_session[$myday][$mysession], $school_term);
                                $page_count = 0;
                            }
                            $student_name = '<b' . $distance_learning_yes . '>' . $all_student->student_name . $my_advisory_student . '</b>';
                            $advisor_info = $advisor_info . '<tr class="cell_student"><td class="cellborderleft"><center>' . $student_count . '</center></td><td><p class="cut_text_monthly">' . $student_name . '</p></td>';
                            $advisor_info = $advisor_info . '<td><center>' . $all_student->grade . '</center></td><td><p class="cut_text_monthly"><i>' . $all_student->course_name . '</i></p></td>';
                            $advisor_info = $advisor_info . '<td><center>' . $all_student->credits . '</center></td>';
                            // Add the cells for the next 4 weeks.
                            $advisor_info = $advisor_info . '<td class="cellborderleft"></td><td></td><td></td><td></td><td></td>' .
                                '<td class="cellborderleft"></td><td></td><td></td><td></td><td></td>' .
                                '<td class="cellborderleft"></td><td></td><td></td><td></td><td></td>' .
                                '<td class="cellborderleft"></td><td></td><td></td><td></td><td class="cellborderright"></td><td></td><td></td></tr>';
                        }
                    }
                    // Since there are no student during this session, print None.
                    else {
                        $advisor_info = $advisor_info . '<tr><td colspan="25"><center><bold>NONE</bold></center></td></tr>';
                        $student_count = 0;
                    }
                }
				// Print a few blank rows to so that advisor can add new students.
                $page_count = $page_count + 3;
                if ($page_count >= $total_page_count) {
                    $advisor_info = $advisor_info . utils::start_new_page_monthly($school_advisor, $myday, -1, 'Students not Scheduled', $school_term);
                }
                else {
                    $advisor_info = $advisor_info . utils::get_header_monthly_sheets($myday, $school_term, -1, 'Students not Scheduled', $school_startofweek, $dailyschedule);
                }
                $student_count = 0;
				for ($blank_row = 1; $blank_row <= $total_blank_rows; $blank_row++) {
					$student_count = $student_count + 1;
					$advisor_info = $advisor_info . '<tr><td class="cellborderleft"><center>' . $student_count . '</center></td><td><p class="cut_text_monthly"></p></td><td></td>' .
						'<td><p class="cut_text_monthly"></p></td><td></td>' .
						'<td class="cellborderleft"></td><td></td><td></td><td></td><td></td>' .
						'<td class="cellborderleft"></td><td></td><td></td><td></td><td></td>' .
						'<td class="cellborderleft"></td><td></td><td></td><td></td><td></td>' .
						'<td class="cellborderleft"></td><td></td><td></td><td></td><td class="cellborderright"></td><td></td><td></td></tr>';
					$page_count = $page_count + 1;
					// If we have reached the bottom of the page and we are not on the last blank row, start a new page.
					if ($page_count >= $total_page_count and $blank_row < $total_blank_rows) {
						$advisor_info = $advisor_info . utils::start_new_page_monthly($school_advisor, $myday, $mysession, $time_session[$myday][$mysession], $school_term);
						$page_count = 0;
					}
				}
                $advisor_info = $advisor_info . '</table><br><h2>Date Printed: ' . date("m/d/Y") . '</h2>'	;
			}
			if (($school_day_of_week == 6) AND ($myday < 5)) {
				$advisor_info = $advisor_info . '<P style="page-break-before: always">';
			}
		}
        $advisor_info = $advisor_info . '</body></html>';
        // Print the advisor and students.
        $advisor_print_newtab = "<script>
                var newWindow = window.open('');
                newWindow.document.write('$advisor_info');
            </script>";
        echo $advisor_print_newtab;
        return;
    }

    // Create the page title.
    public static function get_page_header($school_name, $school_advisor, $school_term, $course_name) {
        $page_title = '';
        // Page Title - School Information
        $page_title = $page_title . '<h1>' . $school_name . '</h1><h2>' . utils::put_comma_in_name($school_advisor) . ' - ' .
            strtoupper($school_term) . ' Courses</h2><h3>' . $course_name . ' (' . utils::get_mycourseid($course_name) . ')</h3><br>';
        // Table heading
        $page_title = $page_title . '<table><tr>' .
            '<th width="2%" class="cellborderall">#</th><th width="10%" class="cellborderbottom">ID#</th>' .
            '<th width="26%" class="cellborderbottom">STUDENT NAME</th><th width="2%" class="cellborderall">CR</th>' .
            '<th width="15%" class="cellborderall" colspan="5"></th><th width="15%" class="cellborderall" colspan="5"></th>' .
            '<th width="15%" class="cellborderall" colspan="5"></th><th width="15%" class="cellborderall" colspan="5"></th></tr>';
        $page_title = $page_title . '<tr class="fontfordates"><td width="2%" class="cellborderleft"><center>-</center></td>' .
            '<td class="cellborderleft"></td><td></td><td class="cellborderleft"></td>' .
            '<td width="2%" class="leftborder_dark"></td><td width="2%" class="cell_width"></td><td width="2%" class="cell_width"></td>' .
            '<td width="2%" class="cell_width"></td><td width="2%" class="cell_width"></td>' .
            '<td width="2%" class="leftborder_dark"></td><td width="2%" class="cell_width"></td><td width="2%" class="cell_width"></td>' .
            '<td width="2%" class="cell_width"></td><td width="2%" class="cell_width"></td>' .
            '<td width="2%" class="leftborder_dark"></td><td width="2%" class="cell_width"></td><td width="2%" class="cell_width"></td>' .
            '<td width="2%" class="cell_width"></td><td width="2%" class="cell_width"></td>' .
            '<td width="2%" class="leftborder_dark"></td><td width="2%" class="cell_width"></td><td width="2%" class="cell_width"></td>' .
            '<td width="2%" class="cell_width"></td><td width="2%" class="cell_width"></td></tr>';
        return $page_title;
    }

    // Print the advisor roll sheet by subject.
    public static function print_students_by_subject($mystudent_list, $school_term, $school_advisor, $student_count) {
        $school_name = get_config('mod_istudy', 'schoolname');
        $blank_rows = 5;
        $empty_table_cells = '<td class="cellborderleft"></td><td></td><td></td><td></td><td></td>' .
            '<td class="cellborderleft"></td><td></td><td></td><td></td><td></td>' .
            '<td class="cellborderleft"></td><td></td><td></td><td></td><td></td>' .
            '<td class="cellborderleft"></td><td></td><td></td><td></td><td class="cellborderright"></td></tr>';
        $blank_line = '<td class="cellborderleft"></td><td></td><td class="cellborderleft"></td><td class="cellborderleft">' .
            '</td><td></td><td></td><td></td><td></td><td class="cellborderleft"></td><td></td><td></td><td></td><td></td>' .
            '<td class="cellborderleft"></td><td></td><td></td><td></td><td></td>' .
            '<td class="cellborderleft"></td><td></td><td></td><td></td><td class="cellborderright"></td></tr>';
        // Initialize html page.
        $advisor_info = '<link rel="stylesheet" type="text/css" href="styles-roll_sheets.css"><html><head><title>Students by Subject</title></head><body>';
        // Print a student list for each subject.
        $count = 0;
        $page_count = 0;
        $course_name = 'page1';
        $previous_studentid = 0;
        if ($student_count > 0) {
            foreach ($mystudent_list as $mystudent) {
                // If the student is a repeat, skip the student.
                if (!($previous_studentid == $mystudent->student_id AND $course_name == $mystudent->course_name)) {
                    $previous_studentid = $mystudent->student_id;
                    // Print the title for page 1.
                    if ($course_name == 'page1') {
                        $course_name = $mystudent->course_name;
                        $advisor_info = $advisor_info . utils::get_page_header($school_name, $school_advisor, $school_term, $course_name);
                    }
                    // If the subject has changed, start a new page.
                    else if ($course_name <> $mystudent->course_name) {
                        // Insert some blank rows and a page break as long as it's not page 1.
                        for ($i = 1; $i <= $blank_rows; $i++) {
                            $count = $count + 1;
                            $page_count = $page_count + 1;
                            $advisor_info = $advisor_info . '<tr><td class="cellborderleft"><center>' . $count . '</center></td>' . $blank_line;
                        }
                        $advisor_info = $advisor_info . '</table><br><h2>Date Printed: ' . date("m/d/Y") . '</h2>'	;
                        $advisor_info = $advisor_info . '<P style="page-break-before: always">';
                        $course_name = $mystudent->course_name;
                        $count = 0;
                        $page_count = 0;
                        $advisor_info = $advisor_info . utils::get_page_header($school_name, $school_advisor, $school_term, $course_name);
                    }
                    // If page is nearly full, start a new page for the current course.
                    else if ($page_count == 50) {
                        $page_count = 0;
                        $advisor_info = $advisor_info . '</table><br><h2>Date Printed: ' . date("m/d/Y") . '</h2>'	;
                        $advisor_info = $advisor_info . '<P style="page-break-before: always">';
                        $course_name = $mystudent->course_name;
                        $advisor_info = $advisor_info . utils::get_page_header($school_name, $school_advisor, $school_term, $course_name);
                    }
                    $count = $count + 1;
                    $page_count = $page_count + 1;
                    $student_info = '<tr class="cell_student"><td class="cellborderleft"><center>' . $count . '</center></td>' .
                        '<td class="cellborderleft"><center>' . $mystudent->school_id . '</center></td>' .
                        '<td>' . $mystudent->student_name . ' (' . $mystudent->grade . ')</td>' .
                        '<td class="cellborderleft"><center>' . $mystudent->credits . '</center></td>';
                    $advisor_info = $advisor_info . $student_info . $empty_table_cells;
                }
            }
        }
        else {
             $advisor_info = $advisor_info . utils::get_page_header($school_name, $school_advisor, $school_term, 'There are no course available');
        }
        // Insert some blank rows and a page break as long as it's not page 1.
        for ($i = 1; $i <= $blank_rows; $i++) {
            $count = $count + 1;
            $advisor_info = $advisor_info . '<tr><td class="cellborderleft"><center>' . $count . '</center></td>' . $blank_line;
        }
        $advisor_info = $advisor_info . '</table><br><h2>Date Printed: ' . date("m/d/Y") . '</h2></body></html>';
        // Print the students by subject.
        $advisor_print_newtab = "<script>
                var newWindow = window.open('');
                newWindow.document.write('$advisor_info');
            </script>";
        echo $advisor_print_newtab;
        return;
    }

    // Get all of the students for the advisor.
    public static function get_students_by_subject($school_advisor, $school_term) {
        global $DB;
        $mystudent_list[] = array();
        $count = -1;
        $week_day[1] = 'monday';
        $week_day[2] = 'tuesday';
        $week_day[3] = 'wednesday';
        $week_day[4] = 'thursday';
        $week_day[5] = 'friday';
        $number_of_sessions = get_config('mod_istudy', 'number_of_sessions');
        $this_school_term = utils::get_term($school_term);
        $advisor_name = $school_advisor;
        // Get student id for the term (Fall, Spring, or Summer) and for the year.
        for ($myday = 1; $myday <= 5; $myday++) {
            for ($mysession = 1; $mysession <= $number_of_sessions; $mysession++) {
                $name_of_session = $this_school_term . $week_day[$myday] . 'session' .  $mysession;
                $name_of_session_credits = $this_school_term . $week_day[$myday] . 'creditssession' .  $mysession;
                $course_id = utils::get_fieldid($name_of_session);  // fallmondaysession1
                $select = "SELECT * FROM {user_info_data} WHERE fieldid = " . $course_id . " AND data LIKE '%" . $advisor_name . "%'";
                $mystudents = $DB->get_records_sql($select, null, 0,0);
                // Get the data that identifies the student.
                foreach ($mystudents as $mystudent) {
                    $temp_info = student_info::get_student_info($mystudent->userid, $mystudent->data, $name_of_session_credits);
                    // Don't include students that have been deleted or suspended.
                    if (($temp_info->deleted == false) and ($temp_info->suspended == false)){
                        $count = $count + 1;
                        $mystudent_list[$count] = new \stdClass();
                        $mystudent_list[$count]->student_id = $mystudent->userid;
                        $mystudent_list[$count]->school_id = $temp_info->school_id;
                        $mystudent_list[$count]->student_name = $temp_info->student_name;
                        $mystudent_list[$count]->grade = $temp_info->grade;
                        $mystudent_list[$count]->courseid = $temp_info->courseid;
                        $mystudent_list[$count]->course_name = $temp_info->course_name;
                        $mystudent_list[$count]->credits = $temp_info->credits;
                    }
                }
            }
        }
        // Sort by student id.
        for ($i=0; $i<=$count; $i++){
            $val = $mystudent_list[$i];
            $j = $i-1;
            while($j>=0 && $mystudent_list[$j]->student_id > $val->student_id){
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
        // Sort the list by the name of the course
        for ($i=0; $i<=$count; $i++){
            $val = $mystudent_list[$i];
            $j = $i-1;
            while($j>=0 && $mystudent_list[$j]->course_name > $val->course_name){
                $mystudent_list[$j+1] = $mystudent_list[$j];
                $j--;
            }
            $mystudent_list[$j+1] = $val;
        }
        utils::print_students_by_subject($mystudent_list, $this_school_term, $advisor_name, $count);
    }

    // Get Monday's date for each week for this school year.
    public static function dates_by_week() {
        $dates_by_week = [];
        $month[1] = 'month01';
        $month[2] = 'month02';
        $month[3] = 'month03';
        $month[4] = 'month04';
        $month[5] = 'month05';
        $month[6] = 'month06';
        $month[7] = 'month07';
        $month[8] = 'month08';
        $month[9] = 'month09';
        $month[10] = 'month10';
        $month[11] = 'month11';
        $week[1] = 'week01date';
        $week[2] = 'week02date';
        $week[3] = 'week03date';
        $week[4] = 'week04date';
        $count = 0;
		// Choose the month (#1-11)
		// There are 86400 seconds in a day. Add 5 or 7 days to find the date for Friday.
        $four_days = 86400+86400+86400+86400;
        $seven_days = 86400+86400+86400+86400+86400+86400+86400;
		for ($i = 1; $i <= 11; $i++) {
            for ($j = 1; $j <= 4; $j++) {
                $temp_monday_date_text = get_config('mod_istudy', $month[$i] . $week[$j]);
                $temp_monday_date = strtotime($temp_monday_date_text);
                $temp_friday_date = $temp_monday_date + $four_days;
                $temp_friday_date_text = date("m/d/y", $temp_friday_date);
                if ($temp_monday_date > 0) {
                    // Each menu item will start with the week count.
                    $count++;
                    $week_label = 'Week ' . $count . ':  ';
                    if ($count <10) {
                        $week_label = 'Week 0' . $count . ':  ';
                    }
                    $dates_by_week[$temp_monday_date] = $week_label . $temp_monday_date_text . ' - ' . $temp_friday_date_text;
                    $last_saved_date =  $temp_monday_date;
                }
            }
        }
		for ($i = 1; $i <= 10; $i++) {
            $last_saved_date = $last_saved_date + $seven_days;
            $temp_monday_date =  $last_saved_date;
            $temp_monday_date_text = date("m/d/y", $temp_monday_date);
            $temp_friday_date = $temp_monday_date + $four_days;
            $temp_friday_date_text = date("m/d/y", $temp_friday_date);
            // Each menu item will start with the week count.
            $count++;
            $week_label = 'Week ' . $count . ':  ';
            $dates_by_week[$temp_monday_date] = $week_label . $temp_monday_date_text . ' - ' . $temp_friday_date_text;
        }
        return $dates_by_week;
    }

    // **Get Monday's date for each week for this school year.
    public static function get_default_week() {
        $month[1] = 'month01';
        $month[2] = 'month02';
        $month[3] = 'month03';
        $month[4] = 'month04';
        $month[5] = 'month05';
        $month[6] = 'month06';
        $month[7] = 'month07';
        $month[8] = 'month08';
        $month[9] = 'month09';
        $month[10] = 'month10';
        $month[11] = 'month11';
        $week[1] = 'week01date';
        $week[2] = 'week02date';
        $week[3] = 'week03date';
        $week[4] = 'week04date';
        // Find the Monday date for this week.
        $date_today = date("l");
		if ($date_today == 'Monday') {
			$monday_this_week = strtotime("today");
		}
		else {
			$monday_this_week = strtotime("last Monday");
		}
        // Adjust the default week if there are no classes this week.
        for ($i = 1; $i <= 11; $i++) {
            for ($j = 1; $j <= 4; $j++) {
                $temp_monday_date_text = get_config('mod_istudy', $month[$i] . $week[$j]);
                $temp_monday_date = strtotime($temp_monday_date_text);
                if ($temp_monday_date >= $monday_this_week) {
                    return $temp_monday_date;
                }
            }
        }
        return $monday_this_week;
    }
}