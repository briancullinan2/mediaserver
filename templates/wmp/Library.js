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



