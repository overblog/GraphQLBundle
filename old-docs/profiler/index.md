> _The profiler feature was introduced in the version **1.0**_

# Profiler

This bundle provides a profiler to monitor your GraphQL queries and mutations.

## Configuration

In order to display only GraphQL related requests, the profiler will filter requests based on the requested url.  
By default, it will display requests matching the configured endpoint url (ie. The route `overblog_graphql_endpoint`).  

If you need to change the behavior (for example if you have parameters in your endpoint url), you can change the matching with the following option:

```yaml
overblog_graphql:
    profiler:
        query_match:  my_string_to_match
```

In the example above, only the requests containing the string `my_string_to_match` will be displayed.  