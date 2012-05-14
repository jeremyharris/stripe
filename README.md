# CakePHP Stripe Plugin

This plugin provides the basic functionality for integrating with the [Stripe][1]
payment gateway.

## Requirements

* PHP 5.2.8+
* CAKEPHP 2.0+
* A [Stripe][1] Account

## Installation

### Manual

* Download this: http://github.com/jeremyharris/stripe/zipball/master
* Unzip that download.
* Copy the resulting folder to app/Plugin/Stripe/

### GIT Submodule

In your app directory type:

    git submodule add git://github.com/jeremyharris/stripe.git Plugin/Stripe
    git submodule update --init

### GIT Clone

In your plugin directory type:

    git clone git://github.com/jeremyharris/stripe.git Stripe

## Usage

Enable the plugin in your `app/Config/bootstrap.php` by adding the line
`CakePlugin::load('Stripe');` or `CakePlugin::loadAll();`.

Create an entry in your `app/Config/database.php`:

``` php
public $stripe = array(
    'datasource' => 'Stripe.StripeSource',
    'api_key' => 'YOUR API KEY HERE',
);
```

Run a charge, create a model that extends the StripeAppModel:

``` php
<?php
App::uses('StripeAppModel', 'Stripe.Model');
class Authorize extends StripeAppModel {

    public $path = '/charges'; // Specify the API URL pattern

    public function charge($data = null) {
        $this->save(array(
            'amount' => '500',
            'currency' => 'usd',
            'card' => array(
                'name' => 'Your Name',
                'number' => '4242424242424242',
                'cvc' => '123',
                'exp_month' => '01',
                'exp_year' => '2020',
            ),
        ));
    }

}
```

For more options please consult the [stripe api docs][2].

## Limitations

* Doesn't support updating plans because Stripe's API doesn't
* Currently doesn't support listing all plans

## License

Copyright (c) 2012 Jeremy Harris

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.


[1]: http://stripe.com
[2]: https://stripe.com/docs/api
