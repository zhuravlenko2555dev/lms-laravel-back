# App Repo — Security Review

Scope: the files generated in **this** repo (image build, php/nginx config,
entrypoint, CI/CD). Cluster-side controls (NetworkPolicies, PSA, quotas,
sealed secrets, ArgoCD RBAC, TLS issuance) are reviewed in the Config repo's
`docs/SECURITY-REVIEW.md`; they are referenced here where the app image depends
on them.

---

## 1. Tooling — results and how to run

| Tool | Purpose | Status here | Command |
|---|---|---|---|
| **npm audit** | JS dependency CVEs | **RAN — see §1.1** | `npm audit --audit-level=high` |
| **hadolint** | Dockerfile lint | not installed | `hadolint docker/Dockerfile` |
| **Trivy (image)** | image CVEs | not installed (no Docker here) | `trivy image --severity HIGH,CRITICAL --ignore-unfixed registry.lms.local/app-back:<tag>` |
| **Trivy (config/IaC)** | Dockerfile/workflow misconfig | not installed | `trivy config --severity HIGH,CRITICAL .` |
| **composer audit** | PHP dependency CVEs | not run (no PHP/Composer here) | `composer audit` |
| **actionlint** | workflow lint | not installed | `actionlint` |

> This environment has no Docker/PHP/Trivy/hadolint, so those were not executed.
> The exact commands above are wired into CI (`ci.yml`, `build-and-publish.yml`)
> and run on the self-hosted runner. **Trivy gates the image build on
> HIGH/CRITICAL.**

### 1.1 npm audit — actual result

`npm audit` reported **14 vulnerabilities (1 critical, 10 high, 1 moderate,
2 low)**, all in **`devDependencies`** — `vite`, `tar`, `launch-editor`.

- **Runtime impact: none.** These are build-time tools. The runtime image
  contains only the compiled `public/build/*` static assets — **no Node, Vite,
  or tar ships in production**. The critical/high Vite issues are dev-server
  path-traversal bugs not present in a production build.
- **Action required:** run `npm audit fix` (and bump Vite to a patched line) so
  the **runner** isn't exposed and the `npm audit --audit-level=high` gate in
  `ci.yml` passes. Until then that gate will (correctly) fail.

---

## 2. Control → threat (why the image is hardened)

| Control (where) | Threat mitigated | Why it holds |
|---|---|---|
| **Non-root `USER 82`** (Dockerfile) | Container→host escalation; writing system paths | A compromised process has no root in the container; combined with dropped caps there is no path to privileged operations. |
| **`readOnlyRootFilesystem`-compatible layout** (entrypoint pre-creates only the needed `emptyDir` paths) | Persistent implant / tampering with code or binaries | App code, vendor, and PHP binaries are immutable at runtime; only scoped scratch dirs are writable, so an attacker can't rewrite `index.php` or drop a webshell into servable paths. |
| **Drop ALL caps + no-privilege-escalation + seccomp RuntimeDefault** (chart, contract) | Kernel-level abuse, setuid escalation | The workload needs no Linux capabilities; removing them shrinks the kernel attack surface. |
| **No build tools in runtime** (`.build-deps` virtual pkg removed; multi-stage) | Post-exploitation tooling (compilers, `apk`, headers) | Runtime image has no compiler/toolchain, so an attacker can't build/compile in place. |
| **Minimal build context** (`.dockerignore` excludes `.env`, `.git`, keys, `node_modules`, `vendor`) | Secret/`.git` leakage into image layers | Secrets and history never enter the context, so they can't be baked into a layer or exfiltrated from the image. |
| **`expose_php=Off`, `server_tokens off`, `X-Powered-By` hidden** | Version fingerprinting | Removes easy targeting of known PHP/nginx CVEs. |
| **`display_errors=Off`, errors → stderr** | Info disclosure (stack traces, paths) | No sensitive detail returned to clients; logs centralize to the platform. |
| **OPcache `validate_timestamps=0`** | Runtime code swap / TOCTOU on cached bytecode | Bytecode is frozen per immutable image; no re-read of PHP files at runtime. |
| **php-fpm `listen=9000` (localhost only in-Pod), nginx `:8080` non-root** | Direct fpm exposure; needing root for :80 | Only nginx faces the Service; fpm is reachable solely over the Pod's loopback; nginx binds an unprivileged port so it runs under PSA `restricted`. |
| **nginx passes only real `.php` to fpm (`try_files $uri =404`), dotfiles denied** | Arbitrary PHP execution / path traversal / leaking `.env`, `.git` | Prevents classic `fpm` misrouting (e.g. `evil.jpg/x.php`) and blocks dotfile reads. |
| **`/up` does not touch the DB** | Cascading probe failures | A DB outage won't fail liveness and mass-restart healthy pods. |
| **No migrations / no migration rollback in entrypoint** | Uncontrolled, concurrent, or accidental schema changes on scale-up | Schema changes are a single guarded Helm Job; N replicas booting can't race migrations. |
| **Client body / upload limits aligned (25M)** | Memory-exhaustion via large uploads | Bounded at both nginx and PHP. |
| **HEALTHCHECK via fpm ping** | Shipping a silently-broken web image | Advisory local signal; K8s probes remain authoritative. |

