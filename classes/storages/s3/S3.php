<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace local_alternative_file_system\storages\s3;

/**
 * phpcs:disable
 *
 * Amazon S3 PHP class
 *
 * @package    local_alternative_file_system
 * @copyright  2024 Eduardo Kraus {@link http://eduardokraus.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @link    http://undesigned.org.za/2007/10/22/amazon-s3-php-class
 * @version 0.5.1
 */
class S3 {
    // ACL flags
    const ACL_PRIVATE = 'private';
    const ACL_PUBLIC_READ = 'public-read';
    const ACL_PUBLIC_READ_WRITE = 'public-read-write';
    const ACL_AUTHENTICATED_READ = 'authenticated-read';

    const STORAGE_CLASS_STANDARD = 'STANDARD';
    const STORAGE_CLASS_RRS = 'REDUCED_REDUNDANCY';

    const SSE_NONE = '';
    const SSE_AES256 = 'AES256';

    /**
     * The AWS Access key
     *
     * @var string
     * @access private
     * @static
     */
    private static $__accessKey = null;

    /**
     * AWS Secret Key
     *
     * @var string
     * @access private
     * @static
     */
    private static $__secretKey = null;

    /**
     * Default delimiter to be used, for example while getBucket().
     *
     * @var string
     * @access public
     * @static
     */
    public static $defDelimiter = null;

    /**
     * AWS URI
     *
     * @var string
     * @acess public
     * @static
     */
    public static $endpoint = 's3.amazonaws.com';

    /**
     * Proxy information
     *
     * @var null|array
     * @access public
     * @static
     */
    public static $proxy = null;

    /**
     * Use SSL validation?
     *
     * @var bool
     * @access public
     * @static
     */
    public static $useSSLValidation = true;

    /**
     * Use SSL version
     */
    public static $useSSLVersion = CURL_SSLVERSION_TLSv1;

    /**
     * Use PHP exceptions?
     *
     * @var bool
     * @access public
     * @static
     */
    public static $useExceptions = false;

    /**
     * Time offset applied to time()
     *
     * @access private
     * @static
     */
    private static $__timeOffset = 0;

    /**
     * SSL client key
     *
     * @var bool
     * @access public
     * @static
     */
    public static $sslKey = null;

    /**
     * SSL client certfificate
     *
     * @var string
     * @acess public
     * @static
     */
    public static $sslCert = null;

    /**
     * SSL CA cert (only required if you are having problems with your system CA cert)
     *
     * @var string
     * @access public
     * @static
     */
    public static $sslCACert = null;

    /**
     * AWS Key Pair ID
     *
     * @var string
     * @access private
     * @static
     */
    private static $__signingKeyPairId = null;

    /**
     * Key resource, freeSigningKey() must be called to clear it from memory
     *
     * @var bool
     * @access private
     * @static
     */
    private static $__signingKeyResource = false;

    /**
     * Constructor - if you're not using the class statically
     *
     * @param string $accessKey Access key
     * @param string $secretKey Secret key
     * @param string $endpoint  Amazon URI
     *
     * @return void
     */
    public function __construct($accessKey = null, $secretKey = null, $endpoint = 's3.amazonaws.com') {
        if ($accessKey !== null && $secretKey !== null)
            self::setAuth($accessKey, $secretKey);
        self::$endpoint = $endpoint;
    }

    /**
     * Set the service endpoint
     *
     * @param string $host Hostname
     *
     * @return void
     */
    public function setEndpoint($host) {
        self::$endpoint = $host;
    }

    /**
     * Set AWS access key and secret key
     *
     * @param string $accessKey Access key
     * @param string $secretKey Secret key
     *
     * @return void
     */
    public static function setAuth($accessKey, $secretKey) {
        self::$__accessKey = $accessKey;
        self::$__secretKey = $secretKey;
    }

    /**
     * Check if AWS keys have been set
     *
     * @return boolean
     */
    public static function hasAuth() {
        return (self::$__accessKey !== null && self::$__secretKey !== null);
    }

    /**
     * Set SSL on or off
     *
     * @param boolean $validate SSL certificate validation
     *
     * @return void
     */
    public static function setSSL( $validate = true) {
        self::$useSSLValidation = $validate;
    }

    /**
     * Set SSL client certificates (experimental)
     *
     * @param string $sslCert   SSL client certificate
     * @param string $sslKey    SSL client key
     * @param string $sslCACert SSL CA cert (only required if you are having problems with your system CA cert)
     *
     * @return void
     */
    public static function setSSLAuth($sslCert = null, $sslKey = null, $sslCACert = null) {
        self::$sslCert = $sslCert;
        self::$sslKey = $sslKey;
        self::$sslCACert = $sslCACert;
    }

    /**
     * Set proxy information
     *
     * @param string $host   Proxy hostname and port (localhost:1234)
     * @param string $user   Proxy username
     * @param string $pass   Proxy password
     * @param  $type
     *
     * @return void
     */
    public static function setProxy($host, $user = null, $pass = null, $type = CURLPROXY_SOCKS5) {
        self::$proxy = array('host' => $host, 'type' => $type, 'user' => $user, 'pass' => $pass);
    }

    /**
     * Set the error mode to exceptions
     *
     * @param boolean $enabled Enable exceptions
     *
     * @return void
     */
    public static function setExceptions($enabled = true) {
        self::$useExceptions = $enabled;
    }

    /**
     * Set AWS time correction offset (use carefully)
     *
     * This can be used when an inaccurate system time is generating
     * invalid request signatures.  It should only be used as a last
     * resort when the system time cannot be changed.
     *
     * @param string $offset Time offset (set to zero to use AWS server time)
     *
     * @return void
     */
    public static function setTimeCorrectionOffset($offset = 0) {
        if ($offset == 0) {
            $rest = new S3Request('HEAD');
            $rest = $rest->getResponse();
            $awstime = $rest->headers['date'];
            $systime = time();
            $offset = $systime > $awstime ? -($systime - $awstime) : ($awstime - $systime);
        }
        self::$__timeOffset = $offset;
    }

    /**
     * Set signing key
     *
     * @param string $keyPairId  AWS Key Pair ID
     * @param string $signingKey Private Key
     * @param boolean $isFile    Load private key from file, set to false to load string
     *
     * @return boolean
     */
    public static function setSigningKey($keyPairId, $signingKey, $isFile = true) {
        self::$__signingKeyPairId = $keyPairId;
        if ((self::$__signingKeyResource = openssl_pkey_get_private($isFile ?
                file_get_contents($signingKey) : $signingKey)) !== false) return true;
        self::__triggerError('S3::setSigningKey(): Unable to open load private key: ' . $signingKey, __FILE__, __LINE__);
        return false;
    }

    /**
     * Free signing key from memory, MUST be called if you are using setSigningKey()
     *
     * @return void
     */
    public static function freeSigningKey() {
        if (self::$__signingKeyResource !== false)
            openssl_free_key(self::$__signingKeyResource);
    }

    /**
     * Internal error handler
     *
     * @internal Internal error handler
     *
     * @param string $message Error message
     * @param string $file    Filename
     * @param integer $line   Line number
     * @param integer $code   Error code
     *
     * @return void
     */
    private static function __triggerError($message, $file, $line, $code = 0) {
        if (self::$useExceptions) {
           // throw new \Exception($message, $file, $line, $code);
        } else {
            trigger_error($message, E_USER_WARNING);
        }
    }

