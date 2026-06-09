<!-- superpowers-gstack: 2.14.2 -->
# dcat-admin(PHP/Laravel 升级与功能开发)

本仓库是 dcat-admin 框架本体(Composer 包 `dcat/laravel-admin`,`type: library`)。
当前目标:**维护并升级到新版本 PHP / Laravel**,在此基础上**添加新功能**。

- 技术栈:PHP、Laravel(作为依赖)、dcat-admin 框架代码(`src/`,PSR-4 `Dcat\Admin\`)
- 测试:PHPUnit(`vendor/bin/phpunit`)、Laravel Dusk(`phpunit.dusk.xml`)、phpstan(`composer phpstan`)
- 当前分支:`2.0`(升级工作请走专门分支,勿直接提交 `2.0`)
- 文档参考:同级目录 `../dcat-admin-docs`(官方文档仓库)
- 注意:本包是 **library**,无法独立 `artisan serve`;集成测试 / `/qa` 需挂载到一个宿主 Laravel app

## Skill routing

This project uses Superpowers + GStack. Each owns a distinct phase:

### GStack — Planning, Review & QA, Ship & Monitor

**Planning:**
- `/office-hours` — 新功能 / 范围不清晰的需求先过一遍
- `/plan-eng-review` — 升级架构、兼容性、破坏性变更决策
- `/plan-devex-review` — 本包是对外框架,公共 API / 扩展点 / 开发者体验审查(重点)
- `/autoplan` — 需要多轮计划评审时链式跑

**Review & QA:**
- `/review` — 合并前代码审查(几乎总是)
- `/cso` — 鉴权 / 权限 / 用户数据相关改动(后台框架核心,改动这些前先跑)
- `/devex-review` — 开发者面向的 API / 文档 / 扩展点实测审查
- `/investigate` — QA / 生产中发现的 bug(Phase 3+,不用于实现期)
- `/qa <url>` — ⚠️ 需要先有跑起来的宿主 Laravel app 才可用

**Ship & Monitor:**
- `/ship` — 走 feature branch + PR(需 push 权限,见下方 Rules)
- `/document-release` — changelog / release 说明(库的发布很重要)
- `/health` — 跑 phpunit / phpstan / lint 体检
- `/learn` — 长期升级项目,沉淀跨会话经验

### Superpowers — Implementation
- `/superpowers:brainstorming` — 任何创造性工作前
- `/superpowers:writing-plans` — 多步任务落地前写计划
- `/superpowers:subagent-driven-development` — 升级含大量独立任务,并行 TDD
- `/superpowers:dispatching-parallel-agents` — 明确独立的模块并行处理
- `/superpowers:test-driven-development` — 有可测代码(PHPUnit),先写测试
- `/superpowers:systematic-debugging` — 实现期遇到 bug / 测试失败
- `/superpowers:using-git-worktrees` — 需要隔离的特性工作
- `/superpowers:verification-before-completion` — 框架正确性关键,声称完成前先验证
- `/superpowers:requesting-code-review` / `/superpowers:receiving-code-review` — 多文件改动 / 收到评审反馈
- `/superpowers:finishing-a-development-branch` — 分支收尾合并

### 工具(按需)
- `/freeze` + `/unfreeze` — 升级时把改动锁定在指定目录,避免越界
- `/careful` — 高风险破坏性操作前
- `/context-handoff` / `/context-save` / `/context-restore` — 长会话上下文交接
- `/codex` — 第二意见 / 对抗式代码审查
- `/superpowers-gstack:autoimplement` — 多阶段升级计划自动推进
- `/superpowers-gstack:pitfall-verification` / `/superpowers-gstack:quality-review` — 产出后陷阱 / 质量审查

### Routing Logic

新功能 / 不清晰的需求? → `/superpowers:brainstorming`(或 `/office-hours` 做产品向探索)
有了 spec / 需求,要落地多步任务? → `/superpowers:writing-plans` → 视情况 `/plan-eng-review` + `/plan-devex-review`(或 `/autoplan`)
写代码? → `/superpowers:test-driven-development`(独立任务多则 `/superpowers:subagent-driven-development`)
实现期遇 bug? → `/superpowers:systematic-debugging`
改动涉及鉴权 / 权限 / 用户数据? → 实现后先 `/cso`,再 `/review`
准备合并? → `/review` → `/superpowers:finishing-a-development-branch` → `/ship`
QA / 生产发现 bug? → `/investigate`
要发版? → `/document-release` → `/ship`

### Rules
- Never run GStack and Superpowers skills in the same phase
- Never nest subagents from different frameworks
- Use `/superpowers:systematic-debugging` for bugs found during implementation (Phase 2)
- Use `/investigate` only for bugs found in QA or production (Phase 3+)
- Superpowers specs go in `docs/superpowers/`
- GStack state lives in `~/.gstack/projects/`
- **`origin` = 你的 fork `mly520/dcat-admin`(有 push 权限);`upstream` = 上游 `jqhph/dcat-admin`。`/ship`、PR 推到 origin。**
- **升级工作走专门分支(如 `upgrade/php-laravel`),勿直接提交 `2.0`**
- `/qa` 类浏览器测试需先把本包挂到一个宿主 Laravel app 跑起来

### Model Routing

> 来源:plugin `model-routing.md`(v0.1,advisory)。仅列本项目选用的 skill,harness = **Claude Code**。
> 用法:在 Claude Code 用 `Agent` 工具派发子代理执行某 skill 时,按下表 `model:` 传参。"see phases" 见末尾分阶段表。

| Skill | Claude Code |
|---|---|
| `/superpowers:brainstorming` | sonnet |
| `/superpowers:writing-plans` | sonnet |
| `/superpowers:executing-plans` | sonnet |
| `/superpowers:subagent-driven-development` | see phases |
| `/superpowers:dispatching-parallel-agents` | see phases |
| `/superpowers:test-driven-development` | see phases |
| `/superpowers:systematic-debugging` | see phases |
| `/superpowers:verification-before-completion` | haiku |
| `/superpowers:requesting-code-review` | sonnet |
| `/superpowers:receiving-code-review` | sonnet |
| `/superpowers:finishing-a-development-branch` | sonnet |
| `/superpowers:using-git-worktrees` | haiku |
| `/office-hours` | sonnet |
| `/plan-eng-review` | sonnet |
| `/plan-devex-review` | sonnet |
| `/autoplan` | (chained) |
| `/review` | sonnet |
| `/cso` | sonnet |
| `/devex-review` | sonnet |
| `/investigate` | sonnet |
| `/qa` | see phases |
| `/ship` | see phases |
| `/document-release` | sonnet |
| `/health` | haiku |
| `/learn` | haiku |
| `/freeze` / `/unfreeze` / `/careful` | haiku |
| `/context-handoff` / `/context-save` / `/context-restore` | haiku |
| `/codex` | (delegated) |
| `/superpowers-gstack:autoimplement` | sonnet |
| `/superpowers-gstack:pitfall-verification` | sonnet |
| `/superpowers-gstack:quality-review` | sonnet |

**分阶段(see phases):**

`/superpowers:test-driven-development`:Write failing test → sonnet;Implement (non-Swift/PHP) → sonnet;Refactor → sonnet;Run tests + parse → haiku

`/superpowers:systematic-debugging`:Investigate → sonnet;Hypothesize(新颖/模糊)→ opus;Hypothesize(范围清晰)→ sonnet;Verify → haiku;Implement fix → 用 TDD 行

`/superpowers:subagent-driven-development` / `/dispatching-parallel-agents`:Orchestrator → 当前会话模型;Per-task subagent → 按任务类型查对应行

`/ship`:Detect base / Run tests / Bump / Push / Create PR → haiku;Review diff / 写 commit / 写 PR 描述 → sonnet

`/qa`:Navigate + screenshot → haiku;Triage → sonnet;Write fix(PHP)→ sonnet

> 模型 id:`opus`=claude-opus-4-7,`sonnet`=claude-sonnet-4-6,`haiku`=claude-haiku-4-5。建议为默认,可按证据覆盖。

## Session Continuity
On session start or after /compact: if `docs/superpowers/handoff.md` exists and contains content, read it and present a one-line summary of where you left off. Then proceed normally — do not ask "ready to continue?". Clear the file (write empty string) immediately after presenting the summary.
After /compact: if handoff.md does not contain `## Mode: auto`, ask the user once: "Context was compressed. Want me to activate auto context guard for this session? I'll keep handoff.md updated and suggest /clear when context gets heavy." If yes, invoke the context-handoff skill.

## Autonomy and user interruption <!-- gstack-autonomy-v1 -->

Default to autonomous continuation. Stopping to ask the user is the LAST resort, not the default. When you complete a planned phase or pass a milestone, the next action is the next phase — NOT a status report followed by "ping me to continue".

### When you MUST stop and ask the user

Only these five categories warrant stopping:

1. **User-territory operation required** — Apple Developer Portal capability registration, OAuth/SSO login, signing into an external service, payment authorization, anything requiring 2FA / Apple ID / human credentials the agent cannot supply
2. **Destructive operation needing explicit approval** — `rm -rf`, `git push --force`, dropping a database table, deleting cloud resources, any operation listed under the user's `/careful` rules
3. **Genuinely ambiguous design choice** — two paths with materially different long-term consequences AND no signal in the spec / plan / prior conversation pointing to one over the other. ("I assume green but maybe blue?" is NOT this — that is over-asking.)
4. **Explicit checkpoint in the skill or plan** — e.g. `swiftui-design-consultation` Phase 3's Approve/Drill/Change/Start-over gate, `executing-plans`' phase review, `office-hours` final approval
5. **You are truly blocked** — missing information you cannot derive, infinite loop you cannot break, error message you cannot interpret after reasonable investigation (read docs, search corpus, try the obvious fix first)

### When NOT to stop

Do NOT stop to:

- ❌ Report completed work and ask "shall I continue with the next phase?"
- ❌ Check in at convenient milestones because it feels considerate
- ❌ Ask "should I do X?" when X is obviously the next step in scope
- ❌ Wait for permission to do work clearly within the user's original request or agreed plan
- ❌ Wrap up a session early because the plan turned out to be larger than expected — finish it

If the next step is clearly within scope, DO IT. Report what happened after it's done.

### Forbidden phrases

These continuation-tokens signal "I have stopped autonomy and now require user input" — if any creep into your output without a category-1-to-5 reason above, you have failed the autonomy default:

- ❌ "Ping me when you want me to continue"
- ❌ "Let me know when you're ready for the next round"
- ❌ "Ready when you are"
- ❌ "Awaiting your go-ahead"
- ❌ "Si fra når jeg skal fortsette"
- ❌ "Bash-prompten din er fortsatt aktiv — si bare 'fortsett'"

If you catch yourself about to write one of these, ask: "Is there a real category-1-to-5 reason here, or am I just being polite?" If polite, delete the sentence and do the next thing instead.

### Status updates DURING work, not AS wait-states

Give brief progress signals while you continue, not as the final word before stopping:

- ✅ "BookmarkStore + 7 tests green. Moving to RecordingScanner now."
- ✅ "Phase 1 build verified on macOS. Starting Phase 2 UI layer."
- ❌ "Phase 1 done. Here's a 12-row status table. Ready for UI when you say so."

The user reads status WHILE you work, not as a wait-state for permission.

### When to STOP, but only after finishing in-scope work

When you do legitimately reach a stopping point (the agreed scope is done, or a category-1-to-5 reason fires), stop cleanly:

- State what's done in one or two sentences
- Name what's blocked (if anything) with the specific reason from the five categories
- Do NOT propose new work or invite continuation — the next session/turn will decide that

## Git hygiene & commit cadence <!-- gstack-git-hygiene-v2 -->

Commit at meaningful milestones, not at every file save and not only at session end. The goal is a readable git history that lets future-you (or another agent) understand what shipped and why.

### When to commit

Commit when:
- A logical unit of work is done and tested (one feature, one bug fix, one refactor pass)
- About to switch to unrelated work (don't mix concerns in one commit)
- A reversible decision was made (so you can `git revert` cleanly later)
- Before invoking long-running or risky operations (so you have a rollback point)

Do NOT commit:
- Mid-task — wait until the change is coherent
- Just to "save progress" — that's what `git stash` is for (short-lived holds only, minutes to hours; for longer holds create a WIP branch instead so the work survives `git stash clear` and is visible in `git branch`)
- Unrelated changes batched together — split them into separate commits

### Commit message format

Use the convention established in the repo (check `git log --oneline -10` first). Three cases:

- **Repo has a consistent convention** (every recent commit follows the same prefix style — `feat:` / `fix:` / `[TICKET-123]` / plain prose / etc.) → follow it. Do not introduce a different style.
- **Repo log is empty** (first commit, or freshly init'd) → use the default below.
- **Repo log is inconsistent** (mixed styles, no clear winner) → use the default below AND note in your final summary that the project has no clear commit convention so the user can decide whether to standardize.

Default format (use only when no consistent convention is established):

```
<type>(<scope>): <one-line summary>

<body — what changed and why, not how>

<co-authored-by trailer if relevant>
```

Types: `feat`, `fix`, `refactor`, `docs`, `test`, `chore`. Scope = subsystem/module name.

### Hygiene rules (NEVER violate)

- ❌ `git commit --no-verify` — pre-commit hooks exist for a reason; if a hook fails, fix the root cause
- ❌ `git commit --amend` on commits already pushed — rewrites shared history
- ❌ `git push --force` to `main` or shared branches — destroys others' work
- ❌ `git reset --hard` without first stashing or committing — silent work loss
- ❌ `git add -A` or `git add .` when secrets / large binaries / build artifacts may be present — stage specific paths instead

### Cadence rule

If >5 distinct commits in a row without testing the cumulative state, STOP and verify (build, run tests) before continuing. This STOP is a category-5 ("truly blocked — verification gap") per the Autonomy section above; it overrides the autonomous-continuation default exactly the same way an unresolvable error would. Commits accumulate quickly; cumulative breakage is harder to diagnose than per-commit breakage.

If multiple commits land in a single session without ANY commit being tested, the session is committing "progress without verification" — break that cycle by running the project's test suite, or document explicitly why testing is deferred.

## Multi-lens review (ship-worthy changes) <!-- gstack-multi-lens-review-v1 -->

Substantive changes need three different review lenses — each catches what the others miss:

1. **Self-check** (always, ~30 sec): placeholders, consistency, scope drift, ambiguity
2. **Pitfall verification** (always, max 2 rounds): invoke `/superpowers-gstack:pitfall-verification` — catches domain-specific traps (security, idempotency, contracts, edge cases, LLM-output quirks)
3. **Codex review** (ship-worthy changes only): invoke `/codex review` — catches drift across files and cross-section inconsistency that self-review systematically misses

### What counts as "ship-worthy"

**YES (run codex):**
- Commits that bump version files (`plugin.json`, `package.json`, `pyproject.toml`, etc.)
- Commits that produce CHANGELOG entries
- `feat:` / `fix:` / `refactor:` commits that affect runtime behavior
- Changes to public contracts (APIs, schemas, generated artifacts, file formats)

**NO (skip codex — it's overkill):**
- Pure docs/typo fixes
- Comment-only changes
- WIP commits (per Continuous Checkpoint mode)
- Test-only additions where coverage is the only change

### Why three lenses, not two

Self-review catches "is this artifact good?" Pitfall catches "what typically breaks in this domain?" Codex catches "what's inconsistent across the codebase that author was too close to see?". Different lenses see different things; running fewer leaves a known gap.

### Order matters

Run lenses in order: self → pitfall → codex. Each pass fixes issues the previous one couldn't catch. Running codex *before* pitfall wastes its tokens on issues a simpler pass would have surfaced first.

## Code reuse discipline (before writing) <!-- gstack-code-reuse-v1 -->

Before introducing a new reusable concept — a component, helper, model, type-alias, view-modifier, button-style, extension, hook, utility function — the agent MUST search the codebase for existing implementations first. This catches "context-bounded duplication": the agentic-coding failure mode where a subagent writes a new `EntityCard` without knowing one already exists one directory over.

This is *not* a DRY purity rule. The default stance is pragmatist: three or four similar lines is fine, premature abstraction is a real cost. The rule only fires when introducing something that could plausibly already exist.

### Scope — when to scan

Scan before writing:
- A new struct, class, or component with a domain-shared name (`Card`, `Item`, `Cell`, `Detail`, `Manager`, `Service`, `View`, `Modifier`, `Style`, etc.)
- A new helper function that smells like utility (`formatX`, `parseY`, `validateZ`, `serializeFoo`)
- A new extension, type-alias, ViewModifier, ButtonStyle (Swift) or hook, HOC, wrapper component (web)
- A new shared model / DTO / schema definition

Do NOT scan:
- Lines inside an existing function — that's refactoring, not new-concept introduction
- Inline closures / callbacks specific to one call-site
- Test helpers private to one test file
- One-off scripts not intended for reuse

### How to scan

1. **Grep for the bare concept name** (full-word, case-insensitive) — e.g. `EntityCard`, `formatDate`, `validateEmail`
2. **Glob for matching file paths** — `**/*Card*.php`, `**/*Repository*.php`, `**/format*.php`
3. **Read** the matches that look related (don't skim — actually verify it's the same concept)
4. **Decide**: REUSE existing / EXTEND existing / WRITE NEW (and report which)

### Verbalize the scan

Before scaffolding the new code, narrate one line in chat (in whatever language the conversation is happening in):

> Checking whether we already have an existing `<concept>` …

Then report findings, then continue scaffolding immediately. This is **narration, not a stop** — do not wait for user input unless the user actively redirects.

### When dispatched as a subagent

When dispatching a subagent that will write code, include in the dispatch prompt:

> Before introducing new reusable concepts (components, helpers, models, extensions), search the codebase via Grep/Glob for existing implementations. If you find one, **use it or extend it** and continue with your delegated task — report what you reused. If you do not find one, scaffold new and report what you searched for. Escalate to the orchestrator ONLY if the reuse decision is genuinely ambiguous.

### Pragmatist guardrails

- ❌ Do NOT pre-abstract. If two similar lines exist, leave them as two similar lines until a third one shows up.
- ❌ Do NOT refactor existing code unless the task explicitly asks for it.
- ❌ Do NOT ask the user "should we be DRY about this?" — the answer is yes-but-pragmatist by default. Just scan first.

### Local override

If the user explicitly says "skip the reuse-check for this session" or "just write it, I know nothing similar exists", honor that override.
