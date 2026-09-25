# Claude Code Prompt — Two-Repo GitOps CI/CD for a Laravel 12 API + Vue 3 SPA

> **Two repositories.** Run Claude Code **twice**: once in the **App repo**, once in the **Config
> repo**. Sections are tagged `[APP REPO]` or `[CONFIG REPO]`; shared sections apply to both — paste
> them in each run. **After finishing each repo you MUST complete the "Per-section completion
> protocol" (§0): write a usage/credentials guide, then run a security check and explain the
> posture.** Start every run with a plan; generate files component by component.

---

## 0. Per-section completion protocol (MANDATORY — applies to BOTH repos)

When you finish generating a repo's files, before stopping you must:

**A. Write a usage + setup guide** (`docs/USAGE.md` in that repo). It must cover, step by step:
- What was generated and how the pieces fit together.
- **Every credential/secret** needed, how to create it, and where it goes — e.g. app→config
  write deploy key, ArgoCD read-only repo cred, registry push creds (CI) and pull `imagePullSecret`
  (cluster), SeaweedFS S3 access keys, MySQL users, `APP_KEY`, Grafana admin, and the private CA
  trust/DNS setup for `*.lms.local`. Give exact commands (`kubeseal`, `kubectl create secret`,
  `htpasswd`, key generation) with placeholders, never real values.
- How to run the normal workflows: deploy, promote to prod, back up, restore/import a DB dump,
  roll back a deploy, roll back a migration, view dashboards.

**B. Run a security check of the generated files and write `docs/SECURITY-REVIEW.md`.**
- Where tooling is available, run it and include results: **hadolint** (Dockerfile), **Trivy**
  (image + `config`/IaC scan), **kube-linter** and/or **kubesec** and/or **checkov** (manifests/
  chart), `helm lint`, `kubeconform`. If a tool isn't installed, state the exact command to run it.
- Then write a **control → threat** explanation: for each hardening decision (non-root, read-only
  FS, dropped caps, NetworkPolicies, PSA restricted, sealed secrets, TLS, registry auth, ArgoCD
  RBAC, resource limits, image scanning), explain **what attack it mitigates and why this setup is
  secure**. Call out residual risks and single points of failure honestly.

Do not consider a repo "done" until both `docs/USAGE.md` and `docs/SECURITY-REVIEW.md` exist.

---

## 1. Mission

Set up production-grade, GitOps-driven CI/CD and Kubernetes infrastructure for an existing web app,
split across **two Git repositories**, with **full observability** and **safe rollback**. Generate
well-documented, secure-by-default, reviewable files. Pin versions. Never commit plaintext secrets.

## 2. Application stack (facts — do not change)

- **Backend:** Laravel 12 (PHP 8.3+) exposing a **JSON API under `/api`**. Composer.
- **Frontend:** **Vue 3 SPA** (no Inertia), built with **Vite**, served as **static files**; it
  calls the Laravel API. Same repo; the image build produces both the API runtime and the SPA
  static bundle.
- **Database:** MySQL 8.
- **Background work:** Laravel queues (workers) **and** the Laravel scheduler.
- **Health endpoint:** Laravel's built-in `/up` route is available for probes.

## 3. Platform (facts — do not change)

- **Full Kubernetes** with **Helm charts**, **ArgoCD** GitOps CD.
- **GitHub Actions** on **self-hosted runners on our own server**.
- **Traefik** ingress. **cert-manager** for TLS.
- **`prod` and `dev`** on the same cluster, isolated by namespace.
- **ArgoCD manages the platform too** (registry, SeaweedFS, monitoring) via an app-of-apps, not just
  the application.

## 4. Decision Defaults (fixed for this build)

