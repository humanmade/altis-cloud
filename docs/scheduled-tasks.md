# Scheduled Tasks

WordPress provides an API for running scheduled tasks that are typically only triggered by uncached user visits to any page of the site, which can be quite unreliable. Additionally this built in cron job system does not scale well on a multi-server architecture such as that provided by Altis.

Altis provides a service called Cavalcade to solve this problem by using a system process to trigger tasks. This has the added benefits of separating background task processing from the web server processes and supporting long running tasks, up to 1 hour. See the [infrastructure limitations guide](./limitations.md) for further details.

Cavalcade is also used on the Local Server and Local Chassis environments to replicate the behaviour of cloud.

The integration with WordPress is seamless, so existing WordPress themes, plugins and other 3rd party code that uses sheduled tasks will be compatible.


## Creating Scheduled Tasks

Scheduled events work by triggering an action hook, effectively running `do_action( 'hook' )` at the scheduled time. The functions you need to run at the scheduled time should added to that hook using `add_action()`.

Events can be one-off or recurring.

**Note**: Scheduling a one-off event to occur within 10 minutes of an existing event with the same hook and the same arguments will fail in order to prevent the accidental scheduling of duplicate events. This does not affect the scheduling of recurring events.

### Intervals

Recurring events need a named interval. Out of the box these intervals are `hourly`, `twicedaily`, `daily` and `weekly`.

Additional intervals can be added using the `cron_schedules` filter:

```php
add_filter( 'cron_schedules', function ( array $schedules ) : array {
    // The array key is the name used to refer to the schedule.
    $schedules['15minutes'] = [
        // The time between each recurrence.
        'interval' => 15 * 60,
        // A human readable label for the interval.
        'display' => __( 'Every 15 Minutes' ),
    ];
    return $schedules;
} );
```


### Functions

**`wp_schedule_event( int $timestamp, string $recurrence, string $hook, array $args = [], bool $wp_error = false )`**

Schedules a recurring event starting at `$timestamp`. `$recurrence` is a named interval as described above. `$hook` is the action hook to trigger and `$args` is an optional array of data to pass to the action hook.

`$wp_error` affects the return value of the function, if `true` and setting the schedule fails a `WP_Error` object will be returned instead of `false`.

```php
function on_do_process( $check_users ) {
    // Some long running process...
}

add_action( 'do_process', 'on_do_process' );

// Trigger first event immediately by setting the timestamp to now.
wp_schedule_event( time(), 'daily', 'do_process', [ 'check_users' => true ] );
```

**`wp_schedule_single_event( int $timestamp, string $hook, array $args = [], bool $wp_error = false )`**

This is the same as `wp_schedule_event()` but will trigger the event only once.

**`wp_next_scheduled( string $hook, array $args = [] )`**

This function should be used to check if an event for the given hook and set of arguments has already been scheduled. If one has it will return the timestamp for the next occurrence.

**`wp_unschedule_event( int $timestamp, string $hook, array $args = [], bool $wp_error = false )`**

Unschedules any event matching the timestamp, hook and arguments.

**`wp_clear_scheduled_hook( string $hook, array $args = [], bool $wp_error = false )`**

Clears all scheduled events for the given hook and set of arguments regardless of when they will run.


## Configuration

Cavalcade is generally not user configurable, and is handled automatically for you by the Cloud module.

**Note:** Any changes to the Cavalcade configuration are considered to void your warranty, except as directed by the Altis team. Adjusting any configuration may cause catastrophic errors in your environments.

The Cavalcade service can be switched off:

```json
{
    "extra": {
        "altis": {
            "modules": {
                "cloud": {
                    "cavalcade": false
                }
            }
        }
    }
}
```
