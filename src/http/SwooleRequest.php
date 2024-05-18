<?php

namespace GemLibrary\Http;

use GemLibrary\Helper\StringHelper;
use GemLibrary\Http\Request;

class SwooleRequest
{
    public   Request $request; 
    private  object  $incomingRequestObject;
       
    /**
     * @param object $swooleRquest
     */
    public function __construct(object $swooleRquest)
    {
        $this->request = new Request();
        $this->incomingRequestObject = $swooleRquest;
        if(isset($swooleRquest->server['request_uri'])) {
            $this->request->requestMethod = $swooleRquest->server['request_method'];
            $this->request->requestedUrl = $swooleRquest->server['request_uri'];
            isset($swooleRquest->server['query_string']) ? $this->request->queryString = $swooleRquest->server['query_string'] : $this->request->queryString = null;
            $this->request->remoteAddress = $swooleRquest->server['remote_addr'] .':'. $swooleRquest->server['remote_port'];
            if(isset($swooleRquest->header['user-agent'])) {
                $this->request->userMachine = $swooleRquest->header['user-agent'];
            }
            $this->setData();
        }
        else
        {
            $this->request->error = "incomming request is not openSwoole request";
        }
    }

    public function getOriginalSwooleRequest():object
    {
        return $this->incomingRequestObject;
    }

    private function setData():void
    {
        $this->setAuthorizationToken();
        $this->setPost();
        $this->setFiles();
        $this->setGet();
    }


    private function setPost():void
    {
        if(isset($this->incomingRequestObject->post)) {
            $this->request->post = $this->incomingRequestObject->post;
        }
    }


    private function setAuthorizationToken():void
    {
        if(isset($this->incomingRequestObject->header['authorization'])) {
            $this->request->authorizationHeader = $this->incomingRequestObject->header['authorization'];
        }
    }

    private function setFiles():void
    {
        if(isset($this->incomingRequestObject->files)) {
            $this->request->files = $this->incomingRequestObject->files;
        }
    }

    private function setGet():void
    {
        if(isset($this->incomingRequestObject->get)) {
            $this->request->get = $this->incomingRequestObject->get;
        }
    }
}
