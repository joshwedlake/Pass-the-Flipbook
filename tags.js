//Planet View
//(C) Josh Wedlake 2011, GPL v3, see LICENSE for information.

var canvas,context,offscreen_canvas,offscreen_context,interval_timer;

var status_line;

var tags=[];
var animations=[];
//selected item
//[0] = 0 is nothing, 1 is tag, 2 is animation
//[1] = item id
var selected_item=[0,-1];
var loaded_items=[]; //an array of [type,id] pairs

//fraction of screen space that a selected planet takes up;
var default_tag_size=0.03;
var default_animation_size=0.04;
var default_tag_text_size=8;
var default_animation_text_size=12;
var default_link_remove_size=0.1; //proportion of planet size to make link remover
var default_separation_multiplier=4; //absolute minumum separation
var default_allergy_separation_multiplier=3;
var default_generational_step=0.75;
var default_opacity_step=0.7;

var default_drag_swell_multiplier=1.1;
var default_swell_size=0.2; //proportion of normal size extra added when swelling
var default_swell_trigger=1.5; //proximity of the mouse in terms of screen width
var default_magnetic_trigger=1.2;
//the proportion of size smaller that children are to parents
var default_anim_opacity_speed=0.3;
var default_anim_recentre_speed=0.1;
var default_anim_child_recentre_speed=0.5;
var default_anim_swell_speed=0.3;
var default_anim_drag_speed=0.7;
var default_anim_allergy_speed=0.5;

var xmlhttp_tags,xmlhttp_tags_busy=false;
var xmlhttp_temp,xmlhttp_temp_busy=false;
var mouse_x=0,mouse_y=0;
var mouse_x_pixels=0,mouse_y_pixels=0;
var mouse_start_x=0;mouse_start_y=0;
var mouse_drag=false;

var add_tag_height=150,add_tag_width=200;
var add_tag_radius=Math.pow(Math.pow(add_tag_width/2,2)+Math.pow(add_tag_height/2,2),0.5);
var play_button_height=150,play_button_width=200;
var play_button_radius=Math.pow(Math.pow(play_button_width/2,2)+Math.pow(play_button_height/2,2),0.5);

function init_tags(){
	//send data request
	if(seq_id!=-1){
		//add an animation to the graph in the very centre
		animations[seq_id]=[];
		copy_basic_data(animations[seq_id],first_item_data,2);
		animations[seq_id]=setup_first_item(animations[seq_id],2);
		selected_item=[2,seq_id];
	}
	else if(tag_id!=-1){
		//add a tag to the graph in the very centre
		tags[tag_id]=[];
		copy_basic_data(tags[tag_id],first_item_data,1);
		tags[tag_id]=setup_first_item(tags[tag_id],1);
		selected_item=[1,tag_id];
	}
	loaded_items.push(selected_item.slice(0));
	
	get_data(selected_item);
	
	//debug line
	status_line = document.getElementById('status_line');
	
	//get canvas
	canvas = document.getElementById('tags_canvas');
	context = canvas.getContext('2d');
	context.fillStyle = "#fff";
	
	//build an offscreen canvas for flipping
	offscreen_canvas = document.createElement("canvas");
	offscreen_context = offscreen_canvas.getContext("2d");
	
	//resize canvas to window
	resize_canvas();
	
	//attach event to window resize
	window.addEventListener('resize', resize_canvas, false);
	
	//add mouse click events
	canvas.addEventListener('mousedown', ev_mousedown, false);
	canvas.addEventListener('mouseout', ev_mouseout, false);
	canvas.addEventListener('mouseup', ev_mouseup, false);
	canvas.addEventListener('mousemove', ev_mousemove, false);

	//add timer
	interval_timer=setInterval("update_display();",83);
}

function setup_first_item(item_array,item_type){
	item_array['draw_centre']=[0,0];
	item_array['draw_radius']=(item_type==2?default_animation_size:default_tag_size);
	item_array['draw_opacity']=0;
	item_array['generation']=0;
	item_array['links']=[];
	item_array['processed']=false; //has been moved by rearrange_tree
	//though the graph does not have any direction, it is organised outwards from the selected item
	//'draw_parent' is the item drawn as this item's parent
	item_array['draw_parent']=[0,-1];
	//whether connections have been loaded or not
	item_array['loaded']=false;
	//there is now enough data to draw without running into undefineds
	item_array['ready']=true;
	return item_array;
}

function get_data(data_ref){
	if(!xmlhttp_tags_busy){
		xmlhttp_tags_busy=true;
		//send an ajax request
		if (window.XMLHttpRequest){// code for IE7+, Firefox, Chrome, Opera, Safari
			xmlhttp_tags=new XMLHttpRequest();
		}
		else {// code for IE6, IE5
			xmlhttp_tags=new ActiveXObject("Microsoft.XMLHTTP");
		}
		
		//voting process callback
		xmlhttp_tags.onreadystatechange=new Function("on_item_load();");
		
		//send request
		xmlhttp_tags.open("POST",'tag_helper.php',true);
		xmlhttp_tags.setRequestHeader("Content-type","application/x-www-form-urlencoded");
		if(data_ref[0]==1)xmlhttp_tags.send("mode=10"+"&tag_id="+data_ref[1].toString()); //mode 10 = everything to do with tag
		else if(data_ref[0]==2)xmlhttp_tags.send("mode=7"+"&seq_id="+data_ref[1].toString()); //mode 7 = an array of animations
	}
}

