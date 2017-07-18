# RIT webservices client library for PHP

## Current status

Library is not feature complete (see all `@todo`'s in [RIT_Webservices.php](RIT_Webservices.php)) and its interface isn't stable. You can join efforts to improve it or use it as an excellent example on how to use standard PHP SoapClient class to communicate with RIT webservices (and build your own lib).

## License

See [LICENSE.md](LICENSE.md). You can choose between two more and less commercial-friendly licenses.

## Generating documentation from the source

Download and use [apigen](https://github.com/ApiGen/ApiGen) to generate docs from the source, for example:

```
php apigen.phar generate -d docs -s rit-webservices
```

Above command generates documentation in folder `docs` from all files in folder `rit-webservices`.
