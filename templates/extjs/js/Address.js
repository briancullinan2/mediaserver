// JavaScript Document


//Ext.MultilineToolbar = function(config){
//    Ext.MultilineToolbar.superclass.constructor.call(this, config);
//};

Ext.override(Ext.Toolbar, {
	add : function() {
		var a = arguments, l = a.length;
		for(var i = 0; i < l; i++){
			var el = a[i];
			if(el.isFormField){ // some kind of form field
				this.addField(el);
			}else if(el.render){ // some kind of Toolbar.Item
				this.addItem(el);
			}else if(typeof el == "string"){ // string
				if(el == "separator" || el == "-"){
					this.addSeparator();
				}else if(el == " "){
					this.addSpacer();
				}else if(el == "->"){
					this.addFill();
				}else if(el == "|"){
					var new_row = Ext.DomHelper.append(this.el, {tag:'table', html:'<tr></tr>'}, true);
					new_row.dom.setAttribute("cellspacing", "0");
					this.tr = new_row.child("tr", true);
				}else{
					this.addText(el);
				}
			}else if(el.tagName){ // element
				this.addElement(el);
			}else if(typeof el == "object"){ // must be button config?
				if(el.xtype){
					this.addField(Ext.ComponentMgr.create(el, 'button'));
				}else{
					this.addButton(el);
				}
			}
		}
	},

	insertButton : function(index, item){
        if(Ext.isArray(item)){
            var buttons = [];
            for(var i = 0, len = item.length; i < len; i++) {
               buttons.push(this.insertButton(index + i, item[i]));
            }
            return buttons;
        }
        if (!(item instanceof Ext.Toolbar.Button) && !(item instanceof Ext.Toolbar.SplitButton)){
           item = new Ext.Toolbar.Button(item);
        }
        var td = document.createElement("td");
        this.tr.insertBefore(td, this.tr.childNodes[index]);
        this.initMenuTracking(item);
        item.render(td);
        this.items.insert(index, item);
        return item;
    }
	
});

/*
 * Ext JS Library 2.1
 * Copyright(c) 2006-2008, Ext JS, LLC.
 * licensing@extjs.com
 * 
 * http://extjs.com/license
 */

/**
 * @class Ext.form.ComboBox
 * @extends Ext.form.TriggerField
 * A combobox control with support for autocomplete, remote-loading, paging and many other features.
 * @constructor
 * Create a new ComboBox.
 * @param {Object} config Configuration options
 */
