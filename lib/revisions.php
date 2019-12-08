<?php
/**
 * Copyright (c) 2019 TASoft Applications, Th. Abplanalp <info@tasoft.ch>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

return [
    'revisions' => [
        '0002' => 'B',
        '0003' => 'B',
        '0004' => 'B',
        '0005' => 'B',
        '0006' => 'B',
        '0007' => 'A',
        '0008' => 'A',
        '0009' => 'A',
        '000d' => 'B',
        '000e' => 'B',
        '000f' => 'B',
        '0010' => 'B+',
        '0011' => 'B+',
        '0012' => 'A+',
        '0013' => 'B+',
        '0014' => 'B+',
        '0015' => 'A+',
        'a01040' => '2B',
        'a01041' => '2B',
        'a21041' => '2B',
        'a22042' => '2B',
        900021 => 'A+',
        900032 => 'B+',
        900092 => 'Zero',
        900093 => 'Zero',
        920093 => 'Zero',
        '9000c1' => 'Zero',
        'a02082' => '3 Model B',
        'a020a0' => '3 Model B',
        'a22082' => '3 Model B',
        'a32082' => '3 Model B',
        'a020d3' => '3 Model B+',
        '9020e0' => '3 Model A+',
        'a03111' => '4 Model B',
        'b03111' => '4 Model B',
        'c03111' => '4 Model B'
    ],
    'pinout' => function($revision) {
        $rev = hexdec($revision);
        if($rev < 4)
            return 'r1';
        if($rev < 16)
            return 'r2';
        return 'r3';
    }
];