function get_nearest_item(test_x,test_y){
	//gets the closest item, excluding the drag
	closest_item_ref=[0,-1];
	closest_distance=-1;
	for(i=loaded_items.length-1;i>=0;i--){
		item=resolve_reference(loaded_items[i]);
		
		if(loaded_items[i][0]==selected_item[0] && loaded_items[i][1]==selected_item[1] && mouse_drag==false){
			//the click is on the selected item, and its not the end of a drag
			//instead of testing the selected item, we need to test the selected item's connection points
			//as this could be the start of a drag to remove a connection.
			//connection point refs are [3,position in selected item's links array]
			for(j=item['links'].length-1;j>=0;j--){
				child=resolve_reference(item['links'][j]);
				
				centre_to_centre_vec=[child["draw_centre"][0]-item["draw_centre"][0],child["draw_centre"][1]-item["draw_centre"][1]];
				centre_to_centre_length=vector_length(centre_to_centre_vec);
				parent_to_edge_vec=vector_scalar(centre_to_centre_vec,item["draw_radius"]/centre_to_centre_length);
				connection_loc=[item["draw_centre"][0]+parent_to_edge_vec[0],item["draw_centre"][1]+parent_to_edge_vec[1]];
				click_distance=vector_length([test_x-connection_loc[0],test_y-connection_loc[1]]);
				
				if(closest_distance==-1 || click_distance<closest_distance){
					closest_item_ref=[3,j];
					closest_distance=click_distance;
				}
			}
			
		}
		else{
			click_distance=vector_length([test_x-item["draw_centre"][0],test_y-item["draw_centre"][1]]);
			if(closest_distance==-1 || click_distance<closest_distance){
				closest_item_ref=loaded_items[i].slice(0); //copy ref
				closest_distance=click_distance;
			}
		}
	}
	return closest_item_ref;
}

function update_mouse_pos(ev){
	mouse_x_pixels=ev.pageX - canvas.offsetLeft;
	mouse_y_pixels=ev.pageY - canvas.offsetTop;
	mouse_x = (mouse_x_pixels-(context.canvas.width/2))/context.canvas.width;
	mouse_y = (mouse_y_pixels-(context.canvas.height/2))/context.canvas.width;
}

function ev_mousemove(ev){
	update_mouse_pos(ev);
}

function ev_mousedown(ev){
	//find the position of the mouse in draw space
	update_mouse_pos(ev);
	mouse_start_x = mouse_x;
	mouse_start_y = mouse_y;
	
	//start drag
	if(!(selected_item[0]==2 && mouse_x_pixels<add_tag_width && mouse_y_pixels>(context.canvas.height-add_tag_height))
		&& (!(selected_item[0]==2 && mouse_x_pixels>(context.canvas.width-play_button_width) && mouse_y_pixels>(context.canvas.height-play_button_height))))
		mouse_drag=get_nearest_item(mouse_x,mouse_y);
}

function ev_mouseout(ev){
	//abort the drag
	mouse_drag=false;
}

function resolve_reference(resolve_item_ref){
	if(resolve_item_ref[0]==1)resolve_item=tags[resolve_item_ref[1]]; //tag
	else if(resolve_item_ref[0]==2)resolve_item=animations[resolve_item_ref[1]]; //animation
	return resolve_item;
}

function add_tag_named(animation_ref){
	if(!xmlhttp_temp_busy){
		xmlhttp_temp_busy=true;
		animation_item=resolve_reference(animation_ref);
		new_tag_name=prompt("Add tag for "+animation_item["name"],"");
		//replace bad chars and trim leading and trailing whitespace
		new_tag_name=new_tag_name.replace(/[^a-zA-Z0-9 ]+/g,'').replace(/(^\s*)|(\s*$)/gi,"").replace(/[ ]{2,}/gi," ").replace(/\n /,"\n");
		if(new_tag_name!=""){
			//ajax
			if (window.XMLHttpRequest)xmlhttp_temp=new XMLHttpRequest();
			else xmlhttp_temp=new ActiveXObject("Microsoft.XMLHTTP");
			
			xmlhttp_temp.onreadystatechange=new Function("generic_ajax_callback(["+animation_ref[0].toString()+","+animation_ref[1].toString()+"]);");
			
			//send request
			xmlhttp_temp.open("POST",'tag_helper.php',true);
			xmlhttp_temp.setRequestHeader("Content-type","application/x-www-form-urlencoded");
			xmlhttp_temp.send("mode=0"+"&seq_id="+animation_ref[1].toString()+"&tag_name="+encodeURIComponent(new_tag_name));
		}
	}
}

function generic_ajax_callback(load_on_success_ref){
	if (xmlhttp_temp.readyState==4){
		//if it was a success
		if (xmlhttp_temp.status==200){
			//read the response text
			temp_response_data=JSON.parse(xmlhttp_temp.responseText);
			if(temp_response_data["success"]==0)get_data(load_on_success_ref);
			xmlhttp_temp_busy=false;
		}
	}
}

