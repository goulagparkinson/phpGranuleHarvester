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

/***********************************************************************
 * !!! Warning, if you have to add a new product and of course a new
 * datetime_handler_PRODUCT_ID, take in consideration that you have to
 * carefully replace "-" and "." in the name of the product by "_"
 * due to the fact it's impossible in PHP to have such character in the
 * name of a function. For the automatic pattern matching in the caller
 * section, a sort of str_replace(array('-','.'),"_",$product_id) is
 * done !!!
 **********************************************************************/

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
  $file_info['time_coverage'] = $time_coverage->format('%S');
  echo "from     = ".$file_info['start_datetime']."\n";
  echo "to       = ".$file_info['stop_datetime']."\n";
  echo "interval = ".$time_coverage->format("%dd%Hh%Im%Ss")."\n";
}
  
/***********************************************************************
 * AQUARIUS_L3_SSS_SMI_7DAY => AQUARIUS_L3_SSS_SMI_7DAY
 **********************************************************************/
function datetime_handler_AQUARIUS_L3_SSS_SMI_7DAY($dt) {
  //echo "[DEBUG] inside ".__FUNCTION__."\n";
  $t = date_parse_from_format("Y-z H:i:s", $dt[1]."-".($dt[2]-1)." 00:00:01");
  $b_ts = mktime($t['hour'],$t['minute'],$t['second'],$t['month'],$t['day'],$t['year']);
  $t = date_parse_from_format("Y-z H:i:s", $dt[3]."-".($dt[4]-1)." 23:59:59");
  $e_ts = mktime($t['hour'],$t['minute'],$t['second'],$t['month'],$t['day'],$t['year']);
/*
  $b_str = strftime("%F %T", $b_ts);
  $e_str = strftime("%F %T", $e_ts);
  echo "[DEBUG] filename = $dt[0]\n";
  echo "[DEBUG] beginDateTime_str = $b_str\n";
  echo "[DEBUG] endDateTime_str   = $e_str\n";
*/
  return array($b_ts,$e_ts);
}

/***********************************************************************
 * ASCAT-L2-12km => ASCAT_L2_12km
 **********************************************************************/
function datetime_handler_ASCAT_L2_12km(&$file_info) {
  gunzip_dataset($file_info['path'],$nc_filename);
  ncdump_xml($nc_filename, $xml_filename);

  $xml = new SimpleXMLElement(str_replace('xmlns=', 'ns=', file_get_contents($xml_filename)));
  unlink($nc_filename);
  unlink($xml_filename);

  $start_date_str = (string)$xml->xpath('/netcdf/attribute[@name="start_date"]')[0]['value'];
  $start_time_str = (string)$xml->xpath('/netcdf/attribute[@name="start_time"]')[0]['value'];
  $stop_date_str = (string)$xml->xpath('/netcdf/attribute[@name="stop_date"]')[0]['value'];
  $stop_time_str = (string)$xml->xpath('/netcdf/attribute[@name="stop_time"]')[0]['value'];
  $start_datetime_str = $start_date_str." ".$start_time_str;
  $stop_datetime_str = $stop_date_str." ".$stop_time_str;

  fill_datetime_fileinfo($start_datetime_str, $stop_datetime_str, $file_info);
}

/***********************************************************************
 * ASCAT-L2-Coastal => ASCAT_L2_Coastal
 **********************************************************************/
function datetime_handler_ASCAT_L2_Coastal(&$file_info) {
  return datetime_handler_ASCAT_L2_12km($file_info);
}

/***********************************************************************
 * ASI-AMSRE => ASI_AMSRE
 **********************************************************************/
