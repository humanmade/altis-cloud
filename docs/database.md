# Database Servers (MySQL)

The primary data store for user content is the database server.

Altis uses MySQL-compatible servers running on [Amazon Aurora](https://aws.amazon.com/rds/aurora/), with an auto-scaling, replicated
configuration.

## Multi-Server Configuration

Unlike many WordPress hosts, Altis Cloud environments use a multi-server configuration for the database, with fully dedicated
servers for your database. Servers are categorised into two general groups:

- **Primary**: The primary server is the authoritative server storing all data.
- **Replica:** The replica servers store copies of the data, continually synchronised from the primary.

All database writes (`INSERT`, `UPDATE`, `DELETE`, etc) are sent directly to the primary server, while database reads (`SELECT`) are
sent to the replicas. (The primary server is also used for reads as part of load balancing.) This allows offloading most of the
database load from the primary.

Many replicas can be created from primary server (using horizontal scaling), while the primary server typically must scaled through
the use of more capable hardware instead (using vertical scaling). Horizontal scaling is performed automatically through
auto scaling, while vertical scaling is performed by the Altis team based on your typical traffic patterns.

Non-Production environments are provisioned with only a Primary server, and with auto scaling disabled. Production environments
without high-availability are provisioned with only a Primary server by default, but with auto scaling enabled, and Replicas created
as necessary.

For customers who regularly run larger numbers of replicas, an additional [Proxy server](https://aws.amazon.com/rds/proxy/) is used
to pool connections and more evenly distribute load across the server pool. The Altis team manages the provisioning of Proxy servers
on your behalf.

## Auto Scaling and Performance

As load on your site increases, the Altis Cloud infrastructure will automatically start new replica servers as necessary to cope
with database load. Auto scaling occurs primarily based on CPU usage of your database servers, with other factors incorporated.

In order for auto scaling to work well and for your site to continue to perform well, **you should avoid writes on frontend
requests**.
While replicas can easily be created, in typical configurations only a single primary server exists. Writes to the database cause
more load upon the database server, and since the primary cannot easily be automatically scaled, large numbers of writes may cause
your database to be overloaded.

(As most frontend requests are also [cached at the CDN](./page-caching.md), writing on page load may not have the desired effect
anyway, as users may be served a cached version of the page.)

Sites expecting large numbers of logged in users or many database writes (such as ecommerce sites) may need increased provisioning
for the primary database server, changes to the auto scaling behaviour, or a multi-primary setup. Altis Cloud environments can
accommodate this behaviour upon arrangement; please note that this may incur extra cost.

## Configuration

### Versions

The active version of MySQL supported by Altis is MySQL 8.0.

(Note that environments run using Amazon Aurora, which is MySQL-compatible
but [may differ from standard MySQL](https://docs.aws.amazon.com/AmazonRDS/latest/AuroraUserGuide/Aurora.AuroraMySQL.CompareMySQL57.html).)

### Behaviour

Database behaviour is generally not user configurable, and is handled automatically for you by the Cloud module.

**Note:** Any changes to the database configuration are considered to void your warranty, except as directed by the Altis team.
Adjusting any configuration may cause catastrophic errors in your environments.

This functionality can be disabled using the `modules.cloud.ludicrousdb` option:

```json
{
    "extra": {
        "altis": {
            "modules": {
                "cloud": {
                    "ludicrousdb": false
                }
            }
        }
    }
}
```
