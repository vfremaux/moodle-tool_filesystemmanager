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
 * Unit tests for tool_filesystemmanager.
 *
 * @package    tool_filesystemmanager
 * @copyright  2023 Valery Fremaux
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/' . $CFG->admin . '/tool/filesystemmanager/classes/manager.php');

/**
 * Health lib testcase.
 *
 * @package    tool_filesystemmanager
 * @copyright  2023 Valery Fremaux
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class filesystemmanager_testcase extends advanced_testcase {

    /**
     * Test moving files from the test repo to $USER context forth and back.
     * @covers tool\filesystemmanager\manager
     */
    public function test_move_files() {
        global $CFG;

        $this->resetAfterTest(true);
        $manager = new tool\filesystemmanager\manager();

        // Assert nothing is on the way. 
        $this->assertTrue($manager->is_repo_empty('samplerepo'));

        // Put sample repo in place.
        $testsamples = $CFG->dirroot.'/admin/tool/filesystemmanager/tests/samplerepo';
        $repolocation = $CFG->dataroot.'/repository';
        if (!is_dir($repolocation)) {
            mkdir($repolocation, 0777);
        }

        $cmd = "cp -r $testsamples $repolocation";
        exec($cmd, $output, $return);

        $deepestsamplefile = $CFG->dataroot.'/repository/samplerepo/subdir2/subdir21/file211.txt';
        $this->assertFileExists($deepestsamplefile);

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $usercontext = context_user::instance($user->id);

        // Test capture.
        $result = $manager->capture_repo_files('samplerepo', $user, '');

        $fs = get_file_storage();
        $areaempty = $fs->is_area_empty($usercontext->id, 'user', 'systemfilestemp', 0);
        $this->assertFalse($areaempty);

        $files = $fs->get_area_files($usercontext->id, 'user', 'systemfilestemp', 0, 'filepath, filename', false);
        $this->assertCount(6, $files);

        // Testing clear()
        $manager->clear('samplerepo');
        $this->assertTrue($manager->is_repo_empty('samplerepo'));

        // Test releasing files.
        $manager->release_repo_files('samplerepo', $user);

        $samplefile = $CFG->dataroot.'/repository/samplerepo/file1.txt';
        $this->assertFileExists($samplefile);

        $deepestsamplefile = $CFG->dataroot.'/repository/samplerepo/subdir2/subdir21/file211.txt';
        $this->assertFileExists($deepestsamplefile);

        $f = implode("\n", file($deepestsamplefile));
        $this->assertStringContainsString('This is file 211 content', $f);

        // Leave things as they were initially.
        $manager->clear('samplerepo');
        rmdir($CFG->dataroot.'/repository/samplerepo');
    }

}
