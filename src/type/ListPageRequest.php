<?php
namespace XAF\type;

/**
 * Generic data object for passing to a method that implements filterable, sortable, paginated data access
 * (e.g. by querying a database for a list of records)
 */
class ListPageRequest
{
	/** @var int 1-based number of the list page to return */
	public $pageNumber;

	/** @var int Maximum items per page - 0 for unlimited */
	public $pageSize;

	/**
	 * @var array Hash of (all optional) filter definitions for the list to be returned.
	 *     Allowed element keys depend on the particular method the ListPageRequest is passed to.
	 *     Example: {searchPhrase: <string>, countryCodes: <array>}
	 */
	public $filters;

	/**
	 * @var null|string|array Optional sort key or array of sort keys.
	 *     Allowed values depend on the particular method the ListPageRequest is passed to.
	 *     Example: 'id' or ['name', 'price']
	 */
	public $orderBy;

	/**
	 * @var boolean Whether to sort the result in the opposite direction of what is the default sort direction
	 *     for the selected sort criteria/on
	 */
	public $reverseOrder;

	/**
	 * @var array List of tokens for activating retrieval of additional item data not returned by default.
	 */
	public $includeDetails;

	/**
	 * @param int $pageNumber
	 * @param int $pageSize
	 * @param array $filters
	 * @param null|string|array $orderBy
	 * @param bool $reverseOrder
	 * @param array $includeDetails
	 */
	function __construct(
		$pageNumber = 1,
		$pageSize = 0,
		$filters = [],
		$orderBy = null,
		$reverseOrder = false,
		array $includeDetails = []
	)
	{
		$this->pageNumber = $pageNumber;
		$this->pageSize = $pageSize;
		$this->filters = $filters;
		$this->orderBy = $orderBy;
		$this->reverseOrder = $reverseOrder;
		$this->includeDetails = $includeDetails;
	}
}
