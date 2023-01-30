<?php
    use Azserverless\Context\FunctionContext;
    require __DIR__ . '/vendor/autoload.php';

    function run(FunctionContext $context) {

        $req = $context->inputs['req'];

        $context->log->info('Http trigger invoked');

        $query = $req['Query'];

        if (array_key_exists('name', $query)) {
            $name = $query['name'];
            $message = 'Hello ' . $query['name'] . '!';

            $pdf = new TCPDF();                 // create TCPDF object with default constructor args
            $pdf->AddPage();                    // pretty self-explanatory
            $pdf->Write(1, $message);           // 1 is line height
            //https://techinsighter.wordpress.com/2020/01/03/different-parameters-for-tcpdf-output/
            $message = $pdf->Output('hello_world.pdf', "E");    // send the file in
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
