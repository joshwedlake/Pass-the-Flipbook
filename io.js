//Drawing IO tools
//(C) Josh Wedlake 2011, GPL v3, see LICENSE for information.

//continue_frame is the passed frame from the previous animator
var continue_frame, continue_frame_imagedata;
var continue_frame_ready=false;
var save_id,save_pass,parent_seq_id,parent_seq_len;

//variables to monitor sending process
var save_in_progress=false;
var save_success=[];
var save_count_rec,save_count_sent;
//an array, one for each frame
var save_xmlhttp=[];
//for deciding if the thumb has saved
var thumb_saved=false;

//loads query string params into qsParm
var qsParm = new Array();
function qs() {
	var query = window.location.search.substring(1);
	var parms = query.split('&');
	for (var i=0; i<parms.length; i++) {
		var pos = parms[i].indexOf('=');
		if (pos > 0) {
			var key = parms[i].substring(0,pos);
			var val = parms[i].substring(pos+1);
			qsParm[key] = decodeURIComponent(val.replace('+',' '));
		}
	}
}

function init_io(){
	//load the query variables
	qs();
	//expect to receive
	//	id - for saving
	//	pass - must match the sql pass for the doc to be saveable
	//	ps_id - the parent_seq_id
	//	ps_len - the parent_seq_len (only if ps_id!=-1
	save_id=parseInt(qsParm["id"]);
	save_pass=parseInt(qsParm["pass"]);
	parent_seq_id=parseInt(qsParm["ps_id"]);
	//find the png image of the parent_seq_id, load it into memory
	if(parent_seq_id!=-1){
		parent_seq_len=parseInt(qsParm["ps_len"]);
		//when the frame is ready, set its status as such
		image_URL="get_frame.php?&seq_id="+encodeURIComponent(parent_seq_id.toString())+"&fr_id="+encodeURIComponent((parent_seq_len-1).toString());
		continue_frame=new Image();
		continue_frame.src=image_URL;
		continue_frame.onload=function () {
			on_continue_frame_ready ();
		}
	}
	else {
		parent_seq_len=0;
	}
	
	//add the event handlers
	finish_discard_object=document.getElementById('finish_discard');
	finish_discard_object.addEventListener('click', ev_finish_discard, false);
	finish_save_object=document.getElementById('finish_save');
	finish_save_object.addEventListener('click', ev_finish_save, false);
	finish_reload_object=document.getElementById('finish_reload');
	finish_reload_object.addEventListener('click', ev_finish_reload, false);
}


//load the frame for the onion skinner at frame 0
function on_continue_frame_ready () {
	//build a temp canvas and load onto that
	var temp_canvas = document.createElement("canvas");
    temp_canvas.width = canvas.width;
    temp_canvas.height = canvas.height;

    // Copy the image contents to the temp
    var temp_context = temp_canvas.getContext("2d");
    temp_context.drawImage(continue_frame, 0,0);
	continue_frame_imagedata=temp_context.getImageData(0, 0, temp_canvas.width, temp_canvas.height);
	
	//let the onion skinner know we are ready
	continue_frame_ready=true;
	
	//update the onion skin buffer and write to screen
	os_buffer=update_onion_skin(os_previous,os_next,os_opacity);
	context.putImageData(os_buffer, 0,0);
}

function ev_finish_discard(ev) {
	//check, really?
	if(confirm("Quit without saving?")){
		window.onbeforeunload = function(){return;};
		//disable drawing, disable playback
		if(play_enabled){
			ev_play_toggle();
		}
		draw_enabled=false;
		//send to the deletion page, this redirects to the main page
		request_URL="delete_animation.php?"
			+"id="+encodeURIComponent(save_id)
			+"&pass="+encodeURIComponent(save_pass);
		location.href=request_URL;
	}
}

function ev_finish_reload(ev) {
	//check, really?
	if(confirm("Start over without saving changes?")){
		window.onbeforeunload = function(){return;};
		//reload the page
		location.href=window.location;
	}
}

