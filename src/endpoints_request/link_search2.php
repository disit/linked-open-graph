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
//$check_second_search = $argv[2];
//costanti di esecuzione
global $sparql_1_1_offset_increment;
global $sparql_1_0_offset_increment;
global $timeout_query_sparql;

$limit_default = 400000;
$sparql_1_1_offset_increment = 100000;
$timeout_query_sparql = 120000;
$sparql_1_0_offset_increment = 20000;

$mysqli_db = mysqli_connect($hostname, $username, $password, $schema)
        or die("Unable to connect to MySQL");

mysqli_set_charset($mysqli_db, "uft8");
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


    /*
    $found_link = check1($db, $namespace, $id, $array_info);
    if(empty($found_link) && $namespace!='no-namespace-given')
      $found_link = check1($db, 'no-namespace-given', $id, $array_info);
    if($found_link===FALSE)
      exit();
     * 
     */

    $found_link = array();
    foreach ($array_info as $info) {
      $namespace = clean_namespace($info['namespace']);
      echo $namespace.": ";
      if($id!=$info['id'] && check_link($db, $namespace)>0) {
        $found_link[] = $namespace;
        echo "X\n";
      } else
        echo ".\n";
    }
    
    
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
      echo "  CLEAN LINKS\n";
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

function check_link($db, $namespace) {
    global $timeout_query_sparql;
    $n_rows = 0;
    $sparql_query = "PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>"
                  . "SELECT ?o WHERE{ ?s ?p ?o . "
                  . "FILTER( ISURI(?o) && STRSTARTS(STR(?o), '$namespace'))}"
                  . "LIMIT 1";

    echo $sparql_query."\n";
    $result_sparql = $db->query($sparql_query, $timeout_query_sparql);
    if ($result_sparql) {
      foreach (sparql_fetch_all($result_sparql) as $result) {
        $n_rows++;
      }
    } else {
      echo "FAILED ".sparql_error($db);
      $n_rows=-1;
    }
    return $n_rows;
}

function check1($db, $namespace, $id, $array_info) {
    global $sparql_1_1_offset_increment;
    global $sparql_1_0_offset_increment;
    global $timeout_query_sparql;
    
    $found_link=array();
    $offset = 0;
    do {
      if($namespace!='no-namespace-given') {
        $increment = $sparql_1_1_offset_increment;
        // cerca tutti gli uri 
        $sparql_query = "PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>"
                . "SELECT DISTINCT ?o WHERE{ ?s ?p ?o . "
                . "FILTER( ISURI(?o) && !STRSTARTS(STR(?o), '$namespace') && ?p!=rdf:type)}"
                . "OFFSET $offset LIMIT $increment";
      } else {
        $increment = $sparql_1_0_offset_increment;
        $sparql_query = "PREFIX rdf:<http://www.w3.org/1999/02/22-rdf-syntax-ns#>"
                . "SELECT distinct ?o WHERE { ?s ?p ?o . filter(isuri(?o))} offset $offset limit $increment";        
      }

      echo $sparql_query."\n";
      $result_sparql = $db->query($sparql_query, $timeout_query_sparql);
      echo ":";

      $n_rows = 0;
      if ($result_sparql) {
        foreach (sparql_fetch_all($result_sparql) as $result) {
          $result_name = $result["o"];
          $n_rows++;

          foreach ($array_info as $info) {
            if (strpos($result_name, $info['namespace']) !== false && !is_contained($found_link, $info['namespace']) && $id != $info['id']) {
              array_push($found_link, $result_name);
              echo "\nlink to ".$info['namespace']."--";
            }
          }
        }
      } else {
        echo "FAILED ".sparql_error($db);
        return FALSE;
      }
      $result_sparql = NULL;
      $offset = $offset + $increment;
    } while ($n_rows > 0);
    return $found_link;
}

function clean_namespace($ns) {
  if(substr($ns,-1) == ":")
    return substr($ns,0,-1);
  return $ns;
}