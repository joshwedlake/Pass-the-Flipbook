// Full player
//(C) Josh Wedlake 2011, GPL v3, see LICENSE for information.

var play_enabled=false;

var frame_images=[];
var frames_total;
var frames_loaded=0;
var frame_current=0;

var canvas,context,loading_status,display_frame_current;
var offscreen_canvas,offscreen_context,flip_image_data;
//frame buttons
var button_rewind,button_frame_back,button_play,button_frame_forward;
//scoring
var display_play_count,display_thumbs_up,display_thumbs_down;
//flagging and thumbing buttons
var button_thumbs_up,button_thumbs_down,button_flag,view_zoom_slider,button_embed;

//interval controller
var play_interval;

var xmlhttp_voting;

//escapes html chars
function html_safe(unsafe) {
	return unsafe
		.replace(/&/g, "&amp;")
		.replace(/</g, "&lt;")
		.replace(/>/g, "&gt;")
		.replace(/"/g, "&quot;")
		.replace(/'/g, "&#039;");
}

function init_player(){
	//find the canvas and context
	canvas = document.getElementById('viewer_canvas');
	context = canvas.getContext('2d');
	
	//build an offscreen canvas for flipping
	offscreen_canvas = document.createElement("canvas");
	offscreen_canvas.width = canvas.width;
	offscreen_canvas.height = canvas.height;
	offscreen_context = offscreen_canvas.getContext("2d");
	offscreen_context.fillStyle = "#fff";
	
	//displays
	loading_status = document.getElementById('loading_status');
	display_frame_current = document.getElementById('frame_current');
	display_play_count = document.getElementById('play_count');
	display_play_count.innerHTML=play_count.toString();
	display_thumbs_up = document.getElementById('thumbs_up_count');
	display_thumbs_up.innerHTML=thumbs_up.toString();
	display_thumbs_down = document.getElementById('thumbs_down_count');
	display_thumbs_down.innerHTML=thumbs_down.toString();
	
	//flagging and thumbing events
	button_thumbs_up = document.getElementById('thumbs_up');
	button_thumbs_up.addEventListener('click', ev_thumbs_up, false);
	button_thumbs_down = document.getElementById('thumbs_down');
	button_thumbs_down.addEventListener('click', ev_thumbs_down, false);
	button_flag = document.getElementById('flag');
	button_flag.addEventListener('click', ev_flag, false);
	
	//embed events
	button_embed = document.getElementById('embed');
	button_embed.addEventListener('click', ev_embed, false);
	
	//add playback event handlers
	button_rewind = document.getElementById('rewind');
	button_rewind.addEventListener('click', ev_rewind, false);
	button_frame_back = document.getElementById('frame_back');
	button_frame_back.addEventListener('click', ev_frame_back, false);
	button_play = document.getElementById('play');
	button_play.addEventListener('click', ev_play_toggle, false);
	button_frame_forward = document.getElementById('frame_forward');
	button_frame_forward.addEventListener('click', ev_frame_forward, false);
	
	//add view_zoom handlers
	view_zoom_slider = document.getElementById('view_zoom');
	view_zoom_slider.addEventListener( 'change', ev_zoom, false );
	ev_zoom();
	
	//start loading the frames
	load_frames();
	
	//set the animation to play
	ev_play_toggle();
	
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
	if(frames_loaded==frames_total) loading_status.innerHTML="Loaded";
	else loading_status.innerHTML="Loading: "+Math.round(frames_loaded*100/frames_total).toString()+"%";
}

function ev_zoom (ev) {
	canvas.style.width=(canvas.width*view_zoom_slider.value).toString()+"px";
	canvas.style.height=(canvas.height*view_zoom_slider.value).toString()+"px";
}

//embed
function ev_embed(ev){
	loading_status.innerHTML="<textarea maxlength='500' readonly='readonly' style='float:left;width:592px;height:100px;resize:none'>"
		+html_safe("<iframe width='400' height='325' src='"
			+embed_url
			+"' frameborder='0' style='border:0px;margin:0px;padding:0px;'></iframe><br /><a href='"
			+player_url
			+"' style='font-size:x-small;' target='_blank'>View "
			+html_safe(seq_name)
			+" on Pass the Flipbook</a><br />")
		+"</textarea>";
}

//frame controls
function ev_rewind(ev){
	frame_current=0;
	draw_frame(frame_current);
}

function ev_frame_back(ev){
	if(frame_current>0){
		frame_current--;
		draw_frame(frame_current);
	}
}

function ev_frame_forward(ev){
	if(frame_current<(frames_total-1)){
		frame_current++;
		draw_frame(frame_current);
	}
}

//play controls
function ev_play_toggle (ev) {
	//if play is off
	if(!play_enabled){
		//start playing
		play_enabled=true;
		//play the next frame every 12th of a second
		play_interval=setInterval("play_next_frame()",83);
		//change the button to stop
		button_play.innerHTML="PAUSE ||";
	}
	else {
		//stop playing
		clearInterval(play_interval);
		//update the current frame display
		display_frame_current.innerHTML=frame_current;
		//change the button to play
		button_play.innerHTML="PLAY &#62;";
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
	offscreen_context.fillRect(0, 0, offscreen_canvas.width, offscreen_canvas.height);
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
	display_frame_current.innerHTML=frame_index.toString();
}

function ev_thumbs_up(ev){
	if(!is_logged_in)alert_require_login("vote up an animation");
	else {
		if (window.XMLHttpRequest){// code for IE7+, Firefox, Chrome, Opera, Safari
			xmlhttp_voting=new XMLHttpRequest();
		}
		else {// code for IE6, IE5
			xmlhttp_voting=new ActiveXObject("Microsoft.XMLHTTP");
		}
		
		//voting process callback
		xmlhttp_voting.onreadystatechange=function () { voting_callback(); }
		
		//send request
		xmlhttp_voting.open("POST",'voting.php',true);
		xmlhttp_voting.setRequestHeader("Content-type","application/x-www-form-urlencoded");
		xmlhttp_voting.send("seq_id="+seq_id.toString()+"&thumbs_up=1");
	}
}

function ev_thumbs_down(ev){
	if(!is_logged_in)alert_require_login("vote down an animation");
	else{
		if (window.XMLHttpRequest){// code for IE7+, Firefox, Chrome, Opera, Safari
			xmlhttp_voting=new XMLHttpRequest();
		}
		else {// code for IE6, IE5
			xmlhttp_voting=new ActiveXObject("Microsoft.XMLHTTP");
		}
		
		//voting process callback
		xmlhttp_voting.onreadystatechange=function () { voting_callback(); }
		
		//send request
		xmlhttp_voting.open("POST",'voting.php',true);
		xmlhttp_voting.setRequestHeader("Content-type","application/x-www-form-urlencoded");
		xmlhttp_voting.send("seq_id="+seq_id.toString()+"&thumbs_down=1");
	}
}

function ev_flag(ev){
	if(!is_logged_in)alert_require_login("flag an animation");
	else{
		if (window.XMLHttpRequest){// code for IE7+, Firefox, Chrome, Opera, Safari
			xmlhttp_voting=new XMLHttpRequest();
		}
		else {// code for IE6, IE5
			xmlhttp_voting=new ActiveXObject("Microsoft.XMLHTTP");
		}
		
		//voting process callback
		xmlhttp_voting.onreadystatechange=function () { voting_callback(); }
		
		//send request
		xmlhttp_voting.open("POST",'voting.php',true);
		xmlhttp_voting.setRequestHeader("Content-type","application/x-www-form-urlencoded");
		xmlhttp_voting.send("seq_id="+seq_id.toString()+"&flag=1");
	}
}

function alert_require_login(attempted_action){
	//attempted_action describes what the user tried to do
	if(is_anonymous) loading_status.innerHTML="Anonymous users cannot "+attempted_action+", please <a href='logout.php?redir="
		+encodeURIComponent("login.php?hide_anonymous=0&redir="+encodeURIComponent(window.location))+"'>Sign In</a> first.";
	else loading_status.innerHTML="Please <a href='login.php?hide_anonymous=0&redir="+encodeURIComponent(window.location)+"'>sign in</a> first to "+attempted_action+".";
}

function voting_callback(){
	//check the status codes, then update the status line as appropriate
	if (xmlhttp_voting.readyState==4){
		//if it was a success
		if (xmlhttp_voting.status==200){
			//check the return code
			if(xmlhttp_voting.responseText.indexOf("FAIL1")!=-1){
				//authentication failed
				alert_require_login();
			}
			else if(xmlhttp_voting.responseText.indexOf("FAIL2")!=-1){
				loading_status.innerHTML="Request Error.";
			}
			else if(xmlhttp_voting.responseText.indexOf("FAIL2")!=-1){
				loading_status.innerHTML="Database Error.";
			}
			//SUCCESS1 - added thumbs up
			//SUCCESS2 - removed thumbs up
			//SUCCESS3 - added thumbs down
			//SUCCESS4 - removed thumbs down
			//SUCCESS5 - switched thumb direction up>down
			//SUCCESS6 - switched thumb direction down>up
			//SUCCESS7 - added flag
			//SUCCESS8 - removed flag
			else if(xmlhttp_voting.responseText.indexOf("SUCCESS1")!=-1){
				button_thumbs_up.style.fontWeight='bold';
				thumbs_up++;
			}
			else if(xmlhttp_voting.responseText.indexOf("SUCCESS2")!=-1){
				button_thumbs_up.style.fontWeight='normal';
				thumbs_up--;
			}
			else if(xmlhttp_voting.responseText.indexOf("SUCCESS3")!=-1){
				button_thumbs_down.style.fontWeight='bold';
				thumbs_down++;
			}
			else if(xmlhttp_voting.responseText.indexOf("SUCCESS4")!=-1){
				button_thumbs_down.style.fontWeight='normal';
				thumbs_down--;
			}
			else if(xmlhttp_voting.responseText.indexOf("SUCCESS5")!=-1){
				button_thumbs_up.style.fontWeight='normal';
				button_thumbs_down.style.fontWeight='bold';
				thumbs_up--;
				thumbs_down++;
			}
			else if(xmlhttp_voting.responseText.indexOf("SUCCESS6")!=-1){
				button_thumbs_up.style.fontWeight='bold';
				button_thumbs_down.style.fontWeight='normal';
				thumbs_up++;
				thumbs_down--;
			}
			else if(xmlhttp_voting.responseText.indexOf("SUCCESS7")!=-1){
				button_flag.style.fontWeight='bold';
			}
			else if(xmlhttp_voting.responseText.indexOf("SUCCESS8")!=-1){
				button_flag.style.fontWeight='normal';
			}
			//update the thumbs up and down counts
			display_thumbs_up.innerHTML=thumbs_up.toString();
			display_thumbs_down.innerHTML=thumbs_down.toString();
		}
		else {
			//couldn't communicate with server
			loading_status.innerHTML="Communication Error.";
		}
	}
}