| Key | Value | Notes |
|---|---|---|
| `gitops_layout` | **two repos** | App repo + Config/GitOps repo. |
| `platform_management` | **ArgoCD app-of-apps** | ArgoCD manages platform components (SeaweedFS, registry, monitoring, Redis, MySQL, Traefik, cert-manager) with **sync waves**. ArgoCD itself is bootstrapped out-of-band, then self/platform-manages. |
| `cluster_distro` | **full Kubernetes** | |
| `ingress_controller` | **Traefik** | Standard `Ingress` (Traefik ingressClass) + cert-manager TLS; Traefik **IngressRoute + Middleware** for HTTPS redirect, secure headers, rate limiting. |
| `base_domain` | **`lms.local`** | prod `app.lms.local`, dev `dev.lms.local`, `registry.lms.local`, `grafana.lms.local`, `argocd.lms.local`. |
| `tls_mechanism` | **cert-manager + private CA (self-signed root) ClusterIssuer for `*.lms.local`** | ⚠ Let's Encrypt **cannot** issue for `.local` (not publicly resolvable). Use a private CA; clients/runners must **trust the CA**. Keep a documented one-switch path to a Let's Encrypt ClusterIssuer for when a public domain is adopted. |
| `observability` | **kube-prometheus-stack** (Prometheus + Grafana + Alertmanager + node-exporter + kube-state-metrics) | Dashboards for requests, CPU/RAM, storage, DB, queues. Grafana at `grafana.lms.local`. |
| `app_file_storage` | **SeaweedFS (in-cluster, S3-compatible)** | Laravel **S3 driver** → SeaweedFS. Also backs DB backups and registry blobs. **ArgoCD-managed.** |
| `container_registry` | **Self-hosted CNCF Distribution (Registry v2)** | TLS via Traefik+cert-manager, htpasswd/token auth, **SeaweedFS S3 blob backend**. **ArgoCD-managed.** CI pushes; cluster pulls via `imagePullSecret`. |
| `cache_session_queue_driver` | **Redis** | Stateless web tier. |
| `mysql_placement` | **in-cluster StatefulSet + PVC + scheduled backups** | External-MySQL switch documented. |
| `secrets_management` | **Sealed Secrets** | |
| `prod_sync_policy` | dev = auto-sync+selfHeal+prune; prod = manual/PR-gated | |
| `php_version` / `node_version` | **8.3 / 22 LTS** | |

## 5. Two-repo overview & cross-repo wiring (both runs)

**Repo A — App** (`myapp`): application code + Docker build + CI. **Repo B — Config** (`myapp-deploy`):
Helm chart, env values, platform, ArgoCD, sealed secrets, backup/restore/rollback. **ArgoCD watches
only Repo B.**

Connection: App CI builds+scans the image, pushes to the self-hosted registry (tag = git SHA), then
**commits the new tag into `[CONFIG REPO] envs/<env>/values.yaml`** → ArgoCD rolls it out.

Credentials (least privilege): app→config **write** deploy key; ArgoCD→config **read-only** repo
cred; cluster→registry `imagePullSecret`; CI→registry push creds. **Because the domain is `.local`
with a private CA**, the self-hosted runner and any client must **trust the private CA** and
**resolve `*.lms.local`** (local DNS or `/etc/hosts` → Traefik LB IP) — document this explicitly.

---

## 6. `[APP REPO]` — structure

```
myapp/
├── app/ ...                          # Laravel API + Vue 3 SPA source (leave code as-is)
├── docker/
│   ├── Dockerfile                    # multi-stage: node(Vite SPA build) -> composer -> php-fpm
│   ├── nginx/                        # serves SPA static; proxies /api and /up to php-fpm
│   ├── php/                          # php.ini, opcache, php-fpm pool tuning
│   └── entrypoint.sh                 # config/route/view/event cache; NO migrations here
├── .github/workflows/
│   ├── ci.yml                        # test, lint, static analysis, dependency audit
│   ├── build-and-publish.yml         # build on self-hosted runner, scan, push, bump config repo
│   └── promote-prod.yml              # gated dev->prod promotion (no rebuild)
├── docs/
│   ├── USAGE.md                      # §0.A — REQUIRED
│   └── SECURITY-REVIEW.md            # §0.B — REQUIRED
└── .env.example
```

