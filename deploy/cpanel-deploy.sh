#!/bin/bash
set -Eeuo pipefail

MODE="${1:---check}"
case "$MODE" in
  --check|--preflight|--deploy) ;;
  *)
    printf 'Usage: %s [--check|--preflight|--deploy]\n' "$0" >&2
    exit 2
    ;;
esac

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
CONFIG_FILE="$SCRIPT_DIR/cpanel.config"

ERRORS=0
BACKUP_DIR=""
STAGE_DIR=""
THEME_ATTEMPTED=0
PLUGIN_ATTEMPTED=0

info() { printf '%s\n' "$*"; }
warn() { printf 'WARN: %s\n' "$*" >&2; }
error() { printf 'ERROR: %s\n' "$*" >&2; ERRORS=$((ERRORS + 1)); }
die() { printf 'ERROR: %s\n' "$*" >&2; exit 1; }

load_config() {
  local line key value
  [[ -f "$CONFIG_FILE" ]] || die "Missing $CONFIG_FILE"
  while IFS= read -r line || [[ -n "$line" ]]; do
    line="${line%$'\r'}"
    [[ -z "$line" || "$line" == \#* ]] && continue
    key="${line%%=*}"
    value="${line#*=}"
    key="$(printf '%s' "$key" | tr -d '[:space:]')"
    case "$key" in
      IGP_WP_ROOT|IGP_CPANEL_REPOSITORY_ROOT|IGP_THEME_DIR|IGP_PLUGIN_DIR|IGP_BACKUP_ROOT|IGP_ROOT_VERIFIED|IGP_DATABASE_BACKUP_CONFIRMED|IGP_RELEASE_APPROVED|IGP_ALLOW_LOCAL_DEPLOY)
        if [[ -z "${!key+x}" ]]; then
          printf -v "$key" '%s' "$value"
        fi
        ;;
      *) warn "Ignoring unknown config key: $key" ;;
    esac
  done < "$CONFIG_FILE"
}

