# CardCrafter Release Protocol

This document outlines the standard operating procedure for releasing updates to "CardCrafter – Data-Driven Card Grids".

## 1. Pre-Release Preparation
- [ ] **Version Bump**:
    - Update `Version: x.x.x` in `cardcrafter.php`.
    - Update `define('CARDCRAFTER_VERSION', 'x.x.x')` in `cardcrafter.php`.
    - Update `version` in `composer.json`.
- [ ] **Changelog**:
    - Add new entry to `CHANGELOG.md` following [Keep a Changelog](https://keepachangelog.com/).
    - Add new entry to `readme.txt` under `== Changelog ==`.
- [ ] **Readme Update**:
    - Update `Stable tag: x.x.x` in `readme.txt`.
    - Ensure `Tested up to` matches the latest WordPress version.
- [ ] **Cleanup**:
    - Ensure no debug code (`error_log`, `var_dump`) remains.
    - Verify `vendor` directory only contains production dependencies (currently none).

## 2. SVN Sync & Deploy works
**Prerequisite**: Ensure you have the SVN repo checked out at `../cardcrafter-svn-final` (or similar).

```bash
# 1. Go to plugin root
cd /path/to/cardcrafter-data-grids

# 2. Sync to SVN Trunk (excluding dev files)
rsync -av --delete \
    --exclude='.git' \
    --exclude='.github' \
    --exclude='tests' \
    --exclude='phpunit.xml' \
    --exclude='phpunit.xml.dist' \
    --exclude='composer.lock' \
    --exclude='node_modules' \
    --exclude='vendor' \
    --exclude='.DS_Store' \
    ./ ../cardcrafter-svn-final/trunk/

# 3. Handle Assets (if changed)
# Copy banner-772x250.png, banner-1544x500.png, icon-128x128.png, icon-256x256.png
# to ../cardcrafter-svn-final/assets/
```

## 3. Commit & Tag
```bash
cd ../cardcrafter-svn-final

# 1. Commit Trunk
svn add trunk/* --force
svn ci -m "Update to version x.x.x" trunk

# 2. Tag Release
svn cp trunk tags/x.x.x
svn ci -m "Tagging version x.x.x" tags/x.x.x
```

## 4. Post-Release
- [ ] Check [WP Plugin Page](https://wordpress.org/plugins/cardcrafter-data-grids/) (updates can take a few minutes to hours).
- [ ] Create GitHub Release (mirroring the changelog).

## 5. Assets Checklist (Required for "Premium" feel)
- `icon-128x128.png` (Standard icon)
- `icon-256x256.png` (Retina icon)
- `banner-772x250.png` (Standard banner)
- `banner-1544x500.png` (Retina banner)
- `screenshot-1.png` (Admin Dashboard)
- `screenshot-2.png` (Grid Layout)
- `screenshot-3.png` (Masonry Layout)
