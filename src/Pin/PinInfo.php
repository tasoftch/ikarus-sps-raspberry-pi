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

namespace Ikarus\SPS\Raspberry\Pin;


use Ikarus\SPS\Raspberry\RaspberryPiBoardInterface;

class PinInfo
{
    const USAGE_INPUT = 'in';
    const USAGE_OUTPUT = 'out';
    const USAGE_PWM = 'pwm';

    const OPTION_RESISTOR_PULL_UP = 1<<0;
    const OPTION_RESISTOR_PULL_DOWN = 1<<1;
    const OPTION_RESISTOR_NONE = 0;


    /** @var int */
    private $boardPinNumber;
    /** @var int */
    private $BCMPinNumber;
    /** @var int */
    private $wiredPinNumber;
    /** @var string */
    private $name;
    /** @var int */
    private $modes;

    /**
     * PinInfo constructor.
     * @param int $boardPinNumber
     * @param int $BCMPinNumber
     * @param int $wiredPinNumber
     * @param string $name
     * @param int $modes
     */
    public function __construct(int $boardPinNumber, int $BCMPinNumber, int $wiredPinNumber, string $name, int $modes)
    {
        $this->boardPinNumber = $boardPinNumber;
        $this->BCMPinNumber = $BCMPinNumber;
        $this->wiredPinNumber = $wiredPinNumber;
        $this->name = $name;
        $this->modes = $modes;
    }


    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @inheritDoc
     */
    public function getModes(): int
    {
        return $this->modes;
    }

    /**
     * @inheritDoc
     */
    public function isChangeable(): bool
    {
        return $this->getModes() & RaspberryPiBoardInterface::MODE_GPIO ? true : false;
    }


    /**
     * @inheritDoc
     */
    public function getBoardPinNumber(): int
    {
        return $this->boardPinNumber;
    }

    /**
     * @inheritDoc
     */
    public function getBCMPinNumber(): int
    {
        return $this->BCMPinNumber;
    }

    /**
     * @inheritDoc
     */
    public function getWiredPinNumber(): int
    {
        return $this->wiredPinNumber;
    }

    /**
     * Debug output
     *
     * @return array
     */
    public function __debugInfo()
    {
        $info = [
            'BOARD' => $this->boardPinNumber,
            'BCM' => $this->BCMPinNumber,
            "WPI" => $this->wiredPinNumber,
            'Name' => $this->name
        ];

        $modes = [];
        foreach([
                    RaspberryPiBoardInterface::MODE_GPIO => 'GPIO',
                    RaspberryPiBoardInterface::MODE_GROUND => 'GROUND',
                    RaspberryPiBoardInterface::MODE_5V => '5v',
                    RaspberryPiBoardInterface::MODE_33V => '3.3v',
                    RaspberryPiBoardInterface::MODE_I2C => 'I2C',
                    RaspberryPiBoardInterface::MODE_UART => 'UART',
                    RaspberryPiBoardInterface::MODE_SPI => 'SPI'
                ] as $mode => $name) {
            if($this->modes & $mode)
                $modes[] = $name;
        }
        $info["Modes"] = implode(" ", $modes);

        return $info;
    }
}