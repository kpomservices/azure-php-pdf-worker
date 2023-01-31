<?php
    require __DIR__ . '/../vendor/autoload.php';
    use Azserverless\Context\FunctionContext;

    function run(FunctionContext $context) {

        $req = $context->inputs['req'];

        $context->log->info('Http trigger invoked');

        $query = $req['Query'];        

        if (array_key_exists('svg', $query)) {
            
            //$cwidth = 750;
            //$cheight = 600;            
            // $jsonData = preg_replace("/%u([0-9a-f]{3,4})/i","&#x\\1;",$query['jsonData']); 
            // $jsonData = html_entity_decode($jsonData,null,'UTF-8');
            $jsonData = urldecode($query['jsonData']);
            // $jsonData = $query['jsonData'];
            $message = $jsonData;
        } else {
            $name = 'EMPTY';
            $message = 'Please pass a name in the query string';
        }

        $context->outputs['outputQueueItem'] = json_encode($name);
        $context->log->info(sprintf('Adding queue item: %s', $name));

        return [
            'body' => $message,
            'headers' => [
                'Content-type' => 'text/plain'
            ]
        ];
    }
?>
