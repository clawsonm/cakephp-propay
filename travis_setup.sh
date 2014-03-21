#!/bin/bash
if [[ "$PHPCS" != "1" ]] ; then
	cd ../cakephp/app
	./Plugin/ProPay/setup.sh Plugin/ProPay/generated
	ls -al Plugin/ProPay/generated
	cd ../../cakephp-propay
fi