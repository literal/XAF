<?php
namespace XAF\view\twig;

use Twig_TokenParser;
use Twig_Token;
use Twig_NodeInterface;

class DefaultTokenParser extends Twig_TokenParser
{
	public function parse( Twig_Token $token )
	{
		$lineNumber = $token->getLine();
		$stream = $this->parser->getStream();

		$variableName = $stream->expect(Twig_Token::NAME_TYPE)->getValue();
		$capture = $stream->test(Twig_Token::BLOCK_END_TYPE);
		$valueNode = $capture ? $this->parseCaptureValue() : $this->parseExpressionValue();
		$stream->expect(Twig_Token::BLOCK_END_TYPE);
		return new DefaultNode($variableName, $valueNode, $capture, $lineNumber, $this->getTag());
	}

	/**
	 * @return Twig_NodeInterface
	 */
	private function parseCaptureValue()
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
		return $token->test('enddefault');
	}

	/**
	 * @return Twig_NodeInterface
	 */
	private function parseExpressionValue()
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
		return 'default';
	}
}
