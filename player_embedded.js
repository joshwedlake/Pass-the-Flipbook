// Embeded player
//(C) Josh Wedlake 2011, GPL v3, see LICENSE for information.

var loaded_data=false;
var play_enabled=false;

var frame_stack=[];
var frame_images=[];
var frames_total;
var frames_loaded=0;
var frame_current=0;

var canvas,context,display_frame_current;
var offscreen_canvas,offscreen_context,flip_image_data;

//interval controller
var play_interval;

var buttons=[];
var buttons_draw_order=[];
var button_x=0;
var button_x_spacing=4;

function init_player(){
	//find the canvas and context
	canvas = document.getElementById('viewer_canvas');
	context = canvas.getContext('2d');
	
	//add the click event
	canvas.addEventListener('click', ev_click, false);
	
	//build an offscreen canvas for flipping
	offscreen_canvas = document.createElement("canvas");
	offscreen_canvas.width = canvas.width;
	offscreen_canvas.height = canvas.height;
	offscreen_context = offscreen_canvas.getContext("2d");
	offscreen_context.fillStyle = "#fff";
	
	//load the thumb
	ready_screen();
}

function ready_screen(){
	//blank the canvas
	context.fillStyle="#FFF";
	context.fillRect(0, 0, offscreen_canvas.width, offscreen_canvas.height);
	//write loading
	context.font = "12px sans-serif";
	context.fillStyle = 'rgba(0, 0, 0, 1)';
	context.textAlign = "center";
	context.textBaseline = "middle";
	context.fillText("Loading Thumbnail...", context.canvas.width/2, context.canvas.height/2);
	
	thumbnail=new Image();
	//on success
	thumbnail.onload=function (){
		//draw the thumbnail
		context.fillStyle="#FFF";
		context.fillRect(0, 0, offscreen_canvas.width, offscreen_canvas.height);
		context.drawImage(thumbnail,0,0,400,300);
		
		context.textAlign = "left";
		context.textBaseline = "bottom";
		
		//write the "x by y" text
		context.fillStyle='rgba(0, 0, 0, 1)';
		context.font = "18px sans-serif";
		context.fillText(animation_data["name"]+" by "+animation_data["animator"],10, 30);
		
		//write 'on pass-the-flipbook'
		context.font = "14px sans-serif";
		context.fillText("on Pass the Flipbook",10, 50);
		
		//write click to play centrally
		context.textAlign = "center";
		context.textBaseline = "middle";
		context.font = "20px sans-serif";
		context.fillStyle = 'rgba(0, 0, 0, 0.5)';
		context.fillText("Click to Play >", context.canvas.width/2, context.canvas.height/2);
	}
	thumbnail.src="get_frame.php?&seq_id="+encodeURIComponent(animation_data["id"]);
	
}


function create_initial_buttons(){
	buttons=[];
	buttons_draw_order=[];
	create_button("rewind","|<<",ev_rewind,24,false);
	create_button("frame_back","|<",ev_frame_back,20,false);
	create_button("frame_display","0",false,40,false);
	create_button("play","PLAY >",ev_play_toggle,60,true);
	create_button("frame_forward",">|",ev_frame_forward,20,false);
	create_button("extend","extend",ev_extend,50,true);
	create_button("planets","planets",ev_planets,50,false);
	create_button("loading_status","...",ev_player,100,false);
	
}

function create_button(button_name,button_text,button_callback,button_width,button_bold){
	//draw at y300-350 x0- +
	//fill buttons with
	buttons[button_name]=[];
	buttons[button_name]["text"]=button_text;
	buttons[button_name]["callback"]=button_callback;
	buttons[button_name]["width"]=button_width;
	buttons[button_name]["bold"]=button_bold;
	buttons_draw_order.push(button_name);
}

function draw_all_buttons(draw_context){
	button_x=0;
	for(button_draw_temp=0;button_draw_temp<buttons_draw_order.length;button_draw_temp++){
		button_id=buttons_draw_order[button_draw_temp];
		draw_button(button_id,draw_context);
		}
}

function draw_button(button_id,draw_context){
	button_x+=button_x_spacing;
	buttons[button_id]["x0"]=button_x;
	button_x+=buttons[button_id]["width"];
	buttons[button_id]["x1"]=button_x;
	buttons[button_id]["y0"]=300;
	buttons[button_id]["y1"]=318;
	//draw the rectangle
	draw_context.strokeStyle = 'rgba(0, 0, 0, 1)';
	draw_context.lineWidth=1;
	draw_context.strokeRect(buttons[button_id]["x0"],buttons[button_id]["y0"],buttons[button_id]["x1"]-buttons[button_id]["x0"],buttons[button_id]["y1"]-buttons[button_id]["y0"]);
	//draw the text centred
	draw_context.fillStyle = 'rgba(0, 0, 0, 1)';
	draw_context.textAlign = "center";
	draw_context.textBaseline = "middle";
	if(buttons[button_id]["bold"])draw_context.font = "bold 12px sans-serif";
	else draw_context.font = "12px sans-serif";
	draw_context.fillText(buttons[button_id]["text"], (buttons[button_id]["x0"]+buttons[button_id]["x1"])/2, (buttons[button_id]["y0"]+buttons[button_id]["y1"])/2);
}

function ev_click(ev){
	if(!loaded_data)load_data();
	else{
		mouse_x=ev.pageX - canvas.offsetLeft;
		mouse_y=ev.pageY - canvas.offsetTop;
		//find out which button was clicked
		for(button_click_temp=buttons_draw_order.length-1;button_click_temp>=0;button_click_temp--){
			button_id=buttons_draw_order[button_click_temp];
			if(mouse_x>buttons[button_id]["x0"]
				&& mouse_x<buttons[button_id]["x1"]
				&& mouse_y>buttons[button_id]["y0"]
				&& mouse_y<buttons[button_id]["y1"])if(buttons[button_id]["callback"]!=false) buttons[button_id]["callback"]();
		}
	}
}

