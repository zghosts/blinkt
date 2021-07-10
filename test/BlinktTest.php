<?php

declare(strict_types=1);

namespace Zghosts\Blinkt\Test;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use PiPHP\GPIO\GPIOInterface;
use PiPHP\GPIO\Pin\OutputPinInterface;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Zghosts\Blinkt\Blinkt;
use Zghosts\Blinkt\Pixel;
use Zghosts\Blinkt\Test\GPIO\RecordingOutputPin;

class BlinktTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function brightnessIsSetOnAllPixels(): void
    {
        $gpio   = $this->prophesize(GPIOInterface::class);
        $blinkt = new Blinkt($gpio->reveal());

        $blinkt->setBrightness(0.5);
        foreach ($blinkt->getPixels() as $pixel) {
            $this->assertEquals(15, $pixel->getBrightness());
        }
    }

    /**
     * @test
     */
    public function setInvalidBrightnessThrowsException(): void
    {
        $gpio   = $this->prophesize(GPIOInterface::class);
        $blinkt = new Blinkt($gpio->reveal());

        $this->expectException(InvalidArgumentException::class);
        $blinkt->setBrightness(1.1);
    }

    /**
     * @test
     */
    public function showOutputsTheCorrectValuesToTheOutputPins(): void
    {
        $gpio = $this->prophesize(GPIOInterface::class);

        $dataPin  = new RecordingOutputPin(Blinkt::PI_DAT);
        $clockPin = new RecordingOutputPin(Blinkt::PI_CLK);

        $gpio->getOutputPin(Blinkt::PI_DAT)->willReturn($dataPin)->shouldBeCalledOnce();
        $gpio->getOutputPin(Blinkt::PI_CLK)->willReturn($clockPin)->shouldBeCalledOnce();

        $blinkt = new Blinkt($gpio->reveal());

        $blinkt->setPixel(0, 255, 0, 0, 1);
        $blinkt->setPixel(1, 255, 0, 0, 1);
        $blinkt->setPixel(2, 255, 0, 0, 1);
        $blinkt->setPixel(3, 255, 0, 0, 1);
        $blinkt->setPixel(4, 255, 0, 0, 1);
        $blinkt->setPixel(5, 255, 0, 0, 1);
        $blinkt->setPixel(6, 255, 0, 0, 1);
        $blinkt->setPixel(7, 255, 0, 0, 1);

        $blinkt->show();

        $dataOutput   = [];
        $dataOutput[] = 0;

        foreach ($blinkt->getPixels() as $pixel) {
            $value = 0xE0 | $pixel->getBrightness();
            for ($q = 0; $q < 8; ++$q) {
                $dataOutput[] = $value & 0x80;
                $value        = $value << 1;
            }
            $value = $pixel->getBlue();
            for ($q = 0; $q < 8; ++$q) {
                $dataOutput[] = $value & 0x80;
                $value        = $value << 1;
            }
            $value = $pixel->getGreen();
            for ($q = 0; $q < 8; ++$q) {
                $dataOutput[] = $value & 0x80;
                $value        = $value << 1;
            }
            $value = $pixel->getRed();
            for ($q = 0; $q < 8; ++$q) {
                $dataOutput[] = $value & 0x80;
                $value        = $value << 1;
            }
        }

        $dataOutput[] = 0;

        $clockOutput = $this->getClockOutput();

        $this->assertEquals(
            $clockOutput,
            $clockPin->getRecording()
        );

        $this->assertEquals(
            $dataOutput,
            $dataPin->getRecording()
        );
    }

    /**
     * @test
     * @dataProvider invalidValuesProvider
     */
    public function settingInvalidValuesOnAllPixelsThrowsException(int $red, int $green, int $blue, float $brightness, ?string $exception): void
    {
        $gpio   = $this->prophesize(GPIOInterface::class);
        $blinkt = new Blinkt($gpio->reveal());

        if (null !== $exception) {
            $this->expectException($exception);
        }

        $blinkt->setPixels($red, $green, $blue, $brightness);
    }

    /**
     * @test
     * @dataProvider invalidValuesProvider
     */
    public function settingInvalidValuesPixelsThrowsException(int $red, int $green, int $blue, float $brightness, ?string $exception): void
    {
        $gpio   = $this->prophesize(GPIOInterface::class);
        $blinkt = new Blinkt($gpio->reveal());

        if (null !== $exception) {
            $this->expectException($exception);
        }

        $blinkt->setPixel(0, $red, $green, $blue, $brightness);
    }

    /**
     * @test
     */
    public function settingInvalidPixelThrowsException(): void
    {
        $gpio   = $this->prophesize(GPIOInterface::class);
        $blinkt = new Blinkt($gpio->reveal());

        $this->expectException(InvalidArgumentException::class);

        $blinkt->setPixel(-1, 255, 255, 255, 0.1);
    }

    /**
     * @test
     */
    public function allPixelsAreUpdated(): void
    {
        $gpio   = $this->prophesize(GPIOInterface::class);
        $blinkt = new Blinkt($gpio->reveal());

        $blinkt->setPixels(250, 251, 252, 0.1);
        foreach ($blinkt->getPixels() as $pixel) {
            $this->assertEquals(250, $pixel->getRed());
            $this->assertEquals(251, $pixel->getGreen());
            $this->assertEquals(252, $pixel->getBlue());
            $this->assertEquals(0.1, $pixel->getBrightnessValue());
        }
    }

    /**
     * @test
     * @dataProvider invalidPinValuesProvider
     */
    public function setupWithInvalidPinsThrowsException(int $dat, int $clk, ?string $expectedException): void
    {
        $gpio = $this->prophesize(GPIOInterface::class);

        $gpio->getOutputPin($dat)->shouldNotBeCalled();
        $gpio->getOutputPin($clk)->shouldNotBeCalled();

        $blinkt = new Blinkt($gpio->reveal());

        if (null !== $expectedException) {
            $this->expectException($expectedException);
        }

        $blinkt->setup($dat, $clk);
    }

    /**
     * @test
     * @dataProvider validPinValuesProvider
     */
    public function setupValidPinsSucceeds(int $dat, int $clk): void
    {
        $gpio = $this->prophesize(GPIOInterface::class);

        $gpio->getOutputPin($dat)->shouldBeCalledOnce();
        $gpio->getOutputPin($clk)->shouldBeCalledOnce();

        $blinkt = new Blinkt($gpio->reveal());
        $blinkt->setup($dat, $clk);
    }

    /**
     * @test
     */
    public function setupValidDefaultsSucceeds(): void
    {
        $gpio = $this->prophesize(GPIOInterface::class);

        $gpio->getOutputPin(BLINKT::PI_DAT)->shouldBeCalledOnce();
        $gpio->getOutputPin(BLINKT::PI_CLK)->shouldBeCalledOnce();

        $blinkt = new Blinkt($gpio->reveal());
        $blinkt->setup();
    }

    /**
     * @test
     */
    public function pixelsCanBeCleared(): void
    {
        $gpio = $this->prophesize(GPIOInterface::class);

        $blinkt = new Blinkt($gpio->reveal());
        $blinkt->clear();

        $blinkt->setPixels(255, 255, 255, 1.0);
        $blinkt->clear();

        foreach ($blinkt->getPixels() as $pixel) {
            $this->assertEquals(0, $pixel->getRed());
            $this->assertEquals(0, $pixel->getGreen());
            $this->assertEquals(0, $pixel->getBlue());
        }
    }

    /**
     * @test
     */
    public function blinktIsNotClearedOnExit(): void
    {
        $gpio = $this->prophesize(GPIOInterface::class);

        $gpio->getOutputPin(BLINKT::PI_DAT)->shouldNotBeCalled();
        $gpio->getOutputPin(BLINKT::PI_CLK)->shouldNotBeCalled();

        $blinkt = new Blinkt($gpio->reveal());
    }

    /**
     * @test
     */
    public function blinktIsClearedOnExit(): void
    {
        $gpio = $this->prophesize(GPIOInterface::class);

        $datPin = $this->prophesize(OutputPinInterface::class);
        $clkPin = $this->prophesize(OutputPinInterface::class);

        $datPin->setValue(Argument::any())->shouldBeCalledTimes(258);
        $clkPin->setValue(Argument::any())->shouldBeCalledTimes(648);

        $gpio->getOutputPin(BLINKT::PI_DAT)->willReturn($datPin->reveal())->shouldBeCalledOnce();
        $gpio->getOutputPin(BLINKT::PI_CLK)->willReturn($clkPin->reveal())->shouldBeCalledOnce();

        $blinkt = new Blinkt($gpio->reveal());
        $blinkt->setClearOnExit();
    }

    /**
     * @test
     */
    public function selectingInvalidPixelThrowsException(): void
    {
        $gpio   = $this->prophesize(GPIOInterface::class);
        $blinkt = new Blinkt($gpio->reveal());

        $this->expectException(InvalidArgumentException::class);
        $blinkt->getPixel(8);
    }

    /**
     * @test
     */
    public function canSelectingValidPixel(): void
    {
        $gpio   = $this->prophesize(GPIOInterface::class);
        $blinkt = new Blinkt($gpio->reveal());

        $pixel = $blinkt->getPixel(7);
        $this->assertInstanceOf(Pixel::class, $pixel);
    }

    /**
     * @return array<int,array<int, int>>
     */
    public function validPinValuesProvider(): array
    {
        return [
            [1, 27],
            [2, 26],
            [3, 25],
            [4, 24],
            [5, 23],
            [6, 22],
            [7, 21],
            [8, 20],
            [9, 19],
            [10, 18],
            [11, 17],
            [12, 16],
            [13, 15],
            [14, 1],
            [1, 14],
            [15, 13],
            [16, 12],
            [17, 11],
            [18, 10],
            [19, 9],
            [20, 8],
            [21, 7],
            [22, 6],
            [23, 5],
            [24, 4],
            [25, 3],
            [26, 2],
            [27, 1],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function invalidPinValuesProvider(): array
    {
        return [
            'DataPin < 1'              => [0, 1, InvalidArgumentException::class],
            'DataPin > 199'            => [200, 1, InvalidArgumentException::class],
            'ClockPin < 1'             => [1, 0, InvalidArgumentException::class],
            'ClockPin > 199'           => [1, 200, InvalidArgumentException::class],
            'Clock and Data are Equal' => [1, 1, InvalidArgumentException::class],
        ];
    }

    /**
     * @return array|int[]
     */
    private function getClockOutput(): array
    {
        $clockOutput = [];

        for ($i = 0; $i < 32; ++$i) {
            $clockOutput[] = 1;
            $clockOutput[] = 0;
        }

        for ($a = 0; $a < 8; ++$a) {
            for ($b = 0; $b < 4; ++$b) {
                for ($p = 0; $p < 8; ++$p) {
                    $clockOutput[] = 1;
                    $clockOutput[] = 0;
                }
            }
        }

        for ($i = 0; $i < 36; ++$i) {
            $clockOutput[] = 1;
            $clockOutput[] = 0;
        }

        return $clockOutput;
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function invalidValuesProvider(): array
    {
        return [
            'Invalid red > 255'        => [256, 255, 255, 0.1, InvalidArgumentException::class],
            'Invalid red < 0'          => [-1, 255, 255, 0.1, InvalidArgumentException::class],
            'Invalid green > 255'      => [255, 256, 255, 0.1, InvalidArgumentException::class],
            'Invalid green < 0'        => [255, -1, 255, 0.1, InvalidArgumentException::class],
            'Invalid blue > 255'       => [255, 255, 256, 0.1, InvalidArgumentException::class],
            'Invalid blue < 0'         => [255, 255, -1, 0.1, InvalidArgumentException::class],
            'Invalid brightness > 1'   => [255, 255, 255, 1.1, InvalidArgumentException::class],
            'Invalid brightness < 0'   => [255, 255, 255, -0.1, InvalidArgumentException::class],
        ];
    }
}
