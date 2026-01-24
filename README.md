# S3 offloader for WordPress

This WordPress plugin provides a simple way to offload media files from a WordPress site to Amazon S3, helping to reduce server load and improve site performance.

## Features

- Automatically upload media files to an S3 bucket upon upload.
- Serve media files directly from S3.
- Option to delete local copies of media files after upload.
- Integration with wp-cli for synchronization and bulk offloading of existing media files.


## Code standards

* Use docker for development environment to ensure consistency across different setups.
* PHP CodeSniffer can be used to check for compliance with the coding standards.
* Composer is used for managing dependencies and autoloading classes.
* PSR-4 autoloading standard is followed for class files.
* Namespaces are used to avoid class name collisions.
* Tests are written using PHPUnit and can be run with the `phpunit` command
