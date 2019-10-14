<?php include("includes/header.php");
?>
<?php include("includes/nav.php");
?>
	
<div class="container">


	<div class="jumbotron">
		<h1 class="text-center"> <?php display_message(); ?></h1>
	</div>

    <?php
    $sql = "SELECT * FROM users";
    $result =query($sql);

    confirm($result);

    $row = fetch_array($result);

    echo $row['first_name'];
    ?>
	
</div> <!--Container-->




	
<?php include ('includes/footer.php');?>