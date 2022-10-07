# Cloud

The cloud part of the project provides many commands via the bin/cloud script allowing you to manage your pipelines on 
our [application](https://app.gyroscops.com/).

### Login
The first step to using cloud commands is to login to the API using the command `bin/cloud login`.

### Setting up your organization and the workspace
Once connected, you need to attach an organization to the project as well as a workflow.
To do so, please use respectively the commands `bin/cloud organization:change` and `bin/cloud project:change`.

When you use these 2 commands, you will be asked for the uuid of the concerned entity. To find the uuid ,
you can use respectively the commands `bin/cloud organization:list` and `bin/cloud workspace:list`.

If you don't have a workspace yet, you can directly create one with the `bin/cloud workspace:create` command.

Warning : you must first determine which organization to attach before determining which workspace to attach.

### Send the pipeline configuration

Make sure that the YAML configuration file of your pipeline is well configured (naming of the pipeline, naming of the steps etc...).

Then, we can start sending our pipelines with `bin/cloud create config=/path/to/file.yaml`.

### Update your satellite configuration

If you want to update your pipeline, you can use the command `bin/cloud update config=path/to/file.yaml`.

### Remove your pipeline

You may decide to remove a pipeline from your workspace, for this you simply need to run the command `bin/cloud remove config=path/to/file`

### See all console commands

You can use the `bin/cloud list` command to view all available commands in the application.

