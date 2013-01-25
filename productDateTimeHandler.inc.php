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
use pgh;

/***********************************************************************
 * AQUARIUS_L3_SSS_SMI_7DAY => AQUARIUS_L3_SSS_SMI_7DAY
 **********************************************************************/
function datetime_handler_AQUARIUS_L3_SSS_SMI_7DAY(&$file) {
  bunzip_dataset($file->path,$hdf5_filename);
  h5dump_xml($hdf5_filename,  $xml_filename);

  $xml = new SimpleXMLElement(str_replace('xmlns=', 'ns=', file_get_contents($xml_filename)));
  unlink($hdf5_filename);
  unlink($xml_filename);
  
  $datetime_str = trim($xml->xpath('/HDF5-File/RootGroup/Attribute[@Name="Start Time"]/Data/DataFromFile')[0]);
  preg_match('/"(\d{4})(\d{3})(\d{2})(\d{2})(\d{2})(\d{3})"/',$datetime_str, $dt);
  $t = date_parse_from_format("Y-z H:i:s.u", $dt[1]."-".($dt[2])." ".($dt[3]).":".($dt[4]).":".($dt[5]).".".($dt[6]));
  $start_datetime_ts = mktime($t['hour'],$t['minute'],$t['second'],$t['month'],$t['day'],$t['year']);
  $start_datetime_str = strftime("%F %T", $start_datetime_ts);

  $datetime_str = trim($xml->xpath('/HDF5-File/RootGroup/Attribute[@Name="End Time"]/Data/DataFromFile')[0]);
  preg_match('/"(\d{4})(\d{3})(\d{2})(\d{2})(\d{2})(\d{3})"/',$datetime_str, $dt);
  $t = date_parse_from_format("Y-z H:i:s.u", $dt[1]."-".($dt[2])." ".($dt[3]).":".($dt[4]).":".($dt[5]).".".($dt[6]));
  $stop_datetime_ts = mktime($t['hour'],$t['minute'],$t['second'],$t['month'],$t['day'],$t['year']);
  $stop_datetime_str = strftime("%F %T", $stop_datetime_ts);

  $file->setDatetimeInterval($start_datetime_str,$stop_datetime_str);
  return true;
}

/***********************************************************************
 * ASCAT-L2-12km => ASCAT_L2_12km
 **********************************************************************/
function datetime_handler_ASCAT_L2_12km(&$file) {
  gunzip_dataset($file->path,$nc_filename);
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

  $file->setDatetimeInterval($start_datetime_str,$stop_datetime_str);
  return true;
}

/***********************************************************************
 * ASCAT-L2-Coastal => ASCAT_L2_Coastal
 **********************************************************************/
function datetime_handler_ASCAT_L2_Coastal(&$file) {
  return datetime_handler_ASCAT_L2_12km($file);
}

/***********************************************************************
 * ASI-AMSRE => ASI_AMSRE
 **********************************************************************/
function datetime_handler_ASI_AMSRE(&$file) {
  if (!\pgh\File::extract_datetime_array_from_filepath($file, $dt)) return false;

  $t = date_parse_from_format("Y-m-d H:i:s", $dt[1]."-".$dt[2]."-".$dt[3]." 00:00:00");
  $start_datetime_ts = mktime($t['hour'],$t['minute'],$t['second'],$t['month'],$t['day'],$t['year']);
  $start_datetime_str = strftime("%F %T", $start_datetime_ts);
  $t = date_parse_from_format("Y-m-d H:i:s", $dt[1]."-".$dt[2]."-".$dt[3]." 23:59:59");
  $stop_datetime_ts = mktime($t['hour'],$t['minute'],$t['second'],$t['month'],$t['day'],$t['year']);
  $stop_datetime_str = strftime("%F %T", $stop_datetime_ts);

  $file->setDatetimeInterval($start_datetime_str,$stop_datetime_str);
  return true;
}

/***********************************************************************
 * AVHRR_Pathfinder-NODC-L3C-v5.2 => AVHRR_Pathfinder_NODC_L3C_v5_2
 **********************************************************************/