    /**
     * Get a list of buckets
     *
     * @param boolean $detailed Returns detailed bucket list when true
     *
     * @return array | false
     */
    public static function listBuckets($detailed = false) {
        $rest = new S3Request('GET', '', '', self::$endpoint);
        $rest = $rest->getResponse();
        if ($rest->error === false && $rest->code !== 200)
            $rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
        if ($rest->error !== false) {
            self::__triggerError(sprintf("S3::listBuckets(): [%s] %s", $rest->error['code'],
                $rest->error['message']), __FILE__, __LINE__);
            return false;
        }
        $results = array();
        if (!isset($rest->body->Buckets)) return $results;

        if ($detailed) {
            if (isset($rest->body->Owner, $rest->body->Owner->ID, $rest->body->Owner->DisplayName))
                $results['owner'] = array(
                    'id' => (string)$rest->body->Owner->ID, 'name' => (string)$rest->body->Owner->DisplayName
                );
            $results['buckets'] = array();
            foreach ($rest->body->Buckets->Bucket as $b)
                $results['buckets'][] = array(
                    'name' => (string)$b->Name, 'time' => strtotime((string)$b->CreationDate)
                );
        } else
            foreach ($rest->body->Buckets->Bucket as $b) $results[] = (string)$b->Name;

        return $results;
    }

    /**
     * Get contents for a bucket
     *
     * If maxKeys is null this method will loop through truncated result sets
     *
     * @param string $bucket                Bucket name
     * @param string $prefix                Prefix
     * @param string $marker                Marker (last file listed)
     * @param string $maxKeys               Max keys (maximum number of keys to return)
     * @param string $delimiter             Delimiter
     * @param boolean $returnCommonPrefixes Set to true to return CommonPrefixes
     *
     * @return array | false
     */
    public static function getBucket($bucket, $prefix = null, $marker = null, $maxKeys = null, $delimiter = null, $returnCommonPrefixes = false) {
        $rest = new S3Request('GET', $bucket, '', self::$endpoint);
        if ($maxKeys == 0) $maxKeys = null;
        if ($prefix !== null && $prefix !== '') $rest->setParameter('prefix', $prefix);
        if ($marker !== null && $marker !== '') $rest->setParameter('marker', $marker);
        if ($maxKeys !== null && $maxKeys !== '') $rest->setParameter('max-keys', $maxKeys);
        if ($delimiter !== null && $delimiter !== '') $rest->setParameter('delimiter', $delimiter);
        else if (!empty(self::$defDelimiter)) $rest->setParameter('delimiter', self::$defDelimiter);
        $response = $rest->getResponse();
        if ($response->error === false && $response->code !== 200)
            $response->error = array('code' => $response->code, 'message' => 'Unexpected HTTP status');
        if ($response->error !== false) {
            self::__triggerError(sprintf("S3::getBucket(): [%s] %s",
                $response->error['code'], $response->error['message']), __FILE__, __LINE__);
            return false;
        }

        $results = array();

        $nextMarker = null;
        if (isset($response->body, $response->body->Contents))
            foreach ($response->body->Contents as $c) {
                $results[(string)$c->Key] = array(
                    'name' => (string)$c->Key,
                    'time' => strtotime((string)$c->LastModified),
                    'size' => (int)$c->Size,
                    'hash' => substr((string)$c->ETag, 1, -1)
                );
                $nextMarker = (string)$c->Key;
            }

        if ($returnCommonPrefixes && isset($response->body, $response->body->CommonPrefixes))
            foreach ($response->body->CommonPrefixes as $c)
                $results[(string)$c->Prefix] = array('prefix' => (string)$c->Prefix);

        if (isset($response->body, $response->body->IsTruncated) &&
            (string)$response->body->IsTruncated == 'false') return $results;

        if (isset($response->body, $response->body->NextMarker))
            $nextMarker = (string)$response->body->NextMarker;

        // Loop through truncated results if maxKeys isn't specified
        if ($maxKeys == null && $nextMarker !== null && (string)$response->body->IsTruncated == 'true')
            do {
                $rest = new S3Request('GET', $bucket, '', self::$endpoint);
                if ($prefix !== null && $prefix !== '') $rest->setParameter('prefix', $prefix);
                $rest->setParameter('marker', $nextMarker);
                if ($delimiter !== null && $delimiter !== '') $rest->setParameter('delimiter', $delimiter);

                if (($response = $rest->getResponse()) == false || $response->code !== 200) break;

                if (isset($response->body, $response->body->Contents))
                    foreach ($response->body->Contents as $c) {
                        $results[(string)$c->Key] = array(
                            'name' => (string)$c->Key,
                            'time' => strtotime((string)$c->LastModified),
                            'size' => (int)$c->Size,
                            'hash' => substr((string)$c->ETag, 1, -1)
                        );
                        $nextMarker = (string)$c->Key;
                    }

                if ($returnCommonPrefixes && isset($response->body, $response->body->CommonPrefixes))
                    foreach ($response->body->CommonPrefixes as $c)
                        $results[(string)$c->Prefix] = array('prefix' => (string)$c->Prefix);

                if (isset($response->body, $response->body->NextMarker))
                    $nextMarker = (string)$response->body->NextMarker;

            } while ($response !== false && (string)$response->body->IsTruncated == 'true');

        return $results;
    }

    /**
     * Put a bucket
     *
     * @param string $bucket   Bucket name
     * @param $acl
     * @param string $location Set as "EU" to create buckets hosted in Europe
     *
     * @return boolean
     */
    public static function putBucket($bucket, $acl = self::ACL_PRIVATE, $location = false) {
        $rest = new S3Request('PUT', $bucket, '', self::$endpoint);
        $rest->setAmzHeader('x-amz-acl', $acl);

        if ($location !== false) {
            $dom = new \DOMDocument;
            $createBucketConfiguration = $dom->createElement('CreateBucketConfiguration');
            $locationConstraint = $dom->createElement('LocationConstraint', $location);
            $createBucketConfiguration->appendChild($locationConstraint);
            $dom->appendChild($createBucketConfiguration);
            $rest->data = $dom->saveXML();
            $rest->size = strlen($rest->data);
            $rest->setHeader('Content-Type', 'application/xml');
        }
        $rest = $rest->getResponse();

        if ($rest->error === false && $rest->code !== 200)
            $rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
        if ($rest->error !== false) {
            self::__triggerError(sprintf("S3::putBucket({$bucket}, {$acl}, {$location}): [%s] %s",
                $rest->error['code'], $rest->error['message']), __FILE__, __LINE__);
            return false;
        }
        return true;
    }

    /**
     * Delete an empty bucket
     *
     * @param string $bucket Bucket name
     *
     * @return boolean
     */
    public static function deleteBucket($bucket) {
        $rest = new S3Request('DELETE', $bucket, '', self::$endpoint);
        $rest = $rest->getResponse();
        if ($rest->error === false && $rest->code !== 204)
            $rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
        if ($rest->error !== false) {
            self::__triggerError(sprintf("S3::deleteBucket({$bucket}): [%s] %s",
                $rest->error['code'], $rest->error['message']), __FILE__, __LINE__);
            return false;
        }
        return true;
    }

    /**
     * Create input info array for putObject()
     *
     * @param string $file  Input file
     * @param mixed $md5sum Use MD5 hash (supply a string if you want to use your own)
     *
     * @return array | false
     */
    public static function inputFile($file, $md5sum = true) {
        if (!file_exists($file) || !is_file($file) || !is_readable($file)) {
            self::__triggerError('S3::inputFile(): Unable to open input file: ' . $file, __FILE__, __LINE__);
            return false;
        }
        clearstatcache(false, $file);
        return array('file' => $file, 'size' => filesize($file), 'md5sum' => $md5sum !== false ?
            (is_string($md5sum) ? $md5sum : base64_encode(md5_file($file, true))) : '');
    }

    /**
     * Create input array info for putObject() with a resource
     *
     * @param string $resource    Input resource to read from
     * @param integer $bufferSize Input byte size
     * @param string $md5sum      MD5 hash to send (optional)
     *
     * @return array | false
     */
    public static function inputResource(&$resource, $bufferSize = false, $md5sum = '') {
        if (!is_resource($resource) || (int)$bufferSize < 0) {
            self::__triggerError('S3::inputResource(): Invalid resource or buffer size', __FILE__, __LINE__);
            return false;
        }

        // Try to figure out the bytesize
        if ($bufferSize === false) {
            if (fseek($resource, 0, SEEK_END) < 0 || ($bufferSize = ftell($resource)) === false) {
                self::__triggerError('S3::inputResource(): Unable to obtain resource size', __FILE__, __LINE__);
                return false;
            }
            fseek($resource, 0);
        }

        $input = array('size' => $bufferSize, 'md5sum' => $md5sum);
        $input['fp'] =& $resource;
        return $input;
    }

