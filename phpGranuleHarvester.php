#!/usr/bin/php
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

require_once("functions.inc.php");

if (php_sapi_name() != 'cli') die('[FATAL] Must run from command line !');
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);
ini_set('log_errors', 0);
ini_set('html_errors', 0);
date_default_timezone_set('GMT');

$now_time = time();
$output_basedir = sys_get_temp_dir();
$output_dirname = 'pgh_'.strftime("%F_%T", $now_time);
$tree_command = 'tree -X -s -D --dirsfirst --du --timefmt "%Y-%m-%d %H:%M:%S"';

$options_array = array(
  'output_basedir' => $output_basedir,
  'output_dirname' => $output_dirname,
  'input_dir_array' => array(),
  'tree_command' => $tree_command,
  'now_time' => $now_time
);

$config_array = parse_ini_file("phpGranuleHarvester.ini", TRUE);
$compress_suffix_array = explode(',', $config_array['compressSuffix']);
$compress_suffix_regex = '';
foreach($compress_suffix_array as $value) {
  $compress_suffix_regex.=(!empty($compress_suffix_regex)?"|":"");
  $suffix = strtolower($value);
  $compress_suffix_regex.=$suffix."|".strtoupper($suffix);
}
$compress_suffix_regex = "($|\.(".$compress_suffix_regex.")$)";
$regex_ignore = $config_array['regexToIgnore'];
$regex_array = array();
foreach($config_array['products'] as $key => $product_id) {
  $product_regex = $config_array[$product_id]['productRegex'].$compress_suffix_regex;
  $regex_array[$product_regex] = $product_id;
}

$shortopts  = "d:o:n:";
$longopts  = array("debug::");
$options = getopt($shortopts, $longopts);

if (!array_key_exists('d',$options)) {
  echo "[CRITICAL] You must provide at least one input directory to browse as -d option !\n";
  exit(1);
} else {
  if (!is_array($options['d'])) $options['d'] = array($options['d']);
  foreach($options['d'] as $key => $value) {
    $input_dirname = realpath($value);
    if (!is_dir($input_dirname)) {
      echo "[ERROR] Input directory $input_dirname is not a real directory path\n";
      continue;
    }
    if (!is_readable($input_dirname)) {
      echo "[ERROR] Unable to read input directory $input_dirname\n";
      continue;
    }
    //TODO : Verify that a dir is not a sub dir of a already present dir !
    array_push($options_array['input_dir_array'],$input_dirname);
  }
}
$options_array['input_dir_array'] = array_unique($options_array['input_dir_array']);

$directory_array = array();
$file_array = array();
$file_count = 0;

foreach($options_array['input_dir_array'] as $input_dirname) {
  $tree_output_tmpfilename = tempnam(sys_get_temp_dir(), 'pgh_xml_');
  $tree_command.=" ".$input_dirname." > ".$tree_output_tmpfilename;
  //echo "[DEBUG] Unix tree command used is :\n$tree_command\n";
  $exec_output = array();
  $exec_return_val = 0;
  exec($tree_command, $output, $return_val);
  $tree_xml = new SimpleXMLElement(file_get_contents($tree_output_tmpfilename));
  $base_path = dirname((string)$tree_xml->directory['name']);
  foreach ($tree_xml->directory as $simple_xml_element_dir) {
    $file_count+= recurse_xml($simple_xml_element_dir,
		$base_path,
		$directory_array,
		$file_array);
  }
  unset($tree_xml);
  unlink($tree_output_tmpfilename);
}

echo "[DEBUG]        file_count:$file_count\n";
echo "[DEBUG] count(file_array):".count($file_array)."\n";

$unmatched_filename = 'unmatched.txt';
if (!$output_unmatched_handle = fopen($unmatched_filename, 'w')) {
  echo "[ERROR] Unable to open output file ($unmatched_filename)\n";
  exit(1);
}

foreach($file_array as $path => &$file_info_array) {
  if (preg_match("/".$regex_ignore."/",$file_info_array['name'], $matches))
    continue;
  $product_matched = false;
  foreach($regex_array as $regex => $product_id) {
    if (preg_match("/".$regex."/",$file_info_array['name'], $matches)) {
      $product_matched = true;
      //echo "[DEBUG] ".$file_info_array['name']." => ".$product_id."\n";
      $file_info_array['product_id_regex_matching'] = $product_id;
      break;
    }
  }
  if (!$product_matched) {
    if (fwrite($output_unmatched_handle, $file_info_array['path']."\n") === FALSE) {
      echo "[ERROR] Unable to write into file ($unmatched_filename)\n";
    }
    echo "[WARNING] ".$file_info_array['name']." => UNKNOW\n";    
  }
}
fclose($output_unmatched_handle);

//print_r($file_array);

//print_r($directory_array);

exit(0);
?>
