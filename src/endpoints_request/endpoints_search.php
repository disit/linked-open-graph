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

require_once 'login_db.php';

srand();

$triple_default_value = 30000;
$timeout_query_sparql = 120000;

//variabili statistiche
$num_tot = 0;
$num_alive = 0;
$num_not_alive = 0;
$num_example = 0;
$num_saved = 0;
$num_dupl = 0;

//zzzzz inizio aggiunti per aumentare il tempo di una richiesta
//è stato aggiunto a tutte le richieste file_get_contents
//usato per datahub poichè i suoi tempi di risposta sono molto lunghi
//120 e il numero in secondi del timeout della connessione
$opts = array('http' =>
    array(
        'method' => 'GET',
        'timeout' => 120
    )
);
$context = stream_context_create($opts);
//zzzzz fine modifiche
//apre connesione database
$mysqli_db = mysqli_connect($hostname, $username, $password, $schema)
        or die("Unable to connect to MySQL");

//zzzzz inizio aggiunta codifica per icaratteri speciali
mysqli_set_charset($mysqli_db, "uft8");
mb_internal_encoding('UTF-8');
//zzzzz fine
//zzzzz inizio scarica le informazioni da datahub
$url_datahub_rdf = 'https://datahub.io/api/action/package_search?q=res_format:api/sparql&rows=10000';
$rdf_datahub = json_decode((file_get_contents($url_datahub_rdf, false, $context)));
//crea una lista di id di endpoint
$rdf = array();
if (isset($rdf_datahub)) {
    //crea una lista di id di endpoint
    foreach ($rdf_datahub->result->results as $endpoint_id) {
        //var_dump($endpoint_id->name);
        array_push($rdf, $endpoint_id->name);
    }
}
#var_dump($endpoints);
sparql_example_endpoint($rdf, "https://datahub.io/");

//scarica le informazioni da datahub
$url_datahub_2 = 'https://datahub.io/api/action/package_search?q=res_format:sparql&rows=10000';
$endpoints_datahub_2 = json_decode((file_get_contents($url_datahub_2, false, $context)));
//crea una lista di id di endpoint
$rdf = array();
if (isset($endpoints_datahub_2)) {
    //crea una lista di id di endpoint
    foreach ($endpoints_datahub_2->result->results as $endpoint_id) {
        //var_dump($endpoint_id->name);
        array_push($rdf, $endpoint_id->name);
    }
}
#var_dump($endpoints);
sparql_example_endpoint($rdf, "https://datahub.io/");
//zzzzz fine
//scarica le informazioni da http://data.gov.uk/
$url_datagov = 'http://data.gov.uk/api/search/package?res_format=SPARQL&limit=1000';
$endpoints_datagov = json_decode((file_get_contents($url_datagov, false, $context)));
//crea una lista di id di endpoint
$rdf = array();
if (isset($endpoints_datagov)) {
    //crea una lista di id di endpoint
    foreach ($endpoints_datagov->results as $endpoint_id) {
        array_push($rdf, $endpoint_id);
    }
}
#var_dump($endpoints);
sparql_example_endpoint($rdf, "http://data.gov.uk/");
//scarica le informazioni da http://publicdata.eu
$url_publicdata = 'http://publicdata.eu/api/search/package?res_format=api/sparql&limit=1000';
$endpoints_publicdata = json_decode((file_get_contents($url_publicdata, false, $context)));
//crea una lista di id di endpoint
$rdf = array();
if (isset($endpoints_publicdata)) {
    //crea una lista di id di endpoint
    foreach ($endpoints_publicdata->results as $endpoint_id) {
        array_push($rdf, $endpoint_id);
    }
}
#var_dump($endpoints);
sparql_example_endpoint($rdf, "http://publicdata.eu/");

//zzzzz inizio aggiunto un nuovo repository di endpoint
//scarica le informazioni da http://linkeddatacatalog.dws.informatik.uni-mannheim.de/
$url_linkeddatacatalog = 'http://linkeddatacatalog.dws.informatik.uni-mannheim.de/api/search/package?res_format=api/sparql&limit=10000';
$endpoints_linkeddatacatalog = json_decode((file_get_contents($url_linkeddatacatalog, false, $context)));
//crea una lista di id di endpoint
$rdf = array();
if (isset($endpoints_linkeddatacatalog)) {
    //crea una lista di id di endpoint
    foreach ($endpoints_linkeddatacatalog->results as $endpoint_id) {
        array_push($rdf, $endpoint_id);
    }
}
#var_dump($endpoints);
sparql_example_endpoint($rdf, "http://linkeddatacatalog.dws.informatik.uni-mannheim.de/");
//zzzzzz fine

mysqli_close($mysqli_db);

$statics = $num_tot . " numero totale--        " . $num_alive . " alive--    " . $num_not_alive . " not-alive--     " . $num_example . " example--       " . $num_dupl . " dupl--    " . $num_saved . " saved--      ";
$fp = fopen("endpoints_statics.txt", "a");
fwrite($fp, $statics);
fclose($fp);

