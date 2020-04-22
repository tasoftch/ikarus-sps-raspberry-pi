<?php
/**
 * BSD 3-Clause License
 *
 * Copyright (c) 2019, TASoft Applications
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 *  Redistributions of source code must retain the above copyright notice, this
 *   list of conditions and the following disclaimer.
 *
 *  Redistributions in binary form must reproduce the above copyright notice,
 *   this list of conditions and the following disclaimer in the documentation
 *   and/or other materials provided with the distribution.
 *
 *  Neither the name of the copyright holder nor the names of its
 *   contributors may be used to endorse or promote products derived from
 *   this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
 * OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 */

namespace Ikarus\SPS\Raspberry\Plugin\Cyclic;


use Ikarus\SPS\Plugin\Cyclic\AbstractCyclesDependentPlugin;
use Ikarus\SPS\Plugin\Management\CyclicPluginManagementInterface;
use Ikarus\SPS\Raspberry\RaspberryPi;

class PiInfoPlugin extends AbstractCyclesDependentPlugin
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

	private $properties = [];
	private $_registered = self::PROP_ALL;

	private $piInstance;

	private $usage, $lastUsage, $nullCount;

	public function __construct(int $cycleInterval = 1, string $identifier = NULL)
	{
		parent::__construct($cycleInterval, $identifier);
		$this->piInstance = RaspberryPi::getBoard();

		$str = file_get_contents('/proc/cpuinfo');

		$this->properties[ static::PROP_CORE_COUNT ] = preg_match_all("/processor\s*:\s*\d+/", $str);
		$this->properties[ static::PROP_CPU_FREQUENCY ] = $this->piInstance->getCpuFrequency();
		$this->properties[ static::PROP_HARDWARE ] = $this->piInstance->getHardware();
		$this->properties[ static::PROP_MODEL ] = $this->piInstance->getModel();
		$this->properties[ static::PROP_MODEL_NAME ] = $this->piInstance->getModelName();
		$this->properties[ static::PROP_SERIAL ] = $this->piInstance->getSerial();
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

	protected function updateInterval(CyclicPluginManagementInterface $pluginManagement)
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


	}
}