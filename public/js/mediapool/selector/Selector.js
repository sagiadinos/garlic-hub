import {EventEmitter} from "../../core/EventEmitter.js";

export class Selector
{
	#filter = "";
	#selectedMediaId = 0;
	#selectedMediaLink = "";
	#emitter = new EventEmitter();

	#dragItem = null;
	#isDragDrop = true;
	#dropTarget = null;


	#treeViewWrapper  = {};
	#mediaService = {};
	#selectorView = {};

	constructor(treeViewWrapper, mediaService, selectorView)
	{
		this.#treeViewWrapper = treeViewWrapper;
		this.#mediaService = mediaService;
		this.#selectorView = selectorView;
		this.#initEvents();
	}

	set filter(value)
	{
		this.#filter = value;
	}

	get selectedMediaId()
	{
		return this.#selectedMediaId;
	}

	get selectedMediaLink()
	{
		return this.#selectedMediaLink;
	}

	set dropTarget(value)
	{
		this.#dropTarget = value;
	}

	set isDragDrop(value)
	{
		this.#isDragDrop = value;
	}

	on(eventName, listener)
	{
		return this.#emitter.on(eventName, listener);
	}

	off(eventName, listener)
	{
		return this.#emitter.off(eventName, listener);
	}

	async showSelector(element)
	{
		element.innerHTML = await this.#mediaService.loadSelectorTemplate();

		this.#treeViewWrapper.initTree();
	}

	async loadMedia(nodeId)
	{
		return await this.#mediaService.loadFilteredMediaByNodeId(nodeId, this.#filter);
	}

	displayMediaList(mediaList)
	{
		this.#selectorView.displayMediaList(mediaList);

		if (this.#isDragDrop === true)
			this.#prepareDragDrop();
	}


	#initEvents()
	{
		this.#treeViewWrapper.on("treeview:loadMediaInDirectory", async (args) =>
		{
			const results = await this.loadMedia(args.node_id);
			this.displayMediaList(results);
		});
	}

	#prepareDragDrop()
	{
		for (const media of this.#selectorView.mediaItems)
		{
			media.mediaItem.addEventListener("dragstart", (event) =>
			{
				this.#dragItem = media;
				event.dataTransfer.effectAllowed = 'copy';
			});
		}
		this.#dropTarget.addEventListener('dragover', (event) => {
			event.preventDefault();
		});
		this.#dropTarget.addEventListener('drop', (event) => {
			event.preventDefault();
			this.#createPlaylistItem(this.#dragItem);
			this.#emitter.emit('mediapool:selector:drop', { id: this.#dragItem.mediaId });
			this.#dragItem = null;
		});
	}

	/**
	 *
	 * @param {Media} media
	 * @returns {Node}
	 */
	#createPlaylistItem(media)
	{
		const template = document.getElementById("playlistItemTemplate");
		const playlistItem = template.content.cloneNode(true);

		const listItem = playlistItem.querySelector('.playlist-item');
		listItem.dataset.mediaId = media.mediaId;

		const thumbnail = playlistItem.querySelector('img');
		thumbnail.src = media.thumbnailPath;
		thumbnail.alt = media.filename;

		const itemName = playlistItem.querySelector('.item-name');
		itemName.textContent = media.filename;

		const itemDuration = playlistItem.querySelector('.item-duration');
		itemDuration.textContent = media.duration;


		this.#dropTarget.appendChild(playlistItem);
	}

}
