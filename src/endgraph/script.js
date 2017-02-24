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
var root;
var clicks = 0, timer = null;
var tooltip;
var info;
var drag;
var svg;
var vis;
var zoom;
var defaultRadius = 15;
var maxRadius = 60;

jQuery(document).ready(function () {


    root = localStorage.getItem("root");
    console.log(root);

    width = (window.innerWidth || document.body.clientWidth);
    height = (window.innerHeight || document.body.clientHeight);


    //var width = 1024;
    //var height = 1024;

    //setta gli attributi del layout force
    force = d3.layout.force()
            .linkDistance(50)
            .charge(-2000)
            .gravity(0.05)
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

    //aggiunge un svg container al div
    svg = d3.select("body")
            .append("svg:svg")
            .attr("width", width)
            .attr("height", height);

//aggiunge le frecce
    svg.append("svg:defs").selectAll("marker")
            .data(["end"])      // Different link/path types can be defined here
            .enter().append("svg:marker")    // This section adds in the arrows
            .attr("id", String)
            .attr("viewBox", "0 -5 10 10")
            .attr("refX", 50)
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
        url: 'find_links_root.php',
        type: "POST",
        dataType: 'json',
        data: {
            node: root
        }}).done(function (data) {
        //salva i nodi nella variabile globale
        nodes = data.nodes;

        //mappatura dei numeri in nomi per i link
        var nodeMap = {};
        data.nodes.forEach(function (x) {
            x.clicked = false;
            x.connection = 0;
            x.parent = nodes[0].name;
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
            var element = d.source.name + ";" + d.target.name;
            linkList.push(element);
            //console.log(linkList);
        });


//posiziono il nodo iniziale al centro della pagina
        nodes[0].fixed = true;
        nodes[0].x = width / 2;
        nodes[0].y = height / 2;
        nodes[0].clicked = true;
        nodes[0].root = true;
        nodes[0].connection = data.nodes.length - 1;
        //console.log(nodes[0]);

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
                .on("click", click)
                .on("mouseover", over)
                .on("mouseout", out)
                .call(force.drag);

        g.append("circle")
                .attr('id', function (d) {
                    return "node" + d.name;
                })
                .attr("r", radius)
                .style("stroke", colorStroke)
                .style("stroke-width", "2")
                .style("fill", color);


        g.append("text")
                .attr("dy", ".35em")
                .text(function (d) {
                    return d.name;
                });

    });


});



