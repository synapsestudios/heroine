<?php

namespace Heroine;

use Heroine\Exception\ServiceDefinitionException;
use Heroine\Exception\ServiceNotFoundException;

class Config
{
	const TYPE_ALIAS        = 0;
	const TYPE_INSTANTIABLE = 1;
	const TYPE_CALLABLE     = 2;
	const TYPE_FACTORY      = 3;
	const TYPE_MOCK         = 4;

	protected $_aliases       = array();
	protected $_instantiables = array();
	protected $_callables     = array();
	protected $_factories     = array();
	protected $_initializers  = array();
	protected $_mocks         = array();

	public function __construct($config = array())
	{
		if (isset($config['aliases']))
		{
			foreach ($config['aliases'] as $k => $v)
			{
				$this->addAlias($k, $v);
			}
		}

		if (isset($config['instantiables']))
		{
			foreach ($config['instantiables'] as $k => $v)
			{
				$this->addInstantiable($k, $v);
			}
		}

		if (isset($config['callables']))
		{
			foreach ($config['callables'] as $k => $v)
			{
				$this->addCallable($k, $v);
			}
		}

		if (isset($config['factories']))
		{
			foreach ($config['factories'] as $k => $v)
			{
				$this->addFactory($k, $v);
			}
		}

		if (isset($config['initializers']))
		{
			foreach ($config['initializers'] as $callable)
			{
				$this->addInitializer($callable);
			}
		}
	}

	public function exists($alias)
	{
		return array_key_exists($alias, $this->_aliases)
			OR array_key_exists($alias, $this->_instantiables)
			OR array_key_exists($alias, $this->_callables)
			OR array_key_exists($alias, $this->_factories);
	}

	public function addAlias($alias, $service)
	{
		if ($this->exists($alias))
			throw new ServiceDefinitionException('Service already defined');

		if ( ! is_string($service))
			throw new ServiceDefinitionException('Expected string value for '.$alias);

		$this->_aliases[$alias] = $service;
		return TRUE;
	}

	public function addInstantiable($alias, $service)
	{
		if ($this->exists($alias))
			throw new ServiceDefinitionException('Service already defined');

		if ( ! is_string($service))
			throw new ServiceDefinitionException('Expected string value for '.$alias);

		if ( ! class_exists($service))
			throw new ServiceDefinitionException('Cannot load class for '.$alias);

		$this->_instantiables[$alias] = $service;
	}

	public function addCallable($alias, $service)
	{
		if ($this->exists($alias))
			throw new ServiceDefinitionException('Service already defined');

		if ( ! is_callable($service))
			throw new ServiceDefinitionException('Expected callable for '.$alias);

		$this->_callables[$alias] = $service;
	}

	public function addFactory($alias, $service)
	{
		if ($this->exists($alias))
			throw new ServiceDefinitionException('Service already defined');

		if ( ! is_string($service))
			throw new ServiceDefinitionException('Expected string value for '.$alias);

		if ( ! class_exists($service))
			throw new ServiceDefinitionException('Cannot load class for '.$alias);

		$this->_factories[$alias] = $service;
	}

	public function addMock($alias, $mock)
	{
		$this->_mocks[$alias] = $mock;
	}

	public function removeMock($alias)
	{
		unset($this->_mocks[$alias]);
	}

	public function addInitializer($initializer)
	{
		if ( ! is_callable($initializer))
			throw new ServiceDefinitionException('Initializer must be a callable');

		$this->_initializers[] = $initializer;
	}

	public function getAliases()
	{
		return $this->_aliases;
	}

	public function getInstantiables()
	{
		return $this->_instantiables;
	}

	public function getCallables()
	{
		return $this->_callables;
	}

	public function getFactories()
	{
		return $this->_factories;
	}

	public function getInitializers()
	{
		return $this->_initializers;
	}

	public function resolveAlias($service)
	{
		if (isset($this->_aliases[$service]))
		{
			$service = $this->resolveAlias($this->_aliases[$service]);
		}

		return $service;
	}

	public function resolveService($service)
	{
		if (isset($this->_mocks[$service]))
		{
			return array(
				'type' => self::TYPE_MOCK,
				'factory' => $this->_mocks[$service]
			);
		}
		if (isset($this->_instantiables[$service]))
		{
			return array(
				'type'    => self::TYPE_INSTANTIABLE,
				'factory' => $this->_instantiables[$service],
			);
		}
		if (isset($this->_callables[$service]))
		{
			return array(
				'type'    => self::TYPE_CALLABLE,
				'factory' => $this->_callables[$service],
			);
		}

		if (isset($this->_factories[$service]))
		{
			return array(
				'type'    => self::TYPE_FACTORY,
				'factory' => $this->_factories[$service],
			);
		}

		throw new ServiceNotFoundException($service);
	}
}