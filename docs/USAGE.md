# App Repo — Usage & Setup Guide

This is the **App repo** (`lms-laravel-back`) half of a two-repo GitOps setup. It holds the
application code, the container image build, and the CI/CD that builds, scans,
and publishes the image, then bumps the image tag in the **Config repo**
(`lms-k8s`), which ArgoCD watches and deploys.

> The Config repo owns the Helm chart, environment values, platform components,
> ArgoCD, sealed secrets, and the backup/restore/rollback runbooks. Cross-repo
> topics below link to what the Config repo must provide.

> **Naming.** The **internal application identity is `app-back`** — the container
> image name (`registry.lms.local/app-back`) and the app label/release name the
> Config repo chart keys on. This is distinct from the **git repo** name
> (`lms-laravel-back`); the repo can be renamed without changing the running
> app's identity.

---

## 1. What was generated and how it fits together

```
docker/
  Dockerfile          multi-stage, hardened; ONE image, THREE roles
  entrypoint.sh       prepares writable dirs + rebuilds caches; NO migrations
  php/                php.ini, opcache.ini, www.conf (php-fpm pool)
  nginx/              nginx.conf + default.conf for the nginx sidecar
.github/workflows/
  ci.yml              test, code style, dependency audit (self-hosted)
  build-and-publish.yml  build -> Trivy scan -> push -> bump dev tag in config repo
  promote-prod.yml    gated dev->prod promotion via PR (no rebuild)
routes/web.php        + lightweight /up health route (added; see §7)
.dockerignore         keeps secrets/dev artifacts out of the build context
docs/                 this file + SECURITY-REVIEW.md
```

**Image model.** A single image is built and runs in three roles, selected by
the container command in the chart:

| Role      | Command (set by the chart)                                  |
|-----------|-------------------------------------------------------------|
| web       | `php-fpm -F` (default) — paired with an nginx sidecar       |
| worker    | `php artisan queue:work --max-time=3600 --max-jobs=1000 --tries=3` |
| scheduler | `php artisan schedule:work` (or a minutely CronJob)         |

**Request path (web).** Traefik (TLS) → nginx sidecar (`:8080`) → serves
`public/build/*` static assets directly; forwards everything else (`/api`,
`/up`, SPA routes) to php-fpm (`127.0.0.1:9000`) → Laravel.

**Deploy path.** Merge to `dev` → `build-and-publish.yml` builds + scans +
pushes `registry.lms.local/app-back:<git-sha>` → commits that tag into the config
repo's `envs/dev/values.yaml` → ArgoCD (dev auto-sync) rolls it out.

### Reality vs. the original brief (deviations — read this)

This repo is **Laravel 10.37**, not Laravel 12, so the infra was adapted:

- **`/up` did not exist** (built-in only from Laravel 11). A minimal `/up` route
  was added to `routes/web.php` above the SPA catch-all — the single justified
  app change. See §7.
- **The frontend is a Blade-served SPA** built by `laravel-vite-plugin` into
  `public/build/` (there is **no standalone `dist/`**). nginx therefore serves
  Laravel's `public/` and routes dynamic requests to php-fpm — the standard
  Laravel model, not a separate static SPA container.
- **Tests are PHPUnit + Pint**, not Pest/Larastan. `ci.yml` runs `php artisan
  test` and `pint --test`. Larastan/PHPStan is not installed; add it later if
  wanted.
- **`route:cache` is skipped** at boot because the app uses closure routes
  (which Laravel cannot serialize). Convert them to invokable controllers to
  enable it.

---

## 2. Prerequisites (self-hosted runner + clients)

Because the base domain is **`lms.local`** with a **private CA** (Let's Encrypt
cannot issue for `.local`), every machine that talks to the cluster over TLS
must:

1. **Trust the private CA.** Obtain `ca.crt` from the Config repo bootstrap and
   install it:
   ```bash
   sudo cp ca.crt /usr/local/share/ca-certificates/lms-local-ca.crt
   sudo update-ca-certificates                 # Debian/Ubuntu
   # Also add it to Docker's trust so pushes to registry.lms.local succeed:
   sudo mkdir -p /etc/docker/certs.d/registry.lms.local
   sudo cp ca.crt /etc/docker/certs.d/registry.lms.local/ca.crt
   sudo systemctl restart docker
   ```
2. **Resolve `*.lms.local`** to the Traefik LoadBalancer IP — local DNS, or
   `/etc/hosts`:
   ```
   <TRAEFIK_LB_IP>  app.lms.local dev.lms.local registry.lms.local grafana.lms.local argocd.lms.local
   ```

The self-hosted runner additionally needs on `PATH`:
`docker` (buildx), `trivy`, `git`, `yq`, `gh`, plus **PHP 8.3 + Composer** and
**Node 22** for `ci.yml`.

---

## 3. Credentials & secrets (what, how to create, where it goes)

**No plaintext secrets live in this repo.** GitHub secrets/vars are configured
in the App repo settings; cluster secrets live in the Config repo as
SealedSecrets. Use placeholders below — never commit real values.

### 3.1 Owned by / configured in THIS repo (GitHub Actions)