function ev_mouseup(ev){
	//find the position of the mouse in draw space
	update_mouse_pos(ev);
	
	//check if it was in the add new tag space
	if(selected_item[0]==2 && mouse_x_pixels<add_tag_width && mouse_y_pixels>(context.canvas.height-add_tag_height))add_tag_named(selected_item.slice(0));
	else if(selected_item[0]==2 && mouse_x_pixels>(context.canvas.width-play_button_width) && mouse_y_pixels>(context.canvas.height-play_button_height))
		location.href="player.php?id="+selected_item[1];
	else{
		//if the drag has been cancelled, or the 
		if(mouse_drag==false || (Math.abs(mouse_x-mouse_start_x)<0.01 && Math.abs(mouse_y-mouse_start_y)<0.01)){
			closest_item_ref=get_nearest_item(mouse_x,mouse_y);
			if(closest_item_ref[0]!=0){
				closest_item=resolve_reference(closest_item_ref);
				//load that planet if it isn't already loaded
				if(!closest_item["loaded"])get_data(closest_item_ref);
				else select_item(selected_item,closest_item_ref);
			}
			mouse_drag=false;
		}
		else if(mouse_drag!=false){
			//are we making or breaking a connection
			selected_item_object=resolve_reference(selected_item);
			switch (mouse_drag[0]){
				case 3:
					//removing a tag<>animation link
					//check the drop is outside to selected object's click radius
					if(vector_length([selected_item_object['draw_centre'][0]-mouse_x,selected_item_object['draw_centre'][1]-mouse_y])>
						selected_item_object['draw_radius']*default_drag_swell_multiplier){
							dragged_item_ref=selected_item_object['links'][mouse_drag[1]];
							if(selected_item[0]==1 && dragged_item_ref[0]==2){
								if(confirm("Really remove tag "+selected_item_object['name']
									+" from animation "+resolve_reference(dragged_item_ref)['name']+"?"))link_tag_animation(selected_item.slice(0),dragged_item_ref.slice(0),false);
							}
							else if(selected_item[0]==2 && dragged_item_ref[0]==1){
								if(confirm("Really remove tag "+resolve_reference(dragged_item_ref)['name']
									+" from animation "+selected_item_object['name']+"?"))link_tag_animation(dragged_item_ref.slice(0),selected_item.slice(0),false);
							}
							else if(selected_item[0]==1 && dragged_item_ref[0]==1){
								if(confirm("Really remove synonym "+selected_item_object['name']
									+" from animation "+resolve_reference(dragged_item_ref)['name']+"?"))link_tag_synonym(selected_item.slice(0),dragged_item_ref.slice(0),false);
							}
						}
					break;
				case 2:
					//creating an animation<>tag link
					//check the drag is inside the selected item
					if(vector_length([selected_item_object['draw_centre'][0]-mouse_x,selected_item_object['draw_centre'][1]-mouse_y])<
						selected_item_object['draw_radius']*default_drag_swell_multiplier){
						if(selected_item[0]==1){
							if(confirm("Really add tag "+selected_item_object['name']+" to animation "+resolve_reference(mouse_drag)['name']+"?"))
								link_tag_animation(selected_item.slice(0),mouse_drag.slice(0),true);
						}
					}
					break;
				case 1:
					//creating either a tag<>tag link or a tag<>animation link
					//check the drag is inside the area
					if(vector_length([selected_item_object['draw_centre'][0]-mouse_x,selected_item_object['draw_centre'][1]-mouse_y])<
						selected_item_object['draw_radius']*default_drag_swell_multiplier){
						if(selected_item[0]==2){
							if(confirm("Really add tag "+resolve_reference(mouse_drag)['name']+" to animation "+selected_item_object['name']+"?"))
								link_tag_animation(mouse_drag.slice(0),selected_item.slice(0),true);
						}
						else if(selected_item[0]==1){
							if(confirm("Really add synonym "+resolve_reference(mouse_drag)['name']+" to tag "+selected_item_object['name']+"?"))
								link_tag_synonym(mouse_drag.slice(0),selected_item.slice(0),true);
						}
					}
					break;
			}
			mouse_drag=false;
		}
		//otherwise its just a drag which has left the canvas: do nothing.
	}
}


function link_tag_animation(link_tag,link_animation,link_add){
	if(!xmlhttp_temp_busy){
		xmlhttp_temp_busy=true;
		if(link_add)link_mode=1;
		else {
			link_mode=2;
			//remove link from the active graph
			remove_link(link_tag,link_animation);
		}
		if (window.XMLHttpRequest) xmlhttp_temp=new XMLHttpRequest();
		else xmlhttp_temp=new ActiveXObject("Microsoft.XMLHTTP");
		xmlhttp_temp.onreadystatechange=new Function("generic_ajax_callback(["+link_tag[0].toString()+","+link_tag[1].toString()+
			"]);generic_ajax_callback(["+link_animation[0].toString()+","+link_animation[1].toString()+"]);");
		xmlhttp_temp.open("POST",'tag_helper.php',true);
		xmlhttp_temp.setRequestHeader("Content-type","application/x-www-form-urlencoded");
		xmlhttp_temp.send("mode="+link_mode.toString()+"&seq_id="+link_animation[1].toString()+"&tag_id="+link_tag[1].toString());
	}
}

function link_tag_synonym(link_tag,link_synonym,link_add){
	if(!xmlhttp_temp_busy){
		xmlhttp_temp_busy=true;
		if(link_add)link_mode=5;
		else {
			link_mode=4;
			//remove link from the active graph
			 remove_link(link_tag,link_synonym);
		}
		if (window.XMLHttpRequest) xmlhttp_temp=new XMLHttpRequest();
		else xmlhttp_temp=new ActiveXObject("Microsoft.XMLHTTP");
		xmlhttp_temp.onreadystatechange=new Function("generic_ajax_callback(["+link_tag[0].toString()+","+link_tag[1].toString()+
			"]);generic_ajax_callback(["+link_synonym[0].toString()+","+link_synonym[1].toString()+"]);");
		xmlhttp_temp.open("POST",'tag_helper.php',true);
		xmlhttp_temp.setRequestHeader("Content-type","application/x-www-form-urlencoded");
		xmlhttp_temp.send("mode="+link_mode.toString()+"&parent_tag_id="+link_tag[1].toString()+"&child_tag_id="+link_synonym[1].toString());
	}
}


function rotate_vector(p, a) {
	//rotates a point p, angle a around the origin [0,0] 
	x = p[0]*Math.cos(a) - p[1]*Math.sin(a); 
	y = p[0]*Math.sin(a) + p[1]*Math.cos(a); 
	return ([x,y]); 
}

function add_vectors(p, q) {
	x = p[0]+q[0];
	y = p[1]+q[1];
	return ([x,y]); 
}

function vector_length(p) {
	l = Math.pow(Math.pow(p[0],2)+Math.pow(p[1],2),0.5);
	return l; 
}

function vector_scalar(p,s) {
	return ([p[0]*s,p[1]*s]);
}

