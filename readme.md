# Nautilus

This project is meant to run as a standalone service to deliver posts from your own website to ActivityPub followers. You can run your own website at your own domain, and this service can handle the ActivityPub-specific pieces needed to let people follow your own website from Mastodon or other compatible services.

## Setup

These instructions assume you've deployed this proxy service to `proxy.example.com` and that your primary domain that you want to use for your ActivityPub identity is `example.com`.

Create a user account in this service

```
$ php artisan user:create username email name
```

Fetch the JSON profile from this service to serve on your domain

```
$ curl https://proxy.example.com/username.json
```

Save that file on your own website in a file named:

`https://example.com/.well-known/user.json`

Save the below as a file named: `https://example.com/.well-known/host-meta`

```
<?xml version="1.0"?>
<XRD xmlns="http://docs.oasis-open.org/ns/xri/xrd-1.0">
  <Link rel="lrdd" type="application/xrd+xml" template="https://example.com/.well-known/user.json?resource={uri}"/>
</XRD>
```

In order for the permalinks of your posts to also live on your domain, you'll need to create an HTTP redirect rule from your server to this service. In nginx, you can create a rewrite rule such as this:

```
rewrite ^/activitypub/(.+) https://proxy.example.com/username/$1;
```

Your posts will be identified with a URL on your own domain this way, so that you continue to own the permalinks. This rewrite is required in order for Mastodon and other servers to be able to fetch the JSON version of your posts.


## Running

This project uses the Laravel framework. Please refer to the [Laravel Configuration Guide](https://laravel.com/docs/5.6/configuration) for more details.

You'll need to copy `.env.example` to `.env` and set up your database connection and queue driver there. It is recommended to use an asynchronous queuing driver such as `redis` or `database`, otherwise your server may time out if you have many followers.

Once your queue and database are configured, you can run the worker in the background via:

```
$ php artisan queue:work
```

## Usage

### Following 

Once your domain is configured to delegate the ActivityPub profile bits to this service, you can now be followed by people on Mastodon. They will be able to search for `yourusername@example.com` and click "Follow".

### Delivery

To create a post to deliver to all your followers, you'll need to create a token that you can use to tell this service to deliver a post. From the command line, run:

```
$ php artisan token:generate yourusername
```

This will output a new token you can use in a request.

To create a post and deliver it to your followers, send a POST request with the token to this service's post creation endpoint. For example:

```php
$ch = curl_init('https://proxy.example.com/yourusername/micropub');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
  'Authorization: Bearer xxxxxxxxxxxxxxxxxxx'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
  'h' => 'entry',
  'content' => 'The content of your post'
]);
curl_exec($ch);
```

The background worker will deliver the post to all of your followers.

## Todo

This is currently a pretty barebones ActivityPub implementation. There are still a few things needed to turn this into a full ActivityPub proxy service.

* [ ] Include photos and videos in posts
* [ ] Support creating replies
* [ ] Support creating likes
* [ ] Support creating reposts
* [ ] Support updating existing posts
* [ ] Support deleting posts
* [ ] Follow other users
* [ ] Deliver posts created by people you follow into somewhere you can read them - likely by pushing them into an Aperture channel, or providing a feed you can subscribe to in an RSS reader
  * Note that I will not implement this as a single feed, but rather you'll be able to group people you follow into different channels, since [a single timeline is unsustainable](https://aaronparecki.com/2018/04/20/46/indieweb-reader-my-new-home-on-the-internet)

