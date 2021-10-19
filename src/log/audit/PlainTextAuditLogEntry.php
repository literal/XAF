<?php
namespace XAF\log\audit;

class PlainTextAuditLogEntry extends AuditLogEntry
{
	private $data;

	public function setData( $data )
	{
		$this->data = $data;
	}

	/**
	 * @return string
	 */
	public function getEncoding()
	{
		return 'plain';
	}

	/**
	 * @return string
	 */
	public function getEncodedData()
	{
		return $this->data;
	}
}
