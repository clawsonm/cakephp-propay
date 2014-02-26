cakephp-propay
==============

wrapper for propay SOAP service in cakephp. Source WSDL is located at [http://protectpay.propay.com/API/SPS.svc?wsdl](http://protectpay.propay.com/API/SPS.svc?wsdl). However to test your implementation you must use their test service located at [http://protectpaytest.propay.com/api/sps.svc?wsdl](http://protectpaytest.propay.com/api/sps.svc?wsdl)

Installation
=============

Composer
---------------

    composer install

or if you're already using composer

    composer update

then

    path/to/plugin/setup.sh

or specify a path as the first parameter e.g.

    Plugin/ProPay/setup.sh Plugin/ProPay/generated

NOTE: setup.sh uses the production WSDL URL. It assumes that you will specify the correct URL, test or production in your code, see below.

Non Composer
-----------------

You must generate the SOAP client with wsdl2phpgenerator from [github.com/wsdl2phpgenerator/wsdl2phpgenerator](github.com/wsdl2phpgenerator/wsdl2phpgenerator). If you use [Composer](getcomposer.org) it should already be downloaded by composer. Place the generated code in generated/.

e.g. Run:

    ./vender/bin/wsdl2php -i http://protectpay.propay.com/API/SPS.svc?wsdl -o generated/

    ./vender/bin/wsdl2php -i http://protectpaytest.propay.com/api/sps.svc?wsdl -o generated/

Configuration
======================

Please bootstrap the plugin when you load it. e.g.:

    CakePlugin::load('ProPay', array('bootstrap' => true));

ProPay.wsdlUrl
------------------

This is the WSDL URL used by the SOAP client to determine the correct URL to communicate with.

ProPay.generatedLib
---------------------

This allows you to set a path to the generated SOAP client files for the autoloader (which is loaded in the plugin's bootstrap) to use.

ProPay.authenticationToken
-------------------------------

This is your ProPay Authentication Token. If you use ProPay you know what this is. Please do not include this in your repo. Put it in a separate file that you exclude from your repo.

ProPay.billerAccountId
------------------------------

This is your ProPay BillerAccountId. If you use ProPay you know what this is. Please do not include this in your repo. Put it in a separate file that you exclude from your repo.

License
=========================

MIT License

See LICENSE file.
