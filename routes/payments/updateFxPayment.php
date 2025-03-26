<?php
include_once('authMiddleware.php');


if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    
    $data = json_decode(file_get_contents("php://input"), true);


    $id = $data['id'] ?? null;
    // Validate input
    $beneficiary_name = $data['beneficiary_name'] ?? null;
    $currency_table = $data['currency_table'] ?? null;
    $reference = $data['reference'] ?? null;
    $payment_purpose = $data['payment_purpose'] ?? null;
    $amount_figure = $data['amount_figure'] ?? null;
    $payment_bank = $data['payment_bank'] ?? null;
    $bank_name = $data['bank_name'] ?? null;
    $currency = $data['currency'] ?? null;
    $amount_figure = $data['amount_figure'] ?? null;
    $payment_date = date('Y-m-d h:i:s', time()-3600);
    $account = $data['account'] ?? null;
    $beneficiary_address = $data['beneficiary_address'] ?? null;
    $beneficiary_bank = $data['beneficiary_bank'] ?? null;
    $beneficiary_bank_address = $data['beneficiary_bank_address'] ?? null;
    $swift_code = $data['swift_code'] ?? null;
    $beneficiary_account_number = $data['beneficiary_account_number'] ?? null;
    $bank_code = $data['bank_code'] ?? null;
    $sort_code = $data['sort_code'] ?? null;
    $account = $data['account'] ?? null;
    $intermediary_bank = $data['intermediary_bank'] ?? null;
    $intermediary_bank_swift_code = $data['intermediary_bank_swift_code'] ?? null;
    $intermediary_bank_iban = $data['intermediary_bank_iban'] ?? null;
    $domiciliation = $data['domiciliation'] ?? null;
    $code_guichet = $data['code_guichet'] ?? null;
    $compte_no = $data['compte_no'] ?? null;
    $cle_rib = $data['cle_rib'] ?? null;
    $payment_account_number = $data['payment_account_number'] ?? null;
    $curren = $currency_table;
    $year = $data['year'] ?? null;
    $curr = "";
    $points = "";


    if (!$id) {
        http_response_code(400); // Bad Request
        echo json_encode(["message" => "ID is required for update!"]);
        exit;
    }

    if (!$beneficiary_name || !$currency_table || !$reference || !$amount_figure) {
        http_response_code(400); // Bad Request
        echo json_encode(["message" => "Please fill in all required fields!"]);
        exit;
    }

    // Check if the fxRecord exists
    $sqlId = "SELECT * FROM fx_instruction_letter_table WHERE id = ?";
    $stmtId = mysqli_prepare($conn, $sqlId);
    mysqli_stmt_bind_param($stmtId, 's', $id);
    mysqli_stmt_execute($stmtId);
    $idResult = mysqli_stmt_get_result($stmtId);

   
   if (mysqli_num_rows($idResult) == 0) {
       http_response_code(404); // Not found
       echo json_encode(["message" => "The payment you're trying to update doesn't exist!"]);
       exit;
   } 


    // Check if the reference exists in another record
    $sql = "SELECT id FROM fx_instruction_letter_table WHERE reference = ? AND id != ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'si', $reference, $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        echo json_encode(["message" => "Oops, this payment already exists!"]);
        http_response_code(409); // Conflict
        exit;
    }

    // Proceed with the update if no conflict found



   function numtowords($number){
            
            
    if (($number < 0) || ($number > 999999999999)) 
        { 
            return "$number"; 
        } 
    
        $Bn = floor($number / 1000000000);  /* Billions */ 
        $number -= $Bn * 1000000000;	
        $Mn = floor($number / 1000000);  /* Millions */ 
        $number -= $Mn * 1000000; 
        $kn = floor($number / 1000);     /* Thousands */ 
        $number -= $kn * 1000; 
        $Hn = floor($number / 100);      /* Hundreds */ 
        $number -= $Hn * 100; 
        $Dn = floor($number / 10);       /* Tens */ 
        $n = $number % 10;               /* Ones */ 
    
        $res = ""; 
    
        if ($Bn) 
        { 
            $res .= numtowords($Bn) . " Billion "; 
        }	
        if ($Mn) 
        { 
            $res .= numtowords($Mn) . " Million "; 
        } 
    
        if ($kn) 
        { 
            $res .= (empty($res) ? "" : " ") . 
                numtowords($kn) . " Thousand"; 
        } 
    
        if ($Hn) 
        { 
            $res .= (empty($res) ? "" : " ") . 
                numtowords($Hn) . " Hundred"; 
        } 
    
        $ones = array("", "One", "Two", "Three", "Four", "Five", "Six", 
            "Seven", "Eight", "Nine", "Ten", "Eleven", "Twelve", "Thirteen", 
            "Fourteen", "Fifteen", "Sixteen", "Seventeen", "Eighteen", 
            "Nineteen"); 
        $tens = array("", "", "Twenty", "Thirty", "Forty", "Fifty", "Sixty", 
            "Seventy", "Eighty", "Ninety"); 
    
        if ($Dn || $n) 
        { 
            if (!empty($res)) 
            { 
                $res .= " "; 
            } 
    
            if ($Dn < 2) 
            { 
                $res .= $ones[$Dn * 10 + $n]; 
            } 
            else 
            { 
                $res .= $tens[$Dn]; 
    
                if ($n) 
                { 
                    $res .= " " . $ones[$n]; 
                } 
            } 
        } 
    
        if (empty($res)) 
        { 
            $res = "Zero"; 
        } 
    
        return $res;
        
    }




    if (!$reference) {
    http_response_code(401); // Bad Request
    echo json_encode(["message" => "Enter a Payment Enterprise!"]);
    exit; // Exit after sending response
}


