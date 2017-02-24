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

var cardinal='e'; //The cardinal direction where is put a new node in the graph, based on the position of the roots presents.
var num_esec=0;
var sg_title="Graph";
var full_screen=false;
var svg_normal_heigth;
var num_info_dialog=0;
var num_more_dialog=0;
var current_graph_title=""; //The title of the graph.
var current_graph_desc=""; //The description of the graph.
/* Parameters for makes a multi-EP search. */
var active_multiple_endpoints=null;
var multiple_endpoint_priority=0;
var multiple_endpoints=null;

/*
axrelations_graph_open is the function that construct the graph. It takes 4 parameters:
-id: the initial uri. -div_position: the position where place the graph. -type: the type for the request to server.
-endpoint: the sparql endpoint for the query.
*/
var force,links,nodes,zoom,vis,buttons;
var endpoint;//endpoint for sparql request.
var endpoints_list=new Array(); //A list of endpoints for the current nodes in the graph.
var count;//For make the relations distinct.
var stack_lifo;//Contains the actions of the user on the graph. It's used for realize the BACK function.
var initial_uri;//for save in the database.
var parent_id;//for know the starter configuration.
var user_mail;//for know if the user had already save the current configuration.
var save_operation;//for know if the user can only read or also modify a saved configuration.var initial_uri=id;//for save in the database.
var w;//the width of the graph container
var h;//the height of the graph container
var json_file; // file in cui scrivo il contenuto del mio json.
var save_r;//The id of the save for read only.
var save_rw;//The id of the save for read and write.

var initial_x=-1;
var initial_y=-1;

var default_image="images/icons/default.png"; //Default images for nodes. It's only used when a changes of image is required. It could occurs whit LD and LOD.
function concat_noduplicate(arr){//This prototype makes a concatenation of two arrays and de-duplicate items.
    var a = arr.concat();
    for(var i=0; i<a.length; ++i){
        for(var j=i+1; j<a.length; ++j){
            if(a[i].id === a[j].id || (a[i].type=="more_LD" && a[j].type=="more_LD"))
                a.splice(j--, 1);
        }
    }
    return a;
};

