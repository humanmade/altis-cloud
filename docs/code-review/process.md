# Code Review Process

All Code Review for HM Cloud is done via GitHub in the pre set-up GitHub repository for your project. Specific development flow process varies project to project, this document only covers the required Human Made Code Review. Specifically where in the "code development to running in production" flow the Human Made Code Review needs to be performed can be discussed separately.

It is required that all code review be performed on Git branches, via a GitHub Pull Request. When a given Git branch is ready for review, the following process should be followed:

1. Open a Pull Request against the "mainline" branch (typically `master` or `staging`)
1. Assign the Pull Request to the `@humanmade-cloud` GitHub user.
1. Make sure your Pull Request passes any status checks and WordPress Coding Standards.

Once the Pull Request is submitted, it's possible an automated code review will be performed by the HM Linter bot. In this case, the Pull Request will be marked as "Changes Requested" and you should fix up any errors to do with formatting for the manual review to continue.

Upon receiving a new Pull Request, Human Made will perform the manual code review. This will result in one of three outcomes:

1. "Changes Requested" - We asked for some things to be changed in the code, they should be rectified before a second review pass.
1. "Commented" - We asked a question of for more information for the Pull Request. Get back to us, and we'll be able to continue.
1. "Approved" - The code is ready to be merged into the mainline branch, and will not need to be reviewed again on it's way to production!

Once a Pull Request is reviewed, the next steps depend on the process of the project. You may communicate on the Pull Request for Human Made to merge and deploy the Pull Request on Approval. This can be done via a comment, or a label of "Review Merge" or similar.

The Pull Request will be assigned back to the developer, if the Pull Request requires changes or comment. Once the changes have been made, the developer should assign the Pull Request back to the `@humanmade-cloud` GitHub user.

If you only want code reviewed, but not yet merged and deployed then you don't need to do anything. This is useful if you want to control when code is merged to the mainline branch, as you can perform the merge yourself.

Note: If you have requested Code Reviews from other users, we will not merge and deploy a Pull Request until those users have also Approved the Pull Request.
