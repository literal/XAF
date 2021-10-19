<?php
namespace XAF\view\helper;

use XAF\markdown\HtmlToPlaintextConverter;

class HtmlHelper
{
	/**
	 * @param string $value
	 * @return string
	 */
	public function escape( $value )
	{
		return \htmlspecialchars($value, ENT_COMPAT, 'UTF-8');
	}

	/**
	 * @param string $str
	 * @return string
	 */
	public function nl2br( $str )
	{
		return \nl2br($str, false);
	}

	/**
	 * Convert sections separated by line breaks into HTML paragraphs
	 *
	 * @param string $str
	 * @param string $startTag
	 * @param string $endTag
	 * @return string
	 */
	public function nl2p( $str, $startTag = '<p>', $endTag = '</p>' )
	{
		return $str != ''
			? $startTag
			  . \strtr($str, ["\n" => $endTag . "\n" . $startTag, "\r\n" => $endTag . "\r\n" . $startTag])
			  . $endTag
			: '';
	}

	/**
	 * @param string $html
	 * @return string
	 */
	public function html2plain( $html )
	{
		$converter = new HtmlToPlaintextConverter();
		return $converter->convert($html);
	}
}