    /**
     * Put an object
     *
     * @param mixed $input                   Input data
     * @param string $bucket                 Bucket name
     * @param string $uri                    Object URI
     * @param \ $acl
     * @param array $metaHeaders             Array of x-amz-meta-* headers
     * @param array $requestHeaders          Array of request headers or content type as a string
     * @param  $storageClass
     * @param  $serverSideEncryption
     *
     * @return boolean
     */
    public static function putObject($input, $bucket, $uri, $acl = self::ACL_PRIVATE, $metaHeaders = array(), $requestHeaders = array(), $storageClass = self::STORAGE_CLASS_STANDARD, $serverSideEncryption = self::SSE_NONE) {
        if ($input === false) return false;
        $rest = new S3Request('PUT', $bucket, $uri, self::$endpoint);

        if (!is_array($input)) $input = array(
            'data' => $input, 'size' => strlen($input),
            'md5sum' => base64_encode(md5($input, true))
        );

        // Data
        if (isset($input['fp']))
            $rest->fp =& $input['fp'];
        elseif (isset($input['file']))
            $rest->fp = @fopen($input['file'], 'rb');
        elseif (isset($input['data']))
            $rest->data = $input['data'];

        // Content-Length (required)
        if (isset($input['size']) && $input['size'] >= 0)
            $rest->size = $input['size'];
        else {
            if (isset($input['file'])) {
                clearstatcache(false, $input['file']);
                $rest->size = filesize($input['file']);
            } elseif (isset($input['data']))
                $rest->size = strlen($input['data']);
        }

        // Custom request headers (Content-Type, Content-Disposition, Content-Encoding)
        if (is_array($requestHeaders))
            foreach ($requestHeaders as $h => $v)
                strpos($h, 'x-amz-') === 0 ? $rest->setAmzHeader($h, $v) : $rest->setHeader($h, $v);
        elseif (is_string($requestHeaders)) // Support for legacy contentType parameter
            $input['type'] = $requestHeaders;

        // Content-Type
        if (!isset($input['type'])) {
            if (isset($requestHeaders['Content-Type']))
                $input['type'] =& $requestHeaders['Content-Type'];
            elseif (isset($input['file']))
                $input['type'] = self::__getMIMEType($input['file']);
            else
                $input['type'] = 'application/octet-stream';
        }

        if ($storageClass !== self::STORAGE_CLASS_STANDARD) // Storage class
            $rest->setAmzHeader('x-amz-storage-class', $storageClass);

        if ($serverSideEncryption !== self::SSE_NONE) // Server-side encryption
            $rest->setAmzHeader('x-amz-server-side-encryption', $serverSideEncryption);

        // We need to post with Content-Length and Content-Type, MD5 is optional
        if ($rest->size >= 0 && ($rest->fp !== false || $rest->data !== false)) {
            $rest->setHeader('Content-Type', $input['type']);
            if (isset($input['md5sum'])) $rest->setHeader('Content-MD5', $input['md5sum']);

            $rest->setAmzHeader('x-amz-acl', $acl);
            foreach ($metaHeaders as $h => $v) $rest->setAmzHeader('x-amz-meta-' . $h, $v);
            $rest->getResponse();
        } else
            $rest->response->error = array('code' => 0, 'message' => 'Missing input parameters');

        if ($rest->response->error === false && $rest->response->code !== 200)
            $rest->response->error = array('code' => $rest->response->code, 'message' => 'Unexpected HTTP status');
        if ($rest->response->error !== false) {
            self::__triggerError(sprintf("S3::putObject(): [%s] %s",
                $rest->response->error['code'], $rest->response->error['message']), __FILE__, __LINE__);
            return false;
        }
        return true;
    }

    /**
     * Put an object from a file (legacy function)
     *
     * @param string $file        Input file path
     * @param string $bucket      Bucket name
     * @param string $uri         Object URI
     * @param  $acl
     * @param array $metaHeaders  Array of x-amz-meta-* headers
     * @param string $contentType Content type
     *
     * @return boolean
     */
    public static function putObjectFile($file, $bucket, $uri, $acl = self::ACL_PRIVATE, $metaHeaders = array(), $contentType = null) {
        return self::putObject(self::inputFile($file), $bucket, $uri, $acl, $metaHeaders, $contentType);
    }

    /**
     * Put an object from a string (legacy function)
     *
     * @param string $string      Input data
     * @param string $bucket      Bucket name
     * @param string $uri         Object URI
     * @param  $acl
     * @param array $metaHeaders  Array of x-amz-meta-* headers
     * @param string $contentType Content type
     *
     * @return boolean
     */
    public static function putObjectString($string, $bucket, $uri, $acl = self::ACL_PRIVATE, $metaHeaders = array(), $contentType = 'text/plain') {
        return self::putObject($string, $bucket, $uri, $acl, $metaHeaders, $contentType);
    }

    /**
     * Get an object
     *
     * @param string $bucket Bucket name
     * @param string $uri    Object URI
     * @param mixed $saveTo  Filename or resource to write to
     *
     * @return mixed
     */
    public static function getObject($bucket, $uri, $saveTo = false) {
        $rest = new S3Request('GET', $bucket, $uri, self::$endpoint);
        if ($saveTo !== false) {
            if (is_resource($saveTo))
                $rest->fp =& $saveTo;
            else
                if (($rest->fp = @fopen($saveTo, 'wb')) !== false)
                    $rest->file = realpath($saveTo);
                else
                    $rest->response->error = array('code' => 0, 'message' => 'Unable to open save file for writing: ' . $saveTo);
        }
        if ($rest->response->error === false) $rest->getResponse();

        if ($rest->response->error === false && $rest->response->code !== 200)
            $rest->response->error = array('code' => $rest->response->code, 'message' => 'Unexpected HTTP status');
        if ($rest->response->error !== false) {
            self::__triggerError(sprintf("S3::getObject({$bucket}, {$uri}): [%s] %s",
                $rest->response->error['code'], $rest->response->error['message']), __FILE__, __LINE__);
            return false;
        }
        return $rest->response;
    }

    /**
     * Get object information
     *
     * @param string $bucket      Bucket name
     * @param string $uri         Object URI
     * @param boolean $returnInfo Return response information
     *
     * @return mixed | false
     * @throws \Exception
     */
    public static function getObjectInfo($bucket, $uri, $returnInfo = true) {
        $rest = new S3Request('HEAD', $bucket, $uri, self::$endpoint);
        $rest = $rest->getResponse();
        if ($rest->error === false && ($rest->code !== 200 && $rest->code !== 404))
            $rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
        if ($rest->error !== false) {
            self::__triggerError(sprintf("S3::getObjectInfo({$bucket}, {$uri}): [%s] %s",
                $rest->error['code'], $rest->error['message']), __FILE__, __LINE__);
            return false;
        }
        return $rest->code == 200 ? $returnInfo ? $rest->headers : true : false;
    }

