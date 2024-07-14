<?php namespace ProcessWire;

/**
 * Manages the “remember me” feature for Tfa class
 * 
 * Accessed from $tfaInstance->remember($user, $settings)->method().
 * This class is kept in Tfa.php because it cannot be instantiated without
 * a Tfa instance. 
 * 
 * @method array getFingerprintArray($getLabels = false)
 * 
 * #pw-internal
 * 
 */
class RememberTfa extends Wire {

	/**
	 * Shows debug info in warning messages, only for development
	 * 
	 */
	public const debug = false;

	/**
	 * Max browsers to remember for any user
	 * 
	 */
	public const maxItems = 10; 

	/**
	 * @var Tfa
	 * 
	 */
	protected $tfa;

	/**
	 * @var User|null
	 * 
	 */
	protected $user = null;

	/**
	 * @var array
	 * 
	 */
	protected $settings = [];

	/**
	 * @var array
	 * 
	 */
	protected $remember = [];

	/**
	 * Days to remember
	 * 
	 * @var int
	 * 
	 */
	protected $days = 90;

	/**
	 * Means by which to fingerprint user (extras on top of random remembered cookie)
	 * 
	 * Options: agent, agentVL, accept, scheme, host, ip, fwip
	 * 
	 * @var array
	 * 
	 */
	protected $fingerprints = ['agentVL', 'accept', 'scheme', 'host'];

	/**
	 * Construct
	 *
	 * @param User $user
	 * @param Tfa $tfa
	 * @param array $settings
	 * 
	 */
	public function __construct(Tfa $tfa, User $user, array $settings) {
		$this->tfa = $tfa;
		$tfa->wire($this);
		$this->user = $user;
		$this->settings = $settings;
		if(isset($settings['remember'])) $this->remember = $settings['remember'];
		parent::__construct();
	}

	/**
	 * Set days to remember between logins
	 * 
	 * @param int $days
	 * 
	 */
	public function setDays($days) {
		$this->days = (int) $days;
	}

	/**
	 * Fingerprints to use for newly created "remember" items
	 * 
	 * @param array $fingerprints
	 * 
	 */
	public function setFingerprints(array $fingerprints) {
		$this->fingerprints = $fingerprints;
	}

	/**
	 * Save Tfa 'remember' settings
	 * 
	 * @return bool
	 * 
	 */
	protected function saveRemember() {
		if(count($this->remember)) {
			$this->settings['remember'] = $this->remember;
		} else {
			unset($this->settings['remember']); 
		}
		return $this->tfa->saveUserSettings($this->user, $this->settings);
	}

	/**
	 * Set combination of user/browser/host/page as remembered and allowed to skip TFA
	 *
	 * @return bool
	 *
	 */
	public function enable() {
		
		if(!$this->days) return false;
	
		$rand = new WireRandom();
		$this->wire($rand);
		$cookieValue = $rand->alphanumeric(0, ['minLength' => 40, 'maxLength' => 256]);
		$qty = count($this->remember);
		
		if($qty > self::maxItems) {
			$this->remember = array_slice($this->remember, $qty - self::maxItems);
		}
		
		do {
			$name = $rand->alpha(0, ['minLength' => 3, 'maxLength' => 7]);
		} while(isset($this->remember[$name]) || $this->getCookie($name) !== null);
		
		$this->remember[$name] = ['fingerprint' => $this->getFingerprintString(), 'created' => time(), 'expires' => strtotime("+$this->days DAYS"), 'value' => $this->serverValue($cookieValue), 'page' => $this->wire()->page->id];
		
		$this->debugNote("Enabled new remember: $name"); 
		$this->debugNote($this->remember[$name]); 

		$result = $this->saveRemember();
		if($result) $this->setCookie($name, $cookieValue);

		return $result;
	}

