<?php

declare(strict_types=1);

include_once __DIR__ . '/../libs/WebHookModule.php';
include_once __DIR__ . '/../libs/pdfReport.php';

    class VariablenVergleich extends WebHookModule
    {
        use pdfReport;

        const PNG_FONT_SIZE = 5;
        const MINOR_LINE = 4 / 2;
        const MAJOR_LINE = 8 / 2;
        const CIRCLE_DIAMETER = 6;
        public function __construct($InstanceID)
        {
            parent::__construct($InstanceID, 'linear-regression/' . $InstanceID);
        }

        public function Create()
        {

            //Baseline Variables
            $this->RegisterPropertyInteger('XValueBaseline', 0);
            $this->RegisterPropertyInteger('YValueBaseline', 0);
            $this->RegisterPropertyInteger('BaseLineColor', 0);

            //Variable settings
            $this->RegisterPropertyInteger('AggregationLevel', 1);
            $this->RegisterPropertyString('AxesValues', '[]');

            //Chart settings
            $this->RegisterPropertyInteger('AxisMinorStep', 1);
            $this->RegisterPropertyInteger('AxisMajorStep', 5);
            $this->RegisterPropertyString('ChartFormat', 'svg');
            $this->RegisterPropertyInteger('ChartWidth', 1000);
            $this->RegisterPropertyInteger('ChartHeight', 500);
            $this->RegisterPropertyInteger('YMax', 40);
            $this->RegisterPropertyInteger('YMin', 0);
            $this->RegisterPropertyInteger('XMax', 40);
            $this->RegisterPropertyInteger('XMin', 0);

            $this->RegisterPropertyString('Chart', '');

            $this->RegisterVariableString('ChartSVG', $this->Translate('Chart'), '~HTMLBox', 50);
            $this->RegisterVariableString('Function', $this->Translate('Function'), '', 10);
            $this->RegisterVariableFloat('YIntercept', $this->Translate('b'), '', 20);
            $this->RegisterVariableFloat('Slope', $this->Translate('m'), '', 30);
            $this->RegisterVariableFloat('MeasureOfDetermination', $this->Translate('Measure of determination'), '', 40);

            //Baseline Variables
            $this->RegisterVariableInteger('StartDateBaseline', $this->Translate('Start Date Baseline'), '~UnixTimestampDate', 52);
            $this->EnableAction('StartDateBaseline');
            if ($this->GetValue('StartDateBaseline') == 0) {
                $this->SetValue('StartDateBaseline', strtotime('01.01.' . date('Y')));
            }
            $this->RegisterVariableInteger('EndDateBaseline', $this->Translate('End Date Baseline'), '~UnixTimestampDate', 53);
            $this->EnableAction('EndDateBaseline');
            if ($this->GetValue('EndDateBaseline') == 0) {
                $this->SetValue('EndDateBaseline', time());
            }

            $this->RegisterVariableBoolean('BaseLineCloud', $this->Translate('BaseLine Cloud'), '~Switch', 54);
            $this->EnableAction('BaseLineCloud');

            $this->RegisterVariableInteger('RangeforReport', $this->Translate('Range for Report'), '', 55);
            $this->EnableAction('RangeforReport');

            $this->RegisterPropertyInteger('Outlier', 30);
            //Report settings
            $this->RegisterPropertyString('Logo', '');

            $this->RegisterAttributeInteger('OldDateVariables', 0);

            $MedienID = @IPS_GetMediaIDByName($this->Translate('Report Energy-saving meter'), $this->InstanceID);
            if (!IPS_MediaExists($MedienID)) {
                $MedienID = IPS_CreateMedia(5);
                IPS_SetName($MedienID, $this->Translate('Report Energy-saving meter'));
                IPS_SetParent($MedienID, $this->InstanceID);
                IPS_SetPosition($MedienID, 50);
            }
        }

        public function Destroy()
        {
            //Never delete this line!
            parent::Destroy();
        }

        public function ApplyChanges()
        {
            //Never delete this line!
            parent::ApplyChanges();

            if (!@$this->GetIDForIdent('ChartPNG')) {
                $mediaID = IPS_CreateMedia(1);
                IPS_SetIdent($mediaID, 'ChartPNG');
                IPS_SetName($mediaID, 'Chart');
                IPS_SetParent($mediaID, $this->InstanceID);
                IPS_SetPosition($mediaID, 50);
                $this->UpdateFormField('Chart', 'mediaID', $mediaID);
            }

            $oldDateVariables = $this->ReadAttributeInteger('OldDateVariables');
            $axesValues = json_decode($this->ReadPropertyString('AxesValues'), true);

            if ($oldDateVariables == 0) {
                $oldDateVariables = count($axesValues); // Wenn die Liste leerr ist
            }

            for ($i = 0; $i <= $oldDateVariables; $i++) {
                if (count($axesValues) <= $i) {
                    $this->UnregisterVariable('StartDate' . $i);
                    $this->UnregisterVariable('EndDate' . $i);
                    continue;
                } else {
                    $this->RegisterVariableInteger('StartDate' . $i, $this->Translate('Start Date') . ' ' . $i, '~UnixTimestampDate', 60 + ($i * 10));
                    $this->EnableAction('StartDate' . $i);
                    if ($this->GetValue('StartDate' . $i) == 0) {
                        $this->SetValue('StartDate' . $i, strtotime('01.01.' . date('Y')));
                    }
                    $this->RegisterVariableInteger('EndDate' . $i, $this->Translate('End Date') . ' ' . $i, '~UnixTimestampDate', 70 + ($i * 10));
                    $this->EnableAction('EndDate' . $i);
                    if ($this->GetValue('EndDate' . $i) == 0) {
                        $this->SetValue('EndDate' . $i, time());
                    }
                }
            }
            $this->WriteAttributeInteger('OldDateVariables', count($axesValues));
            $this->UpdateChart();
        }

        public function GetConfigurationForm()
        {
            $form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);

            $charts = $this->GenerateChart();
            if ($charts != null) {
                if ($this->ReadPropertyString('ChartFormat') == 'svg') {
                    $form['elements'][0]['items'][1]['image'] = 'data:image/svg+xml;utf8,' . $charts['SVG'];
                } else {
                    $form['elements'][0]['items'][1]['image'] = 'data:image/png;base64,' . $charts['PNG'];
                }
            }

            return json_encode($form);
        }
        public function testReport(string $type, int $ListIndex)
        {
            $ReportFileName = $this->getReport($type, $ListIndex);
            $MedienID = @IPS_GetMediaIDByName($this->Translate('Report Energy-saving meter'), $this->InstanceID);
            IPS_SetMediaFile($MedienID, $ReportFileName, true);
        }

        public function RequestAction($Ident, $Value)
        {
            switch ($Ident) {
                case  preg_match('/StartDate.*/', $Ident) ? true : false:
                // No break. Add additional comment above this line if intentional
                case  preg_match('/EndDate.*/', $Ident) ? true : false:
                // No break. Add additional comment above this line if intentional
                case 'StartDate':
                case 'EndDate':
                case 'BaseLineCloud':
                    $this->SetValue($Ident, $Value);
                    $this->UpdateChart();
                    break;
                case 'RangeforReport':
                    $this->SetValue($Ident, $Value);
                    $ReportFileName = $this->getReport('pdf', intval($Value));
                    $MedienID = @IPS_GetMediaIDByName($this->Translate('Report Energy-saving meter'), $this->InstanceID);
                    IPS_SetMediaFile($MedienID, $ReportFileName, true);
                    break;
                default:
                    throw new Exception('Invalid Ident');
            }
        }

        public function UpdateChart()
        {
            $charts = $this->GenerateChart();
            if ($charts == null) {
                return;
            }
            $svg = $charts['SVG'];
            $png = $charts['PNG'];
            //Force update
            if ($this->ReadPropertyString('ChartFormat') == 'svg') {
                $this->UpdateFormField('Chart', 'image', 'data:image/svg+xml;utf8,' . $svg);
                $this->SetValue('ChartSVG', '<div style="background-color:white; width:' . $this->ReadPropertyInteger('ChartWidth') . "px\">$svg</div>");
                IPS_SetHidden($this->GetIDForIdent('ChartPNG'), true);
                IPS_SetHidden($this->GetIDForIdent('ChartSVG'), false);
            } else {
                $mediaID = $this->GetIDForIdent('ChartPNG');
                IPS_SetMediaFile($mediaID, $mediaID . '.png', false);
                IPS_SetMediaContent($mediaID, $png);
                $this->UpdateFormField('Chart', 'mediaID', $mediaID);
                IPS_SetHidden($this->GetIDForIdent('ChartPNG'), false);
                IPS_SetHidden($this->GetIDForIdent('ChartSVG'), true);
            }
        }

        public function GenerateChart(int $RangeIndex = null)
        {
            $yAxisMax = $this->ReadPropertyInteger('YMax');
            $yAxisMin = $this->ReadPropertyInteger('YMin');
            $xAxisMax = $this->ReadPropertyInteger('XMax');
            $xAxisMin = $this->ReadPropertyInteger('XMin');

            $axesValues = json_decode($this->ReadPropertyString('AxesValues'), true);
            if (count($axesValues) <= 0) {
                $this->SetStatus(202);
                return;
            }

            //Set the status to active if there are no errors
            $this->SetStatus(102);

            $customWidth = $this->ReadPropertyInteger('ChartWidth');
            $customHeight = $this->ReadPropertyInteger('ChartHeight');

            $chartXOffset = 50;
            $chartYOffset = 50;
            $xRange = $customWidth - $chartXOffset;
            $width = $xRange + $chartXOffset;

            $xAvailablePixels = $customWidth - $chartYOffset * 2;

            $yAvailablePixels = $customHeight - $chartYOffset * 2;
            $height = $customHeight;

            $image = imagecreate($width, $height);
            $font = 5;

            //PNG colors
            $white = imagecolorallocate($image, 255, 255, 255);
            $textWhite = imagecolorallocate($image, 254, 254, 254);
            $black = imagecolorallocate($image, 0, 0, 0);
            $textColor = $black;
            imagecolortransparent($image, $white);
            // imagefill($image, 0, 0, $grey);

            $dynamicXMinValue = $this->getDynamicMinValue($xAxisMin, $xAxisMax);
            $getXValue = function ($x) use ($xRange, $chartXOffset, $customWidth, $xAvailablePixels, $dynamicXMinValue)
            {
                $xAxisMin = $this->ReadPropertyInteger('XMin');
                $xAxisMax = $this->ReadPropertyInteger('XMax');
                return intval($this->getZeroX($xAxisMin, $xAxisMax, $xAvailablePixels) + ($x - $dynamicXMinValue) * (($xAvailablePixels) / ($xAxisMax - $xAxisMin))) - 1;
            };

            $dynamicYMinValue = $this->getDynamicMinValue($yAxisMin, $yAxisMax);

            $getYValue = function ($y) use ($yAvailablePixels, $chartYOffset, $yAxisMin, $yAxisMax, $dynamicYMinValue)
            {
                $yZero = $this->getZeroY($yAxisMin, $yAxisMax, $yAvailablePixels);
                return intval($yZero - ($y - $dynamicYMinValue) * (($yAvailablePixels) / ($yAxisMax - $yAxisMin))) - 1;
            };

            $svg = '<svg version="1.1" ';
            $svg .= 'width= "' . $width . '" height="' . $height . '" ';
            $svg .= 'xmlns="http://www.w3.org/2000/svg"> ';

            //Y AXIS
            imageline($image, $getXValue($dynamicXMinValue), $getYValue($yAxisMin), $getXValue($dynamicXMinValue), $getYValue($yAxisMax), $textColor);
            $svg .= $this->drawLine($getXValue($dynamicXMinValue), $getYValue($yAxisMin), $getXValue($dynamicXMinValue), $getYValue($yAxisMax), 'black');

            //Y number line
            $axisLabelOffset = 5;
            $svgOffset = 5;
            $charWidth = imagefontwidth($font);
            $yAxisPosition = $getXValue($dynamicXMinValue);
            for ($j = $yAxisMin; $j <= $yAxisMax; $j++) {
                $offset = intval(imagefontheight($font) / 2);
                $stepPosition = $getYValue($j);
                if ($j % $this->ReadPropertyInteger('AxisMajorStep') == 0) {
                    imageline($image, $yAxisPosition - self::MAJOR_LINE, $stepPosition, $yAxisPosition + self::MAJOR_LINE, $stepPosition, $textColor);
                    imagestring($image, $font, $yAxisPosition - ($charWidth * strlen(strval($j))) - $axisLabelOffset, $stepPosition - $offset, strval($j), $textColor);
                    $svg .= $this->drawLine($yAxisPosition - self::MAJOR_LINE, $stepPosition, $yAxisPosition + self::MAJOR_LINE, $stepPosition, 'black');
                    $svg .= $this->drawText($yAxisPosition - $svgOffset, $stepPosition, 'black', 15, strval($j), true);
                } elseif ($j % $this->ReadPropertyInteger('AxisMinorStep') == 0) {
                    imageline($image, $yAxisPosition - self::MINOR_LINE, $stepPosition, $yAxisPosition + self::MINOR_LINE, $stepPosition, $textColor);
                    $svg .= $this->drawLine($yAxisPosition - self::MINOR_LINE, $stepPosition, $yAxisPosition + self::MINOR_LINE, $stepPosition, 'black');
                }
            }

            $axisNameLabelOffset = 25;
            //Y label
            $charHeight = imagefontheight($font);
            $yLabelText = $this->getAxisLabel('YValue');
            imagestringup($image, 5, $getXValue($xAxisMin) - imagefontheight($font) - $axisLabelOffset * 2 - ($charWidth * strlen(strval($yAxisMax))), intval($customHeight / 2 + (($charWidth * strlen($yLabelText)) / 2)), $yLabelText, $textColor);
            $svg .= $this->drawAxisTitle(1 + $svgOffset, $customHeight / 2, 'black', $yLabelText, true);

            //X AXIS
            imageline($image, $getXValue($xAxisMin), $getYValue($dynamicYMinValue), $getXValue($xAxisMax), $getYValue($dynamicYMinValue), $textColor);
            $svg .= $this->drawLine($getXValue($xAxisMin), $getYValue($dynamicYMinValue), $getXValue($xAxisMax), $getYValue($dynamicYMinValue), 'black');

            //X number line
            for ($j = $xAxisMin; $j <= $xAxisMax; $j++) {
                $stepPosition = $getXValue($j);
                if ($j % $this->ReadPropertyInteger('AxisMajorStep') == 0) {
                    $valueString = strval($j);
                    $offset = intval((strlen($valueString) * $charWidth) / 2);
                    imageline($image, $stepPosition, $getYValue($dynamicYMinValue) - self::MAJOR_LINE, $stepPosition, $getYValue($dynamicYMinValue) + self::MAJOR_LINE, $textColor);
                    imagestring($image, $font, $getXValue($j) - $offset, $getYValue($dynamicYMinValue) + 10, strval($j), $textColor);
                    $svg .= $this->drawLine($stepPosition, $getYValue($dynamicYMinValue) - self::MAJOR_LINE, $stepPosition, $getYValue($dynamicYMinValue) + self::MAJOR_LINE, 'black');
                    $svg .= $this->drawText($stepPosition, $getYValue($dynamicYMinValue) + $svgOffset, 'black', 15, $valueString, false);
                } elseif ($j % $this->ReadPropertyInteger('AxisMinorStep') == 0) {
                    imageline($image, $stepPosition, $getYValue($dynamicYMinValue) - self::MINOR_LINE, $stepPosition, $getYValue($dynamicYMinValue) + self::MINOR_LINE, $textColor);
                    $svg .= $this->drawLine($stepPosition, $getYValue($dynamicYMinValue) - self::MINOR_LINE, $stepPosition, $getYValue($dynamicYMinValue) + self::MINOR_LINE, 'black');
                }
            }

            //X label
            $xLabelText = $this->getAxisLabel('XValue');
            imagestring($image, 5, $customWidth / 2 - intval((strlen($xLabelText) * $charWidth) / 2), $getYValue($xAxisMin) + $axisNameLabelOffset, $xLabelText, $textColor);
            $svg .= $this->drawAxisTitle($customWidth / 2, $customHeight - $svgOffset, 'black', $xLabelText);

            //Alle Punkte aus der Liste malen, oder nur einen.
            if (is_null($RangeIndex)) {
                for ($i = 0; $i < count($axesValues); $i++) {
                    $xVariableId = $axesValues[$i]['XValue'];
                    $yVariableId = $axesValues[$i]['YValue'];
                    $startDate = $this->GetValue('StartDate' . $i);
                    $endDate = $this->GetValue('EndDate' . $i);
                    $svg .= $this->drawPointCloud($image, $xVariableId, $yVariableId, $startDate, $endDate, $axesValues[$i]['PointColor'], $getXValue, $getYValue);
                }
            } else {
                if (count($axesValues) >= $RangeIndex) {
                    $xVariableId = $axesValues[$RangeIndex]['XValue'];
                    $yVariableId = $axesValues[$RangeIndex]['YValue'];
                    $startDate = $this->GetValue('StartDate' . $RangeIndex);
                    $endDate = $this->GetValue('EndDate' . $RangeIndex);
                    $svg .= $this->drawPointCloud($image, $xVariableId, $yVariableId, $startDate, $endDate, $axesValues[$RangeIndex]['PointColor'], $getXValue, $getYValue);
                }
            }

            //Baseline Values
            $xVariableId = $this->ReadPropertyInteger('XValueBaseline');
            $yVariableId = $this->ReadPropertyInteger('YValueBaseline');
            $startDate = $this->GetValue('StartDateBaseline');
            $endDate = $this->GetValue('EndDateBaseline');
            $Values = $this->getValues($xVariableId, $yVariableId, $startDate, $endDate);

            //Baseline auch als Wolke zeichnen
            if ($this->GetValue('BaseLineCloud')) {
                $svg .= $this->drawPointCloud($image, $xVariableId, $yVariableId, $startDate, $endDate, $this->ReadPropertyInteger('BaseLineColor'), $getXValue, $getYValue);
            }

            if ($Values != null) {
                $valuesX = $Values['x'];
                $valuesY = $Values['y'];

                //Linear regression - Baseline
                $lineHex = '#' . str_pad(dechex($this->ReadPropertyInteger('BaseLineColor')), 6, '0', STR_PAD_LEFT);
                $lineRGB = $this->splitHexToRGB($lineHex);
                $lineSVGColor = 'rgb(' . implode(',', $lineRGB) . ')';
                $lineColor = imagecolorallocate($image, $lineRGB[0], $lineRGB[1], $lineRGB[2]);

                //Filter Werte mit 0
                $keysValueNull = (array_keys($valuesY, 0));

                for ($i = 0; $i <= count($keysValueNull) - 1; $i++) {
                    unset($valuesX[$keysValueNull[$i]]);
                    $valuesX = array_values($valuesX);
                    unset($valuesY[$keysValueNull[$i]]);
                    $valuesY = array_values($valuesY);
                }

                $lineParameters = $this->computeLinearRegressionParameters($valuesX, $valuesY);

                $this->SetValue('YIntercept', $lineParameters[0]);
                $this->SetValue('Slope', $lineParameters[1]);
                $this->SetValue('Function', sprintf('f(x) = %s - %sx', $lineParameters[0], $lineParameters[1]));
                $this->SetValue('MeasureOfDetermination', $lineParameters[2]);
                imageline($image, $getXValue($xAxisMin), intval($getYValue($lineParameters[0] + ($lineParameters[1] * $xAxisMin))), $getXValue($xAxisMax), intval($getYValue($lineParameters[0] + ($lineParameters[1] * $xAxisMax))), $lineColor);
                $svg .= $this->drawLine($getXValue($xAxisMin), intval($getYValue($lineParameters[0] + ($lineParameters[1] * $xAxisMin))), $getXValue($xAxisMax), intval($getYValue($lineParameters[0] + ($lineParameters[1] * $xAxisMax))), $lineSVGColor);
            }

            $svg .= '</svg>';
            // $this->SendDebug('SVG', $svg, 0);

            //Base64 encode image
            ob_start();
            imagepng($image);
            $imageData = ob_get_contents();
            ob_end_clean();
            $base64 = base64_encode($imageData);
            $this->SetBuffer('ChartPNG', $base64);
            $this->SetBuffer('ChartSVG', $svg);

            return [
                'SVG' => $svg,
                'PNG' => $base64
            ];
        }

        public function Download()
        {
            $charts = $this->GenerateChart();
            echo '/hook/linear-regression/' . $this->InstanceID;
        }

        public function getReport($type, $ListIndex)
        {
            $axesValues = json_decode($this->ReadPropertyString('AxesValues'), true);
            if (count($axesValues) <= 0) {
                $this->SetStatus(202);
                return;
            }
            $xVariableId = $axesValues[$ListIndex]['XValue'];
            $yVariableId = $axesValues[$ListIndex]['YValue'];
            $startDate = $this->GetValue('StartDate' . $ListIndex);
            $endDate = $this->GetValue('EndDate' . $ListIndex);
            $Values = $this->getValues($xVariableId, $yVariableId, $startDate, $endDate, true);

            $b = $this->GetValue('YIntercept');
            $m = $this->GetValue('Slope');
            $r = $this->GetValue('MeasureOfDetermination');

            $valuesX = $Values['x'];
            $valuesY = $Values['y'];

            $report = [];

            for ($i = 0; $i <= count($Values['x']) - 1; $i++) {
                $report[$i]['timestampX'] = date('d.m.y', $Values['x'][$i]['TimeStamp']);
                $report[$i]['Temperatur'] = $Values['x'][$i]['Avg'];
                $report[$i]['BerchnetAusBaseline'] = $m * $Values['x'][$i]['Avg'] + $b;
                $report[$i]['Verbrauch'] = $Values['y'][$i]['Avg'];
                $report[$i]['Einsparung'] = $report[$i]['BerchnetAusBaseline'] - $Values['y'][$i]['Avg'];
                $report[$i]['Fehlerhaft'] = false;

                //Prüfung ob Wert zu hoch oder zu niedrig
                $outlier = $this->ReadPropertyInteger('Outlier');
                $outlierValue = ($report[$i]['BerchnetAusBaseline'] / 100) * $outlier;
                $min = $report[$i]['BerchnetAusBaseline'] - $outlierValue;
                $max = $report[$i]['BerchnetAusBaseline'] + $outlierValue;

                if (($report[$i]['Verbrauch'] <= $min) || ($report[$i]['Verbrauch'] >= $max)) {
                    $report[$i]['Fehlerhaft'] = true;
                    $this->LogMessage($this->Translate('The value') . ' (' . date('d.m.y', $Values['x'][$i]['TimeStamp']) . ') ' . $this->Translate('could be faulty.'), KL_WARNING);
                }
            }

            if ($type == 'csv') {
                $csv = 'Datum X;Datum Y;Berchnet aus Baseline;Einsparung';
                $csv .= "\n";
                foreach ($report as $value) {
                    $csv .= implode(';', $value) . PHP_EOL;
                }
                mb_convert_encoding($csv, 'UTF-8', mb_list_encodings());
                return $csv;
            }

            if ($type == 'pdf') {
                $ReportFileName = $this->GeneratePDFReport($report, $this->GenerateChart($ListIndex)['PNG']);
                return $ReportFileName;
            }

            return $report;
        }

        protected function ProcessHookData()
        {
            if ($this->ReadPropertyString('ChartFormat') == 'svg') {
                header('Content-Type: image/svg+xml;charset=utf-8');
                header('Content-Length: ' . strlen($this->GetBuffer('ChartSVG')));
                echo $this->GetBuffer('ChartSVG');
            } else {
                header('Content-Type: image/png');
                header('Content-Length: ' . strlen(base64_decode($this->GetBuffer('ChartPNG'))));
                echo base64_decode($this->GetBuffer('ChartPNG'));
            }
        }

        private function getZeroY($min, $max, $availableSpace)
        {
            $ratio = abs($max) / (abs($min) + abs($max));
            //Positive
            if (($min >= 0) && ($max >= 0)) {
                return $availableSpace + 50;
            //Negative
            } elseif (($min <= 0) && ($max <= 0)) {
                return 50;
            } else {
                return 50 + ($availableSpace * $ratio);
            }
        }

        private function getZeroX($min, $max, $availableSpace)
        {
            $ratio = 1 - abs($max) / (abs($min) + abs($max));
            //Positive
            if (($min >= 0) && ($max >= 0)) {
                return 50;
            //Negative
            } elseif (($min <= 0) && ($max <= 0)) {
                return $availableSpace + 50;
            } else {
                return 50 + ($availableSpace * $ratio);
            }
        }

        private function sameSign($min, $max)
        {
            return ($min * $max) >= 0;
        }

        private function getDynamicMinValue($min, $max)
        {
            if (($min >= 0) && ($max >= 0)) {
                return $min;
            } elseif (($min <= 0) && ($max <= 0)) {
                return $max;
            } else {
                return 0;
            }
        }

        private function splitHexToRGB(string $hex)
        {
            $rgb = sscanf($hex, '#%02x%02x%02x');
            $this->SendDebug('HEX', $hex, 0);
            $this->SendDebug('RGB', json_encode($rgb), 0);
            $fixedRGB = [
                $rgb[0] === null ? 0 : $rgb[0],
                $rgb[1] === null ? 0 : $rgb[1],
                $rgb[2] === null ? 0 : $rgb[2]
            ];
            $this->SendDebug('FIXED_RGB', json_encode($fixedRGB), 0);
            return $fixedRGB;
        }

        private function pngPoint($image, $x, $y, $radius, $color)
        {
            imagefilledellipse($image, $x, $y, $radius, $radius, $color);
        }

        private function drawText($x, $y, $color, $size, $text, $vertical, $orientation = '')
        {
            $anchor = $vertical ? 'end' : 'middle';
            $baseline = $vertical ? 'central' : 'hanging';
            $style = $orientation == 'vertical' ? " transform=\"rotate(-90 $x $y)\"" : '';
            return "<text x=\"$x\" y=\"$y\" font-size=\"medium\" text-anchor=\"$anchor\" alignment-baseline=\"$baseline\" fill=\"$color\"$style font-family=\"Roboto\">$text</text>";
        }

        private function drawAxisTitle($x, $y, $color, $text, $vertical = false)
        {
            $anchor = 'middle';
            $baseline = $vertical ? 'hanging' : 'baseline';
            $transform = $vertical == 'vertical' ? " transform=\"rotate(-90 $x $y)\"" : '';
            return "<text x=\"$x\" y=\"$y\" font-size=\"large\" text-anchor=\"$anchor\" alignment-baseline=\"$baseline\" fill=\"$color\"$transform font-family=\"Roboto\">$text</text>";
        }

        private function drawChartTitle($x, $y, $size, $color, $text)
        {
            return "<text x=\"$x\" y=\"$y\" font-size=\"$size\" text-anchor=\"middle\" fill=\"$color\" font-family=\"Roboto\">$text</text>";
        }

        private function getAxisLabel(string $axis)
        {
            $values = json_decode($this->ReadPropertyString('AxesValues'), true);
            $variableID = $values[0][$axis];
            $variable = IPS_GetVariable($variableID);
            $profileName = $variable['VariableProfile'] ? $variable['VariableProfile'] : $variable['VariableCustomProfile'];
            $profile = IPS_GetVariableProfile($profileName);
            $suffix = $profile['Suffix'];
            return IPS_GetName($variableID) . ' in' . $suffix;
        }

        private function drawCircle($x, $y, $radius, $hexString)
        {
            $rgbColor = 'rgb(' . implode(',', $this->splitHexToRGB($hexString)) . ')';
            return "<circle cx=\"$x\" cy=\"$y\" r=\"$radius\" fill=\"$rgbColor\" />";
        }

        private function drawLine($x1, $y1, $x2, $y2, $color)
        {
            return "<line x1=\"$x1\" y1=\"$y1\" x2=\"$x2\" y2=\"$y2\" stroke=\"$color\" />";
        }

        private function drawPolygon(array $points)
        {
            $pointsString = '';
            foreach ($points as $point) {
                $pointsString .= $point[0] . ',' . $point[1] . ' ';
            }
            $pointsString = substr($pointsString, 0, strlen($pointsString) - 1);
            $string = "<polygon points=\"$pointsString\"/>";
            return $string;
        }

        //Formular from example at https://de.wikipedia.org/wiki/Lineare_Einfachregression https://wikimedia.org/api/rest_v1/media/math/render/svg/31c4eb5b4144dc6ff9364337f902c9ca65623039
        private function computeLinearRegressionParameters(array $valuesX, array $valuesY)
        {
            $averageX = array_sum($valuesX) / count($valuesX);
            $averageY = array_sum($valuesY) / count($valuesY);
            $beta1Denominator = 0;
            $beta1Divider = 0;
            for ($i = 0; $i < count($valuesX); $i++) {
                $beta1Denominator += ($valuesX[$i] - $averageX) * ($valuesY[$i] - $averageY);
                $beta1Divider += pow(($valuesX[$i] - $averageX), 2);
            }
            $beta1 = $beta1Denominator / $beta1Divider;

            $beta0 = $averageY - ($beta1 * $averageX);

            $sqr = 0;
            $sqt = 0;
            for ($i = 0; $i < count($valuesX); $i++) {
                $sqr += pow(($valuesY[$i] - $beta0 - ($beta1 * $valuesX[$i])), 2);
                $sqt += pow(($valuesY[$i] - $averageY), 2);
            }
            $measureOfDetermination = 1 - ($sqr / $sqt);
            return [$beta0, $beta1, $measureOfDetermination];
        }

        private function getValues($xVariableId, $yVariableId, $startDate, $endDate, $timestsamp = false)
        {
            if ($xVariableId != 0 && $yVariableId != 0) {
                $archiveID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];

                $rawX = AC_GetAggregatedValues($archiveID, $xVariableId, $this->ReadPropertyInteger('AggregationLevel'), $startDate, $endDate, 0);
                $xVarValues = [];
                foreach ($rawX as $dataset) {
                    if ($timestsamp) {
                        $xVarValues[] = $dataset;
                    } else {
                        $xVarValues[] = $dataset['Avg'];
                    }
                }
                $valuesX = array_reverse($xVarValues);

                $rawY = AC_GetAggregatedValues($archiveID, $yVariableId, $this->ReadPropertyInteger('AggregationLevel'), $startDate, $endDate, 0);
                $yVarValues = [];
                foreach ($rawY as $dataset) {
                    if ($timestsamp) {
                        $yVarValues[] = $dataset;
                    } else {
                        $yVarValues[] = $dataset['Avg'];
                    }
                }
                $valuesY = array_reverse($yVarValues);
                if (count($valuesX) != count($valuesY)) {
                    $this->SetStatus(200);
                    // The amount of values is not the same for both axis
                    return null;
                } elseif (count($valuesY) <= 1) {
                    $this->SetStatus(201);
                    // The count of values is zero or one which leads to an error in the linear regression
                    return null;
                }
            } else {
                //No vars selected
                $this->SetStatus(202);
                return null;
            }

            $VarValues['x'] = $valuesX;
            $VarValues['y'] = $valuesY;
            return $VarValues;
        }

        //private function drawPointCloud(int $RangeIndex)
        private function drawPointCloud($image, int $xVariableId, int $yVariableId, $startDate, $endDate, int $pointColor, $getXValue, $getYValue)
        {
            $svg = '';
            $Values = $this->getValues($xVariableId, $yVariableId, $startDate, $endDate);
            if ($Values != null) {
                $valuesX = $Values['x'];
                $valuesY = $Values['y'];

                //Filter Werte mit 0
                $keysValueNull = array_keys($valuesY, 0);

                foreach ($keysValueNull as $key) {
                    unset($valuesX[$key]);
                    $valuesX = array_values($valuesX);
                    unset($valuesY[$key]);
                    $valuesY = array_values($valuesY);
                }

                IPS_LogMessage('values Y bereinigt', print_r($valuesY, true));
                /** for ($i = 0; $i <= count($keysValueNull) - 1; $i++) {
                 *
                 * unset($valuesX[$keysValueNull[$i]]);
                 * $valuesX = array_values($valuesX);
                 *
                 * $valuesY = array_values($valuesY);
                 * unset($valuesY[$keysValueNull[$i]]);
                 * }
                 */

                //Draw point cloud
                $pointHex = '#' . str_pad(dechex($pointColor), 6, '0', STR_PAD_LEFT);
                $pointRGB = $this->splitHexToRGB($pointHex);
                $pointColor = imagecolorallocate($image, $pointRGB[0], $pointRGB[1], $pointRGB[2]);
                for ($j = 0; $j < count($valuesY); $j++) {
                    $xValue = $getXValue($valuesX[$j]);
                    $yValue = $getYValue($valuesY[$j]);
                    $this->pngPoint($image, $xValue, $yValue, self::CIRCLE_DIAMETER, $pointColor);
                    $svg .= $this->drawCircle($xValue, $yValue, self::CIRCLE_DIAMETER / 2, $pointHex);
                }
            }
            return $svg;
        }
    }
