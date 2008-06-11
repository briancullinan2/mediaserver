// JavaScript Document


Ext.MultilineToolbar = function(config){
    Ext.MultilineToolbar.superclass.constructor.call(this, config);
};

Ext.extend(Ext.MultilineToolbar, Ext.Toolbar, {
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
	 
    initComponent : function(){
        Ext.Address.superclass.initComponent.call(this);
		if(!this.tpl)
		{
			this.tpl = '<tpl for="."><div class="search-item x-combo-list-item">{' + this.displayField + '}</div></tpl>';
			this.itemSelector = 'div.search-item';
		}

	},
	
						 
	onRender : function(ct, position){
		
		// call superclass
        Ext.Address.superclass.onRender.call(this, ct, position);
		
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
				fn: function(input, newValue, oldValue) {
					this.setValue(newValue);
					this.setButtons();
					if(this.folderview)
					{
						this.folderview.dir = this.getValue();
						this.folderview.fireEvent("reload");
					}
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
						this.el.setDisplayed(true);
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
					if(this.el.isDisplayed() == false && !this.isMenu)
					{
						this.clearButtons();
						this.el.setDisplayed(true);
						this.wrap.addClass('ux-form-active')
						this.wrap.removeClass('ux-form-inactive')
						this.el.dom.disabled = false;
						this.focus(true);
					}
					this.isMenu = false;
				},
				scope: this
			}
		});
		
		
		// add some extra stuff to toolbar
		if(this.wrap)
			this.wrap.dom.style.width = '100%';

		if(!this.buttons)
			this.buttons = [];
		
		this.setButtons();
		
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
			this.buttons[i].destroy();
		}
		this.buttons = Array();
	},
	
	setButtons : function() {
		this.el.setDisplayed(false);
		this.wrap.removeClass('ux-form-active')
		this.wrap.addClass('ux-form-inactive')
		this.clearButtons();
		
		// split the items and make drop downs for each
		var folders = this.getValue().split('/');
		var current_dir = '/';
		
		for(var i = 0; i < folders.length; i++)
		{
			if(folders[i] != '')
			{
				// set up menu that loads files
				current_dir += folders[i] + '/';
				
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
				var folder_dropdown = new Ext.SplitButton({
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
							fn: function(button) { this.menu = null; button.el.addClass('ux-button-inactive'); },
							scope: this
						},
						'click': {
							fn: function() {
								this.address.isMenu = true;
								this.address.startValue = this.path;
								if(String(this.address.getValue()) !== String(this.address.startValue)){
									this.address.fireEvent('change', this.address, this.path, this.startValue);
								}
							},
							delay: 100
						}
					}
				});
				folder_dropdown.render(this.wrap, this.el.dom);
				this.buttons[this.buttons.length] = folder_dropdown;
				folder_dropdown.render();
			}
		}
		
		if(current_dir != this.getValue())
			this.setValue(current_dir);
		
		this.el.dom.disabled = true;
	},
	
	loadMenu : function(button, menu) {
		
		this.isMenu = true;
		this.menu = menu;
		
		button.el.removeClass("ux-button-inactive");
		
		if(!button.loaded)
		{
			var current_dir = button.path;
		
			if(!this.cachedMenus)
				this.cachedMenus = Array();
				
			// save items
			this.cachedMenus[current_dir] = menu;
			
			// set loading on menu
			menu.removeAll();
			menu.add({
				text: '<span class="loading-indicator">' + this.loadingText + '</span>',
				disabled: true
			});
			
			// use the store to get folders
			this.store.baseParams[this.queryParam] = current_dir;
			this.store.load({
				menu: menu,
				callback: this.addMenuItems,
				scope: this,
				params: {
					count: 40,
					start: 0
				}
			});
			button.loaded = true;
		}
	},
	
	addMenuItems : function(r, options, success) {
		if(options.menu)
		{
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
										this.address.startValue = this.path;
										if(String(this.address.getValue()) !== String(this.address.startValue)){
											this.address.fireEvent('change', this.address, this.path, this.startValue);
										}
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
						{text: 'Search...'}
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

