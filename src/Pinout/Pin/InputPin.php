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

namespace Ikarus\SPS\Raspberry\Pinout\Pin;


class InputPin implements InputPinInterface
{
    const GPIO_VALUE_PATH = '/sys/class/gpio/gpio%d/value';

    /**
     * @var int
     */
    private $pinNumber;
    /** @var bool */
    private $activeLow;

	/**
	 * InputPin constructor.
	 * @param int $pinNumber
	 * @param bool $activeLow
	 */
    public function __construct(int $pinNumber, bool $activeLow = false)
    {
        $this->pinNumber = $pinNumber;
        $this->activeLow = $activeLow;
    }


    /**
     * @return int
     */
    public function getPinNumber(): int
    {
        return $this->pinNumber;
    }

    /**
     * @inheritDoc
     */
    public function getValue() {
    	if($this->isActiveLow())
			return trim( file_get_contents(sprintf(static::GPIO_VALUE_PATH, $this->getPinNumber())) ) ? 0 : 1;
        return trim( file_get_contents(sprintf(static::GPIO_VALUE_PATH, $this->getPinNumber())) ) ? 1 : 0;
    }

	/**
	 * @return bool
	 */
	public function isActiveLow(): bool
	{
		return $this->activeLow;
	}
}