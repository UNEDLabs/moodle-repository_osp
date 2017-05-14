<?php
// This file is part of the Moodle repository plugin "OSP"
//
// OSP is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// OSP is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.
//
// OSP has been developed by:
// - Ruben Heradio: rheradio@issi.uned.es
// - Luis de la Torre: ldelatorre@dia.uned.es
//
// at the Universidad Nacional de Educacion a Distancia, Madrid, Spain.

/**
 * This plugin is used to access EJS applications from the OSP collection in ComPADRE.
 *
 * @package    repository_osp
 * @subpackage osp
 * @copyright  2013 Luis de la Torre and Ruben Heradio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/repository/lib.php');
require_once(dirname(__FILE__) . '/osp.php');

/**
 * This is a class used to browse EJS simulations from the OSP collection.
 *
 * @package    repository_osp
 * @copyright  2013 Luis de la Torre and Ruben Heradio
 * @author     Luis de la Torre and Ruben Heradio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class repository_osp extends repository {

    /**
     * repository_osp constructor.
     *
     * @param int $repositoryid
     * @param bool|int|stdClass $context
     * @param array $options
     */
    public function __construct($repositoryid, $context = SYSCONTEXTID, $options = array()) {
        global $SESSION;
        parent::__construct($repositoryid, $context, $options);
        $this->keywords = optional_param('osp_keyword', '', PARAM_RAW);
        if (empty($this->keywords)) {
            $this->keywords = optional_param('s', '', PARAM_RAW);
        }
        $keyword = 'osp_'.$this->id.'_keyword';
        if (empty($this->keywords) && optional_param('page', '', PARAM_RAW)) {
            // This is the request of another page for the last search, retrieve the cached keywords.
            if (isset($SESSION->{$keyword})) {
                $this->keywords = $SESSION->{$keyword};
            }
        } else if (!empty($this->keywords)) {
            // Save the search keywords in the session so we can retrieve it later.
            $SESSION->{$keyword} = $this->keywords;
        }
    }

    /**
     * Get the list of ejss simulations in the osp repository.
     *
     * @param string $path
     * @param string $page
     * @return array list
     */
    public function get_listing($path = '', $page = '') {
        $client = new osp;
        $list = array();
        $list['page'] = (int)$page;
        if ($list['page'] < 1) {
            $list['page'] = 1;
        }
        $list['manage'] = 'http://www.compadre.org/osp/';
        $list['help'] = $CFG->dirroot . '/repository/osp/help/help.htm';
        $list['list'] = $client->search_simulations($client->format_keywords($this->keywords), $list['page'] - 1);
        $list['nologin'] = true;
        $list['norefresh'] = false;
        if (!empty($list['list'])) {
            $list['pages'] = -1; // Means we don't know exactly how many pages there are but we can always jump to the next page.
        } else if ($list['page'] > 1) {
            $list['pages'] = $list['page']; // No images available on this page, this is the last page.
        } else {
            $list['pages'] = 0; // No paging.
        }
        return $list;
    }

    /**
     * If this plugin supports global search, this function returns true.
     * Search function will be called when global searching is working
     *
     * @return bool
     */
    public function global_search() {
        return false;
    }

    /**
     * Searches for EjsS simulations in the OSP repository.
     *
     * @param string $searchtext
     * @param string $page
     * @return array
     */
    public function search($searchtext, $page = '') {
        global $CFG;

        $client = new osp;
        $list = array();
        $list['page'] = (int)$page;
        if ($list['page'] < 1) {
            $list['page'] = 1;
        }
        if ($searchtext == '' && !empty($this->keywords)) {
            $searchtext = $this->keywords;
        }
        $keywords = $client->format_keywords($searchtext);
        $list['list'] = $client->search_simulations($keywords, $list['page'] - 1);
        $list['manage'] = 'http://www.compadre.org/osp/';
        $list['help'] = $CFG->wwwroot . '/repository/osp/help/help.htm';
        $list['nologin'] = true;
        $list['norefresh'] = false;
        if (!empty($list['list'])) {
            $list['pages'] = -1; // Means we don't know exactly how many pages there are but we can always jump to the next page.
        } else if ($list['page'] > 1) {
            $list['pages'] = $list['page']; // No images available on this page, this is the last page.
        } else {
            $list['pages'] = 0; // No paging.
        }
        return $list;
    }

    /**
     * Specifies the types that can be returned
     *
     * @return int
     */
    public function supported_returntypes() {
        return (FILE_INTERNAL);
    }

    /**
     * EJSApp OSP plugin supports .jar and .zip files
     *
     * @return array
     */
    public function supported_filetypes() {
        return array('application/java-archive', 'application/zip');
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