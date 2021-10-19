<?php
namespace XAF\log\audit;

use XAF\db\Dbh;
use PDO;

class SqlAuditLogger extends AuditLogger
{
	/** @var Dbh */
	private $dbh;

	/**
	 * @param Dbh $dbh
	 * @param string $appKey
	 */
	public function __construct( Dbh $dbh, $appKey )
	{
		$this->dbh = $dbh;
		parent::__construct($appKey);
	}

	public function addEntry( AuditLogEntry $entry )
	{
		$stmt = $this->dbh->prepare(
			'INSERT INTO audit_log('
				. ' t,'
				. ' app_key,'
				. ' operation_class,'
				. ' operation_type,'
				. ' subject_type,'
				. ' subject_id,'
				. ' subject_label,'
				. ' request,'
				. ' remote_ip,'
				. ' user_agent,'
				. ' user_name,'
				. ' data_format,'
				. ' data_encoding,'
				. ' data'
			. ') VALUES(now(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
		);
		$stmt->bindValue(1, $this->appKey);
		$stmt->bindValue(2, $entry->operationClass);
		$stmt->bindValue(3, $entry->operationType);
		$stmt->bindValue(4, $entry->subjectType);
		$stmt->bindValue(5, $entry->subjectId);
		$stmt->bindValue(6, $entry->subjectLabel);
		$stmt->bindValue(7, $this->request);
		$stmt->bindValue(8, $this->remoteAddress);
		$stmt->bindValue(9, $this->userAgent);
		$stmt->bindValue(10, $this->user);
		$stmt->bindValue(11, $entry->dataFormat);
		$stmt->bindValue(12, $entry->getEncoding());
		$stmt->bindValue(13, $entry->getEncodedData(), PDO::PARAM_LOB);
		$stmt->execute();
	}
}
