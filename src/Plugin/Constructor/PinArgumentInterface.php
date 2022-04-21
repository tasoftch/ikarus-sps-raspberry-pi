<?php


namespace Ikarus\SPS\Raspberry\Plugin\Constructor;


interface PinArgumentInterface
{
	/**
	 * Returns the pin number. Which number system depends on the options returned from the plugin description.
	 *
	 * @return int
	 */
	public function getPinNumber(): int;

	/**
	 * If the pin holds an address, it can be obtained by this method.
	 * Normally used by chips on i2c bus
	 *
	 * @return int
	 */
	public function getAddress(): int;

	/**
	 * A direct callback to read/write to the pin contact.
	 *
	 * @param null $value
	 * @return mixed
	 */
	public function __invoke($value = NULL);
}