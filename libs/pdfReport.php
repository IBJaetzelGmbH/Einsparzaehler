<?php

declare(strict_types=1);

include_once __DIR__ . '/../libs/vendor/autoload.php';

trait pdfReport
{
    protected function GeneratePDFReport(array $Values, string $Chart)
    {
        $pdfContent = $this->GeneratePDF(
            'IP-Symcon ' . IPS_GetKernelVersion(),
            'Report',
            'Report',
            $this->GenerateHTML($Values, $Chart),
            IPS_GetKernelDir() . 'media/Report' . $this->InstanceID . '.pdf'
        );

        return $pdfContent;
    }
    private function GenerateHTMLHeader()
    {
        $logoData = $this->ReadPropertyString('Logo');
        $logoBafaData = base64_encode(file_get_contents('/../../imgs/BAFALogo.png'));
        $firmenName = 'Ingenieurbüro Jaetzel GmbH';
        $date = date('d.m.Y');

        return <<<EOT
			
<table cellpadding="5" cellspacing="0" border="0" width="95%">
<tr>
	<td width="50%">
        <img src="@$logoData">
    </td>
	<td align="right">
        <img src="@$logoBafaData">
		<br/>
		$date
	</td>
</tr>
</table>
EOT;
    }

    private function generateTable($Values)
    {
        $rows = '';
        $headCols = '';

        $Verbrauch = false;
        $Zaehlerstand = false;
        /**
         * $headCols .= '<td colspan="3" style="background-color: #ffff00; text-align: left;"><b>Baseline(X/Y)</b></td>';
         * $headCols .= '<td colspan="3" style="background-color: #00ff00; text-align: left;"><b>Bewerteter Zeitraum</b></td>';
         * $headCols .= '<td style="background-color: #E1C699; text-align: left;"><b>Berechnet aus Baseline</b></td>';
         * $headCols .= '<td style="background-color: #FF8800; text-align: left;"><b>Einsparung</b></td>';
         */
        $headCols .= '<td style="text-align: left;"><b>Datum Messwert</b></td>';
        $headCols .= '<td style="text-align: left;"><b>Temperatur</b></td>';
        $headCols .= '<td style="text-align: left;"><b>Verbrauch erwartet aus Baseline</b></td>';
        $headCols .= '<td style="text-align: left;"><b>tatsächlicher Verbrauch</b></td>';
        $headCols .= '<td style="text-align: left;"><b>Einsparung od. Mehrverbrauch</b></td>';

        foreach ($Values as $value) {
            $title = $this->Translate('Report Energy-saving meter');
            $rows .= '<tr>';
            $rows .= '<td style="text-align: left;">' . $value['timestampX'] . '</td>';
            $rows .= '<td style="text-align: left;">' . number_format($value['Temperatur'], 2, ',', '') . '</td>';
            $rows .= '<td style="text-align: left;">' . number_format($value['BerchnetAusBaseline'], 2, ',', '') . '</td>';
            $rows .= '<td style="text-align: left;">' . number_format($value['Verbrauch'], 2, ',', '') . '</td>';
            $rows .= '<td style="text-align: left;">' . number_format($value['Einsparung'], 2, ',', '') . '</td>';
            $rows .= '</tr>';
        }

        return <<<EOT
        <h2>$title</h2>
        <br/>
        <br/>
        <br/>
        <table cellpadding="5" cellspacing="0" border="0" width="95%">
	<tr style="padding:5px;">
	   $headCols
	</tr>
	$rows
	<tr>
		<td colspan="5"><hr/></td>
	</tr>
</table>
EOT;
    }

    private function GenerateHTML(array $Values, $Chart)
    {
        $header = $this->GenerateHTMLHeader();
        $table = $this->generateTable($Values);
        $footer = $this->Translate('');

        return <<<EOT
$header
<br/>
$table
<br/>
<img src="@$Chart">
<br/>
$footer
EOT;
    }

    private function GeneratePDF($author, $title, $subject, $html, $filename)
    {
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor($author);
        $pdf->SetTitle($title);
        $pdf->SetSubject($subject);

        $pdf->setPrintHeader(false);

        //$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont([PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA]);
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP - 15, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

        $pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);

        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        $pdf->SetFont('dejavusans', '', 10);

        $pdf->AddPage();

        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output($filename, 'F');
        return $filename;
    }
}