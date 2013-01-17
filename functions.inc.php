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

function friendly_error_type($type)  { 
  switch($type)  { 
    case E_ERROR: // 1 // 
        return 'RUN_ERROR'; 
    case E_WARNING: // 2 // 
        return 'RUN_WARNING'; 
    case E_PARSE: // 4 // 
        return 'RUN_PARSE'; 
    case E_NOTICE: // 8 // 
        return 'RUN_NOTICE'; 
    case E_CORE_ERROR: // 16 // 
        return 'CORE_ERROR'; 
    case E_CORE_WARNING: // 32 // 
        return 'CORE_WARNING'; 
    case E_CORE_ERROR: // 64 // 
        return 'COMPILE_ERROR'; 
    case E_CORE_WARNING: // 128 // 
        return 'COMPILE_WARNING'; 
    case E_USER_ERROR: // 256 // 
        return 'USER_ERROR'; 
    case E_USER_WARNING: // 512 // 
        return 'USER_WARNING'; 
    case E_USER_NOTICE: // 1024 // 
        return 'USER_NOTICE'; 
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

/*
 function error_handler($errno, $errstr, $errfile, $errline) {
  global $is_piped;
  if (!(error_reporting() & $errno)) {
    // This error code is not included in error_reporting
    return false;
  }
  $error_str = friendly_error_type($errno);
  $msg = "file:$errfile;line:$errline;msg:$errstr";
  if (!$is_piped) {
    if (substr($error_str,-5)=="ERROR") $msg="%R[$error_str] ".$msg;
    else if (substr($error_str,-7)=="WARNING") $msg="%Y[$error_str] ".$msg;
    else if ($errno==E_USER_NOTICE) $msg="%p[$error_str] ".$msg;
    else $msg="[$error_str] ".$msg;
    echo Colors::colorize( $msg."%n\n" );
  }
  else {
    $msg="[$error_str] ".$msg;
    echo $msg."\n";
  }
  //if ($errno==E_USER_ERROR) exit(1);
  return true;
}
*/

function trigger_success($msg) {
  global $is_piped;
  if (!$is_piped) {
    $msg="%G[SUCCESS] ".$msg;
    echo Colors::colorize( $msg."%n\n" );
  }
  else {
    $msg="[SUCCESS] ".$msg;
    echo $msg."\n";
  }
}
function debug($msg) {
  global $is_piped;
  global $option_debug;
  if (!$option_debug) return;
  if (!$is_piped) {
    $msg="%c".$msg;
    echo Colors::colorize( $msg."%n" );
  }
  else {
    echo $msg;
  }
}

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

function formatBytes($bytes, $precision = 2) {
  $units = array('B', 'KB', 'MB', 'GB', 'TB');

  $bytes = max($bytes, 0);
  $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
  $pow = min($pow, count($units) - 1);

  // Uncomment one of the following alternatives
  $bytes /= pow(1024, $pow);
  // $bytes /= (1 << (10 * $pow));

  return round($bytes, $precision) . ' ' . $units[$pow];
}

function extract_datetime_array_from_filepath(&$filepath, &$product_id) {
  global $regex_datetime_array;
  $regex = $regex_datetime_array[$product_id];
  preg_match("/".$regex."/",$filepath, $matches);
  return $matches;
}

function fill_datetime_fileinfo(&$start_datetime_str,&$stop_datetime_str,
  &$file_info) {
  $start_datetime = new DateTime($start_datetime_str);
  $stop_datetime = new DateTime($stop_datetime_str);
  $start_datetime_str = $start_datetime->format("Y-m-d H:i:s");
  $stop_datetime_str = $stop_datetime->format("Y-m-d H:i:s");
  $time_coverage = $start_datetime->diff($stop_datetime);

  $file_info['start_datetime'] = $start_datetime_str;
  $file_info['stop_datetime'] = $stop_datetime_str;
  $file_info['time_coverage'] = $time_coverage->format('%dd%Hh%Im%Ss');
}

function detect_duplicate_byname(&$file_array) {
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

function gunzip_dataset_partial($input_filename, &$output_filename, $length) {
    $output_filename = tempnam(sys_get_temp_dir(), 'pgh_gunzip_');
    $handle = gzopen($input_filename, "r");
    $contents = gzread($handle, $length);
    gzclose($handle);
    $handle = fopen($output_filename,"wb");
    fwrite($handle, $contents);
    fclose($handle);
    return true;
}

function bunzip_dataset($input_filename, &$output_filename) {
    $output_filename = tempnam(sys_get_temp_dir(), 'pgh_bunzip_');
    $command = "bunzip2 -ck ".$input_filename." > ".$output_filename;
    $exec_output = array();
    $exec_return_val = 0;
    exec($command, $output, $return_val);
    return true;
}

function bunzip_dataset_partial($input_filename, &$output_filename, $length) {
    $output_filename = tempnam(sys_get_temp_dir(), 'pgh_bunzip_');
    $bz = bzopen($input_filename,'r');
    if ($bz==FALSE) {
      trigger_error("Unable to bzopen in $input_filename", E_USER_ERROR);
      return FALSE;
    }
    $blob_data = '';
    while(strlen($blob_data)<$length) {
      $buffer = bzread($bz);
      if ($buffer === FALSE) {
        trigger_error("Unable to bzread in $input_filename", E_USER_ERROR);
        bzclose($bz);
        return false;
      }
      if (bzerrno($bz) !== 0) {
        trigger_error(bzerrstr($bz)." in $input_filename", E_USER_ERROR);
        bzclose($bz);
        return false;
      }
      $blob_data.=$buffer;
    }
    bzclose($bz);
    $handle = fopen($output_filename,"wb");
    fwrite($handle, $blob_data);
    fclose($handle);
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

function h5dump_xml($input_filename, &$output_filename) {
    $output_filename = tempnam(sys_get_temp_dir(), 'pgh_h5dump_');
    $command = "h5dump -HAx -u ".$input_filename." > ".$output_filename;
    $exec_output = array();
    $exec_return_val = 0;
    exec($command, $output, $return_val);
    return true;
}

?>
