<?php
namespace XAF\view\twig;

use Twig\TokenParser\AbstractTokenParser;
use Twig\Token;
use Twig_NodeInterface;

class DefaultTokenParser extends AbstractTokenParser
{
	public function parse( Token $token )
	{
		$lineNumber = $token->getLine();
		$stream = $this->parser->getStream();

		$variableName = $stream->expect(Token::NAME_TYPE)->getValue();
		$capture = $stream->test(Token::BLOCK_END_TYPE);
		$valueNode = $capture ? $this->parseCaptureValue() : $this->parseExpressionValue();
		$stream->expect(Token::BLOCK_END_TYPE);
		return new DefaultNode($variableName, $valueNode, $capture, $lineNumber, $this->getTag());
	}

	/**
	 * @return Twig_NodeInterface
	 */
	private function parseCaptureValue()
	{
		$stream = $this->parser->getStream();
		$stream->expect(Token::BLOCK_END_TYPE);
		return $this->parser->subparse([$this, 'decideBlockEnd'], true);
	}

	/**
	 * @param Token $token
	 * @return bool
	 */
	public function decideBlockEnd( Token $token )
	{
		return $token->test('enddefault');
	}

	/**
	 * @return Twig_NodeInterface
	 */
	private function parseExpressionValue()
	{
		$stream = $this->parser->getStream();
		$stream->expect(Token::OPERATOR_TYPE, '=');
		return $this->parser->getExpressionParser()->parseExpression();
	}

	/**
	 * @return string
	 */
	public function getTag()
	{
		return 'default';
	}
}
