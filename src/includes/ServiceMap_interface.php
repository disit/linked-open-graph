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

require( "../config.php" );
global $suggestion_number;
global $endpoint_with_suggest;
require( "sparqllib.php" );
$result;
if($_POST['elements']){
// if($_GET['elements']){
	
	$elements=$_POST['elements'];
	// $elements=$_GET['elements'];
	$elements=stripslashes ($_POST['elements']);
	// $elements=stripslashes ($_GET['elements']);
	// var_dump(json_decode($elements));
	$elements=json_decode($elements);
	//Makes the query for retrieve which of this ep is a place.
	$query="";
	// $db = sparql_connect( $server );
	$structure=array();
	$j=0; //Counts the number of results
	
	foreach($elements as $e){
		// var_dump($e);
		// $db = sparql_connect( $e.);
		//if($db = sparql_connect( $e.ep );)
		//Sets the connection to the endpoint of the node checked.
		if($e->ep) $server = urldecode($e->ep);
		else $server="http://192.168.0.205:8080/openrdf-sesame/repositories/km4city36";//For test only siiMobi.
		// else $server="http://servicemap.disit.org/openrdf-sesame/repositories/km4city36";//XXX To change with the line above. THis is for external requests. For test only Km4City.
	
		//XXX Maybe there we could check if the connection is the same as the previous object for avoid to make sparql_connect and db->alive
		$db = sparql_connect( $server );
		if( $db->alive() ){
			/* Only for lat e long in w3c. 
			List of possible properties to search:
			KM4CITY36 - DBPEDIA:  http://www.w3.org/2003/01/geo/wgs84_pos#lat - http://www.w3.org/2003/01/geo/wgs84_pos#long
			DBPEDIA : geo:geometry
			*/
			$query="SELECT DISTINCT ?lat ?long WHERE{ <".urldecode($e->uri) ."> <http://www.w3.org/2003/01/geo/wgs84_pos#lat> ?lat. <".urldecode($e->uri) ."> <http://www.w3.org/2003/01/geo/wgs84_pos#long> ?long }"; 
			// $query="SELECT ?lat ?long WHERE{ <".urldecode($e->uri) ."> <http://www.w3.org/2003/01/geo/wgs84_pos#lat> ?lat }"; 
			// var_dump($query);
			$result = sparql_query( $query );
			// var_dump(sparql_query( $query ));
			// var_dump($result);
			if($result){
				// var_dump($result);
				while( $row = sparql_fetch_array( $result ) )
				{
					// var_dump($row);
					$structure[$j]['uri']=$e->uri;
					if($row['lat']) $structure[$j]['lat']=$row['lat'];
					if($row['long']) $structure[$j]['long']=$row['long'];
					$j++;
					break;//XXX To check how, in some cases, the query returns all the combination of lat and long with different type (float, decimal)
				}
			}
		}
	}
	
	// print"Structure"; var_dump($structure);
	if($structure){
		//Makes a redirect to one of this page sending the json of the structure
		// servicemap.disit.org/WebAppGrafo/mappa.jsp
		// servicemap.disit.org/WebAppGrafo/json/get-info-from-uri.jsp
		echo JSON_ENCODE($structure);
	}
	else{
		$error->error="No result found";
		echo JSON_ENCODE($error);
	}
}


?>
