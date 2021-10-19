<?php
namespace XAF\view\twig;

use Twig_TokenParser;
use Twig_Token;
use Twig_NodeInterface;

class ReturnTokenParser extends Twig_TokenParser
{
	/**
	 * @param Twig_Token $token
	 * @return Twig_NodeInterface
	 */
	public function parse( Twig_Token $token )
	{
		$stream = $this->parser->getStream();
		$expressionParser = $this->parser->getExpressionParser();
		$lineNumber = $token->getLine();

		$valueNode = null;
		if( !$stream->test(Twig_Token::BLOCK_END_TYPE) )
		{
			$valueNode = $expressionParser->parseExpression();
		}

		$stream->expect(Twig_Token::BLOCK_END_TYPE);

		return new ReturnNode($valueNode, $lineNumber, $this->getTag());
	}

	/**
	 * @return string
	 */
	public function getTag()
	{
		return 'return';
	}
}
