<?php
namespace XAF\web\routing;

use PHPUnit\Framework\TestCase;
use Phake;

use XAF\http\Request;
use XAF\validate\ValidationService;
use XAF\validate\ValidationResult;

/**
 * @covers \XAF\web\routing\RequestVarResolver
 */
class RequestVarResolverTest extends TestCase
{
	/** @var RequestVarResolver */
	private $object;

	/** @var Request */
	private $requestMock;

	/** @var ValidationService */
	private $validationServiceMock;

	protected function setUp(): void
	{
		$this->requestMock = Phake::mock(Request::class);
		$this->validationServiceMock = Phake::mock(ValidationService::class);
		$this->object = new RequestVarResolver($this->requestMock, $this->validationServiceMock);
	}

	public function testInputValueIsReturnedUnchangedWithoutColonInExpression()
	{
		$result = $this->object->resolveVar('varName', 'inputValue');

		Phake::verifyNoInteraction($this->validationServiceMock);
		$this->assertEquals('inputValue', $result);
	}

	public function testInputValueIsReturnedUnchangedWhenNotAString()
	{
		$result = $this->object->resolveVar('varName', ['foo', 'bar']);

		$this->assertEquals(['foo', 'bar'], $result);
	}

	public function testReturnValueOfValidationServiceIsReturnedAsResultWhenColonIsPresent()
	{
		$this->setValidationPassesAndReturns('resultValue');

		$result = $this->object->resolveVar('varName', 'inputValue:rule');

		Phake::verify($this->validationServiceMock)->validate('inputValue', 'rule');
		$this->assertEquals('resultValue', $result);
	}

	public function testValidationRuleStartsAfterLastColonWhenMultipleColonsExist()
	{
		$this->setValidationPassesAndReturns('resultValue');

		$this->object->resolveVar('varName', 'inputValue:notARule:rule');

		Phake::verify($this->validationServiceMock)->validate('inputValue:notARule', 'rule');
	}

	public function testFailingInputValidationThrowsBadRequest()
	{
		$this->setValidationFails();

		$this->expectException(\XAF\web\exception\RequestFieldError::class);
		$this->object->resolveVar('varName', 'value:rule');
	}

	public function testValueIsFetchedFromRequestWhenExpressionStartsWithPOSTVAL()
	{
		Phake::when($this->requestMock)->getPostField(Phake::anyParameters())->thenReturn('postValue');

		$result = $this->object->resolveVar('varName', 'POSTVAL');

		Phake::verify($this->requestMock)->getPostField('varName');
		$this->assertEquals('postValue', $result);
	}

	public function testPostFieldNameCanBeExplicitlySpecifiedInParenhesis()
	{
		$this->object->resolveVar('varName', 'POSTVAL(fieldName)');

		Phake::verify($this->requestMock)->getPostField('fieldName');
	}

	public function testValueIsFetchedFromRequestWhenExpressionStartsWithGETVAL()
	{
		Phake::when($this->requestMock)->getQueryParam(Phake::anyParameters())->thenReturn('getValue');

		$result = $this->object->resolveVar('varName', 'GETVAL');

		Phake::verify($this->requestMock)->getQueryParam('varName');
		$this->assertEquals('getValue', $result);
	}

	public function testGetFieldNameCanBeExplicitlySpecifiedInParenhesis()
	{
		$this->object->resolveVar('varName', 'GETVAL(fieldName)');

		Phake::verify($this->requestMock)->getQueryParam('fieldName');
	}

	public function testValueIsFirstFetchedFromPostDataWhenExpressionStartsWithREQUESTVAL()
	{
		Phake::when($this->requestMock)->getPostField(Phake::anyParameters())->thenReturn('postValue');
		Phake::when($this->requestMock)->getQueryParam(Phake::anyParameters())->thenReturn('getValue');

		$result = $this->object->resolveVar('varName', 'REQUESTVAL');

		Phake::verify($this->requestMock)->getPostField('varName');
		Phake::verifyNoFurtherInteraction($this->requestMock);
		$this->assertEquals('postValue', $result);
	}

	public function testValueIsFetchedFromGetDataWhenPostFieldNotExistsAndExpressionStartsWithREQUESTVAL()
	{
		Phake::when($this->requestMock)->getQueryParam(Phake::anyParameters())->thenReturn('getValue');

		$result = $this->object->resolveVar('varName', 'REQUESTVAL');

		Phake::verify($this->requestMock)->getPostField('varName');
		Phake::verify($this->requestMock)->getQueryParam('varName');
		$this->assertEquals('getValue', $result);
	}

	public function testRequestFieldNameCanBeExplicitlySpecifiedInParenhesis()
	{
		$this->object->resolveVar('varName', 'REQUESTVAL(fieldName)');

		Phake::verify($this->requestMock)->getPostField('fieldName');
		Phake::verify($this->requestMock)->getQueryParam('fieldName');
	}

	public function testCookieIsFetchedFromRequestWhenExpressionStartsWithCOOKIE()
	{
		Phake::when($this->requestMock)->getCookie(Phake::anyParameters())->thenReturn('cookieValue');

		$result = $this->object->resolveVar('varName', 'COOKIE');

		Phake::verify($this->requestMock)->getCookie('varName');
		$this->assertEquals('cookieValue', $result);
	}

	public function testCookieNameCanBeExplicitlySpecifiedInParenhesis()
	{
		$this->object->resolveVar('varName', 'COOKIE(cookieName)');

		Phake::verify($this->requestMock)->getCookie('cookieName');
	}

	public function testRequestExpressionCanBeCombinedWithValidation()
	{
		$this->setValidationPassesAndReturns('resultValue');
		Phake::when($this->requestMock)->getQueryParam(Phake::anyParameters())->thenReturn('getValue');

		$this->object->resolveVar('varName', 'GETVAL:rule');

		Phake::verify($this->requestMock)->getQueryParam('varName');
		Phake::verify($this->validationServiceMock)->validate('getValue', 'rule');
	}

	public function testRequestExpressionWithFieldNameCanBeCombinedWithValidation()
	{
		$this->setValidationPassesAndReturns('resultValue');
		Phake::when($this->requestMock)->getCookie(Phake::anyParameters())->thenReturn('cookieValue');

		$this->object->resolveVar('varName', 'COOKIE(cookieName):rule');

		Phake::verify($this->requestMock)->getCookie('cookieName');
		Phake::verify($this->validationServiceMock)->validate('cookieValue', 'rule');
	}

	public function testExpressionIsTreatedAsLiteralValueIfThereAreCharsAfterRequestExpression()
	{
		$this->setValidationPassesAndReturns('resultValue');

		// The "bar" makes the "POSTVAL" source expression invalid, so everything before ":rule" is taken literally
		$this->object->resolveVar('varName', 'POSTVAL(foo)bar:rule');

		Phake::verify($this->validationServiceMock)->validate('POSTVAL(foo)bar', 'rule');
	}

	public function testValueInParenthesisIsTreatedAsLiteralWithoutPrecedingRequestSource()
	{
		$result = $this->object->resolveVar('varName', '(NotAFieldName)');

		$this->assertEquals('(NotAFieldName)', $result);
	}

	private function setValidationPassesAndReturns( $result )
	{
		Phake::when($this->validationServiceMock)->validate(Phake::anyParameters())
			->thenReturn(ValidationResult::createValid($result));
	}

	private function setValidationFails()
	{
		Phake::when($this->validationServiceMock)->validate(Phake::anyParameters())
			->thenReturn(ValidationResult::createError('someError'));
	}
}
