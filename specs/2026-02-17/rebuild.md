# Hybrid Deterministic-First Rebuild Plan

Date: 2026-02-17
Scope: Short Option 1 hardening phase first, then Option 2 hybrid deterministic-first architecture.
Target system: Rivalwatch company monitoring pipeline.

## 1. Decision

We will not do a full rewrite first. We will stabilize the current platform, then migrate to a deterministic-first pipeline where LLMs are constrained to enrichment and normalization.

This plan is optimized for:
- Higher reliability without stopping delivery.
- Full evidence traceability for every saved claim.
- Better run status truthfulness.
- Lower token spend per useful output.

## 2. Success Criteria

The rebuild is complete only when all criteria are met for 30 consecutive production runs.

1. Run lifecycle correctness is 100%.
2. 100% of saved facts have at least one linked evidence record.
3. 100% of technical failures are persisted in structured error tables.
4. 0 runs report `success` when required source types are incomplete.
5. Fact extraction reproducibility is at least 99% from stored evidence.
6. Median tokens per saved fact improve by at least 60% from baseline.
7. Median run duration is stable within agreed SLO by company size tier.
8. Monitoring rows have non-null correlation IDs at least 99.9% of the time.

## 3. High-Level Target Architecture

1. Deterministic ingestion first.
2. Deterministic extraction second.
3. LLM enrichment last, behind schema validators and evidence constraints.
4. Claims are written only through an evidence-backed claim service.
5. Run orchestration uses explicit DAG completion semantics, never implicit success.

## 4. Phase 0: Short Hardening (Option 1 Subset)

Timeline: 1 to 2 weeks.
Goal: Stop silent failures and status drift before migration.

### Deliverables

1. Run finalization barrier.
- `company_run` cannot finish until all child `source_type_runs` are terminal.
- Terminal statuses are `success`, `no_findings`, `partial`, `failed`, `blocked`.
- `planned` and `running` children block finalization.

2. Strict status contract.
- `success` requires output contract satisfied for that source type.
- `no_findings` is explicit and requires `reason_code`.
- `partial` requires at least one structured error linked to that source type run.

3. Structured error surfacing.
- Every parser/tool/model/provider failure writes `errors` row with `kind`, `severity`, `reason_code`, `context_json`.
- Any tool-level exception cannot remain log-only.

4. Correlation ID propagation.
- `company_run_id` and `source_type_run_id` must be present across `run_jobs`, `monitoring_api_requests`, `monitoring_llm_requests`.
- Missing ID writes validation error and blocks status `success`.

5. Evidence minimum contract.
- Fact write path enforces at least one source link.
- Missing evidence downgrades to `blocked` and writes error.

6. Run quality summary.
- Persist run summary object with counts of success, no_findings, partial, failed, blocked, facts_saved, facts_rejected, sources_verified, sources_unverified.

### Acceptance Criteria

1. No company run closes before its latest child run finish timestamp.
2. `errors.csv` captures intentionally injected parser and tool failures.
3. Monitoring records with null correlation IDs are near zero and audited.
4. Facts without evidence links cannot be persisted as current facts.

## 5. Phase 1: Deterministic Ingestion Layer

Timeline: 2 to 4 weeks.
Goal: Move discovery and fetch away from unconstrained agent behavior.

### Deliverables

1. Source registry.
- Canonical source catalog per source type with `source_kind`, `priority`, `crawl_policy`, `auth_mode`, `allowed_domains`.

2. Deterministic discoverers.
- Implement provider-specific discoverers for search engines, sitemaps, RSS, LinkedIn endpoints, known company subpaths.
- Discovery outputs normalized URL candidates with confidence and reason.

3. Fetch service.
- Deterministic fetcher with retries, backoff, content-type routing, robots and policy controls, anti-dup hashing.
- Save raw payload and normalized compact payload.

