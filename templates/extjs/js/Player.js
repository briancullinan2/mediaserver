// JavaScript Document

Ext.app.PlayerWindow = Ext.extend(Ext.app.Module, {
    id:'player-win',
    init : function(){
        this.launcher = {
            text: 'Player',
            iconCls:'player',
            handler : this.createWindow,
            scope: this
        }
    },
	image_type: 'GIF',
	video_type: 'WMV',
	audio_type: 'WMA',
	
	types: new Ext.data.SimpleStore({
		fields: ['short', 'long', 'type'],
		data: [
			['WMV', 'Windows Media Video (wmv)', 'video'], ['MP4', 'MPEG-4 (mp4)', 'video'], ['MPG', 'MPEG-1 (mpg)', 'video'],
			['WMA', 'Windows Media Audio (wma)', 'audio'], ['MP4A', 'MPEG-4 Audio (mp4)', 'audio'], ['MP3', 'MPEG Layer 3 (mp3)', 'audio'],
			['GIF', 'Graphics Interchange Format (gif)', 'image'], ['PNG', 'Portable Network Graphics (png)', 'image'], ['JPG', 'JPEG Image (jpg)', 'image']
		]
	}),
	
    createWindow : function(row){
        var desktop = this.app.getDesktop();

		var toolbar_items = [
			'Video Type'
		];
		
		type = row.data['info-Filemime'].split('/')[0];
		var main;
		// set up the player
		switch(type)
		{
			case 'audio':
			case 'video':
				url = plugins_path + 'encode/' + row.data.id + '/' + this.video_type + '/' + row.data.id + '.' + this.video_type.toLowerCase();
				
				toolbar_items[toolbar_items.length] = {xtype: 'button', text: 'WMV', enableToggle: true, pressed: true};
				toolbar_items[toolbar_items.length] = {xtype: 'button', text: 'MP4', enableToggle: true, pressed: false};
				toolbar_items[toolbar_items.length] = {xtype: 'button', text: 'MPG', enableToggle: true, pressed: false};
				main = new Ext.ux.MediaPanel({
					region: 'center',
					mediaCfg: {
						mediaType: 'WMV',
						type: 'application/x-ms-wmp',
						url: url,
						params: {
							'autostart' : true,
							'uimode' : 'none'
						}
					}
				});
			break;
		}
		
		var toolbar = new Ext.Toolbar({
			items: toolbar_items
		});
		
		var total = new Ext.Toolbar.TextItem('00:00:00.00');
		var remaining = new Ext.Toolbar.TextItem('00:00:00.00');		

		var slider = new Ext.Slider({minValue: 0, maxValue: row.data['info-Length']});
		slider.on('drag', function (slider, e) {
			seconds = slider.getValue();
			minutes = Math.floor(seconds / 60);
			seconds = seconds % 60;
			hours = Math.floor(minutes / 60);
			minutes = minutes % 60;
			total.getEl.innerHTML = hours + ':' + minutes + ':' + seconds;
		});

		var controls = new Ext.Toolbar({
			items: [
				{xtype: 'button', iconCls: 'ux-eject'},
				{xtype: 'button', iconCls: 'ux-play'},
				{xtype: 'button', iconCls: 'ux-stop'},
				{xtype: 'button', iconCls: 'ux-start'},
				{xtype: 'button', iconCls: 'ux-end'},
				' ',
				total,
				' ',
				slider,
				' ',
				remaining,
				' ',
				{xtype: 'button', iconCls: 'ux-mute'},
			]
		});
		
		var win = desktop.createWindow({
			title: 'Player Window',
			width: 700,
			height: 450,
			iconCls: 'player',
			shim: false,
			animCollapse: false,
			constrainHeader: true,
			cls: 'player-window ux-player',
			layout: 'border',
			border: false,
			items: [main],
			tbar: toolbar,
			bbar: controls
		});
		
        win.show();
		
		slider.container.setStyle('width', '100%');
	}
	
});
