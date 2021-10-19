<?php
namespace XAF\type;

/**
 * public data container for information on a single page of a paginated result
 * (i.e. a "page" of data from a larger list, usually returned from a data source query)
 */
class PageInfo
{
	/** @var int 1-based */
	public $pageNumber = 1;

	/** @var int|null 0 or null for unlimited, else maximum items per page (actual number of items on page may be less) */
	public $itemsPerPage;

	/** @var int|null Number of first item on page (1-based) */
	public $firstItemNumber;

	/** @var int|null Number of last item on page (1-based) */
	public $lastItemNumber;

	/** @var int Number of items on page */
	public $itemsOnCurrentPage = 0;

	/** @var int Total number of pages */
	public $pageCount = 0;

	/** @var int Total number of items across all pages */
	public $totalItemCount = 0;

	/**
	 * @param int $pageNumber 1-based
	 * @param int|null $itemsPerPage 0 or null for unlimited
	 */
	public function __construct( $pageNumber = 1, $itemsPerPage = null )
	{
		$this->pageNumber = $pageNumber < 1 ? 1 : $pageNumber;
		$this->itemsPerPage = $itemsPerPage;
	}

	/**
	 * Calculate all other values according to the total number of items
	 *
	 * @param int $totalItemCount
	 */
	public function setTotalItemCount( $totalItemCount )
	{
		$this->totalItemCount = \max(0, $totalItemCount);

		if( $this->totalItemCount < 1 )
		{
			$this->firstItemNumber = 0;
			$this->lastItemNumber = 0;
			$this->itemsOnCurrentPage = 0;
			$this->pageCount = 0;
		}
		else if( !$this->itemsPerPage )
		{
			$this->firstItemNumber = 1;
			$this->lastItemNumber = $this->totalItemCount;
			$this->itemsOnCurrentPage = $this->totalItemCount;
			$this->pageCount = 1;
		}
		else
		{
			$firstItemIndex = ($this->pageNumber - 1) * $this->itemsPerPage;
			if( $firstItemIndex < $totalItemCount )
			{
				$this->firstItemNumber = $firstItemIndex + 1;
				$this->lastItemNumber = \min($firstItemIndex + $this->itemsPerPage, $this->totalItemCount);
				$this->itemsOnCurrentPage = $this->lastItemNumber - $firstItemIndex;
			}
			$this->pageCount = \ceil($this->totalItemCount / $this->itemsPerPage);
		}
	}
}
