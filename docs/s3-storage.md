# S3 Storage

Regular file storage is not an option for the scalable multi-server architecture of Altis, so the platform provides a seamless integration with AWS S3 for storing media and anything that would typically be placed in the `uploads` directory of a standard WordPress installation.

This requires no modification to the way existing code interacts with uploads because all upload file paths are rewritten to use an `s3://` protocol stream wrapper. This is achieved with the [S3 Uploads plugin](https://github.com/humanmade/S3-Uploads) written and maintained by Human Made.

In the event you wish to use an alternative storage solution or you need to heavily customise your S3 integration the bundled version can be deactivated with the following configuration:

```json
{
    "extra": {
        "altis": {
            "modules": {
                "cloud": {
                    "s3-uploads": false
                }
            }
        }
    }
}
```