function axrelations_graph_open(id,div_position,type,endpointforrequest,multi_ep){
	active_multiple_endpoints=multi_ep;
	endpoint=endpointforrequest;
	initial_uri=id;
	parent_id='';//for know the starter configuration.
	user_mail='';//for know if the user had already save the current configuration.
	save_operation="read";//for know if the user can only read or also modify a saved configuration.
	num_esec++;
	//Hide the navigation panels if it's open
	d3.select("#axrelations_graph_navigation_panel").transition().style("display","none");
	d3.select("#axrelations_graph_navigation_more").transition().style("display","none");
	// Insert here the code that has to be called every time.
	w=$("#graphContainer").width();
	h=$("#graphContainer").height();
	if(h==0 || h>700) h=700;  
	count=0; //Initializes the id for the relations.
	stack_lifo=new Array();
	buttons=new Array();	
	// Defines the force attributes for the graph.
	force = d3.layout.force()
		.gravity(0.05)
		.friction(0.7)// Specifies how fast decrease the speed. For default is set to 0.9
		// .theta(1)
		.charge(-2000)
		.linkDistance(50)
		.size([w, h]);

	nodes = force.nodes();
	links = force.links();
	//Defines the zoom for the visualization. In this way it can be resetted in any moment.
	zoom=d3.behavior.zoom();
	zoom.scaleExtent([0.3,6]);
	//If there are some specified initial transistion, passed via url, sets them.
	var actual_url=document.URL;
	//Searches for the translation.
	if(actual_url.indexOf("translate")>=0){//Retrieves the translation.
		var initial_translate=actual_url.substring(actual_url.indexOf("[",actual_url.indexOf("translate"))+1,actual_url.indexOf("]",actual_url.indexOf("translate")));//returns "x,y"
		x_start=parseInt(initial_translate);
		y_start=parseInt(initial_translate.substr(initial_translate.indexOf(",")+1));
		if(x_start )initial_x=x_start;
		else initial_x=0;
		if(y_start )initial_y=y_start;
		else initial_y=0;
		zoom.translate([initial_x,initial_y]);
	}
	//Searches for the scale
	if(actual_url.indexOf("scale")>=0){//Retrieves the scale.
		var initial_scale=actual_url.substring(actual_url.indexOf("(",actual_url.indexOf("scale"))+1,actual_url.indexOf(")",actual_url.indexOf("scale")));
		if(parseFloat(initial_scale)){
			if(parseFloat(initial_scale)<0.3)zoom.scale(0.3);
			else if(parseFloat(initial_scale)>6)zoom.scale(6);
			else zoom.scale(parseFloat(initial_scale))
		}
	}	
	
	function axrelations_first_call(){
		//Loads the buttons.
		var call1time=true;
		if(type!="load_configuration"){
			d3.json("request.php?relations=sparql",function(json){	// Loads the list of relations.
				var list,i;
				list='<div id="axrelations_graph_menu_bottom"><h2>Type of relations</h2>';
				list+='<input id="axr_all_relations" type="button" value=" Select all "><input id="axr_no_relations" type="button" value=" Deselect all "><input id="axr_invert_relations" type="button" value=" Invert "><input id="hide_inverse" type="checkbox" title="hide all inverse properties"><span>Hide all inverse</span></br> ';
				list+='<ul id="axrelations_type" display="inline">';
				i=0;
				for(var r in json.relations){
					var relation=json.relations[r];
					if(relation.checked=="true" ){
						list += '<li class="relation_' + (i++) + '"><input type="checkbox" checked="checked" value="' + relation.type_name + '" title="' + relation.type_name + '">' + relation.name + '</li>';				
					}
					else if(relation.checked=="close"){
						list += '<li class="relation_' + (i++) + '"><input id="closed" type="checkbox" checked="checked" value="' + relation.type_name + '" title="' + relation.type_name + '">' + relation.name + '</li>';
					}
					else{
						list += '<li class="relation_' + (i++) + '"><input type="checkbox" value="' + relation.type_name + '" title="' + relation.type_name + '">' + relation.name + '</li>';
					}
				}
				list+='</ul></div>';
				if(call1time==true ){ 
					$("#axrelations_graph_menu_bottom").remove();
					call1time=false;
					$("#options_panels").append(list);
					$("#axrelations_graph_menu_bottom").resizable({maxHeight: 500,minHeight: 100,handles: 's'});			
					// var relations=$("#axrelations_graph_div input");
					var relations=$("#axrelations_graph_menu_bottom ul input");
					for(var i=0;i<relations.length;i++){
						relations[i].onchange=(function(r){
							add_remove_relations(this,this.checked,true);
						});
						
					}
					//Edit the function for the buttons.
					$("#axr_all_relations").unbind('click').click(function(){
						var relation_buttons=$("#axrelations_graph_menu_bottom ul input");
						for(var i=0;i<relation_buttons.length;i++){
							if(relation_buttons[i].checked!=true){
								relation_buttons[i].checked="checked";
								add_remove_relations(relation_buttons[i],relation_buttons[i].checked,true);
							}
						}
						restart();
					});
					$("#hide_inverse").unbind('change').change(function(){//Sets the function for hide all the inverse relations.
						axrelations_show_inverse(this.checked);
						axrelations_close_all_inverse();//Collapses all the inverse.
					});					
					
					$("#axr_no_relations").unbind('click').click(function(){
						var relation_buttons=$("#axrelations_graph_menu_bottom ul input");
						for(var i=0;i<relation_buttons.length;i++){
							if(relation_buttons[i].checked!=false){
								relation_buttons[i].checked=false;
								add_remove_relations(relation_buttons[i],relation_buttons[i].checked,true);
							}
						}
						restart();
					});
					$("#axr_invert_relations").unbind('click').click(function(){
						var relation_buttons=$("#axrelations_graph_menu_bottom ul input");
						for(var i=0;i<relation_buttons.length;i++){
							if(relation_buttons[i].checked!=false){
								relation_buttons[i].checked=false;
								add_remove_relations(relation_buttons[i],relation_buttons[i].checked,true);
							}
							else{
								relation_buttons[i].checked="checked";
								add_remove_relations(relation_buttons[i],relation_buttons[i].checked,true);
							}
						}
						restart();
					});
				}
			});			
		}
		//Defines some graphic elements of the page that have to be defined once.
		var location=div_position;
		var div_contents=d3.select("#"+location).append("div")
			.attr("id","axrelations_graph_div")
			.attr("float","right");	
		d3.select("#"+location).insert("h2","#axrelations_graph_div")
		.attr("id","axrelations_graph_toggle")
		.attr("class","axrelations_on ax_descriptions")
		.on("mouseup",function () {
			var toggle=d3.select("h2.axrelations_on");
					  
			if(toggle[0][0]){
				toggle.attr("class","axrelations_off ax_descriptions");
			}
			else{
				d3.select("h2.axrelations_off").attr("class","axrelations_on ax_descriptions");
			}
			$("div#axrelations_graph_div").toggle("");
		})
		.text(sg_title);
		
		//Adds click event to toggle's button
		$("h2#axrelations_graph_toggle").click();
	
		var div_grafo=div_contents.append("div")
			.attr("id","axrelations_graph_container")			
			.attr("oncontextmenu","return false;")
			.attr("float","left")
			.attr("width", w)
			.attr("height", h);
		//Menu top left.(Back and description)
		var div_g_tl=div_grafo.append("div")
			.attr("id","axrelations_options_topleft");
		//Back button for axrelations_graph
		div_g_tl.append("img")
			.attr("id","axrelations_undo")
			.attr("class","tool_function");
		//Full screen button for axrelations_graph
		div_grafo.append("img")
			.attr("id","axrelations_screenfull")
			.attr("class","tool_function");
		//Reduce screen button for axrelations_graph
		div_grafo.append("img")
			.attr("id","axrelations_screenreduce")
			.attr("class","tool_function");
		//Help button for axrelations
		div_grafo.append("img")
			.attr("id","axrelations_help")
			.attr("class","tool_function");
		//Recenter graph button
		div_grafo.append("img")
			.attr("id","axrelations_recenter")
			.attr("class","tool_function");
		//Zoom buttons
		div_grafo.append("img")
			.attr("id","axrelations_zoom_in")
			.attr("class","tool_function");
		div_grafo.append("img")
			.attr("id","axrelations_zoom_reset")
			.attr("class","tool_function");
		div_grafo.append("img")
			.attr("id","axrelations_zoom_out")
			.attr("class","tool_function");
		div_grafo.append("img")
			.attr("id","axrelations_save_configuration")
			.attr("class","tool_function");
		div_grafo.append("img")
			.attr("id","axrelations_sblock_nodes")
			.attr("class","tool_function");
		div_grafo.append("span")
			.attr("id","axrelations_share")
			.attr("class","tool_function");
		/*div_grafo.append("img")
			.attr("id","axrelations_servicemap")
			.attr("class","tool_function");*/

		var svg=div_grafo.append("svg:svg")
		.attr("id","axrelations_graph_svg")
		.attr("width", w)
		.attr("height", h)
		;
		//Creates the marker object, used for the points of arrows.
		// Per-type markers, as they don't inherit styles.	
		d3.select("svg").append("svg:defs").selectAll("marker")
			.data(["axrelations_graph_def_marker","axrelations_graph_def_marker_out","axrelations_graph_def_marker_in_big","axrelations_graph_def_marker_in"])
			.enter().append("svg:marker")
			.attr("id", String)
			.attr("viewBox", "0 -5 10 10")//Describes the line.
			.attr("refX", function(String){
				if(String=="axrelations_graph_def_marker_out") return 40;
				if(String=="axrelations_graph_def_marker_in") return -25;
				if(String=="axrelations_graph_def_marker_in_big") return -25;
				return 25;
			})
			.attr("refY", function(String){
				if(String=="axrelations_graph_def_marker_out") return 0;
				if(String=="axrelations_graph_def_marker_in") return 0;
				if(String=="axrelations_graph_def_marker_in_big") return 0;
				return 0;
			})
			// .attr("markerUnits", "strokeWidth")
			.attr("markerWidth", function(String){
				if(String=="axrelations_graph_def_marker_out") return 12;
				if(String=="axrelations_graph_def_marker_in") return 12;
				if(String=="axrelations_graph_def_marker_in_big") return 6;
				return 6;
			})
			.attr("markerHeight", function(String){
				if(String=="axrelations_graph_def_marker_out") return 12;
				if(String=="axrelations_graph_def_marker_in") return 12;
				if(String=="axrelations_graph_def_marker_in_big") return 6;
				return 6;
			})
			// .attr("fill","#ccc")
			.attr("orient", "auto")
			.append("svg:path")
			.attr("d",function(String){
				if(String=="axrelations_graph_def_marker_out" || String=="axrelations_graph_def_marker")  return "M0,-5 L10,0 L0,5";
				return "M10,-5 L0,0 L10,5";
			});//Descrive un percorso. Parte dal punto (0,-5) traccia una linea al punto (10,0) ed un'altra fino a (0,5). Dopodich� viene chiuso il percorso.	
		
		var g1=d3.select("defs").append('linearGradient').attr("id","r_grad").attr("x1","0%").attr("y1","0%").attr("x2","140%").attr("y2","0%");
		g1.append("stop").attr("offset","0%").style("stop-color","rgb(255,0,0)").style("stop-opacity","1");
		g1.append("stop").attr("offset","100%").style("stop-color","rgb(255,180,0)").style("stop-opacity","1");
		
		var g2=d3.select("defs").append("linearGradient").attr("id","g_grad").attr("x1","0%").attr("y1","0%").attr("x2","140%").attr("y2","0%");
		g2.append("stop").attr("offset","0%").style("stop-color","rgb(0,150,0)").style("stop-opacity","1");
		g2.append("stop").attr("offset","100%").style("stop-color","rgb(180,255,0)").style("stop-opacity","1");
		
		vis = d3.select("svg").append('svg:g')
		.attr("id","axrelations_graph_main_g")
		.attr("transform","translate("+zoom.translate()+")scale("+zoom.scale()+")");
		var mainrect=vis.append('svg:rect')
			.attr('id',"frame")
			.attr('width', '100%')
			.attr('height', '100%')
			.attr('x',30)
			.attr('fill', 'white');
		//Differentiate the userAgent, because in d3v2 the behaviours are different.
		if(navigator.userAgent.match(/(iPod|iPhone|iPad)/)){
		  // mainrect.call(zoom.on("zoom", redraw));
		  svg.call(zoom.on("zoom", redraw));
		  // alert("Apple SmartPhone");
		}
		else if(navigator.userAgent.match(/(Android)/))
		  svg.call(zoom.on("zoom", redraw));
		else
		  svg.call(zoom.on("zoom", redraw));
		
		if(type!="load_configuration") download_json_for_current_object(id,type); //Creates the graph.
		else{
			wait_button_load(0,id,true);//Waits that the buttons are loaded.
			// retrieve_configuration(id); //Loads the configuration.
		} 
	}
	
	if($("#axrelations_graph_div").length==0){
		axrelations_first_call();		
	}
	
	else{
		if($("#axrelations_graph_div").parent()[0].id!=div_position){//Deletes the present axrelations_graph and rebuild it.
			$("#axrelations_graph_div").remove();
			$("#axrelations_graph_toggle").remove();
			axrelations_first_call();			
		}
		else{	
			vis = d3.select("#axrelations_graph_main_g");		
			vis.selectAll("g.node").remove();
			vis.selectAll("line.link").remove();
			var svg=d3.select("#axrelations_graph_svg")
			zoom.translate([0,0]).scale(1);
			vis.attr("transform", "translate(0,0)"+" scale(1)"); //Refocuses the visualization without zoom.
			svg.call(zoom.on("zoom", redraw));
			download_json_for_current_object(id,type);
		}
	}
	/* Loads the configuration for the LOG: the urls of the button - the list of_endpoint */
	d3.json("request.php?configuration=g",function(json){
		/* Loads the options for the multi endpoint mode.*/
		if(json.active_multiple_endpoints && json.multiple_endpoint_priority && json.multiple_endpoints){
			if(active_multiple_endpoints==null)active_multiple_endpoints = json.active_multiple_endpoints;
			multiple_endpoint_priority = json.multiple_endpoint_priority;
			multiple_endpoints = json.multiple_endpoints;
		}
		//Loads the default image for the node.
		default_image=json.default_image;
		sg_title=json['graph_title'];
		$("#axrelations_graph_toggle").text(sg_title);
		var list_of_buttons=json['buttons'];
		for(var r in list_of_buttons) buttons[r]=list_of_buttons[r];
		
		d3.select("#axrelations_undo")
		.attr("id","axrelations_undo")
		.attr("disabled","disabled")
		.attr("type","image")
		.attr("src",buttons[2])
		.attr("alt","Back")
		.attr("title","Back")
		.on("mousedown",function(){
			this.src=buttons[1];
			})
		.on("mouseup",function(){
			this.src=buttons[0];
			var operation=stack_lifo.pop();
			//Refocuses the visualization
			// zoom.translate([operation.node.x,operation.node.y]);
			// vis.attr("transform", "translate("+operation.node.x+","+operation.node.y+")"); //Refocues in the center the visualization without zoom.
			//Replaces the precedent configuration of the graph
			if(operation.type=="open_all"){
				node_close_all(operation.node.relations);
			}
			if(operation.type=="close_all"){
				node_open_all(operation.node);
			}
			if(operation.type=="newroot"){//Back for an add of a new root.
				operation.node.root=null;//Removes the attribute root from the node.
				remove_status(operation.status);//Removes the option from the status panel.
				//TO_CHECK Removes the link added??
			}
			if(operation.type=="explore" || operation.type=="new_root_explore"){
				if(operation.node.isRelation==true){
					var circle=d3.select("circle#id"+operation.node.id);
					var img=d3.select("image#id"+operation.node.id);
					circle.attr("class",function(d){
						if(d.explored==true){if(d.inbound==true)return "inbound_reduced"; return "reduced"; }else{return "inbound_expanded"; return "expanded";}
					});//Changes class for color
					if($("Body.Safari").length>0){//With Safari D3 has a sort of bug. It's not possible select img like above.
						if(circle[0][0]){
							var g_element=circle[0][0].parentNode;
							img=g_element.getElementsByTagName('image');
							var image_path;
							if(operation.node.explored==false) image_path=buttons[11];
							else image_path=buttons[12];
							img[0].setAttribute('href',image_path);
						}
					}
					else{
						img.attr("xlink:href",function(d){
							if(d.explored==true){return buttons[11]; }else{return buttons[12];}
						});//Changes image.
					}
				}
				operation.node.explored=true;
				if(operation.type=="new_root_explore")operation.node.root=null;
				explore(operation.node,false);
			}
			if(operation.type=="focus" || operation.type=="addnode" || operation.type=="status_removed" || operation.type=="node_remove"){//Deletes all the nodes and all the links displayed and not.
				nodes.splice(0,nodes.length);
				links.splice(0,links.length);
				for(var i in operation.nodes){
					// if(operation.type=="focus" || operation.type=="status_removed"){
						if(operation.nodes[i].id==operation.node.id&&operation.nodes[i].name==operation.node.name){//Searches the focused node for remove the fix.
							operation.nodes[i].explored=operation.node.explored;
							if(operation.type=="focus"){operation.nodes[i].fixed=false;if(!operation.node.root)delete operation.nodes[i].root}
							else if(operation.type=="status_removed") operation.nodes[i].root=true;//Resets the root.
							else if(operation.type=="addnode"){
								if(!operation.node.single_search)delete operation.nodes[i].single_search;//Removes the boolean value single_search
								operation.nodes[i].relations=operation.node.relations;//Resets the previous relations.
							}
							else if(operation.type=="node_remove"){
								operation.nodes[i].root=operation.node.root;
								operation.nodes[i].fixed=operation.node.fixed;
							}
						}
					// }
					operation.nodes[i].present=true;
					nodes.push(operation.nodes[i]);
				}
				for(var i in operation.links){
					operation.links[i].present=true;
					links.push(operation.links[i]);
				}
				if(operation.type=="addnode")remove_status(operation.status); //Removes the option from the status log.
				if(operation.type=="focus"){//Removes the actual status option and Restores the status log.
					$(".uri_added option").remove();
					add_status_options(true,operation.status);
				}
				if(operation.type=="status_removed"){//Adds the status in to the log.
					add_status_options(true,operation.status);
				}
				restart();
			}
			if(operation.type=="reduce"){
				if(operation.node.isRelation){
					var circle=d3.select("circle#id"+operation.node.id);
					var img=d3.select("image#id"+operation.node.id);
					circle.attr("class",function(d){
						if(d.explored==true){if(d.inbound==true)return "inbound_reduced";return "reduced"; }else{if(d.inbound==true)return "inbound_expanded";return "expanded";}
					});//Changes class for color coherence
					if($("Body.Safari").length>0){//With Safari D3 has a sort of bug. It's not possible select img like above.
						if(circle[0][0]){
							var g_element=circle[0][0].parentNode;
							img=g_element.getElementsByTagName('image');
							var image_path;
							if(operation.node.explored==false) image_path=buttons[11];
							else image_path=buttons[12];
							img[0].setAttribute('href',image_path);
						}
					}
					else{
						img.attr("xlink:href",function(d){
							if(d.explored==true){return buttons[11]; }else{return buttons[12];}
						});//Changes image.
					}
				}
				//Deletes all the nodes and all the links (displayed and not)
				nodes.splice(0,nodes.length);
				links.splice(0,links.length);
				for(var i in operation.nodes){
					if(operation.nodes[i].id==operation.node.id&&operation.nodes[i].name==operation.node.name){
						operation.nodes[i].explored=operation.node.explored;
					}	
					operation.nodes[i].present=true;
					nodes.push(operation.nodes[i]);
				}
				for(var i in operation.links){
					operation.links[i].present=true;	
					links.push(operation.links[i]);
				}
				restart();
			}
			if(operation.type=="explore_more"){
				var father;
				for(var i=nodes.length-1;i>0&&operation.nodes.length!=0;i--){
					if(nodes[i].index==operation.nodes[operation.nodes.length-1].index){
						nodes.splice(i,1);
						operation.nodes.splice(operation.nodes.length-1,1);
					}
				}
				for(var i=links.length-1;i>0&&operation.links.length!=0;i--){
					if(links[i].source==operation.links[operation.links.length-1].source&&links[i].target==operation.links[operation.links.length-1].target){
						father=links[i].source;
						links.splice(i,1);
						operation.links.splice(operation.links.length-1,1);
					}
				}
				operation.node.explored=false;
				nodes.push(operation.node);
				links.push({source: father, target: operation.node});
				restart();
			}
			if(operation.type=="relation"){
				add_remove_relations(operation.relation,operation.add,false);
				if(operation.add==true)$("#axrelations_graph_menu_bottom").contents().find('input[value="'+operation.relation.value+'"]').attr("checked","checked");
				else $("#axrelations_graph_menu_bottom").contents().find('input[value="'+operation.relation.value+'"]').attr("checked",false);
				restart();
			}
			if(stack_lifo.length==0){ //de actives the button
				d3.select("#axrelations_undo").attr("disabled","disabled")
				.attr("src",buttons[2]);
			}
		})
		.on("mouseout",function(){
			this.src=buttons[0];
		});		
		//Helps button and function for axrelations_graph
		function HideDialog()
		{
		  
			$("#overlay").hide();
			$("#axrelations_help_dialog").fadeOut(300);
			$("#axrelations_save_dialog").fadeOut(300);
		}
		function HideDialogFullscreen()
		{
			axrelations_fullscreen_close();
		}
		
		function ShowDialog(modal)
	   {
			$("#overlay").show();
			$("#axrelations_help_dialog").fadeIn(300);

			if (modal){$("#overlay").unbind("click");}
			else
			{
				$("#overlay").click(function (e)
				{HideDialog();});
			}
	   }
	   
	   function axrelations_fullscreen_open(){//Opens the axrelastions_graph full-screen and extend the graph container.
			$("#axrelations_screenfull").css("display","none");
			$("#axrelations_screenreduce").css("display","block");
			$("#axrelations_fullscreen").fadeIn(300);
			$("#axrelations_graph_div").appendTo("#axrelations_fullscreen");
			svg_normal_heigth=$("#axrelations_graph_svg").height();
			$("#axrelations_graph_svg").height("100%");
			$("#axrelations_graph_svg").width($("#axrelations_graph_container").outerWidth());
			recenter_graph(0);
			full_screen=true;
		}
		function axrelations_fullscreen_close(){//Close the axrelastions_grapf full-screen and resize the graph container.
			$("#overlay").hide();
			$("#axrelations_graph_div").insertAfter("#axrelations_graph_toggle");
			if(svg_normal_heigth="NaN"){ //Firefox case. The function heoght() return NaN if not set.
				svg_normal_heigth=500;
			}
			$("#axrelations_screenfull").css("display","block");
			$("#axrelations_screenreduce").css("display","none");
			$("#axrelations_graph_svg").height(svg_normal_heigth);
			$("#axrelations_graph_svg").width($("#axrelations_graph_container").width());
			$("#axrelations_fullscreen").fadeOut(300);
			recenter_graph(0);
			full_screen=false;
		}
		var popup='<div id="axrelations_fullscreen"><table id="axrelations_help_table" style="width: 100%; border: 0px;" cellpadding="3" cellspacing="0"><tr><td class="help_dialog_title">'+sg_title+'</td><td class="help_dialog_title align_right"><a href="#" id="fullscreen_btnClose">Close</a></td></tr></tr>';
		popup+='</table></div>';
		$("html").append(popup);
		
		var popup='<div id="overlay"></div><div id="axrelations_help_dialog"><table id="axrelations_help_table" style="width: 100%; border: 0px;" cellpadding="3" cellspacing="0"><tr><td class="help_dialog_title">HELP FOR '+sg_title+'</td><td class="help_dialog_title align_right"><a href="#" id="help_btnClose">Close</a></td></tr></tr>';
		popup+="<tr><td colspan=2 style='padding:20px'><b>"+sg_title+"</b> (<a href='http://log.disit.org' target='_blank'>http://log.disit.org</a>) is provided by DISIT of UNIFI (University of Florence) (<a href='http://www.disit.dinfo.unifi.it' target='_blank'>http://www.disit.dinfo.unifi.it</a>). LOG is a browsing tool to explore LOD sparql services via their entry point. The graph is made from nodes and arcs where the nodes can be: elements (rectangles) or element's relations (circles) and the arcs show the relationships between the elements. <br />";
		popup+="<br />"
		popup+="With this tool you can explore all the RDF elements and view which contents or users are linked with that. With just one click (or tap) over a node you can see appear the navigation panel that allows you to:<br />";
		popup+="<ul><li>Explore/Reduce a node of the graph.</li>";
		popup+="<li>Focus the visualization over a node.</li>";
		popup+="<li>Open a specified content and view it's info.</li>";
		popup+="<li>Direct accessing to the info associated with an entity, attributes and their values.</li>";
		popup+="<li>Filtering relationships, inverting the filtering.</li>";
                popup+="<li>Save your linked open graph with your preferences and navigations and get their access via email, that you can share with your colleagues for reading and further browsing and change.</li>";
                popup+="</ul>";
		popup+="Under the graph there is a list of check buttons, one for each relation kind. With that you can turn on/off the visualization of relations from the "+sg_title+" visualization. If you would like to see your LOD service entry point added in the examples, please send an email to <a href='mailto:paolo.nesi@unifi.it?Subject=LOD%20info'>paolo.nesi@unifi.it</a>. LOG tool is free of use for no profit organizations. You can embed the LOG tool in your web pages.</td></tr>";
		popup+='</table></div>';
		$("html").append(popup);
		
		$(document).ready(function ()
		{
			$("#help_btnClose").click(function (e)
			{
				HideDialog();				
				e.preventDefault();
			}); 
			$("#fullscreen_btnClose").click(function (e)
			{
				HideDialogFullscreen();				
				e.preventDefault();
			});  
		});
		
		//Creates the buttons.   
		d3.select("#axrelations_help").attr("type","image")
		.attr("src",buttons[4])
		.attr("alt","Help")
		.attr("title","Help")
		.on("mouseup",function(){
			ShowDialog(false);
		});
		
		d3.select("#axrelations_screenfull").attr("type","image")
		.attr("src",buttons[9])
		.attr("alt","Full Screen")
		.attr("title","Full Screen")
		.on("mouseup",function(){
			axrelations_fullscreen_open();
		});
		d3.select("#axrelations_screenreduce").attr("type","image")
		.attr("src",buttons[10])
		.attr("alt","Reduce Screen")
		.attr("title","Reduce Screen")
		.style("display","none")
		.on("mouseup",function(){
			axrelations_fullscreen_close();
		});
		
		d3.select("#axrelations_recenter").attr("type","image")
		.attr("src",buttons[5])
		.attr("alt","Re-center")
		.attr("title","Re-center")
		.on("mouseup",function(){
			recenter_graph(0);
		});
		
		d3.select("#axrelations_zoom_in").attr("type","image")
		.attr("src",buttons[6])
		.attr("alt","Zoom In")
		.attr("title","Zoom In");
		if($("body.Android").length>0){
			d3.select("#axrelations_zoom_in")
			.on("touchend",function(){
				var s=zoom.scale();
				var t=zoom.translate();
				if(zoom.scaleExtent()[1]>=s+0.05){
					var w2=d3.select("#axrelations_graph_svg").attr("width")/2;
					var h2=d3.select("#axrelations_graph_svg").attr("height")/2;
					var s1 = s+0.05;
					var l = [ (w2 - t[0]) / s, (h2 - t[1]) / s ];
					l = [ l[0] * s1 + t[0], l[1] * s1 + t[1] ];
					t[0] += w2 - l[0];
					t[1] += h2 - l[1];
					zoom.translate(t);
					zoom.scale(s1);
					vis.attr("transform","translate("+zoom.translate()+")scale("+zoom.scale()+")"); //Aumento lo zoom.
				}
			});
		}
		else{
			d3.select("#axrelations_zoom_in")
			.on("mousedown",function(){
				var s=zoom.scale();
				var t=zoom.translate();
				if(zoom.scaleExtent()[1]>=s+0.05){
					var w2=d3.select("#axrelations_graph_svg").attr("width")/2;
					var h2=d3.select("#axrelations_graph_svg").attr("height")/2;
					var s1 = s+0.05;
					var l = [ (w2 - t[0]) / s, (h2 - t[1]) / s ];
					l = [ l[0] * s1 + t[0], l[1] * s1 + t[1] ];
					t[0] += w2 - l[0];
					t[1] += h2 - l[1];
					zoom.translate(t);
					zoom.scale(s1);
					vis.attr("transform","translate("+zoom.translate()+")scale("+zoom.scale()+")"); //Aumento lo zoom.
				}
			});
		}
		
		d3.select("#axrelations_zoom_reset").attr("type","image")
		.attr("src",buttons[7])
		.attr("alt","Zoom Reset")
		.attr("title","Zoom Reset");
		if($("body.Android").length>0){
			d3.select("#axrelations_zoom_reset")
			.on("touchend",function(){
				var s=zoom.scale();
				var t=zoom.translate();
				var w2=d3.select("#axrelations_graph_svg").attr("width")/2;
				var h2=d3.select("#axrelations_graph_svg").attr("height")/2;
				var s1 = 1;
				var l = [ (w2 - t[0]) / s, (h2 - t[1]) / s ];
				l = [ l[0] * s1 + t[0], l[1] * s1 + t[1] ];
				t[0] += w2 - l[0];
				t[1] += h2 - l[1];
				zoom.translate(t);
				zoom.scale(s1);
				vis.attr("transform","translate("+zoom.translate()+")scale("+zoom.scale()+")"); //Resetto lo zoom.
			});		

		}
		else{
			d3.select("#axrelations_zoom_reset")
			.on("mouseup",function(){
				var s=zoom.scale();
				var t=zoom.translate();
				var w2=d3.select("#axrelations_graph_svg").attr("width")/2;
				var h2=d3.select("#axrelations_graph_svg").attr("height")/2;
				var s1 = 1;
				var l = [ (w2 - t[0]) / s, (h2 - t[1]) / s ];
				l = [ l[0] * s1 + t[0], l[1] * s1 + t[1] ];
				t[0] += w2 - l[0];
				t[1] += h2 - l[1];
				zoom.translate(t);
				zoom.scale(s1);
				vis.attr("transform","translate("+zoom.translate()+")scale("+zoom.scale()+")"); //Resetto lo zoom.
			});		
		}
		
		d3.select("#axrelations_zoom_out").attr("type","image")
		.attr("src",buttons[8])
		.attr("alt","Zoom Out")
		.attr("title","Zoom Out")
		.on("mousedown",function(){
			var s=zoom.scale();
			var t=zoom.translate();
			if(zoom.scaleExtent()[0]<=s-0.05){
				var w2=d3.select("#axrelations_graph_svg").attr("width")/2;
				var h2=d3.select("#axrelations_graph_svg").attr("height")/2;
				var s1 = s-0.05;
				var l = [ (w2 - t[0]) / s, (h2 - t[1]) / s ];
				l = [ l[0] * s1 + t[0], l[1] * s1 + t[1] ];
				t[0] += w2 - l[0];
				t[1] += h2 - l[1];
				zoom.translate(t);
				zoom.scale(s1);
				vis.attr("transform","translate("+zoom.translate()+")scale("+zoom.scale()+")"); //Diminuisco lo zoom.
			}
		});
		d3.select("#axrelations_sblock_nodes").attr("type","image")
			.attr("src",buttons[14])
			.attr("alt","Unlock the Nodes")
			.attr("title","Unlock the Nodes")
			.on("mousedown",function(){axrelations_sblock_nodes();});
		d3.select("#axrelations_share")
			.text("Embed")
			.attr("alt","Embed the Graph")
			.attr("title","Embed the Graph")
			.on("mousedown",function(){axrelations_share();});
		/*d3.select("#axrelations_servicemap").attr("type","image")
			.attr("src",buttons[17])
			.attr("alt","Find your nodes in servicemap")
			.attr("title","Find your nodes in the servicemap")
			.on("mousedown",function(){axrelations_servicemap();});	*/
			
		
		d3.select("#axrelations_save_configuration").attr("type","image")
		.attr("src",buttons[13])
		.attr("alt","Save and Share")
		.attr("title","Save and Share")
		.on("mousedown",function(){save_handler();});
	});
	
	/* force tick is the function that is calles from force every step of the force simulation.
	With tick the position of all the nodes are updated each step.
	*/
	force.on("tick", function() {
		if($("body.Android").length>0){
			vis.selectAll("g.node")
				.attr("transform", function(d) { 				
					if(d.x && d.y)return "translate(" + d.x + "," + d.y + ")";});
			vis.selectAll("line.link")
			  .attr("x1", function(d) {if(d.source.x)return d.source.x;})
			  .attr("y1", function(d) {if(d.source.y)return d.source.y;})
			  .attr("x2", function(d) {if(d.source.x)return d.target.x;})
			  .attr("y2", function(d) {if(d.source.y)return d.target.y;});
			
		}
		else{
			vis.selectAll("g.node")
				.attr("cx", function(d) { return d.x; })
				.attr("cy", function(d) { return d.y; })
				.attr("transform", function(d) { 
				//Controlli per far si che non esca fuori dal piano.
				// if(d.x>w)d.x=w;
				// else if(-d.x>w)d.x=-w;
				// if(d.y>h)d.y=h;
				// else if(-d.y>h)d.y=-h;
				return "translate(" + d.x + "," + d.y + ")";});
			vis.selectAll("line.link")
			  .attr("x1", function(d) {return d.source.x;})
			  .attr("y1", function(d) {return d.source.y;})
			  .attr("x2", function(d) {return d.target.x;})
			  .attr("y2", function(d) {return d.target.y;});
		}
	});
	
	var node;
	var link;
	var t;			
		
	$(document).bind('click', function(e){
		if(e.target.parentNode.className&&e.target.parentNode.className.baseVal=="node"){
			$("#axrelations_graph_navigation_panel").css("top",e.pageY).css("left",e.pageX);			
			$("#axrelations_graph_navigation_more").css("top",e.pageY).css("left",e.pageX);			
		}
		else{
			if($("body.Android").length>0 || $("body.iPad").length>0)d3.selectAll(".node_remove").attr("display","none");
			d3.select("#axrelations_graph_navigation_panel").transition()
			  .duration(500)
			  .style("display","none");
			d3.select("#axrelations_graph_navigation_more").transition()
			  .duration(500)
			  .style("display","none");
		}
	});
		
	$(document).bind('contextmenu','#right', function(e){//Menu that appera with the right click.
		if(e.target.parentNode.className&&e.target.parentNode.className.baseVal=="node"){
			$("#axrelations_graph_navigation_panel").css("top",e.pageY).css("left",e.pageX);			
			$("#axrelations_graph_navigation_more").css("top",e.pageY).css("left",e.pageX);			
		}
		else{
			d3.select("#axrelations_graph_navigation_panel").transition()
			  .duration(500)
			  .style("display","none");
			d3.select("#axrelations_graph_navigation_more").transition()
			  .duration(500)
			  .style("display","none");
		}
	});	
	$( document ).ajaxError(function(event, request, settings){
		console.log("Error: "+request.response);
		alert("There was an error for your request. We couldn't save yours actual configuration.");
		$("#axrelations_save_dialog").fadeOut(300);
		$("#axrelations_save_dialog").remove();
		$("#overlay").hide();
	});
}
function recenter_graph(node_index){
	//Finds the root and check the translation from the center.
	var all_nodes=$("#axrelations_graph_main_g g");
	var nodo_radice;
	for(var i=0;i<all_nodes.length;i++){
		if(all_nodes[i].__data__.id==nodes[node_index].id&&all_nodes[i].__data__.type==nodes[node_index].type){
			nodo_radice=all_nodes[i];
			break;
		}
	}
	// var xforms = nodo_radice.getAttribute('transform');
	// var parts  = /translate\(\s*([^\s,)]+)[ ,]([^\s,)]+)/.exec(xforms);
	// var firstX = parts[1],
		// firstY = parts[2];
	var firstX=nodes[node_index].x;
	var firstY=nodes[node_index].y;
	var s=zoom.scale();
	var t=zoom.translate();
	var h=$("#axrelations_graph_svg").height()/2;
	var w=$("#axrelations_graph_svg").width()/2;
	if(h.toString()=="Nan" || w.toString()=="NaN"){ //For firefox the function .height and .width not work allways correctly.
		var w=d3.select("#axrelations_graph_svg").attr("width")/2;
		var h=d3.select("#axrelations_graph_svg").attr("height")/2;
	}
	var x_next=w-parseInt(firstX)*s;
	var y_next=h-parseInt(firstY)*s;
	zoom.translate([x_next,y_next]);
	vis.attr("transform", "translate("+zoom.translate()+")"+" scale("+zoom.scale()+")"); //Ricentro la visualizzazione e resetto lo zoom.
}
function redraw(){ //Function for zoom.
  var translate=d3.event.translate;
  var scale=d3.event.scale;
  vis.attr("transform",
	  "translate(" +d3.event.translate+ ")"
	  + " scale(" + d3.event.scale + ")");
}
function download_json_for_current_object(id,type){
/* Downloads information for the first object. */
	var call1time=true;
	var current_end='&sparql='+endpoint;
	d3.json("request.php?"+type+"="+encodeURIComponent(id)+""+current_end, function(json) { 			
		if(!json){
			$( "#error_uri_not_exist" ).remove();
			var message="<div id='error_uri_not_exist' title='Try again'><p>The current uri does not exist or is not present in the current endpoint. Please check the correctness of the URI. </p></div>";
			$("body").append(message);
			$( "#error_uri_not_exist" ).dialog();
			return;
		}
		if(!json.relations){
			$( "#error_uri_not_exist" ).remove();
			var message="<div id='error_uri_not_exist' title='Try again'><p>The current uri does not exist or is not present in the current endpoint. Please check the correctness of the URI. </p></div>";
			$("body").append(message);
			$( "#error_uri_not_exist" ).dialog();
		}
		d3.select("#axrelations_graph_svg").style("opacity","1");
		$("#axrelations_graph_container").css("background","none");
		json_file = json;
		restart();
		if(json_file!=null && call1time==true){
			call1time=false;
			step1(null,null,endpoint);
		}			
	});
}
function restart() {//Function for update the visualization and d3
	t=Date.now();
	link = vis.selectAll("line.link")
	  .data(links, function(d) { 
	  return d.source.type+d.source.id + "-" +d.source.type+ d.target.id; 
	  }); 
	//Il secondo parametro di data serve a definire una chiave univoca.
	link.enter().insert("svg:line", "g.node")
	.attr("opacity",function(d){
		if(d.present==true){//Present � una variabile che mi permette di decidere se stampare un arco fin da subito o no.
			d.present=false;
			return 1;
		}
		else return 1e-6;
	})
	.attr("marker-end", function(d){//Defines the arrow to place at the end of the line.		
		if(d.target.isRelation==true){
			if(d.target.inbound==true) return null;
			return "url(#axrelations_graph_def_marker)";
		}
		if(d.source.isRelation==true){
			if(d.source.inbound==true) return null;
			return "url(#axrelations_graph_def_marker_out)";
		}
		return null;
	})
	.attr("marker-start", function(d){//Defines the arrow to place at the start of the line.		
		if(d.target.isRelation==true){
			if(!d.target.inbound) return null;
			return "url(#axrelations_graph_def_marker_in_big)";
		}
		if(d.source.isRelation==true){
			if(!d.source.inbound) return null;
			return "url(#axrelations_graph_def_marker_in)";
		}
		return null;
	})
	.attr("class",function(d){
		if(d.target.isRelation==true) return "link relation_in";
		return "link relation_out";
	});
	link.exit().remove();
	
	node = vis.selectAll("g.node")
	  .data(nodes, function(d) {return d.type+" "+d.id;});
	//Il secondo parametro di data serve a definire una chiave univoca. In pratica associa a __data__ dell'elemento l'attributo name.
	
	var element_height=72/2; //Dimensioni delle immagini.
	var element_width=120/2;
	var relations_radius=36/2; //Raggio delle relazioni.
	
	//Define focus functions to call with mouse over.
	// !!!The code above probably it can be done a little easyer setting the variable zoom of the nodes elements.
	//------------------------<START FOCUS>----------------------------
	var focus_rect=function(element,focus){
		if(element_width*zoom.scale()<=120 && focus==true){
				d3.select(element).transition()
					.duration(500)
					.attr("class","focus")
					.attr("width",function(d){return (120+20)/zoom.scale();})		
					.attr("height",function(d){return (72+20)/zoom.scale();});
		}
		else{						
			d3.select(element).transition()
				.duration(500)
				.attr("class","focus")
				.attr("width",function(d){return (element_width)+20;})		
				.attr("height",function(d){return (element_height)+20;});
		}
	}
	var focus_img=function(element,focus){
		if(!element.__data__.isRelation){
			if(element_width*zoom.scale()<=120 && focus==true){
				d3.select(element).transition()
					.duration(500)
					.attr("class","focus")
					.attr("width",function(d){return (120+10)/zoom.scale();})		
					.attr("height",function(d){return (72+10)/zoom.scale();});
			}
			else{						
				d3.select(element).transition()
					.duration(500)
					.attr("class","focus")
					.attr("width",function(d){return (element_width)+10;})		
					.attr("height",function(d){return (element_height)+10;});
			}
		}
		else{
			//The immage must be scaled.
		}
	}
	var focus_x=function(element){//Focuses in for the image of the x for close
		if(!element.__data__.isRelation){
			if(element_width*zoom.scale()<=120){
				var new_x;
				if(zoom.scale()<=1) new_x=(13/zoom.scale()+(120+10)/(2*zoom.scale()));
				else new_x=(13/zoom.scale()+(120+10)/(2*zoom.scale()*zoom.scale()));
				d3.select(element).transition()
					.duration(500)
					// .attr("x",function(d){var new_x=((120/2+10)/(zoom.scale()));
					// .attr("width",function(d){return (120/2.5)/(zoom.scale());})		
					// .attr("height",function(d){return (72/2.5)/(zoom.scale());});
					.attr("x",function(d){
						return new_x;})
					.attr("width",function(d){return (120/2.5)/(zoom.scale());})		
					.attr("height",function(d){return (72/2.5)/(zoom.scale());});
			}
			else{						
				d3.select(element).transition()
					.duration(500)
					.attr("x",function(d){var new_x=(13)+10;
						return new_x;});
					// .attr("width",function(d){return (element_width/2.5);})		
					// .attr("height",function(d){return (element_height/2.5);});
			}
		}
		else{
			//The immage must be scaled.
		}
	}
	var focus_circle=function(element){
		d3.select(element).transition()
			  .attr("class",function(d){
			  if(d.explored==true){
				if(d.inbound==true)return "inbound_expanded focus"; return "expanded focus"; }else{if(d.inbound==true)return "inbound_reduced focus"; return "reduced focus"};
			  })
			  .duration(500)
			  .attr("r", function(d){return relations_radius+8;});
	}
	var focus_text=function(element){
		d3.select(element).transition()
			  .duration(500)
			  .attr("y",-28);
	}
	var out_focus_rect=function(element){
		d3.select(element).transition()
		  .duration(300)
		  .attr("class",null)//null annulla la classe.
		  .attr("width",function(d){return (element_width+10);
			})		
			.attr("height",function(d){return (element_height+10);
			});
	}
	var out_focus_img=function(element){			
		d3.select(element).transition()
			.duration(300)
			.attr("class",null)
			.attr("width",function(d){return (element_width);})		
			.attr("height",function(d){return (element_height);});			
	}
	var out_focus_x=function(element){//Focuses out for the image of the x for close
		d3.select(element).transition()
			.duration(300)
			.attr("x",function(d){
				if(!d.isRelation)return 13;
				else return 7;
			})
			.attr("width",function(d){return (element_width/2.5);})		
			.attr("height",function(d){return (element_height/2.5);});	
	}
	var out_focus_circle=function(element){
		d3.select(element).transition()
		  .duration(300)
		  .attr("class",function(d){if(d.explored==true){if(d.inbound==true)return "inbound_expanded";return "expanded"; }else{if(d.inbound==true)return "inbound_reduced"; return "reduced";}})
		  .attr("r", function(d){return relations_radius;});
	}
	var out_focus_text=function(element){
		d3.select(element).transition()
			  .duration(300)
			  .attr("y",-18);
	}
	function focusIn(element,first){
		if(first==true){//porto in primo piano il nodo e salvo i suoi figli.
			if(!$.browser.msie && !$.browser.opera)
			  element.insertAfter("#axrelations_graph_main_g g:last");
			var children=element.children();				
		}
		else var children=element[0].childNodes; //That is for the objects of type Object#<SVGGElement>
		//Call the focus.
		element=element[0].__data__;
		for(var i=0;i<children.length;i++){
			if(children[i].localName=="rect"){						
				focus_rect(children[i],first);					
			}
			else if(children[i].localName=="image"){
				if(children[i].getAttribute('class')!='node_remove')focus_img(children[i],first);
				else{//display the x
					if(first){children[i].setAttribute("display","inherit");focus_x(children[i]);}
				}
			}
			else if(children[i].localName=="circle"){
				focus_circle(children[i]);
			}
			else if(children[i].localName=="text" ){
				if(element.isRelation==true)focus_text(children[i]);
				if(element.isRelation==false && element_width*zoom.scale()<=120 && first==true){
					d3.select(children[i]).transition()
					  .duration(500)
					  .style("font-size",16/(zoom.scale())+"px");
				}
				
			}
		}
		if(first==true)//Propago il focus ai figli.
		{			
			for(var i=0;i<links.length;i++){
				if(links[i].source.type==element.type&&links[i].source.id==element.id){
					for(var j=0;j<node[0].length;j++){
						if(node[0][j].__data__.type==links[i].target.type&&node[0][j].__data__.id==links[i].target.id){
							var node_passed=Array();
							node_passed.push(node[0][j]);
							focusIn(node_passed,false);
							break;
						}
					}
				}
				else if(links[i].target.type==element.type&&links[i].target.id==element.id){
					for(var j=0;j<node[0].length;j++){
						if(node[0][j].__data__.type==links[i].source.type&&node[0][j].__data__.id==links[i].source.id){
							var node_passed=Array();
							node_passed.push(node[0][j]);
							focusIn(node_passed,false);
							break;
						}
					}
				}
			}						
		}
	}
	function focusOut(element,first){
		if(first==true)var children=element.children();				
		else var children=element[0].childNodes; //Questo per gli ogetti di tipo Object#<SVGGElement>
		//Chiamo i focus.
		element=element[0].__data__;
		for(var i=0;i<children.length;i++){
			if(children[i].localName=="rect"){						
				out_focus_rect(children[i]);					
			}
			else if(children[i].localName=="image"){
				if(children[i].getAttribute('class')!='node_remove')out_focus_img(children[i]);
				else{//Hiddens the x
					if(first){children[i].setAttribute("display","none");out_focus_x(children[i]);}
				}
			}
			else if(children[i].localName=="circle"){
				out_focus_circle(children[i]);
			}
			else if(children[i].localName=="text"){
				if(element.isRelation==true)out_focus_text(children[i]);
				if(element.isRelation==false && first==true){
					d3.select(children[i]).transition()
					  .duration(300)
					  .style("font-size","10px");
				}
			}
		}
		if(first==true)//Propago il focus ai figli.
		{							
			for(var i=0;i<links.length;i++){
				if(links[i].source.type==element.type&&links[i].source.id==element.id){
					for(var j=0;j<node[0].length;j++){
						if(node[0][j].__data__.type==links[i].target.type&&node[0][j].__data__.id==links[i].target.id){
							var node_passed=Array();
							node_passed.push(node[0][j]);
							focusOut(node_passed,false);
							break;
						}
					}
				}
				else if(links[i].target.type==element.type&&links[i].target.id==element.id){
					for(var j=0;j<node[0].length;j++){
						if(node[0][j].__data__.type==links[i].source.type&&node[0][j].__data__.id==links[i].source.id){
							var node_passed=Array();
							node_passed.push(node[0][j]);
							focusOut(node_passed,false);
							break;
						}
					}
				}
			}						
		}
	}
	//------------------------------<END FOCUS>------------------------------------------
	//Fix of drag for d3.v3. See: http://stackoverflow.com/questions/17953106/why-does-d3-js-v3-break-my-force-graph-when-implementing-zooming-when-v2-doesnt
	var drag = force.drag()
		.origin(function(d) { return d; })
		.on("dragstart", dragstarted)
		.on("drag", dragged)
		.on("dragend", dragended);
	function dragstarted(d) {
		d3.event.sourceEvent.stopPropagation();
	}

	function dragged(d) {
		d3.select(this).attr("cx", d.x = d3.event.x).attr("cy", d.y = d3.event.y);
	}

	function dragended(d) {
	}
	
	//Appends the nodes.
	var nodeEnter = node.enter().append("svg:g")
		.attr("class", "node")
		// .attr("class", function(d){if(d.isRelation)return"node circle";else return"node rect";})
		// .on("mouseout", function(){
		  // focusOut($(this),true);
		// })
		// .on("mousedown",function(d){
		  // d.fixed=true;
		// })
		// .on ("mouseover", function(){
		  // focusIn($(this),true);
		// })
		// .on("mouseup",function(e){open_navigation_panel(e,$(this))})
		.attr("opacity",function(d){
			if(d.present==true){ //Mi serve per il caso del reduce per far vedere tutti i nodi subito.
				d.present=false;
				return 1;
			}
			return 1e-6;
		})
		.each(appear())
		.call(drag);
		// .call(force.drag);
	if($("body.Android").length>0 || $("body.iPad").length>0 ){
		nodeEnter
		.on("click",function(e){
			if (d3.event.defaultPrevented) return;
			// d3.event.sourceEvent.stopPropagation();
			open_navigation_panel(e,$(this));
			//Hiddens all the 'remove_node' buttons.
			d3.selectAll(".node_remove").attr("display","none");
			//Shows the 'remove_node' button.
			
			var children=d3.event.currentTarget.children;
			for(var i=0;i<children.length;i++){
				if(children[i].localName=="image"){
					if(children[i].getAttribute('class')=='node_remove')children[i].setAttribute("display","initial");
				}
			}
			
		})
		.on("touchend",function(d){
				if (d3.event.defaultPrevented) return;
				// open_navigation_panel(d,$(this));
				d.fixed=true;
			});
	}
	else{
		nodeEnter.on("click",function(e){
			if (d3.event.defaultPrevented) return;
			// d3.event.sourceEvent.stopPropagation();
			open_navigation_panel(e,$(this));
		})
		.on("mousedown",function(d){
			d.fixed=true;
		})
		.on("contextmenu",function(e){open_navigation_panel(e)});
		if($("body.firefox").length>0){
			nodeEnter.on ("mouseover", function(){
				focusIn($(this),true);
			})
			.on ("mouseout", function(){
				focusOut($(this),true);
			});
		}
		else{
			nodeEnter.on ("mouseenter", function(){
				focusIn($(this),true);
			})
			.on ("mouseleave", function(){
				focusOut($(this),true);
			});
		}
	}
	  
	nodeEnter.append("title").text(function(d){
		if(d.isRelation==true)return d.uri+" 'of "+d.father+"'";
		if(d.type=='more' || d.type=='more_LD')return d.name;
		if(d.name!=d.id) return d.name+" \n "+d.id;
		return d.id;
	});
	
	nodeEnter.append("circle")
		.attr("id",function(d){if(d.isRelation==true)return "id"+d.id;})
		.attr("class",function(d){if(d.isRelation==true){if(d.explored==true){if(d.inbound==true)return "inbound_expanded focus";return "expanded";} else{if(d.inbound==true)return "inbound_reduced focus"; return "reduced";}}})
		.attr("cx", function(d){
			return 0;
		}) //Imposta l'ascissa del centro del cercio.
		.attr("cy", 0)
		.attr("stroke","#666666")
		.attr("stroke-width","1.5px")
		.attr("display",function(d){
			if(d.isRelation==true)return null;
			return "none";
		})
		.attr("fill",function(d){
			// return color_scale(d.id);
			return "#969696";
		})
		// .style("opacity",0.5)
		.attr("r", function(d){
			return relations_radius;
		}); //raggio del cerchio.
		
	nodeEnter.append("svg:rect")
	// .style("opacity",0.5)
	.attr("rx", 6)	//Smussamento degli angoli del rettangolo.
	.attr("ry", 6)
	.attr("stroke","#767676")
	.attr("stroke-width","1.5px")
	.attr("x",function(d){return -(element_width+10)/2;
	})
	.attr("y",function(d){return -(element_height+10)/2;
	})
	.attr("width",function(d){return (element_width+10);
	})		
	.attr("height",function(d){return (element_height+10);
	})
	.attr("display",function(d){
			if(d.isRelation==false)return null;
			return "none";
		})
		
	.attr("fill",function(d){
			// return color_scale(d.id);
			return "#c7c7c7";});
	

	
	nodeEnter.append("svg:image")
	.attr("id",function(d){if(d.isRelation==true)return "id"+d.id;})
	  .attr("class", function(d){
		if(d.isRelation==true){
			if(d.explored==true){return "expanded";}
			else {return "reduced";}
		}
		if(d.img=="")return "image_not_found";
		return "image"
	  })
	  .attr("xlink:href",function(d){
		return d.img;
	  })
	  .attr("x", function(d){return -(element_width+10)/2+5;})
	  .attr("y",function(d){return -(element_height+10)/2+5;})
	  .attr("width",function(d){return (element_width);})		
	  .attr("height",function(d){return (element_height);});
	//Creates the Label for the node.
	nodeEnter.append("svg:text")
		.attr("class", "nodetext")
		.attr("x", function(d){
			if(d.isRelation==false) return -(element_width+10)/2;
			else return -(relations_radius);
		})
		.attr("dy", "-0.3em")
		.attr("y",function(d){
			if(d.isRelation==false) return -(element_height+10)/2;
			else return -(relations_radius);
		})
		.text(function(d) {		  
			var label=d.name
			if(label!=null && label.length>=15) label=label.slice(0,15)+"...";
			return label; 
		});
	//Creates the close icon for the node.
	nodeEnter.append("svg:image")
		.attr("class","node_remove")
		.attr("display","none")
		.attr("x", function(d){
			if(!d.isRelation)return 13;
			else return 7;
		})
		.attr("dy", "-0.3em")
		.attr("y",function(d){
			if(!d.isRelation)return -element_height+13;
			else return -element_height+13;
		})
		.attr("width",function(d){return (element_width/2.5);})		
		.attr("height",function(d){return (element_height/2.5);})
		.attr("xlink:href",function(d){
			return buttons[15];//X icon
		})
		.on("click",function(d){//removes the single node from the visualization
			d3.event.stopPropagation(); 
			axrelations_save_status("node_remove",d);//Saves like a focus.
			element_elimination_allway(d);//Removes the links that connects the target node.
			delete d.root; //Deletes the attribute 'root'
			axrelations_graph_finder();//Removes the nodes connected with the target.
			restart();
		});
	//Add images to relations buttons.
	//---TOCHECK: Is that real work? It's work on the first call.=> It works in some cases, like only in the initialization... Before remove check
	d3.selectAll("#axrelations_graph_main_g image.minus").attr('xlink:href',buttons[12]);
	d3.selectAll("#axrelations_graph_main_g image.plus").attr('xlink:href',buttons[11]);
	//---
	d3.selectAll("#axrelations_graph_main_g image.expanded").attr('xlink:href',buttons[12]);//Sets the icons for the image, based on the class value.
	d3.selectAll("#axrelations_graph_main_g image.reduced").attr('xlink:href',buttons[11]);
	
	// $("#axrelations_graph_main_g image.minus")
	node.exit().remove();
	force.start();

	var rep="<Text id='graph_report' class='ax_descriptions'>Shown:"+nodes.length+"  </br>&nbsp;&nbsp; Entities:"+nodes.filter(function(d){return !d.isRelation}).length+" </br> &nbsp;&nbsp; Relations:"+nodes.filter(function(d){return d.isRelation}).length+"  </Text>";//Sets the reports of the graph.
	$("#graph_report").remove();
	// $("#axrelations_graph_container").prepend(rep);
	$("#axrelations_options_topleft").append(rep);
	
	// for (var i = 0; i < force.nodes.length; ++i) force.tick(); //per debuggare le distanze
	// force.stop();
}//end of restart().

