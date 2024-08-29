<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Record Deleted Database Results</title>
    <link rel="stylesheet" href="delete_style.css">
</head>
<body>
    <h1>Database After Record Deletion</h1>

    <?php
    
        // Function to sanitize and validate input
        function sanitize_validate($input, $column){
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
                    }
                    break;
                case 'price':
                    $input = filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                    if(filter_var($input, FILTER_VALIDATE_FLOAT)===false){
                        //display error message
                        echo "<h2>Invalid input for ".$column.", not a valid float</h2>";
                    }
                    break;
                case 'description':
                case 'product_name':
                case 'supplier_name':
                    // Allow letters, spaces, and hyphens in supplier_name and product_name
                    if (!preg_match('/^[A-Za-z\s-]+$/', $input)) {
                        //display error message
                        echo "<h2>Invalid input for ".$column.", not a valid string</h2>";
                    }
                    break;
                case 'address':
                    // Allow letters, numbers, spaces, and hyphens in the address
                    if (!preg_match('/^[A-Za-z0-9\s-]+$/', $input)) {
                        //display error message
                        echo "<h2>Invalid input for ".$column.", not a valid address</h2>";
                    }
                    break;
                case 'phone':
                    // Allow numbers and hyphens in the phone number
                    if (!preg_match('/^[\d-]+$/', $input)) {
                        //display error message
                        echo "<h2>Invalid input for ".$column.", not a valid phone number</h2>";
                    }
                    break;
                case 'email':
                    $input = filter_var($input, FILTER_SANITIZE_EMAIL);
                    if(filter_var($input, FILTER_VALIDATE_EMAIL)===false){
                        //display error message
                        echo "<h2>Invalid input for email, not a valid email address</h2>";
                    }
                    break;
            }
            
            return $input;
        }
        
        

        // Check if the form is submitted
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Check if the table and search fields are submitted
            if (isset($_POST['table']) && isset($_POST['columns']) && isset($_POST['values'])) {
                require_once 'db.php';

                $table = $_POST['table'];
                $columns = $_POST['columns'];
                $values = $_POST['values'];

                // Sanitize and validate the input values
                $values = array_map('sanitize_validate', $values, $columns);



                // Perform deletion based on user-entered criteria
                $deleteQuery = "DELETE FROM $table WHERE ";
                $placeholders = array();
                foreach ($columns as $column) {
                    //Making sure that comparison is case sensitive for columns with strings
                    if ($column == 'supplier_name' || $column == 'address' || $column == 'phone' || $column == 'email'){
                        $placeholders[] = "$column COLLATE utf8mb4_bin = ?";
                    } else{
                        $placeholders[] = "$column = ?";
                    }
                }
                $deleteQuery .= implode(" AND ", $placeholders);

                try {
                    // Prepare the DELETE statement
                    $stmt = $conn->prepare($deleteQuery);

                    // Bind the values to the prepared statement
                    foreach ($values as $index => $value) {
                        

                        $stmt->bindValue($index + 1, $value);
                    }

                    // Execute the DELETE statement
                    // Execute the DELETE statement
                    $stmt->execute();

                    // Check the number of affected rows
                    $affectedRows = $stmt->rowCount();

                    if ($affectedRows > 0) {
                        // Output success message
                        echo "Record(s) deleted successfully!";
                    } else {
                        // Output error message if no rows were deleted
                        echo "Record not found or could not be deleted.";
                    }
                } catch (PDOException $e) {
                    echo "Error: " . $e->getMessage();
                }

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


            } else {
                echo "Please select a table and enter criteria for deletion.";
            }
        }
?>

    <br>
    <a href="delete.html">Go Back</a>
</body>
</html>