function datetime_handler_AVHRR_Pathfinder_NODC_L3C_v5_2(&$file) {
  ncdump_xml($file->path, $xml_filename);

  $xml = new SimpleXMLElement(str_replace('xmlns=', 'ns=', file_get_contents($xml_filename)));
  unlink($xml_filename);

  $start_datetime_str = (string)$xml->xpath('/netcdf/attribute[@name="time_coverage_start"]')[0]['value'];
  $stop_datetime_str = (string)$xml->xpath('/netcdf/attribute[@name="time_coverage_end"]')[0]['value'];

  $file->setDatetimeInterval($start_datetime_str,$stop_datetime_str);
  return true;
}

/***********************************************************************
 * AVISO_DT_REF_MADT_MERGED_H => AVISO_DT_REF_MADT_MERGED_H
 **********************************************************************/
function datetime_handler_AVISO_DT_REF_MADT_MERGED_H(&$file) {
  if (!\pgh\File::extract_datetime_array_from_filepath($file, $dt)) return false;

  $t = date_parse_from_format("Y-m-d H:i:s", $dt[1]."-".$dt[2]."-".$dt[3]." 00:00:00");
  $start_datetime_ts = mktime($t['hour'],$t['minute'],$t['second'],$t['month'],$t['day'],$t['year']);
  $start_datetime_str = strftime("%F %T", $start_datetime_ts);
  $ts_coverage = 7*24*3600; // 7days is the time coverage
  $stop_datetime_ts = $start_datetime_ts+$ts_coverage-1;
  $stop_datetime_str = strftime("%F %T", $stop_datetime_ts);

  $file->setDatetimeInterval($start_datetime_str,$stop_datetime_str);
  return true;
}

/***********************************************************************
 * AVISO_DT_REF_MADT_MERGED_UV => AVISO_DT_REF_MADT_MERGED_UV
 **********************************************************************/
function datetime_handler_AVISO_DT_REF_MADT_MERGED_UV(&$file) {
  //echo "[DEBUG] inside ".__FUNCTION__."\n";
  return datetime_handler_AVISO_DT_REF_MADT_MERGED_H($file);
}

/***********************************************************************
 * AVISO_NRT_MADT_MERGED_H => AVISO_NRT_MADT_MERGED_H
 **********************************************************************/
function datetime_handler_AVISO_NRT_MADT_MERGED_H(&$file) {
  if (!\pgh\File::extract_datetime_array_from_filepath($file, $dt)) return false;

  $t = date_parse_from_format("Y-m-d H:i:s", $dt[1]."-".$dt[2]."-".$dt[3]." 00:00:00");
  $start_datetime_ts = mktime($t['hour'],$t['minute'],$t['second'],$t['month'],$t['day'],$t['year']);
  $start_datetime_str = strftime("%F %T", $start_datetime_ts);
  $ts_coverage = 24*3600; // 1 day is the time coverage
  $stop_datetime_ts = $start_datetime_ts+$ts_coverage-1;
  $stop_datetime_str = strftime("%F %T", $stop_datetime_ts);
  
  $file->setDatetimeInterval($start_datetime_str,$stop_datetime_str);
  return true;
}

/***********************************************************************
 * AVISO_NRT_MADT_MERGED_UV => AVISO_NRT_MADT_MERGED_UV
 **********************************************************************/
function datetime_handler_AVISO_NRT_MADT_MERGED_UV(&$file) {
  //echo "[DEBUG] inside ".__FUNCTION__."\n";
  return datetime_handler_AVISO_NRT_MADT_MERGED_H($file);
}

/***********************************************************************
 * AVISO_NRT_MSWH_MERGED => AVISO_NRT_MSWH_MERGED
 **********************************************************************/
function datetime_handler_AVISO_NRT_MSWH_MERGED(&$file) {
  //echo "[DEBUG] inside ".__FUNCTION__."\n";
  return datetime_handler_AVISO_NRT_MADT_MERGED_H($file);
}

