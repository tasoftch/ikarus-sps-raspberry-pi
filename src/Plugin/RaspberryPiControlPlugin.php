<?php


namespace Ikarus\SPS\Raspberry\Plugin;


use Ikarus\Raspberry\RaspberryPiDevice;
use Ikarus\SPS\Plugin\Interval\AbstractCycleIntervalPlugin;
use Ikarus\SPS\Plugin\SetupPluginInterface;
use Ikarus\SPS\Register\MemoryRegisterInterface;

class RaspberryPiControlPlugin extends AbstractCycleIntervalPlugin implements SetupPluginInterface
{
	const PROP_MODEL =              1<<1;
	const PROP_MODEL_NAME =         1<<2;
	const PROP_SERIAL =             1<<3;
	const PROP_HARDWARE =           1<<4;
	const PROP_CORE_COUNT =         1<<5;

	const PROP_TEMPERATURE =        1<<6;
	const PROP_CPU_USAGE =          1<<7;
	const PROP_CPU_FREQUENCY =      1<<8;
	const PROP_ADDRESS =            1<<11;

	const PROP_ALL =                4095;

	private $interval;
	private $properties = [];
	private $_registered = self::PROP_ALL;

	private $piInstance;

	private $usage, $lastUsage, $nullCount, $writer;

	/**
	 * RaspberryPiControlPlugin constructor.
	 *
	 * Define a writer to put the properties into memory management
	 *
	 * @param string $identifier
	 * @param int $cycleInterval
	 * @param callable|null $writer
	 */
	public function __construct(string $identifier, int $cycleInterval = 1, callable $writer = NULL)
	{
		parent::__construct($identifier);
		$this->interval = $cycleInterval;
		$this->writer = $writer;
	}

	/**
	 * @inheritDoc
	 */
	protected function getInterval(): int
	{
		return $this->interval;
	}


	/**
	 * Defines, which properties should be updated.
	 * If no parameter specified, this method just returns the current flags.
	 *
	 * @param int|null $propertyFlags
	 * @return int
	 */
	public function maintainUpToDate(int $propertyFlags = NULL): int {
		if(NULL !== $propertyFlags)
			$this->_registered = $propertyFlags;
		return $this->_registered;
	}

	/**
	 * Retrieves a property
	 *
	 * @param int $propertyFlag
	 * @return mixed|null
	 */
	public function getProperty(int $propertyFlag) {
		return $this->properties[$propertyFlag] ?? NULL;
	}

	/**
	 * Retrieves multiple properties
	 *
	 * @param int|null $propertyFlags
	 * @return array
	 */
	public function getProperties(int $propertyFlags = NULL): array {
		if(NULL === $propertyFlags)
			return $this->properties;
		return array_filter($this->properties, function($flag) use ($propertyFlags) {
			return $propertyFlags & $flag ? true : false;
		}, ARRAY_FILTER_USE_KEY);
	}

	public function setup()
	{
		$this->piInstance = RaspberryPiDevice::getDevice();

		$str = file_get_contents('/proc/cpuinfo');
		$this->properties[ static::PROP_CORE_COUNT ] = preg_match_all("/processor\s*:\s*\d+/", $str);
		$this->properties[ static::PROP_CPU_FREQUENCY ] = $this->piInstance->getCpuFrequency();
		$this->properties[ static::PROP_HARDWARE ] = $this->piInstance->getHardware();
		$this->properties[ static::PROP_MODEL ] = $this->piInstance->getModel();
		$this->properties[ static::PROP_MODEL_NAME ] = $this->piInstance->getModelName();
		$this->properties[ static::PROP_SERIAL ] = $this->piInstance->getSerial();
	}

	/**
	 * @inheritDoc
	 */
	protected function updateInterval(MemoryRegisterInterface $memoryRegister)
	{
		if($this->_registered & static::PROP_TEMPERATURE) {
			$this->properties[ static::PROP_TEMPERATURE ] = $this->piInstance->getCpuTemperature();
		} else
			$this->properties[ static::PROP_TEMPERATURE ] = false;

		if($this->_registered & static::PROP_ADDRESS) {
			$iface = "";
			$addresses = [];

			foreach(preg_split("/(\n\r|\r|\n)/i", `ifconfig`) as $line) {
				if(preg_match("/^([a-z0-9_\-]+):/i", $line, $ms)) {
					$iface = $ms[1];
					continue;
				}

				if(preg_match("/^\s*inet\s*(\d+\.\d+\.\d+\.\d+)/i", $line, $ms)) {
					if($iface) {
						$addresses[$iface] = $ms[1];
						$iface = NULL;
						continue;
					}
				}
			}
			$this->properties[ static::PROP_ADDRESS ] = $addresses;
		} else
			$this->properties[ static::PROP_ADDRESS ] = false;

		if($this->_registered & static::PROP_CPU_USAGE) {
			if(preg_match("/^cpu\s+([0-9\s]+)$/im", file_get_contents("/proc/stat"), $ms)) {
				list($usr,/* Not used */, $sys, $idle) = preg_split("/\s+/", $ms[1]);
				$used = $usr+$sys;

				if($this->usage) {
					list($ou, $oi) = $this->usage;

					$du = $used-$ou;
					$di = $idle-$oi;

					$us = $du / ($du+$di) * 100;
					if($us == 0 && $this->lastUsage != 0) {
						$this->nullCount++;
						if($this->nullCount < 20)
							$us = $this->lastUsage;
					} else {
						$this->nullCount = 0;
					}

					$this->properties[ static::PROP_CPU_USAGE ] = $us;
					$this->lastUsage = $us;
				}
				$this->usage = [$used, $idle];
			}
		} else {
			$this->lastUsage = $this->nullCount = $this->usage = NULL;
			$this->properties[ static::PROP_CPU_USAGE ] = false;
		}

		if($this->writer)
			($this->writer)($memoryRegister, $this->properties);
	}
}