<?php
/* Linked Open Graph
   Copyright (C) 2017 DISIT Lab http://www.disit.org - University of Florence

   This program is free software: you can redistribute it and/or modify
   it under the terms of the GNU Affero General Public License as
   published by the Free Software Foundation, either version 3 of the
   License, or (at your option) any later version.

   This program is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU Affero General Public License for more details.

   You should have received a copy of the GNU Affero General Public License
   along with this program.  If not, see <http://www.gnu.org/licenses/>. */

//Checks if the user is autenticated
include_once 'admin-class.php';
$admin = new itg_admin();
/* Saves the change in the endpoint table. */
$count=0;
global $db;
var_dump($_POST);
foreach($_POST as $rows) {
	// var_dump($rows);
	foreach($rows as $row) {
		//Saves the graphs as default.
		
		if($row['deleted']){
			$delete_query="DELETE FROM endpoints WHERE endpoints.url='$row[deleted]'";
			var_dump($db->query($delete_query));
		}
		if($row['modified']) {
			//updates the table.
			$update_query="UPDATE endpoints SET ";
			foreach($row as $prop=>$value){
				if($prop!="modified") {
					if($prop!="order" && $prop!="active" ) $update_query.=" endpoints.$prop='$value' ,";
					else $update_query.=" endpoints.$prop=$value ,";
				}
			}
			$count++;
			$update_query=substr($update_query,0,-1);
			$update_query.="WHERE url='$row[modified]'";
			// var_dump($update_query);
			var_dump($db->query($update_query));
		}
		if($row['add']) {
			$insert_query="INSERT INTO endpoints ";
			$properties=" (";
			$values=" VALUES (";
			foreach($row as $prop=>$value){
				if($prop!="add"){
					$properties.=" endpoints.$prop ,";
					if($prop!="order" && $prop!="active" ) $values.=" '$value' ,";
					else $values.=" $value ,";
				}
			}
			$count++;
			
			$properties = substr($properties,0,-1);
			$properties.=")";
			$values=substr($values,0,-1);
			$values.=")";
			// var_dump($properties);
			// var_dump($values);
			$insert_query.=$properties.$values;
			var_dump($db->query($insert_query));
		}
	}
}
if( $db->captured_errors ) var_dump( $db->captured_errors );
// var_dump($_POST);


header("location: index.php");
die();

?>