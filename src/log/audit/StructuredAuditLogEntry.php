<?php
namespace XAF\log\audit;

class StructuredAuditLogEntry extends AuditLogEntry
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
		return 'json';
	}

	/**
	 * @return string
	 */
	public function getEncodedData()
	{
		return \json_encode($this->data, \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE);
	}
}
