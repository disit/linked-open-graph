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

include_once 'admin-class.php';
$admin = new itg_admin();
$admin->_authenticate();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <title>Administrator page</title>
        <link href="admin.css" rel="stylesheet" type="text/css" />
        <link href="../style.css" rel="stylesheet" type="text/css" />
        <link href="../axrelations_graph.css" rel="stylesheet" type="text/css" />
		
        <script type="text/javascript" src="../javascript/jquery.js"></script>
        <script type="text/javascript" src="../javascript/jquery.tablesorter.min.js"></script>
        <script type="text/javascript" >
			
			function validateForm() {
				var urls = $("#edit_endpoints .url_field");
				if(urls.length<=0){
					alert("No url provided.");
					return false;
				}
				for(var i=0; i<urls.length; i++){
					if(!urls[i].value){
						alert("One or more url missed.");
						return false;
					}
				}
			}
			function delete_row(row){
			/* Deletes the row specified by row */
				var row_to_delete=$("#"+row+"").parent();			
				//Moves the row where it can't be deleted.
				var r_hidden=$("#"+row+"");
				$("#edit_endpoints").append(r_hidden);
				var old_name=r_hidden.attr("name");
				var new_name=old_name.substring(0,old_name.lastIndexOf("["));
				new_name+="[deleted]";
				r_hidden.attr("name",new_name);
				r_hidden.attr("disabled",false);
				if(row_to_delete.length>0)row_to_delete.remove();
				console.log("delete row:"+row);
				
			}
			function row_changed(row){
			/* Indicates that the row is changed */
				$("#"+row+"").attr("disabled",false);			
			}
			function edit_suggest(row,row2){
			/* Function for edit the suggestion field */
				var ep=$("input#"+row2+"").attr("value");
				var overlay='<div id="overlay" style="display: block;"></div>';
				$("body").append(overlay);
				var dialog="<div id='config_suggest_dialog' title='Add suggest'><div id='config_suggest_title' >Example for "+ep+".<a id='config_suggest_close' title='Close'>Close</a></div>";
				dialog+="<div id='config_suggest_main_div'>";
				dialog+="<input type='hidden' value='"+row2+"' title="+row+">";
				dialog+="</br><input type=\"button\" value=\"SAVE\" onclick=\"save_suggests('"+row+"','"+row2+"')\">  </p></br>";
				dialog+="<p> Insert a suggest with a probability </p>";
				dialog+="</br><p> Property:&nbsp&nbsp <input id='property_url' type=\"text\" value=\"rdfs:..\"> </p></br>";
				dialog+="</br><input type=\"button\" value=\"Add\" onclick=\"append_suggest('"+row+"','"+row2+"')\">&nbsp&nbsp";
				
				dialog+="</div></div>";
				$("body").append(dialog);
				
				var title= row;
				try{
					var actual_content=JSON.parse($("textarea[name='"+title+"']").val());
				}
				catch(err){
					console.log(err);
				}
				var editable_config="";
				if(actual_content){
					editable_config+="</br><b>Configuration:</b></br><div class='config_display'>";
					for(var i in actual_content) {
						var suggest_value = actual_content[i];
						// if(typeof(suggest_value)!="string") suggest_value = actual_content[i][i];
						editable_config+="<p class='suggest_element'>URI:<input class='uri_config' value='"+actual_content[i]+"' type='text'>&nbsp&nbsp";
						editable_config+="<input class='move_suggest'  value='move UP'   type='button' onclick=\"move_example(this,'up')\">&nbsp&nbsp";
						editable_config+="<input class='move_suggest'  value='move DOWN' type='button' onclick=\"move_example(this,'down')\">&nbsp&nbsp";
						editable_config+="<input class='delete_config' value='remove' type='button'></p>";
					}
				}
				else{
					editable_config+="</br><b> No configuration found. New:</b></br><div class='config_display'>";
				}
				editable_config+="</div>";			
				$("#config_suggest_main_div input[type='hidden']").after( editable_config );
				
				$("#config_suggest_dialog .delete_config").click(function() {
					//functions for delete a prefix
					this.parentElement.remove();
				});
				$("#config_suggest_close").click(function (e) {
					$("#config_suggest_dialog").fadeOut(300);
					$("#config_suggest_dialog").remove();
					$("#overlay").remove();
				});
			}
			function save_suggests(row,row2){
				/* Function for save the prefixes */
				try{
					var pref_to_save = $(".config_display p");
					var element = new Object;
					for(var i=0; i<pref_to_save.length; i++) {
						var p_el = pref_to_save[i];
						var suggest_el = p_el.getElementsByClassName("uri_config")[0].value;
						element[i] = suggest_el;
					}
					var json_content = JSON.stringify(element);
					var info = $("#config_suggest_main_div input[type='hidden']");
					var title = info.attr("title");
					var txtarea = $("textarea[name='"+title+"']");
					txtarea.attr("value",json_content);
					$("table #"+info.attr("value")).attr("disabled",false);
					alert("The suggests have been set. SAVE the table for confirm the changes");
				}
				catch(err){
					console.log(err);
				}
			}
			function append_suggest(row,row2){
			/* Appends a suggestion to the endpoint */
				var prefix_to_add=$("#config_suggest_dialog #property_url").attr("value");
				var editable_config="";				
				editable_config+="<p class='suggest_element'>URI:<input class='uri_config' value='"+prefix_to_add+"' type='text'>&nbsp&nbsp";
				editable_config+="<input class='move_suggest'  value='move UP'   type='button' onclick=\"move_example(this,'up')\">&nbsp&nbsp";
				editable_config+="<input class='move_suggest'  value='move DOWN' type='button' onclick=\"move_example(this,'down')\">&nbsp&nbsp";
				editable_config+="<input class='delete_config' value='remove' type='button'></p>";
				
				$("#config_suggest_main_div .config_display").append(editable_config);
				$("#config_suggest_dialog .delete_config").click(function() {
					//functions for delete a prefix
					this.parentElement.remove();
				});
			}
			function add_uri_prefix(row,row2){
			/* Functions for add one or more prefix to the endpoint selected */
				var ep=$("input#"+row2+"").attr("value");
				var overlay='<div id="overlay" style="display: block;"></div>';
				$("body").append(overlay);
				var dialog="<div id='config_addprefix_dialog' title='Add more prefixes'><div id='config_addprefix_title' >Example for "+ep+".<a id='config_addprefix_close' title='Close'>Close</a></div>";
				dialog+="<div id='config_addprefix_main_div'>";
				dialog+="<input type='hidden' value='"+row2+"' title="+row+">";
				dialog+="</br><input type=\"button\" value=\"SAVE\" onclick=\"save_prefix('"+row+"','"+row2+"')\">  </p>";
				dialog+="</br></br><p> Insert a prefix with a probability </p>";
				dialog+="</br><p> Prefix:&nbsp&nbsp <input id='prefix_url' type=\"text\" value=\"http://..\"> </p>";
				dialog+="</br><p> Priority: <input id='prefix_prio' type=\"number\" value=1 min=1>  </p>";
				dialog+="</br><input type=\"button\" value=\"Add\" onclick=\"append_prefix('"+row+"','"+row2+"')\">&nbsp&nbsp";
				
				dialog+="</div></div>";
				$("body").append(dialog);
				var title= row;
				try{
					var actual_content=JSON.parse($("textarea[name='"+title+"']").val());
				}
				catch(err){
					console.log(err);
				}
				var editable_config="<div class='config_display'>";
				if(actual_content){
					editable_config+="</br><b>Configuration:</b></br>";
					for(var i in actual_content) {
						editable_config+="<p class='prefix_element'>URI:<input class='uri_config' value='"+actual_content[i].uri+"' type='text'>&nbsp&nbsp";
						editable_config+="priority:<input class='prio_config' value='"+actual_content[i].p+"' type='number' min=1>&nbsp&nbsp";
						editable_config+="<input class='delete_config' value='remove' type='button'></p>";
					}
				}
				else{
					editable_config+="</br><b> No configuration found. New:</b></br>";
				}
				editable_config+="</div>";			
				$("#config_addprefix_main_div input[type='hidden']").after(editable_config);
				
				$("#config_addprefix_dialog .delete_config").click(function() {
					//functions for delete a prefix
					this.parentElement.remove();
				});
				$("#config_addprefix_close").click(function (e)
				{
					var info=$("#config_addprefix_main_div input[type='hidden']");
					var title=info.attr("title");
					
					$("#config_addprefix_dialog").fadeOut(300);
					$("#config_addprefix_dialog").remove();
					$("#overlay").remove();
				});
			}
			function save_prefix(row,row2){
				/* Function for save the prefixes */
				try{
					var pref_to_save=$(".config_display p");
					var content=new Array;
					for(var i=0; i<pref_to_save.length; i++  ){
						var element=new Object;
						var p_el = pref_to_save[i];
						var uri_el = p_el.getElementsByClassName("uri_config")[0].value;
						var prio_el= p_el.getElementsByClassName("prio_config")[0].value;
						element["uri"]=uri_el;
						element["p"]=prio_el;
						content.push(element);
					}
					var json_content = JSON.stringify(content);
					var info=$("#config_addprefix_main_div input[type='hidden']");
					var title=info.attr("title");
					var txtarea=$("textarea[name='"+title+"']");
					txtarea.attr("value",json_content);
					$("table #"+info.attr("value")).attr("disabled",false);
					alert("The prefixes have been set. SAVE the table for confirm the changes");
				}
				catch(err){
					console.log(err);
				}
			}
			function append_prefix(row,row2){
			/* Appends a prefix to the endpoint */
				var prefix_to_add=$("#prefix_url").attr("value");
				var prio_to_add=$("#prefix_prio").attr("value");
				var new_prefix="";
				
				new_prefix+="<p class='prefix_element'>URI:<input class='uri_config' value='"+prefix_to_add+"' type='text'>&nbsp&nbsp";
				new_prefix+="priority:<input class='prio_config' value='"+prio_to_add+"' type='number' min=1>&nbsp&nbsp";
				new_prefix+="<input class='delete_config' value='remove' type='button'></p>";
				
				$("#config_addprefix_main_div .config_display").append(new_prefix);
				
				$("#config_addprefix_dialog .delete_config").click(function() {
					//functions for delete a prefix
					this.parentElement.remove();
				});
			}
			function move_example(element,move){
			/* Moves up or down the example selected*/
				var p_to_move=element.parentElement;
				if(move=="up"){
					var p_next=$();
					if(p_to_move.previousSibling) $(p_to_move.previousSibling).before(p_to_move);
				}
				else{
					var p_next=$();
					if(p_to_move.nextSibling) $(p_to_move.nextSibling).after(p_to_move);
				}
			}
			function remove_example(element){
			/* Removes the example selected */
				element.parentElement.remove();//removes the entire <p>..</p>
			}
			function view_example(element,row,row2,write){
			/* opens in a new page the example */
				target=element.parentElement;
				var ex_id;
				if(target.getElementsByClassName("example_id").length>0 && !write) ex_id=target.getElementsByClassName("example_id")[0].value;
				if(target.getElementsByClassName("example_rw_id").length>0 && write) ex_id=target.getElementsByClassName("example_rw_id")[0].value;
				var href="";
				if(ex_id) href="../index.php?graph="+ex_id +"&multiple_search=false";
				else{
					var ex_uri=target.getElementsByClassName("example_uri")[0].value;
					var ep=$("input."+row2+".url_field").attr("value");
					href="../index.php?uri="+ex_uri+"&sparql="+ep;
				}
				
				window.open(href, 'mywin');
			}
			function save_example(row,row2){
				/* Function for save the prefixes */
				try{
					var pref_to_save=$("#config_examples_main_div .config_display p");
					var content=new Array;
					for(var i=0; i<pref_to_save.length; i++  ){
						var id_el=null;
						var rw_id_el=null;
						var mail_el=null;
						var element=new Object;
						var p_el = pref_to_save[i];
						var uri_el = p_el.getElementsByClassName("example_uri")[0].value;
						var name_el= p_el.getElementsByClassName("example_name")[0].value;
						if(p_el.getElementsByClassName("example_id").length>0)   id_el= p_el.getElementsByClassName("example_id")[0].value;
						if(p_el.getElementsByClassName("example_rw_id").length>0)rw_id_el= p_el.getElementsByClassName("example_rw_id")[0].value;
						if(p_el.getElementsByClassName("example_mail").length>0) mail_el= p_el.getElementsByClassName("example_mail")[0].value;
						element["uri"]=uri_el;
						element["name"]=name_el;
						if(id_el) element["id"]  = id_el;
						if(rw_id_el) element["readwrite_id"]  = rw_id_el;
						if(mail_el) element["mail"]=mail_el;
						content.push(element);
					}
					var json_content = JSON.stringify(content);
					var info=$("#config_examples_main_div input[type='hidden']");
					var title=info.attr("title");
					var txtarea=$("textarea[name='"+title+"']");
					txtarea.attr("value",json_content);
					$("table #"+info.attr("value")).attr("disabled",false);
					
					var info=$("#config_examples_main_div input[type='hidden']");
					$("table #"+info.attr("value")).attr("disabled",false);
					alert("the examples have been set. SAVE the table for confirm the changes");
				}
				catch(err){
					console.log(err);
				}
			}
			
			function example_search(row,row2){
			/* Function for insert the examples for an endpoint */
				var ep=$("input."+row2+".url_field").attr("value");
				call="../admin/admin_function.php?examples="+ep;
				var overlay='<div id="overlay" style="display: block;"></div>';
				$("body").append(overlay);
				
				var dialog="<div id='config_examples_dialog' title='Choose the example'><div id='config_examples_title' >Example for "+ep+".<a id='config_examples_close' title='Close'>Close</a></div>";
				dialog+="<div id='config_examples_main_div'>";
				dialog+="<input type='hidden' value='"+row2+"' title="+row+">";
				dialog+="</br><input type='button' value='SAVE' onclick=\"save_example('"+row+"','"+row2+"')\">";
				dialog+="</br></br><p>Insert an uri or choose an example to load from below:</p>";
				dialog+="</br><p>Name:<input type='text' class='config_example_name'> </p>";
				dialog+="<p>URI:&nbsp&nbsp&nbsp<input type='text' class='config_example_uri'> </p>";
				dialog+="</br><p><input type='button' class='config_example_add_normal' value='ADD'> </p>";
				dialog+="</br></br><p> Choose the examples </p></br>";
				
				dialog+="</div></div>";
				$("body").append(dialog);
				
				var title= row;
				try{
					var actual_content=JSON.parse($("textarea[name='"+title+"']").val());
				}
				catch(err){
					console.log(err);
				}
				var editable_config="";
				if(actual_content){
					editable_config+="</br><b>Examples:</b></br><div class='config_display'>";
					for(var i in actual_content) {
						editable_config+="<p class='example_element'>NAME:&nbsp&nbsp<input class='example_name' value='"+actual_content[i].name+"' type='text'></br>";
						editable_config+="URI:&nbsp&nbsp&nbsp&nbsp<input class='example_uri' value='"+actual_content[i].uri+"' type='text'></br>";
						if(actual_content[i].id) editable_config+="ID:&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp<input class='example_id' value='"+actual_content[i].id+"' type='text'></br>";
						if(actual_content[i].readwrite_id) editable_config+="RW ID:<input class='example_rw_id' value='"+actual_content[i].readwrite_id+"' type='text'></br>";
						if(actual_content[i].mail) editable_config+="MAIL:&nbsp&nbsp<input class='example_mail' value='"+actual_content[i].mail+"' type='text'></br>";
						editable_config+="<input class='view_example' value='view' type='button' onclick=\"view_example(this,'"+row+"','"+row2+"',false)\">&nbsp&nbsp";
						if(actual_content[i].readwrite_id) editable_config+="<input class='edit_example' value='edit' type='button' onclick=\"view_example(this,'"+row+"','"+row2+"',true)\">&nbsp&nbsp";
						else editable_config+="<input class='edit_example' value='edit' type='button' disabled \">&nbsp&nbsp";
						editable_config+="<input class='delete_example' value='remove' type='button' onclick=\"remove_example(this)\">&nbsp&nbsp";
						editable_config+="<input class='move_example' value='move UP' type='button' onclick=\"move_example(this,'up')\">&nbsp&nbsp";
						editable_config+="<input class='move_example' value='move DOWN' type='button' onclick=\"move_example(this,'down')\"></p>";
					}
				}
				else{
					editable_config+="</br><b> No Examples found. New:</b></br><div class='config_display'>";
				}
				editable_config+="</div>";			
				$("#config_examples_main_div input[type='hidden']").after(editable_config);
				
				var title= row;
				
				
				$(".config_example_add_normal").click(function(){
				/* Appends a new example */
					var name=$(".config_example_name").attr('value');
					var uri = $(".config_example_uri").attr('value');
					
					var editable_config="";
					editable_config+="<p class='example_element'>NAME:&nbsp&nbsp<input class='example_name' value='"+name+"' type='text'></br>";
					editable_config+="URI:&nbsp&nbsp&nbsp&nbsp<input class='example_uri' value='"+uri+"' type='text'></br>";
					editable_config+="<input class='view_example' value='view' type='button' onclick=\"view_example(this,'"+row+"','"+row2+"',false)\">&nbsp&nbsp";
					editable_config+="<input class='edit_example' value='edit' type='button' disabled onclick=\"view_example(this,'"+row+"','"+row2+"',true)\">&nbsp&nbsp";
					editable_config+="<input class='delete_example' value='remove' type='button' onclick=\"remove_example(this)\">&nbsp&nbsp";
					editable_config+="<input class='move_example' value='move UP' type='button' onclick=\"move_example(this,'up')\">&nbsp&nbsp";
					editable_config+="<input class='move_example' value='move DOWN' type='button' onclick=\"move_example(this,'down')\"></p>";
					
					$("#config_examples_main_div .config_display").append(editable_config);
				});
				
				$.getJSON( call, function( data ) {
				/* Constructs the html structure. */
					// console.log(data);
					if(data.length==0){
						var content="<p><b></br> Examples not found.</br> Save some graphs for the endpoint selected and return here.</b></p>";	
						$("#config_examples_main_div").append(content);
						return;
					}
					
					var content="<div class='example_show'><ul>";
					for(var i=0;i<data.length;i++){
						var element_title=data[i].title?(data[i].title):("No Title");
						content+="<li><input class='add_example' type='button' value='ADD' id='"+data[i].id+"' readwrite_id='"+data[i].readwrite_id+"' initial_uri='"+data[i].initial_uri+"' mail='"+data[i].mail+"' g_title='"+data[i].title+"'> <input class='view_example' type='button' value='VIEW' id='"+data[i].id+"' initial_uri='"+data[i].initial_uri+"'> <input class='edit_example' type='button' value='EDIT' id='"+data[i].readwrite_id+"'> &nbsp <b>Title:</b> "+element_title+"&nbsp <b>URI:</b> "+data[i].initial_uri+"&nbsp <b>creator's email:</b> "+data[i].mail+"</li>";
					}
					content+="</ul></div>";
					$("#config_examples_main_div").append(content);
					//Triggers
					$("#config_examples_main_div ul input.view_example").click(function(e){
						/* opens in a new page the example */
						target=e.target;						
						var href="../index.php?graph="+target.id +"&multiple_search=false";						
						window.open(href, 'mywin');
					});
					$("#config_examples_main_div ul input.edit_example").click(function(e){
						/* opens in a new page the example */
						target=e.target;						
						var href="../index.php?graph="+target.id +"&multiple_search=false";						
						window.open(href, 'mywin');
					});
					$("#config_examples_main_div ul input.add_example").click(function(e){
						/* Adds the example to the endpoint. */
						target=e.target;						
						var name=target.getAttribute('g_title');
						var uri =target.getAttribute('initial_uri');
						var readwrite_id = target.getAttribute('readwrite_id');
						
						var ex_id = target.id;
						var ex_mail = target.getAttribute('mail');
						
						var editable_config="";
						editable_config+="<p class='example_element'>NAME:&nbsp&nbsp<input class='example_name' value='"+name+"' type='text'></br>";
						editable_config+="URI:&nbsp&nbsp&nbsp&nbsp<input class='example_uri' value='"+uri+"' type='text'></br>";
						if(ex_id) editable_config+="ID:&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp<input class='example_id' value='"+ex_id+"' type='text'></br>";
						if(readwrite_id) editable_config+="RW ID:<input class='example_rw_id' value='"+readwrite_id+"' type='text'></br>";
						if(ex_mail) editable_config+="MAIL:&nbsp&nbsp<input class='example_mail' value='"+ex_mail+"' type='text'></br>";
						editable_config+="<input class='view_example' value='view' type='button' onclick=\"view_example(this,'"+row+"','"+row2+"',false)\">&nbsp&nbsp";
						if(readwrite_id) editable_config+="<input class='edit_example' value='edit' type='button'  onclick=\"view_example(this,'"+row+"','"+row2+"',true)\">&nbsp&nbsp";
						else editable_config+="<input class='edit_example' value='edit' type='button' disabled \">&nbsp&nbsp";
						editable_config+="<input class='delete_example' value='remove' type='button' onclick=\"remove_example(this)\">&nbsp&nbsp";
						editable_config+="<input class='move_example' value='move UP' type='button' onclick=\"move_example(this,'up')\">&nbsp&nbsp";
						editable_config+="<input class='move_example' value='move DOWN' type='button' onclick=\"move_example(this,'down')\"></p>";
						
						$("#config_examples_main_div .config_display").append(editable_config);
					});
				});
				$("#config_examples_close").click(function (e)
				{
					$("#config_examples_dialog").fadeOut(300);
					$("#config_examples_dialog").remove();
					$("#overlay").remove();
				});
			}
			function txtarea_changed(row,row2){
			/* Function for update the input text bind with the textarea currently changed*/
				var new_value=$("textarea[name='"+row+"']").val();
				$("input[name='"+row+"']").attr('value',new_value);	
				row_changed(row2);//Notifies the change.
			}
			function insert_blank_row(){
			/* Inserts a blank row in the table. */
				var current_number=$("#numer_of_row").attr("value");
				var txtarea_uriassociated_onchange="onchange=\"txtarea_changed('field["+current_number+"][uri_associated]','row_"+current_number+"')\" ";
				var txtarea_example_onchange="onchange=\"txtarea_changed('field["+current_number+"][examples]','row_"+current_number+"')\" ";
				var txtarea_suggest_onchange="onchange=\"txtarea_changed('field["+current_number+"][suggest]','row_"+current_number+"')\" ";
				var button_example_click="onclick=\"example_search('field["+current_number+"][examples]','row_"+current_number+"')\" ";
				var button_addprefix_click="onclick=\"add_uri_prefix('field["+current_number+"][uri_associated]','row_"+current_number+"')\" ";
				var button_suggests_click="onclick=\"edit_suggest('field["+current_number+"][suggest]','row_"+current_number+"')\" ";
				var delete_click="onclick=\"delete_row('row_"+current_number+"')\" ";
				
				var row="<tr><input id='row_"+current_number+"' value='added' type='hidden' name='field["+current_number +"][add]' >";
				row+="<td><input class='row_"+current_number+" url_field' type='text' name='field["+current_number +"][url]'  value=''></td>"
				+"<td><input class='title_field' type='text' name='field["+current_number +"][title]'  value=''></td>"
				+"<td><input class='active_field' type='hidden' name='field["+current_number +"][active]'  value='0'>"
				+"<input class='active_field' type='checkbox' name='field["+current_number +"][active]'  value='1' checked></td>"
				+"<td><input class='order_field' type='number' name='field["+current_number +"][order]'  value='"+(parseInt(current_number)+1)+"'></td>"
				+"<td><input class='uri_associated_field' type='text' name='field["+current_number +"][uri_associated]'  value=''>"
				+"<textarea rows='1' class='uri_associated_field' type='text' name='field["+current_number+"][uri_associated]'  value='' "+txtarea_uriassociated_onchange+"></textarea>"
				+"<input class='uri_associated_button' type='button' value='EDIT' "+button_addprefix_click+"></td>"
				
				+"<td><input class='limit_field' type='number' name='field["+current_number +"][limit]'  value='1000000'></td>"
				+"<td><input class='search_type_field' type='text' name='field["+current_number +"][search_type]'  value='none'></td>"
				+"<td><input class='examples_field' type='text' name='field["+current_number +"][examples]'  value=''>"
				+"<textarea rows='1' class='examples_field' type='text' name='field["+current_number+"][examples]'  value='' "+txtarea_example_onchange+"></textarea>"
				+"<input class='examples_button' type='button' value='EDIT' "+button_example_click+"></td>"
				
				+"<td><input class='blank_node_field' type='hidden' name='field["+current_number +"][blank_node]'  value='0'>"
				+"<input class='blank_node_field' type='checkbox' name='field["+current_number +"][blank_node]'  value='1'></td>"
				+"<td><input class='suggest_field' type='text' name='field["+current_number +"][suggest]'  value=''>"
				+"<textarea rows='1' class='suggest_field' type='text' name='field["+current_number+"][suggest]'  value='' "+txtarea_suggest_onchange+"></textarea> "
				+"<input class='suggest_button' type='button' value='EDIT' "+button_suggests_click+"></td>"
				
				+"<td><input class='delete_field' type='button' value='delete_row' "+delete_click+"></td>";
				row+="<tr>";
				
				$("table").append(row);
				$("#numer_of_row").attr("value",parseInt($("#numer_of_row").attr("value"))+1);
			}
		</script>
	</head>
    <body>
        <fieldset>
            <legend>Welcome <?php echo $admin->get_nicename(); ?></legend>
                <p>
                    Here are some of the basic informations
                </p>
                <p>
                    Username: <?php echo $_SESSION['admin_login']; ?>
                </p>
                <p>
                    Email: <?php echo $admin->get_email(); ?>
                </p>
				<div id="configuration">
				<form id="edit_endpoints" action="edit_endpoints.php" onsubmit="return validateForm()" method="post">
				<p>
					<input type="button" onclick="javascript:window.location.href='logout.php'" value="logout" />
				</p>
				<!-- <h1>Endpoints Configuration (Click on the class to sort the table)</h1> -->
				<h1 title='Endpoint_configuration'>Endpoints Configuration (Click on the class to sort the table)</h1>
  
				<table id="endpoints_table" cellspacing="0" cellpadding="0">
