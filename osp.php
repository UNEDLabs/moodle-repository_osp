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

define('OSP_URL', 'http://www.compadre.org/osp/services/REST/search_v1_02.cfm?verb=Search&OSPType=EJS+Model&');
define('OSP_THUMBS_PER_PAGE', 10);

class osp {

    function load_xml_file($url) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $xml = simplexml_load_string(curl_exec($ch));
        curl_close($ch);
        return $xml->Search->records;
    } //load_xml_file

    function process_record($record){
        /////////////////////////////////////////////////////
        // Remark: a record may include 0..n simulations
        /////////////////////////////////////////////////////

        $result = array();
        $record_as_array = (array) $record;

        // <information common to all the simulations of the record>

        $result['common_information'] = array();
        
        // author
        $seeker = (array) $record->contributors;
        $author = $seeker['contributor'];
        if (is_array($author)) {
            $author = implode(', ', $author);
        }
        $result['common_information']['author'] = $author;// . $description;

        // date
        $date = $record_as_array['oai-datestamp'];
        $date = preg_replace('/Z/', '', $date);
        $result['common_information']['date'] = strtotime($date);

        // thumbnail
        $result['common_information']['thumbnail'] = (string) $record->{'thumbnail-url'};

        // license
        $result['common_information']['license'] = 'cc-sa';

        // <\information common to all the simulations of the record>
        

        // <information specific for each simulation of the record>

        $result['simulations'] = array();
        $simulation = array();

        $seeker = (array) $record_as_array['attached-document'];
        foreach ($seeker as $key => $value) {
            $filename = (string) $value->{'file-name'};
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            if ( ($extension == 'jar') or ($extension == 'js') ) {

                // filename title
                $simulation['title'] = $filename;

                // source
                $source = (string) $value->{'access-url'};
                $simulation['source'] = $source . '&EJSMoodleApp=1';

                // shorttitle
                $description = (string) $record->{'description'};
                $description = preg_replace('/<.+?>/', '', $description);
                $simulation['shorttitle'] = '`' . $filename . '´: ' . $description;

                // size
                $seeker_aux = (array) $value->{'file-name'};
                $seeker_aux = $seeker_aux['@attributes'];
                $size = $seeker_aux['file-size'];
                $simulation['size'] = $size;

                $result['simulations'][] = $simulation;
            }
        } //foreach

        // <\information specific for each simulation of the record>

        return $result;
    } // process_record

    public function format_keywords($keywords) {
        $keywords=trim($keywords);
        if ( ($keywords == '') || ($keywords == 'Search') || (strtoupper($keywords) == 'ALL') ){
           $keywords = '*';
        } else {
            // making possible conjunctive boolean searches (a&b&...)
            $keywords=preg_replace('/\s+/', '+', $keywords);
        }
        return $keywords;
    } //format_keywords

    public function search_simulations($keywords, $page) {
        // get Skip OSP parameter from $page
        $skip = OSP_THUMBS_PER_PAGE * ($page);

        // get records from compadre that fulfill the keywords
        $records= $this->load_xml_file(OSP_URL . 'Skip=' . $skip . '&Max=' .
            OSP_THUMBS_PER_PAGE .'&q=' . $keywords);

        $file_list = array();
        if (isset($records->record)) {
            foreach($records->record as $record){
                $processed_record = $this->process_record($record);
                if (!empty($processed_record['simulations'])) {
                    foreach ($processed_record['simulations'] as $simulation) {
                        $list_item = array();
                        $list_item['author'] = $processed_record['common_information']['author'];
                        $list_item['date'] =  $processed_record['common_information']['date'];
                        $list_item['thumbnail'] =  $processed_record['common_information']['thumbnail'];
                        $list_item['license'] =  $processed_record['common_information']['license'];
                        $list_item['title'] =  $simulation['title'];
                        $list_item['source'] =  $simulation['source'];
                        $list_item['shorttitle'] =  $simulation['shorttitle'];
                        $list_item['size'] =  $simulation['size'];
                        $file_list[] = $list_item;
                    }
                }
            }
        }

        return $file_list;
    } //search_simulations

} //class osp
