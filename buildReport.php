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

$bin_name = "reportBuild";
$bin_version = "0.1alpha";
$now_time = time();
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

$arguments->addOption(array('o'), array(
	'default'     => $config_array['output_dirpath'],
	'description' => 'Set the output directory path to store results'));
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

$db = false;

try {
  $db = new PDO(
    $ini_array[$dbstore_section]['dsn'],
    $ini_array[$dbstore_section]['username'],
    $ini_array[$dbstore_section]['password']
  );
} catch (Exception $e) {
  trigger_error("Unable to create a valid db with you config for $dbstore_section", E_USER_ERROR);
  trigger_error($e->getMessage(), E_USER_ERROR);
  exit(1);
}

$extraction_array = array(
  'extraction_count' => 0,
  'extraction_size' => 0,
  'extraction_fail_count' => 0,
  'extraction_fail_size' => 0
);

$product_array = array();
$product_fail_array = array(
  'ignored_count' => 0,
  'ignored_size' => 0,
  'ignored_list' => array(),
  'product_fail_count' => 0,
  'product_fail_size' => 0,
  'product_fail_list' => array()
);

$query = "SELECT * FROM `files`";

foreach  ($db->query($query) as $row) {
  $product_id = $row['product_id'];
  $status = $row['status']; // IGNORED / EXTRACTED / PRODUCT FAILED / NO PRODUCT REGEX
  $name = $row['name'];
  $path = $row['path'];
  $sha1_path = $row['sha1_path'];
  $insert_datetime = $row['insert_datetime'];
  $update_datetime = $row['update_datetime'];
  $size = $row['size'];
  if (empty($product_id)) {
    if ($status=="IGNORED") {
      $product_fail_array['ignored_count']++;
      $product_fail_array['ignored_size']+=$size;
      $product_fail_array['ignored_list'][$sha1_path] = array('name' => $name, 'path' => $path, 'sha1_path' => $sha1_path, 'size' => $size, 'insert_datetime' => $insert_datetime, 'update_datetime' => $update_datetime);
    } else if ($status=="PRODUCT FAILED" || $status=="NO PRODUCT REGEX") {
      $product_fail_array['product_fail_count']++;
      $product_fail_array['product_fail_size']+=$size;
      $product_fail_array['product_fail_list'][$sha1_path] = array('name' => $name, 'path' => $path, 'sha1_path' => $sha1_path, 'size' => $size, 'insert_datetime' => $insert_datetime, 'update_datetime' => $update_datetime);;
    } else {
      die("STRANGE STATUS CODE!!!");
    }
    continue;
  } else if (!array_key_exists($product_id, $product_array)) {
    $product_array[$product_id] = array(
      'extraction_count' => 0,
      'extraction_size' => 0,
      'extraction_list' => array(),
      'extraction_fail_count' => 0,
      'extraction_fail_size' => 0,
      'extraction_fail_list' => array(),
      'start_datetime' => 100000,
      'stop_datetime' => 0
    );
  }
  if ($status == "EXTRACTED") {
      $extraction_array['extraction_count']++;
      $product_array[$product_id]['extraction_count']++;
      $extraction_array['extraction_size']+=$size;
      $product_array[$product_id]['extraction_size']+=$size;
      $product_array[$product_id]['extraction_list'][$sha1_path] = array('name' => $name, 'path' => $path, 'sha1_path' => $sha1_path, 'size' => $size, 'insert_datetime' => $insert_datetime, 'update_datetime' => $update_datetime);
  } else if ($status == "EXTRACT FAILED") {
      $extraction_array['extraction_fail_count']++;
      $product_array[$product_id]['extraction_fail_count']++;
      $extraction_array['extraction_fail_size']+=$size;
      $product_array[$product_id]['extraction_fail_size']+=$size;
      $product_array[$product_id]['extraction_fail_list'][$sha1_path] = array('name' => $name, 'path' => $path, 'sha1_path' => $sha1_path, 'size' => $size, 'insert_datetime' => $insert_datetime, 'update_datetime' => $update_datetime);    
  } else {
    die("STRANGE STATUS CODE!!! $status");
  }
}
ksort($product_array);

$product_count = count($product_array);
$product_index = 1;
foreach($product_array as $product_id => &$product_info) {
  echo "Product ".sprintf("%2d", $product_index)."/$product_count : $product_id\n";
  echo "  Extracted total count = ".$product_info['extraction_count']."\n";
  $product_info['extraction_avg_size'] = round($product_info['extraction_size']/$product_info['extraction_count']);
  echo "  Extracted total size  = ".formatBytes($product_info['extraction_size'])." (".$product_info['extraction_size']." B)\n";
  echo "  Extracted avg   size  = ".formatBytes($product_info['extraction_avg_size'])." (".$product_info['extraction_avg_size']." B)\n";
  if ($product_info['extraction_fail_count']) {
    echo "\n  Warning ! Some extraction failed :\n";
    echo "    Fail rate = ".round(100*$product_info['extraction_fail_count']/($product_info['extraction_fail_count']+$product_info['extraction_count']))."%\n";
    echo "    Fail total count = ".$product_info['extraction_fail_count']."\n";
    $product_info['extraction_fail_avg_size'] = round($product_info['extraction_fail_size']/$product_info['extraction_fail_count']);
    echo "    Fail  total size  = ".formatBytes($product_info['extraction_fail_size'])." (".$product_info['extraction_fail_size']." B)\n";
    echo "    Fail  avg   size  = ".formatBytes($product_info['extraction_fail_avg_size'])." (".$product_info['extraction_fail_avg_size']." B)\n";
    echo "    Fail file list (size;path):\n";
    foreach($product_info['extraction_fail_list'] as $sha1_path => &$file_info) {
    echo "      ".$file_info['size'].";".$file_info['path']."\n";
    }
  }
  echo "\n\n";
  $product_index++;
}

$total_count = $extraction_array['extraction_count']+$extraction_array['extraction_fail_count']+$product_fail_array['ignored_count']+$product_fail_array['product_fail_count'];
$total_size = $extraction_array['extraction_size']+$extraction_array['extraction_fail_size']+$product_fail_array['ignored_size']+$product_fail_array['product_fail_size'];
echo "Total\n";
echo "  Ignored   = ".sprintf("%5.1f", round(100.0*$product_fail_array['ignored_count']/$total_count,1))." %, ".sprintf("%".strlen($total_count)."d",$product_fail_array['ignored_count']).", ".formatBytes($product_fail_array['ignored_size'])."\n";
echo "  Unknow    = ".sprintf("%5.1f", round(100.0*$product_fail_array['product_fail_count']/$total_count,1))." %, ".sprintf("%".strlen($total_count)."d",$product_fail_array['product_fail_count']).", ".formatBytes($product_fail_array['product_fail_count'])."\n";
echo "  Extracted = ".sprintf("%5.1f", round(100.0*$extraction_array['extraction_count']/$total_count,1))." %, ".sprintf("%".strlen($total_count)."d",$extraction_array['extraction_count']).", ".formatBytes($extraction_array['extraction_size'])."\n";
echo "  Failed    = ".sprintf("%5.1f", round(100.0*$extraction_array['extraction_fail_count']/$total_count,1))." %, ".sprintf("%".strlen($total_count)."d",$extraction_array['extraction_fail_count']).", ".formatBytes($extraction_array['extraction_fail_size'])."\n";
echo "              ______________________\n";
echo "  Indexed   = 100.0 %, ".$total_count.", ".formatBytes($total_size)."\n";


//print_r($product_fail_array);
exit(0);
?>
