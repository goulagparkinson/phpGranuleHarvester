<?php

/*******************************************************************************

  This file is part of "phpGranuleHarvester" - Copyright 2013 Goulag PARKINSON
  Author(s) : Goulag PARKINSON <goulag.parkinson@gmail.com>

  "phpGranuleHarvester" is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  any later version.

  "phpGranuleHarvester" is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with "phpGranuleHarvester".  If not, see <http://www.gnu.org/licenses/>.

*******************************************************************************/

namespace pgh;

/**
 * Registers a basic auto loader for the `pgh` namespace.
 */
function register_autoload() {
	spl_autoload_register( function($class) {
	  // Only attempt to load classes in our namespace
	  if( substr( $class, 0, 4 ) !== 'pgh\\' ) {
		  return;
	  }

	  $base = dirname( __DIR__ ) . DIRECTORY_SEPARATOR;
	  $path = $base . str_replace( '\\', DIRECTORY_SEPARATOR, $class ) . '.php';
	  if( is_file( $path ) ) {
		  require_once $path;
	  }
  } );
}

function friendly_error_type($type)  { 
  switch($type)  { 
    case E_ERROR: // 1 // 
        return 'ERROR'; 
    case E_WARNING: // 2 // 
        return 'WARNING'; 
    case E_PARSE: // 4 // 
        return 'PARSE'; 
    case E_NOTICE: // 8 // 
        return 'NOTICE'; 
    case E_CORE_ERROR: // 16 // 
        return 'CORE_ERROR'; 
    case E_CORE_WARNING: // 32 // 
        return 'CORE_WARNING'; 
    case E_CORE_ERROR: // 64 // 
        return 'COMPILE_ERROR'; 
    case E_CORE_WARNING: // 128 // 
        return 'COMPILE_WARNING'; 
    case E_USER_ERROR: // 256 // 
        return 'ERROR'; 
    case E_USER_WARNING: // 512 // 
        return 'WARNING'; 
    case E_USER_NOTICE: // 1024 // 
        return 'DEBUG'; 
    case E_STRICT: // 2048 // 
        return 'STRICT'; 
    case E_RECOVERABLE_ERROR: // 4096 // 
        return 'RECOVERABLE_ERROR'; 
    case E_DEPRECATED: // 8192 // 
        return 'DEPRECATED'; 
    case E_USER_DEPRECATED: // 16384 // 
        return 'USER_DEPRECATED'; 
  } 
  return "UNKNOW"; 
}

function error_handler($errno, $errstr, $errfile, $errline) {
  global $config_array;
  if (!(error_reporting() & $errno)) {
    // This error code is not included in error_reporting
    return false;
  }

  $error_str = friendly_error_type($errno);
  $msg = "[$error_str] $errstr";
  if ($config_array['debug']) $msg.= " ($errfile:$errline)";

  if (!(function_exists('posix_isatty') && !posix_isatty(STDERR))) {
    if ($error_str=="ERROR") $msg="%R".$msg."%n";
    else if ($error_str=="WARNING") $msg="%Y".$msg."%n";
    else if ($errno==E_USER_NOTICE) $msg="%p".$msg."%n";
  }
  \cli\err($msg);
  return true;
}

function info( $msg = '' ) {
  global $config_array;
  if ($config_array['verbose']) {
    echo $msg."\n";
  }
}

function success( $msg = '' ) {
  global $config_array;
  if ($config_array['verbose']) {
    \cli\line("%G[SUCCESS] ".$msg."%n");
  }
}

function version() {
  global $bin_name;
  global $bin_version;
  \cli\line($bin_name." - Version ".$bin_version."
Copyright © 2013 Goulag PARKINSON.
Author(s) : Goulag PARKINSON <goulag.parkinson@gmail.com>
\"".$bin_name."\" is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or any later version.

\"".$bin_name."\" is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with \"".$bin_name."\" . If not, see <http://www.gnu.org/licenses/>.");
}

function recurse_xml(&$simple_xml_element_dir, &$parent_dirpath,
                     &$directory_array, &$file_array) {
  $total_dirsize = 0;
  $total_filesize = 0;


  $dirname = basename((string)$simple_xml_element_dir['name']);
  $dirpath = $parent_dirpath.'/'.$dirname;
  
  $command = "du -sb ".$dirpath." | cut -f1";  
  $exec_output = array();
  $exec_return_val = 0;
  $dirsize = exec($command, $output, $return_val);

  $directory = new \pgh\Directory($dirname, $dirpath, $dirsize);
    
  /*****************************************************************************
   * STEP 1 : Start by files in this directory
   * **************************************************************************/
  $file_counter = 0;
  foreach ($simple_xml_element_dir->file as $simple_xml_element_file) {
    $filename = (string)$simple_xml_element_file['name'];
    $filepath = $dirpath."/".$filename;
    $filesize = (string)$simple_xml_element_file['size'];
    $filedatetime = (string)$simple_xml_element_file['time'];

    $file = new \pgh\File($filename, $filepath, $filesize,
    $filedatetime);
    array_push($file_array, $file);
    $file_counter++;
  }
  $directory->fs_file_counter = $file_counter;
  
  /*****************************************************************************
   * STEP 2 : Recurse by browsing sub directory
   * **************************************************************************/
  $sub_dir_counter = 0;
  foreach ($simple_xml_element_dir->directory as $simple_xml_element_child_dir) {
    $file_counter+=recurse_xml($simple_xml_element_child_dir,$dirpath, $directory_array, $file_array);
    $sub_dir_counter++;
  }
  $directory->fs_dir_counter = $sub_dir_counter;

  array_push($directory_array, $directory);
}