### 6a. Container image
- Multi-stage: (1) Node 22 `npm ci && npm run build` → SPA static bundle (`dist/`); (2) Composer
  `install --no-dev --optimize-autoloader`; (3) runtime `php:8.3-fpm-alpine`.
- **nginx** serves the **SPA static bundle** and proxies **`/api`** and **`/up`** to php-fpm
  (sidecar pattern in the Pod: one nginx + one php-fpm).
- PHP ext: `pdo_mysql`, `bcmath`, `gd`, `intl`, `opcache`, `pcntl`, `redis`, `zip`. Tune OPcache.
- **One image, three roles** (web / worker / scheduler) by command.
- Hardening: non-root `USER`, no build tools in runtime, base pinned by tag+digest, `HEALTHCHECK`,
  filesystem compatible with `readOnlyRootFilesystem` (writable emptyDirs for storage/cache/tmp).
- `entrypoint.sh` runs artisan caches, starts php-fpm. **No migrations in entrypoint.**

### 6b. GitHub Actions (`runs-on: [self-hosted, linux]`)
- **`ci.yml`**: cache Composer+npm; Pest/PHPUnit, Larastan/PHPStan, Pint, SPA lint + `vite build`
  smoke check; `composer audit` + `npm audit`.
- **`build-and-publish.yml`** (merge to dev branch): build → **Trivy scan (fail HIGH/CRITICAL)** →
  push to `registry.lms.local` (git SHA) → checkout config repo via write deploy key, bump
  `envs/dev/values.yaml` image tag, commit. (Runner must trust the private CA to push over TLS.)
- **`promote-prod.yml`** (`workflow_dispatch`/release tag): **no rebuild** — open a PR bumping
  `envs/prod/values.yaml` to the tested tag.
- Minimal `permissions:`, actions pinned by SHA, no secret echoing.

---

## 7. `[CONFIG REPO]` — structure

```
myapp-deploy/
├── chart/                            # application Helm chart
│   └── templates/                    # web, worker, scheduler, migrate-hook, migrate-rollback-job,
│                                     # backup-cronjob, restore-job, svc, ingress, netpol, rbac, servicemonitor
├── envs/{dev,prod}/values.yaml       # per-env overrides + current image tag
├── platform/                         # ALL ArgoCD-managed; install order via sync waves
│   ├── cert-manager/                 # install + private-CA ClusterIssuer (*.lms.local) [+ LE issuer, disabled]
│   ├── traefik/                      # controller + middlewares (headers, rate-limit, https-redirect)
│   ├── sealed-secrets/               # controller
│   ├── seaweedfs/                    # S3 storage + buckets: app-files, mysql-backups, registry
│   ├── registry/                     # CNCF Distribution, SeaweedFS S3 backend, TLS, auth
│   ├── redis/
│   ├── mysql/                        # StatefulSet + PVC (or external pointer)
│   └── monitoring/                   # kube-prometheus-stack + dashboards + exporters
├── argocd/
│   ├── install/                      # hardened ArgoCD values (bootstrapped out-of-band)
│   ├── projects/                     # AppProject: platform, app-dev, app-prod (least privilege)
│   ├── root-platform.yaml            # app-of-apps over platform/ with sync waves
│   ├── root-app.yaml                 # app-of-apps / ApplicationSet over the app chart per env
│   └── applications/
├── secrets/                          # SealedSecrets ONLY (encrypted)
├── Makefile                          # bootstrap, lint/validate, seal-secret, backup, restore, rollback
└── docs/
    ├── INFRASTRUCTURE.md             # architecture + bootstrap + operations runbook
    ├── USAGE.md                      # §0.A — REQUIRED
    └── SECURITY-REVIEW.md            # §0.B — REQUIRED
```

