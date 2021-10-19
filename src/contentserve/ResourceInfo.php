<?php
namespace XAF\contentserve;

class ResourceInfo
{
	/** @var bool */
	public $exists = false;

	/** @var string|null normalised version of the unique resource ID, if the resource exists */
	public $id;

	/** @var int|null Unix timestamp of last modification, if the resource exists */
	public $lastModifiedTimestamp;

	/** @var string|null Mime Type of the resource, if the resource exists, may contain appended '; charset=...' */
	public $mimeType;
}