function ev_finish_save(ev) {
	//if a save is already in progres then block the user from trying again...
	if(save_in_progress) alert("Please wait, save in progress...");
	else {
		//check, really?
		if(confirm("Finish and save?\nThe current frame will be used as a thumbnail.")){
			window.onbeforeunload = function(){return;};
			//disable drawing, disable playback
			if(play_enabled){
				ev_play_toggle();
			}
			draw_enabled=false;
			//lock the user out from trying to save again until we've heard back from everything
			save_in_progress=true;
			
			//notify the user
			status_line.innerHTML="Preparing to Save, Please Wait...";
			//counts for the number of frames to be sent in this batch
			//when these two vars match, the saving procedure is done
			save_count_rec=0;
			save_count_sent=frames_total-save_success.length;
			//if the frames have all been sent to the server, then jump straight to that part of the process
			if(save_count_sent==0)save_thumb(frame_current);
			else {
				//a progress count for the user's sending % indicator
				save_count_progress=0;
				
				//for each imagedata, draw it to the canvas then save it
				for(var j=0;j<frames_total;j++){
					//check if frame j has already been sent successfully
					//if it hasn't, try to send it now
					if(save_success.indexOf(j) == -1){
						//update the status line
						status_line.innerHTML=Math.round((save_count_progress+save_count_rec)*50/save_count_sent).toString()+"% Saving frame "+j.toString()+":"+cell_names[j];
					
						//load the imageddata to the canvas
						context.putImageData(imagedata[j], 0,0);
						//convert to png
						var canvas_data = canvas.toDataURL("image/png");
						
						// create xmlhttp object in the right slot
						if (window.XMLHttpRequest){// code for IE7+, Firefox, Chrome, Opera, Safari
							save_xmlhttp[j]=new XMLHttpRequest();
						}
						else {// code for IE6, IE5
							save_xmlhttp[j]=new ActiveXObject("Microsoft.XMLHTTP");
						}
						
						//build the function for save completion
						save_xmlhttp[j].onreadystatechange=new Function("on_frame_save_complete("+j+");");
						
						//open
						save_xmlhttp[j].open("POST",'save_frame.php',true);
						//build request
						request="seq_id="+encodeURIComponent(save_id.toString())+"&fr_id="+encodeURIComponent(j.toString())+"&data="+encodeURIComponent(canvas_data);
						//send
						save_xmlhttp[j].setRequestHeader("Content-type","application/x-www-form-urlencoded");
						save_xmlhttp[j].send(request);
						
						save_count_progress++;
					}
				}
				//sending is now complete
			}
		}
	}
}

//every frame calls this function when its xmlhttp request finishes
//after each callback it checks to see if all frames have called home
//if they have it moves on to save the thumbnail
function on_frame_save_complete (frame_index) {
	//when the ajax completes, check it was OK
	if (save_xmlhttp[frame_index].readyState==4){
		//note we have a save notification received
		save_count_rec++;
		//if it was a success
		if (save_xmlhttp[frame_index].status==200 && save_xmlhttp[frame_index].responseText.indexOf("SUCCESS")!=-1){
			//if this frame hasn't yet been registered as successfully sent, then register it as such
			if(save_success.indexOf(frame_index)==-1) save_success.push(frame_index);
			//tell the user that the frame has been sent ok
			status_line.innerHTML=Math.round((save_count_progress+save_count_rec)*50/save_count_sent).toString()+"% Frame " + frame_index.toString() + " saved OK";
		}
		else {
			//something failed
			status_line.innerHTML=Math.round((save_count_progress+save_count_rec)*50/save_count_sent).toString()+"% Frame " + frame_index.toString() + " save failed";
		}
		//have we heard home from every send attempt yet
		if (save_count_rec==save_count_sent){
			if (save_success.length==frames_total){
				//all the images have been saved, we can move on
				status_line.innerHTML="100% Sent, saving thumbnail..."
				//save the thumbnail
				//when the thumbnail is successfully saved it will call the function which updates the database
				save_thumb(frame_current);
			}
			else {
				//there was a failure, try again
				status_line.innerHTML=Math.round((frames_total-save_success.length)*100/frames_total).toString()+
					"% of frames failed to send, please <a href='#' onClick='ev_finish_save();return false;'>try again</a>";
				//unlock the saving procedure for the user
				save_in_progress=false;
			}
		}
	}
}

