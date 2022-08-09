<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Exception;
use GuzzleHttp\Client;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\CssSelector\Exception\InternalErrorException;

class InfoController extends Controller
{

    private function parseResponse($elements)
    {
        $response= [];

        foreach ($elements as $element)
        {
            try
            {
                $response[]= $element->pt->term;
            }
            catch (Exception $e)
            {
            }
        }

        return collect($response);
    }


    private function queryConcept($concept)
    {
        $client = new Client();

        $url= "https://snowstorm.fhirdev.wrs.cloud/MAIN/concepts";

        // Future WRS endpoint
        //$url= "https://snowstorm.wrs.cloud/MAIN/concepts";

        $params= [
            "query"=>[
//                'activeFilter'=>true,
 //               'termActive'=>true,
                'language'=>'en',
                'ecl'=>$concept,
//                'active'=>true,
                'limit'=>200
            ],
            "headers"=> [
                "Accept-Language"=>"en"
            ]
        ];

        try
        {
            $response= $client->request('GET',$url,$params);

            if ($response->getStatusCode()==200)
            {
                $body= json_decode($response->getBody());

                $return= $this->parseResponse($body->items);

                return $return;
            }
            else
                throw new InternalErrorException($response->getStatusCode());

        }
        catch (Exception $e)
        {
                throw new InternalErrorException($e->getMessage());
        }
    }

    private function codeSystems($cs)
    {
        $return= null;

        switch($cs)
        {
            case "DiagnosisICD10CM":
                $return= "2.16.840.1.113883.6.90";
                break;

            case "DiagnosisICD9CM":
                $return= "2.16.840.1.113883.6.103";
                break;

            case "DiagnosisSNOMED":
                $return= "2.16.840.1.113883.6.96";
                break;

            case "DrugsRXCUI":
                $return= "2.16.840.1.113883.6.88";
                break;

            case "DrugsNDC":
                $return= "2.16.840.1.113883.6.69";
                break;

            case "LabLOINC":
                $return= "2.16.840.1.113883.6.1";
                break;

            case "LabLOINCObservation":
                $return= "2.16.840.1.113883.11.79";
                break;

            case "ProcedureCPT":
                $return= "2.16.840.1.113883.6.12";
                break;

            case "ProcedureSNOMED":
                $return= "2.16.840.1.113883.6.96";
                break;

        }

        return $return;
    }


    private function directQuery($type,$conceptID)
    {
        $client = new Client();
        $url= "https://connect.medlineplus.gov/service?informationRecipient.languageCode.c=en&mainSearchCriteria.v.c=".$conceptID;

        $url.= "&mainSearchCriteria.v.cs=".$this->codeSystems($type);


        try
        {
            $response= $client->get($url);

            if ($response->getStatusCode()==200)
            {

                $body= $response->getBody()->getContents();


                $xml = simplexml_load_string($body);

                $json = json_encode($xml);

                $array = json_decode($json,TRUE);

                return [
                    "code"=>200,
                    "result"=>$array["entry"]["summary"]
                ];
            }
            else
                throw new InternalErrorException($response->getStatusCode());

        }
        catch (Exception $e)
        {
            return [
                "code"=>403,
                "result"=>"Info not found, please check the concept id"
            ];
        }
    }

    public function getinfo($type,$Concept)
    {
        return $this->directQuery($type,$Concept);
        /*
        die("capo");
        $terms= $this->queryConcept($Concept)->toArray();



        print_r($terms);

        die();
        die("capo");
        */
    }
}
