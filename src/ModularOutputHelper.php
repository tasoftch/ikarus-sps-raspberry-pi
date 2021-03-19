<?php


namespace Ikarus\SPS\Raspberry;

/**
 * The modular output helper can be used to dynamically interact between the Ikarus SPS instance and the output model.
 * So if a mcp23017 or another known chip extends your gpio ports, this helper is able to communicate with all.
 * Please note that the Ikarus SPS binary must provide the modular plugins.
 *
 * @package Ikarus\SPS\Raspberry
 */
class ModularOutputHelper
{
	/** @var int */
	private $pin;
	/** @var int|null The i2c address of the gpio extender. If null, assumes bcm pins on raspberry pi directly. */
	private $address;

	/** @var string|callable hander */
	private $gpioQ;

	public function __construct($pin, $addr = NULL)
	{
		$this->pin = $pin;
		$this->address = $addr;
		if(!$addr)
			$this->gpioQ = sprintf("/sys/class/gpio/gpio%d", $pin);
		else
			$this->gpioQ = function() use ($addr, $pin) { return $GLOBALS[ $addr ][ $pin ] ?? NULL; };
	}

	/**
	 * Should be called in setup handler of plugin
	 */
	public function setup()
	{
		if(is_callable($this->gpioQ))
			return;

		file_put_contents("/sys/class/gpio/export", $this->pin);
		file_put_contents("$this->gpioQ/direction", 'out');
		file_put_contents("$this->gpioQ/value", "0");
	}

	/**
	 * Calling this, sends the requested output state.
	 * Please note that modular gpio (extenders) are updated at end of all cycles.
	 *
	 * @param int $state
	 */
	public function sendOutput(int $state) {
		if(is_callable($this->gpioQ)) {
			if(!is_callable($fn = ($this->gpioQ)())) {
				static $warned = false;

				$fn = function() use (&$warned) {
					if(!$warned) {
						$warned = true;
						echo "*** No module specified for address $this->address and output at pin $this->pin.", PHP_EOL;
					}
				};
			}
		} else {
			$fn = function($state) { file_put_contents("$this->gpioQ/value", $state); };
		}

		$fn($state);
	}

	/**
	 * Should be called in tear down handler of plugin
	 */
	public function tearDown()
	{
		if(is_callable($this->gpioQ))
			return;

		file_put_contents("$this->gpioQ/value", "0");
		file_put_contents("$this->gpioQ/direction", 'in');
		file_put_contents("/sys/class/gpio/unexport", $this->pin);
	}

	/**
	 * @return int
	 */
	public function getPin(): int
	{
		return $this->pin;
	}

	/**
	 * @return int|null
	 */
	public function getAddress(): ?int
	{
		return $this->address;
	}
}