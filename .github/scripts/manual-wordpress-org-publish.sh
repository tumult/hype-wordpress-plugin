#!/usr/bin/env bash
set -euo pipefail

VERSION="${VERSION:?VERSION is required, for example 1.9.19}"
SLUG="${SLUG:-tumult-hype-animations}"
ASSETS_DIR="${ASSETS_DIR:-assets}"
REPLACE_EXISTING_TAG="${REPLACE_EXISTING_TAG:-false}"
DRY_RUN="${DRY_RUN:-false}"
SVN_USERNAME="${SVN_USERNAME:?SVN_USERNAME is required}"
SVN_PASSWORD="${SVN_PASSWORD:?SVN_PASSWORD is required}"
SVN_URL="${SVN_URL:-https://plugins.svn.wordpress.org/${SLUG}}"

if [[ ! "$VERSION" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
	echo "Version '$VERSION' must use x.y.z format." >&2
	exit 1
fi

for command_name in php rsync svn; do
	if ! command -v "$command_name" >/dev/null 2>&1; then
		echo "Missing required command: $command_name" >&2
		exit 1
	fi
done

file_version=$(php -r 'if(!preg_match("~\\\$version\\s*=\\s*[\x22\x27]([^\x22\x27]+)~", file_get_contents("includes/variables.php"), $m)){fwrite(STDERR, "Unable to parse version from includes/variables.php"); exit(1);} echo $m[1];')
plugin_header_version=$(php -r 'if(!preg_match("~^Version:\\s*([^\\r\\n]+)~m", file_get_contents("hypeanimations.php"), $m)){fwrite(STDERR, "Unable to parse plugin header version"); exit(1);} echo trim($m[1]);')

if [[ "$file_version" != "$VERSION" ]]; then
	echo "includes/variables.php version ($file_version) does not match requested version $VERSION." >&2
	exit 1
fi

if [[ "$plugin_header_version" != "$VERSION" ]]; then
	echo "hypeanimations.php header version ($plugin_header_version) does not match requested version $VERSION." >&2
	exit 1
fi

work_dir="${RUNNER_TEMP:-/tmp}/wporg-publish-${SLUG}-${VERSION}"
export_dir="${work_dir}/export"
svn_dir="${work_dir}/svn"
svn_auth=(--username "$SVN_USERNAME" --password "$SVN_PASSWORD" --non-interactive --trust-server-cert)

rm -rf "$work_dir"
mkdir -p "$export_dir"

rsync_excludes=(
	--exclude='.git/' \
	--exclude='.github/' \
	--exclude='.gitignore' \
	--exclude='.gitattributes' \
	--exclude='.DS_Store' \
	--exclude='.gstack/' \
	--exclude='node_modules/' \
	--exclude='vendor/' \
	--exclude='build/' \
	--exclude="${ASSETS_DIR}/"
)

if [[ -f .distignore ]]; then
	rsync_excludes+=(--exclude-from='.distignore')
fi

rsync -rc --delete "${rsync_excludes[@]}" ./ "$export_dir/"

svn checkout --depth immediates "$SVN_URL" "$svn_dir" "${svn_auth[@]}"
svn update "$svn_dir/trunk" "$svn_dir/assets" --depth infinity "${svn_auth[@]}"
svn update "$svn_dir/tags" --depth immediates "${svn_auth[@]}"

rsync -rc --delete --exclude='.svn/' "$export_dir/" "$svn_dir/trunk/"

if [[ -d "$ASSETS_DIR" ]]; then
	rsync -rc --delete --exclude='.svn/' "$ASSETS_DIR/" "$svn_dir/assets/"
fi

apply_svn_adds_and_deletes() {
	local path="$1"

	while IFS= read -r line; do
		local status="${line:0:1}"
		local item="${line:8}"

		case "$status" in
			'?')
				svn add --parents "$item"
				;;
			'!')
				svn delete --force "$item"
				;;
		esac
	done < <(svn status "$path")
}

apply_svn_adds_and_deletes "$svn_dir/trunk"
apply_svn_adds_and_deletes "$svn_dir/assets"

tag_url="${SVN_URL}/tags/${VERSION}"
tag_path="${svn_dir}/tags/${VERSION}"

if svn info "$tag_url" "${svn_auth[@]}" >/dev/null 2>&1; then
	if [[ "$REPLACE_EXISTING_TAG" != "true" ]]; then
		echo "WordPress.org SVN tag $VERSION already exists. Re-run with replace_existing_tag=true to replace it." >&2
		exit 1
	fi

	svn update "$tag_path" --depth infinity "${svn_auth[@]}"
	svn delete "$tag_path"
fi

svn copy "$svn_dir/trunk" "$tag_path"

echo "SVN changes prepared:"
svn status "$svn_dir"

if [[ "$DRY_RUN" == "true" ]]; then
	echo "Dry run only. No SVN commit was made."
	exit 0
fi

if [[ -z "$(svn status "$svn_dir")" ]]; then
	echo "No SVN changes to commit."
	exit 0
fi

svn commit "$svn_dir" \
	"${svn_auth[@]}" \
	-m "Release version ${VERSION}. Tested to WordPress 7.0."