<?php 
	//Loads the list of Endpoints from the Database.
	global $db;
	$info_icon = "../images/icons/info_icon.png";
	$table_header = "<thead><tr>";
	$output="<tbody>";
	$count=0;
	$info = $db->get_results("SELECT * FROM endpoints as e ORDER BY e.order");
	// var_dump($info);
	if($info){
		foreach($info as $i){
			$output.="<tr><input id='row_$count' value='".$i->url."' type='hidden' name='field[".$count ."][modified]' disabled>";
			$current_id='row_'.$count;
			$f_onchange="onchange=\"row_changed('$current_id')\" }";
			foreach($i as $c=>$c_value){
				switch($c){
					case "url":
						if($count==0){							
							$table_header.="<th class=\"$c\"><span >".$c ." &nbsp <img class='info_img' src=\"$info_icon\" title=\"The Url of the endpoint\" > &nbsp </span></th>";
						}
						$output.="<td><input class='$current_id ".$c ."_field' type='text' name='field[".$count ."][".$c ."]'  value='".$c_value ."' $f_onchange>";
						$output.="</td>";
						break;
					case "title":
						if($count==0){
							$table_header.="<th class=\"$c\"><span>".$c ."&nbsp <img class='info_img' src=\"$info_icon\" title=\"The name for the endpoint to display\" > &nbsp</span></th>";
						}
						$output.="<td><input class='".$c ."_field' type='text' name='field[".$count ."][".$c ."]'  value='".$c_value ."' $f_onchange>";
						$output.="</td>";
						break;
					case "active":
						if($count==0){
							$table_header.="<th class=\"".$c."_ep\"><span>".$c ."&nbsp <img class='info_img' src=\"$info_icon\" title=\"If checked, displays the endpoint\" > &nbsp</span></th>";
						}
						$checked="";
						if($c_value)$checked="checked";
						$output.="<td><input class='".$c ."_field' type=\"hidden\" name=\"field[".$count ."][".$c ."]\" value=\"0\" /><input type='checkbox' value=\"1\" name='field[".$count ."][".$c ."]'  $checked  $f_onchange>";
						$output.="</td>";
						break;
					case "order":
						if($count==0){
							$table_header.="<th class=\"$c\"><span>".$c ."&nbsp <img class='info_img' src=\"$info_icon\" title=\"The position in the list of the endpoints\" > &nbsp</span></th>";
						}
						$output.="<td><input class='".$c ."_field' type='number' name='field[".$count ."][".$c ."]'  min=1 value='".$c_value ."' $f_onchange>";
						$output.="</td>";
						break;
					case "uri_associated":
						$uri_add_onclick="onclick=\"add_uri_prefix('field[".$count ."][".$c ."]','$current_id')\" ";
						$txtarea_onchange="onchange=\"txtarea_changed('field[".$count ."][".$c ."]','$current_id')\" ";
						if($count==0){
							$table_header.="<th class=\"$c\"><span>Prefix associated  &nbsp <img class='info_img' src=\"$info_icon\" title=\"The list of prefixes associated to the endpoint. One element is like:\n {&#34;uri&#34;:&#34;http://sws.geonames.org&#34;,&#34;p&#34;:&#34;1&#34;} where 'uri' is the prefix and 'p' is the priorities associate and it works like: if the priority is less or equal to the value set in config.php to '\$multiple_endpoint_priority', then the endpoint is used to make a search for results \" > &nbsp</span></th>";
						}
						$output.="<td><input class='".$c ."_field' type='text' name='field[".$count ."][".$c ."]'  value='".$c_value ."' $f_onchange>";
						$output.="<textarea rows='1' class='".$c ."_field' type='text' name='field[".$count ."][".$c ."]'  value='".$c_value ."' $txtarea_onchange>$c_value</textarea>";
						$output.="<input class='".$c ."_button' type='button' value='EDIT' $uri_add_onclick>";
						$output.="</td>";
						break;
					case "limit":
						if($count==0){
							$table_header.="<th class=\"$c\"><span>".$c ."&nbsp <img class='info_img' src=\"$info_icon\" title=\"The max number of rows returned from the query on the endpoint during the navigation. For some endpoint, like facforge, could be useful to limit the results number for reduce the users waiting time.\" > &nbsp</span></th>";
						}
						$output.="<td><input class='".$c ."_field' type='number' name='field[".$count ."][".$c ."]' min=1 value='".$c_value ."' $f_onchange>";
						$output.="</td>";
						break;
					case "search_type":
						if($count==0){
							$table_header.="<th class=\"$c\"><span>".$c ."&nbsp <img class='info_img' src=\"$info_icon\" title=\"The keyword search method. The possible values are: -none: keyword search disabled -owlim: search with owlim, use it if the endpoint supports owlim  -regex: use regex to make the query  -contains: use contains for the query\" > &nbsp</span></th>";
						}
						$output.="<td><input class='".$c ."_field' type='text' name='field[".$count ."][".$c ."]'  value='".$c_value ."' $f_onchange>";
						$output.="</td>";
						break;
					case "examples":
						if($count==0){
							$table_header.="<th class=\"$c\"><span>".$c ."&nbsp <img class='info_img' src=\"$info_icon\" title=\"The examples for the endpoint. Clicking on 'EDIT' is possible to select the example from a list of examples saved for the endpoint. For remove an example just edit the text\" > &nbsp </span></th>";
						}
						$example_onclick="onclick=\"example_search('field[".$count ."][".$c ."]','$current_id')\" ";
						$txtarea_onchange="onchange=\"txtarea_changed('field[".$count ."][".$c ."]','$current_id')\" ";
						$output.="<td><input class='" . $c . "_field' type='text' name='field[" . $count . "][" . $c . "]'  value='" . str_replace("'", "&apos;", $c_value) . "' $f_onchange>";
						$output.="<textarea rows='1' class='" . $c . "_field' type='text' name='field[" . $count . "][" . $c . "]'  value=\"" . str_replace("'", "&apos;", $c_value) . "\" $txtarea_onchange>$c_value</textarea>";
						$output.="<input class='".$c ."_button' type='button' value='EDIT' $example_onclick>";
						$output.="</td>";
						break;
					case "blank_node":
						if($count==0){
							$table_header.="<th class=\"$c\"><span>".$c ." &nbsp <img class='info_img' src=\"$info_icon\" title=\"The query returns also the blank nodes and adds them as nodes into the graph\" > &nbsp </span></th>";
						}
						$checked="";
						if($c_value)$checked="checked";
						$output.="<td><input class='".$c ."_field' type='hidden' name='field[".$count ."][".$c ."]' value='0'><input type='checkbox' value=\"1\" name='field[".$count ."][".$c ."]' value='1' $checked $f_onchange>";
						$output.="</td>";
						break;
					case "suggest":
						if($count==0){
							$table_header.="<th class=\"$c\"><span> Keywords &nbsp <img class='info_img' src=\"$info_icon\" title=\"The list of properties for search the keyword. Insert '?p'  for search in every properties. The search are made in cascade from the first properties to the last one. If the number of results are not reached with the first property, then goes on. An example is:\n{ &#34;0&#34;:&#34; rdfs:label&#34;,&#34; 1&#34;:&#34; foaf:name&#34;,&#34; 2&#34;:&#34; ?p&#34;}\" > &nbsp </span></th>";
						}
						$suggest_onclick="onclick=\"edit_suggest('field[".$count ."][".$c ."]','$current_id')\" ";
						$txtarea_onchange="onchange=\"txtarea_changed('field[".$count ."][".$c ."]','$current_id')\" ";
						$output.="<td><input class='".$c ."_field' type='text' name='field[".$count ."][".$c ."]' value='".$c_value ."' $f_onchange>";
						$output.="<textarea rows='1' class='".$c ."_field' type='text' name='field[".$count ."][".$c ."]'  value='".$c_value ."' $txtarea_onchange>$c_value</textarea>";
						$output.="<input class='".$c ."_button' type='button' value='EDIT' $suggest_onclick>";
						$output.="</td>";
						break;
				}
			}
			//Adds the button for delete a row.
			if($count==0)$table_header.="<th><span>DELETE</span></th>";	
			$f_click="onclick=\"delete_row('$current_id')\" ";
			$output.="<td><input class='delete_field' type='button' value='delete_row' $f_click>";
			$output.="</td>";
			$output.="</tr>";
			$count++;
		}
		$output.="<input id='numer_of_row' type='hidden' disabled value=$count>";
	}
	else
		return 'Endpoints not provided.';
	print $table_header."</tr></thead>";
	print $output."</tbody>";
?>			
			</table>
			<div class="form_button">
				<input class="button_insert" type="button" value="Add" onclick="insert_blank_row()"> <input type="reset" value="Reset" /> <input type="submit" value="Save" />
			</div>
			</form>
			<script>
				$('#endpoints_table').tablesorter({// define a custom text extraction function 
					textExtraction: function(node) { 
						// extract data from markup and return it  
						return node.childNodes[0].value;
					}
				});//Adds table sort functionality.
				$("#endpoints_table").bind("sortStart",function() { 
					$("#overlay").show(); 
				}).bind("sortEnd",function() { 
					$("#overlay").hide(); 
				});
				$("td input").change(function(){
					console.log("change");
					//Resets the order for the table sorter
					$('#endpoints_table').unbind();
					$('#endpoints_table th').unbind();
					$('#endpoints_table').tablesorter({// define a custom text extraction function 
						textExtraction: function(node) { 
							// extract data from markup and return it  
							return node.childNodes[0].value;
						}
					});//Adds table sort functionality.
				})
			</script>
			</div>
        </fieldset>
    </body>
</html>
