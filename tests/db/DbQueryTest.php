<?php
namespace XAF\db;

use PHPUnit\Framework\TestCase;
use Phake;

class TestQueryImplementation extends DbQuery
{
	protected function init()
	{
		$this->sqlQuery->setTableName('dummy_table');
		$this->sqlQuery->addFieldTerm('dummy_column');
	}

	public function queryAll()
	{
		return $this->queryAllRows();
	}

	public function querySingle()
	{
		return $this->querySingleRow();
	}
}

/**
 * @covers \XAF\db\DbQuery
 */
class DbQueryTest extends TestCase
{
	/** @var Dbh */
	private $dbhMock;

	/** @var TestQueryImplementation */
	private $object;

	protected function setUp(): void
	{
		$this->dbhMock = Phake::mock(Dbh::class);
		$this->object = new TestQueryImplementation($this->dbhMock);
	}

	public function testTableQueryCallsDbhWithAssembledSqlStatementAndParameters()
	{
		Phake::when($this->dbhMock)->queryTable(Phake::anyParameters())->thenReturn([]);

		$this->object->queryAll();

		Phake::verify($this->dbhMock)->queryTable('SELECT dummy_column FROM dummy_table', []);
	}

	public function testTableQueryReturnsAllResultRowsFromSqlQuery()
	{
		$dummySqlQueryResult = [['id' => 1, 'name' => 'foo'], ['id' => 2, 'name' => 'bar']];
		Phake::when($this->dbhMock)->queryTable(Phake::anyParameters())->thenReturn($dummySqlQueryResult);

		$result = $this->object->queryAll($this->dbhMock);

		$this->assertEquals($dummySqlQueryResult, $result);
	}

	public function testQueryAllReturnsAllResultRowsFromSqlQuery()
	{
		$dummySqlQueryResult = [['id' => 1, 'name' => 'foo'], ['id' => 2, 'name' => 'bar']];
		Phake::when($this->dbhMock)->queryTable(Phake::anyParameters())->thenReturn($dummySqlQueryResult);

		$result = $this->object->queryAll($this->dbhMock);

		$this->assertEquals($dummySqlQueryResult, $result);
	}

	public function testSingleRowQueryCallsDbhWithAssembledSqlStatementAndParameters()
	{
		Phake::when($this->dbhMock)->queryRow(Phake::anyParameters())->thenReturn(['id' => 1, 'name' => 'foo']);

		$this->object->querySingle();

		Phake::verify($this->dbhMock)->queryRow('SELECT dummy_column FROM dummy_table', []);
	}

	public function testSingleRowQueryReturnsResultRowFromSqlQuery()
	{
		$dummySqlQueryResult = ['id' => 1, 'name' => 'foo'];
		Phake::when($this->dbhMock)->queryRow(Phake::anyParameters())->thenReturn($dummySqlQueryResult);

		$result = $this->object->querySingle($this->dbhMock);

		$this->assertEquals($dummySqlQueryResult, $result);
	}

	public function testSingleRowQueryWithNoResultThrowsException()
	{
		Phake::when($this->dbhMock)->queryRow(Phake::anyParameters())->thenReturn([]);

		$this->expectException(\XAF\exception\NotFoundError::class);
		$this->object->querySingle($this->dbhMock);
	}
}
