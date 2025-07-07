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
 * selects a repo
 *
 * @package   tool_filesystemmanager
 * @category  tool
 * @copyright 2023 Valery Fremaux (valery.fremaux@gmail.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

/**
 * Class system_files_form
 * @copyright 2023 Valery Fremaux <valery.fremaux@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class repos_form extends moodleform {

    public function __construct($url) {
        parent::__construct($url);
    }

    /**
     * Add elements to this form.
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;

        $repos = glob($CFG->dataroot.'/repository/*');
        $reposoptions = [];
        foreach($repos as $repo) {
            $repodir = basename($repo);
            if (preg_match('/^\./', $repodir)) {
                continue;
            }
            $reposoptions[$repodir] = $repodir;
        }

        $mform->addElement('hidden', 'sesskey', sesskey());

        $mform->addElement('select', 'repo', get_string('repo', 'tool_filesystemmanager'), $reposoptions);

        $this->add_action_buttons(true, get_string('chooserepo', 'tool_filesystemmanager'));
    }

}
