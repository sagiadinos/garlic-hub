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

import {PlaylistsApiConfig} from "./PlaylistsApiConfig.js";
import {BaseService}        from "../../../../core/Base/BaseService.js";

export class PlaylistsService extends BaseService
{
	async loadSelectorTemplate()
	{
		const url    = PlaylistsApiConfig.SELECTOR_URI;
		const result = await this._sendRequest(url, "GET", null);

		return result.template;
	}

	async findPlaylists(playlistMode, playlistName)
	{
		const url     = `${PlaylistsApiConfig.FIND_URI}/${playlistMode}/${playlistName}`;
		const response    = await fetch(url);
		return await response.json();
	}


}

