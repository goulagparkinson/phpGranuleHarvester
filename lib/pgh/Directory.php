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

class Directory {
	public $name;
	public $path;
	public $fs_total_size = 0;
	public $sha1_name;
	public $sha1_path;
  public $fs_file_counter = 0;
  public $fs_dir_counter = 0;
  
	public function __construct($name, $path, $size) {
		$this->name = $name;
		$this->path = $path;
		$this->fs_total_size = $size;
    $this->sha1_name = sha1($this->name);
    $this->sha1_path = sha1($this->path);
	}

}
