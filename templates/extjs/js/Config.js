// JavaScript Document



// Sample desktop configuration
MyDesktop = new Ext.app.App({
	init : function(){
		Ext.QuickTips.init();
	},

    // config for the start menu
    getStartConfig : function(){
        return {
            title: 'Jack Slocum',
            iconCls: 'user',
            toolItems: [{
                text:'Settings',
                iconCls:'settings',
                scope:this
            },'-',{
                text:'Logout',
                iconCls:'logout',
                scope:this
            }]
        };
    }
});
