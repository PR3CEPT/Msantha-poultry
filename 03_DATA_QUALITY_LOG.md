# Data Quality Log — issues found in the Excel files

Nothing was corrected in the PDFs or the CSV data pack: values are exactly as typed. The importer must **keep the original value, flag the issue, and let the owner review**.
`excel_row` in every CSV points to the exact Excel row.

## A. Dates
| File / sheet | Excel row(s) | Issue |
|---|---|---|
| Dynamics — all 28 batch sheets | to row ends | Calendar is pre-filled to **31-Dec-2026**; rows after today (3-Nov-2026) are projections, not history → `is_projected=1`. |
| Dynamics — Poults 12th Batch | 44 | 04-Jul-2024 followed by 05-Jul-**2025** (year typo). |
| Dynamics — Poults 12th Batch | 230, 290, 292, 293 | Date jumps back to 01-Jan-**2023**, then 28-Feb-2023 → 29-Feb-2024 → 01-Mar-2023 → 02-Mar-2024 (template year mix-up). |
| Dynamics — Poults 12th Batch | 418 | 30-Jun-2024 → 01-Jul-**2026**. |
| Dynamics — MIKOLONGWE 7 (59), 7 (57), SASSO 2 (9), SASSO 2 (3) | 19/33/25/100 → 81/95/88/163 → 206/220/213/288 | Same 2023 / 2024 / 2026 template year mix-up. |
| Dynamics — MIKOLONGWE 8 (1) | 23 | 30-Jun-2024 → 01-Jul-2026. |
| Dynamics — MIKOLONGWE 8(3), 8 (2) | 167, 176 | 31-Dec-2026 → 27-Nov-2026 (goes backwards). |
| Dynamics — PEKIN DUCKS, KHAKI COMPELL DUCKS | 68/130/168/192/255 and 68/130/169/193/256 | 2023 and 2024 dates mixed with 2026. |
| Dynamics — QUAILS 22 | 53 (and first date) | date **1980**-10-01 / 1980-08-17 (Excel serial error). |
| Dynamics — QUAILS 21 | 127 | 30-Nov-2026 repeated twice. |
| Incubation analysis | 250 | `24.10.20204` (extra digit). |
| Incubation analysis | 311 | `9.01.2025` (single-digit day; other rows use 2 digits). |
| Incubation analysis | 648 | `22.05.2026+` (stray "+"). |
| Quail egg production | row 4 | `02.12.205` (year missing a digit). |
| Transactions 2023 | P346 / Q346 | "incubated on 2/11/2q23" — typo; Q346 = 18.12.2023. |
| Transactions 2024-25 | S179, Y209 | `08.0.2024`, `16.0.2024` (missing month digit). |
| Cash book — all sheets | many | Dates are **text** in mixed styles (`1.12.2023`, `05.12.2023`, `04.01.2024`); some rows have **no date** (e.g. Poultry rows 3-5, 11-13). |

## B. Structure / junk
| File | Where | Issue |
|---|---|---|
| Incubation analysis | rows ~982-1111 | Only formula residue (`0`, `#DIV/0!`) — 500 cells, not data (omitted from PDFs/CSV). |
| Incubation analysis | "… END", "YEAR 2024" rows | Month-end / label rows inside the data (not batches). |
| Dynamics | trailing rows | Zero-only formula residue after the last real day on 15 sheets (omitted). |
| Transactions 2024-25 | ES263 = "`", IV264 = "Z" | Stray keystrokes far outside the table (listed, not imported). |
| Transactions 2024-25 | row 771 | Garbage text in the Month column (`yke'anejj`). |
| Transactions 2022 & 2023 | May, June first rows | Month label present but **Day blank** on the first row of the month. |
| Transactions 2024-25 | Feb-2024 (row 37) and Mar-2024 (row 67) | Month blocks almost empty (28 and 9 values vs ≈ 75 normal) — verify whether those months were recorded elsewhere. |
| Transactions 2025 (sheet) | — | Sheet is **completely empty**. |
| Cash book — Poultry | hidden columns E–J, row 20 | Hidden scratch cells (17,600 repeated). Kept in CSV. |
| Cash book — Car hire MZ 9350, MZ8170, MJ 4324 | hidden columns / rows 139-140 | Hidden helper columns; rows 139-140 hidden and empty. |
| Cash book — NA 7153 / MJ 4324 | trailing | Stray `0` in Receipts column below the last entry (omitted). |

## C. Customers / contacts
- ≈ **222 distinct customer spellings** for 837 batches (same person typed several ways, with and without location, e.g. `Mayi Nkhoma`, `Mr Haviour`, `Matekenya Ulongwe` vs `Matekenya`).
- Phone typos for the same person: `883119711` vs `88119711`; `999899620` (Msantha) vs `888899620` on 16.09.2025 and 24.09.2025.
- Several rows hold two numbers in one cell (`999088749/0995222333`).
- Rows with no phone (e.g. Gilbert, Mponda on 16.09.2025).
- Phone numbers are stored as plain numbers → **leading zero lost** → normalise to `+265…`.

## D. Money / balances
- Incubation: negative balance (`-200`) with a number typed in the Remarks column (`-180`) — row 10.
- Incubation 2026: unit rate is not constant (some rows imply MK138/egg) → keep per-job `rate_per_egg`.
- Quail egg price quoted as K275/egg (K8,250/tray) in one sheet and K10,000/tray in another → keep a price history table.
- Cash book "… END / MONTH END" rows hold the month total as a number in the same column as transactions → import as `month_total`, not as a transaction.
- Dynamics: two `#VALUE!` errors in formula cells (kept as text in the CSV).

## E. What the importer must do with each class
1. Keep original text in `legacy_*`/`import_log`.
2. Store parsed value if confident; otherwise NULL + flag (`date_suspect`, `date_missing`, `is_projected`).
3. Never silently drop a row; every skipped row appears in the reconciliation report with the reason.
4. After import show a **review screen** (Admin → Data review) so the owner can fix flagged rows once.
