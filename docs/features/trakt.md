# Trakt

The Trakt integration imports movie watch history and ratings into Movary. It currently reads data from a public Trakt profile; it does not export Movary changes to Trakt or keep the services synchronized.

## Create Trakt API credentials

Movary needs the Trakt username whose data will be imported and a Client ID for a Trakt API application.

1. Open [Trakt's application settings](https://trakt.tv/oauth/applications) and create an application.
2. Enter a redirect URI when Trakt requests one. Movary does not use it, so a placeholder such as `http://movary` is sufficient.
3. Copy the generated **Client ID**. Movary does not need the Client Secret.

<img src="/assets/trakt-new-application-page.png" alt="Creating a Trakt application" width="70%"/>

<img src="/assets/trakt-view-application-page.png" alt="Trakt application page showing the Client ID" width="70%"/>

## Configure and run an import

1. Make sure the Trakt profile is public for the duration of the import.
2. Open **Settings → Integrations → Trakt**.
3. Enter the Trakt username and Client ID.
4. Select **Verify**, then **Save**.
5. Select **Import history** or **Import ratings**.
6. Select **Show imports** beside either action to review the background job status.

Imports started from the web interface are additive. History entries and ratings already present in Movary are left unchanged. A large first import may take several minutes.

## Import from the command line

Administrators can run the same import from the command line:

```shell
php bin/console.php trakt:import --userId=USER_ID
```

Without `--history` or `--ratings`, the command imports both. Available options are:

- `--history` imports watch history only;
- `--ratings` imports ratings only;
- `--overwrite` makes Trakt authoritative for the selected data, allowing existing local values to be replaced and local history absent from Trakt to be removed; and
- `--ignore-cache` forces the history import to check every Trakt movie instead of skipping entries unchanged since the previous import.

For example, to import both history and ratings for user 1 and replace conflicting local data:

```shell
php bin/console.php trakt:import --userId=1 --history --ratings --overwrite
```

Use `--overwrite` carefully. Unlike the default import, it can change or remove existing Movary data.
