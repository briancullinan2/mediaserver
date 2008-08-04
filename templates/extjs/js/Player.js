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
	
    createWindow : function(){
        var desktop = this.app.getDesktop();
		
		
		var main = new Ext.ux.MediaPanel({
			region: 'center',
			mediaCfg: {
				mediaType: 'VLC',
				url: template_path + 'player.swf',
				params: {
					'flashvars' : 'file=http://209.250.30.30/Share/Videos/test.flv'
				}
			}
		});
		
		var win = desktop.createWindow({
			title: 'Player Window',
			width: 700,
			height: 450,
			iconCls: 'player',
			shim: false,
			animCollapse: false,
			constrainHeader: true,
			cls: 'player-window',
			layout: 'border',
			border: false,
			items: [main]
		});
		

        win.show();
	}
	
});