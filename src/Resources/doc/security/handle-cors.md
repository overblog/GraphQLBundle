Handle CORS
===========

The bundle comes out of the box with a generic and simple CORS (Cross-Origin Resource Sharing) handler 
but we recommends using [NelmioCorsBundle](https://github.com/nelmio/NelmioCorsBundle) for more flexibility... 

The handler is disabled by default. To enabled it:

```yaml
overblog_graphql:
    # ...
    security:
        handle_cors: true
```

Here the values of the headers that will be returns on preflight request:

Headers                          | Value
-------------------------------- | ---------------------------------------
Access-Control-Allow-Origin      | the value of the request Origin header
Access-Control-Allow-Credentials | 'true'
Access-Control-Allow-Headers     | 'Content-Type, Authorization'
Access-Control-Allow-Methods     | 'OPTIONS, GET, POST'
Access-Control-Max-Age           | 3600
