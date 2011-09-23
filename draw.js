//Drawing tools
//(C) Josh Wedlake 2011, GPL v3, see LICENSE for information.

var canvas, context, tool, pencil, status_line, draw_enabled, play_enabled, play_interval;

//tool opacity
var tool_opacity=1.0;

//frame values
var frame_current=0;
var frames_total=1;
var frame_current_display;

//onionskin
var os_previous=true;
var os_next=false;
var os_opacity=0.5;
var os_buffer;

//imagedata
var imagedata=new Array()

function init_draw() {
	// make sure the user can't accidentally leave
	window.onbeforeunload = function (ev) {
		ev = ev || window.event;
		
		// For IE and Firefox prior to version 4
		if (ev) {
			ev.returnValue = 'Really quit without saving?';
		}
		
		// For Safari
		return 'Really quit without saving?';
	}

	// find the statusline element
	status_line = document.getElementById('status_line');
	
	// Find the canvas element.
	canvas = document.getElementById('draw_canvas');
	context = canvas.getContext('2d');
	
	//create frame 0's imagedata
	imagedata[frame_current] = context.getImageData(0, 0, canvas.width, canvas.height);
	//create onion skin buffer
	os_buffer=update_onion_skin(os_previous,os_next,os_opacity);
	
	// Pencil tool instance.
	tool = new tool_pencil();

	// Attach the mousedown, mousemove and mouseup event listeners.
	canvas.addEventListener('mousedown', ev_canvas, false);
	canvas.addEventListener('mousemove', ev_canvas, false);
	canvas.addEventListener('mouseup', ev_canvas, false);
	canvas.addEventListener('mouseout', ev_canvas, false);
	
	//Find the eraser and pencil buttons
	eraser_button = document.getElementById('eraser');
	eraser_button.addEventListener('click', ev_set_eraser, false);
	pencil_button = document.getElementById('pencil');
	pencil_button.addEventListener('click', ev_set_pencil, false);
	
	//Find the erase/pencil opacity options
	tool_opacity_button = document.getElementById('tool_opacity');
	tool_opacity_button.addEventListener( 'change', ev_tool_opacity, false );
	ev_tool_opacity();
	
	//Add next/prev/new frame handlers
	frame_back = document.getElementById('frame_back');
	frame_back.addEventListener('click', ev_frame_back, false);
	frame_forward = document.getElementById('frame_forward');
	frame_forward.addEventListener('click', ev_frame_forward, false);
	frame_add_after = document.getElementById('frame_add_after');
	frame_add_after.addEventListener('click', ev_frame_add_after, false);
	frame_add_before = document.getElementById('frame_add_before');
	frame_add_before.addEventListener('click', ev_frame_add_before, false);
	frame_remove = document.getElementById('frame_remove');
	frame_remove.addEventListener('click', ev_frame_remove, false);
	frame_current_display = document.getElementById('frame_current_display');
	//frame shifters
	frame_shift_backward = document.getElementById('frame_shift_backward');
	frame_shift_backward.addEventListener('click', ev_frame_shift_backward, false);
	frame_shift_forward = document.getElementById('frame_shift_forward');
	frame_shift_forward.addEventListener('click', ev_frame_shift_forward, false);
	
	//Add the onion skin handlers
	os_previous_toggle_button = document.getElementById('os_previous_toggle');
	os_previous_toggle_button.addEventListener('click', ev_os_previous_toggle, false);
	os_next_toggle_button = document.getElementById('os_next_toggle');
	os_next_toggle_button.addEventListener('click', ev_os_next_toggle, false);
	os_opacity_button = document.getElementById('os_opacity');
	os_opacity_button.addEventListener( 'change', ev_os_opacity, false );
	ev_os_opacity();
	
	//Find the play button
	play_button =  document.getElementById('play');
	play_button.addEventListener('click', ev_play_toggle, false);
	
	//Set the pencil to draw black
	ev_set_pencil();
	draw_enabled=true;
	play_enabled=false;
}

//drawing functions

function circle (x,y,rad) {
	context.strokeStyle = '#000';
	context.lineWidth   = 1;
	
	context.beginPath();
	context.arc(x, y, rad, 0, Math.PI*2, true);
	context.stroke();
	context.closePath();
}

function draw_points(points) {
	var i = 0, n = points.length;
	if (!n){
		return;
	}
	//set line style
	if (pencil){
		context.strokeStyle = 'rgba(0, 0, 0, '+tool_opacity+')';
		context.lineWidth   = 1;
	}
	else {
		context.strokeStyle = 'rgba(255, 255, 255, '+tool_opacity+')';
		context.lineWidth   = 10;
	}
	//draw stroke
	context.beginPath();
	context.moveTo(tool._x0, tool._y0);
	while (i < n) {
		x0 = tool.points[i++];
		y0 = tool.points[i++];
		context.lineTo(x0, y0);
	}
	context.stroke();
	context.closePath();
}

