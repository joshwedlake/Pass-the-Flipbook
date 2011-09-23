//Planet View
//(C) Josh Wedlake 2011, GPL v3, see LICENSE for information.

var canvas,context,offscreen_canvas,offscreen_context,interval_timer;

var status_line;

var planets=[];
var loaded_planets=[]; //an array of the loaded planets
var planet_thumbs=[];
var current_planet=-1;

//for fading thumbnail
var last_planet=-1;
var thumb_fade_down=false;
var thumb_opacity=0.1;
var thumb_width=200;
var thumb_height=150;
var thumb_radius=Math.pow(Math.pow(thumb_width/2,2)+Math.pow(thumb_height/2,2),0.5);

//fraction of screen space that a selected planet takes up;
var default_planet_size=0.03;
var default_planet_separation=default_planet_size*3;
var default_shelf_spacing=2.2;
var shelf_limit=1/(default_planet_size*default_shelf_spacing);
var default_swell_size=0.2; //proportion of normal size extra added when swelling
var default_swell_trigger=1.5*default_planet_size; //proximity of the mouse in terms of screen width
var default_magnetic_trigger=1.2*default_planet_size;
//the proportion of size smaller that children are to parents
var default_generation_step=0.75;
var default_anim_opacity_speed=0.3;
var default_anim_recentre_speed=0.1;
var default_anim_thumb_fade_speed=0.3;
var default_anim_swell_speed=0.3;

var xmlhttp_planet;
var mouse_x=0,mouse_y=0;

function init_planets(){
	//send data request
	get_planet(seq_id,0);
	
	//debug line
	status_line = document.getElementById('status_line');
	
	//get canvas
	canvas = document.getElementById('planet_canvas');
	context = canvas.getContext('2d');
	context.fillStyle = "#fff";
	
	//build an offscreen canvas for flipping
	offscreen_canvas = document.createElement("canvas");
	offscreen_context = offscreen_canvas.getContext("2d");
	
	//build a temp canvas for resizing images
	temp_resize_canvas = document.createElement("canvas");
	temp_resize_context = temp_resize_canvas.getContext("2d");
	
	//resize canvas to window
	resize_canvas();
	
	//attach event to window resize
	window.addEventListener('resize', resize_canvas, false);
	
	//add mouse click events
	canvas.addEventListener('click', ev_click, false);
	canvas.addEventListener('mousemove', ev_mousemove, false);

	//add timer
	interval_timer=setInterval("update_display();",83);
}

function get_planet(seq_id,attach_type){
	//send an ajax request
	if (window.XMLHttpRequest){// code for IE7+, Firefox, Chrome, Opera, Safari
		xmlhttp_planet=new XMLHttpRequest();
	}
	else {// code for IE6, IE5
		xmlhttp_planet=new ActiveXObject("Microsoft.XMLHTTP");
	}
	
	//voting process callback
	xmlhttp_planet.onreadystatechange=new Function("on_planet_load("+attach_type.toString()+")");
	
	//send request
	xmlhttp_planet.open("POST",'planet_helper.php',true);
	xmlhttp_planet.setRequestHeader("Content-type","application/x-www-form-urlencoded");
	xmlhttp_planet.send("id="+seq_id.toString()+"&parentless_limit="+encodeURIComponent(Math.floor(shelf_limit).toString())+"&parentless_stat="+encodeURIComponent(shelf_stat));
	
}

function ev_mousemove(ev){
	//find the position of the mouse in draw space
	mouse_x = ((ev.pageX - canvas.offsetLeft)-(context.canvas.width/2))/context.canvas.width;
	mouse_y = ((ev.pageY - canvas.offsetTop)-(context.canvas.height/2))/context.canvas.width;
}

