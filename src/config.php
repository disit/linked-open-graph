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

////File for configure the graph, the querys and the visualization.
//Include or not the inverse relations.
global $inverse_relations;

$inverse_relations=true;

$results_n=5; //Number of results sends to client.

$count_threshold=5; //Number of elements for the count query. It's used for make a different query for properties that have more than $count_threshold values.

$max_result_number=10000; //The default max number of results for a query( If the endpoint isn't in the default list).

$time_in_cache=(86400*1);//The time, in seconds, to maintain the data in the cache. 86400 = seconds in 1 hour.

$num_classes=10000;//Number of classes to show in the field 'Choose a class'.

$label_lang='en';//Lang for the label to display.

/* $user_agent is an array of name, or part of name, of the user agents that haven't to be saved.*/
$user_agent_banned=array(
	'bot' , 'Bot' , 'BOT' , 'Spider' , 'spider'
);

$search_more_result_number=100;//Number of result in a more search.

$OD_config='mixed';/*Configuration for OD. Values:'noresult','only','mixed'. 'noresult' for search when sparql returns nothing.
'only' for search only with OD, 'mixed' for merge results with sparql. Leave it empty if you not want to use it.*/
//$configuration contain the configuration for the name and the icons for the buttons of the graph.
$configuration=array(
  'graph_title'=>"Linked Open Graph",
  'buttons'=>array(
    'images/buttons/axrelations_back_1.png',
    'images/buttons/axrelations_back_2.png',
    'images/buttons/axrelations_back_3.png',
    'images/buttons/ajax-loader-big.gif',
    'images/buttons/axrelations_help.png',
    'images/buttons/axrelations_recenter.png',
    'images/buttons/axrelations_zoom_in.png',
    'images/buttons/axrelations_zoom_reset.png',
    'images/buttons/axrelations_zoom_out.png',
    'images/buttons/axrelations_fullscreen.png',
    'images/buttons/axrelations_reduce.png',
    'images/buttons/plus.png',
    'images/buttons/minus.png',
	'images/buttons/axrelations_save.png',
	'images/buttons/unlock_nodes.png',
	'images/buttons/X_icon.png',
	'images/buttons/default.png',
	'images/buttons/map_marker.png'
  ),
);

/*The start relations with the specifics for the checkbox in the bottom menu of the graph.
Every item of $relations has:
type_name = the name of the type. name=the name to display at the right of the checkbutton. checked= the value of the checkbox.
checked could be: false, if the corrispective checkbutton has to be not checked, true if it has to be checked and 
close if the checkbutton has to be checked but the relation in the graph collapsed.
*/
$relations=array(
  'relations'=>array(
    array( 'type_name'=>"http://xmlns.com/foaf/0.1/depiction", 'name'=>"foaf:depiction", 'checked'=>"false" ),
    array( 'type_name'=>"http://www.w3.org/2002/07/owl#sameAs", 'name'=>"owl:sameAs", 'checked'=>"false" ),
    array( 'type_name'=>"http://www.w3.org/2000/01/rdf-schema#seeAlso", 'name'=>"rdfs:seeAlso", 'checked'=>"close" ),
  )
);