	/**
	 * Is current user/browser/host/URL one that is remembered and TFA can be skipped?
	 *
	 * @param bool $getName Return remembered cookie name rather than true? (default=false)
	 * @return bool|string
	 *
	 */
	public function remembered($getName = false) {
		
		if(!$this->days) return false;

		$page = $this->wire()->page;
		$fingerprint = $this->getFingerprintString();
		$valid = false;
		$validName = '';
		$disableNames = [];
		
		foreach($this->remember as $name => $item) {
		
			// skip any that do not match current page
			if("$item[page]" !== "$page->id") {
				$this->debugNote("Skipping $name because page: $item[page] != $page->id"); 
				continue;
			}
			
			if(!empty($item['expires']) && time() >= $item['expires']) {
				$this->debugNote("Skipping $name because it has expired (expires=$item[expires])");
				$disableNames[] = $name;
				continue;
			}

			$cookieValue = $this->getCookie($name);

			// skip any where cookie value isn't present
			if(empty($cookieValue)) {
				// if cookie not present on this browser skip it because likely for another browser the user has
				$this->debugNote("Skipping $name because cookie not present"); 
				continue;
			}
			
			// skip and remove any that do not match current browser fingerprint
			if(!$this->fingerprintStringMatches($item['fingerprint'])) {
				$fingerprintTypes = $this->getFingerprintTypes($item['fingerprint']); 
				if(!isset($fingerprintTypes['ip']) && !isset($fingerprintTypes['fwip'])) {
					// if IP isn't part of fingerprint then it is okay to remove this entry because browser can no longer match
					$disableNames[] = $name;
				}
				$this->debugNote("Skipping $name because fingerprint: $item[fingerprint] != $fingerprint");
				continue;
			}

			// cookie found, now validate it
			$valid = $item['value'] === $this->serverValue($cookieValue);

			if($valid) {
				// cookie is valid, now refresh it, resetting its expiration
				$this->debugNote("Valid remember: $name"); 
				$this->setCookie($name, $cookieValue);
				$validName = $name;
				break;
			} else {
				// clear because cookie value populated but is not correct
				$this->debugNote("Skipping $name because cookie does not authenticate with server value"); 
				$disableNames[] = $name;
			}
		}
		
		if(count($disableNames)) $this->disable($disableNames); 

		return ($getName && $valid ? $validName : $valid);
	}
	
	/**
	 * Disable one or more cookie/remembered client by name(s)
	 * 
	 * @param array|string $names
	 * @return int
	 * 
	 */
	public function disable($names) {
		if(!is_array($names)) $names = [$names]; 
		$qty = 0;
		foreach($names as $name) {
			$found = isset($this->remember[$name]);
			if($found) unset($this->remember[$name]);
			if($this->clearCookie($name)) $found = true;
			if($found) $qty++;
			if($found) $this->debugNote("Disabling: $name");
		}
		if($qty) $this->saveRemember();
		return $qty;
	}

	/**
	 * Disable all stored "remember me" data for user 
	 *
	 * @return bool
	 *
	 */
	public function disableAll() {
		// remove cookies
		foreach($this->remember as $name => $item) {
			$this->clearCookie($name);
		}
		// remove from user settings
		$this->remember = [];
		$this->debugNote("Disabled all"); 
		return $this->saveRemember();
	}

	/**
	 * Get a "remember me" cookie value
	 * 
	 * @param string $name
	 * @return string|null
	 * 
	 */
	protected function getCookie($name) {
		$name = $this->cookieName($name);
		return $this->wire()->input->cookie->get($name);
	}
	
	/**
	 * Set the "remember me" cookie
	 *
	 * @param string $cookieName
	 * @param string $cookieValue
	 * @return WireInputData
	 *
	 */
	protected function setCookie($cookieName, $cookieValue) {
		$cookieOptions = ['age' => ($this->days > 0 ? $this->days * 86400 : 31536000), 'httponly' => true, 'domain' => ''];
		if($this->config->https) $cookieOptions['secure'] = true;
		$cookieName = $this->cookieName($cookieName);
		$this->debugNote("Setting cookie: $cookieName=$cookieValue"); 
		return $this->wire()->input->cookie->set($cookieName, $cookieValue, $cookieOptions);
	}

	/**
	 * Get cookie prefix
	 * 
	 * @return string
	 * 
	 */
	protected function cookiePrefix() {
		$config = $this->wire()->config;
		$cookiePrefix = $config->https ? $config->sessionNameSecure : $config->sessionName;
		if(empty($cookiePrefix)) $cookiePrefix = 'wire';
		return $cookiePrefix . '_';
	}

