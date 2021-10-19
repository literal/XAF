<?php
namespace XAF\web\outfilter;

use PHPUnit\Framework\TestCase;
use XAF\web\Response;

class ResponseFieldPassThroughFilterTest extends TestCase
{
	/** @var ResponseFieldPassThroughFilter */
	private $object;

	protected function setUp(): void
	{
		$this->object = new ResponseFieldPassThroughFilter();
	}

	public function testDefaultResponseFieldNameIsResponse()
	{
		$response = new Response();
		$response->data = ['response' => 'expected response', 'unrelatedField' => 'ignore this'];

		$this->object->execute($response);

		$this->assertEquals('expected response', $response->result);
	}

	public function testResponseFieldNameCanBeSpecified()
	{
		$response = new Response();
		$response->data = ['customFieldKey' => 'expected response'];

		$this->object->setParam('field', 'customFieldKey');
		$this->object->execute($response);

		$this->assertSame('expected response', $response->result);
	}

	public function testEmptyStringIsReturnedWhenResponseFieldDoesNotExist()
	{
		$response = new Response();
		$response->data = ['unrelatedField' => 'ignore this'];

		$this->object->execute($response);

		$this->assertSame('', $response->result);
	}
}