### 7a. Application Helm chart
- **web** (php-fpm + nginx sidecar): startup/readiness/liveness on `/up`; HPA-ready; PDB.
- **queue-worker(s)**: `queue:work` with `--max-time/--max-jobs/--tries`; graceful SIGTERM;
  configurable replicas/queues.
- **scheduler**: CronJob (minutely) `schedule:run` or 1-replica `schedule:work`.
- **migrate hook**: pre-upgrade/pre-install Job `migrate --force`, hook-weight + sync-wave ordered,
  blocks rollout on failure, once per release.
- Config via ConfigMap; secrets **referenced** but supplied via **SealedSecrets** (APP_KEY, DB,
  Redis, SeaweedFS S3 keys, mail, registry pull).
- **Security context**: runAsNonRoot, uid/fsGroup, readOnlyRootFilesystem, no priv-esc, drop ALL
  caps, seccomp RuntimeDefault. **Resources** requests/limits everywhere.
- **Networking**: Service; Ingress (Traefik) + cert-manager TLS; Middlewares (https-redirect,
  secure headers, rate-limit); default-deny NetworkPolicy + explicit allows (web→redis/mysql/
  seaweedfs, workers→same, prometheus→metrics).
- **ServiceMonitor** exposing app/queue metrics to Prometheus.
- ServiceAccount per workload, least-privilege RBAC.

### 7b. Platform — ArgoCD-managed, sync-wave ordered
Wave 0: cert-manager + private-CA ClusterIssuer, Traefik, sealed-secrets. Wave 1: SeaweedFS
(+buckets). Wave 2: registry (needs SeaweedFS). Wave 3: Redis, MySQL, monitoring. Then the app.
Also enforce **PSA `restricted`** + `ResourceQuota`/`LimitRange` on `app-dev`,`app-prod`.

### 7c. Observability (best practice)
- **kube-prometheus-stack**: Prometheus scrapes via ServiceMonitors/PodMonitors; node-exporter +
  kube-state-metrics for cluster/node/pod CPU/RAM; **exporters** for MySQL (mysqld-exporter), Redis
  (redis-exporter), Traefik metrics, SeaweedFS metrics; app/queue metrics via the app ServiceMonitor.
- **Grafana** at `grafana.lms.local` (behind Traefik + TLS, admin via SealedSecret), dashboards
  **provisioned as ConfigMaps via the Grafana dashboard sidecar**: HTTP requests/latency/error rate
  (Traefik), node & pod CPU/RAM, PVC + SeaweedFS storage usage, MySQL, Redis, queue depth/throughput.
- **Alertmanager** with a few starter alerts (pod crashloop, node/pod memory pressure, disk/PVC
  nearly full, cert expiring, target down).

### 7d. Database backup **and import/restore**
- **Backup CronJob** per env: `mysqldump`/`mydumper` → gzip → SeaweedFS `mysql-backups/<env>/…`;
  retention.
- **Restore/Import Job** (manual, parameterized), two sources: (1) an existing SeaweedFS backup
  object; (2) an arbitrary local `.sql`/`.sql.gz` you upload to the bucket first (document via `mc`/
  S3 client or `kubectl cp` to a helper pod). Guards: `TARGET_ENV` confirm; **prod requires
  `--confirm-prod`**; optional maintenance mode; run `migrate --force` after. `make backup`,
  `make restore`.

### 7e. Rollback — deploy **and** manual migration rollback (best practice)
- **Deploy rollback**: document rolling the image tag back in `envs/<env>/values.yaml` (Git revert →
  ArgoCD syncs) and/or ArgoCD's own rollback to a previous synced revision. This is the primary,
  safe rollback.
