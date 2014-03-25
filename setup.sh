#!/bin/sh

# Attempts to run the wsdl2php generator to create the SOAP client classes required by the plugin
# intended to be run from the project root not the plugin root

path=generated/
if [ -z $1 ] ; then
	echo "defaulting to $path"
	else path=$1
fi

echo "Generating Soap Client"
./vendor/bin/wsdl2php -i http://protectpaytest.propay.com/api/sps.svc?wsdl -o $path
if [ $? -ne 0 ]; then
	echo "Failed generation"
	exit 1;
fi

echo "Editing TempTokenResult as a workaround for bad generation of TempTokenResult on some versions of wsdl2php"
cat > $path/TempTokenResult.php <<EOF
<?php

class TempTokenResult extends TempTokensForPayerEditResult
{

}
EOF
if [ $? -ne 0 ]; then
	echo "Failed to edit TempTokenResult.php. GetTempToken might not work."
	exit 1;
fi
echo "success"