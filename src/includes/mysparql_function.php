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

//Possible connections: http://dbpedia.org/sparql + http://www.w3.org/wiki/SparqlEndpoints
// global $results_n;//Number of results sends to client.
// $results_n=5;
// global $count_threshold;
// $count_threshold=50;
global $types; //It's used for save the types of the uri and for retrieve an image for a node.
$types=array();
global $current_endpoint;
global $max_rows_4query;//Limits the number of rows for each query.
$max_rows_4query=null;

function mysparql_error($x) {
    $f=fopen('error.log','at');
    fwrite($f,date('c')." ERROR ".$x."\n");
    fclose($f);
}
function truncate_uri($uri,$first_part=false) {
    if(substr($uri,0,1)=='<' && substr($uri,strlen($uri)-1,1)=='>')
        $uri=substr($uri,1,-1);
    $phash=strrpos($uri,'#',-1);
    if($phash){if(!$first_part)return substr($uri,$phash+1);else return substr($uri,0,$phash+1);}
        
    $pslash=strrpos($uri,'/',-1);
    if($pslash){$substr=substr($uri,$pslash+1);if($first_part)$substr=substr($uri,0,$pslash+1);if($substr!==false)return $substr;}        
    return $uri;
}
#funzione per adattare socialGraph a sparql.
function first_sparql($uri="<http://dbpedia.org/resource/Fiat>",$connection="http://dbpedia.org/sparql",&$warning=false) {
	include('config.php');
	require_once( "sparqllib.php" );	
	global $max_rows_4query;
	global $current_endpoint;
	$current_endpoint=$connection;
	$EP=get_endpoint($connection);
	$max_rows_4query=$EP["limit"];//Set the max number of rows for this endpoint
	$object->img=$node_default_image;
	$info=spql_get_first_info($uri,$connection);
	$object->img=$info['image'];	
	if($info['label']!=null)$object->name=$info['label'];	
	else $object->name=truncate_uri($uri);
	$object->id=$uri;
	$uri="<".$uri .">";
	$object->type="uri";  
	$object->relations=get_sparql($uri,$connection,$warning);
	return $object;
}

