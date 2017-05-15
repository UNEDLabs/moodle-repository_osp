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
 * This plugin is used to access EJS applications from the OSP collection in compadre
 *
 * @package    repository_osp
 * @copyright  2013 Luis de la Torre and Ruben Heradio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('LIST_ALL_SIMULATIONS_URL', 'http://www.compadre.org/osp/services/REST/osp_moodle.cfm?');

define('SEARCH_URL', 'http://www.compadre.org/osp/services/REST/search_v1_02.cfm?verb=Search&');

define('OSP_THUMBS_PER_PAGE', 10);

defined('MOODLE_INTERNAL') || die();

/**
 * Class osp
 *
 * @package    repository_osp
 * @copyright  2013 Luis de la Torre and Ruben Heradio
 * @author     Luis de la Torre and Ruben Heradio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class osp {

    /**
     * @var array
     */
    private $javawords = array('java', 'jar', 'ejs');
    /**
     * @var array
     */
    private $jswords = array('javascript', 'js', 'zip', 'ejss');
    /**
     * @var bool
     */
    private $javakeywords = false;
    /**
     * @var bool
     */
    private $jskeywords = false;

    /**
     * Load the xml file served by the OSP repository
     *
     * @param string $url
     * @param mixed $choice
     * @return stdClass $xml
     */
    public function load_xml_file($url, $choice) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $xml = simplexml_load_string(curl_exec($ch));
        curl_close($ch);
        if ($choice == LIST_ALL_SIMULATIONS_URL) {
            return $xml->Results->records;
        } else { // Choice == SEARCH_URL.
            return $xml->Search->records;
        }
    }

    /**
     * Process a record with information of n simulations
     *
     * @param stdClass $record
     * @return array $result
     */
    public function process_record($record) {
        // Remark: a record may include 0...n simulations.

        $result = array();
        $arrayrecord = (array) $record;

        // Information common to all the simulations of the record.
        $result['common_information'] = array();

        // Authors.
        $seeker = (array) $record->contributors;
        $author = $seeker['contributor'];
        if (is_array($author)) {
            $author = implode(', ', $author);
        }
        $result['common_information']['author'] = $author;// . $description;

        // Date.
        $date = $arrayrecord['oai-datestamp'];
        $date = preg_replace('/Z/', '', $date);
        $result['common_information']['date'] = strtotime($date);

        // Thumbnail.
        $result['common_information']['thumbnail'] = (string) $record->{'thumbnail-url'};

        // License.
        $result['common_information']['license'] = 'cc-sa';

        // Information specific for each simulation of the record.

        $result['simulations'] = array();
        $simulation = array();
        $seeker = $record->{'attached-document'};
        foreach ($seeker as $value) {
            if (is_object($value)) {
                $filename = (string) $value->{'file-name'};
                $filetype = (string) $value->{'file-type'};
                $title = (string) $value->{'title'};
                $extension = pathinfo($filename, PATHINFO_EXTENSION);

                if (((($extension == 'jar') && $this->javakeywords && preg_match('/^ejs_/i', $filename)) ||
                        (($extension == 'zip') && $this->jskeywords && preg_match('/^ejss_/i', $filename)))
                    && ($filetype == 'Main')
                    && ($title != 'Easy Java Simulations Modeling and Authoring Tool')) {

                    // Filename title.
                    $simulation['title'] = $filename;

                    // Source.
                    $source = (string) $value->{'access-url'};
                    $simulation['source'] = $source . '&EJSMoodleApp=1';

                    // Shorttitle.
                    $description = (string) $record->{'description'};
                    $description = preg_replace('/<.+?>/', '', $description);
                    $simulation['shorttitle'] = '`' . $filename . '´: ' . $description;

                    // Size.
                    $seekeraux = (array) $value->{'file-name'};
                    $seekeraux = $seekeraux['@attributes'];
                    $size = $seekeraux['file-size'];
                    $simulation['size'] = $size;

                    $result['simulations'][] = $simulation;
                }
            }
        } //End of foreach.

        return $result;
    } // End of function process_record.


    /**
     * Keywords for searching EjsS simulations
     *
     * @param string $keywords
     * @return mixed|string $keywords
     */
    public function format_keywords($keywords) {
        // Let's see if java/javascript simulations have to be filtered...

        // Java?
        $this->javakeywords = false;
        $i = 0;
        $size = count($this->javawords);
        while ((!($this->javakeywords)) && ($i < $size)) {
            if ( preg_match('/\b' . $this->javawords[$i] . '\b/i', $keywords) ) {
                $this->javakeywords = true;
                foreach ($this->javawords as $javaword) {
                    $keywords = preg_replace('/\b' . $javaword . '\b/i', '', $keywords);
                }
            } else {
                $i++;
            }
        }

        // Javascript?
        $this->jskeywords = false;
        $i = 0;
        $size = count($this->jswords);
        while ((!($this->jskeywords)) && ($i < $size)) {
            if ( preg_match('/\b' . $this->jswords[$i] . '\b/i', $keywords) ) {
                $this->jskeywords = true;
                foreach ($this->jswords as $jsword) {
                    $keywords = preg_replace('/\b' . $jsword . '\b/i', '', $keywords);
                }
            } else {
                $i++;
            }
        }

        // By default, no filter is used.
        if (!( $this->javakeywords) && !($this->jskeywords)) {
            $this->javakeywords = true;
            $this->jskeywords = true;
        }

        $keywords = trim($keywords);
        if (($keywords == '') || ($keywords == 'Search') || (strtoupper($keywords) == 'ALL')) {
            $keywords = '*';
        } else {
            // Making possible conjunctive boolean searches (a&b&...).
            $keywords = preg_replace('/\s+/', '+', $keywords);
        }

        return $keywords;
    } // End of function format_keywords.

    /**
     * Searches simulations using the specified keywords
     *
     * @param string $keywords
     * @param int $page
     * @return array $filelist
     */
    public function search_simulations($keywords, $page) {

        // Get the type of the simulation to be retrieved.
        $type = null;
        if ($this->javakeywords && $this->jskeywords) {
            $type = 'EJS+EJSS';
        } else if ($this->javakeywords && !$this->jskeywords) {
            $type = 'EJS';
        } else {
            $type = 'EJSS';
        }

        // Get skip OSP parameter from $page.
        $skip = OSP_THUMBS_PER_PAGE * ($page);

        // Get records from compadre that fulfill the keywords.
        if ($keywords == '*') { // List all simulations.
            $records = $this->load_xml_file(LIST_ALL_SIMULATIONS_URL . 'skip=' . $skip .
                '&OSPType=' . $type .
                '&max=' . OSP_THUMBS_PER_PAGE, LIST_ALL_SIMULATIONS_URL);
        } else { // Search with a keyword.
            $records = $this->load_xml_file(SEARCH_URL . 'Skip=' . $skip .
                '&OSPType=' . $type .
                '&Max=' .
                OSP_THUMBS_PER_PAGE .'&q=' . $keywords, SEARCH_URL);
        }

        $filelist = array();
        if (isset($records->record)) {
            foreach ($records->record as $record) {
                $processedrecord = $this->process_record($record);
                if (!empty($processedrecord['simulations'])) {
                    foreach ($processedrecord['simulations'] as $simulation) {
                        $item = array();
                        $item['author'] = $processedrecord['common_information']['author'];
                        $item['date'] = $processedrecord['common_information']['date'];
                        $item['thumbnail'] = $processedrecord['common_information']['thumbnail'];
                        $item['license'] = $processedrecord['common_information']['license'];
                        $item['title'] = $simulation['title'];
                        $item['source'] = $simulation['source'];
                        $item['shorttitle'] = $simulation['shorttitle'];
                        $item['size'] = $simulation['size'];
                        $filelist[] = $item;
                    }
                }
            }
        }

        return $filelist;
    } // End of function search_simulations.

} // End of class osp.