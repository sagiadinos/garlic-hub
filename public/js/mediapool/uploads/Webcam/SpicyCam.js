/*
 * SpicyCam: JavaScript Lib for easy Camera access
 *
 * SpicyCam is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.
 *
 * SpicyCam is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with [Project Name].  If not, see <http://www.gnu.org/licenses/>.
 *
 * Copyright (C) 2025 Nikolaos Sagiadinos <niko@saghiadinos.de>
 */

/**
 * This class was inspired by Benson Ruan webcam-easy
 */
export class SpicyCam
{
	#imageQuality    = 0.8
    #videoElement    = null;
    #camerasList     = [];
    #streamList      = [];
    #currentDeviceId = '';
    #settings        = null;
	#isMirror        = false;

    constructor(videoElement)
    {
        this.#videoElement        = videoElement;
        this.#videoElement.width  = this.#videoElement.width || 640;
        this.#videoElement.height = this.#videoElement.height || 360;
    }

	set isMirror(value)	{ this.#isMirror = value;}

	get camerasList() { return this.#camerasList; }

    countCameras() { return this.#camerasList.length; }

    get currentDeviceId() { return this.#currentDeviceId;}

    getMediaConstraints()
    {
        const videoConstraints    = {};

        videoConstraints.deviceId = {exact: this.#currentDeviceId};
        videoConstraints.width    = {ideal: 3840};
        videoConstraints.height   = {ideal: 2160};

        return {video: videoConstraints, audio: false};
    }

    selectCamera(deviceId)
    {
        for(let camera of this.#camerasList)
        {
            if (deviceId === camera.deviceId)
            {
                this.#currentDeviceId = camera.deviceId;
                return;
            }
        }
    }

    detectVideoDevices()
    {
        return new Promise(async (resolve, reject) => {
            try
            {
                await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
                const devices     = await navigator.mediaDevices.enumerateDevices();
                this.#camerasList = devices.filter(device => device.kind === 'videoinput');

				resolve(this.#camerasList);
            }
            catch (err)
            {
				console.error('Error accessing media devices:', err);

				if (err.name === 'NotAllowedError')
					reject(new Error('Permission to access media devices was denied.'));
				 else if (err.name === 'NotFoundError')
					reject(new Error('No media devices found.'));
				 else
					reject(new Error('An unknown error occurred.'));
			}
        });
    }

	/**
	 *
	 * Just start streaming the first video device found
	 * to the video elements
	 */
	justStart()
	{
		return new Promise(async (resolve, reject) => {
			try
			{
				await this.detectVideoDevices();
				if (this.countCameras() > 0)
				{
					this.#currentDeviceId = this.#camerasList[0].deviceId;
					await this.startCamera();
					resolve();
				}
				else
				{
					reject(new Error("No video device was found."));
				}
			}
			catch (error)
			{
				reject(error);
			}
		});
	}

    async startCamera()
    {
        return new Promise((resolve, reject) => {
            navigator.mediaDevices.getUserMedia(this.getMediaConstraints())
                .then(stream => {
                    const track = stream.getVideoTracks()[0];
                    this.#settings = track.getSettings();

                    this.#streamList.push(stream);
                    this.#videoElement.srcObject = stream;

                    if(this.#isMirror)
                        this.#videoElement.style.transform = "scale(-1,1)";

                    this.#videoElement.play();
                    resolve(this.#currentDeviceId);
                })
                .catch(error => {
                    console.log(error);
                    reject(error);
                });
        });
    }

    stopCamera()
    {
        this.#streamList.forEach(stream => {
            stream.getTracks().forEach(track => {
                track.stop();
            });
        });

		this.#videoElement.srcObject = null; // reset video element
    }

    capturePhoto(canvasElement)
    {
		return this.capturePhotoAsJpeg(canvasElement);
    }

	capturePhotoAsPng(canvasElement)
	{
		canvasElement = this.#handleCanvas(canvasElement);
		return canvasElement.toDataURL('image/png');
	}

	capturePhotoAsJpeg(canvasElement)
	{
		canvasElement = this.#handleCanvas(canvasElement);
		return canvasElement.toDataURL('image/jpeg', this.#imageQuality);
	}

	capturePhotoAsWebp(canvasElement)
	{
		canvasElement = this.#handleCanvas(canvasElement);
		return canvasElement.toDataURL('image/webp', this.#imageQuality);
	}

	#handleCanvas(canvasElement)
	{
		canvasElement.height = this.#settings.height;
		canvasElement.width = this.#settings.width;
		let context = canvasElement.getContext('2d');
		if(this.#isMirror)
		{
			context.translate(canvasElement.width, 0);
			context.scale(-1, 1);
		}
		context.clearRect(0, 0, canvasElement.width, canvasElement.height);
		context.drawImage(this.#videoElement, 0, 0, canvasElement.width, canvasElement.height);

		return canvasElement;
	}
}