function get_min_parent_child_sep(child_count,semicircle,child_generation,item_type){
	//calculate a minimum parent-child separation based on spreading round a diameter
	//1.5 is an error margin,itemsize*2 gives an item's dia, * by count gives the circ
	ideal_circumference=1.5*(item_type==2?default_animation_size:default_tag_size)*Math.pow(default_generational_step,child_generation)*2*child_count;
	//double the effective child count if we only have a semicircle to work with
	if(semicircle)ideal_circumference*=2;
	//divide by 2 pi to find the orbit radius
	ideal_parent_child_sep=ideal_circumference/6.28;
	
	//calculate a min parent-child sep based on the default sep given above
	user_set_parent_child_sep=(item_type==2?default_animation_size:default_tag_size)*default_separation_multiplier*Math.pow(default_generational_step,child_generation);
	
	if(user_set_parent_child_sep>ideal_parent_child_sep) return user_set_parent_child_sep;
	else return ideal_parent_child_sep;
}

function get_child_item_locations(child_count,parent_loc,grandparent_loc,child_generation,item_type){
	//this returns a nice spread of locations
	locations=[];
	//are an x and y provided for the grandparent location?
	if(grandparent_loc.length==2){
		angle_spread=2.5;
		child_vec=[(parent_loc[0]-grandparent_loc[0])*default_generational_step,(parent_loc[1]-grandparent_loc[1])*default_generational_step];
		//get the child vec's length
		child_vec_length=vector_length(child_vec);
		//get the minimum parent child orbit distance
		min_parent_child_sep=get_min_parent_child_sep(child_count,true,child_generation,item_type);
		//if the vector based on grandparent>parent is too small, use the min vector
		if(min_parent_child_sep>child_vec_length)child_vec=vector_scalar(child_vec,min_parent_child_sep/child_vec_length);
		
		//if there's only one child it will be colinear with grandparent,parent
		if(child_count==1)locations.push(add_vectors(parent_loc,child_vec));
		else{
			rotate_step=angle_spread/(child_count-1);
			child_vec=rotate_vector(child_vec,-(angle_spread/2));
			locations.push(add_vectors(parent_loc,child_vec));
			for(i=1;i<child_count;i++){
				child_vec=rotate_vector(child_vec,rotate_step);
				locations.push(add_vectors(parent_loc,child_vec));
			}
			
		}
	}
	//othertwise there is no grandparent, just spin out round centre
	//the vector to the first position is calced based on which existing child is available
	else{
		angle_spread=6.28;
		//get the minimum parent child orbit distance
		min_parent_child_sep=get_min_parent_child_sep(child_count,false,child_generation,item_type);
		//first one is straight up
		child_vec=[0,-min_parent_child_sep];
		locations.push(add_vectors(parent_loc,child_vec));
		if(child_count>1){
			rotate_step=angle_spread/child_count;
			for(i=1;i<=child_count;i++){
				child_vec=rotate_vector(child_vec,rotate_step);
				locations.push(add_vectors(parent_loc,child_vec));
			}
		}
	}
	return locations;
}

function trim_string(input_string,string_max_length){
	if(input_string.length>string_max_length){
		input_string=input_string.substr(0,string_max_length-3)+"...";
	}
	return input_string;
}

function copy_basic_data(copy_target,copy_source,copy_type){
	if(copy_type==2){
		copy_target["name"]=trim_string(copy_source["name"],default_animation_text_size);
		copy_target["animator"]=trim_string(copy_source["animator"],default_animation_text_size);
		copy_target["ago"]=trim_string(copy_source["ago"],default_animation_text_size);
	}
	else if(copy_type==1){
		copy_target["name"]=trim_string(copy_source["name"],default_tag_text_size);
	}

	
	//put more here on expansion
}

function add_items(add_items_type,new_items_array,parent_item_ref){
	parent_item=resolve_reference(parent_item_ref);
	if(add_items_type==1)items_array=tags;
	else if(add_items_type==2)items_array=animations;
	//find out if a grandparent_loc is available
	if(parent_item['draw_parent'][0]==1)grandparent_loc=tags[parent_item['draw_parent'][1]]['draw_centre'];
	else if(parent_item['draw_parent'][0]==2)grandparent_loc=animations[parent_item['draw_parent'][1]]['draw_centre'];
	else grandparent_loc=[];
	//get locations for the objects	
	locations=get_child_item_locations(new_items_array.length,parent_item["draw_centre"],grandparent_loc,parent_item['generation']+1,parent_item_ref[0]);
	//add the items into items_array
	for(temp_add_items_id=new_items_array.length-1;temp_add_items_id>=0;temp_add_items_id--){
		temp_item_row=new_items_array[temp_add_items_id];
		temp_item_row["id"]=parseInt(temp_item_row["id"]);
		//only setup these values if the item isn't loaded
		if(items_array[temp_item_row["id"]]==undefined){
			items_array[temp_item_row["id"]]=[];
			copy_basic_data(items_array[temp_item_row["id"]],temp_item_row,add_items_type);
			items_array[temp_item_row["id"]]['generation']=parent_item['generation']+1;
			items_array[temp_item_row["id"]]['draw_centre']=[parent_item['draw_centre'][0]+locations[temp_add_items_id][0],
				parent_item['draw_centre'][1]+locations[temp_add_items_id][1]];
			items_array[temp_item_row["id"]]['draw_radius']=(add_items_type==2?default_animation_size:default_tag_size)
				*Math.pow(default_generational_step,items_array[temp_item_row["id"]]['generation']);
			items_array[temp_item_row["id"]]['draw_opacity']=0;
			items_array[temp_item_row["id"]]['draw_parent']=[0,-1];
			items_array[temp_item_row["id"]]['links']=[];
			items_array[temp_item_row["id"]]['loaded']=false; //whether connections have been loaded
			items_array[temp_item_row["id"]]['processed']=false; //not yet been moved by rearrange_tree
			items_array[temp_item_row["id"]]['ready']=true;
			//add the item to the parent's links
			add_link(items_array[temp_item_row["id"]],parent_item_ref.slice(0));
			add_link(parent_item,[add_items_type,temp_item_row["id"]]);
			set_item_loaded([add_items_type,temp_item_row["id"]]);
		}
		else{
			copy_basic_data(items_array[temp_item_row["id"]],temp_item_row,add_items_type);
			add_link(items_array[temp_item_row["id"]],parent_item_ref.slice(0));
			add_link(parent_item,[add_items_type,temp_item_row["id"]]);
			set_item_loaded([add_items_type,temp_item_row["id"]]);
		}
	}
	//set the parent item as having had its connections loaded
	parent_item["loaded"]=true;
}

