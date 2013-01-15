<?php
/*******************************************************************************

    This file is part of "phpGranuleHarvester" - Copyright 2013 Goulag PARKINSON
    Author(s) : Goulag PARKINSON <goulag.parkinson@gmail.com>

    "phpGranuleHarvester" is free software: you can redistribute it and/or
    modify it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    any later version.

    "phpGranuleHarvester" is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License along with
    "phpGranuleHarvester".  If not, see <http://www.gnu.org/licenses/>.

*******************************************************************************/

function recurse_xml(&$simple_xml_element_dir, &$parent_dirpath,
                     &$directory_array, &$file_array) {
  $total_dirsize = 0;
  $total_filesize = 0;

  $dirname = basename((string)$simple_xml_element_dir['name']);
  $dirpath = $parent_dirpath.'/'.$dirname;
  $du_command = "du -sb ".$dirpath." | cut -f1";
  
/*
  echo "[DEBUG] BEGIN:function ".__FUNCTION__."()\n";
  echo "[DEBUG] \tdirpath:$dirpath\n";
  echo "[DEBUG] \tdirname:$dirname\n";
*/
  
  $exec_output = array();
  $exec_return_val = 0;
  $dirsize = exec($du_command, $output, $return_val);
    
  $directory_array[$dirpath] = array(
    'name' => $dirname,
    'sha1_name' => sha1($dirname),
    'path' => $dirpath,
    'sha1_path' => sha1($dirpath),
    'size' => $dirsize
  );

  if (isset($simple_xml_element_dir['time'])) {
    $directory_array[$dirpath]['last_modification_datetime'] =
      (string)$simple_xml_element_dir['time'];
  };

  /*****************************************************************************
   * STEP 1 : Start by files in this directory
   * **************************************************************************/
  $file_counter = 0;
  foreach ($simple_xml_element_dir->file as $simple_xml_element_file) {
    $filename = (string)$simple_xml_element_file['name'];
    $filepath = $dirpath."/".$filename;
    $filesize = (string)$simple_xml_element_file['size'];
    $filedatetime = (string)$simple_xml_element_file['time'];
    $file_array[$filepath] = array(
      'name' => $filename,
      'sha1_name' => sha1($filename),
      'path' => $filepath,
      'sha1_path' => sha1($filepath),
      'size' => $filesize,
      'last_modification_datetime' => $filedatetime);
    $file_counter++;
  }
  
  /*****************************************************************************
   * STEP 2 : Recurse by browsing sub directory
   * **************************************************************************/
  $sub_dir_counter = 0;
  foreach ($simple_xml_element_dir->directory as $simple_xml_element_child_dir) {
    $file_counter+=recurse_xml($simple_xml_element_child_dir,$dirpath, $directory_array, $file_array);
    $sub_dir_counter++;
  }
  //echo "[DEBUG]   END:function ".__FUNCTION__."()\n";
  $directory_array[$dirpath]['file_count'] = $file_counter;
  
  return $file_counter;
}

function detect_duplicate(&$file_array) {
  $index_file_array = array();
  $duplicate_array = array();
  foreach($file_array as $path => &$file_info) {
    if (!isset($file_info['product_id_regex_matching']))
      continue;
    $filename = $file_info['name'];
    $product_id = $file_info['product_id_regex_matching'];
    $filekey = $product_id.":".$filename;
    if (!array_key_exists($filekey, $index_file_array)) {
      $index_file_array[$filekey] = array();
      $index_file_array[$filekey] = $file_info;
    } else {
      if (!array_key_exists($filekey, $duplicate_array)) {
        $duplicate_array[$filekey][] = $index_file_array[$filekey];
      }
      $duplicate_array[$filekey][] = $file_info;
    }    
  }
  return $duplicate_array;
}

function gunzip_dataset($input_filename, &$output_filename) {
    $output_filename = tempnam(sys_get_temp_dir(), 'pgh_gunzip_');
    $command = "zcat ".$input_filename." > ".$output_filename;
    $exec_output = array();
    $exec_return_val = 0;
    exec($command, $output, $return_val);
    return true;
}

function ncdump_xml($input_filename, &$output_filename) {
    $output_filename = tempnam(sys_get_temp_dir(), 'pgh_ncdump_');
    $command = "ncdump -htx ".$input_filename." > ".$output_filename;
    $exec_output = array();
    $exec_return_val = 0;
    exec($command, $output, $return_val);
    return true;
}
?>
