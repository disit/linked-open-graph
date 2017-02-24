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

ini_set('memory_limit', '8G');
require_once("../includes/sparqllib.php");
require_once 'login_db.php';

$endpoint_db_offset = $argv[1];
$check_second_search = $argv[2];

//costanti di esecuzione
$limit_default = 400000;
$sparql_1_1_offset_increment = 100000;
$timeout_query_sparql = 120000;
$sparql_1_0_offset_increment = 20000;

$mysqli_db = mysqli_connect($hostname, $username, $password, $schema)
        or die("Unable to connect to MySQL");

mysqli_set_charset ( $mysqli_db , "uft8" );
mb_internal_encoding('UTF-8');

$query = "SELECT url FROM endpoints limit 1 offset $endpoint_db_offset";
$result_query = mysqli_query($mysqli_db, $query);
$endpoints_research = array();

while ($row = mysqli_fetch_array($result_query)) {
    array_push($endpoints_research, $row[0]);
}

$array_id = array();
$array_info = array();
//prende le info da ogni endpoints sul database
$query = "SELECT info FROM `endpoints`";
$result_query = mysqli_query($mysqli_db, $query);
while ($row = mysqli_fetch_assoc($result_query)) {
    #var_dump($row);
    $namespace = clean_namespace(json_decode($row['info'])->namespace);
    if ($namespace != "no-namespace-given") {
        array_push($array_info, array('id' => json_decode($row['info'])->id, 'namespace' => $namespace, 'type' => 'internal', 'triple' => json_decode($row['info'])->triple));
    }
    array_push($array_id, json_decode($row['info'])->id);
}
$rdf_info = json_decode((file_get_contents("endpoints_rdf.json")), true);

foreach ($rdf_info as $rdf_store) {
    array_push($array_info, array('id' => $rdf_store['id'], 'namespace' => $rdf_store['namespace'], 'type' => $rdf_store['type'], 'triple' => $rdf_store['triple']));
}

#var_dump($array_info);

