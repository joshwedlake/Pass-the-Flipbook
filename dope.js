//Dope Sheet Tools
//(C) Josh Wedlake 2011, GPL v3, see LICENSE for information.

var cell_names=[''];
var dope_sheet_object,cell_name_object;

function init_dope() {
	//add event handlers
	dope_sheet_object=document.getElementById('dope_sheet');
	dope_sheet_object.addEventListener('change', ev_change_frame, false);
	dope_sheet_object.selectedIndex=0;
	
	cell_name_object=document.getElementById('dope_current');
	cell_name_object.addEventListener('keyup', ev_change_cell_name, false);
	
}

function rebuild_dope_sheet(){
	dope_sheet_object.options.length = 0;
	for (var i=0;i<cell_names.length;i++){
		dope_sheet_object.add(new Option(i.toString()+" "+cell_names[i], i),null);
	}
	dope_sheet_object.selectedIndex=frame_current;
}

function update_dope_sheet_selection(){
	dope_sheet_object.selectedIndex=frame_current;
	//load the selected item into the text box
	cell_name_object.value=cell_names[frame_current];
	}
	
function dope_add_frame_after(frame_index){
	cell_names.splice(frame_index+1,0,"");
	rebuild_dope_sheet();
}

function dope_add_frame_before(frame_index){
	cell_names.splice(frame_index,0,"");
	rebuild_dope_sheet();
}

function dope_remove_frame(frame_index){
	cell_names.splice(frame_index,1);
	rebuild_dope_sheet();
}

function dope_swap_frames(frame_a,frame_b){
	cell_names[frame_a]= cell_names.splice(frame_b, 1, cell_names[frame_a])[0];
	rebuild_dope_sheet();
}

function ev_change_frame(){
	//frame changed by clicking on a dope sheet cell
	//update text box
	cell_name_object.value=cell_names[dope_sheet_object.selectedIndex];
	//update canvas
	dope_frame_jump(dope_sheet_object.selectedIndex);
}
	
function ev_change_cell_name(){
	cell_names[frame_current]=cell_name_object.value;
	rebuild_dope_sheet();
}

