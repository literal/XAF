<?php
namespace XAF\view\twig;

use Twig\TokenParser\AbstractTokenParser;
use Twig\Token;
use Twig_NodeInterface;

class ReturnTokenParser extends AbstractTokenParser
{
	/**
	 * @param Token $token
	 * @return Twig_NodeInterface
	 */
	public function parse( Token $token )
	{
		$stream = $this->parser->getStream();
		$expressionParser = $this->parser->getExpressionParser();
		$lineNumber = $token->getLine();

		$valueNode = null;
		if( !$stream->test(Token::BLOCK_END_TYPE) )
		{
			$valueNode = $expressionParser->parseExpression();
		}

		$stream->expect(Token::BLOCK_END_TYPE);

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
