<?php

/**
 * config.php — Central Configuration File
 *
 * Values come from environment variables first (set in GitHub Actions →
 * Variables), then fall back to the defaults below. Empty-string env vars
 * (what GitHub gives you for an unset variable) also fall back, because
 * of the `?:` operator.
 *
 * NOTE: the git identity uses LOG_GIT_NAME / LOG_GIT_EMAIL on purpose.
 * Do NOT use GIT_AUTHOR_NAME / GIT_AUTHOR_EMAIL as env var names — git
 * itself reads those, and an empty value makes `git commit` fail with
 * "empty ident name not allowed".
 */

return [

    // ── Project identity ──────────────────────────────────────────────────────
    'project_name'    => getenv('PROJECT_NAME') ?: 'robotics',
    'github_username' => getenv('GITHUB_USERNAME') ?: 'BuildWithFindk',

    // "owner/repo" — GitHub Actions sets GITHUB_REPOSITORY automatically.
    'repository'      => getenv('GITHUB_REPOSITORY') ?: 'BuildWithFindk/robotics',

    // ── Git committer identity ────────────────────────────────────────────────
    'git_name'        => getenv('LOG_GIT_NAME')  ?: 'BuildWithFindk',
    'git_email'       => getenv('LOG_GIT_EMAIL') ?: 'rawalroshan950@gmail.com',

    // ── Paths ─────────────────────────────────────────────────────────────────
    'log_dir'         => __DIR__ . '/logs',
    'changelog_path'  => __DIR__ . '/CHANGELOG.md',

    // ── Timezone ──────────────────────────────────────────────────────────────
    'timezone'        => getenv('TZ') ?: 'Asia/Kathmandu',

    // ── Stats window ─────────────────────────────────────────────────────────
    // "midnight" = since 00:00 of the current day (in the timezone above).
    'stats_since'     => 'midnight',

];