function datetime_handler_ASI_AMSRE(&$file_info) {
  $dt = extract_datetime_array_from_filepath($file_info['path'],
    $file_info['product_id_regex_matching']);
  $t = date_parse_from_format("Y-m-d H:i:s", $dt[1]."-".$dt[2]."-".$dt[3]." 00:00:00");
  $start_datetime_ts = mktime($t['hour'],$t['minute'],$t['second'],$t['month'],$t['day'],$t['year']);
  $start_datetime_str = strftime("%F %T", $start_datetime_ts);
  $t = date_parse_from_format("Y-m-d H:i:s", $dt[1]."-".$dt[2]."-".$dt[3]." 23:59:59");
  $stop_datetime_ts = mktime($t['hour'],$t['minute'],$t['second'],$t['month'],$t['day'],$t['year']);
  $stop_datetime_str = strftime("%F %T", $stop_datetime_ts);

  fill_datetime_fileinfo($start_datetime_str, $stop_datetime_str, $file_info);
}

/***********************************************************************
 * AVHRR_Pathfinder-NODC-L3C-v5.2 => AVHRR_Pathfinder_NODC_L3C_v5_2
 **********************************************************************/
function datetime_handler_AVHRR_Pathfinder_NODC_L3C_v5_2(&$file_info) {
  ncdump_xml($file_info['path'], $xml_filename);

  $xml = new SimpleXMLElement(str_replace('xmlns=', 'ns=', file_get_contents($xml_filename)));
  unlink($xml_filename);

  $start_datetime_str = (string)$xml->xpath('/netcdf/attribute[@name="time_coverage_start"]')[0]['value'];
  $stop_datetime_str = (string)$xml->xpath('/netcdf/attribute[@name="time_coverage_end"]')[0]['value'];

  fill_datetime_fileinfo($start_datetime_str, $stop_datetime_str, $file_info);
}

/***********************************************************************
 * AVISO_DT_REF_MADT_MERGED_H => AVISO_DT_REF_MADT_MERGED_H
 **********************************************************************/
function datetime_handler_AVISO_DT_REF_MADT_MERGED_H(&$file_info) {
  $dt = extract_datetime_array_from_filepath($file_info['path'],
    $file_info['product_id_regex_matching']);
  $t = date_parse_from_format("Y-m-d H:i:s", $dt[1]."-".$dt[2]."-".$dt[3]." 00:00:00");
  $start_datetime_ts = mktime($t['hour'],$t['minute'],$t['second'],$t['month'],$t['day'],$t['year']);
  $start_datetime_str = strftime("%F %T", $start_datetime_ts);
  $ts_coverage = 7*24*3600; // 7days is the time coverage
  $stop_datetime_ts = $start_datetime_ts+$ts_coverage-1;
  $stop_datetime_str = strftime("%F %T", $stop_datetime_ts);

  fill_datetime_fileinfo($start_datetime_str, $stop_datetime_str, $file_info);
}

/***********************************************************************
 * AVISO_DT_REF_MADT_MERGED_UV => AVISO_DT_REF_MADT_MERGED_UV
 **********************************************************************/
function datetime_handler_AVISO_DT_REF_MADT_MERGED_UV(&$file_info) {
  //echo "[DEBUG] inside ".__FUNCTION__."\n";
  return datetime_handler_AVISO_DT_REF_MADT_MERGED_H($file_info);
}

/***********************************************************************
 * AVISO_NRT_MADT_MERGED_H => AVISO_NRT_MADT_MERGED_H
 **********************************************************************/
function datetime_handler_AVISO_NRT_MADT_MERGED_H(&$file_info) {
  $dt = extract_datetime_array_from_filepath($file_info['path'],
    $file_info['product_id_regex_matching']);
  $t = date_parse_from_format("Y-m-d H:i:s", $dt[1]."-".$dt[2]."-".$dt[3]." 00:00:00");
  $start_datetime_ts = mktime($t['hour'],$t['minute'],$t['second'],$t['month'],$t['day'],$t['year']);
  $start_datetime_str = strftime("%F %T", $start_datetime_ts);
  $ts_coverage = 24*3600; // 1 day is the time coverage
  $stop_datetime_ts = $start_datetime_ts+$ts_coverage-1;
  $stop_datetime_str = strftime("%F %T", $stop_datetime_ts);
  
  fill_datetime_fileinfo($start_datetime_str, $stop_datetime_str, $file_info);
}

