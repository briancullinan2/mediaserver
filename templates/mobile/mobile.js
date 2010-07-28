// JavaScript Document

window.onscroll = onScroll;

var objs = null;

function init()
{
	// get all files
	objs = document.getElementById('files').getElementsByTagName("div");
	
	count = 0;
	file_top = [];
	file_left = [];
	for(i = 0; i < objs.length; i++)
	{
		tmp_name = objs[i].className + '';
		if(tmp_name.substring(0, 4) == 'file')
		{
			tmp_pos = findPos(objs[i]);
			
			file_left[i] = tmp_pos[0];
			file_top[i] = tmp_pos[1];
		}
	}

}

function findPos(obj) {
	var curleft = curtop = 0;
	if (obj.offsetParent) {
		curleft = obj.offsetLeft
		curtop = obj.offsetTop
		while (obj == obj.offsetParent) {
			curleft += obj.offsetLeft
			curtop += obj.offsetTop
		}
	}
	return [curleft,curtop];
}


function onScroll()
{
	var length = Math.floor(window.innerHeight / 60 / 2);
	var start = Math.floor(window.scrollY / 60);
	
	// get the objects from the center of the screen outwards
	document.getElementById("debug").style.top = window.scrollY + "px";
	document.getElementById("topbuffer").style.height = (length * 60) + "px";
	document.getElementById("bottombuffer").style.height = (length * 60) + "px";
	
	// make the surrounding items darker in color
	for(i = 0; i < length; i++)
	{
		var grey = 255 - Math.round(255 * (i / length));
		var added = start + i;
		if(start - i >= 0)
		{
			objs[start - i].style.color = "rgb(" + grey + ", " + grey + ", " + grey + ")";
		}
		if(added < objs.length)
		{
			objs[added].style.color = "rgb(" + grey + ", " + grey + ", " + grey + ")";
		}
	}
	
	var first_char = objs[start].className.substr(-1);
	document.getElementById("debug").innerHTML = first_char;
	$("#files div:eq(" + start + ")").load("select/dir/" + directory + "?group_by=Filename&group_index=" + first_char);
}