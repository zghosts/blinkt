<?php

declare(strict_types=1);

namespace Zghosts\Blinkt\Test\GPIO;

use PiPHP\GPIO\Pin\OutputPinInterface;

class RecordingOutputPin implements OutputPinInterface
{
    /**
     * @var int
     */
    private $number;

    /**
     * @var array|int[]
     */
    private $recordedValues = [];

    public function __construct(int $number)
    {
        $this->number = $number;
    }

    public function setValue($value): void
    {
        $this->recordedValues[] = $value;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function export(): void
    {
        //not implemented
    }

    public function unexport(): void
    {
        //not implemented
    }

    public function getValue(): int
    {
        return (int)current($this->recordedValues);
    }

    /**
     * @return array|int[]
     */
    public function getRecording(): array
    {
        return $this->recordedValues;
    }
}
