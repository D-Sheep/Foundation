<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Foundation\Oauth\SignatureMethod;

use Foundation\Oauth\IOauthStore;
/**
 * Description of IStoreRequired
 *
 * @author David Menger
 */
interface IStoreRequired {
    
    public function setStore(IOauthStore $store);
    
}
