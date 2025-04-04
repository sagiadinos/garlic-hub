export class SelectorView
{
	#mediaFactory = null;
	#mediaList = null;
	#mediaItems = [];

	constructor(mediaFactory)
	{
		this.#mediaFactory = mediaFactory;
	}

	get mediaItems()
	{
		return this.#mediaItems;
	}

	displayMediaList(mediaDataList)
	{
		this.#mediaList = document.getElementById("mediaList");
		this.#mediaList.innerHTML = "";

		for (const mediaData of mediaDataList)
		{
			let media = this.#mediaFactory.create(mediaData);
			this.#mediaItems.push(media);
			this.#mediaList.appendChild(media.renderSimple());
		}
	}

}