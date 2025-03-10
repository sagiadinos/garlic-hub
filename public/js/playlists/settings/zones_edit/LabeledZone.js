export class LabeledZone extends fabric.Group
{
	static _transparent = "rgba(128, 128, 128, 0.3)";
	static count = 0;
	id = 0;
	rect  = null;
	label = null;
	_type = "LabeledZone";
	// transparent must have shown more usable in editor, so we fake it with a grey plus opacity
	zone_playlist_id = 0;
	zone_playlist_name = '';

	constructor(options = {})
	{
		let fill;
		if (options.zone_bgcolor === "transparent")
			fill = LabeledZone._transparent;
		else
			fill = options.zone_bgcolor || LabeledZone._transparent;

		const stroke_width = 1;
		let rect = new fabric.Rect({
		    left: Number(options.zone_left) || 0,
		    top: Number(options.zone_top) || 0,
			width: Number(options.zone_width) - stroke_width || 200,
			height: Number(options.zone_height)  - stroke_width || 150,
 		    originX: "left",
		    originY: "top",
			stroke: 'black',
			strokeWidth: stroke_width,
			fill: fill,
		});

		// getTextColorFunction must be static as need to call before super
		let label = new fabric.Text(options.zone_name, {
			fontSize: 32,
			originX: 'center',
			originY: 'center',
			left: rect.left + rect.width / 2,
			top: rect.top + rect.height / 2,
			fill: LabeledZone.getTextColorFunction(rect.fill)
		});

		let playlist_name = new fabric.Text(options.zone_playlist_name, {
			fontSize: 24,
			originX: 'center',
			originY: 'center',
			left: rect.left + rect.width / 2,
			top: (rect.top + rect.height / 2) + 40,
			fill: LabeledZone.getTextColorFunction(rect.fill)
		});

		super([rect, label, playlist_name], {
			name: options.zone_name,
			lockScalingFlip: true,
			lockSkewingX: true,
			lockSkewingY: true,
			lockRotation: true,
			hasRotatingPoint: false
		});
		this.setControlsVisibility({ mtr: false })

		LabeledZone.count++;
		this.id = "id-" + LabeledZone.count;
		this.rect  = rect;
		this.label = label;
		this.zone_playlist_id = options.zone_playlist_id || 0;


	}

	getPropertiesForDuplicate()
	{
		return {
			"zone_name": this.label.text,
			"zone_left":  this.left,
			"zone_top":  this.top,
			"zone_width":  this.width,
			"zone_height":  this.height,
			"zone_bgcolor": this.rect.fill,
			"zone_playlist_id" : this.zone_playlist_id,
			"zone_playlist_name" : this.zone_playlist_name
		};

	}

	getPropertiesForExport()
	{
		return {
			"zone_name": this.label.text,
			"zone_left":  Math.round(this.left),
			"zone_top":  Math.round(this.top),
			"zone_width":  Math.round(this.width),
			"zone_height":  Math.round(this.height),
			"zone_z-index": this.canvas.getObjects().indexOf(this),
			"zone_bgcolor": this.rect.fill,
			"zone_playlist_id" : this.zone_playlist_id
		};
	}

	getId()
	{
		return this.id;
	}

	getType()
	{
		return this._type;
	}

	onScaling()
	{
		// important because when we use mouse only scale will change and not the real with/height
		const w = this.rect.width * this.scaleX;
		const h = this.rect.height * this.scaleY;

		this.rect.set({width: w, height: h, left: -w / 2, top: -h / 2 });
		this.set({width: w,height: h});

		// as we changed the real position we need to set scaling back to 1
		this.rect.set({ scaleX: 1, scaleY: 1 });
		this.set({ scaleX: 1, scaleY: 1 });

		this.adjustLabel()

		this.dirty = true;
	}

	changeWidth(new_width)
	{
		this.set({ width: new_width })
		this.rect.set({ left: -new_width / 2, width: new_width}) ;
		this.adjustLabel();

		this.dirty = true;
	}

	changeHeight(new_height)
	{
		this.set({ height: new_height })
		this.rect.set({top: -new_height / 2, height: new_height});
		this.adjustLabel();
		this.dirty = true;
	}

	changeBgColor(new_color)
	{
		this.rect.set({fill: new_color});
		this.label.set({fill: LabeledZone.getTextColorFunction(new_color)});
		this.dirty = true;
	}

	adjustLabel()
	{
		this.label.set({
		   fontSize: 32
		});
	}

	static getTextColorFunction(hexColor)
	{
		if (hexColor === this._transparent)
			return 'black';

		hexColor = hexColor.replace(/^#/, '');
		let r = parseInt(hexColor.substring(0, 2), 16);
		let g = parseInt(hexColor.substring(2, 4), 16);
		let b = parseInt(hexColor.substring(4, 6), 16);
		let yiq = ((r * 299) + (g * 587) + (b * 114)) / 1000;

		return (yiq >= 128) ? 'black' : 'white';
	}

}