/***********************************************************************
 * AVISO_NRT_MWIND_MERGED => AVISO_NRT_MWIND_MERGED
 **********************************************************************/
function datetime_handler_AVISO_NRT_MWIND_MERGED(&$file) {
  //echo "[DEBUG] inside ".__FUNCTION__."\n";
  return datetime_handler_AVISO_NRT_MADT_MERGED_H($file);
}

/***********************************************************************
 * ERSST_V3B => ERSST_V3B
 **********************************************************************/
function datetime_handler_ERSST_V3B(&$file) {
  if (!\pgh\File::extract_datetime_array_from_filepath($file, $dt)) return false;

  $t = date_parse_from_format("Y-m-d H:i:s", $dt[1]."-".$dt[2]."-01 00:00:00");
  $start_datetime_ts = mktime($t['hour'],$t['minute'],$t['second'],$t['month'],$t['day'],$t['year']);
  $start_datetime_str = strftime("%F %T", $start_datetime_ts);
  $stop_datetime = date_create($start_datetime_str);
  $stop_datetime->modify('+1 month -1 sec');
  $stop_datetime_ts = $stop_datetime->getTimestamp();
  $stop_datetime_str = strftime("%F %T", $stop_datetime_ts);
  
  $file->setDatetimeInterval($start_datetime_str,$stop_datetime_str);
  return true;
}

/***********************************************************************
 * EUR-L2P-AVHRR_METOP_A => EUR_L2P_AVHRR_METOP_A
 **********************************************************************/
 function datetime_handler_EUR_L2P_AVHRR_METOP_A(&$file) {
  bunzip_dataset_partial($file->path,$nc_filename,8192);
  ncdump_xml($nc_filename, $xml_filename);

  $xml = new SimpleXMLElement(str_replace('xmlns=', 'ns=', file_get_contents($xml_filename)));
  unlink($nc_filename);
  unlink($xml_filename);

  $start_date_str = (string)$xml->xpath('/netcdf/attribute[@name="start_date"]')[0]['value'];
  $start_time_str = (string)$xml->xpath('/netcdf/attribute[@name="start_time"]')[0]['value'];
  $stop_date_str = (string)$xml->xpath('/netcdf/attribute[@name="stop_date"]')[0]['value'];
  $stop_time_str = (string)$xml->xpath('/netcdf/attribute[@name="stop_time"]')[0]['value'];
  $start_datetime_str = substr($start_date_str,0,-4)." ".substr($start_time_str,0,-4);
  $stop_datetime_str = substr($stop_date_str,0,-4)." ".substr($stop_time_str,0,-4);

  $file->setDatetimeInterval($start_datetime_str,$stop_datetime_str);
  return true;
}

/***********************************************************************
 * IFR-L4-SSTfnd-ODYSSEA-GLOB_010 => IFR_L4_SSTfnd_ODYSSEA_GLOB_010
 **********************************************************************/
function datetime_handler_IFR_L4_SSTfnd_ODYSSEA_GLOB_010(&$file) {
  bunzip_dataset_partial($file->path,$nc_filename,8192);
  ncdump_xml($nc_filename, $xml_filename);

  $xml = new SimpleXMLElement(str_replace('xmlns=', 'ns=', file_get_contents($xml_filename)));
  unlink($nc_filename);
  unlink($xml_filename);


  $start_datetime_str = (string)$xml->xpath('/netcdf/attribute[@name="start_time"]')[0]['value'];
  $start_datetime_str = strftime("%F %T",strtotime ($start_datetime_str));
  $stop_datetime_str = (string)$xml->xpath('/netcdf/attribute[@name="start_time"]')[0]['value'];
  $stop_datetime_str = strftime("%F %T",strtotime ($stop_datetime_str));

  $file->setDatetimeInterval($start_datetime_str,$stop_datetime_str);
  return true;
}

/***********************************************************************
 * IFR-L4-SSTfnd-ODYSSEA-MED_002 => IFR_L4_SSTfnd_ODYSSEA_MED_002
 **********************************************************************/
