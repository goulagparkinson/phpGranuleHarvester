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

error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('GMT');

$bin_name = "phpGranuleHarvester";
$bin_version = "0.1alpha";
$now_time = time();
$tree_command = 'tree -X -s -D --dirsfirst --du --timefmt "%Y-%m-%d %H:%M:%S"';

$config_array = array(
  'start_time'       => $now_time,
  'output_base_path' => sys_get_temp_dir(),
  'output_dirname'   => 'pgh_'.strftime("%F_%Hh%Mm%Ss", $now_time),
  'verbose'          => false,
  'debug'            => false,
  'cache_path'            => "pgh_cache.sqlite"
);

require 'lib/cli/cli.php';
\cli\register_autoload();
require 'lib/pgh/pgh.php';
\pgh\register_autoload();
set_error_handler("\pgh\\error_handler");
require_once("productDateTimeHandler.inc.php");
require_once("functions.inc.php");

$input_directory_array = array();

$arguments = new \cli\Arguments(array('strict' => true));

$arguments->addFlag(array('verbose', 'v'), 'Turn on verbose output');
$arguments->addFlag(array('debug'), 'Turn on debug output');
$arguments->addFlag('version', 'Display the version');
$arguments->addFlag(array('quiet', 'q'), 'Disable all output');
$arguments->addFlag(array('help', 'h'), 'Show this help screen');

$arguments->addOption(array('d'), array(
	'description' => 'Set the input directory to browse'));
$arguments->addOption(array('o'), array(
	'default'     => $config_array['output_base_path'],
	'description' => 'Set the output base directory to store results'));
$arguments->addOption(array('n'), array(
	'default'     => $config_array['output_dirname'],
	'description' => 'Set the output directory name to store results'));
$arguments->addOption(array('c'), array(
	'default'     => $config_array['cache_path'],
	'description' => 'Set the sqlite db cache path'));

try {
  $arguments->parse();
} catch (cli\arguments\InvalidArguments $e) {
  trigger_error($e->getMessage(), E_USER_ERROR);
  exit(1);
}

if ($arguments['debug']) $config_array['debug'] = true;
if ($arguments['verbose']) $config_array['verbose'] = true;
if ($arguments['quiet']) {
  error_reporting(E_ERROR | E_USER_ERROR);
  $config_array['debug'] = false;
  $config_array['verbose'] = false;
}

if ($arguments['help']) {
  \cli\line($arguments->getHelpScreen()."\n\n");
  exit(0);
}
if ($arguments['version']){
  \pgh\version();
  exit(0);
}

/*******************************************************************************
 * Input directory checking
 * ****************************************************************************/

if (!$arguments['d']){
  trigger_error("You must provide at least one input directory to as -d option", E_USER_ERROR);
  exit(1);
}

$string_array = explode(' ', $arguments['d']);
foreach ($string_array as $dirname) {
  $realpath = realpath($dirname);
  if (!is_dir($realpath)) {
    trigger_error("Input directory \"$dirname\" is not a real directory path", E_USER_WARNING);
    continue;
  }
  if (!is_readable($realpath)) {
    trigger_error("Input directory \"$dirname\" is not readable", E_USER_WARNING);
    continue;
  }
  //TODO : Verify that a dir is not a sub dir of a already present dir !
  array_push($input_directory_array,$realpath);
}

if (!count($input_directory_array)) {
  trigger_error("You must provide at least one input directory (real and readable) as -d option", E_USER_ERROR);
  exit(1);
}

/*******************************************************************************
 * Output checking
 * ****************************************************************************/

if ($arguments['n']){
  $config_array['output_dirname'] = $arguments['n'];
}
if ($arguments['o']){
  if (!is_dir($arguments['o'])) {
    trigger_error("Output base directory is not a real directory", E_USER_ERROR);
    exit(1);
  }
  if (is_dir($arguments['o']."/".$config_array['output_dirname'])) {
    trigger_error("Output directory already exist", E_USER_WARNING);
  } else {
    if (!mkdir($arguments['o']."/".$config_array['output_dirname'])) {
      trigger_error("Unable to create the output directory in this base directory", E_USER_ERROR);
      exit(1);
    } else {
      rmdir($arguments['o'].'/'.$config_array['output_dirname']);
    }
  }
  $config_array['output_base_path'] = $arguments['o'];
}
$config_array['output_dirpath'] = $config_array['output_base_path'].'/'.$config_array['output_dirname'];

