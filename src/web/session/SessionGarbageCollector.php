<?php
namespace XAF\web\session;

interface SessionGarbageCollector
{
	/**
	 * Collect and kill expired sessions
	 *
	 * @return int number of killed sessions
	 */
	public function cleanup();
}

