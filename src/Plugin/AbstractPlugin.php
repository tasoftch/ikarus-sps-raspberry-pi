<?php


namespace Ikarus\SPS\Raspberry\Plugin;


use Ikarus\Raspberry\Pin\InputPinInterface;
use Ikarus\Raspberry\Pin\OutputPinInterface;
use Ikarus\Raspberry\Pin\PinInterface;
use Ikarus\Raspberry\Pinout\PinoutInterface;
use Ikarus\Raspberry\RaspberryPiDevice;
use Ikarus\SPS\Plugin\SetupPluginInterface;
use Ikarus\SPS\Plugin\TearDownPluginInterface;

abstract class AbstractPlugin extends \Ikarus\SPS\Plugin\AbstractPlugin implements SetupPluginInterface, TearDownPluginInterface
{
	protected $pinout;
	private $pins;

	const WORKING_NUMBER_SYSTEM = RaspberryPiDevice::GPIO_NS_BCM;

	/**
	 * Specifies which pins are used by this plugin.
	 *
	 * @return PinoutInterface
	 */
	public function getPinout(): PinoutInterface {
		if(!$this->pinout)
			$this->pinout = $this->makePinout();
		return $this->pinout;
	}

	/**
	 * Makes the pinout
	 *
	 * @return PinoutInterface
	 */
	abstract public function makePinout(): PinoutInterface;

	private function _ns($pin, $rev = false) {
		$dev = RaspberryPiDevice::getDevice();
		if($rev)
			return $dev->convertPinNumber($pin, $dev::GPIO_NS_BCM, static::WORKING_NUMBER_SYSTEM);
		return $dev->convertPinNumber($pin, static::WORKING_NUMBER_SYSTEM, $dev::GPIO_NS_BCM);
	}

	/**
	 * Gets all used output pins
	 *
	 * @return OutputPinInterface[]
	 */
	protected function getOutputPins(): array {
		$pins = [];
		foreach($this->pins as $pin) {
			if($pin instanceof OutputPinInterface)
				$pins[ $this->_ns($pin->getPinNumber(), true) ] = $pin;
		}
		return $pins;
	}

	/**
	 * Gets all used input pins
	 *
	 * @return InputPinInterface[]
	 */
	protected function getInputPins(): array {
		$pins = [];
		foreach($this->pins as $pin) {
			if($pin instanceof InputPinInterface)
				$pins[$this->_ns($pin->getPinNumber(), true)] = $pin;
		}
		return $pins;
	}

	/**
	 * Returns all used pins by this plugin
	 *
	 * @return PinInterface[]
	 */
	protected function getPins(): array
	{
		$pins = [];
		foreach($this->pins as $pin) {
			$pins[$this->_ns($pin->getPinNumber(), true)] = $pin;
		}
		return $pins;
	}


	/**
	 * @param $pin
	 * @return PinInterface|null
	 */
	public function getPin($pin): ?PinInterface
	{
		return $this->pins[$pin] ?? NULL;
	}

	/**
	 * @param $pin
	 * @return InputPinInterface|null
	 */
	public function getInputPin($pin): ?InputPinInterface
	{
		return $this->pins[$this->_ns($pin)] ?? NULL;
	}

	/**
	 * @param $pin
	 * @return OutputPinInterface|null
	 */
	public function getOutputPin($pin): ?OutputPinInterface
	{
		return $this->pins[$this->_ns($pin)] ?? NULL;
	}

	/**
	 * @inheritDoc
	 */
	public function setup()
	{
		$dev = RaspberryPiDevice::getDevice();
		$this->pins = $dev->requirePinout( $this->getPinout() );
	}

	/**
	 * @inheritDoc
	 */
	public function tearDown()
	{
		$dev = RaspberryPiDevice::getDevice();
		$dev->releasePinout( $this->getPinout() );
	}
}