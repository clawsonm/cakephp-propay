cakephp-propay
==============

wrapper for propay SOAP service in cakephp. Source WSDL is located at [http://protectpay.propay.com/API/SPS.svc?wsdl](http://protectpay.propay.com/API/SPS.svc?wsdl).

Installation
=============

You must generate the SOAP client with wsdl2phpgenerator from [github.com/wsdl2phpgenerator/wsdl2phpgenerator](github.com/wsdl2phpgenerator/wsdl2phpgenerator). Place the generated code in generated/.

e.g. Run:

    /path/to/wsdl2phpgenerator-2.2.2.phar -i http://protectpay.propay.com/API/SPS.svc?wsdl -o generated/