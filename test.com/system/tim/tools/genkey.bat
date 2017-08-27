@set OPENSSL_CONF=openssl.cnf
@openssl.exe ec -outform PEM -inform PEM -in private_key -out ec_key.pem