    /**
     * Copy an object
     *
     * @param string $srcBucket      Source bucket name
     * @param string $srcUri         Source object URI
     * @param string $bucket         Destination bucket name
     * @param string $uri            Destination object URI
     * @param  $acl
     * @param array $metaHeaders     Optional array of x-amz-meta-* headers
     * @param array $requestHeaders  Optional array of request headers (content type, disposition, etc.)
     * @param  $storageClass
     *
     * @return mixed | false
     */
    public static function copyObject($srcBucket, $srcUri, $bucket, $uri, $acl = self::ACL_PRIVATE, $metaHeaders = array(), $requestHeaders = array(), $storageClass = self::STORAGE_CLASS_STANDARD) {
        $rest = new S3Request('PUT', $bucket, $uri, self::$endpoint);
        $rest->setHeader('Content-Length', 0);
        foreach ($requestHeaders as $h => $v)
            strpos($h, 'x-amz-') === 0 ? $rest->setAmzHeader($h, $v) : $rest->setHeader($h, $v);
        foreach ($metaHeaders as $h => $v) $rest->setAmzHeader('x-amz-meta-' . $h, $v);
        if ($storageClass !== self::STORAGE_CLASS_STANDARD) // Storage class
            $rest->setAmzHeader('x-amz-storage-class', $storageClass);
        $rest->setAmzHeader('x-amz-acl', $acl);
        $rest->setAmzHeader('x-amz-copy-source', sprintf('/%s/%s', $srcBucket, rawurlencode($srcUri)));
        if (sizeof($requestHeaders) > 0 || sizeof($metaHeaders) > 0)
            $rest->setAmzHeader('x-amz-metadata-directive', 'REPLACE');

        $rest = $rest->getResponse();
        if ($rest->error === false && $rest->code !== 200)
            $rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
        if ($rest->error !== false) {
            self::__triggerError(sprintf("S3::copyObject({$srcBucket}, {$srcUri}, {$bucket}, {$uri}): [%s] %s",
                $rest->error['code'], $rest->error['message']), __FILE__, __LINE__);
            return false;
        }
        return isset($rest->body->LastModified, $rest->body->ETag) ? array(
            'time' => strtotime((string)$rest->body->LastModified),
            'hash' => substr((string)$rest->body->ETag, 1, -1)
        ) : false;
    }

    /**
     * Set up a bucket redirection
     *
     * @param string $bucket   Bucket name
     * @param string $location Target host name
     *
     * @return boolean
     */
    public static function setBucketRedirect($bucket = NULL, $location = NULL) {
        $rest = new S3Request('PUT', $bucket, '', self::$endpoint);

        if (empty($bucket) || empty($location)) {
            self::__triggerError("S3::setBucketRedirect({$bucket}, {$location}): Empty parameter.", __FILE__, __LINE__);
            return false;
        }

        $dom = new \DOMDocument;
        $websiteConfiguration = $dom->createElement('WebsiteConfiguration');
        $redirectAllRequestsTo = $dom->createElement('RedirectAllRequestsTo');
        $hostName = $dom->createElement('HostName', $location);
        $redirectAllRequestsTo->appendChild($hostName);
        $websiteConfiguration->appendChild($redirectAllRequestsTo);
        $dom->appendChild($websiteConfiguration);
        $rest->setParameter('website', null);
        $rest->data = $dom->saveXML();
        $rest->size = strlen($rest->data);
        $rest->setHeader('Content-Type', 'application/xml');
        $rest = $rest->getResponse();

        if ($rest->error === false && $rest->code !== 200)
            $rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
        if ($rest->error !== false) {
            self::__triggerError(sprintf("S3::setBucketRedirect({$bucket}, {$location}): [%s] %s",
                $rest->error['code'], $rest->error['message']), __FILE__, __LINE__);
            return false;
        }
        return true;
    }

    /**
     * Set logging for a bucket
     *
     * @param string $bucket       Bucket name
     * @param string $targetBucket Target bucket (where logs are stored)
     * @param string $targetPrefix Log prefix (e,g; domain.com-)
     *
     * @return boolean
     */
    public static function setBucketLogging($bucket, $targetBucket, $targetPrefix = null) {
        // The S3 log delivery group has to be added to the target bucket's ACP
        if ($targetBucket !== null && ($acp = self::getAccessControlPolicy($targetBucket, '')) !== false) {
            // Only add permissions to the target bucket when they do not exist
            $aclWriteSet = false;
            $aclReadSet = false;
            foreach ($acp['acl'] as $acl)
                if ($acl['type'] == 'Group' && $acl['uri'] == 'http://acs.amazonaws.com/groups/s3/LogDelivery') {
                    if ($acl['permission'] == 'WRITE') $aclWriteSet = true;
                    elseif ($acl['permission'] == 'READ_ACP') $aclReadSet = true;
                }
            if (!$aclWriteSet) $acp['acl'][] = array(
                'type' => 'Group', 'uri' => 'http://acs.amazonaws.com/groups/s3/LogDelivery', 'permission' => 'WRITE'
            );
            if (!$aclReadSet) $acp['acl'][] = array(
                'type' => 'Group', 'uri' => 'http://acs.amazonaws.com/groups/s3/LogDelivery', 'permission' => 'READ_ACP'
            );
            if (!$aclReadSet || !$aclWriteSet) self::setAccessControlPolicy($targetBucket, '', $acp);
        }

        $dom = new \DOMDocument;
        $bucketLoggingStatus = $dom->createElement('BucketLoggingStatus');
        $bucketLoggingStatus->setAttribute('xmlns', 'http://s3.amazonaws.com/doc/2006-03-01/');
        if ($targetBucket !== null) {
            if ($targetPrefix == null) $targetPrefix = $bucket . '-';
            $loggingEnabled = $dom->createElement('LoggingEnabled');
            $loggingEnabled->appendChild($dom->createElement('TargetBucket', $targetBucket));
            $loggingEnabled->appendChild($dom->createElement('TargetPrefix', $targetPrefix));
            // TODO: Add TargetGrants?
            $bucketLoggingStatus->appendChild($loggingEnabled);
        }
        $dom->appendChild($bucketLoggingStatus);

        $rest = new S3Request('PUT', $bucket, '', self::$endpoint);
        $rest->setParameter('logging', null);
        $rest->data = $dom->saveXML();
        $rest->size = strlen($rest->data);
        $rest->setHeader('Content-Type', 'application/xml');
        $rest = $rest->getResponse();
        if ($rest->error === false && $rest->code !== 200)
            $rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
        if ($rest->error !== false) {
            self::__triggerError(sprintf("S3::setBucketLogging({$bucket}, {$targetBucket}): [%s] %s",
                $rest->error['code'], $rest->error['message']), __FILE__, __LINE__);
            return false;
        }
        return true;
    }

    /**
     * Get logging status for a bucket
     *
     * This will return false if logging is not enabled.
     * Note: To enable logging, you also need to grant write access to the log group
     *
     * @param string $bucket Bucket name
     *
     * @return array | false
     */
    public static function getBucketLogging($bucket) {
        $rest = new S3Request('GET', $bucket, '', self::$endpoint);
        $rest->setParameter('logging', null);
        $rest = $rest->getResponse();
        if ($rest->error === false && $rest->code !== 200)
            $rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
        if ($rest->error !== false) {
            self::__triggerError(sprintf("S3::getBucketLogging({$bucket}): [%s] %s",
                $rest->error['code'], $rest->error['message']), __FILE__, __LINE__);
            return false;
        }
        if (!isset($rest->body->LoggingEnabled)) return false; // No logging
        return array(
            'targetBucket' => (string)$rest->body->LoggingEnabled->TargetBucket,
            'targetPrefix' => (string)$rest->body->LoggingEnabled->TargetPrefix,
        );
    }

    /**
     * Disable bucket logging
     *
     * @param string $bucket Bucket name
     *
     * @return boolean
     */
    public static function disableBucketLogging($bucket) {
        return self::setBucketLogging($bucket, null);
    }

    /**
     * Get a bucket's location
     *
     * @param string $bucket Bucket name
     *
     * @return string | false
     */
    public static function getBucketLocation($bucket) {
        $rest = new S3Request('GET', $bucket, '', self::$endpoint);
        $rest->setParameter('location', null);
        $rest = $rest->getResponse();
        if ($rest->error === false && $rest->code !== 200)
            $rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
        if ($rest->error !== false) {
            self::__triggerError(sprintf("S3::getBucketLocation({$bucket}): [%s] %s",
                $rest->error['code'], $rest->error['message']), __FILE__, __LINE__);
            return false;
        }
        return (isset($rest->body[0]) && (string)$rest->body[0] !== '') ? (string)$rest->body[0] : 'US';
    }

