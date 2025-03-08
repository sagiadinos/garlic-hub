export class CanvasView
{
	canvas        = {}
	width         = 0;
	height        = 0;
	changed       = false;
	_clipboard = {};

	constructor(canvas, lang)
	{
		this.canvas = canvas;
		this.lang = lang;
	}

	setDimensions(width, height)
	{
		this.width = width;
		this.height = height;
		this.canvas.setWidth(width);
		this.canvas.setHeight(height);
		this.renderCanvas();
	}

	getWidth()
	{
		return this.width;
	}

	getHeight()
	{
		return this.height;
	}

	setChanged(val)
	{
		this.changed = val;
	}

	hasChanged()
	{
		return this.changed;
	}

	renderCanvas()
	{
		this.canvas.renderAll();
	}

	dublicateActiveObject()
	{
		this.copyActiveObjectToClipboard();
		this.pasteFromClipboardToPos(this._clipboard.left + 20, this._clipboard.top + 20)
	}

	removeActiveObject()
	{
		let object = this.getActiveObject();
		if (object == null)
			return;

		return this.removeObject(object);
	}

	removeObject(object)
	{
		return this.canvas.remove(object);
	}

	moveActiveObject(direction, step = 50)
	{
		if (this.getActiveObject() === undefined)
			return;

		switch (direction) {
			case "ArrowLeft":
				this.getActiveObject().left -= step;
				break;
			case "ArrowRight":
				this.getActiveObject().left += step;
				break;
			case "ArrowUp":
				this.getActiveObject().top -= step;
				break;
			case "ArrowDown":
				this.getActiveObject().top += step;
				break;
			default:
				break;
		}
		// this.getCanvas().fire('object:modified');
	}

	#copyActiveObjectToClipboard()
	{
		let object = this.getActiveObject();
		if (object == null)
			return;

		object.clone(cloned =>
					 {
						 this._clipboard = cloned;
					 });
	}

	#pasteFromClipboardToPos(x, y)
	{
		if (this._clipboard == null)
			return;

		this._clipboard.clone(cloned =>
							  {
								  this.canvas.discardActiveObject();
								  cloned.set({
												 left: x,
												 top: y,
												 evented: true,
											 });
								  if (cloned.type === 'activeSelection')
								  {
									  cloned.canvas = this.canvas;
									  cloned.forEachObject((obj) =>
														   {
															   this.canvas.add(obj);
														   });
									  // this should solve the unselectability
									  cloned.setCoords();
								  }
								  else
									  this.canvas.add(cloned);

								  this.canvas.setActiveObject(cloned);
								  this.canvas.requestRenderAll();
							  });
	}


	getActiveObject()
	{
		return this.canvas.getActiveObject();
	}

	setActiveObject(object)
	{
		return this.canvas.setActiveObject(object);
	}

	addZone(MyLabeledZone)
	{
		this.canvas.add(MyLabeledZone);
	}

	getCanvas()
	{
		return this.canvas;
	}

	scaleCanvas(zoom)
	{
		this.canvas.setZoom(zoom / 100);
		this.canvas.setWidth(Math.floor(this.width / 100 * zoom))
		this.canvas.setHeight(Math.floor(this.height / 100 * zoom));
	}

}