function update_onion_skin(display_previous,display_next,opacity){
	//opacity is a decimal value
	context.putImageData(imagedata[frame_current], 0,0);
	tempimagedata=context.getImageData(0, 0, canvas.width, canvas.height);
	
	if(display_previous){
		if (frame_current==0 && continue_frame_ready){
			for(var i = 0; i < tempimagedata.data.length; i+= 4 ) {
				//multiply
				//calc alphas
				alpha_bg=tempimagedata.data[i+3]/255;
				alpha_fg=continue_frame_imagedata.data[i+3]/255;
				
				//calc alphaless values
				value_bg_R=(alpha_bg*tempimagedata.data[i])+(255*(1-alpha_bg));
				value_bg_G=(alpha_bg*tempimagedata.data[i+1])+(255*(1-alpha_bg));
				value_bg_B=(alpha_bg*tempimagedata.data[i+2])+(255*(1-alpha_bg));
				
				value_fg=(alpha_fg*opacity*continue_frame_imagedata.data[i])+(255*(1-(alpha_fg*opacity)));
				
				tempimagedata.data[i]=(value_bg_R*value_fg)/255;
				tempimagedata.data[i+1]=value_bg_G;
				tempimagedata.data[i+2]=value_bg_B;
				tempimagedata.data[i+3]=255;
			}
		}
		else if(frame_current>0){
			for(var i = 0; i < tempimagedata.data.length; i+= 4 ) {
				//multiply
				//calc alphas
				alpha_bg=tempimagedata.data[i+3]/255;
				alpha_fg=imagedata[frame_current-1].data[i+3]/255;
				
				//calc alphaless values
				value_bg_R=(alpha_bg*tempimagedata.data[i])+(255*(1-alpha_bg));
				value_bg_G=(alpha_bg*tempimagedata.data[i+1])+(255*(1-alpha_bg));
				value_bg_B=(alpha_bg*tempimagedata.data[i+2])+(255*(1-alpha_bg));
				
				value_fg=(alpha_fg*opacity*imagedata[frame_current-1].data[i])+(255*(1-(alpha_fg*opacity)));
				
				tempimagedata.data[i]=(value_bg_R*value_fg)/255;
				tempimagedata.data[i+1]=value_bg_G;
				tempimagedata.data[i+2]=value_bg_B;
				tempimagedata.data[i+3]=255;
				
			}
		}
	}
	
	if(display_next && frame_current<(frames_total-1)){
		for(var i = 0; i < tempimagedata.data.length; i+= 4 ) {
			//multiply
			//calc alphas
			alpha_bg=tempimagedata.data[i+3]/255;
			alpha_fg=imagedata[frame_current+1].data[i+3]/255;
			
			//calc alphaless values
			value_bg_R=(alpha_bg*tempimagedata.data[i])+(255*(1-alpha_bg));
			value_bg_G=(alpha_bg*tempimagedata.data[i+1])+(255*(1-alpha_bg));
			value_bg_B=(alpha_bg*tempimagedata.data[i+2])+(255*(1-alpha_bg));
			
			value_fg=(alpha_fg*opacity*imagedata[frame_current+1].data[i+1])+(255*(1-(alpha_fg*opacity)));
			
			tempimagedata.data[i]=value_bg_R;
			tempimagedata.data[i+1]=(value_bg_G*value_fg)/255;
			tempimagedata.data[i+2]=value_bg_B;
			tempimagedata.data[i+3]=255;
		}
	}
	
	return tempimagedata;
}

// This painting tool works like a drawing pencil which tracks the mouse 
// movements.
function tool_pencil () {
	var tool = this;
	this.started = false;
	
	// This is called when you start holding down the mouse button.
	// This starts the pencil drawing.
	this.mousedown = function (ev) {
		if(draw_enabled){
			//save first coords and blank points
			tool._x0=ev._x;
			tool._y0=ev._y;
			tool.points=[];
			tool.started = true;
		}
	};

	// This function is called every time you move the mouse. Obviously, it only 
	// draws if the tool.started state is set to true (when you are holding down 
	// the mouse button).
	this.mousemove = function (ev) {
		if(draw_enabled){
			//write the 'initial image' if working with the eraser
			if (imagedata[frame_current]){
				//redraw the onion skin buffer without regenning it
				context.putImageData(os_buffer, 0,0);
			}
			//if the tool is started then append points
			if (tool.started) {
				//append points
				tool.points.push(ev._x,ev._y);
				//draw the line
				draw_points(tool.points);
			}
			if (!pencil){
				circle(ev._x,ev._y,5);
			}
		}
	};

	// This is called when you release the mouse button.
	this.mouseup = function (ev) {
		if (draw_enabled && tool.started) {
			//load the image
			context.putImageData(imagedata[frame_current], 0,0);
			//add the stroke
			draw_points(tool.points)
			//save the image
			imagedata[frame_current] = context.getImageData(0, 0, canvas.width, canvas.height);
			//update the onion skin buffer and write to screen
			os_buffer=update_onion_skin(os_previous,os_next,os_opacity);
			context.putImageData(os_buffer, 0,0);
			//reset the tool
			tool.points=[];
			tool.started = false;
		}
	};

	// leaves the canvas
	this.mouseout = function (ev) {
		if (draw_enabled){
			if (tool.started) {
				//same as if let go
				tool.mouseup(ev);
			}
			else {
				//get rid of the tools brush mark
				context.putImageData(os_buffer, 0,0);
			}
		}
	};
}