//funzione php che svolge tutto il lavoro importante. è generalizzata in modo tale da funzionare
//con qualsiasi sito utilizzi le api ckan. aquisisce i dati relativi all'endpoint dalla sorgente.
//verifica che l'endpoint non sia già presente nel database o sia già stato cercato.
//verifica che l'endpoint sia funzionante
//prende tre esempi in moto totalmente casuale dall'endpoint
//prende le informazioni extra quali id e namespace relative all'endpoint
//salva tutto sul database
function sparql_example_endpoint($endpoints, $site_url) {

    global $timeout_query_sparql, $triple_default_value;

    require_once("../includes/sparqllib.php");

    echo "Checking $site_url\n";
    $query = "SELECT url FROM `endpoints`";
    $result_query = mysqli_query($GLOBALS["mysqli_db"], $query);
    $endpoints_on_db = array();
    while ($row = mysqli_fetch_assoc($result_query)) {
        array_push($endpoints_on_db, $row["url"]);
    }
    #var_dump($endpoints_on_db);
    //array per sapere quali endpoint sparql sono già stati esaminati
    $endpoints_sparql_done = array();

    //variabile per dare un ordine agli endpoints
    $order = 1;

    //per ogni endpoint guarda se è attivo, prende i dati da datahub, acquisisce massimo 3 esempi, lo aggiunge al database
    foreach ($endpoints as $id) {
        $GLOBALS["num_tot"] = $GLOBALS["num_tot"] + 1;

        $url = "$site_url" . "api/action/package_show?id=$id";
        $endpoint_results = json_decode((file_get_contents($url, false, $GLOBALS["context"])));

        if ($endpoint_results != NULL && isset($endpoint_results)) {
            #var_dump($endpoint_results);
            $endpoint_result = $endpoint_results->result;
            #var_dump($endpoint_result);
            $resources = $endpoint_result->resources;
            #var_dump($resources);

            foreach ($resources as $value) {
                //seleziona la risorsa api/sparql
                if ($value->format == "api/sparql" || $value->format == "sparql") {
                    #var_dump($value->url);
                    //guarda se l'endpoint non esiste già
                    if (!(in_array($value->url, $endpoints_sparql_done)) && !(in_array($value->url, $endpoints_on_db))) {
                        //prova a connettersi all'endpoint
                        #var_dump($value->url);
                        echo "  $value->url";
                        $db = sparql_connect($value->url);
                        //solo se l'endpoint risponde allora lo aggiunge all'array
                        if ($db->alive()) {
                            $GLOBALS["num_alive"] = $GLOBALS["num_alive"] + 1;
                            $limit = NULL;
                            //chiede il numero massimo di triple, per poi efferruare delle chiamate con un offset random
                            $sparql_query = "PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>"
                                    . "PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>"
                                    . "select (count(*) as ?count) where {SELECT distinct ?s WHERE{?s rdfs:label ?o}}";
                            $limit_result = $db->query($sparql_query, $timeout_query_sparql);
                            if ($limit_result != NULL) {
                                $row = sparql_fetch_array($limit_result);
                                $limit = (int) $row["count"];
                                if ($limit == NULL || $limit == 0) {
                                    $limit = (int) $row[".1"];
                                }
                            }

                            #var_dump($limit);
                            //cerca tre esempi
                            $count_example = 0;
                            $element = NULL;
                            for ($i = 0; $i < 100; $i++) {
                                if ($count_example == 4) {
                                    break;
                                }

                                if ($limit != NULL || $limit != 0) {
                                    $random = rand(($limit / 2), $limit);
                                } else {
                                    $random = rand(200, 20000);
                                }
                                $sparql_query = "PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>"
                                        . "PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>"
                                        . " SELECT distinct ?s ?o WHERE{?s rdfs:label ?o} OFFSET $random LIMIT 1";
                                $result = $db->query($sparql_query, $timeout_query_sparql);
                                #var_dump($result);
                                if ($result) {
                                    $row = sparql_fetch_array($result);
                                    #var_dump($row);
                                    if ($row['s']) {
                                        $subject = mysql_escape_string($row['s']);
                                        $name = mysql_escape_string($row['o']);
                                        $sparql_query = "SELECT distinct ?property "
                                                . "WHERE{{ <$subject> ?property ?object . filter(isuri(?object)) } UNION { ?subject ?property <$subject> . filter(isuri(?subject))} }";

                                        $result_property = $db->query($sparql_query, $timeout_query_sparql);
                                        #var_dump($result_property);

                                        $count_property = 0;
                                        if ($result_property) {
                                            while ($row_property = sparql_fetch_array($result_property)) {
                                                #var_dump($row_property);
                                                $count_property++;
                                            }
                                        }
                                        #var_dump($count_property);
                                        //aggiunge solo gli esempi con più di 2 proprietà
                                        if ($count_property > 2) {
                                            if ($element == NULL) {
                                                $element.= "{\"uri\":\"" . $subject . "\", ";
                                                $element.="\"name\":\"" . $name . "\"}";
                                            } else {
                                                $element.= ",{\"uri\":\"" . $subject . "\", ";
                                                $element.="\"name\":\"" . $name . "\"}";
                                            }
                                            $count_example++;
                                        }
                                    }
                                } else {
                                    continue;
                                }
                            }


                            //solo se ho almeno un esempio aggiungo l'endpoint al database
                            if ($element != NULL) {
                                $element = "[" . $element . "]";
                                $GLOBALS["num_example"] = $GLOBALS["num_example"] + 1;
                            } else {
                                $element = "[]";
                                echo "    NO EXAMPLE\n";
                            }

                            #var_dump($endpoint_result->title);
                            #var_dump($element);
                            //cerca il campo namespace che servirà alla ricerca dei link fra endpoint
                            $namespace = "no-namespace-given";
                            foreach ($endpoint_result->extras as $extra) {
                                if ($extra->key == "namespace") {
                                    if ($extra->value != NULL || $extra->value != "")
                                        $namespace = $extra->value;
                                }
                            }

                            //cerca il numero di triple dell'endpoint
                            $triple = $triple_default_value;
                            $sparql_query = " SELECT (count(*) as ?count) WHERE{?s ?p ?o}";
                            $result_count = $db->query($sparql_query, $timeout_query_sparql);
                            if ($result_count != NULL) {
                                $row = sparql_fetch_array($result_count);
                                $triple = (int) $row["count"];
                                if ($triple == NULL || $triple == 0) {
                                    $triple = $triple_default_value;
                                }
                            }

                            //campo delle info
                            $info = "{\"id\":\"$id\", \"namespace\":\"$namespace\", \"triple\":\"$triple\"}";
                            //var_dump($id);
                            //query di aggiunta al db
                            $query = "INSERT INTO `endpoints` (`url`, `title`, `active`, `order`, `uri_associated`, `limit`, `search_type`, `examples`, `blank_node`, `suggest`, `info`, `links`)"
                                    . " VALUES ('$value->url', '$endpoint_result->title', '1', '$order', NULL, '1000000', '', '$element', '0', '', '$info', 'none');";

                            mysqli_query($GLOBALS["mysqli_db"], $query) or print("\nERROR:".mysqli_error($GLOBALS["mysqli_db"])."\n");
                            //var_dump($query);
                            $GLOBALS["num_saved"] = $GLOBALS["num_saved"] + 1;
                            array_push($endpoints_sparql_done, $value->url);
                            echo "    SAVED\n";
                        } else {
                            $GLOBALS["num_not_alive"] = $GLOBALS["num_not_alive"] + 1;
                            echo "    not alive!\n";
                        }
                    } else {
                        echo "  $value->url";
                        echo "    already analysed\n";
                        $GLOBALS["num_dupl"] = $GLOBALS["num_dupl"] + 1;
                        //nel caso l'endpoint esistesse già nel database e il namespace trovato è significativo allora lo aggiorna
                        if (in_array($value->url, $endpoints_on_db)) {
                            $namespace = "no-namespace-given";
                            $query = "SELECT `info` FROM `endpoints` WHERE `url`='$value->url'";

                            $result_query_info = mysqli_query($GLOBALS["mysqli_db"], $query);
                            while ($row = mysqli_fetch_assoc($result_query_info)) {
                                $result_info = $row["info"];
                            }

                            //var_dump($result_info);

                            if (strpos($result_info, $namespace) == TRUE) {
                                //var_dump("entrato");

                                foreach ($endpoint_result->extras as $extra) {
                                    if ($extra->key == "namespace") {
                                        if ($extra->value != NULL || $extra->value != "")
                                            $namespace = $extra->value;
                                    }
                                }

                                if ($namespace != "no-namespace-given") {

                                    $db = sparql_connect($value->url);

                                    if ($db->alive()) {

                                        //cerca il numero di triple dell'endpoint
                                        $triple = $triple_default_value;
                                        $sparql_query = " SELECT (count(*) as ?count) WHERE{?s ?p ?o}";
                                        $result_count = $db->query($sparql_query, $timeout_query_sparql);
                                        if ($result_count != NULL) {
                                            $row = sparql_fetch_array($result_count);
                                            $triple = (int) $row["count"];
                                            if ($triple == NULL || $triple == 0) {
                                                $triple = $triple_default_value;
                                            }
                                        }

                                        //campo delle info
                                        $info = "{\"id\":\"$id\", \"namespace\":\"$namespace\", \"triple\":\"$triple\"}";
                                        $query = "UPDATE `endpoints` SET `info` = '$info' WHERE `endpoints`.`url` = '$value->url'";

                                        mysqli_query($GLOBALS["mysqli_db"], $query);
                                        //var_dump($query);
                                        array_push($endpoints_sparql_done, $value->url);
                                        echo "    UPDATED\n";
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        //incrementa l'ordine
        $order++;
    }
}
