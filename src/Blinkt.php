<?php

declare(strict_types=1);

namespace Zghosts\Blinkt;

use InvalidArgumentException;
use PiPHP\GPIO\GPIOInterface;
use PiPHP\GPIO\Pin\OutputPinInterface;
use Webmozart\Assert\Assert;

final class Blinkt
{
    public const DAT = 23;
    public const CLK = 24;

    public const NUM_PIXELS = 8;

    private const SIGNIFICANT_BIT_MASK = 0xE0; // 0b11100000
    private const BIT_MASK             = 0x80; // 0b10000000

    /**
     * @var Pixel[]
     */
    private $pixels = [];

    /**
     * @var GPIOInterface
     */
    private $gpio;

    /**
     * @var OutputPinInterface
     */
    private $dataPin;

    /**
     * @var OutputPinInterface
     */
    private $clockPin;

    /**
     * @var bool
     */
    private $clearOnExit = false;

    /**
     * @var bool
     */
    private $isGpioSetup = false;

    public function __construct(GPIOInterface $gpio)
    {
        $this->gpio = $gpio;

        for ($p = 0; $p < self::NUM_PIXELS; ++$p) {
            $this->pixels[$p] = new Pixel();
        }
    }

    public function setup(int $dat = self::DAT, int $clk = self::CLK): void
    {
        Assert::greaterThanEq($dat, 1, sprintf('DAT pin must be between 1 and 27 (default is %d)', self::DAT));
        Assert::lessThanEq($dat, 27, sprintf('DAT pin must be between 1 and 27 (default is %d)', self::DAT));

        Assert::greaterThanEq($clk, 1, sprintf('CLK pin must be between 1 and 27 (default is %d)', self::CLK));
        Assert::lessThanEq($clk, 27, sprintf('CLK pin must be between 1 and 27 (default is %d)', self::CLK));

        if ($dat === $clk) {
            throw new InvalidArgumentException('DAT-pin and CLK-pin cannot be the same');
        }

        $this->dataPin  = $this->gpio->getOutputPin($dat);
        $this->clockPin = $this->gpio->getOutputPin($clk);

        $this->isGpioSetup = true;
    }

    public function __destruct()
    {
        if ($this->clearOnExit) {
            $this->clear();
            $this->show();
        }
    }

    public function setClearOnExit(bool $clearOnExit = true): void
    {
        $this->clearOnExit = $clearOnExit;
    }

    /**
     * Clear the pixel buffer.
     */
    public function clear(): void
    {
        foreach (range(0, self::NUM_PIXELS - 1) as $x) {
            $this->pixels[$x]->clear();
        }
    }

    /**
     * @return Pixel[]
     */
    public function getPixels(): iterable
    {
        return $this->pixels;
    }

    /**
     * Set the brightness of all pixels.
     *
     * @param float $brightness Brightness: 0.0 to 1.0
     */
    public function setBrightness(float $brightness): void
    {
        Assert::greaterThanEq($brightness, 0.0, 'Brightness should be between 0.0 and 1.0');
        Assert::lessThanEq($brightness, 1.0, 'Brightness should be between 0.0 and 1.0');

        foreach ($this->pixels as $pixel) {
            $pixel->setBrightness($brightness);
        }
    }

    public function getPixel(int $pixel): Pixel
    {
        Assert::greaterThanEq($pixel, 0, 'Pixel should be between 0 and 7');
        Assert::lessThanEq($pixel, 7, 'Pixel should be between 0 and 7');

        return $this->pixels[$pixel];
    }

    public function setPixel(int $pixel, int $red, int $green, int $blue, float $brightness = null): void
    {
        Assert::greaterThanEq($pixel, 0, 'Pixel should be between 0 and 7');
        Assert::lessThanEq($pixel, 7, 'Pixel should be between 0 and 7');

        Assert::greaterThanEq($red, 0, 'Red should be between 0 and 255');
        Assert::lessThanEq($red, 255, 'Red should be between 0 and 255');

        Assert::greaterThanEq($green, 0, 'Green should be between 0 and 255');
        Assert::lessThanEq($green, 255, 'Green should be between 0 and 255');

        Assert::greaterThanEq($blue, 0, 'Blue should be between 0 and 255');
        Assert::lessThanEq($blue, 255, 'Blue should be between 0 and 255');

        if (null !== $brightness) {
            Assert::greaterThanEq($brightness, 0.0, 'Brightness should be between 0.0 and 1.0');
            Assert::lessThanEq($brightness, 1.0, 'Brightness should be between 0.0 and 1.0');
        }

        $this->pixels[$pixel]
            ->setRed($red)
            ->setGreen($green)
            ->setBlue($blue);

        if (null !== $brightness) {
            $this->pixels[$pixel]->setBrightness($brightness);
        }
    }

    public function setPixels(int $red, int $green, int $blue, float $brightness = null): void
    {
        Assert::greaterThanEq($red, 0, 'Red should be between 0 and 255');
        Assert::lessThanEq($red, 255, 'Red should be between 0 and 255');

        Assert::greaterThanEq($green, 0, 'Green should be between 0 and 255');
        Assert::lessThanEq($green, 255, 'Green should be between 0 and 255');

        Assert::greaterThanEq($blue, 0, 'Blue should be between 0 and 255');
        Assert::lessThanEq($blue, 255, 'Blue should be between 0 and 255');

        if (null !== $brightness) {
            Assert::greaterThanEq($brightness, 0.0, 'Brightness should be between 0.0 and 1.0');
            Assert::lessThanEq($brightness, 1.0, 'Brightness should be between 0.0 and 1.0');
        }

        for ($p = 0; $p < self::NUM_PIXELS; ++$p) {
            $this->setPixel($p, $red, $green, $blue, $brightness);
        }
    }

    private function write(int $byte): void
    {
        for ($i = 0; $i < self::NUM_PIXELS; ++$i) {
            $this->dataPin->setValue($byte & self::BIT_MASK);
            $this->clockPin->setValue(1);
            $this->clockPin->setValue(0);
            $byte = $byte << 1;
        }
    }

    private function eof(): void
    {
        $this->dataPin->setValue(0);
        for ($i = 0; $i < 36; ++$i) {
            $this->clockPin->setValue(1);
            $this->clockPin->setValue(0);
        }
    }

    private function sof(): void
    {
        $this->dataPin->setValue(0);
        for ($i = 0; $i < 32; ++$i) {
            $this->clockPin->setValue(1);
            $this->clockPin->setValue(0);
        }
    }

    /**
     * Output the buffer to Blinkt!.
     */
    public function show(): void
    {
        if (!$this->isGpioSetup) {
            $this->setup();
        }

        $this->sof();

        foreach ($this->pixels as $pixel) {
            $this->writePixel($pixel);
        }

        $this->eof();
    }

    private function writePixel(Pixel $pixel): void
    {
        $this->write(self::SIGNIFICANT_BIT_MASK | $pixel->getBrightness());
        $this->write($pixel->getBlue());
        $this->write($pixel->getGreen());
        $this->write($pixel->getRed());
    }
}