// Canvas event handler
function ev_canvas (ev) {
	
	//find the position of the mouse in canvas space
	ev._x = ev.pageX - canvas.offsetLeft;
	ev._y = ev.pageY - canvas.offsetTop;

	// Call the event handler of the tool.
	var func = tool[ev.type];
	if (func) {
		func(ev);
	}
}

// Pencil eraser event handlers
function ev_set_eraser (ev) {
	eraser_button.style.fontWeight = 'bold';
	pencil_button.style.fontWeight = 'normal';
	canvas.style.cursor="none";
	pencil=false;
}

function ev_set_pencil (ev) {
	eraser_button.style.fontWeight = 'normal';
	pencil_button.style.fontWeight = 'bold';
	canvas.style.cursor="crosshair";
	pencil=true;
}

//tool opacity control
function ev_tool_opacity (ev) {
	tool_opacity=tool_opacity_button.value;
	document.getElementById('tool_opacity_display').innerHTML=Math.round(tool_opacity*100);
}

// Frame event handlers
function ev_frame_back (ev) {
	if(!play_enabled){
		if(frame_current>0){
			frame_current-=1;
			//update the onion skin buffer and write to screen
			os_buffer=update_onion_skin(os_previous,os_next,os_opacity);
			context.putImageData(os_buffer, 0,0);
			//update current frame display
			frame_current_display.innerHTML=frame_current;
			//update dope sheet
			update_dope_sheet_selection();
		}
	}
}

function ev_frame_forward (ev) {
	if(!play_enabled){
		if(frame_current<(frames_total-1)){
			frame_current+=1;
			//update the onion skin buffer and write to screen
			os_buffer=update_onion_skin(os_previous,os_next,os_opacity);
			context.putImageData(os_buffer, 0,0);
			//update current frame display
			frame_current_display.innerHTML=frame_current;
			//update dope sheet
			update_dope_sheet_selection();
		}
	}
}

function ev_frame_add_after (ev) {
	if(!play_enabled){
		//clear the canvas
		context.clearRect(0, 0, canvas.width, canvas.height);
		//save the canvas to the list 1 place after (frame_current+1) the current pos
		imagedata.splice(frame_current+1,0,context.getImageData(0, 0, canvas.width, canvas.height));
		//add frame to dope
		dope_add_frame_after(frame_current);
		//handle counts
		frame_current+=1;
		frames_total+=1;
		//update the onion skin buffer and write to screen
		os_buffer=update_onion_skin(os_previous,os_next,os_opacity);
		context.putImageData(os_buffer, 0,0);
		//update current frame display
		frame_current_display.innerHTML=frame_current;
		//update dope sheet
		update_dope_sheet_selection();
	}
}

function ev_frame_add_before (ev) {
	if(!play_enabled){
		//clear the canvas
		context.clearRect(0, 0, canvas.width, canvas.height);
		//save the canvas to the list 1 place after (frame_current+1) the current pos
		imagedata.splice(frame_current,0,context.getImageData(0, 0, canvas.width, canvas.height));
		//add frame to dope
		dope_add_frame_before(frame_current);
		//handle counts
		frames_total+=1;
		//update the onion skin buffer and write to screen
		os_buffer=update_onion_skin(os_previous,os_next,os_opacity);
		context.putImageData(os_buffer, 0,0);
		//update current frame display
		frame_current_display.innerHTML=frame_current;
		//update dope sheet
		update_dope_sheet_selection();
	}
}

function ev_frame_remove (ev) {
	if(!play_enabled){
		//ask the user if they are sure
		if (frames_total>1 && confirm("Permanently delete frame "+frame_current+"?")) { 
			//remove c frame
			imagedata.splice(frame_current,1);
			//remove from dope
			dope_remove_frame(frame_current);
			//handle counts
			frames_total-=1;
			if(frame_current>=frames_total){
				frame_current-=1;
				frame_current_display.innerHTML=frame_current;
			}
			//update the onion skin buffer and write to screen
			os_buffer=update_onion_skin(os_previous,os_next,os_opacity);
			context.putImageData(os_buffer, 0,0);
			//update dope sheet
			update_dope_sheet_selection();
		}
	}
}

