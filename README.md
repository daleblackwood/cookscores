# ccscores

A small PHP + MySQL high-score API for games. Version: see `api/version.php`.

**Install:** serve the repo root with Apache + PHP 8, then run `./install.sh`. It writes `api/config/dbc.php` and creates the tables. You can re-run it to upgrade.

**Endpoints** (`/api/index.php?m=<method>`): `time`, `score` (POST JSON), `scoreboard`, `replay`, `name`, `stats`.

**Test** (local only): `php api/test.php http://localhost/api/`

**Docker:** `docker build -t ccscores . && docker run --rm ccscores` runs the tests. Use `docker run --rm -p 8080:80 ccscores serve` to run the API.
