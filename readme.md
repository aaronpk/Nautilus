# MyActivity.stream

## Setup

Create a user account

```
$ php artisan user:create username email name
```

Fetch the JSON profile to serve on your domain

```
$ curl https://myactivity.stream/username.json
```

Save that file on your server in a file named:

`https://example.com/.well-known/user.json`

Save the below as a file named: `https://example.com/.well-known/host-meta`

```
<?xml version="1.0"?>
<XRD xmlns="http://docs.oasis-open.org/ns/xri/xrd-1.0">
  <Link rel="lrdd" type="application/xrd+xml" template="https://example.com/.well-known/user.json?resource={uri}"/>
</XRD>
```