/*Icons to combine with the types. Every item in image_for_type has two attributes: 
type_name =the name of the attribute.
image= the name of the image, with its extension, that must be located in the folder spqlgraph\images\icons.
*/
$image_for_type=array(
  'http://schema.org/Person'=>"user.png", 
  'bnode'=>"BlankNodes.png",
  'http://www.cloudicaro.it/cloud_ontology/core#HostMachine'=>"hostmachine.jpg",
  'http://www.cloudicaro.it/cloud_ontology/core#VirtualMachine'=>"virtualmachine.png",
  'http://www.cloudicaro.it/cloud_ontology/core#WebServer'=>"webserver.jpg",
  'http://www.cloudicaro.it/cloud_ontology/core#DataCenter'=>"datacenter.jpg",
  'http://www.cloudicaro.it/cloud_ontology/core#LinuxOS'=>"linux.jpg",
  'http://www.cloudicaro.it/cloud_ontology/core#LocalNetwork'=>"network.png",
  'http://www.cloudicaro.it/cloud_ontology/core#MySQL'=>"mysql.png",
  'http://purl.org/ontology/bibo/Document'=>"article.jpg",
 /* 'http://vocab.getty.edu/language/en */
 /* 'http://www.cloudicaro.it/cloud_ontology/core#ubuntu'=>"ubuntu.jpg", */
  'http://linkeddata.comune.fi.it:8080/resource/data/musei'=>"museifirenze.jpg",
  'http://linkeddata.comune.fi.it:8080/resource/data/toponomastica'=>"toponomasticafirenze.jpg",
  'http://www.disit.dinfo.unifi.it/SiiMobility#StreetNumber'=>"toponomasticafirenze.jpg",
  'http://www.disit.dinfo.unifi.it/SiiMobility#Road'=>"road.jpg",
  'http://dbpedia.org/ontology/Place'=>"place.png",
  'http://www.dsi.unifi.it/CMSAteneoCompetencel#Department'=>"department.jpg",
  'http://www.dsi.unifi.it/CMSAteneoCompetence#Laboratory'=>"lab.jpg",
  'http://www.dsi.unifi.it/CMSAteneoCompetence#Course'=>"course.gif",
  'http://www.disit.dinfo.unifi.it/SiiMobility#Municipality'=>"place.png" ,
  'http://dbpedia.org/property/hasPhotoCollection'=>"gallery_icon.jpg" ,
  'http://xmlns.com/foaf/0.1/Organization'=>"memberhubtree.png",
  'http://xmlns.com/foaf/0.1/Document'=>"article.jpg",
  'http://www.w3.org/2000/10/swap/pim/contact#Person'=>"eclapuser.png",
  'http://www.w3.org/2002/07/owl#Class'=>"w3cdonottrack.png",
  
);
/*
Image for sub strings of type.
*/
$image_for_substring=array(
/*	'www.disit.dinfo.unifi.it'=>"disit.jpg", */
/*	'www.w3.org'=>"w3cdonottrack.png", */
	'urn:u-gov:unifi:RI_PRD'=>"article.jpg",
);

//email to display for the sender.
$email_sender="info@disit.org";
//Database reference
global $db_username, $db_psw, $db_host, $db_schema;
$db_host="localhost";
$db_username="root";
$db_psw="kodekode";
$db_schema="lograph_test";

/*List of endpoint. Each endpoint has these properties:
-name: the name of the endpoint
-endpoint: the sparql url
-examples: a list of active examples
-search_type: the type of search for retrieve the suggestion to display. It can be: none (no suggestion), dbpedia (only for dbpedia), regex (query with filter regex)
if regex is the type of search insert in enspoint_with_suggest the dataproperty for suggestion.
-toOther: is a bolean variable. If true searches if the elements are presents in others EPs (e.g. geoname, europeana etc).
-limit: if is set make a limit for the all query. This is made for a problem with endpoint that return to many results.
*/
global $suggestion_number;//Number of suggestion
$suggestion_number=15;

