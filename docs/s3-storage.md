# S3 Storage

Within the scalable multi-server architecture of Altis, sites cannot use the server filesystem to store artifacts, as they will not be shared between different instances, eg: a user would create a file while hitting instance A but then try to read it while hitting instance B where it doesn't exist. So the platform provides a seamless integration with AWS S3 for storing media and anything that would typically be placed in the `uploads` directory of a standard WordPress installation, so all instances would share the same set of files/artifacts.

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