| Name | Type | Used by | How to create |
|---|---|---|---|
| `REGISTRY_USERNAME` / `REGISTRY_PASSWORD` | secret | `build-and-publish.yml` (docker login → **push**) | Create a push user in the registry's htpasswd (Config repo): `htpasswd -nbB ci '<password>'`. Store user + password as GitHub secrets. |
| `CONFIG_REPO_DEPLOY_KEY` | secret | `build-and-publish.yml`, `promote-prod.yml` | SSH **write** deploy key for the config repo (see §3.3). Paste the **private** key. |
| `CONFIG_REPO_TOKEN` | secret | `promote-prod.yml` (`gh pr create`) | Fine-grained PAT / GitHub App token scoped to the config repo with **Pull requests: write**. Nothing else. |
| `CONFIG_REPO_SSH` | **variable** | both publish workflows | e.g. `git@github.com:zhuravlenko2555dev/lms-k8s.git` |
| `CONFIG_REPO_SLUG` | **variable** | `promote-prod.yml` | e.g. `zhuravlenko2555dev/lms-k8s` |

### 3.2 Consumed by the cluster (created in the Config repo as SealedSecrets)

Referenced by the chart, not by this repo — listed so you know the full set:
`APP_KEY`, DB user/password, Redis password, SeaweedFS S3 access/secret keys,
mail creds, and the registry **pull** secret (`imagePullSecret`). See the
Config repo's `secrets/README.md` and `docs/USAGE.md`.

- `APP_KEY`: generate with `php artisan key:generate --show` and seal it.
- Registry **pull** secret (cluster side, distinct from CI push creds):
  ```bash
  kubectl create secret docker-registry regcred \
    --docker-server=registry.lms.local \
    --docker-username='<pull-user>' \
    --docker-password='<pull-password>' \
    --namespace app-dev --dry-run=client -o yaml \
    | kubeseal --format yaml > secrets/app-dev/regcred.sealed.yaml
  ```

### 3.3 App → Config **write deploy key** (create once)

```bash
ssh-keygen -t ed25519 -N '' -f deploykey -C 'app-ci-to-config'
# Public key  -> config repo: Settings → Deploy keys → Add, ALLOW WRITE ACCESS.
# Private key -> this repo:    Settings → Secrets → Actions → CONFIG_REPO_DEPLOY_KEY.
shred -u deploykey deploykey.pub
```

> Least privilege: this key writes **only** to the config repo. ArgoCD uses a
> **separate read-only** repo credential (configured in the Config repo).

---

## 4. Everyday workflows

### 4.1 CI (every PR / push to `dev`|`master`)
`ci.yml` runs automatically: Composer install, `pint --test`, `php artisan test`
(against an ephemeral MySQL service), `composer audit`; and for the frontend:
`npm ci`, ESLint, `vite build` smoke check, `npm audit`.

### 4.2 Deploy to dev
Merge to `dev`. `build-and-publish.yml` builds → **Trivy fails the run on
HIGH/CRITICAL** → pushes `registry.lms.local/app-back:<git-sha>` → bumps
`envs/dev/values.yaml` in the config repo. ArgoCD (dev auto-sync) deploys.
Migrations run via the chart's guarded pre-upgrade Job — **not** here.

### 4.3 Promote to prod (no rebuild)
Run **Actions → Promote to prod → Run workflow**, entering the **git-sha tag
currently validated in dev** (or publish a release). It opens a **PR** against
the config repo bumping `envs/prod/values.yaml`. A human reviews/merges; ArgoCD
(prod = manual/PR-gated) syncs. The exact same, already-scanned image is
promoted — never rebuilt.

### 4.4 Rollback, backup, restore, migration rollback, dashboards
These live in the **Config repo** (`make rollback`, `make backup`,
`make restore`, `make migrate-rollback`, Grafana at `grafana.lms.local`).
Summary of the safe deploy rollback: revert the tag commit in
`envs/<env>/values.yaml` (ArgoCD re-syncs) or use ArgoCD's rollback to a prior
synced revision. Migration rollback is a **manual, guarded Job** — never
automatic, and potentially destructive; back up first.

---

## 5. nginx sidecar contract (for the Config repo chart)

The runtime image is php-fpm only; nginx runs as a **stock `nginx:1.27-alpine`
sidecar** in the same Pod. The image ships the sidecar config at
`/var/www/nginx/` and the built assets at `/var/www/html/public/`. The chart
should:

1. Add an **initContainer** (using this app image) that copies
   `/var/www/html/public` and `/var/www/nginx` into a shared `emptyDir`.
2. Mount that emptyDir into the nginx sidecar: `nginx.conf` →
   `/etc/nginx/nginx.conf`, `default.conf` → `/etc/nginx/conf.d/default.conf`,
   `public/` → `/var/www/html/public`.
3. Give nginx a writable `/tmp` (`emptyDir`) so it runs read-only + non-root.
4. Point the Service/probes at nginx **`:8080`**, path **`/up`**.

(An equivalent alternative is delivering `nginx.conf`/`default.conf` via a
ConfigMap in the config repo; the files here are the source of truth either way.)

## 6. Writable paths under `readOnlyRootFilesystem`

The chart must mount `emptyDir`s (php-fpm container) at:
`storage/framework/cache`, `storage/framework/sessions`,
`storage/framework/views`, `storage/logs`, `storage/app`, and
`bootstrap/cache`. `entrypoint.sh` recreates their structure and rebuilds
caches on boot. Cache/session/queue use **Redis** in-cluster; logs go to stderr.

## 7. The `/up` route
`GET /up` returns `{"status":"ok"}` (HTTP 200) without touching the database —
suitable for liveness **and** readiness. If you later want readiness to gate on
DB/Redis, add a separate deeper endpoint; do **not** make `/up` depend on
downstreams (a DB blip would then kill otherwise-healthy pods).
