<?php

	$template = "/^Hello World, this is ([\w'\-\s]+) with HNGi7 ID HNG-(\d{1,}) using ([\w*]+) for stage 2 task.\s([a-zA-Z0-9._-]+@[a-zA-Z0-9._-]+\.[a-zA-Z0-9_-]+)/";

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
	$supported_map = json_decode($supported_json, true); # convert to json object to work with

	# Retrive the runtime engine name
	function getRuntime($fileName) {;
		global $supported_map;

		$tokens = explode(".", $fileName); // split file name into [fileName, extension];
		if (isset($tokens[1])) {
			$ext = $tokens[1]; // extension
			if ($ext && isset($supported_map[strtolower($ext)])) {
				$runtime = $supported_map[strtolower($ext)]; // Get the name of the runtime
				return $runtime;
			}
		}

		return null;
	}
 

	$path = "scripts";
	$files = scandir($path);

	$counter = 0;
	$totalCount = count($files) - 2;
	$failCount = 0;
	$passCount = 0;
?>

<?php
	$data =  array();

	$isJson = false;
	if(isset($_SERVER["QUERY_STRING"])) {
	 	$queryStr = $_SERVER["QUERY_STRING"];
	 	$isJson = $queryStr == "json";
	}

	if ($isJson) {
		header("Content-Type: application/json");
		foreach ($files as $key => $fileName) {

			$filePath = "./$path/$fileName";

			if (!is_dir($filePath)) {
				$item = array();

				$runtime = getRuntime("$fileName");

				// echo $fileName;
				if ($runtime) {
					$output = shell_exec("$runtime $filePath 2>&1 << input.txt"); # Execute script and assign result
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
						} else {
							$item["status"] = "fail";
							$item["output"] = $output;
							$item["name"] = $fileName;
						}
						$item["fileName"] = $fileName;
					}
				} else {
					$item["name"] = $fileName;
					$item["output"] = "%> File type not supported";
					$item["status"] = "fail";
				}

				array_push($data, $item);
			}
		}
		echo json_encode($data);
		die();
	}
?>


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
								$output = shell_exec("$runtime $filePath 2>&1 << input.txt"); # Execute script and assign result
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