    /**
     * Set object or bucket Access Control Policy
     *
     * @param string $bucket Bucket name
     * @param string $uri    Object URI
     * @param array $acp     Access Control Policy Data (same as the data returned from getAccessControlPolicy)
     *
     * @return boolean
     */
    public static function setAccessControlPolicy($bucket, $uri = '', $acp = array()) {
        $dom = new \DOMDocument;
        $dom->formatOutput = true;
        $accessControlPolicy = $dom->createElement('AccessControlPolicy');
        $accessControlList = $dom->createElement('AccessControlList');

        // It seems the owner has to be passed along too
        $owner = $dom->createElement('Owner');
        $owner->appendChild($dom->createElement('ID', $acp['owner']['id']));
        $owner->appendChild($dom->createElement('DisplayName', $acp['owner']['name']));
        $accessControlPolicy->appendChild($owner);

        foreach ($acp['acl'] as $g) {
            $grant = $dom->createElement('Grant');
            $grantee = $dom->createElement('Grantee');
            $grantee->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
            if (isset($g['id'])) { // CanonicalUser (DisplayName is omitted)
                $grantee->setAttribute('xsi:type', 'CanonicalUser');
                $grantee->appendChild($dom->createElement('ID', $g['id']));
            } elseif (isset($g['email'])) { // AmazonCustomerByEmail
                $grantee->setAttribute('xsi:type', 'AmazonCustomerByEmail');
                $grantee->appendChild($dom->createElement('EmailAddress', $g['email']));
            } elseif ($g['type'] == 'Group') { // Group
                $grantee->setAttribute('xsi:type', 'Group');
                $grantee->appendChild($dom->createElement('URI', $g['uri']));
            }
            $grant->appendChild($grantee);
            $grant->appendChild($dom->createElement('Permission', $g['permission']));
            $accessControlList->appendChild($grant);
        }

        $accessControlPolicy->appendChild($accessControlList);
        $dom->appendChild($accessControlPolicy);

        $rest = new S3Request('PUT', $bucket, $uri, self::$endpoint);
        $rest->setParameter('acl', null);
        $rest->data = $dom->saveXML();
        $rest->size = strlen($rest->data);
        $rest->setHeader('Content-Type', 'application/xml');
        $rest = $rest->getResponse();
        if ($rest->error === false && $rest->code !== 200)
            $rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
        if ($rest->error !== false) {
            self::__triggerError(sprintf("S3::setAccessControlPolicy({$bucket}, {$uri}): [%s] %s",
                $rest->error['code'], $rest->error['message']), __FILE__, __LINE__);
            return false;
        }
        return true;
    }

    /**
     * Get object or bucket Access Control Policy
     *
     * @param string $bucket Bucket name
     * @param string $uri    Object URI
     *
     * @return mixed | false
     */
    public static function getAccessControlPolicy($bucket, $uri = '') {
        $rest = new S3Request('GET', $bucket, $uri, self::$endpoint);
        $rest->setParameter('acl', null);
        $rest = $rest->getResponse();
        if ($rest->error === false && $rest->code !== 200)
            $rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
        if ($rest->error !== false) {
            self::__triggerError(sprintf("S3::getAccessControlPolicy({$bucket}, {$uri}): [%s] %s",
                $rest->error['code'], $rest->error['message']), __FILE__, __LINE__);
            return false;
        }

        $acp = array();
        if (isset($rest->body->Owner, $rest->body->Owner->ID, $rest->body->Owner->DisplayName))
            $acp['owner'] = array(
                'id' => (string)$rest->body->Owner->ID, 'name' => (string)$rest->body->Owner->DisplayName
            );

        if (isset($rest->body->AccessControlList)) {
            $acp['acl'] = array();
            foreach ($rest->body->AccessControlList->Grant as $grant) {
                foreach ($grant->Grantee as $grantee) {
                    if (isset($grantee->ID, $grantee->DisplayName)) // CanonicalUser
                        $acp['acl'][] = array(
                            'type' => 'CanonicalUser',
                            'id' => (string)$grantee->ID,
                            'name' => (string)$grantee->DisplayName,
                            'permission' => (string)$grant->Permission
                        );
                    elseif (isset($grantee->EmailAddress)) // AmazonCustomerByEmail
                        $acp['acl'][] = array(
                            'type' => 'AmazonCustomerByEmail',
                            'email' => (string)$grantee->EmailAddress,
                            'permission' => (string)$grant->Permission
                        );
                    elseif (isset($grantee->URI)) // Group
                        $acp['acl'][] = array(
                            'type' => 'Group',
                            'uri' => (string)$grantee->URI,
                            'permission' => (string)$grant->Permission
                        );
                    else continue;
                }
            }
        }
        return $acp;
    }

    /**
     * Delete an object
     *
     * @param string $bucket Bucket name
     * @param string $uri    Object URI
     *
     * @return boolean
     */
    public static function deleteObject($bucket, $uri) {
        $rest = new S3Request('DELETE', $bucket, $uri, self::$endpoint);
        $rest = $rest->getResponse();
        if ($rest->error === false && $rest->code !== 204)
            $rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
        if ($rest->error !== false) {
            self::__triggerError(sprintf("S3::deleteObject(): [%s] %s",
                $rest->error['code'], $rest->error['message']), __FILE__, __LINE__);
            return false;
        }
        return true;
    }

    /**
     * Get a query string authenticated URL
     *
     * @param string $bucket      Bucket name
     * @param string $uri         Object URI
     * @param integer $lifetime   Lifetime in seconds
     * @param boolean $hostBucket Use the bucket name as the hostname
     * @param boolean $https      Use HTTPS ($hostBucket should be false for SSL verification)
     *
     * @return string
     */
    public static function getAuthenticatedURL($bucket, $uri, $lifetime, $hostBucket = false, $https = false) {
        $expires = self::__getTime() + $lifetime;
        $uri = str_replace(array('%2F', '%2B'), array('/', '+'), rawurlencode($uri));
        return sprintf(($https ? 'https' : 'http') . '://%s/%s?AWSAccessKeyId=%s&Expires=%u&Signature=%s',
            $hostBucket ? $bucket : self::$endpoint . '/' . $bucket, $uri, self::$__accessKey, $expires,
            // $hostBucket ? $bucket : $bucket . '.' . self::$endpoint, $uri, self::$__accessKey, $expires,
            urlencode(self::__getHash("GET\n\n\n{$expires}\n/{$bucket}/{$uri}")));
    }

    /**
     * Get a CloudFront signed policy URL
     *
     * @param array $policy Policy
     *
     * @return string
     */
    public static function getSignedPolicyURL($policy) {
        $data = json_encode($policy);
        $signature = '';
        if (!openssl_sign($data, $signature, self::$__signingKeyResource)) return false;

        $encoded = str_replace(array('+', '='), array('-', '_', '~'), base64_encode($data));
        $signature = str_replace(array('+', '='), array('-', '_', '~'), base64_encode($signature));

        $url = $policy['Statement'][0]['Resource'] . '?';
        foreach (array('Policy' => $encoded, 'Signature' => $signature, 'Key-Pair-Id' => self::$__signingKeyPairId) as $k => $v)
            $url .= $k . '=' . str_replace('%2F', '/', rawurlencode($v)) . '&';
        return substr($url, 0, -1);
    }

    /**
     * Get a CloudFront canned policy URL
     *
     * @param string $url       URL to sign
     * @param integer $lifetime URL lifetime
     *
     * @return string
     */
    public static function getSignedCannedURL($url, $lifetime) {
        return self::getSignedPolicyURL(array(
            'Statement' => array(
                array('Resource' => $url, 'Condition' => array(
                    'DateLessThan' => array('AWS:EpochTime' => self::__getTime() + $lifetime)
                ))
            )
        ));
    }

