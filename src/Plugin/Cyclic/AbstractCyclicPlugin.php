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


use Ikarus\SPS\Plugin\SetupPluginInterface;
use Ikarus\SPS\Plugin\TearDownPluginInterface;
use Ikarus\SPS\Raspberry\Pin\InputPinInterface;
use Ikarus\SPS\Raspberry\Pin\PinInterface;
use Ikarus\SPS\Raspberry\Pinout\Pin\InputPin;
use Ikarus\SPS\Raspberry\Pinout\Pin\OutputPin;
use Ikarus\SPS\Raspberry\Pinout\Pin\OutputPinInterface;
use Ikarus\SPS\Raspberry\Pinout\Pin\PullDownInputPin;
use Ikarus\SPS\Raspberry\Pinout\Pin\PullUpInputPin;
use Ikarus\SPS\Raspberry\Pinout\Pin\PulseWithModulationPin;
use Ikarus\SPS\Raspberry\Pinout\Pin\PulseWithModulationPinInterface;
use Ikarus\SPS\Raspberry\Pinout\PinoutInterface;

abstract class AbstractCyclicPlugin extends \Ikarus\SPS\Plugin\Cyclic\AbstractCyclicPlugin implements SetupPluginInterface, TearDownPluginInterface
{
    const GPIO_PREFIX = '/sys/class/gpio';

    const GPIO_EXPORT = self::GPIO_PREFIX . "/export";
    const GPIO_UNEXPORT = self::GPIO_PREFIX . "/unexport";

    const GPIO_EXPORTED_PIN = self::GPIO_PREFIX . "/gpio%d";

    const PIN_MODE_INPUT = 1 << 0;
    const PIN_MODE_RESISTOR_UP = 1<<1;
    const PIN_MODE_RESISTOR_DOWN = 1<<2;

    const PIN_MODE_OUTPUT = 1 << 4;
    const PIN_MODE_OUTPUT_PWM = 1 << 5;

    private $usedPins = [];
    private $pinout;

    /**
     * Makes the pinout
     *
     * @return PinoutInterface
     */
    abstract public function makePinout(): PinoutInterface;

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

    public function setup()
    {
        $resistor = 0;
        $pinout = $this->getPinout();

        foreach($pinout->yieldInputPin($resistor) as $pin) {
            if(!file_exists( sprintf(self::GPIO_EXPORTED_PIN, $pin) )) {
                file_put_contents( self::GPIO_EXPORT, $pin );
            }
            file_put_contents(sprintf(self::GPIO_EXPORTED_PIN . "/direction", $pin), 'in');

            if($resistor == $pinout::INPUT_RESISTOR_UP) {
                $this->usedPins[$pin] = new PullUpInputPin($pin);
                exec("gpio -g mode $pin up");
            }
            elseif($resistor == $pinout::INPUT_RESISTOR_DOWN) {
                $this->usedPins[$pin] = new PullDownInputPin($pin);
                exec("gpio -g mode $pin down");
            } else
                $this->usedPins[$pin] = new InputPin($pin);
            $resistor = 0;
        }

        $pwm = false;
        foreach($pinout->yieldOutputPin($pwm) as $pin) {
            if($pwm) {
                $this->usedPins[$pin] = new PulseWithModulationPin($pin);
                exec("gpio -g mode $pin pwm");
                $pwm = false;
            } else {
                if(!file_exists( sprintf(self::GPIO_EXPORTED_PIN, $pin) )) {
                    file_put_contents( self::GPIO_EXPORT, $pin );
                }
                file_put_contents(sprintf(self::GPIO_EXPORTED_PIN . "/direction", $pin), 'out');
                $this->usedPins[$pin] = new OutputPin($pin);
            }
        }
    }

    public function tearDown()
    {
        foreach($this->usedPins as $pin) {
            if($pin instanceof InputPinInterface) {
                if($pin instanceof PullUpInputPin || $pin instanceof PullDownInputPin)
                    exec("gpio -g mode $pin tri");
            } elseif ($pin instanceof OutputPinInterface) {
                if($pin instanceof PulseWithModulationPinInterface)
                    exec("gpio -g mode $pin in");
            }

            if(file_exists( sprintf(self::GPIO_EXPORTED_PIN, $pin->getPinNumber()) )) {
                file_put_contents(sprintf(self::GPIO_EXPORTED_PIN . "/direction", $pin->getPinNumber()), 'in');
                file_put_contents( self::GPIO_UNEXPORT, $pin->getPinNumber() );
            }
        }
        $this->usedPins = [];
    }

    /**
     * @param $pin
     * @return PinInterface|null
     */
    public function getPin($pin) {
        return $this->usedPins[$pin] ?? NULL;
    }

    /**
     * @return PinInterface[]
     */
    public function getPins(): array {
        return array_keys($this->usedPins);
    }

    /**
     * Gets all used input pins
     *
     * @return InputPinInterface[]
     */
    public function getInputPins(): array {
        $pins = [];
        foreach($this->usedPins as $p => $m) {
            if($m & self::PIN_MODE_INPUT)
                $pins[$p] = $m;
        }
        return $pins;
    }

    /**
     * Gets all used output pins
     *
     * @return OutputPinInterface[]
     */
    public function getOutputPins(): array {
        $pins = [];
        foreach($this->usedPins as $p => $m) {
            if($m & self::PIN_MODE_OUTPUT)
                $pins[$p] = $m;
        }
        return $pins;
    }
}