/***********************************************************************
 * AVISO_NRT_MADT_MERGED_UV => AVISO_NRT_MADT_MERGED_UV
 **********************************************************************/
function datetime_handler_AVISO_NRT_MADT_MERGED_UV(&$file_info) {
  //echo "[DEBUG] inside ".__FUNCTION__."\n";
  return datetime_handler_AVISO_NRT_MADT_MERGED_H($file_info);
}

/***********************************************************************
 * AVISO_NRT_MSWH_MERGED => AVISO_NRT_MSWH_MERGED
 **********************************************************************/
function datetime_handler_AVISO_NRT_MSWH_MERGED(&$file_info) {
  //echo "[DEBUG] inside ".__FUNCTION__."\n";
  return datetime_handler_AVISO_NRT_MADT_MERGED_H($file_info);
}

/***********************************************************************
 * AVISO_NRT_MWIND_MERGED => AVISO_NRT_MWIND_MERGED
 **********************************************************************/
function datetime_handler_AVISO_NRT_MWIND_MERGED(&$file_info) {
  //echo "[DEBUG] inside ".__FUNCTION__."\n";
  return datetime_handler_AVISO_NRT_MADT_MERGED_H($file_info);
}

/***********************************************************************
 * ERSST_V3B => ERSST_V3B
 **********************************************************************/
function datetime_handler_ERSST_V3B(&$file_info) {
  $dt = extract_datetime_array_from_filepath($file_info['path'],
    $file_info['product_id_regex_matching']);
  $t = date_parse_from_format("Y-m-d H:i:s", $dt[1]."-".$dt[2]."-01 00:00:00");
  $start_datetime_ts = mktime($t['hour'],$t['minute'],$t['second'],$t['month'],$t['day'],$t['year']);
  $start_datetime_str = strftime("%F %T", $start_datetime_ts);
  $stop_datetime = date_create($start_datetime_str);
  $stop_datetime->modify('+1 month -1 sec');
  $stop_datetime_ts = $stop_datetime->getTimestamp();
  $stop_datetime_str = strftime("%F %T", $stop_datetime_ts);
  
  fill_datetime_fileinfo($start_datetime_str, $stop_datetime_str, $file_info);
}

/***********************************************************************
 * EUR-L2P-AVHRR_METOP_A => EUR_L2P_AVHRR_METOP_A
 **********************************************************************/
 function datetime_handler_EUR_L2P_AVHRR_METOP_A($dt) {
  //echo "[DEBUG] inside ".__FUNCTION__."\n";
  $ts_coverage = 60*3; // 3m is the time coverage
  $t = date_parse_from_format("Y-m-d H:i:s", $dt[1]."-".$dt[2]."-".$dt[3]." ".$dt[4].":".$dt[5].":".$dt[6]);
  $b_ts = mktime($t['hour'],$t['minute'],$t['second'],$t['month'],$t['day'],$t['year']);
  $e_ts = $b_ts+$ts_coverage;
/*
  $b_str = strftime("%F %T", $b_ts);
  $e_str = strftime("%F %T", $e_ts);
  echo "[DEBUG] filename = $dt[0]\n";
  echo "[DEBUG] beginDateTime_str = $b_str\n";
  echo "[DEBUG] endDateTime_str   = $e_str\n";
*/
  return array($b_ts,$e_ts);
}

/***********************************************************************
 * IFR-L4-SSTfnd-ODYSSEA-GLOB_010 => IFR_L4_SSTfnd_ODYSSEA_GLOB_010
 **********************************************************************/
