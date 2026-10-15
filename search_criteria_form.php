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
 * The main istudyrollsheets configuration form
 *
 * It uses the standard core Moodle formslib. For more info about them, please
 * visit: http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * @package    mod_istudyrollsheets
 * @copyright  2024 Jesus Ramirez <jram1993@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/lib/formslib.php');

/**
 * Module instance settings form
 *
 * @package    mod_istudyrollsheets
 * @copyright  2024 Jesus Ramirez <jram1993@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_istudyrollsheets_search_criteria_form extends moodleform
{

    public function definition()
	{
		$mform = $this->_form;
		$mform->addElement('html', '<link rel="stylesheet" type="text/css" href="styles-search_criteria_form.css">');
		$mform->addElement('html', '<table><tr><th colspan="4"><h3>Teacher Roll Sheets</h3>');
		$mform->addElement('html', '<h5>Refine Your Search</h5></th></tr>');

		// Choose to print a)sign-in sheets or b)monthly attendance form or c) print by subject.
		$mform->addElement('html', '<tr><td class="mycell2">Choose the form to print</td><td class="mycell3">');
		$mform->addElement('radio', 'whichform', '','Sign-in Sheets', 1);
		$mform->addElement('html', '</td><td class="mycell3">');
		$mform->addElement('radio', 'whichform', '','Monthly Attendance', 2);
		$mform->addElement('html', '</td><td class="mycell3">');
		$mform->addElement('radio', 'whichform', '','Print by Subject', 3);

		$mform->setDefault('whichform', 1);
		$mform->setType('whichform', PARAM_INT);
		$mform->addElement('html', '</td></tr></table><br>');

		// OPTIONS 1, 2, AND 3: Choose the Advisory Teacher.
		$advisors = mod_istudyrollsheets\teacher_info::get_advisors();
		$default_advisor = mod_istudyrollsheets\teacher_info::get_default_advisor($advisors);
		$select = $mform->addElement('select', 'advisor', '<b><u>CHOOSE THE ADVISOR</u></b>', $advisors);
		// Set default to first advisor on the list.
		$select->setSelected($default_advisor);

		// OPTIONS 2 AND 3: Choose the term that you are printing from.
		$defaultterm_set = get_config('mod_istudy','schoolterm');
		if ($defaultterm_set == 'Fall') { $defaultterm = 1; }
		else if ($defaultterm_set == 'Spring') { $defaultterm = 2; }
		else { $defaultterm = 3; };

		$options = [
         1 => 'Fall',
         2 => 'Spring',
         3 => 'Summer'
		];

		$select = $mform->addElement('select', 'schoolterm', '<b><u>CHOOSE THE CURRENT SCHOOL TERM</u></b>', $options);
		$select->setSelected($defaultterm);
		// Disable term when SIGN-IN SHEETS is chosen (equals 1).
		$mform->hideIf('schoolterm', 'whichform', 'eq', 1);

		// OPTION 1: Choose the week.
		// Get the current day of the week (Monday-Friday).
		$my_date_today = date("l");
		switch ($my_date_today) {
			case "Monday":
				$date_today = 1;
				break;
			case "Tuesday":
				$date_today = 2;
				break;
			case "Wednesday":
				$date_today = 3;
				break;
			case "Thursday":
				$date_today = 4;
				break;
			case "Friday":
				$date_today = 5;
				break;
			default:
				$date_today = 1;
		}
		// If it is Friday after 1:00 pm, adjust the default week and day to next Monday.
		$default_week = mod_istudyrollsheets\utils::get_default_week();
		if (($date_today == 5) AND (date("H") > 13)) {
			$seven_days = 86400*7;
			$default_week = $default_week + $seven_days;
			$date_today = 1;
		}
		$weeks_for_menu = mod_istudyrollsheets\utils::dates_by_week();
		$select = $mform->addElement('select', 'schoolstartofweek', '<b><u>CHOOSE THE WEEK</u></b>', $weeks_for_menu);
		// Set default to first advisor on the list.
		$select->setSelected($default_week);

		// Disable date when MONTYLY ATTENDANCE or PRINT BY SUBJECT is chosen (equals 3).
		$mform->hideIf('schoolstartofweek', 'whichform', 'neq', 1);

		// OPTIONS 1 AND 2: Print by day of the week (ALL, Monday, Tuesday, Wednesday, Thursday, or Friday).
		$mform->addElement('html', '<table class="table1"><tr><td class="cell_noborder" valign="top" colspan="3"><b><u>');
		$mform->addElement('static', 'weekday_title', '', '<b><u>CHOOSE THE DAY OF THE WEEK</u></b>');
		$mform->addElement('html', '</u></b></td></tr><tr><td class="cell_noborder">');
		$mform->addElement('radio', 'day_of_week', '','ALL', 6, ['class' => 'radiobuttons-lastname-all']);
		$mform->addElement('html', '<td class="cell_noborder">');
		$mform->addElement('radio', 'day_of_week', '','Monday', 1, ['class' => 'radiobuttons-lastname']);
		$mform->addElement('html', '</td><td class="cell_noborder">');
		$mform->addElement('radio', 'day_of_week', '','Tuesday', 2, ['class' => 'radiobuttons-lastname']);
		$mform->addElement('html', '</td></tr><tr><td class="cell_noborder">');
		$mform->addElement('radio', 'day_of_week', '','Wednesday', 3, ['class' => 'radiobuttons-lastname']);
		$mform->addElement('html', '</td><td class="cell_noborder">');
		$mform->addElement('radio', 'day_of_week', '','Thursday', 4, ['class' => 'radiobuttons-lastname']);
		$mform->addElement('html', '</td><td class="cell_noborder">');
		$mform->addElement('radio', 'day_of_week', '','Friday', 5, ['class' => 'radiobuttons-lastname']);
		$mform->addElement('html', '</td></tr></table>');
		// Set default to the current day of the week.
		$mform->setDefault('day_of_week', $date_today);
		$mform->setType('day_of_week', PARAM_INT);
		// If PRINT BY THE DAY OF THE WEEK is not chosen, disable day_of_week.
		$mform->hideIf('day_of_week', 'whichform', 'eq', 3);
		$mform->hideIf('weekday_title', 'whichform', 'eq', 3);

		$mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
		$mform->addElement('html', '<br><h3>  </h3>');

		// Add standard buttons, common to all modules.
		$mform->addElement('html', '<div class="buttonsetup">');
			$this->add_action_buttons(true, 'Submit');
		$mform->addElement('html', '</div></html>');
    }
}