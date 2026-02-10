# Napkin

## Corrections
| Date | Source | What Went Wrong | What To Do Instead |
|------|--------|----------------|-------------------|

## User Preferences
- 1TBS brace style (opening brace on same line for classes, methods, control structures)
- No mail module in framework — use PHPMailer externally
- Polish error messages in framework (Wymagane logowanie, Brak dostępu, etc.)

## Patterns That Work
- Single-file framework with dual namespace blocks (`namespace P1 {}` + `namespace {}`)
- SQLite fallback in Session (INSERT OR REPLACE vs ON DUPLICATE KEY)
- Null-safe helpers: stt() = strtotime, sStrip() = strip_tags+trim, sTrim() = scalar coercion

## Domain Notes
- P1.php contains 14 classes + 10 global helper functions
- 118 tests, 246 assertions — full coverage
- Config uses PHP arrays (not INI files)
