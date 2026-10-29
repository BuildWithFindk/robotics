<?php

/**
 * LogEntry.php — Value Object (immutable data for one day's log)
 */
class LogEntry
{
    public function __construct(
        public readonly string $date,
        public readonly string $dayOfWeek,
        public readonly string $timestamp,
        public readonly string $projectName,
        public readonly string $branch,
        public readonly array  $commits,
        public readonly int    $filesChanged,
        public readonly int    $insertions,
        public readonly int    $deletions,
        public readonly string $topContributor,
        public readonly int    $totalFiles,
    ) {}

    public function commitCount(): int
    {
        return count($this->commits);
    }

    public function activityLevel(): string
    {
        return match (true) {
            $this->commitCount() === 0 => '😴 Quiet day',
            $this->commitCount() <= 2  => '🌱 Light',
            $this->commitCount() <= 5  => '🔥 Active',
            $this->commitCount() <= 10 => '⚡ High output',
            default                    => '🚀 Shipping hard',
        };
    }

    public function netLines(): int
    {
        return $this->insertions - $this->deletions;
    }
}
