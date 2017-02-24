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

var nodes;
var links;
var nodeList = [];
var linkList = [];
var clicks = 0, timer = null;
var tooltip;
var drag;
var foci;
var svg;
var vis;
var zoom;
var defaultRadius = 10;
var maxRadius = 60;


jQuery(document).ready(function () {

    width = (window.innerWidth || document.body.clientWidth);
    height = (window.innerHeight || document.body.clientHeight);

    foci = {x: (width / 2), y: (height / 2)};

    //var width = 1024;
    //var height = 1024;

    //setta gli attributi del layout force
    force = d3.layout.force()
            .linkDistance(50)
            .charge(-2000)
            .chargeDistance(1500)
            .gravity(0.15)
            .friction(0.7)
            .size([width, height])
            .on("tick", tick);

    //applica il fixed ai nodi
    drag = force.drag()
            .on("dragstart", dragstart)
            .on("dragend", dragend);


    zoom = d3.behavior.zoom();


    tooltip = d3.select("body").append("div")
            .attr("class", "tooltip")
            .style("opacity", 0);

    info = d3.select("#info");

    info.on("mouseover", function () {
        info.transition().duration(100).style("opacity", 0.2);
        //$('#info').css("pointer-events", "none");
    });

    info.on("mouseout", function () {
        info.transition().duration(100).style("opacity", 1);

    });



    svg = d3.select("body")
            .append("svg:svg")
            .attr("width", width)
            .attr("height", height);

    /*aggiunge un svg container al div
     svg = d3.select("#graph").append("svg")
     .attr("width", width)
     .attr("height", height);*/

//aggiunge le frecce
    svg.append("svg:defs").selectAll("marker")
            .data(["end"])      // Different link/path types can be defined here
            .enter().append("svg:marker")    // This section adds in the arrows
            .attr("id", String)
            .attr("viewBox", "0 -5 10 10")
            .attr("refX", 80)
            .attr("refY", 0)
            .attr("markerWidth", 6)
            .attr("markerHeight", 6)
            .attr("orient", "auto")
            .append("svg:path")
            .attr("d", "M0,-5L10,0L0,5")
            .attr('fill', '#747474');

    vis = d3.select("svg").append('svg:g')
            .attr("id", "axrelations_graph_main_g")
            .attr("transform", "translate(" + zoom.translate() + ")scale(" + zoom.scale() + ")");
    var mainrect = vis.append('svg:rect')
            .attr('id', "frame")
            .attr('width', '100%')
            .attr('height', '100%')
            .attr('x', 30)
            .attr('fill', 'white');

    svg.call(zoom.on("zoom", redraw));


    //selezionano tutti i nodi e tutti i link
    link = vis.selectAll(".link");
    node = vis.selectAll(".node");


    //chiamata asincrona per ricevere i dati
    $.ajax({
        url: 'find_links_all.php',
        type: "POST",
        dataType: 'json'
    }).done(function (data) {
        //salva i nodi nella variabile globale
        nodes = data.nodes;

        //mappatura dei numeri in nomi per i link
        var nodeMap = {};
        data.nodes.forEach(function (x) {
            x.clicked = false;
            x.connection = 0;
            nodeList.push(x.name);
            nodeMap[x.name] = x;
        });
        data.links = data.links.map(function (x) {
            return {
                source: nodeMap[x.source],
                target: nodeMap[x.target]
            };
        });

        //salvo i link
        links = data.links;

        data.links.forEach(function (d) {
            console.log(d);
            var element = d.source.name + ";" + d.target.name;
            linkList.push(element);
            console.log(linkList);
        });


        force
                .nodes(nodes)
                .links(links)
                .start();

        link = link.data(force.links())
                .enter().insert("line", ".node")
                .attr("class", "link")
                .attr("marker-end", "url(#end)");

        node = node.data(force.nodes());
        var g = node.enter().append("g")
                .attr("class", "node")
                .on("dblclick", dblclick)
                .on("mouseover", over)
                .on("mouseout", out)
                .call(force.drag);

        g.append("circle")
                .attr('id', function (d) {
                    return "node" + d.name;
                })
                .attr("r", radius)
                .style("stroke", '#000000')
                .style("stroke-width", "1")
                .style("fill", color);


        g.append("text")
                .attr("dy", ".35em")
                .text(function (d) {
                    return d.name;
                });

    });


});

