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

error_reporting(0);


require_once 'login_db.php';

$url = $_POST["node"];
$id = NULL;
$links = array();
$node = array();

$mysqli_db = mysqli_connect($hostname, $username, $password, $schema)
        or die("Unable to connect to MySQL");
$query = "SELECT info FROM `endpoints` where url='$url'";
$result_query = mysqli_query($mysqli_db, $query);

while ($row = mysqli_fetch_assoc($result_query)) {
    $id = json_decode($row['info'])->id;
    $node[] = array('name' => $id, 'type' => 'internal', 'triple' => json_decode($row['info'])->triple);
}

$query = "SELECT links FROM `endpoints` where url='$url'";
$result_query = mysqli_query($mysqli_db, $query);

while ($row = mysqli_fetch_assoc($result_query)) {

    $links_json = $row['links'];
    $link_array = (json_decode($links_json));
    foreach ($link_array as $link) {
        $id_target = $link->id;
        $type_target = $link->type;
        $node[] = array('name' => $id_target, 'type' => $type_target, 'triple' => $link->triple);
        $links[] = array('source' => $id, 'target' => $id_target);
    }
}

$string = json_encode($node);
$string2 = json_encode($links);

$result = "{" . '"nodes": ' . "$string" . "\n" . ',"links": ' . "$string2" . "\n" . "}";

$result2 = "{" . '"nodes":' . "$string" . ',"links":' . "$string2" . "}";
echo $result2;