validate_config() {
  local key
  for key in IGP_WP_ROOT IGP_CPANEL_REPOSITORY_ROOT IGP_THEME_DIR IGP_PLUGIN_DIR IGP_BACKUP_ROOT IGP_ROOT_VERIFIED IGP_DATABASE_BACKUP_CONFIRMED IGP_RELEASE_APPROVED IGP_ALLOW_LOCAL_DEPLOY; do
    [[ -n "${!key:-}" ]] || error "$key is empty"
  done

  [[ "$IGP_WP_ROOT" == /* ]] || error "IGP_WP_ROOT must be an absolute path"
  [[ "$IGP_CPANEL_REPOSITORY_ROOT" == /* ]] || error "IGP_CPANEL_REPOSITORY_ROOT must be an absolute path"
  [[ "$IGP_BACKUP_ROOT" == /* ]] || error "IGP_BACKUP_ROOT must be an absolute path"
  [[ "$IGP_THEME_DIR" =~ ^[A-Za-z0-9][A-Za-z0-9._-]*$ ]] || error "IGP_THEME_DIR must be a simple directory name"
  [[ "$IGP_PLUGIN_DIR" =~ ^[A-Za-z0-9][A-Za-z0-9._-]*$ ]] || error "IGP_PLUGIN_DIR must be a simple directory name"
  [[ "$IGP_THEME_DIR" != "." && "$IGP_THEME_DIR" != ".." ]] || error "IGP_THEME_DIR is invalid"
  [[ "$IGP_PLUGIN_DIR" != "." && "$IGP_PLUGIN_DIR" != ".." ]] || error "IGP_PLUGIN_DIR is invalid"

  local flag
  for flag in IGP_ROOT_VERIFIED IGP_DATABASE_BACKUP_CONFIRMED IGP_RELEASE_APPROVED IGP_ALLOW_LOCAL_DEPLOY; do
    case "${!flag:-}" in
      true|false) ;;
      *) error "$flag must be true or false" ;;
    esac
  done

  if [[ "$IGP_BACKUP_ROOT" == "$IGP_WP_ROOT" || "$IGP_BACKUP_ROOT" == "$IGP_WP_ROOT"/* ]]; then
    error "IGP_BACKUP_ROOT must be outside IGP_WP_ROOT"
  fi
}

validate_source() {
  local file
  local required=(
    .cpanel.yml
    style.css
    functions.php
    index.php
    front-page.php
    inc/catalogue.php
    inc/enquiries.php
    template-parts/hero.php
    assets/css/site.css
    assets/js/site.js
    igp-core/igp-core.php
    igp-core/inc/catalogue.php
    igp-core/inc/enquiries.php
    deploy/cpanel-deploy.sh
  )
  for file in "${required[@]}"; do
    [[ -f "$REPO_ROOT/$file" ]] || error "Missing required source file: $file"
  done

  [[ -d "$REPO_ROOT/assets" ]] || error "Missing theme assets directory"
  [[ -d "$REPO_ROOT/inc" ]] || error "Missing theme inc directory"
  [[ -d "$REPO_ROOT/template-parts" ]] || error "Missing theme template-parts directory"

  if [[ -f "$REPO_ROOT/style.css" ]] && ! grep -q 'Theme Name:' "$REPO_ROOT/style.css"; then
    error "style.css has no WordPress Theme Name header"
  fi
  if [[ -f "$REPO_ROOT/igp-core/igp-core.php" ]] && ! grep -q 'Plugin Name:' "$REPO_ROOT/igp-core/igp-core.php"; then
    error "igp-core.php has no WordPress Plugin Name header"
  fi
}

validate_live() {
  local wp_content theme_target plugin_target
  wp_content="$IGP_WP_ROOT/wp-content"
  theme_target="$wp_content/themes/$IGP_THEME_DIR"
  plugin_target="$wp_content/plugins/$IGP_PLUGIN_DIR"

  [[ -d "$IGP_WP_ROOT" ]] || error "WordPress root does not exist: $IGP_WP_ROOT"
  [[ -f "$IGP_WP_ROOT/wp-config.php" ]] || error "Missing wp-config.php in $IGP_WP_ROOT"
  [[ -d "$wp_content" ]] || error "Missing wp-content in $IGP_WP_ROOT"
  [[ -d "$wp_content/themes" ]] || error "Missing themes directory in $wp_content"
  [[ -d "$wp_content/plugins" ]] || error "Missing plugins directory in $wp_content"
  [[ -d "$theme_target" ]] || error "Installed theme directory not found: $theme_target"
  [[ -d "$plugin_target" ]] || error "Installed plugin directory not found: $plugin_target"
  [[ ! -L "$theme_target" ]] || error "Theme directory is a symlink: $theme_target"
  [[ ! -L "$plugin_target" ]] || error "Plugin directory is a symlink: $plugin_target"

  if command -v wp >/dev/null 2>&1; then
    local active_theme
    active_theme="$(wp --path="$IGP_WP_ROOT" option get template 2>/dev/null || true)"
    if [[ -n "$active_theme" && "$active_theme" != "$IGP_THEME_DIR" ]]; then
      warn "Active theme is '$active_theme'; this deployment does not change activation state"
    fi
  fi
}

validate_gates() {
  if [[ "$IGP_ROOT_VERIFIED" != true ]]; then
    if [[ "$MODE" == --deploy ]]; then
      error "IGP_ROOT_VERIFIED must be true before deployment"
    else
      warn "IGP_ROOT_VERIFIED is false; production paths are not approved"
    fi
  fi
  if [[ "$IGP_DATABASE_BACKUP_CONFIRMED" != true ]]; then
    if [[ "$MODE" == --deploy ]]; then
      error "IGP_DATABASE_BACKUP_CONFIRMED must be true before deployment"
    else
      warn "IGP_DATABASE_BACKUP_CONFIRMED is false"
    fi
  fi
  if [[ "$IGP_RELEASE_APPROVED" != true ]]; then
    if [[ "$MODE" == --deploy ]]; then
      error "IGP_RELEASE_APPROVED must be true before deployment"
    else
      warn "IGP_RELEASE_APPROVED is false; critical issues are not resolved"
    fi
  fi
  if [[ "$MODE" == --deploy && "$IGP_ALLOW_LOCAL_DEPLOY" != true ]]; then
    local actual_root
    actual_root="$(cd "$REPO_ROOT" && pwd -P)"
    [[ "$actual_root" == "$IGP_CPANEL_REPOSITORY_ROOT" ]] || error "Deployment must run from $IGP_CPANEL_REPOSITORY_ROOT (actual: $actual_root)"
  fi
}

copy_theme() {
  local dest="$1" f d
  mkdir -p "$dest"
  shopt -s nullglob
  for f in "$REPO_ROOT"/*.php "$REPO_ROOT"/style.css "$REPO_ROOT"/screenshot.png; do
    [[ -f "$f" ]] && cp -p "$f" "$dest/"
  done
  shopt -u nullglob
  for d in assets inc template-parts; do
    [[ -d "$REPO_ROOT/$d" ]] || die "Missing source directory: $d"
    cp -Rp "$REPO_ROOT/$d" "$dest/"
  done
}

copy_plugin() {
  local dest="$1"
  mkdir -p "$dest"
  cp -Rp "$REPO_ROOT/$IGP_PLUGIN_DIR/." "$dest/"
}

lint_staged() {
  local file
  [[ -f "$STAGE_DIR/theme/functions.php" ]] || die "Staged theme is missing functions.php"
  [[ -f "$STAGE_DIR/theme/style.css" ]] || die "Staged theme is missing style.css"
  [[ -f "$STAGE_DIR/plugin/igp-core.php" ]] || die "Staged plugin is missing igp-core.php"
  [[ ! -e "$STAGE_DIR/theme/.git" ]] || die "Staged theme contains Git metadata"
  [[ ! -e "$STAGE_DIR/plugin/.git" ]] || die "Staged plugin contains Git metadata"

  if command -v php >/dev/null 2>&1; then
    while IFS= read -r -d '' file; do
      php -l "$file" >/dev/null || die "PHP syntax error in $file"
    done < <(find "$STAGE_DIR" -type f -name '*.php' -print0)
  else
    warn "php CLI unavailable; PHP syntax was not linted"
  fi
}

backup_existing() {
  local theme_target="$IGP_WP_ROOT/wp-content/themes/$IGP_THEME_DIR"
  local plugin_target="$IGP_WP_ROOT/wp-content/plugins/$IGP_PLUGIN_DIR"

  if [[ -d "$theme_target" ]]; then
    cp -Rp "$theme_target" "$BACKUP_DIR/theme.previous"
  fi
  if [[ -d "$plugin_target" ]]; then
    cp -Rp "$plugin_target" "$BACKUP_DIR/plugin.previous"
  fi
  cp -p "$IGP_WP_ROOT/wp-config.php" "$BACKUP_DIR/wp-config.php"
  if [[ -f "$IGP_WP_ROOT/.htaccess" ]]; then
    cp -p "$IGP_WP_ROOT/.htaccess" "$BACKUP_DIR/htaccess"
  fi

  if command -v wp >/dev/null 2>&1; then
    if wp --path="$IGP_WP_ROOT" core is-installed >/dev/null 2>&1; then
      wp --path="$IGP_WP_ROOT" db export "$BACKUP_DIR/database.sql" --quiet >/dev/null || die "Database export failed"
    else
      warn "wp CLI cannot confirm an installed WordPress site; no database export was created"
    fi
  else
    warn "wp CLI unavailable; verify the external database backup before proceeding"
  fi
}

restore_target() {
  local dest="$1" label="$2" flag="$3"
  if [[ "${!flag}" == 1 ]]; then
    if [[ -d "$BACKUP_DIR/$label.previous" ]]; then
      [[ ! -e "$dest" ]] || rm -rf -- "$dest"
      mv "$BACKUP_DIR/$label.previous" "$dest" || true
    elif [[ -e "$dest" ]]; then
      rm -rf -- "$dest"
    fi
  fi
}

rollback() {
  local status=$?
  trap - EXIT
  if (( status != 0 )); then
    printf 'Deployment failed; restoring previous theme and plugin\n' >&2
    restore_target "$IGP_WP_ROOT/wp-content/plugins/$IGP_PLUGIN_DIR" plugin PLUGIN_ATTEMPTED
    restore_target "$IGP_WP_ROOT/wp-content/themes/$IGP_THEME_DIR" theme THEME_ATTEMPTED
  fi
  [[ -z "$STAGE_DIR" ]] || rm -rf -- "$STAGE_DIR" 2>/dev/null || true
  exit "$status"
}

swap_target() {
  local src="$1" dest="$2" label="$3" flag="$4"
  if [[ -e "$dest" ]]; then
    mv "$dest" "$BACKUP_DIR/$label.previous" || die "Could not back up $dest"
  fi
  printf -v "$flag" '%s' 1
  mv "$src" "$dest" || die "Could not install $dest"
}

verify_targets() {
  local theme_target="$IGP_WP_ROOT/wp-content/themes/$IGP_THEME_DIR"
  local plugin_target="$IGP_WP_ROOT/wp-content/plugins/$IGP_PLUGIN_DIR"
  [[ -f "$theme_target/functions.php" ]] || die "Deployed theme failed verification"
  [[ -f "$theme_target/style.css" ]] || die "Deployed theme stylesheet failed verification"
  [[ -f "$plugin_target/igp-core.php" ]] || die "Deployed plugin failed verification"
  [[ ! -e "$theme_target/.git" ]] || die "Git metadata found in deployed theme"
  [[ ! -e "$plugin_target/.git" ]] || die "Git metadata found in deployed plugin"
}

run_deploy() {
  local stamp run_id commit theme_target plugin_target
  stamp="$(date -u +%Y%m%dT%H%M%SZ)"
  run_id="${stamp}-$$"
  BACKUP_DIR="$IGP_BACKUP_ROOT/$run_id"
  STAGE_DIR="$IGP_BACKUP_ROOT/.stage-$run_id"
  theme_target="$IGP_WP_ROOT/wp-content/themes/$IGP_THEME_DIR"
  plugin_target="$IGP_WP_ROOT/wp-content/plugins/$IGP_PLUGIN_DIR"
  commit="$(git -C "$REPO_ROOT" rev-parse HEAD 2>/dev/null || printf 'unknown')"

  mkdir -p "$BACKUP_DIR" "$STAGE_DIR/theme" "$STAGE_DIR/plugin"
  trap rollback EXIT

  info "Deploying commit $commit"
  info "Backing up to $BACKUP_DIR"
  backup_existing
  copy_theme "$STAGE_DIR/theme"
  copy_plugin "$STAGE_DIR/plugin"
  lint_staged
  info "Staged and validated theme and plugin"

  swap_target "$STAGE_DIR/theme" "$theme_target" theme THEME_ATTEMPTED
  swap_target "$STAGE_DIR/plugin" "$plugin_target" plugin PLUGIN_ATTEMPTED
  verify_targets

  info "Deployment complete"
  info "Theme: $theme_target"
  info "Plugin: $plugin_target"
  info "Backup: $BACKUP_DIR"
}

load_config
validate_config
validate_source
if [[ "$MODE" != --check ]]; then
  validate_live
fi
validate_gates

if (( ERRORS > 0 )); then
  printf 'Deployment checks failed with %d error(s)\n' "$ERRORS" >&2
  exit 1
fi

case "$MODE" in
  --check)
    info "Offline deployment configuration check passed"
    ;;
  --preflight)
    info "Live WordPress preflight passed"
    ;;
  --deploy)
    run_deploy
    ;;
esac
