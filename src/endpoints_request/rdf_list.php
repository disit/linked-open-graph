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

//scarica la lista di rdf store esterni al database.
//Li salva su un file temporaneo per evitare che nella ricerca dei link sia effettuata
//questa operazione a ogni chiamata

ini_set('memory_limit', '4G');
require_once("../includes/sparqllib.php");
require_once 'login_db.php';

$mysqli_db = mysqli_connect($hostname, $username, $password, $schema)
        or die("Unable to connect to MySQL");

mysqli_set_charset ( $mysqli_db , "uft8" );
mb_internal_encoding('UTF-8');

//zzzzz aggiunti per aumentare il tempo di una richiesta
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

//lista degli endpoints per effettuarci la ricerca all'interno
$query = "SELECT url FROM `endpoints`";
$result_query = mysqli_query($mysqli_db, $query);
$endpoints_on_db = array();

while ($row = mysqli_fetch_array($result_query)) {
    array_push($endpoints_on_db, $row[0]);
}


$query = "SELECT url FROM endpoints where links <= ''";
$result_query = mysqli_query($mysqli_db, $query);
$endpoints_research = array();

while ($row = mysqli_fetch_array($result_query)) {
    array_push($endpoints_research, $row[0]);
}

#var_dump($endpoints_without_links);

$array_id = array();
$array_info = array();
//prende le info da ogni endpoints sul database
$query = "SELECT info FROM `endpoints`";
$result_query = mysqli_query($mysqli_db, $query);
while ($row = mysqli_fetch_assoc($result_query)) {
    #var_dump($row);
    $namespace = json_decode($row['info'])->namespace;
    array_push($array_id, json_decode($row['info'])->id);
}
#var_dump($array_id);
#var_dump($array_info);


//zzzzz inizio
//prendo la lista degli rdf stores su datahub
$url_datahub_rdf = 'https://datahub.io/api/action/package_search?q=tags:format-rdf&rows=10000';
$rdf_datahub = json_decode((file_get_contents($url_datahub_rdf, false, $context)));

//crea una lista di id di endpoint per poi escluderli dalla richiesta a datahub

$rdf = array();
if (isset($rdf_datahub)) {
    //crea una lista di id di endpoint
    foreach ($rdf_datahub->result->results as $endpoint_id) {
        //var_dump($endpoint_id->name);
        array_push($rdf, $endpoint_id->name);
    }
}

#var_dump($rdf);
//cerca gli id che non sono contenuti nel database e che non sono endpoint
foreach ($rdf as $rdf_store) {
    if (!is_contained($array_id, $rdf_store)) {
        $url = "https://datahub.io/api/action/package_show?id=$rdf_store";
        $endpoint_results = json_decode((file_get_contents($url, false, $context)));
        if ($endpoint_results != NULL) {
            $endpoint_result = $endpoint_results->result;
            $namespace = "no-namespace-given";
            foreach ($endpoint_result->extras as $extra) {
                if ($extra->key == "namespace") {
                    if ($extra->value != NULL || $extra->value != "") {
                        $namespace = $extra->value;
                    }
                }
            }
            if ($namespace != "no-namespace-given") {
                array_push($array_id, $rdf_store);
                array_push($array_info, array('id' => $rdf_store, 'namespace' => $namespace, 'type' => 'external', 'triple' => 30000));
                #var_dump($namespace);
            }
        }
    }
}

//zzzzz fine


//zzzzz inizio prendo la lista degli rdf stores su datahub
$url_linkeddatacatalog_rdf = 'http://linkeddatacatalog.dws.informatik.uni-mannheim.de/api/search/package?tags=format-rdf&limit=10000';
$rdf_linkeddatacatalog = json_decode((file_get_contents($url_linkeddatacatalog_rdf, false, $context)));

//crea una lista di id di endpoint per poi escluderli dalla richiesta a datahub

$rdf = array();
if (isset($rdf_linkeddatacatalog)) {
    //crea una lista di id di endpoint
    foreach ($rdf_linkeddatacatalog->results as $endpoint_id) {
        //var_dump($endpoint_id->name);
        array_push($rdf, $endpoint_id);
    }
}

#var_dump($rdf);
//cerca gli id che non sono contenuti nel database e che non sono endpoint
foreach ($rdf as $rdf_store) {
    if (!is_contained($array_id, $rdf_store)) {
        $url = "http://linkeddatacatalog.dws.informatik.uni-mannheim.de/api/action/package_show?id=$rdf_store";
        #var_dump($url);
        $endpoint_results = json_decode((file_get_contents($url, false, $context)));
        if ($endpoint_results != NULL) {
            #var_dump($endpoint_results);
            $endpoint_result = $endpoint_results->result;
            #var_dump($endpoint_result);
            $namespace = "no-namespace-given";
            foreach ($endpoint_result->extras as $extra) {
                if ($extra->key == "namespace") {
                    if ($extra->value != NULL || $extra->value != "") {
                        $namespace = $extra->value;
                    }
                }
            }
            if ($namespace != "no-namespace-given") {
                array_push($array_id, $rdf_store);
                array_push($array_info, array('id' => $rdf_store, 'namespace' => $namespace, 'type' => 'external', 'triple' => 30000));
                #var_dump($namespace);
            }
        }
    }
}
//zzzzz fine



$fp = fopen("endpoints_rdf.json", "w");
fwrite($fp, json_encode($array_info));
fclose($fp);

//funzione che guarda se una stringa è contenuta in un array di stringhe
function is_contained($found_link, $namespace) {
    foreach ($found_link as $name) {
        if (stripos($name, $namespace) !== FALSE) {
            return true;
        }
    }
}
