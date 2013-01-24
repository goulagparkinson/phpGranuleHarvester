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

class File {
	public $name;
	public $path;
	public $size = 0;
	public $sha1_name;
	public $sha1_path;
  public $md5sum = 0;
  public $status = "UNKNOW";
  public $last_modification_time;
  protected $_ignore_by_regex = false;
  protected $_product_id;
  public $start_datetime_str = false;
  public $stop_datetime_str = false;
  public $metadataExtracted = false;
  
	public function __construct($name, $path,
    $size, $last_modification_time) {
		$this->name = $name;
		$this->path = $path;
		$this->size = $size;
    $this->last_modification_time = $last_modification_time;
    $this->sha1_name = sha1($this->name);
    $this->sha1_path = sha1($this->path);
	}

  public function checkForIgnoreRegex(&$regex) {
    if (preg_match("/".$regex."/",$this->path, $matches)) {
      $this->_ignore_by_regex = true;
      $this->status = "IGNORED";
      return true;
    } else return false;
  }
  public function checkForProductRegex(&$regex_array) {
    foreach($regex_array as $regex => &$product_id) {
      if (preg_match("/".$regex."/",$this->name, $matches)) {
        $this->_product_id = $product_id;
        $this->status = "READY TO PROCEED";
        return true;
      }
    }
    $this->status = "NO PRODUCT REGEX";
    return false;
  }

  public function extractMetadata() {
    $functionHandlerName = "datetime_handler_".str_replace(array('-','.'),"_",$this->_product_id);
    if ($functionHandlerName($this)) {
      $this->metadataExtracted = true;
      $this->md5sum = md5_file($this->path);
      $this->status = "EXTRACTED";
      return true;
    } else {
      $this->status = "EXTRACT FAILED";
      return false;
    }
  }
	static public function extract_datetime_array_from_filepath($file, &$dt) {
    global $config_array;
    $regex = $config_array['regex_datetime'][$file->_product_id];
    if (preg_match("/".$regex."/",$file->path, $dt)) {
      return true;
    }
    return false;
  }
  public function setDatetimeInterval($start_datetime_str,$stop_datetime_str) {
    $this->start_datetime_str = $start_datetime_str;
    $this->stop_datetime_str = $stop_datetime_str;
  }

  public function isIgnoredByRegex() {
    return $this->_ignore_by_regex;
  }
  public function getProductId() {
    return $this->_product_id;
  }
}
