<?php
namespace XAF\view\twig;

use Twig\Error\RuntimeError;

class SetFieldHelper
{
	static public function assertFieldCanBeSet( array $context, array $fieldKeyChain, $lineNumber = -1, $fileName = null )
	{
		$leafFieldKey = \array_pop($fieldKeyChain);
		$currentTarget = $context;
		foreach( $fieldKeyChain as $fieldKey )
		{
			if( $fieldKey === null || !\array_key_exists($fieldKey, $currentTarget) )
			{
				return;
			}
			if( !\is_array($currentTarget[$fieldKey]) )
			{
				throw new RuntimeError('Fields can only be assigned on arrays or hashes.', $lineNumber, $fileName);
			}

			$currentTarget = $currentTarget[$fieldKey];
		}
	}
}
