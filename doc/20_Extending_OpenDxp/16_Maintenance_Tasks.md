# Maintenance Tasks

OpenDXP offers you to run scheduled maintenance tasks. This allows you to periodically do stuff like cleanups. 
It is essential that the maintenance cron job is set up perperly, see: [install guide](../01_Getting_Started/00_Installation/01_Webserver_Installation.md#5-maintenance-cron-job).  

## Register a new Maintenance Task

To register a new Maintenance Task, create a new class and implement the interface `OpenDxp\Maintenance\TaskInterface`. Register your class to the symfony container with the tag `opendxp.maintenance.task` and a `type` attribute:   

```yaml
App\Maintenance\MyMaintenanceTask:
    tags:
        - { name: opendxp.maintenance.task, type: my_maintenance_task }
```

OpenDXP will then call your maintenance task on the maintenance cron job you have to configure. You will have to take care about timing operations inside the Task yourself.

## Track a Maintenance Task

A task can appear as a job run in the admin, with its progress and its errors. Wrap the work of `execute()` in
`JobTrackingAgentInterface::trackJob()` of the Generic Execution Engine. See
[Track Background Work](../19_Development_Tools_and_Details/08_Generic_Execution_Engine/01_Jobs_and_Jobruns/03_Track_Background_Work.md).

## Register a new Maintenance Task using a separate messenger transport

First follow the steps mentioned above, then add the `messengerMessageClass` property to your tag, as shown in the example below.
OpenDXP then will make sure to add this specific message to the messenger bus.

```yaml
App\Maintenance\MyMaintenanceTask:
    tags:
        - { name: opendxp.maintenance.task, type: my_maintenance_task, messengerMessageClass: '\App\Messenger\MyMaintenanceMessage' }
```

**Please be aware:** 
You need to implemented both, the message and the handler class. 
After that you can route your message to the corresponding transport.

For a full example you can have a look at the `\OpenDxp\Messenger\ScheduledTaskMessage` class and its usage.