/*
$sparql_endpoints = array(
	array(
        'name'=>'dbpedia live',
        'endpoint'=>'http://dbpedia-live.openlinksw.com/sparql/',
        'examples'=>array(
            array('name'=>'Fiat', 'uri'=>'http://dbpedia.org/resource/Fiat'),
            array('name'=>'Florence', 'uri'=>'http://dbpedia.org/resource/Florence'),
        ),
		'search_type'=>'dbpedia',
		'url'=>'http://lookup.dbpedia.org/api/search.asmx/PrefixSearch?QueryClass=&MaxHits='.$suggestion_number .'&QueryString=',//prefix of the url for dbpedia to retrieve the labels suggested
		'limit'=>1000000
	),
    array(
        'name'=>'British Museum',
        'endpoint'=>'http://collection.britishmuseum.org/sparql',
        'examples'=>array(
            array('name'=>'Rosetta Stone', 'uri'=>'http://collection.britishmuseum.org/id/object/YCA62958'),
        ),
		'search_type'=>'none',
		'limit'=>1000000
	),
    array(
        'name'=>'FactForge live',
        'endpoint'=>'http://factforge.net/sparql',
        'examples'=>array(
            array('name'=>'Peretola Aeroporto', 'uri'=>'http://dbpedia.org/resource/Peretola_Airport'),
        ),
		'search_type'=>'contains',
		'limit'=>1000000
	),
    array(
        'name'=>'LinkedGeoData',
        'endpoint'=>'http://linkedgeodata.org/sparql',
        'examples'=>array(
            array('name'=>'Pitti (Florence)', 'uri'=>'http://linkedgeodata.org/triplify/node938208923'),
            array('name'=>'Il Cibreo (Florence)', 'uri'=>'http://linkedgeodata.org/triplify/node335930294'),
        ),
		'search_type'=>'contains',
		'limit'=>1000000
	),
    array(
        'name'=>'Europeana',
        'endpoint'=>'http://europeana.ontotext.com/sparql',
        'examples'=>array(
            array('name'=>'Sieben Wurfspeere', 'uri'=>'http://data.europeana.eu/item/01004/4EDF0A18D5F9B7AE9747A6DF3B99580CABF0320F'),
            array('name'=>'Dario Fo, Premio Nobel per la Letteratura - 1997', 'uri'=>'http://data.europeana.eu/proxy/provider/2022105/F5F8F40DCA36E201A24D84E0D11BD3563A63193A'),
        ),
		'search_type'=>'contains',
		'limit'=>1000000
	),
    array(
        'name'=>'Cultura Italia',
        'endpoint'=>'http://dati.culturaitalia.it/sparql/',
        'examples'=>array(
            array('name'=>'Accademia dei Georgofili', 'uri'=>'http://dati.culturaitalia.it/resource/actor/accademia-dei-georgofili'),
        ),
		'search_type'=>'contains',
		'limit'=>1000000
	),   
    array(
        'name'=>'Comune Firenze',
        'endpoint'=>'http://linkeddata.comune.fi.it:8080/sparql',
        'examples'=>array(
            array('name'=>'BASILICA_DI_SANTA_MARIA_NOVELLA', 'uri'=>'http://linkeddata.comune.fi.it:8080/resource/musei/BASILICA_DI_SANTA_MARIA_NOVELLA'),
			array('name'=>'Borgo_Santa_Croce', 'uri'=>'http://linkeddata.comune.fi.it:8080/resource/sinistri/Borgo_Santa_Croce'),
        ),
		'search_type'=>'regex',
		'limit'=>1000000
	), 
    array(
        'name'=>'Senato, Italiano',
        'endpoint'=>'http://dati.senato.it/sparql',
        'examples'=>array(
			array('name'=>'Mandato Senato', 'uri'=>'http://dati.camera.it/ocd/mandatoSenato'),
            array('name'=>'Mandato Camera', 'uri'=>'http://dati.camera.it/ocd/mandatoCamera'),
            array('name'=>'Senatore', 'uri'=>'http://dati.senato.it/osr/Senatore'),
            array('name'=>'Procedura', 'uri'=>'http://dati.senato.it/osr/Procedura'),
        ),
		'search_type'=>'regex',
		'limit'=>1000000
	), 
   array(
        'name'=>'Camera dei deputati, Italiano',
        'endpoint'=>'http://dati.camera.it/sparql',
        'examples'=>array(
            array('name'=>'Mandato Senato', 'uri'=>'http://dati.camera.it/ocd/mandatoSenato'),
            array('name'=>'Mandato Camera', 'uri'=>'http://dati.camera.it/ocd/mandatoCamera'),
            array('name'=>'Presidente', 'uri'=>'http://dati.camera.it/ocd/presidenteCamera'),
            array('name'=>'Presidente del consiglio', 'uri'=>'http://dati.camera.it/ocd/presidenteConsiglioMinistri'),
        ),
		'search_type'=>'regex',
		'limit'=>1000000
	), 
	array(
        'name'=>'Getty Vocabularies',
        'endpoint'=>'http://vocab.getty.edu/sparql',
        'examples'=>array(
            array('name'=>'Rheon', 'uri'=>'http://vocab.getty.edu/aat/300198841'),
        ),
		'search_type'=>'regex',
		'limit'=>1000000
	),
	array(
        'name'=>'Open Link SW',
        'endpoint'=>'http://lod.openlinksw.com/sparql',
        'examples'=>array(
            array('name'=>'Les jumeaux exe du Togo ', 'uri'=>'http://www.archeographe.net/Les-jumeaux-ewe-du-Togo'),
        ),
		'search_type'=>'regex',
		'limit'=>1000000
	),
	array(
        'name'=>'IEEE Video Stanford representation',
        'endpoint'=>'http://ieeevis.tw.rpi.edu/sparql',
        'examples'=>array(
            array('name'=>'Starwars: R2D2 Character', 'uri'=>'http://ieeevis.tw.rpi.edu/source/ieeevis-tw-rpi-edu/dataset/movie-characters/version/2013-May-22/r2-d2'),
        ),
		'search_type'=>'contains',
		'limit'=>10000
	),	
    array(
        'name'=>'SiiMobility (by DISIT)',
        'endpoint'=>'http://192.168.0.205:8080/openrdf-sesame/repositories/siimobilityultimate',
        'examples'=>array(
            array('name'=>'VIA GIACOMO MATTEOTTI', 'uri'=>'http://www.disit.dinfo.unifi.it/SiiMobility/RT04800102991TO'),
            array('name'=>'Bagno a ripoli', 'uri'=>'http://www.disit.dinfo.unifi.it/SiiMobility/048001'),
            array('name'=>'Florence', 'uri'=>'http://www.disit.dinfo.unifi.it/SiiMobility/048017'),
        ),
		'search_type'=>'contains',
		'limit'=>10000
	),		
    array(
        'name'=>'ICARO cloud (by DISIT)',
        'endpoint'=>'http://192.168.0.106:8080/openrdf-sesame/repositories/icaro8',
        'examples'=>array(
			array('name'=>'DataCenter DISIT', 'uri'=>'urn:cloudicaro:DataCenter:disit'),
			array('name'=>'Host 165 DC DISIT', 'uri'=>'urn:cloudicaro:HostMachine:disit-165'),
			array('name'=>'Hosts on DC DISIT', 'uri'=>'http://www.cloudicaro.it/cloud_ontology/core#HostMachine'),
			array('name'=>'MyECLAP on DC DISIT', 'uri'=>'urn:cloudicaro:BusinessConfiguration:eclap'),
          //  array('name'=>'DataCenter 1', 'uri'=>'urn:cloudicaro:DataCenter:01'),
        ),
		'search_type'=>'owlim',
		'limit'=>1000000
	),
    array(
        'name'=>'MyStoryPlayer (by DISIT)',
        'endpoint'=>'http://192.168.0.106:8080/openrdf-sesame/repositories/msptest2',
        'examples'=>array(
            array('name'=>'Laboratorio con Toni Servillo_g.1 dx/1', 'uri'=>'urn:axmedis:00000:obj:1fd0220e-36c6-4df6-8b04-e38903d0759f'),
            array('name'=>'Dario Fo, Trasmissione forzata II', 'uri'=>'urn:axmedis:00000:obj:084c197f-364f-46e4-bcfd-9b9186154667'),
        ),
		'search_type'=>'none',
		'limit'=>1000000
	),
    array(
        'name'=>'OSIM (by DISIT)',
        'endpoint'=>'http://openmind.disit.org:8080/openrdf-sesame/repositories/osim-rdf-store',
        'examples'=>array(
            array('name'=>'Paolo Nesi', 'uri'=>'urn:u-gov:unifi:AC_AB0:8cf8e70205520a44e90211a34e6b7a9e'),
            array('name'=>'Dip. Ingegneria dell\'Informazione', 'uri'=>'http://www.unifi.it/cercachi-str-058507.html'),
        ),
		'search_type'=>'owlim',
		'limit'=>1000000
	),
	array(
        'name'=>'ECLAP (by DISIT)',
        'endpoint'=>'http://www.eclap.eu/sparql',
        'examples'=>array(
        array('name'=>'Dance performances', 'uri'=>'http://www.eclap.eu/resource/term/501'),//Test1 Dance
		//array('name'=>'TEST1', 'uri'=>'http://www.eclap.eu/resource/object/urn%3Aaxmedis%3A00000%3Aobj%3Adf1c3655-5064-439a-97e0-e79e2d99b472'),
        array('name'=>'Europeana', 'uri'=>'http://www.europeana.eu/resolve/record/2022105/urn_axmedis_00000_obj_b7a13d78_2082_493e_8b52_c44a6665fed8'),//Test2 Europeana
        array('name'=>'Performances in Germany', 'uri'=>'http://sws.geonames.org/2921044/'),//Test3 Geoname
         ),
		'search_type'=>'owlim',
//		'toOther'=>'true',
		'limit'=>1000000
	)	
);
*/

