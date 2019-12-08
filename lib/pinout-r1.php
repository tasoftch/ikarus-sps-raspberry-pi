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

use Ikarus\SPS\Raspberry\RaspberryPiBoardInterface as RaspberryPi;

return [
    'max' => 26,
    'name' => [
        0 => '',
        1 => '3.3v',
        2 => '5v',
        3 => 'SDA.1',
        4 => '5v',
        5 => 'SCL.1',
        6 => '0v',
        7 => 'GPIO. 7',
        8 => 'TxD',
        9 => '0v',
        10 => 'RxD',
        11 => 'GPIO. 0',
        12 => 'GPIO. 1',
        13 => 'GPIO. 2',
        14 => '0v',
        15 => 'GPIO. 3',
        16 => 'GPIO. 4',
        17 => '3.3v',
        18 => 'GPIO. 5',
        19 => 'MOSI',
        20 => '0v',
        21 => 'MISO',
        22 => 'GPIO. 6',
        23 => 'SCLK',
        24 => 'CE0',
        25 => '0v',
        26 => 'CE1'
    ],
    'bcm2brd' => [
        0 => 3,
        1 => 5,
        4 => 7,
        14 => 8,
        15 => 10,
        17 => 11,
        18 => 12,
        21 => 13,
        22 => 15,
        23 => 16,
        24 => 18,
        10 => 19,
        9 => 21,
        25 => 22,
        11 => 23,
        8 => 24,
        7 => 26,
    ],
    'wpi2brd' => [
        0 => 11,
        1 => 12,
        2 => 13,
        3 => 15,
        4 => 16,
        5 => 18,
        6 => 22,
        7 => 7,
        8 => 3,
        9 => 5,
        10 => 24,
        11 => 26,
        12 => 19,
        13 => 21,
        14 => 23,
        15 => 8,
        16 => 10,
    ],
    'funcs' => [
        RaspberryPi::MODE_33V => [
            1, 17
        ],
        RaspberryPi::MODE_5V => [
            2, 4
        ],
        RaspberryPi::MODE_GROUND => [
            6, 9, 14, 20, 25
        ],
        RaspberryPi::MODE_GPIO => [
            3, 5, 7, 8, 10, 11, 12, 13, 15, 16, 18, 19, 21, 22, 23, 24, 26
        ],
        RaspberryPi::MODE_SPI => [
            19, 21, 23, 24, 26
        ],
        RaspberryPi::MODE_I2C => [
            3, 5
        ],
        RaspberryPi::MODE_UART => [
            8, 10
        ],
    ]
];
