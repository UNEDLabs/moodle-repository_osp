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
 * This plugin is used to access EJS applications from the OSP collection in compadre
 *
 * @package    repository
 * @subpackage osp
 * @copyright  2013 Luis de la Torre and Ruben Heradio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . '/repository/lib.php');
require_once(dirname(__FILE__) . '/osp.php');


/**
 * repository_osp class
 * This is a class used to browse EJS simulations from the OSP collection
 */
class repository_osp extends repository {
    public function __construct($repositoryid, $context = SYSCONTEXTID, $options = array()) {
        global $SESSION;
        parent::__construct($repositoryid, $context, $options);
        $this->keywords = optional_param('osp_keyword', '', PARAM_RAW);
        if (empty($this->keywords)) {
            $this->keywords = optional_param('s', '', PARAM_RAW);
        }
        $sess_keyword = 'osp_'.$this->id.'_keyword';
        if (empty($this->keywords) && optional_param('page', '', PARAM_RAW)) {
            // This is the request of another page for the last search, retrieve the cached keywords
            if (isset($SESSION->{$sess_keyword})) {
                $this->keywords = $SESSION->{$sess_keyword};
            }
        } else if (!empty($this->keywords)) {
            // save the search keywords in the session so we can retrieve it later
            $SESSION->{$sess_keyword} = $this->keywords;
        }
    }

    public function get_listing($path = '', $page = '') {
    $client = new osp;
        $list = array();
        $list['page'] = (int)$page;
        if ($list['page'] < 1) {
            $list['page'] = 1;
        }
        $list['help'] = 'http://www.compadre.org/osp';
        $list['list'] = $client->search_simulations($client->format_keywords($this->keywords), $list['page'] - 1);
        $list['nologin'] = true;
        $list['norefresh'] = true;
        if ( !empty($list['list']) ) {
            $list['pages'] = -1; // means we don't know exactly how many pages there are but we can always jump to the next page
        } else if ($list['page'] > 1) {
            $list['pages'] = $list['page']; // no images available on this page, this is the last page
        } else {
            $list['pages'] = 0; // no paging
        }
        return $list;
    } // get_listing

    // Search
    // If this plugin supports global search, this function returns true.
    // Search function will be called when global searching is working
    public function global_search() {
        return false;
    }
    public function search($search_text, $page = '') {
        $client = new osp;
        $list = array();
        $list['page'] = (int)$page;
        if ($list['page'] < 1) {
            $list['page'] = 1;
        }
        if ($search_text == '' && !empty($this->keywords)) {
            $search_text = $this->keywords;
        }
        $keywords = $client->format_keywords($search_text);
        $list['list'] = $client->search_simulations($keywords, $list['page'] - 1);
        $list['help'] = 'http://www.compadre.org/osp/search/search.cfm?qc=Compiled+Simulation&q='.$keywords;
        $list['nologin'] = true;
        $list['norefresh'] = true;
        if ( !empty($list['list']) ) {
            $list['pages'] = -1; // means we don't know exactly how many pages there are but we can always jump to the next page
        } else if ($list['page'] > 1) {
            $list['pages'] = $list['page']; // no images available on this page, this is the last page
        } else {
            $list['pages'] = 0; // no paging
        }
        return $list;
    } //search

    public function supported_returntypes() {
        return (FILE_INTERNAL);
    }

    /**
     * EJSApp OSP plugin supports .jar and .zip files
     *
     * @return array
     */
    public function supported_filetypes() {
        return array('application/java-archive'); //,'application/zip');
    }

    /**
     * Return the source information
     *
     * @param stdClass $url
     * @return string|null
     */
    public function get_file_source_info($url) {
        return $url;
    }

    /**
     * Is this repository accessing private data?
     *
     * @return bool
     */
    public function contains_private_data() {
        return false;
    }
}