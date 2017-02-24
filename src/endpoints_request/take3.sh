#!/bin/bash

echo end_point search
php endpoints_search.php

echo rdf list
php rdf_list.php

echo link search I
for (( c=0; c<400; c++ ))
do
    echo link1 $c 
    php link_search.php $c false
done

echo link search II
for (( c=0; c<400; c++ ))
do
    echo link2 $c
    php link_search.php $c true
done
