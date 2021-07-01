<?php

declare(strict_types=1);

namespace Zghosts\Blinkt;

use InvalidArgumentException;
use PiPHP\GPIO\GPIOInterface;
use PiPHP\GPIO\Pin\OutputPinInterface;
use Zghosts\Blinkt\Exception\InvalidBrightnessLevelException;
use Zghosts\Blinkt\Exception\InvalidColorValueException;
use Zghosts\Blinkt\Exception\InvalidDataPinException;
use Zghosts\Blinkt\Exception\InvalidGpioPinsException;

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
        if ($dat < 1 || $dat > 27) {
            throw new InvalidDataPinException(sprintf('DAT pin must be between 1 and 27 (default is %d)', self::DAT));
        }

        if ($clk < 1 || $clk > 27) {
            throw new InvalidBrightnessLevelException(sprintf('CLK pin must be between 1 and 27 (default is %d)', self::CLK));
        }

        if ($dat === $clk) {
            throw new InvalidGpioPinsException('DAT-pin and CLK-pin cannot be the same');
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
        if ($brightness < 0 || $brightness > 1) {
            throw new InvalidBrightnessLevelException('Brightness should be between 0.0 and 1.0');
        }

        foreach ($this->pixels as $pixel) {
            $pixel->setBrightness($brightness);
        }
    }

    public function getPixel(int $pixel): Pixel
    {
        if ($pixel < 0 || $pixel > 7) {
            throw new InvalidArgumentException('Pixel should be between 0 and 7');
        }

        return $this->pixels[$pixel];
    }

    public function setPixel(int $pixel, int $red, int $green, int $blue, float $brightness = null): void
    {
        if ($pixel < 0 || $pixel > 7) {
            throw new InvalidArgumentException('Pixel should be between 0 and 7');
        }

        if ($red < 0 || $red > 255) {
            throw new InvalidColorValueException('Pixel should be between 0 and 255');
        }

        if ($green < 0 || $green > 255) {
            throw new InvalidColorValueException('Pixel should be between 0 and 255');
        }

        if ($blue < 0 || $blue > 255) {
            throw new InvalidColorValueException('Pixel should be between 0 and 255');
        }

        if (null !== $brightness) {
            if ($brightness < 0.0 || $brightness > 1.0) {
                throw new InvalidBrightnessLevelException('Pixel should be between 0.0 and 1.0');
            }
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
        if ($red < 0 || $red > 255) {
            throw new InvalidColorValueException('Pixel should be between 0 and 255');
        }

        if ($green < 0 || $green > 255) {
            throw new InvalidColorValueException('Pixel should be between 0 and 255');
        }

        if ($blue < 0 || $blue > 255) {
            throw new InvalidColorValueException('Pixel should be between 0 and 255');
        }

        if (null !== $brightness) {
            if ($brightness < 0.0 || $brightness > 1.0) {
                throw new InvalidBrightnessLevelException('Brightness should be between 0.0 and 1.0');
            }
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
            $byte = $byte << 1;
            $this->clockPin->setValue(0);
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
            $this->write(self::SIGNIFICANT_BIT_MASK | $pixel->getBrightness());
            $this->write($pixel->getBlue());
            $this->write($pixel->getGreen());
            $this->write($pixel->getRed());
        }

        $this->eof();
    }
}
