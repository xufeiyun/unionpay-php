# unionpay-php

> The UnionPay Library for php language.

# UnionPayment

> The common unionpay library for union-pay-business.

# RSACryptor

> The RSA cryptor library using public & private key pair to encrypt & decrypt strings.

> sample usages:
> 
> Do NOT send two key files to each other! One has one key file, and the other has another key file.
>
        $pubKey = '<file_content_of_rsa_public_key.pem>'; // public key
        $pvtKey = '<file_content_of_rsa_private_key.pem>'; // private key

        $password = RSACryptor::publicEncrypt('xufeiyun', $pubKey); // encrypt, the $password does CHANGED after every encryption
        $passtext = RSACryptor::privateDecrypt($password, $pvtKey); // decrypt
        var_dump($password);
        var_dump($passtext);

        $password = RSACryptor::privateEncrypt('EricXu', $pvtKey); // encrypt, the $password does NOT CHANGED after every encryption
        $passtext = RSACryptor::publicDecrypt($password, $pubKey); // decrypt
        var_dump($password);
        var_dump($passtext);
>