//Boolean for asserts whether changes the labels with the prefixes or not.
$change_with_prefix=true;
//Label to replace in the info window
$label_for_uri=array(
  'http://www.w3.org/2000/01/rdf-schema#'=>"rdfs:", 
  'http://www.w3.org/1999/02/22-rdf-syntax-ns#'=>"rdf:",
  'http://www.w3.org/2004/02/skos/core#'=>"skos:",
  'http://xmlns.com/foaf/0.1/'=>"foaf:",
  'http://yago-knowledge.org/resource/'=>"yago:",
  'http://dbpedia.org/class/yago/'=>"yago:",
  'http://dbpedia.org/property/'=>"dbp:",
  'http://dbpedia.org/ontology/'=>"dbo:",
  'http://purl.org/dc/elements/1.1/'=>"dc:",
  'http://dublincore.org/documents/dces/'=>"dc:",
  'http://www.w3.org/2002/07/owl#'=>"owl:",
  'http://purl.org/dc/terms/'=>'dct:',
  'http://www.openarchives.org/ore/terms/'=>"ore:",
  'http://www.europeana.eu/schemas/edm/'=>'edm:',
  'http://www.cidoc-crm.org/rdfs/cidoc-crm#'=>'crm:',
  'http://creativecommons.org/ns#'=>'cc:',
  'http://www.w3.org/1999/xhtml/vocab#'=>'xhv:',
  'http://www.eclap.eu/schema/eclap/'=>'eclap:',
  'http://www.dsi.unifi.it/CMSAteneoCompetence#'=>'atn:',
  'http://mystoryplayer.org/msp.owl#'=>'msp:',
  'http://www.cloudicaro.it/cloud_ontology/core#'=>'icr:',
  'http://www.disit.dinfo.unifi.it/SiiMobility#'=>'smo:',
  'http://rdfs.org/ns/void#'=>'void:',
  'http://www.w3.org/2000/10/swap/pim/contact#'=>'con:',
  'http://www.w3.org/ns/prov#'=>'prov:',
  'http://vocab.getty.edu/ontology#'=>'gvp:',
  'http://purl.org/iso25964/skos-thes#'=>'iso:',
  'http://dati.camera.it/ocd/'=>'ocd:',
  'http://dati.senato.it/osr/'=>'osr:',
  'http://erlangen-crm.org/120111/'=>'crm:',
  'http://geovocab.org/geometry#'=>'ngeo:',
  'http://www.researchspace.org/ontology/'=>'rso:',
  'http://erlangen-crm.org/current/'=>'crm:',
  'http://collection.britishmuseum.org/id/ontology/'=>'bmo:',
);
//Endpoints which supports the queries for blank nodes.
$endpoint_for_bnode=array(
	'http://europeana.ontotext.com/sparql'=>true,
	'http://192.168.0.106:8080/openrdf-sesame/repositories/icaro7'=>true,
	'http://192.168.0.205:8080/openrdf-sesame/repositories/siimobilityultimate'=>true,
	'http://openmind.disit.org:8080/openrdf-sesame/repositories/osim-rdf-store'=>true
);
//List of DataProperty for the research of suggest
// $endpoint_with_suggest=array(
	// 'http://openmind.disit.org:8080/openrdf-sesame/repositories/osim-rdf-store'=>array(
		// 0=>"rdfs:label",
		// 1=>"foaf:name",
		// 2=>"?p"
	// ),
	// 'http://dati.senato.it/sparql'=>array(
		// 0=>"rdfs:label",
		// 1=>"?p"
	// ),
	// 'http://dati.camera.it/sparql'=>array(
		// 0=>"rdfs:label",
		// 1=>"?p"
	// ),
	// 'http://192.168.0.106:8080/openrdf-sesame/repositories/icaro7'=>array(
		// 0=>"rdfs:label"
	// ),
	// 'http://vocab.getty.edu/sparql'=>array(
		// 0=>"rdfs:label",
		// 1=>"?p"
	// ),
	// 'http://lod.openlinksw.com/sparql'=>array(
		// 0=>"rdfs:label",
		// 1=>"?p"
	// ),
	// 'http://192.168.0.205:8080/openrdf-sesame/repositories/siimobilityultimate'=>array(
		// 0=>"rdfs:label",
		// 1=>"foaf:name",
		// 2=>"<http://purl.org/dc/terms/alternative>",
		// 3=>"<http://www.disit.dinfo.unifi.it/SiiMobility#extendName>"
	// ),
	// 'http://ieeevis.tw.rpi.edu/sparql'=>array(
		// 0=>"rdfs:label",
		// 1=>"foaf:name",
		// 2=>"<http://purl.org/dc/terms/alternative>",
		// 3=>"?p"
	// ),
	// 'http://factforge.net/sparql'=>array(
		// 0=>"rdfs:label",
		// 1=>"foaf:name",
		// 2=>"?p"
	// ),
	// 'http://dati.culturaitalia.it/sparql/'=>array(
		// 0=>"rdfs:label",
		// 1=>"foaf:name",
		// 2=>"?p"
	// ),
	// 'http://linkeddata.comune.fi.it:8080/sparql'=>array(
		// 0=>"rdfs:label",
		// 1=>"foaf:name",
		// 2=>"?p"
	// ),
	// // 'http://europeana.ontotext.com/sparql'=>array(
		// // 0=>"rdfs:label",
		// // 1=>"foaf:name",
		// // 2=>"?p"
	// // ),
	// 'http://www.eclap.eu/sparql'=>array(
		// 0=>"rdfs:label",
		// 1=>"foaf:name",
		// 2=>"?p"
	// )	
