<?php

/**
 * GitStats.php — Repository Metrics Collector (read-only)
 *
 * All "today" numbers (commits, files, insertions, deletions, top
 * contributor) now cover the SAME window — every real commit since
 * $since — instead of mixing "today" with "latest commit only".
 *
 * The logger's own output (logs/ and CHANGELOG.md) is excluded, so the
 * bot's 5 daily log commits never count as your work.
 */
class GitStats
{
    private GitRunner $git;
    private string    $since;
    private ?array    $diffCache = null;

    /** Paths written by the logger itself — ignored when counting activity. */
    private const EXCLUDED_PATHS = ['logs', 'CHANGELOG.md'];

    public function __construct(GitRunner $git, string $since = 'midnight')
    {
        $this->git   = $git;
        $this->since = $since;
    }

    /**
     * Real (non-merge, non-bot) commits since the window start.
     *
     * @return array<int, array{hash: string, message: string, author: string, time: string}>
     */
    public function getCommits(): array
    {
        $raw = $this->git->output(
            'log --since=' . escapeshellarg($this->since)
            . ' --no-merges --pretty=format:' . escapeshellarg('%h|||%s|||%an|||%ar')
            . ' ' . $this->pathspec()
        );

        if ($raw === '') {
            return [];
        }

        $commits = [];

        foreach (explode("\n", $raw) as $line) {
            $line = trim($line);
            if ($line === '') continue;

            $parts = explode('|||', $line, 4);

            if (count($parts) === 4) {
                $commits[] = [
                    'hash'    => $parts[0],
                    'message' => $parts[1],
                    'author'  => $parts[2],
                    'time'    => $parts[3],
                ];
            }
        }

        return $commits;
    }

    /** Unique files touched since the window start. */
    public function getFilesChangedToday(): int
    {
        return $this->diffStats()['files'];
    }

    /** Lines inserted since the window start. */
    public function getInsertions(): int
    {
        return $this->diffStats()['insertions'];
    }

    /** Lines deleted since the window start. */
    public function getDeletions(): int
    {
        return $this->diffStats()['deletions'];
    }

    /** Current branch name (falls back to the checked-out ref on detached HEAD). */
    public function getActiveBranch(): string
    {
        $branch = $this->git->output('rev-parse --abbrev-ref HEAD');
        return ($branch === '' || $branch === 'HEAD') ? 'main' : $branch;
    }

    /** Author with the most real commits since the window start. */
    public function getTopContributorToday(): string
    {
        $raw = $this->git->output(
            'log --since=' . escapeshellarg($this->since)
            . ' --no-merges --pretty=format:' . escapeshellarg('%an')
            . ' ' . $this->pathspec()
        );

        if ($raw === '') {
            return 'No commits yet';
        }

        $names  = array_filter(array_map('trim', explode("\n", $raw)));
        $counts = array_count_values($names);
        arsort($counts);

        return (string) (array_key_first($counts) ?? 'N/A');
    }

    /** Total files currently tracked by git (counted in PHP — no shell pipe). */
    public function getTotalTrackedFiles(): int
    {
        $raw = $this->git->output('ls-files');
        return $raw === '' ? 0 : count(explode("\n", $raw));
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /** Pathspec that excludes the logger's own files. */
    private function pathspec(): string
    {
        $parts = ['--', '.'];
        foreach (self::EXCLUDED_PATHS as $path) {
            $parts[] = escapeshellarg(':(exclude)' . $path);
        }
        return implode(' ', $parts);
    }

    /**
     * One `git log --numstat` call, summed up. Cached for the run.
     *
     * numstat lines look like "12<TAB>3<TAB>path/to/file"
     * (binary files show "-<TAB>-<TAB>path" and count as 0 lines).
     *
     * @return array{files: int, insertions: int, deletions: int}
     */
    private function diffStats(): array
    {
        if ($this->diffCache !== null) {
            return $this->diffCache;
        }

        $raw = $this->git->output(
            'log --since=' . escapeshellarg($this->since)
            . ' --no-merges --numstat --pretty=tformat: '
            . $this->pathspec()
        );

        $files = [];
        $ins   = 0;
        $del   = 0;

        foreach (explode("\n", $raw) as $line) {
            $line = trim($line);
            if ($line === '') continue;

            $cols = explode("\t", $line, 3);
            if (count($cols) !== 3) continue;

            [$a, $d, $path] = $cols;
            $files[$path] = true;
            $ins += ctype_digit($a) ? (int) $a : 0;
            $del += ctype_digit($d) ? (int) $d : 0;
        }

        return $this->diffCache = [
            'files'      => count($files),
            'insertions' => $ins,
            'deletions'  => $del,
        ];
    }
}
