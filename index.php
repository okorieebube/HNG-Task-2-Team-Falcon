<?php
	// we'll capture the variable with regular expressions
	$template = "/^Hello World, this is ([\w'\-\s]+) with HNGi7 ID HNG-(\d{1,}) using ([\w*]+) for stage 2 task.\s([a-zA-Z0-9._-]+@[a-zA-Z0-9._-]+\.[a-zA-Z0-9_-]+)/";

	// a map of all the file types and the command to run them
	$supported_json = '{
		"py": "python",
		"js": "node",
		"php": "php",
		"rb": "irb",
		"java": "java",
		"kt": "kotlinc",
		"kts": "kotlinc",
		"dart": "dart"
	}'; # currently supported types should be updated
	$supported_map = json_decode($supported_json, true); # convert to json object in other to manipulate 

	# Retrive the runtime engine name
	function getRuntime($fileName) {;
		global $supported_map;

		$tokens = explode(".", $fileName); // split file name into [fileName, extension];
		if (isset($tokens[1])) {
			$ext = $tokens[1]; // extension
			if ($ext && isset($supported_map[strtolower($ext)])) {
				$runtime = $supported_map[strtolower($ext)]; // Get the name of the runtime from the map
				return $runtime;
			}
		}

		return null;
	}
 

	$path = "scripts"; // The folder we intend to read from
	$files = scandir($path); // We get all the files in the folder

	// counter variables
	$counter = 0;
	$totalCount = count($files) - 2; // exclude the current and previous working directory folders
	$failCount = 0;
	$passCount = 0;
?>

<?php
	$data =  array();

	$isJson = false;
	// check if the user wants a json response
	// if user doesn't want json, return the html
	if(isset($_SERVER["QUERY_STRING"])) {
	 	$queryStr = $_SERVER["QUERY_STRING"];
	 	$isJson = $queryStr == "json";
	}

	if ($isJson) {
		header("Content-Type: application/json"); // set the content type
		foreach ($files as $key => $fileName) { // loop through file

			$filePath = "./$path/$fileName"; // set the relative path of the file

			if (!is_dir($filePath)) { // skip folders
				$item = array(); // create a store to keep out processed files

				$runtime = getRuntime("$fileName"); // retrieve the command to run this file

				// echo $fileName;
				if ($runtime) {
					# Execute script and assign result and redirect some input into it to prevent files waiting for user input 
					$output;
					try {
						$output = shell_exec("$runtime $filePath 2>&1 << input.txt");
					} catch(Exception $e) {
						$output = null;
					}

					if (is_null($output)) {

						$item["status"] = "fail";
						$item["output"] = "%> script produced no output";
						$item["name"] = $fileName;

					} else {

						// match the output of the file to the format we expect and extract our information 
						if (preg_match($template, $output, $matches)) {
							$item["status"] = "pass";
							$item["output"] = $matches[0];
							$item["name"] = $matches[1];
							$item["id"] = $matches[2];
							$item["language"] = $matches[3];
							$item["email"] = $matches[4];
						} else {
							$item["status"] = "fail";
							$item["output"] = $output;
							$item["name"] = $fileName;
						}
						$item["fileName"] = $fileName;
					}
				} else {
					// At this point the server cannot run the file
					$item["name"] = $fileName;
					$item["output"] = "%> File type not supported";
					$item["status"] = "fail";
				}

				array_push($data, $item); // add our item to our item store.
			}
		}
		echo json_encode($data); // send it back to the user
		// Since our user wants a json response we should prevent php
		// from proceeding further to return the html format.
		// so we call the die function in other to kill it. :(
		die();
	}
?>

<!-- At this point the user didn't request of a json response -->
<!-- We we do the same thing as earlier. But this time add the items to a html table instead -->
<!DOCTYPE html>
<html>
	<head>
		<title>Team Falcon</title>
	</head>
		<body>
			<div class=container>
			<h1 class="text-center">Team Falcon</h1>
			<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous">
			<script src="https://code.jquery.com/jquery-3.5.1.min.js" integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
			<table class="table">
				<thead>
					<tr class="text-center">
						<th scope="col">Submissions</th>
						<th scope="col">Pass</th>
						<th scope="col">Fail</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td class="col-4 table-info text-center">
							<span id="totalCount">
								<?php echo $totalCount; ?>
							</span>
						</td>
						<td class="col-4 table-success text-center">
							<span id="passCount">
								<?php echo $passCount; ?>
							</span>
						</td>
						<td class="col-4 table-danger text-center">
							<span id="failCount">
								<?php echo $failCount; ?>
							</span>
						</td>
					</tr>
				</tbody>
			</table>
			<table class="table table-bordered table-hover">
				<thead class="thead-dark">
					<tr>
						<th scope="col">#</th>
						<th scope="col">Name</th>
						<th scope="col">Output</th>
						<th scope="col">Status</th>
					</tr>
				</thead>
				<tbody>
					<?php

						foreach ($files as $key => $fileName) {
							$filePath = "./$path/$fileName";

							if (!is_dir($filePath)) {
							$item = array();

							$runtime = getRuntime("$fileName");

							// echo $fileName;
							if ($runtime) {
								$output = null;
								try {
									$output = shell_exec("$runtime $filePath 2>&1 << input.txt"); # Execute script and assign result
								} catch(Exception $e) {
									$output = null;
								}
								if (is_null($output)) {

									$item["status"] = "fail";
									$item["output"] = "%> script produced no output";
									$item["name"] = $fileName;

								} else {

									if (preg_match($template, $output, $matches)) {
										$item["status"] = "pass";
										$item["output"] = $matches[0];
										$item["name"] = $matches[1];
										$item["id"] = $matches[2];
										$item["language"] = $matches[3];
										$item["email"] = $matches[4];
										$passCount++;
									} else {
										$item["status"] = "fail";
										$item["output"] = "%> ".substr($output, 0, 200);
										$item["name"] = $fileName;
										$failCount++;
									}
									$item["fileName"] = $fileName;
								}
							} else {
								$item["name"] = $fileName;
								$item["output"] = "%> File type not supported";
								$item["status"] = "fail";
								$failCount++;
							}

							$name = $item["name"];
							$response = htmlspecialchars($item["output"]);
							$status = strtoupper($item["status"]);

							$failed = $item["status"] == "fail";
							$class = $failed ? "text-danger" : "'text-success'";
							$code = $failed ? "text-danger" : "text-black";
							$counter++;

							echo <<<EOL
								<tr>
									<th scope=row>$counter</th>
									<td class=$class>$name</td>
									<td><samp class=$code>$response</samp></td>
									<td class=$class>$status</td>
								</tr>
							EOL;

							# We dynamically update the table counter elements
							if ($failed) {
								echo <<<EOL
									<script>
										$('#passCount').text($passCount);
									</script>
								EOL;
							} else {
								echo <<<EOL
									<script>
										$('#failCount').text($failCount);
									</script>
								EOL;
							}
						}
					}
				?>
			</tbody>
		</table>
		</div>
	</body>
</html>
