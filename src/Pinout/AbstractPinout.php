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

namespace Ikarus\SPS\Raspberry\Pinout;


abstract class AbstractPinout implements PinoutInterface
{
	/**
	 * List all pin numbers the pinout wants to use as keys and the values must be an integer
	 *
	 * @var array
	 * @see PinoutInterface::INPUT_RESISTOR_NONE
	 * @see PinoutInterface::INPUT_RESISTOR_UP
	 * @see PinoutInterface::INPUT_RESISTOR_DOWN
	 */
    protected $inputPins = [
    ];

	/**
	 * List all pin numbers the pinout wants to use as keys and the values must be a boolean indicating pwm or not.
	 *
	 * @var array
	 */
    protected $outputPins = [
    ];

	/**
	 * List all pin numbers that should be treated as active low.
	 * 
	 * @var array 
	 */
    protected $activeLowPins = [
	];

	/**
	 * @inheritDoc
	 */
    public function yieldInputPin(int &$resistor, bool &$activeLow)
    {
        foreach($this->inputPins as $pin => $r) {
            if(NULL !== ($pin = $this->convertPin($pin))) {
                $resistor = (int)$r;
                $activeLow = $this->isActiveLow($pin);
                yield $pin;
            }
        }
    }

	/**
	 * @inheritDoc
	 */
    public function yieldOutputPin(bool &$usePWM, bool &$activeLow)
    {
        foreach($this->outputPins as $pin => $r) {
            if(NULL !== ($pin = $this->convertPin($pin))) {
                $usePWM = (bool)$r;
				$activeLow = $this->isActiveLow($pin);
                yield $pin;
            }
        }
    }

	/**
	 * Called to determine if a pin should be treated as active low.
	 *
	 * @param int $pin
	 * @return bool
	 */
    protected function isActiveLow(int $pin): bool {
		return in_array($pin, $this->activeLowPins);
	}

    /**
     * Converts the pin into bcm pinout scheme
     *
     * @param int $pin
     * @return null|int
     */
    abstract protected function convertPin(int $pin);
}