<?php
namespace XAF\web\routing;

use PHPUnit\Framework\TestCase;
use Phake;

use XAF\http\Request;
use XAF\validate\ValidationService;

use XAF\validate\ValidationResult;
use XAF\web\exception\PageNotFound;

/**
 * @covers \XAF\web\routing\RequestRouter
 */
class RequestRouterTest extends TestCase
{
	/** @var RequestRouter */
	protected $router;

	/** @var Request */
	protected $requestMock;

	/** @var ValidationService */
	protected $validationServiceMock;

	protected function init( $routingTable )
	{
		$this->requestMock = Phake::mock(Request::class);
		$this->validationServiceMock = Phake::mock(ValidationService::class);
		$requestVarResolver = new RequestVarResolver($this->requestMock, $this->validationServiceMock);

		$this->router = new RequestRouter(
				new PathPatternMatcher('#'),
				new ControlRouteBuilder($requestVarResolver)
		);

		$this->router->setRoutingTable($routingTable);
	}

	// ************************************************************************
	// Basic routing
	// ************************************************************************

	public function testNoMatchingRouteThrowsException()
	{
		$this->init([
			'routes' => []
		]);

		$this->expectException(\XAF\web\exception\PageNotFound::class);
		$this->router->resolveRoute('GET', '/unknown');
	}

	/**
	 * A partial routing result collected before an error occurs is often useful - e.g. certain request
	 * vars like the ones for language or content-type may be of interest for the error handling
	 */
	public function testPartialRoutingResultIsAvailableAfterRoutingError()
	{
		$this->init([
			'vars' => ['foo' => 'bar'],
			'routes' => []
		]);

		try
		{
			$this->router->resolveRoute('GET', '/unknown');
		}
		catch( PageNotFound $notFound )
		{}

		$result = $this->router->getResult();

		$this->assertFalse($result->resolved);
		$this->assertEquals('bar', $result->vars['foo']);
	}

	public function testMatchingRouteIsReportedResolved()
	{
		$this->init([
			'routes' => [
				'/inpath' => []
			]
		]);

		$result = $this->router->resolveRoute('GET', '/inpath');

		$this->assertTrue($result->resolved);
	}

	public function testMatchingRouteActionIsCollected()
	{
		$this->init([
			'routes' => [
				'/inpath' => [
					'actions' => 'outaction'
				]
			]
		]);

		$result = $this->router->resolveRoute('GET', '/inpath');

		$this->assertEquals(['outaction'], $result->actions);
	}

	public function testRequestPathWithTrailingSlashDoesNotMatchRouteWithoutTrailingSlash()
	{
		$this->init([
			'routes' => [
				'/inpath' => [
					'actions' => 'outaction'
				]
			]
		]);

		$this->expectException(\XAF\web\exception\PageNotFound::class);
		$this->router->resolveRoute('GET', '/inpath/');
	}

	public function testStringRouteEntryIsTreatedAsAction()
	{
		$this->init([
			'routes' => [
				'/inpath' => 'outaction'
			]
		]);

		$result = $this->router->resolveRoute('GET', '/inpath');

		$this->assertTrue($result->resolved);
		$this->assertEquals(['outaction'], $result->actions);
	}

	public function testGroupsFromRouteFragmentPatternsAreCaptured()
	{
		$this->init([
			'routes' => [
				'/x(.+)x' => [
					'actions' => 'result_$1' // captured group (.+) from pattern shall be inserted here
				]
			]
		]);

		$result = $this->router->resolveRoute('GET', '/xFOOx');

		$this->assertTrue($result->resolved);
		$this->assertEquals(['result_FOO'], $result->actions);
	}

	public function testFirstMatchingRouteFragmentIsFollowed()
	{
		$this->init([
			'routes' => [
				'/inpath(\\d+)' => [
					'actions' => 'outaction',
				],
				'/inpath2' => [
					'actions' => 'invalid',
				]
			]
		]);

		$result = $this->router->resolveRoute('GET', '/inpath2');

		$this->assertTrue($result->resolved);
		$this->assertEquals(['outaction'], $result->actions);
	}

