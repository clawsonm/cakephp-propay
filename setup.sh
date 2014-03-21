#!/bin/sh

# Attempts to run the wsdl2php generator to create the SOAP client classes required by the plugin
# intended to be run from the project root not the plugin root

path=generated/
if [ -z $1 ] ; then
	echo "defaulting to $path"
	else path=$1
fi

result=$(./vendor/bin/wsdl2php -i http://protectpaytest.propay.com/api/sps.svc?wsdl -o $path)

echo $result