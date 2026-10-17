# Letterboxd

The Letterboxd integration can import watch history and ratings into Movary or generate an export that Letterboxd can import. It is a manual file transfer, not an automatic synchronization.

## Import data into Movary

1. Open [Letterboxd's data export page](https://letterboxd.com/settings/data/) and request an export.
2. Extract the downloaded ZIP archive.
3. Open **Settings → Integrations → Letterboxd** in Movary.
4. Upload `diary.csv` under **Import History**.
5. After the history import finishes, upload `ratings.csv` under **Import Ratings**.
6. Select **Show imports** beside either action to review the background job status.

Import the diary first because the ratings importer only matches movies that already exist in Movary. Diary entries can add missing movies; the ratings file cannot.

The import is additive:

- an existing watch date for the same movie is left unchanged;
- an existing Movary rating is not overwritten; and
- Letterboxd's five-star ratings are converted to Movary's 1–10 scale.

Large first-time imports may take several minutes. If a completed import is missing movies, ask the Movary administrator to check the application logs for entries that could not be matched or imported.

## Export data to Letterboxd

1. Open **Settings → Integrations → Letterboxd**.
2. Select **Generate export data** to download a ZIP archive.
3. Extract the archive and upload every included CSV file to [Letterboxd's importer](https://letterboxd.com/import/).
4. Review Letterboxd's matches before confirming the import.

The export contains Movary watch dates and ratings. Movary splits it into files of at most 1,000 records so that large libraries are easier to import. See [Letterboxd's import documentation](https://letterboxd.com/about/importing-data/) for its current format and upload requirements.