	public function testContinueFlagFallsThroughToNextMatchingPattern()
	{
		$this->init([
			'routes' => [
				'/inpath(\\d+)' => [
					// routing should *not* stop here because of the 'continue' flag
					'continue' => true,
					'actions' => 'action1',
				],
				'/inpath2' => [
					'actions' => 'action2',
				]
			]
		]);

		$result = $this->router->resolveRoute('GET', '/inpath2');

		$this->assertEquals(['action1', 'action2'], $result->actions);
	}

	public function testRouteWithContinueFlagDoesNotSatisfyRouting()
	{
		$this->init([
			'routes' => [
				'/inpath' => [
					'continue' => true
				]
			]
		]);

		$this->expectException(\XAF\web\exception\PageNotFound::class);
		$this->router->resolveRoute('GET', '/inpath');
	}

	public function testFirstMatchingRouteFragmentIsFollowedEvenIfItIsADeadEnd()
	{
		$this->init([
			'routes' => [
					// router should get stuck here:
				'/path' => [],
					// ...and not evaluate this route:
				'/path/sub' => [],
			]
		]);

		$this->expectException(\XAF\web\exception\PageNotFound::class);
		$this->router->resolveRoute('GET', '/path/sub');
	}

	/**
	 * By convention, paths for internal redirects begin with '@' like in nginx vhost configuration
	 * These paths are not reachable from the outside because an HTTP request path always begins with '/'
	 */
	public function testPathDoesNotNeedToBeginWithSlash()
	{
		$this->init([
			'routes' => [
				'@internal_path' => []
			]
		]);

		$result = $this->router->resolveRoute('GET', '@internal_path');

		$this->assertTrue($result->resolved);
	}

	// ************************************************************************
	// Nested routing (with sub-routes)
	// ************************************************************************

	public function testSubRouteIsBeingFollowed()
	{
		$this->init([
			'routes' => [
				'/main' => [
					'actions' => 'action1',
					'routes' => [
						'/sub' => [
							'actions' => 'action2'
						]
					]
				]
			]
		]);

		$result = $this->router->resolveRoute('GET', '/main/sub');

		$this->assertEquals(['action1', 'action2'], $result->actions);
	}

	public function testUnknownSubRouteIsNotResolved()
	{
		$this->init([
			'routes' => [
				'/main' => [
					'routes' => [
						'/sub' => [
						]
					]
				]
			]
		]);

		$this->expectException(\XAF\web\exception\PageNotFound::class);
		$this->router->resolveRoute('GET', '/main/unknown');
	}



	public function testEmptyStringSubRouteMatchesIfRequestPathIsAlreadyFullyMatched()
	{
		$this->init([
			'routes' => [
				'/main' => [
					'routes' => [
						'' => [
							'actions' => 'index'
						]
					]
				]
			]
		]);

		$result = $this->router->resolveRoute('GET', '/main');

		$this->assertEquals(['index'], $result->actions);
	}

	public function testEmptyStringSubRouteDoesNotMatchTrailingSlash()
	{
		$this->init([
			'routes' => [
				'/main' => [
					'actions' => 'main_result',
					'routes' => [
						'' => []
					]
				]
			]
		]);

		$this->expectException(\XAF\web\exception\PageNotFound::class);
		$this->router->resolveRoute('GET', '/main/'); // With trailing slash!
	}

	public function testEmptyStringSubRouteDoesOnlyMatchAtTheEndOfTheRequestPath()
	{
		$this->init([
			'routes' => [
				'/main' => [
					'routes' => [
						// While '' would technically match, the router shall *not* get stuck here
						'' => [
							'routes' => [
								'/sub' => [
									'actions' => 'invalid'
								],
							]
						],
						'/sub' => [
							'actions' => 'expected'
						],
					]
				]
			]
		]);

		$result = $this->router->resolveRoute('GET', '/main/sub');

		$this->assertEquals(['expected'], $result->actions);
	}