- **Migration rollback (manual)**: a parameterized **Job** running `php artisan migrate:rollback
  --force --step=<N>` (or `migrate:reset` guarded), invoked manually — **never automatic**. Guards:
  `TARGET_ENV` confirm, **prod requires `--confirm-prod`**, optional maintenance mode + pre-rollback
  backup. `make migrate-rollback ENV= STEP=`.
- Document the **combined runbook** (roll image back, then optionally roll migrations) and warn
  clearly that migration rollback can be **destructive/irreversible** and that not all migrations are
  safely reversible — recommend a backup first.

### 7f. ArgoCD
- Hardened install (no anonymous, RBAC, SSO where possible), UI at `argocd.lms.local`.
- **AppProjects**: `platform`, `app-dev`, `app-prod`, each restricting source repo, destination
  namespaces, resource kinds.
- `root-platform.yaml` (app-of-apps over `platform/`, sync waves) + `root-app.yaml` (per-env app).
- dev auto-sync+prune+selfHeal; prod manual/PR-gated.

---

## 8. Execution plan

**Phase 0 — Shared:** confirm Decision Defaults; output plan + both repo trees; list cross-repo
credentials + the `.local`/private-CA trust & DNS requirements.

**Phase 1 — `[APP REPO]`:** Dockerfile (SPA static + `/api` proxy) → nginx/php configs → entrypoint →
`ci.yml` → `build-and-publish.yml` (+ config-repo tag bump) → `promote-prod.yml`. **Then §0: write
`docs/USAGE.md`, then run the security check and write `docs/SECURITY-REVIEW.md`.**

**Phase 2 — `[CONFIG REPO]`:** ArgoCD install/projects → platform app-of-apps (cert-manager+private
CA → Traefik → sealed-secrets → SeaweedFS → registry → Redis → MySQL → monitoring) → application
Helm chart (migrate hook, migrate-rollback Job, backup CronJob, restore Job, ServiceMonitor) → env
values → root-app → `secrets/README.md` → Grafana dashboards → Makefile → `INFRASTRUCTURE.md`.
**Then §0: write `docs/USAGE.md`, then run the security check and write `docs/SECURITY-REVIEW.md`.**

**Phase 3 — Wiring & bootstrap runbook:** create the write deploy key (app→config), read-only repo
cred (ArgoCD→config), registry pull/push creds; **generate the private CA and distribute trust to
runner+clients**; set up `*.lms.local` DNS → Traefik; register the repo in ArgoCD; ordered bootstrap:
install ArgoCD → apply root-platform (waves bring up registry) → run app CI to push first image →
apply root-app → verify TLS, dashboards, a backup, a restore, and a rollback.

## 9. Security acceptance criteria

Non-root + read-only FS + dropped caps + no-priv-esc + RuntimeDefault seccomp + resource limits +
pinned/digested images; namespaces `restricted` + default-deny NetworkPolicies + quotas; Trivy +
hadolint + kube-linter/kubesec/checkov + `composer/npm audit` gate + dependency-update bot; TLS
everywhere (private CA) with HSTS/secure-headers/rate-limit/HTTP→HTTPS at Traefik; registry behind
TLS+auth; ArgoCD no-anon + RBAC + AppProjects; **no plaintext secrets in either repo**; MySQL
least-privilege user + tested backup, restore, **and rollback**; monitoring + alerts live; documented
single-node/DB SPOF and dev/prod blast radius mitigated by quotas. **Each repo ships
`SECURITY-REVIEW.md` explaining control→threat and why the setup is secure.**

## 10. Out of scope / do not do

- No plaintext secret/`.env`/key in either repo (SealedSecrets only).
- No migrations, and **no migration rollback**, from the entrypoint or automatically — rollback is
  always a manual, guarded Job.
- No image rebuild during prod promotion.
- No app code changes beyond infra needs (`/up` exists; SPA already talks to `/api`).
- No app manifests/chart in the app repo; no app source in the config repo.
- Do not attempt Let's Encrypt for `*.lms.local` — use the private CA issuer.
