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


use Ikarus\SPS\Communication\CommunicationInterface;
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

    /** @var CommunicationInterface */
    private $communication;
    /** @var string */
    private $identifier;
    /** @var array */
    private $properties;
    /** @var string */
    private $domain;

    private $enabled = true;

    /**
     * ExternalPiControllerPlugin constructor.
     * @param string $identifier
     * @param array $properties mapping self::PROP_* constants to desired field names
     * @param string $domain
     * @param CommunicationInterface $communication
     */
    public function __construct(string $identifier, array $properties, string $domain, CommunicationInterface $communication)
    {
        $this->communication = $communication;
        $this->identifier = $identifier;
        $this->domain = $domain;

        foreach ($properties as $key => $name) {
            if(is_numeric($key))
                $this->properties[$name] = $name;
            else
                $this->properties[$key] = $name;
        }
    }


    public function update(CyclicPluginManagementInterface $pluginManagement)
    {
        if($pluginManagement->hasCommand("$this->identifier.ENABLED")) {
            $this->setEnabled(true);
            $pluginManagement->clearCommand("$this->identifier.ENABLED");
        }
        if($pluginManagement->hasCommand("$this->identifier.DISABLED")) {
            $this->setEnabled(false);
            $pluginManagement->clearCommand("$this->identifier.DISABLED");
        }

        if($this->isEnabled()) {
            if($pluginManagement->hasCommand("$this->identifier.POWEROFF")) {
                $this->getCommunication()->sendToSPS("poweroff");
                $pluginManagement->clearCommand("$this->identifier.POWEROFF");
            }
            if($pluginManagement->hasCommand("$this->identifier.REBOOT")) {
                $this->getCommunication()->sendToSPS("reboot");
                $pluginManagement->clearCommand("$this->identifier.REBOOT");
            }


            $data = serialize(array_keys($this->getProperties()));
            $data = $this->getCommunication()->sendToSPS("rpi-info $data");
            $data = unserialize($data);

            foreach($this->getProperties() as $key => $property) {
                $v = $data[$key] ?? NULL;
                if(NULL !== $v)
                    $pluginManagement->putValue($v, $property, $this->getDomain());
            }
        }
    }

    /**
     * @return CommunicationInterface
     */
    public function getCommunication(): CommunicationInterface
    {
        return $this->communication;
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