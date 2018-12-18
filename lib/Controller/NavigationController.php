<?php
declare(strict_types=1);


/**
 * Nextcloud - Social Support
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2018, Maxence Lange <maxence@artificial-owl.com>
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

namespace OCA\Social\Controller;


use daita\MySmallPhpTools\Traits\Nextcloud\TNCDataResponse;
use daita\MySmallPhpTools\Traits\TArrayTools;
use Exception;
use OC\User\NoUserException;
use OCA\Social\AppInfo\Application;
use OCA\Social\Exceptions\AccountAlreadyExistsException;
use OCA\Social\Exceptions\SocialAppConfigException;
use OCA\Social\Service\ActivityPub\DocumentService;
use OCA\Social\Service\ActivityPub\PersonService;
use OCA\Social\Service\ActorService;
use OCA\Social\Service\CheckService;
use OCA\Social\Service\ConfigService;
use OCA\Social\Service\MiscService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\FileDisplayResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IURLGenerator;

class NavigationController extends Controller {


	use TArrayTools;
	use TNCDataResponse;


	/** @var string */
	private $userId;

	/** @var IConfig */
	private $config;

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var ActorService */
	private $actorService;

	private $documentService;

	/** @var ConfigService */
	private $configService;

	/** @var MiscService */
	private $miscService;

	/** @var IL10N */
	private $l10n;

	/** @var PersonService */
	private $personService;

	/** @var CheckService */
	private $checkService;

	/**
	 * NavigationController constructor.
	 *
	 * @param IRequest $request
	 * @param string $userId
	 * @param IConfig $config
	 * @param IURLGenerator $urlGenerator
	 * @param ActorService $actorService
	 * @param DocumentService $documentService
	 * @param ConfigService $configService
	 * @param PersonService $personService
	 * @param CheckService $checkService
	 * @param MiscService $miscService
	 * @param IL10N $l10n
	 */
	public function __construct(
		IRequest $request, $userId, IConfig $config, IURLGenerator $urlGenerator,
		ActorService $actorService, DocumentService $documentService, ConfigService $configService,
		PersonService $personService, CheckService $checkService,
		MiscService $miscService, IL10N $l10n
	) {
		parent::__construct(Application::APP_NAME, $request);

		$this->userId = $userId;
		$this->config = $config;
		$this->urlGenerator = $urlGenerator;
		$this->checkService = $checkService;

		$this->actorService = $actorService;
		$this->documentService = $documentService;
		$this->configService = $configService;
		$this->personService = $personService;
		$this->miscService = $miscService;
		$this->l10n = $l10n;
	}


	/**
	 * Display the navigation page of the Social app.
	 *
	 * @NoCSRFRequired
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @param string $path
	 *
	 * @return TemplateResponse
	 */
	public function navigate(string $path = ''): TemplateResponse {
		$data = [
			'serverData' => [
				'public'   => false,
				'firstrun' => false,
				'setup'    => false,
				'isAdmin'  => \OC::$server->getGroupManager()->isAdmin($this->userId),
				'cliUrl'   => $this->getCliUrl()
			]
		];

		try {
			$data['serverData']['cloudAddress'] = $this->configService->getCloudAddress();
		} catch (SocialAppConfigException $e) {
			$this->checkService->checkInstallationStatus();
			$cloudAddress = $this->setupCloudAddress();
			if ($cloudAddress !== ''){
				$data['serverData']['cloudAddress'] = $cloudAddress;
			} else {
				$data['serverData']['setup'] = true;

				if ($data['serverData']['isAdmin']) {
					$cloudAddress = $this->request->getParam('cloudAddress');
					if ($cloudAddress !== null) {
						$this->configService->setCloudAddress($cloudAddress);
					} else {
						return new TemplateResponse(Application::APP_NAME, 'main', $data);
					}
				}
			}
		}

		if ($data['serverData']['isAdmin']) {
			$checks = $this->checkService->checkDefault();
			$data['serverData']['checks'] = $checks;
		}

		/*
		 * Create social user account if it doesn't exist yet
		 */
		try {
			$this->actorService->createActor($this->userId, $this->userId);
			$data['serverData']['firstrun'] = true;
		} catch (AccountAlreadyExistsException $e) {
			// we do nothing
		} catch (NoUserException $e) {
			// well, should not happens
		} catch (SocialAppConfigException $e) {
			// neither.
		}

		return new TemplateResponse(Application::APP_NAME, 'main', $data);
	}

	private function setupCloudAddress(): string {
		$frontControllerActive = ($this->config->getSystemValue('htaccess.IgnoreFrontController', false) === true || getenv('front_controller_active') === 'true');

		$cloudAddress = rtrim($this->config->getSystemValue('overwrite.cli.url', ''), '/');
		if ($cloudAddress !== '') {
			if (!$frontControllerActive) {
				$cloudAddress .= '/index.php';
			}
			$this->configService->setCloudAddress($cloudAddress);
			return $cloudAddress;
		}
		return '';
	}

	private function getCliUrl() {
		$url = rtrim($this->urlGenerator->getBaseUrl(), '/');
		$frontControllerActive = ($this->config->getSystemValue('htaccess.IgnoreFrontController', false) === true || getenv('front_controller_active') === 'true');
		if (!$frontControllerActive) {
			$url .= '/index.php';
		}
		return $url;
	}

	/**
	 * Display the navigation page of the Social app.
	 *
	 * @NoCSRFRequired
	 * @PublicPage
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @return DataResponse
	 */
	public function test(): DataResponse {

		$setup = false;
		try {
			$address = $this->configService->getCloudAddress(true);
			$setup = true;
		} catch (SocialAppConfigException $e) {
		}

		return $this->success(
			[
				'version' => $this->configService->getAppValue('installed_version'),
				'setup'   => $setup
			]
		);
	}


	/**
	 * Display the navigation page of the Social app.
	 *
	 * @NoCSRFRequired
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @param string $path
	 *
	 * @return TemplateResponse
	 */
	public function timeline(string $path = ''): TemplateResponse {
		return $this->navigate();
	}

	/**
	 * Display the navigation page of the Social app.
	 *
	 * @NoCSRFRequired
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @param string $path
	 *
	 * @return TemplateResponse
	 */
	public function account(string $path = ''): TemplateResponse {
		return $this->navigate();
	}


	/**
	 *
	 * // TODO: Delete the NoCSRF check
	 *
	 * @NoCSRFRequired
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @param string $id
	 *
	 * @return Response
	 */
	public function documentGet(string $id): Response {

		try {
			$file = $this->documentService->getFromCache($id);

			return new FileDisplayResponse($file);
		} catch (Exception $e) {
			return $this->fail($e);
		}
	}


	/**
	 *
	 * // TODO: Delete the NoCSRF check
	 *
	 * @PublicPage
	 * @NoCSRFRequired
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @param string $id
	 *
	 * @return Response
	 */
	public function documentGetPublic(string $id): Response {

		try {
			$file = $this->documentService->getFromCache($id, true);

			return new FileDisplayResponse($file);
		} catch (Exception $e) {
			return $this->fail($e);
		}
	}

}