	public function testSlashSubRouteMatchesTrailingSlash()
	{
		$this->init([
			'routes' => [
				'/main' => [
					'routes' => [
						'/' => [
							'actions' => 'expected'
						]
					]
				]
			]
		]);

		$result = $this->router->resolveRoute('GET', '/main/');

		$this->assertEquals(['expected'], $result->actions);
	}

	public function testSlashSubRouteDoesNotMatchEndOfRequestPath()
	{
		$this->init([
			'routes' => [
				'/main' => [
					'routes' => [
						'/' => []
					]
				]
			]
		]);

		$this->expectException(\XAF\web\exception\PageNotFound::class);
		$this->router->resolveRoute('GET', '/main');
	}

	public function testRouteDoesNotMatchIfPathGoesBeyond()
	{
		$this->init([
			'routes' => [
				'/main' => []
			]
		]);

		$this->expectException(\XAF\web\exception\PageNotFound::class);
		$this->router->resolveRoute('GET', '/main/sub'); // route should not match because of extra '/sub'
	}

	// ************************************************************************
	// Actions
	// ************************************************************************

	public function testAccumulationOfActions()
	{
		$this->init([
			'actions' => 'default_action',
			'routes' => [
				'/inpath' => [
					'actions' => 'specific_action'
				]
			]
		]);

		$result = $this->router->resolveRoute('GET', '/inpath');

		$this->assertEquals(['default_action', 'specific_action'], $result->actions);
	}

	public function testMultipleActions()
	{
		$this->init([
			'routes' => [
				'/inpath' => [
					'actions' => [
						'action1',
						'action2'
					]
				]
			]
		]);

		$result = $this->router->resolveRoute('GET', '/inpath');

		$this->assertEquals(['action1', 'action2'], $result->actions);
	}

	public function testRequestMethodSpecificActions()
	{
		$this->init(
			[
				'routes' => [
					'/inpath' => [
						'methods' => [
							'POST' => ['actions' => 'post_action'],
							'GET' => ['actions' => 'get_action'],
							'*' => ['actions' => 'unspecific_action']
						]
					]
				]
			]
		);

		$result = $this->router->resolveRoute('POST', '/inpath');

		$this->assertEquals(['post_action', 'unspecific_action'], $result->actions);
	}

	// ************************************************************************
	// Base path handling
	// ************************************************************************

	public function testDefaultBasePathIsEmpty()
	{
		$this->init([
			'routes' => [
				'/inpath' => []
			]
		]);

		$result = $this->router->resolveRoute('GET', '/inpath');

		$this->assertEquals('', $result->basePath);
	}

	public function testBasePathIsCapturesForNestedRoutes()
	{
		$this->init([
			'routes' => [
				'/base' => [
					'routes' => [
						'/sub' => [
							'basepath' => true, // triggers capturing of 'basepath/sub' as base path
							'routes' => [
								'/leaf' => []
							]
						]
					]
				]
			]
		]);

		$result = $this->router->resolveRoute('GET', '/base/sub/leaf');

		$this->assertEquals('/base/sub', $result->basePath);
	}

	// ************************************************************************
	// Request vars
	// ************************************************************************

	public function testVarCapturing()
	{
		$this->init([
			'routes' => [
				'/main/(\\d+)' => [
					'vars' => ['number' => '$1', 'fixed' => 'fixed'],
					'routes' => [
						'/(\\d+),(\\w+)' => [
							'vars' => ['combined' => '$1-$2'],
						]
					]
				]
			]
		]);

		$result = $this->router->resolveRoute('GET', '/main/31/091,foo_bar');

		$this->assertEquals('31', $result->vars['number']);
		$this->assertEquals('fixed', $result->vars['fixed']);
		$this->assertEquals('091-foo_bar', $result->vars['combined']);
	}

	public function testOptionalVarIsCapturedAsEmptyStringIfNotPresent()
	{
		$this->init([
			'routes' => [
				'/(\\d+)?' => [
					'vars' => ['number' => '$1'],
				]
			]
		]);

		$result = $this->router->resolveRoute('GET', '/');

		$this->assertSame('', $result->vars['number']);
	}