function add_link(parent_item,child_ref){
	//check if child_ref is in parent_item['links']
	for(i=(parent_item['links'].length-1);i>=0 && (!(parent_item['links'][i][0]==child_ref[0] && parent_item['links'][i][1]==child_ref[1]));i--);
	//if it isn't, push it onto the end
	if(i<0 || (!(parent_item['links'][i][0]==child_ref[0] && parent_item['links'][i][1]==child_ref[1]))){
		parent_item['links'].push(child_ref.slice(0));
	}
}

function remove_link(parent_ref,child_ref){
	parent_links_list=resolve_reference(parent_ref)['links'];
	for(i=parent_links_list.length-1;i>=0 && (!(parent_links_list[i][0]==child_ref[0] && parent_links_list[i][1]==child_ref[1]));i--);
	if(i>=0 && parent_links_list[i][0]==child_ref[0] && parent_links_list[i][1]==child_ref[1])parent_links_list.splice(i,1);
	
	child_links_list=resolve_reference(child_ref)['links'];
	for(i=child_links_list.length-1;i>=0 && (!(child_links_list[i][0]==parent_ref[0] && child_links_list[i][1]==parent_ref[1]));i--);
	if(i>=0 && child_links_list[i][0]==parent_ref[0] && child_links_list[i][1]==parent_ref[1])child_links_list.splice(i,1);
}

function on_item_load() {
	//handle response
	if (xmlhttp_tags.readyState==4){
		//if it was a success
		if (xmlhttp_tags.status==200){
			//read the response text
			tags_response_data=JSON.parse(xmlhttp_tags.responseText);
			if(tags_response_data["success"]==0){
				//work out what kind of data has been received
				if(tags_response_data["animations"]!=undefined && tags_response_data["synonyms"]!=undefined){
					//tags are connected to...
					//animations
					add_items(2,tags_response_data["animations"],[1,tags_response_data["call_tag_id"]]);
					//and other tags
					add_items(1,tags_response_data["synonyms"],[1,tags_response_data["call_tag_id"]]);
					//mark the calling tag as loaded and selected
					set_item_loaded([1,tags_response_data["call_tag_id"]]);
					select_item(selected_item.slice(0),[1,tags_response_data["call_tag_id"]]);
				}
				if(tags_response_data["tags"]!=undefined){
					//animations are connected to tags
					add_items(1,tags_response_data["tags"],[2,tags_response_data["call_seq_id"]]);
					//mark the calling animation as loaded and selected
					set_item_loaded([2,tags_response_data["call_seq_id"]]);
					select_item(selected_item.slice(0),[2,tags_response_data["call_seq_id"]]);
				}
			}
		}
		else {
			status_line.innerHTML="Failed to load item.  Please try again.";
		}
		xmlhttp_tags_busy=false;
	}
}

function set_item_loaded(new_loaded_item){
	//check if new_loaded_item is in loaded_items
	for(i=(loaded_items.length-1);i>=0 && (!(loaded_items[i][0]==new_loaded_item[0] && loaded_items[i][1]==new_loaded_item[1]));i--);
	//if it isn't, push it onto the end
	if(i<0 || (!(loaded_items[i][0]==new_loaded_item[0] && loaded_items[i][1]==new_loaded_item[1])))loaded_items.push(new_loaded_item.slice(0));
}


function resize_canvas(ev){
	//resize canvas to window
	context.canvas.width  = window.innerWidth-50;
	context.canvas.height = window.innerHeight-150;
	offscreen_context.canvas.width  = context.canvas.width;
	offscreen_context.canvas.height = context.canvas.height;
}

function select_item(previous,next){
	//put all the on item change stuff here
	selected_item=next.slice(0); //copy array
}

