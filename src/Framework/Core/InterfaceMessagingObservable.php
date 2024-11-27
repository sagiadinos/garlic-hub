<?php
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

namespace App\Framework\Core;

/**
 * Interface InterfaceMessagingObservable
 */
interface InterfaceMessagingObservable
{
	/**
	 * register an observer
	 *
	 * @param InterfaceMessagingObserver $observer
	 * @return $this
	 */
	public function registerObserver(InterfaceMessagingObserver $observer): static;

	/**
	 * remove a registered observer by name
	 *
	 * @param   string  $observer_name
	 * @return  $this
	 */
	public function unregisterObserver($observer_name): static;

	/**
	 * send a message to all registered notifiers.
	 * Should call InterfaceMessagingObserver::notify($message) on every registered observer
	 *
	 * @param string    $message
	 * @return $this
	 */
	public function notifyObservers($message): static;
}