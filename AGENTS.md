# AGENTS.md

Guidance for AI coding agents working on the **Tumult Hype Animations** WordPress plugin
(WordPress.org slug `tumult-hype-animations`). Users upload Tumult Hype `.oam` exports in
wp-admin and embed them with a shortcode.

**Keep this file current.** If a change makes anything here wrong, fix this file in the same
commit. Record only what an agent can't cheaply work out from the code: conventions,
contracts, traps. Delete lines that go stale instead of adding new ones next to them.

## Quick facts

- Plain procedural PHP. No Composer manifest, no build step, no JS bundler.
- Requires PHP 7.4+, WordPress 5.0+, tested up to 7.0 (header in `hypeanimations.php`, mirrored in `readme.txt`).
- Text domain: `tumult-hype-animations`. Translations live in `languages/`.
- Runs only inside a WordPress install. The usual dev setup is LocalWP, with this repo at
  `wp-content/plugins/hype-wordpress-plugin/`.
- Default branch: `master`.

## Commands

```bash
# Syntax-check every PHP file (no other automated checks exist). Run it on PHP 7.4 and the newest PHP
for f in hypeanimations.php uninstall.php includes/*.php; do php -l "$f"; done

# Regenerate the POT and merge into the .po files (needs WP-CLI)
wp i18n make-pot . languages/tumult-hype-animations.pot --domain=tumult-hype-animations
wp i18n update-po languages/tumult-hype-animations.pot languages/

# Compile .po -> .mo (same as the VS Code task "Compile translation .po -> .mo")
for f in languages/*.po; do msgfmt -o "${f%.po}.mo" "$f"; done
```

There is no test suite and no lint config. `vendor/` is git-ignored and local-only. It holds
PHPUnit and WPCS packages, but no `phpcs` binary. Do not assume `vendor/bin/phpcs` exists.
Verify behaviour by hand in a local WordPress: upload an `.oam`, replace it, delete it,
change the container type, and render the shortcode on a page.

When you render through WP-CLI (`wp eval`), watch for two traps:
- WP-CLI loads plugins inside a function, so file-level globals such as
  `$hypeanimations_table_name` stay unset and the shortcode renders nothing. Set the global
  yourself first.
- `hypeanimations.php` uses relative `include('includes/...')`, so run from the WordPress
  root, not from this folder.

## Layout

| Path | Role |
|------|------|
| `hypeanimations.php` | Plugin header, loads the text domain, then `include`s every file in `includes/` |
| `includes/variables.php` | Globals: `$version`, `$hypeanimations_db_version`, `$hypeanimations_table_name` (`{prefix}hypeanimations`), upload-size helpers |
| `includes/init.php` | Table schema via `dbDelta()`. Runs on `plugins_loaded` when the stored DB version is < 1.9.14 |
| `includes/functions.php` | Admin menu (`publish_posts` capability), `hyperrmdir()`, admin asset enqueue (scoped to `toplevel_page_hypeanimations_panel`) |
| `includes/adminpanel.php` | Most of the logic: new upload, replace, delete, admin page markup + inline JS, AJAX handlers, ZIP validation, OAM structure discovery |
| `includes/shortcode.php` | `[hypeanimations_anim id="N"]` front-end rendering. Ids `<= 0` or missing return `''` |
| `includes/iframe.php` | Public `?just_hypeanimations=N` endpoint on `template_redirect` (priority 1) that serves a full HTML page (iframe fallback) |
| `includes/reload-translations.php` | Dev-only helper. Its `include` in `hypeanimations.php` is commented out |
| `uninstall.php` | Drops the table, deletes the option, removes `uploads/hypeanimations/` |
| `css/`, `js/`, `images/` | Vendored DataTables, jQuery UI CSS, Dropzone 5.9.3, and plugin styles |
| `assets/` | WordPress.org banners, icons, screenshots. Excluded from the plugin zip (`.distignore`) |
| `.github/workflows/` | Release automation (see below) |

## Data model

