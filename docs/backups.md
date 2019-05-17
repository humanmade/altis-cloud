# Backups

In Altis, there are two main components that must be backed up: the database and file uploads. Both the database and file uploads are backed up on a daily basis.

## Automated Backup Strategy

**The database** — including CMS-stored content like posts, custom post types, stored user-data, and user generated content — has point-in-time recovery and backup, up to 7 days, with to-the-second granularity. This is achieved via full backups every day, with saved MySQL transaction logs for each 24 hour period.

**Uploaded files** — such as images, PDF documents, or any other part of the WordPress Media Library — are stored on a distributed file system using revisions. This allows us to do point-in-time restorations of uploaded files indefinitely.

## Manual Snapshot

Manual snapshots of both the database or uploaded files can be performed at any time, via the Altis Dashboard. Manual snapshots are a ZIP archive containing a `mysqldump` SQL file and all the uploaded files. Manual backups are retained indefinitely. It's generally recommended to do a manual backup before any major event or migration. Restoring is typically quicker from a manual backup than an automated, point in time backup procedure.

## Restore Process

Restoring an environment to an earlier point in time typically takes between 1 and 2 hours, and should only be used for a "full site restore". If individual pages need to be restored it's best to first check WordPress revisions or other application layer revisions before resorting to a full site restore.

Contact support to start the full site restore process.
