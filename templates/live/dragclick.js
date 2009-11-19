// JavaScript Document

var IE = document.all?true:false

var selectOffset = 0;

document.onmousemove = getMouseXY;
document.onselectstart = new Function ("return false")
window.onresize = pauseInit;

var tempX = 0;
var tempY = 0;

var is_dragging = false;
var startX = 0;
var startY = 0;
var tmp_timer = null;

var min_top = 0;
var min_left = 0;

// variables for files
var file_obj;
var file_changed;
var file_top;
var file_left;

var file_height;
var file_width;

var selected = new Array();

var selector_off = false;

function pauseInit()
{
	clearTimeout(tmp_timer);
	tmp_timer = setTimeout(init, 500);
}

var inited = false;
function init()
{
	inited = true;
	
	// get min positions for selector based on content location
	min_pos = findPos(document.getElementById('content'));
	min_left = 0;
	min_top = 0;
	
	// go through objects and get information
	if(!document.getElementById('files'))
	{
		selector_off = true;
		return;
	}
	objs = document.getElementById('files').childNodes;
	
	count = 0;
	for(i = 0; i < objs.length; i++)
	{
		tmp_name = objs[i].className + '';
		if(tmp_name.substring(0, 4) == 'file')
		{
			// get the width of a file
			if(file_height == null)
			{
				if(IE) {
					file_height = objs[i].offsetHeight;
					file_width = objs[i].offsetWidth;
				} else {
					file_height = objs[i].clientHeight;
					file_width = objs[i].clientWidth;
				}
			}
			count++;
		}
	}
	
	file_obj = new Array(count);
	file_changed = new Array(count);
	file_top = new Array(count);
	file_left = new Array(count);
	index = 0;
	for(i = 0; i < objs.length; i++)
	{
		tmp_name = objs[i].className + '';
		if(tmp_name.substring(0, 4) == 'file')
		{
			file_obj[index] = objs[i];
			
			fileSelect(file_obj[index], false);
			
			tmp_pos = findPos(file_obj[index]);
			
			file_left[index] = tmp_pos[0];
			file_top[index] = tmp_pos[1];
			
			file_changed[index] = false;
			
			index++;
		}
	}
	
	if(file_obj.length == 0)
		selector_off = true;
}

function showMenu(file)
{
	if(file.className.indexOf(' select') == -1)
	{
		deselectAll();
		fileSelect(file, true);
	}
	
	var menu = document.getElementById("menu");
	
	// change default action depending on type of file
	if(selected.length == 1)
	{
		if(selected[0].className.indexOf(' FOLDER') != -1)
		{
			document.getElementById('option_download').style.display = 'none';
			document.getElementById('option_download').style.visiblity = 'hidden';
			document.getElementById('option_open').style.display = '';
			document.getElementById('option_open').style.visiblity = 'visible';
		}
		else
		{
			document.getElementById('option_open').style.display = 'none';
			document.getElementById('option_open').style.visiblity = 'hidden';
			document.getElementById('option_download').style.display = '';
			document.getElementById('option_download').style.visiblity = 'visible';
		}
	}
	else
	{
		document.getElementById('option_open').style.display = 'none';
		document.getElementById('option_open').style.visiblity = 'hidden';
		document.getElementById('option_download').style.display = 'none';
		document.getElementById('option_download').style.visiblity = 'hidden';
	}
	
	menu.style.display = "block";
	menu.style.visibility = "visible";
	menu.style.top = startY + 1 + "px";
	menu.style.left = startX + 1 + "px";
	
	var shadow = document.getElementById("shadow");
	shadow.style.display = "block";
	shadow.style.visibility = "visible";
	shadow.style.height = menu.offsetHeight + "px";
	shadow.style.width = menu.offsetWidth + "px";
	shadow.style.top = startY + 7 + "px";
	shadow.style.left = startX + 7 + "px";
}

function hideMenu()
{
	var menu = document.getElementById("menu");
	menu.style.display = "none";
	menu.style.visibility = "hidden";
	var shadow = document.getElementById("shadow");
	shadow.style.display = "none";
	shadow.style.visibility = "hidden";
}

