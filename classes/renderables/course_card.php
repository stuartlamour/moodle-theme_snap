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
 * Course card renderable
 * @author    gthomas2
 * @copyright Copyright (c) 2016 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_snap\renderables;

use theme_snap\services\course;
use theme_snap\local;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/coursecatlib.php');

class course_card implements \renderable {

    /**
     * @var stdClass $course
     */
    private $course;

    /**
     * @var course_cards service
     */
    private $service;

    /**
     * @var int $courseid
     */
    public $courseid;

    /**
     * @var str $fullname
     */
    public $fullname;

    /**
     * @var str $shortname
     */
    public $shortname;

    /**
     * @var bool
     */
    public $favorited;

    /**
     * @var array
     */
    public $visibleavatars;

    /**
     * @var array
     */
    public $hiddenavatars;

    /**
     * @var int
     */
    public $hiddenavatarcount;

    /**
     * @var bool
     */
    public $showextralink = false;

    /**
     * @var string
     */
    public $url;

    /**
     * @var string
     */
    public $imagecss;

    /**
     * @var bool
     */
    public $published = true;

    /**
     * @param int $courseid
     */
    public function __construct($courseid) {
        $this->courseid = $courseid;
        $this->service = course::service();
        $this->apply_properties();
    }

    /**
     * Set props.
     */
    private function apply_properties() {
        global $DB;
        $this->course = $DB->get_record('course', ['id' => $this->courseid]);
        $this->url = new \moodle_url('/course/view.php', ['id' => $this->course->id]) . '';
        $this->shortname = $this->course->shortname;
        $this->fullname = $this->course->fullname;
        $this->published = (bool)$this->course->visible;
        $this->favorited = $this->service->favorited($this->courseid);
        $this->apply_contact_avatars();
        $this->apply_image_css();
    }

    /**
     * Set image css for course card (cover image, etc).
     */
    private function apply_image_css() {
        $bgcolor = local::get_course_color($this->courseid);
        $this->imagecss = "background-color: #$bgcolor;";
        $bgimage = local::course_coverimage_url($this->courseid);
        if (!empty($bgimage)) {
            $this->imagecss .= "background-image: url($bgimage);";
        }
    }

    /**
     * Set course contact avatars;
     */
    private function apply_contact_avatars() {
        global $DB, $OUTPUT;
        $clist = new \course_in_list($this->course);
        $teachers = $clist->get_course_contacts();
        $avatars = [];
        $blankavatars = [];

        if (!empty($teachers)) {
            foreach ($teachers as $teacher) {
                $teacherids[] = $teacher['user']->id;
            }
            $teacherusers = $DB->get_records_list('user', 'id', $teacherids);

            foreach ($teachers as $teacher) {
                if (!isset($teacherusers[$teacher['user']->id])) {
                    continue;
                }
                $teacheruser = $teacherusers [$teacher['user']->id];
                $userpicture = new \user_picture($teacheruser);
                $userpicture->link = false;
                $userpicture->size = 100;
                $teacherpicture = $OUTPUT->render($userpicture);

                if (stripos($teacherpicture, 'defaultuserpic') === false) {
                    $avatars[] = $teacherpicture;
                } else {
                    $blankavatars[] = $teacherpicture;
                }
            }
        }

        if (!empty($avatars + $blankavatars)) {
            // Let's put the interesting avatars first!
            $avatars = array_merge($avatars, $blankavatars);
            // Limit visible to 4.
            if (count($avatars) > 5) {
                // Show 4 avatars and link to show more.
                $visibleavatars = array_slice($avatars, 0, 4);
                $hiddenavatars = array_slice($avatars, 4);
                $this->showextralink = true;
            } else {
                $visibleavatars = $avatars;
                $hiddenavatars = [];
            }
            $this->visibleavatars = $visibleavatars;
            $this->hiddenavatars = $hiddenavatars;
        }

        $this->hiddenavatarcount = count($this->hiddenavatars);
    }
}