    /**
     * Get upload POST parameters for form uploads
     *
     * @param string $bucket          Bucket name
     * @param string $uriPrefix       Object URI prefix
     * @param  $acl
     * @param integer $lifetime       Lifetime in seconds
     * @param integer $maxFileSize    Maximum filesize in bytes (default 5MB)
     * @param string $successRedirect Redirect URL or 200 / 201 status code
     * @param array $amzHeaders       Array of x-amz-meta-* headers
     * @param array $headers          Array of request headers or content type as a string
     * @param boolean $flashVars      Includes additional "Filename" variable posted by Flash
     *
     * @return object
     */
    public static function getHttpUploadPostParams($bucket, $uriPrefix = '', $acl = self::ACL_PRIVATE, $lifetime = 3600,
                                                   $maxFileSize = 5242880, $successRedirect = "201", $amzHeaders = array(), $headers = array(), $flashVars = false) {
        // Create policy object
        $policy = new \stdClass;
        $policy->expiration = gmdate('Y-m-d\TH:i:s\Z', (self::__getTime() + $lifetime));
        $policy->conditions = array();
        $obj = new \stdClass;
        $obj->bucket = $bucket;
        array_push($policy->conditions, $obj);
        $obj = new \stdClass;
        $obj->acl = $acl;
        array_push($policy->conditions, $obj);

        $obj = new \stdClass; // 200 for non-redirect uploads
        if (is_numeric($successRedirect) && in_array((int)$successRedirect, array(200, 201)))
            $obj->success_action_status = (string)$successRedirect;
        else // URL
            $obj->success_action_redirect = $successRedirect;
        array_push($policy->conditions, $obj);

        if ($acl !== self::ACL_PUBLIC_READ)
            array_push($policy->conditions, array('eq', '$acl', $acl));

        array_push($policy->conditions, array('starts-with', '$key', $uriPrefix));
        if ($flashVars) array_push($policy->conditions, array('starts-with', '$Filename', ''));
        foreach (array_keys($headers) as $headerKey)
            array_push($policy->conditions, array('starts-with', '$' . $headerKey, ''));
        foreach ($amzHeaders as $headerKey => $headerVal) {
            $obj = new \stdClass;
            $obj->{$headerKey} = (string)$headerVal;
            array_push($policy->conditions, $obj);
        }
        array_push($policy->conditions, array('content-length-range', 0, $maxFileSize));
        $policy = base64_encode(str_replace('\/', '/', json_encode($policy)));

        // Create parameters
        $params = new \stdClass;
        $params->AWSAccessKeyId = self::$__accessKey;
        $params->key = $uriPrefix . '${filename}';
        $params->acl = $acl;
        $params->policy = $policy;
        unset($policy);
        $params->signature = self::__getHash($params->policy);
        if (is_numeric($successRedirect) && in_array((int)$successRedirect, array(200, 201)))
            $params->success_action_status = (string)$successRedirect;
        else
            $params->success_action_redirect = $successRedirect;
        foreach ($headers as $headerKey => $headerVal) $params->{$headerKey} = (string)$headerVal;
        foreach ($amzHeaders as $headerKey => $headerVal) $params->{$headerKey} = (string)$headerVal;
        return $params;
    }

    /**
     * Create a CloudFront distribution
     *
     * @param string $bucket               Bucket name
     * @param boolean $enabled             Enabled (true/false)
     * @param array $cnames                Array containing CNAME aliases
     * @param string $comment              Use the bucket name as the hostname
     * @param string $defaultRootObject    Default root object
     * @param string $originAccessIdentity Origin access identity
     * @param array $trustedSigners        Array of trusted signers
     *
     * @return array | false
     */
    public static function createDistribution($bucket, $enabled = true, $cnames = array(), $comment = null, $defaultRootObject = null, $originAccessIdentity = null, $trustedSigners = array()) {
        if (!extension_loaded('openssl')) {
            self::__triggerError(sprintf("S3::createDistribution({$bucket}, " . (int)$enabled . ", [], '$comment'): %s",
                "CloudFront functionality requires SSL"), __FILE__, __LINE__);
            return false;
        }

        $rest = new S3Request('POST', '', '2010-11-01/distribution', 'cloudfront.amazonaws.com');
        $rest->data = self::__getCloudFrontDistributionConfigXML(
            $bucket . '.s3.amazonaws.com',
            $enabled,
            (string)$comment,
            (string)microtime(true),
            $cnames,
            $defaultRootObject,
            $originAccessIdentity,
            $trustedSigners
        );

        $rest->size = strlen($rest->data);
        $rest->setHeader('Content-Type', 'application/xml');
        $rest = self::__getCloudFrontResponse($rest);

        if ($rest->error === false && $rest->code !== 201)
        {
            $rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
        }
        if ($rest->error !== false) {
            self::__triggerError(sprintf("S3::createDistribution({$bucket}, " . (int)$enabled . ", [], '$comment'): [%s] %s",
                $rest->error['code'], $rest->error['message']), __FILE__, __LINE__);
            return false;
        } elseif ($rest->body instanceof \SimpleXMLElement)
        {
            return self::__parseCloudFrontDistributionConfig($rest->body);
        }
        return false;
    }

    /**
     * Get CloudFront distribution info
     *
     * @param string $distributionId Distribution ID from listDistributions()
     *
     * @return array | false
     */
    public static function getDistribution($distributionId) {
        if (!extension_loaded('openssl')) {
            self::__triggerError(sprintf("S3::getDistribution($distributionId): %s",
                "CloudFront functionality requires SSL"), __FILE__, __LINE__);
            return false;
        }

        $rest = new S3Request('GET', '', '2010-11-01/distribution/' . $distributionId, 'cloudfront.amazonaws.com');
        $rest = self::__getCloudFrontResponse($rest);

        if ($rest->error === false && $rest->code !== 200)
        {
            $rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
        }
        if ($rest->error !== false) {
            self::__triggerError(sprintf("S3::getDistribution($distributionId): [%s] %s",
                $rest->error['code'], $rest->error['message']), __FILE__, __LINE__);
            return false;
        } elseif ($rest->body instanceof \SimpleXMLElement) {
            $dist = self::__parseCloudFrontDistributionConfig($rest->body);
            $dist['hash'] = $rest->headers['hash'];
            $dist['id'] = $distributionId;
            return $dist;
        }
        return false;
    }

    /**
     * Update a CloudFront distribution
     *
     * @param array $dist Distribution array info identical to output of getDistribution()
     *
     * @return array | false
     */
    public static function updateDistribution($dist) {
        if (!extension_loaded('openssl')) {
            self::__triggerError(sprintf("S3::updateDistribution({$dist['id']}): %s",
                "CloudFront functionality requires SSL"), __FILE__, __LINE__);
            return false;
        }

        $rest = new S3Request('PUT', '', '2010-11-01/distribution/' . $dist['id'] . '/config', 'cloudfront.amazonaws.com');
        $rest->data = self::__getCloudFrontDistributionConfigXML(
            $dist['origin'],
            $dist['enabled'],
            $dist['comment'],
            $dist['callerReference'],
            $dist['cnames'],
            $dist['defaultRootObject'],
            $dist['originAccessIdentity'],
            $dist['trustedSigners']
        );

        $rest->size = strlen($rest->data);
        $rest->setHeader('If-Match', $dist['hash']);
        $rest = self::__getCloudFrontResponse($rest);

        if ($rest->error === false && $rest->code !== 200)
            $rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
        if ($rest->error !== false) {
            self::__triggerError(sprintf("S3::updateDistribution({$dist['id']}): [%s] %s",
                $rest->error['code'], $rest->error['message']), __FILE__, __LINE__);
            return false;
        } else {
            $dist = self::__parseCloudFrontDistributionConfig($rest->body);
            $dist['hash'] = $rest->headers['hash'];
            return $dist;
        }
    }

    /**
     * Delete a CloudFront distribution
     *
     * @param array $dist Distribution array info identical to output of getDistribution()
     *
     * @return boolean
     */
    public static function deleteDistribution($dist) {
        if (!extension_loaded('openssl')) {
            self::__triggerError(sprintf("S3::deleteDistribution({$dist['id']}): %s",
                "CloudFront functionality requires SSL"), __FILE__, __LINE__);
            return false;
        }

        $rest = new S3Request('DELETE', '', '2008-06-30/distribution/' . $dist['id'], 'cloudfront.amazonaws.com');
        $rest->setHeader('If-Match', $dist['hash']);
        $rest = self::__getCloudFrontResponse($rest);

        if ($rest->error === false && $rest->code !== 204)
            $rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
        if ($rest->error !== false) {
            self::__triggerError(sprintf("S3::deleteDistribution({$dist['id']}): [%s] %s",
                $rest->error['code'], $rest->error['message']), __FILE__, __LINE__);
            return false;
        }
        return true;
    }

