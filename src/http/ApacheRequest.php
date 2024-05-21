<?php

namespace GemLibrary\Http;

use GemLibrary\Http\Request;
use stdClass;

class ApacheRequest
{
    public  Request $request; 

    public function __construct()
    {
        $this->sanitizeAllServerHttpRequestHeaders();
        $this->sanitizeAllHTTPGetRequest();

        $this->sanitizeQueryString();
        $this->request = new Request();
        $this->request->requestedUrl = $this->sanitizeRequestURI();
        $this->request->requestMethod = $this->getRequestMethod();
        $this->request->userMachine = $this->getUserAgent();
        $this->request->remoteAddress = $this->getRemoteAddress();
        $this->request->queryString = $_SERVER['QUERY_STRING'];
        $this->request->post = new Post($_POST);
        $this->request->get = $_GET;
        $this->request->put = $this->sanitizeAllHTTPPutRequest();
        $this->request->patch = $this->sanitizeAllHTTPPatchRequest();
        $_GET = null;
        $_POST = null;

        if (isset($_FILES['file'])) {
            $this->request->files = $_FILES['file'];
        }
        $this->getAuthHeader();
    }

    private function sanitizeAllServerHttpRequestHeaders():void
    {
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                if(is_string($_SERVER[$key])) {
                    $_SERVER[$key] = $this->sanitizeInput($value);
                }
                if(is_array($_SERVER[$key])) {
                    foreach($_SERVER[$key] as $subKey=>$subValue)
                    {
                        $_SERVER[$key][$subKey] = $this->sanitizeInput($subValue);
                    }
                }
            }
        }
    }

    /**
     * @return array<mixed>|null
     */
    private function sanitizeAllHTTPPatchRequest(): null|array
    {
        // Read the raw input stream from the request
        $input = file_get_contents('php://input');
        if(!$input) {
            $input = '';
        }
        // Parse the raw input data
        parse_str($input, $_PATCH);
        
        // Check if $_PATCH is an array and not empty
        if (!is_array($_PATCH) || empty($_PATCH)) {
            return null;
        }

        // Iterate over each key-value pair in $_PATCH
        foreach ($_PATCH as $key => $value) {
            // Sanitize the value using your sanitizeInput() function
            if (is_string($value)) {
                $_PATCH[$key] = $this->sanitizeInput($value);
            }
            // If the value is an array, you may choose to sanitize its elements as well
            elseif (is_array($value)) {
                foreach ($value as $subKey => $subValue) {
                        $_PATCH[$key][$subKey] = $this->sanitizeInput($subValue); /*@phpstan-ignore-line*/
                }
            }
        }
        return $_PATCH;
    }


    private function sanitizeAllHTTPGetRequest():void
    {
        foreach ($_GET as $key => $value) {
            if (is_string($value)) {
                $_GET[$key] = $this->sanitizeInput($value);
            }
            if (is_array($value)) {
                foreach ($value as $subKey => $item) {
                    if (is_string($item)) {
                        $_GET[$key][$subKey] = $this->sanitizeInput($item);
                    }
                }
            }
        }
    }

    /**
     * @return array<mixed>|null
     */
    private function sanitizeAllHTTPPutRequest(): null|array
    {
        // Read the raw input stream from the request
        $input = file_get_contents('php://input');
        if(!$input) {
            $input = '';
        }
        // Parse the raw input data
        parse_str($input, $_PUT);
        
        // Check if $_PUT is an array and not empty
        if (!is_array($_PUT) || empty($_PUT)) {
            return null;
        }

        // Iterate over each key-value pair in $_PUT
        foreach ($_PUT as $key => $value) {
            // Sanitize the value using your sanitizeInput() function
            if (is_string($value)) {
                $_PUT[$key] = $this->sanitizeInput($value);
            }
            // If the value is an array, you may choose to sanitize its elements as well
            elseif (is_array($value)) {
                foreach ($value as $subKey => $subValue) {
                    if (is_string($subValue)) {
                        $_PUT[$key][$subKey] = $this->sanitizeInput($subValue);/*@phpstan-ignore-line*/
                    }
                }
            }
        }
        return $_PUT;
    }


    private function sanitizeQueryString():void
    {
        if(isset($_SERVER['QUERY_STRING'])) {
            $_SERVER['QUERY_STRING'] = trim($_SERVER['QUERY_STRING']);
            $_SERVER['QUERY_STRING'] = filter_var($_SERVER['QUERY_STRING'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        }
    }

    private function sanitizeRequestURI():string
    {
        if(isset($_SERVER['REQUEST_URI']) && is_string($_SERVER['REQUEST_URI'])) {
            $sanitizedURI = trim($_SERVER['REQUEST_URI']);
            if(!filter_var($_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL)) {
                return '';
            }
            return $sanitizedURI;
        }
        return '';
    }

    /**
     * @param  mixed $input
     * @return mixed
     */
    private function sanitizeInput(mixed $input):mixed
    {
        if(!is_string($input)) {
            return $input;
        }
        $input = trim($input);
        return filter_var($input, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    }

    private function getUserAgent():string
    {
        if(isset($_SERVER['HTTP_USER_AGENT'])) {
            return $_SERVER['HTTP_USER_AGENT'];
        }
        return '';
    }

    private function getRemoteAddress():string
    {
        if(isset($_SERVER['REMOTE_ADDR'])) {
            if (filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP)) {
                return $_SERVER['REMOTE_ADDR'];
            } else {
                return 'invalid_remote_address_ip_format';
            }
        }
        return 'remote_address is not set';
    }

    private function getRequestMethod():string
    {
        if(isset($_SERVER['REQUEST_METHOD'])) {
            $_SERVER['REQUEST_METHOD'] = trim($_SERVER['REQUEST_METHOD']);
            $_SERVER['REQUEST_METHOD'] = strtoupper($_SERVER['REQUEST_METHOD']);
            $allowedMethods = array('GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS', 'HEAD');
            if (in_array($_SERVER['REQUEST_METHOD'], $allowedMethods)) {
                return $_SERVER['REQUEST_METHOD'];
            } else {
                return ''; // Invalid request method
            }
        }
        return '';
    }

    private function getAuthHeader():void
    {
        $this->request->authorizationHeader = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : null;
        // If the "Authorization" header is empty, you may want to check for the "REDIRECT_HTTP_AUTHORIZATION" header as well.
        if (!$this->request->authorizationHeader && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
                $res = $this->sanitizeInput($_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
            if(is_string($res)) {
                $this->request->authorizationHeader = $res;
            }
        }
    }
}