## 3. Control → threat (CI/CD & supply chain)

| Control | Threat mitigated | Why |
|---|---|---|
| **Trivy gate (fail HIGH/CRITICAL)** before push | Deploying known-vulnerable images | Vulnerable builds never reach the registry or cluster. |
| **Image tagged by git SHA** (immutable), promotion reuses the exact tag | "works in dev, different in prod"; tag mutation | prod runs the byte-identical, already-scanned artifact — no rebuild drift. |
| **Least-privilege `permissions: contents: read`** on workflows | Token abuse if a step is compromised | The `GITHUB_TOKEN` can't write code/releases. |
| **Separate creds per direction**: CI push creds, cluster **pull** secret, app→config **write** deploy key, ArgoCD **read-only** repo cred, prod PR token (`PR:write` only) | Lateral movement from one leaked credential | Each credential does exactly one thing; leaking the pull secret can't push, the deploy key can't open prod PRs, etc. |
| **prod promotion = PR, no direct push, no rebuild** | Unauthorized/unreviewed prod deploys | A human review + merge gates every prod change; ArgoCD prod sync is manual. |
| **SSH key written to a `mktemp` file, `chmod 600`, `trap … rm` cleanup; `--password-stdin` login; `docker logout` in `always()`** | Credential persistence on a shared self-hosted runner | Secrets aren't left in the workspace, argv, or the docker config after the job. |
| **Registry over TLS + auth; runner trusts private CA** | MITM / anonymous push-pull | Pushes/pulls are authenticated and encrypted end to end. |

---

## 4. Residual risks, SPOFs & required follow-ups

**Must fix before production:**
1. **Pin base images by digest.** `FROM` lines are tag-pinned only. Append
   `@sha256:…` (resolve with `docker buildx imagetools inspect <image>`) to
   prevent tag-mutation supply-chain attacks. Do the same for `composer:2.8`,
   `node:22-alpine`, `nginx:1.27-alpine`.
2. **Pin GitHub Actions to full commit SHAs.** `actions/checkout@v4` and
   `actions/cache@v4` are tag-pinned; replace with `@<40-char-sha>  # v4.x`.
   (Third-party actions were deliberately avoided — build/scan/push use CLI
   `run:` steps — to shrink this surface.)
3. **`npm audit fix`** the dev toolchain (esp. Vite) so the runner is safe and
   the CI gate passes (§1.1).

**Residual risks / single points of failure (honest):**
- **Self-hosted runner** is a high-value host: it holds push creds, the config
  write deploy key, and the private CA trust. Compromise ≈ ability to ship
  arbitrary images and rewrite dev deploys. Isolate it, keep it ephemeral where
  possible, restrict who can trigger workflows, and never expose the prod PR
  token to `pull_request` from forks.
- **Private CA** is a trust root: whoever holds the CA private key can mint
  trusted certs for `*.lms.local`. Store it offline/sealed (Config repo covers
  this). Documented switch to Let's Encrypt when a public domain is adopted.
- **Trivy gate** only catches *known* CVEs at build time; images can rot. Rescan
  on a schedule and rebuild periodically. Add a dependency-update bot
  (Dependabot/Renovate) for `composer.lock` and `package-lock.json`.
- **No SBOM / image signing** yet. Recommended next steps: generate an SBOM
  (`trivy sbom`/`syft`) and sign images with cosign, verified by an ArgoCD/
  admission policy.
- **`clear_env=no`** in php-fpm (needed so K8s env/secrets reach PHP) means any
  env var in the Pod is visible to PHP — keep Pod env minimal and rely on
  mounted secrets scoped per workload.
- **DB/registry/SeaweedFS single-instance** SPOFs and dev/prod blast radius are
  addressed in the Config repo (quotas, backups, PDBs). This repo assumes those
  exist.

## 5. Acceptance criteria — status (app-repo portion)

- Non-root ✅ · read-only-FS compatible ✅ · dropped caps (chart contract) ✅ ·
  no-priv-esc / seccomp (chart contract) ✅ · pinned images ⚠ (tag only — digest
  pending, item 1) · Trivy + composer/npm audit gates ✅ (npm gate needs
  `audit fix`) · hadolint documented ✅ · no plaintext secrets in repo ✅ ·
  no migrations from entrypoint ✅ · no rebuild on prod promotion ✅ · least-
  privilege split credentials ✅.