function appear() {//Function for make appear the nodes with a fadeIn effect
	return function(d, i, j) {
	  d.pending = 1;     // add 'pending' member to each enter()ed node object
	  t += 50; //Delay. Before 14-8-26 t=150
		d3.select(this).transition()
		  .duration(150)
		  .delay(t - Date.now())
		  .attr("opacity", 1)
		  .each("end", function(d, idx) {
			 d.pending = 0;

			 // select the links for this node:
			 var neighbors;
			 var i, j, n = nodes.length, m = links.length;
			 // we know that the FORCE LAYOUT adds the field 'index' to each node.
			 // We use that 'internals' knowledge here to match links with node d.
			 link.each(function(lnk, a, b) {
			   // only show those links which have both endpoints (nodes) already showing.
			   //
			   // Note: this condition is sufficient to ensure all links are shown
			   //       once all the nodes are shown.
			   if ((lnk.source == d || lnk.target == d) && !lnk.source.pending && !lnk.target.pending) {
				 //lnk.pending = 0;
				 d3.select(this).transition()
					 .duration(50)
					 .attr("opacity", 1);
			   }
			 });
		  });
	};
}

function step1(x,y,EP) {//First step for the visualization. It creates a node root and search the relations. (x,y) is the position where place the new root. EP is the EndPoint of this search. 
	if(!EP)EP=endpoint;//Sets the actual EndPoint
	EPindex=endpoints_list.indexOf(EP);
	if(EPindex==-1){//Checks if the endpoint is present in the list of EndPoint for the graph. If not, adds it.
		endpoints_list.push(EP);//add the EndPoint to the list for the graph.
		EPindex=endpoints_list.length-1;//Saves the index of this EndPoint for make the query.
	}
	var root = json_file;
	if(root['warning']){alert(root['warning']); delete root['warning'];}//Prints a warning message if the number of element is less than the number on the endpoint.
	root["isRelation"]=false;
	root["explored"]=true;
	root["root"]=true;
	root["EP"]=Array();//Defines an array of index of EndPoint for the node. 
	root["EP"].push(EPindex); //Save the index of endpoint to the list of endpoint for the node.
	
	nodes.push(root);
	var toggle=d3.select("h2.axrelations_on");
	if(!x && !y && toggle[0][0]){
	  root.x=$("#axrelations_graph_container").width()/2; //Places the root at the centre of the screen.
	  root.y=$("#axrelations_graph_container").height()/2;
	}
	else if(x && y){
		root.x=x; //Places the root at a given position.
		root.y=y;
	}
	else{
		root.x=300; 
		root.y=276;
	}
	root.fixed=true; //Fixes the node.
	for(var r in json_file.relations){//Adds the relations
		var relation=json_file.relations[r];		
		relation["isRelation"]=true;
		relation["explored"]=true;
		relation["id"]=count;
		relation["uri"]=relation["uri"]; 
		relation["father"]=root.name;
		relation["type"]=relation["name"];
		relation["EP"]=EPindex; //Specifies the EndPoint of the property. A relation has only one EP.
		
		relation.x=root.x;
		relation.y=root.y;	
		count++;
		if(relation.elements.length!=0 && to_display(relation)["check"]==true && to_display_inverse(relation)){//If it's not empty and its checkbutton is selected, it's displayed.
			nodes.push(relation);
			links.push({source: root, target: relation});
			if(to_display(relation)["class"]!="closed") explores_relationship(relation,false,EPindex);
			else relation["explored"]=false;
		}
	}
	// relation_differentiate(json_file.relations);//differentiates duplicated names.
	restart();
	node_close_all(root.relations);//Closes all the relations.
	/* MULTIPLE ENDPOINT Search. Checks for result for this object in other EP. This part is like explores_elements
	Adds other EP where search if multienspoints are expected.*/
	if(active_multiple_endpoints===null){
		//TODO--------------Waits until the end of the loading of the configuration.
		wait_button_load(0,null,false);
	}
	if(active_multiple_endpoints){
		node_to_explore=root;
		var EP_to_add
		EP_to_add=axr_EP_for_node(node_to_explore.id);
		//Checks if the elements in EP_to_add are already present in endpoints_list and in node_to_explore.EP, else adds that to each one.
		var list_index;
		if(EP_to_add){
			list_index=axr_add_EPs(EP_to_add);
			if(node_to_explore.EP){
				EndPoints=node_to_explore.EP.concat(list_index);
				EndPoints=axr_unique(EndPoints);
				//Removes the current EP, because the search is just finished.
				EndPoints.shift(1);			
			}
		}
		else return;
		//Adds the loading gif on the node.
		var loading_gif=axr_add_loading_gif(node_to_explore,EndPoints);
		for(var p=0;p<EndPoints.length;p++){//Makes a search for all the EP of the node to explore.
			EP=endpoints_list[EndPoints[p]];				
			//Prepares the url for the request.
			var request_url="request.php?"+node_to_explore.type+"="+encodeURIComponent(node_to_explore.id)+"&sparql="+EP;
			if(active_multiple_endpoints){
				if( EndPoints[p]!=-1 && node_to_explore.type=="LD" )request_url="request.php?uri="+encodeURIComponent(node_to_explore.id)+"&sparql="+EP;
			}
			/* Gets the informations for the node. */
			d3.json(request_url, function(json) { 
				axr_add_relations( node_to_explore , json , false, false, loading_gif);
			});		
		}
	}
}
//Choose if a relation must be show or no, dependent from the checkbuttons in axrelations_graph_menu_bottom.
function to_display(relation){
	var relations=$("#axrelations_graph_menu_bottom li input");
	var rel=new Object;
	rel["class"]="open";
	rel['check']=true;
	var position=relations.length;//the position where place the new relation, if it's not present.
	for(var i=0;i<relations.length;i++){
		if(relations[i].value==relation.uri && relations[i].nextSibling.textContent==relation.name){//the second parameter is needed for differentiate direct from inverse relation.
			if(relations[i].id=="closed") rel['class']="closed";
			if(relations[i].checked!=true) rel['check']=false;
			return rel;
		}
		else if(position==relations.length && $("input[value='"+relations[i].value +"']").parent().text() > relation.name)position=i; //for insert in litteral order the new relations.
	}
	//if the code pass through here, the relation must be included.
	button='<li class="relation_' + (relations.length) + '"><input type="checkbox" checked="checked" value="' + relation.uri + '" title="' + relation.uri + '">' + relation.name + '</li>';				
	
	// if(position==0) $("input[value='"+relations[position].value +"']").parent().before(button);//Puts the element in order with the others.
	if(position==0) $("#axrelations_type").prepend(button);//Puts the element in order with the others.
	else $("li."+relations[position-1].parentNode.className+"").after(button);
	//add the click event.
	var relations=$("#axrelations_graph_menu_bottom input[value='"+relation.uri +"']");
	for(var i=0;i<relations.length;i++){
		if(relations[i].nextSibling.textContent==relation.name)relations[i].onchange=(function(r){add_remove_relations(this,this.checked,true);});
	}
	return rel;	//if it's not present.		
}
function explores_relationship(relation_to_explore,save_status,EPindex) {
/*	Explore a relation. The parameters are: 
relation_to_explore: the relation and its nodes to attach to the graph.
save_status: A flag that is used to decide if save or not save the current configurations. 
EPindex: the index (relative to the position in endpoints_list) of the current EndPoint of the relation. 
*/
	if(!EPindex)EPindex=endpoints_list.indexOf(endpoint);//Defines the EndPoint index for the nodes. If no EPindex is specified it sets the first in the list as default.
	if(save_status==true){	//Saves the operation on stack
		axrelations_save_status("explore",relation_to_explore);//Calls the function that save.
	}
	var	rejects_count=0;//Counts the number of links that weren't added.
	for(var i=0;i<relation_to_explore.elements.length;i++){
		var element=relation_to_explore.elements[i];
		//Checks if the element is already present.
		var is_present=false;
		for(var j=0;j<nodes.length;j++){
			if(!nodes[j].isRelation ){
				//Checks if the node is already present. Checks if there is one with the same id, and not with the same type, because LD and uri are the same things.
				if(nodes[j].id==element.id) { 
					if(nodes[j].index && element.index ) {
						if(nodes[j].index == element.index){console.log("Elemento visualizzato: "+element.id);is_present=true;break;}// This is for the cases of add new elements to a node already explored(Case MultiEP).
					}
					if(nodes[j].EP.indexOf(EPindex)==-1) nodes[j].EP.push(EPindex);//If the node is reached by another with a different EndPoint, adds the EndPoint index in the list of the node.
					var existent_link=links.filter(function(d){
						if(d.target.id==nodes[j].id && d.source.id==relation_to_explore.id) return d;
					});//Checks if there is already a link between the two nodes. This is for the case of multi EndPoint.
					// var duplicated_link=links.filter(function(d){
						// if(d.target.id==nodes[j].id && d.source.uri==relation_to_explore.uri && d.source.father==relation_to_explore.father) return d;
					// });//Checks if there is already a link between the two nodes. This is for the case of duplication. This control is necessary for hide all. TOCHECK is  '.father' a sufficient condition?
					if(existent_link.length==0 && axrelations_search_node_duplication({source: relation_to_explore, target: nodes[j], present:true}))links.push({source: relation_to_explore, target: nodes[j], present:true});
					else rejects_count++;
					
					is_present=true;
					break;
				}
			}
		}
		if(is_present==false){//if the node isn't present in the graph, adds the node to the visualization.
			element["isRelation"]=false;
			element["explored"]=false;
			if(!element["EP"]){//if the list of EPs for the node is empty, adds the current EP.
				element["EP"]=Array();//Defines the node EndPoint.
				element["EP"].push(EPindex);//pushes the index of the EndPoint in the list for the node.
			}
			// else{//Case where the element is passed with already the EPs. Adds the EP that aren't present.
				// var Eps=Array();
				// for(var ep in element["EP"]){
					// if(typeof element["EP"][ep] == "string"){
						// if(endpoints_list.indexOf(element["EP"][ep])!=-1) Eps.push(endpoints_list.indexOf(element["EP"][ep]));
						// else{//If the EP is not present, adds that to the list. 
							// endpoints_list.push(element["EP"][ep]);
							// Eps.push(endpoints_list.indexOf(element["EP"][ep]));
						// }
					// }
				// }
				// if(Eps.length!=0) element["EP"]=Eps;
			// }
			if(!element.relations){
				element["relations"]=null;
			}
			if(element.type=="more" || element.type=="more_LD"){ //If it's the 'more' node, gives to that node an id for distinguish.
				element.id=count;
				count++;				
			}
			if( !element.x && !element.y ){//If the nodes haven't a position, it places them in the same position of the explored relation.
				element.x=relation_to_explore.x;
				element.y=relation_to_explore.y;
				// element.fixed=true;
			}
			nodes.push(element);
			links.push({source: relation_to_explore, target: element});
		}			
	}
	/* 	Deletes the current relation from the visualizzation if no elements are displayed(Probably for duplication). 	*/
	if(rejects_count==relation_to_explore.elements.length){
		var link_to_delete=links.filter(function(d){
			return (d.target.id==relation_to_explore.id && d.target.uri==relation_to_explore.uri && d.target.isRelation);
		});
		var index_to_delete=links.indexOf(link_to_delete[0]);
		links.splice(index_to_delete,1);
		axrelations_graph_finder();
	}
}
function open_navigation_panel(e,current) {//Opens the navigation panel with the functions of the tool.
	var node_to_explore=e;
	
	if(node_to_explore.type=="more" || node_to_explore.type=="more_LD"){//Opens the navigation panel for the more button.
		d3.select("#axrelations_graph_navigation_panel").transition()
		  .duration(500)
		  .style("display","none");
		d3.select("#axrelations_graph_navigation_more").transition()
			.duration(500)
			.style("display","inline");
		$("#axrelations_graph_navigation_more_explore").unbind('click').click(function(){//Specifies another trigger.
			explore(node_to_explore,true);
		});
		$("#axrelations_graph_navigation_more_search").unbind('click').click(function(){//Specifies another trigger.
			axrelations_more_panels(node_to_explore,0);			
		});
		return;
	}
	else{
		d3.select("#axrelations_graph_navigation_more").transition()
		  .duration(500)
		  .style("display","none");
	}
	d3.select("#axrelations_graph_navigation_panel").transition()
	  .duration(500)
	  .style("display","inline");
	// if(node_to_explore.isRelation==true){ //For a relations doesn't show the menu, explore immediately.
	if(node_to_explore.isRelation==true){ //For a relations doesn't show the menu, explore immediately.
		// Changes the color for the node_relation during the focus.
		if(node_to_explore.isRelation==true){//TOCHECK is necessary with the explorations with multiple step
			var children_circle=current.children("circle");
			var children_image=current.children("image");
			d3.select(children_circle[0]).transition()
			  .attr("class",function(d){
			  if(d.explored==true){if(d.inbound==true)return "inbound_reduced focus";return "reduced focus"; }else{if(d.inbound==true)return "inbound_expanded focus"; return "expanded focus"};
			});
			d3.select(children_image[0]).attr("class",function(d){
			  if(d.explored==true){return "reduced"; }else{return "expanded"};
			});
		}
		explore(node_to_explore,true);
	}
	else{
		axrelations_NavigationP_render(node_to_explore,true);
	}
	$("#axrelations_graph_navigation_panel_info").unbind('click').click(//specifico un altro trigger
		function(){
			var position="#axrelations_graph_div";//Defines a place for the literals.
			var count=num_info_dialog;
			num_info_dialog++;
			var info_div="<div id='axrelations_info' class='draggable info_number_"+count+" axrelations_info_view' value='"+node_to_explore.id+"' ><div id='axrelations_info_title' >"+node_to_explore.name+"<a id='axr_info_close'>Close</a></div>";
			info_div+="<div id='axrelations_info_main_div'>";
			info_div+="<p class='property_name'>Identifier:</p>"+node_to_explore.id;
			info_div+="<p class='property_name'>Image:</p><a href="+node_to_explore.img+" target='_blank'><img class='info_image' src="+node_to_explore.img+"></a>";
			info_div+="<p class='property_name'>Info:</p>";
			info_div+="<ul id='axrelations_list'></ul>";
			var EP=endpoints_list[node_to_explore.EP[0]];//Searchs the info in the first endpoint. XXXXXXXXXXXX Make it for all the endpoint
			if(!EP)EP=endpoint;
			info_div+="<p class='property_name'>Sparql Query:</p>ENDPOINT:</br> "+EP+" </br></br>QUERY:</br> SELECT ?subject ?property ?object </br> WHERE{{ &lt"+node_to_explore.id+"&gt ?property ?object } UNION { ?subject ?property &lt"+node_to_explore.id+"&gt } }";
			info_div+="</div></div>";
			$(position).append(info_div);
			$(".draggable").draggable({ handle: "#axrelations_info_title" });
			$(".draggable").resizable({minHeight: 400, minWidth:360});
			$(".info_number_"+count+" #axr_info_close").click(function (e)
			{
				$(".info_number_"+count).fadeOut(300);
				$(".info_number_"+count).remove();
			});
			var loading_gif="<img id='axrelations_info_loading_gif' src="+buttons[3] +">";
			$(".info_number_"+count+" ul").append(loading_gif);
			d3.json("request.php?literals="+encodeURIComponent(node_to_explore.id)+"&sparql="+EP,function(json){	// Downloads the list of relations.
				axrelations_display_literals(json,node_to_explore,count);
			});
		}
    );
	$("#axrelations_graph_navigation_panel_focus").unbind('click').click(function(){//specifico un altro trigger	  
		// if(stack_lifo.length==0){ //actives the button
			// d3.select("#axrelations_undo").attr("disabled",null)
			// .attr("src",buttons[0]);
		// }
		axrelations_save_status("focus",node_to_explore);
		// var operation=new Object;
		// operation.type="focus";
		// operation.node=new Object;
		// for(var p in node_to_explore){ //duplicates the attributes of the current element
			// operation.node[p]=node_to_explore[p];
		// }
		// operation.nodes=Array();
		// for(var i=0;i<nodes.length;i++)operation.nodes[i]=nodes[i];
		// operation.links=Array();
		// for(var i=0;i<links.length;i++)operation.links[i]=links[i];
		// //Saves the status messages.
		// operation.status=get_status_options();
		// if(stack_lifo.length<50) stack_lifo.push(operation);
		// else{
			// stack_lifo.splice(0,1);
			// stack_lifo.push(operation);
		// }
		//Deletes all the nodes and all the edges displayed and not.
		nodes.splice(0,nodes.length);
		links.splice(0,links.length);
		// node=new Array;				
		node_to_explore.x=w/2; //Specify the position of the new root at the center of the window.
		node_to_explore.y=h/2;
		node_to_explore.px=w/2;
		node_to_explore.py=h/2;
		node_to_explore.fixed=true;//Fixes the node at the centre.
		node_to_explore.root=true; //It's the new root.
		nodes.push(node_to_explore);
		node_to_explore.explored=false;
		explore(node_to_explore,false);
		zoom.translate([0,0]).scale(1);
		vis.attr("transform", "translate(0,0)"+" scale(1)"); //Riposiziono al centro la visualizzazione senza zoom.
		//Deletes the status, except those related to the nodes shown.
		roots=nodes.filter(function(d){return d.root==true;});
		if(roots.length!=0)
		{
			var Roots_present=Array();
			for(var i=0;i<roots.length;i++){
				Roots_present[i]=roots[i].id;
			}
			var Op=$(".uri_added option");
			Op.filter(function(){
				if(jQuery.inArray(this.value,Roots_present)==-1){return this;}
			}).remove();
		}
		else $(".uri_added option").remove();
		$("#remove_status").attr("disabled",'disabled');//Turns off the 'remove' button.
		restart();
		var options=Array();
		var option=Object();//Creates the option to append to the status panel.
		if($(".uri_added [value='"+node_to_explore.id +"']").length==0){
			option.uriname=node_to_explore.name;
			option.endpointname=endpoints_list[node_to_explore.EP[0]];
			option.uri=node_to_explore.id;
			option.endpoint=endpoints_list[node_to_explore.EP[0]];
			options.push(option);
			add_status_options(false,options);
		}
	});	
	$("#axrelations_graph_navigation_panel_open").unbind('click').click(function(){//Specifies another trigger.
		var href;
		if(node_to_explore.axoid){
		  href="?q=home&axoid="+node_to_explore.axoid;
		  if(full_screen==true){
			$("#axrelations_graph_div").insertAfter("#axrelations_graph_toggle");
			$("#overlay").show();
			$("#axrelations_graph_svg").height($("#axrelations_graph_container").height());
			$("#axrelations_graph_svg").width($("#axrelations_graph_container").width());
			$("#axrelations_fullscreen").fadeOut(300);
			$("#overlay").hide();
			full_screen=false;
		  }
		  AjaxTabsPlayer.loadPlayerX(href);
		}
		else{//Adds alert window
		  function HideDialogPage()
		  {
			$("#overlay").hide();
			$("#axrelations_change_page").fadeOut(300);
			$("#axrelations_change_page").remove();
		  }
		  function ShowDialogPage(modal)
		  {
			$("#overlay").show();
			$("#axrelations_change_page").fadeIn(300);
			if (modal)$("#overlay").unbind("click");
			else $("#overlay").click(function (e){
			  HideDialogPage();
			});
		  }
		  if(node_to_explore.type=="uri" || node_to_explore.type=="LD") href=node_to_explore.id; //The link to the external page.
		  var popup='<div id="axrelations_change_page"><table id="axrelations_help_table" style="width: 100%; border: 0px;" cellpadding="3" cellspacing="0"><tr><td class="help_dialog_title">'+sg_title+' ALERT!</td><td class="help_dialog_title align_right"><a id="axr_ch_pg_btnClose">Close</a></td></tr><tr><td>&nbsp;</td><td>&nbsp;</td></tr>';
		  popup+='<tr><td colspan="2" style="padding-left: 15px;">Opening this page close the '+sg_title+'!</td></tr>';
		  popup+='<tr><td>&nbsp;</td><td>&nbsp;</td><tr><td>';
		  popup+='<a id="axr_ch_page_ok" href="'+href+'"  target="_blank" > <button  style="margin-left:35%; height:35px; width:100px;">Accept </button></a>';
		  popup+='</td><td><input id="axr_ch_page_no" type="button" value=" Cancel " style="margin-right:35%; height:35px; width:100px;"></td></tr>';
		  popup+='<tr><td>&nbsp;</td><td>&nbsp;</td>';
		  popup+='</table></div>';
		  $("html").append(popup);
		  ShowDialogPage(false);
		  $("#axr_ch_page_ok").click(function(){
			
				HideDialogPage();
			
			$("#axrelations_change_page").remove();
		  });
		  $("#axr_ch_page_no").click(function(){
			HideDialogPage();
		  });
		  $("#axr_ch_pg_btnClose").click(function(){
			HideDialogPage();
		  });
		}
	});
	$("#axrelations_graph_navigation_panel_share").unbind('click').click(function(){
		//Specifies the trigger for the share button of a node.
		axrelations_share(node_to_explore);
	});
    $("#axrelations_graph_navigation_panel_search").unbind('click').click(function () {//Specifies another trigger.
		$(".axrelations_search").remove();
		var position = "#axrelations_graph_div";//Place for the literals.
		var info_div = "<div id='axrelations_info' class='draggable axrelations_search' value='" + node_to_explore.id + "' ><div id='axrelations_info_title' >" + node_to_explore.name + "<a id='axr_srch_close'>Close</a></div>";
		info_div += "<div id='axrelations_search_main_div'>";
		//info_div += "<ul id='axrelations_list'></ul>";
		var options = "";
		d3.json("config.php?search=yes", function (json) {
			//console.log(json);
			$.each(json, function (i, d) {
				options += "<option value='" + d.endpoint + "' id='search_element" + i + "' >" + d.name + "</option>";
			});
			info_div += "<p id='specification'>Select up to 10 endpoints on which to search ( Ctrl-click to select multiple options )</p>";
			info_div += "<select id='search_list' multiple='multiple' size ='15'>" + options + "</select>";
			info_div += "<textarea name='display' id='display' placeholder='view selected option(s)' cols='60' rows='17' readonly></textarea>";
			info_div += "<p id='counter_container'><span>Number of element selected: </span><span id='counter'></span><span>/10</span></p>";
			info_div += "<button id='search_button'>Cerca</button>";
			info_div += "</div></div>";
			$(position).append(info_div);
			$(".draggable").draggable({handle: "#axrelations_info_title"});
			$(".draggable").resizable({minHeight: 400, minWidth: 360});
			$("#axr_srch_close").click(function (e) {
				$(".axrelations_search").fadeOut(300);
				$(".axrelations_search").remove();
			});

			var last_valid_selection = null;
			var last_valid_selection_string = localStorage.getItem("list_selected");
			if (last_valid_selection_string) {
				last_valid_selection = JSON.parse(last_valid_selection_string);
				$('#search_list').val(last_valid_selection);
			}

			$('#search_list').change(function (e) {
				//console.log("change")
				if ($(this).val().length > 10) {
					$(this).val(last_valid_selection);
				} else {
					// get reference to display textarea
					var display = document.getElementById('display');
					//console.log(display);
					display.innerHTML = ""; // reset
					var counter = document.getElementById('counter');
					counter.innerHTML = ""; // reset
					counter.innerHTML = " " + $(this).val().length;
					// callback fn handles selected options
					getSelectedOptions(this, update_textarea);
					last_valid_selection = $(this).val();
					//console.log(last_valid_selection);
				}
			});

			$('#search_button').bind("click", function () {
				//take the value selected in the selected section
				var sel = document.getElementById('search_list');
				//array of sparql url selected
				var list_sparql = [];
				for (var i = 0, len = sel.options.length; i < len; i++) {
					var opt3 = sel.options[i];
					// check if selected
					if (opt3.selected) {
						// add to array of option elements to return from this function
						list_sparql.push(opt3.value);
					}
				}
				console.log(last_valid_selection)
				localStorage.setItem("list_selected", JSON.stringify(last_valid_selection));
				console.log(JSON.parse(localStorage.getItem("list_selected")))

				//console.log(list_sparql)

				//update the view
				$("#axrelations_info").remove();
				console.log("click search")
				var info_div = "<div id='axrelations_info' class='draggable axrelations_search' value='" + node_to_explore.id + "' ><div id='axrelations_info_title' >" + node_to_explore.name + "<a id='axr_srch_close'>Close</a></div>";
				info_div += "<div id='axrelations_search_main_div'>";
				info_div += "<ul id='axrelations_list'></ul>";
				info_div += "</div></div>";
				$(position).append(info_div);
				$(".draggable").draggable({handle: "#axrelations_info_title"});
				$(".draggable").resizable({minHeight: 400, minWidth: 360});
				$("#axr_srch_close").click(function (e) {
					$(".axrelations_search").fadeOut(300);
					$(".axrelations_search").remove();
				});
				var loading_gif = "<img id='axrelations_info_loading_gif' src=" + buttons[3] + ">";
				$(".axrelations_search ul").append(loading_gif);

				var EP = endpoints_list[node_to_explore.EP[0]];//Makes the search on the first EP.
				if (!EP)
					EP = endpoint;
                //make the ajax request for the data
				d3.json("request.php?query_search=" + encodeURIComponent(node_to_explore.id) + "&sparql=" + EP + "&sparql_list=" + JSON.stringify(list_sparql), function (json) {// Downloads the list of query.
					$(".axrelations_search #axrelations_info_loading_gif").remove();
					if (!json)
						return;
					var output = "";
					var part1 = "";//The results of the query for the current site.
					var part2 = "";//The result for the other sites.
					for (var j in json) {
						if (json[j].endpoint == EP) {
							var button = "<button value='" + json[j].endpoint + "' node='" + node_to_explore.id + "' onclick=\"axrelations_query_view(event,0," + json[j].count_out + ",'" + json[j].endpoint_name + "')\" title='View the result of the query'>View</button>"
							part1 += "<li value='" + json[j].endpoint + "'><p><b>inside outbound</b> " + json[j].endpoint + " <span class='prop_right'>" + json[j].count_out + " <b> results</b>: " + button + "</span>";
							part1 += "</p></li>";
							button = "<button value='" + json[j].endpoint + "' node='" + node_to_explore.id + "' onclick=\"axrelations_query_view(event,1," + json[j].count_in + ",'" + json[j].endpoint_name + "')\" title='View the result of the query'>View</button>"
							part1 += "<li value='" + json[j].endpoint + "'><p><b>inside inbound</b> " + json[j].endpoint + " <span class='prop_right'>" + json[j].count_in + " <b> results</b>: " + button + "</span>";
							part1 += "</p></li>";
							part1 += "<hr>";
						} else {
							var button = "<button value='" + json[j].endpoint + "' node='" + node_to_explore.id + "' onclick=\"axrelations_query_view(event,1," + json[j].count + ",'" + json[j].endpoint_name + "')\" title='View the result of the query'>View</button>"
							part2 += "<li value='" + json[j].endpoint + "'><p><b>endpoint inbound</b> " + json[j].endpoint + " <span class='prop_right'>" + json[j].count + " <b> results</b>: " + button + "</span>";
							part2 += "</p></li>";
						}

					}
					output = part1 + part2;
					$(".axrelations_search ul").append(output);
				});
			});
		});
	});
	//Enables or Disables the buttons in the navigation pannel.
	if(node_to_explore.isRelation!=true && (node_to_explore.type!="more"|| node_to_explore.type!="more_LD")){
		$("#axrelations_graph_navigation_panel_focus").attr("disabled",null);
		$("#axrelations_graph_navigation_panel_open").attr("disabled",null);
	}
	else{
		$("#axrelations_graph_navigation_panel_focus").attr("disabled","disabled");
		$("#axrelations_graph_navigation_panel_open").attr("disabled","disabled");
	}
	if(node_to_explore.type=="uri"||node_to_explore.type=="LD" )$("#axrelations_graph_navigation_panel_open").attr("disabled",null);//Shows the open button
	else{
		$("#axrelations_graph_navigation_panel_open").attr("disabled","disabled");
	}
}

