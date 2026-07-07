# Is It Vibe Coded?

in one word : yes it is

A satirical AI-code-detection engine. Paste any URL → always get an 80–100% "vibe coded"
verdict with absurd forensic findings. Your own apps are whitelisted as 100% human.

Live: **isitvibecoded.iazz.fr**

## Structure

```
index.html          Analyzer + "Best human apps"
how-it-works.html   Fake forensic pipeline (long-form SEO article)
pricing.html        3 fake tiers + troll modal + pricing FAQ
about.html          Real bio, experience, skills, Medium articles, socials
router.php          PHP built-in-server router (static + pretty URLs + API + X-Robots-Tag)
api/meta.php        Fetches real title/description/OG-image/favicon; GitHub API for repos & users
assets/             css, js, favicons, per-page OG images, real app logos, avatar
robots.txt · sitemap.xml · site.webmanifest
Dockerfile · docker-compose.yml
```

## Features

- **Deterministic** - the same target always yields the same score & findings (seeded by host, or host+path for GitHub).
- **Deep links** - `/google.fr` or `/github.com/owner/repo` auto-analyzes that target.
- **Live preview** - pulls the target's real favicon, OG image, title & description via `/api/meta`.
- **GitHub-aware** - repo/user URLs use the GitHub API for real name, description, language & stars, with GitHub's own social-card image.
- **Real SEO** - per-page `<title>`/description/keywords, robots + X-Robots-Tag, canonical, per-page Open Graph + Twitter cards, JSON-LD (WebSite+SearchAction, Person, Article, FAQ, Breadcrumb, Product), sitemap, manifest.

## Run

```bash
docker compose up --build     # → http://localhost:8080
```

Or without Docker:

```bash
php -S 0.0.0.0:8080 router.php
```

## Editing

Both lists live at the top of `assets/js/engine.js`:

- `HUMAN_WHITELIST` - domains reported as 100% human (0% AI).
- `BEST_HUMAN_APPS` - the cards at the bottom of the homepage.

Add more absurd findings in `FINDINGS_POOL` (each tagged with a category in `CATS`).

## Notes

- The container runs the PHP CLI server with `PHP_CLI_SERVER_WORKERS=8` so a slow remote
  metadata fetch never blocks static assets. Put it behind Traefik/Caddy/nginx for TLS
  (Traefik labels are already in `docker-compose.yml` - edit or remove them).
- `api/meta.php` has a basic SSRF guard (refuses private/reserved IPs).