	public function testNonStringVarValueIsReturnedUnchanged()
	{
		$this->init([
			'routes' => [
				'/inpath' => [
					'vars' => ['id' => 1],
				]
			]
		]);

		$result = $this->router->resolveRoute('GET', '/inpath');

		$this->assertSame(1, $result->vars['id']);
	}

	public function testPOSTVALGetsValueFromRequestWithVarnameAsFieldnameByDefault()
	{
		$this->init([
			'routes' => [
				'/inpath' => [
					'vars' => ['fieldname' => 'POSTVAL'],
				]
			]
		]);
		Phake::when($this->requestMock)->getPostField('fieldname')->thenReturn('fieldvalue');

		$result = $this->router->resolveRoute('*', '/inpath');

		$this->assertEquals('fieldvalue', $result->vars['fieldname']);
	}

	public function testPOSTVALCanGetValueFromRequestWithExplicitFieldname()
	{
		$this->init([
			'routes' => [
				'/inpath' => [
					'vars' => ['varname' => 'POSTVAL(fieldname)'],
				]
			]
		]);
		Phake::when($this->requestMock)->getPostField('fieldname')->thenReturn('fieldvalue');

		$result = $this->router->resolveRoute('*', '/inpath');

		$this->assertEquals('fieldvalue', $result->vars['varname']);
	}

	public function testGETVALGetsValueFromRequestWithVarnameAsFieldname()
	{
		$this->init([
			'routes' => [
				'/inpath' => [
					'actions' => 'outaction',
					'vars' => ['fieldname' => 'GETVAL'],
				]
			]
		]);
		Phake::when($this->requestMock)->getQueryParam('fieldname')->thenReturn('fieldvalue');

		$result = $this->router->resolveRoute('*', '/inpath');

		$this->assertEquals('fieldvalue', $result->vars['fieldname']);
	}

	public function testGETVALCanGetValueFromRequestWithExplicitFieldname()
	{
		$this->init([
			'routes' => [
				'/inpath' => [
					'vars' => ['varname' => 'GETVAL(fieldname)'],
				]
			]
		]);
		Phake::when($this->requestMock)->getQueryParam('fieldname')->thenReturn('fieldvalue');

		$result = $this->router->resolveRoute('*', '/inpath');

		$this->assertEquals('fieldvalue', $result->vars['varname']);
	}

	public function testREQUESTVALGetsValueFromPostFieldIfAvailable()
	{
		$this->init([
			'routes' => [
				'/inpath' => [
					'actions' => 'outaction',
					'vars' => ['fieldname' => 'REQUESTVAL'],
				]
			]
		]);
		Phake::when($this->requestMock)->getPostField('fieldname')->thenReturn('fieldvalue');

		$result = $this->router->resolveRoute('*', '/inpath');

		$this->assertEquals('fieldvalue', $result->vars['fieldname']);
	}

	public function testREQUESTVALGetsValueFromQueryStringIfNoPostFieldAvailable()
	{
		$this->init([
			'routes' => [
				'/inpath' => [
					'actions' => 'outaction',
					'vars' => ['fieldname' => 'REQUESTVAL'],
				]
			]
		]);
		Phake::when($this->requestMock)->getPostField('fieldname')->thenReturn(null);
		Phake::when($this->requestMock)->getQueryParam('fieldname')->thenReturn('fieldvalue');

		$result = $this->router->resolveRoute('*', '/inpath');

		$this->assertEquals('fieldvalue', $result->vars['fieldname']);
	}

	public function testCOOKIEGetsValueFromRequestWithVarnameAsFieldname()
	{
		$this->init([
			'routes' => [
				'/inpath' => [
					'actions' => 'outaction',
					'vars' => ['fieldname' => 'COOKIE'],
				]
			]
		]);
		Phake::when($this->requestMock)->getCookie('fieldname')->thenReturn('fieldvalue');

		$result = $this->router->resolveRoute('*', '/inpath');

		$this->assertEquals('fieldvalue', $result->vars['fieldname']);
	}