function explore(node_to_explore,save_status,save_type,add_info,details){
	/*Explores or reduces the clicked node.Add_info is for only the case of exploration of a node add by search, 
	present and not explored. */
	if(details){ 
		/*If details are specified makes the specific requests. 
		'details' is used for differentiate the explorations in multiple step with the previous version.*/
		if(node_to_explore.isRelation && !node_to_explore.explored){ //If the node is a relation...
			explores_relationship(node_to_explore,save_status,node_to_explore.EP);
			node_to_explore.explored=true;restart();
			// axrelations_search_single_duplication()//TOCHECK does exist a faster way?
			return;		
		}
		d3.select("#axrelations_graph_navigation_panel").transition()//Removes the navigation panel.
			  .duration(500)
			  .style("display","none");
		d3.select("#axrelations_graph_navigation_more").transition()//Removes the navigation panel.
			  .duration(500)
			  .style("display","none");
		// $("#axrelations_graph_navigation_panel").delay().fadeOut("slow");	
		var graph_status=axrelations_node_relations_status(node_to_explore);//retrieves the current state of the relations.
		if(graph_status=='to_explore' && node_to_explore.explored==false && details=='more'){ //If the node wasn't already been explored.
			//If the node is an element, gets the properties from the server.
			explores_element(node_to_explore,save_status,save_type,add_info);			
			node_to_explore.explored=true;
			node_close_all(node_to_explore.relations);			
		}
		else if((graph_status=='all_closed'&&details=='less')||(node_to_explore.isRelation && node_to_explore.explored)){ //Deletes the subgraph with an algorithm of deep research over the link.
			//Prunes the branch.
			if(save_status)	
			{	//Saves the operation on stack
				axrelations_save_status("reduce",node_to_explore);
			}
			element_elimination(node_to_explore);
			node_to_explore.explored=false;
			axrelations_graph_finder();
		}
		else if((graph_status=='all_open'&&details=='less')||(graph_status=='mixed'&&details=='less')){
			if(save_status){
				axrelations_save_status("close_all",node_to_explore);
			}
			node_close_all(node_to_explore.relations);return;//Closes all the relations for the node.
		}
		else if((graph_status=='all_closed'&&details=='more')||(graph_status=='mixed'&&details=='more')){
			if(save_status){
				axrelations_save_status("open_all",node_to_explore);
			}
			node_open_all(node_to_explore);return;//Opens all the relations for the node.
		}
	}
	else{
		d3.select("#axrelations_graph_navigation_panel").transition()
		  .duration(500)
		  .style("display","none");
		d3.select("#axrelations_graph_navigation_more").transition()//Removes the navigation panel.
		  .duration(500)
		  .style("display","none");
		// $("#axrelations_graph_navigation_panel").delay().fadeOut("slow");	
		
		if(node_to_explore.explored==false){ //If the node wasn't already been explored.
			if(node_to_explore.isRelation==true){ //If the node is a relation
				explores_relationship(node_to_explore,save_status,node_to_explore.EP);
				restart();				
			}
			else{  //If the node is an element, gets the properties from the server.
				explores_element(node_to_explore,save_status,save_type,add_info);
			}
			node_to_explore.explored=true;			
		}
		//Prunes the branch.
		else{ //Deletes the subgraph with an algorithm of deep search over the link.
			if(save_status==true)	
			{	//Save the operation on stack
				if(stack_lifo.length==0){ //active the button
					d3.select("#axrelations_undo").attr("disabled",null)
					.attr("src",buttons[0]);
				}
				var operation=new Object;
				operation.type="reduce"
				operation.node=new Object;
				for(var p in node_to_explore){ //duplicates the element and all attributes
					operation.node[p]=node_to_explore[p];
				}
				operation.nodes=Array();
				for(var i=0;i<nodes.length;i++)operation.nodes[i]=nodes[i];
				operation.links=Array();
				for(var i=0;i<links.length;i++)operation.links[i]=links[i];
				if(stack_lifo.length<50) stack_lifo.push(operation);
				else{
					stack_lifo.splice(0,1);
					stack_lifo.push(operation);
				}				
			}
			element_elimination(node_to_explore);
			node_to_explore.explored=false;
			axrelations_graph_finder();
		}
	}
}
//____________________DELETION______________________________________
function element_elimination(source){//Defines the functions for deletion. This function deletes all the edges that have for source 'source'.
	for(var i=0;i<links.length;i++){
		if(links[i].source.index==source.index ){//Search the edges where 'source' is the source or the target.
			links.splice(i,1); //Deletes the edge.
			i=i-1;
		}
	}	
}
function element_elimination_allway(source){
	//Defines the functions for deletion. This function deletes all the edges that have for source and for target 'source'.
	for(var i=0;i<links.length;i++){
		if(links[i].source.index==source.index || links[i].target.index==source.index){//Search the edges where 'source' is the source or the target.
			links.splice(i,1); //Deletes the edge.
			i=i-1;
		}
	}	
}	
function axrelations_graph_finder(){//Function that completes the elimination. Removes all the isolated nodes with a breadth search.
	var stack_of_nodes=Array();
	for(var i=0;i<nodes.length;i++){
		if(nodes[i].root==true){
			nodes[i].reached=true;//Starts the labelling from the root. For more roots more pushes!
			stack_of_nodes.push(nodes[i]);		
		}
		else nodes[i].reached=false;
	}
	while(stack_of_nodes.length!=0){
		var node_reached=stack_of_nodes.pop();			
		for(var i=0;i<links.length;i++){
			if(links[i].source.index==node_reached.index && links[i].target.reached==false){
				links[i].target.reached=true;
				stack_of_nodes.push(links[i].target);
				// console.log("push con index di: "+links[i].target.name);
			}
		}
	}
	for(var i=0;i<nodes.length;i++){
		if(nodes[i].reached==false){//For the nodes not reached.
			for(var j=0;j<links.length;j++){
				if(nodes[i].index && links[j].source.index==nodes[i].index){//Deletes the edges.
					// console.log("Delete links from: "+nodes[i].name+" to: "+links[j].target.name);
					links.splice(j,1);
					j=j-1;
				}
			}
			nodes.splice(i,1);
			i=i-1;
		}
	}
	restart();
}
//____________________DELETION END_________________________________
function explores_element(node_to_explore,save_status,save_type,add_info) {//Element exploration			
	var relations_json;
	if(node_to_explore.relations==null || node_to_explore.single_search==true) { // If the relations aren't already been loaded.
		delete node_to_explore.single_search;//Removes the attribute single_search
		// MORE case.
		if(node_to_explore.type=="more" || node_to_explore.type=="more_LD") { //Passo i parametri al lato-server, elimino il nodo more e al suo posto aggiungo quelli scaricati.
			var loading_gif = axr_add_loading_gif(node_to_explore);//Adds loading gif.
			if(node_to_explore.EP)EP=endpoints_list[node_to_explore.EP]; //Defines the EndPoint where search the object's properties.
			else EP=endpoint; //Else, searches in the first EndPoint passed.
			//The request has 3 parameters: more->is the subject ; from->is the number of elements already showed ; function-> is the predicate.
			d3.json("request.php?"+node_to_explore.type+"="+encodeURIComponent(node_to_explore.source)+"&from="+node_to_explore.from+"&function="+encodeURIComponent(node_to_explore.function_to_call)+"&sparql="+EP+"&isInverse="+node_to_explore.isInverse, function(json) { 
				//Controllo per evitare problemi di sincornizzazione tra focus e explore.
				var found=false;
				for(var i=0;i<nodes.length;i++){//Checks if the node is present jet.
					if(nodes[i].index==node_to_explore.index){found=true;break;}
				}
				if(found==false){node_to_explore.explored=false;return;}
				//-----------
				var father;
				//Cerco la relazione a cui attaccarmi ed elimino il nodo fittizio.
				for(var i=0;i<links.length;i++){//controllo se c'� un altro nodo che pu� fargli da padre.
					if(links[i].target.id==node_to_explore.id&&links[i].target.type==node_to_explore.type){
						father=links[i].source;
						links.splice(i,1); //Deletes the edge.
						break;
					}
				}
				for(var i=0;i<nodes.length;i++){//Deletes the 'more' element.
					if(nodes[i].index==node_to_explore.index){
						nodes.splice(i,1);
						break;
					}
				}
				if(json==null){restart();return;}
				var elements= json;
				if(save_status){//Saves the operation on stack
					if(stack_lifo.length==0){//actives the button
						d3.select("#axrelations_undo").attr("disabled",null)
						.attr("src",buttons[0]);
					}
					var operation=new Object;
					operation.type="explore_more";
					operation.node=node_to_explore;			
				}
				operation.nodes=new Array();
				operation.links=new Array();
				for(var i=0;i<elements.length;i++){ //Adds the elements founded.
					var element=elements[i];
					//Checks if the element is present.
					var is_present=false;
					for(var j=0;j<nodes.length;j++){
						if(nodes[j].isRelation==false){
							//Per vedere se il nodo � gi� presente controllo se esiste uno con lo stesso id e tipo.
							if(nodes[j].id==element.id&&nodes[j].type==element.type){
								//axrelations_search_node_duplication Checks if the link is not a duplication.
								if(axrelations_search_node_duplication({source: father, target: nodes[j]})){
									links.push({source: father, target: nodes[j],present:true}); 
									operation.links.push({source: father, target: nodes[j], present:true});
								}
								is_present=true;
								break;
							}
						}
					}
					if(is_present==false){
						element["isRelation"]=false;
						element["explored"]=false;
						if(!element.relations){
							element["relations"]=null;
						}
						if(element.type=="more" || element.type=="more_LD"){ //Se il nodo e' quello fittizio del more gli do io un id per evitare di trovarne altri con stesso tipo e id.
							element.id=count;
							count++;
						}
						element.x=node_to_explore.x;
						element.y=node_to_explore.y;
						element.EP=node_to_explore.EP;
						nodes.push(element);
						links.push({source: father, target: element});
						operation.nodes.push(element);
						operation.links.push({source: father, target: element})
					}						
				}
				if(save_status==true){
					if(stack_lifo.length<50) stack_lifo.push(operation);
					else{
						stack_lifo.splice(0,1);
						stack_lifo.push(operation);
					}
				}
				restart();
			});
		}
		if(node_to_explore.type!="more" && node_to_explore.type!="more_LD") { //Normal search. Gets the properties of a node from the server.
			/* Adds other EP where search if multiendpoints are expected.*/
			var EP_to_add
			if(active_multiple_endpoints){
				EP_to_add=axr_EP_for_node(node_to_explore.id);
				//Checks if the elements in EP_to_add are already present in endpoints_list and in node_to_explore.EP, else adds that to each one.
				var list_index;
				if(EP_to_add){
					list_index=axr_add_EPs(EP_to_add);
					if(node_to_explore.EP){
						EndPoints=node_to_explore.EP.concat(list_index);
						EndPoints=axr_unique(EndPoints);
						if(node_to_explore.type=="LD"){//prevents that the server handle all the request like LD.
							EndPoints.unshift(-1);//-1 identify the special case.
						}
					}
				}
			}
			if(!EP_to_add){
				/* Defines the EndPoint where search the object's properties. If multi endpoints search is active add other EP.*/
				if(node_to_explore.EP)	EndPoints=node_to_explore.EP; 
				else{
					var EndPoints=Array();//This log should not ever been printed.
					EndPoints.push(0);//If there aren't endpoint assigned sets the first found.					
				}
			}
			//Adds 1 or max 3 loading gif to the node explored. Based on EndPoints.length .
			var loading_gif=axr_add_loading_gif(node_to_explore,EndPoints); 			
			for(var p=0;p<EndPoints.length;p++){//Makes a search for all the EP of the node to explore.
				EP=endpoints_list[EndPoints[p]];				
				//Prepares the url for the request.
				var request_url="request.php?"+node_to_explore.type+"="+encodeURIComponent(node_to_explore.id)+"&sparql="+EP;
				if(active_multiple_endpoints){
					if( EndPoints[p]!=-1 && node_to_explore.type=="LD" )request_url="request.php?uri="+encodeURIComponent(node_to_explore.id)+"&sparql="+EP;
				}
				/* Gets the informations for the node. */
				d3.json(request_url, function(json) { 
					axr_add_relations(node_to_explore , json , save_status , save_type , loading_gif , add_info);
				});		
			}
		}
	}
	else{ //The relations are already been loaded.			
		if(save_status==true){	//Saves the operation on stack...TOCHECK if is possible to use the function save.
			if(stack_lifo.length==0){ //active the button
				d3.select("#axrelations_undo").attr("disabled",null)
				.attr("src",buttons[0]);
			}
			var operation=new Object;
			if(!save_type)operation.type="explore";
			else operation.type=save_type;
			operation.node=node_to_explore;			
			if(stack_lifo.length<50) stack_lifo.push(operation);
			else{
				stack_lifo.splice(0,1);
				stack_lifo.push(operation);
			}
		}
		
		for(var r in node_to_explore.relations){
			var relation=node_to_explore.relations[r];		
			relation["isRelation"]=true;
			relation["explored"]=true;
			//For nodes that are been passed from server with already some relations (like blank nodes in LOG)
			if(!relation["id"]){
				relation["id"]=count;
				relation["uri"]=relation["uri"];
				relation["father"]=node_to_explore.name;
				relation["type"]=relation["name"];
				relation.x=node_to_explore.x;
				relation.y=node_to_explore.y;
				count++;
			}				
			if(relation.elements.length!=0 && to_display(relation)["check"] && to_display_inverse(relation)){//Adds the relation only if it has some elements.
				nodes.push(relation);
				links.push({source: node_to_explore, target: relation});
				if(to_display(relation)["class"]!="closed")explores_relationship(relation,false,relation.EP);
				else relation["explored"]=false;
			}
		}
		// relation_differentiate(node_to_explore.relations);//differentiates duplicated names.
		restart();
		// axrelations_search_relations_duplication(node_to_explore);//Prevents the inserts of inverse relations.		
		axrelations_search_relations_duplication_model(node_to_explore);//Prevents the inserts of inverse relations.TOCHECK Is it really necessary?
		node_close_all(node_to_explore.relations);//Closes all the node when loaded.		
	}
}
function axr_add_loading_gif(node_to_explore,EndPoints){	
	//assigns the load gif to the clicked node
	var element_height=72/2; //Image size.
	var element_width=120/2;
	var graphic_nodes=d3.selectAll("g.node").filter(function(d){
		if(d.index==node_to_explore.index) return d;
	});
	if(!EndPoints){//If there aren't EndPoints passed, places only one loading gif.
		var loading_gif=graphic_nodes.append("image")
		.attr("xlink:href",buttons[3])
		.attr("x", function(d){return -(element_width+10)/2+5;
		})
		  .attr("y",function(d){return -(element_height+10)/2+5;
		})
		  .attr("width",function(d){return (element_width);
		})		
		.attr("height",function(d){return (element_height);
		})
		.attr('id',"axrelations_loading_gif");
	}
	else{
		var set_dimensions=false;
		var count=1;
		switch (true){
			case (EndPoints.length>=3):
				if(!set_dimensions){
					set_dimensions=true;
					element_height=element_height/2;
					element_width = element_width/2;
					count=3;
				}
			case (EndPoints.length>=2):
				if(!set_dimensions){
					set_dimensions=true;
					element_height=element_height/1.5;
					element_width = element_width/1.5;
					count=2;
				}
			default:
				if(!set_dimensions){
					count=1;
				}
		}
		var loading_gif;
		if(count>1){loading_gif=new Object; loading_gif.number=EndPoints.length;loading_gif.node=node_to_explore.name;}
		else{//If there aren't EndPoints passed, places only one loading gif.
			var loading_gif=graphic_nodes.append("image")
			.attr("xlink:href",buttons[3])
			.attr("x", function(d){return -(element_width+10)/2+5;
			})
			  .attr("y",function(d){return -(element_height+10)/2+5;
			})
			  .attr("width",function(d){return (element_width);
			})		
			.attr("height",function(d){return (element_height);
			})
			.attr('id',"axrelations_loading_gif");
			return loading_gif;
		}
		for(var i=0;i<count;i++){
			var gif=graphic_nodes.append("image")
			.attr("xlink:href",buttons[3])
			.attr("x", function(d){return -((element_width+15))+(element_width*i);
			})
			.attr("y",function(d){return -(element_height+10)/2+5;
			})
			.attr("width",function(d){return (element_width);
			})		
			.attr("height",function(d){return (element_height);
			})
			.attr('id',"axrelations_loading_gif");
			if(count==1)loading_gif=gif;
			else{
				loading_gif[i+1]=gif;
			}			
		}
	}
	return loading_gif;
}
function axr_add_relations(node_to_explore , json , save_status , save_type , loading_gif , add_info) {
/* Function that adds the relations from the json loaded */
	//Controls for avoid syncronization problems between focus and explore.
	var found=false;
	for(var i=0;i<nodes.length;i++){//Checks if the node is present yet.
		if(nodes[i].index==node_to_explore.index){ found=true; break; }
	}
	if(found==false){ node_to_explore.explored=false; return; }
	//-----------					
	if(json){//If there is some result for the node
		var EP_searched=json.EP;//The endpoint that was searched.
		var relations_to_close=new Array();//List of relations to close at the end.
		if(save_status) {//Saves the operation on stack. XXXXXXXXXXXXX Checks if it can be done by the function 'save_handler'
			if(stack_lifo.length==0) {//Actives the button
				d3.select("#axrelations_undo").attr("disabled",null)
				.attr("src",buttons[0]);
			}
			var operation=new Object;
			if(!save_type)operation.type="explore";
			else operation.type = save_type;
			operation.node=new Object;
			operation.node=node_to_explore;			
			if(stack_lifo.length<50) stack_lifo.push(operation);
			else{
				stack_lifo.splice(0,1);
				stack_lifo.push(operation);
			}
		}
		
		if(loading_gif) {
			if(loading_gif.number) {//There are more than one loading gif.
				if(loading_gif.number) {
					if(loading_gif[loading_gif.number])loading_gif[loading_gif.number].remove();
					loading_gif.number--;
				}
			}
			else loading_gif.remove();
		}
		if( node_to_explore.type=='LD' || json.id ) {//Checks if is a LD or if sparql didn't found anything. In that case, changes its label and image.
			relations_json = json.relations;
			if(json.name)node_to_explore.name=json.name;
			if(json.img)node_to_explore.img=json.img;
			// var node_show=d3.selectAll("g.node")[0][node_to_explore.index];//Changes the attributes to the node showed.
			var node_show=d3.selectAll("g.node").filter(function(d){return d.id==node_to_explore.id;})[0];//Changes the attributes to the node showed.
			if(!node_show[0]){return;}
			if(json.img){
				var node_img=node_show[0].getElementsByTagName("image")[0];
				if(node_img.getAttribute("href")==default_image)node_img.setAttribute("href",json.img);
			}
			if(json.name){
				var node_txt=node_show[0].getElementsByTagName("text")[0];
				if(json.name!=null && json.name.length>=15) json.name=json.name.slice(0,15)+"...";
				node_txt.textContent=json.name;
			}
			if(json.name){
				var node_title=node_show[0].getElementsByTagName("title")[0];
				node_title.textContent=json.name+'\n'+node_to_explore.id;
			}
		}
		else relations_json = json.relations;
		if(node_to_explore.relations) {
		/* If the node has already some relations, adds the others and updates the existent. (This is needed for searches on multiple endpoint ) */
			for(var r in relations_json) {
				var relation=relations_json[r];
				if( node_to_explore.relations[r] ) {//if the relation is already present, only adds the new elements. 
					//Sets the EP of the node, to the current one.
					for(var element in relation.elements){
						relation.elements[element]["EP"]=Array();//Defines the node EndPoint.
						relation.elements[element]["EP"].push(endpoints_list.indexOf(EP_searched));//Pushes the index of the EndPoint in the list for the node.
					}
					relation["EP"]=endpoints_list.indexOf(EP_searched);
					var lenght_pre_concat=node_to_explore.relations[r].elements.length;
					node_to_explore.relations[r].elements = concat_noduplicate(node_to_explore.relations[r].elements.concat(relation.elements)); //merges the two array with no duplication.
					if( node_to_explore.relations[r].explored ) {  
						if(node_to_explore.relations[r].elements.length!=0 && to_display(node_to_explore.relations[r])["check"] && to_display_inverse(node_to_explore.relations[r])){
							explores_relationship(node_to_explore.relations[r],false,endpoints_list.indexOf(EP_searched));
						}
					}
					// if(node_to_explore.relations[r].elements.length!=0 && to_display(node_to_explore.relations[r])["check"] && to_display_inverse(node_to_explore.relations[r])){//Adds the relation only if it has some elements.
						// if(to_display(node_to_explore.relations[r])["class"]!="closed") explores_relationship(node_to_explore.relations[r],false,endpoints_list.indexOf(EP_searched));
						// else node_to_explore.relations[r]["explored"]=false;
					// }
				}
				else{ //adds the relation.
					relation["isRelation"]=true;
					relation["explored"]=true;
					relation["uri"]=relation["uri"];
					relation["father"]=node_to_explore.name;
					relation["type"]=relation["name"];
					relation["EP"]=endpoints_list.indexOf(EP_searched);//Sets the EndPoint of the relations. Also the relations have more than one endpoint e.g. If a relation is present in two endpoints and if a node has either the two EP, an explore merges the two relations. XXXXXXXXXXXX
					relation.x=node_to_explore.x;
					relation.y=node_to_explore.y;
					relation["id"]=count;
					count++;
					node_to_explore.relations[r]=relation;
					if(node_to_explore.relations[r].elements.length!=0 && to_display(node_to_explore.relations[r])["check"] && to_display_inverse(node_to_explore.relations[r])){//Adds the realtion only if it has some elements.
						nodes.push(node_to_explore.relations[r]);
						links.push({source: node_to_explore, target: node_to_explore.relations[r]});
						if(to_display(node_to_explore.relations[r])["class"]!="closed")explores_relationship(node_to_explore.relations[r],false,endpoints_list.indexOf(EP_searched));
						else node_to_explore.relations[r]["explored"]=false;
						relations_to_close.push(relation);
					}
				}
			}					
		}	
		else{
			node_to_explore["relations"]=relations_json;
			for(var r in relations_json){
				var relation=relations_json[r];
				relation["isRelation"]=true;
				relation["explored"]=true;
				relation["id"]=count;
				relation["uri"]=relation["uri"];
				relation["father"]=node_to_explore.name;
				relation["type"]=relation["name"];
				relation["EP"]=endpoints_list.indexOf(EP_searched);//Sets the EndPoint of the relations
				relation.x=node_to_explore.x;
				relation.y=node_to_explore.y;
				count++;
				if(relation.elements.length!=0 && to_display(relation)["check"] && to_display_inverse(relation)){//Aggiungo la relazione solo se ha degli elementi
					nodes.push(relation);
					links.push({source: node_to_explore, target: relation});
					if(to_display(relation)["class"]!="closed")explores_relationship(relation,false,endpoints_list.indexOf(EP_searched));
					else relation["explored"]=false;
					relations_to_close.push(relation);
				}
			}
			// relation_differentiate(relations_json);//differentiates duplicated names.
		}
		restart();//XXXXXX Is it really necessary?
		//Special case of add.
		if(add_info) {
			if(add_info.EP==EP_searched){//If is not present the link inbound adds that.
				node_searched=nodes.filter(function(d){return d.id==add_info.node_searched})[0];
				if(add_info.inbound==0){//case outbound
					//Checks if is present the relation
					if(!node_searched.relations){//if there isn't the relations or if there is the relation on the graph. Adds the link.
						add_singular_relation(add_info);//singular outbound
					}
					else{
						var relation_chose=node_searched.relations[add_info.property];
						var links_find=links.filter(function(d){if(d.source.id==relation_chose.id&&d.source.type==relation_chose.type&&d.target.id==add_info.node_add)return d})
						if(links_find.length==0){//Adds the link.
							add_singular_relation(add_info,relation_chose);//singular outbound, relation found
						}
					}
					//Adds the relation from the searched element to the add element
				}
				else{//Inverse case
					//Checks if is present the relation
					//Adds the relation from the searched element to the add element
					node_add=nodes.filter(function(d){return d.id==add_info.node_add})[0];
					var relation_chose=node_add.relations[add_info.property];
					var links_find=links.filter(function(d){if(d.source.id==relation_chose.id&&d.source.type==relation_chose.type&&d.target.id==add_info.node_searched)return d})
					if(links_find.length==0){//Adds the link
						add_singular_relation(add_info,relation_chose);//singular inbound
					}
				}
			}
		}
		// // axrelations_search_relations_duplication(node_to_explore);//Prevents the inserts of inverse relations.
		relations_added=relations_to_close;
		axrelations_search_relations_duplication_model(node_to_explore,relations_added);//Prevents the inserts of inverse relations.//TOCHECK. Is it really necessary?!? Yes, For deletes the new relation.
		node_close_all(relations_to_close);//Closes all the node when loaded.
	}
	else{
		if(loading_gif.number){//There are more than one loading gif.
			if(loading_gif.number){
				if(loading_gif[loading_gif.number])loading_gif[loading_gif.number].remove();
				loading_gif.number--;
			}
		}
		else{
			if(loading_gif)loading_gif.remove();
		}
	}
}		
function axr_EP_for_node(uri_searched){
/* Function that checks if the node passed is associated with many other EP. */
	if(!uri_searched)return null;
	var prefix=uri_searched.substr(0,uri_searched.indexOf('/',8));
	var EP_finded=new Array();
	if(multiple_endpoints[prefix]){
		for(var ep_element in multiple_endpoints[prefix]){
			//Adds the EndPoint if it has a priority less or egual to the prefixed one.
			if(multiple_endpoints[prefix][ep_element]['p']<=multiple_endpoint_priority){
				EP_finded.push(multiple_endpoints[prefix][ep_element]['name']);
			}
		}
	} 
	else return null;
	return EP_finded;
}
function axr_add_EPs(EP_to_add){
/* Functions for adds a list of EP in the current list, and that return the EP.*/
	if(!EP_to_add) return null;
	var list_of_position=new Array();//Positions of the EPs in the enpoints_list.
	for(var i in EP_to_add){
		if(!(endpoints_list.indexOf(EP_to_add[i])>=0)){//Adds the EP.
			list_of_position.push(endpoints_list.length);
			endpoints_list.push(EP_to_add[i]);
		}
		else list_of_position.push(endpoints_list.indexOf(EP_to_add[i]));
	}
	return list_of_position;
}
function axr_unique(origArr) {
/* Functions for remove duplication from an array. */
    var newArr = [],
        origLen = origArr.length,
        found, x, y;

    for (x = 0; x < origLen; x++) {
        found = undefined;
        for (y = 0; y < newArr.length; y++) {
            if (origArr[x] === newArr[y]) {
                found = true;
                break;
            }
        }
        if (!found) {
            newArr.push(origArr[x]);
        }
    }
    return newArr;
}
	
