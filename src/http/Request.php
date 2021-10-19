<?php
namespace XAF\http;

class Request
{
	/**
	 * @return string|null HTTP request method in upper case (e.g. GET, POST, HEAD, DELETE, PUT)
	 */
	public function getMethod()
	{
		return isset($_SERVER['REQUEST_METHOD']) ? \strtoupper($_SERVER['REQUEST_METHOD']) : null;
	}

	/**
	 * @return string The original URL-path from the HTTP request whithout protocol, host or query string
	 */
	public function getRequestPath()
	{
		if( isset($_SERVER['REQUEST_URI']) )
		{
			return \rawurldecode(\parse_url($_SERVER['REQUEST_URI'], \PHP_URL_PATH));
		}
		return '/';
	}

	/**
	 * @return string
	 */
	public function getFullRequestUrl()
	{
		return \rtrim($this->getBaseUrl(), '/') . (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/');
	}

	/**
	 * @return string|null
	 */
	public function getRemoteIp()
	{
		return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
	}

	/**
	 * @return bool Whether the remote host is the local host
	 */
	public function isLocalClient()
	{
		$ipAddress = $this->getRemoteIp();
		return $ipAddress == '127.0.0.1' || $ipAddress == '::1';
	}

	/**
	 * @param string $name Case insensitive name of request HTTP header
	 * @return string|null
	 */
	public function getHeader( $name )
	{
		$serverFieldName = \strtoupper(\strtr($name, '-', '_'));
		if( isset($_SERVER['HTTP_' . $serverFieldName]) )
		{
			return $_SERVER['HTTP_' . $serverFieldName];
		}
		if( isset($_SERVER[$serverFieldName]) )
		{
			return $_SERVER[$serverFieldName];
		}
		return null;
	}

	/**
	 * @return string|null
	 */
	public function getReferer()
	{
		return $this->getHeader('Referer');
	}

	/**
	 * @return string|null User agent signature sent by client
	 */
	public function getUserAgent()
	{
		return $this->getHeader('User-Agent');
	}

	/**
	 * HTTP accept headers sent by client
	 * @return array {'content': <string>, 'language': <string>, 'encoding': <string>, 'charset': <string>}
	 */
	public function getAcceptHeaders()
	{
		return [
			'content' => $this->getHeader('Accept'),
			'language' => $this->getHeader('Accept-Language'),
			'encoding' => $this->getHeader('Accept-Encoding'),
			'charset' => $this->getHeader('Accept-Charset')
		];
	}

	/**
	 * @return string|null The user's name if HTTP basic authentication is used
	 */
	public function getHttpAuthUserName()
	{
		return isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : null;
	}

	/**
	 * @return string|null
	 */
	public function getRawRequestBody()
	{
		$result = @\file_get_contents('php://input');
		return $result !== false ? $result : null;
	}

	/**
	 * @return array
	 */
	public function getPostData()
	{
		return $_POST;
	}

	/**
	 * @param string $key
	 * @param mixed $default Value to return if requested param does not exist
	 * @return string|array|null
	 */
	public function getPostField( $key, $default = null )
	{
		return isset($_POST[$key]) ? $_POST[$key] : $default;
	}

	/**
	 * @return array
	 */
	public function getQueryParams()
	{
		return $_GET;
	}

	/**
	 * @param string $key
	 * @param mixed $default Value to return if requested param does not exist
	 * @return string|array|null
	 */
	public function getQueryParam( $key, $default = null )
	{
		return isset($_GET[$key]) ? $_GET[$key] : $default;
	}

	/**
	 * "eat away" a field which has been handled
	 *
	 * When building an URL re-appending all current GET-fields, unsetting a param will prevent it from
	 * being carried forward.
	 *
	 * @param string $key
	 */
	public function unsetQueryParam( $key )
	{
		unset($_GET[$key]);
	}

	/**
	 * @param string $key
	 * @param mixed $default Value to return when the requested cookie does not exist
	 * @return string|array|null
	 */
	public function getCookie( $key, $default = null )
	{
		return isset($_COOKIE[$key]) ? $_COOKIE[$key] : $default;
	}

	/**
	 * @param string $key
	 * @return FileUpload
	 */
	public function getFileUpload( $key )
	{
		return new FileUpload($_FILES[$key]);
	}

	/**
	 * @return string Protocol and host name the current request was made to, e.g. 'http:/localhost/'
	 */
	public function getBaseUrl()
	{
		$isHttps = $this->isHttps();
		$serverPort = $this->getServerPort();
		return ($isHttps ? 'https' : 'http') . '://'
			. $this->getServerHostName()
			. (!$isHttps && $serverPort != 80 || $isHttps && $serverPort != 443 ? ':' . $serverPort : '')
			. '/';
	}

	/**
	 * @return bool
	 */
	public function isHttps()
	{
			// Apache & IIS (on/off):
		return isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] && \strtolower($_SERVER['HTTPS']) != 'off'
			// nginx:
			|| isset($_SERVER['HTTP_SCHEME']) && \strtolower($_SERVER['HTTP_SCHEME']) == 'https';
	}

	/**
	 * @return string
	 */
	public function getServerHostName()
	{
		return $_SERVER['SERVER_NAME'];
	}

	/**
	 * @return int
	 */
	public function getServerPort()
	{
		return \intval($_SERVER['SERVER_PORT']) ?: ($this->isHttps() ? 443 : 80);
	}
}
