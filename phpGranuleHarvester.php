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
require_once("colors.class.php");
require_once("productDateTimeHandler.inc.php");

if (php_sapi_name() != 'cli') die('[FATAL] Must run from command line !');
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 0);
ini_set('html_errors', 0);
date_default_timezone_set('GMT');
set_error_handler("error_handler");

$now_time = time();
$now_str = strftime("%F_%Hh%Mm%Ss", $now_time);
$output_basedir = sys_get_temp_dir();
$output_dirname = 'pgh_'.strftime("%F_%T", $now_time);
$tree_command = 'tree -X -s -D --dirsfirst --du --timefmt "%Y-%m-%d %H:%M:%S"';
$is_piped = (function_exists('posix_isatty') && !posix_isatty(STDOUT));

$option_debug=FALSE;
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
$regex_product_array = array();
$regex_datetime_array = array();
foreach($config_array['products'] as $key => $product_id) {
  $product_regex = $config_array[$product_id]['productRegex'];
  $regex_product_array[$product_regex] = $product_id;
  if (array_key_exists('datetimeRegex',$config_array[$product_id])) {
    $datetime_regex = $config_array[$product_id]['datetimeRegex'];
    $regex_datetime_array[$product_id] = $datetime_regex;
  }
}

$shortopts  = "d:o:n:";
$longopts  = array("debug::");
$options = getopt($shortopts, $longopts);
if (array_key_exists('debug',$options)) {
  $option_debug = TRUE;
}

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

if (array_key_exists('n',$options)) {
  $options_array['output_dirname'] = $options['n'];
}

if (array_key_exists('o',$options)) {
  if (!is_dir($options['o'])) {
    trigger_error("Output base directory is not a writable directory", E_USER_ERROR);
    exit(1);
  }
  if (is_dir($options['o']."/".$options_array['output_dirname'])) {
    trigger_error("Output directory already exist ", E_USER_WARNING);
  } else {
    if (!mkdir($options['o']."/".$options_array['output_dirname'])) {
      trigger_error("Unable to create the output directory in this base directory", E_USER_ERROR);
      exit(1);
    }
    rmdir($options_array['output_basedir'].'/'.$options_array['output_dirname']);
  }
  $options_array['output_basedir'] = $options['o'];
}

$options_array['output_dirpath'] = $options_array['output_basedir'].'/'.$options_array['output_dirname'];


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

debug("[DEBUG]        file_count:$file_count\n");
debug("[DEBUG] count(file_array):".count($file_array)."\n");


$unmatched_regex_filename = $options_array['output_dirpath'].'/unmatched_regex_filename_'.$now_str.'.txt';
if (!$unmatched_regex_handle = fopen($unmatched_regex_filename, 'w+')) {
  trigger_error("Unable to open output file ($unmatched_regex_filename)", E_USER_ERROR);
  exit(1);
}

foreach($file_array as $path => &$file_info_array) {
  if (preg_match("/".$regex_ignore."/",$file_info_array['name'], $matches))
    continue;
  $product_matched = false;
  foreach($regex_product_array as $regex => $product_id) {
    if (preg_match("/".$regex."/",$file_info_array['name'], $matches)) {
      $product_matched = true;
      //echo "[DEBUG] ".$file_info_array['name']." => ".$product_id."\n";
      $file_info_array['product_id_regex_matching'] = $product_id;
      break;
    }
  }
  if (!$product_matched) {
    if (fwrite($unmatched_regex_handle, $file_info_array['path']."\n") === FALSE) {
      trigger_error("Unable to write into output file $unmatched_regex_filename",E_USER_ERROR);
    }
  }
}
fclose($unmatched_regex_handle);

$duplicate_array = detect_duplicate_byname($file_array);
if (count($duplicate_array)) {
  echo "[INFO] There's some duplicate files :\n\n";
  foreach($duplicate_array as $filekey => &$fileset_array) {
    echo "$filekey :\n";
    foreach($fileset_array as $file_info) {
      echo "\t=> ".$file_info['path']."\n";
    }
  }
}
$product_handle_array = array();
$product_stats = array('match_count' => 0,
      'match_size' => 0, 'match_min_datetime' => 9999,
      'match_max_datetime' => 0, 'match_avg_filesize' => 0,
      'unmatch_count' =>0, 'product' => array());