//--------------------CHECKBUTTON RELATION ---------------	
function add_remove_relations(relation_button,add,save){//Displays or remove the nodes that specifies a relations on the graph.
	if(relation_button.id=="hide_inverse")return;
	relation=relation_button.value;
	if(save==true && add!="close"){
		//Saves the operation on stack
		if(stack_lifo.length==0){ //active the button
			d3.select("#axrelations_undo").attr("disabled",null)
			.attr("src",buttons[0]);
		}
		var operation=new Object;
		operation.type="relation";
		operation.relation=relation_button;
		operation.add=!(add);
		if(stack_lifo.length<50) stack_lifo.push(operation);
		else{
			stack_lifo.splice(0,1);
			stack_lifo.push(operation);
		}
	}		
	if(add==true){//Re-adds the nodes. Checks for all the nodes if it has in its relations the current one ('relation').
		var actual_number_of_elements=nodes.length;
		for(var i=0;i<actual_number_of_elements;i++){
			if(nodes[i].isRelation!=true && (nodes[i].explored==true || nodes[i].single_search==true)){ //The relations were already been loaded and the node was already been explored.
				for(var r in nodes[i].relations){
					if(nodes[i].relations[r].uri==relation && nodes[i].relations[r].name==relation_button.nextSibling.textContent &&to_display_inverse(nodes[i].relations[r])){
						nodes[i].relations[r]["isRelation"]=true;
						nodes[i].relations[r]["explored"]=true;
						if(nodes[i].relations[r].elements.length!=0){//Adds the relation only if it has some element.
							nodes.push(nodes[i].relations[r]);
							links.push({source: nodes[i], target: nodes[i].relations[r]});
							explores_relationship(nodes[i].relations[r],false,nodes[i].relations[r].EP);
							// axrelations_search_relations_duplication(nodes[i]);
							// axrelations_search_single_duplication(nodes[i],nodes[i].relations[r]);
							axrelations_search_single_duplication_model(nodes[i],nodes[i].relations[r]);
						}
					}
				}
			}
		}
		axrelations_graph_finder();
	}
	else{//Deletes the nodes from the visualization.
		for(var i=0;i<nodes.length;i++){
			if(nodes[i].uri==relation && nodes[i].name==relation_button.nextSibling.textContent ){//Rimuovo il nodo a partire dal link con il padre.
				for(var j=0;j<links.length;j++){
					if(links[j].target==nodes[i]){
						links.splice(j,1);
						break;//Breaks this cycle because a relation has only one father.
					}
				}
				element_elimination(nodes[i]);
				axrelations_graph_finder();
				i=0; //i=0 because it has to restart from the begin.
			}
		}
	}
	restart();
}
//--------------------END of controls for checkbuttons--------------	
//Function for display literals of an objects.
function axrelations_display_literals(json,node,number){ 
	var info_div="";
	$(".info_number_"+number+" #axrelations_info_loading_gif").remove();
	for(var i=0;i<json.length;i++){
		info_div+="<li><p class='property_name'>"+json[i].name+"</p>"+json[i].value+"  </li>";		
	}		
	$(".info_number_"+number+" ul").append(info_div);
}
/*Differentiates the properties. Is possible that 2 or more properties have the same name but differents uri. 
This function differentiates the name in this cases.
*/
function relation_differentiate(relations){
	for(var r in relations){
		var duplicated=false;
		for(var i=0; i<nodes.length; i++){
			if(nodes[i].isRelation==true){
				if(nodes[i].uri!=relations[r].uri && nodes[i].name==relations[r].name){duplicated=true;break;}
			}
		}
		if(duplicated==true){//Differentiates the property with the same name.
			var c=0;
			var name=relations[r].name;
			for(var i=0; i<nodes.length; i++){
				if(nodes[i].isRelation==true){
					if(nodes[i].name==name){
						nodes[i].name=nodes[i].name+" "+c;
						c++;
					}						
				}
			}
		}
		//nodes links
	}
}
function save_current_configuration(email,update){
	/*Saves the current status of the graph. 
	Creates an Object for save EndPoint - count of the item - list of nodes and links - list of relations - list of EndPoints.
	*/
	current_graph_title=$("#axrelations_save_dialog #graph_save_title").val();if(current_graph_title=="")current_graph_title="LOG graph.";
	current_graph_desc=$("#axrelations_save_dialog #graph_save_desc").val();if(current_graph_desc=="")current_graph_desc="No description provided.";
	var loading_gif="</br><img id='axrelations_info_loading_gif' src="+buttons[3] +">";
	$("#axrelations_save_main_div").append(loading_gif);
	var configuration=new Object;
	configuration.sparql=endpoint;
	configuration.eps=endpoints_list; //EndPoints list.
	configuration.initial_uri=initial_uri;
	configuration.parent_id=parent_id;
	configuration.email=email;
	configuration.count=count; //For start with other explorations.
	configuration.nodes=Array();
	configuration.nodes=nodes;
	configuration.links=Array();
	//Gets the options from the status and saves them.
	var status_opt=get_status_options();//function in index.php
	status_opt=JSON.stringify(status_opt);
	//Saves the nodes and the links.
	for(var i=0;i<links.length;i++){
		configuration.links[i]=new Object;
		configuration.links[i].source=new Object;
		configuration.links[i].source.id=links[i].source.id;
		configuration.links[i].target=new Object;
		configuration.links[i].target.id=links[i].target.id;
	}
	var li_list=$("#axrelations_graph_menu_bottom #axrelations_type li");
	configuration.relations=Array();
	for(var i=0;i<li_list.length;i++){
		var rel=new Object;
		rel.name=li_list[i].textContent;
		var input_r=li_list[i].children[0];
		rel.value=input_r.value;
		rel.checked=input_r.checked;
		rel.id="";
		if(input_r.id!="")rel.id=input_r.id;
		configuration.relations[i]=rel;
	}
	var save=JSON.stringify(configuration,function(key,value){
		if(this.fixed!=true &&(key=="x"||key=="y"||key=="py"||key=="px") ) return undefined;
		return value;
	});
	var EPs=JSON.stringify(endpoints_list);
	$.post("save_configuration.php",{'email':email,'update':update,'endpoint':endpoint,'initial_uri':initial_uri,'parent_id':parent_id,'save':save,'EPs':EPs,'status':status_opt,'title':JSON.stringify(current_graph_title),'desc':JSON.stringify(current_graph_desc)},function(data){
		//Changes the content on the open dialog of the save.
		$("#axrelations_save_main_div").remove();
		var text="Save not work. We are sorry, try lather or send us an email with your problems.";
		if(data['response']=='save'){
			if(data['save_r'])save_r=data['save_r'];//Saves the link to the read version.
			if(data['save_rw'])save_rw=data['save_rw'];//Saves the link to the read/write version.
			text=" The graph has been saved.";
			if(update!=true){ 
				text+=" <p>A direct url to the graph has been sent at the email:"+data['email']+"</p>";
				parent_id=data.overwride_code;
				user_mail=email;
				save_operation="write";							
			}
			else text+="<p>"+email+"'s version has been overwritten </p>";
			axrelations_insert_description();//Inserts the description box in the page.
		}
		var new_dialog="<div id='axrelations_save_main_div'><p>"+text+"</p>";
		// new_dialog+="<input type='button' id='axr_btn_close' title='close' value='close'>";
		new_dialog+="</div>";
		$("#axrelations_save_dialog").attr("class","small");
		$("#axrelations_save_dialog").append(new_dialog);
		$("#axrelations_save_dialog #axr_btn_close").click(function (e)
		{
			$("#axrelations_save_dialog").fadeOut(300);
			$("#axrelations_save_dialog").remove();
			$("#overlay").hide();
		});
	},'json');		
}

function retrieve_configuration(code){//Retrieves the configuration identified by 'code'. load_config
	if(code!=null){
		d3.json("request.php?retrieve_configuration="+code,function(json){
			if(json.error!=undefined){alert(json.error);$("#graphContainer").remove();return;}
			var call1time=true;
			endpoint=json.sparql;
			if(json.eps==null){//if is an old save and the list of EndPoints isn't defined; sets the value of EP of all the nodes to 0 and in 'endpoints_list' inserts the EndPoint. 
				endpoints_list.push(endpoint);
				for(var i in json.nodes){
					if(json.nodes[i].isRelation!=true){
						json.nodes[i]["EP"]=Array(); //Defines an array of index of EndPoint for the node.
						json.nodes[i]["EP"].push(0);
					}
					else json.nodes[i]["EP"]=0;
				}
			}
			else endpoints_list=json.eps;
			//Changes the visualization.
			$("select").val(endpoint).change();
			$("#endpoint_form").parent().attr('class','endpoint active');			
			if(json.status)add_status_options(true,json.status);//Loads the status options in the window.
			//Loads the title and the description.
			current_graph_title=json.title;
			current_graph_desc=json.desc;
			axrelations_insert_description();
			
			count=json.count;
			initial_uri=json.initial_uri;
			parent_id=json.parent_id;
			user_mail=json.email;
			save_operation=json.op;
			if(parent_id=="")parent_id=code;
			//-------retrieve relations check buttons.
			var list,i;
			list='<div id="axrelations_graph_menu_bottom"><h2>Type of relations</h2>';
			list+='<input id="axr_all_relations" type="button" value=" Select all "><input id="axr_no_relations" type="button" value=" Deselect all "><input id="axr_invert_relations" type="button" value=" Invert "><input id="hide_inverse" type="checkbox" checked="checked" title="hide all inverse properties"><span>Hide all inverse</span></br> ';
			list+='<ul id="axrelations_type" display="inline">';
			i=0;
			for(var r in json.relations){
				var relation=json.relations[r];
				var check="";
				if(relation.checked==true)check="checked='true'";
				list += '<li class="relation_' + (i++) + '"><input id="'+relation.id+'" type="checkbox" '+check+'" value="' + relation.value + '" title="' + relation.value + '">' + relation.name + '</li>';				
			}
			list+='</ul></div>';
			if(call1time==true ){ 
				$("#axrelations_graph_menu_bottom").remove();
				call1time=false;
				$("#options_panels").append(list);
				$("#axrelations_graph_menu_bottom").resizable({maxHeight: 500,minHeight: 100,handles: 's'});			
				var relations=$("#axrelations_graph_div input");
				for(var i=0;i<relations.length;i++){
					relations[i].onchange=(function(r){
						add_remove_relations(this,this.checked,true);
					});						
				}
				//Edit the function for the buttons.
				$("#axr_all_relations").unbind('click').click(function(){
					var relation_buttons=$("#axrelations_graph_menu_bottom ul input");
					for(var i=0;i<relation_buttons.length;i++){
						if(relation_buttons[i].checked!=true){
							relation_buttons[i].checked="checked";
							add_remove_relations(relation_buttons[i],relation_buttons[i].checked,true);
						}
					}
					restart();
				});
				$("#hide_inverse").unbind('change').change(function(){//Sets the function for hide all the inverse relations.
					axrelations_show_inverse(this.checked);
					axrelations_close_all_inverse();//Closes all the inverse.
				});	
				
				$("#axr_no_relations").unbind('click').click(function(){
					var relation_buttons=$("#axrelations_graph_menu_bottom ul input");
					for(var i=0;i<relation_buttons.length;i++){
						if(relation_buttons[i].checked!=false){
							relation_buttons[i].checked=false;
							add_remove_relations(relation_buttons[i],relation_buttons[i].checked,true);
						}
					}
					restart();
				});
				$("#axr_invert_relations").unbind('click').click(function(){
					var relation_buttons=$("#axrelations_graph_menu_bottom ul input");
					for(var i=0;i<relation_buttons.length;i++){
						if(relation_buttons[i].checked!=false){
							relation_buttons[i].checked=false;
							add_remove_relations(relation_buttons[i],relation_buttons[i].checked,true);
						}
						else{
							relation_buttons[i].checked="checked";
							add_remove_relations(relation_buttons[i],relation_buttons[i].checked,true);
						}
					}
					restart();
				});
			}				
			//-------Retrieves nodes.
			nodes.splice(0,nodes.length);
			links.splice(0,links.length);
			d3.select("#axrelations_graph_svg").style("opacity","1");
			$("#axrelations_graph_container").css("background","none");
			for(var i in json.nodes){
				json.nodes[i].present=true;
				nodes.push(json.nodes[i]);
			}
			for(var i in json.links){//Remaps the source and the Object of the links with the nodes.
				json.links[i].present=true;
				var source_found=false;
				var target_found=false;
				for(var j=0; j<nodes.length; j++){
					if(json.links[i].source.id==nodes[j].id){//If the source is node[j]
						json.links[i].source=nodes[j];
						source_found=true;
					}
					if(json.links[i].target.id==nodes[j].id){
						json.links[i].target=nodes[j];
						target_found=true;
					}
					if(source_found==true && target_found==true)break;
				}
				links.push(json.links[i]);
			}
			//Links all the node.relations with the corrispective node.
			for(var i in nodes){
				if(nodes[i].explored==true && !nodes[i].isRelation){//links each relation
					relations=nodes[i].relations;
					for(var r in relations){
						rel=relations[r];
						displayed_node=nodes.filter(function(d){return (d.isRelation && d.id==rel.id)});
						if(displayed_node.length!=0){relations[r]=displayed_node[0];} //ReSets the link.
					}
				}
			}
			
			if(axrelations_inverse_showed())$("#hide_inverse").attr("checked",null);//Checks if select or deselect the hide button.
			if($("body.embed").length>=0){
				// recenter_graph(0);
				// zoom.translate([initial_x,initial_y]);
			}
			restart();
		});
	}
}
function axrelations_graph_addgraph(id,active_endpoint,add_info){
	/* 	This Function adds a new graph. It makes a request to the endpoint passed as 'active_endpoint' 
	for the property of the node passed as 'id'
	*/
	type='first_uri';
	var call1time=true;
	var current_end='&sparql='+active_endpoint;
	//Checks if the node requested is already in the graph.
	var is_present=false;
	var j;
	for(j=0;j<nodes.length;j++){ //TOCHECK: maybe this could be changed with filter.
		if(nodes[j].isRelation==false){
			if(nodes[j].id==id || (nodes[j].name==id && nodes[j].type=="placeholder")){ 
				is_present=true;
				break;
			}
		}
	}
	if(is_present){
		recenter_graph(j);//Repositions the visualization
		//Adds the node as a root
		nodes[j].root=true;
		nodes[j].fixed=true;
		if(!nodes[j].explored){//Node is present but not explored.				
			if(add_info){//Saves the previous state of the node explored.
				explore(nodes[j],true,"new_root_explore",add_info);
			}
			else{
				explore(nodes[j],true,"new_root_explore");
			}
		}	
		else{
			//Saves the operation.
			if(stack_lifo.length==0){ //actives the button
				d3.select("#axrelations_undo").attr("disabled",null)
				.attr("src",buttons[0]);
			}
			var operation=new Object;
			operation.type="newroot";
			operation.node=new Object;
			//operation.node=nodes[j];
			operation.status=nodes[j].id;
			//Checks if the last operation is the same.
			if(stack_lifo.length!=0){
				var last_op=stack_lifo[stack_lifo.length-1];
				if(last_op.type!="newroot" || last_op.node!=nodes[j]){//else doesn't add the op.
					if(stack_lifo.length<50) stack_lifo.push(operation);
					else{stack_lifo.splice(0,1);stack_lifo.push(operation);}
				}
			}
			else{
				stack_lifo.splice(0,1);
				stack_lifo.push(operation);
			}
			//Checks if is necessary to add the relation.
			if(add_info){
				//Saves the previous state of the node explored.
				node_searched=nodes.filter(function(d){return d.id==add_info.node_searched})[0];
				for(var p in node_searched){ //duplicates the attributes of the current element
					operation.node[p]=node_searched[p];
				}
				if(add_info.inbound==0){//case outbound
					if(add_info.for=="more"){
						// special case of add for an inverse relation
						node_add=nodes.filter(function(d){return d.id==add_info.node_searched})[0];
						var relation_chose=node_add.relations[add_info.property+"Inv"];
						var links_find=links.filter(function(d){if(d.source.id==relation_chose.id&&d.source.type==relation_chose.type&&d.target.id==add_info.node_add)return d})
						if(links_find.length==0){//Adds the link
							add_singular_relation(add_info,relation_chose);//singular inbound
						}
					}
					else{
						//Checks if is present the relation
						// node_searched=nodes.filter(function(d){return d.id==add_info.node_searched})[0];
						if(!node_searched.relations){//if there isn't the relations or if there is the relation on the graph. Adds the link.
							add_singular_relation(add_info);//singular outbound
						}
						else{
							var relation_chose=node_searched.relations[add_info.property];
							var links_find=links.filter(function(d){if(d.source.id==relation_chose.id&&d.source.type==relation_chose.type&&d.target.id==add_info.node_add)return d})
							if(links_find.length==0){//Adds the link
								add_singular_relation(add_info,relation_chose);//singular outbound, relation found
							}
						}
						//Adds the relation from the searched element to the add element
					}
				}
				else{//Case inverse
					if(add_info.for=="more"){
						// special case of add for an inverse relation
						node_add=nodes.filter(function(d){return d.id==add_info.node_searched})[0];
						var relation_chose=node_add.relations[add_info.property+"Inv"];
						var links_find=links.filter(function(d){if(d.source.id==relation_chose.id&&d.source.type==relation_chose.type&&d.target.id==add_info.node_add)return d})
						if(links_find.length==0){//Adds the link
							add_singular_relation(add_info,relation_chose);//singular inbound
						}
					}
					else{
						//Checks if is present the relation
						//Adds the relation from the searched element to the add element
						
						node_add=nodes.filter(function(d){return d.id==add_info.node_add})[0];
						var relation_chose=node_add.relations[add_info.property];
						var links_find=links.filter(function(d){if(d.source.id==relation_chose.id&&d.source.type==relation_chose.type&&d.target.id==add_info.node_searched)return d})
						if(links_find.length==0){//Adds the link
							add_singular_relation(add_info,relation_chose);//singular inbound
						}
					}
				}
			}
		}
	}
	else{
		//Adds a fictitious node like a place-holder for the new entry.
		element=new Object();
		element.isRelation=false;
		element.explored=false;
		element.root=true;
		element.name=id;
		element.type="placeholder";
		element.id=count;//Gives to the placeholder an id that makes it unique.
		count++;		
		//Finds a free position for the new node and Fixs it.
		axrelations_place_node(element);				
		nodes.push(element);
		restart();
		recenter_graph(nodes.length-1);
		//assigns the load gif to the clicked node
		var graphic_nodes=d3.selectAll("g.node").filter(function(d){
			if(d.id==element.id && d.type=="placeholder") return d;
		});
		
		graphic_nodes.append("image")//Adds the loading gif.
		.attr("xlink:href",buttons[3])
		.attr("x", function(d){return -(36)/2+5;
		})
		.attr("y",function(d){return -(70)/2+5;
		})
		.attr("width",function(d){return (36);
		})		
		.attr("height",function(d){return (60);
		})
		.attr('id',"axrelations_loading_gif");
		//Searches the element and its relations.
		d3.json("request.php?"+type+"="+encodeURIComponent(id)+""+current_end, function(json) { 			
			if(!json.relations) {
				$( "#error_uri_empty" ).remove();
				var message="<div id='error_uri_empty' title='Try again'><p>The current uri has no properties with it. Probably the uri is wrong or is not present in the current endpoint. Please check the correctness of the URI. </p></div>";
				$("body").append(message);
				$( "#error_uri_empty" ).dialog();
			}
			if(json==null)return;
			json_file = json;
			if(json_file!=null && call1time==true) {
				for(var i=0;i<nodes.length;i++){//Removes the placeholder. TOCHECK make this for with .filter function.
					if(nodes[i].id==element.id && nodes[i].type=="placeholder"){
						nodes.splice(i,1);
						break;//Breaks this cycle because there is only one placeholder for a node.
					}
				}
				//Saves the operation.
				if(stack_lifo.length==0) { //actives the button
					d3.select("#axrelations_undo").attr("disabled",null)
					.attr("src",buttons[0]);
				}
				var operation=new Object;
				operation.type="addnode";
				operation.node=new Object;
				operation.status=id;
				operation.nodes=Array();
				for(var i=0;i<nodes.length;i++)operation.nodes[i]=nodes[i];
				operation.links=Array();
				for(var i=0;i<links.length;i++)operation.links[i]=links[i];
				if(stack_lifo.length<50) stack_lifo.push(operation);
				else{stack_lifo.splice(0,1);stack_lifo.push(operation);}
				//Creates the separate graph
				call1time=false;
				step1(element.x,element.y,active_endpoint);//Calls the step1 function.
				// axrelations_search_relations_duplication_model(json);
				//Checks if is necessary to add the relation.
				if(add_info) {
					//Saves the previous state of the node explored.
					node_searched=nodes.filter(function(d){return d.id==add_info.node_searched})[0];
					for(var p in node_searched){ //duplicates the attributes of the current element
						operation.node[p]=node_searched[p];
					}
					
					if(add_info.inbound==0){//case outbound
						//Checks if is present the relation and Adds the relation from the searched element to the add element
						if(!node_searched.relations || add_info['for']=='more'){
							//if there isn't the relations or if there is the relation on the graph or if is a search in the more object. Adds the link.
							add_singular_relation(add_info);//singular 
						}
						else{
							var relation_chose=node_searched.relations[add_info.property];
							var links_find=links.filter(function(d){if(d.source.id==relation_chose.id&&d.source.type==relation_chose.type&&d.target.id==add_info.node_add)return d})
							if(links_find.length==0){//Adds the link
								add_singular_relation(add_info,relation_chose);//singular outbound
							}
						}
						
					}
					else {//Case inbound
						//Checks if is present the relation and Adds the relation from the searched element to the add element
						node_add=nodes.filter(function(d){return d.id==add_info.node_add})[0];
						var relation_chose=node_add.relations[add_info.property];
						//For the case of search for more. Without the line below adds the link to the inverse relation.
						if(add_info['for']=='more' || node_searched.relations[add_info.property]){
							relation_chose=node_searched.relations[add_info.property+"Inv"];//TOCHECK if add 'Inv' is necessary only for the more type. In that case differentiate with node_searched.relations[add_info.property]
						}						
						var links_find=links.filter(function(d){if(d.source.id==relation_chose.id&&d.source.type==relation_chose.type&&d.target.id==add_info.node_searched)return d})
						if(links_find.length==0 || add_info['for']=='more'){
							//Adds the link if is not found or if is a search in the more object
							add_singular_relation(add_info,relation_chose);//singolar inbound
						}						
					}
				}				
				axrelations_search_relations_duplication_model(json);
			}	
		});
	}
	return is_present;
}
function axrelations_place_node(element){
	//Sets a free position for 'element'. This is used for the root nodes.
	max_x=0;
	max_y=0;
	min_x=10000;
	min_y=10000;
	if(nodes.length>0){
		for(var i=0;i<nodes.length;i++){
			if(cardinal=='e' && nodes[i].fixed==true && nodes[i].x>max_x){max_x=nodes[i].x; max_y=nodes[i].y;} 
			if(cardinal=='n' && nodes[i].fixed==true && nodes[i].y>max_y){max_x=nodes[i].x; max_y=nodes[i].y;} 
			if(cardinal=='w' && nodes[i].fixed==true && nodes[i].x<min_x){min_x=nodes[i].x; min_y=nodes[i].y;} 
			if(cardinal=='s' && nodes[i].fixed==true && nodes[i].y<min_y){min_x=nodes[i].x; min_y=nodes[i].y;} 
		}		
		switch(cardinal){
			case 'e':
				element.x=max_x+300;
				element.y=max_y;
				cardinal="n";
				break;
			case 'n':
				element.x=max_x;
				element.y=max_y+300;
				cardinal="w";
				break;
			case 'w':
				element.x=min_x-300;
				element.y=min_y;
				cardinal="s";
				break;
			case 's':
				element.x=min_x;
				element.y=min_y-300;
				cardinal="e";
				break;
		}	
	}
	element.fixed=true;
}
//Clears the content of the graph. Deletes all the nodes, all the links, the undo stack and the relations in the bottom menu.
function clear_graph(){
	nodes.splice(0,nodes.length);//removes the nodes
	links.splice(0,links.length);//removes the links
	stack_lifo.splice(0,stack_lifo.length);//removes the undo in the stack.
	d3.select("#axrelations_undo").attr("disabled","disabled")//de actives the button
	.attr("src",buttons[2]);
	$("#axrelations_graph_menu_bottom #axrelations_type").children().remove();//removes the relations on the bottom. It also removes some relations from the start.XXXXXXXXXXXX
	restart();
}
function control_add_graph(num_form){//Checks if there is a value in the uri field. If there is, add another graph to the existent one.
	var id_to_add=$("form")[num_form].getElementsByTagName("input")[1].value;//chooses the uri from the selected form.
	if(id_to_add){
		if(num_form==0)var active_endpoint=$("select option:selected").val();
		else{
			active_endpoint=$("form")[num_form].getElementsByTagName("input")[0].value;
			// if(active_endpoint==""){window.alert("sparql endpoint field empty.");return;}
			if(active_endpoint==""){active_endpoint="LD";}
		}
		var node_present=axrelations_graph_addgraph(id_to_add,active_endpoint);//calls the js function for adds another root on the graph.
		var opt=$(".uri_added option");
		var present=opt.filter(function(d){//Checks if is present in the status.
			return opt[d].value==id_to_add;
		});
		if(!node_present || present.length==0){//If the node isn't already in the graph, adds the option in the status panel.
			var label=""+id_to_add +"["+active_endpoint +"]";
			if(num_form==0){//if the form is the first checks if there is a keyword associated to the uri.
				var uri_input=$("#uri_input")[0];
				var uri_field=uri_input.value;
				if(uri_input.getAttribute('keyword'))uri_field=uri_input.getAttribute('keyword');//if is present the keyword, adds that.
				label=""+uri_field +"["+$("select option:selected").attr("label") +"]" ;
			}
			var option_to_add="<option value="+id_to_add +" title='URI:"+id_to_add +"  EndPoint:"+active_endpoint +"' >"+label +"</option>"; //Adds the uri requested to the list of uri called.
			$("select.uri_added").prepend(option_to_add);
			split_status()//Calls the function for put in two columns the option of the select item.
		}
	}
	else window.alert("URI field empty.");
}
function reset_graph(){//removes the actual nodes and links from the graph, and clears the status list.
	$(".uri_added").children().remove();
	$("#graph_description_container").remove();
	clear_graph();//function in axrelations.js that clear the graph.
}
function changeurinput(){//Removes the keyword.
	$("#uri_input").removeAttr("keyword");
}
function split_status(){//Code for split the select box on the right in 2 columns.						 
	var spacesToAdd = 2;
	var biggestLength = 0;
	$(".uri_added option").each(function(){
		var parts = $(this).text().split('[');//resets the spaces.
		parts[0]=parts[0].trim();//removes the white spaces on the left side.
		var len = parts[0].length+parts[1].length;
		// var len = $(this).text().length;
		if(len > biggestLength) biggestLength = len;
	});
	var padLength = biggestLength + spacesToAdd;
	var first=true;
	$(".uri_added option").each(function(){
		var parts = $(this).text().split('[');
		// parts[0]=parts[0].trim();//remove the white spaces on the left side.
		var strLength = parts[0].length+parts[1].length;
		if(first==true){first=false;strLength=strLength-spacesToAdd;}
		var blank='';
		for(var x=0; x<(padLength-strLength); x++){
			blank = blank+' '; 
		}
		$(this).text(parts[0]+blank.replace(/ /g, '\u00a0')+'['+parts[1]).text;
		// $(this).text(parts[0]+'['+parts[1]).text;
	});
}
function get_status_options(value){//Returns the options in the status window						
	var options=$(".uri_added option");
	var status=Array();
	for(var o=0;o<options.length;o++){
		var st=Object();
		st['title']=options[o].title;
		st['text']=options[o].text;
		if(value)st['value']=options[o].value;
		status.push(st);
	}
	return status;
}
function add_status_options(input_list,options){//Adds to the status the passed option(s). If input_list is a boolean flag for distinguish if options is a list of input button or no.
	if(input_list){
		var option_to_add="";
		var value;
		for(var o=0;o<options.length;o++){
			value=options[o].title.substr(4,options[o].title.indexOf("EndPoint:")-6);
			option_to_add+="<option value="+value +" title='"+options[o].title +"' >"+options[o].text +"</option>"; //Adds the uri requested to the list of uri called.
		}
		$("select.uri_added").prepend(option_to_add);
	}
	else{
		var option_to_add="";
		var value;
		for(var o=0;o<options.length;o++){
			value=options[o].uri;
			var option_title="URI:"+value +"  EndPoint:"+options[o].endpoint +"";
			var option_text=""+options[o].uriname +"["+options[o].endpointname +"]";
			option_to_add+="<option value="+value +" title='"+option_title +"' >"+option_text +"</option>"; //Adds the uri requested to the list of uri called.
		}
		$("select.uri_added").prepend(option_to_add);
		split_status()//Calls the function for put in two columns the options of the select item.
	}
}
function remove_status(s_value){//Removes the status identified by s_status.
	var o=$(".uri_added option[value='"+s_value +"'] ");
	if(o.length!=0){o.remove();return true;}
	return false;
}
function remove_status_option(){//Saves the configuration and removes an option from the status.
	var option_selected=$(".uri_added")[0].selectedOptions[0];
	if(!option_selected)return;
	option_value=option_selected.value;
	var node_target=nodes.filter(function(d){return d.id==option_value});
	node_target=node_target[0];
	//Saves the operation.
	if(stack_lifo.length==0){ //actives the button
		d3.select("#axrelations_undo").attr("disabled",null)
		.attr("src",buttons[0]);
	}
	var operation=new Object;
	operation.type="status_removed";
	operation.node=new Object;
	for(var p in node_target){ //duplicates the attributes of the current element
		operation.node[p]=node_target[p];
	}
	var status_selected;
	var states=get_status_options(true);
	status_selected=states.filter(function(d){
		return d.value==option_value;
	});
	operation.status=status_selected;
	operation.nodes=Array();
	for(var i=0;i<nodes.length;i++)operation.nodes[i]=nodes[i];
	operation.links=Array();
	for(var i=0;i<links.length;i++)operation.links[i]=links[i];
	if(stack_lifo.length<50) stack_lifo.push(operation);
	else{stack_lifo.splice(0,1);stack_lifo.push(operation);}	
	//Save ends.
	
	element_elimination(node_target);//Removes the links that connects the target node.
	delete node_target.root; //Deletes the attribute 'root'
	axrelations_graph_finder();//Removes the nodes connected with the target.
	restart();
	option_selected.remove();//Removes the target option from the log
	$("#remove_status").attr("disabled",'disabled');
}
function axrelations_query_view(event,inbound,number_of_elements,EP_name) {
	$(".axrelations_view_query").remove();//Deletes the existent one
	event = event || window.event //For IE
	var button_click=event.target;
	node_id=button_click.getAttribute('node');
	EP=button_click.value;
	var position="#axrelations_graph_div";//Place for the literals.
	var info_div="<div id='axrelations_info' class='draggable axrelations_view_query' value='"+node_id+"' ><div id='axrelations_info_title' >RESULT<a id='axr_view_close'>Close</a></div>";
	info_div+="<div id='axrelations_info_main_div'>";
	if(inbound==1) info_div+="<p class='property_name'>Sparql Query:</p>EndPoint:</br> "+EP+" </br></br>QUERY:</br> Select ?subject ?property </br> WHERE{?subject ?property &lt"+node_id +"&gt </br> .FILTER(isURI(?subject)) </br>} LIMIT 100";
	else info_div+="<p class='property_name'>Sparql Query:</p>EndPoint:</br> "+EP+" </br></br>QUERY:</br> Select ?object ?property </br> WHERE{&lt"+node_id +"&gt ?property ?object </br> .FILTER(isURI(?object)) </br>} LIMIT 100";
	info_div+="<div id='axrelations_list'></div>";
	info_div+="</div></div>";
	$(position).append(info_div);
	$(".draggable").draggable({ handle: "#axrelations_info_title"});
	$(".draggable").resizable({minHeight: 400, minWidth:360});
	$("#axr_view_close").click(function (e)
	{
		$(".axrelations_view_query").fadeOut(300);
		$(".axrelations_view_query").remove();
	});
	var loading_gif="<img id='axrelations_info_loading_gif' src="+buttons[3] +">";
	$(".axrelations_view_query #axrelations_info_main_div").append(loading_gif);
	d3.json("request.php?query_view="+encodeURIComponent(node_id)+"&sparql="+EP+"&inbound="+inbound+"&offset="+0+"&limit="+100,function(json){	// Downloads the list of query.
		$(".axrelations_view_query #axrelations_info_loading_gif").remove();
		if(!json)return;
		var output="";
		if(json.length!=0){
			if(inbound==1) output+="<table id='axrelations_query_header'><tr><th>Add to graph</th><th>Subject</th><th>Property</th></tr>";
			else output+="<table id='axrelations_query_header'><tr><th>Add to graph</th><th>Object</th><th>Property</th></tr>";
		}
		for(var j in json){
			var button="<button value='"+EP +"' node='"+json[j].subject +"' onclick=\"axrelations_append_node(event,'"+node_id+"','"+EP+"',"+inbound+",'"+json[j].subject+"','"+json[j].property+"','"+json[j].property_name+"','"+EP_name +"','"+json[j].subject_name +"')\" title='Add to the graph'>Add</button>"
			if(json[j].subject_name!=json[j].subject && json[j].property!=json[j].property_name)output+="<tr><td>"+button+"</td><td>"+json[j].subject_name+"  ("+json[j].subject +")</td><td>"+json[j].property_name+" ("+json[j].property+")</td>";
			else output+="<tr><td>"+button+"</td><td>"+json[j].subject +"</td><td>"+json[j].property+"</td>";
			output+="</tr>";
		}
		if(json.length!=0){
			if(number_of_elements>json.length)output+="<tr id='more_query_view'><td colspan='3' align='center' valign='middle'><div id=id='more_query_result' > Results: "+(100)+" / "+number_of_elements +"<button value='"+EP +"' node='"+node_id +"' onclick='axrelations_more_view(event,"+inbound+",100,"+(number_of_elements-100)+",\""+EP_name +"\")' title='Get more result' >More</button></div></td></tr>";//Adds the option other if 
			else output+="<tr id='more_query_view'><td colspan='3' align='center' valign='middle'><div id=id='more_query_result' >Results: "+number_of_elements+" / "+number_of_elements+"</div></td></tr></table>";
			output+="</table>";
		}
		if(output=="")output="<li><b>No results found</b></li>";
		$(".axrelations_view_query #axrelations_list").append(output);
	});
}
function axrelations_more_view(event,inbound,start,number_of_elements,EP_name){
	$("#more_query_view #more_query_result").remove();//Removes the button of the more.
	event = event || window.event //For IE
	var button_click=event.target;
	node_id=button_click.getAttribute('node');
	EP=button_click.value;
	var loading_gif="<img id='axrelations_info_loading_gif' src="+buttons[3] +">";
	$("#more_query_view td").prepend(loading_gif);
	d3.json("request.php?query_view="+encodeURIComponent(node_id)+"&sparql="+EP+"&inbound="+inbound+"&offset="+start+"&limit="+100,function(json){	// Downloads the list of query.
		$(".axrelations_view_query #axrelations_info_loading_gif").remove();
		if(!json)return;
		var output="";
		for(var j in json){
			var button="<button value='"+EP +"' node='"+json[j].subject +"' onclick=\"axrelations_append_node(event,'"+node_id+"','"+EP+"',"+inbound+",'"+json[j].subject+"','"+json[j].property+"','"+json[j].property_name+"','"+EP_name +"','"+json[j].subject_name +"')\" title='Add to the graph'>Add</button>"
			if(json[j].subject_name!=json[j].subject && json[j].property!=json[j].property_name&&json[j].subject_name!=null && json[j].property!=null)output+="<tr><td>"+button+"</td><td>"+json[j].subject_name+"  ("+json[j].subject +")</td><td>"+json[j].property_name+" ("+json[j].property+")</td>";
			else output+="<tr><td>"+button+"</td><td>"+json[j].subject +"</td><td>"+json[j].property+"</td>";
			output+="</tr>";
		}
		if(json.length!=0){
			if(number_of_elements>json.length) output+="<tr id='more_query_view'><td colspan='3' align='center' valign='middle'><div id=id='more_query_result' > Results: "+(start+100)+" / "+(number_of_elements+start) +"<button value='"+EP +"' node='"+node_id +"' onclick='axrelations_more_view(event,"+inbound+","+(start+100) +","+(number_of_elements-100)+",\""+EP_name +"\")' title='Get more result' >More</button></div></td></tr>";//Adds the option other if 
			else output+="<tr id='more_query_view'><td colspan='3' align='center' valign='middle'><div id='more_query_result'>Results: "+(number_of_elements+start)+" / "+(number_of_elements+start)+"</div></td></tr>";
			output+="</table>";
		}
		if(output=="")output="<li><b>No more results found</b></li>";
		var position=$("#more_query_view");
		var location=position.parent();
		position.remove();
		location.append(output);
	});
}

