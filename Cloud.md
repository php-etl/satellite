# Cloud

## How to use the cloud commands?

The first step to using cloud commands is to login to the API using the command
̀`bin/cloud login`.

To see all the commands usable in the application `bin/cloud list`

Once connected, you need to attach an organization to the project as well as a workflow.
To do so, please use respectively the commands organization:change and `bin/cloud project:change`.

Warning: you must first determine which organization to attach before determining which project to attach.

When you use these 2 commands, you will be asked for the uuid of the concerned entity. To find the uuid ,
you can use respectively the commands `organization:list` and `project:list` to find the uuid of the organization and the project.

If you don't have a project yet, you can directly create one with the `bin/cloud project:create` command.

Once the configuration steps have been done, we can start sending our pipelines with `bin/cloud create config=/path/to/file.yaml`

Make sure that the YAMl configuration file of your pipeline is well configured (naming of the pipeline, naming of the steps etc...)