function get_sparql($uri="<http://dbpedia.org/resource/Fiat>",$connection="http://dbpedia.org/sparql", &$warning=false){
	//xdebug_start_trace("c:/xdebug/trace");
	$startt=microtime(true);
	$count_query=0;
	// print "start".microtime(true)."</br>";
	global $types;
	//Make a sparql query with the given uri.
	require_once( "sparqllib.php" ); 
	require( "config.php" ); 
	global $inverse_relations;
	global $max_rows_4query;
	$EP=get_endpoint($connection);//Loads the configuration for the current EP.
	if($max_rows_4query==null){$max_rows_4query=$EP["limit"];}//Sets the number of rows for each query.
	$relations=array();
	while(true){
		// Checks for multiple EP.
		$list_of_EP=array();
		// if($active_multiple_endpoints){
			// $uri_to_search=substr($uri,8);//removes '<http://' from <uri>
			// $uri_to_search=substr($uri_to_search,0,strpos($uri_to_search,"/"));//removes < from <uri>
			// // Checks if there are some Ep associated with the substring.
			// if($multiple_endpoints[$uri_to_search]){
				// //Saves the EP with a defined priority and makes a searches on that.
				// foreach($multiple_endpoints[$uri_to_search] as $ep){
					// if($ep['p']<=$multiple_endpoint_priority)$list_of_EP[]=$ep['name'];					
				// }
			// }
			// $EP_length=count($list_of_EP);
			// //Checks if the EndPoint passed is present in the list.
			// if(!array_search($connection,$list_of_EP)){//If it's not present insert in head.
				// $list_of_EP[$EP_length]=$list_of_EP[0];
				// $list_of_EP[0]=$connection;
			// }
			// else{//Switchs in first position
				// $index=array_search($connection,$list_of_EP);
				// if($index!=0){
					// $support=$list_of_EP[0];
					// $list_of_EP[0]=$list_of_EP[$index];
					// $list_of_EP[$index]=$support;
				// }
			// }			
		// }
		// else{
		$list_of_EP[]=$connection;
		
		// }
		/* Makes the searche in all the endpoints in $list_of_EP */
		foreach($list_of_EP as $endpoint){		
			
			// $endpoint=$connection;
			// if($endpoint=="http://192.168.0.205:8080/openrdf-sesame/repositories/SiiMobilityRid") $desc="SiiMobility"; 
			$db = sparql_connect( $endpoint );
			if( $db->alive() ){ //else not alived.
			
				for($iteration=0;$iteration<2;$iteration++ ){
					//Add prefix with sparql_ns
					sparql_ns( "rdfs","http://www.w3.org/2000/01/rdf-schema#" );
					sparql_ns("rdf","http://www.w3.org/1999/02/22-rdf-syntax-ns#");
					sparql_ns( "foaf","http://xmlns.com/foaf/0.1/" );
					sparql_ns( "SiiMobility","http://www.disit.dinfo.unifi.it/SiiMobility#" );
					if( !$db ) { print sparql_errno() . ": " . sparql_error(). "\n"; exit; }				
					/*Count the result for each objects. The predicate wich have more than $count_threshold rows are chosen separately.*/
					// if($iteration==0) $query_for_count="Select ?p (count(*) as ?c) where {".$uri ." ?p ?o .FILTER ISURI(?o)} group by ?p";
					if($iteration==0) $query_for_count=get_query("count1",$uri);
					else if($inverse_relations==true){//Deletes the dual relations.
						// $q="select distinct ?op where{ 
						// {".$uri ." ?p ?o . ?p <http://www.w3.org/2002/07/owl#inverseOf> ?op .FILTER ISURI(?o)} 
						// union
						// {".$uri ." ?p ?o . ?op <http://www.w3.org/2002/07/owl#inverseOf> ?p .FILTER ISURI(?o)}}";
						$q=get_query("countinv",$uri);
						// var_dump($q);print"  <--Counts inverse";return;
						$result = sparql_query( $q );
						$list_reverse=array();
						if($result){
							$fields = sparql_field_array( $result );		
							//Divides literals from uri elements.
							while( $row = sparql_fetch_array( $result ))$list_reverse[]=$row['op'];
						}
						$filter=" ";
						if($list_reverse!=null)$filter="filter(?p not in (<".implode('>,<',$list_reverse) .">))";
						$query_for_count="Select ?p (count(*) as ?c) where {?s ?p ".$uri ." ".$filter ."} group by ?p";
					}
					// else $query_for_count="Select ?p (count(*) as ?c) where {?s ?p ".$uri ." .FILTER ISURI(?s)} group by ?p";
					else $query_for_count=get_query("countr",$uri);
					// var_dump($query_for_count);print"<--Query for count </br></br>";
					$result = sparql_query( $query_for_count );
					$list_single_query=array();
					if($result){
						$fields = sparql_field_array( $result );		
						//Divides literals from uri elements.
						while( $row = sparql_fetch_array( $result )){
							if((int)($row['c'])>$count_threshold) $list_single_query[]=$row['p']; //checks if the property has more than $count_threshold element.
						}
					}
					else	mysparql_error($endpoint." ERROR query for count: ".$query_for_count."\nerror:\n".$db->error."\n-----------");
					$result=null;
					$opt_label=" ?o rdfs:label ?l ";
					// if($SiiMobility==true)$opt_label=" ?o SiiMobility:name ?l ";
					$filter=" ";
					$list_filter=$list_single_query;
					if($iteration!=0 && $inverse_relations==true) $list_filter=array_merge($list_filter,$list_reverse);
					if($list_filter!=null)$filter="filter(!(?p in (<".implode('>,<',$list_filter) .">)))";
					if($iteration==0) $first_query="SELECT ?p ?o ?l (lang(?l) as ?lang) ?d ?t WHERE {".$uri." ?p ?o. optional{ ".$opt_label ."} optional{?o rdf:type ?t} optional{?o foaf:depiction ?d} .FILTER(!isBlank(?o) && !isLiteral(?o)) ".$filter ."} LIMIT ".$max_rows_4query;
					else $first_query="SELECT ?p ?o ?l (lang(?l) as ?lang) ?d ?t WHERE {?o ?p ".$uri ." optional{ ".$opt_label ."} optional{?o rdf:type ?t} optional{?o foaf:depiction ?d} .FILTER(!isBlank(?o) && !isLiteral(?o)) ".$filter ."} LIMIT ".$max_rows_4query;
					if($endpoint_for_bnode[$connection]==true){
						if($iteration==0) $first_query="SELECT ?p ?bnode ?p2 ?o ?l (lang(?l) as ?lang) ?d ?t WHERE {{{".$uri." ?p ?o .FILTER (!isBlank(?o)) .FILTER ISURI(?o)}UNION {".$uri ." ?p ?bnode .?bnode ?p2 ?o
							FILTER (isBlank(?bnode) && !isBlank(?o)) .FILTER ISURI(?o)}}optional{ ".$opt_label ."} optional{?o rdf:type ?t} optional{?o foaf:depiction ?d} ".$filter ."} LIMIT ".$max_rows_4query;
						else $first_query="SELECT ?p ?bnode ?p2 ?o ?l (lang(?l) as ?lang) ?d ?t WHERE {{{?o ?p ".$uri ." .FILTER (!isBlank(?o)) .FILTER ISURI(?o)}UNION {?bnode ?p ".$uri ." .?bnode ?p2 ?o FILTER (isBlank(?bnode) && !isBlank(?o)) .FILTER ISURI(?o)}}
							optional{ ".$opt_label ."} optional{?o rdf:type ?t} optional{?o foaf:depiction ?d} ".$filter ."} LIMIT ".$max_rows_4query;
					}
					// if($iteration!=0){var_dump($first_query); print "  <- Second QUERY</br></br>";return;}
					// if($iteration==0){var_dump($first_query); print "  <- First QUERY</br></br>";return;}
					// $f=fopen("query.log",'at');//Query log. TO_CHECK Memory overflow with siiMobility and FactForge
					// fwrite($f,$count_query. ")    time:".(microtime(true)-$startt) ."\n PRINCIPAL QUERY ".$first_query );
					// fwrite($f,(memory_get_usage()/1000000). " MEMORY \n\n \n");
					// fclose($f);
					// $count_query++;
					$result=null;
					$result = sparql_query( $first_query );		
					// print$first_query;print"</br></br>";
					// var_dump($iteration."  ".$first_query);print"</br></br>";
					// print"</br> Iteration =".$iteration ."</br>";
					// if($iteration==0)print "request for objects: ".(microtime(true)-$startt) ."</br>";
					// else print "request for subjects: ".(microtime(true)-$startt)."</br>";
					if( $result ) { //else error return sparql_errno() . ": " . sparql_error(). "\n";
						if(sizeof($result->rows)>=$max_rows_4query) $warning="partial_results";//Places a warning if there are too many results
						$data=decode_query($result,null);
						$literals=$data['literals'];
						$uri_elements=$data['uri_elements'];
						$labels=$data['labels'];				
						$images=$data['images'];
						$bnodes=$data['bnodes'];
						// var_dump($data); print "  <- First QUERY</br></br>";return;
						$lang=$label_lang; //The language to chose for the label (if there are some labels for the objects).
						//$key is the predicate (the name of the relation) $property are the objects of the predicate.
						foreach($uri_elements as $key => $property){						
							// print "<p>First cycle.</p></br></br>";
							$newkey=$key;//Saves a copy of the name because it can be overwritten
							if($relations[$newkey]!=null){//In the case that the same property is present in direct and indirect relations.
								$newkey=$key ."Inv";//Concatenating 'Inv' it prevent duplications.
								$relations[$newkey]->uri=$key;
							}
							else $relations[$newkey]->uri=$key;
							$prop_name=truncate_uri($key);
							//Changes the name with the prefix if 'change_with_prefix' is true.
							if($change_with_prefix){
								$first_part_uri=truncate_uri($key,true);
								if($label_for_uri[$first_part_uri]!=null)$prop_name=$label_for_uri[$first_part_uri]. truncate_uri($key);
							}
							$relations[$newkey]->name=$prop_name;
							if($iteration==1){
								$relations[$newkey]->inbound=true;//for specify that is a inverse relation.
							}
							$relations[$newkey]->img="";
							$elements=array();
							$j=0;
							foreach($property as $e){
								if($j>=$results_n){
									// Counts the number of element left
									// if($iteration==0)$query_for_count="Select (count(*) as ?c) where { ".$uri ." <".$key ."> ?s .Filter(!isBlank(?s))}";
									// else $query_for_count="Select (count(*) as ?c) where {?s <".$key ."> ".$uri ." .Filter(!isBlank(?s))}";									
									if($iteration==0)$query_for_count=get_query("count2",$uri,$key);
									else $query_for_count=get_query("count2r",$uri,$key);									
									/* FOR Blank node */
									if($endpoint_for_bnode[$connection]==true){
										// if($iteration==0)$query_for_count="Select (count(*) as ?c) where { ".$uri ." <".$key ."> ?s }";
										// else $query_for_count="Select (count(*) as ?c) where {?s <".$key ."> ".$uri ."}";
										if($iteration==0)$query_for_count=get_query("count2b",$uri,$key);
										else $query_for_count=get_query("count2br",$uri,$key);	
									}
									
									$result = sparql_query( $query_for_count );
									if($result)	$count = sparql_fetch_array( $result ); //Divids literals from uri elements.									
									else	mysparql_error($endpoint." ERROR query for count: ".$query_for_count."\nerror:\n".$db->error."\n-----------");
									if(($count['c']-$results_n)>0){
										$elements[$j]->type ="more";
										$elements[$j]->name="more ".($count['c']-$results_n) ." ".$relations[$newkey]->name;
										$elements[$j]->from=$results_n; //The last element found.
										$elements[$j]->img='images/icons/more.png';
										$elements[$j]->source=$uri;		
										$elements[$j]->function_to_call=$key; //the predicate.
										$elements[$j]->isInverse='yes';
										if($iteration==0)$elements[$j]->isInverse='no';
									}
									break;
								}
								/*Specific for bnodes, if $e is a bnode the code takes the relations for the bnode. 
								This query doesn't work for all the sites (for example doesn't work for British Museum).
								*/
								if($bnodes[$e]){
									$bnode_rel=$bnodes[$e];
									
									$elements[$j]->type = "uri";
									//Scans the type of the current element.
									if($types[$e]==null){
										$types[$e][]='bnode'; //If there is no type, the type is the predicate.
									}
									//Takes the name from the list of labels.
									if($labels[$e]!=null){
										$elements[$j]->name=reset($labels[$e]);
										if($labels[$e][$lang])$elements[$j]->name=$labels[$e][$lang];
									}
									else $elements[$j]->name = $e;
									$elements[$j]->id ='bnode'.$e;
									$file_headers = @get_headers($images['bnode']);
									$elements[$j]->img = spql_get_image($elements[$j]->id,$types[$e]);
									//Adds relations of the first level of the blank nodes. The relations are considered only in one direction.
									$bnrelations=array();
									foreach($bnode_rel as $bnrel=>$bnprop){
										$bnrelations[$bnrel]->uri=$bnrel;
										$bnrelations[$bnrel]->name=truncate_uri($bnrel); //direct property
										$bnrelations[$bnrel]->img="";
										$j2=0;
										$bnelements=array();
										foreach($bnprop as $bnel){
											$bnelements[$j2]->type = "uri";
											//Scans the type of the current element.
											if($types[$bnel]==null){
												$types[$bnel][]=$bnrel; //If there is no type, the type is the predicate.
											}
											//Takes the name from the list of labels.
											if($labels[$bnel]!=null){
												$bnelements[$j2]->name=reset($labels[$bnel]);
												if($labels[$bnel][$lang])$bnelements[$j2]->name=$labels[$bnel][$lang];
											}
											else $bnelements[$j2]->name = truncate_uri($bnel);
											$bnelements[$j2]->id =$bnel;
											$file_headers = @get_headers($images[$bnel]);
											if($file_headers[0] == 'HTTP/1.1 404 Not Found' || $images[$bnel]==NULL) $bnelements[$j2]->img = spql_get_image($bnelements[$j2]->id,$types[$bnel]);						
											else $bnelements[$j2]->img =$images[$bnel];
											$j2++;
											
										}
										$bnrelations[$bnrel]->elements=$bnelements;
									}
									$elements[$j]->relations=$bnrelations;				
									
								}
								else{
									$elements[$j]->type = "uri";
									//Scans the type of the current element.
									if($types[$e]==null)$types[$e][]=$key; //If there is no type, the type is the predicate.								
									//Takes the name from the list of labels.
									if($labels[$e]!=null){
										$elements[$j]->name=reset($labels[$e]);
										if($labels[$e][$lang])$elements[$j]->name=$labels[$e][$lang];
									}
									else $elements[$j]->name = truncate_uri($e);
									$elements[$j]->id =$e;
									$file_headers = @get_headers($images[$e]);
									if($file_headers[0] == 'HTTP/1.1 404 Not Found' || $images[$e]==NULL) $elements[$j]->img = spql_get_image($elements[$j]->id,$types[$e]);						
									else $elements[$j]->img =$images[$e];								
									
									// if($EP.toOther==true){//Checks if the element is present in multiple endpoints.
										// $endpoints=null;
										// foreach($multiple_endpoints as $mep=>$eps){
											// if(strpos($e,$mep)==true){
												// $elements[$j]->EP=$eps;//Adds all the EndPoints to the element.
												// array_push($elements[$j]->EP,$EP['endpoint']);//Adds the current EP.
												// break;
											// }
										// }
									// }							
								}							
								$j++;
							}
							$relations[$newkey]->elements=$elements; 					
						}
					}			
					else	mysparql_error($endpoint." ERROR first query: ".$first_query."\nerror:\n".$db->error."\n-----------");
					$result=null;
					//EXPLODE QUERY. For each one makes a single limited query 
					foreach($list_single_query as $l){
						$c=0;
						// else $sparql="SELECT ?p ?o ?l (lang(?l) as ?lang) ?d ?t WHERE {?o ?p ".$uri .". optional{ ?o rdfs:label ?l } optional{?o rdf:type ?t} optional{?o foaf:depiction ?d}}"; //Revert query
						//Query without blank nodes
						// if($iteration==0) $sparql="SELECT ?p ?o ?l (lang(?l) as ?lang)  ?d ?t WHERE {{select ?o where {".$uri ." <".$l ."> ?o} LIMIT  ".($results_n+1) ."}optional{ ".$opt_label ."} optional{?o rdf:type ?t}  optional{?o foaf:depiction ?d} .FILTER(!isBlank(?o) && !isLiteral(?o))} LIMIT ".$max_rows_4query ;
						// else $sparql="select ?p ?o ?l (lang(?l) as ?lang) ?d ?t where{{select ?o where {?o <".$l ."> ".$uri ."} LIMIT ".($results_n+1) ."}optional{ ".$opt_label ." } optional{?o rdf:type ?t} optional{?o foaf:depiction ?d} .FILTER(!isBlank(?o) && !isLiteral(?o))} LIMIT ".$max_rows_4query;
						if($iteration==0) $sparql=get_query("select",$uri,$l,$opt_label); 
						else $sparql=get_query("selectr",$uri,$l,$opt_label);
						//Query for blank nodes.										
						if($endpoint_for_bnode[$connection]==true){
							// if($iteration==0) $sparql="SELECT ?p ?bnode ?p2 ?o ?l (lang(?l) as ?lang)  ?d ?t WHERE {{select ?o where {{".$uri ." <".$l ."> ?o .FILTER (!isBlank(?o)) .FILTER ISURI(?o)}UNION {".$uri ." <".$l ."> ?bnode .?bnode ?p2 ?o FILTER (isBlank(?bnode) && !isBlank(?o)) .FILTER ISURI(?o)}}LIMIT  ".($results_n+1) ."}optional{ ".$opt_label ."} optional{?o rdf:type ?t} optional{?o foaf:depiction ?d} }  LIMIT ".$max_rows_4query;
							// else $sparql="SELECT ?p ?bnode ?p2 ?o ?l (lang(?l) as ?lang)  ?d ?t WHERE {{select ?o where {{ ?o <".$l ."> ".$uri ." .FILTER (!isBlank(?o)) .FILTER ISURI(?o)}UNION {".$uri ." <".$l ."> ?bnode .?bnode ?p2 ?o FILTER (isBlank(?bnode) && !isBlank(?o)) .FILTER ISURI(?o)}}LIMIT  ".($results_n+1) ."}optional{ ".$opt_label ."} optional{?o rdf:type ?t} optional{?o foaf:depiction ?d} } LIMIT ".$max_rows_4query;
							if($iteration==0) $sparql=get_query("selectb",$uri,$l,$opt_label);
							else $sparql=get_query("selectbr",$uri,$l,$opt_label);
						}
						// $f=fopen("query.log",'at');
						// fwrite($f,$count_query. ")    time:".(microtime(true)-$startt) ."\n SECONDARY QUERY: ".$sparql ."\n \n \n");
						// fwrite($f,(memory_get_usage()/1000000). " MEMORY \n\n \n");
						// fclose($f);
						// $count_query++;
						$result = sparql_query( $sparql );					
						// if($c==0)var_dump($sparql);print" <--Query for single property</br></br>";return;
						$c++;
						if( $result ) { //else error return sparql_errno() . ": " . sparql_error(). "\n";
							if(sizeof($result->rows)>=$max_rows_4query) $warning="partial_results";//Places a warning if there are too many results
							$data;
							$data=decode_query($result,$l);
							$literals=$data['literals'];
							$uri_elements=$data['uri_elements'];
							$labels=$data['labels'];				
							$images=$data['images'];						
							$bnodes=$data['bnodes'];
							// print"DATA per $sparql </br>";var_dump($l);print"</br></br>";
							$lang=$label_lang; //The language to chose for the label (if there are some labels for the objects).
							//$key is the predicate (the name of the relation).
							// foreach($uri_elements as $key => $property){
							$newkey=$l;//saves a copy of the name because it can be overwritten
							if($relations[$newkey]!=null){//In the case that the same property is present in direct and indirect relations.
								$newkey=$l ."Inv";
								$relations[$newkey]->uri=$l;
							}
							else $relations[$newkey]->uri=$l;
							$prop_name=truncate_uri($l);
							//Changes the name with the prefix if 'change_with_prefix' is true.
							if($change_with_prefix){
								$first_part_uri=truncate_uri($l,true);
								if($label_for_uri[$first_part_uri]!=null)$prop_name=$label_for_uri[$first_part_uri]. truncate_uri($l);
							}
							$relations[$newkey]->name=$prop_name;
							if($iteration==1) $relations[$newkey]->inbound=true;//for specify that is a inverse relation.
							
							$relations[$newkey]->img="";
							$elements=array();
							$j=0;
							if($uri_elements[$l]){
								foreach($uri_elements[$l] as $e){
									if($j>=$results_n){
										/*Count the number of element left.*/
										// if($iteration==0)$query_for_count="Select (count(*) as ?c) where { ".$uri ." <".$l ."> ?s .Filter(!isBlank(?s))}";
										// else $query_for_count="Select (count(*) as ?c) where {?s <".$l ."> ".$uri ." .Filter(!isBlank(?s))}";
										if($iteration==0)$query_for_count=get_query("count2",$uri,$l);
										else $query_for_count=get_query("count2r",$uri,$l);
										/* FOR Blank node */
										if($endpoint_for_bnode[$connection]==true){
											// if($iteration==0)$query_for_count="Select (count(*) as ?c) where { ".$uri ." <".$l ."> ?s }";
											// else $query_for_count="Select (count(*) as ?c) where {?s <".$l ."> ".$uri ."}";	
											if($iteration==0)$query_for_count=get_query("count2b",$uri,$l);
											else $query_for_count=get_query("count2br",$uri,$l);	
											// if($iteration==0)$query_for_count="Select (count(*) as ?c) where {{ ".$uri ." <".$l ."> ?s .FILTER (!isBlank(?s))} UNION {".$uri ." ?p ?s .?s ?w ?o FILTER (isBlank(?s) && !isBlank(?o))}}";
											// else $query_for_count="Select (count(*) as ?c) where {{?s <".$l ."> ".$uri ." .FILTER (!isBlank(?s))} UNION {".$uri ." ?p ?s .?s ?w ?o FILTER (isBlank(?s) && !isBlank(?o))}}";
										}
										// var_dump($query_for_count);print"</br></br>";
										$result = sparql_query( $query_for_count );
										if($result)	$count = sparql_fetch_array( $result ); //Divids literals from uri elements.									
										else	mysparql_error($endpoint." ERROR query for count: ".$query_for_count."\nerror:\n".$db->error."\n-----------");
										if(($count['c'] - $results_n)>0){
											$elements[$j]->type="more";
											$elements[$j]->name="more ".($count['c'] - $results_n) ." ".$relations[$newkey]->name;
											$elements[$j]->from=$results_n; //The last element found.
											$elements[$j]->img='images/icons/more.png';
											$elements[$j]->source=$uri;		
											$elements[$j]->function_to_call=$l; //the predicate.
											$elements[$j]->isInverse='yes';
											if($iteration==0)$elements[$j]->isInverse='no';
										}	
										break;
									}
									
									if($bnodes[$e]){//Specific for bnodes
										$bnode_rel=$bnodes[$e];										
										$elements[$j]->type = "uri";
										//Scans the type of the current element.
										if($types[$e]==null){
											$types[$e][]='bnode'; //If there is no type, the type is the predicate.
										}
										//Takes the name from the list of labels.
										if($labels[$e]!=null){
											$elements[$j]->name=reset($labels[$e]);
											if($labels[$e][$lang])$elements[$j]->name=$labels[$e][$lang];
										}
										else $elements[$j]->name = $e;
										$elements[$j]->id ='bnode'.$e;
										$file_headers = @get_headers($images['bnode']);
										$elements[$j]->img = spql_get_image($elements[$j]->id,$types[$e]);
										//adds relations of the first level of the blank nodes. The relations are considered only in one direction.
										$bnrelations=array();
										foreach($bnode_rel as $bnrel=>$bnprop){
											$bnrelations[$bnrel]->uri=$bnrel;
											$bnrelations[$bnrel]->name=truncate_uri($bnrel); //direct property
											$bnrelations[$bnrel]->img="";
											$j2=0;
											$bnelements=array();
											foreach($bnprop as $bnel){
												$bnelements[$j2]->type = "uri";
												//Scans the type of the current element.
												if($types[$bnel]==null){
													$types[$bnel][]=$bnrel; //If there is no type, the type is the predicate.
												}
												//Takes the name from the list of labels.
												if($labels[$bnel]!=null){
													$bnelements[$j2]->name=reset($labels[$bnel]);
													if($labels[$bnel][$lang])$bnelements[$j2]->name=$labels[$bnel][$lang];
												}
												else $bnelements[$j2]->name = truncate_uri($bnel);
												$bnelements[$j2]->id =$bnel;
												$file_headers = @get_headers($images[$bnel]);
												if($file_headers[0] == 'HTTP/1.1 404 Not Found' || $images[$bnel]==NULL) $bnelements[$j2]->img = spql_get_image($bnelements[$j2]->id,$types[$bnel]);						
												else $bnelements[$j2]->img =$images[$bnel];
												$j2++;
												
											}
											$bnrelations[$bnrel]->elements=$bnelements;
										}
										$elements[$j]->relations=$bnrelations;				
										
									}
									else{
										$elements[$j]->type = "uri";
										//Scans the type of the current element.
										if($types[$e]==null){
											$types[$e][]=$l; //If there is no type, the type is the predicate.
										}
										//Takes the name from the list of labels.
										if($labels[$e]!=null){
											$elements[$j]->name=reset($labels[$e]);
											if($labels[$e][$lang])$elements[$j]->name=$labels[$e][$lang];
										}
										else $elements[$j]->name = truncate_uri($e);
										$elements[$j]->id =$e;
										$file_headers = @get_headers($images[$e]);
										if($file_headers[0] == 'HTTP/1.1 404 Not Found' || $images[$e]==NULL) $elements[$j]->img = spql_get_image($elements[$j]->id,$types[$e]);						
										else $elements[$j]->img =$images[$e];
									}
									
									$j++;
								}
							}
							$relations[$newkey]->elements=$elements; 					
						}
						else mysparql_error($endpoint." ERROR query: ".$sparql."\nerror:\n".$db->error."\n-----------");
						$result=null;
					}
					// print "after code: ".(microtime(true)-$startt) ."</br></br>";
					//------------------------------
				}
			}
			else{if($list_of_EP[0]==$endpoint) mysparql_error($endpoint." NOT ALIVE\n".$db->error."\n-----------");}//If the first Endpoint is wrong exits with error message.
		}
		break;//remove this break for make it run for all the endpoints and not only for dbpedia.
	}
	//xdebug_stop_trace();
	return $relations;
}
function get_more($subject,$relation,$from,$connection,$isInverse){
	/*More elements for a relation.
	subject->the subject for the query;$relation->the specified property;$from->the number of rows already displaied
	$connection->the endpoint;$isInverse->if the property is direct or inverse(like 'is p of').
	*/
	global $types;
	// global $results_n;
	//Make a sparql query with the given uri.
	require_once( "sparqllib.php" ); 	
	require_once( "config.php" ); 
	global $max_rows_4query;
	if($max_rows_4query==null){
		$EP=get_endpoint($connection);
		$max_rows_4query=$EP["limit"];	
	}
	// global $endpoints;
	// $relations=array();
	// foreach( $endpoints as $endpoint=>$desc)
	// {	
	while(true){//xxxxxx
		$endpoint=$connection;//xxxxxx
		// if($endpoint=="http://192.168.0.205:8080/openrdf-sesame/repositories/SiiMobilityRid") $desc="SiiMobility"; //xxxxxx
		// $SiiMobility=false;
		// if($desc=="SiiMobility")$SiiMobility=true;//This variation is for takes different label names.
		$db = sparql_connect( $endpoint );
		if( $db->alive() ) //else not alived.
		{
			// for($iteration=0;$iteration<2;$iteration++ ){
				$iteration=1;
				if($isInverse=='no')$iteration=0;
				//Add prefix with sparql_ns
				sparql_ns( "rdfs","http://www.w3.org/2000/01/rdf-schema#" );
				sparql_ns("rdf","http://www.w3.org/1999/02/22-rdf-syntax-ns#");
				sparql_ns( "foaf","http://xmlns.com/foaf/0.1/" );
				sparql_ns( "SiiMobility","http://www.disit.dinfo.unifi.it/SiiMobility#" );
				$opt_label=" ?o rdfs:label ?l ";
				// if($SiiMobility==true)$opt_label=" ?o SiiMobility:name ?l ";
				if($iteration==0){
					// $sparql="SELECT ?o ?l (lang(?l) as ?lang) ?d ?t WHERE {{select DISTINCT ?o where {".$subject ." <".$relation ."> ?o} LIMIT ".($results_n+1) ." OFFSET ".($from) ."} optional{ ".$opt_label ." } optional{?o rdf:type ?t} optional{?o foaf:depiction ?d} .FILTER(!isBlank(?o) && !isLiteral(?o))}";
					$sparql=get_query("more",$subject,$relation,$results_n,$from,$opt_label);
				}
				else {
					// $sparql="SELECT ?o ?l (lang(?l) as ?lang) ?d ?t WHERE {{select DISTINCT ?o where{?o <".$relation ."> ".$subject ."} LIMIT ".($results_n+1) ." OFFSET ".($from) ."}. optional{ ".$opt_label ." } optional{?o rdf:type ?t} optional{?o foaf:depiction ?d} .FILTER(!isBlank(?o) && !isLiteral(?o))}"; //Revert query
					$sparql=get_query("more_r",$subject,$relation,$results_n,$from,$opt_label);//Revert query
				}
				/*For blank node
					makes first the query for non blank nodes, and then the query for blank nodes
				*/
				if($endpoint_for_bnode[$connection]==true){
					if($iteration==0){
						// $sparql="SELECT ?o ?l (lang(?l) as ?lang) ?d ?t WHERE {{select DISTINCT ?o where {".$subject ." <".$relation ."> ?o .FILTER (!isBlank(?o)) .FILTER ISURI(?o)} LIMIT ".($results_n+1) ." OFFSET ".($from) ."} optional{ ".$opt_label ." } optional{?o rdf:type ?t} optional{?o foaf:depiction ?d}}";
						$sparql=get_query("more_b",$subject,$relation,$results_n,$from,$opt_label);
					}
					else{
						// $sparql="SELECT ?o ?l (lang(?l) as ?lang) ?d ?t WHERE {{select DISTINCT ?o where{?o <".$relation ."> ".$subject ." .FILTER (!isBlank(?o)) .FILTER ISURI(?o)} LIMIT ".($results_n+1) ." OFFSET ".($from) ."}. optional{ ".$opt_label ." } optional{?o rdf:type ?t} optional{?o foaf:depiction ?d}}"; //Revert query
						$sparql=get_query("more_br",$subject,$relation,$results_n,$from,$opt_label);
					}
				}
				// var_dump($sparql); print " <-PRIMA </br></br>";
				$result = sparql_query( $sparql );
				if( $result ) { //else error return sparql_errno() . ": " . sparql_error(). "\n";
					if(sizeof($result->rows)>=$max_rows_4query) $warning="partial_results";//Places a warning if there are too many results
					$data;
					$data=decode_query($result,null);
					$literals=$data['literals'];
					$uri_elements=$data['uri_elements'];
					$labels=$data['labels'];
					$images=$data['images'];	
					$bnodes=$data['bnodes'];					
					$lang=$label_lang;
					$elements=array();
					$j=0;
					foreach($uri_elements as $key => $property){//This cycle is executed only one time because there is only one property.
						$name=truncate_uri($relation);
						foreach($property as $e){
							if($j>=$results_n){
								/*Count the number of element left.
								BUG: There is a problem with some relations, like type; with this count doesn't update correctly.
								The problem is that the result of the previous query was in a different order respect this query.
								*/
								// if($iteration==0)$query_for_count="Select (count(*) as ?c) where { ".$subject ." <".$relation ."> ?s .Filter(!isBlank(?s))}";
								// else $query_for_count="Select (count(*) as ?c) where {?s <".$relation ."> ".$subject ." .Filter(!isBlank(?s))}";
								if($iteration==0)$query_for_count=get_query("count2",$subject,$relation);
								else $query_for_count=get_query("count2r",$subject,$relation);
								/* FOR Blank node */
								if($endpoint_for_bnode[$connection]==true){
									// if($iteration==0)$query_for_count="Select (count(*) as ?c) where { ".$subject ." <".$relation ."> ?s }";
									// else $query_for_count="Select (count(*) as ?c) where {?s <".$relation ."> ".$subject ."}";	
									if($iteration==0)$query_for_count=get_query("count2b",$subject,$relation);
									else $query_for_count=get_query("count2br",$subject,$relation);
								}
								
								$result = sparql_query( $query_for_count );
								if($result)	$count = sparql_fetch_array( $result ); //Divids literals from uri elements.									
								else	mysparql_error($endpoint." ERROR query for count: ".$query_for_count."\nerror:\n".$db->error."\n-----------");
								if(($count['c']-($from+$results_n))>0){
									$elements[$j]->type = "more";
									$elements[$j]->from=$from+$results_n; //L'ultimo elemento trovato.
									$elements[$j]->name="more ".($count['c']-$elements[$j]->from) ." ".$name;
									$elements[$j]->img='images/icons/more.png';
									$elements[$j]->source=$subject;		
									$elements[$j]->function_to_call=$relation;
									$elements[$j]->isInverse='yes';
									if($iteration==0)$elements[$j]->isInverse='no';
								}								
								return $elements;
							}
							
							/*Specific for bnodes, if $e is a bnode the code takes the relations for the bnode. 
							This query doesn't work for all the sites (for example doesn't work for British Museum).
							*/
							if($bnodes[$e]){
								$bnode_rel=$bnodes[$e];
								
								$elements[$j]->type = "uri";
								//Scan the type of the current element.
								if($types[$e]==null){
									$types[$e][]='bnode'; //If there is no type, the type is the predicate.
								}
								//Take the name from the list of labels.
								if($labels[$e]!=null){
									$elements[$j]->name=reset($labels[$e]);
									if($labels[$e][$lang])$elements[$j]->name=$labels[$e][$lang];
								}
								else $elements[$j]->name = $e;
								$elements[$j]->id ='bnode'.$e;
								$file_headers = @get_headers($images['bnode']);
								$elements[$j]->img = spql_get_image($elements[$j]->id,$types[$e]);
								//add relations of the first level of the blank nodes. The relations are considered only in one direction.
								$bnrelations=array();
								foreach($bnode_rel as $bnrel=>$bnprop){
									$bnrelations[$bnrel]->uri=$bnrel;
									$bnrelations[$bnrel]->name=truncate_uri($bnrel); //direct property
									$bnrelations[$bnrel]->img="";
									$j2=0;
									$bnelements=array();
									foreach($bnprop as $bnel){
										$bnelements[$j2]->type = "uri";
										//Scan the type of the current element.
										if($types[$bnel]==null){
											$types[$bnel][]=$bnrel; //If there is no type, the type is the predicate.
										}
										//Take the name from the list of labels.
										if($labels[$bnel]!=null){
											$bnelements[$j2]->name=reset($labels[$bnel]);
											if($labels[$bnel][$lang])$bnelements[$j2]->name=$labels[$bnel][$lang];
										}
										else $bnelements[$j2]->name = truncate_uri($bnel);
										$bnelements[$j2]->id =$bnel;
										$file_headers = @get_headers($images[$bnel]);
										if($file_headers[0] == 'HTTP/1.1 404 Not Found' || $images[$bnel]==NULL) $bnelements[$j2]->img = spql_get_image($bnelements[$j2]->id,$types[$bnel]);						
										else $bnelements[$j2]->img =$images[$bnel];
										$j2++;
										
									}
									$bnrelations[$bnrel]->elements=$bnelements;
								}
								$elements[$j]->relations=$bnrelations;				
								
							}
							else{
								$elements[$j]->type = "uri";
								// var_dump($elements);print"</br></br>";
								//Scan the type of the current element.
								if($types[$e]==null){
									$types[$e][]=$key; //If there is no type, the type is the predicate.
								}
								//Take the name from the list of labels.
								if($labels[$e]!=null){
									$elements[$j]->name=reset($labels[$e]);
									if($labels[$e][$lang])$elements[$j]->name=$labels[$e][$lang];
								}
								else $elements[$j]->name = truncate_uri($e);
								$elements[$j]->id =$e;
								$file_headers = @get_headers($images[$e]);
								if($file_headers[0] == 'HTTP/1.1 404 Not Found' || $images[$e]==NULL) $elements[$j]->img = spql_get_image($elements[$j]->id,$types[$e]);						
								else $elements[$j]->img =$images[$e];
							}							
							$j=$j+1;							
						}
					}	
					if($j<$results_n && $endpoint_for_bnode[$connection]==true){
						// /*For blank node*/
						if($iteration==0)$query_for_count="Select (count(*) as ?c) where { ".$subject ." <".$relation ."> ?s .FILTER (!isBlank(?s))}";
						else $query_for_count="Select (count(*) as ?c) where {?s <".$relation ."> ".$subject ." .FILTER (!isBlank(?s))}";
						$countres = sparql_query( $query_for_count );
						if($countres)	$countres = sparql_fetch_array( $countres ); //for jump the non blank node.
						// var_dump($countres);print"</br></br>";
						
						if($iteration==0) $sparql="SELECT ?bnode ?p2 ?o ?l (lang(?l) as ?lang)  ?d ?t WHERE {".$subject ." <".$relation ."> ?bnode .?bnode ?p2 ?o .{SELECT ?bnode WHERE { ".$subject ." <".$relation ."> ?bnode .Filter(isBlank(?bnode)) } LIMIT  ".($results_n+1) ." OFFSET ".($from+$j-$countres['c']) ."} .FILTER (isBlank(?bnode) && !isBlank(?o)) optional{ ".$opt_label ."} optional{?o rdf:type ?t} optional{?o foaf:depiction ?d}}";
						else $sparql="SELECT ?bnode ?p2 ?o ?l (lang(?l) as ?lang)  ?d ?t WHERE {?bnode <".$relation ."> ".$subject ."   .?bnode ?p2 ?o .{SELECT ?bnode WHERE { ?bnode <".$relation ."> ".$subject ." .Filter(isBlank(?bnode)) } LIMIT  ".($results_n+1) ." OFFSET ".($from+$j-$countres['c']) ."} .FILTER (isBlank(?bnode) && !isBlank(?o))optional{ ".$opt_label ."} optional{?o rdf:type ?t} optional{?o foaf:depiction ?d}}";
						
						// var_dump($sparql);print"<- Secondo </br></br>";
						$result = sparql_query( $sparql );
						if($result){
							if(sizeof($result->rows)>=$max_rows_4query) $warning="partial_results";//Places a warning if there are too many results
							$data;
							$data=decode_query($result,null);
							$literals=$data['literals'];
							$uri_elements=$data['uri_elements'];
							$labels=$data['labels'];
							$images=$data['images'];
							$bnodes=$data['bnodes'];							
							$lang=$label_lang;
							// var_dump($uri_elements);print"</br></br>";
							foreach($uri_elements as $key => $property){//This cycle is executed only one time because there is only one property.
								$name=truncate_uri($relation);
								// $elements=array();
								foreach($property as $e){
									if($j>=$results_n){
										/*Count the number of element left.
										BUG: There is a problem with some relations, like type; with this count doesn't update correctly.
										The problem is that the result of the previous query was in a different order respect this query.
										*/
										if($iteration==0)$query_for_count="Select (count(*) as ?c) where { ".$subject ." <".$relation ."> ?s }";
										else $query_for_count="Select (count(*) as ?c) where {?s <".$relation ."> ".$subject ." }";
										// /*Query for blank node*/
										// if($endpoint_for_bnode[$connection]==true){
											// if($iteration==0)$query_for_count="Select (count(*) as ?c) where {{ ".$subject ." <".$relation ."> ?s .FILTER (!isBlank(?s))} UNION {".$subject ." ?p ?s .?s ?w ?o FILTER (isBlank(?s) && !isBlank(?o))}}";
											// else $query_for_count="Select (count(*) as ?c) where {{?s <".$relation ."> ".$subject ." .FILTER (!isBlank(?s))} UNION {".$subject ." ?p ?s .?s ?w ?o FILTER (isBlank(?s) && !isBlank(?o))}}";
										// }
										
										$result = sparql_query( $query_for_count );
										if($result)	$count = sparql_fetch_array( $result ); //Divids literals from uri elements.									
										else	mysparql_error($endpoint." ERROR query for count: ".$query_for_count."\nerror:\n".$db->error."\n-----------");
										if(($count['c']-($from+$results_n))>0){
											//This control is for some endpoint that not support offset in the query. This prevent to recall infinite time this function.
											$elements[$j]->type = "more";
											$elements[$j]->from=$from+$results_n; //L'ultimo elemento trovato.
											$elements[$j]->name="more ".($count['c']-$elements[$j]->from) ." ".$name;
											$elements[$j]->img='images/icons/more.png';
											$elements[$j]->source=$subject;		
											$elements[$j]->function_to_call=$relation;
											$elements[$j]->isInverse='yes';
											if($iteration==0)$elements[$j]->isInverse='no';
										}
										return $elements;
									}
									
									/*Specific for bnodes, if $e is a bnode the code takes the relations for the bnode. 
									This query doesn't work for all the sites (for example doesn't work for British Museum).
									*/
									// var_dump($e);print"</br></br>";
									if($bnodes[$e]){
										$bnode_rel=$bnodes[$e];
										
										$elements[$j]->type = "uri";
										//Scan the type of the current element.
										if($types[$e]==null){
											$types[$e][]='bnode'; //If there is no type, the type is the predicate.
										}
										//Take the name from the list of labels.
										if($labels[$e]!=null){
											$elements[$j]->name=reset($labels[$e]);
											if($labels[$e][$lang])$elements[$j]->name=$labels[$e][$lang];
										}
										else $elements[$j]->name = $e;
										$elements[$j]->id ='bnode'.$e;
										$file_headers = @get_headers($images['bnode']);
										$elements[$j]->img = spql_get_image($elements[$j]->id,$types[$e]);
										//add relations of the first level of the blank nodes. The relations are considered only in one direction.
										$bnrelations=array();
										foreach($bnode_rel as $bnrel=>$bnprop){
											$bnrelations[$bnrel]->uri=$bnrel;
											$bnrelations[$bnrel]->name=truncate_uri($bnrel); //direct property
											$bnrelations[$bnrel]->img="";
											$j2=0;
											$bnelements=array();
											foreach($bnprop as $bnel){
												$bnelements[$j2]->type = "uri";
												//Scan the type of the current element.
												if($types[$bnel]==null){
													$types[$bnel][]=$bnrel; //If there is no type, the type is the predicate.
												}
												//Take the name from the list of labels.
												if($labels[$bnel]!=null){
													$bnelements[$j2]->name=reset($labels[$bnel]);
													if($labels[$bnel][$lang])$bnelements[$j2]->name=$labels[$bnel][$lang];
												}
												else $bnelements[$j2]->name = truncate_uri($bnel);
												$bnelements[$j2]->id =$bnel;
												$file_headers = @get_headers($images[$bnel]);
												if($file_headers[0] == 'HTTP/1.1 404 Not Found' || $images[$bnel]==NULL) $bnelements[$j2]->img = spql_get_image($bnelements[$j2]->id,$types[$bnel]);						
												else $bnelements[$j2]->img =$images[$bnel];
												$j2++;
												
											}
											$bnrelations[$bnrel]->elements=$bnelements;
										}
										$elements[$j]->relations=$bnrelations;				
										
									}
									else{
										$elements[$j]->type = "uri";
										//Scan the type of the current element.
										if($types[$e]==null){
											$types[$e][]=$key; //If there is no type, the type is the predicate.
										}
										//Take the name from the list of labels.
										if($labels[$e]!=null){
											$elements[$j]->name=reset($labels[$e]);
											if($labels[$e][$lang])$elements[$j]->name=$labels[$e][$lang];
										}
										else $elements[$j]->name = truncate_uri($e);
										$elements[$j]->id =$e;
										$file_headers = @get_headers($images[$e]);
										if($file_headers[0] == 'HTTP/1.1 404 Not Found' || $images[$e]==NULL) $elements[$j]->img = spql_get_image($elements[$j]->id,$types[$e]);						
										else $elements[$j]->img =$images[$e];
									}
									$j++;
								}
							}
						}
					} 						
				}
				else
					mysparql_error($endpoint." ERROR query: ".$sparql."\nerror:\n".$db->error."\n-----------");
			// }
		}
		else
			mysparql_error($endpoint." NOT ALIVE\n".$db->error."\n-----------");
		break;//remove this break for make it run for all the endpoints and not only for dbpedia.
	}
	return $elements;
}
//Decode the structure of the file returned from the query.
function decode_query($result,$predicate){
	if($predicate!=null)$p=$predicate;
	require_once( "sparqllib.php" );
	if( $result ) { //else error return sparql_errno() . ": " . sparql_error(). "\n";
		$literals=array();
		$uri_elements=array();
		$bnodes=array();
		$labels=array();
		global $types;
		$types=null;//Resets the contents for free the memory.
		$types=array();//Resets the contents for free the memory.
		$fields = sparql_field_array( $result );
		while( $row = sparql_fetch_array( $result )) //Divids literals from uri elements.
		{
			// print"ROW---   ";var_dump($row);print"</br></br>";
			$present=false;
			//without the cicle of the field
			if($row['p']){
				$p=$row['p'];
				if(!$uri_elements[$p]){$previous=false;}//Resets the previous element.
			}
			if($row['o']){
				//Checks if this object was already present. This because retrieving the label imply the duplication of the first element.
				if($previous!=$row['o']){
					if($uri_elements[$p]!=null){//Checks if there is already the property.
						foreach($uri_elements[$p] as $ue){
							if(urldecode($ue)==urldecode($row['o'])){ $present=true; break;}
						}
					}
					if($row['bnode']){
						if($row['o.type']=='uri'){
							$insert=true;
							if(!$bnodes[$row['bnode']]){
								$uri_elements[$p][]=$row['bnode'];
							}
							if($bnodes[$row['bnode']][$row['p2']]){
								foreach($bnodes[$row['bnode']][$row['p2']] as $ue){
									if(urldecode($ue)==urldecode($row['o'])){ $insert=false; break;}
								}
							}
							if($insert==true) $bnodes[$row['bnode']][$row['p2']][]=$row['o'];
						} 
						else if($row['o.type']=='literal'){ $literals[$p2][]=$row['o'];}
					}
					if($present==false){
						if($row['o.type']=='uri' && !$row['bnode']){								
							$uri_elements[$p][]=$row['o'];
						}
						else if($row['o.type']=='literal'){ $literals[$p][]=$row['o'];}
					}
				}
				else{
					$present=true;
				}
			}
			if($row['l']){ //it's a label
				$l['text']=$row['l'];
			// }
			// if($row['lang'] && $l){ //it's a label
				if($row['lang'])$l['lang']=$row['lang'];
				else $l['lang']='no lang';
				// if($l['text']!="")$labels[$row['o']][]=$l;
				if($l['text']!="")$labels[$row['o']][$l['lang']]=$l['text'];
			}
			//Display the depiction as the node's image.
			if($row['d'])	$images[$row['o']]=$row['d'];
			if($row['t']){//t is the type of an o
				if($row['t']!=""){
					if($present){//Searches in the type if the object is already present.								
						$flag=false;
						if($types[$row['o']]!=null){
							foreach($types[$row['o']] as $t){
								if($t==$row['t']){ $flag=true; break;}//If the type is present doesn't add that.
							}
						}
						if($flag==false)$types[$row['o']][]=$row['t'];
					}
					else{
						$types[$row['o']][]=$row['t'];									
						$previous=$row['o'];
					}
				}
				else{
					if($previous!=$row['o']) $previous=$row['o'];
				}
			}
		}
		$data=array();
		$data['labels']=$labels;
		$data['uri_elements']=$uri_elements;
		$data['bnodes']=$bnodes;
		$data['literals']=$literals;
		$data['images']=$images;
		// print"TYPE:----   ";var_dump($types);print"</br>";
		// print"DATA:----   ";var_dump($data);print"</br></br>";
		return $data;
	}
}
//Gets image for the node.
function spql_get_image($uri,$types=array()){
	include('config.php');
	/*Searches if there is an image associated at the type. 
	If there are more than one type with an image, takes the one with more priority (The first from the head of the file) 
	$index is the position of the actual image associated.
	*/
	$img=$node_default_image;
	$index=count($image_for_type);
	foreach($types as $t){
		if($image_for_type[$t]){
			$t_index=array_search($t, array_keys($image_for_type));
			if($t_index<$index){$img='images/icons/'.$image_for_type[$t];$index=$t_index;}
		}
	}
	if($img==$node_default_image){//If no change appends. Checks in sub string.
		$index=count($image_for_substring);
		foreach($types as $t){
			foreach($image_for_substring as $is=>$imm){
				if(strpos ( $t , $is )!=false){//If the sub string is present.
					$t_index=array_search($is, array_keys($image_for_substring));
					if($t_index<$index){$img='images/icons/'.$imm;$index=$t_index;}
				}
			}
		}
	}
	return $img;
}
//Get image for the node.
function spql_get_first_info($uri,$connection){
	require_once( "sparqllib.php" );
	include('config.php');
	global $max_rows_4query;
	$lang=$label_lang;
	$types=null;
	$types=array();
	$endpoint=$connection;
	$db = sparql_connect( $endpoint );
	if( $db->alive() ) //else not alived.
	{
		sparql_ns( "rdfs","http://www.w3.org/2000/01/rdf-schema#" );
		sparql_ns("rdf","http://www.w3.org/1999/02/22-rdf-syntax-ns#");
		sparql_ns( "foaf","http://xmlns.com/foaf/0.1/" );
		sparql_ns( "SiiMobility","http://www.disit.dinfo.unifi.it/SiiMobility#" );
		if( !$db ) { print sparql_errno() . ": " . sparql_error(). "\n"; exit; }
		
		$opt_label=" <".$uri ."> rdfs:label ?l ";
		// $first_query="SELECT ?t WHERE { <".$uri ."> rdf:type ?t }";
		$first_query=get_query("type",$uri);
		$result = sparql_query( $first_query );
		if( $result ) { 		
			$fields = sparql_field_array( $result );
			while( $row = sparql_fetch_array( $result ) )$types[]=$row['t'];
		}
		// $first_query="SELECT ?l (lang(?l) as ?lang) WHERE { ".$opt_label ." }";
		$first_query=get_query("label",$opt_label);
		$result = sparql_query( $first_query );
		$labels;
		if( $result ) { 		
			$fields = sparql_field_array( $result );
			while( $row = sparql_fetch_array( $result ) )
			{
				if($row['l']){ //it's a label
					$l['text']=$row['l'];
				// }
				// if($l){ //it's a label
					if($row['lang'])$l['lang']=$row['lang'];
					else $l['lang']='no lang';
					// if($l['text']!="")$labels[]=$l;
					if($l['text']!="")$labels[$l['lang']]=$l['text'];
				}
			}				
		}
		//-------
		// $obj_label=$labels[0];
		if($labels!=null){
			$obj_label=reset($labels);
			if($labels[$lang]) $obj_label =$labels[$lang];
			
		}
		//For the image
		// $sparql="SELECT ?d WHERE {<".$uri ."> foaf:depiction ?d}";
		$sparql=get_query("depiction",$uri);
		$result = sparql_query( $sparql );
		if( $result ) { 		
			$fields = sparql_field_array( $result );
			while( $row = sparql_fetch_array( $result ) )
			{
				$file_headers = @get_headers($row['d']);
				if($file_headers[0] != 'HTTP/1.1 404 Not Found') {
					$info['label']=$obj_label;
					$info['image']=$row['d'];
					return $info;
				}
			}				
		}
	}
	else	mysparql_error($endpoint." NOT ALIVE\n".$db->error."\n-----------");
	/*Search if there is an image associated at the type. 
	If there are more than one type with an image, is take the one with more priority (The first from the head of the file) */
	require_once("config.php");
	$img=$node_default_image;
	$index=count($image_for_type);
	foreach($types as $t){
		if($image_for_type[$t]){
			$t_index=array_search($t, array_keys($image_for_type));
			if($t_index<$index){$img='images/icons/'.$image_for_type[$t];$index=$t_index;}
		}
	}
	if($img==$node_default_image){//If no change appends. Checks in sub string.
		$index=count($image_for_substring);
		foreach($types as $t){
			foreach($image_for_substring as $is=>$imm){
				if(strpos ( $t , $is )!=false){//If the sub string is present.
					$t_index=array_search($is, array_keys($image_for_substring));
					if($t_index<$index){$img='images/icons/'.$imm;$index=$t_index;}
				}
			}
		}
	}
	$info['label']=$obj_label;
	$info['image']=$img;
	return $info;
}
function get_literals($uri="<http://dbpedia.org/resource/Fiat>",$connection="http://dbpedia.org/sparql"){
	/* Gets the informations for the $uri passed. The info are the literals property of the node. */
	require_once( "sparqllib.php" ); 
	require( "config.php" ); 
	global $endpoints;
	global $max_rows_4query;
	if($max_rows_4query==null){
		$EP=get_endpoint($connection);
		$max_rows_4query=$EP["limit"];
	}
	$relations=array();
	$literals=array();
	$lang=$label_lang;
	// foreach( $endpoints as $endpoint=>$desc)
	// {
	while(true){//xxxxxx
		$endpoint=$connection;//xxxxxx
		$db = sparql_connect( $endpoint );
		if( $db->alive() ) //else not alived.
		{
			for($iteration=0;$iteration<2;$iteration++ ){//first iteration for direct literals and second for inverse.
				//Add prefix with saprql_ns
				sparql_ns( "rdfs","http://www.w3.org/2000/01/rdf-schema#" );
				sparql_ns( "rdf" ,"http://www.w3.org/1999/02/22-rdf-syntax-ns#");
				sparql_ns( "foaf","http://xmlns.com/foaf/0.1/" );
				if( !$db ) { print sparql_errno() . ": " . sparql_error(). "\n"; exit; }				
				/*Counts the result for each objects. The predicate wich have more than $count_threshold rows are chosen separately.*/
				// if($iteration==0) $query_for_count="Select ?p (count(*) as ?c) where {".$uri ." ?p ?o} group by ?p";
				// else $query_for_count="Select ?p (count(*) as ?c) where {?s ?p ".$uri ."} group by ?p";
				if($iteration==0) $query_for_count=get_query("count1",$uri);
				else $query_for_count=$query_for_count=get_query("countr",$uri);;
				$result = sparql_query( $query_for_count );
				$list_single_query=array();
				if($result){
					$fields = sparql_field_array( $result );		
					while( $row = sparql_fetch_array( $result )) //Divids literals from uri elements.
					{
						if((int)($row['c'])>15) $list_single_query[]=$row['p'];						
					}
				}
				$filter=" ";
				
				if($list_single_query!=null)$filter="filter(?p not in (<".implode('>,<',$list_single_query) .">))";
				// if($iteration==0) $first_query="SELECT ?p ?o ?l (lang(?l) as ?lang) ?d ?t WHERE {".$uri." ?p ?o. optional{ ?o rdfs:label ?l } optional{?o rdf:type ?t} optional{?o foaf:depiction ?d} ".$filter ."}";
				// else $first_query="SELECT ?p ?o ?l (lang(?l) as ?lang) ?d ?t WHERE {?o ?p ".$uri ." optional{ ?o rdfs:label ?l } optional{?o rdf:type ?t} optional{?o foaf:depiction ?d} ".$filter ."}";
				if($iteration==0) $first_query="SELECT ?p ?l (lang(?l) as ?lang) WHERE {".$uri." ?p ?l. FILTER isLiteral(?l)}";
				else $first_query="SELECT ?p ?l (lang(?l) as ?lang) WHERE {?l ?p ".$uri ." FILTER isLiteral(?l)}";
				$result = sparql_query( $first_query );
				
				// if($iteration==0)print "request for objects: ".(microtime(true)-$startt) ."</br>";
				// else print "request for subjects: ".(microtime(true)-$startt)."</br>";
				// print"</br></br>";				
				if($result){
					while( $row = sparql_fetch_array( $result ) )//Creates the structures of the literals.
					{
						$prop_name=$row['p'];
						//Changes the name with the prefix if 'change_with_prefix' is true.
						if($change_with_prefix){
							$first_part_uri=truncate_uri($prop_name,true);
							if($label_for_uri[$first_part_uri]!=null)$prop_name=$label_for_uri[$first_part_uri]. truncate_uri($prop_name);
						}
						
						if($row['lang'])$l_lang=$row['lang'];
						else $l_lang='no lang';
						$literals[$prop_name][$l_lang][] = $row['l'];
					}
				}
				// if( $result ) { //else error return sparql_errno() . ": " . sparql_error(). "\n";
					// $data=decode_query($result,null);
					// // var_dump($data);
					// $data_literals=$data['literals'];
					// // var_dump($data_literals);
					// // foreach($data_literals as $d){
						// // var_dump($d);print"</br>----------</br>";
						// // var_dump($data_literals[$d][$lang]);
					// // }
					// if($data['literals']!=null)array_push($literals,$data['literals']);
				// }
			}
		}
		else mysparql_error($endpoint." NOT ALIVE\n".$db->error."\n-----------");
		break;//remove this break for make it run for all the endpoints and not only for the first.
	}	
	$result=array();
	if($literals!=null){
		foreach($literals as $name=>$l){
		/* Creates the structure to return to the client. The structure is an array of array with 2 parameter: 
		String name = the uri of the property. Array String value = the literal value. */
			$first_part_uri=truncate_uri($name,true);
			$lit['name']=$name;
			if($l[$lang]){
				if(count($l[$lang])==1)$lit['value']=$l[$lang];
				else $lit['value']=$l[$lang][0];
			}
			else{ $lit['value']=array_shift(array_values($l));}
			$result[]=$lit;
		}
	}
	else{$lit['name']="no other informations";$lit['value']=""; $result[]=$lit;}
	return $result;
}
function get_suggestion($endpoint_selected, $text, $type, $search_InClass){
	/*
	$type: specify the type of search.
	$text: is the part of text to search.
	$search_InClass: is used for distinguish between searches with keyword and searches in a specified class.
	*/
	require_once( "config.php" );
	global $suggestion_number;
	global $endpoint_with_suggest;
	require_once( "sparqllib.php" );
	$db = sparql_connect( $endpoint_selected );
	$suggestion=array();
	if( $db->alive() ) //else not alived.
	{
		//Add prefix with saprql_ns
		sparql_ns( "rdfs","http://www.w3.org/2000/01/rdf-schema#" );
		sparql_ns( "foaf","http://xmlns.com/foaf/0.1/" );
		if($type=="owlim") sparql_ns( "fts","http://www.ontotext.com/owlim/fts#");
		if( !$db ) { print sparql_errno() . ": " . sparql_error(). "\n"; exit; }
		//Costruct the query.
		$subquery="";
		if($search_InClass=='keyword'){
			// print "ciao";
		 	//var_dump($endpoint_with_suggest);
			if(!$endpoint_with_suggest[$endpoint_selected])return $suggestion;
			foreach ($endpoint_with_suggest[$endpoint_selected] as $dataprop){
				if($subquery!="")$subquery.=" UNION ";
				switch($type){
					case 'owlim':
						$search_value=str_replace(" ",":",$text).":";//Replaces the space with : A specific for Owlim
						$subquery.="{?s ".$dataprop ." ?label . <".$search_value ."> fts:prefixMatchIgnoreCase ?label.  FILTER(!isBlank(?s))} ";
						break;
					case 'contains':
						$subquery.="{?s ".$dataprop ." ?label FILTER(contains(str(lcase(?label)),lcase('".$text ."')))} ";
						break;
					case 'regex':
						$subquery.="{?s ".$dataprop ." ?label FILTER regex(str(?label), '".$text ."', 'i')} ";
						break;
				}
			}
			$suggest_query="SELECT DISTINCT ?s (str(?label) as ?label_str) WHERE{".$subquery ."} LIMIT ".$suggestion_number;
		}
		else{//for a search in a class the query change.
			// $subquery="{?s ?p <".$search_InClass ."> FILTER regex(str(?s), '".$text ."', 'i')} ";
			switch($type){
				case 'owlim':
					$search_value=str_replace(" ",":",$text).":";//Replace the space with :
					// $subquery.="{?s ".$dataprop ." ?label . <".$search_value ."> fts:prefixMatchIgnoreCase ?label.  FILTER(!isBlank(?s))} ";
					$subquery="{?s a <".$search_InClass .">. ?s ?p ?label .<".$search_value ."> fts:prefixMatchIgnoreCase ?label. FILTER (isLiteral(?label))} ";
					break;
				case 'contains':
					$subquery="{?s a <".$search_InClass .">. ?s ?p ?label FILTER (isLiteral(?label) && contains(str(lcase(?label)), lcase('".$text ."')))} ";
					// $subquery.="{?s ".$dataprop ." ?label FILTER(contains(str(lcase(?label)),lcase('".$text ."')))} ";
					break;
				case 'regex':
					// $subquery.="{?s ".$dataprop ." ?label FILTER regex(str(?label), '".$text ."', 'i')} ";
					$subquery="{?s a <".$search_InClass .">. ?s ?p ?label FILTER (isLiteral(?label) && regex(str(?label), '".$text ."', 'i'))} ";
					break;
			}
			$suggest_query="SELECT DISTINCT ?s (str(?label) as ?label_str) WHERE{".$subquery ."} LIMIT ".$suggestion_number;
		} 
		//var_dump($suggest_query);print"</br></br>";
		// return;
		$result = sparql_query( $suggest_query );
		if($result){
			$j=0;
			while( $row = sparql_fetch_array( $result ) )
			{
				$suggestion[$j]['URI']=$row['s'];
				$suggestion[$j]['Label']=$row['label_str'];
				$j++;				
			}
		} else {
			print sparql_error(). "\n"; 
		}
	}
	else{ mysparql_error($endpoint_selected." NOT ALIVE\n".$db->error."\n-----------");}
	
	return $suggestion;
}

