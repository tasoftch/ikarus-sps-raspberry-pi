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

namespace Ikarus\SPS\Raspberry\Event;


use Ikarus\SPS\Event\DispatchedEvent;

class InputSensorEvent extends DispatchedEvent
{
    /** @var int */
    private $oldState;
    /** @var int */
    private $newState;
    /** @var int */
    private $pinNumber;

    /**
     * InputSensorEvent constructor.
     * @param int $oldState
     * @param int $newState
     * @param int $pinNumber
     */
    public function __construct(int $oldState, int $newState, int $pinNumber)
    {
        $this->oldState = $oldState;
        $this->newState = $newState;
        $this->pinNumber = $pinNumber;
    }

    /**
     * @return int
     */
    public function getOldState(): int
    {
        return $this->oldState;
    }

    /**
     * @return int
     */
    public function getNewState(): int
    {
        return $this->newState;
    }

    public function serialize()
    {
        return serialize([
            $this->oldState,
            $this->newState,
            $this->pinNumber,
            parent::serialize()
        ]);
    }

    public function unserialize($serialized)
    {
        list($this->oldState, $this->newState, $this->pinNumber, $parent) = unserialize($serialized);
        parent::unserialize($parent);
    }

    /**
     * @return int
     */
    public function getPinNumber(): int
    {
        return $this->pinNumber;
    }
}