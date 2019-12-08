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
use Ikarus\SPS\Event\DispatchedEventInterface;
use Ikarus\SPS\Plugin\Listener\AbstractListenerPlugin;
use Ikarus\SPS\Raspberry\Event\InputSensorEvent;
use Ikarus\SPS\Raspberry\Pin\InputPinInterface;
use Ikarus\SPS\Raspberry\Pin\OutputPinInterface;
use Ikarus\SPS\Raspberry\Pin\PinInfo;
use Ikarus\SPS\Raspberry\RaspberryPi;
use TASoft\EventManager\EventManager;

class OutputActorBrick extends AbstractListenerPlugin
{
    private $pin;

    /**
     * OutputActorBrick constructor.
     * @param $pin
     * @param array $eventNames
     * @throws Exception
     */
    public function __construct($pin, array $eventNames)
    {
        parent::__construct($eventNames);
        if(is_int($pin)) {
            $pi = RaspberryPi::getBoard();
            if($pi->isPinUsed($pin)) {
                throw new Exception("Pin $pin is already in use");
            }

            $pin = $pi->getPin($pin);
            $pin = $pi->requireUsage($pin, PinInfo::USAGE_OUTPUT);
        }

        if($pin instanceof OutputPinInterface) {
            $this->pin = $pin;
        } else
            throw new Exception("Pin must be instance of OutputPin");
    }

    public function __invoke(string $eventName, DispatchedEventInterface $event, EventManager $eventManager, ...$arguments)
    {
        if($event instanceof InputSensorEvent) {
            $state = $event->getNewState();
        } else {
            $state = $arguments[0] ?? NULL;
        }

        if(is_int($state)) {
            $this->pin->setValue( $state ? InputPinInterface::VALUE_HIGH : InputPinInterface::VALUE_LOW );
        } else {
            trigger_error("Can not recognize desired state", E_USER_WARNING);
        }
    }

    public function getIdentifier(): string
    {
        return "OUTPUT_ACTOR_" . $this->pin->getPinNumber();
    }
}