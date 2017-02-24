<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
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
?>
<meta http-equiv="X-UA-Compatible" content="IE=edge"> 
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php print $language->language ?>" lang="<?php print $language->language ?>">
    <head>
        <meta charset="UTF-8">
        <title>Linked Open Graph</title>
        <!--<script type="text/javascript" src="javascript/d3.v2.js"></script>--> 
		<!--<script src="http://d3js.org/d3.v3.js"></script>-->
		<script src="javascript/d3.v3-min.js"></script>
		<!--<script src="javascript/d3.v2.10.js"></script>-->
		<!--<script src="javascript/d3.v3-min.js"></script>-->
        <script type="text/javascript" src="javascript/axrelations.js"></script>
        <script type="text/javascript" src="javascript/jquery.js"></script>
		<!--<script type="text/javascript" src="jquery-autocomplete/jquery.autocomplete.js"></script>-->
		<!--<link href="jquery-autocomplete/jquery.autocomplete.css" rel="stylesheet" type="text/css"/>-->
		<!--<script type="text/javascript" src="javascript/jquery-1.10.1.js"></script>-->
        <script type="text/javascript" src="javascript/jquery.ui.all.js"></script>
		<!--<link href="select2-3.4.5/select2.css" rel="stylesheet"/>-->
		<!--<script src="select2-3.4.5/select2.js"></script>-->
        <link href="axrelations_graph.css" rel="stylesheet" type="text/css" />
        <link href="style.css" rel="stylesheet" type="text/css" />
		
		<link rel="stylesheet" href="jquery-ui-1.10.3.custom.css">
		
    </head>
    <body>
        <div id="container" >
            <div id="header" class="panels_on">
                <?php
					if(!isset($_GET['embed'])) print"<h1>Linked Open Graph </h1><p></p>";//Case for embed the graph.				
				?>
				<script>
					//Sets a class to the browser for differentiate the version of the browser, for css.
					browser_version= parseInt(navigator.appVersion);
					browser_type = navigator.appName;
					browser_name = navigator.appCodeName;
					browser_user = navigator.userAgent;
					// console.log(screen.width+'x'+screen.height);
					// console.log(browser_name);
					// console.log(browser_type);
					// console.log(browser_user);
					if(browser_user.indexOf("iPad") > -1){$("body").attr("class","iPad");}
					else if(browser_user.indexOf("Android") > -1){$("body").attr("class","Android");}
					else if(browser_user.indexOf("Firefox") > -1){$("body").attr("class","firefox");}
					else if(browser_user.indexOf("Opera") > -1){$("body").attr("class","Opera");}
					else if(browser_user.indexOf("Chrome") > -1){$("body").attr("class","Chrome");}
					else if(browser_type.indexOf("Microsoft Internet Explorer")>-1){$("body").attr("class","IE");}
					else if(browser_user.indexOf("Safari") > -1){$("body").attr("class","Safari");}
				</script>
                <script>                    
					var axrelations_graph_navigation_panel = '<div id="axrelations_graph_navigation_panel">' +
						'<button class="navigation_button" id="axrelations_graph_navigation_panel_explore">+ Details</button>' +
						'<button class="navigation_button" id="axrelations_graph_navigation_panel_reduce">- Details</button>' +
						'<button class="navigation_button" id="axrelations_graph_navigation_panel_focus">Focus</button>' +
						'<button class="navigation_button" id="axrelations_graph_navigation_panel_info">Info</button>' +
						'<button class="navigation_button" id="axrelations_graph_navigation_panel_open">Open</button>' +
						'<button class="navigation_button" id="axrelations_graph_navigation_panel_search" height="30">Search</button> '+					
						'<button class="navigation_button" id="axrelations_graph_navigation_panel_share" height="30">Embed</button> '+					
						'</div>';					
					axrelations_graph_navigation_panel += '<div id="axrelations_graph_navigation_more">' + //The menu for when a more buttons is clicked.
						'<button class="more_button" id="axrelations_graph_navigation_more_explore">+ Results</button>' +
						'<button class="more_button" id="axrelations_graph_navigation_more_search" height="30">Search</button> '+
						'</div>';					
					var ajax_classes;//ajax request for classes of an endpoint.
					var count_class_request=0;//used for give a temporary id to the request of class suggest.
					
					$(document).ready(function() {
						$("body").append(axrelations_graph_navigation_panel);//Appends the navigation panel for the graph.
						split_status();
						//If there is already an open graph, the request button adds a new graph on click.
						if($("#graphContainer").length!=0){
							$( "form" ).submit(function( event ) {
								if(this.id=="user_data") control_add_graph(1); //For the user data form.
								else control_add_graph(0); //For the first form.
								event.preventDefault();
							});
						}
						//Adds the toggle event to the header.
						$("#header").mouseup(function () {
							var toggle=$("#header.panels_on");									  
							if(toggle[0]) toggle.attr("class","panels_off");
							else $("#header").attr("class","panels_on");
							$("div#options_panels").toggle("");
						});
						var ajax;//ajax request for json.
						var ajax_classes;//ajax request for classes of an endpoint.
						var xhReq = new XMLHttpRequest();
						var suggest_solution=function(){ //function that requests the keywords suggestion.
							if(this.value){
								if(this.value.length>0){
									var opt=$("select option:selected").attr("id");
									var autocomplete=$("#auto"+opt);
									var call=autocomplete.text();
									var parameters=this.value;
									var search_type=autocomplete.attr('value');
									var class_of_search=$(".list_of_class option:selected").attr('value');
									if(!class_of_search)class_of_search="keyword";//If the user writes before the search for the classes completes. 
									if(search_type=='dbpedia'){
										call+=parameters;
										if(xhReq){//interrupts the previous request
											if(xhReq.status==0) xhReq.abort();
										}
										var top=($("#search_input").position().top)+30;
										var suggestion="<div id='Suggestion' style='top:"+top +"'>";
										suggestion+="<div id='close_suggestion'><a>X</a></div>"; //Creates the box with the suggestion.
										suggestion+="<img id='axrelations_info_loading_gif' src='images/buttons/ajax-loader-big.gif'>";
										suggestion+="</div>";
										$("#Suggestion").remove();
										$("#search_input").parent().append(suggestion);
										$("#close_suggestion").click(function(){
											if(xhReq){//interrupts the previous request
												if(xhReq.status==0) xhReq.abort();
											}
											$("#Suggestion").remove();
										});
										
										xhReq.open("GET", call, true);
										xhReq.send(null);
										xhReq.onload=function(data){
											if(xhReq.status==500){ //Server error
												$( "#dialog_server_error" ).remove();
												$("#Suggestion").remove();
												var message="<div id='dialog_server_error' title="+xhReq.statusText+"><p> Error: keyword search is not available due to an endpoint error. </p></div>";
												$("body").append(message);
												$( "#dialog_server_error" ).dialog();
											}
											else if(xhReq.responseXML){
												var result=xhReq.responseXML.getElementsByTagName('Result');
												var top=($("#search_input").position().top)+30;
												var suggestion="<div id='Suggestion' style='top:"+top +"'>"; //Creates the box with the suggestion.
												suggestion+="<div id='close_suggestion'><a>X</a></div>";
												$("#Suggestion").remove();
												for(var i=0;i<result.length;i++){
													var label=result[i].getElementsByTagName('Label')[0].textContent;
													var uri=result[i].getElementsByTagName('URI')[0].textContent;
													suggestion+="<div class='selectionList'><span title='"+uri+"' >"+label;
													suggestion+="</span></div>";										
												}
												suggestion+="</div>";
												
												if(result.length>0){//Defines some events.
													$("#search_input").parent().append(suggestion);
													$(".selectionList").mouseover(function(){
														this.setAttribute("class","over")
													});
													$(".selectionList").mouseout(function(){
														this.setAttribute("class","")
													});
													$(".selectionList").click(function(){
														$("#search_input").val(this.firstChild.textContent);
														$("#uri_input").val(this.firstChild.title);
														$("#uri_input").attr('keyword',this.firstChild.textContent);//Associates the keyword to the uri.
														$("#Suggestion").remove();
													});
													$("#close_suggestion").click(function(){$("#Suggestion").remove();});
												}										
											}
										};										
									}
									else if(search_type!='none'){//Makes a call to server for get some suggestion with a sparql query with filter regex, or contains of with owlim.
										if(ajax){//interrupts the previous request
											if(ajax.status==0) ajax.abort();
										}
										var top=($("#search_input").position().top)+30;
										var suggestion="<div id='Suggestion' style='top:"+top +"'>";
										suggestion+="<div id='close_suggestion'><a>X</a></div>"; //Creates the box with the suggestion.
										suggestion+="<img id='axrelations_info_loading_gif' src='images/buttons/ajax-loader-big.gif'>";
										suggestion+="</div>";
										$("#Suggestion").remove();
										$("#search_input").parent().append(suggestion);
										$("#close_suggestion").click(function(){
											if(ajax){//interrupts the previous request
												if(ajax.status==0) ajax.abort();
											}
											$("#Suggestion").remove();
										});										
										setTimeout(function() {//Sets a time-out delay for avoid to make repetitive calls.
											if($("#search_input").attr('value')!=parameters) return; //if the text before search was modified or is empty.
											ajax=$.getJSON( "request.php?suggestion="+call +"&text="+parameters +"&filter_type="+search_type+"&search_InClass="+encodeURIComponent(class_of_search), function( json ) {
												var top=($("#search_input").position().top)+30;
												var suggestion="<div id='Suggestion' style='top:"+top +"'>"; //Creates the box with the suggestion.
												suggestion+="<div id='close_suggestion'><a>X</a></div>"; //Creates the box with the suggestion.
												$("#Suggestion").remove();
												for(var i=0;i<json.length;i++){
													var label=json[i].Label;
													var uri=json[i].URI;
													suggestion+="<div class='selectionList'><span title='"+uri+"' >"+label;
													suggestion+="</span></div>";										
												}
												suggestion+="</div>";											
												if(json.length>0){//Defines some events.
													$("#search_input").parent().append(suggestion);
													$(".selectionList").mouseover(function(){
														this.setAttribute("class","over")
													});
													$(".selectionList").mouseout(function(){
														this.setAttribute("class","")
													});
													$(".selectionList").click(function(){
														$("#search_input").val(this.firstChild.textContent)
														$("#uri_input").val(this.firstChild.title)
														$("#uri_input").attr('keyword',this.firstChild.textContent);//Associates the keyword to the uri.
														$("#Suggestion").remove();
													});
													$("#close_suggestion").click(function(){$("#Suggestion").remove();});
												}
											});										
										},1500);										
									}									
								}
								else{
									$("#Suggestion").remove();
								}
							}
							else{
								$("#Suggestion").remove();
							}
						};
						function search_classes_request(EP_toSearch) {			
							count_class_request=(count_class_request+1)%20
							var current_count=count_class_request;
							var loading_gif="<img id='axrelations_info_loading_gif' src='images/buttons/ajax-loader-big.gif'>";
							if($("#axrelations_info_loading_gif").length==0)$("#div_class_select").append(loading_gif);//If there is already a loading gif leaves the one present.
							// $("#div_class_select #axrelations_info_loading_gif").remove(); //Removes previous gif.
							$(".list_of_class").remove();//Removes the list of class of the previous EP.
							if($("#auto"+EP_toSearch).length!=0){//Binds the auto complete function to the input field.
								var search=$("#search_input");
								search.keyup(suggest_solution);
							}
							//Changes the list of classes of the endpoint.
							if(ajax_classes){//interrupts the previous request
								if(ajax_classes.status==0){
									ajax_classes.abort();
									$("#div_class_select #axrelations_info_loading_gif").remove();
								}
							}
							setTimeout(function() {//Sets a time-out delay for avoid to make repetitive calls.
								if(current_count==count_class_request){
									var ep_height = $(".left_panel .endpoint").outerHeight(true);
									var EP=$("#"+EP_toSearch).attr("value");
									ajax_classes=$.getJSON( "request.php?get_classes="+EP, function( json ) {
										if(current_count==count_class_request) {
											var options="";
											options+="<option value='keyword'>Search for keyword</option>";
											for(var option in json){
												options+="<option value='"+json[option]+"'>"+json[option]+"</option>";	
											}
											var select="<select class='list_of_class'>"+options +"</select>";
											$("#div_class_select #axrelations_info_loading_gif").remove();
											$("#div_class_select").append(select);
											$('.list_of_class').change(function(){ //Changes the name in the uri with the name of the class.
												var opt=$(".list_of_class option:selected").attr("value");
												if(opt!="keyword"){
													$("#uri_input").val(opt);
													$("#uri_input").removeAttr('keyword');//Removes the attribute 'keyword' used for memorize the name.
												}
											});
										}
										return;
									});	
								}
							},1500);
							change_options_height(true);
						}
						function change_options_height(resize){
							//Changes the height of the option field to fit the height
							var lp_height = $(".left_panel").outerHeight(true);
							var go_height = $(".left_panel .graph_options").outerHeight(true);
							var ep_height = $(".left_panel .endpoint").outerHeight(true);
							var load_height = 0;//The height of the loading gif.
							if(resize) load_height = 32;//The height of the loading gif.
							if($("#axrelations_info_loading_gif").height()>0) load_height = 0;
							var height_to_fit= lp_height - ( ep_height + go_height + load_height );
							if(height_to_fit>=0){//There is a gap between the expected height and the real value.
								$(".left_panel .graph_options").height($(".left_panel .graph_options").height()+height_to_fit);
							}
							else{ //Calls a function that decides if collapse or not.
								resize_option(height_to_fit,false);
							}						
						}
						function resize_option(height_to_fit,direct,value){
						/* This function reduces the height of the options. height_to_fit: The height to subtract. */
							if(direct){
								$(".graph_options").height(value);
							}
							else{
								var h2_height=$(".graph_options h2").outerHeight();
								var op_height=0;
								if($(".graph_options h2.open").length>0) op_height=$("#log_options").outerHeight(true);
								var gopt_heaight=$(".graph_options").height();
								var difference = gopt_heaight+height_to_fit-(op_height+h2_height);
								if(difference>=0){
									$(".graph_options").height($(".graph_options").height()+height_to_fit);
								}
								else{
									value=$(".graph_options").height()+height_to_fit;//The new hight for options.
									$(".graph_options h2").click();
									resize_option(height_to_fit,true,value)
								}
							}
						}
						var opt=$("select option:selected").attr("id"); //opt = the index of the selected option.
						var option_selected=$("#auto"+opt);
						if(option_selected.length!=0) {
							$("#prefix_search").show();//Shows allways the first element.
							//Binds the auto complete function to the input field.
							var search=$("#search_input");
							search.keyup(suggest_solution);
							if(option_selected.attr('value')!="dbpedia"){//For dbpedia doesn't show the classes.
								$("#div_class_select").show();
								search_classes_request(opt);
								$(".examples").hide();
								$("#ex"+opt).show();
							}
							else{
								change_options_height(false);
							}
						}
						else change_options_height(false);
						
						$("select.EndPoints").change(function(){
							var opt=$("select.EndPoints option:selected").attr("id");
							$("#prefix_search").hide();//Hides allways
							$("#div_class_select").hide();
							var option_selected=$("#auto"+opt);
							$("#search_input").unbind('keyup');							
							$("#Suggestion").remove();//Removes the suggestion panel.
							$("#uri_input").val("");//Removes the uri in the uri field.
							$("#search_input").val("");//Removes the keyword in the keyword field.
                            $(".examples").hide();
                            $("#ex"+opt).show();
							if(option_selected.length!=0){
								var opt_class=$("select.EndPoints option:selected").attr("class");
								// $("#prefix_search").attr("class",opt_class);
								$("#prefix_search").show();//Shows allways
								if(option_selected.attr('value')!='dbpedia'){
									$("#div_class_select").show();
									search_classes_request(opt); 
								}
								else{
									var search=$("#search_input");
									search.keyup(suggest_solution);
									change_options_height(false);
								}
							}
							else change_options_height(false);
						});
						$("select.uri_added").click(function(){ //Centres the graph on the clicked node.
							if(this.selectedOptions[0]){
								var referenced_id=this.selectedOptions[0].value;
								referenced=nodes.filter(function(d){return d.id==referenced_id })//Retrieves the node.
								if(referenced.length!=0) recenter_graph(referenced[0].index);//calls the function in axrelations.js that recentres the graph.
								$("#remove_status").attr("disabled",'');//Enables the remove button.
							}
						});
						$("#relation").click(function (e) {
							localStorage.setItem("root", $(".EndPoints option:selected").val());
							window.open("endgraph/index.html");
						});
						$("#global").click(function (e) {
							window.open("endgraph_global/index.html");
						});
						if (window.addEventListener) {
							// Normal browsers
							window.addEventListener("storage", changeValue, false);
						} else {
							// for IE (why make your life more difficult)
							window.attachEvent("onstorage", changeValue);
						}
						//riceve i dati da Endgraph e aggoirna gli esempi
						function changeValue(e) {
							console.log('Successfully communicate with other tab');
							console.log('Received data: ' + localStorage.getItem('value'));
							$(".EndPoints").val(localStorage.getItem('value'));

							var opt = $("select option:selected").attr("id"); //opt = the index of the selected option.
							var option_selected = $("#auto" + opt);
							if (option_selected.length != 0) {
								$("#prefix_search").show();//Shows allways the first element.
								//Binds the auto complete function to the input field.
								var search = $("#search_input");
								search.keyup(suggest_solution);
								if (option_selected.attr('value') != "dbpedia") {//For dbpedia doesn't show the classes.
									$("#div_class_select").show();
									search_classes_request(opt);
									$(".examples").hide();
									$("#ex" + opt).show();
								} else {
									change_options_height(false);
								}
							} else
								change_options_height(false);

							$("select.EndPoints").change(function () {
								var opt = $("select.EndPoints option:selected").attr("id");
								$("#prefix_search").hide();//Hides allways
								$("#div_class_select").hide();
								var option_selected = $("#auto" + opt);
								$("#search_input").unbind('keyup');
								$("#Suggestion").remove();//Removes the suggestion panel.
								$("#uri_input").val("");//Removes the uri in the uri field.
								$("#search_input").val("");//Removes the keyword in the keyword field.
								$(".examples").hide();
								$("#ex" + opt).show();
								if (option_selected.length != 0) {
									var opt_class = $("select.EndPoints option:selected").attr("class");
									// $("#prefix_search").attr("class",opt_class);
									$("#prefix_search").show();//Shows allways
									if (option_selected.attr('value') != 'dbpedia') {
										$("#div_class_select").show();
										search_classes_request(opt);
									} else {
										var search = $("#search_input");
										search.keyup(suggest_solution);
										change_options_height(false);
									}
								} else
									change_options_height(false);
							});
							$("select.uri_added").click(function () { //Centres the graph on the clicked node.
								if (this.selectedOptions[0]) {
									var referenced_id = this.selectedOptions[0].value;
									referenced = nodes.filter(function (d) {
										return d.id == referenced_id
									})//Retrieves the node.
									if (referenced.length != 0)
										recenter_graph(referenced[0].index);//calls the function in axrelations.js that recentres the graph.
										$("#remove_status").attr("disabled", '');//Enables the remove button.
								}
							});
						}
                    });					
                </script>	
            </div>
            <div id="center" >
                <?php
                require_once("config.php");
				require_once("includes/sparqllib.php"); 
                $active='';
				$keyword_first='';
				$multi_search="";
				if($multiple_endpoints_default=="true"){
					$multi_search="true";//Actives multi endpoint search.
					if($_GET["multiple_search"])$multi_search=$_GET["multiple_search"];
					else{//Controls for disable the multiple endpoint search for the old embeds.
						if(isset($_GET['embed'])) $multi_search="false";
					}
				}
				else $multi_search="false";
                $n=0;
                foreach($sparql_endpoints as $e) {
					$keyword='';//If is present the keyword search.
                    $selected='';
                    if($e['endpoint']==$_GET['sparql']) {
                        $selected='selected';
                        $active='active';
						if($endpoint_with_suggest[$e['endpoint']]){$keyword='keyword';$keyword_first='keyword';}
                    }
					if($endpoint_with_suggest[$e['endpoint']])$keyword='keyword';
                    $options .= "<option value='$e[endpoint]' $selected id='opt$n' class='$keyword'>$e[name]</option>";
                    $n++;
                }
                $output .= "<div id='options_panels'>";
				$output .= "<div class='left_panel'><div class='endpoint $active'><form id='endpoint_form'><span>Select a SPARQL endpoint:</span><button type='button' id='global'>Global Endpoint Map</button><br/></br><select class='EndPoints' name='sparql'>$options</select><button type='button' id='relation'>View endpoint relation</button><br/>Examples:";
                $n=0;
				// var_dump($sparql_endpoints);
                foreach($sparql_endpoints as $e) {
					$show='';
                    if($e['endpoint']==$_GET['sparql'] || ($n==0 && !$active))
                        $show='show';
                    $output .= "<div class='examples $show' id='exopt$n'><ul>";
					if(count($e['examples'])==0) $output .= "<b>no examples for this endpoint</b>";
                    foreach($e['examples'] as $ex) {
						if(gettype($ex)=='object'){$ex=(array)$ex;}
						$load_conf=false;
						if($ex['id'])$load_conf=true;						
                        if($load_conf){
							if(substr($ex[name],0,1)=="\"")$ex[name]=substr($ex[name],1);//removes the first quotes;
							if(substr($ex[name],-1)=="\"")$ex[name]=substr($ex[name],0,-1);//removes the last quotes;
							$output.="<li><a class=ep_example normal=\"index.php?uri=$ex[uri]&sparql=$e[endpoint]&keyword=$ex[name]&multiple_search=false\"
							href=\"index.php?graph=$ex[id]&multiple_search=false\">$ex[name]</a></li>";
						}
                        else $output.="<li><a href=\"index.php?uri=$ex[uri]&sparql=$e[endpoint]&keyword=$ex[name]&multiple_search=false\">$ex[name]</a></li>";
                    }
                    $output .= "</ul></div>";
					//added for query keyword based.
					if($e['search_type']=='dbpedia')$output.="<p id='autoopt$n' class='autocomplete' value=$e[search_type]>$e[url]</p>";
					else if($e['search_type']!='none')$output.="<p id='autoopt$n' class='autocomplete' value=$e[search_type]>$e[endpoint]</p>";
					
                    $n++;
                }
				$output.="<div id='div_class_select'><span>Choose a class:</span></div>";                
                $output.= "<p id='prefix_search' class='$keyword_first'><span >keyword:</span></br> <input id='search_input' type='text' name='keyword' autocomplete='off'/></p>";
                $output.= "<p><span class='uri'>uri:</span> <input id='uri_input'type='text' name='uri' placeholder='http://...' value='".($active?$_GET['uri']:'')."' oninput='changeurinput();'/><input type='submit' class='graph_request' value='Request'/></p>";
                // if($active_multiple_endpoints)$output.="<input class=\"multiple_ep_hidden\"type=\"hidden\" name=\"multiple_search\" value=\"$multi_search\" />";//Adds the options for multiple EP.
				$me_checked="checked";
				if($multi_search=="false")$me_checked="";
                if($active_multiple_endpoints)$output.="<input id=\"multiple_ep\"type=\"checkbox\" name=\"multiple_search\" value=\"$multi_search\" $me_checked /> Multiple endpoint search";//Adds the options for multiple EP.
                $output.="</form>";
                $output.="</div>";
				// if($active_multiple_endpoints)$output.="<div class='graph_options endpoint'><h2 class='open'>Options</h2> <div id='log_options'><input id=\"multiple_ep\"type=\"checkbox\" name=\"multiple_search\" value=\"multiple_search\" $me_checked /> Multiple endpoint search</div></div>";
                $output.="</div>";
				$active = !$active && ($_GET['sparql']||$_GET['uri']) ? 'active' :'';
                $output.="<div class='user_status'><div class='endpoint $active'>" .
                        "<form id='user_data'><h2>Your data</h2><p>sparql endpoint: (optional)<input type='text' name='sparql' placeholder='http://...' value='".($active?$_GET['sparql']:'')."' /></p>".
                        "<p>uri: <input type='text' name='uri' placeholder='http://...' value='".($active?$_GET['uri']:'')."' /><input type='submit' class='graph_request' value='Request' /></p>";
				// if($active_multiple_endpoints)$output.="<input class=\"multiple_ep_hidden\"type=\"hidden\" name=\"multiple_search\" value=\"$multi_search\" />";
				if($active_multiple_endpoints)$output.="<input id=\"multiple_ep\"type=\"checkbox\" name=\"multiple_search\" value=\"$multi_search\" $me_checked /> Multiple endpoint search";
				$output.="</form></div>";
				$last_placeholder="";
				$LOG_html="";
				if ($_GET['uri'] != '' || $_GET['sparql'] != '') {					
					//Checks if the endpoint is not alive.
					try {
						if(strpos($uri,'http://')===false)
						if($_GET['uri']==''){
							if(isset($_GET["embed"]))throw new Exception('Insert an URI');
							else die('Missing URI');
						}
						// if($_GET['sparql']==''){
							// if(isset($_GET["embed"]))die('Missing a SPARQL endpoint');//If no sparql endpoint is insert, makes a search based on LD.
						// }
						$uri=$_GET['uri'];//Checks the uri.
						if($_GET['sparql']!=''){
							$endpoint=$_GET['sparql'];//Checks the endpoint.
							$db = sparql_connect( $endpoint );
							if( !($db->alive()) )throw new Exception('Endpoint not alive.');	
							//Adds the status panel.
							$label="$_GET[uri] [$_GET[sparql]]";
							if($_GET[keyword]!=''){
								require_once("includes/mysparql_function.php");
								$ep=get_endpoint($_GET[sparql]);
								
								$label="$_GET[keyword][$ep[name]]";	
							}
						}
						else $label="$_GET[uri] [Linked Data]";
						$status_panel="<div class='endpoint active'id='status_panel'><h2>Status</h2><p>Requests: </p><p><div class='scrollable'><select class='uri_added' name='requests_made' size='6' Multiple>";
						if($_GET[sparql]!='')$status_panel.="<option value='$_GET[uri]' title='URI:$_GET[uri]   EndPoint:$_GET[sparql]'>$label</option></select></div></p>";//inserts a scrollable list of items
						else $status_panel.="<option value='$_GET[uri]' title='URI:$_GET[uri] LinkedData'>$label</option></select></div></p>";//inserts a scrollable list of items
						$status_panel.="<button id='remove_status' value='Remove' title='Remove the selected element from the graph.' onclick=remove_status_option() disabled>Remove</button>";
						$status_panel.="<button id='clear_graph' value='Clear' title='Clear the graph' onclick=reset_graph()>Clear</button></div>";
						$output.=$status_panel;
						$output.="</div>";//Closes div user-status
						$output.="</div>";//Closes div options_panels
						//Adds the graph.
						$LOG_html.="<div id='graphContainer'></div>";
						if($_GET[sparql]!='')$LOG_html.="<script type='text/javascript'>axrelations_graph_open('".$_GET['uri'] ."','graphContainer','first_uri','".$_GET['sparql'] ."',$multi_search)</script>";
						else $LOG_html.="<script type='text/javascript'>axrelations_graph_open('".$_GET['uri'] ."','graphContainer','first_uri','LD',$multi_search)</script>";
						$output=$output.$LOG_html;
					} catch (Exception $e) {
						echo "<div id='dialog' title='Try again'><p>".$e->getMessage() ."</p></div>";
						if(!isset($_GET["embed"]))echo '<script type="text/javascript">$( "#dialog" ).dialog();</script>';
					}
					//Controls the user agent and decides to save or not.					
					saveAccessLog($user_agent_banned);
                }
				else{
					$last_placeholder.="</div>";//Closes div user-status
					$last_placeholder.="</div>";//Closes div options_panels
					if($_GET['graph'] == '')$output.=$last_placeholder;
					try {//Checks if both endpoint and url are empty.
						if(strpos($_SERVER[REQUEST_URI],'sparql')!==false)throw new Exception('Please provide Endpoint and Uri.');
					} catch (Exception $e) {
						echo "<div id='dialog' title='Try again'><p>".$e->getMessage() ."</p></div>";
						echo '<script type="text/javascript">$( "#dialog" ).dialog();</script>';
					}
				}
				if($_GET['graph'] != ''){//If the user is loading a configuration saved previously, loads the configuration identified by $_GET['graph']
					//Checks if the configuration is present in the database.
					require_once("config.php");
					require_once( "includes/mysparql_function.php" ); 
					//Access to the database.
					$username = $db_username;
					$password = $db_psw;
					$hostname = $db_host; 
					$schema=$db_schema;
					// connect to the database		
					$dbhandle = mysqli_connect($hostname, $username, $password,$schema) 
					or die("Unable to connect to MySQL");
					$query="SELECT * FROM graph WHERE readwrite_id='".$_GET['graph'] ."' ";
					$result=mysqli_query($dbhandle,$query);
					$r=mysqli_fetch_array($result);
					//Check if it's a read code.
					
					if(!$r){//Check if the code is for write/read.
						$query="SELECT * FROM graph WHERE id='".$_GET['graph'] ."' ";
						$result=mysqli_query($dbhandle,$query);
						$r=mysqli_fetch_array($result);
						if(!$r){//The row is not present. Shows an error.
							// print"NO FILE FOUND!";
							if(!isset($_GET['embed'])){
								print '<script type="text/javascript">'; 
								print 'alert("Error: No file found for the current url.")'; 
								print '</script>';  
							}
							die ("Error: No file found for the current url.");
							// return;
						}
					}
					//Adds the status panel with empty status.
					$status_panel="<div class='endpoint active'id='status_panel'><h2>Status</h2><p>Requests: </p>";
					$status_panel.="<p><div class='scrollable'><select class='uri_added' name='requests_made' size='6' Multiple></select></div></p>";//inserts a scrollable list of items
					$status_panel.="<button id='remove_status' value='Remove' title='Remove the selected element from the graph.' onclick=remove_status_option() disabled>Remove</button>";
					$status_panel.="<button id='clear_graph' value='Clear' title='Clear the graph' onclick=reset_graph()>Clear</button></div>";
					$output.=$status_panel;
					$output.=$last_placeholder;
					//Adds the graph
					$LOG_html.="<div id='graphContainer'></div>";
                    $LOG_html.="<script type='text/javascript'>axrelations_graph_open('".$_GET['graph'] ."','graphContainer','load_configuration','load')</script>";
                    //Controls the user agent and decides to save or not.					
					saveAccessLog($user_agent_banned);
					$output=$output.$LOG_html;
				}
                if(isset($_GET['embed'])){//If the content is to embed, prints only the graph.
					print $LOG_html;
					echo '<script type="text/javascript">';
					echo 'var actual_body_class=$("body").attr("class");';
					echo '$("body").attr("class",actual_body_class+" embed ");';
					echo '</script>';
					if(!$_GET['controls'] || $_GET['controls']!="true"){
						echo '<script type="text/javascript">';
						echo 'var actual_body_class=$("body").attr("class");';
						echo '$("body").attr("class",actual_body_class+" no_control");';
						echo '</script>';
					}
					if(!$_GET['description'] || $_GET['description']!="true"){
						echo '<script type="text/javascript">';
						echo 'var actual_body_class=$("body").attr("class");';
						echo '$("body").attr("class",actual_body_class+" no_desc");';
						echo '</script>';
					}
					if(!$_GET['info'] || $_GET['info']!="true"){
						echo '<script type="text/javascript">';
						echo 'var actual_body_class=$("body").attr("class");';
						echo '$("body").attr("class",actual_body_class+" no_info");';
						echo '</script>';
					}
				}
				else print $output;
                ?>	
            </div>
			<script>
				// Binds function for the change of h2.				
				$(".graph_options h2").click(function(){
					//Toggles the options tab.
					if(this.className=='open'){
						$(".graph_options h2").attr("class","close");
						$("#log_options").css("display","none");						
					}
					else{
						$(".graph_options h2").attr("class","open");
						$("#log_options").css("display","block");
						$(".graph_options").css("height","auto");
					}
					var lp_height = $(".left_panel").outerHeight(true);
					var go_height = $(".left_panel .graph_options").outerHeight(true);
					var ep_height = $(".left_panel .endpoint").outerHeight(true);
					var height_to_fit= lp_height - ( ep_height + go_height );
					if(height_to_fit>=0){//There is a gap between the expected height and the real value.
						$(".left_panel .graph_options").height($(".left_panel .graph_options").height()+height_to_fit);
					}
				});
				// Binds function for the change of MultipleEP checkbox. TODO it should be placed in axrelations.js 
				$(".active #multiple_ep").change(function(){
					if(this.checked){
						active_multiple_endpoints=true;
					}
					else{
						active_multiple_endpoints=false;
					}
				});
				$(".ep_example").click(function(e){
				/* Open the dialog for makes the user choose between the example in cache or not. */
					console.log(" Open the dialog for the example. ");
					e.preventDefault();
					
					var dialog="<div id='ep_example_dialog' class='dialog' title='Embed the Graph'><div id='ep_example_title' >Open the example.<a id='ep_example_close' title='Close'>Close</a></div>";
					dialog+="<div id='ep_example_main_div'>";
					dialog+="<p>The example is present in the cache.</br> If you want to load from the cache click yes otherwise click no and it will be loaded directly from the endpoint.</p>";
					dialog+="<a id='ep_example_proced' href=\""+e.target.attributes.href.value+"\"><input type='button' value='yes'></a> <a href=\""+e.target.attributes.normal.value+"\"> <input type='button' value='no'></a>";
					dialog+="</div></div>";
					$("#overlay").show();
					$("body").append(dialog);
					$("#ep_example_close").click(function (e)
					{
						$("#ep_example_dialog").fadeOut(300);
						$("#ep_example_dialog").remove();
						$("#overlay").hide();
					});
				});
			</script>
            <div id="footer" ></div>
        </div>
    </body>