function ev_extend(){
	window.open(extend_url);
}

function ev_planets(){
	window.open(planets_url);
}

function ev_player(){
	window.open(player_url);
}

function load_data(){
	//blank the canvas
	context.fillStyle="#FFF";
	context.fillRect(0, 0, offscreen_canvas.width, offscreen_canvas.height);
	//write loading
	context.fillStyle = 'rgba(0, 0, 0, 1)';
	context.textAlign = "center";
	context.textBaseline = "middle";
	context.font = "12px sans-serif";
	context.fillText("Loading...", context.canvas.width/2, context.canvas.height/2);
	
	//send an ajax request
	if (window.XMLHttpRequest){// code for IE7+, Firefox, Chrome, Opera, Safari
		xmlhttp_frames=new XMLHttpRequest();
	}
	else {// code for IE6, IE5
		xmlhttp_frames=new ActiveXObject("Microsoft.XMLHTTP");
	}
	
	//voting process callback
	xmlhttp_frames.onreadystatechange=function (){
		//check the response code
		if (xmlhttp_frames.readyState==4 && xmlhttp_frames.status==200){
			//read the response text
			frame_response=JSON.parse(xmlhttp_frames.responseText);
			if(frame_response["success"]==0){
				//if its good
				loaded_data=true;
				frame_stack=frame_response["frame_stack"];
				
				//create the buttons
				create_initial_buttons();
				
				//call load frames
				load_frames();
				
				//set the animation to play
				ev_play_toggle();
			}
		}
	}
	
	//send request
	xmlhttp_frames.open("POST",'player_embedded_helper.php',true);
	xmlhttp_frames.setRequestHeader("Content-type","application/x-www-form-urlencoded");
	xmlhttp_frames.send("id="+animation_data["id"].toString());
}

function load_frames(){
	//work out frames_total
	frames_total=frame_stack.length;
	
	//load the frames into an image buffer, starting with the first one (array order is reversed)
	for(var i=(frames_total-1);i>=0;i--){
		j=(frames_total-1)-i;
		image_URL="get_frame.php?&seq_id="+encodeURIComponent(frame_stack[i][0])+"&fr_id="+encodeURIComponent(frame_stack[i][1]);
		frame_images.push(new Image());
		frame_images[j].onload=new Function("on_frame_ready ("+j.toString()+");");
		frame_images[j].src=image_URL;
	}
}

function on_frame_ready(frame_number){
	frames_loaded++;
	if(frames_loaded==frames_total) buttons["loading_status"]["text"]="popout!";
	else buttons["loading_status"]["text"]=Math.round(frames_loaded*100/frames_total).toString()+"%";
	draw_frame(frame_current);
}

//frame controls
function ev_rewind(){
	frame_current=0;
	buttons["frame_display"]["text"]=frame_current.toString();
	draw_frame(frame_current);
}

function ev_frame_back(){
	if(frame_current>0){
		frame_current--;
		buttons["frame_display"]["text"]=frame_current.toString();
		draw_frame(frame_current);
	}
}

function ev_frame_forward(){
	if(frame_current<(frames_total-1)){
		frame_current++;
		buttons["frame_display"]["text"]=frame_current.toString();
		draw_frame(frame_current);
	}
}

//play controls
function ev_play_toggle() {
	//if play is off
	if(!play_enabled){
		//start playing
		play_enabled=true;
		//play the next frame every 12th of a second
		play_interval=setInterval("play_next_frame()",83);
		//change the button to stop
		buttons["play"]["text"]="PAUSE ||";
	}
	else {
		//stop playing
		clearInterval(play_interval);
		//update the current frame display
		buttons["frame_display"]["text"]=frame_current.toString();
		//change the button to play
		buttons["play"]["text"]="PLAY >";
		//update the display
		draw_frame(frame_current);
		//change the status
		play_enabled=false;
	}
}

//play next frame
function play_next_frame() {
	if((frames_loaded/frames_total)<0.2 && frames_loaded<5){
		context.fillStyle="#FFF";
		context.fillRect(0, 0, canvas.width, canvas.height);
		context.fillStyle="#000";
		context.fillText("Buffering...", 10, 15);
	}
	//more than 20% or more than 5 frames have loaded
	else {
		//shift to the next frame
		frame_current+=1;
		frame_current%=frames_total;
		draw_frame(frame_current);
	}
}

function draw_frame(frame_index){
	//blank the offscreen canvas
	offscreen_context.fillStyle="#FFF";
	offscreen_context.fillRect(0, 0, offscreen_canvas.width, offscreen_canvas.height);
	//draw all the buttons
	draw_all_buttons(offscreen_context);
	//if the image has finished loading
	if(frame_images[frame_index].complete){
		//draw the image to it
		offscreen_context.drawImage(frame_images[frame_index], 0,0);
		//get the image data from the offscreen canvas
		flip_image_data=offscreen_context.getImageData(0, 0, offscreen_canvas.width, offscreen_canvas.height);
		//put it onscreen
		context.putImageData(flip_image_data, 0,0);
	}
	else {
		context.fillStyle="#FFF";
		context.fillRect(0, 0, canvas.width, canvas.height);
		context.fillStyle="#000";
		context.fillText("Frame "+frame_index.toString()+" not yet buffered", 10, 15);
	}
	//update the current frame display
	buttons["frame_display"]["text"]=frame_index.toString();
}