function datetime_handler_IFR_L4_SSTfnd_ODYSSEA_MED_002(&$file) {
  return datetime_handler_IFR_L4_SSTfnd_ODYSSEA_GLOB_010($file);
}

/***********************************************************************
 * JPL-L4UHfnd-GLOB-MUR => JPL_L4UHfnd_GLOB_MUR
 **********************************************************************/
function datetime_handler_JPL_L4UHfnd_GLOB_MUR(&$file) {
  bunzip_dataset_partial($file->path,$nc_filename,8192);
  ncdump_xml($nc_filename, $xml_filename);
  //pgh\debug("[DEBUG] nc_filename = $nc_filename");
  //pgh\debug("[DEBUG] xml_filename = $xml_filename");

  $xml = new SimpleXMLElement(str_replace('xmlns=', 'ns=', file_get_contents($xml_filename)));
  unlink($nc_filename);
  unlink($xml_filename);

  $start_date_str = (string)$xml->xpath('/netcdf/attribute[@name="start_date"]')[0]['value'];
  $start_time_str = (string)$xml->xpath('/netcdf/attribute[@name="start_time"]')[0]['value'];
  $stop_date_str = (string)$xml->xpath('/netcdf/attribute[@name="stop_date"]')[0]['value'];
  $stop_time_str = (string)$xml->xpath('/netcdf/attribute[@name="stop_time"]')[0]['value'];
  $start_datetime_str = $start_date_str." ".substr($start_time_str,0,-4);

  $stop_datetime = date_create($start_datetime_str);
  $stop_datetime->modify('+1 day -1 sec');
  $stop_datetime_ts = $stop_datetime->getTimestamp();
  $stop_datetime_str = strftime("%F %T", $stop_datetime_ts);

  $file->setDatetimeInterval($start_datetime_str,$stop_datetime_str);
  return true;
}

/***********************************************************************
 * JPL_OUROCEAN-L4UHfnd-GLOB-G1SST => JPL_OUROCEAN_L4UHfnd_GLOB_G1SST
 **********************************************************************/
function datetime_handler_JPL_OUROCEAN_L4UHfnd_GLOB_G1SST(&$file) {
  return datetime_handler_JPL_L4UHfnd_GLOB_MUR($file);
}

/***********************************************************************
 * MERCI_MER_RR_1P => MERCI_MER_RR_1P
 **********************************************************************/
function datetime_handler_MERCI_MER_RR_1P(&$file) {
  gunzip_dataset_partial($file->path,$n1_filename, 480);
  $handle = fopen($n1_filename, "rb");
  fseek($handle, hexdec("0x15F"), SEEK_SET);
  $start_datetime_str = fread($handle, 27);
  fseek($handle, hexdec("0x18A"), SEEK_SET);
  $stop_datetime_str = fread($handle, 27);
  fclose($handle);
  unlink($n1_filename);

  $start_datetime_str = strftime("%F %T",strtotime ($start_datetime_str));
  $stop_datetime_str = strftime("%F %T",strtotime ($stop_datetime_str));

  $file->setDatetimeInterval($start_datetime_str,$stop_datetime_str);
  return true;
}

/***********************************************************************
 * NCDC-SEAWINDS-OW-6hr => NCDC_SEAWINDS_OW_6hr
 **********************************************************************/
function datetime_handler_NCDC_SEAWINDS_OW_6hr(&$file) {
  ncdump_xml($file->path, $xml_filename);
  //debug("[DEBUG] xml_filename = $xml_filename\n");

  $xml = new SimpleXMLElement(str_replace('xmlns=', 'ns=', file_get_contents($xml_filename)));
  unlink($xml_filename);

  $start_date_str = (string)$xml->xpath('/netcdf/attribute[@name="Data_Calendar_Date"]')[0]['value'];
  $start_datetime_str = $start_date_str." 00:00:00";
  $stop_datetime_str =  $start_date_str." 23:59:59";

  $file->setDatetimeInterval($start_datetime_str,$stop_datetime_str);
  return true;
}

