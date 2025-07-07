<?php

namespace tool\filesystemmanager;

use StdClass;
use context_user;

class manager {

    static $level = 0;

    /**
     * Captures repo files into a temporary storage area of the current user for altering content.
     * @param string $repo the top level directory of the repo
     * @param object $user the requiring user as context
     * @param string $relpath the relative path for recursion.
     */
    static public function capture_repo_files($repo, $user, $relpath = '') {
        global $CFG;
        static $level;

        // Check directory exists.
        $repodir = $CFG->dataroot.'/repository/'.$repo.$relpath;
        if (!is_dir($repodir)) {
            return false;
        }

        if ($relpath == '') {
            // First level only.
            $lockfile = $repodir.'/lock.txt';
            if (file_exists($lockfile)) {
                $fileinfo = stat($lockfile);
                if ($fileinfo['timecreated'] < (time() - HOURSEC * 12)) {
                    // Probably too old undeleted lock. discard it an continue.
                    unlink($lockfile);
                } else {
                    return false;
                }
            }

            $readlockfile = $repodir.'/lock.txt';
            if ($f = fopen($readlockfile, 'w')) {
                fputs($f, time());
                fclose($f);
            } else {
                // Something wrong in sync input dir. Notify admin.
                if ($interactive) {
                    mtrace('Could not create readlock file. Possible severe issue in storage, or read only situation. Resuming sync input capture.');
                } else {
                    email_to_user(get_admin(), get_admin(), $SITE->shortname." : Could not create readlock file.",
                    'Possible local sync process issue.');
                }
                return false;
            }
        }

        $d = opendir($repodir);

        $fs = get_file_storage();

        $count = 0;
        while ($entry = readdir($d)) {
            if (preg_match('/^\./', $entry)) {
                continue;
            }

            // Ignore dirs.
            if (is_dir($repodir.'/'.$entry)) {
                self::$level++;
                $count += self::capture_repo_files($repo, $user, $relpath.'/'.$entry);
                self::$level--;
                continue;
            }

            // Forget any locking file.
            if (preg_match('/^lock/', $entry)) {
                continue;
            }

            // Forget any file starting with '_' (could be an output file).
            if (preg_match('/^_/', $entry)) {
                continue;
            }

            $filerec = new StdClass();
            $filerec->contextid = context_user::instance($user->id)->id;
            $filerec->component = 'user';
            $filerec->filearea = 'systemfilestemp';
            $filerec->itemid = 0;
            $filerec->filepath = (!empty($relpath)) ? '/'.$relpath . '/' : '/';
            $filerec->filename = $entry;

            // Delete previous version and avoid file collision.
            if ($oldfile = $fs->get_file($filerec->contextid, $filerec->component, $filerec->filearea, $filerec->itemid,
                    $filerec->filepath, $filerec->filename)) {
                $oldfile->delete();
            }

            $fs->create_file_from_pathname($filerec, $repodir.'/'.$entry);
            $count++;
            // unlink($repodir.'/'.$entry);
        }

        closedir($d);

        if ($relpath == '') {
            unlink($readlockfile);
        }
        return $count;
    }

    /**
     * Rebuild the repository content from user temp filearea. this will first empty the whole repo
     * before rebuilding it from moodle filestore. (No file_storage funciton for this).
     * @param string $repo the repo (top dir under dataroot /directory subpath). Repo is constant during the recursion.
     * @param object $user the requiring user as context
     * @param string $relpath relpath from repo root.
     */
    static public function release_repo_files($repo, $user) {
        global $CFG;

        $fs = get_file_storage();

        // Check directory exists.
        $repodir = $CFG->dataroot.'/repository/'.$repo;
        if (!is_dir($repodir)) {
            return false;
        }

        // Empty the repo.
        self::clear($repo, '');

        $context = context_user::instance($user->id);
        $component = 'user';
        $filearea = 'systemfilestemp';
        $itemid = 0;

        // Scan the user's temp file area and rebuild files and dirs. Only need files.
        $allfiles = $fs->get_area_files($context->id, $component, $filearea, $itemid, 'filepath, filename', false);

        if (!empty($allfiles)) {
            foreach ($allfiles as $afile) {
                $realpath = $repodir.$afile->get_filepath().$afile->get_filename();
                $dirname = dirname($realpath);
                if (!is_dir($dirname)) {
                    mkdir($dirname, 0775, true);
                }
                $afile->copy_content_to($realpath);
            }
        }
    }

    /*
     * Recursively remove all files in the repos subdirs.
     * @param string $repo the repo (top dir under dataroot /directory subpath). Repo is constant during the recursion.
     * @param string $relpath relpath from repo root.
     */
    static public function clear($repo, $relpath = '') {
        global $CFG;

        $repodir = $CFG->dataroot.'/repository/'.$repo.$relpath;
        if (!is_dir($repodir)) {
            return false;
        }

        $d = opendir($repodir);

        while ($entry = readdir($d)) {
            if ($entry == '.' || $entry == '..') {
                // Let pseudo dirs pass.
                continue;
            }

            if (preg_match('/^(_|lock)/', $entry)) {
                // Protect some files.
                continue;
            }

            // Recurse down to deepest. Del the directory when returning from dig.
            if (is_dir($repodir.'/'.$entry)) {
                self::clear($repo, $relpath.'/'.$entry);
                rmdir($repodir.'/'.$entry);
                continue;
            }

            unlink($repodir.'/'.$entry);
        }

        closedir($d);
    }

    /**
     * Is the repo empty or not there ? 
     */
    static public function is_repo_empty($repo) {
        global $CFG;

        $repodir = $CFG->dataroot.'/repository/'.$repo;
        if (!is_dir($repodir)) {
            return true;
        }

        $d = opendir($repodir);

        $count = 0;
        while ($entry = readdir($d)) {
            if (preg_match('/^\./', $entry)) {
                continue;
            }
            $count++;
        }

        closedir($d);

        return $count === 0;
    }
}