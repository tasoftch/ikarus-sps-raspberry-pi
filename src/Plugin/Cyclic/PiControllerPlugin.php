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

use Ikarus\SPS\Plugin\Intermediate\CyclicIntermediatePlugin;
use Ikarus\SPS\Plugin\Management\CyclicPluginManagementInterface;
use Ikarus\SPS\Plugin\Management\PluginManagementInterface;
use Ikarus\SPS\Raspberry\RaspberryPi;

/**
 * This plugin must be installed on a raspberry pi device if you want to control it from another sps.
 *
 *
 * @package Ikarus\SPS\Raspberry\Plugin\Cyclic
 * @see ExternalPiControllerPlugin
 */
class PiControllerPlugin extends CyclicIntermediatePlugin
{
    private $piInstance;
    private $properties = [];

    public function __construct(string $address, int $port = NULL, string $startMessage = 'Welcome to Remote Event Server of Ikarus SPS!')
    {
        parent::__construct($address, $port, $startMessage);
        $this->piInstance = RaspberryPi::getBoard();

        $str = file_get_contents('/proc/cpuinfo');

        $this->properties[ ExternalPiControllerPlugin::PROP_CORE_COUNT ] = preg_match_all("/processor\s*:\s*\d+/", $str);
        $this->properties[ ExternalPiControllerPlugin::PROP_CPU_FREQUENCY ] = $this->piInstance->getCpuFrequency();
        $this->properties[ ExternalPiControllerPlugin::PROP_HARDWARE ] = $this->piInstance->getHardware();
        $this->properties[ ExternalPiControllerPlugin::PROP_MODEL ] = $this->piInstance->getModel();
        $this->properties[ ExternalPiControllerPlugin::PROP_MODEL_NAME ] = $this->piInstance->getModelName();
        $this->properties[ ExternalPiControllerPlugin::PROP_SERIAL ] = $this->piInstance->getSerial();
    }

    private function _updateProperties() {
        static $usage;

        $this->properties[ ExternalPiControllerPlugin::PROP_TEMPERATURE ] = $this->piInstance->getCpuTemperature();

        if(preg_match("/^cpu\s+([0-9\s]+)$/im", file_get_contents("/proc/stat"), $ms)) {
            list($usr,/* Not used */, $sys, $idle) = preg_split("/\s+/", $ms[1]);
            $used = $usr+$sys;

            if($usage) {
                list($ou, $oi) = $usage;

                $du = $used-$ou;
                $di = $idle-$oi;

                $this->properties[ ExternalPiControllerPlugin::PROP_CPU_USAGE ] = $du / ($du+$di) * 100;
            }
            $usage = [$used, $idle];
        }
    }

    protected function doCommand($command, PluginManagementInterface $management): string
    {
        if($command == 'poweroff') {
            exec("sudo poweroff");
            $management->stopEngine();
            return "";
        }
        if($command == 'poweroff') {
            exec("sudo poweroff");
            $management->stopEngine();
            return "";
        }

        if(substr($command, 0, 8) == 'rpi-info') {
            $data = unserialize(substr($command, 8));
            $values = [];
            foreach($data as $key) {
                if(isset($this->properties[$key]))
                    $values[$key] = $this->properties[$key];
            }
            return serialize($values);
        }

        return parent::doCommand($command, $management);
    }

    public function update(CyclicPluginManagementInterface $pluginManagement)
    {
        $this->_updateProperties();
        parent::update($pluginManagement);
    }


}