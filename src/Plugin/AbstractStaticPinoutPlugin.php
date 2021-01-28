<?php


namespace Ikarus\SPS\Raspberry\Plugin;


use Ikarus\Raspberry\Pinout\PinoutInterface;

abstract class AbstractStaticPinoutPlugin extends AbstractPlugin
{
	public function __construct(string $identifier, PinoutInterface $pinout)
	{
		parent::__construct($identifier);
		$this->pinout = $pinout;
	}

	/**
	 * @inheritDoc
	 */
	public function makePinout(): PinoutInterface{return $this->pinout;}
}