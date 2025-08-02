<?php

namespace App\Common\Domain;

/**
 * TimeRange Value Object
 * Represents a time range with start and end times
 */
class TimeRange
{
    /**
     * @var string Start time in format HH:MM
     */
    private $start;

    /**
     * @var string End time in format HH:MM
     */
    private $end;

    /**
     * @param string $start Start time in format HH:MM
     * @param string $end End time in format HH:MM
     */
    public function __construct($start, $end)
    {
        $this->validateTimeFormat($start);
        $this->validateTimeFormat($end);
        $this->validateTimeRange($start, $end);

        $this->start = $start;
        $this->end = $end;
    }

    /**
     * Get the start time
     *
     * @return string
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * Get the end time
     *
     * @return string
     */
    public function getEnd()
    {
        return $this->end;
    }

    /**
     * Get the duration in minutes
     *
     * @return int
     */
    public function getDurationInMinutes()
    {
        $startDateTime = \DateTime::createFromFormat('H:i', $this->start);
        $endDateTime = \DateTime::createFromFormat('H:i', $this->end);

        $diff = $endDateTime->diff($startDateTime);
        return ($diff->h * 60) + $diff->i;
    }

    /**
     * Validate time format
     *
     * @param string $time
     * @throws \InvalidArgumentException
     */
    private function validateTimeFormat($time)
    {
        if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $time)) {
            throw new \InvalidArgumentException("Invalid time format: $time. Expected format: HH:MM");
        }
    }

    /**
     * Validate time range
     *
     * @param string $start
     * @param string $end
     * @throws \InvalidArgumentException
     */
    private function validateTimeRange($start, $end)
    {
        $startDateTime = \DateTime::createFromFormat('H:i', $start);
        $endDateTime = \DateTime::createFromFormat('H:i', $end);

        if ($startDateTime >= $endDateTime) {
            throw new \InvalidArgumentException("End time must be after start time");
        }
    }

    /**
     * Convert to array
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'start' => $this->start,
            'end' => $this->end
        ];
    }

    /**
     * Create from array
     *
     * @param array $data
     * @return self
     */
    public static function fromArray($data)
    {
        return new self($data['start'], $data['end']);
    }
}
