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

include_once 'admin-class.php';
$admin = new itg_admin();
$admin->_authenticate();


global $db;
if($_GET['examples'] != ''){
/* function for get the examples from the DB. */
	// var_dump($_GET['examples']);
	$query_for_example="SELECT g.title , g.id , g.mail , g.initial_uri , g.readwrite_id FROM graph as g WHERE g.sparql_endpoint='$_GET[examples]' ORDER BY g.timestamp DESC ";
	$examples=$db->get_results($query_for_example);
	if(!$examples)echo "[]";
	else echo json_encode($examples);
}
?>