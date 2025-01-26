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

import { AbstractStockPlatform } from './AbstractStockPlatform.js';
export class UnsplashPlatform extends AbstractStockPlatform
{
	#searchUri = "https://api.unsplash.com/search/photos"

	constructor(fetchClient)
	{
		super(fetchClient);
	}

	search(query)
	{
		(async () => {
			const filePath = this.domElements.externalLinkField.value;
			try
			{

				this.uploaderDialog.disableActions();

				const apiUrl   = this.#searchUri + "?query=" + query + "&client_id=" + this.apiToken;
				const result = await this.fetchClient.fetchData(apiUrl);

				if (!result || !result.success)
					console.error('Error for file:', filePath, result?.error_message || 'Unknown error');
				else
				{
					this.domElements.externalLinkField.value = "";
					this.disableUploadButton();
				}

			}
			catch(error)
			{
				console.log('Upload failed for file:', filePath, '\n', error.message);
			}
			finally
			{
				this.uploaderDialog.enableActions()
			}

		})();


	}


	hasApiToken()
	{
		if (localStorage.getItem('UnsplashApiToken') === null)
			return false

		this.apiToken = localStorage.getItem('UnsplashApiToken');
		return true;
	}

	saveToken(token)
	{
		localStorage.setItem('UnsplashApiToken', token);
		this.apiToken = token;
	}
}