# Big Pink Australian Orienteering Rankings

Live site: https://ranking.bigfootorienteers.com

PHP/MySQL web application that pulls results from [Eventor](https://eventor.orienteering.asn.au) and produces a statistical ranking of Australian orienteers.

## Ranking algorithm

Based on the [BOF ranking scheme](https://www.britishorienteering.org.uk/images/uploaded/downloads/Competition%20Rule%20S%202014rankingscheme.pdf) with modifications:

- A runner's score for an event is derived from how their time compares to other ranked runners on the same course, weighted by the quality of the field.
- A runner's **year-end ranking** is the sum of their best 6 event scores, with at most 2 sprint events counting.
- All scores are periodically **rebased** to a normalised scale (mean ≈ 1000, std ≈ 200) using `rebase.php`.
- Urban, relay, score, MTBO, and several other event types are excluded automatically.

## Pages

| Page | Description |
|------|-------------|
| `display.php` | Main rankings table — filterable, sortable, virtual scroll |
| `displayrunner.php` | Individual runner profile — last year's results |
| `runnerchart.php` | Runner's year-end percentile rank history as a chart |
| `displayevent.php` | Results for a specific event |
| `fixEvent.php` | Admin page — edit/delete events, set sprint flag, search |

## Event processing pipeline

1. **`getEventorEvents.php`** — Fetches the Eventor event list and saves new event IDs (and sub-race IDs) into the `eventorEvents` table.
2. **`parseManyEv.php`** — Iterates unprocessed events and calls `process_event()` for each.
3. **`processEvEvent.php`** — Downloads result HTML from Eventor, parses runners/clubs/courses, applies the scoring formula, and saves results.
4. **`rebase.php`** — After each event, renormalises all `results.points` and `runners.current_score` to the current population mean/std.
5. **`rerank.php`** — Recalculates each runner's current ranking from their stored results.

## Database tables

| Table | Description |
|-------|-------------|
| `eventorEvents` | Queue of Eventor event/race IDs pending processing |
| `events` | Processed events (name, date, Eventor URL) |
| `results` | Individual runner scores per event, with sprint flag |
| `runners` | Runner profiles: current score, ranking, gender, club |
| `clubs` | Club names, short names, state, country |
| `control` | Key/value config (e.g. maintenance password) |

## Setup

Requires PHP 7.4+ and MySQL/MariaDB. Import `schema.sql` to create the database structure. Configure `mysqli_connect.php` with database credentials.

Run `getEventorEvents.php` then `parseManyEv.php` on a schedule (e.g. nightly cron) to keep rankings up to date.