Table `{prefix}hypeanimations`: `id`, `nom` (display name), `slug`, `code` (stored HTML
snippet), `updated` (unix time), `container` (`none` / `div` / `iframe`), `containerclass`,
`notes`. Option `hypeanimations_db_version` holds the plugin version.

Files live in `wp_upload_dir()['basedir'] . '/hypeanimations/<id>/'`. Each folder holds
`<name>.hyperesources/` plus the processed HTML in `index.html`. Animations replaced before
1.9.20 may also have a leftover `<name>.html`. Temp extraction uses a per-request
`hypeanimations/tmp/<uuid>/`.

`code` is stored as `addslashes(htmlentities($snippet))`. Every reader calls
`html_entity_decode()` first: the shortcode, the `hypeanimations_getcontent` AJAX handler,
and the iframe endpoint. If you change the encoding, update all three readers.

## Upload flow (`hypeanimations_panel_upload()`, hooked on `admin_menu`)

1. Dropzone posts `file` to `admin.php?page=hypeanimations_panel`. It accepts `.oam`,
   `maxFilesize` 2000 MB, timeout 180 s. The `accept` callback allows any name. The handler
   returns immediately unless `page=hypeanimations_panel` and the request is a POST.
2. The handler requires `is_user_logged_in()`, `current_user_can('upload_files')`, and nonce
   field `upload_check_oam` for action `protect_content`.
3. `wp_handle_upload()` with an `oam => application/octet-stream` mime override.
4. `is_zip_clean()` runs next. It checks that the file is readable and non-empty and that it
   starts with the `PK\003\004` magic bytes. It also checks that `ZipArchive` opens it and that
   it holds 1000 files or fewer. Each entry must have no `..` and no absolute or drive path. The
   archive needs an `Assets/*.html` file, and every extension must be on the allowlist. On a
   `WP_Error`, the handler echoes the message, deletes the upload, and exits.
5. `unzip_file()` extracts to `tmp/<uuid>/`. The handler then deletes the zip and any root `config.xml`.
6. `discover_oam_structure('<tmp>/Assets/')` returns the first `*.hyperesources` dir and the
   first `*.html` file.
7. Insert the DB row (`container => 'div'`) and use `insert_id` as the folder name.
8. Set files under `Assets/` to `chmod 0644`. Then `rename()` the `.hyperesources` dir into
   place, or fall back to `copy_dir()`. The source is removed only after the copy succeeds.
9. Rewrite `<html_base>.hyperesources` references in the HTML to the public upload URL, with
   the folder name passed through `rawurlencode()`.
10. Save the snippet. It runs from the first line containing `<div id="` through the first
    line containing `</div>`. Write the full HTML to `<id>/index.html`.
11. Clean up the temp dir, then echo the new ID (Dropzone reads it) and exit. Every failure
    path deletes the upload and the temp dir, echoes an escaped message, and exits.
    Exception: a failure after step 7 leaves the new DB row in place.

The replace flow lives inside `hypeanimations_panel()`. It is triggered by `$_FILES['updatefile']`
plus `dataid`. Before touching any files it checks the nonce, `upload_files`, and that the row
exists. It uses `move_uploaded_file()` instead of `wp_handle_upload()`. The replacement is
non-destructive:
- It builds the new `.hyperesources` as `<name>.hyperesources.new-<uuid>` and the new HTML as
  `index.html.new-<uuid>`.
- It moves the live folder aside to `.old-<uuid>` and swaps the new files in. On failure it
  restores the old folder.
- It updates the DB row only after the files are in place.

## Security conventions

Keep these patterns. Add them to anything new.

- Check capability and nonce before any mutation:
  - Upload and replace: `upload_files` plus `protect_content` / `upload_check_oam`.
  - Delete (`?delete=N`): `edit_posts` plus `delete-animation_<id>`.
  - `wp_ajax_hypeanimations_updatecontainer`: `edit_posts` plus `hypeanimations_updatecontainer` (`_wpnonce`).
  - `wp_ajax_hypeanimations_getcontent`: `edit_posts` plus `hypeanimations_getcontent_nonce` (`nonce`).
