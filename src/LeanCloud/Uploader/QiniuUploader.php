<?php

namespace LeanCloud\Uploader;

use LeanCloud\LeanClient;

/**
 * Qiniu file uploader
 *
 * @link http://developer.qiniu.com/code/v6/api/kodo-api/up/upload.html
 */
class QiniuUploader extends AbstractUploader {

    public function getUploadUrl() {
        return "https://up.qbox.me/";
    }

    /**
     * Upload file to qiniu
     *
     * @param string $content  File content
     * @param string $mimeType MIME type of file
     * @param string $key      Generated file name
     */
    public function upload($content, $mimeType, $key) {
        $boundary = md5(microtime(true));

        $body = LeanClient::multipartEncode(array(
            "name"  => $key,
            "mimeType"  => $mimeType,
            "content"   => $content,
        ), array(
            "token" => $this->authToken,
            "key"   => $key,
        ), $boundary);

        $headers[] = "User-Agent: " . LeanClient::getVersionString();
        $headers[] = "Content-Type: multipart/form-data;" .
                     " boundary={$boundary}";
        $headers[] = "Content-Length: " . strlen($body);

        $url = static::getUploadUrl();
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        $resp     = curl_exec($ch);
        $respCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $respType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $error    = curl_errno($ch);
        $errno    = curl_errno($ch);
        curl_close($ch);

        /** type of error:
         *  - curl error
         *  - http status error 4xx, 5xx
         *  - rest api error
         */
        if ($errno > 0) {
            throw new \RuntimeException("CURL ($url) error: " .
                                        "{$errno} {$error}",
                                        $errno);
        }

        $data = json_decode($resp, true);
        if (isset($data["error"])) {
            $code = isset($data["code"]) ? $data["code"] : -1;
            throw new \RuntimeException("{$code} {$data['error']}", $code);
        }
        return $data;
    }

}