4. Evidence store v1.
- Store every fetched artifact with immutable `evidence_id`, content hash, canonical URL, fetched timestamp, fetch metadata, parser version placeholder.

5. URL canonicalization module.
- Shared canonicalization across all pipelines.
- Deduplicate domain variants and query noise by policy.

### Acceptance Criteria

1. All source URL and snapshot creation is done through deterministic services.
2. Re-running same company with unchanged web state yields stable URL set and hashes.
3. No discovery path requires LLM to produce URLs.

## 6. Phase 2: Deterministic Extractors by Source Type

Timeline: 4 to 7 weeks.
Goal: Build parser-first extraction where feasible and narrow LLM usage.

### Deliverables

1. Extractor interfaces.
- Standard extractor contract: input evidence bundle, output typed candidate claims plus extraction diagnostics.

2. Source-type extractor set.
- Implement deterministic extractors for homepage, about, careers, prices, releases, events, customer stories, media appearances, partner integrations, company posts, employee discovery, news.

3. Extraction diagnostics.
- Every extracted candidate has `extractor_name`, `extractor_version`, `rule_id`, `confidence_base`, `parse_warnings`.

4. Fallback behavior.
- If deterministic extraction fails, emit `no_findings` or `partial` with reason codes.
- LLM fallback allowed only for approved source types and only with evidence bundle input.

### Acceptance Criteria

1. At least 80% of final claims on pilot companies are produced by deterministic extractors.
2. Deterministic extractor failures are structured and visible.
3. No direct fact save from free-form model output without typed candidate schema.

## 7. Phase 3: LLM as Constrained Enrichment

Timeline: 6 to 8 weeks, parallel with Phase 2.
Goal: Keep model value, remove model authority over truth.

### Deliverables

1. Enrichment-only model tasks.
- Summarization, normalization, categorization, language cleanup, duplicate clustering.

2. Strict schemas.
- JSON schema validation and repair loop with bounded retries.
- Invalid outputs become structured failures, not silent drops.

3. Prompt and model governance.
- Versioned prompts by task.
- Task-specific model selection.
- Cost and latency budgets per task.

4. Hallucination guardrails.
- Model cannot create new facts not present in evidence bundle.
- Any unsupported field becomes null with warning.

### Acceptance Criteria

1. 100% model outputs are schema-valid before persistence.
2. New claim creation from model text without evidence is impossible by design.
3. Tokens per saved fact materially reduced versus baseline.

## 8. Phase 4: Evidence-Backed Claim Engine

Timeline: 7 to 9 weeks.
Goal: Make claims auditable and versioned.

### Deliverables

1. Claim write service.
- Central service handles dedupe, versioning, confidence policy, and fact state transitions.

2. Evidence linking rules.
- Every claim version requires one or more evidence links.
- Link cardinality and source diversity policies by claim category.

3. Confidence policy engine.
- Confidence derived from source quality, extractor reliability, corroboration count, and recency.

4. Contradiction handling.
- Conflicting claims stay coexisting with inconsistency flags until resolved.

### Acceptance Criteria

1. Every `is_current=true` claim has evidence links and provenance metadata.
2. Confidence is policy-derived and reproducible.
3. Contradictions are explicit, not overwritten silently.

## 9. Phase 5: Orchestration and Reliability Controls

Timeline: 8 to 10 weeks.
Goal: Deterministic DAG orchestration with truthful status and retry behavior.

### Deliverables

1. DAG run planner.
- Define source type dependencies and fanout explicitly.

2. State machine hardening.
- Enforce valid status transitions.
- Add timeout, retry, and dead-letter policies per stage.

3. Idempotency.
- Idempotency keys for discovery, fetch, extraction, and claim writes.

4. Backpressure and rate controls.
- Provider-specific concurrency and rate limits.

### Acceptance Criteria

1. Duplicate job execution does not duplicate claims or evidence.
2. Run status always reflects child stage completion.
3. Failed stages are retryable with bounded attempts and clear terminal outcome.