function get_endpoint($endP_url){
	require( "config.php" );
	foreach($sparql_endpoints as $EP){
		// var_dump($EP["endpoint"]);print"</br>";var_dump($endP_url);print"</br>";
		if($EP["endpoint"]==$endP_url){return $EP;}
	}
	//If is not present set the number of max row to default config options.
	$NewEP=array();
	$NewEP["limit"]=$max_result_number;
	return $NewEP;
}

function query_count($uri, $sparql, $sparql_list) {//Counts the number of results for the uri specified.
	#require_once("config.php");
	require_once( "sparqllib.php" );
	#var_dump(json_decode($sparql_list));
	$sparql_list_array = json_decode($sparql_list);
	$element_searched = "<" . $uri . ">"; //Searches in the other endpoint.
	$j = 0; //Counters of query made
	$queries_made = array();
	$found = false; //If the $sparql is not found, makes a specific query to that. Case for 'Your Data' request. 

	foreach ($sparql_list_array as $ep) {
		if ($ep != $sparql) {
			$db = sparql_connect($ep);
			$query = "Select (count(*) as ?c) where {?s ?p " . $element_searched . " .FILTER(!isBlank(?s) && !isLiteral(?s))}";
			if ($db->alive()) { //else not alived.
				$result = sparql_query($query);
				if ($result) {
					while ($row = sparql_fetch_array($result)) {
						$queries_made[$j]['endpoint'] = $ep;
						$queries_made[$j]['count'] = $row['c'];
						$j++;
					}
				}
			}
		} else {
			$found = true;
			$db = sparql_connect($ep);
			$query = "Select (count(*) as ?c) where {?s ?p " . $element_searched . " .FILTER(!isBlank(?s) && !isLiteral(?s))}"; //outbound query
			if ($db->alive()) { //else not alived.
				$result = sparql_query($query);
                if ($result) {
					while ($row = sparql_fetch_array($result)) {
						$queries_made[$j]['endpoint'] = $ep;
						$queries_made[$j]['count_in'] = $row['c'];
					}
				}
				$query = "Select (count(*) as ?c) where {" . $element_searched . " ?p ?s .FILTER(!isBlank(?s) && !isLiteral(?s))}"; //inbound query
				$result = sparql_query($query);
				if ($result) {
					while ($row = sparql_fetch_array($result)) {
						$queries_made[$j]['count_out'] = $row['c'];
						$j++;
					}
				}
			}
		}
	}
	if ($found == false) {//If the endpoint is not found, makes a specific call.
		$db = sparql_connect($sparql);
		$query = "Select (count(*) as ?c) where {?s ?p " . $element_searched . " .FILTER(!isBlank(?s) && !isLiteral(?s))}"; //outbound query
		if ($db->alive()) { //else not alived.
			$result = sparql_query($query);
			if ($result) {
				while ($row = sparql_fetch_array($result)) {
					$queries_made[$j]['endpoint'] = $sparql;
					$queries_made[$j]['endpoint_name'] = $sparql;
					$queries_made[$j]['count_in'] = $row['c'];
				}
			}
			$query = "Select (count(*) as ?c) where {" . $element_searched . " ?p ?s .FILTER(!isBlank(?s) && !isLiteral(?s))}"; //inbound query
			$result = sparql_query($query);
			if ($result) {
				while ($row = sparql_fetch_array($result)) {
					$queries_made[$j]['count_out'] = $row['c'];
					$j++;
				}
			}
		}
	}
	return $queries_made;
}

