# Altis Dashboard Import Export

The Altis Dashboard allows you to perform data migrations between environments, as well as perform data exports for use in local development environments.

As shown below, you can find the Import & Export features under the Data tab of the selected Altis Environment.

![import export view](../assets/import-export-view.png)

From here you can view and download previously taken Exports, displayed as a list, ordered by date. 

You can also create a new Export of your Database and/or Assets. Typically this would be comprised of your WordPress Uploads directory, in addition to any other assets your application may be uploading to your Uploads directory.)

To create a new Export, you can press the Export button. This will display a new modal (as shown below) in which you can choose to export the uploads and/or the database. If you choose to create an export of the Uploads, you’ll be able to optionally specify a path. This is useful if you’d quickly like to create an export of assets in a specific directory.

![export modal](../assets/export-modal.png)

Via the Altis Dashboard, you can also Import your database and assets from one environment to another. 

To do this, first goto the environment you want to import to in the Altis Dashboard. Then select Import, a modal will appear in which you can select the source environment from which you will import from. You can choose database, and/or uploaded assets, and as mentioned above, you can select a subpath of the assets if desired.

![import modal](../assets/import-modal.png)

Importing in this way is a destructive, non-reversible process, any existing data in the database will be deleted, and replaced with the database of the source environment. **Important** Once the Import is complete, you will need to run a search-replace on the URLs in the database via the Altis CLI. 

A few tips before running the search-replace is to create a `screen` session, if you unfamiliar with screen sessions, checkout this helpful article: [https://linuxize.com/post/how-to-use-linux-screen/#starting-linux-screen](https://linuxize.com/post/how-to-use-linux-screen/#starting-linux-screen).

If you’d like to learn more or are unfamiliar with WP CLI search replaces, it’s recommended to familiarise yourself with these commands, and make use of the `--dry-run` flag before running a search replace. See the following article for more information: [https://developer.wordpress.org/cli/commands/search-replace](https://developer.wordpress.org/cli/commands/search-replace/).

**Note:** These features are design with the idea that ‘code moves up, content moves down’, so we do not recommend this feature be used to migrate content from non-production environments to production environments; content should typically flow down from production→staging→development→test, etc.
