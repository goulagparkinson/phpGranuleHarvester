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

error_reporting(E_ERROR | E_USER_ERROR);
ini_set('display_errors', 1);
date_default_timezone_set('GMT');

$bin_name = "phpGranuleHarvester";
$bin_version = "0.1alpha";
$now_time = time();
$tree_command = 'tree -X -s -D --dirsfirst --du --timefmt "%Y-%m-%d %H:%M:%S"';
$stdout_piped = function_exists('posix_isatty') && !posix_isatty(STDOUT);
$stderr_piped = function_exists('posix_isatty') && !posix_isatty(STDERR);
$ini_filepath = "phpGranuleHarvester.ini";
$db_store_id = 1;

$config_array = array(
  'start_time'       => $now_time,
  'output_dirpath'   => tempnam(sys_get_temp_dir(), 'pgh_'),
  'verbose'          => false,
  'debug'            => false,
  'ini_filepath'     => $ini_filepath,
  'db_store_id'      => $db_store_id,
  'cache_path'       => "cache.sqlite3"
);
unlink($config_array['output_dirpath']);

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
	'default'     => $config_array['output_dirpath'],
	'description' => 'Set the output directory path to store results'));
$arguments->addOption(array('c'), array(
	'default'     => $config_array['cache_path'],
	'description' => 'Set the sqlite db cache path'));
$arguments->addOption(array('s'), array(
	'default'     => $config_array['db_store_id'],
	'description' => 'Set the dbstore_id as defined in  db cache path'));

try {
  $arguments->parse();
} catch (cli\arguments\InvalidArguments $e) {
  trigger_error($e->getMessage(), E_USER_ERROR);
  exit(1);
}

if ($arguments['debug']) {
  $config_array['debug'] = true;
  error_reporting(E_ALL);
}
if ($arguments['verbose']) {
  $config_array['verbose'] = true;
}
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

if ($arguments['o']){
  if (is_dir($arguments['o'])) {
    trigger_error("Output directory already exist", E_USER_WARNING);
  } else {
    if (!mkdir($arguments['o'])) {
      trigger_error("Unable to create the output directory", E_USER_ERROR);
      exit(1);
    } else {
      rmdir($arguments['o']);
    }
  }
  $config_array['output_dirpath'] = $arguments['o'];
}


/*******************************************************************************
 * Ini processing
 * ****************************************************************************/

$ini_array = parse_ini_file($config_array['ini_filepath'], true);

if (!array_key_exists('general',$ini_array)) {
  trigger_error("Unable to find 'global' key in the .ini file", E_USER_ERROR);
  exit(1);
}

if (!array_key_exists('regexToIgnore',$ini_array['general'])) {
  trigger_error("Unable to find 'general.regexToIgnore' key in the .ini file", E_USER_ERROR);
  exit(1);
}
if (!array_key_exists('productArray',$ini_array['general']) || !is_array($ini_array['general']['productArray'])) {
  trigger_error("Unable to find 'general.productArray' array key in the .ini file", E_USER_ERROR);
  exit(1);
}
if (!array_key_exists('dbstoreArray',$ini_array['general']) || !is_array($ini_array['general']['dbstoreArray'])) {
  trigger_error("Unable to find 'general.dbstoreArray' array key in the .ini file", E_USER_ERROR);
  exit(1);
}

if (!array_key_exists($config_array['db_store_id'],$ini_array['general']['dbstoreArray']) ||
    !is_array($ini_array["dbstore:".$ini_array['general']['dbstoreArray'][$config_array['db_store_id']]])) {
  trigger_error("Unable to find 'dbstore:".$ini_array['general']['dbstoreArray'][$config_array['db_store_id']]."' section in the .ini file", E_USER_ERROR);
  exit(1);
}

$dbstore_section = "dbstore:".$ini_array['general']['dbstoreArray'][$config_array['db_store_id']];

if (!array_key_exists('name',$ini_array[$dbstore_section]) ||
  !array_key_exists('dsn',$ini_array[$dbstore_section]) ||
  !array_key_exists('username',$ini_array[$dbstore_section]) ||
  !array_key_exists('password',$ini_array[$dbstore_section])) {
  trigger_error("Your dbstore_section section is incomplete", E_USER_ERROR);
  exit(1);
}

$config_array['regex_ignore'] = $ini_array['general']['regexToIgnore'];
$config_array['regex_product'] = array();
$config_array['regex_datetime'] = array();

