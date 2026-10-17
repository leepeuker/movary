# Netflix

The Netflix integration imports movie watch dates from a Netflix viewing-activity CSV file. It does not import series episodes or stay connected to Netflix after the import.

## Download the viewing activity

1. Open [Viewing activity](https://www.netflix.com/settings/viewed) for the relevant Netflix profile.
2. Select **Download all** at the bottom of the page.

Netflix provides a CSV file with the titles and dates watched.

## Review and import the data

1. Open **Settings → Integrations → Netflix** in Movary.
2. Select the downloaded CSV file.
3. Enter the date format used in its `Date` column, such as `d/m/y`, `Y/m/d`, or `m/d/y`.
4. Select **Upload CSV** and wait while Movary searches TMDB for each movie.
5. Review the generated table. Replace incorrect TMDB matches, remove entries you do not want, and optionally add a Movary rating.
6. Use the **Not found** filter to find entries that still need a TMDB match.
7. Select **Import data**.

Movary tries to recognize and exclude series episodes based on their Netflix titles. Because title matching is not always exact, review the complete table before importing.

The import creates missing movies and adds one play on each listed date. If Movary already has a history entry for the same movie and date, that CSV entry is skipped. Ratings selected during review are applied only to entries that are imported.