//funzione che gestisce il raggio dei cerchi in base al numero di triple
function radius(d) {
    if (d.triple == 30000) {
        d.r = defaultRadius;
        return defaultRadius;
    } else {
        var r = (Math.log(d.triple) / Math.LN10) * 2;//d.triple / 50000000;
        if (r < 5) {
            d.r = defaultRadius;
            return defaultRadius;
        } else {
            if (r > maxRadius) {
                d.r = maxRadius;
                return maxRadius;
            } else {
                d.r = r;
                return r;
            }
        }

    }
}


//funzione del passaggio del tempo
function tick(e) {

    var k = .01 * e.alpha;

    // Push nodes toward their designated focus.
    nodes.forEach(function (o, i) {
        o.y += (foci.y - o.y) * k;
        o.x += (foci.x - o.x) * k;
    });

    //movimento dei link e dei nodi

    link.attr("x1", function (d) {
        return d.source.x;
    })
            .attr("y1", function (d) {
                return d.source.y;
            })
            .attr("x2", function (d) {
                return d.target.x;
            })
            .attr("y2", function (d) {
                return d.target.y;
            });

    node.attr("transform", function (d) {
        return "translate(" + d.x + "," + d.y + ")";
    });
}


function dblclick(d) {
    if (d.type == 'internal') {
        $.ajax({
            url: 'find_url.php',
            type: "POST",
            dataType: 'json',
            data: {
                node: d.name
            }}).done(function (data) {
            localStorage.setItem("value", data);
        });
    }
}

//decide il colore del nodo
function color(d) {
    if (d.root == true) {
        return "#64B6B6";
    } else {
        if (d.type == 'internal') {
            return '#BFDFDF';
        }
        if (d.type == 'external') {
            return '#FF8330';
        }
    }
}

function out(d) {
    //if (d.type == 'internal') {
    tooltip.transition().duration(500).style("opacity", 0);
    d3.select(this).select("circle").transition()
            .duration(500)
            .attr("r", d.r);
    force.links().forEach(function (x) {
        if (x.source.name == d.name) {
            d3.select('#node' + x.target.name).transition()
                    .duration(500)
                    .attr("r", x.target.r);
            outLinkNumber++;
        }
    });
    // }
}

function over(d) {
    d3.select(this).select("circle").transition()
            .ease("elastic")
            .duration(700)
            .attr("r", d.r + 5);
    outLinkNumber = 0;
    inLinkNumber = 0;

    force.links().forEach(function (x) {
        if (x.source.name == d.name) {
            d3.select('#node' + x.target.name).transition()
                    .ease("elastic")
                    .duration(700)
                    .attr("r", x.target.r + 5);
            outLinkNumber++;
        }
        if (x.target.name == d.name) {
            inLinkNumber++;
        }
    });
    if (d.type == 'internal') {
        var triple = d.triple;
        if (triple == 30000) {
            triple = "N/A"
        }
        tooltip.transition().duration(200).style("opacity", .9);
        tooltip.html("Id: " + d.name + "</br>Number of triple: " + triple + "</br>Out-connection: " + outLinkNumber + "<br/>In-connection: " + inLinkNumber)
                .style("background", "#B0C4DE")
                .style("left", (d3.event.pageX + 25) + "px")
                .style("top", (d3.event.pageY - 20) + "px");
    }
    if (d.type == 'external') {
        tooltip.transition().duration(200).style("opacity", .9);
        tooltip.html("Id: " + d.name + "</br>In-connection: " + inLinkNumber)
                .style("background", "#ffb834")
                .style("left", (d3.event.pageX + 25) + "px")
                .style("top", (d3.event.pageY - 20) + "px");
    }
}

function colorStroke(d) {
    if (d.root == true) {
        return '#CF0000';
    } else {
        if (d.type == 'internal') {
            return '#005C00';
        }
        if (d.type == 'external') {
            return 'black';
        }
    }
}

function dragend(d) {
    info.transition().duration(500).style("opacity", 1);
}

function dragstart(d) {
    d3.event.sourceEvent.stopPropagation();
    d3.select(this).classed("fixed", d.fixed = true);
    tooltip.transition().duration(500).style("opacity", 0);
    info.transition().duration(500).style("opacity", 0.2);
}

function redraw() { //Function for zoom.
    var translate = d3.event.translate;
    var scale = d3.event.scale;
    vis.attr("transform",
            "translate(" + d3.event.translate + ")"
            + " scale(" + d3.event.scale + ")");
}

function include(arr, obj) {
    return (arr.indexOf(obj) != -1);
}