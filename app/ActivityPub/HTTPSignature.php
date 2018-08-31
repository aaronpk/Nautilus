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

  public static function parseSignatureHeader($signature) {
    $parts = explode(',', $signature);
    $signatureData = [];

    foreach($parts as $part) {
      if(preg_match('/(.+)="(.+)"/', $part, $match)) {
        $signatureData[$match[1]] = $match[2];
      }
    }

    if(!isset($signatureData['keyId'])) {
      return [
        'error' => 'No keyId was found in the signature header. Found: '.implode(', ', array_keys($signatureData))
      ];
    }

    if(!\p3k\url\is_url($signatureData['keyId'])) {
      return [
        'error' => 'keyId is not a URL: '.$signatureData['keyId']
      ];
    }

    if(!isset($signatureData['headers']) || !isset($signatureData['signature'])) {
      return [
        'error' => 'Signature is missing headers or signature parts'
      ];
    }

    return $signatureData;
  }

  public static function verify($publicKey, $signatureData, $inputHeaders, $path, $body) {
    // TODO: Not sure how to determine the algorithm used, but everyone seems to use SHA256 right now
    $digest = 'SHA-256='.base64_encode(hash('sha256', $body, true));

    $headersToSign = [];
    foreach(explode(' ',$signatureData['headers']) as $h) {
      if($h == '(request-target)') {
        $headersToSign[$h] = 'post '.$path;
      } elseif($h == 'digest') {
        $headersToSign[$h] = $digest;
      } elseif(isset($inputHeaders[$h][0])) {
        $headersToSign[$h] = $inputHeaders[$h][0];
      }
    }
    $signingString = self::_headersToSigningString($headersToSign);

    $verified = openssl_verify($signingString, base64_decode($signatureData['signature']), $publicKey, OPENSSL_ALGO_SHA256);

    return [$verified, $signingString];
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
