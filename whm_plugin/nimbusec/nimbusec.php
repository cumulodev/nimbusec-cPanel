<html>
<head>
<title>nimbusec - WHM / cPanel plugin</title>
<!-- Cpanel libraries -->
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="stylesheet" type="text/css" href="/combined_optimized.css" />
<link rel="stylesheet" type="text/css" href="/themes/x/style_optimized.css" />
<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.4/jquery.min.js"></script>
<!-- Latest compiled and minified CSS -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css">
<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/bootstrap-table/1.8.1/bootstrap-table.min.css">
<!-- Latest compiled and minified JavaScript -->
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/bootstrap-table/1.8.1/bootstrap-table.min.js"></script>
<!-- Nimbusec WHM Plugin CSS -->
<link rel="stylesheet" href="whm-plugin.css?version=2.0">

<!-- jQuery code beginning -->
<script>
// Exlude definitions and declaration of variables and functions form the $(document).ready event to keep the code cleaner
// Const is only supported in ECMA 6 (not yet implemented in all modern browsers)
CONTROLLER_PATH = "controller.php";

// Most icons are used from font-awesome as their icons are more flexible and scaleable
STATUS = {
	SUCCESS: {alertClass: "success", icon: "check", prefix: "Success"},
	INFO: {alertClass: "info", icon: "info", prefix: "Info"},
	WARNING: {alertClass: "warning", icon: "exclamation", prefix: "Warning"},
	ERROR: {alertClass: "danger", icon: "times", prefix: "Error"}
};

// .find() is a bit faster than children (using native DOM functions)
// saving elements in var and using it afterwards is faster than selecting the same element multiple times (like I used to)
// .. but i prefer chaining (looks cleaner)
// http://stackoverflow.com/questions/4453707/are-long-jquery-chains-bad

/**
 * This method serves as a helper method showing 'status' messages in a more appealing way
 * by modify the status blocks defined in each tab
 */
function displayStatus(element, status, message){
	$("#" + element).prop("class", "alert alert-" + status.alertClass)
		.find("i")
			.prop("class", "fa fa-" + status.icon + "-circle")
			.end()
		.find("strong")
			.html(status.prefix + ":")
			.end()
		.find("p")
			.html(message);
}

/**
 * This method is used as the initial preparer on whose results the form is modified by
 * It calls the server demanding to know whether the nimbusec plugin is already installed
 *
 * If so, then it enables the Bundle tab and executes futher modifications by passing the result to
 * a method which is responsible for the actual modification
 */
function init(){

	// Animate loading icon
	$("#statusLabel").prop("class", "label label-primary nimbu-status-label").html('<i class="fa fa-spinner fa-spin"><i>').append("&nbsp; Loading...");

	// Call server
	$.ajax({

		url: CONTROLLER_PATH,
		data: {
			action: "initialize"
		},
		type: "POST",
		dataType: "json"

	})
	// No need to call the constructor of the method so skip the method brackets
	.done(setupPage)
	.fail(function( xhr, status, errorThrown ) {
		var msg = "Status: " + status + "<br />" + "Error thrown: "+ errorThrown + "<br />" + "XHR: " + xhr;
		displayStatus("statusMessage", STATUS.ERROR, msg);
    });
}

