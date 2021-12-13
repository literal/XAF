<?php
namespace XAF\view\twig;

use Twig\Node\Node;
use Twig\Node\Expression\AbstractExpression;
use Twig_NodeInterface as NodeInterface;
use Twig\Compiler;

/**
 * Based on twig-cache-extension (c) 2013 Alexander <iam.asm89@gmail.com>
 * @link https://github.com/asm89/twig-cache-extension
 * @license https://github.com/asm89/twig-cache-extension/blob/master/LICENSE
 */
class CacheNode extends Node
{
	/** @var int Global counter for creating unique variable names in templates, because cache tags may be nested */
	private static $compileCount = 0;

	/**
	 * @param Expression $key
	 * @param Expression $lifetimeSeconds
	 * @param NodeInterface $body
	 * @param int $lineno
	 * @param string $tag
	 */
	public function __construct( AbstractExpression $key, AbstractExpression $lifetimeSeconds, NodeInterface $body,
		$lineno, $tag = null )
	{
		parent::__construct(
			['key' => $key, 'lifetime_sec' => $lifetimeSeconds, 'body' => $body],
			[],
			$lineno,
			$tag
		);
	}

	public function compile( Compiler $compiler )
	{
		self::$compileCount++;
		$cacheKeyVarName = '$_cacheKey' . self::$compileCount;
		$cacheValueVarName = '$_cacheValue' . self::$compileCount;

		$compiler->addDebugInfo($this);
		$compiler->write('$_cacheExtension = $this->getEnvironment()->getExtension(\'XAFcache\');' . "\n");
		$compiler->write($cacheKeyVarName . ' = ')->subcompile($this->getNode('key'))->raw(';' . "\n");
		$compiler->write($cacheValueVarName . ' = $_cacheExtension->fetchBlock(' . $cacheKeyVarName . ');' . "\n");

		$compiler->write('if(' . $cacheValueVarName . ' === null) {' . "\n");
		$compiler->indent();
		$compiler->write('ob_start();' . "\n");
		$compiler->subcompile($this->getNode('body'));
		$compiler->write($cacheValueVarName . ' = ob_get_clean();' . "\n");
		$compiler->write('$_cacheExtension->storeBlock(' . $cacheKeyVarName . ', ' . $cacheValueVarName . ', ')
			->subcompile($this->getNode('lifetime_sec'))->raw(');' . "\n");
		$compiler->outdent();
		$compiler->write('}' . "\n");

		$compiler->write('echo ' . $cacheValueVarName . ';' . "\n");
	}
}
