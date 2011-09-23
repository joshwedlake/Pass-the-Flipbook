// Comments management for player.php
//(C) Josh Wedlake 2011, GPL v3, see LICENSE for information.

var comments_container;

var input_comment_text,button_comment_add;

function init_comments(){

	comments_container = document.getElementById('comments_container');
	
	//only activate the comment buttons if the user is logged in
	if(is_logged_in){
		input_comment_text = document.getElementById('comment_text');
		button_comment_add = document.getElementById('comment_add');
		button_comment_add.addEventListener('click', ev_add_comment, false);
	}
	
	//load the comments from php
	fetch_comments();
}

//escapes html chars
function html_safe(unsafe) {
	return unsafe
		.replace(/&/g, "&amp;")
		.replace(/</g, "&lt;")
		.replace(/>/g, "&gt;")
		.replace(/"/g, "&quot;")
		.replace(/'/g, "&#039;");
}

function fetch_comments(){
	var xmlhttp_comments;
	
	if (window.XMLHttpRequest){// code for IE7+, Firefox, Chrome, Opera, Safari
		xmlhttp_comments=new XMLHttpRequest();
	}
	else {// code for IE6, IE5
		xmlhttp_comments=new ActiveXObject("Microsoft.XMLHTTP");
	}
	
	//comments callback
	xmlhttp_comments.onreadystatechange=function() {
		//when the response is ready
		if (xmlhttp_comments.readyState==4){
			//check the code
			if(xmlhttp_comments.status==200) {
				//get the comment data
				comment_data=JSON.parse(xmlhttp_comments.responseText);
				if(comment_data["success"]==0){
					//reset the comments field
					comments_container.innerHTML="";
					//for each comment
					for(i=0;i<comment_data["comments"].length;i++){
						//allow user to delete their own comments
						if(is_logged_in){
							//allow user to delete their own comments
							if(comment_data["comments"][i]["user"]==username)comments_container.innerHTML+="<div class='menuButton' onClick='delete_comment("
								+comment_data["comments"][i]["id"]+");return false;'>X</div>";
							//allow a logged in user to flag any comment
							comments_container.innerHTML+="<div class='menuButton' onClick='flag_comment("
								+comment_data["comments"][i]["id"]+");return false;' style='font-weight:"
								+(comment_data["comments"][i]["already_flagged"]==0?"bold":"default")
								+";'>flag</div>";
						}
						//add the flag count
						if(comment_data["comments"][i]["flags"]>0) comments_container.innerHTML+="<div class='menuFlag'>"+comment_data["comments"][i]["flags"].toString()+"!</div>";
						
						//insert the comment itself
						comments_container.innerHTML+="<div><a href='browse_by_animator.php?animator="
							+encodeURIComponent(comment_data["comments"][i]["user"])+"'>"
							+html_safe(comment_data["comments"][i]["user"])+"</a> : "+comment_data["comments"][i]["ago"]+" ago : "
							+html_safe(comment_data["comments"][i]["comment"]).replace(/\n/g,"<br />")+"</div><br /><br />";
					}
				}
				else{
					comments_container.innerHTML="Failed to receive comments from server.";
				}
			}
			else {
				comments_container.innerHTML="Failed to receive comments from server.";
			}
		}
	}
	
	//send request
	xmlhttp_comments.open("POST",'process_comments.php',true);
	xmlhttp_comments.setRequestHeader("Content-type","application/x-www-form-urlencoded");
	xmlhttp_comments.send("mode=0&seq_id="+encodeURIComponent(seq_id.toString()));

}

function delete_comment(comment_id){
	var xmlhttp_comments;
	
	if (window.XMLHttpRequest){// code for IE7+, Firefox, Chrome, Opera, Safari
		xmlhttp_comments=new XMLHttpRequest();
	}
	else {// code for IE6, IE5
		xmlhttp_comments=new ActiveXObject("Microsoft.XMLHTTP");
	}
	
	//delete comment callback
	xmlhttp_comments.onreadystatechange=function() {
		//handle the response
		if (xmlhttp_comments.readyState==4){
			if(xmlhttp_comments.status==200) {
				temp_response_data=JSON.parse(xmlhttp_comments.responseText);
				if(temp_response_data["success"]==0) fetch_comments();
				else{
					loading_status.innerHTML="Failed to Delete Comment...";
					fetch_comments();
				}
			}
			else {
				loading_status.innerHTML="Failed to Delete Comment...";
				fetch_comments();
			}
		}
	}
	
	//send request
	xmlhttp_comments.open("POST",'process_comments.php',true);
	xmlhttp_comments.setRequestHeader("Content-type","application/x-www-form-urlencoded");
	xmlhttp_comments.send("mode=3&id="+encodeURIComponent(comment_id.toString()));
}

function flag_comment(comment_id){
	var xmlhttp_comments;
	
	if (window.XMLHttpRequest){// code for IE7+, Firefox, Chrome, Opera, Safari
		xmlhttp_comments=new XMLHttpRequest();
	}
	else {// code for IE6, IE5
		xmlhttp_comments=new ActiveXObject("Microsoft.XMLHTTP");
	}
	
	//flag comment callback
	xmlhttp_comments.onreadystatechange=function() {
		//handle the response
		if (xmlhttp_comments.readyState==4) {
			if(xmlhttp_comments.status==200) {
				//get message text
				temp_response_data=JSON.parse(xmlhttp_comments.responseText);
				if(temp_response_data["success"]==0) fetch_comments();
				else{
					loading_status.innerHTML="Failed to Flag Comment...";
					fetch_comments();
				}
			}
			else {
				loading_status.innerHTML="Failed to Flag Comment...";
				fetch_comments();
			}
		}
	}
	
	//send request
	xmlhttp_comments.open("POST",'process_comments.php',true);
	xmlhttp_comments.setRequestHeader("Content-type","application/x-www-form-urlencoded");
	xmlhttp_comments.send("mode=4&id="+encodeURIComponent(comment_id.toString()));
}

function ev_add_comment(ev){
	//get comment text
	comment_text=encodeURIComponent(input_comment_text.value);
	var xmlhttp_comments;
	
	if (window.XMLHttpRequest){// code for IE7+, Firefox, Chrome, Opera, Safari
		xmlhttp_comments=new XMLHttpRequest();
	}
	else {// code for IE6, IE5
		xmlhttp_comments=new ActiveXObject("Microsoft.XMLHTTP");
	}
	
	//delete comment callback
	xmlhttp_comments.onreadystatechange=function() {
		//handle the response
		if (xmlhttp_comments.readyState==4) {
			input_comment_text.disabled=false;
			button_comment_add.disabled=false;
			//get message text
			if(xmlhttp_comments.status==200){
				temp_response_data=JSON.parse(xmlhttp_comments.responseText);
				if(temp_response_data["success"]==0){
					input_comment_text.value="";
					fetch_comments();
				}
				else{
					loading_status.innerHTML="Failed to Add Comment...";
					fetch_comments();
				}
			}
			else {
				loading_status.innerHTML="Failed to Add Comment...";
				fetch_comments();
			}
		}
	}
	
	//freeze the comment box and button
	input_comment_text.disabled=true;
	button_comment_add.disabled=true;
	
	//send request
	xmlhttp_comments.open("POST",'process_comments.php',true);
	xmlhttp_comments.setRequestHeader("Content-type","application/x-www-form-urlencoded");
	xmlhttp_comments.send("mode=2&seq_id="+encodeURIComponent(seq_id.toString())+"&comment="+comment_text);
	

}