function rearrange_tree(selected_item_ref,target_loc){
	//resolve the selected item ref
	item=resolve_reference(selected_item_ref);
	item['draw_parent']=[0,-1];

	//the selected planet should head towards its target location
	x_shift=(target_loc[0]-item["draw_centre"][0])*default_anim_recentre_speed;
	y_shift=(target_loc[1]-item["draw_centre"][1])*default_anim_recentre_speed;
	
	//work outwards through the tree
	to_process=[selected_item_ref];
	gen=0;
	while(to_process.length>0){
		new_to_process=[];
		while(temp_item_ref=to_process.pop()){
			//resolve
			temp_item=resolve_reference(temp_item_ref);
			
			if(temp_item['processed']==false){
				temp_item['generation']=gen;
			
				//add the cumulative shift to each item
				temp_item['draw_centre'][0]+=x_shift;
				temp_item['draw_centre'][1]+=y_shift;
				
				//size the radius by on generation
				desired_draw_radius=(temp_item_ref[0]==2?default_animation_size:default_tag_size)*Math.pow(default_generational_step,temp_item['generation']);
				
				
				if(mouse_drag==false){
				
					//the selected item doesn't respond to the mouse
					if(!(temp_item_ref[0]==selected_item[0] && temp_item_ref[1]==selected_item[1])){
						//effect of mouse proximity
						item_mouse_vec=[mouse_x-temp_item["draw_centre"][0],mouse_y-temp_item["draw_centre"][1]];
						mouse_distance=vector_length(item_mouse_vec);
						
						//if mouse is less than default_swell_trigger planets away then start to swell
						this_swell_trigger=default_swell_trigger*temp_item["draw_radius"];
						if(mouse_distance<this_swell_trigger)swell_size=1+((this_swell_trigger-mouse_distance)*(default_swell_size/this_swell_trigger));
						else swell_size=1;
						temp_item['draw_radius']+=((desired_draw_radius*swell_size)-temp_item['draw_radius'])*default_anim_swell_speed;
						
						//mouse magneticness
						this_magnetic_trigger=default_magnetic_trigger*temp_item["draw_radius"];
						if(mouse_distance>0 && mouse_distance<this_magnetic_trigger){
							magnetic_attraction=0.3*(this_magnetic_trigger-mouse_distance)/this_magnetic_trigger;
							temp_item['draw_centre'][0]+=magnetic_attraction*item_mouse_vec[0];
							temp_item['draw_centre'][1]+=magnetic_attraction*item_mouse_vec[1];
						}
					}
					else temp_item['draw_radius']+=(desired_draw_radius-temp_item['draw_radius'])*default_anim_recentre_speed;
					
					//move each item's links towards the correct position
					//first set their draw_parent = temp_item_ref
					//generated using get_child_item_locations and allocating the locations to the links on a closest first basis.
					if(temp_item['draw_parent'][0]!=0)grandparent_loc=resolve_reference(temp_item['draw_parent'])['draw_centre'];
					else grandparent_loc=[];
					
					//find out how many links have already been processed
					already_processed_links_count=0;
					for(link_index=temp_item["links"].length-1;link_index>=0;link_index--)if(resolve_reference(temp_item["links"][link_index])['processed'])already_processed_links_count++;
					
					locations=get_child_item_locations(temp_item["links"].length-already_processed_links_count,
						temp_item["draw_centre"],grandparent_loc,temp_item['generation']+1,temp_item_ref[0]);
					
					location_index_count=0;
					for(link_index=temp_item["links"].length-1;link_index>=0;link_index--){
						link_item=resolve_reference(temp_item["links"][link_index]);
						if(!link_item['processed']){
							link_item['draw_parent']=temp_item_ref.slice(0);
							
							/*
							closest_location_index=-1;
							closest_location_distance=-1
							for(location_index=locations.length-1;location_index>=0;location_index--){
								//for each given location, find the distance
								location_distance=vector_length([locations[location_index][0]-link_item["draw_centre"][0],locations[location_index][1]-link_item["draw_centre"][1]]);
								if(closest_location_index==-1 || location_distance<closest_location_distance){
									closest_location_index=location_index;
									closest_location_distance=location_distance;
								}
							}
							//assign the closest location to the link
							link_item["draw_centre"][0]+=(locations[closest_location_index][0]-link_item["draw_centre"][0])*default_anim_child_recentre_speed;
							link_item["draw_centre"][1]+=(locations[closest_location_index][1]-link_item["draw_centre"][1])*default_anim_child_recentre_speed;
							//status_line.innerHTML+="|item "+link_index.toString()+" location "+closest_location_index.toString();
							//remove the closest location from the list
							locations.splice(closest_location_index,1);
							*/
							
							link_item["draw_centre"][0]+=(locations[location_index_count][0]-link_item["draw_centre"][0])*default_anim_child_recentre_speed;
							link_item["draw_centre"][1]+=(locations[location_index_count][1]-link_item["draw_centre"][1])*default_anim_child_recentre_speed;
							location_index_count++;
							
						}
						
					}
					
				}
				else{
					//mouse_drag
					if(temp_item_ref[0]==mouse_drag[0] && temp_item_ref[1]==mouse_drag[1]){
						temp_item["draw_centre"][0]+=(mouse_x-temp_item["draw_centre"][0])*default_anim_drag_speed;
						temp_item["draw_centre"][1]+=(mouse_y-temp_item["draw_centre"][1])*default_anim_drag_speed;
					}
				}
				
				
				
				temp_item['processed']=true;
				//for every link add to new_to_process if they've not already been processed
				for(temp_link_index=temp_item['links'].length-1;temp_link_index>=0;temp_link_index--){
					temp_link_ref=temp_item['links'][temp_link_index];
					//resolve
					if(temp_link_ref[0]==1)temp_link_item=tags[temp_link_ref[1]]; //tag
					else if(temp_link_ref[0]==2)temp_link_item=animations[temp_link_ref[1]]; //animation
					if(temp_link_item['processed']==false)new_to_process.push(temp_link_ref.slice(0));
				}
				
			}
		}
		to_process=to_process.concat(new_to_process);
		gen++;
	}

}


function allergic_nodes(){
	for(ia=loaded_items.length-1;ia>=0;ia--){
		ref_a=loaded_items[ia];
		if((mouse_drag==false || (mouse_drag!=false && !(ref_a[0]==mouse_drag[0] && ref_a[1]==mouse_drag[1]))) && !(ref_a[0]==selected_item[0] && ref_a[1]==selected_item[1])){
			object_ia=resolve_reference(ref_a);
			push_vector=[0,0]
			for(ib=loaded_items.length-1;ib>=0;ib--){
				if(ib!=ia){
					object_ib=resolve_reference(loaded_items[ib]);
					vector_ib_ia=[object_ia['draw_centre'][0]-object_ib['draw_centre'][0],object_ia['draw_centre'][1]-object_ib['draw_centre'][1]];
					vector_length_ib_ia=vector_length(vector_ib_ia);
					ideal_separation=default_allergy_separation_multiplier*Math.max(object_ib['draw_radius'],object_ia['draw_radius']);
					if(vector_length_ib_ia<ideal_separation){
						desired_move=(ideal_separation-vector_length_ib_ia)*default_anim_allergy_speed;
						vector_ib_ia=vector_scalar(vector_ib_ia,desired_move/vector_length_ib_ia);
						push_vector[0]+=vector_ib_ia[0];
						push_vector[1]+=vector_ib_ia[1];
					}
				}
			}
			object_ia['draw_centre'][0]+=push_vector[0];
			object_ia['draw_centre'][1]+=push_vector[1];
		}
	}
}


