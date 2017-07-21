# Query batching

The bundle supports batching using [ReactRelayNetworkLayer](https://github.com/nodkz/react-relay-network-layer) or [Apollo GraphQL](http://dev.apollodata.com/core/network.html#query-batching) directly.
To use batching, you must use "/batch" as a suffix to your graphql endpoint (see routing config). 
Then you can switch between implementations in your configuration like so:

For Relay (default value):
```yaml
overblog_graphql:
    batching_method: "relay"
```

For Apollo:
```yaml
overblog_graphql:
    batching_method: "apollo"
```

Done!