// );
/* Actives search in multiple endpoints. Searches if the prefix match with the uri. If false the Options panel isn't showed*/
$active_multiple_endpoints=true;
/* The default choise for multiple endpoint search. With value=true it's enabled. */
$multiple_endpoints_default="true";
/* Sets the priority of threshold for select EP. One EP is selected if its priority is less or egual to t*/
$multiple_endpoint_priority=1;
//List of elements that have multiple EPs.
$multiple_endpoints=array(
	'http://sws.geonames.org'=>array(
		array("name"=>"http://dbpedia-live.openlinksw.com/sparql/","p"=>1),
		array("name"=>"http://europeana.ontotext.com/sparql","p"=>1),
		array("name"=>"http://factforge.net/sparql","p"=>2)
	),
	'http://dbpedia.org'=>array(
		array("name"=>"http://dbpedia-live.openlinksw.com/sparql/","p"=>1),
		array("name"=>"http://europeana.ontotext.com/sparql","p"=>1),
		array("name"=>"http://factforge.net/sparql","p"=>2),
		array("name"=>"http://www.eclap.eu/sparql","p"=>1)
	)
	// 'www.europeana.eu'=>array(
		// 0=>"http://europeana.ontotext.com/sparql"
	// ),
);
/* The default image for nodes */
$node_default_image='images/buttons/default.png';