## 10. Phase 6: Evaluation Harness and QA Gates

Timeline: 9 to 11 weeks.
Goal: Quantify accuracy and completeness continuously.

### Deliverables

1. Gold dataset.
- Curated company set with known expected outputs by source type.

2. Replay framework.
- Re-run pipeline from stored evidence snapshots for deterministic regression checks.

3. Quality scorecards.
- Per-run and per-source-type metrics: completeness, precision proxy, evidence coverage, parser failure rate, cost.

4. CI quality gates.
- Block releases if reliability regressions exceed thresholds.

### Acceptance Criteria

1. Every release runs replay + quality checks.
2. Regression thresholds are enforced automatically.
3. Scorecards are visible in admin and exported for audits.

## 11. Phase 7: Incremental Rollout

Timeline: 11 to 12 weeks.
Goal: Safe migration with rollback path.

### Rollout Steps

1. Dark launch deterministic pipeline with shadow outputs.
2. Compare old and new outputs on same runs.
3. Enable write mode for pilot accounts only.
4. Expand by tier after SLO pass windows.
5. Deprecate legacy agent-first write paths.

### Rollback Strategy

1. Feature flags at discover, extract, enrich, and claim-write layers.
2. One-click fallback to legacy path per source type.
3. Preserve evidence and monitoring continuity during rollback.

## 12. Data Model and Contract Changes

Planned additions or changes:

1. `evidence_items`.
- Immutable fetched artifacts with hash and metadata.

2. `claim_candidates`.
- Typed intermediate extraction output before claim finalization.

3. `claim_versions`.
- Normalized fact versions with confidence policy fields.

4. `claim_evidence_links`.
- Many-to-many links between claim versions and evidence.

5. `source_type_run_status_details`.
- Structured reason codes and completion contract outcomes.

6. Monitoring contracts.
- Required non-null run identifiers and operation-level correlation keys.

## 13. Operating Metrics and SLOs

Track continuously:

1. Run correctness SLO: run finalization consistency.
2. Evidence coverage SLO: claims with valid evidence links.
3. Extraction reliability SLO: parser and tool failure rates.
4. Cost SLO: tokens and provider spend per run and per saved claim.
5. Timeliness SLO: run completion percentile by company size.
6. Drift SLO: extractor change impact measured by replay diff rate.

## 14. Risks and Mitigations

1. Risk: Deterministic extractors initially underperform on messy pages.
Mitigation: allow bounded LLM fallback with strict evidence constraints and rapid rule iteration.

2. Risk: Migration complexity across many source types.
Mitigation: ship by source-type slices with feature flags and shadow mode.

3. Risk: Increased storage costs from full evidence retention.
Mitigation: retention tiers, compression, and hash-based dedupe.

4. Risk: Team velocity drop during platform work.
Mitigation: parallel tracks for hardening, extractor delivery, and quality harness.

## 15. Execution Backlog (Priority Order)

P0:
1. Run finalization barrier.
2. Status contract and reason codes.
3. Structured error persistence.
4. Correlation ID propagation enforcement.
5. Evidence-required fact write gate.

P1:
1. Source registry and URL canonicalization.
2. Deterministic fetch service and evidence store.
3. Extractor framework and first source-type implementations.
4. Claim write service with evidence links.

P2:
1. LLM enrichment contracts and schema validators.
2. Full source-type migration.
3. Replay and quality gates in CI.
4. Shadow rollout and gradual cutover.

## 16. Definition of Done

The rebuild is done when:

1. Option 1 hardening controls are fully active in production.
2. Deterministic-first flow handles all core source types.
3. LLM outputs are enrichment-only and schema-validated.
4. Every current claim is evidence-backed and reproducible.
5. Run status and monitoring are trustworthy and complete.
6. SLOs in Section 2 pass for 30 consecutive production runs.

