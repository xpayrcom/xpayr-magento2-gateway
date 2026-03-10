<?php
namespace Xpayr\Gateway\Api;

interface IpnManagementInterface
{
    /**
     * Handle incoming XPayr webhook
     *
     * @param mixed $param
     * @return string
     */
    public function post($param = null);
}
