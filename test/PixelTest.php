<?php

declare(strict_types=1);

namespace Zghosts\Blinkt\Test;

use PHPUnit\Framework\TestCase;
use Zghosts\Blinkt\Exception\InvalidBrightnessLevelException;
use Zghosts\Blinkt\Exception\InvalidColorValueException;
use Zghosts\Blinkt\Pixel;

class PixelTest extends TestCase
{
    public function testSetBrightness(): void
    {
        $pixel = new Pixel();
        $pixel->setBrightness(0.5);
        $this->assertEquals((int)(0.5 * 31.0), $pixel->getBrightness());
        $this->assertEquals(0.5, $pixel->getBrightnessValue());
    }

    /**
     * @test
     */
    public function brightnessMoreThan1ThrowsException(): void
    {
        $this->expectException(InvalidBrightnessLevelException::class);
        $pixel = new Pixel();
        $pixel->setBrightness(1.1);
    }

    /**
     * @test
     */
    public function brightnessLessThan0ThrowsException(): void
    {
        $this->expectException(InvalidBrightnessLevelException::class);
        $pixel = new Pixel();
        $pixel->setBrightness(-1);
    }

    /**
     * @test
     */
    public function redMoreThan255ThrowsException(): void
    {
        $this->expectException(InvalidColorValueException::class);
        $pixel = new Pixel();
        $pixel->setRed(256);
    }

    /**
     * @test
     */
    public function redLessThan0ThrowsException(): void
    {
        $this->expectException(InvalidColorValueException::class);
        $pixel = new Pixel();
        $pixel->setRed(-1);
    }

    /**
     * @test
     */
    public function greenLessThan0ThrowsException(): void
    {
        $this->expectException(InvalidColorValueException::class);
        $pixel = new Pixel();
        $pixel->setGreen(-1);
    }

    /**
     * @test
     */
    public function blueLessThan0ThrowsException(): void
    {
        $this->expectException(InvalidColorValueException::class);
        $pixel = new Pixel();
        $pixel->setBlue(-1);
    }

    /**
     * @test
     */
    public function brightnessIsConvertedToBinaryValueCorrectly(): void
    {
        $pixel = new Pixel();
        $pixel->setBrightness(0.5);
        $this->assertEquals(15, $pixel->getBrightness());
        $this->assertEquals(0.5, $pixel->getBrightnessValue());
    }

    public function rgbbCanBeSet(): void
    {
        $pixel = new Pixel();
        $pixel->setRGBB(123, 124, 125, 1.0);
        $this->assertEquals(123, $pixel->getRed());
        $this->assertEquals(124, $pixel->getGreen());
        $this->assertEquals(125, $pixel->getBlue());
        $this->assertEquals(234, $pixel->getBrightness());
    }

    /**
     * @test
     */
    public function pixelCanBeCleared(): void
    {
        $pixel = new Pixel(255, 255, 255, 1);

        $pixel->clear();
        $this->assertEquals(0, $pixel->getRed());
        $this->assertEquals(0, $pixel->getGreen());
        $this->assertEquals(0, $pixel->getBlue());
    }
}