// ############################### Start --> document.ready ###############################
$(document).ready(function () {

	// Initialize the page
	init();

	/**
	 * When the settings tab is clicked
	 */
	$("#settingsLink").on('click', function(){

		$("#statusMessage").removeAttr("class")
			.find("i")
				.prop("class", "")
				.end()
			.find("strong")
				.empty()
				.end()
			.find("p")
				.empty();
	});

	/**
	 * When the update tab is clicked
	 */
	$("#updateLink").on('click', function(){

		$("#updateStatusMessage").removeAttr("class")
			.find("i")
				.prop("class", "")
				.end()
			.find("strong")
				.empty()
				.end()
			.find("p")
				.empty();
	});

	/**
	 * When the users tab is clicked
	 */
	$("#usersLink").on('click', function(){
	});

	$('#packages').on('click', '.nimbu-package-link', function(){

		var packageLink = $(this);

		// Collapse panel
		packageLink.parent().parent().children('div:last').collapse('toggle');

		// Make fancy rotation
		var rotationDegree = parseInt(packageLink.attr("degree"));
		rotationDegree += 180;

		packageLink.find("span")
		.css("transform", "rotate(" + rotationDegree + "deg)")
		.css("-moz-transform", "rotate(" + rotationDegree + "deg)")
		.css("-webkit-transform", "rotate(" + rotationDegree + "deg)");

		packageLink.attr("degree", rotationDegree);
	});

	$("#installation").on('click', installation);
	$("#uninstallation").on('click', uninstallation);
	$("#updateBundles").on('click', updateBundles);

	// Prevent redirect
	$("#settingsForm").on('submit', function(e) { e.preventDefault(); } );
});


function updateBundles(e){

	// Prevent from redirect
	e.preventDefault();

	// Display output
	displayStatus("updateStatusMessage", STATUS.INFO, "Starting update...");

	// Call ajax
	$.ajax({
		url: CONTROLLER_PATH,
		data: {
			action: "update"
		},
		type: "POST",
		dataType: "json"

	}).done(function(res){

		if(res.status){
			displayStatus("updateStatusMessage", STATUS.SUCCESS, res.content);
			setupBundleTable();
			setupUsersPage();
		}else{
			displayStatus("updateStatusMessage", STATUS.WARNING, res.content);
		}

	}).fail(function(xhr, status, errorThrown){

		var msg = "Status: " + status + "<br />" + "Error thrown: "+ errorThrown + "<br />" + "XHR: " + xhr;
		displayStatus("updateStatusMessage", STATUS.ERROR, msg);

	});
}
function installation(e){

	// Prevent from redirect
	e.preventDefault();

	// Build serializeString
	var id = $(this).attr("id");
	var params = $("#settingsForm").serialize() + "&action=" + id;

	// Display output
	displayStatus("statusMessage", STATUS.INFO, "Installation of the nimbusec plugin will be started...");

	// Call ajax
	executeProcess(params);
}
function uninstallation(e){

	// Prevent from redirect
	e.preventDefault();

	// Build serializeString
	var id = $(this).attr("id");
	var params = "action=" + id;

	// Display output
	displayStatus("statusMessage", STATUS.INFO, "Uninstallation of the nimbusec plugin will be started...");

	// Call ajax
	executeProcess(params);
}

/**
 * The actual method which performs ajax calls for both the installation and uninstallation
 */
function executeProcess(params){

	// Setup ajax
	// Could also be save in a varable more flexible
	// Nested callbacks like success and error are more or less deprecated since jQuery 1.8
	$.ajax({

		url: CONTROLLER_PATH,
	    data: params,
	    type: "POST",
	    dataType: "json"

	}).done(function(res) {

		var msg = (res.content instanceof Array) ? res.content.join("<br />") : res.content;

		if(res.status){
			displayStatus("statusMessage", STATUS.SUCCESS, msg); init();
	    }else
			displayStatus("statusMessage", STATUS.ERROR, msg);

    }).fail(function( xhr, status, errorThrown ) {

		var msg = "Status: " + status + "<br />" + "Error thrown: "+ errorThrown + "<br />" + "XHR: " + xhr;
		displayStatus("statusMessage", STATUS.ERROR, msg);
    });
}

// ############################# Other methods ####################################
var toggleTabAnimation = function(){
	$(this).find('i').toggleClass( "fa-spin" );
}

