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
    $stmt = parent::prepare("INSERT INTO file ('name','path','size','sha1_name','sha1_path','creation_datetime','last_modification_datetime','product_id','start_datetime','stop_datetime') VALUES (:name,:path,:size,:sha1_name,:sha1_path,:creation_datetime,:last_modification_datetime,:product_id,:start_datetime,:stop_datetime)");



    foreach ($file_array as &$file) {    
      $stmt->bindValue(':name', $file->name, SQLITE3_TEXT)."\n";
      $stmt->bindValue(':path', $file->path, SQLITE3_TEXT);
      $stmt->bindValue(':size', $file->fs_total_size, SQLITE3_INTEGER);
      $stmt->bindValue(':sha1_name', $file->sha1_name, SQLITE3_TEXT);
      $stmt->bindValue(':sha1_path', $file->sha1_path, SQLITE3_TEXT);
      $stmt->bindValue(':creation_datetime', $now, SQLITE3_TEXT);
      $stmt->bindValue(':last_modification_datetime', $now, SQLITE3_TEXT);
      $stmt->bindValue(':product_id', $file->getProductId(), SQLITE3_TEXT);
      $stmt->bindValue(':start_datetime', $file->start_datetime_str, SQLITE3_TEXT);
      $stmt->bindValue(':stop_datetime', $file->stop_datetime_str, SQLITE3_TEXT);
      $result = $stmt->execute();
    }
  }
}
