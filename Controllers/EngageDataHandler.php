<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Elasticsearch\ClientBuilder;

class EngageDataHandler extends Controller
{
    public function handle_engage_data(Request $request)
    {
        if(isset($request["access_key"]) && $request["access_key"] == 'bKmpFxQRcqDbYw3')
        {
            if(isset($request["mode"]) && !empty($request["mode"]))
            {
                $this->client = ClientBuilder::create()->setHosts([env('ELASTICSEARCH_HOST').":".env('ELASTICSEARCH_PORT')])->build();
                $this->search_index_name = env('ELASTICSEARCH_DEFAULTINDEX');
                
                if($request["mode"] == 'get_twitter_handle_data') //This will return data from elastic via twitter handle name
                {
                    if(isset($request["twitter_handle_str"]) && !empty($request["twitter_handle_str"]))
                    {
                        $params = [
                            'index' => $this->search_index_name,
                            'type' => 'mytype',
                            'from' => '0',
                            'size' => '5',
                            'body' => [
                                'query' => [
                                    'bool' => [
                                        'must' => [
                                            ['query_string' => ['query' => 'p_message_text:("'.$request["twitter_handle_str"].'") AND source:("Twitter")']] //(p_message_text:("'.$request["twitter_handle_str"].'") OR u_source:("'.$request["twitter_handle_str"].'") OR u_fullname:("'.$request["twitter_handle_str"].'")) AND source:("Twitter")'
                                        ]
                                    ]
                                ],
                                'sort' => [
                                    ['p_created_time' => ['order' => 'desc']]
                                ]
                            ]
                        ];
                        
                        $results = $this->client->search($params);
                        
                        $output_data = array();
                        
                        for ($i = 0; $i < count($results["hits"]["hits"]); $i++)
                        {
                            $output_data[$i]["pid"] = $results["hits"]["hits"][$i]["_source"]["p_id"];
                            $output_data[$i]["message"] = $results["hits"]["hits"][$i]["_source"]["p_message_text"];
                            $output_data[$i]["url"] = $results["hits"]["hits"][$i]["_source"]["p_url"];
                            $output_data[$i]["full_name"] = $results["hits"]["hits"][$i]["_source"]["u_fullname"];
                            $output_data[$i]["pic"] = $results["hits"]["hits"][$i]["_source"]["u_profile_photo"];
                            $output_data[$i]["profile"] = $results["hits"]["hits"][$i]["_source"]["u_source"];
                        }
                        
                        return response()->json(['status' => 'Success', 'data' => $output_data]);
                    }
                    else
                    {
                        return response()->json(['status' => 'Error', 'message' => 'Handler name not provided']);
                    }
                }
            }
            else
            {
                return response()->json(['status' => 'Error', 'message' => 'Data identification mode not provided']);
            }
        }
        else
        {
            return response()->json(['status' => 'Error', 'message' => 'Invalid access']);
        }
    }
}
?>
