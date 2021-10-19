<?php
namespace XAF\db;

use PHPUnit\Framework\TestCase;

/**
 * @covers \XAF\db\SqlQuery
 */
class SqlQueryTest extends TestCase
{
	/** @var SqlQuery */
	protected $object;

	protected function setUp(): void
	{
		$this->object = new SqlQuery();
	}

	public function testGetFieldsExpression()
	{
		$this->object->addFieldTerm('foo');
		$this->object->addFieldTerm('bar');

		$result = $this->object->getFieldsExpression();

		$this->assertEquals('foo, bar', $result);
	}

	public function testClearFields()
	{
		$this->object->addFieldTerm('foo');
		$this->object->clearFields();

		$result = $this->object->getFieldsExpression();

		$this->assertEquals('*', $result);
	}

	public function testGetConditionExpression()
	{
		$this->object->addWhereTerm('foo = 1');
		$this->object->addWhereTerm('bar = 2');

		$result = $this->object->getWhereExpression();

		$this->assertEquals(' WHERE foo = 1 AND bar = 2', $result);
	}

	public function testGetJoinExpression()
	{
		$this->object->addJoinTerm('foo ON foo.id = bar.foo_id');
		$this->object->addJoinTerm('batz ON batz.id = foo.batz_id', 'LEFT');

		$result = $this->object->getJoinExpression();

		$this->assertEquals(' INNER JOIN foo ON foo.id = bar.foo_id LEFT JOIN batz ON batz.id = foo.batz_id', $result);
	}

	public function testGetOrderByExpression()
	{
		$this->object->addOrderByTerm('foo ASC');
		$this->object->addOrderByTerm('bar DESC');

		$result = $this->object->getOrderByExpression();

		$this->assertEquals(' ORDER BY foo ASC, bar DESC', $result);
	}

	public function testClearOrderBy()
	{
		$this->object->addOrderByTerm('foo ASC');
		$this->object->clearOrderBy();

		$result = $this->object->getOrderByExpression();

		$this->assertEquals('', $result);
	}

	public function testLimit()
	{
		$this->object->setLimit(10, 20);

		$result = $this->object->getLimitExpression();

		$this->assertEquals(' LIMIT 10 OFFSET 20', $result);
	}

	public function testGetGroupByExpression()
	{
		$this->object->addGroupByTerm('foo');
		$this->object->addGroupByTerm('bar');

		$result = $this->object->getGroupByExpression();

		$this->assertEquals(' GROUP BY foo, bar', $result);
	}

	public function testGetParams()
	{
		$expected = [
			'bar' => 'foo'
		];
		$this->object->addWhereTerm('foo = :bar', ['bar' => 'foo']);

		$params = $this->object->getParams();

		$this->assertEquals($expected, $params);
	}

	public function testGetDefaultSelect()
	{
		$this->object->setTableName('my_table');

		$result = $this->object->getSqlStatement();

		$this->assertEquals('SELECT * FROM my_table', $result);
	}

	public function testGetSelect()
	{
		$this->object->setTableName('my_table');

		$this->object->addFieldTerm('foo');
		$this->object->addJoinTerm('boom');
		$this->object->addWhereTerm('foo = \'foo\'');
		$this->object->addFieldTerm('bar');
		$this->object->addWhereTerm('bar = \'foo\'');
		$this->object->addOrderByTerm('foo DESC');
		$this->object->addGroupByTerm('foo');

		$result = $this->object->getSqlStatement();
		$this->assertEquals(
			'SELECT foo, bar' .
			' FROM my_table INNER JOIN boom' .
			' WHERE foo = \'foo\' AND bar = \'foo\'' .
			' GROUP BY foo' .
			' ORDER BY foo DESC',
			$result
		);
	}

}