// Will be called when the page is loaded, initializing all elements depending on existing conditiions( installed / not installed )
function setupPage(res){
	if(res.status){

		// Deferred objects with $.when are a good way to ensure that everything will be enabled / activated only when the ajax calls are done

		// Input
		// .val can be chained with .prop (returns obj from type jQuery)
		$("#apiKey").val(res.content.key).prop("disabled", true);
		$("#apiSecret").val(res.content.secret).prop("disabled", true);
		$("#apiServer").prop("disabled", true);

		// As from jQuery documentation for $.when:
		// Provides a way to execute callback functions based on one or more objects, usually Deferred objects that represent asynchronous events.
		//
		// The executing ajax calls in both methods gonna return a Promise object (a subset of Deferred) that indicates, has it worked or not
		// Only when both methods have worked when will continue with the .then callback
		$.when(setupBundleTable(), setupUsersPage()).done(function(){

			// Status
			// Note: Val works only with elements like input, select... (elements which may have a value attr)
			// The .prop() method should be used to set disabled and checked instead of the .attr() method. The .val() method should be used for getting and setting value.
			$("#statusLabel").prop("class", "label label-success nimbu-status-label").html("Installed");

			// Buttons
			$("#installation").prop("disabled", true);
			$("#uninstallation").prop("disabled", false);

			// Tabs
			// Multiple selectors ;)
			$("#updateTab, #usersTab").prop("class", "");
			$("#updateLink, #usersLink").attr("data-toggle", "tab");

			// Add event handler for animation
			// .hover makes the same as: "$( selector ).on( "mouseenter mouseleave", handlerInOut );""
			$("#settingsTab, #updateTab").hover(toggleTabAnimation);
		});

	}else{

		// Remove event bindings
		// Therefore a need to unbind both events
		$("#settingsTab, #updateTab").off('mouseenter mouseleave', toggleTabAnimation);

		// Buttons
		$("#installation").prop("disabled", false);
		$("#uninstallation").prop("disabled", true);

		// Input
		$("#apiKey").val("").prop("disabled", false);
		$("#apiSecret").val("").prop("disabled", false);
		$("#apiServer").prop("disabled", false);

		// Tabs
		$("#updateTab, #usersTab").prop("class", "disabled");
		$("#updateLink, #usersLink").attr("data-toggle", "");

		// Status
		$("#statusLabel").prop("class", "label label-warning nimbu-status-label").html("Not installed");
	}
}

// Will be called whenever the update tab is clicked, loading all bundle and setting up the table
function setupBundleTable(){

	// The ajax call will return a Promise object (a subset of Deferred) that indicates whether the ajax call worked or failed
	return $.ajax({

		url: CONTROLLER_PATH,
		data: {
			action: "retrieveTableData"
		},
		type: "POST",
		dataType: "json"

	}).done(function(res){

		if(res.status){
			$("#bundleTable").hide();
			$("#bundleTable").bootstrapTable({
			    columns: [{
			        field: "id",
			        title: "Bundle ID"
			    }, {
			        field: "name",
			        title: "Bundle Name"
			    }],
			    data: res.content
			});
			$("#bundleTable").fadeIn();

		}else
			displayStatus("updateStatusMessage", STATUS.WARNING, res.content);

	}).fail(function( xhr, status, errorThrown ) {
		var msg = "Status: " + status + "<br />" + "Error thrown: "+ errorThrown + "<br />" + "XHR: " + xhr;
		displayStatus("updateStatusMessage", STATUS.ERROR, msg);
    });
}

