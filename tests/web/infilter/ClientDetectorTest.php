<?php
namespace XAF\web\infilter;

use PHPUnit\Framework\TestCase;
use Phake;

use XAF\http\Request;
use XAF\type\ParamHolder;
use XAF\type\ArrayParamHolder;

/**
 * @vovers XAF\web\infilter\ClientDetector
 */
class ClientDetectorTest extends TestCase
{
	/** @var ClientDetector */
	private $object;

	/** @var Request */
	private $requestMock;

	/** @var ParamHolder */
	private $requestVars;

	protected function setUp(): void
	{
		$this->requestMock = Phake::mock(Request::class);
		$this->requestVars = new ArrayParamHolder();
		$this->object = new ClientDetector($this->requestMock, $this->requestVars);
	}

	static function getUserAgentTestTuples()
	{
		return [
			// Real-life Android user agent string
			[
				'Mozilla/5.0 (Linux; U; Android 2.3.3; en-us; HTC_Pyramid Build/GRI40) AppleWebKit/533.1 (KHTML, like Gecko) Version/4.0 Mobile Safari/533.1',
				['isAndroidClient' => true]
			],

			// Real-life iOS user agent strings
			[
				'Mozilla/5.0 (iPhone; U; CPU like Mac OS X; en) AppleWebKit/420+ (KHTML, like Gecko) Version/3.0 Mobile/1A543a Safari/419.3',
				['isIosClient' => true, 'iosDeviceType' => 'iPhone']
			],
			[
				'Mozilla/5.0 (iPod; U; CPU like Mac OS X; en) AppleWebKit/420.1 (KHTML, like Gecko) Version/3.0 Mobile/3A101a Safari/419.3',
				['isIosClient' => true, 'iosDeviceType' => 'iPod']
			],
			[
				'Mozilla/5.0 (iPad; U; CPU OS 3_2 like Mac OS X; en-us) AppleWebKit/531.21.10 (KHTML, like Gecko) version/4.0.4 Mobile/7B367 Safari/531.21.10',
				['isIosClient' => true, 'iosDeviceType' => 'iPad']
			],
			// Key words are *NOT* detected when part of other words
			[
				'AldiPhone AppleWebKit',
				['isIosClient' => false, 'iosDeviceType' => null]
			],
			// Requires AppleWebKit to detect iOS Client
			[
				'iPad PineappleWebKit',
				['isIosClient' => false, 'iosDeviceType' => null]
			],
			// Requires iPad, iPhone or iPod to detect iOS Client
			[
				'iPoo AppleWebKit',
				['isIosClient' => false, 'iosDeviceType' => null]
			],

			// Desktop Browser
			[
				'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.9; rv:32.0) Gecko/20100101 Firefox/32.0">Mozilla/5.0 (Macintosh; Intel Mac OS X 10.9; rv:32.0) Gecko/20100101 Firefox/32.0',
				['isAndroidClient' => false, 'isIosClient' => false, 'iosDeviceType' => null]
			],
		];
	}

	/**
	 * @dataProvider getUserAgentTestTuples
	 */
	public function testRequestVarsAreSetFromUserAgent( $agentString, array $expectedResult )
	{
		Phake::when($this->requestMock)->getUserAgent(Phake::anyParameters())->thenReturn($agentString);

		$this->object->execute();

		foreach( $expectedResult as $key => $expectedValue )
		{
			$this->assertSame($expectedValue, $this->requestVars->get($key));
		}
	}

	public function testRequestVarNamesCanBeChanged()
	{
		Phake::when($this->requestMock)->getUserAgent(Phake::anyParameters())->thenReturn('iPhone AppleWebKit');
		$this->object->setParam('isAndroidTargetVar', 'quux');
		$this->object->setParam('isIosTargetVar', 'foo');
		$this->object->setParam('iosDeviceTypeVar', 'bar');

		$this->object->execute();

		$this->assertFalse($this->requestVars->get('quux'));
		$this->assertTrue($this->requestVars->get('foo'));
		$this->assertEquals('iPhone', $this->requestVars->get('bar'));
	}
}