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


use Ikarus\SPS\Client\ClientInterface;
use Ikarus\SPS\Client\TcpClient;
use Ikarus\SPS\Plugin\Cyclic\AbstractCyclicPlugin;
use Ikarus\SPS\Plugin\Management\CyclicPluginManagementInterface;

/**
 * Use this plugin to communicate between this sps and an sps running on a raspberry pi.
 * Please note, that the raspberry pi must have the PiControllerPlugin installed.
 *
 * @package Ikarus\SPS\Raspberry\Plugin\Cyclic
 * @see PiControllerPlugin
 */
class ExternalPiControllerPlugin extends AbstractCyclicPlugin
{
    const PROP_MODEL =              1<<1;
    const PROP_MODEL_NAME =         1<<2;
    const PROP_SERIAL =             1<<3;
    const PROP_HARDWARE =           1<<4;
    const PROP_CORE_COUNT =         1<<5;

    const PROP_TEMPERATURE =        1<<6;
    const PROP_CPU_USAGE =          1<<7;
    const PROP_CPU_FREQUENCY =      1<<8;
    const PROP_STATUS =             1<<9;
    const PROP_ON_OFF_STATUS =      1<<10;
    const PROP_ADDRESS =            1<<11;

    const PROP_ALL =                4095;

    /** @var ClientInterface */
    private $client;
    /** @var int */
    private $properties;
    private $_properties;

    private $enabled = true;
    private $status = -2;
    private $on_off_status = 1;
    private $auto_stat = 0;

    /**
     * ExternalPiControllerPlugin constructor.
     * @param string $identifier
     * @param ClientInterface $client
     * @param int $properties   Bitmask of which properties are required from the pi
     */
    public function __construct(string $identifier, ClientInterface $client, int $properties = self::PROP_ALL)
    {
        parent::__construct($identifier);
        $this->client = $client;
        $this->properties = $properties;
    }

    /**
     * Sets an automatical value (by a procedure) which covers the detecting status receiving from pi
     *
     * @param int|NULL $status
     * @param null $on_off
     */
    public function setAutomaticalStatus(int $status = NULL, $on_off = NULL) {
        $this->auto_stat = $status !== NULL || $on_off !== NULL;
        $this->status = $status !== NULL ?: $this->status;
        $this->on_off_status = $on_off !== NULL ?: $this->on_off_status;
    }


    public function update(CyclicPluginManagementInterface $pluginManagement)
    {
        $c = $this->getClient();

        $setStatus = function($status, $stat = -1) {
            if(!$this->auto_stat) {
                if($stat == -1)
                    $stat = $status ? 1 : 0;

                $this->status = $status;
                $this->on_off_status = $stat;
            }
        };

        if($c instanceof TcpClient ? $c->isReachable() : true) {
            $setStatus(2);
            $ID = $this->getIdentifier();

            if($pluginManagement->hasCommand("$ID.ENABLED")) {
                $this->setEnabled(true);
                $pluginManagement->clearCommand("$ID.ENABLED");
            }
            if($pluginManagement->hasCommand("$ID.DISABLED")) {
                $this->setEnabled(false);
                $pluginManagement->clearCommand("$ID.DISABLED");
            }

            try {
                if($this->isEnabled()) {
                    if($pluginManagement->hasCommand("$ID.POWEROFF")) {
                        $pluginManagement->clearCommand("$ID.POWEROFF");
                        @$c->sendCommandNamed("poweroff");
                    }

                    $data = @$c->sendCommandNamed("rpi-info " . $this->getProperties());
                    $this->_properties = unserialize($data);

                    $this->_properties[ self::PROP_STATUS ] = $this->status;
                    $this->_properties[ self::PROP_ON_OFF_STATUS ] = $this->on_off_status;

                    $setStatus(4, 1);
                }
            } catch (\Exception $exception) {
                $setStatus(-1, 4);
            }
        } else {
            $setStatus(0, 1);
        }
    }

    /**
     * @return ClientInterface
     */
    public function getClient(): ClientInterface
    {
        return $this->client;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     */
    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    /**
     * @return int
     */
    public function getDesiredProperties(): int
    {
        return $this->properties;
    }

    /**
     * Gets all properties as key value array.
     * The keys are integer numbers representing the self::PROP_* constants.
     *
     * @return array
     */
    public function getProperties(): array {
        return $this->_properties ?: [];
    }

    /**
     * @param int $property
     * @return mixed|null
     */
    public function getProperty(int $property) {
        return $this->_properties[ $property ] ?? NULL;
    }
}