    /**
     * Get a list of CloudFront distributions
     *
     * @return array|bool
     */
    public static function listDistributions() {
        if (!extension_loaded('openssl')) {
            self::__triggerError(sprintf("S3::listDistributions(): [%s] %s",
                "CloudFront functionality requires SSL"), __FILE__, __LINE__);
            return false;
        }

        $rest = new S3Request('GET', '', '2010-11-01/distribution', 'cloudfront.amazonaws.com');
        $rest = self::__getCloudFrontResponse($rest);

        if ($rest->error === false && $rest->code !== 200)
            $rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
        if ($rest->error !== false) {
            self::__triggerError(sprintf("S3::listDistributions(): [%s] %s",
                $rest->error['code'], $rest->error['message']), __FILE__, __LINE__);
            return false;
        } elseif ($rest->body instanceof \SimpleXMLElement && isset($rest->body->DistributionSummary)) {
            $list = array();
            if (isset($rest->body->Marker, $rest->body->MaxItems, $rest->body->IsTruncated)) {
                //$info['marker'] = (string)$rest->body->Marker;
                //$info['maxItems'] = (int)$rest->body->MaxItems;
                //$info['isTruncated'] = (string)$rest->body->IsTruncated == 'true' ? true : false;
            }
            foreach ($rest->body->DistributionSummary as $summary)
                $list[(string)$summary->Id] = self::__parseCloudFrontDistributionConfig($summary);

            return $list;
        }
        return array();
    }

    /**
     * List CloudFront Origin Access Identities
     *
     * @return array|bool
     */
    public static function listOriginAccessIdentities() {
        if (!extension_loaded('openssl')) {
            self::__triggerError(sprintf("S3::listOriginAccessIdentities(): [%s] %s",
                "CloudFront functionality requires SSL"), __FILE__, __LINE__);
            return false;
        }

        $rest = new S3Request('GET', '', '2010-11-01/origin-access-identity/cloudfront', 'cloudfront.amazonaws.com');
        $rest = self::__getCloudFrontResponse($rest);

        if ($rest->error === false && $rest->code !== 200)
            $rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
        if ($rest->error !== false) {
            trigger_error(sprintf("S3::listOriginAccessIdentities(): [%s] %s",
                $rest->error['code'], $rest->error['message']), E_USER_WARNING);
            return false;
        }

        if (isset($rest->body->CloudFrontOriginAccessIdentitySummary)) {
            $identities = array();
            foreach ($rest->body->CloudFrontOriginAccessIdentitySummary as $identity)
                if (isset($identity->S3CanonicalUserId))
                    $identities[(string)$identity->Id] = array('id' => (string)$identity->Id, 's3CanonicalUserId' => (string)$identity->S3CanonicalUserId);
            return $identities;
        }
        return false;
    }

    /**
     * Invalidate objects in a CloudFront distribution
     *
     * Thanks to Martin Lindkvist for S3::invalidateDistribution()
     *
     * @param string $distributionId Distribution ID from listDistributions()
     * @param array $paths           Array of object paths to invalidate
     *
     * @return boolean
     */
    public static function invalidateDistribution($distributionId, $paths) {
        if (!extension_loaded('openssl')) {
            self::__triggerError(sprintf("S3::invalidateDistribution(): [%s] %s",
                "CloudFront functionality requires SSL"), __FILE__, __LINE__);
            return false;
        }

        $rest = new S3Request('POST', '', '2010-08-01/distribution/' . $distributionId . '/invalidation', 'cloudfront.amazonaws.com');
        $rest->data = self::__getCloudFrontInvalidationBatchXML($paths, (string)microtime(true));
        $rest->size = strlen($rest->data);
        $rest = self::__getCloudFrontResponse($rest);

        if ($rest->error === false && $rest->code !== 201)
            $rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
        if ($rest->error !== false) {
            trigger_error(sprintf("S3::invalidate('{$distributionId}',{$paths}): [%s] %s",
                $rest->error['code'], $rest->error['message']), E_USER_WARNING);
            return false;
        }
        return true;
    }

    /**
     * Get a InvalidationBatch DOMDocument
     *
     * @internal Used to create XML in invalidateDistribution()
     *
     * @param array $paths Paths to objects to invalidateDistribution
     * @param int $callerReference
     *
     * @return string
     */
    private static function __getCloudFrontInvalidationBatchXML($paths, $callerReference = '0') {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;
        $invalidationBatch = $dom->createElement('InvalidationBatch');
        foreach ($paths as $path)
            $invalidationBatch->appendChild($dom->createElement('Path', $path));

        $invalidationBatch->appendChild($dom->createElement('CallerReference', $callerReference));
        $dom->appendChild($invalidationBatch);
        return $dom->saveXML();
    }

    /**
     * List your invalidation batches for invalidateDistribution() in a CloudFront distribution
     *
     * http://docs.amazonwebservices.com/AmazonCloudFront/latest/APIReference/ListInvalidation.html
     * returned array looks like this:
     *    Array
     *    (
     *        [I31TWB0CN9V6XD] => InProgress
     *        [IT3TFE31M0IHZ] => Completed
     *        [I12HK7MPO1UQDA] => Completed
     *        [I1IA7R6JKTC3L2] => Completed
     *    )
     *
     * @param string $distributionId Distribution ID from listDistributions()
     *
     * @return array|bool
     */
    public static function getDistributionInvalidationList($distributionId) {
        if (!extension_loaded('openssl')) {
            self::__triggerError(sprintf("S3::getDistributionInvalidationList(): [%s] %s",
                "CloudFront functionality requires SSL"), __FILE__, __LINE__);
            return false;
        }

        $rest = new S3Request('GET', '', '2010-11-01/distribution/' . $distributionId . '/invalidation', 'cloudfront.amazonaws.com');
        $rest = self::__getCloudFrontResponse($rest);

        if ($rest->error === false && $rest->code !== 200)
            $rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
        if ($rest->error !== false) {
            trigger_error(sprintf("S3::getDistributionInvalidationList('{$distributionId}'): [%s]",
                $rest->error['code'], $rest->error['message']), E_USER_WARNING);
            return false;
        } elseif ($rest->body instanceof \SimpleXMLElement && isset($rest->body->InvalidationSummary)) {
            $list = array();
            foreach ($rest->body->InvalidationSummary as $summary)
                $list[(string)$summary->Id] = (string)$summary->Status;

            return $list;
        }
        return array();
    }

    /**
     * Get a DistributionConfig DOMDocument
     *
     * http://docs.amazonwebservices.com/AmazonCloudFront/latest/APIReference/index.html?PutConfig.html
     *
     * @internal Used to create XML in createDistribution() and updateDistribution()
     *
     * @param string $bucket               S3 Origin bucket
     * @param boolean $enabled             Enabled (true/false)
     * @param string $comment              Comment to append
     * @param string $callerReference      Caller reference
     * @param array $cnames                Array of CNAME aliases
     * @param string $defaultRootObject    Default root object
     * @param string $originAccessIdentity Origin access identity
     * @param array $trustedSigners        Array of trusted signers
     *
     * @return string
     */
    private static function __getCloudFrontDistributionConfigXML($bucket, $enabled, $comment, $callerReference = '0', $cnames = array(), $defaultRootObject = null, $originAccessIdentity = null, $trustedSigners = array()) {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;
        $distributionConfig = $dom->createElement('DistributionConfig');
        $distributionConfig->setAttribute('xmlns', 'http://cloudfront.amazonaws.com/doc/2010-11-01/');

        $origin = $dom->createElement('S3Origin');
        $origin->appendChild($dom->createElement('DNSName', $bucket));
        if ($originAccessIdentity !== null) $origin->appendChild($dom->createElement('OriginAccessIdentity', $originAccessIdentity));
        $distributionConfig->appendChild($origin);

        if ($defaultRootObject !== null) $distributionConfig->appendChild($dom->createElement('DefaultRootObject', $defaultRootObject));

        $distributionConfig->appendChild($dom->createElement('CallerReference', $callerReference));
        foreach ($cnames as $cname)
            $distributionConfig->appendChild($dom->createElement('CNAME', $cname));
        if ($comment !== '') $distributionConfig->appendChild($dom->createElement('Comment', $comment));
        $distributionConfig->appendChild($dom->createElement('Enabled', $enabled ? 'true' : 'false'));

        $trusted = $dom->createElement('TrustedSigners');
        foreach ($trustedSigners as $id => $type)
            $trusted->appendChild($id !== '' ? $dom->createElement($type, $id) : $dom->createElement($type));
        $distributionConfig->appendChild($trusted);

        $dom->appendChild($distributionConfig);
        //var_dump($dom->saveXML());
        return $dom->saveXML();
    }

