// JavaScript Document

// mods to library
Ext.Fx.shift = function(o){
	var el = this.getFxEl();
	o = o || {};
	el.queueFx(o, function(){
		var a = {}, w = o.width, h = o.height, x = o.x, y = o.y,  op = o.opacity;
		if(w !== undefined){
			a.width = {to: this.adjustWidth(w)};
		}
		if(h !== undefined){
			a.height = {to: this.adjustHeight(h)};
		}
		if(o.left !== undefined){
			a.left = {to: o.left};
		}
		if(o.top !== undefined){
			a.top = {to: o.top};
		}
		if(o.right !== undefined){
			a.right = {to: o.right};
		}
		if(o.bottom !== undefined){
			a.bottom = {to: o.bottom};
		}
		if(x !== undefined || y !== undefined){
			a.points = {to: [
				x !== undefined ? x : this.getX(),
				y !== undefined ? y : this.getY()
			]};
		}
		if(op !== undefined){
			a.opacity = {to: op};
		}
		if(o.xy !== undefined){
			a.points = {to: o.xy};
		}
		arguments.callee.anim = this.fxanim(a,
			o, 'motion', o.duration, "easeOut", function(){
			el.afterFx(o);
		});
	});
	return this;
}

Ext.apply(Ext.Element.prototype, Ext.Fx);

Ext.override(Ext.Window, {
	onRender : function(ct, position){
		Ext.Window.superclass.onRender.call(this, ct, position);
	
		if(this.plain){
			this.el.addClass('x-window-plain');
		}
	
		// this element allows the Window to be focused for keyboard events
		this.focusEl = this.el.createChild({
					tag: "a", href:"#", cls:"x-dlg-focus",
					tabIndex:"-1", html: "&#160;"});
		this.focusEl.swallowEvent('click', true);
	
		this.proxy = this.el.createProxy("x-window-proxy");
		this.proxy.enableDisplayMode('block');
	
		if(this.modal){
			this.mask = this.container.createChild({cls:"ext-el-mask"}, this.el.dom);
			this.mask.enableDisplayMode("block");
			this.mask.hide();
			this.mask.on({
				'click': function() {
					this.el.disableShadow();
					this.el.shift(
						{x: this.el.getX()-2, duration: .1}
					).shift(
						{x: this.el.getX()+4, duration: .1}
					).shift(
						{x: this.el.getX()-4, duration: .1}
					).shift(
						{x: this.el.getX()+2, duration: .1, callback: function() { this.enableShadow(); }, scope: this.el}
					);
				},
				scope: this
			});
		}
	}
});