function axrelations_append_node(event,node_searched,EP_search,inbound,node_add,property,property_name,EP_name,node_label){
/* Appends the node selected in to the graph. event= ,node_searched= EP_search=, inbound, node_add=, property=,property_name=,EP_name=,node_label=
 */
	var add_info=Object();//Creates an object to pass through the function for eventually add a relation.
	add_info['node_searched']=node_searched;
	add_info['EP']=EP_search;
	add_info['inbound']=inbound;
	add_info['node_add']=node_add;
	add_info['property']=property;
	add_info['property_name']=property_name;
	
	event = event || window.event //For IE
	var button_click=event.target;
	if(button_click.className=='add_to_more'){add_info['for']='more';}//Specifies that the search is made for a more.
	node_id=button_click.getAttribute('node');
	EP=button_click.value;
	var node_present=axrelations_graph_addgraph(node_id,EP,add_info);
	// var node_present=nodes.filter(function(d){if(d.id==node_id)return d.index;});
	// if(node_present.length==0) node_present=false;
	// else node_present=true;
	var opt=$(".uri_added option");
	var present=opt.filter(function(d){//Checks if is present in the status.
		return opt[d].value==node_id;
	});
	if(!node_present || present.length==0){//If the node isn't already in the graph and isn't in the option, adds the option in the status panel.
		var label=""+node_label +"["+EP_name +"]";
		var option_to_add="<option value="+node_id +" title='URI:"+node_id +"  EndPoint:"+EP +"' >"+label +"</option>"; //Adds the uri requested to the list of uri called.
		$("select.uri_added").prepend(option_to_add);
		split_status()//Calls the function for put in two columns the option of the select item.
	}
	else{// recenters the graph on the node selected
		if(node_present){
			var j=nodes.filter(function(d){if(d.id==node_id)return d.index;});
			recenter_graph(j[0].index);
		}
		return;
	}
}
function add_singular_relation(add_info,prop_to_expand){//Adds the relations for the node.
	// console.log("add singular relation ");
	node_searched=nodes.filter(function(d){return d.id==add_info.node_searched})[0];
	node_add=nodes.filter(function(d){return d.id==add_info.node_add})[0];
	if(add_info.for!='more'){
		if(add_info.inbound==0){
			if(!prop_to_expand){//Adds the prop and the link.			
				var relation=Object();
				relation["elements"]=Array();
				relation["elements"].push(node_add);
				relation["img"]="";
				relation["isRelation"]=true;
				relation["explored"]=true;
				relation["id"]=count;
				relation["uri"]=add_info.property;
				relation["father"]=node_searched.name;
				relation["name"]=add_info.property_name;
				relation["type"]=add_info.property_name;
				relation["EP"]=endpoints_list.indexOf(add_info.EP);//Set the EndPoint of the relations
				relation.x=node_searched.x;
				relation.y=node_searched.y;
				count++;
				node_searched.relations=new Object();
				node_searched.relations[relation["uri"]]=relation;
				if(to_display(relation)["check"] && to_display_inverse(relation)){
					nodes.push(relation);
					links.push({source:node_searched , target:relation ,present:true}); 
					links.push({source: relation, target:node_add ,present:true}); 
				}
				if(node_searched.explored==false){//Adds a variable for differentiate this case of a singular relation from the case with multiple relations.
					node_searched["single_search"]=true;
				}
			}
			else{//Adds only the link.
				node_searched.relations[prop_to_expand.uri].elements.push(node_add);//Adds the node_add to the relations of the node XXXXXXXXXXXX Check
				if(to_display(prop_to_expand)["check"] && to_display_inverse(prop_to_expand)){				
					links.push({source: prop_to_expand, target:node_add ,present:true}); 
				}
			}
		}
		else{
			//Adds to the existent relations if is possible, else adds the relation.
			if(node_searched.relations[prop_to_expand.uri]){
				node_searched.relations[prop_to_expand.uri].elements.push(node_add);
				if(to_display(prop_to_expand)["check"] && to_display_inverse(prop_to_expand)){				
					links.push({source: prop_to_expand, target:node_add ,present:true});
				}
			}
			else{
				node_add.relations[prop_to_expand.uri].elements.push(node_searched);//Adds the node_add to the relations of the node XXXXXXXXXXXX Check
				if(to_display(prop_to_expand)["check"] && to_display_inverse(prop_to_expand)){				
					links.push({source: prop_to_expand, target:node_searched ,present:true});
				}
			}
		}
	}
	else{
		//Cases of add for more object.
		if(add_info.inbound==0){
			node_searched.relations[prop_to_expand.uri].elements.push(node_add);//Adds the node_add to the relations of the node XXXXXXXXXXXX Check
			if(to_display(prop_to_expand)["check"] && to_display_inverse(prop_to_expand)){				
				links.push({source: prop_to_expand, target:node_add ,present:true}); 
			}
		}
		else{
			// node_add.relations[prop_to_expand.uri].elements.push(node_searched);//Adds the node_add to the relations of the node XXXXXXXXXXXX Check
			node_searched.relations[prop_to_expand.uri].elements.push(node_add);
			if(to_display(prop_to_expand)["check"] && to_display_inverse(prop_to_expand)){				
				links.push({source: prop_to_expand, target:node_add ,present:true});			
			}
		}	
	}
	
	restart();
}
function axrelations_checks_description(){//Checks if the description inserted is correct.
	var new_graph_title=$("#axrelations_save_dialog #graph_save_title").val();
	if(new_graph_title.length>200){alert('Title too large!');return false;};
	// var new_graph_desc=$("#axrelations_save_dialog #graph_save_desc").val();	
	return true;
}
function axrelations_insert_description(){//Inserts the box for the description provided by the users.
	//Changes #axrelations_graph_toggle text with the title of the graph.
	$("#axrelations_graph_toggle").text(current_graph_title.replace(/\n/g,"</br>"));
	
	$("#graph_description_container").remove();
	var desc_box="<div id='graph_description_container'>";
	desc_box+="<div id='left_bracket' class='open'><p><font>DESCRIPTION</font></p></div>";
	desc_box+="<div id='graph_description_box'>";
	desc_box+="<div class='description_title'><h2>"+current_graph_title.replace(/\n/g,"</br>") +"</h2></div>";
	desc_box+="<div class='description_text'><p>"+current_graph_desc.replace(/\n/g,"</br>") +"</p></div>";
	desc_box+="</div>";
	desc_box+="</div>";
	$("#axrelations_graph_svg").before(desc_box);
	$("#graph_description_container #left_bracket").click(function(){
		$("#graph_description_box").toggle("fast");
		// $("#graph_description_box").toggle('slide',{ //Another type of effect.
            // direction: 'right'
        // },'fast');
		var class_desc=$("#graph_description_container #left_bracket").attr("class");
		if(class_desc=="open")$("#graph_description_container #left_bracket").attr("class","close");	
		else $("#graph_description_container #left_bracket").attr("class","open");
	});
}
function axrelations_show_inverse(hide){
	//TOCHECK if is necessary to save the operation on the stack.
	// if(save==true && add!="close"){
		// //Saves the operation on stack
		// if(stack_lifo.length==0){ //active the button
			// d3.select("#axrelations_undo").attr("disabled",null)
			// .attr("src",buttons[0]);
		// }
		// var operation=new Object;
		// operation.type="relation";
		// operation.relation=relation_button;
		// operation.add=!(add);
		// if(stack_lifo.length<50) stack_lifo.push(operation);
		// else{
			// stack_lifo.splice(0,1);
			// stack_lifo.push(operation);
		// }
	// }		
	if(!hide){//Re-adds the inverse relations. 
		var actual_number_of_elements=nodes.length;
		for(var i=0;i<actual_number_of_elements;i++){
			if(nodes[i].isRelation!=true && (nodes[i].explored==true || nodes[i].single_search)){ //Le relazioni sono gi� state caricate ed il nodo � stato esplorato.			
				for(var r in nodes[i].relations){ 
					if(nodes[i].relations[r].inbound==true && to_display(nodes[i].relations[r])["check"]==true){ //TOCHECK if is unchecked? It's not to add.
						nodes[i].relations[r]["isRelation"]=true;
						nodes[i].relations[r]["explored"]=true;
						if(nodes[i].relations[r].elements.length!=0){//Adds the relation only if it has some element.
							nodes.push(nodes[i].relations[r]);
							links.push({source: nodes[i], target: nodes[i].relations[r]});
							explores_relationship(nodes[i].relations[r],false,nodes[i].relations[r].EP);
							// axrelations_search_single_duplication(nodes[i],nodes[i].relations[r]);//Deletes the relation if is a duplication.
							axrelations_search_single_duplication_model(nodes[i],nodes[i].relations[r]);//Deletes the relation if is a duplication.
						}
					}
				}
			}
		}
	}
	else{//Deletes the nodes from the visualization.
		for(var i=0;i<nodes.length;i++){
			if(nodes[i].inbound==true){//Removes the node starting from the link with the father.
				for(var j=0;j<links.length;j++){
					if(links[j].target==nodes[i]){
						links.splice(j,1);
						break;//Breaks this cycle because a relation has only one father.
					}
				}
				element_elimination(nodes[i]);
				axrelations_graph_finder();
				i=0; //Restarts the count from 0 because the length of 'nodes' is changed.
			}
		}
	}
	restart();
}
function to_display_inverse(rel){//Returns true if the inverse relations have to been show.
	if(rel.inbound==true && $("#hide_inverse").attr("checked"))return false;
	return true;
}
function axrelations_inverse_showed(){//Checks if the inverse are shown in the graph, in this case returns true.
	for(var i=0;i<nodes.length;i++){
		if(nodes[i].inbound==true)return true;
	}
	return false;
}
function axrelations_sblock_nodes(){//Unfixes all the nodes except the roots.
	for(var i=0;i<nodes.length;i++){
		if(nodes[i].root!=true && nodes[i].fixed==true)delete nodes[i].fixed;
	}
	restart();
}
function axrelations_search_node_duplication(link_to_add){
	/*Returns true if the link passed is to add. Is to add if isn't a duplication. The following lines make this:
	-Checks if the relation(link_to_add.source), that point to the node(link_to_add.target), are present in the relations
	of the node. In that case, checks if is present in the elements of that relation, the father of link_to_add.source. 
	If is this the case, and the relation is explored, this function return false. A=>B'->C  C<=B"<-A  */
	// // if(!$("#hide_inverse").attr("checked")){ //If the inverse aren't shown, exits.
		// // node_explored=link_to_add.target;
		// // if(node_explored.relations){
			// // var uri_pointer=link_to_add.source.uri;//the uri of the node pointed. The uri of the relations
			// // if(node_explored.relations[uri_pointer]){//Checks if the specific relation is present.
				
				// // //if this relation points to the father of the link.source return false, the relations must not be shown.
				// // var current_relation=node_explored.relations[uri_pointer];
				// // var pointer=links.filter(function(d){return (d.target.id==link_to_add.source.id && d.target.uri==link_to_add.source.uri)});//returns the link between the relation passed and the father.
				// // var found=current_relation.elements.filter(function(d){return (d.id==pointer[0].source.id)});//Searches if the source  present the element 
				
				// // if (found.length!=0 ){
					// // r_current=nodes.filter(function(d){return (d.id==current_relation.id && d.uri==current_relation.uri);});//the node of current_relation in the graph.
					// // if(r_current.length!=0){//if is present.
						// // if(r_current[0].explored)return false;//if is explored.
					// // }
				// // } 
				
				// // else return true;					
				
			// // }			
		// // }		
	// // }
	// // return true;
	// console.log("axrelations_search_node_duplication");
	if(!$("#hide_inverse").attr("checked")){ //If the inverse aren't shown, exits.
		node_explored=link_to_add.target;// [A]
		if(node_explored.relations){//if it has relations
			var uri_pointer=link_to_add.source.uri;//(B")the uri of the node pointed. The uri of the relations
			if(node_explored.relations[uri_pointer]){//Checks if the specific relation is present.
				
				//if this relation points to the father of the link.source return false, the relations must not be shown.
				node_to_exp_visible_relations=//[A]=>(B')
				links.filter(function(d){return (d.source.id==node_explored.id && d.target.uri==uri_pointer && d.target.id!=link_to_add.source.id )});//prende la relazione/le relazioni (eventualmente diretta e inversa) che partono dal nodo puntato. Il controllo sull'id serve a non prendere l'inversa.
				if(node_to_exp_visible_relations.length!=0){
					for (var i in node_to_exp_visible_relations){ 
						// var current_relation=node_explored.relations[uri_pointer];
						var pointer=//(B")<=[C]
						links.filter(function(d){return (d.target.id==link_to_add.source.id && d.target.uri==link_to_add.source.uri)});//returns the link between the relation passed at the function and the father.
						var found=node_to_exp_visible_relations[i].target.elements.filter(function(d){return (d.id==pointer[0].source.id)});//Searches if the source of the passed link is present in the element of node_to_exp_visible_relations
						if (found.length!=0 ){
							/* The only case here that returns true is if there isn't already the link and if the link_to_add.source.id has id < of uri_pointer.
							Like: (B')---->[C] to add And: [A]===>(B')_____[C]  and [A]_____(B")<===[C] & B'.id < B".id then (B')---->[C] add.
							*/
							var link_already_existent=//[A]<---(B")
							links.filter(function(d){return(d.target.id==node_explored.id && d.source.uri==node_to_exp_visible_relations[i].target.uri && d.source.id==node_to_exp_visible_relations[i].target.id);});
							if(link_already_existent.length==0 && link_to_add.source.id < node_to_exp_visible_relations[i].target.id){return true;} 
							return false;							
							// 14-9-30 commented the three lines below because of duplications. The 2 if doesn't needed anymore
							// r_current=nodes.filter(function(d){return (d.id==node_to_exp_visible_relations[i].target.id && d.uri==node_to_exp_visible_relations[i].target.uri);});//the node of current_relation in the graph.TOCHECK now is necessary?
							// if(r_current.length!=0){//if is present.
								// if(r_current[0].explored)return false;//if is explored.
							// }
						}				
					}
				}
				else return true;				
			}			
		}		
	}
	return true;
}
function axrelations_search_relations_duplication(node_explored){
	/*Checks if the relations inbound are showed, in that case necessary checks if there are some duplications to delete.*/
	// console.log("Removes Duplications"+node_explored.id);
	if(!$("#hide_inverse").attr("checked")){
		//Checks if there are duplications, in that case deletes the last relation entered.
		if(node_explored.relations){
			var pointers=links.filter(function(d){return d.target.id==node_explored.id});//Keeps all the relations that have 'node_explored' as a target.
			for(var i=0;i<pointers.length;i++){//Searches if the relations is duplicated
				var uri_pointer=pointers[i].source.uri;
				if(node_explored.relations[uri_pointer]){
					/*If the relation is present, it's deleted from the visualization. If the relation to delete is direct,
					deletes also the node relation. if it's inbound delete only the last part or all the relation if there 
					is only one child.
					*/
					links_to_delete=links.filter(function(d){//Gets the link where source is the same relation as uri_pointer but fot node_to_explore.
						return (d.source.uri==node_explored.relations[uri_pointer].uri && d.source.id==node_explored.relations[uri_pointer].id);
					});
					if(links_to_delete.length==1 || links_to_delete.length==0){//Only one link or the link is alreasy been deleted
						var link_to_delete=links.filter(function(d){
							return (d.target.uri==node_explored.relations[uri_pointer].uri && d.target.id==node_explored.relations[uri_pointer].id);
						});
						var index_to_delete=links.indexOf(link_to_delete[0]);
						links.splice(index_to_delete,1);
					}
					else{//Multiple one link, deletes only the new link to the node already existent.
						var pointer_node=links.filter(function(d){return (d.target.uri==uri_pointer && d.target.id==pointers[i].source.id)});//Retrieves the element that point to the new one. In source of the link
						var link_to_delete=links_to_delete.filter(function(d){
							return (d.target.id==pointer_node[0].source.id)
						});
						var index_to_delete=links.indexOf(link_to_delete[0]);
						links.splice(index_to_delete,1);
					}
				}
			}
		}
		axrelations_graph_finder();
		restart();
	}
}
function axrelations_search_relations_duplication_model(node_explored,relations_added){//Searches in 'nodes' and not in 'links'. Based on displayed relations. 
	/*Checks if the relations inbound are showed, in that case necessary checks if there are some duplications to delete.
	With this functions are deleted also the relation if they are collapsed.*/
	// console.log("Removes Duplications Model for: "+node_explored.id);
	if(!$("#hide_inverse").attr("checked")){
		//Checks if there are duplications; in that case deletes the last relation entered.
		if(node_explored.relations){
			if(relations_added) var rel_to_check=relations_added;//Checks only in the relations passed.
			else var rel_to_check=node_explored.relations;//The relations potentially duplicated.
			for(var i in rel_to_check){
				var rel_same_uri=nodes.filter(function(d){
					if(d.isRelation && d.uri==rel_to_check[i].uri)return d;
				});//relations already present with the same uri.
				var counter=rel_to_check[i].elements.length;//Counts the number of element of rel_to_check[i] deleted. If count == number of elements in rel_to_check[i] than deletes rel_to_check[i] from the visualization.  
				//For each relation already present, checks if there is one duplicated (Is a duplications if has the node_explored in its elements).
				for(var j in rel_same_uri){
					//Checks if the relations has node_to_explore in its elements.
					if(rel_same_uri[j].elements.filter(function(d){return d.id==node_explored.id}).length!=0)
					{
						/* Checks if the father of 'rel_same_uri[j]' is one of the target of 'rel_to_check[i]' and deletes.
						Searchs with the name. */
						if(rel_to_check[i].elements.filter(function(d){return d.name==rel_same_uri[j].father}).length!=0){
							counter--;
							//Deletes the link if is present, and the relations if has no more links.
							if(rel_to_check[i].elements.length==1 || counter==0){
								//rel_to_check[i].elements.length==1 maybe it's used for prevent self loop(?).
								var link_to_delete=links.filter(function(d){//Takes the link where the relation to delete is the target, so is possible to remove the relation.
									return (d.target.uri==rel_to_check[i].uri && d.target.id==rel_to_check[i].id);
								});
								var index_to_delete=links.indexOf(link_to_delete[0]);
								if(index_to_delete>=0)links.splice(index_to_delete,1);
							}
							else{//Removes the single link.
								var link_to_delete=links.filter(function(d){
									return (d.source.id==rel_to_check[i].id && d.source.uri==rel_to_check[i].uri && d.target.name==rel_same_uri[j].father)
								});
								var index_to_delete=links.indexOf(link_to_delete[0]);
								if(index_to_delete>=0)links.splice(index_to_delete,1);
							}
							links_to_delete=links.filter(function(d){//Gets the link where source is the same relation as uri_pointer but fot node_to_explore.
								return (d.source.uri==rel_to_check[i].uri && d.source.id==rel_to_check[i].id);
							});
						}
					}
				}				
			}			
		}
		axrelations_graph_finder();
		restart();
	}
}
function axrelations_search_single_duplication_model(node_explored,relation_to_check){
	/*Checks if the relation inbound is showed, in that case checks if is a duplications to delete. Function based on the model.*/
	// console.log("Search single duplication Model: "+relation_to_check.id+"  del nodo"+node_explored.id);
	if(!$("#hide_inverse").attr("checked")){
		//Checks if there are duplications, in that case deletes the last relation entered.
		if(node_explored.relations && relation_to_check.isRelation){
			var pointers=nodes.filter(function(d){
				if(d.isRelation && d.uri==relation_to_check.uri && d.id!=relation_to_check.id )return d;
			});//relations already present with the same uri.
			// var pointers=links.filter(function(d){return d.target.id==node_explored.id});//Keeps all the relations that have 'node_explored' as a target.
			for(var i=0;i<pointers.length;i++){//Searches if the relations is duplicated
				var uri_pointer=pointers[i].uri;
				//If the relations has the father of relation_to_check in its elements, deletes the link and the relations if there aren't more links.
				if(pointers[i].elements.filter(function(d){return d.id==node_explored.id}).length!=0){					
					/*Checks if the father of 'rel_same_uri[j]' is one of the target of 'relation_to_check[i]' and deletes.
					Searchs with the name. */
					if(relation_to_check.elements.filter(function(d){return d.name==pointers[i].father}).length!=0){
						// counter--;
						//Deletes the link if is present, and the relations if has no more links.
						// if(relation_to_check.elements.length==1 || counter==0){
						if(relation_to_check.elements.length==1 ){
							var link_to_delete=links.filter(function(d){//Takes the link where the relation to delete is the target, so is possible to remove the relation.
								return (d.target.uri==relation_to_check.uri && d.target.id==relation_to_check.id);
							});
							var index_to_delete=links.indexOf(link_to_delete[0]);
							if(index_to_delete!=-1)links.splice(index_to_delete,1);//the control is for the case that the link is already been deleted
						}
						else{//Removes the single link.
							var link_to_delete=links.filter(function(d){
								return (d.source.id==relation_to_check.id && d.source.uri==relation_to_check.uri && d.target.name==pointers[i].father)
							});
							var index_to_delete=links.indexOf(link_to_delete[0]);
							if(index_to_delete!=-1)links.splice(index_to_delete,1);//the control is for the case that the link is already been deleted							
						}
						// links_to_delete=links.filter(function(d){//Gets the link where source is the same relation as uri_pointer but fot node_to_explore.
							// return (d.source.uri==relation_to_check.uri && d.source.id==relation_to_check.id);
						// });
					}
				}
			}
		}
		axrelations_graph_finder();
		restart();
	}
}
function axrelations_search_single_duplication(node_explored,relation_to_check){//Actually Not in use.
	/*Checks if the relation inbound is showed, in that case checks if is a duplications to delete.*/
	// console.log("Search single duplication: "+relation_to_check.id+"  del nodo"+node_explored.id);
	if(!$("#hide_inverse").attr("checked")){
		//Checks if there are duplications, in that case deletes the last relation entered.
		if(node_explored.relations && relation_to_check.isRelation){
			var pointers=links.filter(function(d){return d.target.id==node_explored.id});//Keeps all the relations that have 'node_explored' as a target.
			for(var i=0;i<pointers.length;i++){//Searches if the relations is duplicated
				if(pointers[i].source.id!=relation_to_check.id){//If is not the same relation.
					var uri_pointer=pointers[i].source.uri;
					if(uri_pointer==relation_to_check.uri){
						/*If the relation is 'relation_to_check', it's deleted from the visualization. If the relation to delete is direct,
						deletes also the node relation. if it's inbound delete only the last part or all the relation if there 
						is only one child.
						*/
						links_to_delete=links.filter(function(d){
							// return (d.source.uri==node_explored.relations[uri_pointer].uri && d.source.id==node_explored.relations[uri_pointer].id);//The links from the node_explore[relation].NB (important!) node_explore[relation] may not be 'relationto_check'.
							return (d.source.uri==relation_to_check.uri && d.source.id==relation_to_check.id);//The links from the relation_to_check.
						});
						if(links_to_delete.length==0){//The relation is empty. Deletes the link to the relations and the relations.
							link_to_delete=links.filter(function(d){//Finds the link between node_to_explore and the relation
								// return (d.target.uri==node_explored.relations[uri_pointer].uri && d.target.id==node_explored.relations[uri_pointer].id);
								return (d.target.uri==relation_to_check.uri && d.target.id==relation_to_check.id);
							});
							if(link_to_delete.length>0){
								// node_to_delete=nodes.filter(function(d){//Finds the node of the relation.
									// return d.id==link_to_delete[0].source.id;
								// });
								var index_to_delete=links.indexOf(link_to_delete[0]);
								links.splice(index_to_delete,1);
								// index_to_delete=nodes.indexOf(node_to_delete[0]);
								// nodes.splice(index_to_delete,1);
							}
							return;
						}
						if(links_to_delete.length==1 ){//Only one link 
							// var link_to_delete=links.filter(function(d){
								// return (d.target.uri==node_explored.relations[uri_pointer].uri && d.target.id==node_explored.relations[uri_pointer].id);
							// });
							var pointer_node=links.filter(function(d){return (d.target.uri==uri_pointer && d.target.id==pointers[i].source.id)});//Retrieves the element that point to the new one. In source of the link
							var link_to_delete=links_to_delete.filter(function(d){
								return (d.target.id==pointer_node[0].source.id)
							});
							if(link_to_delete.length>0){//Else, maybe the link was already deleted somewhere else.
								var index_to_delete=links.indexOf(link_to_delete[0]);
								links.splice(index_to_delete,1);
							}
						}
						else{//Multiple one link, deletes only the new link to the node already existent.
							var pointer_node=links.filter(function(d){return (d.target.uri==uri_pointer && d.target.id==pointers[i].source.id)});//Retrieves the element that point to the new one. In source of the link
							var link_to_delete=links_to_delete.filter(function(d){
								return (d.target.id==pointer_node[0].source.id)
							});
							if(link_to_delete.length>0){//Else, maybe the link was already deleted somewhere else.
								var index_to_delete=links.indexOf(link_to_delete[0]);
								links.splice(index_to_delete,1);
							}
						}
					}
				}
			}
		}
		axrelations_graph_finder();
		restart();
	}
}
function axrelations_close_all(){//Closes all the relations.
	for(var i=0;i<nodes.length;i++){
		if(nodes[i].isRelation==1 && nodes[i].explored==1 &&to_display_inverse(nodes[i]))explore(nodes[i],false);
	}
}
function axrelations_node_relations_status(node){
	/* Returns info about the relations of a node. If the node has to be explored returns 'to_explore'.
	If all the relation are explored return 'all_open', if all are closed returns 'all_closed'
	else returns 'mixed'.
	*/
	if(node.explored==false) return "to_explore";
	var status="none";
	for(var r in node.relations){
		if(to_display(node.relations[r])["check"] && to_display_inverse(node.relations[r])){//if the relation is displayed.
			if(node.relations[r].explored && (status=="none" || status=="all_open")) status="all_open";
			else if(node.relations[r].explored && status=="all_closed"){
				//First checks if the relations is present. Maybe it isn't because it was deleted.
				var visual_node=nodes.filter(function(d){return (d.id==node.relations[r].id && d.father==node.name)});
				if(visual_node.length>0){status="mixed";return status;}
			}			
			else if(!node.relations[r].explored && (status=="none" || status=="all_closed"))status="all_closed";
			else{
				//First checks if the relations is present. Maybe it isn't because it was deleted.
				var visual_node=nodes.filter(function(d){return (d.id==node.relations[r].id && d.father==node.name)});
				if(visual_node.length>0){status="mixed";return status;}
			}
		}
	}
	return status;
}
function axrelations_NavigationP_render(node,save){
	/*Modifies the pannel for navigation and returns the status of the node.*/
	var status=axrelations_node_relations_status(node);
	switch(status){
		case 'mixed':
		case 'all_closed':
			$("#axrelations_graph_navigation_panel_explore").css("display",'initial');
			if($("Body.Opera").length>0)$("#axrelations_graph_navigation_panel_explore").css("display",null);//display=initial doesn't work on Opera.
			$("#axrelations_graph_navigation_panel_reduce").css("display",'initial');
			if($("Body.Opera").length>0)$("#axrelations_graph_navigation_panel_reduce").css("display",null);//display=initial doesn't work on Opera.
			break;
		case 'all_open':
			$("#axrelations_graph_navigation_panel_explore").css("display","none");
			$("#axrelations_graph_navigation_panel_reduce").css("display",'initial');
			if($("Body.Opera").length>0)$("#axrelations_graph_navigation_panel_reduce").css("display",null);//display=initial doesn't work on Opera.
			break;
		case 'to_explore':
			$("#axrelations_graph_navigation_panel_explore").css("display",'initial');
			$("#axrelations_graph_navigation_panel_reduce").css("display","none");
			if($("Body.Opera").length>0)$("#axrelations_graph_navigation_panel_explore").css("display",null);//display=initial doesn't work on Opera.
			break;
	}
	$("#axrelations_graph_navigation_panel_explore").unbind('click').click(//Specifies another trigger.
		function(){explore(node,save,null,null,'more');});
	$("#axrelations_graph_navigation_panel_reduce").unbind('click').click(//Specifies another trigger.
		function(){explore(node,save,null,null,'less');});
	return status;
}
function node_close_all(relations){//Closes all the relations passed.
	for(var r in relations){
		if(relations[r].explored && to_display(relations[r])["check"]==true && to_display_inverse(relations[r])){
			explore(relations[r],false);
			//Changes the icon and the color at the circles.
			var circle=d3.select("circle#id"+relations[r].id);
			var img=d3.select("image#id"+relations[r].id);
			circle.attr("class",function(d){return "reduced"; });//Changes class for color
			if($("Body.Safari").length>0){//With Safari D3 has a sort of bug. It's not possible select img like above.
				if(circle[0][0]){
					var g_element=circle[0][0].parentNode;
					img=g_element.getElementsByTagName('image');
					img[0].setAttribute('class','reduced');
				}
			}
			else img.attr("class","reduced");//Changes image.
			// img.attr("xlink:href",function(d){return buttons[11];});//Changes image.	
		}
	}
	restart();
}
function node_open_all(node){//Opens all the relations displayed for 'node'.
	for(var r in node.relations){
		if(!node.relations[r].explored && to_display(node.relations[r])["check"]==true && to_display_inverse(node.relations[r])){
			/*Checks if the node has to be explored.*/
			view=nodes.filter(function(d){return (d.id==node.relations[r].id && d.uri==node.relations[r].uri )});
			if(view.length!=0){//If the node is present in the visualization, explores that.
				explore(node.relations[r],false);
				//Changes the icon and the color at the circles.
				var circle=d3.select("circle#id"+node.relations[r].id);
				var img=d3.select("image#id"+node.relations[r].id);
				circle.attr("class",function(d){return "expanded";});//Changes class for color
				if($("Body.Safari").length>0){//With Safari D3 has a sort of bug. It's not possible select img like above.
					if(circle[0][0]){
						var g_element=circle[0][0].parentNode;
						img=g_element.getElementsByTagName('image');
						img[0].setAttribute('class','expanded');
					}
				}
				else img.attr("class","expanded");//Changes image.
			}
		}
	}
	restart();
}
function axrelations_close_all_inverse(){//Closes the inverse relations. It's reduced them.
	for(var i=(nodes.length-1);i>=0;i--){//For inverse because maybe 
		if(nodes[i].isRelation==1 && nodes[i].explored==1 &&to_display_inverse(nodes[i]) && nodes[i].inbound==1){
			explore(nodes[i],false);
			if(i>nodes.length){i=nodes.length-1;}//TOCHECK Repositions the pointer. Maybe it's wrtong restart from the actual length.
			//Changes the icon and the color at the circles.
			var circle=d3.select("circle#id"+nodes[i].id);
			var img=d3.select("image#id"+nodes[i].id);
			circle.attr("class",function(d){return "reduced"; });//Changes class for color
			if($("Body.Safari").length>0){//With Safari D3 has a sort of bug. It's not possible select img like above.
				if(circle[0][0]){
					var g_element=circle[0][0].parentNode;
					img=g_element.getElementsByTagName('image');
					img[0].setAttribute('class','reduced');
				}
			}
			else img.attr("class","reduced");//Changes image.
		}
	}
	restart();
}
function axrelations_more_panels(node_to_explore,offset){
	/* Creates the more panel for view the results of a more object. 
	Parameters: node_to_explore,offset->for the query
	*/
	// $(".axrelations_view_more").remove();//Deletes the existent one
	var position="#axrelations_graph_div";//Place for the literals.
	// var info_div="<div id='axrelations_info' class='draggable axrelations_view_more axrelations_more_info' value='"+node_to_explore.id+"' ><div id='axrelations_info_title' >RESULT<a id='axr_view_close'>Close</a></div>";
	var count=num_more_dialog;
	num_more_dialog++;
	var info_div="<div id='axrelations_info' class='more_number_"+count+" draggable axrelations_view_more' value='"+node_to_explore.id+"' ><div id='axrelations_info_title' >RESULT<a id='axr_viewmore_close'>Close</a></div>";
	info_div+="<div id='axrelations_info_main_div'>";
	//Keyword search.
	if(node_to_explore.type!="more_LD"){
		info_div+="</br><p class='property_name'>Search with a keyword:</p><input id='search_more' type='text' name='more_keyword' autocomplete='off'><button class='more_search' id='axrelations_graph_more_search'>Search</button>";
	}
	else{
		info_div+="</br><p class='property_name'>Search with a keyword is not available for Linked Data. </br>For more results use the 'search' option on the parent node, or scroll the below results.</p>";
	}
	//Finds the link from relation to more.
	var link_to_more=links.filter(function(d){return (d.target.id==node_to_explore.id && d.target.type==node_to_explore.type);});
	var principal_node;
	if(link_to_more.length>0){
		link_to_more=link_to_more[0];
		//Finds the link to the relation from the node.
		var link_to_rel=links.filter(function(d){return (d.target.id==link_to_more.source.id && d.target.type==link_to_more.source.type);});
		if(link_to_rel.length>0){
			principal_node=link_to_rel[0].source;
		}
	}		
	// if(inbound==1) info_div+="<p class='property_name'>Sparql Query:</p>EndPoint:</br> "+EP+" </br></br>QUERY:</br> Select ?subject ?property </br> WHERE{?subject ?property &lt"+node_id +"&gt </br> .FILTER(isURI(?subject)) </br>} LIMIT 100";
	// else info_div+="<p class='property_name'>Sparql Query:</p>EndPoint:</br> "+EP+" </br></br>QUERY:</br> Select ?object ?property </br> WHERE{&lt"+node_id +"&gt ?property ?object </br> .FILTER(isURI(?object)) </br>} LIMIT 100";
	info_div+="<p class='property_name'>More result for "+node_to_explore.function_to_call+" </br> Of: "+principal_node.name +"</p>";
	info_div+="<div id='axrelations_list'></div>";
	info_div+="</div></div>";
	$(position).append(info_div);
	$(".more_number_"+count+" #axrelations_graph_more_search").click(function(e) {//Trigger for click on Search.
		$(".more_number_"+count+" #axrelations_info_loading_gif").remove();//Removes the loading gif.
		var loading_gif="<img id='axrelations_info_loading_gif' src="+buttons[3] +">";
		$(".more_number_"+count+" #axrelations_info_main_div").append(loading_gif);
		var key=$("#search_more").val();
		$(".more_number_"+count+" #axrelations_query_header").remove(); 
		$(".more_number_"+count+" #axrelations_list li").remove(); 
		d3.json("request.php?more_view="+node_to_explore.source+"&function="+encodeURIComponent(node_to_explore.function_to_call)+"&inbound="+node_to_explore.isInverse+"&type="+node_to_explore.type+"&sparql="+endpoints_list[node_to_explore.EP[0]]+"&offset="+offset+"&key="+key,function(json){	// Downloads the list of query.
			/* The query returns the number of elements and 100 elements per time. */
			$(".more_number_"+count+" #axrelations_info_loading_gif").remove();
			if(!json)return;
			var output="";
			var r_download='';//Results downloaded.
			if(json.length!=0){
				output+="<table id='axrelations_query_header'><tr><th>Add to graph</th><th>Object</th></tr>";
			}
			var inbound;
			if(node_to_explore.isInverse=='no')inbound=false;
			else inbound=true;
			var results=json.results;
			for(var j in results){
				var button="<button class='add_to_more' value='"+endpoints_list[node_to_explore.EP[0]] +"' node='"+results[j].subject +"' onclick=\"axrelations_append_node(event,'"+principal_node.id+"','"+endpoints_list[node_to_explore.EP[0]]+"',"+inbound+",'"+results[j].subject+"','"+node_to_explore.function_to_call+"','"+node_to_explore.function_to_call+"','"+endpoints_list[node_to_explore.EP[0]] +"','"+results[j].subject_name +"')\" title='Add to the graph'>Add</button>"
				if(results[j].subject_name!=results[j].subject)r_download+="<tr><td>"+button+"</td><td>"+results[j].subject_name+"  ("+results[j].subject +")</td>";
				else r_download+="<tr><td>"+button+"</td><td>"+results[j].subject +"</td>";
				r_download+="</tr>";
			}
			if(json.count!=0 && results){
				if(json.results.length<json.count)r_download+="<tr id='more_more_view'><td colspan='3' align='center' valign='middle'><div id=id='more_more_result' > Results: "+json.results.length+" / "+json.count +"<button value='"+endpoints_list[node_to_explore.EP[0]] +"' node='"+node_to_explore.id +"' onclick='axrelations_more_more(event,"+inbound+","+json.results.length+",\""+endpoints_list[node_to_explore.EP[0]] +"\",\""+node_to_explore.source+"\",\""+node_to_explore.function_to_call+"\",\""+node_to_explore.type+"\",\""+key+"\","+count+")' title='Get more results' >More</button></div></td></tr>";//Adds the option other if 
				else r_download+="<tr id='more_more_view'><td colspan='3' align='center' valign='middle'><div id=id='more_more_result' >Results: "+json.count+" / "+json.count+"</div></td></tr></table>";
				r_download+="</table>";
			}
			if(r_download=="")output="<li><b>No results found</b></li>";
			else output+=r_download;
			$(".more_number_"+count+" #axrelations_list").append(output);
		});		
	});
	$(".draggable").draggable({ handle: "#axrelations_info_title"});
	$(".draggable").resizable({minHeight: 400, minWidth:360});
	$(".more_number_"+count+" #axr_viewmore_close").click(function (e)
	{
		$(".more_number_"+count+"").fadeOut(300);
		$(".more_number_"+count+"").remove();
	});
	var loading_gif="<img id='axrelations_info_loading_gif' src="+buttons[3] +">";
	$(".more_number_"+count+" #axrelations_info_main_div").append(loading_gif);
	// var number_of_elements_displayed=100;//Limits the query results.
	d3.json("request.php?more_view="+node_to_explore.source+"&function="+encodeURIComponent(node_to_explore.function_to_call)+"&inbound="+node_to_explore.isInverse+"&type="+node_to_explore.type+"&sparql="+endpoints_list[node_to_explore.EP[0]]+"&offset="+offset,function(json){	// Downloads the list of query.
		/* The query returns the number of elements and 100 elements per time. */
		$(".more_number_"+count+" #axrelations_info_loading_gif").remove();
		if(!json)return;
		var output="";
		if(json.length!=0){
			output+="<table id='axrelations_query_header'><tr><th>Add to graph</th><th>Object</th></tr>";
		}
		var inbound;
		if(node_to_explore.isInverse=='no')inbound=false;
		else inbound=true;
		var results=json.results;
		for(var j in results){
			var button="<button class='add_to_more' value='"+endpoints_list[node_to_explore.EP[0]] +"' node='"+results[j].subject +"' onclick=\"axrelations_append_node(event,'"+principal_node.id+"','"+endpoints_list[node_to_explore.EP[0]]+"',"+inbound+",'"+results[j].subject+"','"+node_to_explore.function_to_call+"','"+node_to_explore.function_to_call+"','"+endpoints_list[node_to_explore.EP[0]] +"','"+results[j].subject_name +"')\" title='Add to the graph'>Add</button>"
			if(results[j].subject_name!=results[j].subject)output+="<tr><td>"+button+"</td><td>"+results[j].subject_name+"  ("+results[j].subject +")</td>";
			else output+="<tr><td>"+button+"</td><td>"+results[j].subject +"</td>";
			output+="</tr>";
		}
		if(json.count!=0){
			var key='';
			if(json.results.length<json.count)output+="<tr id='more_more_view'><td colspan='3' align='center' valign='middle'><div id=id='more_more_result' > Results: "+json.results.length+" / "+json.count +"<button value='"+endpoints_list[node_to_explore.EP[0]] +"' node='"+node_to_explore.id +"' onclick='axrelations_more_more(event,"+inbound+","+json.results.length+",\""+endpoints_list[node_to_explore.EP[0]] +"\",\""+node_to_explore.source+"\",\""+node_to_explore.function_to_call+"\",\""+node_to_explore.type+"\",\""+key+"\","+count+")' title='Get more results' >More</button></div></td></tr>";//Adds the option other if 
			else output+="<tr id='more_more_view'><td colspan='3' align='center' valign='middle'><div id=id='more_more_result' >Results: "+json.count+" / "+json.count+"</div></td></tr></table>";
			output+="</table>";
		}
		if(output=="")output="<li><b>No results found</b></li>";
		$(".more_number_"+count+" #axrelations_list").append(output);
	});
}