- Write every query with `$wpdb->prepare()`, or use `$wpdb->insert()` / `$wpdb->update()`.
- Sanitize inputs with `intval()` for IDs and `sanitize_text_field()` for text.
  Run container classes through `sanitize_html_classname()` (in `adminpanel.php`) before saving,
  and through `sanitize_html_class()` when rendering.
- Escape output with `esc_html()`, `esc_attr()`, `esc_url()`, and `esc_js()`. Wrap user-facing
  strings in `__()` / `esc_html__()` with the `tumult-hype-animations` domain. Add a
  `/* translators: */` comment when a string has placeholders.
- Reuse the existing helpers instead of writing new ones: `is_zip_clean()`,
  `discover_oam_structure()`, `delete_temp_files()`, `hyperrmdir()`, `sanitize_html_classname()`.
- Report security issues to support@tumult.com (see `SECURITY.md`).

## Known quirks (verify before relying on them; don't silently "fix" them)

- `register_activation_hook(__FILE__, ...)` sits in `includes/init.php`, so `__FILE__` points
  at the wrong file and the hook never fires. The table actually gets created by the
  `plugins_loaded` version check.
- `get_flat_allowlist()` caches its result in a `static`. The first call happens at include
  time with the built-in list, so the `tumult_hype_animations_whitelist` filter has no effect.
- Capabilities are inconsistent: the menu uses `publish_posts`, upload and replace use
  `upload_files`, delete and AJAX use `edit_posts`.
- `uninstall.php` deletes every animation (table, option, files) and cleans only the current
  site on multisite.
- Snippet extraction stops at the first line containing `</div>`.
- `?just_hypeanimations=N` is public by design, because it serves front-end iframes.

## Upgrade contract

Existing sites must keep working after an update without doing anything. Never change:
- the shortcode tag `hypeanimations_anim`, its `id` attribute, or the function name
  `hypeanimations_anim()`
- animation ids, `uploads/hypeanimations/<id>/` paths and file URLs
- the `?just_hypeanimations=N` endpoint

Code that reads stored rows must accept every format already in the DB. Schema changes must
be additive and idempotent, and must run on `plugins_loaded`, because auto-updates don't fire
activation hooks. Before a release, render every shortcode with the old and the new code and
diff the output.

## Versioning and release

- The version appears in three places. Keep them in sync:
  - `includes/variables.php` (`$version`)
  - the `Version:` header in `hypeanimations.php`
  - the fallback string in `hypeanimations_admin_style()` in `includes/functions.php`
- Add a `= x.y.z =` entry under `== Changelog ==` in `readme.txt`, plus a line under
  `== Upgrade Notice ==` when users should update. `Stable tag` stays `trunk`.
- `.github/workflows/publish.yml` deploys to WordPress.org SVN through
  `10up/action-wordpress-plugin-deploy`. It runs on pushed `x.y.z` tags and fails if the tag
  does not match `includes/variables.php`.
- `.github/workflows/manual-publish.yml` (`workflow_dispatch`) runs
  `.github/scripts/manual-wordpress-org-publish.sh`. It supports `dry_run` and
  `replace_existing_tag`, and it checks both version locations.
- Pushing a tag publishes to WordPress.org. Never create or push tags unless the maintainer asks.
- `.distignore` controls what ships. Update it when you add dev-only files. `AGENTS.md` and
  `tofix.md` are excluded. `tofix.md` is the local, untracked stability plan; keep it out of git.
- Commit messages are one short imperative sentence with no prefix, e.g. "Fix PHP 8 errors in
  shortcode and iframe endpoint".

## Style

Match the surrounding code: procedural functions prefixed `hypeanimations_`, globals for
shared state, admin HTML and inline JS built as PHP strings in `adminpanel.php`. Indentation
mixes tabs and spaces, so follow the block you are editing. Keep changes small. There are no
tests to catch regressions, so describe the manual checks you ran.