foreach($file_array as $path => &$file_info) {

  if (!isset($file_info['product_id_regex_matching'])) {
    trigger_error("No product_id detected for this file $path", E_USER_WARNING);
    continue;
  }
  $product_id = $file_info['product_id_regex_matching'];
  $filename = $file_info['name'];
  $filepath = $file_info['path'];
  $filesize = $file_info['size'];
  if (!array_key_exists($product_id, $product_stats['product'])) {
    $product_stats['product'][$product_id] = array('match_count' => 0,
      'match_size' => 0, 'match_min_datetime' => 9999,
      'match_max_datetime' => 0, 'match_avg_filesize' => 0,
      'unmatch_count' =>0);
  }

  if ($filesize==0) {
    trigger_error("Filesize null for $filepath (not indexed)", E_USER_ERROR);
    $product_stats['product'][$product_id]['unmatch_count']++;
    continue;
  }
  debug("[DEBUG] New file to index :\n");
  debug("\tname: $filename\n");
  debug("\tpath: $filepath\n");
  debug("\tproduct_id: $product_id\n");
  $functionHandlerName = "datetime_handler_".str_replace(array('-','.'),"_",$product_id);
  //$file_info['sha1_file'] = sha1_file($filepath);
  $functionHandlerName($file_info); // function invocation by string (aka. call_user_func)
  debug("\tstart_datetime: ".$file_info['start_datetime']."\n");
  debug("\tstop_datetime : ".$file_info['stop_datetime']."\n");
  debug("\n");
  $product_stats['product'][$product_id]['match_count']++;
  $product_stats['product'][$product_id]['match_size']+=$filesize;
  $product_stats['product'][$product_id]['match_min_datetime']=
    min($product_stats['product'][$product_id]['match_min_datetime'],
    $file_info['start_datetime']);
  $product_stats['product'][$product_id]['match_max_datetime']=
    max($product_stats['product'][$product_id]['match_max_datetime'],
    $file_info['stop_datetime']);
  
  if (!array_key_exists($product_id,$product_handle_array)) {
    $product_output_filename = $options_array['output_dirpath'].'/product_matched_'.$product_id.'_'.$now_str.'.txt';
    if (!$handle = fopen($product_output_filename, 'w')) {
      trigger_error("Unable to open output file ($product_output_filename)", E_USER_WARNING);
    } else {
      $product_handle_array[$product_id] = $handle;
    }
  }
  if (fwrite($product_handle_array[$product_id], json_encode($file_info)."\n") === FALSE) {
      trigger_error("Unable to write into output matched product file ",E_USER_ERROR);
  }
  

  //trigger_success("From ".$file_info['start_datetime']." To ".$file_info['stop_datetime']);
}

foreach($product_handle_array as $product_id => $handle) {
  fclose($handle);
}
unset($product_handle_array);

/***********************************************************************
 * Building stats                                                      *
 **********************************************************************/
 
$output_stats = "
;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
Output stats for last phpGranuleHarvester execution.
Datetime is : ".strftime("%F %T", $now_time)."
;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;

Product(s) : ".count($product_stats['product'])."\n\n";

$product_counter = 0;
foreach($product_stats['product'] as $key => &$value) {
  $product_counter++;
  $value['match_avg_size'] = round($value['match_size'] / $value['match_count']);
  $output_stats.="Product ".$product_counter."/".count($product_stats['product'])." => ".$key."\n";
  $output_stats.="\t"."Total number of file(s) : ".$value['match_count']."\n";
  $output_stats.="\t"."Total size of file(s)   : ".formatBytes($value['match_size'])."\n";
  $output_stats.="\t"."Average size of file    : ".formatBytes($value['match_avg_size'])."\n";
  $output_stats.="\t"."First seen datetime     : ".$value['match_min_datetime']."\n";
  $output_stats.="\t"."Last  seen datetime     : ".$value['match_max_datetime']."\n";
  $output_stats.="\n";
  $product_stats['match_count']+=$value['match_count'];
  $product_stats['match_size']+=$value['match_size'];
  $product_stats['match_min_datetime']=
    min($product_stats['match_min_datetime'],
    $value['match_min_datetime']);
  $product_stats['match_max_datetime']=
    max($product_stats['match_min_datetime'],
    $value['match_max_datetime']);
}

$product_stats['match_avg_size'] = round($product_stats['match_size'] / $product_stats['match_count']);

$output_stats.="\n;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
Total file count     : ".$product_stats['match_count']."
Total file size      : ".formatBytes($product_stats['match_size'])."
Average size of file : ".formatBytes($product_stats['match_avg_size'])."
First seen datetime  : ".$value['match_min_datetime']."
Last seen datetime   : ".$value['match_max_datetime']."
;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;\n";

$stat_filename = $options_array['output_dirpath'].'/summary_'.$now_str.'.txt';
if (!$stat_handle = fopen($stat_filename, 'w')) {
  trigger_error("Unable to open output file ($stat_filename)", E_USER_ERROR);
  exit(1);
}

if (fwrite($stat_handle, $output_stats) === FALSE) {
  trigger_error("Unable to write into output file ($stat_filename)", E_USER_ERROR);
  exit(1);
}
fclose($stat_handle);

//print_r($file_array);


exit(0);
?>