function axrelations_more_more(event,inbound,start,EP_name,source,function_to_call,type,key,count){
	/* 'count' is the windows number.*/
	$(".more_number_"+count+" #more_more_view #more_more_result").remove();//Removes the more button.
	event = event || window.event; //For IE
	var button_click=event.target;
	node_id=button_click.getAttribute('node');
	EP=button_click.value;
	var loading_gif="<img id='axrelations_info_loading_gif' src="+buttons[3] +">";
	$(".more_number_"+count+" #more_more_view td").prepend(loading_gif);
	// number_of_elements_displayed=100;
	//Searchs more results.
	
	d3.json("request.php?more_view="+source+"&function="+encodeURIComponent(function_to_call)+"&inbound="+inbound+"&type="+type+"&sparql="+EP+"&offset="+start+"&key="+key,function(json){	// Downloads the list of query.
		$(".more_number_"+count+" .axrelations_view_query #axrelations_info_loading_gif").remove();
		if(!json)return;
		//Finds the link from relation to more.
		var link_to_more=links.filter(function(d){return (d.target.id==node_id && d.target.type==type);});
		var principal_node;
		if(link_to_more.length>0){
			link_to_more=link_to_more[0];
			//Finds the link to the relation from the node.
			var link_to_rel=links.filter(function(d){return (d.target.id==link_to_more.source.id && d.target.type==link_to_more.source.type);});
			if(link_to_rel.length>0){
				principal_node=link_to_rel[0].source;
			}
		}	
		var output="";
		var results=json.results;
		for(var j in results){
			var button="<button class='add_to_more' value='"+EP +"' node='"+results[j].subject +"' onclick=\"axrelations_append_node(event,'"+principal_node.id+"','"+EP+"',"+inbound+",'"+results[j].subject+"','"+function_to_call+"','"+function_to_call+"','"+EP +"','"+results[j].subject_name +"')\" title='Add to the graph'>Add</button>"
			if(results[j].subject_name!=results[j].subject)output+="<tr><td>"+button+"</td><td>"+results[j].subject_name+"  ("+results[j].subject +")</td>";
			else output+="<tr><td>"+button+"</td><td>"+results[j].subject +"</td>";
			output+="</tr>";
		}
		start=start+json.results.length;
		if(json.length!=0){
			if(start<json.count) output+="<tr id='more_more_view'><td colspan='3' align='center' valign='middle'><div id=id='more_more_result' > Results: "+start+" / "+json.count +"<button value='"+EP +"' node='"+node_id +"' onclick='axrelations_more_more(event,"+inbound+","+start +",\""+EP_name +"\",\""+source+"\",\""+function_to_call+"\",\""+type+"\",\""+key+"\","+count+")' title='Get more result' >More</button></div></td></tr>";//Adds the option other if 
			else output+="<tr id='more_more_view'><td colspan='3' align='center' valign='middle'><div id='more_more_result'>Results: "+start+" / "+json.count+"</div></td></tr>";
			output+="</table>";
		}
		if(output=="")output="<li><b>No more results found</b></li>";
		var position=$(".more_number_"+count+" #more_more_view");
		var location=position.parent();
		position.remove();
		location.append(output);
	});
}

