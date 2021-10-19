<?php
namespace XAF\log\audit;

abstract class AuditLogEntry
{
	public $operationClass;
	public $operationType;
	public $subjectType;
	public $subjectId;
	public $subjectLabel;
	public $dataFormat;

	/**
	 * @return string
	 */
	abstract public function getEncoding();

	/**
	 * @return string
	 */
	abstract public function getEncodedData();
}