function getFolder(href)
{
	//HTML_AJAX.replace('content', href);
	window.location = href;
}

function setProperties(file)
{
	properties = file.childNodes;
	
	for(i = 0; i < properties.length; i++)
	{
		prop = properties[i].className;
		
		setprop = document.getElementById(prop);
		if(setprop)
		{
			setprop.innerHTML = properties[i].innerHTML;
		}
		
	}
	
}

function deselectAll(evt)
{
	if(!evt || (evt.which && evt.which == 1) || (evt.button && evt.button == 1))
	{
		/*if(file != null)
		{
			if(file.className.indexOf(' select') != -1)
			{
				return;
			}
		}*/
	
		for(i = 0; i < selected.length; i++)
		{
			fileSelect(selected[i], false);
		}
		selected = new Array();
	}
}

function fileSelect(file, state, evt)
{
	if(!evt || (evt.which && evt.which == 1) || (evt.button && evt.button == 1))
	{
		if(state != null)
		{
			if(state == true)
			{
				if(file.className.indexOf(' select') == -1)
				{
					file.className += ' select';
					selected.push(file);
				}
			}
			else
			{
				file.className = file.className.replace(' select', '');
			}
		}
		else
		{
			if(file.className.indexOf(' select') == -1)
			{
				file.className += ' select';
				selected.push(file);
			}
			else
			{
				file.className = file.className.replace(' select', '');
			}
		}
	}
}

function fileSelect2(file)
{
	if(is_dragging)
	{
		fileSelect(file);
	}
}


function findPos(obj) {
	var curleft = curtop = 0;
	if (obj.offsetParent) {
		curleft = obj.offsetLeft
		curtop = obj.offsetTop
		while (obj = obj.offsetParent) {
			curleft += obj.offsetLeft
			curtop += obj.offsetTop
		}
	}
	return [curleft,curtop];
}


function setSelector()
{
	if(is_dragging && (startX - tempX > 2 || startX - tempX < 2 || startY - tempY > 2 || startY - tempY < 2))
	{
		var selector = document.getElementById('selector');
		
		selector.style.display = 'block';
		
		tmp_top = 0;
		tmp_bottom = 0;
		tmp_left = 0;
		tmp_right = 0;
		
		if(tempY < min_top)
			tempY = min_top;
		if(tempX < min_left)
			tempX = min_left;
		
		if(tempY < startY)
		{
			tmp_top = tempY;
			tmp_bottom = startY;
			selector.style.top = tempY + 'px';
			selector.style.height = (startY - tempY) + 'px';
		}
		else
		{
			tmp_top = startY;
			tmp_bottom = tempY;
			selector.style.height = (tempY - startY) + 'px';
		}
		if(tempX < startX)
		{
			tmp_left = tempX;
			tmp_right = startX;
			selector.style.left = tempX + 'px';
			selector.style.width = (startX - tempX) + 'px';
		}
		else
		{
			tmp_left = startX;
			tmp_right = tempX;
			selector.style.width = (tempX - startX) + 'px';
		}
		
		if(select_timer != null)
		{
			//clearTimeout(select_timer);
		}
		selectUnder();
		
	}
	
}

var select_timer = null;
function selectUnder()
{
	// select all files underneath the selector
	for(i = 0; i < file_obj.length; i++)
	{
		
		// always select origin
		if(startY <= file_top[i]+file_height && startY >= file_top[i] && startX <= file_left[i]+file_width && startX >= file_left[i] && file_changed[i] == false)
		{
			file_changed[i] = true;
			fileSelect(file_obj[i], true);
			continue;
		}
		
		if(file_top[i]+file_height >= tmp_top && file_top[i] <= tmp_bottom && file_left[i]+file_width >= tmp_left && file_left[i] <= tmp_right)
		{
			if(file_changed[i] == false)
			{
				file_changed[i] = true;
				fileSelect(file_obj[i]);
			}
		}
		else
		{
			if(file_changed[i] == true)
			{
				fileSelect(file_obj[i]);
				file_changed[i] = false;
			}
		}
	}
}


