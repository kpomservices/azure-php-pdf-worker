<?php
    require __DIR__ . '/../vendor/autoload.php';
    use Azserverless\Context\FunctionContext;

    function run(FunctionContext $context) {
        //https://stackoverflow.com/questions/53612373/tcdpf-render-a-pdf-with-a-png-image-problem-with-transparent-png
        define('K_PATH_CACHE', __DIR__ . '/../tempimages/');
        // ini_set('max_execution_time', '1200'); //300 seconds = 5 minutes

        $accesskey = "/1trovN9uvAh0Cvziv/GTgI9V/P/IQJg0BANb9W8beMtTd2KtwnMkpQd4eDz1JTltNoDsl/QdZLj+AStS1RcDg==";            
        $storageAccount = 'papdfgen';

        $req = $context->inputs['req'];

        $context->log->info('Http trigger invoked');

        $query = $req['Query'];//request parameters        
        $body = $req['Body']; //post body paramters
               
        $body = json_decode($body);
        
        if (isset($body->info)) {
            $contentType = "text/plain";
            $name = 'Info';      
            ob_start();
            phpinfo();
            $message = ob_get_contents();
            ob_get_clean();
        } else if (isset($body->image)) {
                       
            $imageData = $body->imageData;
            $imagefilename = $body->imagefilename;

            $extension = explode('/', mime_content_type($imageData))[1];            
            $imagefilename = uniqid() . '.' . $extension;
            
            // $imageData = file_get_contents($imageData);
            //https://blog.niklasottosson.com/php/using-php-curl-to-put-a-string/
            // $fh = tmpfile(); //Get temporary filehandle
            // fwrite($fh, $imageData); //Write the string to the temporary file
            // fseek($fh, 0); //Put filepointer to the beginning of the temporary file

            // $fs = strlen($imageData);

            file_put_contents(__DIR__ . '/../tempimages/'.$imagefilename, file_get_contents($imageData));
            $filetoUpload = __DIR__ . '/../tempimages/'.$imagefilename;
            
            $fh = fopen($filetoUpload, "r");
            $fs = filesize($filetoUpload);

            $containerName = 'objectimages';
            $blobName = $imagefilename;
            
            $destinationURL = "https://$storageAccount.blob.core.windows.net/$containerName/$blobName";
            
            uploadBlob($fh, $fs, $storageAccount, $containerName, $blobName, $destinationURL, $accesskey);
            
            unlink($filetoUpload);
            // fclose($fh);

            $contentType = "text/plain";
            $name = 'Image';
            
            $message = $destinationURL;
        } else if (isset($body->svg)) {
                       
            $jsonData = $body->jsonData;
            $cwidth = $body->cwidth;
            $cheight = $body->cheight;
            $clipbgimg = $body->clipbgimg;
            $clipLeft = $body->clipLeft;
            $clipTop = $body->clipTop;
            $clipWidth = $body->clipWidth;
            $clipHeight = $body->clipHeight;
            $pdffilename = $body->pdffilename;
            $savecrop = 'false';
            $rows = 1;
            $cols = 1;
            $canvasScale = 1;

            $rc = $rows * $cols;

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

                $colscount = 0;

                $rowscount = 0;

                for ($y = $x; $y < $x + $rc; $y++) {
                    $dataString = $jsonData[$y];

                    //Replace font path to real and current path. If not than font will not be loaded

                    // $dataString = str_replace(
                    //     "https://www.kpomservices.com/",
                    //     "../",
                    //     $dataString
                    // );

                    if ($colscount >= $cols) {
                        $colscount = 0;

                        $rowscount++;
                    }

                    // Set Clipping Mask

                    // $pdf->Rect(
                    //     $offsetwidth,
                    //     $offsetheight,
                    //     $offsetwidth,
                    //     $offsetheight,
                    //     "CNZ"
                    // );

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

                            $folderName = strtolower(str_replace(" ", "", $fontFamily));

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
                                __DIR__ . "/../googlefonts/" .
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
                                    __DIR__ . "/../googlefonts/" .
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
                                        __DIR__ . "/../googlefonts/" .
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

                    // $pdf->setXY($offsetwidth, $offsetheight);

                    $pdf->ScaleXY(($scalef / $canvasScale) * 100);

                    $pdf->SetXY(0, 0);
        
                    if(isset($clipbgimg)) {
                        @$pdf->Image($clipbgimg, 0, 0, $cwidth, $cheight, 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
            
                        $pdf->StartTransform();
                        // Set Clipping Mask
                        $pdf->Rect(
                            $clipLeft,
                            $clipTop,
                            $clipWidth,
                            $clipHeight,
                            "CNZ"
                        );
                        
                        $pdf->setXY(150, 150);                
                        $pdf->ImageSVG("@" . $dataString);
                
                        $pdf->StopTransform();                
                    } else {
                        $pdf->ImageSVG("@" . $dataString);
                    }

                    if ($savecrop != "false") {
                        // $pdf->cropMark(
                        //     $offsetwidth * $colscount + $cmp,
                        //     $offsetheight * $rowscount + $cmp,
                        //     $cmp,
                        //     $cmp,
                        //     "TL",
                        //     [136, 136, 136]
                        // );

                        // $pdf->cropMark(
                        //     $offsetwidth * $colscount + $offsetwidth + $cmp,
                        //     $offsetheight * $rowscount + $cmp,
                        //     $cmp,
                        //     $cmp,
                        //     "TR",
                        //     [136, 136, 136]
                        // );

                        // $pdf->cropMark(
                        //     $offsetwidth * $colscount + $cmp,
                        //     $offsetheight * $rowscount + $offsetheight + $cmp,
                        //     $cmp,
                        //     $cmp,
                        //     "BL",
                        //     [136, 136, 136]
                        // );

                        // $pdf->cropMark(
                        //     $offsetwidth * $colscount + $offsetwidth + $cmp,
                        //     $offsetheight * $rowscount + $offsetheight + $cmp,
                        //     $cmp,
                        //     $cmp,
                        //     "BR",
                        //     [136, 136, 136]
                        // );
                    }

                    $colscount++;
                }
            }

            $pdf->Close();

            $contentType = 'text/plain';
            $name = 'PDF';

            $pdf->Output(__DIR__ . '/../outputpdfs/'.$pdffilename, "F");    // send the file in
            $filetoUpload = __DIR__ . '/../outputpdfs/'.$pdffilename;
            
            $fh = fopen($filetoUpload, "r");
            $fs = filesize($filetoUpload);

            $containerName = 'outputpdfs';
            $blobName = $pdffilename;
            
            $destinationURL = "https://$storageAccount.blob.core.windows.net/$containerName/$blobName";
            
            uploadBlob($fh, $fs, $storageAccount, $containerName, $blobName, $destinationURL, $accesskey);
            
            unlink($filetoUpload);
            
            $message = $destinationURL;
            //$message = $pdf->Output('svgtopdf.pdf', "E");    // send the file in
        } else {
            $contentType = "text/plain";
            $name = 'EMPTY';
            $message .= 'Please pass a name in the query string';
        }

        $context->outputs['outputQueueItem'] = json_encode($name);
        $context->log->info(sprintf('Adding queue item: %s', $name));

        return [
            'body' => $message,
            'headers' => [
                'Content-type' => $contentType
            ]
        ];
    }

    //https://stackoverflow.com/questions/41682393/simple-php-curl-file-upload-to-azure-storage-blob
    function uploadBlob($fh, $fs, $storageAccount, $containerName, $blobName, $destinationURL, $accesskey) {

        $currentDate = gmdate("D, d M Y H:i:s T", time());
        // $handle = fopen($fh, "r");
        $fileLen = $fs;
    
        $headerResource = "x-ms-blob-cache-control:max-age=3600\nx-ms-blob-type:BlockBlob\nx-ms-date:$currentDate\nx-ms-version:2015-12-11";
        $urlResource = "/$storageAccount/$containerName/$blobName";
    
        $arraysign = array();
        $arraysign[] = 'PUT';               /*HTTP Verb*/  
        $arraysign[] = '';                  /*Content-Encoding*/  
        $arraysign[] = '';                  /*Content-Language*/  
        $arraysign[] = $fileLen;            /*Content-Length (include value when zero)*/  
        $arraysign[] = '';                  /*Content-MD5*/  
        $arraysign[] = 'image/png';         /*Content-Type*/  
        $arraysign[] = '';                  /*Date*/  
        $arraysign[] = '';                  /*If-Modified-Since */  
        $arraysign[] = '';                  /*If-Match*/  
        $arraysign[] = '';                  /*If-None-Match*/  
        $arraysign[] = '';                  /*If-Unmodified-Since*/  
        $arraysign[] = '';                  /*Range*/  
        $arraysign[] = $headerResource;     /*CanonicalizedHeaders*/
        $arraysign[] = $urlResource;        /*CanonicalizedResource*/
    
        $str2sign = implode("\n", $arraysign);
    
        $sig = base64_encode(hash_hmac('sha256', urldecode(utf8_encode($str2sign)), base64_decode($accesskey), true));  
        $authHeader = "SharedKey $storageAccount:$sig";
    
        $headers = [
            'Authorization: ' . $authHeader,
            'x-ms-blob-cache-control: max-age=3600',
            'x-ms-blob-type: BlockBlob',
            'x-ms-date: ' . $currentDate,
            'x-ms-version: 2015-12-11',
            'Content-Type: image/png',
            'Content-Length: ' . $fileLen
        ];
    
        $ch = curl_init($destinationURL);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_INFILE, $fh); 
        curl_setopt($ch, CURLOPT_INFILESIZE, $fileLen); 
        curl_setopt($ch, CURLOPT_UPLOAD, true); 
        $result = curl_exec($ch);
    
        // echo ('Result<br/>');
        // print_r($result);
    
        // echo ('Error<br/>');
        // print_r(curl_error($ch));
    
        curl_close($ch);
    }

    //https://stackoverflow.com/questions/59987142/azure-file-storage-using-php
    // function storePDFTOAzureStorage() {
    //     $accountName = "<your account name>";
    //     $accountKey = "<your account key>";

    //     $shareName = "<your share name>";
    //     $fileName = "<your pdf file name>";

    //     $now = date(DATE_ISO8601);
    //     $date = date_create($now);
    //     date_add($date, date_interval_create_from_date_string("1 hour"));
    //     $expiry = str_replace("+0000", "Z", date_format($date, DATE_ISO8601));

    //     $helper = new FileSharedAccessSignatureHelper($accountName, $accountKey);
    //     $sas = $helper->generateFileServiceSharedAccessSignatureToken(
    //             Resources::RESOURCE_TYPE_FILE,
    //             "$shareName/$fileName",
    //             'r',                        // Read
    //             $expiry // A valid ISO 8601 format expiry time， such as '2020-01-01T08:30:00Z' 
    //         );
    //     $fileUrlWithSAS = "https://$accountName.file.core.windows.net/$shareName/$fileName?$sas";
    //     echo "<h1>Demo to display PDF from Azure File Storage</h1>";
    //     echo "<iframe src='$fileUrlWithSAS'  width='800' height='500' allowfullscreen webkitallowfullscreen></iframe>";
    // }

    function xml_attribute($object, $attribute)
    {
        if (isset($object[$attribute])) {
            return (string) $object[$attribute];
        }
    }
    
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
?>
