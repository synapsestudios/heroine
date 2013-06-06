<?php

namespace Heroine;

/**
 * Heroine
 *
 * Heroine is here to rescue you!
 */
class Heroine
{

	protected static $_instances = array();

	/**
	 * For the lazy only
	 *
	 * @param  string $alias
	 * @return Heroine
	 */
	public static function instance($alias = 'default', array $config = array())
	{
		if ( ! isset(self::$_instances[$alias]))
			self::$_instances[$alias] = new Heroine($config);

		return self::$_instances[$alias];
	}

	protected $_config;

	protected $_repository;

	public function __construct($config = array(), RepositoryInterface $repository = NULL)
	{
		if (is_object($config) && $config instanceof Config)
		{
			$this->_config = $config;
		}
		else
		{
			$this->_config = new Config($config);
		}

		$this->_repository = $repository ?: new Repository;
	}

	/**
	 * Fetches an object, either from the repository, or by creating it.
	 * When creating objects, we follow this exact load order:
	 *  - Instantiables
	 *  - Callables
	 *  - Factories
	 *
	 * Initializers are run on all objects, regardless of where they are loaded
	 *
	 * @param  string $service service name
	 * @return object
	 */
	public function get($service)
	{
		$resolvedName = $this->_config->resolveAlias($service);

		if ($this->_repository->has($resolvedName))
			return $this->_repository->get($resolvedName);

		$serviceConfig = $this->_config->resolveService($resolvedName);
		$factory       = $serviceConfig['factory'];
		$type          = $serviceConfig['type'];

		switch ($serviceConfig['type'])
		{
			case Config::TYPE_INSTANTIABLE:
				$object = new $factory;
				break;
			case Config::TYPE_CALLABLE:
				$object = $factory($this);
				break;
			case Config::TYPE_FACTORY:
				$factory = new $factory;
				$object = $factory->createService($this, $resolvedName)
					?: $factory->createService($this, $name);
				break;
			default:
				throw new Exception\InvalidFactoryException;
		}

		if ( ! $object)
			throw new Exception\ServiceNotFoundException;

		if ($object instanceof HeroineAwareInterface)
			$object->setHeroine($this);

		$initializers = $this->_config->getInitializers();
		foreach ($initializers as $callable)
		{
			$callable($this, $object);
		}

		$this->_repository->set($resolvedName, $object);

		return $object;
	}

	public function addAlias($alias, $service)
	{
		return $this->_config->addAlias($alias, $service);
	}

	public function addInstantiable($alias, $service)
	{
		return $this->_config->addInstantiable($alias, $service);
	}

	public function addCallable($alias, $service)
	{
		return $this->_config->addCallable($alias, $service);
	}

	public function addFactory($alias, $service)
	{
		return $this->_config->addFactory($alias, $service);
	}

	public function addInitializer($callable)
	{
		return $this->_config->addInitializer($callable);
	}

	/**
	 * @return Config this instance's config
	 */
	public function getConfig()
	{
		return $this->_config;
	}

	/**
	 * @param Config $config
	 */
	public function setConfig(Config $config)
	{
		$this->_config = $config;
		return $this;
	}

	/**
	 * @return RepositoryInterface
	 */
	public function getRepository()
	{
		return $this->_repository;
	}

	/**
	 * @param RepositoryInterface $repository
	 * @return Heroine
	 */
	public function setRepository(RepositoryInterface $repository)
	{
		$this->_repository = $repository;
		return $this;
	}
}