function axrelations_save_status(op_type,node){
	if(stack_lifo.length==0){ //actives the button
		d3.select("#axrelations_undo").attr("disabled",null)
		.attr("src",buttons[0]);
	}
	var operation=new Object;
	switch(op_type){
		case 'explore':
			operation.type="explore";
			operation.node=node;
		case 'reduce':
			operation.type="reduce";
			operation.node=new Object;
			for(var p in node){ //duplicates the element and all attributes
				operation.node[p]=node[p];
			}
			operation.nodes=Array();
			for(var i=0;i<nodes.length;i++)operation.nodes[i]=nodes[i];
			operation.links=Array();
			for(var i=0;i<links.length;i++)operation.links[i]=links[i];
			break;
		case 'close_all':
			operation.type="close_all";
			operation.node=node;
			break;
		case 'open_all':
			operation.type="open_all";
			operation.node=node;
			break;
		case 'node_remove':
			operation.type="node_remove";
		case 'focus':
			if(!operation.type)operation.type="focus";
			operation.node=new Object;
			for(var p in node){ //duplicates the attributes of the current element
				operation.node[p]=node[p];
			}
			operation.nodes=Array();
			for(var i=0;i<nodes.length;i++)operation.nodes[i]=nodes[i];
			operation.links=Array();
			for(var i=0;i<links.length;i++)operation.links[i]=links[i];
			//Saves the status messages.
			operation.status=get_status_options();
			break;			
	}
	if(op_type=="reduce"){
	}
	
	if(stack_lifo.length<50) stack_lifo.push(operation);
	else{
		stack_lifo.splice(0,1);
		stack_lifo.push(operation);
	}
	return true;
}
//TOCHECK check if the functions 'to_display_inverse' and 'function to_display' could be joint.

function wait_button_load(count,id,proced){
/* Function that waits until the configuration has been loaded. count= the try number. id= the id of a specific configuration. 
proced=indicates if proceed with retrieve a configuration or not  */
	time=5;//5 ms
	if(count<1000 && !buttons[11]){//Waits max 1000*time for the loading of the button icons. With time=5, it waits max 5 sec.
		count++;
		setTimeout(function(){wait_button_load(count,id,proced)},time);
	}
	else{
		if(proced)retrieve_configuration(id); //Loads the configuration.
	} 
}
function save_handler(){
	/* Creates the window that handle the save case.*/
	var position="body";
	var dialog="<div id='axrelations_save_dialog' title='Save Configuration'><div id='axrelations_info_title' >Save your status.<a id='axr_save_close' title='Close without save'>Close</a></div>";
	if(save_operation=="write"){	
		dialog+="<div id='axrelations_save_main_div'><p>Would you overwrite the previous version? This will allow you to access at your linked open graph with the same link you accessed it.</p>";
		dialog+="<input type='button' id='ax_save_overwrite' value='Yes'>";
		dialog+="<input type='button' id='ax_save_normally' value='No'></div>";
	}
	else{
		dialog+="<div id='axrelations_save_main_div'>";
		dialog+="<p>You can save the status of your Linked Open Graph. Please insert a valid e-mail, and you will receive a link that could allow you to access at the LOG and share it with your friends.</p>";
		dialog+="<p>Insert your e-mail:</p>";
		dialog+="<input type='email' id='user_email' name='email' autocomplete='on' placeholder='email@domain.ext'>";
		if(user_mail!=""){
			dialog+="<p>Please pay attention that, the Linked Open Graph ";
			dialog+="saved is distinct with respect to the original LOG modified. You will receive the url link to access at the new LOG from email.</p>";				
			
		}
		//Inserts a testbox for the title and a text area for the description.
		dialog+="</br>Insert a title for the graph: </br><input type='text' id='graph_save_title' placeholder='LOG title'></br> Insert a description for the graph: </br><textarea id='graph_save_desc' placeholder='Insert a description'></textarea>";
		dialog+="<input type='button' id='checkmail' value='Send'>";
		dialog+="</div>";
	}
	dialog+="</div>";
	$(position).append(dialog);
	if(save_operation=="write")$("#axrelations_save_dialog").attr("class","mini");
	// if(user_mail!="")$("#axrelations_save_dialog").attr("class","resave");
	$("#overlay").show();
	$("#axrelations_save_dialog #axr_save_close").click(function (e)
	{
		$("#axrelations_save_dialog").fadeOut(300);
		$("#axrelations_save_dialog").remove();
		$("#overlay").hide();
	});
	//Defines some trigger.
	$("#axrelations_save_dialog #ax_save_overwrite").click(function(){//Overwrites the previous save
		$("#axrelations_save_dialog").attr("class","resave");
		$("#axrelations_save_dialog #axrelations_save_main_div").remove();
		dialog="<div id='axrelations_save_main_div'>";
		dialog+="<p>If you want you can change the title and the description of the current save.";
		dialog+="</br>Title of the graph: </br><input type='text' id='graph_save_title' placeholder='LOG title'></br> Description of the graph: </br><textarea id='graph_save_desc' placeholder='Insert a description'>"+current_graph_desc +"</textarea>";
		dialog+="<input type='button' id='proceed' value='Proceed'>";
		dialog+="</div>";
		$("#axrelations_save_dialog").append(dialog);
		$("#axrelations_save_dialog #graph_save_title").val(current_graph_title);
		$("#axrelations_save_dialog #proceed").click(function(d){ //Defines the function for check the email and save the configuration.
			if(axrelations_checks_description()==false) return;
			$("#axrelations_save_dialog").attr("class","small");
			save_current_configuration(user_mail,true);//Saves the configuration and send the email to the user.
		});					
	});
	$("#axrelations_save_dialog #ax_save_normally").click(function(){
		$("#axrelations_save_dialog").attr("class","resave");
		$("#axrelations_save_dialog #axrelations_save_main_div").remove();
		dialog="<div id='axrelations_save_main_div'>";
		dialog+="<p>You can save the status of your Linked Open Graph. Please insert a valid e-mail, and you will receive a link that could allow you to access at the LOG and share it with your friends</p>";
		dialog+="<p>Insert your e-mail:</p>";
		
		// dialog+="<form autocomplete='on'>";
		dialog+="<input type='email' id='user_email' name='email' autocomplete='on' placeholder='email@domain.ext'>";
		dialog+="</br>Insert a title for the graph: </br><input type='text' id='graph_save_title' placeholder='LOG title'></br> Insert a description for the graph: </br><textarea id='graph_save_desc' placeholder='Insert a description'></textarea>";
		dialog+="<input type='button' id='checkmail' value='Send'>";
		// dialog+="</form>";
		dialog+="<p> The Linked Open Graph saved will be distinct with respect to the original LOG modified. ";
		dialog+="You will receive the url link to access at the new LOG from email. </p>";				
		dialog+="</div>";
		$("#axrelations_save_dialog")
		.attr("class","resave")
		.append(dialog);
		
		$("#axrelations_save_dialog #checkmail").click(function(d){ //Defines the function for check the email and save the configuration.
			var email=$("#axrelations_save_dialog #user_email")[0];
			//Checks if the email is correct.
			var filter = /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
			if (!filter.test(email.value)) {
				alert('Please provide a valid email address!');
				return;
			}
			else{
				if(axrelations_checks_description()==false) return;
				save_current_configuration(email.value,false);//Saves the configuration and send the email to the user.
			}
		});				
	});
	$("#axrelations_save_dialog #checkmail").click(function(d){ //Defines the function for check the email and save the configuration.
		var email=$("#axrelations_save_dialog #user_email")[0];
		//Checks if the email is correct.
		var filter = /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
		if (!filter.test(email.value)) {
			alert('Please provide a valid email address!');
			return;
		}
		else{
			if(axrelations_checks_description()==false) return;
			save_current_configuration(email.value,false);//Saves the configuration and send the email to the user.				
		}
	});
}
function axrelations_share(current_node) {
	/* Provides a dialog for share the graph. Share with: -Embed. */
	var position="body";
	var dialog="<div id='axrelations_share_dialog' title='Embed the Graph'><div id='axrelations_info_title' >Embed your Graph.<a id='axr_share_close' title='Close'>Close</a></div>";
	dialog+="<div id='axrelations_share_main_div'>";
	if(!current_node){
		if(!save_r)dialog+="<p><b>Link for embed the initial LOG:</b></p>";
		else dialog+="<p>Link for embed the last save of the LOG:</p>";
	}
	else dialog+="<p>Link for embed '"+current_node.name+"'  LOG:</p>";
	dialog+="<textarea id='axr_embed_link' rows=8 cols=40 onclick='this.select()' spellcheck='false'></textarea></br>";
	dialog+="<p>Show Controls:<input id='axr_embed_controls' type='checkbox'>";
	dialog+="&nbsp;&nbsp;&nbsp;Show Info:<input id='axr_embed_info' type='checkbox'>";
	dialog+="&nbsp;&nbsp;&nbsp;Show Description:<input id='axr_embed_description' type='checkbox'></p>";
	
	dialog+="<div class=\"share_left\"> <b>iFrame dimensions:</b>"
	
	if($("body.Chrome").length>0) dialog+="<p>Width:&nbsp;<input id='axr_embed_width' type='text' value='800' onkeypress='return event.charCode >= 48 && event.charCode <= 57'></p>";
	else dialog+="<p>Width:<input id='axr_embed_width' type='text' value='800'></p>";		
	if($("body.Chrome").length>0)dialog+="<p>Height:<input id='axr_embed_height' type='text' value='500' onkeypress='return event.charCode >= 48 && event.charCode <= 57'></p>";
	else dialog+="<p>Height:<input id='axr_embed_height' type='text' value='500' ></p>";
	dialog+="<p>Border:<input id='axr_embed_border' type='checkbox' checked='true' ></p>";
	
	dialog+="</div><div class=\"share_right\"><b>Graph options:</b>";
	
	dialog+="<p>Scale:&nbsp;&nbsp;<input id='axr_embed_scale' type='text' value='0.7' > </br>( Insert a number between 0.3 and 6 ) </p>";
	dialog+="<b>Moves the graph</b> <p>x:&nbsp;&nbsp;<input id='axr_embed_trX' type='text' value='0' > </br> </p>";
	dialog+="<p>y:&nbsp;&nbsp;<input id='axr_embed_trY' type='text' value='0' > </br> </p>";
	dialog+="</div>";
	dialog+="<p><button id='embed_preview' style='margin-left:35%; height:35px; width:100px;'>Preview </button></p>";
	if(!current_node && !save_r)dialog+="<p>If you want to embed the actual configuration, first save the LOG. For save click <a  href='' id='link_to_save' >HERE!</a></p>"
	if(!current_node)dialog+="<p>If you want to embed only one element of LOG just click on the node and select 'embed'.</p>";
	dialog+="</div></div>";
	$(position).append(dialog);
	//Some permission.
	$("#axr_embed_width").keypress(function (event) {
		// Allow: backspace, delete, tab, escape, enter and .
		event = window.event ? event.keyCode : event.which;
		if ($.inArray(event.keyCode, [46, 8, 9, 27, 13, 110, 190]) !== -1 ||
			 // Allow: Ctrl+A
			(event.keyCode == 65 && event.ctrlKey === true) || 
			 // Allow: home, end, left, right
			(event.keyCode >= 35 && event.keyCode <= 39)) {
				 // let it happen, don't do anything
				 return;
		}
		// Ensure that it is a number and stop the keypress
		if ((event.shiftKey || (event.keyCode < 48 || event.keyCode > 57)) && (event.keyCode < 96 || event.keyCode > 105)) {
			event.preventDefault();
		}
	});
	$("#axr_embed_scale").keypress(function (event) {
		// Allow: backspace, delete, tab, escape, enter and .
		event = window.event ? event.keyCode : event.which;
		if ($.inArray(event.keyCode, [46, 8, 9, 27, 13, 110, 190]) !== -1 ||
			 // Allow: Ctrl+A
			(event.keyCode == 65 && event.ctrlKey === true) || 
			 // Allow: home, end, left, right
			(event.keyCode >= 35 && event.keyCode <= 39)) {
				 // let it happen, don't do anything
				 return;
		}
		// Ensure that it is a number and stop the keypress
		if ((event.shiftKey || (event.keyCode < 48 || event.keyCode > 57)) && (event.keyCode < 96 || event.keyCode > 105)) {
			event.preventDefault();
		}
	});
	$("#axr_embed_trX").keypress(function (event) {
		// Allow: backspace, delete, tab, escape, enter and .
		event = window.event ? event.keyCode : event.which;
		if ($.inArray(event.keyCode, [46, 8, 9, 27, 13, 110, 190]) !== -1 ||
			 // Allow: Ctrl+A
			(event.keyCode == 65 && event.ctrlKey === true) || 
			 // Allow: home, end, left, right
			(event.keyCode >= 35 && event.keyCode <= 39)) {
				 // let it happen, don't do anything
				 return;
		}
		// Ensure that it is a number and stop the keypress
		if ((event.shiftKey || (event.keyCode < 48 || event.keyCode > 57)) && (event.keyCode < 96 || event.keyCode > 105)) {
			event.preventDefault();
		}
	});
	$("#axr_embed_trY").keypress(function (event) {
		// Allow: backspace, delete, tab, escape, enter and .
		event = window.event ? event.keyCode : event.which;
		if ($.inArray(event.keyCode, [46, 8, 9, 27, 13, 110, 190]) !== -1 ||
			 // Allow: Ctrl+A
			(event.keyCode == 65 && event.ctrlKey === true) || 
			 // Allow: home, end, left, right
			(event.keyCode >= 35 && event.keyCode <= 39)) {
				 // let it happen, don't do anything
				 return;
		}
		// Ensure that it is a number and stop the keypress
		if ((event.shiftKey || (event.keyCode < 48 || event.keyCode > 57)) && (event.keyCode < 96 || event.keyCode > 105)) {
			event.preventDefault();
		}
	});
	$("#axr_embed_height").keydown(function (e) {
		// Allow: backspace, delete, tab, escape, enter and .
		if ($.inArray(e.keyCode, [46, 8, 9, 27, 13, 110, 190]) !== -1 ||
			 // Allow: Ctrl+A
			(e.keyCode == 65 && e.ctrlKey === true) || 
			 // Allow: home, end, left, right
			(e.keyCode >= 35 && e.keyCode <= 39)) {
				 // let it happen, don't do anything
				 return;
		}
		// Ensure that it is a number and stop the keypress
		if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
			e.preventDefault();
		}
	});
	//Some other function, like close etc.
	$("#axr_embed_width").change(function(){
		if($("#axr_embed_width").attr("value")!='')new_value="width=\""+$("#axr_embed_width").attr("value")+"\"";
		else new_value="width=\"800\""; 
		embed_code=$("#axr_embed_link").val();
		part1=embed_code.substr(0,embed_code.indexOf("width"));
		part2=embed_code.substr(embed_code.indexOf("height"));
		embed_code=part1+new_value+" "+part2;
		$("#axr_embed_link").val(embed_code);
	});
	$("#axr_embed_height").change(function(){
		if($("#axr_embed_height").attr("value")!='')new_value="height=\""+$("#axr_embed_height").attr("value")+"\"";
		else new_value="height=\"500\""; 
		embed_code=$("#axr_embed_link").val();
		part1=embed_code.substr(0,embed_code.indexOf("height"));
		part2=embed_code.substr(embed_code.indexOf("src"));
		embed_code=part1+new_value+" "+part2;
		$("#axr_embed_link").val(embed_code);
	});
	$("#axr_embed_border").change(function(){
		val_bord="0";
		if(this.checked)val_bord="1";
		new_value="frameborder=\""+val_bord+"\">";
		
		embed_code=$("#axr_embed_link").val();
		part1=embed_code.substr(0,embed_code.indexOf("frameborder"));
		part2=embed_code.substr(embed_code.indexOf("</iframe>"));
		embed_code=part1+new_value+part2;
		$("#axr_embed_link").val(embed_code);
	});
	$("#axr_embed_scale").change(function(){
		if($("#axr_embed_scale").attr("value")!='')new_value="scale=("+$("#axr_embed_scale").attr("value")+")\" ";
		else new_value="scale=(0.7)\" "; 
		embed_code=$("#axr_embed_link").val();
		part1=embed_code.substr(0,embed_code.indexOf("scale"));
		part2=embed_code.substr(embed_code.indexOf("frameborder"));
		embed_code=part1+new_value+" "+part2;
		$("#axr_embed_link").val(embed_code);
	});
	$("#axr_embed_trX").change(function(){
		x_value=0;y_value=0;
		if($("#axr_embed_trX").attr("value")!='') x_value=$("#axr_embed_trX").attr("value");
		if($("#axr_embed_trY").attr("value")!='') y_value=$("#axr_embed_trY").attr("value");
		new_value="translate=["+x_value+","+y_value+"]";
		embed_code=$("#axr_embed_link").val();
		part1=embed_code.substr(0,embed_code.indexOf("translate"));
		part2=embed_code.substr(embed_code.indexOf("&scale"));
		embed_code=part1+new_value+part2;
		$("#axr_embed_link").val(embed_code);
	});
	$("#axr_embed_trY").change(function(){
		x_value=0;y_value=0;
		if($("#axr_embed_trX").attr("value")!='') x_value=$("#axr_embed_trX").attr("value");
		if($("#axr_embed_trY").attr("value")!='') y_value=$("#axr_embed_trY").attr("value");
		new_value="translate=["+x_value+","+y_value+"]";
		embed_code=$("#axr_embed_link").val();
		part1=embed_code.substr(0,embed_code.indexOf("translate"));
		part2=embed_code.substr(embed_code.indexOf("&scale"));
		embed_code=part1+new_value+part2;
		$("#axr_embed_link").val(embed_code);
	});
	//Embed preview.
	$("#embed_preview").click(function(){
		var w = screen.width*3/4;
		var h = screen.height*3/4;
		var l = Math.floor((w/4)/2);
		var t = Math.floor((h/4)/2);
		var iframe_html=$("#axr_embed_link").val();
		var newPage_content="<html><title>iFrame Preview</title><head></head><body><h1>iFrame Preview</h1><h3>Linked Open Graph</h3> \
		<p>"+iframe_html +"</p>\
		</body></html>";
		var newWindow = window.open("","","width=" + w + ",height=" + h + ",top=" + t + ",left=" + l);
		newWindow.document.write(newPage_content);
	});
	$("#link_to_save").click(function(){
		$('#axrelations_share_dialog').fadeOut(300);
		$('#axrelations_share_dialog').remove();
		save_handler();
		return false;
	});
	$("#overlay").show();
	$("#axr_share_close").click(function (e)
	{
		$("#axrelations_share_dialog").fadeOut(300);
		$("#axrelations_share_dialog").remove();
		$("#overlay").hide();
	});
	$("#axr_embed_controls").change(function (e)
	{//Changes the value in the textarea for the controls
		new_value="&controls=";
		if(this.checked)new_value+="true";
		else new_value+="false";
		embed_code=$("#axr_embed_link").val();
		part1=embed_code.substr(0,embed_code.indexOf("&controls"));
		var fin=embed_code.indexOf("&",embed_code.indexOf("controls"));
		part2="";
		if(fin>0)part2=embed_code.substr(fin);
		embed_code=part1+new_value+part2;
		$("#axr_embed_link").val(embed_code);
	});
	$("#axr_embed_description").change(function (e)
	{//Changes the value in the textarea for the description
		new_value="&description=";
		if(this.checked)new_value+="true";
		else new_value+="false";
		embed_code=$("#axr_embed_link").val();
		part1=embed_code.substr(0,embed_code.indexOf("&description"));
		var fin=embed_code.indexOf("&",embed_code.indexOf("description"));
		part2="";
		if(fin>0)part2=embed_code.substr(fin);
		embed_code=part1+new_value+part2;
		$("#axr_embed_link").val(embed_code);
	});
	$("#axr_embed_info").change(function (e)
	{//Changes the value in the textarea for the info
		new_value="&info=";
		if(this.checked)new_value+="true";
		else new_value+="false";
		embed_code=$("#axr_embed_link").val();
		part1=embed_code.substr(0,embed_code.indexOf("&info"));
		var fin=embed_code.indexOf("&",embed_code.indexOf("info"));
		part2="";
		if(fin>0)part2=embed_code.substr(fin);
		embed_code=part1+new_value+part2;
		$("#axr_embed_link").val(embed_code);
	});
	//Sets the URL.
	var main_url=document.URL;
	if(save_r){//If the graph is saved, link to that.
		//Insert the id and the Ep in place of the current one.
		//Checks if is the link of a graph or of a search.
		main_url=window.location.protocol+"//"+window.location.host+window.location.pathname;
		main_url+="?graph="+save_r;//Change this!!
	}
	if(current_node){
		//Insert the id and the Ep in place of the current one.
		//Checks if is the link of a graph or of a search.
		main_url=window.location.protocol+"//"+window.location.host+window.location.pathname;
		main_url+="?uri="+encodeURIComponent(current_node.id)+"&sparql="+endpoints_list[current_node.EP[0]];
	}
	//Retrieves the value of multicheck.
	var multi_ep_value="&multiple_search=true";
	// var multi_ep_opt=$("#log_options #multiple_ep");
	var multi_ep_opt=$(".endpoint.active #multiple_ep");
	if(multi_ep_opt.length>0) multi_ep_value="&multiple_search="+multi_ep_opt.attr("checked");//Sets the value for multiple endpoints
	
	var url_to_embed=main_url+"&embed"+multi_ep_value +"&controls=false&description=false&info=false&translate=[0,0]&scale=(0.7)";
	var embed_code="<iframe width=\"800\" height=\"500\" src=\""+url_to_embed+"\"  frameborder=\"1\"></iframe>";
	$("#axr_embed_link").val(embed_code);	
}
function axrelations_servicemap(){
	/* Redirects to  km4city36 for show all the element with a place */
	/* Creates the dialog*/
	var position="body";
	var dialog="<div id='axrelations_servicemap_dialog' title='LOG Service Map'><div id='axrelations_info_title' >View the places on Service Map.<a id='axr_servicemap_close' title='Close'>Close</a></div>";
	dialog+="<div id='axrelations_servicemap_main_div'>";
	
	dialog+="&nbsp;&nbsp;&nbsp;<b>Opening this page close the "+sg_title+" and will open \"Service Map\" for display the places found!</b>";
	
	dialog+="<div id='show_results'><div id='loading_gif'> Searching for places... </br><img id='axrelations_info_loading_gif' src="+buttons[3] +"></div>";
	dialog+="</div>";
	dialog+="<div class='user_options'>";
	dialog+="<p><button id='axr_servicemap_forward' style='margin-left:10%;' disabled>Accept </button>";
	dialog+="<button id='axr_servicemap_close2' >Close </button></p>";
	dialog+="</div></div>";
	dialog+="</div>";
	$(position).append(dialog);
	
	$("#overlay").show();
	
	$("#axrelations_servicemap_dialog #axr_servicemap_close, #axrelations_servicemap_dialog #axr_servicemap_close2").click(function(e){
		//Removes the window
		$("#overlay").hide();
		$("#axrelations_servicemap_dialog").remove();
	});
	//Checks if the nodes have coordinates.
	// console.log("Redirects to SiiMobility.");
	var elements_to_check=new Array;
	var j=0;
	for(var i=0;i<nodes.length;i++){
		if(!nodes[i].isRelation && ( nodes[i].type=="uri" || nodes[i].type=="LD" )){
			var new_o=new Object;
			new_o.uri=encodeURIComponent(nodes[i].id);
			new_o.ep=encodeURIComponent(endpoints_list[nodes[i].EP[0]]);//makes a search for the first EP.
			if(endpoints_list[nodes[i].EP[0]]=="LD") new_o.ep=encodeURIComponent("http://192.168.0.205:8080/openrdf-sesame/repositories/km4city36"); //Makes a try on ServiceMap
			elements_to_check[j]=new_o;
			j++;
		}
	}
	// console.log("Elements to be checked: "+elements_to_check);
	// console.log(elements_to_check);
	
	$.post("includes/ServiceMap_interface.php",{'elements':JSON.stringify(elements_to_check)},function(data){
	// $.get("includes/ServiceMap_interface.php",{'elements':JSON.stringify(elements_to_check)},function(data){
		// console.log(" Result returned ");
		// console.log(data);
		//Creates the table for with the results.
		var table='<table style="width: 90%;" cellpadding="3" cellspacing="0"><tr><th class="first_column">Result</th><th> Name </th><th>Uri</th></tr></table>';
		$("#axrelations_servicemap_dialog #show_results").append(table);
		//Displays the results
		if(data.error){$("#axrelations_servicemap_dialog #show_results table").append("<tr><td colspan='3'>No results found!</td></tr>");}
		else{
			var results="";
			for(var i=0;i<data.length;i++){
				var name="No name";
				var filter = nodes.filter(function(d){
					return d.id==decodeURIComponent(data[i].uri);
				});
				if(filter.length>0){ name=filter[0].name; data[i].name=name;}
				results+="<tr><td>"+(i+1)+"</td><td>"+name+"</td><td>"+data[i].uri+"</td></tr>";					
			}
			$("#axrelations_servicemap_dialog #show_results table").append(results);
			$("#axrelations_servicemap_dialog button#axr_servicemap_forward").attr("disabled","");
		}
		$("#axrelations_servicemap_dialog #show_results #loading_gif").remove();	
		console.log(data);
		//Enables the 'Accept' button and set the href.
	},'json');	
	
}

function getSelectedOptions(sel, fn) {
	var all_selected = [];
	var opt2;

	//console.log(sel)

	// loop through options in select list
	for (var i = 0, len = sel.options.length; i < len; i++) {
		opt2 = sel.options[i];

		// check if selected
		if (opt2.selected) {
			// add to array of option elements to return from this function
			all_selected.push(opt2);
            // invoke optional callback function if provided
			if (fn) {
				fn(opt2);
			}
		}
	}

	// return array containing references to selected option elements
	return all_selected;
}

// example callback function (selected options passed one by one)
function update_textarea(opt2) {
	// can access properties of opt, such as...
	// display in textarea for this example
	var display = document.getElementById('display');
	display.innerHTML += opt2.text + ',\n';
}