function ev_click(ev){
	//find the position of the mouse in draw space
	mouse_x_pixels=ev.pageX - canvas.offsetLeft;
	mouse_y_pixels=ev.pageY - canvas.offsetTop;
	mouse_x = (mouse_x_pixels-(context.canvas.width/2))/context.canvas.width;
	mouse_y = (mouse_y_pixels-(context.canvas.height/2))/context.canvas.width;
	
	//first check if it was in the play button's space
	if(current_planet!=-1 && mouse_x_pixels<thumb_width && mouse_y_pixels>(context.canvas.height-thumb_height))location.href='player.php?id='+current_planet.toString();
	
	closest_planet=-1;
	closest_distance=-1;
	for(i=loaded_planets.length-1;i>=0;i--){
		planet_index=loaded_planets[i];
		click_planet_distance=vector_length([mouse_x-planets[planet_index]["draw_centre"][0],mouse_y-planets[planet_index]["draw_centre"][1]]);
		if(closest_distance==-1 || click_planet_distance<closest_distance){
			closest_planet=planet_index;
			closest_distance=click_planet_distance;
		}
	}
	
	//load that planet if it isn't already loaded
	if(planets[closest_planet]["attach_type"]!=0)get_planet(closest_planet,planets[closest_planet]["attach_type"]);
	else select_planet(current_planet,closest_planet);
	
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

function get_min_parent_child_sep(child_count,semicircle,child_generation){
	//calculate a minimum parent-child separation based on spreading round a diameter
	//1.5 is an error margin,planetsize*2 gives a planet's dia, * by count gives the circ
	ideal_circumference=1.5*default_planet_size*Math.pow(default_generation_step,child_generation)*2*child_count;
	//double the effective child count if we only have a semicircle to work with
	if(semicircle)ideal_circumference*=2;
	//divide by 2 pi to find the orbit radius
	ideal_parent_child_sep=ideal_circumference/6.28;
	
	//calculate a min parent-child sep based on the default sep given above
	user_set_parent_child_sep=default_planet_separation*Math.pow(default_generation_step,child_generation);
	
	if(user_set_parent_child_sep>ideal_parent_child_sep) return user_set_parent_child_sep;
	else return ideal_parent_child_sep;
}

function get_child_planet_locations(child_count,parent_loc,grandparent_loc,existing_child_planet,existing_child_planet_loc,child_generation){
	locations=[];
	//are an x and y provided for the grandparent location?
	if(grandparent_loc.length==2){
		angle_spread=2.5;

		//first one is straight ahead
		//unless a child already exists
		if(existing_child_planet!=-1){
			rotate_step=angle_spread/(child_count-1);
			child_vec=[existing_child_planet_loc[0]-parent_loc[0],existing_child_planet_loc[1]-parent_loc[1]];
			//rotate counterclockwise by the number of the planet
			child_vec=rotate_vector(child_vec,rotate_step*(-1)*(existing_child_planet-1));
		}
		else {
			child_vec=[(parent_loc[0]-grandparent_loc[0])*default_generation_step,(parent_loc[1]-grandparent_loc[1])*default_generation_step];
		}
		
		//get the child vec's length
		child_vec_length=vector_length(child_vec);
		//get the minimum parent child orbit distance
		min_parent_child_sep=get_min_parent_child_sep(child_count,true,child_generation);
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
		min_parent_child_sep=get_min_parent_child_sep(child_count,false,child_generation);
		//first one is straight up
		//unless a child already exists
		if(existing_child_planet!=-1){
			rotate_step=angle_spread/child_count;
			child_vec=[existing_child_planet_loc[0]-parent_loc[0],existing_child_planet_loc[1]-parent_loc[1]];
			//rotate counterclockwise by the number of the planet
			child_vec=rotate_vector(child_vec,rotate_step*(-1)*existing_child_planet);
		}
		else {
			child_vec=[0,-min_parent_child_sep];
		}
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

function trim_string(input_string){
	string_max_lex=12;
	if(input_string.length>string_max_lex){
		input_string=input_string.substr(0,string_max_lex-3)+"...";
	}
	return input_string;
}

function copy_basic_data(copy_planet_id,original_planet_data){
	planets[copy_planet_id]["name"]=trim_string(original_planet_data["name"]);
	planets[copy_planet_id]["animator"]=trim_string(original_planet_data["animator"]);
	planets[copy_planet_id]["ago"]=trim_string(original_planet_data["ago"]);
	
	//put more here on expansion
}

function on_planet_load(attach_type) {
	//attach point is the closest planet on the graph which has its connections fully loaded
	//attach_type is the generational step of the loaded planet relative to its attach point
	//handle response
	if (xmlhttp_planet.readyState==4){
		//if it was a success
		if (xmlhttp_planet.status==200){
			//read the response text
			temp_planet_data=JSON.parse(xmlhttp_planet.responseText);
			if(temp_planet_data["success"]==0){
				//add relevant planet info to planets
				planet_id=temp_planet_data["this"]["id"];

				//add the planet to the graph
				
				//if the planet doesn't exist on the graph, then add the params which get updated every frame refresh
				//if it already exists we don't want to touch there
				if(planets[planet_id]==undefined){
					planets[planet_id]=new Array();
					planets[planet_id]['draw_centre']=[0,0];
					planets[planet_id]['draw_radius']=default_planet_size*Math.pow(default_generation_step,0);
					planets[planet_id]['draw_opacity']=0;
					planets[planet_id]['generation']=0;
				}
				//get name animator ago
				copy_basic_data(planet_id,temp_planet_data["this"]);
				
				planets[planet_id]['ready']=false;
				planets[planet_id]["shelved"]=false; //the planet can't be on the shelf - its just been loaded
				planets[planet_id]["attach_type"]=0; //none
				planets[planet_id]['parent']=temp_planet_data["this"]["parent_seq_id"];
				
				if(attach_type==-1 || planets[planet_id]['parent']==undefined)planets[planet_id]['parent']=temp_planet_data["this"]["parent_seq_id"];
				if(attach_type==1 || planets[planet_id]['children']==undefined)planets[planet_id]['children']=[];
				
				//if the planet has a parent (ie not -1)
				if(temp_planet_data["this"]["parent_seq_id"]!=-1){
					parent_id=temp_planet_data["parent"][0]["id"];
					//this next block of code runs in the even the parent_id is new
					if(attach_type==-1 || planets[parent_id]==undefined){
						planets[parent_id]=new Array();
						planets[parent_id]["ready"]=false;
						planets[parent_id]["generation"]=planets[planet_id]["generation"]-1;
						planets[parent_id]["shelved"]=false;
						planets[parent_id]['parent']=-1; //-1 indicates a stop on the graph
						planets[parent_id]['children']=[planet_id];
						planets[parent_id]["attach_type"]=-1; //this is now the parentmost
						planets[parent_id]['draw_centre']=[planets[planet_id]['draw_centre'][0],
							planets[planet_id]['draw_centre'][1]+(default_planet_separation*Math.pow(default_generation_step,planets[parent_id]["generation"]))];
						planets[parent_id]['draw_radius']=planets[planet_id]['draw_radius']*Math.pow(default_generation_step,-1);
						planets[parent_id]['draw_opacity']=0;
					}
					
					//if its not new, these bits can be updated
					copy_basic_data(parent_id,temp_planet_data["parent"][0]);
					planets[parent_id]['ready']=true; //OK to draw
					
					set_planet_loaded(parent_id);
					parent_loc=planets[parent_id]['draw_centre'];
					}
				//otherwise if there is an array called parent it refers to the top level planets on the shelf
				else if(temp_planet_data["parent"].length>0) {
					//put them in a row along the top
					//but don't include the current planet
					shelf=get_planet_shelf_dimensions();
					for(pp=0;pp<temp_planet_data["parent"].length;pp++){
						//randomise position
						parent_id=temp_planet_data["parent"][pp]["id"];
						//if its a parentmost attachment
						if(planets[parent_id]==undefined){
							planets[parent_id]=new Array();
							planets[parent_id]["ready"]=false;
							planets[parent_id]["generation"]=planets[planet_id]["generation"]-1;
							planets[parent_id]["shelved"]=true;
							planets[parent_id]['parent']=-1; //-1 indicates a stop on the graph
							planets[parent_id]['children']=[];
							planets[parent_id]["attach_type"]=-1;
							planets[parent_id]['draw_centre']=[shelf['x'],shelf['y']];
							planets[parent_id]['draw_radius']=default_planet_size; //draw these at the default size
							planets[parent_id]['draw_opacity']=0;
							shelf['x']+=shelf['step'];
						}
						
						copy_basic_data(parent_id,temp_planet_data["parent"][pp]);
						planets[parent_id]['ready']=true; //OK to draw
						
						set_planet_loaded(parent_id);
						
					}
					parent_loc=[];
				}
				else parent_loc=[];
				
				//find out if there is already a child planet
				if(planets[planet_id]['children'].length>0){
					existing_child_id=planets[planet_id]['children'][0];
					//which order is this id in temp_planet_data["children"]
					for(cp=0;cp<temp_planet_data["children"].length && existing_child_id!=temp_planet_data["children"][cp]['id'];cp++);
					if(existing_child_id==temp_planet_data["children"][cp]['id']) existing_child_planet=cp;
					else existing_child_planet=-1;
				}
				else existing_child_planet=-1;
				if(existing_child_planet!=-1){
					existing_child_planet_loc=planets[existing_child_id]["draw_centre"];
				}
				else existing_child_planet_loc=[];
				//now build centres for each of the children
				child_centres=get_child_planet_locations(temp_planet_data["children"].length,
					planets[planet_id]['draw_centre'],parent_loc,existing_child_planet,existing_child_planet_loc,planets[planet_id]["generation"]+1);
				//apply child centres
				for(cp=0;cp<temp_planet_data["children"].length;cp++){
					child_id=temp_planet_data["children"][cp]["id"];
					//if parent is -1 then its free floating...
					if(planets[child_id]==undefined || attach_type==1){
						planets[child_id]=new Array();
						planets[child_id]["ready"]=false;
						planets[child_id]["generation"]=planets[planet_id]["generation"]+1;
						planets[child_id]['parent']=planet_id;
						planets[child_id]["shelved"]=false;
						planets[child_id]['children']=[];
						planets[child_id]["attach_type"]=1; //1 = look to parent
						planets[child_id]['draw_centre']=[child_centres[cp][0],child_centres[cp][1]];
						planets[child_id]['draw_radius']=planets[planet_id]['draw_radius']*Math.pow(default_generation_step,1);
						planets[child_id]['draw_opacity']=0;
					}
					
					copy_basic_data(child_id,temp_planet_data["children"][cp]);
					planets[child_id]['ready']=true;
					
					add_child(child_id,planet_id);
					
					set_planet_loaded(child_id);
					
				}
					
				//finally register the planet as loaded
				planets[planet_id]["ready"]=true;
				set_planet_loaded(planet_id);
				
				//and set it as the current planet
				select_planet(current_planet,planet_id);
				
			}
		}
		else {
			status_line.innerHTML="Failed to load planet.  Please try again.";
		}
	}
}

function get_planet_shelf_dimensions(){
	var shelf=new Array();
	shelf['step']=default_planet_size*default_shelf_spacing;
	shelf['x']=(shelf['step']/2)-0.5;
	shelf['y']=(((shelf['step']/2)*(context.canvas.width/context.canvas.height))-0.5)*(context.canvas.height/context.canvas.width);
	return shelf;

}

function set_planet_loaded(loaded_id){
	//check if loaded_id is in loaded_planets
	for(i=(loaded_planets.length-1);i>=0 && loaded_planets[i]!=loaded_id;i--);
	//if it isn't, push it onto the end
	if(loaded_planets[i]!=loaded_id)loaded_planets.push(loaded_id);
}

function add_child(child,parent){
	//check if child is in parent's children list already
	for(i=(planets[parent]['children'].length-1);i>=0 && planets[parent]['children'][i]!=child;i--);
	//if it isn't, push it onto the end
	if(planets[parent]['children'][i]!=child)planets[parent]['children'].push(child);
}

function set_tree_shelved(i,state){
	//iterate up the tree until we find the top
	while(planets[i]["parent"]!=-1 && planets[planets[i]["parent"]]!=undefined){
		i=planets[i]["parent"];
	}
	//set the top planet to be shelved
	to_unshelf=[i];
	while(to_unshelf.length>0){
		new_to_unshelf=[];
		while(j=to_unshelf.pop()){
			planets[j]['shelved']=state;
			new_to_unshelf=new_to_unshelf.concat(planets[j]['children']);
		}
		to_unshelf=to_unshelf.concat(new_to_unshelf);
	}
}

function select_planet(previous,next){
	//if the thumb isn't ready, then get it ready
	if(planet_thumbs[next]==undefined) load_planet_thumb(next);
	else if(!planet_thumbs[next].complete) load_planet_thumb(next);
	last_planet=previous;
	thumb_fade_down=true;
	//changes the selection from previous to next
	if(previous!=-1 && planets[previous]!=undefined && !planets[previous]['shelved'])set_tree_shelved(previous,true);
	set_tree_shelved(next,false);
	current_planet=next;
}

function load_planet_thumb(load_planet){
	//create an image object at the correct location
	planet_thumbs[load_planet]=new Image();
	//send request
	planet_thumbs[load_planet].src="get_frame.php?&seq_id="+load_planet.toString();
}

function resize_canvas(ev){
	//resize canvas to window
	context.canvas.width  = window.innerWidth-50;
	context.canvas.height = window.innerHeight-150;
	offscreen_context.canvas.width  = context.canvas.width;
	offscreen_context.canvas.height = context.canvas.height;
}

function rearrange_tree(start_planet,target_loc){
	//the selected planet should head towards its target location
	x_shift=(target_loc[0]-planets[start_planet]["draw_centre"][0])*default_anim_recentre_speed;
	y_shift=(target_loc[1]-planets[start_planet]["draw_centre"][1])*default_anim_recentre_speed;
	
	//working up the tree, each parent should
	//1. copy its child's motion (towards the centre)
	//2. head towards the target separation for that generation
	i=start_planet;
	child_planet=-1;
	gen=0;
	//while its parent isn't top, and its parent is ready
	while(i!=-1){
		//shift the planet towards the centre
		planets[i]["draw_centre"][0]+=x_shift;
		planets[i]["draw_centre"][1]+=y_shift;
		//measure planet-child_planet distance
		if(child_planet!=-1){
			planet_last_vec=[planets[i]["draw_centre"][0]-planets[child_planet]["draw_centre"][0],planets[i]["draw_centre"][1]-planets[child_planet]["draw_centre"][1]];
			planet_last_ideal_sep=get_min_parent_child_sep(planets[i]['children'].length,(planets[i]['parent']==-1?false:true),planets[i]['generation'])
			//resize the vector between the child_planet and this planet to tend towards the ideal
			planet_last_resize=vector_scalar(planet_last_vec,
				((planet_last_ideal_sep/vector_length(planet_last_vec))*default_anim_recentre_speed)+
				(1-default_anim_recentre_speed));
			x_shift+=planet_last_resize[0]-planet_last_vec[0];
			y_shift+=planet_last_resize[1]-planet_last_vec[1];
			planets[i]["draw_centre"]=add_vectors(planet_last_resize,planets[child_planet]["draw_centre"]);
		}
		child_planet=i;
		i=planets[i]["parent"];
		gen--;
	}
	gen+=1;
	top_planet=child_planet;
	
	//work back down the tree
	//setting location and gen
	to_traverse=[top_planet];
	while(to_traverse.length>0){
		new_to_traverse=[];
		while(k=to_traverse.pop()){
			if(planets[k]!=undefined && planets[k]["ready"]){
				//set the generation
				planets[k]["generation"]=gen;
				//work out the normal size based on shelved status
				if(planets[k]['shelved']!=true) desired_draw_radius=default_planet_size*Math.pow(default_generation_step,planets[k]['generation']);
				else desired_draw_radius=default_planet_size;
				
				//effect of mouse proximity
				planet_mouse_vec=[mouse_x-planets[k]["draw_centre"][0],mouse_y-planets[k]["draw_centre"][1]];
				mouse_distance=vector_length(planet_mouse_vec);
				
				//if mouse is less than default_swell_trigger planets away then start to swell
				if(mouse_distance<default_swell_trigger)swell_size=1+((default_swell_trigger-mouse_distance)*(default_swell_size/default_swell_trigger));
				else swell_size=1;
				planets[k]['draw_radius']+=((desired_draw_radius*swell_size)-planets[k]['draw_radius'])*default_anim_swell_speed;
				
				//mouse magneticness
				if(mouse_distance>0 && mouse_distance<default_magnetic_trigger){
					magnetic_attraction=0.3*(default_magnetic_trigger-mouse_distance)/default_magnetic_trigger;
					planets[k]['draw_centre'][0]+=magnetic_attraction*planet_mouse_vec[0];
					planets[k]['draw_centre'][1]+=magnetic_attraction*planet_mouse_vec[1];
				}
				
				//get children locations
				if(planets[k]['children'].length>0){
					//get parent, if present
					if(planets[k]["parent"]!=-1)parent_loc=planets[planets[k]["parent"]]["draw_centre"];
					else parent_loc=[];
					child_centres=get_child_planet_locations(planets[k]["children"].length,planets[k]['draw_centre'],parent_loc,-1,[],planets[k]["generation"]);
					for(j=0;j<planets[k]['children'].length;j++){
						//set child location
						planets[planets[k]['children'][j]]["draw_centre"][0]+=(child_centres[j][0]-planets[planets[k]['children'][j]]["draw_centre"][0])*2*default_anim_recentre_speed;
						planets[planets[k]['children'][j]]["draw_centre"][1]+=(child_centres[j][1]-planets[planets[k]['children'][j]]["draw_centre"][1])*2*default_anim_recentre_speed;
					}
				}
				new_to_traverse=new_to_traverse.concat(planets[k]['children']);
			}
		}
		to_traverse=to_traverse.concat(new_to_traverse);
		gen++;
	}

}

function update_display(){
	//called at an interval
	if(planets.length==0 || current_planet==-1){
		//loading message
		context.fillStyle = 'rgba(0, 0, 0, 1)';
		context.textAlign = "center";
		context.textBaseline = "middle";
		context.fillText("Loading...", context.canvas.width/2, context.canvas.height/2);
	}
	else{
		
		rearrange_tree(current_planet,[0,0]);
		
		//send shelved planets to a place on the shelf
		shelf=get_planet_shelf_dimensions();
		for(ii=0;ii<loaded_planets.length;ii++){
			if(planets[loaded_planets[ii]]['shelved'] && planets[loaded_planets[ii]]['parent']==-1){
				rearrange_tree(loaded_planets[ii],[shelf['x'],shelf['y']]);
				shelf['x']+=shelf['step'];
			}
		}
		
		//blank the offscreen canvas
		offscreen_context.fillStyle = 'rgba(255,255,255,1)';
		offscreen_context.fillRect(0, 0, canvas.width, canvas.height);
		
		//do all the drawing of planets and connections, iterate over every loaded planet
		for(ii=0;ii<loaded_planets.length;ii++){
			draw_planet(offscreen_context,loaded_planets[ii]);
			draw_planet_child_connections(offscreen_context,loaded_planets[ii]);
		}
		
		//draw the selected thumb
		draw_thumb(offscreen_context);
		
		//put the flip onscreen
		context.fillRect(0, 0, canvas.width, canvas.height); 
		context.drawImage(offscreen_context.canvas, 0,0);
	}
}


function draw_thumb (draw_context){
	//draw the bg frame in the bottom left corner
	//if fading down, draw the last_planet
	if(thumb_fade_down){
		if(planet_thumbs[last_planet]!=undefined && planet_thumbs[last_planet].complete)
			draw_context.drawImage(planet_thumbs[last_planet],0,offscreen_context.canvas.height-thumb_height);
		thumb_opacity+=(0-thumb_opacity)*default_anim_thumb_fade_speed;
		if (thumb_opacity<0.1){
			thumb_fade_down=false;
			thumb_opacity=0.1;
		}
	}
	else{
		if(planet_thumbs[current_planet]!=undefined && planet_thumbs[current_planet].complete){
			draw_context.drawImage(planet_thumbs[current_planet],0,offscreen_context.canvas.height-thumb_height);
			thumb_opacity+=(1-thumb_opacity)*default_anim_thumb_fade_speed;
		}
	}
	//draw the circle surrounding it
	draw_context.strokeStyle = "rgba(0, 0, 0, 1)";
	draw_context.fillStyle = 'rgba(255,255,255,'+(Math.max(0.01,(1-thumb_opacity))).toString()+')';
	draw_context.beginPath();
	draw_context.arc((thumb_width/2), (offscreen_context.canvas.height-(thumb_height/2)),
		thumb_radius, 0, Math.PI*2, true);
	draw_context.fill();
	draw_context.stroke();
	draw_context.closePath();
	
	draw_context.fillStyle = "rgba(0, 0, 0, 0.5)";
	draw_context.textAlign = "center";
	draw_context.textBaseline = "bottom";
	draw_context.font = "bold 18px sans-serif";
	draw_context.fillText("click to play >",(thumb_width/2), (offscreen_context.canvas.height-thumb_height));
}

function draw_planet_child_connections (draw_context,draw_index) {
	//draw the line from parent to child (minus overlap with planet itself)
	//only draw the planet if its ready, and (its unshelved, or its shelved but a parentmost node)
	if(planets[draw_index]!=undefined && planets[draw_index]['ready']!=undefined && planets[draw_index]['ready']){
		for (i=(planets[draw_index]["children"].length-1);i>=0;i--){
			child=planets[planets[draw_index]["children"][i]];
			//only draw it if its visible
			if(child['draw_opacity']>0.01){
				
				centre_to_centre_vec=[child["draw_centre"][0]-planets[draw_index]["draw_centre"][0],child["draw_centre"][1]-planets[draw_index]["draw_centre"][1]];
				centre_to_centre_length=vector_length(centre_to_centre_vec);
				parent_to_edge_vec=vector_scalar(centre_to_centre_vec,planets[draw_index]["draw_radius"]/centre_to_centre_length);
				edge_to_edge_vec=vector_scalar(centre_to_centre_vec,(centre_to_centre_length-(planets[draw_index]["draw_radius"]+child["draw_radius"]))/centre_to_centre_length);
				
				start_loc=[(draw_context.canvas.width/2)+((planets[draw_index]["draw_centre"][0]+parent_to_edge_vec[0])*draw_context.canvas.width),
					(draw_context.canvas.height/2)+((planets[draw_index]["draw_centre"][1]+parent_to_edge_vec[1])*draw_context.canvas.width)];
				end_loc=[(draw_context.canvas.width/2)+((planets[draw_index]["draw_centre"][0]+parent_to_edge_vec[0]+edge_to_edge_vec[0])*draw_context.canvas.width),
					(draw_context.canvas.height/2)+((planets[draw_index]["draw_centre"][1]+parent_to_edge_vec[1]+edge_to_edge_vec[1])*draw_context.canvas.width)];
				
				draw_context.strokeStyle = "rgba(0, 0, 0, "+child['draw_opacity'].toString()+")";
				draw_context.lineWidth   = 1;
				draw_context.beginPath();
				draw_context.moveTo(start_loc[0], start_loc[1]);
				draw_context.lineTo(end_loc[0], end_loc[1]);
				draw_context.closePath();
				draw_context.stroke();
			}
		}
	}
}

function draw_planet (draw_context,draw_index) {
	//generation=0 means selected
	//-1 means parent one step, +2 means child one step
	//only draw the planet if its ready
	if(planets[draw_index]!=undefined && planets[draw_index]['ready']!=undefined && planets[draw_index]['ready']){
		
		//fade in...
		if(planets[draw_index]['shelved']==false || (planets[draw_index]['shelved'] && planets[draw_index]['parent']==-1)){
			if(planets[draw_index]['attach_type']==0)target_opacity=1;
			//if it hasn't had its connections loaded, then don't fade in all the way
			else target_opacity=0.3;
		}
		//if its unshelved, or its shelved but a parentmost node, then fade it away
		else target_opacity=0;
		planets[draw_index]['draw_opacity']+=((target_opacity-planets[draw_index]['draw_opacity'])*default_anim_opacity_speed);
		
		//don't bother drawing it if its faded out
		if(planets[draw_index]['draw_opacity']>0.01){
			draw_context.strokeStyle = "rgba(0, 0, 0, "+planets[draw_index]['draw_opacity']+")";
			draw_context.fillStyle = "rgba(255, 255, 255, "+planets[draw_index]['draw_opacity'].toString()+")";
			draw_context.lineWidth   = 1;
			
			planets[draw_index]['click_radius']=planets[draw_index]['draw_radius']*draw_context.canvas.width;
			//positions is from centre of both axes, but the multiplier is the width not the height!
			planets[draw_index]['click_centre']=[(draw_context.canvas.width/2)+(planets[draw_index]['draw_centre'][0]*draw_context.canvas.width),
				(draw_context.canvas.height/2)+(planets[draw_index]['draw_centre'][1]*draw_context.canvas.width)];
			
			
			draw_context.beginPath();
			draw_context.arc(planets[draw_index]['click_centre'][0], planets[draw_index]['click_centre'][1], planets[draw_index]['click_radius'], 0, Math.PI*2, true);
			draw_context.fill();
			draw_context.stroke();
			draw_context.closePath();
			
			text_line_height=(planets[draw_index]['click_radius']*0.25);
			
			//name the planet
			draw_context.fillStyle = "rgba(0, 0, 0, "+planets[draw_index]['draw_opacity'].toString()+")";
			draw_context.textAlign = "center";
			draw_context.textBaseline = "middle";
			draw_context.font = "bold "+text_line_height.toString()+"px sans-serif";
			draw_context.fillText(planets[draw_index]['name'],planets[draw_index]['click_centre'][0], (planets[draw_index]['click_centre'][1]-text_line_height));
			draw_context.fillText(planets[draw_index]['animator'], planets[draw_index]['click_centre'][0], planets[draw_index]['click_centre'][1]);
			draw_context.fillText(planets[draw_index]['ago']+" ago", planets[draw_index]['click_centre'][0], (planets[draw_index]['click_centre'][1]+text_line_height));
		}
	}
}



