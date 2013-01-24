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

$dir_to_proceed = array();
/*
$dir_to_proceed[] = "/data/solab/AQUARIUS_L3_SSS_SMI_7DAY";
$dir_to_proceed[] = "/data/solab/ASCAT-L2-12km";
$dir_to_proceed[] = "/data/solab/ASCAT-L2-Coastal";
$dir_to_proceed[] = "/data/solab/ASI-AMSRE";
$dir_to_proceed[] = "/data/solab/AVHRR_Pathfinder-NODC-L3C-v5.2";
$dir_to_proceed[] = "/data/solab/AVISO_DT_REF_MADT_MERGED_H";
$dir_to_proceed[] = "/data/solab/AVISO_DT_REF_MADT_MERGED_UV";
$dir_to_proceed[] = "/data/solab/AVISO_NRT_MADT_MERGED_H";
$dir_to_proceed[] = "/data/solab/AVISO_NRT_MADT_MERGED_UV";
$dir_to_proceed[] = "/data/solab/AVISO_NRT_MSWH_MERGED";
$dir_to_proceed[] = "/data/solab/AVISO_NRT_MWIND_MERGED";
$dir_to_proceed[] = "/data/solab/ERSST-V3B";
$dir_to_proceed[] = "/data/solab/EUR-L2P-AVHRR_METOP_A";
$dir_to_proceed[] = "/data/solab/IFR-L4-SSTfnd-ODYSSEA-GLOB_010";
$dir_to_proceed[] = "/data/solab/IFR-L4-SSTfnd-ODYSSEA-MED_002";
$dir_to_proceed[] = "/data/solab/JPL-L4UHfnd-GLOB-MUR";
$dir_to_proceed[] = "/data/solab/JPL_OUROCEAN-L4UHfnd-GLOB-G1SST";
$dir_to_proceed[] = "/data/solab/MERCI_MER_RR_1P";
$dir_to_proceed[] = "/data/solab/NCDC-SEAWINDS-OW-6hr";
$dir_to_proceed[] = "/data/solab/NCEP_CFSR1HR";
$dir_to_proceed[] = "/data/solab/NCEP_GFS";
$dir_to_proceed[] = "/data/solab/OISST-AVHRR-AMSR-V2";
$dir_to_proceed[] = "/data/solab/OISST-AVHRR-V2";
$dir_to_proceed[] = "/data/solab/OSTIA";
$dir_to_proceed[] = "/data/solab/QSCAT_L2B12";
*/
$dir_to_proceed[] = "/data/solab/SSMI";

function processIsRunning($pid){
  try{
    $result = shell_exec(sprintf("ps %d", $pid));
    if( count(preg_split("/\n/", $result)) > 2){
      return true;
    }
  } catch(Exception $e){}
  return false;
}


$pid_array = array();
foreach($dir_to_proceed as $dirname) {
  $exec_output = array();
  $exec_return_val = 0;
  $output_dirpath = tempnam(sys_get_temp_dir(), 'pgh_');
  unlink($output_dirpath);
  $cmd = "./phpGranuleHarvester.php -d ".$dirname." -o ".$output_dirpath." --verbose > /dev/null 2>&1 & echo $!";
  echo $cmd."\n";
  $pid = exec($cmd, $output, $return_val);
  $pid_array[$pid] = array("dir_to_proceed" => $dirname, "output_dirpath" => $output_dirpath, "running" => true);
}
print_r($pid_array);

$ended = false;
while (!$ended) {
  $ended=true;
  foreach($pid_array as $pid => $pid_info) {
    $pid_indo['running'] = processIsRunning($pid);
    $ended = $ended && !$pid_indo['running'];
    echo "[".$pid."] ".($pid_indo['running']?"RUNNING":"ENDED")." ".$pid_info['dir_to_proceed']." => ".$pid_info['output_dirpath']."\n\n\n";
  }
  sleep(1);
}