function update_display(){
	//called at an interval
	if(loaded_items.length==0){
		//loading message
		context.fillStyle = 'rgba(0, 0, 0, 1)';
		context.textAlign = "center";
		context.textBaseline = "middle";
		context.fillText("Loading...", context.canvas.width/2, context.canvas.height/2);
	}
	else{
		
		rearrange_tree(selected_item,[0,0]);
		allergic_nodes();
		
		//blank the offscreen canvas
		offscreen_context.fillStyle = 'rgba(255,255,255,1)';
		offscreen_context.fillRect(0, 0, canvas.width, canvas.height);
		
		//do all the drawing of planets and connections, iterate over every loaded planet, except for the dragged on which is drawn last
		to_remove=[];
		for(ii=0;ii<loaded_items.length;ii++){
			if(resolve_reference(loaded_items[ii])['processed']==false)to_remove.push(ii);  //anything which hasn't been touched by rearrange tree gets dropped
			if(mouse_drag==false || !(loaded_items[ii][0]==mouse_drag[0] && loaded_items[ii][1]==mouse_drag[1])){
				draw_item(offscreen_context,loaded_items[ii]);
				draw_item_connections(offscreen_context,loaded_items[ii]);
			}
		}
		
		//drop items no longer connected to the tree, working backwards through loaded items
		while(remove_index=to_remove.pop()){
			loaded_items.splice(remove_index,1);
		}
		
		//draw the dragged planet
		if(mouse_drag!=false && (mouse_drag[0]==1 || mouse_drag[0]==2)){
			draw_item(offscreen_context,mouse_drag);
			draw_item_connections(offscreen_context,mouse_drag);
		}
		
		//draw the add tag button
		if(selected_item[0]==2){
			draw_add_tag(offscreen_context);
			draw_play_button(offscreen_context);
		}
		
		//put the flip onscreen
		context.fillRect(0, 0, canvas.width, canvas.height); 
		context.drawImage(offscreen_context.canvas, 0,0);
	}
}


function draw_item_connections (draw_context,draw_ref) {
	draw_item_object=resolve_reference(draw_ref);

	//draw the line from parent to child (minus overlap with planet itself)
	//only draw the planet if its ready, and (its unshelved, or its shelved but a parentmost node)
	if(draw_item_object!=undefined && draw_item_object['ready']!=undefined && draw_item_object['ready']){
		for (i=(draw_item_object["links"].length-1);i>=0;i--){
			if(!(draw_item_object["links"][i][0]==selected_item[0] && draw_item_object["links"][i][1]==selected_item[1])){
			
				child=(draw_item_object["links"][i][0]==2?animations[draw_item_object["links"][i][1]]:tags[draw_item_object["links"][i][1]]);
				//only draw it if its visible, and its not the selected object
				if(child['draw_opacity']>0.01){
					
					centre_to_centre_vec=[child["draw_centre"][0]-draw_item_object["draw_centre"][0],child["draw_centre"][1]-draw_item_object["draw_centre"][1]];
					centre_to_centre_length=vector_length(centre_to_centre_vec);
					if(centre_to_centre_length>(child["draw_radius"]+draw_item_object["draw_radius"])){
						parent_to_edge_vec=vector_scalar(centre_to_centre_vec,draw_item_object["draw_radius"]/centre_to_centre_length);
						edge_to_edge_vec=vector_scalar(centre_to_centre_vec,(centre_to_centre_length-(draw_item_object["draw_radius"]+child["draw_radius"]))/centre_to_centre_length);
						
						if(mouse_drag[0]==3 && mouse_drag[1]==i && draw_ref[0]==selected_item[0] && draw_ref[1]==selected_item[1])start_loc=[mouse_x_pixels,mouse_y_pixels];
						else start_loc=[(draw_context.canvas.width/2)+((draw_item_object["draw_centre"][0]+parent_to_edge_vec[0])*draw_context.canvas.width),
							(draw_context.canvas.height/2)+((draw_item_object["draw_centre"][1]+parent_to_edge_vec[1])*draw_context.canvas.width)];
						end_loc=[(draw_context.canvas.width/2)+((draw_item_object["draw_centre"][0]+parent_to_edge_vec[0]+edge_to_edge_vec[0])*draw_context.canvas.width),
							(draw_context.canvas.height/2)+((draw_item_object["draw_centre"][1]+parent_to_edge_vec[1]+edge_to_edge_vec[1])*draw_context.canvas.width)];
						
						draw_context.strokeStyle = "rgba(0, 0, 0, "+(child['draw_opacity']/2).toString()+")";
						draw_context.lineWidth = 1;
						draw_context.beginPath();
						draw_context.moveTo(start_loc[0], start_loc[1]);
						draw_context.lineTo(end_loc[0], end_loc[1]);
						draw_context.closePath();
						draw_context.stroke();
						
						if(draw_ref[0]==selected_item[0] && draw_ref[1]==selected_item[1]){
							temp_line_color=(draw_ref[0]==2?0:255).toString();
							temp_fill_color=(draw_ref[0]==2?255:0).toString();
							draw_context.fillStyle = "rgba("+temp_line_color+","+temp_line_color+","+temp_line_color+",1)";
							draw_context.strokeStyle = "rgba("+temp_fill_color+","+temp_fill_color+","+temp_fill_color+",1)";
							draw_context.lineWidth = 1;
							draw_context.beginPath();
							draw_context.arc(start_loc[0], start_loc[1], draw_item_object['click_radius']*default_link_remove_size, 0, Math.PI*2, true);
							draw_context.fill();
							draw_context.stroke();
							draw_context.closePath();
						}
					}
				}
			}
		}
	}
}

