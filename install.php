<?php
// Update cdr database with did field
$sql = "SELECT did FROM asteriskcdrdb.cdr";
$confs = $db->getRow($sql, DB_FETCHMODE_ASSOC);
if (DB::IsError($confs)) { // no error... Already there
  out("Adding did field to cdr");
  out("This might take a while......");
  $sql = "ALTER TABLE asteriskcdrdb.cdr ADD did VARCHAR ( 20 ) NOT NULL DEFAULT ''";
  $results = $db->query($sql);
  if(DB::IsError($results)) {
    die($results->getMessage());
  }
  out("Added field did to cdr");
}
