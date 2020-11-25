<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
    

    public function array_xml ($a){
        $data = simplexml_load_string($a, "SimpleXMLElement", LIBXML_NOCDATA);
        $datat = json_decode(json_encode($data),true);
        return $datat;

    }

}
