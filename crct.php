<?php
// Customer Retention Counting Tool
//
//  Install Steps:
//      Install MAMP
//      Put crct folder in htdocs
//      Initialize DB (set root login and passwords, 
//                      create initial DB '<business>'
//                      
//                      )
//      Install PHPMailer (in htdocs)
//      Set up gmail acct for client
//      Set gmail to allow insecure apps
//      Enter smtp info for client
//      Clear caches if it doesn't work
//      
//      (Non)Profit!
//
//      Install python with 'requests' module on RaspPi
//
// Thanks to https://www.binpress.com/tutorial/using-php-with-mysql-the-right-way/17
//  and https://bueltge.de/einfaches-php-debugging-in-browser-console/

require 'C:\MAMP\PHPMailer-master\PHPMailerAutoload.php';
// Main()
    echo "\nCRCT Loaded.\n";
    $myDB = db_connect();
    $datafile = uploadFile();
//   test_db_connection($myDB);
    testCreateDB($myDB);
    feedDB($myDB, $datafile);
//  readDB();
    mailDB();

// end Main()
// Connect to DB
    function db_connect() {
        // Define connection as a static variable, to avoid connecting more than once 
        static $db_connection;
        
        // Try and connect to the database, if a connection has not been established yet
        if(!isset($db_connection)) {
            $config = parse_ini_file('crct_config.ini');
            
            $db_connection = mysqli_connect($config['host'],
                                            $config['user'],
                                            $config['pwd'],
                                            $config['db_name']
                                           );
        }

        // If connection was not successful, handle the error
        if($db_connection === false) {
            // Handle error - notify administrator, log to a file, show an error screen, etc.
            return mysqli_connect_error(); 
        } else {
            echo "DB Connected.\n";
        }
        return $db_connection;
    }

// Upload a file; return filename
    function uploadFile(){
        $dfilename = 'data_'.date('U');
        if (move_uploaded_file($_FILES['datafile']['tmp_name'], $dfilename)) {
            echo "File ".$dfilename ." is valid, and was successfully uploaded.\n";
        } else {
            echo "Invalid file: ".var_dump($_FILES)."\n";
        }
        return $dfilename;
    }

// Ensure DB is connected; Check that table exists; Create table if it doesn't
    function testCreateDB($conn){
        $val = mysqli_query($conn, 'select 1 from `myguests` LIMIT 1');

        if($val === FALSE)
        {
            echo ("Creating Table.\n");
            // sql to create table
            $sql = "CREATE TABLE myguests (
            id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            guest_id VARCHAR(20) NOT NULL,
            curr_date DATETIME NOT NULL,
            time_in VARCHAR(30) NOT NULL,
            time_out VARCHAR(30) NOT NULL
            )";

            if ($conn->query($sql) === TRUE) {
                echo "Table ".$conn->host_info." created successfully.\n";
            } else {
                echo "Error creating table: " . $conn->error."\n";
            }
        } else {
            echo ("Found DB: ".$conn->host_info."\n");
        }
        echo "DB connection tested.\n";
    }

// Shove info from file into DB
    function feedDB($conn, $dfile){
        $fh = fopen($dfile,'r') or die ("Cannot open " . $dfile);
        while ($line = fgets($fh)) {
            $entry = explode(',', $line);
            mysqli_query($conn, "INSERT INTO myguests (`guest_id`, `curr_date`, `time_in`, `time_out`) 
                                VALUES ('$entry[0]',now(),'$entry[2]','$entry[3]')"
                    ) or die (mysqli_error($conn));
        }
        fclose($fh);
        echo "Data from file written to DB.\n";
    }

