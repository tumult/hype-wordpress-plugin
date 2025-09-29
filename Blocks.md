# Tumult Hype Animations Blocks (v1.9+)

## Snapshot
- **Goal**: Deliver a reliable Gutenberg block that embeds Tumult Hype animations while remaining aligned with the shortcode workflow and upcoming 1.9+ releases.
- **Scope**: Block registration (`includes/blocks.php`), block source (`blocks/animation/`), localized data, shortcode renderer, block transforms/patterns, thumbnail handling, and dashboard touchpoints.
- **Audience**: Core maintainers planning the v1.9 release, designers tuning the editor UX, and engineers responsible for thumbnail services and admin tooling.

## Architecture Overview
1. **Registration on `init`**
   - `hypeanimations_register_blocks()` validates the block API availability, registers build assets (`build/index.js`, `build/index.css`), and localizes animation metadata via `wp_localize_script` → `window.hypeAnimationsData`.
   - Errors about missing build artifacts surface through `error_log`, aiding development but potentially noisy in production.
2. **Animation data pipeline**
   - `hypeanimations_get_animations_for_gutenberg()` queries `$wpdb` for `id`, `nom`, optional `container_id`, and `updated` columns, accommodating schema drift between 1.8 → 1.9.
   - Thumbnail discovery prioritizes `Default_<ID>.png`, then legacy `container_id/thumbnail.jpg`, finally `<ID>/thumbnail.jpg`, defaulting to `images/hype-placeholder.png`.
   - Dimensions are scraped from `uploads/hypeanimations/<ID>/index.html` using a regex against the `HYPE_document` div.
   - The resulting array is injected into the editor script; no REST endpoint exists yet.
3. **Editor block implementation**
   - `blocks/animation/index.js` registers the metadata-defined dynamic block; `edit.js` renders the inspector and preview, while `save` returns `null` (server-rendered).
   - The edit component pulls from `window.hypeAnimationsData`, offers an animation `<SelectControl>`, width/height fields, embed mode selector, and static thumbnail preview.
   - Responsive and auto-height toggles are present but commented out; console logging remains enabled for debugging.
4. **Frontend rendering**
   - `hypeanimations_render_block()` maps block attributes to the existing `[hypeanimations_anim]` shortcode for consistent rendering and sanitization.
   - The shortcode (`includes/shortcode.php`) rewrites `.hyperesources` paths, enqueues the auto-height helper when needed, and honors embed mode.
5. **Transforms & patterns**
   - `hypeanimations_block_transform_support()` enqueues `build/transform.js`, adding shortcode ↔︎ block transforms via `blocks.registerBlockType` filter.
   - Pattern registration seeds two curated layouts (`featured-animation`, `sidebar-animation`) under the "Tumult Hype Animations" category.

## Editor Experience & Dashboard Touchpoints
- The block currently relies on a statically localized dataset; changes require a page reload and won’t reflect uploads made in parallel tabs until refresh.
- Thumbnail previews reference the uploads directory directly; missing files fall back to the placeholder but log noisily. TODO fix logs so missing images do not make noise. This will occur for 1.0 installations who have upgraded.
- Dashboard upload flow (Dropzone in `includes/adminpanel.php`) generates `Default_<ID>.png` thumbnails when present in the OAM archive but never regenerates derivatives.
- No deep link from the block sidebar back to the dashboard list; authors must switch screens to manage animations, impacting discoverability.
- Inspector controls lack validation feedback (e.g., non-numeric `width`/`height`), and responsive/auto-height controls remain hidden even though the shortcode supports them.

## Security & Stability Notes
- **Capabilities**: Scripts enqueue for all editors without explicit `current_user_can('edit_posts')` checks; while block assets are editor-only, add guards to avoid leaking animation metadata to lower-privilege contexts (e.g., `wp_enqueue_script` via REST).
- **Escaping**: Animation names originate from admin uploads; ensure `nom` is sanitized on insert and double-check escaping when rendering in editor previews (currently injected into JSX without escaping).
- **Logging**: Persistent `error_log` statements can expose filesystem paths in production; gate behind `WP_DEBUG` or replace with `trigger_error` conditioned on environment.
- **Regex dimension parsing**: Current pattern assumes `px` values; responsive documents using `%` or `vh` skip width/height extraction. Harden by broadening pattern or by storing canonical dimensions at upload time.
- **Localized payload size**: Large installations with many animations can bloat editor load time. Consider pagination or REST-driven lazy loading to mitigate DoS vectors.

## Works Well Today
- Dynamic block rendering ensures parity with shortcode output and inherits future shortcode fixes automatically.
- Block transforms preserve backwards compatibility with legacy shortcode embeds and allow manual conversion without breaking content.
- Pattern registration offers curated layouts that help authors compose consistent sections quickly.
- Thumbnail discovery path respects legacy folder layouts, easing upgrades from pre-1.8 sites.

## Needs Improvement
- Editor data is not reactive; users must refresh after uploading a new animation to see it in the selector.
- Thumbnail generation is opportunistic and lacks a guaranteed capture pipeline; no tooling exists to regenerate corrupted or missing previews.
- Debug logging is always on, risking log pollution and sensitive path disclosure.
- Inspector controls hide responsive/auto-height toggles rather than surfacing them with contextual messaging.
- Block assets assume the `build/` bundle exists; missing files only emit logs instead of failing gracefully or re-running `npm run build` prompts.
- No smoke tests or lint coverage verify that `Blocks.md`-documented promises stay true (e.g., transforms, data localization).

