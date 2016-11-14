<?php
/**
 * @copyright Copyright (c) 2016 Joas Schilling <coding@schilljs.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Spreed\Notification;


use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Notification\INotification;
use OCP\Notification\INotifier;

class Notifier implements INotifier {

	/** @var IFactory */
	protected $lFactory;

	/** @var IURLGenerator */
	protected $url;

	/** @var IUserManager */
	protected $userManager;

	/**
	 * @param IFactory $lFactory
	 * @param IURLGenerator $url
	 * @param IUserManager $userManager
	 */
	public function __construct(IFactory $lFactory, IURLGenerator $url, IUserManager $userManager) {
		$this->lFactory = $lFactory;
		$this->url = $url;
		$this->userManager = $userManager;
	}

	/**
	 * @param INotification $notification
	 * @param string $languageCode The code of the language that should be used to prepare the notification
	 * @return INotification
	 * @throws \InvalidArgumentException When the notification was not prepared by a notifier
	 * @since 9.0.0
	 */
	public function prepare(INotification $notification, $languageCode) {
		if ($notification->getApp() !== 'spreed') {
			throw new \InvalidArgumentException('Incorrect app');
		}

		$l = $this->lFactory->get('spreed', $languageCode);

		if ($notification->getSubject() === 'invitation') {
			$parameters = $notification->getSubjectParameters();
			$uid = $parameters[0];
			$notification
				->setIcon($this->url->getAbsoluteURL($this->url->imagePath('spreed', 'app.svg')))
				->setLink($this->url->linkToRouteAbsolute('spreed.page.index') . '#' . $notification->getObjectId());

			if ($notification->getObjectType() === 'one2one') {
				$user = $this->userManager->get($uid);
				if ($user instanceof IUser) {
					$notification
						->setParsedSubject(
							$l->t('%s wants to have a call with you', [$user->getDisplayName()])
						)
						->setRichSubject(
							$l->t('{user} wants to have a call with you'), [
								'user' => [
									'type' => 'user',
									'id' => $uid,
									'name' => $user->getDisplayName(),
								]
							]
						)
					;
				} else {
					throw new \InvalidArgumentException('Calling user does not exist anymore');
				}
			} else {
				throw new \InvalidArgumentException('Unknown object type');
			}
		} else {
			throw new \InvalidArgumentException('Unknown subject');
		}

		return $notification;
	}
}