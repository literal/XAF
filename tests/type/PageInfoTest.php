<?php
namespace XAF\type;

use PHPUnit\Framework\TestCase;

/**
 * @covers \XAF\type\PageInfo
 */
class PageInfoTest extends TestCase
{
	public function testUnlimitedItemsPerPageYieldOnePage()
	{
		$pageNumber = 1;
		$itemsPerPage = 0;
		$pageInfo = new PageInfo($pageNumber, $itemsPerPage);

		$pageInfo->setTotalItemCount(123);

		$this->assertEquals(1, $pageInfo->pageCount);
		$this->assertEquals(123, $pageInfo->totalItemCount);
		$this->assertEquals(0, $pageInfo->itemsPerPage);
		$this->assertEquals(1, $pageInfo->firstItemNumber);
		$this->assertEquals(123, $pageInfo->lastItemNumber);
		$this->assertEquals(123, $pageInfo->itemsOnCurrentPage);
	}

	public function testNoItemsYieldNoPage()
	{
		$pageInfo = new PageInfo();

		$pageInfo->setTotalItemCount(0);

		$this->assertEquals(0, $pageInfo->pageCount);
	}

	public function testFirstPageOfMany()
	{
		$pageNumber = 1;
		$itemsPerPage = 10;
		$pageInfo = new PageInfo($pageNumber, $itemsPerPage);

		$pageInfo->setTotalItemCount(25);

		$this->assertEquals(1, $pageInfo->pageNumber);
		$this->assertEquals(3, $pageInfo->pageCount);
		$this->assertEquals(1, $pageInfo->firstItemNumber);
		$this->assertEquals(10, $pageInfo->lastItemNumber);
		$this->assertEquals(10, $pageInfo->itemsOnCurrentPage);
	}

	public function testMiddlePageOfMany()
	{
		$pageNumber = 2;
		$itemsPerPage = 10;
		$pageInfo = new PageInfo($pageNumber, $itemsPerPage);

		$pageInfo->setTotalItemCount(25);

		$this->assertEquals(2, $pageInfo->pageNumber);
		$this->assertEquals(3, $pageInfo->pageCount);
		$this->assertEquals(11, $pageInfo->firstItemNumber);
		$this->assertEquals(20, $pageInfo->lastItemNumber);
		$this->assertEquals(10, $pageInfo->itemsOnCurrentPage);
	}

	public function testLastPageOfMany()
	{
		$pageNumber = 3;
		$itemsPerPage = 10;
		$pageInfo = new PageInfo($pageNumber, $itemsPerPage);

		$pageInfo->setTotalItemCount(25);

		$this->assertEquals(3, $pageInfo->pageNumber);
		$this->assertEquals(3, $pageInfo->pageCount);
		$this->assertEquals(21, $pageInfo->firstItemNumber);
		$this->assertEquals(25, $pageInfo->lastItemNumber);
		$this->assertEquals(5, $pageInfo->itemsOnCurrentPage);
	}

	public function testLastPageOfManyWithItemCountDividableByPageSize()
	{
		$pageNumber = 3;
		$itemsPerPage = 10;
		$pageInfo = new PageInfo($pageNumber, $itemsPerPage);

		$pageInfo->setTotalItemCount(30);

		$this->assertEquals(3, $pageInfo->pageNumber);
		$this->assertEquals(3, $pageInfo->pageCount);
		$this->assertEquals(21, $pageInfo->firstItemNumber);
		$this->assertEquals(30, $pageInfo->lastItemNumber);
		$this->assertEquals(10, $pageInfo->itemsOnCurrentPage);
	}

	public function testPageBeyondLastPageIsEmpty()
	{
		$pageNumber = 10;
		$itemsPerPage = 2;
		$pageInfo = new PageInfo($pageNumber, $itemsPerPage);

		$pageInfo->setTotalItemCount(5);

		$this->assertEquals(10, $pageInfo->pageNumber);
		$this->assertEquals(3, $pageInfo->pageCount);
		$this->assertNull($pageInfo->firstItemNumber);
		$this->assertNull($pageInfo->lastItemNumber);
		$this->assertEquals(0, $pageInfo->itemsOnCurrentPage);
	}

	public function testNegativeItemCountIsTreatedLikeZero()
	{
		$pageInfo = new PageInfo();

		$pageInfo->setTotalItemCount(-1);

		$this->assertEquals(0, $pageInfo->pageCount);
		$this->assertEquals(0, $pageInfo->totalItemCount);
		$this->assertEquals(0, $pageInfo->firstItemNumber);
		$this->assertEquals(0, $pageInfo->lastItemNumber);
		$this->assertEquals(0, $pageInfo->itemsOnCurrentPage);
	}
}
