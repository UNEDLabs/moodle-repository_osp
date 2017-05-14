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
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.
//
// OSP has been developed by:
// - Ruben Heradio: rheradio@issi.uned.es
// - Luis de la Torre: ldelatorre@dia.uned.es
//
// at the Universidad Nacional de Educacion a Distancia, Madrid, Spain.

/**
 * Installation file for the osp repository.
 *
 * @package    repository_osp
 * @copyright  2013 Luis de la Torre and Ruben Heradio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Create a default instance of the osp repository
 *
 * @return bool A status indicating success or failure
 */
function xmldb_repository_osp_install() {
    global $CFG;
    $result = true;
    require_once($CFG->dirroot.'/repository/lib.php');
    $osp = new repository_type('osp', array(), true);
    if (!$id = $osp->create(true)) {
        $result = false;
    }
    return $result;
}