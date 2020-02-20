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
    const PROP_MODEL = 'md';
    const PROP_MODEL_NAME = 'mdn';
    const PROP_SERIAL = 'srl';
    const PROP_HARDWARE = 'hdw';
    const PROP_CORE_COUNT = 'cpc';

    const PROP_TEMPERATURE = 'tmp';
    const PROP_CPU_USAGE = 'cpu';
    const PROP_CPU_FREQUENCY = 'cpf';
    const PROP_STATUS = 'stat';
    const PROP_ON_OFF_STATUS = 'onoff';
    const PROP_ADDRESS = 'addr';

    /** @var ClientInterface */
    private $client;
    /** @var string */
    private $identifier;
    /** @var array */
    private $properties;
    /** @var string */
    private $domain;

    private $enabled = true;
    private $status = -2;
    private $on_off_status = 1;
    private $auto_stat = 0;

    /**
     * ExternalPiControllerPlugin constructor.
     * @param string $identifier
     * @param array $properties mapping self::PROP_* constants to desired field names
     * @param string $domain
     * @param ClientInterface $client
     */
    public function __construct(string $identifier, array $properties, string $domain, ClientInterface $client)
    {
        $this->client = $client;
        $this->identifier = $identifier;
        $this->domain = $domain;

        foreach ($properties as $key => $name) {
            if(is_numeric($key))
                $this->properties[$name] = $name;
            else
                $this->properties[$key] = $name;
        }
    }


    public function setAutomaticalStatus(int $status = NULL, $on_off = NULL) {
        $this->auto_stat = $status !== NULL || $on_off !== NULL;

        $this->status = $status;
        $this->on_off_status = $on_off;
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

            if($pluginManagement->hasCommand("$this->identifier.ENABLED")) {
                $this->setEnabled(true);
                $pluginManagement->clearCommand("$this->identifier.ENABLED");
            }
            if($pluginManagement->hasCommand("$this->identifier.DISABLED")) {
                $this->setEnabled(false);
                $pluginManagement->clearCommand("$this->identifier.DISABLED");
            }

            try {
                if($this->isEnabled()) {
                    if($pluginManagement->hasCommand("$this->identifier.POWEROFF")) {
                        @$c->sendCommandNamed("poweroff");
                        $pluginManagement->clearCommand("$this->identifier.POWEROFF");
                    }
                    elseif($pluginManagement->hasCommand("$this->identifier.REBOOT")) {
                        @$c->sendCommandNamed("reboot");
                        $pluginManagement->clearCommand("$this->identifier.REBOOT");
                    }

                    $data = serialize(array_keys($this->getProperties()));
                    $data = @$c->sendCommandNamed("rpi-info $data");
                    $data = unserialize($data);
                    $setStatus(4, 1);
                }
            } catch (\Exception $exception) {
                $setStatus(-1, 4);
            }
        } else {
            $setStatus(0, 1);
        }


        $values = [];

        foreach($this->getProperties() as $key => $property) {
            if($key == static::PROP_STATUS)
                $v = $this->status;
            elseif($key == static::PROP_ON_OFF_STATUS)
                $v = $this->on_off_status;
            else
                $v = $data[$key] ?? NULL;

            if($v !== NULL)
                $values[$property] = $v;
        }

        $pluginManagement->putValue($values, $this->getIdentifier(), $this->getDomain());
    }

    /**
     * @return ClientInterface
     */
    public function getClient(): ClientInterface
    {
        return $this->client;
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @return array
     */
    public function getProperties(): array
    {
        return $this->properties;
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
     * @return string
     */
    public function getDomain(): string
    {
        return $this->domain;
    }

    /**
     * @param string $domain
     */
    public function setDomain(string $domain): void
    {
        $this->domain = $domain;
    }
}