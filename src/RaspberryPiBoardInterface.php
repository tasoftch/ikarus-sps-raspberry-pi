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

namespace Ikarus\SPS\Raspberry;

use Exception;
use Ikarus\SPS\Raspberry\Pin\PinInfo;
use Ikarus\SPS\Raspberry\Pin\PinInterface;

interface RaspberryPiBoardInterface
{
    /** @var int Pin is available as GPIO */
    const MODE_GPIO         = 1<<0;

    /** @var int Pin is ground 0v */
    const MODE_GROUND       = 1<<1;

    /** @var int Power pin 3.3v */
    const MODE_33V          = 1<<2;

    /** @var int Power pin 5v */
    const MODE_5V           = 1<<3;

    /** @var int SPI Pin */
    const MODE_SPI          = 1<<4;

    /** @var int I2C Pin */
    const MODE_I2C          = 1<<5;

    /** @var int UART Pin */
    const MODE_UART         = 1<<6;


    /** @var int The physical pin numbers on board */
    const GPIO_NS_BOARD      = 0;

    /** @var int The Broadcom SOC bord numbers */
    const GPIO_NS_BCM        = 1;

    /** @var int The wired board numbers */
    const GPIO_NS_WIRED      = 2;


    const GPIO_EDGE_FALLING = 1;
    const GPIO_EDGE_RISING = 2;
    const GPIO_EDGE_BOTH = 3;

    /**
     * Gets a singleton instance of the raspberry pi device.
     * Please only create a board instance using this method!
     *
     * @return RaspberryPi
     * @throws Exception
     */
    public static function getBoard(): RaspberryPiBoardInterface;

    /**
     * The Device model as board value
     *
     * @return string
     */
    public function getModel(): string ;

    /**
     * The device model as string value
     *
     * @return string
     */
    public function getModelName(): string;

    /**
     * The hardware
     *
     * @return string
     */
    public function getHardware(): string;

    /**
     * The serial number
     *
     * @return string
     */
    public function getSerial(): string;

    /**
     * The Pinout scheme of your device
     *
     * @return array
     */
    public function getPinout(): array;

    /**
     * Reads the CPU temperature of the pi
     *
     * @return float
     */
    public function getCpuTemperature(): float;
    /**
     * Reads the current frequency of the CPU
     *
     * @return int
     */
    public function getCpuFrequency(): int;

    /**
     * Gets the default (your desired number system)
     * @return int
     */
    public function getNumberSystem(): int;

    /**
     * sets the default number system
     * @param int $numberSystem
     */
    public function setNumberSystem(int $numberSystem);

    /**
     * Converts any pin number from a given number system into another
     *
     * @param int $pinNumber
     * @param int|NULL $from
     * @param int $to
     * @return int
     *
     * @see RaspberryPiBoardInterface::GPIO_NS_* constants
     */
    public function convertPinNumber(int $pinNumber, int $from = NULL, int $to = self::GPIO_NS_BOARD): int;

    /**
     * Reads from revision what modes (functions) a given pin has
     *
     * @param int $pinNumber
     * @param int|NULL $ns      Number system or default
     * @return int
     *
     * @see RaspberryPiBoardInterface::MODE_* constants
     */
    public function getModesForPin(int $pinNumber, int $ns = NULL): int;

    /**
     * Creates pin information about a given pin
     *
     * @param int $pin
     * @param null $mode
     * @return PinInfo|null
     */
    public function getPin(int $pin, $mode = NULL): PinInfo;

    /**
     * Returns true, if a given pin is in use
     *
     * @param int $pin
     * @return bool
     */
    public function isPinUsed(int $pin): bool;

    /**
     * Returns a pin that is in use
     *
     * @param int $pin
     * @return PinInterface|null
     */
    public function getUsedPin(int $pin): ? PinInterface;

    /**
     * Prepares a pin for usage
     *
     * @param PinInfo $pin
     * @param string $usage
     * @param int $options
     * @return PinInterface
     * @throws Exception
     */
    public function requireUsage(PinInfo $pin, $usage = PinInfo::USAGE_INPUT, int $options = 0);

    /**
     * This method sets all used pins to input mode.
     */
    public function cleanupUsedPins();
}