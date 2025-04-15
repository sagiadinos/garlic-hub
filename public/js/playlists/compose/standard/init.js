/*
 garlic-hub: Digital Signage Management Platform

 Copyright (C) 2025 Nikolaos Sagiadinos <garlic@saghiadinos.de>
 This file is part of the garlic-hub source code

 This program is free software: you can redistribute it and/or  modify
 it under the terms of the GNU Affero General Public License, version 3,
 as published by the Free Software Foundation.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU Affero General Public License for more details.

 You should have received a copy of the GNU Affero General Public License
 along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/
"use strict";

import {InsertContextMenu} from "./InsertContextMenu.js";
import {SelectorFactory} from "./SelectorFactory.js";
import {ItemsService}    from "./items/ItemsService.js";
import {FetchClient}     from "../../../core/FetchClient.js";
import ItemList          from "./items/ItemList.js";
import {ItemFactory}     from "./items/ItemFactory.js";
import {DragDropHandler} from "./DragDropHandler.js";
import {PlayListsProperties} from "./playlists/PlayListsProperties.js";
import {PlaylistsService} from "./playlists/PlaylistsService.js";

document.addEventListener("DOMContentLoaded", function ()
{
	const dropTarget = document.getElementById("thePlaylist");
	const playlistId = document.getElementById("playlist_id").value;

	const itemsService = new ItemsService(new FetchClient());
	const itemsList = new ItemList(new ItemFactory(), itemsService, dropTarget);

	const dragDropHandler = new DragDropHandler(dropTarget, itemsService, itemsList);
	dragDropHandler.playlistId = playlistId;

	const playlistsService = new PlaylistsService(new FetchClient());
	const insertContextMenu = new InsertContextMenu(new SelectorFactory(playlistsService), dragDropHandler);

	const playlistsProperties = new PlayListsProperties(playlistsService, lang);
	insertContextMenu.init(playlistId);
	itemsList.displayPlaylist(playlistId, playlistsProperties);

	playlistsProperties.init(playlistId);

});