/***********************************************************************
 * NCEP_CFSR1HR => NCEP_CFSR1HR
 **********************************************************************/
function datetime_handler_NCEP_CFSR1HR(&$file) {
  if (!\pgh\File::extract_datetime_array_from_filepath($file, $dt)) return false;

  $t = date_parse_from_format("Y-m-d H:i:s", $dt[1]."-".$dt[2]."-01 00:00:00");
  $start_datetime_ts = mktime($t['hour'],$t['minute'],$t['second'],$t['month'],$t['day'],$t['year']);
  $start_datetime_str = strftime("%F %T", $start_datetime_ts);  
  $stop_datetime = date_create($start_datetime_str);
  $stop_datetime->modify('+1 month -1 sec');
  $stop_datetime_ts = $stop_datetime->getTimestamp();
  $stop_datetime_str = strftime("%F %T", $stop_datetime_ts);
  
  $file->setDatetimeInterval($start_datetime_str,$stop_datetime_str);
  return true;
}

/***********************************************************************
 * NCEP_GFS => NCEP_GFS
 **********************************************************************/
function datetime_handler_NCEP_GFS(&$file) {
  if (!\pgh\File::extract_datetime_array_from_filepath($file, $dt)) return false;

  $t = date_parse_from_format("Y-m-d H:i:s", $dt[1]."-".$dt[2]."-".$dt[3]." ".$dt[4].":00:00");
  $start_datetime_ts = mktime($t['hour'],$t['minute'],$t['second'],$t['month'],$t['day'],$t['year']);
  $start_datetime_str = strftime("%F %T", $start_datetime_ts);  
  $stop_datetime = date_create($start_datetime_str);
  $stop_datetime->modify('+6 hours -1 sec');
  $stop_datetime_ts = $stop_datetime->getTimestamp();
  $stop_datetime_str = strftime("%F %T", $stop_datetime_ts);

  $file->setDatetimeInterval($start_datetime_str,$stop_datetime_str);
  return true;
}

/***********************************************************************
 * OISST-AVHRR-AMSR-V2 => OISST_AVHRR_AMSR_V2
 **********************************************************************/
function datetime_handler_OISST_AVHRR_AMSR_V2(&$file) {
  if (!\pgh\File::extract_datetime_array_from_filepath($file, $dt)) return false;

  $t = date_parse_from_format("Y-m-d H:i:s", $dt[1]."-".$dt[2]."-".$dt[3]." 00:00:00");
  $start_datetime_ts = mktime($t['hour'],$t['minute'],$t['second'],$t['month'],$t['day'],$t['year']);
  $start_datetime_str = strftime("%F %T", $start_datetime_ts);  
  $stop_datetime = date_create($start_datetime_str);
  $stop_datetime->modify('+24 hours -1 sec');
  $stop_datetime_ts = $stop_datetime->getTimestamp();
  $stop_datetime_str = strftime("%F %T", $stop_datetime_ts);

  $file->setDatetimeInterval($start_datetime_str,$stop_datetime_str);
  return true;
}

/***********************************************************************
 * OISST-AVHRR-V2 => OISST-AVHRR-V2
 **********************************************************************/
function datetime_handler_OISST_AVHRR_V2(&$file) {
  return datetime_handler_OISST_AVHRR_AMSR_V2($file);
}

/***********************************************************************
 * OSTIA => OSTIA
 **********************************************************************/
function datetime_handler_OSTIA(&$file) {
  bunzip_dataset_partial($file->path,$nc_filename,8192);
  ncdump_xml($nc_filename, $xml_filename);
  //debug("[DEBUG] nc_filename = $nc_filename\n");
  //debug("[DEBUG] xml_filename = $xml_filename\n");
  
  $xml = new SimpleXMLElement(str_replace('xmlns=', 'ns=', file_get_contents($xml_filename)));
  unlink($nc_filename);
  unlink($xml_filename);

  $start_date_str = (string)$xml->xpath('/netcdf/attribute[@name="start_date"]')[0]['value'];
  $start_time_str = (string)$xml->xpath('/netcdf/attribute[@name="start_time"]')[0]['value'];
  $stop_date_str = (string)$xml->xpath('/netcdf/attribute[@name="stop_date"]')[0]['value'];
  $stop_time_str = (string)$xml->xpath('/netcdf/attribute[@name="stop_time"]')[0]['value'];
  $start_datetime_str = substr($start_date_str,0,-4)." 00:00:00";
  $stop_datetime_str =  substr($start_date_str,0,-4)." 23:59:59";

  $file->setDatetimeInterval($start_datetime_str,$stop_datetime_str);
  return true;
}

