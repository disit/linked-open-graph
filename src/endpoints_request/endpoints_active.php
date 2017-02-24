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

//disattiva i database inattivi 
$timeout_query_sparql = 120000;

require_once("../includes/sparqllib.php");
require_once 'login_db.php';

$mysqli_db = mysqli_connect($hostname, $username, $password, $schema)
        or die("Unable to connect to MySQL");

//testa tutti gli endpoints nel database e metta inattivi quelli che non rispondono
$query = "SELECT url,info FROM `endpoints`";
$result_query = mysqli_query($mysqli_db, $query);

while ($row = mysqli_fetch_array($result_query)) {
    $info_update = "";
    $db = sparql_connect($row[0]);
    if (!($db->alive())) {
      $active = '0';
      echo $row[0]. " NOT active\n";
    } else {
      $active = '1';
      echo $row[0]. " active\n";
      $sparql_query = " SELECT (count(*) as ?count) WHERE{?s ?p ?o}";
      $result_count = $db->query($sparql_query, $timeout_query_sparql);
      $triple=0;
      if ($result_count != NULL) {
          $rowcnt = sparql_fetch_array($result_count);
          $triple = (int)$rowcnt["count"];
      }
      if(!$triple>0) {
        $sparql_query = " SELECT count(*) as ?count WHERE{?s ?p ?o}";
        $result_count = $db->query($sparql_query, $timeout_query_sparql);
        if ($result_count != NULL) {
            $rowcnt = sparql_fetch_array($result_count);
            $triple = (int)$rowcnt["count"];
        }
      }
      if($triple>0) {
        $info = json_decode($row[1]);
        $info->triple = $triple;
        $info_update = ",info='".json_encode($info)."'";
        echo "  update triples: $triple\n";
      }
    }
    $query = "UPDATE endpoints SET `active` = '$active'$info_update WHERE endpoints.url = '$row[0]'";
    mysqli_query($mysqli_db, $query) or print(mysqli_error($mysqli_db));
}

mysqli_close($mysqli_db);