function datetime_handler_IFR_L4_SSTfnd_ODYSSEA_GLOB_010($dt) {
  //echo "[DEBUG] inside ".__FUNCTION__."\n";
  $ts_coverage = 24*3600; // 24h is the time coverage
  $t = date_parse_from_format("Y-m-d H:i:s", $dt[1]."-".$dt[2]."-".$dt[3]." 12:00:00");
  $e_ts = mktime($t['hour'],$t['minute'],$t['second'],$t['month'],$t['day'],$t['year']);
  $b_ts = $e_ts-$ts_coverage;
/*
  $b_str = strftime("%F %T", $b_ts);
  $e_str = strftime("%F %T", $e_ts);
  echo "[DEBUG] filename = $dt[0]\n";
  echo "[DEBUG] beginDateTime_str = $b_str\n";
  echo "[DEBUG] endDateTime_str   = $e_str\n";
*/
  return array($b_ts,$e_ts);
}

/***********************************************************************
 * IFR-L4-SSTfnd-ODYSSEA-MED_002 => IFR_L4_SSTfnd_ODYSSEA_MED_002
 **********************************************************************/
function datetime_handler_IFR_L4_SSTfnd_ODYSSEA_MED_002($dt) {
  return datetime_handler_IFR_L4_SSTfnd_ODYSSEA_GLOB_010($dt);
}

/***********************************************************************
 * JPL-L4UHfnd-GLOB-MUR => JPL_L4UHfnd_GLOB_MUR
 **********************************************************************/
function datetime_handler_JPL_L4UHfnd_GLOB_MUR($dt) {
  //echo "[DEBUG] inside ".__FUNCTION__."\n";
  $ts_coverage = 24*3600; // 24h is the time coverage
  $t = date_parse_from_format("Y-m-d H:i:s", $dt[1]."-".$dt[2]."-".$dt[3]." 09:00:00");
  $b_ts = mktime($t['hour'],$t['minute'],$t['second'],$t['month'],$t['day'],$t['year']);
  $e_ts = $b_ts+$ts_coverage;
/*
  $b_str = strftime("%F %T", $b_ts);
  $e_str = strftime("%F %T", $e_ts);
  echo "[DEBUG] filename = $dt[0]\n";
  echo "[DEBUG] beginDateTime_str = $b_str\n";
  echo "[DEBUG] endDateTime_str   = $e_str\n";
*/
  return array($b_ts,$e_ts);
}

/***********************************************************************
 * JPL_OUROCEAN-L4UHfnd-GLOB-G1SST => JPL_OUROCEAN_L4UHfnd_GLOB_G1SST
 **********************************************************************/
function datetime_handler_JPL_OUROCEAN_L4UHfnd_GLOB_G1SST($dt) {
  //echo "[DEBUG] inside ".__FUNCTION__."\n";
  $ts_coverage = 24*3600; // 24h is the time coverage
  $t = date_parse_from_format("Y-m-d H:i:s", $dt[1]."-".$dt[2]."-".$dt[3]." 00:00:01");
  $b_ts = mktime($t['hour'],$t['minute'],$t['second'],$t['month'],$t['day'],$t['year']);
  $e_ts = $b_ts+$ts_coverage-2;
/*
  $b_str = strftime("%F %T", $b_ts);
  $e_str = strftime("%F %T", $e_ts);
  echo "[DEBUG] filename = $dt[0]\n";
  echo "[DEBUG] beginDateTime_str = $b_str\n";
  echo "[DEBUG] endDateTime_str   = $e_str\n";
*/
  return array($b_ts,$e_ts);
}

/***********************************************************************
 * MERCI_MER_RR_1P => MERCI_MER_RR_1P
 **********************************************************************/
function datetime_handler_MERCI_MER_RR_1P($dt) {
  //echo "[DEBUG] inside ".__FUNCTION__."\n";
  $ts_coverage = 44*60; // 44m is the time coverage (avg but not sure)
  $t = date_parse_from_format("Y-m-d H:i:s", $dt[1]."-".$dt[2]."-".$dt[3]." ".$dt[4].":".$dt[5].":".$dt[6]);
  $b_ts = mktime($t['hour'],$t['minute'],$t['second'],$t['month'],$t['day'],$t['year']);
  $e_ts = $b_ts+$ts_coverage;
/*
  $b_str = strftime("%F %T", $b_ts);
  $e_str = strftime("%F %T", $e_ts);
  echo "[DEBUG] filename = $dt[0]\n";
  echo "[DEBUG] beginDateTime_str = $b_str\n";
  echo "[DEBUG] endDateTime_str   = $e_str\n";
*/
  return array($b_ts,$e_ts);
}