function draw_item(draw_context,draw_ref){
	draw_item_object=resolve_reference(draw_ref);
	//generation=0 means selected
	//-1 means parent one step, +2 means child one step
	//only draw the planet if its ready
	if(draw_item_object!=undefined && draw_item_object['ready']!=undefined && draw_item_object['ready']){
		draw_context.lineWidth   = 1;
		
		//fade in...
		if(draw_ref[0]==selected_item[0] && draw_ref[1]==selected_item[1]){
			//we're drawing the selected item
			if(mouse_drag!=false && mouse_drag[0]!=3 &&
				vector_length([mouse_x-draw_item_object['draw_centre'][0],mouse_y-draw_item_object['draw_centre'][1]])<draw_item_object['draw_radius'])draw_context.lineWidth = 5;
		}
		target_opacity=Math.pow(default_opacity_step,draw_item_object['generation']-1)*(draw_item_object['loaded']?1:0.5);
		
		draw_item_object['draw_opacity']+=((target_opacity-draw_item_object['draw_opacity'])*default_anim_opacity_speed);
		
		//don't bother drawing it if its faded out
		if(draw_item_object['draw_opacity']>0.01){
			draw_context.strokeStyle = "rgba(0, 0, 0, "+draw_item_object['draw_opacity']+")";
			temp_bg_color=(draw_ref[0]==2?255:0).toString();
			draw_context.fillStyle = "rgba("+temp_bg_color+","+temp_bg_color+","+temp_bg_color+","+draw_item_object['draw_opacity'].toString()+")";
			
			
			draw_item_object['click_radius']=draw_item_object['draw_radius']*draw_context.canvas.width;
			//positions is from centre of both axes, but the multiplier is the width not the height!
			draw_item_object['click_centre']=[(draw_context.canvas.width/2)+(draw_item_object['draw_centre'][0]*draw_context.canvas.width),
				(draw_context.canvas.height/2)+(draw_item_object['draw_centre'][1]*draw_context.canvas.width)];
			

			draw_context.beginPath();
			draw_context.arc(draw_item_object['click_centre'][0], draw_item_object['click_centre'][1], draw_item_object['click_radius'], 0, Math.PI*2, true);
			draw_context.fill();
			draw_context.stroke();
			draw_context.closePath();
			
			text_line_height=(draw_item_object['click_radius']*0.25*(12/(draw_ref[0]==2?default_animation_text_size:default_tag_text_size)));
			
			//name the planet
			temp_text_color=(draw_ref[0]==2?0:255).toString();
			draw_context.fillStyle = "rgba("+temp_text_color+","+temp_text_color+","+temp_text_color+","+draw_item_object['draw_opacity'].toString()+")";
			draw_context.textAlign = "center";
			draw_context.textBaseline = "middle";
			draw_context.font = "bold "+text_line_height.toString()+"px sans-serif";
			
			if(draw_ref[0]==2){
				draw_context.fillText(draw_item_object['name'],draw_item_object['click_centre'][0], (draw_item_object['click_centre'][1]-text_line_height));
				draw_context.fillText(draw_item_object['animator'], draw_item_object['click_centre'][0], draw_item_object['click_centre'][1]);
				draw_context.fillText(draw_item_object['ago']+" ago", draw_item_object['click_centre'][0], (draw_item_object['click_centre'][1]+text_line_height));
			}
			else draw_context.fillText(draw_item_object['name'],draw_item_object['click_centre'][0], draw_item_object['click_centre'][1]);
		}
		
		//mark the item as unprocessed
		draw_item_object['processed']=false;
	}
}



function draw_add_tag (draw_context){
	//draw the add tag by name box in the bottom left hand corner
	draw_context.strokeStyle = "rgba(0, 0, 0, 1)";
	draw_context.fillStyle = 'rgba(255,255,255,1)';
	draw_context.beginPath();
	draw_context.arc((add_tag_width/2), (offscreen_context.canvas.height-(add_tag_height/2)),
		add_tag_radius, 0, Math.PI*2, true);
	draw_context.fill();
	draw_context.stroke();
	draw_context.closePath();
	
	draw_context.fillStyle = "rgba(0, 0, 0, 0.5)";
	draw_context.textAlign = "center";
	draw_context.textBaseline = "top";
	draw_context.font = "bold 18px sans-serif";
	
	draw_context.fillText("click to",(add_tag_width/2), (offscreen_context.canvas.height-(add_tag_height/2)-20));
	draw_context.fillText("add a tag by name",(add_tag_width/2), (offscreen_context.canvas.height-(add_tag_height/2)));
	
}

function draw_play_button (draw_context){
	//draw the add tag by name box in the bottom left hand corner
	draw_context.strokeStyle = "rgba(0, 0, 0, 1)";
	draw_context.fillStyle = 'rgba(255,255,255,1)';
	draw_context.beginPath();
	draw_context.arc(offscreen_context.canvas.width-(play_button_width/2), (offscreen_context.canvas.height-(play_button_height/2)),
		play_button_radius, 0, Math.PI*2, true);
	draw_context.fill();
	draw_context.stroke();
	draw_context.closePath();
	
	draw_context.fillStyle = "rgba(0, 0, 0, 0.5)";
	draw_context.textAlign = "center";
	draw_context.textBaseline = "top";
	draw_context.font = "bold 18px sans-serif";
	
	draw_context.fillText("click to",offscreen_context.canvas.width-(play_button_width/2), (offscreen_context.canvas.height-(play_button_height/2)-20));
	draw_context.fillText("PLAY >",offscreen_context.canvas.width-(play_button_width/2), (offscreen_context.canvas.height-(play_button_height/2)));
	
}