    /**
     * Parse a CloudFront distribution config
     *
     * See http://docs.amazonwebservices.com/AmazonCloudFront/latest/APIReference/index.html?GetDistribution.html
     *
     * @internal Used to parse the CloudFront DistributionConfig node to an array
     *
     * @param object &$node DOMNode
     *
     * @return array
     */
    private static function __parseCloudFrontDistributionConfig(&$node) {
        if (isset($node->DistributionConfig))
            return self::__parseCloudFrontDistributionConfig($node->DistributionConfig);

        $dist = array();
        if (isset($node->Id, $node->Status, $node->LastModifiedTime, $node->DomainName)) {
            $dist['id'] = (string)$node->Id;
            $dist['status'] = (string)$node->Status;
            $dist['time'] = strtotime((string)$node->LastModifiedTime);
            $dist['domain'] = (string)$node->DomainName;
        }

        if (isset($node->CallerReference))
            $dist['callerReference'] = (string)$node->CallerReference;

        if (isset($node->Enabled))
            $dist['enabled'] = (string)$node->Enabled == 'true' ? true : false;

        if (isset($node->S3Origin)) {
            if (isset($node->S3Origin->DNSName))
                $dist['origin'] = (string)$node->S3Origin->DNSName;

            $dist['originAccessIdentity'] = isset($node->S3Origin->OriginAccessIdentity) ?
                (string)$node->S3Origin->OriginAccessIdentity : null;
        }

        $dist['defaultRootObject'] = isset($node->DefaultRootObject) ? (string)$node->DefaultRootObject : null;

        $dist['cnames'] = array();
        if (isset($node->CNAME))
            foreach ($node->CNAME as $cname)
                $dist['cnames'][(string)$cname] = (string)$cname;

        $dist['trustedSigners'] = array();
        if (isset($node->TrustedSigners))
            foreach ($node->TrustedSigners as $signer) {
                if (isset($signer->Self))
                    $dist['trustedSigners'][''] = 'Self';
                elseif (isset($signer->KeyPairId))
                    $dist['trustedSigners'][(string)$signer->KeyPairId] = 'KeyPairId';
                elseif (isset($signer->AwsAccountNumber))
                    $dist['trustedSigners'][(string)$signer->AwsAccountNumber] = 'AwsAccountNumber';
            }

        $dist['comment'] = isset($node->Comment) ? (string)$node->Comment : null;
        return $dist;
    }

    /**
     * Grab CloudFront response
     *
     * @internal Used to parse the CloudFront S3Request::getResponse() output
     *
     * @param object &$rest S3Request instance
     *
     * @return object
     */
    private static function __getCloudFrontResponse(&$rest) {
        $rest->getResponse();
        if ($rest->response->error === false && isset($rest->response->body) &&
            is_string($rest->response->body) && substr($rest->response->body, 0, 5) == '<?xml') {
            $rest->response->body = simplexml_load_string($rest->response->body);
            // Grab CloudFront errors
            if (isset($rest->response->body->Error, $rest->response->body->Error->Code,
                $rest->response->body->Error->Message)) {
                $rest->response->error = array(
                    'code' => (string)$rest->response->body->Error->Code,
                    'message' => (string)$rest->response->body->Error->Message
                );
                unset($rest->response->body);
            }
        }
        return $rest->response;
    }

    /**
     * Get MIME type for file
     *
     * To override the putObject() Content-Type, add it to $requestHeaders
     *
     * To use fileinfo, ensure the MAGIC environment variable is set
     *
     * @internal Used to get mime types
     *
     * @param string &$file File path
     *
     * @return string
     */
    private static function __getMIMEType(&$file) {
        static $exts = array(
            'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif',
            'png' => 'image/png', 'ico' => 'image/x-icon', 'pdf' => 'application/pdf',
            'tif' => 'image/tiff', 'tiff' => 'image/tiff', 'svg' => 'image/svg+xml',
            'svgz' => 'image/svg+xml', 'swf' => 'application/x-shockwave-flash',
            'zip' => 'application/zip', 'gz' => 'application/x-gzip',
            'tar' => 'application/x-tar', 'bz' => 'application/x-bzip',
            'bz2' => 'application/x-bzip2', 'rar' => 'application/x-rar-compressed',
            'exe' => 'application/x-msdownload', 'msi' => 'application/x-msdownload',
            'cab' => 'application/vnd.ms-cab-compressed', 'txt' => 'text/plain',
            'asc' => 'text/plain', 'htm' => 'text/html', 'html' => 'text/html',
            'css' => 'text/css', 'js' => 'text/javascript',
            'xml' => 'text/xml', 'xsl' => 'application/xsl+xml',
            'ogg' => 'application/ogg', 'mp3' => 'audio/mpeg', 'wav' => 'audio/x-wav',
            'avi' => 'video/x-msvideo', 'mpg' => 'video/mpeg', 'mpeg' => 'video/mpeg',
            'mov' => 'video/quicktime', 'flv' => 'video/x-flv', 'php' => 'text/x-php'
        );

        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (isset($exts[$ext])) return $exts[$ext];

        // Use fileinfo if available
        if (extension_loaded('fileinfo') && isset($_ENV['MAGIC']) &&
            ($finfo = finfo_open(FILEINFO_MIME, $_ENV['MAGIC'])) !== false) {
            if (($type = finfo_file($finfo, $file)) !== false) {
                // Remove the charset and grab the last content-type
                $type = explode(' ', str_replace('; charset=', ';charset=', $type));
                $type = array_pop($type);
                $type = explode(';', $type);
                $type = trim(array_shift($type));
            }
            finfo_close($finfo);
            if ($type !== false && strlen($type) > 0) return $type;
        }

        return 'application/octet-stream';
    }

    /**
     * Get the current time
     *
     * @internal Used to apply offsets to sytem time
     * @return integer
     */
    public static function __getTime() {
        return time() + self::$__timeOffset;
    }

    /**
     * Generate the auth string: "AWS AccessKey:Signature"
     *
     * @internal Used by S3Request::getResponse()
     *
     * @param string $string String to sign
     *
     * @return string
     */
    public static function __getSignature($string) {
        return 'AWS ' . self::$__accessKey . ':' . self::__getHash($string);
    }

    /**
     * Creates a HMAC-SHA1 hash
     *
     * This uses the hash extension if loaded
     *
     * @internal Used by __getSignature()
     *
     * @param string $string String to sign
     *
     * @return string
     */
    private static function __getHash($string) {
        return base64_encode(extension_loaded('hash') ?
            hash_hmac('sha1', $string, self::$__secretKey, true) : pack('H*', sha1(
                (str_pad(self::$__secretKey, 64, chr(0x00)) ^ (str_repeat(chr(0x5c), 64))) .
                pack('H*', sha1((str_pad(self::$__secretKey, 64, chr(0x00)) ^
                        (str_repeat(chr(0x36), 64))) . $string)))));
    }

}
