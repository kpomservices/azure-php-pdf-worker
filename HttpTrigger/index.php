<?php
    require __DIR__ . '/../vendor/autoload.php';
    use Azserverless\Context\FunctionContext;

    function run(FunctionContext $context) {

        $req = $context->inputs['req'];

        $context->log->info('Http trigger invoked');

        $query = $req['Query'];        
        $body = $req['Body'];        
        $message = $body;

        if (array_key_exists('svg', $query)) {
            
            //$cwidth = 750;
            //$cheight = 600;            
            // $jsonData = preg_replace("/%u([0-9a-f]{3,4})/i","&#x\\1;",$query['jsonData']); 
            // $jsonData = html_entity_decode($jsonData,null,'UTF-8');
            // $jsonData = urldecode($query['jsonData']);
            $jsonData = $query['jsonData'];
            $message = $jsonData;
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

            $jsonData = json_decode($jsonData);

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
            for ($x = 0; $x < $totalcanvas; $x += $rc) {
                $pdf->AddPage();

                $pdf->StartTransform();

                //$pdf->ScaleXY($scalef * 100);

                $colscount = 0;

                $rowscount = 0;

                for ($y = $x; $y < $x + $rc; $y++) {
                    $dataString = $jsonData[$y];
                    $message = $dataString;

                    //Replace font path to real and current path. If not than font will not be loaded

                    //$dataString = str_replace("design.youprintem.com",$_SERVER['HTTP_HOST'].'/HTML5CanvasTemplateEditor/design',$dataString);

                    $dataString = str_replace(
                        "https://www.kpomservices.com/",
                        "../",
                        $dataString
                    );

                    if ($colscount >= $cols) {
                        $colscount = 0;

                        $rowscount++;
                    }

                    // start a new XObject Template and set transparency group option

                    $template_id = $pdf->startTemplate(
                        $offsetwidth * 2,
                        $offsetheight * 2,
                        true
                    );

                    $pdf->StartTransform();

                    // Set Clipping Mask

                    $pdf->Rect(
                        $offsetwidth,
                        $offsetheight,
                        $offsetwidth,
                        $offsetheight,
                        "CNZ"
                    );

                    //Return attribute font name

                    $decoded_xml = simplexml_load_string($dataString);

                    $fontArr = [];

                    $fontNamesArr = [];

                    //Store fonts into an array

                    foreach ($decoded_xml[0] as $i => $xmlList) {
                        $fontFamilyArr = $xmlList->text;

                        $fontName = xml_attribute($fontFamilyArr, "font-family");

                        $fontStyle = xml_attribute($fontFamilyArr, "font-style");

                        $fontWeight = xml_attribute($fontFamilyArr, "font-weight");

                        $textDecoration = xml_attribute($fontFamilyArr, "text-decoration");

                        if (!in_array($fontName, $fontNamesArr)) {
                            $localFont = new Font();

                            $localFont->fontName = $fontName;

                            $localFont->fontStyle = $fontStyle;

                            $localFont->fontWeight = $fontWeight;

                            $localFont->textDecoration = $textDecoration;

                            array_push($fontArr, $localFont);

                            array_push($fontNamesArr, $fontName);
                        }
                    }

                    //Load neccesory fonts
                    foreach ($fontArr as $localFont) {
                        $fontFamily = $localFont->fontName;

                        $fontStyle = $localFont->fontStyle;

                        $fontWeight = $localFont->fontWeight;

                        $textDecoration = $localFont->textDecoration;

                        if ($fontFamily != "" && strlen($fontFamily) > 0) {
                            //$folderName = str_replace(" ","_", $fontFamily);

                            $folderName = $fontFamily;

                            $fontFileName = str_replace(" ", "", $fontFamily);

                            if ($fontStyle == "italic" && $fontWeight == "bold") {
                                $fontStyle = "BoldItalic";
                            } elseif ($fontStyle == "italic") {
                                $fontStyle = "Italic";
                            } elseif ($fontWeight == "bold") {
                                $fontStyle = "Bold";
                            } else {
                                $fontStyle = "Regular";
                            }

                            $fontname = "";

                            $fontpath =
                                "./tcpdf/fonts/googlefonts/" .
                                $folderName .
                                "/" .
                                $fontFileName .
                                "-" .
                                $fontStyle .
                                ".ttf";

                            if (file_exists($fontpath)) {
                                $fontname = TCPDF_FONTS::addTTFfont(
                                    $fontpath,
                                    "TrueTypeUnicode",
                                    "",
                                    96
                                );
                            } else {
                                $fontpath =
                                    "./tcpdf/fonts/googlefonts/" .
                                    $folderName .
                                    "/" .
                                    $fontFileName .
                                    ".ttf";

                                if (file_exists($fontpath)) {
                                    $fontname = TCPDF_FONTS::addTTFfont(
                                        $fontpath,
                                        "TrueTypeUnicode",
                                        "",
                                        96
                                    );
                                } else {
                                    $fontpath =
                                        "./tcpdf/fonts/googlefonts/" .
                                        $folderName .
                                        "/" .
                                        $fontFileName .
                                        "-Regular.ttf";

                                    if (file_exists($fontpath)) {
                                        $fontname = TCPDF_FONTS::addTTFfont(
                                            $fontpath,
                                            "TrueTypeUnicode",
                                            "",
                                            96
                                        );
                                    }
                                }
                            }

                            if ($fontStyle == "Italic") {
                                $fontStyle = "i";
                            } elseif ($fontStyle == "Bold") {
                                $fontStyle = "b";
                            } else {
                                $fontStyle = "";
                            }

                            $pdf->SetFont($fontname, $fontStyle, 14, "", false);
                        }
                    }

                    $pdf->setXY($offsetwidth, $offsetheight);

                    $pdf->ScaleXY(($scalef / $canvasScale) * 100);
                    $message = $dataString;
                    $pdf->ImageSVG("@" . $dataString);

                    $pdf->StopTransform();

                    // end the current Template

                    $pdf->endTemplate();

                    $pdf->printTemplate(
                        $template_id,
                        $offsetwidth * $colscount - $offsetwidth + $cmp,
                        $offsetheight * $rowscount - $offsetheight + $cmp,
                        $offsetwidth * 2,
                        $offsetheight * 2,
                        "",
                        "",
                        false
                    );

                    if ($savecrop != "false") {
                        $pdf->cropMark(
                            $offsetwidth * $colscount + $cmp,
                            $offsetheight * $rowscount + $cmp,
                            $cmp,
                            $cmp,
                            "TL",
                            [136, 136, 136]
                        );

                        $pdf->cropMark(
                            $offsetwidth * $colscount + $offsetwidth + $cmp,
                            $offsetheight * $rowscount + $cmp,
                            $cmp,
                            $cmp,
                            "TR",
                            [136, 136, 136]
                        );

                        $pdf->cropMark(
                            $offsetwidth * $colscount + $cmp,
                            $offsetheight * $rowscount + $offsetheight + $cmp,
                            $cmp,
                            $cmp,
                            "BL",
                            [136, 136, 136]
                        );

                        $pdf->cropMark(
                            $offsetwidth * $colscount + $offsetwidth + $cmp,
                            $offsetheight * $rowscount + $offsetheight + $cmp,
                            $cmp,
                            $cmp,
                            "BR",
                            [136, 136, 136]
                        );
                    }

                    //$pdf->printTemplate($template_id, ($offsetwidth * $colscount), ($offsetheight * $rowscount), $offsetwidth, $offsetheight, '', '', false);

                    $colscount++;
                }

                $pdf->StopTransform();
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