if($curren == "USD"){
    $curr = "USD";
}

if($curren == "EUR"){
    $curr = "Euros";
}

if($curren == "GBP"){
    $curr = "Pounds";
}

if($curren == "ZAR"){
    $curr = "Rands";
}

if($curren == "AED"){
    $curr = "UAE Dirhams";
}


if($curren == "USD"){
    $points = "Cents";
}

if($curren == "EUR"){
    $points = "Cents";
}

if($curren == "GBP"){
    $points = "Cents";
}

if($curren == "ZAR"){
    $points = "Cents";
}

if($curren == "AED"){
    $points = "Fils";
}


$TotNet = $amount_figure;
$table = "";

$USDollar = number_format($TotNet, 2,'.',','); // put it in decimal format, rounded 

$printTotNet = numtowords($TotNet);  //convert to words (see function above) 
$table .= ''; 
$x = $USDollar; 
$explode = explode('.', $x);   //separate the Kobo

$printDolKobo = $printTotNet . ' ' . $curr . ' & ' . numtowords($explode[1]) . ' ' . $points;
$table .= $printDolKobo;  // print the line with Naira words and Kobo in words


$amount_words = $table . " Only";

// Proceed with updating the entry
$stmtUpdate = mysqli_prepare($conn, "UPDATE fx_instruction_letter_table 
SET beneficiary_name = ?, beneficiary_address = ?, beneficiary_bank = ?, 
    beneficiary_bank_address = ?, swift_code = ?, beneficiary_account_number = ?, 
    reference = ?, payment_purpose = ?, amount_figure = ?, amount_words = ?, payment_account_number = ?, 
    payment_bank = ?, currency = ?, currency_table = ?, payment_date = ?, 
    bank_code = ?, sort_code = ?, account = ?, intermediary_bank = ?, 
    intermediary_bank_swift_code = ?, intermediary_bank_iban = ?, domiciliation = ?, 
    code_guichet = ?, compte_no = ?, cle_rib = ?
WHERE id = ?");

mysqli_stmt_bind_param(
    $stmtUpdate, 
    'sssssssssssssssssssssssssi',  // Adjusting the last type to 'i' for the id
    $beneficiary_name, $beneficiary_address, $beneficiary_bank, $beneficiary_bank_address, 
    $swift_code, $beneficiary_account_number, $reference, $payment_purpose, $amount_figure, 
    $amount_words, $payment_account_number, $payment_bank, $currency, $currency_table, $payment_date, 
    $bank_code, $sort_code, $account, $intermediary_bank, $intermediary_bank_swift_code, $intermediary_bank_iban, 
    $domiciliation, $code_guichet, $compte_no, $cle_rib, $id  // This should be the last parameter
);
    
    

   
if (mysqli_stmt_execute($stmtUpdate)) {
    http_response_code(200); // OK
    $data['amount_words'] = $amount_words;

    echo json_encode([
        "message" => "Your payment has been updated successfully!",
        "data" => $data
    ]);
} else {
    http_response_code(500); // Internal Server Error
    echo json_encode(["message" => "Error updating the record."]);
}

exit;

}
elseif($_SERVER['REQUEST_METHOD'] !== 'PUT'){
    http_response_code(405); // Bad Request
    echo json_encode(["message" => "Method Not Allowed!"]);
    exit; // Exit after sending response
}

// Close connection
// mysqli_close($conn);
?>
