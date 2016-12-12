Debug
=====

Query debug information
------------------------

To enabled or disabled debug information:

```yaml
# app/config/config.yml

overblog_graphql:
    definitions:
        show_debug_info: true # Debug info is disabled by default
```

here an example of an answer when debug information is enabled
```json
{
  "data": [{"isEnabled": true}],
  "extensions": {
    "debug": {
      "executionTime": "40 ms",
      "memoryUsage": "1.00 MiB"
    }
  }
}
```

Config validation
------------------

Enabled or disabled the config validation (this should be limited to debug environments) 
```yaml
#app/config/config.yml

overblog_graphql:
    definitions:
        config_validation: %kernel.debug%
```