// Send an email containing the data for the current day
    function mailDB(){
        $mail_string = '';
        $return_val = db_select('SELECT * FROM `myguests` WHERE DATE(`curr_date`) = CURDATE()');
        foreach ($return_val as $rv0){
            $rv1 = implode(',',$rv0);
            $mail_string .= $rv1;
        }

        $attachment_file = fopen("attachment.txt", "w") or die("Unable to open file for writing!");
        fwrite($attachment_file, $mail_string);
        fclose($attachment_file);

        $mail = new PHPMailer;
        $mail->SMTPDebug = 0;                         // 3 to Enable verbose debug output
        $mail->isSMTP();                              // Set mailer to use SMTP
        $mail->Host = 'smtp.gmail.com';               // Specify main and backup SMTP servers
        $mail->SMTPAuth = true;                       // Enable SMTP authentication
        $mail->Username = 'eseifert.nmhu@gmail.com';  // SMTP username
        $mail->Password = 'P0l17t!cK';                // SMTP password
        $mail->SMTPSecure = 'tls';                    // Enable TLS encryption, `ssl` also accepted
        $mail->Port = 587;                            // TCP port to connect to
        
        // to allay certificate problems
        $mail->SMTPOptions = array(
            'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
            )
        );
        
        ////////////
        $mail->setFrom('eseifert.nmhu@gmail.com', 'Mailer');
        $mail->addAddress('eseifert@alumni.nmt.edu', 'L C');     // Add a recipient
        $mail->addAddress('cmonroe1@live.nmhu.edu', 'C M');     // Add a recipient
//        $mail->addReplyTo('info@example.com', 'Information');
//        $mail->addCC('cc@example.com');
//        $mail->addBCC('bcc@example.com');

//        $email->AddAttachment( "/path/to/file" , "filename.ext", 'base64', 'application/octet-stream' );
//        $mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
//        $mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name
        $mail->AddAttachment('attachment.txt');
        $mail->isHTML(false);                                  // Set email format to HTML

        $mail->Subject = 'Here is the subject';
        $mail->Body    = "This is a test attachment message. And it's being sent from a python script running on my laptop, calling the crct.php script on my desktop.";
//        $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

        if(!$mail->send()) {
            echo 'Message could not be sent.\n';
            echo 'Mailer Error: ' . $mail->ErrorInfo;
        } else {
            echo "Message has been sent.\n";
        }
//        $fh = fopen('attachment.txt','r');
//        echo "+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++";
//        while ($line = fgets($fh)) {
//            echo ($line);   // <... Do your work with the line ...>
//        }
//        echo "+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++";
//        fclose($fh);
        rename ('attachment.txt', 'attachment.bk'.date('U'));
        echo "DB Mailed\n";
    }

// Spit the DB info out to the page
    function readDB(){
        $return_val = db_select('select * from `myguests`');
        foreach ($return_val as $rv){
            echo implode(',',$rv)."";
            debug_to_console($rv);
        }
    }

// database functions
    // Query the database
    function db_query($query) {
        //an INSERT: mysqli_query($myDB,"INSERT INTO visitors (`id`, `v_in`, `v_out`, `t_stamp`) VALUES (null, 7, 10,null)") or die(mysqli_error($myDB));
        $myDB = db_connect();
        
        // Query the database
        $result = mysqli_query($myDB, $query);
        // 
        if($result === false) {
            $error = db_error();
        } else {
            return $result;
        }
    }

    // escape and quote string literals
    function db_quote($value) {
        return "'" . mysqli_real_escape_string($myDB,$value) . "'";
        // Usage: 
        //$name = db_quote($_POST['username']);
        //$email = db_quote($_POST['email']);
        //// Insert the values into the database
        //$result = db_query("INSERT INTO `users` (`name`,`email`) VALUES (" . $name . "," . $email . ")");
    }

    // handle error
    function db_error() {
        $connection = db_connect();
        return mysqli_error($connection);
    }

    // SELECT
    function db_select($query) {
        $rows = array();
        $result = db_query($query);

        // If query failed, return `false`
        if($result === false) {
            return false;
        }

        // If query was successful, retrieve all the rows into an array
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
        return $rows;
    }

//    function test_db_connection($conn){
//        if ($conn->connect_errno) {
//            echo("Connect failed: ".$conn->connect_error."");
//            exit();
//        } else {
//            echo ("Connected to database: ".$conn->host_info."");
//        }
//    }

    function debug_to_console( $data ) { 
        $output = $data;
        if ( is_array( $output ) )
            $output = implode( ',', $output);
        echo "<script>console.log( 'Debug Objects: " . $output . "' );</script>";
    }
?>