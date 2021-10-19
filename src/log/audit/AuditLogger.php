<?php
namespace XAF\log\audit;

abstract class AuditLogger
{
	/** @var string|null */
	protected $appKey;

	/** @var string|null */
	protected $request;

	/** @var string|null */
	protected $remoteAddress;

	/** @var string|null */
	protected $userAgent;

	/** @var string|null */
	protected $user;

	/**
	 * @param string $appKey
	 */
	public function __construct( $appKey )
	{
		$this->appKey = $appKey;
	}

	public function setRequest( $request )
	{
		$this->request = $request;
	}

	public function setRemoteAddress( $remoteAddress )
	{
		$this->remoteAddress = $remoteAddress;
	}

	public function setUserAgent( $userAgent )
	{
		$this->userAgent = $userAgent;
	}

	public function setUser( $user )
	{
		$this->user = $user;
	}

	abstract public function addEntry( AuditLogEntry $entry );
}
