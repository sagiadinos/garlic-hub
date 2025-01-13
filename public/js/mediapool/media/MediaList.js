/*
 garlic-hub: Digital Signage Management Platform

 Copyright (C) 2024 Nikolaos Sagiadinos <garlic@saghiadinos.de>
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

export class MediaList
{
    #mediaListElement = null;
    #templateElement = null;
    #contextMenuMediaFactory = null
    constructor(mediaListElement, templateElement, contextMenuMediaFactory)
    {
        this.#templateElement         = templateElement;
        this.#mediaListElement        = mediaListElement;
        this.#contextMenuMediaFactory = contextMenuMediaFactory;
    }

    render(data)
    {
        this.#mediaListElement.innerHTML = ""; // Clear previous content

        data.forEach((media) => {
            this.#addMediaToList(media);
        });

        const lightbox = GLightbox({
            plyr: {
                css: "/css/external/plyr.css",
                js: "/js/external/plyr.js"
            },
            width: "90vw",
            height: "90vh",
            loop: false,
            autoplayVideos: true

        });
    }

    deleteMediaDomBy(dataMediaId)
    {
        const element = document.querySelector(`[data-media-id="${dataMediaId}"]`);
        if (element)
            element.remove();
    }

    toggleUploader(show)
    {
        document.getElementById("file-uploader").style.display = show ? "block" : "none";
    }

    #addMediaToList(media)
    {
        const mediaItem = this.#createMediaItem(media);
        this.#addContextMenu(mediaItem);
        this.#mediaListElement.appendChild(mediaItem);
    }

    #createMediaItem(media)
    {
        const clone = this.#templateElement.content.cloneNode(true);

        clone.querySelector(".media-type-icon").classList.add(this.#detectMediaType(media.mimetype));
        clone.querySelector(".media-item").setAttribute("data-media-id", media.media_id);
        const img = clone.querySelector("img");
        img.src = "/var/mediapool/thumbs/"+media.checksum+"." + media.thumb_extension;
        img.alt = "Thumbnail: " + media.filename;

        const a = clone.querySelector("a");
        a.href  = "/var/mediapool/originals/"+media.checksum+"." + media.extension;
        if (media.extension !== "pdf")
        {
            a.setAttribute("data-title", media.filename);
            a.setAttribute("data-description", media.media_description);
            a.setAttribute("data-desc-position", "bottom");
        }

        clone.querySelector(".media-owner").textContent = media.username;
        clone.querySelector(".media-filename").textContent = media.filename;
        clone.querySelector(".media-filesize").textContent = this.#formatBytes(media.metadata.size);
        clone.querySelector(".media-mimetype").textContent = media.mimetype;
        const dimensionsElement = clone.querySelector(".media-dimensions");
        if (media.metadata.dimensions !== undefined && Object.keys(media.metadata.dimensions).length > 0 )
        {
            dimensionsElement.textContent = media.metadata.dimensions.width + "x" + media.metadata.dimensions.height;
            dimensionsElement.parentElement.style.display = "block";
        }
        const durationElement = clone.querySelector(".media-duration");
        if (media.metadata.duration !== undefined && media.metadata.duration > 0 )
        {
            durationElement.textContent = this.#formatSeconds(media.metadata.duration);
            durationElement.parentElement.style.display = "block";
        }
        return clone.querySelector(".media-item");
    }

    #addContextMenu(mediaItem)
    {
        mediaItem.addEventListener("dragstart", (event) => {
            event.dataTransfer.setData("data-media-id", mediaItem.getAttribute("data-media-id")); // Speichere es im dataTransfer
        });
        mediaItem.addEventListener("contextmenu", (event) => {
            event.preventDefault();

            const contextMenu    = this.#contextMenuMediaFactory.createMenu();
            contextMenu.show(event);

            contextMenu.addRemoveEvent(document.getElementById("remove_media"), mediaItem);
            contextMenu.addEditEvent(document.getElementById("edit_media"), mediaItem);
        });
    }


    #detectMediaType(mimetype)
    {
        let mediatype = "file";
        const first = mimetype.split("/")[0]

        if (first === "audio")
            return "bi-music-note";
        else if (first === "video")
            return "bi-film";
        else if (first === "image")
            return "bi-image";

        switch(mimetype.split("/")[1])
        {
            case "pdf":
                return "bi-filetype-pdf";
            case "zip":
            case "widget":
            case "wgt":
                return "bi-file-zip";

        }

        return "bi-file-earmark";
    }

    #formatBytes(bytes)
    {
        if (bytes >= 1073741824)  // 1 GB
            return (bytes / 1073741824).toFixed(2) + " GB";
         else if (bytes >= 1048576)  // 1 MB
            return (bytes / 1048576).toFixed(2) + " MB";
         else if (bytes >= 1024)  // 1 KB
            return (bytes / 1024).toFixed(2) + " KB";
         else
            return bytes + " Bytes"; // less 1 KB
    }

    #formatSeconds(seconds)
    {
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const secs = seconds % 60;

        return `${String(hours).padStart(2, "0")}:${String(minutes).padStart(2, "0")}:${String(secs).padStart(2, "0")}`;
    }
}
