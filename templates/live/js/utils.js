function ajax_click(event) {
	// get link
	href = String($(this).attr('href'));
	relocate(href);
	
	return false;
}

function relocate(href)
{
	$('#loading').show();
	
	// add inner only
	if(href.indexOf('?'))
	{
		href += '&extra=inneronly';
	} else {
		href += '?extra=inneronly';
	}
	
	// set window location
	var location = String(window.location);
	if(location.indexOf('#'))
		location = location.substring(0, location.indexOf('#'));
	window.location = location + '#' + href;
	
	// load ajax
	$('#container').load(href, function() {
		$('#loading').hide();
		$('body').attr('class', $('#content').attr('class'))
		$('a.pageLink, #breadcrumb a').click(ajax_click);	
		set_selectable();
		document.title = $('#title').html() + " : " + $('#siteTitle a').html();
	});
}

function menu_reorder()
{
	// reorder menu items based on selected type
	var has_preview = false;
	var is_folder = false;
	var has_playable = false;
	$('.ui-selected').each(function(index) {
		if($(this).is('.preview'))
			has_preview = true;
		if($(this).is('.file_type_audio') || $(this).is('.file_type_video'))
			has_playable = true;
		if($(this).is('.file_ext_FOLDER'))
			is_folder = true;
	});
	
	if(is_folder && $('.ui-selected').length == 1)
	{
		$('#option_preview').hide();
		$('#option_open').show();
		$('#option_open').css('font-weight', 'bold');
	}
	else
	if(has_preview && $('.ui-selected').length == 1)
	{
		$('#option_preview').show();
		$('#option_preview').css('font-weight', 'bold');
		$('#option_download').css('font-weight', 'normal');
		$('#option_open').hide();
	}
	else
	{
		$('#option_open').hide();
		$('#option_preview').hide();
		$('#option_download').css('font-weight', 'bold');
	}
	
	if(has_playable)
		$('#option_play').show();
	else
		$('#option_play').hide();
}

function play_selected()
{
	$('.ui-selected').each(function(index) {
		if($(this).is('.file_type_audio') || $(this).is('.file_type_video'))
		{
			$f('player').addClip({
				url: '?path_info=encode/mp3/' + $(this).attr('id') + '/files/file.mp3',
				title: $('a', this).text(),
				img: $('img', this).css("background-image").replace(/"/g,"").replace(/url\(|\)$/ig, ""),
				class: (($('#playlist div').length % 2 == 0) ? 'even' : 'odd')
			}, 0);
			$('#playlist div').hover(
				function()
				{
					$(this).addClass("highlight");
				},
				function()
				{
					$(this).removeClass("highlight");
				}
			);		
		}
	});
}

function set_selectable()
{
	// set up selectable files
	$('.files').selectable({
		filter: 'div.file',
		stop: function(event, ui) {
			if($('.ui-selected').length == 1)
			{
				if($('.ui-selected a').attr('href') != '#')
				{
					if($('.ui-selected').is('.file_ext_FOLDER'))
						relocate(String($('.ui-selected a').attr('href')));
					else
						window.location = String($('.ui-selected a').attr('href'));
					
				}
			}
		}
	});
	
	$('.files div.file').hover(
		function()
		{
			$('#info_' + $(this).attr('id')).show();
			$(this).addClass("highlight");
		},
		function()
		{
			$('#info_' + $(this).attr('id')).hide();
			$(this).removeClass("highlight");
		}
	).mousedown(function(e){ // set up context menu stuff
		if( e.button == 2 ) { 
			selected = true;
			$(this).addClass('ui-selected')
			menu_reorder();
			$('#menu').show().css({top:0, left:0}).position({
				my: "left top",
				at: "right top",
				of: this
			});
			return false; 
		} else { 
			return true; 
		} 
	});
	
	$(document)[0].oncontextmenu = function() {
		if(selected == null)
		{
			$('#menu').hide();
			return true;
		}
		else
		{
			selected = null;
			return false;
		}
	}
}

var selected = null;
$(document).ready(function()
{
	$('a.pageLink, #breadcrumb a').click(ajax_click);	
	set_selectable();
	
	// set up list collapse animation
	$('#collapser').toggle(
		function()
		{
			$('#playlist').animate({
				height: 300,
				width: 421,
			}, 500);     
		},
		function()
		{
			$('#playlist').animate({
				height: 28, 
				width: 421,
			}, 500);
		}
	);
	
	
	// set up context menu highlighting
	$('#menu a').hover(
		function()
		{
			$(this).addClass("itemSelect");
		},
		function()
		{
			$(this).removeClass("itemSelect");
		}
	);
	
	if($('#player').length > 0)
	{
		// set up player
		$f("player", "?path_info=template/live/res/flowplayer-3.2.4.swf", {
			clip: {
				onBeforeBegin: function() {
					$f("player").close();
				}
			},
			plugins: {
				controls: {
					fullscreen: false,
					height: 30,
					autoHide: false,
					playlist: true
				},
				audio: {
					url: '?path_info=template/live/res/flowplayer.audio-3.2.1.swf'
				}
			}
		
		}).playlist("#playlist", {
			template: '<a href="${url}" class="listitem ${class}"><img src="${img}" height="24" width="24" />${title}</a>'
		});
	}
	
});

