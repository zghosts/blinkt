<?php

declare(strict_types=1);

namespace Zghosts\Blinkt;

use Webmozart\Assert\Assert;

final class Pixel
{
    public const DEFAULT_BRIGHTNESS = 0.2;

    private const COLOR_MASK      = 0xFF;
    private const BRIGHTNESS_MASK = 0x1F;

    /**
     * @var int
     */
    private $red;

    /**
     * @var int
     */
    private $green;

    /**
     * @var int
     */
    private $blue;

    /**
     * @var float
     */
    private $brightness;

    public function __construct(int $red = 0, int $green = 0, int $blue = 0, float $brightness = 0.2)
    {
        $this->setRed($red);
        $this->setGreen($green);
        $this->setBlue($blue);
        $this->setBrightness($brightness);
    }

    public function setBrightness(float $brightness): self
    {
        Assert::greaterThanEq($brightness, 0.0, 'Brightness should be between 0.0 and 1.0');
        Assert::lessThanEq($brightness, 1.0, 'Brightness should be between 0.0 and 1.0');

        $this->brightness = $brightness;

        return $this;
    }

    public function setRed(int $red): self
    {
        Assert::greaterThanEq($red, 0, 'Red should be between 0 and 255');
        Assert::lessThanEq($red, 255, 'Red should be between 0 and 255');

        $this->red = $red;

        return $this;
    }

    public function setGreen(int $green): self
    {
        Assert::greaterThanEq($green, 0, 'Green should be between 0 and 255');
        Assert::lessThanEq($green, 255, 'Green should be between 0 and 255');

        $this->green = $green;

        return $this;
    }

    public function setBlue(int $blue): self
    {
        Assert::greaterThanEq($blue, 0, 'Blue should be between 0 and 255');
        Assert::lessThanEq($blue, 255, 'Blue should be between 0 and 255');

        $this->blue = $blue;

        return $this;
    }

    public function getRed(): int
    {
        return $this->red & self::COLOR_MASK;
    }

    public function getGreen(): int
    {
        return $this->green & self::COLOR_MASK;
    }

    public function getBlue(): int
    {
        return $this->blue & self::COLOR_MASK;
    }

    public function getBrightness(): int
    {
        return (int)(31.0 * $this->brightness) & self::BRIGHTNESS_MASK;
    }

    public function getBrightnessValue(): float
    {
        return $this->brightness;
    }

    public function setRGBB(int $red, int $green, int $blue, float $brightness): void
    {
        Assert::greaterThanEq($red, 0, 'Red should be between 0 and 255');
        Assert::lessThanEq($red, 255, 'Red should be between 0 and 255');

        Assert::greaterThanEq($green, 0, 'Green should be between 0 and 255');
        Assert::lessThanEq($green, 255, 'Green should be between 0 and 255');

        Assert::greaterThanEq($blue, 0, 'Blue should be between 0 and 255');
        Assert::lessThanEq($blue, 255, 'Blue should be between 0 and 255');

        Assert::greaterThanEq($brightness, 0.0, 'Brightness should be between 0.0 and 1.0');
        Assert::lessThanEq($brightness, 1.0, 'Brightness should be between 0.0 and 1.0');

        $this
            ->setRed($red)
            ->setGreen($green)
            ->setBlue($blue)
            ->setBrightness($brightness);
    }

    public function clear(): void
    {
        $this->red        = 0;
        $this->green      = 0;
        $this->blue       = 0;
    }
}
