<?php
namespace XAF\view\twig;

use Twig_TokenParser;
use Twig_Token;
use Twig_NodeInterface;
use Twig_Node_Expression_Constant;

/**
 *
 * {% setfield var[key] = value %}
 * {% setfield hash.key = value %}
 * {% setfield hash.key %}value{% endsetfield %}
 */
class SetFieldTokenParser extends Twig_TokenParser
{
	public function parse( Twig_Token $token )
	{
		$stream = $this->parser->getStream();
		$fieldKeys = $this->parseFieldKeyChain();

		$isBlockCaptureAssignment = $stream->test(Twig_Token::BLOCK_END_TYPE);
		$valueNode = $isBlockCaptureAssignment
			? $this->parseBlockCaptureAssignmentValue()
			: $this->parseExpressionAssignmentValue();
		$stream->expect(Twig_Token::BLOCK_END_TYPE);

		return new SetFieldNode(
			$fieldKeys,
			$valueNode,
			$isBlockCaptureAssignment,
			$token->getLine(),
			$this->getTag()
		);
	}

	/**
	 * @return array [<Twig_NodeInterface|null>, ...] Null values represent array append operator, i. e. PHP "$array[] = ..."
	 */
	private function parseFieldKeyChain()
	{
		// The target variable is the first key internally, because variables are hash elements of '$context'
		// in template code
		$result = [$this->parseTargetVariableNameNode()];
		while( $this->existsNextFieldKey() )
		{
			$result[] = $this->parseFieldKey();
		}
		return $result;
	}

	/**
	 * @return Twig_NodeInterface
	 */
	private function parseTargetVariableNameNode()
	{
		$stream = $this->parser->getStream();
		$token = $stream->expect(Twig_Token::NAME_TYPE);
		return new Twig_Node_Expression_Constant($token->getValue(), $token->getLine());
	}

	/**
	 * @return bool
	 */
	private function existsNextFieldKey()
	{
		$stream = $this->parser->getStream();
		return $stream->test(Twig_Token::PUNCTUATION_TYPE, ['.', '[']);
	}

	/**
	 * @return Twig_NodeInterface|null
	 */
	private function parseFieldKey()
	{
		$stream = $this->parser->getStream();
		if( $stream->test(Twig_Token::PUNCTUATION_TYPE, '.') )
		{
			return $this->parseLiteralFieldKey();
		}
		else if( $stream->test(Twig_Token::PUNCTUATION_TYPE, '[') )
		{
			return $this->parseFieldKeyExpression();
		}
		return null;
	}

	/**
	 * @return Twig_NodeInterface
	 */
	private function parseLiteralFieldKey()
	{
		$stream = $this->parser->getStream();
		$stream->expect(Twig_Token::PUNCTUATION_TYPE, '.');
		$token = $stream->expect(Twig_Token::NAME_TYPE);
		return new Twig_Node_Expression_Constant($token->getValue(), $token->getLine());
	}

	/**
	 * @return Twig_NodeInterface|null
	 */
	private function parseFieldKeyExpression()
	{
		$stream = $this->parser->getStream();
		$stream->expect(Twig_Token::PUNCTUATION_TYPE, '[');

		if( $stream->test(Twig_Token::PUNCTUATION_TYPE, ']') )
		{
			$result = null;
		}
		else
		{
			$result = $this->parser->getExpressionParser()->parseExpression();
		}
		$stream->expect(Twig_Token::PUNCTUATION_TYPE, ']');

		return $result;
	}

	/**
	 * @return Twig_NodeInterface
	 */
	private function parseBlockCaptureAssignmentValue()
	{
		$stream = $this->parser->getStream();
		$stream->expect(Twig_Token::BLOCK_END_TYPE);
		return $this->parser->subparse([$this, 'decideBlockEnd'], true);
	}

	/**
	 * @param Twig_Token $token
	 * @return bool
	 */
	public function decideBlockEnd( Twig_Token $token )
	{
		return $token->test('endsetfield');
	}

	/**
	 * @return Twig_NodeInterface
	 */
	private function parseExpressionAssignmentValue()
	{
		$stream = $this->parser->getStream();
		$stream->expect(Twig_Token::OPERATOR_TYPE, '=');
		return $this->parser->getExpressionParser()->parseExpression();
	}

	/**
	 * @return string
	 */
	public function getTag()
	{
		return 'setfield';
	}
}
