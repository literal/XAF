<?php
namespace XAF\progress;

interface Outputter extends Listener
{
	public function start();

	public function finish( $string );
}