//when all of the frames have been saved, this function is called by on_frame_save_complete
//it scales frame frame_index to half size and saves it via ajax
//once the thumbnail is safely sent, it calls set_animation_saved
function save_thumb(frame_index){
	//if the thumbnail hasn't already been saved, then save it
	if(!thumb_saved){
		//dump the imagedata to the full size canvas
		context.putImageData(imagedata[frame_index], 0,0);
		
		//build a half size offscreen canvas
		var temp_canvas = document.createElement("canvas");
			temp_canvas.width = canvas.width/2;
			temp_canvas.height = canvas.height/2;
		var temp_context = temp_canvas.getContext("2d");
		
		//dump the full canvas to the half screen one
		temp_context.drawImage(canvas, 0, 0, canvas.width/2, canvas.height/2);
		
		//capture the canvas to png
		var thumb_data = temp_canvas.toDataURL("image/png");
		
		// create xmlhttp object
		var xmlhttp_thumb;
		
		if (window.XMLHttpRequest){// code for IE7+, Firefox, Chrome, Opera, Safari
			xmlhttp_thumb=new XMLHttpRequest();
		}
		else {// code for IE6, IE5
			xmlhttp_thumb=new ActiveXObject("Microsoft.XMLHTTP");
		}
		
		//when the thumb has been successfully saved, set the animation to saved
		xmlhttp_thumb.onreadystatechange=function () {
			//check the status codes, then redirect
			if (xmlhttp_thumb.readyState==4){
				//if it was a success
				if (xmlhttp_thumb.status==200 && xmlhttp_thumb.responseText.indexOf("SUCCESS")!=-1){
					//the thumbnail was successfully saved
					thumb_saved=true;
					//set the animation as saved in the db and redirect
					set_animation_saved();
				}
				else {
					status_line.innerHTML="Failed to save the thumbnail, please <a href='#' onClick='ev_finish_save();return false;'>try again</a>";
					//unlock the saving procedure for the user
					save_in_progress=false;
				}
			}
		}
		
		//open
		xmlhttp_thumb.open("POST",'save_frame.php',true);
		//build request
		request="seq_id="+encodeURIComponent(save_id.toString())+"&data="+encodeURIComponent(thumb_data);
		//send
		xmlhttp_thumb.setRequestHeader("Content-type","application/x-www-form-urlencoded");
		xmlhttp_thumb.send(request);
	}
	else set_animation_saved(); //if the thumbnail has already been saved

}

function set_animation_saved(){
	//the last step in the process is to talk to save_animation.php, and get a '0' response from it,
	//if a '1' is received, unlock and let the user try again
	
	// create xmlhttp object
	var xmlhttp_db;
	
	if (window.XMLHttpRequest){// code for IE7+, Firefox, Chrome, Opera, Safari
		xmlhttp_db=new XMLHttpRequest();
	}
	else {// code for IE6, IE5
		xmlhttp_db=new ActiveXObject("Microsoft.XMLHTTP");
	}
	
	//build the function for save completion
	xmlhttp_db.onreadystatechange=function () {
		//check the status codes, then redirect
		if (xmlhttp_db.readyState==4){
			//if it was a success
			if (xmlhttp_db.status==200 && xmlhttp_db.responseText.indexOf("SUCCESS")!=-1){
				//redirect!
				request="player.php?id="+save_id;
				status_line.innerHTML="Done! <a href='"+request+"'>click here to proceed</a> if you are not redirected...";
				location.href=request;
			}
			else {
				//give the user another chance to save
				status_line.innerHTML="Failed to add to the listings, please <a href='#' onClick='ev_finish_save();return false;'>try again</a>";
				//unlock the saving procedure for the user
				save_in_progress=false;
			}
		}
	}
	
	//open
	xmlhttp_db.open("POST",'save_animation.php',true);
	//build request
	request="id="+encodeURIComponent(save_id.toString())+"&pass="+encodeURIComponent(save_pass.toString())+"&frames="+encodeURIComponent(frames_total);
	//send
	xmlhttp_db.setRequestHeader("Content-type","application/x-www-form-urlencoded");
	xmlhttp_db.send(request);
}

