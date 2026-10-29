<?php

/**
 * GitRunner.php — Git Shell Executor
 *
 * One job: run a git command and return structured output.
 * Callers are responsible for escaping any dynamic arguments
 * with escapeshellarg().
 */
class GitRunner
{
    private string $repoPath;

    public function __construct(string $repoPath)
    {
        $this->repoPath = rtrim($repoPath, '/');
    }

    /**
     * @param  string $subCommand  Everything after "git", e.g. "log --oneline"
     * @return array{code: int, stdout: string, stderr: string}
     */
    public function run(string $subCommand): array
    {
        $cmd = sprintf(
            'git -C %s %s 2>&1',
            escapeshellarg($this->repoPath),
            $subCommand
        );

        $lines    = [];
        $exitCode = 0;
        exec($cmd, $lines, $exitCode);

        $stdout = trim(implode("\n", $lines));

        return [
            'code'   => $exitCode,
            'stdout' => $stdout,
            'stderr' => $exitCode !== 0 ? $stdout : '',
        ];
    }

    /** Run a command and return stdout, or '' on failure (read-only queries). */
    public function output(string $subCommand): string
    {
        $result = $this->run($subCommand);
        return $result['code'] === 0 ? $result['stdout'] : '';
    }

    public function getRepoPath(): string
    {
        return $this->repoPath;
    }
}
