<?php
class UnipusKeepOnline {
	private $baseURL;
	private $cookie;
	private $username;
	private $password;
	public $name = '';
	public $book = array();
	public $time = array();
	private function curl($url, $postfields = null) {
		$options = array();
		$options[CURLOPT_URL] = $url;
		// if (substr($options[CURLOPT_URL], 0, 8) === 'https://') {
		// 	$options[CURLOPT_SSL_VERIFYHOST] = false;
		// 	$options[CURLOPT_SSL_VERIFYPEER] = false;
		// }
		$options[CURLOPT_COOKIEFILE] = $this->cookie;
		$options[CURLOPT_COOKIEJAR] = $this->cookie;
		if (!is_null($postfields)) {
			$options[CURLOPT_POSTFIELDS] = $postfields;
			$options[CURLOPT_POST] = true;
		}
		$options[CURLOPT_ENCODING] = 'gzip,deflate';
		$options[CURLOPT_FOLLOWLOCATION] = true;
		$options[CURLOPT_MAXREDIRS] = 10;
		$options[CURLOPT_TIMEOUT] = 60;
		$options[CURLOPT_RETURNTRANSFER] = true;
		$ch = curl_init();
		curl_setopt_array($ch, $options);
		$content = curl_exec($ch);
		curl_close($ch);
		return $content;
	}
	public function __construct($baseURL, $cookie, $username, $password) {
		$this->baseURL = $baseURL;
		$this->cookie = $cookie;
		$this->username = $username;
		$this->password = $password;
	}
	private function login() {
		$html = $this->curl($this->baseURL, http_build_query(array(
			'username' => $this->username,
			'password' => $this->password,
		)));
		if (false !== strpos($html, 'Please login from the homepage at')) {
			$html = $this->curl($this->baseURL, http_build_query(array(
				'username' => $this->username,
				'password' => $this->password,
			)));
		}
		if (false === strpos($html, '<meta http-equiv="refresh" content="0; URL=/login/nsindex_student.php">'))
			return false;
		return true;
	}
	public function time() {
		$html = $this->curl($this->baseURL . 'login/onlinetime.php');
		if (false !== strpos($html, 'Please login from the homepage at')) {
			if (!$this->login())
				return false;
			$html = $this->curl($this->baseURL . 'login/onlinetime.php');
		}
		$this->time = array();
		if (1 !== preg_match('/<td colspan="1" align="center" valign="middle" >(.*?)<\\/td>/', $html, $matches))
			return false;
		$this->time[] = 'current month ' . $matches[1];
		if (false === preg_match_all('/<tr bgcolor="#f9f9f9">\\s*?<td align="center">(.+?)<\\/td>\\s*?<td align="center">(.+?)<\\/td>\\s*?<td align="center">(.+?)<\\/td>\\s*?<\\/tr>/', $html, $matches, PREG_SET_ORDER))
			return false;
		foreach ($matches as &$match) {
			$this->time[] = $match[1] . '.' . $match[2] . ' ' . $match[3];
		}
		return true;
	}
	public function info() {
		$html = $this->curl($this->baseURL . 'login/nsindex_student.php');
		if (false !== strpos($html, 'Please login from the homepage at')) {
			if (!$this->login())
				return false;
			$html = $this->curl($this->baseURL . 'login/nsindex_student.php');
		}
		if (1 !== preg_match('/<h3>(.*?)<a href="\\/login\\/logout\.php" target="_top">/', $html, $matches))
			return false;
		$this->name = $matches[1];
		if (false === preg_match_all('/<li >\\s*?<a href="(.*?)">\\s*?(\\S.*?\\S)\s*?<\\/a>\\s*?<br>&nbsp;\\s*?<\\/li>/', $html, $matches, PREG_SET_ORDER))
			return false;
		$this->book = array(array(
			'name' => '教学平台首页',
			'url' => '/login/nsindex_student.php',
		));
		foreach ($matches as &$match) {
			parse_str(parse_url($match[1], PHP_URL_QUERY), $query);
			$this->book[] = array(
				'name' => $match[2],
				'url' => '/book/book' . $query['BID'] . '/index.php',
			);
		}
		return $this->time();
	}
	public function keep($url) {
		$html = $this->curl($this->baseURL . 'template/loggingajax.php?whichURL=' . $url);
		if (false !== strpos($html, 'Please login from the homepage at')) {
			if (!$this->login())
				return false;
			$html = $this->curl($this->baseURL . 'template/loggingajax.php?whichURL=' . $url);
		}
		return true;
	}
}
