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

#error_reporting(0);

require_once 'login_db.php';

$links = array();
$node = array();
$node_inserted_name = array();

$mysqli_db = mysqli_connect($hostname, $username, $password, $schema)
        or die("Unable to connect to MySQL");

$query = "SELECT url FROM `endpoints`";
$result_query = mysqli_query($GLOBALS["mysqli_db"], $query);
$endpoints_on_db = array();
while ($row = mysqli_fetch_assoc($result_query)) {
    array_push($endpoints_on_db, $row["url"]);
}

#var_dump($endpoints_on_db);

foreach ($endpoints_on_db as $endpoint) {
    $query = "SELECT info FROM `endpoints` where url='$endpoint'";
    $result_query = mysqli_query($mysqli_db, $query);

    while ($row = mysqli_fetch_assoc($result_query)) {
        $id = json_decode($row['info'])->id;
        $node_inserted_name = array();
        foreach ($node as $node_element) {
            array_push($node_inserted_name, $node_element['name']);
        }
        if (!is_contained($node_inserted_name, $id)) {
            $node[] = array('name' => $id, 'type' => 'internal', 'triple' => json_decode($row['info'])->triple);
        }
    }

    $query = "SELECT links FROM `endpoints` where url='$endpoint'";
    $result_query = mysqli_query($mysqli_db, $query);

    while ($row = mysqli_fetch_assoc($result_query)) {

        $links_json = $row['links'];
        if ($links_json != 'none' && $links_json != NULL) {
            $link_array = (json_decode($links_json));
            foreach ($link_array as $link) {
                $node_inserted_name = array();
                foreach ($node as $node_element) {
                    array_push($node_inserted_name, $node_element['name']);
                }
                $id_target = $link->id;
                $type_target = $link->type;
                if (!is_contained($node_inserted_name, $id_target)) {
                    $node[] = array('name' => $id_target, 'type' => $type_target, 'triple' => $link->triple);
                }
                $links[] = array('source' => $id, 'target' => $id_target);
            }
        }
    }
}

$string = json_encode($node);
$string2 = json_encode($links);

$result = "{" . '"nodes": ' . "$string" . "\n" . ',"links": ' . "$string2" . "\n" . "}";

$result2 = "{" . '"nodes":' . "$string" . ',"links":' . "$string2" . "}";
echo $result2;
/*
  echo "{";
  echo '"nodes": ', json_encode($children), "\n";
  #echo ',"links": ', json_encode($links), "\n";
  echo "}"; */

//funzione che guarda se una stringa è contenuta in un array di stringhe
function is_contained($found_link, $namespace) {
    foreach ($found_link as $name) {
        if ($name == $namespace) {
            return true;
        }
    }
}