function query_columns_view($uri,$sparql,$inbound,$offset,$limit){//Returns the result of a inverse specified query
	require_once( "sparqllib.php" );
	$element_searched="<".$uri .">";//Search in the other endpoint.
	$j=0;//Counter of query made
	$queries_made=array();
	$db = sparql_connect( $sparql );
	sparql_ns( "rdfs","http://www.w3.org/2000/01/rdf-schema#" );
	$opt_label=" ?subject rdfs:label ?label ";
	// if($inbound==0)$query="Select ?subject ?property where {".$element_searched ." ?property ?subject .FILTER(!isBlank(?subject) && !isLiteral(?subject))} LIMIT 100";
	// else $query="Select ?subject ?property where {?subject ?property ".$element_searched ." .FILTER(!isBlank(?subject) && !isLiteral(?subject))} LIMIT 100";
	if($inbound==0)$query="Select ?subject ?property ?label (lang(?label) as ?lang) where {{SELECT ?subject ?property WHERE{".$element_searched ." ?property ?subject .FILTER(!isBlank(?subject) && !isLiteral(?subject))} OFFSET $offset LIMIT $limit } optional{ $opt_label }}";
	else $query="Select ?subject ?property ?label (lang(?label) as ?lang) where {{SELECT ?subject ?property WHERE{?subject ?property ".$element_searched ." .FILTER(!isBlank(?subject) && !isLiteral(?subject))} OFFSET $offset LIMIT $limit} optional{ $opt_label }}";
	// var_dump($query);print"</br></br>";
	// echo($ep['endpoint']);print"</br>";
	if( $db->alive() ) //else not alived.
	{
		// var_dump($query);print"</br></br>";return;
		//Add prefix with saprql_ns
		$result = sparql_query( $query );
		if($result){
			$previous_property="";
			$previous_uri="";
			while( $row = sparql_fetch_array( $result ) )
			{
				if($previous_property!=$row['property'] || $previous_uri!=$row['subject']){
					if($j>0){
						if($queries_made[$j-1]['subject_name']=="")$queries_made[$j-1]['subject_name']=truncate_uri($queries_made[$j-1]['subject']);//If no label are founds.
					}
					$queries_made[$j]['subject']=$row['subject'];
					if($row['label']!=null)$queries_made[$j]['subject_name']=$row['label'];
					else $queries_made[$j]['subject_name']=truncate_uri($row['subject']);
					// $queries_made[$j]['endpoint']=$sparql;
					$queries_made[$j]['property']=$row['property'];
					$queries_made[$j]['property_name']=truncate_uri($row['property']);//XXXXXXXXX Differentiate from inverse!?				
					$previous_property=$row['property'];
					$previous_uri=$row['subject'];
					$j++;
				}
				else{//Checks only for the label.
					if($row['lang']=='en')$queries_made[$j-1]['subject_name']=$row['label'];
				}
			}
		}
	}			
	// echo($ep['endpoint']);print"</br></br>";	
	return $queries_made;
}