	public function testCOOKIECanGetValueFromRequestWithExplicitFieldname()
	{
		$this->init([
			'routes' => [
				'/inpath' => [
					'vars' => ['varname' => 'COOKIE(fieldname)'],
				]
			]
		]);
		Phake::when($this->requestMock)->getCookie('fieldname')->thenReturn('fieldvalue');

		$result = $this->router->resolveRoute('*', '/inpath');

		$this->assertEquals('fieldvalue', $result->vars['varname']);
	}

	// ************************************************************************
	// Validation of request var values
	// ************************************************************************

	public function testSuccessfulRequestVarValidationYieldsValue()
	{
		$this->init([
			'routes' => [
				'/inpath' => [
					'vars' => ['varname' => 'foo:pass'],
				]
			]
		]);
		Phake::when($this->validationServiceMock)->validate('foo', 'pass')->thenReturn(
			ValidationResult::createValid('foo')
		);

		$result = $this->router->resolveRoute('GET', '/inpath');

		$this->assertEquals('foo', $result->vars['varname']);
	}

	public function testFailingRequestVarValidationThrowsException()
	{
		$this->init([
			'routes' => [
				'/inpath' => [
					'vars' => ['varname' => 'foo:fail'],
				]
			]
		]);
		Phake::when($this->validationServiceMock)->validate('foo', 'fail')->thenReturn(
			ValidationResult::createError('foo')
		);

		$this->expectException(\XAF\web\exception\RequestFieldError::class);
		$this->router->resolveRoute('GET', '/inpath');
	}

	// ************************************************************************
	// Filters
	// ************************************************************************

	public function testSingleInputFilter()
	{
		$this->init([
			'infilters' => 'MyFilter',
			'routes' => [
				'/inpath' => [
				]
			]
		]);

		$result = $this->router->resolveRoute('GET', '/inpath');

		$this->assertEquals(['MyFilter'], $result->inputFilters);
	}

	public function testSingleOutputFilter()
	{
		$this->init([
			'outfilters' => 'MyFilter',
			'routes' => [
				'/inpath' => [
				]
			]
		]);

		$result = $this->router->resolveRoute('GET', '/inpath');

		$this->assertEquals(['MyFilter'], $result->outputFilters);
	}

	public function testAccumulationOfInputFilters()
	{
		$this->init([
			'infilters' => ['MyFilter1', 'MyFilter2'],
			'routes' => [
				'/inpath' => [
					'infilters' => 'MyFilter3',
				]
			]
		]);

		$result = $this->router->resolveRoute('GET', '/inpath');

		$this->assertEquals(['MyFilter1', 'MyFilter2', 'MyFilter3'], $result->inputFilters);
	}

	public function testAccumulationOfOutputFilters()
	{
		$this->init([
			'outfilters' => ['MyFilter1', 'MyFilter2'],
			'routes' => [
				'/inpath' => [
					'outfilters' => 'MyFilter3',
				]
			]
		]);

		$result = $this->router->resolveRoute('GET', '/inpath');

		$this->assertEquals(['MyFilter1', 'MyFilter2', 'MyFilter3'], $result->outputFilters);
	}

	public function testNamedInputFilterWithNullValueAllowsLaterInsertionBeforeOtherFilters()
	{
		$this->init([
			'infilters' => [
				'firstFilter' => null,
				'secondFilter' => 'MySecondFilter'
			],
			'routes' => [
				'/inpath' => [
					'infilters' => ['firstFilter' => 'MyFirstFilter'],
				]
			]
		]);

		$result = $this->router->resolveRoute('GET', '/inpath');

		$this->assertEquals(
			['firstFilter' => 'MyFirstFilter', 'secondFilter' => 'MySecondFilter'],
			$result->inputFilters
		);
	}

