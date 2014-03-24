#!/bin/sh

# Attempts to run the wsdl2php generator to create the SOAP client classes required by the plugin
# intended to be run from the project root not the plugin root

path=generated/
if [ -z $1 ] ; then
	echo "defaulting to $path"
	else path=$1
fi

result1=$(./vendor/bin/wsdl2php -i http://protectpaytest.propay.com/api/sps.svc?wsdl -o $path)

result2=$(sed -i -e "s/class TempTokenResult$/class TempTokenResut extends TempTokensForPayerEditResult/" $path/TempTokenResult.php)

echo $result1
echo $result2