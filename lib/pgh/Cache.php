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

class Cache extends \SQLite3 {  
  public $path;
  
	public function __construct($path) {
		$this->open($path);
	}
  static function create($path) {
    $cache = new Cache($path);
/*
     CREATE TABLE "main"."file" (
    "name" TEXT,
    "path" TEXT,
    "size" INTEGER,
    "sha1_name" TEXT,
    "sha1_path" TEXT,
    "creation_datetime" TEXT,
    "last_modification_datetime" TEXT,
    "product_id" TEXT,
    "start_datetime" TEXT,
    "stop_datetime" TEXT
);
*/
  }
  static function connect($path) {
    $cache = new Cache($path);
    return $cache;
  }

  public function update(&$file_array) {
    global $now_time;
    $now = strftime("%F %T", $now_time);
    $query = '';
    foreach ($file_array as $file) {    
      if ($query) $query.=",";
      $query .= "('".$file->name
      ."','".$file->path."','".$file->fs_total_size."','".$file->sha1_name
      ."','".$file->sha1_path."','".$now."','".$now."','".$file->getProductId()
      ."','".$file->start_datetime_str."','".$file->stop_datetime_str."')";
    }
    $query = "INSERT INTO file ('name','path','size','sha1_name','sha1_path','creation_datetime','last_modification_datetime','product_id','start_datetime','stop_datetime') VALUES ".$query;
    parent::exec($query);
  }
}