function more_columns_view($uri,$property,$inbound,$sparql,$offset,$limit,$key){//Returns the results of a property with more than '$results_n' results.
	require_once( "sparqllib.php" );
	$element_searched=$uri;//Search in the other endpoint.
	$property_searched="<".$property .">";//Search in the other endpoint.
	$j=0;//Counter of query made
	$queries_made=array();
	$db = sparql_connect( $sparql );
	//Checks the type of search for $sparql.
	$subquery="";
	$EP=get_endpoint($sparql);
	$search_type=$EP['search_type'];
	if(!$search_type) $search_type='regex';	
	if($key!=""){
		switch($search_type){
			case 'owlim':
				// sparql_ns( "fts","http://www.ontotext.com/owlim/fts#");
				// $search_value=str_replace(" ",":",$key).":";//Replaces the space with : .is a specific for Owlim
				// $subquery.=".<".$search_value ."> fts:prefixMatchIgnoreCase ?label ";
				$subquery.=".FILTER regex(str(?label), '".$key ."', 'i') ";
				
				break;
			case 'contains':
				$subquery.=".FILTER(contains(str(lcase(?label)),lcase('".$key ."'))) ";
				break;
			case 'regex':
				$subquery.=".FILTER regex(str(?label), '".$key ."', 'i') ";
				break;
			case 'dbpedia'://The same of regex
				$subquery.=".FILTER regex(str(?label), '".$key ."', 'i') ";
				break;
		}
	}
	$opt_label=" ?subject rdfs:label ?label ";
	$query_for_count='';
	if($key!=""){
		if($inbound=='no'|| $inbound=='false')$query_for_count="Select (count(distinct ?subject) as ?c) where { ".$element_searched ." <".$property ."> ?subject .$opt_label $subquery .FILTER(!isBlank(?subject) && !isLiteral(?subject))}";
		else $query_for_count="Select (count(distinct ?subject) as ?c) where {?subject <".$property ."> ".$element_searched ." .$opt_label $subquery .FILTER(!isBlank(?subject) && !isLiteral(?subject))}";
	}
	else{
		if($inbound=='no'|| $inbound=='false')$query_for_count=get_query("count2b",$element_searched,$property);
		else $query_for_count=get_query("count2br",$element_searched,$property);
	}
	// var_dump($query_for_count);
	
	sparql_ns( "rdfs","http://www.w3.org/2000/01/rdf-schema#" );
	// $suggest_query="SELECT DISTINCT ?s (str(?label) as ?label) WHERE{".$subquery ."} LIMIT ".$suggestion_number;
	if($key==""){
		if($inbound=='no'|| $inbound=='false')$query="Select ?subject ?label (lang(?label) as ?lang) where {{SELECT ?subject WHERE{".$element_searched ." ".$property_searched ." ?subject .FILTER(!isBlank(?subject) && !isLiteral(?subject))} OFFSET $offset LIMIT $limit } optional{ $opt_label }}";
		else $query="Select ?subject ?label (lang(?label) as ?lang) where {{SELECT ?subject ?property WHERE{?subject ".$property_searched ." ".$element_searched ." .FILTER(!isBlank(?subject) && !isLiteral(?subject))} OFFSET $offset LIMIT $limit} optional{ $opt_label }}";
	}
	else{
		if($inbound=='no'|| $inbound=='false')$query="Select ?subject ?label (lang(?label) as ?lang) where {{SELECT ?subject ?label WHERE{".$element_searched ." ".$property_searched ." ?subject .$opt_label $subquery .FILTER(!isBlank(?subject) && !isLiteral(?subject))} OFFSET $offset LIMIT $limit } }";
		else $query="Select ?subject ?label (lang(?label) as ?lang) where {{SELECT ?subject ?label WHERE{?subject ".$property_searched ." ".$element_searched ." .$opt_label $subquery .FILTER(!isBlank(?subject) && !isLiteral(?subject))} OFFSET $offset LIMIT $limit} }";
	}
	
	if( $db->alive() ) //else not alived.
	{
		//Counts the number of elements for the query.
		$result_c = sparql_query( $query_for_count );
		if($result_c){
			$row=sparql_fetch_array($result_c);
			$queries_made['count']=$row['c'];		
		}
		//Add prefix with saprql_ns
		$result = sparql_query( $query );
		if($result){
			$previous_property="";
			$previous_uri="";
			while( $row = sparql_fetch_array( $result ) ){
				if($previous_property!=$row['property'] || $previous_uri!=$row['subject']){
					if($j>0){
						if($queries_made['results'][$j-1]['subject_name']=="")$queries_made['results'][$j-1]['subject_name']=truncate_uri($queries_made[$j-1]['subject']);//If no label are founds.
					}
					$queries_made['results'][$j]['subject']=$row['subject'];
					if($row['label']!=null)$queries_made['results'][$j]['subject_name']=$row['label'];
					else $queries_made['results'][$j]['subject_name']=truncate_uri($row['subject']);
					$previous_property=$row['property'];
					$previous_uri=$row['subject'];
					$j++;
				}
				else{//Checks only for the label.
					if($row['lang']=='en')$queries_made['results'][$j-1]['subject_name']=$row['label'];
				}
			}
		}
	}			
	return $queries_made;
}