Ext.Address = Ext.extend(Ext.form.ComboBox, {
				 
	typeAhead: true,
	triggerAction: 'all',
	loadingText: 'Loading files...',
	emptyText:'Type in a folder to browse to...',
	selectOnFocus:true,
	enableKeyEvents: true,
	grow: true,
	 
    initComponent : function(){
        Ext.Address.superclass.initComponent.call(this);
		if(!this.tpl)
		{
			this.tpl = '<tpl for="."><div class="search-item x-combo-list-item">{' + this.displayField + '}</div></tpl>';
			this.itemSelector = 'div.search-item';
		}

	},

	onRender : function(ct, position){
		
		// triggerfield superclass
        Ext.form.TriggerField.superclass.onRender.call(this, ct, position);
		this.toolsParent = ct.createChild({tag: 'table', cls: 'ux-address', children: [{tag: 'tr', children: [{tag: 'td', cls: 'x-form-text ux-address-end'}]}]});
		this.toolsParent.set({'cellspacing': 0, 'cellpadding': 0, 'border': 0})
		this.tools = this.toolsParent.child('tr');
		this.wrap = this.toolsParent.child('td');
		this.tools.createChild({tag: 'td', cls: 'x-form-text ux-address-start', children: [{html: '&nbsp;'}]}, this.wrap);
        //this.wrap = this.tools.createChild({tag: 'td', cls: "x-form-field-wrap"});
		//this.wrap.appendTo(this.tools);
        //this.wrap = this.el.wrap({cls: "x-form-field-wrap"});
        this.trigger = this.wrap.createChild(this.triggerConfig ||
                {tag: "img", src: Ext.BLANK_IMAGE_URL, cls: "x-form-trigger " + this.triggerClass});
        if(this.hideTrigger){
            this.trigger.setDisplayed(false);
        }
        this.initTrigger();
        if(!this.width){
            this.wrap.setWidth(this.el.getWidth()+this.trigger.getWidth());
        }
		
		this.el.remove();
		this.trigger.remove();
		this.wrap.appendChild(this.el);
		this.tools.createChild({tag: 'td', cls: 'x-form-field-wrap'}).appendChild(this.trigger);
		
		// combo superclass
        if(this.hiddenName){
            this.hiddenField = this.el.insertSibling({tag:'input', type:'hidden', name: this.hiddenName,
                    id: (this.hiddenId||this.hiddenName)}, 'before', true);

            // prevent input submission
            this.el.dom.removeAttribute('name');
        }
        if(Ext.isGecko){
            this.el.dom.setAttribute('autocomplete', 'off');
        }

        if(!this.lazyInit){
            this.initList();
        }else{
            this.on('focus', this.initList, this, {single: true});
        }

        if(!this.editable){
            this.editable = true;
            this.setEditable(false);
        }
		
		// call superclass
        //Ext.Address.superclass.onRender.call(this, ct, position);
		
		// set up important events
		this.on({
			'keypress': {
				fn: function(input, e) {
					if(e.getKey() == Ext.EventObject.RETURN || e.getKey() == Ext.EventObject.ENTER)
					{
						this.triggerBlur();
					}
					return false;
				},
				scope: this
			},
			'change': {
				fn: function(input, newValue, oldValue, params) {
					if(params && params.includes)
					{
						newValue = '/Search Results/';
					}
					this.setValue(newValue);
					this.allQuery = this.getValue();
					this.setButtons();
					if(oldValue && (String(oldValue) != String(newValue) || newValue == '/Search Results/'))
					{
						var dirname = '';
						var oldDirs = oldValue.split('/');
						for(var i = oldDirs.length-1; i >= 0; i--)
						{
							if(oldDirs[i] != '')
							{
								dirname = oldDirs[i];
								break;
							}
						}
						this.addMenuItem(dirname, this.grid.store.lastOptions.params, this.backbutton.menu, this.backclick);
						if(!this.forwardbutton.disabled)
							this.forwardbutton.menu.removeAll();
						this.forwardbutton.disable();
						this.backbutton.enable();
					}
					this.grid.store.baseParams.dir = (params)?(params.dir || this.getValue()):this.getValue();
					this.grid.store.load({params: params});
					return true;
				},
				scope: this
			},
			'blur': {
				fn: function(input) {
					this.setButtons();
				},
				scope: this
			},
			'beforequery': {
				fn: function() {
					if(this.el.isVisible() == false)
					{
						this.clearButtons();
						this.el.setVisible(true);
						this.wrap.addClass('ux-form-active')
						this.wrap.removeClass('ux-form-inactive')
						this.el.dom.disabled = false;
						var lw = Math.max(this.el.getWidth(), this.minListWidth);
						this.list.setWidth(lw);
						this.innerList.setWidth(lw - this.list.getFrameWidth('lr'));
					}
				},
				scope: this
			}
		});
		
		this.wrap.on({
			'click': {
				fn: function() {
					if(this.el.isVisible() == false)
					{
						this.clearButtons();
						this.el.setVisible(true);
						this.wrap.addClass('ux-form-active')
						this.wrap.removeClass('ux-form-inactive')
						this.el.dom.disabled = false;
						this.focus(true);
					}
				},
				scope: this
			}
		});
		
		
		// add some extra stuff to toolbar
		this.el.dom.style.width = '100%';
		this.wrap.dom.style.width = '100%';
		this.container.dom.style.width = '100%';

		if(!this.buttons)
			this.buttons = [];
		
		this.setButtons();
		
	},
	
	addMenuItem : function(itemtext, itemParams, targetmenu, targetfunction)
	{
		// copy the itemParams into new object
		var params = Ext.apply({}, itemParams);
		
		var newitem = new Ext.menu.Item({
			text: itemtext,
			hideDelay: 100,
			showDelay: 100,
			params: params,
			listeners: {
				'click': {
					fn: targetfunction,
					delay: 150,
					scope: this
				}
			}
		});
		
		if(targetmenu.items.getCount() == 0)
		{
			targetmenu.add(newitem);
		}
		else
		{
			targetmenu.insert(0, newitem);
		}
	},
	
	
	forwardclick : function(menuitem) {
		this.startValue = menuitem.path;
		//if(String(this.getValue()) !== String(this.startValue)){
			// add current directory to menus
			var dirname = '';
			var oldDirs = this.getValue().split('/');
			for(var i = oldDirs.length-1; i >= 0; i--)
			{
				if(oldDirs[i] != '')
				{
					dirname = oldDirs[i];
					break;
				}
			}
			
			this.addMenuItem(dirname, this.grid.store.lastOptions.params, this.backbutton.menu, this.backclick);

			// change address
			this.fireEvent('change', this.el, menuitem.params.dir, null, menuitem.params);
			
			// add other directory history to back menu
			var menu = this.forwardbutton.menu;
			var limit = menu.items.getCount();
			for(var i = 0; i < limit; i++)
			{
				var item = menu.items.item(0);
				if(item == menuitem)
				{
					menu.remove(item);
					break;
				}
				else
				{
					this.addMenuItem(item.text, item.params, this.backbutton.menu, this.backclick);
					menu.remove(item);
				}
			}
			
			// make it disabled if it can't go forward or back
			if(this.backbutton.menu.items.getCount() > 0){ this.backbutton.enable(); }
			else{ this.backbutton.disable(); }
			if(this.forwardbutton.menu.items.getCount() > 0){ this.forwardbutton.enable(); }
			else{ this.forwardbutton.disable(); }
		//}
	},
	
	backclick : function(menuitem) {
		this.startValue = menuitem.path;
		//if(String(this.getValue()) !== String(this.startValue)){
			// add current directory to menus
			var dirname = '';
			var oldDirs = this.getValue().split('/');
			for(var i = oldDirs.length-1; i >= 0; i--)
			{
				if(oldDirs[i] != '')
				{
					dirname = oldDirs[i];
					break;
				}
			}
			
			this.addMenuItem(dirname, this.grid.store.lastOptions.params, this.forwardbutton.menu, this.forwardclick);

			// change address
			this.fireEvent('change', this.el, menuitem.params.dir, null, menuitem.params);
			
			// add other directory history to forward menu
			var menu = this.backbutton.menu;
			var limit = menu.items.getCount();
			for(var i = 0; i < limit; i++)
			{
				var item = menu.items.item(0);
				if(item == menuitem)
				{
					menu.remove(item);
					break;
				}
				else
				{
					this.addMenuItem(item.text, item.params, this.forwardbutton.menu, this.forwardclick);
					menu.remove(item);
				}
			}
			
			// make it disabled if it can't go forward or back
			if(this.backbutton.menu.items.getCount() > 0){ this.backbutton.enable(); }
			else{ this.backbutton.disable(); }
			if(this.forwardbutton.menu.items.getCount() > 0){ this.forwardbutton.enable(); }
			else{ this.forwardbutton.disable(); }
		//}
	},
	
    // private
    getParams : function(q){
        var p = {};
		p.includes = q;
		p.dirs_only = true;
        //p[this.queryParam] = q;
        if(this.pageSize){
            p.start = 0;
            p.limit = this.pageSize;
        }
        return p;
    },
	

	clearButtons : function() {
		for(var i = 0; i < this.buttons.length; i++)
		{
			// move menu for button because it is cached
			this.buttons[i].menu = null;
			this.buttons[i].el.parent().remove();
			this.buttons[i].destroy();
		}
		this.buttons = Array();
	},
	
	setButtons : function() {
		this.el.setVisible(false);
		this.wrap.removeClass('ux-form-active')
		this.wrap.addClass('ux-form-inactive')
		this.clearButtons();
		
		// split the items and make drop downs for each
		var folders = this.getValue().split('/');
		var current_dir = '/';
		for(var i = 0; i < folders.length; i++)
		{
			if(folders[i] != '' || i == 0)
			{
				// set up menu that loads files
				if(i != 0) current_dir += folders[i] + '/';
				else folders[i] = 'Portal';
				
				// check to see if the buttons path items are cached
				var file_menu = null;
				if(this.cachedMenus && this.cachedMenus[current_dir])
				{
					file_menu = this.cachedMenus[current_dir];
				}
				else
				{
					file_menu = new Ext.menu.Menu();
				}
				
				// set the file split_button
				var folder_dropdown = new Ext.Toolbar.SplitButton({
					text: folders[i],
					menu: file_menu,
					cls: 'ux-address-button ux-button-inactive', //inactive arrow by default
					address: this,
					path: current_dir,
					loaded: (this.cachedMenus && this.cachedMenus[current_dir]),
					listeners: {
						'menutriggerover': {
							fn: function () {
								if(this.address.menu)
								{
									this.focus();
									this.showMenu();
								}
							}
						},
						'menushow': {
							fn: this.loadMenu,
							scope: this
						},
						'menuhide': {
							fn: function(button) { button.el.addClass('ux-button-inactive'); },
							scope: this
						},
						'click': {
							fn: function() {
								this.address.fireEvent('change', this.address.el, this.path, this.address.getValue());
							},
							delay: 100
						}
					}
				});
				var newCell = this.tools.createChild({tag: 'td', cls: 'x-form-text ux-address-middle'}, this.wrap);
				//folder_dropdown.render();
				this.buttons[this.buttons.length] = folder_dropdown;
				folder_dropdown.render(newCell);
				this.toolbar.initMenuTracking(folder_dropdown);
			}
		}
		
		if(current_dir != this.getValue())
			this.setValue(current_dir);
		
		this.el.dom.disabled = true;
	},
	
	loadMenu : function(button, menu) {
		
		button.el.removeClass("ux-button-inactive");
		
		if(!button.loaded || button.text == 'Search Results')
		{
			var current_dir = button.path;
			
			// set loading on menu
			menu.removeAll();
			menu.add({
				text: '<span class="loading-indicator">' + this.loadingText + '</span>',
				disabled: true
			});
			
			var params = {
				limit: 40,
				start: 0
			}
			if(button.path == '/Search Results/' && button.address.grid.store.lastOptions.params)
			{
				if(button.address.grid.store.lastOptions.params.includes)
					params.includes = button.address.grid.store.lastOptions.params.includes;
				params.dir = button.address.grid.store.lastOptions.params.dir;
			}
			else
			{
				this.store.baseParams[this.queryParam] = current_dir;
			}
			
			// use the store to get folders
			this.store.load({
				menu: menu,
				callback: this.addAddressButtons,
				scope: this,
				params: params
			});
			button.loaded = true;
			this.lastQuery = '';
		}
	},
	
	addAddressButtons : function(r, options, success) {
		if(options.menu)
		{
			// cache the menu for this directory
			if(!this.cachedMenus)
				this.cachedMenus = Array();
				
			// save items
			this.cachedMenus[options.params.dir] = options.menu;
			
			// remove loading sign
			options.menu.removeAll();
			
			// only do this if there is less then 40 items
			if(this.store.getTotalCount() <= 40 && r.length != 0)
			{
				for(var i = 0; i < r.length; i++)
				{
					options.menu.add({
						text: r[i].get('name'),
						address: this,
						hideDelay: 100,
						showDelay: 100,
						path: r[i].get('path'),
						listeners: {
							'click': {
								fn: function() {
										this.address.fireEvent('change', this.address, this.path, this.address.getValue());
									},
								delay: 150
							}
						}
					});
				}
			}
			else
			{
				// display count and search instead
				if(r.length != 0)
				{
					options.menu.add({
						text: this.store.getTotalCount() + ' folder' + ((this.store.getTotalCount() != 1)?'s':''),
						disabled: true},
						{
							text: 'Search...',
							handler: function() {this.search.toggle(true);},
							scope: this
						}
					);
				}
				// display no items
				else
				{
					options.menu.add({
						text: '0 items',
						disabled: true}
					);
				}
			}
		}
	},
	
	onDestroy : function(){
		// destroy all the cached menus
		if(this.cachedMenus)
		{
			for(var i = 0; i < this.cachedMenus; i++)
			{
				this.cachedMenus[i].destroy();
			}
		}
		
        Ext.Address.superclass.onDestroy.call(this);
	}


});