\pgh\info("config['output_dirpath'] = ".$config_array['output_dirpath']);

/*******************************************************************************
 * Ini processing
 * ****************************************************************************/

$ini_array = parse_ini_file("phpGranuleHarvester.ini", true);

if (!array_key_exists('regexToIgnore',$ini_array)) {
  trigger_error("Unable to find 'regexToIgnore' key in the .ini file", E_USER_ERROR);
  exit(1);
}
if (!array_key_exists('products',$ini_array) || !is_array($ini_array['products'])) {
  trigger_error("Unable to find 'products' array key in the .ini file", E_USER_ERROR);
  exit(1);
}
$config_array['regex_ignore'] = $ini_array['regexToIgnore'];
$config_array['regex_product'] = array();
$config_array['regex_datetime'] = array();

foreach($ini_array['products'] as $product_id) {
  if (array_key_exists($product_id, $ini_array)) {
    if (array_key_exists('productRegex', $ini_array[$product_id])) {
      $product_regex = $ini_array[$product_id]['productRegex'];
      $config_array['regex_product'][$product_regex] = $product_id;
      if (array_key_exists('datetimeRegex', $ini_array[$product_id])) {
        $config_array['regex_datetime'][$product_id] =
          $ini_array[$product_id]['datetimeRegex'];
      }
    } else {
      trigger_error("Unable to find key 'productRegex' in [$product_id] section in the .ini file", E_USER_WARNING);
      continue;
    }
  } else {
    trigger_error("Unable to find [$product_id] section in the .ini file", E_USER_WARNING);
    continue;
  }
}
if (!count($config_array['regex_product'])) {
  trigger_error("Regex list is empty, unable to browse to match product", E_USER_ERROR);
  exit(1);
}

\pgh\info("config_array = ".print_r($config_array, true));


/*******************************************************************************
 * Cache checking
 * ****************************************************************************/

if ($arguments['c']){
  $config_array['cache_path'] = $arguments['c'];
}

if (!is_file($config_array['cache_path'])) {
  $cache = \pgh\Cache::create($config_array['cache_path']);
  if (!$cache) {
    trigger_error("Unable to create the new cache ".$config_array['cache_path'],
      E_USER_ERROR);
    exit(1);
  }
}

$cache = \pgh\Cache::connect($config_array['cache_path']);
if (!$cache) {
  trigger_error("Unable to connect the cache ".$config_array['cache_path'],
      E_USER_ERROR);
  exit(1);
}

/*******************************************************************************
 * Tree processing
 * ****************************************************************************/

$directory_array = array();
$file_array = array();

foreach($input_directory_array as $input_dirname) {
  trigger_error("Tree processing for \"$input_dirname\"", E_USER_NOTICE);
  $tree_output_tmpfilename = tempnam(sys_get_temp_dir(), 'pgh_xml_');
  $command=$tree_command." ".$input_dirname." > ".$tree_output_tmpfilename;
  \pgh\info("Unix tree command used is :\n\t".$command);
  $exec_output = array();
  $exec_return_val = 0;
  exec($command, $output, $return_val);
  $tree_xml = new SimpleXMLElement(file_get_contents($tree_output_tmpfilename));
  $base_path = dirname((string)$tree_xml->directory['name']);
  foreach ($tree_xml->directory as $simple_xml_element_dir) {
    \pgh\recurse_xml($simple_xml_element_dir,
		$base_path,
		$directory_array,
		$file_array);
  }
  unset($tree_xml);
  unlink($tree_output_tmpfilename);
}

foreach($file_array as &$file) {
  if ($file->checkForIgnoreRegex($config_array['regex_ignore'])) {
    trigger_error("Ignoring ".$file->path, E_USER_NOTICE);
    continue;
  }
  if ($file->checkForProductRegex($config_array['regex_product'])) {
    if ($file->extractMetadata()) {
      \pgh\success("Extract ".$file->getProductId()." for ".$file->path);
    } else {
      trigger_error("Matching ok but extract error in ".$file->path, E_USER_WARNING);
    }
  } else {
    trigger_error("Unmatching ".$file->path, E_USER_WARNING);
  }
}

$cache->update($file_array);

exit(0);
?>
