<?php
namespace XAF\progress;

/**
 * Base class for services emitting progress updates
 */
abstract class ProgressSource
{
	/** @var ProgressDispatcher */
	protected $progress;

	public function __construct( ProgressDispatcher $progressDispatcher = null )
	{
		$this->progress = $progressDispatcher ?: new ProgressDispatcher();
	}

	public function addProgressListener( Listener $listener )
	{
		$this->progress->addListener($listener);
	}

	public function removeProgressListener( Listener $listener )
	{
		$this->progress->removeListener($listener);
	}
}
