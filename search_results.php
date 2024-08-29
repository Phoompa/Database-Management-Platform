<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Search Results</title>
    <link rel="stylesheet" href="search_style.css">
</head>
<body>

<?php
require_once('db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

     //sanatize and validate input
     function sanitize_validate($input, $field){
        $valid = true;
        //remove whitespace from beginning and end of input
        $input = trim($input);
        //remove slashes from input
        $input = stripslashes($input);
        // $input = filter_var($input, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        switch($field){
            case 'supplier_id':
            case 'pid':
            case 'product_id':
            case 'quantity':
                $input = filter_var($input, FILTER_SANITIZE_NUMBER_INT);
                if(filter_var($input, FILTER_VALIDATE_INT)===false){
                    //display error message
                    echo "<p style='color:red'>Invalid input for <strong>".$field."</strong>, not a valid int</p>"; 
                    $valid = false;
                }
                break;
            case 'price':
                if (substr($input,0,1) != "$"){
                    $input = "$" . $input;
                }
                if(filter_var(substr($input,1), FILTER_VALIDATE_FLOAT)===false){
                    //display error message
                    echo "<p style='color:red'>Invalid input for <strong>".$field."</strong>, not a valid float</p>";
                    $valid = false;
                }
                break;
            case 'description':
            case 'product_name':
            case 'supplier_name':
            case 'phone':
                for ($i = 0; $i < strlen($input); $i++) {
                    if (ctype_alpha($input[$i]) === false && ctype_space($input[$i]) === false && $input != '-') {
                        //display error message
                        echo "<p style='color:red'>Invalid input for <strong>".$field."</strong>, not a valid string</p>";
                        $valid = false;
                    }
                }
                break;
            case 'email':
                $input = filter_var($input, FILTER_SANITIZE_EMAIL);
                if(filter_var($input, FILTER_VALIDATE_EMAIL)===false){
                    //display error message
                    echo "<p style='color:red'>Invalid input for <strong>email</strong>, not a valid email address</p>"; 
                    $valid = false;      
                }
                break;
        }
        if ($valid) {
            echo "<p>$field = <strong>$input</strong></p>";
        }
        return $input;
    }

    // Retrieve the submitted table value
    $table = $_POST['table'];

    // var initialization
    $fields = array();
    $values = array();
    $init_count = 0;

    // query statement initialization
    $stmt_prep = "SELECT * FROM $table";
    $stmt_vals = array();
    
    // retrieves variable information from query page
    while(true) {
        $field_name = 'field-' . $init_count;
        $field_val = 'input-' . $init_count;
        if (isset($_POST[$field_name]) && isset($_POST[$field_val])) {
            array_push($fields, $_POST[$field_name]);
            array_push($values, htmlspecialchars($_POST[$field_val]));          
        }else {
            break;
        }
        $init_count += 1;
    }

    // Display search paramters names // generate sql prep statement
    echo "<h2>Query Information</h2>";
    echo "<p><strong>Selected Table: </strong>$table</p>";
    echo "<p><strong>Search Parameters:</strong></p>";
    
    // if no query fields, print whole table
    // otherwise, start forming sql query
    if ($init_count > 0){
        $stmt_prep .= " WHERE ";
        for ($i = 0; $i < count($fields); $i++) {
            $field = $fields[$i];
            $value = $values[$i];

            $val_list = explode(",", $value);
            $val_len = count($val_list);
            $sub_stmt = "(";

            if ($val_len > 1) {
                for ($j = 0; $j < $val_len; $j++) {
                    $updt_val = sanitize_validate($val_list[$j], $field);
                    array_push($stmt_vals, $updt_val);
                    $sub_stmt .= ($j != $val_len - 1) ? "$field = ? OR " : "$field = ?)";
                }
                $stmt_prep .= ($i != count($fields) - 1) ?  "$sub_stmt AND ": "$sub_stmt";
            }else {
                $updt_val = sanitize_validate($value, $field);
                array_push($stmt_vals, $updt_val);
                $stmt_prep .= ($i != count($fields) - 1) ? "$field = ? AND ": "$field = ?";
            }
        }
    }
    $stmt_len=count($stmt_vals);
    echo "<p><strong>Statement:</strong> $stmt_prep, <strong>indexes:</strong> $stmt_len</p>";

    // preparing statement
    $stmt = $conn->prepare($stmt_prep);
    for ($i = 0; $i < $stmt_len; $i++) {
        $binding_val = $stmt_vals[$i];
        $stmt->bindValue($i + 1, $binding_val);
    }

    $stmt->execute();
    $resultsTable = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // outputs information to table
    if (count($resultsTable) > 0) {
        echo "<table border=1>";
        echo "<tr>";
        foreach ($resultsTable[0] as $col => $val) {
            echo "<th>$col</th>";
        }
        echo "</tr>";
        foreach ($resultsTable as $row) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>$value</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }else {
        echo "<p style='color:red'> -- No Matches -- </p>";
    }
}
?>

<a href="search.html"> <button id="back-btn">back</button> </a>
</body>
</html>