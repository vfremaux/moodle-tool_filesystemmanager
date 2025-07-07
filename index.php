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
 * Choose a repo instance to edit.
 *
 * @package    tool_filesystemmanager
 * @copyright  2015 Valery Fremaux (valery.fremaux@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../../config.php');
require_once($CFG->dirroot.'/admin/tool/filesystemmanager/forms/repos_form.php');

$url = new moodle_url('/admin/tool/filesystemmanager/index.php');

$context = context_system::instance();

require_login();
require_capability('tool/filesystemmanager:write', $context);

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_heading(get_string('pluginname', 'tool_filesystemmanager'));
$PAGE->set_pagelayout('mydashboard');

$repos = glob($CFG->dataroot.'/repository/*');

$formurl = new moodle_url('/admin/tool/filesystemmanager/view.php');
$mform = new repos_form($formurl);

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('pluginname', 'tool_filesystemmanager'));

if (empty($repos)) {
    echo $OUTPUT->notification(get_string('nofilesystemrepoavailable', 'tool_filesystemmanager'));
} else {
    $mform->display();
}

echo $OUTPUT->footer();