function LD_more_columns_view($uri,$property,$inbound,$sparql,$offset,$limit){//Returns the results of a property with more than '$results_n' results.
	include_once("includes/arc/ARC2.php");
	include_once("includes/Graphite.php");
	require("config.php");
	$graph = new Graphite();
	$graph->load( $uri );
	$resource=$graph->resource( $uri );	
	$node_relations = $resource->relations();
	// var_dump($node_relations);
	$queries_made=array(); //the result of the function.
	// var_dump($relation);print"</br>";
	$relations=array();
	$elements=array();
	foreach($node_relations as $r){
		$count=0;
		$j=0;
		$rels=$resource->all($r);//rels = All the relations loaded.
		// var_dump($r->toString());print"</br>";
		if($r->toString()==$property){
			//Counts the number of elements for $property.
			$queries_made['count']=count($rels);
			
			//Saves the elements.
			foreach($rels as $e){				
				// $graph->load( $e );
				// $ne=$graph->resource($e);			
				// if($ne->type()=='[NULL]'){//The node is literal.
					// $count--;
				// }
				if(get_class($e)=="Graphite_Literal"){//If the node is literal.
					$count--;
				}
				elseif($count>=$offset){
					// if($j>0){
						// if($queries_made['results'][$j-1]['subject_name']=="")$queries_made['results'][$j-1]['subject_name']=truncate_uri($queries_made[$j-1]['subject']);//If no label are founds.
					// }
					$queries_made['results'][$j]['subject']=$e->toString();
					if($e->hasLabel())$queries_made['results'][$j]['subject_name']=$e->label();
					else $queries_made['results'][$j]['subject_name']=truncate_uri($e->toString());
					// $previous_property=$row['property'];
					// $previous_uri=$row['subject'];
					
					// $elements[$j]->id=$e->toString();
					// $elements[$j]->type="LD";				
					// // print "&nbsp;&nbsp; label:";
					// if($e->hasLabel()){
						// // print $e->label(); 
						// $elements[$j]->name=$e->label();
					// }
					// else{
						// // print "No-Label truncated: ".truncate_uri($e);
						// $elements[$j]->name=truncate_uri($e->toString());
					// }
					// $r_types=$e->types();//Gets the types.
					// $types=array();
					// foreach($r_types as $r_t){
						// $types[]=$r_t->toString();
					// }
					// $elements[$j]->img=spql_get_image($elements[$count]->id,$types);
					$j++;
				}
				if($count==$offset+$limit-1){//Pass the limit.
					if(count($rels)-($offset+$limit)==0)break;
					// print "Adds more object";
					// $elements[$j]->type = "more_LD";
					// $elements[$j]->from=$offset+5; //L'ultimo elemento trovato.
					// $elements[$j]->name="more ".(count($rels)-$elements[$j]->from) ." ".truncate_uri($relation);
					// // $elements[$j]->name="more CONTATORE ".truncate_uri($r->toString());
					// $elements[$j]->img='images/icons/more.png';
					// $elements[$j]->source=$subject;//TOCHECK source?		
					// $elements[$j]->function_to_call=$r->toString();
					// $elements[$j]->isInverse='no';
					break;
				}
				$count++;			
			}
		}
	}
	return $queries_made;
	// require_once( "sparqllib.php" );
	// $element_searched=$uri;//Search in the other endpoint.
	// $property_searched="<".$property .">";//Search in the other endpoint.
	// $j=0;//Counter of query made
	// $queries_made=array();
	// $db = sparql_connect( $sparql );
	// if($inbound=='no')$query_for_count=get_query("count2b",$element_searched,$property);
	// else $query_for_count=get_query("count2br",$element_searched,$property);
	// sparql_ns( "rdfs","http://www.w3.org/2000/01/rdf-schema#" );
	// $opt_label=" ?subject rdfs:label ?label ";
	// if($inbound=='no')$query="Select ?subject ?label (lang(?label) as ?lang) where {{SELECT ?subject WHERE{".$element_searched ." ".$property_searched ." ?subject .FILTER(!isBlank(?subject) && !isLiteral(?subject))} OFFSET $offset LIMIT $limit } optional{ $opt_label }}";
	// else $query="Select ?subject ?label (lang(?label) as ?lang) where {{SELECT ?subject ?property WHERE{?subject ".$property_searched ." ".$element_searched ." .FILTER(!isBlank(?subject) && !isLiteral(?subject))} OFFSET $offset LIMIT $limit} optional{ $opt_label }}";
	// if( $db->alive() ) //else not alived.
	// {
		// //Counts the number of elements for the query.
		// $result_c=sparql_query($query_for_count);
		// if($result_c){
			// $row=sparql_fetch_array($result_c);
			// $queries_made['count']=$row['c'];		
		// }
		// //Add prefix with saprql_ns
		// $result = sparql_query( $query );
		// if($result){
			// $previous_property="";
			// $previous_uri="";
			// while( $row = sparql_fetch_array( $result ) )
			// {
				// if($previous_property!=$row['property'] || $previous_uri!=$row['subject']){
					// if($j>0){
						// if($queries_made['results'][$j-1]['subject_name']=="")$queries_made['results'][$j-1]['subject_name']=truncate_uri($queries_made[$j-1]['subject']);//If no label are founds.
					// }
					// $queries_made['results'][$j]['subject']=$row['subject'];
					// if($row['label']!=null)$queries_made['results'][$j]['subject_name']=$row['label'];
					// else $queries_made['results'][$j]['subject_name']=truncate_uri($row['subject']);
					// $previous_property=$row['property'];
					// $previous_uri=$row['subject'];
					// $j++;
				// }
				// else{//Checks only for the label.
					// if($row['lang']=='en')$queries_made['results'][$j-1]['subject_name']=$row['label'];
				// }
			// }
		// }
	// }			
	// return $queries_made;
}
function search_classes($sparql){
	require( "config.php" );
	require_once( "sparqllib.php" );
	"PREFIX owl:<http://www.w3.org/2002/07/owl#>";
	$queries_made=array();
	$db = sparql_connect( $sparql );
	sparql_ns( "owl","http://www.w3.org/2002/07/owl#" );
	sparql_ns( "rdfs","http://www.w3.org/2000/01/rdf-schema#" );
	if( $db->alive() ) //else not alived.
	{
		$j=0;//number of elements.
		$query="Select distinct ?class where{{?class a rdfs:Class}union{?class a owl:Class} filter(!isBlank(?class))} order by ?class LIMIT ".$num_classes ." ";
		$result = sparql_query( $query );
		if($result){
			$classes=array();
			while( $row = sparql_fetch_array( $result ) )
			{
				// var_dump($row['class']);print"</br></br>";
				$classes[$j]=$row['class'];
				$j++;
			}
			return $classes;
		}
	}
	return "";
}