/***********************************************************************
 * NCDC-SEAWINDS-OW-6hr => NCDC_SEAWINDS_OW_6hr
 **********************************************************************/
function datetime_handler_NCDC_SEAWINDS_OW_6hr($dt) {
  //echo "[DEBUG] inside ".__FUNCTION__."\n";
  $ts_coverage = 24*3600; // 24h is the time coverage
  $t = date_parse_from_format("Y-m-d H:i:s", $dt[1]."-".$dt[2]."-".$dt[3]." 00:00:01");
  $b_ts = mktime($t['hour'],$t['minute'],$t['second'],$t['month'],$t['day'],$t['year']);
  $e_ts = $b_ts+$ts_coverage-2;
/*
  $b_str = strftime("%F %T", $b_ts);
  $e_str = strftime("%F %T", $e_ts);
  echo "[DEBUG] filename = $dt[0]\n";
  echo "[DEBUG] beginDateTime_str = $b_str\n";
  echo "[DEBUG] endDateTime_str   = $e_str\n";
*/
  return array($b_ts,$e_ts);
}

/***********************************************************************
 * NCEP_CFSR1HR => NCEP_CFSR1HR
 **********************************************************************/
function datetime_handler_NCEP_CFSR1HR($dt) {
  //echo "[DEBUG] inside ".__FUNCTION__."\n";
  $t = date_parse_from_format("Y-m-d H:i:s", $dt[1]."-".$dt[2]."-01 00:00:01");
  $b_ts = mktime($t['hour'],$t['minute'],$t['second'],$t['month'],$t['day'],$t['year']);
  $b_str = strftime("%F %T", $b_ts);
  $date = date_create($b_str);
  $date->modify('+1 month -2 secs');
  $e_ts = $date->getTimestamp();
  $e_str = date_format($date, 'Y-m-d H:i:s');
/*
  echo "[DEBUG] filename = $dt[0]\n";
  echo "[DEBUG] beginDateTime_str = $b_str\n";
  echo "[DEBUG] endDateTime_str   = $e_str\n";
*/
  return array($b_ts,$e_ts);
}

/***********************************************************************
 * NCEP_GFS => NCEP_GFS
 **********************************************************************/
function datetime_handler_NCEP_GFS($dt) {
  //echo "[DEBUG] inside ".__FUNCTION__."\n";
  $ts_coverage = 3*3600; // 3h is the time coverage
  $t = date_parse_from_format("Y-m-d H:i:s", $dt[1]."-".$dt[2]."-".$dt[3]." ".$dt[4].":00:01");
  $b_ts = mktime($t['hour'],$t['minute'],$t['second'],$t['month'],$t['day'],$t['year']);
  $e_ts = $b_ts+$ts_coverage-2;
/*
  $b_str = strftime("%F %T", $b_ts);
  $e_str = strftime("%F %T", $e_ts);
  echo "[DEBUG] filename = $dt[0]\n";
  echo "[DEBUG] beginDateTime_str = $b_str\n";
  echo "[DEBUG] endDateTime_str   = $e_str\n";
*/
  return array($b_ts,$e_ts);
}

/***********************************************************************
 * OISST-AVHRR-AMSR-V2 => OISST_AVHRR_AMSR_V2
 **********************************************************************/