foreach($ini_array['general']['productArray'] as $product_id) {
  $product_key = "product:".$product_id;
  if (array_key_exists($product_key, $ini_array)) {
    if (array_key_exists('productRegex', $ini_array[$product_key])) {
      $product_regex = $ini_array[$product_key]['productRegex'];
      $config_array['regex_product'][$product_regex] = $product_id;
      if (array_key_exists('datetimeRegex', $ini_array[$product_key])) {
        $config_array['regex_datetime'][$product_id] =
          $ini_array[$product_key]['datetimeRegex'];
      }
    } else {
      trigger_error("Unable to find key 'productRegex' in [$product_key] section in the .ini file", E_USER_WARNING);
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

/*******************************************************************************
 * DbStore checking
 * ****************************************************************************/
 
$dbstore_section = "dbstore:".$ini_array['general']['dbstoreArray'][$config_array['db_store_id']];

if (!array_key_exists('name',$ini_array[$dbstore_section]) ||
  !array_key_exists('dsn',$ini_array[$dbstore_section]) ||
  !array_key_exists('username',$ini_array[$dbstore_section]) ||
  !array_key_exists('password',$ini_array[$dbstore_section])) {
  trigger_error("Your dbstore_section section is incomplete", E_USER_ERROR);
  exit(1);
}

$dbstore = false;
try {
  $dbstore = new \pgh\DbStore(
    $ini_array[$dbstore_section]['dsn'],
    $ini_array[$dbstore_section]['username'],
    $ini_array[$dbstore_section]['password']
  );
} catch (Exception $e) {
  trigger_error("Unable to create a valid dbstore with you config for $dbstore_section", E_USER_ERROR);
  trigger_error($e->getMessage(), E_USER_ERROR);
  exit(1);
}

/*******************************************************************************
 * Cache checking
 * ****************************************************************************/

if ($arguments['c']){
  $config_array['cache_path'] = $arguments['c'];
}

/*

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

*/

/*******************************************************************************
 * Tree processing
 * ****************************************************************************/

$directory_array = array();
$file_array = array();

@mkdir($config_array['output_dirpath']);


\pgh\info("output_dirpath is : ".$config_array['output_dirpath']);
\pgh\info("STEP 1 : I have to proceed ".count($input_directory_array)." input dir");

$dir_index=1;
foreach($input_directory_array as $input_dirname) {
  \pgh\info("STEP 1.".$dir_index." : Start to proceed dir ".$dir_index."/".count($input_directory_array)." => ".$input_dirname);

  trigger_error("Tree processing for \"$input_dirname\"", E_USER_NOTICE);

  $tree_output_tmpfilename = tempnam(sys_get_temp_dir(), 'pgh_xml_');
  $command=$tree_command." ".$input_dirname." > ".$tree_output_tmpfilename;
  $command = $tree_command." ".$input_dirname;

  $exec_output = array();
  $exec_return_val = 0;
  $notify = false;
  if ($config_array['verbose']&& !$stdout_piped) {
    $notify = new \cli\notify\Spinner('Executing "tree" command on this dir...waiting...',50);
  }
  $pid = exec(sprintf("%s > %s 2>&1 & echo $!", $command, $tree_output_tmpfilename), $output, $return_val)."\n";

  while (\pgh\processIsRunning($pid)) {
    for ($i = 0; $i <= 100; $i++) {
      if ($notify) $notify->tick();
      usleep(10000);
    }
  }
  if ($notify) $notify->finish();
  \pgh\info("Done ! Tree command is finished, starting to proceed the results");

  $tree_xml = new SimpleXMLElement(file_get_contents($tree_output_tmpfilename));

  $tree_total_fs_size = (int)$tree_xml->report->size;
  $tree_total_directories= (int)$tree_xml->report->directories;
  $tree_total_files = (int)$tree_xml->report->files;

  \pgh\info("Some info about this dir \"".$input_dirname."\":");
  \pgh\info(" -> Total of sub dirs : ".$tree_total_directories
         ."\n -> Total of files    : ".$tree_total_files
         ."\n -> Total sum size    : ".formatBytes($tree_total_fs_size));
         
  $notify = false;
  if ($config_array['verbose']&& !$stdout_piped) {
    $notify = new \cli\progress\Bar('STEP 2 : Importing results ', $tree_total_files);
  }
  $base_path = dirname((string)$tree_xml->directory['name']);
  foreach ($tree_xml->directory as $simple_xml_element_dir) {
    \pgh\recurse_xml($simple_xml_element_dir,
		$base_path,
		$directory_array,
		$file_array,$tree_total_files,$notify);
  }
  if ($notify) $notify->finish();
  \pgh\info("Done ! All the results are imported");

  unset($tree_xml);
  unlink($tree_output_tmpfilename);
  $dir_index++;
}

$notify = false;
if ($config_array['verbose']&& !$stdout_piped) {
  $notify = new \cli\progress\Bar('STEP 3 : Extraction of metadata(s) into file',
  count($file_array));
}

foreach($file_array as &$file) {
  if ($notify) $notify->tick();

  if ($file->checkForIgnoreRegex($config_array['regex_ignore'])) {
    trigger_error("Ignoring ".$file->path, E_USER_NOTICE);
    $dbstore->update_file($file);
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
  $dbstore->update_file($file);
}
if ($notify) $notify->finish();

//$cache->update($file_array);

exit(0);
?>
