# Deployment

Production: **https://creativemark.anasfareed.com** on Hostinger (account `u109745148`, PHP 8.5, MariaDB 11.8).

## How a change reaches the site

`git push origin main` → GitHub Actions (`.github/workflows/deploy.yml`):

1. **test**: installs everything, type-checks the frontend and runs the whole PHP test suite. If anything fails, nothing is deployed.
2. **deploy**: builds the frontend, packs the tracked files plus `public/build`, uploads them and runs `deploy/remote-deploy.sh` on the server.

The server script unpacks a new release, links the shared `.env`, `storage` and dashboard uploads, runs
`composer install --no-dev`, `php artisan migrate --force` and `php artisan optimize`, then switches the `current`
symlink in one step, so visitors never see a half-deployed site. The last five releases are kept.

Repository secrets used by the workflow: `DEPLOY_SSH_KEY` (private key whose public half is in the server's SSH keys),
`DEPLOY_HOST`, `DEPLOY_PORT`, `DEPLOY_USER`, `DEPLOY_KNOWN_HOSTS` (output of `ssh-keyscan -p <port> <host>`).

## Server layout

```
~/apps/creativemark/
  releases/<time>-<commit>/        one folder per deploy
  current -> releases/<latest>     what the site runs
  shared/.env                      production settings (never in git)
  shared/storage/                  logs, sessions, cache, private files
  shared/media-uploads/            images uploaded in the dashboard
~/domains/creativemark.anasfareed.com/public_html -> ~/apps/creativemark/current/public
```

## Scheduled tasks

One Hostinger cron job, every minute: `php ~/apps/creativemark/current/artisan schedule:run`
(announces scheduled news when they go live, prunes the activity log after a year).

## Rollback

```bash
cd ~/apps/creativemark && ls releases && ln -sfn releases/<previous> current.next && mv -Tf current.next current
```

Migrations are additive; if a release changed the database, restore the backup Hostinger takes daily before rolling back.

## Database copy from the local machine (done once, 2026-11-11)

The local database was dumped with `mysqldump --single-transaction` (sessions, cache and job tables as empty
structure only) and imported into `u109745148_creativemark`. The production `.env` keeps the same `APP_KEY` as the
local one, so the encrypted two-step secrets keep working.