foreach ($endpoints_research as $endpoint) {

    $db = sparql_connect($endpoint);
    echo "check $endpoint\n";

    if ($db->alive()) {

        #var_dump($endpoint);
        $id = NULL;
        //prende le info sull'endpoint
        $query = "SELECT info FROM `endpoints` WHERE url='$endpoint'";
        $result_query = mysqli_query($mysqli_db, $query);
        while ($row = mysqli_fetch_assoc($result_query)) {
            $info = json_decode($row['info']);
            $namespace = clean_namespace($info->namespace);
            $id = $info->id;
        }
        echo "  namespace: $namespace\n";


        $found_link = array();

        $limit = $limit_default;

        if ($namespace != "no-namespace-given") {

            //sono i link trovati nel database
            //chiede il numero massimo di triple, per poi efferruare delle chiamate con un offset random
            $sparql_query = "PREFIX rdf:<http://www.w3.org/1999/02/22-rdf-syntax-ns#> "
                    . "SELECT (count(*) as ?count) {"
                    . " SELECT DISTINCT ?o WHERE { ?s ?p ?o. FILTER( ISURI(?o) && !STRSTARTS(STR(?o), '$namespace') && ?p!=rdf:type)}"
                    . "}";
            #var_dump($sparql_query);
            $limit_result = $db->query($sparql_query, $timeout_query_sparql);
            if ($limit_result != NULL) {
                $row = sparql_fetch_array($limit_result);
                $limit = (int) $row["count"];
                if ($limit == NULL || $limit == 0) {
                    $limit = (int) $row[".1"];
                }
            }
            //se non trova un limite ne assegna uno di default
            if ($limit == NULL || $limit == "") {
                $limit = $limit_default;
            }
            echo "  limit: $limit\n";
            #var_dump($limit);

            $offset = 0;
            $limit_offset = ($limit / 2);

            if ($check_second_search == "true") {
                #var_dump("entrato");
                $offset = round($limit / 2);
                $limit_offset = $limit;
            }

            //richiesta per sparql 1.1
            while ($offset < $limit_offset) {
                $sparql_query = "PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>"
                        . "SELECT distinct ?o WHERE{ ?s ?p ?o . "
                        . "FILTER( ISURI(?o) && !STRSTARTS(STR(?o), " . "'$namespace'" . ") && ?p!=rdf:type)}"
                        . "OFFSET $offset LIMIT $sparql_1_1_offset_increment";
                $result_sparql = $db->query($sparql_query, $timeout_query_sparql);
                echo ":";

                if (!empty($result_sparql->rows)) {
                    foreach (sparql_fetch_all($result_sparql) as $result) {
                        $result_name = $result["o"];

                        foreach ($array_info as $info) {
                            if (strpos($result_name, $info['namespace']) !== false && !is_contained($found_link, $info['namespace']) && $id != $info['id']) {
                                array_push($found_link, $result_name);
                            }
                        }
                    }
                } else {
                    #print_r("result empty");
                }
                $result_sparql = NULL;
                $offset = $offset + $sparql_1_1_offset_increment;
            }
        }
        
        if (empty($found_link) && $limit === $limit_default) {

            $offset = 0;
            $limit_offset = ($limit / 2);

            if ($check_second_search == "true") {
                $offset = round($limit / 2);
                $limit_offset = $limit;
            }

            #print_r("versione sparql 1.0 or not namespace given");
            while ($offset < $limit_offset) {

                #var_dump($limit_offset);
                //richiesta per sparql versione 1 che non supporta versini differenti
                $sparql_query = "PREFIX rdf:<http://www.w3.org/1999/02/22-rdf-syntax-ns#>"
                        . "SELECT distinct ?o WHERE { ?s ?p ?o . filter(isuri(?o))} offset $offset limit $sparql_1_0_offset_increment";
                $result_sparql = $db->query($sparql_query, $timeout_query_sparql);
                echo ".";

                if (!empty($result_sparql->rows)) {
                    foreach (sparql_fetch_all($result_sparql) as $result) {
                        $result_name = $result["o"];
                        foreach ($array_info as $info) {
                            if (strpos($result_name, $info['namespace']) !== false && !is_contained($found_link, $info['namespace']) && $id != $info['id']) {
                                array_push($found_link, $result_name);
                                #var_dump($result_name);
                            }
                        }
                    }
                }
                $result_sparql = NULL;
                #var_dump(gc_collect_cycles());
                $offset = $offset + $sparql_1_0_offset_increment;
            }
        }
        echo "\n";
        //salvo i link trovati nella colonna link del database: contiene gli id degli altri endpoints a cui fa riferimento
        $link_db = NULL;
        $old_link = array();
        //prende i link precedenti se ci sono
        $query = "SELECT links FROM `endpoints` where url='$endpoint'";
        $result_query_links = mysqli_query($mysqli_db, $query);
        if ($result_query_links != NULL || $result_query_links != 0 || isset($result_query_links)) {
            while ($row = mysqli_fetch_assoc($result_query_links)) {
                $links_json = $row['links'];
                if ($links_json != 'none') {
                    $link_array = (json_decode($links_json));
                    foreach ($link_array as $link) {
                        $id_target = $link->id;
                        $type_target = $link->type;
                        $triple_target = $link->triple;
                        foreach ($array_info as $info) {
                            if ($id_target == $info['id']) {
                                $type_target = $info['type'];
                                $triple_target = $info['triple'];
                            }
                        }
                        array_push($old_link, $id_target);
                        if ($link_db == NULL) {
                            $link_db.="{\"id\":\"$id_target\", \"type\":\"$type_target\", \"triple\":\"$triple_target\"}";
                        } else {
                            $link_db.=",{\"id\":\"$id_target\", \"type\":\"$type_target\", \"triple\":\"$triple_target\"}";
                        }
                    }
                }
            }
        }
        //risultato da immettere nel db
        foreach ($array_info as $info) {
            if (is_contained($found_link, clean_namespace($info['namespace']))) {
                $info_id = $info['id'];
                if (!is_contained($old_link, $info_id)) {
                    $info_type = $info['type'];
                    $info_triple = $info['triple'];
                    if ($link_db == NULL) {
                        $link_db.="{\"id\":\"$info_id\", \"type\":\"$info_type\", \"triple\":\"$info_triple\"}";
                    } else {
                        $link_db.=",{\"id\":\"$info_id\", \"type\":\"$info_type\", \"triple\":\"$info_triple\"}";
                    }
                }
            }
        }

        if (isset($link_db) || $link_db != NULL) {
            $link_db = "[" . $link_db . "]";
            $query = "UPDATE `endpoints` SET `links`='$link_db' WHERE url='$endpoint'";
            $result_query = mysqli_query($mysqli_db, $query);
            echo "  UPDATE LINKS\n";
        }

        if (empty($found_link) && !isset($link_db)) {
            $query = "UPDATE `endpoints` SET `links`='none' WHERE url='$endpoint'";
            $result_query = mysqli_query($mysqli_db, $query);
        }
    } else {
        echo "  not alive!\n";
    }
}

//funzione che guarda se una stringa è contenuta in un array di stringhe
function is_contained($found_link, $namespace) {
    foreach ($found_link as $name) {
        if (stripos($name, $namespace) !== FALSE) {
            return true;
        }
    }
}

function clean_namespace($ns) {
  if(substr($ns,-1) == ":")
    return substr($ns,0,-1);
  return $ns;
}