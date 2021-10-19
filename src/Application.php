<?php
namespace XAF;

use XAF\ErrorHandler;

use XAF\di\DiContainer,
	XAF\di\DefaultContainer,
	XAF\di\DefaultFactory;

use XAF\config\DefaultConfig,
	XAF\config\ConfigBuilder;

/**
 * Basic application (not web specific)
 *
 * Creates Config and DI container, after which the container can handle any further construction tasks
 *
 * - Implement abstract method execute() to make the application do something
 * - Call loadConfigFile() from bootstrap for each config file to load
 * - Call method run() from bootstrap to - well - run the app :-)
 *
 */
abstract class Application
{
	protected $objectMapConfigKey = 'app.objects';

	/** @var DiContainer */
	protected $diContainer;

	/** @var DefaultFactory */
	protected $diFactory;

	/** @var DefaultConfig */
	protected $config;

	/** @var ConfigBuilder */
	protected $configBuilder;

	public function __construct( ErrorHandler $errorHandler )
	{
		$this->setUpDiContainer();
		$this->diContainer->set('ErrorHandler', $errorHandler);
		$this->setUpConfig();
	}

	protected function setUpDiContainer()
	{
		$this->diContainer = new DefaultContainer();
		$this->diFactory = new DefaultFactory($this->diContainer);
		$this->diContainer->setFactory($this->diFactory);
	}

	protected function setUpConfig()
	{
		$this->config = new DefaultConfig();
		$this->registerObject('Config', $this->config);
		$this->configBuilder = new ConfigBuilder($this->config);
	}

	/**
	 * Register an existing object in the DI container
	 *
	 * @param string $key
	 * @param mixed $object
	 */
	public function registerObject( $key, $object )
	{
		$this->diContainer->set($key, $object);
	}

	/**
	 * Add configuration sub-tree from a file - will be merged with any existing entries
	 *
	 * @param string $targetKey dot-separated target key in the config tree
	 * @param string $file full path to the config file
	 */
	public function loadConfigFile( $targetKey, $file )
	{
		$this->configBuilder->mergeConfigFile($targetKey, $file);
	}

	/**
	 * Add configuration sub-tree or single field value directly, will be merged with existing config data
	 *
	 * @param string $targetKey dot-separated target key in the config tree
	 * @param mixed $data The config subtree or value
	 */
	public function importConfigData( $targetKey, $data )
	{
		$this->config->mergeBranch($targetKey, $data);
	}

	public function run()
	{
		$this->setObjectCreationMap();
		$this->execute();
	}

	protected function setObjectCreationMap()
	{
		$objectMap = $this->config->getRequired($this->objectMapConfigKey);
		$this->diFactory->setObjectCreationMap($objectMap);
	}

	abstract protected function execute();
}
