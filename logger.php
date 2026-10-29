#!/usr/bin/env php
<?php

/**
 * logger.php — Entry Point
 *
 * The "composition root": wires the classes together and runs them.
 *
 * Usage:
 *   php logger.php
 *   TZ=Asia/Kathmandu php logger.php
 */

declare(strict_types=1);

require_once __DIR__ . '/src/GitRunner.php';
require_once __DIR__ . '/src/GitStats.php';
require_once __DIR__ . '/src/LogEntry.php';
require_once __DIR__ . '/src/LogWriter.php';
require_once __DIR__ . '/src/Committer.php';

$config = require __DIR__ . '/config.php';

// Set timezone before any date() call.
date_default_timezone_set($config['timezone']);

$today     = date('Y-m-d');
$timestamp = date('Y-m-d H:i:s');
$dayOfWeek = date('l');

echo "\n[logger] ─────────────────────────────────────────\n";
echo "[logger] auto-commit-logger · {$config['github_username']}\n";
echo "[logger] Date      : {$today}\n";
echo "[logger] Timezone  : {$config['timezone']}\n";
echo "[logger] Project   : {$config['project_name']}\n";
echo "[logger] ─────────────────────────────────────────\n\n";

$runner    = new GitRunner(__DIR__);
$stats     = new GitStats($runner, $config['stats_since']);
$writer    = new LogWriter($config);
$committer = new Committer($runner, $config);

echo "[logger] Collecting repo stats...\n";

$entry = new LogEntry(
    date:           $today,
    dayOfWeek:      $dayOfWeek,
    timestamp:      $timestamp,
    projectName:    $config['project_name'],
    branch:         $stats->getActiveBranch(),
    commits:        $stats->getCommits(),
    filesChanged:   $stats->getFilesChangedToday(),
    insertions:     $stats->getInsertions(),
    deletions:      $stats->getDeletions(),
    topContributor: $stats->getTopContributorToday(),
    totalFiles:     $stats->getTotalTrackedFiles(),
);

echo "[logger] Commits today  : {$entry->commitCount()}\n";
echo "[logger] Activity level : {$entry->activityLevel()}\n";
echo "[logger] Branch         : {$entry->branch}\n\n";

$dailyLogPath = $config['log_dir'] . "/{$today}.md";

$writer->writeDailyLog($dailyLogPath, $entry);
echo "[logger] Daily log written  → logs/{$today}.md\n";

$writer->updateChangelog($config['changelog_path'], $entry);
echo "[logger] Changelog updated  → CHANGELOG.md\n\n";

$commitMessage = sprintf(
    'chore(log): daily log %s %s [%d commits · %d files · +%d/-%d]',
    $today,
    date('H:i'),
    $entry->commitCount(),
    $entry->filesChanged,
    $entry->insertions,
    $entry->deletions
);

$committed = $committer->commitAndPush(
    files:   ["logs/{$today}.md", 'CHANGELOG.md'],
    message: $commitMessage
);

echo "\n[logger] ─────────────────────────────────────────\n";
echo $committed
    ? "[logger] ✓ Log committed and pushed successfully.\n"
    : "[logger] ✓ No changes pushed (clean tree).\n";
echo "[logger] ─────────────────────────────────────────\n\n";
