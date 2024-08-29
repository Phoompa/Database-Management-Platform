<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Update Database Results</title>
    <link rel="stylesheet" href="updateDB_style.css">
</head>
<body>
    <h1>Update Database Results</h1>

    <?php

    require_once 'db.php';
    // Check if the form is submitted
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        //set error flag
        $error = false;

        //sanatize and validate input
        function sanitize_validate($input, $column){
            global $error;
            //remove whitespace from beginning and end of input
            $input = trim($input);
            //remove slashes from input
            $input = stripslashes($input);
            $input = filter_var($input, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            switch($column){
                case 'supplier_id':
                case 'pid':
                case 'product_id':
                case 'quantity':
                    $input = filter_var($input, FILTER_SANITIZE_NUMBER_INT);
                    if(filter_var($input, FILTER_VALIDATE_INT)===false){
                        //display error message
                        echo "<h2>Invalid input for ".$column.", not a valid int</h2>"; 
                        $error = true;
                    }
                    break;
                case 'price':
                    $input = filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT);
                    if(filter_var($input, FILTER_VALIDATE_FLOAT)===false){
                        //display error message
                        echo "<h2>Invalid input for ".$column.", not a valid float</h2>";
                        $error = true;
                        
                    }
                    $input = "$".$input;
                    break;
                case 'description':
                case 'product_name':
                case 'supplier_name':
                case 'address':
                case 'phone':
                    for ($i = 0; $i < strlen($input); $i++) {
                        if (ctype_alnum($input[$i]) === false && ctype_space($input[$i]) === false && $input != '-') {
                            //display error message
                            //if column is description, allow for punctuation
                            if($column == 'description'){
                                if($input[$i] == '.' || $input[$i] || ',' && $input[$i] || '!' && $input[$i] || '?' && $input[$i] || ':' || $input[$i] == ';' || $input[$i] == '(' || $input[$i] == ')' || $input[$i] == '"' || $input[$i] == "'"){
                                    continue;
                                }
                            }
                            
                            echo "<h2>Invalid input for ".$column.", not a valid string</h2>";
                            $error = true;
                            break;
    
                            
                        }
                    }
                    break;
                case 'email':
                    $input = filter_var($input, FILTER_SANITIZE_EMAIL);
                    if(filter_var($input, FILTER_VALIDATE_EMAIL)===false){
                        //display error message
                        echo "<h2>Invalid input for email, not a valid email address</h2>";
                        $error = true;
                        
                    }
                    break;

                
            }
        
            return $input;
        }
        // Retrieve the submitted table value
        $table = $_POST['table'];

        // Retrieve the submitted column values
        $columns = isset($_POST['columns']) ? $_POST['columns'] : [];
        $values = isset($_POST['values']) ? array_map('sanitize_validate',$_POST['values'],$columns) : [];

        // Retrieve the submitted WHERE clause column values
        $whereColumns = isset($_POST['where_columns']) ? $_POST['where_columns'] : [];
        $whereValues = isset($_POST['where_values']) ? array_map('sanitize_validate', $_POST['where_values'],$whereColumns) : [];

        //check error flag
        if($error){
            echo "<a href='update.html'>Go Back</a>";
            exit();
        }
        elseif($columns == [] || $values == []){
            echo "<h2>Invalid input for columns or values, please enter at least one column and value</h2>";
            echo "<a href='update.html'>Go Back</a>";
            exit();
        }
        //create sql string

        $sql = "UPDATE $table SET ";
        for ($i = 0; $i < count($columns); $i++) {
            $sql .= "$columns[$i] = ?";
            if ($i < count($columns) - 1) {
                $sql .= ", ";
            }
        }
        if (count($whereColumns) > 0){
            $sql .= " WHERE ";
            for ($i = 0; $i < count($whereColumns); $i++) {
                $sql .= "$whereColumns[$i] = ?";
                if ($i < count($whereColumns) - 1) {
                    $sql .= " AND ";
                }else{
                    $sql .= ";";
                }
            }
        }

        //prepare and execute sql statement
        $stmt = $conn->prepare($sql);

        for ($i = 0; $i < count($values); $i++) {
            $stmt->bindValue($i + 1, $values[$i]);
        }

        for ($i = 0; $i < count($whereValues); $i++) {
            $stmt->bindValue(count($values) + $i + 1, $whereValues[$i]);
        }

        $stmt->execute();
        

        // Fetch the updated table
        $selectSql = "SELECT * FROM $table";
        $selectStmt = $conn->prepare($selectSql);
        $selectStmt->execute();
        $updatedTable = $selectStmt->fetchAll(PDO::FETCH_ASSOC);

        // Print the updated table
        echo "<h2>Updated Table:</h2>";
        echo "<table>";
        echo "<tr>";
        foreach ($updatedTable[0] as $column => $value) {
            echo "<th>$column</th>";
        }
        echo "</tr>";
        foreach ($updatedTable as $row) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>$value</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }
    ?>
    <br>
    <a href="update.html">Go Back</a>
</body>
</html>
