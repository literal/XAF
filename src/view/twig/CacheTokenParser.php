<?php
namespace XAF\view\twig;

use Twig\TokenParser\AbstractTokenParser;
use Twig\Token;
use Twig_NodeInterface;

/**
 * Based on twig-cache-extension (c) 2013 Alexander <iam.asm89@gmail.com>
 * @link https://github.com/asm89/twig-cache-extension
 * @license https://github.com/asm89/twig-cache-extension/blob/master/LICENSE
 */
class CacheTokenParser extends AbstractTokenParser
{
	/**
	 * @return string
	 */
	public function getTag()
	{
		return 'cache';
	}

	/**
	 * @param Token $token
	 * @return Twig_NodeInterface
	 */
	public function parse( Token $token )
	{
		$lineNumber = $token->getLine();
		$stream = $this->parser->getStream();

		$key = $this->parser->getExpressionParser()->parseExpression();
		$lifetimeSeconds = $this->parser->getExpressionParser()->parseExpression();

		$stream->expect(Token::BLOCK_END_TYPE);
		$body = $this->parser->subparse([$this, 'decideCacheEnd'], true);
		$stream->expect(Token::BLOCK_END_TYPE);

		return new CacheNode($key, $lifetimeSeconds, $body, $lineNumber, $this->getTag());
	}

	/**
	 * @return boolean
	 */
	public function decideCacheEnd( Token $token )
	{
		return $token->test('endcache');
	}
}
