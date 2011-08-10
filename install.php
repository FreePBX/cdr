<?php
//This file is part of FreePBX.
//
//    FreePBX is free software: you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation, either version 2 of the License, or
//    (at your option) any later version.
//
//    FreePBX is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You should have received a copy of the GNU General Public License
//    along with FreePBX.  If not, see <http://www.gnu.org/licenses/>.
//
//    cdr module for FreePBX 2.7+
//    Copyright (C) Mikael Carlsson
//
// Update cdr database with did field
//
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