function get_OD($uri,$graph=null){
	/* This function gets the information for $uri, not via sparql but with OD and LD. 
	$graph is the graph eventually already loaded in the case of first_query.
	*/
	include_once("includes/arc/ARC2.php");
	include_once("includes/Graphite.php");
	require("config.php");
	if(!$graph)$graph = new Graphite();
	// $uri = "http://data.ordnancesurvey.co.uk/id/postcodeunit/SO171BJ";
	// $uri = "http://statistics.data.gov.uk/id/statistical-geography/E19000002";
	// $uri = "http://www.disit.dinfo.unifi.it/SiiMobility/048001";//Simobility link.Not work
	// $uri = "http://dbpedia.org/class/yago/1989VideoGames";
	// $uri = "http://www.eclap.eu/resource/term/501"; //Eclap
	if($graph)$graph->load( $uri );
	$resource=$graph->resource( $uri );
	$node_relations = $resource->relations();
	$j=0;
	$relations=array();
	foreach($node_relations as $r){
		// print "Relation: ".$r;
		// var_dump($r);
		$rels=$resource->all($r);//rels = All the relations loaded.
		
		// print "</br>";
		$count=0;
		
		$elements=array();
		// print"</br>Count di rels:".count($rels)."</br>";
		// var_dump($rels);
		foreach($rels as $e){				
			// var_dump($rels);
			// print "</br>&nbsp;&nbsp;&nbsp;&nbsp; element: ";
			// print($e->type());print"</br></br>";
			// print $e->toString();
			// print "</br> TYPE:".gettype($e)."</br>";
			// print "</br> GetString: ".$e->getString()."</br>";
			// print "</br> DataType: ".$e->datatype()."</br>";
			// print "GetOD";print"</br></br>";return;
			// $graph->load( $e );
			// $ne=$graph->resource($e);			
			// print "&nbsp;&nbsp; type:";
			// print $ne->type();
			// if($ne->type()=='[NULL]'){//The node is literal.
				// // print "</br> LITERAL </br>";
				// $count--;
			// }
			if(get_class($e)=="Graphite_Literal"){//If the node is literal.
				$count--;
			}
			else{
				// print "Added</br>";
				$elements[$count]->id=$e->toString();
				$elements[$count]->type="LD";				
				// print "&nbsp;&nbsp; label:";
				if($e->hasLabel()){
					// print $e->label(); 
					$elements[$count]->name=$e->label();
					// $elements[$count]->name=$e->label();
				}
				else{
					// print "No-Label truncated: ".truncate_uri($e);
					$elements[$count]->name=truncate_uri($e->toString());
				}
				$r_types=$e->types();//Gets the types.
				$types=array();
				foreach($r_types as $r_t){
					$types[]=$r_t->toString();
				}
				$elements[$count]->img=spql_get_image($elements[$count]->id,$types);
			}
			if($count==5){
				// print "Adds more object";
				$elements[$count]->type = "more_LD";
				$elements[$count]->from=$results_n; //L'ultimo elemento trovato.
				$elements[$count]->name="more ".(count($rels)-$elements[$count]->from) ." ".truncate_uri($r->toString());
				// $elements[$count]->name="more CONTATORE ".truncate_uri($r->toString());
				$elements[$count]->img='images/icons/more.png';
				$elements[$count]->source=$uri;//TOCHECK source?		
				$elements[$count]->function_to_call=$r->toString();
				$elements[$count]->isInverse='no';
				break;
			}
			$count++;			
		}
		// print "ELEMENTS: ".$elements;
		// var_dump($elements);
		// print "Count: ".count($elements);
		if (!empty($elements)){
			// print"Add the relation. ";
			// $relations[$r];//Add the relations.
			$relations[$r->toString()]->uri=$r->toString();
			$relations[$r->toString()]->type=truncate_uri($r->toString());
			$prop_name=truncate_uri($r->toString());
			//Changes the name with the prefix if 'change_with_prefix' is true.
			if($change_with_prefix){
				$first_part_uri=truncate_uri($r->toString(),true);
				if($label_for_uri[$first_part_uri]!=null)$prop_name=$label_for_uri[$first_part_uri]. truncate_uri($r->toString());
			}
			$relations[$r->toString()]->name=$prop_name;
			$relations[$r->toString()]->img="";
			$relations[$r->toString()]->elements=$elements;
			
		}
		if($j==1) break; 
		// print "</br>";
	}	
	// print "</br>";
	// print "</br>";
	// print "</br>";
	// var_dump($relations);
	// print "</br>";
	// print "</br>";
	// print "</br>";
	return $relations;
} 
function get_first_OD($uri="http://data.ordnancesurvey.co.uk/id/postcodeunit/SO171BJ",$connection="http://dbpedia.org/sparql",&$warning=false){
	include_once("includes/arc/ARC2.php");
	include_once("includes/Graphite.php");
	require("config.php");
	$graph = new Graphite();
	
	/* Some working uri for OD.
	$uri = "http://data.ordnancesurvey.co.uk/id/postcodeunit/SO171BJ"; 
	$uri = "http://statistics.data.gov.uk/id/statistical-geography/E19000002";
	$uri = "http://www.disit.dinfo.unifi.it/SiiMobility/048001";//Simobility link.Not work
	$uri = "http://dbpedia.org/class/yago/1989VideoGames";
	$uri = "http://www.eclap.eu/resource/term/501"; //Eclap
	*/
	$graph->load( $uri );	
	$object->img=$node_default_image;
	// $object->img=$info['image'];	
	$resource=$graph->resource( $uri );
	// print $resource->dump();
	$e_label=get_label_LD($resource);
	if($e_label)$object->name=$e_label;
	else{
		if($resource->hasLabel()){$object->name=$resource->label();}
		else $object->name=truncate_uri($uri);	
	}
	$r_types=$resource->types();//Gets the types.
	$types=array();
	foreach($r_types as $r_t){
		$types[]=$r_t->toString();
		// var_dump($types);print"</br></br>";
	}
	// var_dump($types);print"</br></br>";
	$image=get_depiction_LD($resource);
	if($image){$object->img=$image;}
	else $object->img=spql_get_image($uri,$types);
	
	// print "</br></br>";var_dump($image_for_type);print "</br></br>";
	// if($info['label']!=null)$object->name=$info['label'];	
	// else $object->name=truncate_uri($uri);
	$object->id=$uri;
	// $uri="<".$uri .">";
	$object->type="LD";  
	$object->relations=get_OD($uri,$graph);
	return $object;
}
function get_more_LD($subject,$relation ,$from){
	/* This function gets the more information for $subjects, not via sparql but with OD and LD.
	*/
	include_once("includes/arc/ARC2.php");
	include_once("includes/Graphite.php");
	require("config.php");
	$graph = new Graphite();
	$graph->load( $subject );
	$resource=$graph->resource( $subject );
	$node_relations = $resource->relations();
	// var_dump($relation);print"</br>";
	$relations=array();
	$elements=array();
	foreach($node_relations as $r){
		$rels=$resource->all($r);//rels = All the relations loaded.
		$count=0;
		$j=0;
		// var_dump($r->toString());print"</br>";
		if($r->toString()==$relation){
			foreach($rels as $e){				
				// $graph->load( $e );
				// $ne=$graph->resource($e);			
				// if($ne->type()=='[NULL]'){//The node is literal.
					// $count--;
				// }
				if(get_class($e)=="Graphite_Literal"){//If the node is literal.
					$count--;
				}
				elseif($count>=$from){
					$elements[$j]->id=$e->toString();
					$elements[$j]->type="LD";				
					// print "&nbsp;&nbsp; label:";
					if($e->hasLabel()){
						// print $e->label(); 
						$elements[$j]->name=$e->label();
					}
					else{
						// print "No-Label truncated: ".truncate_uri($e);
						$elements[$j]->name=truncate_uri($e->toString());
					}
					$r_types=$e->types();//Gets the types.
					$types=array();
					foreach($r_types as $r_t){
						$types[]=$r_t->toString();
					}
					$elements[$j]->img=spql_get_image($elements[$count]->id,$types);
					$j++;
				}
				if($count==$from+5-1){
					if(count($rels)-($from+5)==0)break;
					// print "Adds more object";
					$elements[$j]->type = "more_LD";
					$elements[$j]->from=$from+5; //L'ultimo elemento trovato.
					$elements[$j]->name="more ".(count($rels)-$elements[$j]->from) ." ".truncate_uri($relation);
					// $elements[$j]->name="more CONTATORE ".truncate_uri($r->toString());
					$elements[$j]->img='images/icons/more.png';
					$elements[$j]->source=$subject;//TOCHECK source?		
					$elements[$j]->function_to_call=$r->toString();
					$elements[$j]->isInverse='no';
					break;
				}
				$count++;			
			}
		}
	}
	return $elements;
}
function print_version_get_OD($uri){ 
	/* This function gets the information for $uri, not via sparql but with OD and LD.
	*/
	include_once("includes/arc/ARC2.php");
	include_once("includes/Graphite.php");
	 
	$graph = new Graphite();
	$uri = "http://data.ordnancesurvey.co.uk/id/postcodeunit/SO171BJ";
	// $uri = "http://statistics.data.gov.uk/id/statistical-geography/E19000002";
	// $uri = "http://www.disit.dinfo.unifi.it/SiiMobility/048001";//Simobility link.Not work
	// $uri = "http://dbpedia.org/class/yago/1989VideoGames";
	// $uri = "http://www.eclap.eu/resource/term/501"; //Eclap
	$count=$graph->load( $uri );
	print $graph->resource( $uri )->dump();
	print "************************************************************\n";
	$resource=$graph->resource( $uri );
	print $count;
	$relations = $resource->relations();
	print count($relations);
	print "</br>---";
	print $graph->resource( $uri );
	print "---</br>";
	$i=0;
	foreach($relations as $r){
		$elements=$resource->all($r);
		// print $r;
		if($i>=0 ){
			print "</br>";
			$count=0;
			foreach($elements as $e){
				
				print "</br>&nbsp;&nbsp;&nbsp;&nbsp; element: ";
				print $e;
				print "&nbsp;&nbsp; type:";
				$graph->load( $e );
				$ne=$graph->resource($e);
				print $ne->type();
				print "&nbsp;&nbsp; label:";
				if($e->hasLabel()) print $e->label(); else print "No-Label";
				$count++;
				if($count==6)break;
			}
		}
		$i++;
		print "</br>";
	}	
	return ;
}
function get_OD_literals($uri){
	include("config.php");
	include_once("includes/arc/ARC2.php");
	include_once("includes/Graphite.php");	 
	$graph = new Graphite();
	if($graph) $graph->load( $uri );
	$resource = $graph->resource( $uri );
	$node_relations = $resource->relations();
	
	$relations=array();
	$result=array();
	foreach($node_relations as $r){
		$rels=$resource->all($r);
		
		$elements=array();
		$c=0;
		$is_literal=false;
		foreach($rels as $e){				
			if(get_class($e)=="Graphite_Literal"){
				$is_literal=true;
				$first_part_uri=truncate_uri($r->toString(),true);
				$lit['name']=$r->toString();
				$lit['value']=$e->toString();
				if($e->language()==$label_lang) break;
			}
		}
		if($is_literal)$result[]=$lit;
	}	
	// var_dump(empty($result));
	if(empty($result)){
		$lit['name']="no other informations";
		$lit['value']="";
		$result[]=$lit;
	}
	return $result;
}
function get_label_LD($element){
/* Returns the label for the lang specified for the element passed. Checks all the possible property for the 
one that has the label with the lang specified in config.*/
	require("config.php");
	$properties=array("skos:prefLabel", "rdfs:label", "foaf:name", "dct:title", "dc:title", "sioc:name");
	$e_label;
	foreach($properties as $p){
		if($element->has($p)){
			$labels=$element->all($p);
			foreach($labels as $l){
				$e_label=$l->toString();
				$l->language();	
				if($l->language()==$label_lang)return $e_label;
			}
		}
	}
	return	$e_label;
}
function get_depiction_LD($element){
/* Returns the picture for the LD passed in $element. The picture is take from foaf:depiction */
	$properties=array("foaf:depiction");
	$e_picture;
	foreach($properties as $p){
		if($element->has($p)){
			$image=$element->all($p);
			foreach($image as $l){
				$file_headers = @get_headers($l->toString());
				if($file_headers[0] != 'HTTP/1.1 404 Not Found'){//if the image is present, return that immage.
					$e_picture=$l->toString();
					return $e_picture;//returns the first picture.
				}
			}
		}
	}
	return	$e_picture;
}