//Loads configuration from Database.
// var_dump($endpoint_with_suggest);
if(!function_exists ("load_endpoints_config")){
function load_endpoints_config() {
	global $db_username, $db_psw, $db_host, $db_schema,$sparql_endpoints,$suggestion_number,$endpoint_with_suggest,$endpoint_for_bnode,$multiple_endpoints;
	$username = $db_username;
	$password = $db_psw;
	$hostname = $db_host; 
	$schema   = $db_schema;
	// connects to the database		
	$dbhandle = mysqli_connect($hostname, $username, $password,$schema) 
	or die("Unable to connect to MySQL");
    mysqli_set_charset($dbhandle,"uft8");
    $query = "SELECT * FROM endpoints ORDER BY endpoints.title";
	$result=mysqli_query($dbhandle,$query);
	$count=0;
	// var_dump( json_encode($sparql_endpoints[0]) );
	
	if($result){
		$sparql_endpoints=array();
		$endpoint_with_suggest=array();
		$endpoint_for_bnode=array();
		$multiple_endpoints=array();
		while($r=mysqli_fetch_array($result)) {
			if($r['active']){
				$examples=array();
				if( $r['examples'] && json_decode($r['examples'] )) {
					// var_dump($r['examples']);
					$examples=json_decode($r['examples']);
					
					// var_dump($examples);
					// $examples=array();
				}
				if($r['url']=='http://dbpedia-live.openlinksw.com/sparql/'){
					$support=array(			
						'name'=>$r['title'],
						'endpoint'=>$r['url'],
						'examples'=>$examples,
						'search_type'=>$r['search_type'],
						'url'=>'http://lookup.dbpedia.org/api/search.asmx/PrefixSearch?QueryClass=&MaxHits='.$suggestion_number .'&QueryString=',//prefix of the url for dbpedia to retrieve the labels suggested
						'limit'=>(int)$r['limit']
					);
				}
				else{
					$support=array(			
						'name'=>$r['title'],
						'endpoint'=>$r['url'],
						'examples'=>$examples,
						'search_type'=>$r['search_type'],
						'limit'=>(int)$r['limit']
					);
				}
				// var_dump($support);
				//Setts the suggestion.
				if($r['suggest'] && json_decode($r['suggest'])) {
					$sugg_for_current=json_decode($r['suggest']);
					if(gettype($sugg_for_current)=='object') $sugg_for_current = (array) json_decode($r['suggest']);
					$endpoint_with_suggest[$r['url']]=$sugg_for_current;
				}
				//Configures the blank nodes.
				if($r['blank_node'] && $r['blank_node']==1){
					$endpoint_for_bnodes[$r['url']]=true;
				}
				//Configures the multiple endpoints.
				if($r['uri_associated'] && json_decode($r['uri_associated'])) {
					$uri_associated=json_decode($r['uri_associated']);
					// var_dump($uri_associated);
					foreach($uri_associated as $u){
						if($multiple_endpoints[$u->uri]){
							//Adds the endpoint with its priority.
							$ep_insert=array();
							$ep_insert['name']=$r['url'];
							$ep_insert['p']=(int)$u->p;
							$multiple_endpoints[$u->uri][]=$ep_insert;
							// print "presente";
						}
						else{
							//Adds the prefix and the endpoint associated.
							$ep_insert=array();
							$ep_insert['name']=$r['url'];
							$ep_insert['p']=(int)$u->p;
							$multiple_endpoints[$u->uri][]=$ep_insert;
							// print "assente";
						}
					}
				}
				$sparql_endpoints[]=$support;
			}
			// if($count==0)return;
			$count++;
			
		}
		// var_dump($endpoint_with_suggest);
	}
}
}
load_endpoints_config();

if (isset($_GET["search"])) {
    echo json_encode($sparql_endpoints);
}
