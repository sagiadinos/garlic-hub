import {TreeViewApiConfig} from "./TreeViewApiConfig.js";
import {Wunderbaum} from "../../external/wunderbaum.esm.min.js";
import {EventEmitter} from "../../core/EventEmitter.js";

export class WunderbaumWrapper
{
	// Chrome and Safari make DataTransfer while Drag and drop useless
	// see https://stackoverflow.com/questions/12958136/html5-drag-and-drop-datatransfer-and-chrome
	static workaroundShitForMediaIdBecauseOfChrome = "";

	#emitter            = new EventEmitter();
	/**
	 * @property {Wunderbaum} #tree
	 * @property {WunderbaumNode} #activeNode
	 */
	#tree               = {};
	#activeNode         = null;
	#dndConfig          = null;
	#defaultConfig       = null;
	#treeViewElements   = null;

	constructor(treeViewElements)
	{
		this.#treeViewElements = treeViewElements;
		this.#initDefaultConfig();

		this.#tree = new Wunderbaum(this.#defaultConfig);
	}

	activateNodeByTarget(target)
	{
		// getNode is static for some reasons
		const node = Wunderbaum.getNode(target);
		node.setActive(true);

		return node;
	}

	addDragNDrop()
	{
		this.#initDragnDropConfig()
		this.#tree.setOption("dnd", this.#dndConfig);
	}

	addRootFolder(key, folderName)
	{
		this.#tree.addChildren({ key:  key, title: folderName, isFolder: true });
	}

	addSubFolder()
	{
		this.#activeNode.addChildren({ key:  key, title: folder_name, isFolder: true });
	}

	get treeViewElements()
	{
		return this.#treeViewElements;
	}

	on(eventName, listener)
	{
		return this.#emitter.on(eventName, listener);
	}

	off(eventName, listener)
	{
		return this.#emitter.off(eventName, listener);
	}

	#initDefaultConfig()
	{
		this.#defaultConfig = {
			debugLevel: TreeViewApiConfig.DEBUG_LEVEL,
			element: this.#treeViewElements.mediapoolTree,
			source: {url: TreeViewApiConfig.ROOT_NODES_URI},
			init: async (e) =>
			{
				if (localStorage.getItem('parent_list') === null)
					return;

				const parentList = localStorage.getItem('parent_list').split(",");
				let node = null;
				for (const key of parentList)
				{
					node = e.tree.findKey(key);
					if (node === null)
						return;

					await node.setExpanded(true);
				}
				await node.setActive();
			},
			selectMode: "single",
			lazyLoad: function (e)
			{
				return {
					url: TreeViewApiConfig.SUB_NODES_URI + e.node.key,
					params: {parentKey: e.node.key}
				};
			},
			activate: (e) =>
			{
				this.#treeViewElements.currentPath.innerText = " / " + e.node.getPath(true, "title", " / ");
				this.#activeNode = e.node;

				const parentList = e.node.getParentList(false, true);
				let keyList = parentList.map(parent => parent.key);
				localStorage.setItem('parent_list', keyList);

				this.#loadMediaInDirectory(e.node.key);
			},
			filter: {autoApply: true, mode: "hide"}
		}
	}

	#initDragnDropConfig()
	{
		this.#dndConfig = {
			effectAllowed: "move",
			dropEffectDefault: "move",
			guessDropEffect: false,
			preventNonNodes: false,
			preventForeignNodes: false,
			dragStart: (e) =>
			{
				e.event.dataTransfer.effectAllowed = "all";
				return true;
			},
			dragOver: (e) =>
			{
				return true;
			},
			dragLeave: (e) =>
			{
				return true;
			},
			dragEnter: (e) =>
			{
				if (e.sourceNode === null) // media drag'nDrop
					return ["appendChild"];
				else
					return ["before", "after", "appendChild"];
			},
			drop: (e) =>
			{
				if (e.sourceNode === null) // media Drag'nDrop
				{
					const mediaId = WunderbaumWrapper.workaroundShitForMediaIdBecauseOfChrome;
					if (mediaId === null || mediaId === undefined)
						throw Error("mediaId is not defined");

					this.#moveMediaTo(mediaId, e.node.key);
					WunderbaumWrapper.workaroundShitForMediaIdBecauseOfChrome = ""; // reset for security
				}
				else // node Drag'nDrop
				{
					this.#moveNodeTo(e);
				}
			}
		};

		// prevent a drag into this field
		this.#treeViewElements.treeViewFilter.addEventListener('dragover', (event) => event.preventDefault());
		this.#treeViewElements.treeViewFilter.addEventListener('drop', (event) => event.preventDefault());
		this.#treeViewElements.treeViewFilter.addEventListener("input", (event) => {
			this.#tree.filterNodes(event.target.value, { mode: "hide" });
		})

	}

	#loadMediaInDirectory(key)
	{
		this.#emitter.emit('loadMediaInDirectory', { node_id: key });
	}

	#moveMediaTo(mediaId, nodeId)
	{
		this.#emitter.emit('moveMediaTo', { media_id: mediaId, node_id: nodeId });
	}

	#moveNodeTo(e)
	{
		this.#emitter.emit('moveNodeTo', { event: e });
	}
}