	/**
	 * Given name return cookie name
	 * 
	 * @param string $name
	 * @return string
	 * 
	 */
	protected function cookieName($name) {
		$prefix = $this->cookiePrefix();
		if(!str_starts_with($name, $prefix)) $name = $prefix . $name;
		return $name;
	}

	/**
	 * Clear cookie
	 * 
	 * @param string $name
	 * @return bool
	 * 
	 */
	protected function clearCookie($name): bool {
		$name = $this->cookiePrefix() . $name;
		$cookies = $this->wire()->input->cookie;
		if($cookies->get($name) === null) return false;
		$cookies->set($name, null, []); // remove
		$this->debugNote("Clearing cookie: $name"); 
		return true;
	}

	/**
	 * Given a cookie value return equivalent expected server value 
	 * 
	 * @param string $cookieValue
	 * @param User|null $user
	 * @return string
	 * 
	 */
	protected function serverValue($cookieValue, User $user = null) {
		if($user === null) $user = $this->user;
		return sha1(
			$user->id . $user->name . $user->email . 
			substr(((string) $user->pass), 0, 15) . 
			substr($this->wire()->config->userAuthSalt, 0, 10) . 
			$cookieValue
		);
	}

	/**
	 * Get fingerprint of current browser, host and URL
	 * 
	 * Note that this is not guaranted unique, so is only a secondary security measure to
	 * ensure that remember-me record is married to an agent, scheme, and http host.
	 *
	 * @return array
	 *
	 */
	public function ___getFingerprintArray(): array {
		
		$config = $this->wire()->config;
		$agent = $_SERVER['HTTP_USER_AGENT'] ?? 'noagent';
		$fwip = '';
		
		if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])) $fwip .= $_SERVER['HTTP_X_FORWARDED_FOR'];
		if(isset($_SERVER['HTTP_CLIENT_IP'])) $fwip .= ' ' . $_SERVER['HTTP_CLIENT_IP'];
		if(empty($fwip)) $fwip = 'nofwip';
		
		$fingerprints = [
      'agent' => $agent,
      'agentVL' => preg_replace('![^a-zA-Z]!', '', $agent),
      // versionless agent
      'accept' => ($_SERVER['HTTP_ACCEPT'] ?? 'noaccept'),
      'scheme' => ($config->https ? 'HTTPS' : 'http'),
      'host' => $config->httpHost,
      'ip' => ($_SERVER['REMOTE_ADDR'] ?? 'noip'),
      'fwip' => $fwip,
  ];
		
		$fingerprint = [];
		
		foreach($this->fingerprints as $type) {
			$fingerprint[$type] = $fingerprints[$type];
		}
		
		$this->debugNote($fingerprint);
		
		return $fingerprint;
	}

	/**
	 * Get fingerprint string
	 *
	 * @param array $types Fingerprints to use, or omit when creating new
	 * @return string
	 *
	 */
	public function getFingerprintString(array $types = null) {
		if($types === null) $types = $this->fingerprints;
		return implode(',', $types) . ':' . sha1(implode("\n", $this->getFingerprintArray())); 
	}

	/**
	 * Does given fingerprint match one determined from current request?
	 * 
	 * @param string $fpstr Fingerprint to compare
	 * @return bool
	 * 
	 */
	protected function fingerprintStringMatches($fpstr): bool {
		$types = $this->getFingerprintTypes($fpstr);
		$fpnow = $types ? $this->getFingerprintString($types) : '';
		return ($fpstr && $fpnow && $fpstr === $fpnow);
	}

	/**
	 * Get the types used in given fingerprint string
	 * 
	 * @param string $fpstr
	 * @return array|bool
	 * 
	 */
	protected function getFingerprintTypes($fpstr) {
		if(!str_contains($fpstr, ':')) return false;
		[$types, ] = explode(':', $fpstr, 2);
		$a = explode(',', $types);
		$types = [];
		foreach($a as $type) $types[$type] = $type;
		return $types;
	}

	/**
	 * Display debug note
	 * 
	 * @param string|array $note
	 * 
	 */
	protected function debugNote($note) {
		if(self::debug) $this->warning($note);
	}
	
}