	public function testNamedOutputFilterWithNullValueAllowsLaterInsertionBeforeOtherFilters()
	{
		$this->init([
			'outfilters' => [
				'firstFilter' => null,
				'secondFilter' => 'MySecondFilter'
			],
			'routes' => [
				'/inpath' => [
					'outfilters' => ['firstFilter' => 'MyFirstFilter'],
				]
			]
		]);

		$result = $this->router->resolveRoute('GET', '/inpath');

		$this->assertEquals(
			['firstFilter' => 'MyFirstFilter', 'secondFilter' => 'MySecondFilter'],
			$result->outputFilters
		);
	}

	public function testNamedInputFilterReplacement()
	{
		$this->init([
			'infilters' => [
				'first' => 'MyFirstFilter',
				'second' => 'MySecondFilter'
			],
			'routes' => [
				'/inpath' => [
					'infilters' => ['first' => 'MyOtherFirstFilter'],
				]
			]
		]);

		$result = $this->router->resolveRoute('GET', '/inpath');

		$this->assertEquals(
			['first' => 'MyOtherFirstFilter', 'second' => 'MySecondFilter'],
			$result->inputFilters
		);
	}

	public function testNamedOutputFilterReplacement()
	{
		$this->init([
			'outfilters' => [
				'first' => 'MyFirstFilter',
				'second' => 'MySecondFilter'
			],
			'routes' => [
				'/inpath' => [
					'outfilters' => ['first' => 'MyOtherFirstFilter'],
				]
			]
		]);

		$result = $this->router->resolveRoute('GET', '/inpath');

		$this->assertEquals(
			['first' => 'MyOtherFirstFilter', 'second' => 'MySecondFilter'],
			$result->outputFilters
		);
	}

	// ************************************************************************
	// Exception redirects (aka 'catch')
	// ************************************************************************

	public function testCatchItemsAreCombinedIntoResult()
	{
		$this->init([
			'catch' => [
				'ExceptionClass1' => 'redirect_path_1'
			],
			'routes' => [
				'/inpath' => [
					'catch' => [
						'ExceptionClass2' => 'redirect_path_2'
					]
				]
			]
		]);

		$result = $this->router->resolveRoute('GET', '/inpath');

		$this->assertEquals(
			['ExceptionClass1' => 'redirect_path_1', 'ExceptionClass2' => 'redirect_path_2'],
			$result->exceptionRedirects
		);
	}

	// ************************************************************************
	// Resetting of actions, filters, vars and error redirects
	// ************************************************************************

	public function testInputFiltersReset()
	{
		$this->init([
			'infilters' => 'MyFilter',
			'routes' => [
				'/inpath' => [
					'reset' => 'infilters',
					'infilters' => 'MyOtherFilter',
				]
			]
		]);

		$result = $this->router->resolveRoute('GET', '/inpath');

		$this->assertEquals(['MyOtherFilter'], $result->inputFilters);
	}

	public function testOutputFiltersReset()
	{
		$this->init([
			'outfilters' => 'MyFilter',
			'routes' => [
				'/inpath' => [
					'reset' => 'outfilters',
					'outfilters' => 'MyOtherFilter',
				]
			]
		]);

		$result = $this->router->resolveRoute('GET', '/inpath');

		$this->assertEquals(['MyOtherFilter'], $result->outputFilters);
	}

	public function testActionsReset()
	{
		$this->init([
			'actions' => 'default_action',
			'routes' => [
				'/inpath' => [
					'reset' => 'actions',
					'actions' => 'replacement_action'
				]
			]
		]);

		$result = $this->router->resolveRoute('GET', '/inpath');

		$this->assertEquals(['replacement_action'], $result->actions);
	}

	public function testVarsReset()
	{
		$this->init([
			'vars' => ['foo' => 'bar'],
			'routes' => [
				'/inpath' => [
					'reset' => 'vars',
				]
			]
		]);

		$result = $this->router->resolveRoute('GET', '/inpath');

		$this->assertEquals([], $result->vars);
	}

	public function testCatchReset()
	{
		$this->init([
			'catch' => ['exception-class' => 'redirect-path'],
			'routes' => [
				'/inpath' => [
					'reset' => 'catch',
				]
			]
		]);

		$result = $this->router->resolveRoute('GET', '/inpath');

		$this->assertEquals([], $result->exceptionRedirects);
	}