function datetime_handler_OISST_AVHRR_AMSR_V2($dt) {
  //echo "[DEBUG] inside ".__FUNCTION__."\n";
  $ts_coverage = 24*3600; // 24h is the time coverage
  $t = date_parse_from_format("Y-m-d H:i:s", $dt[1]."-".$dt[2]."-".$dt[3]." 00:00:01");
  $b_ts = mktime($t['hour'],$t['minute'],$t['second'],$t['month'],$t['day'],$t['year']);
  $e_ts = $b_ts+$ts_coverage-2;
/*
  $b_str = strftime("%F %T", $b_ts);
  $e_str = strftime("%F %T", $e_ts);
  echo "[DEBUG] filename = $dt[0]\n";
  echo "[DEBUG] beginDateTime_str = $b_str\n";
  echo "[DEBUG] endDateTime_str   = $e_str\n";
*/
  return array($b_ts,$e_ts);
}

/***********************************************************************
 * OISST-AVHRR-V2 => OISST-AVHRR-V2
 **********************************************************************/
function datetime_handler_OISST_AVHRR_V2($dt) {
  return datetime_handler_OISST_AVHRR_AMSR_V2($dt);
}

/***********************************************************************
 * OSTIA => OSTIA
 **********************************************************************/
function datetime_handler_OSTIA($dt) {
  //echo "[DEBUG] inside ".__FUNCTION__."\n";
  $ts_coverage = 24*3600; // 24h is the time coverage
  $t = date_parse_from_format("Y-m-d H:i:s", $dt[1]."-".$dt[2]."-".$dt[3]." 00:00:01");
  $b_ts = mktime($t['hour'],$t['minute'],$t['second'],$t['month'],$t['day'],$t['year']);
  $e_ts = $b_ts+$ts_coverage-2;
/*
  $b_str = strftime("%F %T", $b_ts);
  $e_str = strftime("%F %T", $e_ts);
  echo "[DEBUG] filename = $dt[0]\n";
  echo "[DEBUG] beginDateTime_str = $b_str\n";
  echo "[DEBUG] endDateTime_str   = $e_str\n";
*/
  return array($b_ts,$e_ts);
}

/***********************************************************************
 * QSCAT_L2B12 => QSCAT_L2B12
 **********************************************************************/
function datetime_handler_QSCAT_L2B12($dt) {
  //echo "[DEBUG] inside ".__FUNCTION__."\n";
  $t = date_parse_from_format("Y-z H:i:s", $dt[1]."-".($dt[2]-1)." 00:00:01");
  $b_ts = mktime($t['hour'],$t['minute'],$t['second'],$t['month'],$t['day'],$t['year']);
  $t = date_parse_from_format("Y-z H:i:s", $dt[1]."-".($dt[2]-1)." 23:59:59");
  $e_ts = mktime($t['hour'],$t['minute'],$t['second'],$t['month'],$t['day'],$t['year']);
/*
  $b_str = strftime("%F %T", $b_ts);
  $e_str = strftime("%F %T", $e_ts);
  echo "[DEBUG] filename = $dt[0]\n";
  echo "[DEBUG] beginDateTime_str = $b_str\n";
  echo "[DEBUG] endDateTime_str   = $e_str\n";
*/
  return array($b_ts,$e_ts);
}

/***********************************************************************
 * SSMI => SSMI
 **********************************************************************/
function datetime_handler_SSMI($dt) {
  //echo "[DEBUG] inside ".__FUNCTION__."\n";
  $ts_coverage = 24*3600; // 24h is the time coverage
  $t = date_parse_from_format("Y-m-d H:i:s", $dt[1]."-".$dt[2]."-".$dt[3]." 00:00:01");
  $b_ts = mktime($t['hour'],$t['minute'],$t['second'],$t['month'],$t['day'],$t['year']);
  $e_ts = $b_ts+$ts_coverage-2;

  $b_str = strftime("%F %T", $b_ts);
  $e_str = strftime("%F %T", $e_ts);
/*
  echo "[DEBUG] filename = $dt[0]\n";
  echo "[DEBUG] beginDateTime_str = $b_str\n";
  echo "[DEBUG] endDateTime_str   = $e_str\n";
*/
  return array($b_ts,$e_ts);
}
?>
