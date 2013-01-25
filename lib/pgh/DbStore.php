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
use PDO;

class DbStore {
  public static $db;

  function __construct($dsn, $username='', $password='', $driver_options=array()) {
    self::$db = new PDO($dsn, $username, $password, $driver_options);
  }

  function update_file($file) {
    global $now_time;
    $now = strftime("%F %T", $now_time);
    $stmt = self::$db->query("SELECT * FROM `files` WHERE `sha1_path` = '".$file->sha1_path."';");
    if ($stmt->rowCount()) {
      //Need to merge;
      $query= "UPDATE  `files` SET  `status` =  '".$file->status."', `update_datetime` =  '".$now."', `start_datetime` = '".$file->start_datetime_str."', `stop_datetime` = '".$file->stop_datetime_str."' WHERE  `files`.`sha1_path` =  '".$file->sha1_path."';";
      if (!self::$db->exec($query)) {
        echo "\nPDO::errorInfo():\n";
        print_r(self::$db->errorInfo());
        echo $query."\n";
      }
    } else {
      //Need to insert
      $query= "INSERT INTO `files` (`sha1_path`, `name`, `path`, `product_id`, `size`, `md5sum`, `status`, `insert_datetime`, `update_datetime`, `delete_datetime`, `start_datetime`, `stop_datetime`, `metadata`) VALUES ('".$file->sha1_path."', '".$file->name."', '".$file->path."', '".$file->getProductId()."', '".$file->size."', '".$file->md5sum."', '".$file->status."', '".$now."', '".$now."', NULL, '".$file->start_datetime_str."', '".$file->stop_datetime_str."', 'metadata_TBC');";
      
      if (!self::$db->exec($query)) {
        echo "\nPDO::errorInfo():\n";
        print_r(self::$db->errorInfo());
        echo $query."\n";
      }
    }
  }
}  

/*
class DataMapper {
    public static $db;
    
    public static function init($db)
    {
        self::$db = $db;
    }
}

class VendorMapper extends DataMapper
{
    public static function add($vendor)
    {
        $st = self::$db->prepare(
            "insert into vendors set
            first_name = :first_name,
            last_name = :last_name"
        );
        $st->execute(array(
            ':first_name' => $vendor->first_name,
            ':last_name' => $vendor->last_name
        ));
    }
}
*/
