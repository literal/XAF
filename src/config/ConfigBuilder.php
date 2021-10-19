<?php
namespace XAF\config;

class ConfigBuilder
{
	/** @var DefaultConfig */
	private $config;

	public function __construct( DefaultConfig $config )
	{
		$this->config = $config;
	}

	/**
	 * @param string|null $key target node in config tree, dot-separated, e.g. 'main.sub.key', or null for root
	 * @param string $configFile config file to include, must be a PHP file returning an array -
	 *     absolute path or relative to include path
	 */
	public function loadConfigFile( $key, $configFile )
	{
		$options = $this->getConfigFileContents($configFile);
		$this->config->import($key, $options);
	}

	/**
	 * @param string|null $key target node in config tree, dot-separated, e.g. 'main.sub.key', or null for root
	 * @param string $configFile config file to include, must be a PHP file returning an array -
	 *     absolute path or relative to include path
	 */
	public function mergeConfigFile( $key, $configFile )
	{
		$options = $this->getConfigFileContents($configFile);
		$this->config->mergeBranch($key, $options);
	}

	/**
	 * @param string $configFile
	 * @return array
	 */
	private function getConfigFileContents( $configFile )
	{
		$contents = include $configFile;
		if( false === $contents )
		{
			throw new ConfigBuilderError('failed to include config file: ' . $configFile);
		}
		if( !\is_array($contents) )
		{
			throw new ConfigBuilderError('included config file did not return an array: ' . $configFile);
		}
		return $contents;
	}
}
