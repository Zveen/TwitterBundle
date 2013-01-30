ZveenTwitterBundle
=================

This Bundle provides a simple service to use Twitter REST API in Symfony2 applications.

Prerequisites
-------------

This version requires at least Symfony 2.1


Usage
-------------
Add into app/config.yml following. Check DependencyInjection/Configuration.php for all available options...

```yml
zveen_twitter:
  consumerKey: xxxxxxxxxxxxxxx
  consumerSecret: xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
  debug: true
  checkSSL: false
```

Look into [Twitter class](https://github.com/Zveen/TwitterBundle/blob/master/Services/Twitter.php) for more info.

```php
public function twitterAction(Request $request){  
    $twitter = $this->get('zveen.twitter');
    if(!$twitter->canQueryAPI()){
        $redirect = $twitter->handleLogin($request);
        if($redirect != null)
            return new RedirectResponse($redirect);
    }
    $result = $twitter->apiPost('https://api.twitter.com/1.1/statuses/update.json', array('status' => 'qwerqwerqw'));
    if($result->error)
        throw new \Exception($result->errorMessage);
   
    exit();
}
```

License
-------

This bundle is under the MIT license. See the complete license in the bundle:

    Resources/meta/LICENSE