	public function testCombinedReset()
	{
		$this->init([
			'infilters' => 'delete_me',
			'actions' => 'delete_me',
			'outfilters' => 'delete_me',
			'routes' => [
				'/inpath' => [
					'reset' => ['infilters', 'outfilters', 'actions'],
					'actions' => 'outaction'
				]
			]
		]);

		$result = $this->router->resolveRoute('GET', '/inpath');

		$this->assertEquals([], $result->inputFilters);
		$this->assertEquals(['outaction'], $result->actions);
		$this->assertEquals([], $result->outputFilters);
	}

	// ************************************************************************
	// Conditional routing
	// ************************************************************************

	public function testConditionalIfHit()
	{
		$this->init([
			'vars' => ['enabled' => true],
			'routes' => [
				'/inpath' => [
					'if' => 'enabled',
					'actions' => 'outaction'
				]
			]
		]);

		$result = $this->router->resolveRoute('GET', '/inpath');

		$this->assertEquals(['outaction'], $result->actions);
	}

	public function testConditionalIfMiss()
	{
		$this->init([
			'routes' => [
				'/inpath' => [
					'if' => 'enabled',
				]
			]
		]);

		$this->expectException(\XAF\web\exception\PageNotFound::class);
		$this->router->resolveRoute('GET', '/inpath');
	}

	public function testConditionalUnlessHit()
	{
		$this->init([
			'routes' => [
				'/inpath' => [
					'unless' => 'disabled', // var 'disabled' not set
					'actions' => 'outaction'
				]
			]
		]);

		$result = $this->router->resolveRoute('GET', '/inpath');

		$this->assertEquals(['outaction'], $result->actions);
	}

	public function testConditionalUnlessMiss()
	{
		$this->init([
			'vars' => ['varname' => 'foo'],
			'routes' => [
				'/inpath' => [
					'unless' => 'varname=foo',
				]
			]
		]);

		$this->expectException(\XAF\web\exception\PageNotFound::class);
		$this->router->resolveRoute('GET', '/inpath');
	}

	// ************************************************************************
	// Border conditions
	// ************************************************************************

	public function testNoRoutesDefinedThrowsException()
	{
		$this->init(['foo' => 'bar']);

		$this->expectException(\XAF\exception\SystemError::class);
		$this->expectExceptionMessage('routing table has no routes');
		$this->router->resolveRoute('GET', '/whatever');
	}

	public function testInvalidRoutePatternThrowsException()
	{
		$this->init([
			'routes' => [
				'/..[abc' => [// invalid regex because of unclosed square bracket
				]
			]
		]);

		$this->expectException(\XAF\exception\SystemError::class);
		$this->expectExceptionMessage('invalid routing pattern');
		$this->router->resolveRoute('GET', '/aaa');
	}

	public function testInvalidVarExpressionThrowsException()
	{
		$this->init([
			'routes' => [
				'/inpath' => [
					'vars' => 'this should be an array!'
				]
			]
		]);

		$this->expectException(\XAF\exception\SystemError::class);
		$this->expectExceptionMessage('not an array');
		$this->router->resolveRoute('GET', '/inpath');
	}

	public function testInvalidCatchThrowsException()
	{
		$this->init([
			'routes' => [
				'/inpath' => [
					'catch' => 'this should be an array!'
				]
			]
		]);

		$this->expectException(\XAF\exception\SystemError::class);
		$this->expectExceptionMessage('not an array');
		$this->router->resolveRoute('GET', '/inpath');
	}

	/**
	 * When building preg patterns from route patterns, the pattern matcher uses # as a
	 * preg delimiter and should escape any # within the pattern
	 */
	public function testHashCharInPatternCausesNoProblem()
	{
		$this->init([
			'routes' => [
				'/xx#xx' => [
					'actions' => 'outaction'
				]
			]
		]);

		$result = $this->router->resolveRoute('GET', '/xx#xx');

		$this->assertEquals(['outaction'], $result->actions);
	}

}
