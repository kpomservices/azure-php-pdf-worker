<?php
    require __DIR__ . '/../vendor/autoload.php';
    use Azserverless\Context\FunctionContext;

    function run(FunctionContext $context) {

        $req = $context->inputs['req'];

        $context->log->info('Http trigger invoked');

        $query = $req['Query'];        
        $body = $req['Body'];       
        $body = json_decode($body); 
       
        if (isset($body->svg)) {
            
            //$cwidth = 750;
            //$cheight = 600;            
            // $jsonData = preg_replace("/%u([0-9a-f]{3,4})/i","&#x\\1;",$query['jsonData']); 
            // $jsonData = html_entity_decode($jsonData,null,'UTF-8');
            // $jsonData = urldecode($query['jsonData']);
            $jsonData = $body->jsonData;
            // $cwidth = $query['cwidth'];
            // $cheight = $query["cheight"];
            // $canvasScale = $query["scale"];
            // $jsonData = "Hello World";
            $cwidth = 5*96;
            $cheight = 7*96;
            $savecrop = 'false';
            $rows = 1;
            $cols = 1;
            $canvasScale = 1;

            $rc = $rows * $cols;

            // $jsonData = json_decode($jsonData);

            //scale to 0.75 for inch based on DPI.
            $scalef = 72 / 96;

            //crop mark padding

            $cmp = 0;

            if ($savecrop != "false") {
                $cmp = 10;
            }

            $pdf = new TCPDF(
                "",
                "px",
                [
                    $cwidth * $scalef * $cols + $cmp * 2,
                    $cheight * $scalef * $rows + $cmp * 2,
                ],
                true,
                "UTF-8",
                false,
                false
            );

            $pdf->SetCreator(PDF_CREATOR);

            $pdf->SetHeaderMargin(0);

            $pdf->SetFooterMargin(0);

            $pdf->SetLeftMargin(0);

            $pdf->SetRightMargin(0);

            $pdf->setPrintFooter(false);

            $pdf->setPrintHeader(false);

            $pdf->setCellMargins(0, 0, 0, 0);

            $pdf->SetCellPaddings(0, 0, 0, 0);

            // set auto page breaks

            $pdf->SetAutoPageBreak(false);

            $pdf->SetDisplayMode(100);

            $totalcanvas = count($jsonData);

            $offsetwidth = $cwidth * $scalef;

            $offsetheight = $cheight * $scalef;

            class Font
            {
                public $fontName;

                public $fontStyle;

                public $fontWeight;

                public $textDecoration;
            }
            $message = $totalcanvas;
            for ($x = 0; $x < $totalcanvas; $x += $rc) {
            }

            $pdf->Close();

            //$message = $pdf->Output('svgtopdf.pdf', "E");    // send the file in

            function Hex2RGB($color)
            {
                $color = str_replace("#", "", $color);

                if (strlen($color) != 6) {
                    return [0, 0, 0];
                }

                $rgb = [];

                for ($x = 0; $x < 3; $x++) {
                    $rgb[$x] = hexdec(substr($color, 2 * $x, 2));
                }

                return $rgb;
            }

            function xml_attribute($object, $attribute)
            {
                if (isset($object[$attribute])) {
                    return (string) $object[$attribute];
                }
            }            
        } else {
            $name = 'EMPTY';
            $message .= 'Please pass a name in the query string';
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