//frame shifters

function ev_frame_shift_backward (ev) {
	if(!play_enabled){
		if(frame_current>0){
			//swap frame_current-1 and frame_current
			imagedata[frame_current-1]= imagedata.splice(frame_current, 1, imagedata[frame_current-1])[0];
			//update dope
			dope_swap_frames(frame_current-1,frame_current);
			//update counts
			frame_current-=1;
			//update current frame display
			frame_current_display.innerHTML=frame_current;
			//update the onion skin buffer and write to screen
			os_buffer=update_onion_skin(os_previous,os_next,os_opacity);
			context.putImageData(os_buffer, 0,0);
			//update dope sheet
			update_dope_sheet_selection();
		}
	}
}

function ev_frame_shift_forward (ev) {
	if(!play_enabled){
		if(frame_current<(frames_total-1)){
			//swap frame_current-1 and frame_current
			imagedata[frame_current+1]= imagedata.splice(frame_current, 1, imagedata[frame_current+1])[0];
			//update dope
			dope_swap_frames(frame_current+1,frame_current);
			//update counts
			frame_current+=1;
			//update current frame display
			frame_current_display.innerHTML=frame_current;
			//update the onion skin buffer and write to screen
			os_buffer=update_onion_skin(os_previous,os_next,os_opacity);
			context.putImageData(os_buffer, 0,0);
			//update dope sheet
			update_dope_sheet_selection();
		}
	}
}

//dope helper

function dope_frame_jump (frame_index) {
	if(frame_index<(frames_total) && frame_index>=0){
		frame_current=frame_index;
		//update the onion skin buffer and write to screen
		os_buffer=update_onion_skin(os_previous,os_next,os_opacity);
		context.putImageData(os_buffer, 0,0);
		//update current frame display
		frame_current_display.innerHTML=frame_current;
	}
}

//os opacity event handlers
function ev_os_previous_toggle (ev) {
	if(!play_enabled){
		if(os_previous==true) {
			os_previous=false;
			os_previous_toggle_button.style.fontWeight = 'normal';
		}
		else {
			os_previous=true;
			os_previous_toggle_button.style.fontWeight = 'bold';
		}
		//update the onion skin buffer and write to screen
		os_buffer=update_onion_skin(os_previous,os_next,os_opacity);
		context.putImageData(os_buffer, 0,0);
	}
}

function ev_os_next_toggle (ev) {
	if(!play_enabled){
		if(os_next==true) {
			os_next=false;
			os_next_toggle_button.style.fontWeight = 'normal';
		}
		else {
			os_next=true;
			os_next_toggle_button.style.fontWeight = 'bold';
		}
		//update the onion skin buffer and write to screen
		os_buffer=update_onion_skin(os_previous,os_next,os_opacity);
		context.putImageData(os_buffer, 0,0);
	}
}

function ev_os_opacity (ev) {
	os_opacity=os_opacity_button.value;
	document.getElementById('os_opacity_display').innerHTML=Math.round(os_opacity*100);
	//update the onion skin buffer and write to screen
	os_buffer=update_onion_skin(os_previous,os_next,os_opacity);
	context.putImageData(os_buffer, 0,0);
}

//play controls
function ev_play_toggle (ev) {
	//draw_enabled=true means play=off
	if(!play_enabled){
		//start playing and disable drawing
		play_enabled=true;
		draw_enabled=false;
		//disable the dope sheet
		dope_sheet_object.disabled=true;
		cell_name_object.disabled=true;
		//play the next frame every 12th of a second
		play_interval=setInterval("play_next_frame()",83);
		//change the button to stop
		play_button.innerHTML="||";
	}
	else {
		//stop playing
		clearInterval(play_interval);
		//update the onion skin buffer and write to screen
		os_buffer=update_onion_skin(os_previous,os_next,os_opacity);
		context.putImageData(os_buffer, 0,0);
		//update the current frame display
		frame_current_display.innerHTML=frame_current;
		//update the dope sheet selection
		update_dope_sheet_selection();
		//change the button to play
		play_button.innerHTML="&#62;";
		//reenable the dope sheet
		dope_sheet_object.disabled=false;
		cell_name_object.disabled=false;
		//reenable drawing
		draw_enabled=true;
		play_enabled=false;
	}
}

//play next frame
function play_next_frame() {
	frame_current+=1;
	frame_current%=frames_total;
	context.putImageData(imagedata[frame_current], 0,0);
	frame_current_display.innerHTML=frame_current;
}