## Upgrade Strategy for v1.9 → 1.9.x/2.0
1. **Schema resilience**: Keep the column presence checks but add a migration routine that ensures `container_id`, `updated`, and future columns exist before block usage. Provide WP-CLI command for admins.
2. **Data delivery**: Introduce a REST endpoint (`/wp-json/tumult-hype/v1/animations`) gated by `edit_posts`, enabling live searching and reducing localized payload size. Fallback to localized data when REST unavailable.
3. **Thumbnail service**: During upload, generate standardized previews (`Default_<ID>.png`, `thumbnail@2x.png`, WebP) using headless Chromium or ImageMagick, storing metadata in the DB for quick lookup.
4. **Editor polish**: Re-enable responsive/auto-height controls once shortcode/runtime guarantees exist, providing helper text and validation. Add search/filtering to the selector for large catalogs.
5. **Asset pipeline**: Add a build guard in PHP that gracefully dequeues the block and displays an admin notice if `build/index.js` is absent, avoiding silent failure.
6. **Backwards compatibility**: Ensure transforms respect shortcode variants (`embedmode`, `auto_height`). Provide documentation for editors migrating reusable blocks.
7. **Testing**: Add Jest/React Testing Library coverage for the edit component and PHPUnit coverage for `hypeanimations_get_animations_for_gutenberg()` (mocking uploads) to lock down regressions.

## Thumbnail Generation Focus
- **Current state**: Searches for OAM-provided snapshots; no fallback rendering occurs. Failures log but don’t notify authors.
- **Recommended approach**:
  - Parse the uploaded HTML to locate poster images; if absent, render the animation via headless browser for the first frame.
  - Store thumbnails centrally (`Default_<ID>.png` plus responsive sizes) and surface generation status in the dashboard list.
  - Provide a “Regenerate thumbnail” action in the admin table and expose the state via the block selector (e.g., skeleton UI until ready).

## Dashboard & UX Considerations
- Align admin list columns with block needs (thumbnail, dimensions, last updated) so authors understand which assets will appear in the editor selector.
- Offer quick actions (copy shortcode, insert into post) directly from the dashboard to bridge classic ↔︎ block workflows.
- Add contextual help explaining how the block interacts with responsive setups, iframe vs div modes, and how to resolve missing thumbnails.

## Pre-release TODOs

### Must Have (Critical for Release)
**Security & Stability:**
- [ ] Gate block asset registration behind `current_user_can('edit_posts')` and enqueue assets only within the block editor context.
- [ ] Ensure animation names (`nom`) are properly escaped when rendered in editor previews to prevent XSS.
- [ ] Replace unconditional `error_log` calls with environment-aware logging and optional `do_action` hooks for observability.
- [ ] Remove or gate console.log statements from production builds in block editor JavaScript files.
- [ ] Implement graceful fallback when `build/` assets are missing instead of silent failure.

**Core Functionality:**
- [ ] Add automated tests for `hypeanimations_render_block` attribute mapping and shortcode transforms to prevent regressions.
- [ ] Test and document schema migration for sites upgrading from 1.8 to handle optional columns (`container_id`, `updated`).
- [ ] Create an admin notice/build check that alerts maintainers when `build/` assets are out of date or missing.
- [ ] Fix noisy thumbnail logging for missing images that will occur for 1.0 installations who have upgraded.

**Documentation & Migration:**
- [ ] Document the block workflow in the public README and surface upgrade steps for migrating from shortcode embeds.

### Nice to Have (Quality of Life Improvements)
**User Experience:**
- [x] Build a thumbnail generation/regeneration pipeline triggered on upload and exposed via admin UI and block selector states.
- [x] Provide a "Regenerate thumbnail" action in the admin table and expose the state via the block selector (e.g., skeleton UI until ready).
- [x] Show thumbnail (if present) in dashboard.
- [ ] Add deep link from block inspector to dashboard animation management for improved discoverability.
- [ ] Add search/filtering capabilities to animation selector for large catalogs.
- [ ] Add option on shortcode-embedded animations added in 1.0+ to convert to block.

**Enhanced Functionality:**
- [ ] Add input validation and feedback for width/height fields in inspector controls.
- [ ] Broaden regex dimension parsing pattern to handle responsive documents using `%` or `vh` values.
- [ ] Add pagination or lazy loading for large animation catalogs to prevent DoS via localized payload bloat.

**Future Features (Not Planned for 1.9):**
- [ ] Live refresh in editor is not important, so will not implement this: Implement a REST endpoint and fallback strategy for fetching animation metadata, enabling live refresh in the editor.
- [ ] NOT PLANNED: Reintroduce responsive and auto-height controls with appropriate validation messaging and shortcode parity checks.

## Future Opportunities
- Explore block variations (e.g., "Fullscreen animation", "Lightbox animation") that preconfigure inspector settings.
- Integrate with WordPress pattern directory to share curated Tumult Hype layouts.
- Add telemetry (opt-in) to understand which embed modes and dimensions are most common, guiding default settings.