function getPositionInWindow()
{
	if (IE) { // grab the x-y pos.s if browser is IE
		tmpX = tempX - document.body.scrollLeft - document.documentElement.scrollLeft;
		tmpY = tempY - document.body.scrollTop - document.documentElement.scrollTop;
	} else {  // grab the x-y pos.s if browser is NS
		tmpX = tempX - window.pageXOffset;
		tmpY = tempY - window.pageYOffset;
	}  
	
	return [tmpX,tmpY];
}


function startDrag(evt)
{
	if(selector_off)
		return true;
		
	hideMenu();
	
	if(tempY > min_top && tempX > min_left && inited == true)
	{
		window_pos = getPositionInWindow();
		if (IE) {
			insideX = document.body.offsetWidth - 4;
			insideY = document.body.offsetHeight - 4;
		} else {
			insideX = window.innerWidth - 20;
			insideY = window.innerHeight - 20;
		}
		if(window_pos[0] < insideX && window_pos[1] < insideY)
		{
			// set min
			if(tempY < min_top)
				tempY = min_top;
			if(tempX < min_left)
				tempX = min_left;
			// set starting
			startX = tempX;
			startY = tempY;
			document.getElementById('selector').style.top = startY + 'px';
			document.getElementById('selector').style.left = startX + 'px';
			
			if((evt.which && evt.which == 1) || (evt.button && evt.button == 1))
			{
				is_dragging = true;
			}
			if(startY < 39)
			{
				is_dragging = false;
				return true;
			}
			
			return false;
		}
	}
}

function endDrag()
{
	if(selector_off)
		return;
	if(inited == true)
	{
		var new_selected = new Array();
		is_dragging = false;
		document.getElementById('selector').style.display = 'none';
		for(i = 0; i < file_changed.length; i++)
		{
			file_changed[i] = false;
		}
		for(i = 0; i < selected.length; i++)
		{
			if(selected[i].className.indexOf(' select') != -1)
			{
				new_selected.push(selected[i]);
			}
		}
		selected = new_selected;
	}
}

function getMouseXY(e) {
	if (IE) { // grab the x-y pos.s if browser is IE
		tempX = event.clientX + document.body.scrollLeft + document.documentElement.scrollLeft;
		tempY = event.clientY + document.body.scrollTop + document.documentElement.scrollTop;
	} else {  // grab the x-y pos.s if browser is NS
		tempX = e.pageX;
		tempY = e.pageY;
	}  
	// catch possible negative values in NS4
	if (tempX < 0){tempX = 0}
	if (tempY < 0){tempY = 0}  
	// show the position values in the form named Show
	// in the text fields named MouseX and MouseY
	return true
}


function showInfo(file)
{
}

function hideInfo()
{
}

var rotate_timer = null;
var filename = null;

function startRotate(id)
{
	if(inited == true)
	{
		filename = document.getElementById('filename_'+id);
		rotate_timer = setTimeout(rotate, 100);
	}
}

function rotate()
{
	
	if(filename.left == null)
		filename.left = true;
	
	// current position
	curr_pos = filename.style.left.substring(0, filename.style.left.length - 2);
	
	if(filename.left)
	{
		// get width of filename
		filename_width = filename.scrollWidth;
		
		// if it is all the way left
		if(curr_pos <= -(filename_width - file_width + 10))
		{
			filename.left = false;
		}
		else
		{
			filename.style.left = curr_pos - 1 + 'px';
		}
	}
	else
	{
		// if it is all the way right
		if(curr_pos >= 0)
		{
			filename.left = true;
		}
		else
		{
			filename.style.left = (curr_pos - -1) + 'px';
		}
	}
	
	rotate_timer = setTimeout(rotate, 100);
}

function stopRotate(id)
{
	if(rotate_timer)
	{
		clearTimeout(rotate_timer);
	}
	filename = document.getElementById('filename_'+id).style.left = '0px';
}
