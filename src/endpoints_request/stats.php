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
$node_triples = array();

$mysqli_db = mysqli_connect($hostname, $username, $password, $schema)
        or die("Unable to connect to MySQL");

$query = "SELECT url FROM `endpoints`";
$result_query = mysqli_query($GLOBALS["mysqli_db"], $query);
$endpoints_on_db = array();
while ($row = mysqli_fetch_assoc($result_query)) {
    array_push($endpoints_on_db, $row["url"]);
}
#var_dump($endpoints_on_db);
$nt = 0;
$nsparql10 = 0;
foreach ($endpoints_on_db as $endpoint) {
    $query = "SELECT info,links FROM `endpoints` where url='$endpoint'";
    $result_query = mysqli_query($mysqli_db, $query);
    while ($row = mysqli_fetch_assoc($result_query)) {
        $info = json_decode($row['info']);
        $node[] = $info->id;
        $node_triples[$info->id]=$info->triple;
        if($info->triple!=30000)
          $nt += $info->triple;
        else {
          $nsparql10++;
        }
        
        $links_json = $row['links'];
        if ($links_json != 'none' && $links_json != NULL) {
            $link_array = json_decode($links_json);
            foreach ($link_array as $link) {
                $id_target = $link->id;
                $type_target = $link->type;
                $links[] = array('source' => $info->id, 'target' => $id_target, "target_type" => $type_target);
            }
        }
    }
}

$fp = fopen("stats_alllinks.txt", "w");
foreach($links as $link) {
  fwrite($fp,"$link[source]|$link[target]|$link[target_type]\n");
}
fclose($fp);

$almenounlinkout = 0;
$almenounlinkin = 0;
$sololinkout = 0;
$sololinkin = 0;
$nolink = 0;
$fp = fopen("stats_linkage.txt", "w");
$ft = fopen("stats_percent.txt", "w");

foreach ($node as $n) {
    $count_out = 0;
    $count_out_ext = 0;
    $count_in = 0;
    foreach ($links as $link) {
        if ($link["source"] == $n) {
            $count_out++;
            if($link['target_type']=='external')
              $count_out_ext++;
        }
        if ($link["target"] == $n) {
            $count_in++;
        }
    }

    $stringa = $n . "|" . $count_in . "|" . $count_out . "|" . $count_out_ext . "|" . $node_triples[$n] . "\n";

    if ($count_out > 0) {
        $almenounlinkout++;
    }

    if ($count_in > 0) {
        $almenounlinkin++;
    }

    if ($count_out > 0 && $count_in == 0) {
        $sololinkout++;
    }

    if ($count_out == 0 && $count_in > 0) {
        $sololinkin++;
    }

    if ($count_out == 0 && $count_in == 0) {
        $nolink++;
    }

    fwrite($fp, $stringa);
    echo $stringa;
}
$stringa = "ntriples: ".$nt."\nnsparql 1.0: $nsparql10\n";
fwrite($fp, $stringa);
echo "\n$stringa\n";

$stringa2= "almeno un link out: $almenounlinkout\nalmeno un link in: $almenounlinkin\nsolo link out: $sololinkout\nsolo link in: $sololinkin\nno links: $nolink\n";
fwrite($ft, $stringa2);
echo $stringa2;

fclose($ft);
fclose($fp);