function get_query($q_id,$x1,$x2=null,$x3=null,$x4=null,$x5=null){//returns the query requested. List of queries
	require( "config.php" );
	global $max_rows_4query;
	switch($q_id){
		case "count1":
			return "Select ?p (count(*) as ?c) where {".$x1 ." ?p ?o .FILTER ISURI(?o)} group by ?p";
		case "countinv":
			return "select distinct ?op where{ 
			{".$x1 ." ?p ?o . ?p <http://www.w3.org/2002/07/owl#inverseOf> ?op .FILTER ISURI(?o)} 
			union
			{".$x1 ." ?p ?o . ?op <http://www.w3.org/2002/07/owl#inverseOf> ?p .FILTER ISURI(?o)}}";
		case "countr":
			return "Select ?p (count(*) as ?c) where {?s ?p ".$x1 ." .FILTER ISURI(?s)} group by ?p";
		case "count2":
			return "Select (count(*) as ?c) where { ".$x1 ." <".$x2 ."> ?s .Filter(!isBlank(?s))}";
		case "count2r":
			return "Select (count(*) as ?c) where {?s <".$x2 ."> ".$x1 ." .Filter(!isBlank(?s))}";
		case "count2b":
			return "Select (count(*) as ?c) where { ".$x1 ." <".$x2 ."> ?s }";
		case "count2br":
			return "Select (count(*) as ?c) where {?s <".$x2 ."> ".$x1 ."}";
		case "select":
			return "SELECT ?p ?o ?l (lang(?l) as ?lang)  ?d ?t WHERE {{select DISTINCT ?o where {".$x1 ." <".$x2 ."> ?o} LIMIT  ".($results_n+1) ."} optional{ ".$x3 ."} optional{?o rdf:type ?t}  optional{?o foaf:depiction ?d} .FILTER(!isBlank(?o) && !isLiteral(?o))} LIMIT ".$max_rows_4query ;
		case "selectr":
			return "select ?p ?o ?l (lang(?l) as ?lang) ?d ?t where{{select DISTINCT ?o where {?o <".$x2 ."> ".$x1 ."} LIMIT ".($results_n+1) ."} optional{ ".$x3 ." } optional{?o rdf:type ?t} optional{?o foaf:depiction ?d} .FILTER(!isBlank(?o) && !isLiteral(?o))} LIMIT ".$max_rows_4query; //TO_CHECK: The limit in the subquery doesn't work with virtuoso.
		case "selectb":
			return "SELECT ?p ?bnode ?p2 ?o ?l (lang(?l) as ?lang)  ?d ?t WHERE {{select DISTINCT ?o where {{".$x1 ." <".$x2 ."> ?o .FILTER (!isBlank(?o)) .FILTER ISURI(?o)}UNION {".$x1 ." <".$x2 ."> ?bnode .?bnode ?p2 ?o FILTER (isBlank(?bnode) && !isBlank(?o)) .FILTER ISURI(?o)}}LIMIT  ".($results_n+1) ."}optional{ ".$x3 ."} optional{?o rdf:type ?t} optional{?o foaf:depiction ?d} }  LIMIT ".$max_rows_4query; //This version has a LIMIT in the subquery, in some EndPoint doesn't work properly.
			// return "SELECT ?p ?bnode ?p2 ?o ?l (lang(?l) as ?lang)  ?d ?t WHERE {{select ?o where {{".$x1 ." <".$x2 ."> ?o .FILTER (!isBlank(?o)) .FILTER ISURI(?o)}UNION {".$x1 ." <".$x2 ."> ?bnode .?bnode ?p2 ?o FILTER (isBlank(?bnode) && !isBlank(?o)) .FILTER ISURI(?o)}}}optional{ ".$x3 ."} optional{?o rdf:type ?t} optional{?o foaf:depiction ?d} }  LIMIT ".$max_rows_4query;
		case "selectbr":
			return "SELECT ?p ?bnode ?p2 ?o ?l (lang(?l) as ?lang)  ?d ?t WHERE {{select DISTINCT ?o where {{ ?o <".$x2 ."> ".$x1 ." .FILTER (!isBlank(?o)) .FILTER ISURI(?o)}UNION {?bnode <".$x2 ."> ".$x1 ." .?bnode ?p2 ?o FILTER (isBlank(?bnode) && !isBlank(?o)) .FILTER ISURI(?o)}}LIMIT  ".($results_n+1) ."}optional{ ".$x3 ."} optional{?o rdf:type ?t} optional{?o foaf:depiction ?d} } LIMIT ".$max_rows_4query;
			// return "SELECT ?p ?bnode ?p2 ?o ?l (lang(?l) as ?lang)  ?d ?t WHERE {{select ?o where {{ ?o <".$x2 ."> ".$x1 ." .FILTER (!isBlank(?o)) .FILTER ISURI(?o)}UNION {?bnode <".$x2 ."> ".$x1 ." .?bnode ?p2 ?o FILTER (isBlank(?bnode) && !isBlank(?o)) .FILTER ISURI(?o)}}}optional{ ".$x3 ."} optional{?o rdf:type ?t} optional{?o foaf:depiction ?d} } LIMIT ".$max_rows_4query;
		case "depiction":
			return "SELECT ?d WHERE {<".$x1 ."> foaf:depiction ?d}";
		case "label":
			return "SELECT ?l (lang(?l) as ?lang) WHERE { ".$x1 ." }";
		case "type":
			return "SELECT ?t WHERE { <".$x1 ."> rdf:type ?t }";
		//MORE CASES
		case "more":
			return "SELECT ?o ?l (lang(?l) as ?lang) ?d ?t WHERE {{select DISTINCT ?o where {".$x1 ." <".$x2 ."> ?o} LIMIT ".($x3+1) ." OFFSET ".($x4) ."} optional{ ".$x5 ." } optional{?o rdf:type ?t} optional{?o foaf:depiction ?d} .FILTER(!isBlank(?o) && !isLiteral(?o))}";
		case "more_r":
			return "SELECT ?o ?l (lang(?l) as ?lang) ?d ?t WHERE {{select DISTINCT ?o where{?o <".$x2 ."> ".$x1 ."} LIMIT ".($x3+1) ." OFFSET ".($x4) ."}. optional{ ".$x5 ." } optional{?o rdf:type ?t} optional{?o foaf:depiction ?d} .FILTER(!isBlank(?o) && !isLiteral(?o))}"; 
		case "more_b":
			return "SELECT ?o ?l (lang(?l) as ?lang) ?d ?t WHERE {{select DISTINCT ?o where {".$x1 ." <".$x2 ."> ?o .FILTER (!isBlank(?o)) .FILTER ISURI(?o)} LIMIT ".($x3+1) ." OFFSET ".($x4) ."} optional{ ".$x5 ." } optional{?o rdf:type ?t} optional{?o foaf:depiction ?d}}";
		case "more_br":
			return "SELECT ?o ?l (lang(?l) as ?lang) ?d ?t WHERE {{select DISTINCT ?o where{?o <".$x2 ."> ".$x1 ." .FILTER (!isBlank(?o)) .FILTER ISURI(?o)} LIMIT ".($x3+1) ." OFFSET ".($x4) ."}. optional{ ".$x5 ." } optional{?o rdf:type ?t} optional{?o foaf:depiction ?d}}";
		
	
	}
}