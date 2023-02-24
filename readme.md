## Hooks ##

#### Filters ####

`tf/social/show_ui` Display admin UI.

`tf/social/provider/request_parameters`

`tf/social/provider/post_array`

`tf/social/provider/limit_per_account`

`tf/social/provider/wp_remote_parameters`

`tf/social/settings/instructions`

#### Actions ####

`tf/social/account/deleting`

`tf/social/account/deleted`

`tf/social/accounts/added`

`tf/social/provider/post_inserted`

## Todos ##

- Om access token har gått ut vid synk, notifiera till angiven mail och i admin notice. Mailfält under inställningar.
- Lägg till stöd för taggar på Instagram och Twitter.

Följande packages bör uppdateras och säkerställas att allting fortfarande fungerar:

- league/oauth2-instagram
- league/oauth2-linkedin

## Running PHAN

The project has a PHAN config with a very low level. You may run it with the following command:

```
./vendor/bin/phan --progress-bar -o analysis.txt --allow-polyfill-parser
```

After running the above command you can run the following to get a summary of the types of errors and warnings found:

```
cat analysis.txt | cut -d ' ' -f2 | sort | uniq -c | sort -n -r
```

More info: https://github.com/phan/phan/wiki/Tutorial-for-Analyzing-a-Large-Sloppy-Code-Base

TODO: Use a more strict config. Set `minimum_severity` to `SEVERITY_NORMAL` and do other adjustments as mentioned in ["Slowly Ramp-Up the Strength of the Analysis"](https://github.com/phan/phan/wiki/Tutorial-for-Analyzing-a-Large-Sloppy-Code-Base#slowly-ramp-up-the-strength-of-the-analysis).
