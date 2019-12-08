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
use Ikarus\SPS\Raspberry\Pin\InputPin;
use Ikarus\SPS\Raspberry\Pin\OutputPin;
use Ikarus\SPS\Raspberry\Pin\OutputPWMPin;
use Ikarus\SPS\Raspberry\Pin\PinInfo;
use Ikarus\SPS\Raspberry\Pin\PinInterface;

class RaspberryPi implements RaspberryPiBoardInterface
{
    private $model;
    private $modelName;
    private $pinout;
    private $hardware;
    private $serial;

    private $numberSystem = self::GPIO_NS_WIRED;

    private $usedPins = [];

    /**
     * RaspberryPi constructor.
     * @throws Exception
     */
    public function __construct()
    {
        $info = file_get_contents('/proc/cpuinfo');
        if(preg_match_all("/^revision\s*:\s*(\S+)\s*$/im", $info, $ms)) {
            $this->model = $ms[1][0];
            $revisions = require __DIR__ . "/../lib/revisions.php";
            $this->modelName = $revisions["revisions"][$this->model] ?? 'Unknown';

            if(is_callable( $po = $revisions["pinout"] ?? NULL)) {
                if(is_file($file = __DIR__ . "/../lib/pinout-" . $po($this->model) . ".php")) {
                    $this->pinout = require $file;
                }
            }
            if(!$this->pinout)
                throw new Exception("Unknow Raspberry Pi, can not detect pinout");
        }
        if(preg_match_all("/^hardware\s*:\s*(\S+)\s*$/im", $info, $ms)) {
            $this->hardware = $ms[1][0];
        }
        if(preg_match_all("/^serial\s*:\s*(\S+)\s*$/im", $info, $ms)) {
            $this->serial = $ms[1][0];
        }
    }
    /**
     * @inheritDoc
     */
    public function getModel(): string {
        return $this->model;
    }
    /**
     * @inheritDoc
     */
    public function getModelName(): string {
        return $this->modelName;
    }
    /**
     * @inheritDoc
     */
    public function getHardware(): string {
        return $this->hardware;
    }
    /**
     * @inheritDoc
     */
    public function getSerial(): string {
        return $this->serial;
    }
    /**
     * @inheritDoc
     */
    public function getPinout(): array {
        return $this->pinout;
    }

    /**
     * @inheritDoc
     */
    public static function getBoard(): RaspberryPiBoardInterface {
        static $pi = NULL;
        if(!$pi) {
            $pi = new static();
        }
        return $pi;
    }


    /**
     * @inheritDoc
     */
    public function convertPinNumber(int $pinNumber, int $from = NULL, int $to = self::GPIO_NS_BOARD): int
    {
        if(is_null($from))
            $from = $this->getNumberSystem();

        switch ($from) {
            case self::GPIO_NS_BCM:
                $src = $this->pinout['bcm2brd'];
                break;
            case self::GPIO_NS_WIRED:
                $src = $this->pinout['wpi2brd'];
                break;
            default:
        }

        switch ($to) {
            case self::GPIO_NS_BCM:
                $dst = $this->pinout['bcm2brd'];
                break;
            case self::GPIO_NS_WIRED:
                $dst = $this->pinout['wpi2brd'];
                break;
            default:
        }

        if(isset($src))
            $pinNumber = $src[$pinNumber] ?? -1;

        if(isset($dst)) {
            if(($idx = array_search($pinNumber, $dst)) !== false)
                return $idx;
            return -1;
        }

        if(isset($this->pinout["name"][$pinNumber]))
            return $pinNumber;
        else
            return -1;
    }

    /**
     * @inheritDoc
     */
    public function getModesForPin(int $pinNumber, int $ns = NULL): int {
        $pin = $this->convertPinNumber($pinNumber, $ns);
        $modes = 0;
        foreach($this->pinout["funcs"] as $mode => $pins) {
            if(in_array($pin, $pins))
                $modes|=$mode;
        }
        return $modes;
    }

    /**
     * @inheritDoc
     */
    public function getCpuTemperature(): float
    {
        return floatval(file_get_contents('/sys/class/thermal/thermal_zone0/temp'))/1000;
    }

    /**
     * @inheritDoc
     */
    public function getCpuFrequency(): int
    {
        return floatval(file_get_contents('/sys/devices/system/cpu/cpu0/cpufreq/scaling_cur_freq'))/1000;
    }

    /**
     * @inheritDoc
     */
    public function getNumberSystem(): int
    {
        return $this->numberSystem;
    }

    /**
     * @inheritDoc
     */
    public function setNumberSystem(int $numberSystem)
    {
        $this->numberSystem = $numberSystem;
    }

    /**
     * @inheritDoc
     */
    public function getPin(int $pin, $mode = NULL): PinInfo {
        $bpin = $this->convertPinNumber($pin, $mode, self::GPIO_NS_BOARD);

        return new PinInfo(
            $bpin,
            $this->convertPinNumber($bpin, self::GPIO_NS_BOARD, self::GPIO_NS_BCM),
            $this->convertPinNumber($bpin, self::GPIO_NS_BOARD, self::GPIO_NS_WIRED),
            $this->pinout["name"][$bpin] ?? '??',
            $this->getModesForPin($pin, $mode)
        );
    }

    /**
     * @inheritDoc
     */
    public function isPinUsed(int $pin): bool {
        return ($this->usedPins[$pin] ?? false) ? true : false;
    }

    /**
     * @inheritDoc
     */
    public function getUsedPin(int $pin): ?PinInterface {
        return $this->usedPins[$pin] ?? NULL;
    }

    /**
     * @inheritDoc
     */
    public function requireUsage(PinInfo $pin, $usage = PinInfo::USAGE_INPUT, int $options = 0) {
        $PIN = NULL;

        if($pin->getWiredPinNumber() < 0)
            throw new Exception("Pin is not usable");

        switch ($usage) {
            case PinInfo::USAGE_OUTPUT:
                exec(sprintf("gpio mode %d out && gpio write %d 0", $pin->getWiredPinNumber(), $pin->getWiredPinNumber()));
                $PIN = new OutputPin($pin->getWiredPinNumber(), $pin->getBCMPinNumber());
                file_put_contents("/sys/class/gpio/export", $pin->getBCMPinNumber());
                break;
            case PinInfo::USAGE_PWM:
                exec(sprintf("gpio mode %d pwm && gpio pwm %d 0", $pin->getWiredPinNumber(), $pin->getWiredPinNumber()));
                $PIN = new OutputPWMPin($pin->getWiredPinNumber(), $pin->getBCMPinNumber());
                break;
            default:
                $cmd = sprintf("gpio mode %d in", $pin->getWiredPinNumber());
                if($options & PinInfo::OPTION_RESISTOR_PULL_DOWN)
                    $cmd .= sprintf(" && gpio mode %d down", $pin->getWiredPinNumber());
                elseif($options & PinInfo::OPTION_RESISTOR_PULL_UP)
                    $cmd .= sprintf(" && gpio mode %d up", $pin->getWiredPinNumber());
                elseif($options == PinInfo::OPTION_RESISTOR_NONE)
                    $cmd .= sprintf(" && gpio mode %d tri", $pin->getWiredPinNumber());

                exec($cmd);

                $PIN = new InputPin($pin->getWiredPinNumber(), $pin->getBCMPinNumber());
        }

        $this->usedPins[ $pin->getWiredPinNumber() ] = $PIN;

        return $PIN;
    }
}