# Dog-ear Books: a Social Images demo for Craft

A made-up independent bookshop for trying [Social Images for Craft](https://github.com/1994limited/social-images-craft): twelve books, whose share images use a code template showing each cover, and a journal of posts with writers, dates and pictures, using the Editorial designer template.

The sections, fields, templates and Social Images' settings are in Project Config (`config/project`), so they're set up when Craft is installed. `php craft demo/content` adds the books, journal and writers; the covers and journal pictures are in `web/uploads`. SEOmatic is installed to show the integration (it runs as a trial on local sites).

## Running it with DDEV

```bash
ddev start
ddev composer install
ddev npm install
ddev craft install --username=admin --email=admin@example.com --password=password --site-name="Dog-ear Books" --site-url='$PRIMARY_SITE_URL' --language=en-GB --interactive=0
ddev craft demo/content
ddev craft social-images/generate
ddev launch admin
```

DDEV installs Chromium in the web container and points Social Images at it (see `.ddev/config.yaml`).

## Running it with Herd or Valet

Make a MySQL database called `social_images_craft_demo`, then:

```bash
composer install
npm install
cp .env.example .env
php craft setup/keys
php craft install --username=admin --email=admin@example.com --password=password --site-name="Dog-ear Books" --site-url='$PRIMARY_SITE_URL' --language=en-GB --interactive=0
php craft demo/content
php craft social-images/generate
```

Set the paths to Node, npm, Chrome and `node_modules` in `.env` first (see `.env.example`), or use Cloudflare instead (see the plugin's docs/rendering.md). Check rendering works with `php craft social-images/doctor`.

Sign in at `/admin` with the username and password you gave `craft install`. The ones above are for a local copy only.

## What to look at

- **Social Images → Templates**: Editorial (made in the designer) and Book jacket (code, `templates/social-images/book.twig`).
- **Social Images → Images**: every entry's image.
- **Social Images → Settings**: Journal uses Editorial (and a square size too); Books use Book jacket.
- Any book or journal entry: its Social image field.
- The site itself: each page shows its share image at the foot, and its `<head>` has the tags (from SEOmatic).

## Screenshots

`node scripts/screenshots.mjs` takes the screenshots in the plugin's docs, from a running demo with its images made. It writes to `../social-images-craft/docs/images`, so run it with the plugin checked out beside the demo (or set `SCREENSHOTS`). It signs in as `admin` / `password`, or `CRAFT_USER` and `CRAFT_PASSWORD`.
