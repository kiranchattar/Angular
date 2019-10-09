<?php
error_reporting(0);
require 'db.php';
header('Access-Control-Allow-Origin: *');
require_once('TCPDF/tcpdf.php');
date_default_timezone_set("Asia/Kolkata");

if($_GET["type"]=="getvoucher") {
	$sql = "SELECT * FROM voucher ORDER BY id DESC";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
} else if($_GET["type"]=="addvoucher") {
    $input = json_decode(file_get_contents('php://input'),true);
    $sql = "SELECT IFNULL(MAX(i_no1), 0) as  i_no1 FROM voucher";
    $i_no1 = 0;
    $i_no = "";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $i_no1 = $row["i_no1"];
            break;
        }
    }
    $i_no1++;
	if (count($i_no1) == 1) {
	    $i_no = "0000".$i_no1;
	} else if (count($i_no1) == 2) {
	    $i_no = "000".$i_no1;
	} else if (count($i_no1) == 3) {
	    $i_no = "00".$i_no1;
	} else if (count($i_no1) == 4) {
	    $i_no = "0".$i_no1;
	} else if (count($i_no1) >= 5) {
	    $i_no = "-".$i_no1;
    } 
    $sql = "INSERT INTO voucher (voucher_no,pay_to,date,total_amount,type,type_detail,i_no1) VALUES ('".$i_no."','".$input["pay_to"]."','".$input["date"]."','".$input["total_amount"]."','".$input["type"]."','".$input["type_detail"]."','$i_no1')"; 
    if($conn->query($sql)===TRUE){
         echo "{\"status\":\"success\"}";
        
        $data = $input["products"];
        for ($i = 0; $i < count($input["products"]); $i++) {
            $temp = $data[$i];
            $sql = "INSERT INTO voucher_detail (voucher_no, particular, amount) VALUES ('$i_no','".$temp["particulars"]."','".$temp["amount"]."')";
            $conn->query($sql);
        }
    }
    
    else {
        echo "{\"status\":\"An error has occurred, Please try again.\"}";
    }
} else if($_GET["type"]=="deletevoucher"){
	$input = json_decode(file_get_contents('php://input'),true);
	$sql = "DELETE FROM voucher  where voucher_no = '".$_GET["voucher_no"]."' ";
	if($conn->query($sql)===TRUE){
	    $sql1 = "DELETE FROM voucher_detail  where voucher_no = '".$_GET["voucher_no"]."' ";
        $result = $conn->query($sql1);
		echo "{\"status\":\"success\"}";
	}
	
	else{
		echo "{\"status\":\"".$conn->error."\"}";
	}

} else if ($_GET["type"]=="printvoucher"){
    $sql = "SELECT * FROM voucher WHERE voucher_no='".$_GET["voucher_no"]."'";
    $result = $conn->query($sql);
    if($result->num_rows > 0){
    while($row = $result->fetch_assoc()){
        $voucher_no =$row["voucher_no"];
        $originalDate =$row["date"];
        $pay_to =$row["pay_to"];
        $total_amount =$row["total_amount"];
        $type =$row["type"];
        $type_detail =$row["type_detail"];
    }
    };
$originalDate;
$newDate = date("d-m-Y", strtotime($originalDate));
class MYPDF extends TCPDF {
    public function Header() {
        $image_file = 'cyclone.png';
        $this->Image($image_file, 15, 13, 45, '15', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
        $this->SetFont('helvetica', '', 20);
        $this->SetY(18);
        $this->Cell(0, 15, 'Cash Voucher ', 0, false, 'C', 0, '', 0, false, 'M', 'M');
        $tDate = date("F j, Y, g:i a");
        $this->SetY(29);
        $this->Cell(($this->w - $this->original_lMargin - $this->original_rMargin), 100, '', 'T', 100, 'L');
    }
}
$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Nicola Asuni');
$pdf->SetTitle('CASH VOUCHER');
$pdf->SetSubject('TCPDF Tutorial');
$pdf->SetKeywords('TCPDF, PDF, example, test, guide');
$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setPrintFooter(false);
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
    require_once(dirname(__FILE__).'/lang/eng.php');
    $pdf->setLanguageArray($l);
}

$pdf->AddPage();
$pdf->SetY(30);
$pdf->SetFont ('helvetica', '', '10' , '', 'default', true );
$html1 = '

<table cellpadding="5" style="border: 1px solid #b0e0e6 ; text-align:left; width:100%;">
  <tr>
    <td style="width:15%;"><b>Voucher No :</b></td>
    <td style="width:65%;"><b><label>'.$voucher_no.'</label></b></td>
    <td style="width:20%;"><b>Date : </b><label>'.$newDate.'</label></td>
  </tr>
  <tr>
    <td><b>Pay To : </b></td>
    <td colspan="2"><b><label>'.$pay_to.'</label></b></td>
  </tr>
</table>
<div></div>
<table cellpadding="5" style="border: 1px solid #b0e0e6 ; text-align:left; ">
    <tr style="background-color:#b0e0e6;">
        <td  border="1" style="width:10%; text-align:center;"><b>Sr No</b></td>
        <td  border="1" style="width:70%; text-align:center;"><b>Particulars</b></td>
        <td border="1" style="width:20%; text-align:center;"><b>Amount</b></td>
    </tr>';
    $sql = "SELECT * FROM voucher_detail WHERE voucher_no='".$_GET["voucher_no"]."'";
    $result = $conn->query($sql);
    $counter = 0;
    if($result->num_rows > 0){
    while($row = $result->fetch_assoc()){
        $particular =$row["particular"];
        $amount =$row["amount"];
        $paise =$row["paise"];
    $html1.='
    <tr>
        <td border="1"><label>'.++$counter.'</label></td>
        <td border="1"><label>'.$particular.'</label></td>
        <td border="1"><label>'.number_format((float) $amount, 2, '.', '').' &#47; &#45;</label></td>
    </tr>';
     }
    }
        $html1.='
        <tr>
            <td border="1" colspan="2" style="text-align:right;"><label><b>Total Amount</b></label></td>
            <td border="1"><label><b>'.number_format((float) $total_amount, 2, '.', '').' &#47; &#45;</b></label></td>
        </tr>';
          function numberTowords($amount)
        {
        
        $ones = array(
        0 =>"ZERO",
        1 => "ONE",
        2 => "TWO",
        3 => "THREE",
        4 => "Four",
        5 => "Five",
        6 => "SIX",
        7 => "SEVEN",
        8 => "EIGHT",
        9 => "NINE",
        10 => "TEN",
        11 => "ELEVEN",
        12 => "TWELVE",
        13 => "THIRTEEN",
        14 => "FOURTEEN",
        15 => "FIFTEEN",
        16 => "SIXTEEN",
        17 => "SEVENTEEN",
        18 => "EIGHTEEN",
        19 => "NINETEEN",
        "014" => "FOURTEEN"
        );
        $tens = array( 
        0 => "ZERO",
        1 => "TEN",
        2 => "TWENTY",
        3 => "THIRTY", 
        4 => "FORTY", 
        5 => "FIFTY", 
        6 => "SIXTY", 
        7 => "SEVENTY", 
        8 => "EIGHTY", 
        9 => "NINETY" 
        ); 
        $hundreds = array( 
        "HUNDRED", 
        "Thousand", 
        "MILLION", 
        "BILLION", 
        "TRILLION", 
        "QUARDRILLION" 
        ); /*limit t quadrillion */
        $amount = number_format($amount,2,".",","); 
        $num_arr = explode(".",$amount); 
        $wholenum = $num_arr[0]; 
        $decnum = $num_arr[1]; 
        $whole_arr = array_reverse(explode(",",$wholenum)); 
        krsort($whole_arr,1); 
        $rettxt = ""; 
        foreach($whole_arr as $key => $i){
        	
        while(substr($i,0,1)=="0")
        		$i=substr($i,1,5);
        if($i < 20){ 
        /* echo "getting:".$i; */
        $rettxt .= $ones[$i]; 
        }elseif($i < 100){ 
        if(substr($i,0,1)!="0")  $rettxt .= $tens[substr($i,0,1)]; 
        if(substr($i,1,1)!="0") $rettxt .= " ".$ones[substr($i,1,1)]; 
        }else{ 
        if(substr($i,0,1)!="0") $rettxt .= $ones[substr($i,0,1)]." ".$hundreds[0]; 
        if(substr($i,1,1)!="0")$rettxt .= " ".$tens[substr($i,1,1)]; 
        if(substr($i,2,1)!="0")$rettxt .= " ".$ones[substr($i,2,1)]; 
        } 
        if($key > 0){ 
        $rettxt .= " ".$hundreds[$key]." "; 
        }
        } 
        if($decnum > 0){
        $rettxt .= " and ";
        if($decnum < 20){
        $rettxt .= $ones[$decnum];
        }elseif($decnum < 100){
        $rettxt .= $tens[substr($decnum,0,1)];
        $rettxt .= " ".$ones[substr($decnum,1,1)];
        }
        }
        return $rettxt;
        }
        extract($_POST);
        if(isset($convert))
        {
        
        }
        $html1.='
        <tr>
            <td border="1">In Words</td>
            <td border="1"colspan="3">'.numberTowords("$total_amount").' Only</td>
        </tr>
    </table> 
';
$html1.='

<table cellpadding="5" style="border: 1px solid #000000 ; text-align:left; width:100%;">
<tr>
<td></td></tr>
    <tr>
        <td style="width:4%;"></td>
        <td style="width:10%;">'.$type.' :-</td>
        <td style=" width:86%;">'.$type_detail.'</td>
    </tr>
    <br>
    <tr>
        <td></td>
        <td style="width: 25%;">Received Above Sum of Rs.</td>
        <td style="border: 1px solid #000000; width: 20%;">'.number_format((float) $total_amount, 2, '.', '').' &#47; &#45;</td>
        <td style="width: 25%; text-align:right;">Sign</td>
        <td style="border: 1px solid #000000; width: 20%;"></td>
    </tr>
    <tr>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
</table>
';

EOD;
$pdf->writeHTML($html1, true, false, false, false, '');
$pdf->Output('Cash Voucher.pdf', 'I');
}

?>