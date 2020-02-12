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

namespace Ikarus\SPS\Raspberry\Brick;


use Exception;
use Ikarus\SPS\Plugin\PluginManagementInterface;
use Ikarus\SPS\Raspberry\Event\InputSensorEvent;
use Ikarus\SPS\Raspberry\Pin\InputPin;
use Ikarus\SPS\Raspberry\Pin\InputPinInterface;
use Ikarus\SPS\Raspberry\Pin\PinInfo;
use Ikarus\SPS\Raspberry\RaspberryPi;
use Ikarus\SPS\SensorBrickInterface;

class InputSensorBrick implements SensorBrickInterface
{
    const TRIGGER_INTERVAL_MS = 1;

    protected $pin;
    protected $edge;
    private $eventName;

    /**
     * SimpleInputSensor constructor.
     * @param int|InputPin $pin
     * @param $edge
     * @param string $eventName
     * @throws Exception
     */
    public function __construct($pin, int $edge, $eventName)
    {
        $this->edge = $edge;
        $this->eventName = $eventName;

        if(is_int($pin)) {
            $pi = RaspberryPi::getBoard();
            if($pi->isPinUsed($pin)) {
                throw new Exception("Pin $pin is already in use");
            }

            $pin = $pi->getPin($pin);
            $pin = $pi->requireUsage($pin, PinInfo::USAGE_INPUT, PinInfo::OPTION_RESISTOR_PULL_DOWN);
        }

        if($pin instanceof InputPinInterface) {
            $this->pin = $pin;
        } else
            throw new Exception("Pin must be instance of InputPin");
    }


    public function getIdentifier(): string
    {
        return "INPUT_SENSOR_EVENT";
    }

    public function run(PluginManagementInterface $manager)
    {
        $state = 0;
        while (1) {
            exec(sprintf("gpio wfi %d both", $this->pin->getPinNumber()));
            usleep(static::TRIGGER_INTERVAL_MS * 1000);

            $newState = $this->pin->getValue();

            if($newState != $state) {
                if($this->edge == RaspberryPi::GPIO_EDGE_FALLING && $newState == 0)
                    $manager->dispatchEvent( $this->eventName, new InputSensorEvent($state, $newState, $this->pin->getPinNumber()) );
                elseif($this->edge == RaspberryPi::GPIO_EDGE_RISING && $newState == 1)
                    $manager->dispatchEvent( $this->eventName, new InputSensorEvent($state, $newState, $this->pin->getPinNumber()) );
                else
                    $manager->dispatchEvent( $this->eventName, new InputSensorEvent($state, $newState, $this->pin->getPinNumber()) );
            }

            $state = $newState;
        }
    }
}