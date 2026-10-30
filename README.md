# auto-commit-logger

> A PHP + GitHub Actions project that generates a **real daily dev log** from your repository's actual git activity and commits it automatically **5 times a day**.

**Built by [BuildWithFindk](https://github.com/BuildWithFindk)**

---

## Schedule (Nepal time → GitHub cron, UTC)

| Run | Nepal time | UTC     | Cron line       |
|-----|-----------|---------|-----------------|
| 1   | 08:43 AM  | 02:58   | `'58 2 * * *'`  |
| 2   | 11:16 AM  | 05:31   | `'31 5 * * *'`  |
| 3   | 02:09 PM  | 08:24   | `'24 8 * * *'`  |
| 4   | 05:37 PM  | 11:52   | `'52 11 * * *'` |
| 5   | 10:52 PM  | 17:07   | `'7 17 * * *'`  |

Nepal is UTC+5:45. GitHub can start scheduled runs a few minutes late.

## How it behaves

- Each run rewrites `logs/YYYY-MM-DD.md` and updates **one row per date** in `CHANGELOG.md`.
- Stats (commits, files, lines, top contributor) cover **everything since midnight**, and ignore the logger's own commits (`logs/` and `CHANGELOG.md`).
- Each run creates one commit → 5 commits per day.

## Setup

1. Copy the files into your repo (keep the folder structure below).
2. **Settings → Actions → General → Workflow permissions → Read and write permissions ✓**
3. Test: **Actions → Daily Dev Log → Run workflow**

No PAT or SSH key needed — `GITHUB_TOKEN` is automatic.

### Optional repo variables

**Settings → Secrets and variables → Actions → Variables**

| Variable        | Default                      |
|-----------------|------------------------------|
| `PROJECT_NAME`  | `robotics`                   |
| `LOG_GIT_NAME`  | `BuildWithFindk`             |

| `TIMEZONE`      | `Asia/Kathmandu`             |

> Use `LOG_GIT_NAME` / `LOG_GIT_EMAIL`, **not** `GIT_AUTHOR_NAME` / `GIT_AUTHOR_EMAIL`. Git reads those itself, and an empty value breaks `git commit`.

For commits to appear on your contribution graph, the email must be added and verified in your GitHub account, and the commits must land on the default branch.

## Project structure

```
auto-commit-logger/
├── logger.php              ← entry point
├── config.php              ← all configuration
├── src/
│   ├── GitRunner.php       ← runs git commands
│   ├── GitStats.php        ← reads repo metrics
│   ├── LogEntry.php        ← immutable data for one day
│   ├── LogWriter.php       ← writes the markdown files
│   └── Committer.php       ← stage, commit, push
├── logs/
│   ├── .gitkeep
│   └── YYYY-MM-DD.md       ← generated
├── CHANGELOG.md            ← generated
└── .github/workflows/daily-log.yml
```

## Running locally

```bash
git config user.name "EnterYourName"
git config user.email "yourmail@gmail.com"
php logger.php
```

**Requirements:** PHP 8.1+ · Git

## Good to know

- GitHub may pause scheduled workflows after 60 days without repository activity. Check the Actions tab now and then.
- If you push manually at the same moment a run is pushing, the logger rebases once and retries.
