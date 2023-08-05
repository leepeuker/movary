## Import
You can import your watch history and ratings from Letterboxd to Movary.

Export your data from Letterboxd [here](https://letterboxd.com/settings/data/).
You will receive a ZIP archive containing multiple CSV files.
The `diary.csv` contains your watch dates and the and the `ratings.csv` your ratings.
Visit the movary settings page `/settings/integrations/letterboxd` to import these files to Movary.

!!! Info

    - Ratings are only imported for movies already existing in Movary (**import the diary.csv first**).
    - The import only adds watch dates or ratings missing in Movary and it will not overwrite existing data.
    - Importing hundreds or thousands of movies for the first time can take a few minutes.

## Export
You can export your watch dates and ratings from Movary to Letterboxd. 

Visit `/settings/integrations/letterboxd` to export your watch dates and ratings.
The export will generate a ZIP archive containing one or multiple CSV files. 
You have to upload all CSV file on [Letterboxd](https://letterboxd.com/import/) to completely import your data.
Letterboxd enforces a max file size limit which can make it necessary for Movary to split your data into multiple files. 

More info about how the Letterbox import works can be found [here](https://letterboxd.com/about/importing-data/).
