<?php

/**
 * Committer.php — Git Stage, Commit & Push (write side)
 */
class Committer
{
    private GitRunner $git;
    private array     $config;

    public function __construct(GitRunner $git, array $config)
    {
        $this->git    = $git;
        $this->config = $config;
    }

    /**
     * Configure identity, stage files, commit, push.
     *
     * @param  string[] $files    Relative paths to stage
     * @param  string   $message  The commit message
     * @return bool     true if a commit was pushed, false if nothing changed
     */
    public function commitAndPush(array $files, string $message): bool
    {
        // Step 1 — identity (runners are ephemeral, so set it every run).
        $this->git->run('config user.name '  . escapeshellarg($this->config['git_name']));
        $this->git->run('config user.email ' . escapeshellarg($this->config['git_email']));

        echo "[committer] Git identity set: {$this->config['git_name']} <{$this->config['git_email']}>\n";

        // Step 2 — stage only the files this script generated.
        foreach ($files as $file) {
            $result = $this->git->run('add ' . escapeshellarg($file));

            if ($result['code'] !== 0) {
                echo "[committer] WARNING: Could not stage {$file} — {$result['stderr']}\n";
            } else {
                echo "[committer] Staged: {$file}\n";
            }
        }

        // Step 3 — anything staged? (exit code 0 = nothing staged)
        if ($this->git->run('diff --cached --quiet')['code'] === 0) {
            echo "[committer] Nothing new to commit — working tree clean.\n";
            return false;
        }

        $commitResult = $this->git->run('commit -m ' . escapeshellarg($message));

        if ($commitResult['code'] !== 0) {
            echo "[committer] Commit failed:\n{$commitResult['stderr']}\n";
            exit(1);
        }

        echo "[committer] Committed: {$message}\n";

        // Step 4 — push; if the remote moved (you pushed manually between
        // checkout and now), rebase on top of it once and retry.
        $pushResult = $this->git->run('push');

        if ($pushResult['code'] !== 0) {
            echo "[committer] Push rejected, rebasing and retrying...\n";

            $pull = $this->git->run('pull --rebase');
            if ($pull['code'] !== 0) {
                echo "[committer] Rebase failed:\n{$pull['stderr']}\n";
                $this->git->run('rebase --abort');
                exit(1);
            }

            $pushResult = $this->git->run('push');
        }

        if ($pushResult['code'] !== 0) {
            echo "[committer] Push failed:\n{$pushResult['stderr']}\n";
            exit(1);
        }

        echo "[committer] Pushed to remote ✓\n";
        return true;
    }
}
