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
 * Backup files management tool.
 *
 * @package    tool_filesystemmanager
 * @copyright  2015 Valery Fremaux (valery.fremaux@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../../config.php');
require_once($CFG->dirroot.'/admin/tool/filesystemmanager/forms/files_form.php');
require_once($CFG->dirroot.'/admin/tool/filesystemmanager/classes/manager.php');

$repo = required_param('repo', PARAM_PATH);

$url = new moodle_url('/admin/tool/filesystemmanager/view.php');
$returnurl = new moodle_url('/admin/search.php');

$context = context_system::instance();

require_login();
require_capability('tool/filesystemmanager:write', $context);

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_heading(get_string('pluginname', 'tool_filesystemmanager'));
$PAGE->set_pagelayout('mydashboard');

// Move all files into a user area.
$filesystemmanager = new tool\filesystemmanager\manager();

$usercontext = context_user::instance($USER->id);
$filesystemmanager->capture_repo_files($repo, $USER);
$data = new stdClass();
$data->returnurl = $returnurl;

$options = array('subdirs' => 1, 'maxbytes' => -1, 'maxfiles' => -1, 'accepted_types' => '*', 'areamaxbytes' => -1);
file_prepare_standard_filemanager($data, 'files', $options, $usercontext, 'user', 'systemfilestemp', 0);

$mform = new system_files_form(null, array('data' => $data, 'options' => $options));

if ($mform->is_cancelled()) {
    // Give back files to repo.
    $filesystemmanager->release_repo_files($repo, $USER);
    redirect($returnurl);
} else if ($formdata = $mform->get_data()) {
    // Standard area reception.
    $formdata = file_postupdate_standard_filemanager($formdata, 'files', $options, $usercontext, 'user', 'systemfilestemp', 0);
    // Move files to repo.
    $filesystemmanager->release_repo_files($repo, $USER);

    redirect($returnurl);
}

echo $OUTPUT->header();
echo $OUTPUT->box_start('generalbox');
$formdata = new StdClass;
$formdata->repo = $repo;
$mform->set_data($formdata);
$mform->display();
echo $OUTPUT->box_end();
echo $OUTPUT->footer();