/***********************************************************************
 * QSCAT_L2B12 => QSCAT_L2B12
 **********************************************************************/
function datetime_handler_QSCAT_L2B12(&$file) {
  gunzip_dataset($file->path,$hdf_filename);
  $hdp_filename = tempnam(sys_get_temp_dir(), 'pgh_hdp_');
  $command = "hdp dumpsds -h ".$hdf_filename." > ".$hdp_filename;
  $exec_output = array();
  $exec_return_val = 0;
  exec($command, $output, $return_val);

  //debug("[DEBUG] hdf_filename = $hdf_filename\n");
  //debug("[DEBUG] hdp_filename = $hdp_filename\n");

  $content = file_get_contents($hdp_filename);
  unlink($hdf_filename);
  unlink($hdp_filename);

  if (preg_match("/RangeBeginningDate\s+.*\s+.*\s.*(\d{4})-(\d{3})/",$content, $dt1) &&
      preg_match("/RangeBeginningTime\s+.*\s+.*\s.*(\d{2}):(\d{2}):(\d{2})\.(\d{3})/",$content, $dt2) &&
      preg_match("/RangeEndingDate\s+.*\s+.*\s.*(\d{4})-(\d{3})/",$content, $dt3) &&
      preg_match("/RangeEndingTime\s+.*\s+.*\s.*(\d{2}):(\d{2}):(\d{2})\.(\d{3})/",$content, $dt4)) {

    $t = date_parse_from_format("Y-z H:i:s", $dt1[1]."-".$dt1[2]." ".$dt2[1].":".$dt2[2].":".$dt2[3]);
    $start_datetime_ts = mktime($t['hour'],$t['minute'],$t['second'],$t['month'],$t['day'],$t['year']);
    $start_datetime_str = strftime("%F %T", $start_datetime_ts);  
    $t = date_parse_from_format("Y-z H:i:s", $dt3[1]."-".$dt3[2]." ".$dt4[1].":".$dt4[2].":".$dt4[3]);
    $stop_datetime_ts = mktime($t['hour'],$t['minute'],$t['second'],$t['month'],$t['day'],$t['year']);
    $stop_datetime_str = strftime("%F %T", $stop_datetime_ts);  
    //debug("[DEBUG] start_datetime_str = $start_datetime_str\n");
    //debug("[DEBUG] stop_datetime_str = $stop_datetime_str\n");

    $file->setDatetimeInterval($start_datetime_str,$stop_datetime_str);
    return true;

  } else return false;
}

/***********************************************************************
 * SSMI => SSMI
 **********************************************************************/
function datetime_handler_SSMI(&$file) {
  if (!\pgh\File::extract_datetime_array_from_filepath($file, $dt)) return false;

  $t = date_parse_from_format("Y-m-d H:i:s", $dt[1]."-".$dt[2]."-".$dt[3]." 00:00:00");
  $start_datetime_ts = mktime($t['hour'],$t['minute'],$t['second'],$t['month'],$t['day'],$t['year']);
  $start_datetime_str = strftime("%F %T", $start_datetime_ts);  
  $stop_datetime = date_create($start_datetime_str);
  $stop_datetime->modify('+24 hours -1 sec');
  $stop_datetime_ts = $stop_datetime->getTimestamp();
  $stop_datetime_str = strftime("%F %T", $stop_datetime_ts);

  $file->setDatetimeInterval($start_datetime_str,$stop_datetime_str);
  return true;
}
?>