//funzione del passaggio del tempo
function tick() {

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


// funzione sul click del nodo
//devo gestire i nodi già messi
//inserire quelli nuovi
//evitare doppi archi
//successivamente chiudere quelli collegati con un click

function click(d) {

    if (d3.event.defaultPrevented)
        return; // ignore drag

    var g_clicked = this;

    clicks++;  //count clicks

    if (clicks === 1) {

        timer = setTimeout(function () {

            if (d.clicked === false && d.type === 'internal') {
                addLinkedNode(d);
                d3.select(g_clicked).select("circle").transition()
                        .duration(700)
                        .style("stroke", "#CF0000");
            }
            if (d.clicked === true && d.type === 'internal') {
                closeLinkedNode(d);
                d3.select(g_clicked).select("circle").transition()
                        .duration(700)
                        .style("stroke", "#005C00");
            }

            clicks = 0;             //after action performed, reset counter

        }, 250);

    } else {
        clearTimeout(timer);    //prevent single-click action
        clicks = 0;             //after action performed, reset counter
    }


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

function closeLinkedNode(n) {
    element_elimination(n);
    n.clicked = false;
    n.connection = 0;
    axrelations_graph_finder();
    //nodeList = [n.name];
    //linkList = [];
    if (n.root == true) {
        linkList = [];
    }

    console.log(linkList)
    console.log(nodeList)

    restart();
}

//funzione chiamata quando si clicca su un nodo da esplorare
function addLinkedNode(n) {
    $.ajax({
        url: 'find_links_node.php',
        type: "POST",
        dataType: 'json',
        data: {
            node: n.name
        }}).done(function (data) {

        data.nodes.forEach(function (d) {
            if (!include(nodeList, d.name)) {
                //console.log(d.name)
                nodeList.push(d.name);
                d.clicked = false;
                nodes.push(d);
            }
        });

        //mappatura dei link
        var nodeMap = {};
        nodes.forEach(function (x) {
            nodeMap[x.name] = x;
        });
        data.links = data.links.map(function (x) {
            return {
                source: nodeMap[x.source],
                target: nodeMap[x.target]
            };
        });

        data.links.forEach(function (d) {
            var element = d.source.name + ";" + d.target.name;
            if (!include(linkList, element)) {
                links.push(d);
                linkList.push(element);
            }
        });

        //console.log(links);
        n.connection = data.nodes.length - 1;
        n.clicked = true;
        //d3.select(n).style("fill", "red")
        restart();

    });
}

function element_elimination(source) {//Defines the functions for deletion. This function deletes all the edges that have for source 'source'.
    for (var i = 0; i < links.length; i++) {
        if (links[i].source.index == source.index) {//Search the edges where 'source' is the source or the target.
            links.splice(i, 1); //Deletes the edge.
            linkList.splice(i, 1);
            i = i - 1;
        }
    }
}
function axrelations_graph_finder() {//Function that completes the elimination. Removes all the isolated nodes with a breadth search.
    var stack_of_nodes = Array();
    for (var i = 0; i < nodes.length; i++) {
        if (nodes[i].root == true) {
            nodes[i].reached = true;//Starts the labelling from the root. For more roots more pushes!
            stack_of_nodes.push(nodes[i]);
        } else
            nodes[i].reached = false;
    }
    while (stack_of_nodes.length != 0) {
        var node_reached = stack_of_nodes.pop();
        for (var i = 0; i < links.length; i++) {
            if (links[i].source.index == node_reached.index && links[i].target.reached == false) {
                links[i].target.reached = true;
                stack_of_nodes.push(links[i].target);
                // console.log("push con index di: "+links[i].target.name);
            }
        }
    }
    for (var i = 0; i < nodes.length; i++) {
        if (nodes[i].reached == false) {//For the nodes not reached.
            for (var j = 0; j < links.length; j++) {
                if (nodes[i].index && links[j].source.index == nodes[i].index) {//Deletes the edges.
                    // console.log("Delete links from: "+nodes[i].name+" to: "+links[j].target.name);
                    links.splice(j, 1);
                    j = j - 1;
                }
            }
            nodes.splice(i, 1);
            nodeList.splice(i, 1);
            i = i - 1;
        }
    }
    restart();
}

function restart() {
    console.log("restart called");

    //aggiorna i nodi
    node = node.data(force.nodes());

    var g = node.enter().append("g")
            .attr("class", "node")
            .on("dblclick", dblclick)
            .on("click", click)
            .on("mouseover", over)
            .on("mouseout", out)
            .call(force.drag);

    g.append("circle")
            .attr('id', function (d) {
                return "node" + d.name;
            })
            .attr("r", radius)
            .style("stroke", colorStroke)
            .style("stroke-width", "2")
            .style("fill", color);

    g.append("text")
            .attr("dy", ".35em")
            .text(function (d) {
                return d.name;
            });

    node.exit().remove();

    //aggiorna i link
    link = link.data(force.links());

    link.enter().insert("line", ".node")
            .attr("class", "link")
            .attr("marker-end", "url(#end)");

    link.exit().remove();


    //riavvia il layout force
    force.start();

    //console.log(force.links())
}

//funzione che gestisce il raggio dei cerchi in base al numero di triple
function radius(d) {
    if (d.triple == 30000) {
        d.r = defaultRadius;
        return defaultRadius;
    } else {
        var r = (Math.log(d.triple) / Math.LN10) * 2 + 10;//d.triple / 50000000;
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

//event on mouse out
function out(d) {
    //if (d.type == 'internal') {
    tooltip.transition().duration(500).style("opacity", 0);
    d3.select(this).select("circle").transition()
            .duration(200)
            .attr("r", d.r);
    force.links().forEach(function (x) {
        if (x.source.name == d.name) {
            d3.select('#node' + x.target.name).transition()
                    .duration(200)
                    .attr("r", x.target.r);
            outLinkNumber++;
        }
    });
    // }
}

//event on mouse over
function over(d) {
    d3.select(this).select("circle").transition()
            .ease("elastic")
            .duration(500)
            .attr("r", d.r + 5);
    outLinkNumber = 0;
    inLinkNumber = 0;
    force.links().forEach(function (x) {
        if (x.source.name == d.name) {
            d3.select('#node' + x.target.name).transition()
                    .ease("elastic")
                    .duration(500)
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

//sceglie il colore del bordo del cerchio in base al parametro type
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

//evento trascinamento
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