</html>
<?php
function saveAccessLog($user_agent_banned) {
	//Controls the user agent and decides to save or not.					
	$save_access=true;
	foreach($user_agent_banned as $u){
		if(strpos($_SERVER['HTTP_USER_AGENT'], $u))
			{$save_access=false; break;} 
	}
	if($save_access){
		$f=fopen("access.log",'at');
		if($_GET['graph'] != '')
			fwrite($f,date('c').'|'.$_SERVER['REMOTE_ADDR'].'| graph |'.$_GET['graph'].'|'.$_SERVER['HTTP_USER_AGENT'].'|'.$_SERVER['HTTP_REFERER']."\n");
		else if($_GET['sparql']!='')
			fwrite($f,date('c').'|'.$_SERVER['REMOTE_ADDR'].'|'.$_GET['sparql'].'|'.$_GET['uri'].'|'.$_SERVER['HTTP_USER_AGENT'].'|'.$_SERVER['HTTP_REFERER']."\n");
		else 
			fwrite($f,date('c').'|'.$_SERVER['REMOTE_ADDR'].'| LD no endpoint |'.$_GET['uri'].'|'.$_SERVER['HTTP_USER_AGENT'].'|'.$_SERVER['HTTP_REFERER']."\n");
		fclose($f);
	}
}