function setupUsersPage(){

	// The ajax call will return a Promise object (a subset of Deferred) that indicates whether the ajax call worked or failed
	return $.ajax({

		url: CONTROLLER_PATH,
		data: {
			action: "retrieveUsers"
		},
		type: "POST",
		dataType: "json"

	}).done(function(res){

		if(res.status){

			res.content.forEach(function(entry){

				// Topmost panel
				var panel_default = $('<div>', { class: "panel panel-default" });

				// Head
				var panel_heading = $('<div>', { class: "panel-heading" });

				// Package head
				// Had to use span as jQuery doesn't add <i> tags that way
				var nimbu_package_head = $('<div>', { class: "nimbu-package-head" });
				$('<div><span></span><h4></h4><div><div></div><div></div></div></div>')
								.addClass('nimbu-package-head')
								.find('span')
									.addClass('fa fa-archive fa-2x')
									.end()
								.find('h4')
									.html("&nbsp;" + entry.packageName)
									.end()
								.find('div')
									.addClass('nimbu-head-bundle-data')
									.children('div:first')
										.addClass('nimbu-head-bundle-box')
										.html('Bundle: ' + entry.bundleName)
										.end()
									.children('div:last')
										.html('ID: ' + entry.bundleID)
										.end().end()
				.appendTo(nimbu_package_head);

				// Package link
				var nimbu_package_link = $('<div>', { class: "nimbu-package-link", degree: 0 });
				$('<span></span>')
						.addClass("fa fa-arrow-up fa-1x nimbu-package-icon")
						.css("transform", "rotate(0deg)")
						.css("-moz-transform", "rotate(0deg)")
						.css("-webkit-transform", "rotate(0deg)")
				.appendTo(nimbu_package_link);

				// Appending package to head
				nimbu_package_head.appendTo(panel_heading);
				nimbu_package_link.appendTo(panel_heading);
				// Append head to the topmost panel
				panel_heading.appendTo(panel_default);

				// Body
				var nimbu_collapse = $('<div>', { class: "panel-collapse collapse in" });
				$('<div></div>')
						.addClass("panel-body");

				if(entry.hasOwnProperty('users') && entry.users.length > 0){
					var usersTable = $('<table>', { class: "nimbu-users-table", style: "margin-bottom: 20px" });// Load users table
					usersTable.bootstrapTable({
						columns: [{
							field: "email",
							title: "Mail address"
						}, {
							field: "domain",
							title: "Domain"
						}, {
							field: "name",
							title: "Username"
						}],
						data: entry.users,
						sortable: true
					});
					usersTable.appendTo(nimbu_collapse);
				}else
					nimbu_collapse.append(
						$('<div>', { class: "nimbu-users-table"}).html("No users found using this package."));

				// Append body to topmost panel
				nimbu_collapse.appendTo(panel_default);

				// Append panel
				panel_default.appendTo($('#packages'));
			});

		}else
			$('#packages').html(res.content);
	});
}
</script>
<?php $cptoken = $_ENV ["cp_security_token"]; ?>
</head>
<body>
	<div class="nimbu-masterContainer">
		<div id="navigation">
			<div id="breadcrumbsContainer">
				<ol id="breadcrumbs_list" class="breadcrumbs">
				    <li>
				        <a href="<?php echo $cptoken; ?>/scripts/command?PFILE=main">
						<!-- Have to keep the glyphicon as font awesome's is to small -->
				            <span class="glyphicon glyphicon-home"></span>
							<span class="imageNode">Home</span></a><span>&nbsp;&nbsp;»&nbsp;</span>
				    </li>
				    <li><a uniquekey="plugins" href="<?php echo $cptoken; ?>/scripts/command?PFILE=Plugins" class="leafNode">
				            <span class="nimbu-breadcrumbs"><img border="0" alt="Plugins" src="/themes/x/icons/plugin_v2.gif"></span><span>&nbsp;Plugins</span></a><span>&nbsp;&nbsp;»</span>
					</li>
				    <li>
						<a href="<?php echo $_SERVER["SCRIPT_NAME"]?>" class="leafNode">
							<span class="nimbu-breadcrumbs"><img src="images/nimbusec6464.png" style="width:20px; height: 20px"></span><span>&nbsp;nimbusec WHM / cPanel Plugin</span>
				        </a>
					</li>
				</ol>
			</div>
		</div>
		<div class="nimbu-mainContainer">
			<h1 class="page-header">
				<span class="headerPic"><img src="images/nimbusec6464.png" height="64" width="64" class="img-rounded" alt="Nimbusec"></span>
				nimbusec WHM / cPanel Plugin
			</h1>
			<section class="nimbu-introductionContainer">
			    nimbusec is an Austrian-based cloud service with headquaters in Linz.
				It monitors external webspaces and domains for malware, defacement and blacklisting.
				In addition, nimbusec works with a highly specialized server agent that can detect webshells and malware on your system.
				If potential tampering is detected, our alarm response center will notify you immediately via email or SMS.
			</section>
			<ul class="nav nav-tabs" id="navTabs" data-tabs="tabs">
		        <li id="settingsTab" class="active"><a href="#settings" id="settingsLink" data-toggle="tab"><i class="fa fa-cog"></i>&nbsp;Settings</a></li>
		        <li id="updateTab" class="disabled"><a href="#update" id="updateLink" data-toggle=""><i class="fa fa-refresh"></i>&nbsp;Update</a></li>
				<li id="usersTab" class="disabled"><a href="#users" id="usersLink" data-toggle=""><i class="fa fa-users"></i>&nbsp;Users</a></li>
	    	</ul>
		    <div class="tab-content">
				<div id="settings" class="tab-pane fade in active">
					<h3>Install / uninstall the nimbusec plugin</h3>
					<blockQuote style="font-size: 17px">
						<span>Current status:</span>
						<!--<span id="installationStatus" class="label label-primary nimbu-status-label">
							<i class="fa fa-spinner fa-spin"></i>
							&nbsp;Loading...
						</span>-->
						<span id="statusLabel">
						</span>
					</blockQuote>
					<div id="statusBlock">
						<div id="statusMessage">
							<i></i>
							<strong></strong>
							<p></p>
						</div>
					</div>
					<div class="panel panel-default">
						<div class="panel-heading"><h5>Enter your credentials here to <b>install</b> nimbusec or <b>uninstall</b> nimbusec<h5></div>
	        			<div class="panel-body">
							<div class="nimbu-installContainer">
								<!-- Works when set as action attr (instead of submit handler) -> action="javascript:alert(0);" -->
					            <form class="form-horizontal" id="settingsForm">
							        <div class="form-group">
							            <label class="control-label col-xs-1">API Key*</label>
							            <div class="col-xs-12 col-lg-4 nimbu-awe-input-field">
											<i class="fa fa-key"></i>
							                <input type="text" class="form-control" name="apiKey" id="apiKey" placeholder="Enter your API Key here">
							            </div>
							        </div>
							        <div class="form-group">
							            <label class="control-label col-xs-1">API Secret*</label>
							            <div class="col-xs-12 col-lg-4 nimbu-awe-input-field">
											<i class="fa fa-lock"></i>
							                <input type="text" class="form-control" name="apiSecret" id="apiSecret" placeholder="Enter your API Secret here">
							            </div>
							        </div>
							        <div class="form-group">
							            <label class="control-label col-xs-1">API Server</label>
											<div class="col-xs-12 col-lg-4">
												<div class="input-group nimbu-input-group">
								                    <span class="input-group-addon">https://</span>
													<input type="text" class="form-control" name="apiServer" id="apiServer" placeholder="api.nimbusec.com">
								                </div>
							            	</div>
						            </div>
									<div class="col-xs-offset-1 col-xs-0">
						                	<button type="submit" class="btn btn-primary" id="installation" disabled>Install nimbusec</button>
											<button type="submit" class="btn btn-primary" id="uninstallation" disabled>Uninstall nimbusec</button>
						            </div>
						    	</form>
							</div>
						</div>
					</div>
	        	</div>
		        <div id="update" class="tab-pane fade nimbu-updateContainer">
		            <div class="nimbu-updateTitleContainer">
						<img src="images/bundle.png" style="width:25px; height: 25px">
						<h3>&nbsp;Your nimbusec bundles</h3>
					</div>
					<div id="updateStatusBlock">
						<div id="updateStatusMessage">
							<i></i>
							<strong></strong>
							<p></p>
						</div>
					</div>
					<table id="bundleTable"></table>
					<div class="col-xs-offset-0 col-xs-0">
		                	<button type="submit" class="btn btn-primary" id="updateBundles">Update your nimbusec bundles</button>
		            </div>
		        </div>
				<div id="users" class="tab-pane fade">
					<div class="nimbu-users-title-container"><h3>See all users which have nimbusec included</h3></div>
					<div class="panel-group" id="packages"> <!--nimbu-packages-->
					</div>
				</div>
			</div>
		</div>
	</div>
</body>
</html>