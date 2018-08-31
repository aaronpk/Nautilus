<?php
namespace App\ActivityPub;

use Log;
use App\User;
use DateTime;

class HTTPSignature {

  public static function sign(User &$user, $url, $body=false, $addlHeaders=[]) {
    if($body)
      $digest = self::_digest($body);

    $headers = self::_headersToSign($url, $body ? $digest : false);

    $headers = array_merge($headers, $addlHeaders);

    $stringToSign = self::_headersToSigningString($headers);
    Log::info('String to sign: '.$stringToSign);

    $signedHeaders = implode(' ', array_map('strtolower', array_keys($headers)));
    Log::info('Signed headers: '.$signedHeaders);

    $key = openssl_pkey_get_private($user->private_key);

    openssl_sign($stringToSign, $signature, $key, OPENSSL_ALGO_SHA256);
    $signature = base64_encode($signature);

    $signatureHeader = 'keyId="'.env('APP_URL').'/'.$user->username.'#key",headers="'.$signedHeaders.'",algorithm="rsa-sha256",signature="'.$signature.'"';

    Log::info('Signature: '.$signatureHeader);

    unset($headers['(request-target)']);

    $headers['Signature'] = $signatureHeader;

    return self::_headersToCurlArray($headers);
  }

  private static function _headersToSigningString($headers) {
    return implode("\n", array_map(function($k, $v){
             return strtolower($k).': '.$v;
           }, array_keys($headers), $headers));
  }

  private static function _headersToCurlArray($headers) {
    return array_map(function($k, $v){
             return "$k: $v";
           }, array_keys($headers), $headers);
  }

  private static function _digest($body) {
    return base64_encode(hash('sha256', $body, true));
  }

  protected static function _headersToSign($url, $digest=false) {
    $date = new DateTime('UTC');

    $headers = [
      '(request-target)' => 'post '.parse_url($url, PHP_URL_PATH),
      'Date' => $date->format('D, d M Y H:i:s \G\M\T'),
      'Host' => parse_url($url, PHP_URL_HOST),
      'Content-Type' => 'application/activity+json',
    ];

    if($digest)
      $headers['Digest'] = 'SHA-256='.$digest;

    return $headers;
  }

}
